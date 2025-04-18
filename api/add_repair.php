<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['bike_id'], $data['bike_type'], $data['status'], $data['progression'], $data['stock_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    // Check stock availability
    $stmt = $pdo->prepare('SELECT quantity FROM stock WHERE id = ?');
    $stmt->execute([$data['stock_id']]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$stock) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid stock item']);
        exit;
    }

    // Validate status based on stock quantity
    if ($stock['quantity'] <= 0 && $data['status'] !== 'En attente') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Status must be En attente for out-of-stock items']);
        exit;
    }
    if ($stock['quantity'] > 0 && $data['status'] === 'En attente') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Status cannot be En attente for in-stock items']);
        exit;
    }

    // Insert repair
    $stmt = $pdo->prepare('INSERT INTO repairs (bike_id, bike_type, status, progression, stock_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['bike_id'],
        $data['bike_type'],
        $data['status'],
        $data['progression'],
        $data['stock_id']
    ]);

    // Deduct stock if in stock
    if ($stock['quantity'] > 0) {
        $stmt = $pdo->prepare('UPDATE stock SET quantity = quantity - 1 WHERE id = ?');
        $stmt->execute([$data['stock_id']]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $e->getMessage()]);
    exit;
}
?>