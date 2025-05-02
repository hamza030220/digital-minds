<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'db_connect.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $repair_id = $data['repair_id'];
    $rating = $data['rating'];

    if (!is_numeric($repair_id) || !is_numeric($rating) || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid repair ID or rating']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO repair_ratings (repair_id, rating) VALUES (?, ?)');
    $stmt->execute([$repair_id, $rating]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>