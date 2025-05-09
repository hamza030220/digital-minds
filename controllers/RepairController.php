<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/RepairModel.php';

class RepairController {
    private $repairModel;

    public function __construct() {
        $database = new Database();
        $this->repairModel = new RepairModel();
    }

    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        switch ($action) {
            case 'get_all':
                $this->getRepairs();
                break;
            case 'add':
                $this->addRepair();
                break;
            case 'update':
                $this->updateRepair();
                break;
            case 'delete':
                $this->deleteRepair();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    }

    public function getRepairs() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        try {
            $repairs = $this->repairModel->getAllRepairs();
            error_log("getRepairs: Returning " . count($repairs) . " repairs");
            echo json_encode($repairs);
        } catch (Exception $e) {
            error_log("getRepairs error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function addRepair() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid JSON input');
            }

            $bike_id = isset($data['bike_id']) ? trim($data['bike_id']) : '';
            $bike_type = isset($data['bike_type']) ? trim($data['bike_type']) : '';
            $problem = isset($data['problem']) ? trim($data['problem']) : '';
            $status = isset($data['status']) ? trim($data['status']) : '';
            $progression = isset($data['progression']) ? (int)$data['progression'] : 0;
            $stock_id = isset($data['stock_id']) ? (int)$data['stock_id'] : 0;

            if (empty($bike_id) || strlen($bike_id) > 10) {
                throw new Exception('Bike ID is required and must be less than 10 characters');
            }
            if (empty($bike_type) || strlen($bike_type) > 50) {
                throw new Exception('Bike Type is required and must be less than 50 characters');
            }
            if (strlen($problem) > 65535) {
                throw new Exception('Problem must be less than 65535 characters');
            }
            if (!in_array($status, ['En cours', 'Terminé', 'En attente'])) {
                throw new Exception('Invalid status');
            }
            if ($progression < 0 || $progression > 100) {
                throw new Exception('Progression must be between 0 and 100');
            }
            if ($stock_id < 1) {
                throw new Exception('Stock ID must be a positive number');
            }

            $this->repairModel->addRepair($bike_id, $bike_type, $problem, $status, $progression, $stock_id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("addRepair error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function updateRepair() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid JSON input');
            }

            $id = isset($data['id']) ? (int)$data['id'] : 0;
            $bike_id = isset($data['bike_id']) ? trim($data['bike_id']) : '';
            $bike_type = isset($data['bike_type']) ? trim($data['bike_type']) : '';
            $problem = isset($data['problem']) ? trim($data['problem']) : '';
            $status = isset($data['status']) ? trim($data['status']) : '';
            $progression = isset($data['progression']) ? (int)$data['progression'] : 0;
            $stock_id = isset($data['stock_id']) ? (int)$data['stock_id'] : 0;

            if ($id <= 0) {
                throw new Exception('Invalid repair ID');
            }
            if (empty($bike_id) || strlen($bike_id) > 10) {
                throw new Exception('Bike ID is required and must be less than 10 characters');
            }
            if (empty($bike_type) || strlen($bike_type) > 50) {
                throw new Exception('Bike Type is required and must be less than 50 characters');
            }
            if (strlen($problem) > 65535) {
                throw new Exception('Problem must be less than 65535 characters');
            }
            if (!in_array($status, ['En cours', 'Terminé', 'En attente'])) {
                throw new Exception('Invalid status');
            }
            if ($progression < 0 || $progression > 100) {
                throw new Exception('Progression must be between 0 and 100');
            }
            if ($stock_id < 1) {
                throw new Exception('Stock ID must be a positive number');
            }

            $this->repairModel->updateRepair($id, $bike_id, $bike_type, $problem, $status, $progression, $stock_id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("updateRepair error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function deleteRepair() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['id'])) {
                throw new Exception('Invalid input: ID is required');
            }

            $id = (int)$data['id'];
            if ($id <= 0) {
                throw new Exception('Invalid repair ID');
            }

            $this->repairModel->deleteRepair($id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("deleteRepair error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

try {
    $controller = new RepairController();
    $controller->handleRequest();
} catch (Exception $e) {
    error_log("RepairController error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>