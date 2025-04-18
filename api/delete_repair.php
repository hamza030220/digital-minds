<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    // Get repair data
    $stmt = $pdo->prepare('SELECT stock_id, status FROM repairs WHERE id = ?');
    $stmt->execute([$data['id']]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete repair
    $stmt = $pdo->prepare('DELETE FROM repairs WHERE id = ?');
    $stmt->execute([$data['id']]);

    // Restore stock if repair was not En attente
    if ($repair && $repair['status'] !== 'En attente') {
        $stmt = $pdo->prepare('UPDATE stock SET quantity = quantity + 1 WHERE id = ?');
        $stmt->execute([$repair['stock_id']]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Delete failed: ' . $e->getMessage()]);
    exit;
}
?>