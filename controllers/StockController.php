<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/StockModel.php';

class StockController {
    private $db;
    private $stockModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->stockModel = new StockModel($this->db);
    }

    // Get all stock items
    public function getAllStock() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        try {
            $stockItems = $this->stockModel->getAllStock();
            $formattedStock = array_map(function($item) {
                return [
                    'id' => (int)$item['id'],
                    'item_name' => $item['item_name'] ?: 'N/A',
                    'category' => $item['category'] ?: 'N/A',
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price']
                ];
            }, $stockItems);
            echo json_encode($formattedStock);
        } catch (Exception $e) {
            error_log("StockController::getAllStock error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    // Add a new stock item
    public function addStock() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['item_name'], $data['category'], $data['quantity'], $data['price'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            return;
        }

        try {
            // Validate input
            if (empty($data['item_name']) || strlen($data['item_name']) > 100) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name is required and must be less than 100 characters']);
                return;
            }
            if (empty($data['category']) || strlen($data['category']) > 50) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Category is required and must be less than 50 characters']);
                return;
            }
            if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Quantity must be a non-negative number']);
                return;
            }
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Price must be a non-negative number']);
                return;
            }

            // Insert stock item
            $query = "INSERT INTO stock (item_name, category, quantity, price) VALUES (:item_name, :category, :quantity, :price)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':item_name' => $data['item_name'],
                ':category' => $data['category'],
                ':quantity' => (int)$data['quantity'],
                ':price' => (float)$data['price']
            ]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("StockController::addStock error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $e->getMessage()]);
        }
    }

    // Delete a stock item
    public function deleteStock() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            return;
        }

        try {
            $query = "DELETE FROM stock WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $data['id']]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Stock item not found']);
                return;
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("StockController::deleteStock error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }
}

// Route requests
try {
    $controller = new StockController();
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'get_all':
            $controller->getAllStock();
            break;
        case 'add':
            $controller->addStock();
            break;
        case 'delete':
            $controller->deleteStock();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("StockController routing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>