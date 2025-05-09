<?php
// test_stock.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/StockModel.php';
header('Content-Type: application/json');
try {
    $database = new Database();
    $db = $database->getConnection();
    $stockModel = new StockModel($db);
    $stock = $stockModel->getAllStock();
    echo json_encode($stock);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>