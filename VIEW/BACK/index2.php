<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../CONFIG/db.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'login';

$controller_path = __DIR__ . '/controllers/LoginController.php';
$reservation_controller_path = __DIR__ . '/controllers/ReservationController.php';

switch ($page) {
    case 'login':
        if (!file_exists($controller_path)) {
            die("Error: LoginController.php not found at $controller_path. Please verify the file exists in the controllers/ directory.");
        }
        require_once $controller_path;
        $controller = new LoginController($pdo);
        $controller->handleRequest();
        break;
    case 'reservation':
        if (!file_exists($reservation_controller_path)) {
            die("Error: ReservationController.php not found at $reservation_controller_path. Please verify the file exists in the controllers/ directory.");
        }
        require_once $reservation_controller_path;
        $controller = new ReservationController($pdo);
        $controller->handleRequest();
        break;
    default:
        header('HTTP/1.0 404 Not Found');
        echo 'Page not found';
        exit;
}
?>