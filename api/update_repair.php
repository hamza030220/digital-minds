<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'], $data['bike_id'], $data['bike_type'], $data['status'], $data['progression'], $data['stock_id'])) {
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

    // Get current repair data
    $stmt = $pdo->prepare('SELECT stock_id, status FROM repairs WHERE id = ?');
    $stmt->execute([$data['id']]);
    $current_repair = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update repair
    $stmt = $pdo->prepare('UPDATE repairs SET bike_id = ?, bike_type = ?, status = ?, progression = ?, stock_id = ? WHERE id = ?');
    $stmt->execute([
        $data['bike_id'],
        $data['bike_type'],
        $data['status'],
        $data['progression'],
        $data['stock_id'],
        $data['id']
    ]);

    // Handle stock quantity changes
    if ($current_repair && $current_repair['stock_id'] != $data['stock_id']) {
        // Restore quantity to old stock item if it was in stock
        if ($current_repair['status'] !== 'En attente') {
            $stmt = $pdo->prepare('UPDATE stock SET quantity = quantity + 1 WHERE id = ?');
            $stmt->execute([$current_repair['stock_id']]);
        }
        // Deduct from new stock item if in stock
        if ($stock['quantity'] > 0) {
            $stmt = $pdo->prepare('UPDATE stock SET quantity = quantity - 1 WHERE id = ?');
            $stmt->execute([$data['stock_id']]);
        }
    } elseif ($current_repair['status'] !== $data['status']) {
        // Handle status change affecting stock
        if ($data['status'] === 'En attente' && $current_repair['status'] !== 'En attente') {
            // Restore stock if moving to En attente
            $stmt = $pdo->prepare('UPDATE stock SET quantity = quantity + 1 WHERE id = ?');
            $stmt->execute([$data['stock_id']]);
        } elseif ($data['status'] !== 'En attente' && $current_repair['status'] === 'En attente' && $stock['quantity'] > 0) {
            // Deduct stock if moving from En attente to in-progress or completed
            $stmt = $pdo->prepare('UPDATE stock SET quantity = quantity - 1 WHERE id = ?');
            $stmt->execute([$data['stock_id']]);
        }
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()]);
    exit;
}
?>