<?php
session_start();
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: info2.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);
if (!$user) {
    exit("Erreur : utilisateur introuvable.");
}

$is_admin = $_SESSION['role'] === 'admin';
$is_technicien = $_SESSION['role'] === 'technicien';
$is_user = $_SESSION['role'] === 'user';

$users = [];
if ($is_admin) {
    $filters = [
        'nom' => $_GET['nom'] ?? '',
        'age_sort' => $_GET['age_sort'] ?? ''
    ];
    $users = User::getAll($filters);
}

$stats = ['total' => 0, 'admin' => 0, 'user' => 0, 'technicien' => 0];
$roleStats = User::getRoleStats();
foreach ($roleStats as $role) {
    $stats['total'] += $role['count'];
    $stats[$role['role']] = $role['count'];
}

if ($is_user) {
    $users = [$user];
}

include __DIR__ . '/../views/dashboard.php';
