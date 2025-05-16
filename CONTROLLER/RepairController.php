<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!file_exists(__DIR__ . '/../config/ok.php')) {
    echo json_encode(['success' => false, 'error' => 'Database configuration file not found']);
    exit;
}

require_once __DIR__ . '/../config/ok.php';
require_once __DIR__ . '/../MODEL/RepairModel.php';

class RepairController {
    private $model;

    public function __construct() {
        $this->model = new RepairModel();
    }

    public function getAll() {
        try {
            $repairs = $this->model->getAllRepairs();
            echo json_encode($repairs);
        } catch (Exception $e) {
            error_log("RepairController::getAll error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function add($data) {
        if (empty($data['bike_id']) || strlen($data['bike_id']) > 10) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bike ID is required and must be less than 10 characters']);
            return;
        }
        if (empty($data['bike_type']) || strlen($data['bike_type']) > 50) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bike Type is required and must be less than 50 characters']);
            return;
        }
        if (empty($data['problem'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Problem is required']);
            return;
        }
        if (!is_numeric($data['progression']) || $data['progression'] < 0 || $data['progression'] > 100) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Progression must be between 0 and 100']);
            return;
        }
        if (!is_numeric($data['stock_id']) || $data['stock_id'] < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Stock ID must be a positive number']);
            return;
        }
        if (!in_array($data['status'], ['En cours', 'En attente', 'Terminé'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            return;
        }

        try {
            $this->model->addRepair(
                $data['bike_id'],
                $data['bike_type'],
                $data['problem'],
                $data['status'],
                $data['progression'],
                $data['stock_id']
            );
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("RepairController::add error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function update($data) {
        if (empty($data['id']) || !is_numeric($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Repair ID is required']);
            return;
        }
        if (empty($data['bike_id']) || strlen($data['bike_id']) > 10) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bike ID is required and must be less than 10 characters']);
            return;
        }
        if (empty($data['bike_type']) || strlen($data['bike_type']) > 50) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bike Type is required and must be less than 50 characters']);
            return;
        }
        if (empty($data['problem'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Problem is required']);
            return;
        }
        if (!is_numeric($data['progression']) || $data['progression'] < 0 || $data['progression'] > 100) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Progression must be between 0 and 100']);
            return;
        }
        if (!is_numeric($data['stock_id']) || $data['stock_id'] < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Stock ID must be a positive number']);
            return;
        }
        if (!in_array($data['status'], ['En cours', 'En attente', 'Terminé'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            return;
        }

        try {
            $success = $this->model->updateRepair(
                $data['id'],
                $data['bike_id'],
                $data['bike_type'],
                $data['problem'],
                $data['status'],
                $data['progression'],
                $data['stock_id']
            );
            if (!$success) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Repair item not found']);
                return;
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("RepairController::update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function delete($data) {
    if (empty($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid Repair ID is required']);
        return;
    }

    try {
        $success = $this->model->deleteRepair($data['id']);
        if (!$success) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Repair item not found']);
            return;
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("RepairController::delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
}

try {
    $controller = new RepairController();

    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $input = json_decode(file_get_contents('php://input'), true);

        if (in_array($action, ['add', 'update', 'delete']) && (!$input || !is_array($input))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid or missing input data']);
            exit;
        }

        switch ($action) {
            case 'get_all':
                $controller->getAll();
                break;
            case 'add':
                $controller->add($input);
                break;
            case 'update':
                $controller->update($input);
                break;
            case 'delete':
                $controller->delete($input);
                break;
            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No action specified']);
    }
} catch (Exception $e) {
    error_log("RepairController routing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>