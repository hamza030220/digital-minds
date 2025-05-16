<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Routage simple
switch ($uri) {
    case '/':
    case '/index.php':
        require_once __DIR__ . '/controllers/HomeController.php';
        HomeController::index();
        break;

    case '/login':
        require_once __DIR__ . '/controllers/LoginController.php';
        LoginController::handleRequest();
        break;

    case '/sign_up':
        require_once __DIR__ . '/controllers/SignUpController.php';
        SignUpController::handleRequest();
        break;

    default:
        http_response_code(404);
        echo "Page non trouvée.";
        break;
}
