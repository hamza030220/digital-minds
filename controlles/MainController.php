<?php
class MainController {
    public function handleRequest($page) {
        switch ($page) {
            case 'accueil':
                include '../views/home.php';
                break;
            case 'gestion_utilisateurs':
                include '../views/users.php';
                break;
            default:
                include '../views/home.php';
        }
    }
}
