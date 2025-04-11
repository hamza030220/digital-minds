<?php
/**
 * Front Controller for Green Admin MVC
 * 
 * This file serves as the main entry point for all requests
 * It handles initialization, routing, and security
 * 
 * @package GreenAdmin
 * @version 1.0
 */

// Define base path constant for easier file inclusion
define('BASE_PATH', dirname(__DIR__));

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// Include base controller class
require_once BASE_PATH . '/includes/Controller.php';

// Set up error reporting based on environment
// In production, error_reporting would be set to minimal in config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS-only
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Define application URL for routing
define('APP_URL', '/green-admin-mvc');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Invalid CSRF token
        http_response_code(403);
        exit('CSRF token validation failed');
    }
}

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Session has expired, destroy it
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['message'] = 'Your session has expired. Please log in again.';
    $_SESSION['message_type'] = 'warning';
    
    // Redirect to login if not already on login page
    $requestUri = $_SERVER['REQUEST_URI'];
    if (strpos($requestUri, '/login') === false && strpos($requestUri, '/reset_password') === false) {
        header('Location: ' . APP_URL . '/login');
        exit();
    }
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

/**
 * Simple autoloader for models and controllers
 */
spl_autoload_register(function($className) {
    // Convert class name to file path
    if (substr($className, -10) === 'Controller') {
        $file = BASE_PATH . '/controllers/' . strtolower(substr($className, 0, -10)) . 'Controller.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    } elseif (substr($className, -5) === 'Model') {
        $file = BASE_PATH . '/models/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Parse the URL to determine controller and action
// Default to dashboard
$controller = 'dashboard';
$action = 'index';
$params = [];

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string if present
if (strpos($requestUri, '?') !== false) {
    $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
}

// Extract path from request URI
if (strpos($requestUri, APP_URL) === 0) {
    $requestUri = substr($requestUri, strlen(APP_URL));
}

// Remove leading and trailing slashes
$requestUri = trim($requestUri, '/');

// If URI is not empty, parse it
if (!empty($requestUri)) {
    // Split the URI into segments
    $segments = explode('/', $requestUri);
    
    // First segment is the controller
    if (!empty($segments[0])) {
        $controller = $segments[0];
    }
    
    // Second segment is the action
    if (!empty($segments[1])) {
        $action = $segments[1];
    }
    
    // Additional named parameters (format: /param/value)
    for ($i = 2; $i < count($segments); $i += 2) {
        if (isset($segments[$i + 1])) {
            $params[$segments[$i]] = $segments[$i + 1];
        }
    }
}

// Merge $_GET into params
$params = array_merge($params, $_GET);

// Sanitize all parameters
$sanitizedParams = [];
foreach ($params as $key => $value) {
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $sanitizedParams[$key][$k] = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        }
    } else {
        $sanitizedParams[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
$params = $sanitizedParams;

// Map controller name to controller class
$controllerMap = [
    'dashboard' => 'DashboardController',
    'stations' => 'StationController',
    'trajets' => 'TrajetController',
    'auth' => 'AuthController',
    'login' => 'AuthController',
    'logout' => 'AuthController',
    'reset_password' => 'AuthController'
];

// Special action mapping
$actionMap = [
    'login' => 'login',
    'logout' => 'logout',
    'reset_password' => 'resetPassword'
];

// Determine the controller class
$controllerClass = isset($controllerMap[$controller]) ? $controllerMap[$controller] : 'DashboardController';

// Override the action for special routes
if (in_array($controller, ['login', 'logout', 'reset_password'])) {
    $action = isset($actionMap[$controller]) ? $actionMap[$controller] : 'login';
    $controllerClass = 'AuthController';
}

// Ensure controller file exists
$controllerFilePath = BASE_PATH . '/controllers/' . strtolower(str_replace('Controller', '', $controllerClass)) . 'Controller.php';
if (!file_exists($controllerFilePath)) {
    // Controller not found, serve 404
    header("HTTP/1.0 404 Not Found");
    include_once BASE_PATH . '/views/404.php';
    exit();
}

// Include controller file if not already included by autoloader
if (!class_exists($controllerClass)) {
    require_once $controllerFilePath;
}

// Initialize controller and process request
try {
    $controllerInstance = new $controllerClass();
    
    // Check if controller has route method
    if (method_exists($controllerInstance, 'route')) {
        // Route to the appropriate action
        $controllerInstance->route($action, $params);
    } else {
        // Fallback for controllers without route method
        $actionMethod = $action . 'Action';
        if (method_exists($controllerInstance, $actionMethod)) {
            $controllerInstance->$actionMethod($params);
        } else {
            // Action not found, serve 404
            header("HTTP/1.0 404 Not Found");
            include_once BASE_PATH . '/views/404.php';
            exit();
        }
    }
} catch (Exception $e) {
    // Log the error
    error_log("Application error: " . $e->getMessage());
    
    // Show generic error page in production or detailed error in development
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        header("HTTP/1.0 500 Internal Server Error");
        include_once BASE_PATH . '/views/500.php';
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo "<h1>Application Error</h1>";
        echo "<p>{$e->getMessage()}</p>";
        echo "<pre>{$e->getTraceAsString()}</pre>";
    }
    exit();
}
