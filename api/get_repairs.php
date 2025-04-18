<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'db_connect.php';

try {
    $stmt = $pdo->query("
        SELECT r.id, r.bike_id, r.bike_type, r.status, r.progression, r.stock_id, 
               s.item_name AS stock_item_name, s.quantity AS stock_quantity
        FROM repairs r
        LEFT JOIN stock s ON r.stock_id = s.id
    ");
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($repairs as &$repair) {
        $repair['status'] = $repair['stock_quantity'] > 0 ? 'En cours' : 'En attente de pièces';
    }
    echo json_encode($repairs);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>