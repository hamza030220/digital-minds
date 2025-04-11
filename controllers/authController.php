<?php
/**
 * Auth Controller
 * 
 * Handles authentication, login, logout, and password reset
 */
class AuthController extends Controller {
    /**
     * User model instance
     * @var UserModel
     */
    private $model;
    
    /**
     * Constructor - initialize model
     */
    public function __construct() {
        // Call parent constructor
        parent::__construct();
        
        // Initialize the user model
        require_once(__DIR__ . '/../includes/config.php');
        require_once(BASE_PATH . '/models/UserModel.php');
        
        $this->model = new UserModel();
    }
    
    /**
     * Display the login form
     *
     * @return void
     */
    public function loginFormAction() {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('');
        }
        
        // Render the login view
        $this->render('login');
    }
    
    /**
     * Process login form
     *
     * @param array $data POST data
     * @return void
     */
    public function loginAction($data = []) {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('');
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize input data
            $data = $this->sanitize($data);
            
            // Validate required fields
            if (!$this->validateRequired($data, ['username', 'password'])) {
                $this->setMessage('Error: ' . $this->getError(), 'danger');
                $this->render('login', ['username' => $data['username'] ?? '']);
                return;
            }
            
            // Attempt to authenticate user
            $user = $this->model->authenticate($data['username'], $data['password']);
            
            if ($user) {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Redirect to dashboard
                $this->setMessage('Login successful', 'success');
                $this->redirect('');
            } else {
                // Authentication failed
                $this->setMessage('Error: ' . $this->model->getError(), 'danger');
                $this->render('login', ['username' => $data['username']]);
            }
        } else {
            // Display login form
            $this->loginFormAction();
        }
    }
    
    /**
     * Process logout
     *
     * @return void
     */
    public function logoutAction() {
        // Clear session data
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['last_activity']);
        
        // Destroy the session
        session_destroy();
        
        // Restart session to store message
        session_start();
        
        // Set logout message
        $this->setMessage('You have been logged out successfully', 'info');
        
        // Redirect to login page
        $this->redirect('login');
    }
    
    /**
     * Display password reset request form
     *
     * @return void
     */
    public function resetFormAction() {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('');
        }
        
        // Render the password reset request form
        $this->render('reset_password_request');
    }
    
    /**
     * Process password reset request
     *
     * @param array $data POST data
     * @return void
     */
    public function resetAction($data = []) {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('');
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize input data
            $data = $this->sanitize($data);
            
            // Validate email
            if (empty($data['email'])) {
                $this->setMessage('Error: Email is required', 'danger');
                $this->render('reset_password_request');
                return;
            }
            
            // Generate reset token
            $token = $this->model->generateResetToken($data['email']);
            
            if ($token) {
                // In a real application, send an email with the reset link
                // For this implementation, we'll just display the token
                
                // Store email in session for verification
                $_SESSION['reset_email'] = $data['email'];
                
                // Redirect to token confirmation page
                $this->redirect('reset_password/confirm?token=' . $token);
            } else {
                // If email is not found, show generic message for security
                $this->setMessage('If your email is registered, you will receive reset instructions', 'info');
                $this->render('reset_password_request');
            }
        } else {
            // Display reset form
            $this->resetFormAction();
        }
    }
    
    /**
     * Handle reset token verification
     *
     * @param array $params URL parameters
     * @return void
     */
    public function resetConfirmAction($params) {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('');
        }
        
        // Validate token parameter
        if (!isset($params['token']) || empty($params['token'])) {
            $this->setMessage('Invalid reset token', 'danger');
            $this->redirect('login');
        }
        
        // Check if email is stored in session
        if (!isset($_SESSION['reset_email'])) {
            $this->setMessage('Password reset session expired', 'danger');
            $this->redirect('login');
        }
        
        // Verify token
        $userId = $this->model->verifyResetToken($_SESSION['reset_email'], $params['token']);
        
        if ($userId) {
            // Token is valid, show password reset form
            $this->render('reset_password_form', [
                'token' => $params['token'],
                'email' => $_SESSION['reset_email']
            ]);
        } else {
            // Invalid token
            $this->setMessage('Invalid or expired reset token', 'danger');
            $this->redirect('login');
        }
    }
    
    /**
     * Process new password submission
     *
     * @param array $data POST data
     * @return void
     */
    public function resetPasswordAction($data = []) {
        // If user is already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('');
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize input data
            $data = $this->sanitize($data);
            
            // Validate required fields
            if (!$this->validateRequired($data, ['email', 'token', 'password', 'confirm_password'])) {
                $this->setMessage('Error: ' . $this->getError(), 'danger');
                $this->render('reset_password_form', [
                    'token' => $data['token'],
                    'email' => $data['email']
                ]);
                return;
            }
            
            // Validate password match
            if ($data['password'] !== $data['confirm_password']) {
                $this->setMessage('Error: Passwords do not match', 'danger');
                $this->render('reset_password_form', [
                    'token' => $data['token'],
                    'email' => $data['email']
                ]);
                return;
            }
            
            // Verify token again
            $userId = $this->model->verifyResetToken($data['email'], $data['token']);
            
            if (!$userId) {
                $this->setMessage('Invalid or expired reset token', 'danger');
                $this->redirect('login');
                return;
            }
            
            // Reset password
            $result = $this->model->resetPassword($data['email'], $data['password']);
            
            if ($result) {
                // Password reset successful
                $this->setMessage('Password has been reset successfully. You can now log in with your new password', 'success');
                
                // Clear reset session data
                unset($_SESSION['reset_email']);
                
                $this->redirect('login');
            } else {
                // Password reset failed
                $this->setMessage('Error: ' . $this->model->getError(), 'danger');
                $this->render('reset_password_form', [
                    'token' => $data['token'],
                    'email' => $data['email']
                ]);
            }
        } else {
            // Redirect to login if direct access
            $this->redirect('login');
        }
    }
    
    /**
     * Route the request to the appropriate action
     *
     * @param string $action Action name
     * @param array $params Request parameters
     * @return void
     */
    public function route($action = 'login', $params = []) {
        switch ($action) {
            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loginAction($_POST);
                } else {
                    $this->loginFormAction();
                }
                break;
                
            case 'logout':
                $this->logoutAction();
                break;
                
            case 'reset_password':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->resetAction($_POST);
                } else {
                    $this->resetFormAction();
                }
                break;
                
            case 'reset_password_confirm':
                $this->resetConfirmAction($params);
                break;
                
            case 'reset_password_submit':
                $this->resetPasswordAction($_POST);
                break;
                
            default:
                // Default to login form
                $this->loginFormAction();
                break;
        }
    }
}
