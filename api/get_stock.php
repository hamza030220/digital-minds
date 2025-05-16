<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../CONFIG/ok.php';

try {
    $stmt = $pdo->query("SELECT id, item_name, category, quantity, price FROM stock");
    $stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($stock as &$item) {
        $item['price'] = (float) $item['price'];
    }
    echo json_encode($stock);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>