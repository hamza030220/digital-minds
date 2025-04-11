<?php
/**
 * Base Controller Class
 * 
 * Provides common functionality for all controllers in the application.
 */
abstract class Controller {
    /**
     * Base URL for the application
     * @var string
     */
    protected $baseUrl = '/green-admin-mvc';
    
    /**
     * Database connection
     * @var PDO
     */
    protected $db;
    
    /**
     * Error message
     * @var string
     */
    protected $error;
    
    /**
     * Constructor - initialize common properties
     */
    public function __construct() {
        // Initialize database connection if needed
        try {
            $this->db = getDBConnection();
        } catch (PDOException $e) {
            $this->error = "Database connection failed: " . $e->getMessage();
            error_log($this->error);
        }
    }
    
    /**
     * Render a view file with the provided data
     * 
     * @param string $view Path to the view file
     * @param array $data Data to be passed to the view
     * @return void
     */
    protected function render($view, $data = []) {
        // Extract data to make variables available in the view
        extract($data);
        
        // Include the view file
        $viewPath = BASE_PATH . '/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->setMessage('View file not found: ' . $view, 'danger');
            throw new Exception('View file not found: ' . $viewPath);
        }
    }
    
    /**
     * Set a session message
     * 
     * @param string $message Message text
     * @param string $type Message type (success, danger, info, warning)
     * @return void
     */
    protected function setMessage($message, $type = 'info') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    
    /**
     * Get and clear the session message
     * 
     * @return array|null Message data or null if no message
     */
    protected function getMessage() {
        if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
            $message = [
                'text' => $_SESSION['message'],
                'type' => isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'
            ];
            
            // Clear the message
            $_SESSION['message'] = '';
            $_SESSION['message_type'] = '';
            
            return $message;
        }
        
        return null;
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect($url) {
        header("Location: {$this->baseUrl}/{$url}");
        exit();
    }
    
    /**
     * Send a JSON response
     * 
     * @param mixed $data Data to be encoded as JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Check if user is logged in, redirect if not
     * 
     * @param string $redirectTo URL to redirect to if not logged in
     * @return bool True if logged in, redirects otherwise
     */
    protected function requireLogin($redirectTo = 'login') {
        if (!isset($_SESSION['user_id'])) {
            $this->setMessage('Please log in to access this page', 'warning');
            $this->redirect($redirectTo);
            return false;
        }
        return true;
    }
    
    /**
     * Check if the current request is a POST request
     * 
     * @return bool True if POST request, false otherwise
     */
    protected function isPostRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Validate that required fields are present in the data
     * 
     * @param array $data Data to validate
     * @param array $requiredFields List of required field names
     * @return bool True if all required fields are present, false otherwise
     */
    protected function validateRequired($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->error = "Field '{$field}' is required";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Sanitize input data to prevent XSS
     * 
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get error message
     * 
     * @return string Error message
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Handle routing to different actions
     * 
     * This method must be implemented by child controllers to handle
     * routing requests to the appropriate action methods.
     * 
     * @param string $action Action name
     * @param array $params Parameters
     * @return void
     */
    abstract public function route($action, $params = []);
}
