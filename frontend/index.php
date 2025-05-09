<?php
// frontend/index.php
require_once 'controllers/FrontRepairController.php';

// Get controller and action from URL (e.g., ?controller=repair&action=index)
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'repair';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

if ($controller == 'repair') {
    $controller = new FrontRepairController();
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        die("Action not found");
    }
} else {
    die("Controller not found");
}