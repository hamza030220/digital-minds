<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require '../CONFIG/ok.php';

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

    // Update average rating in repairs
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM repair_ratings WHERE repair_id = ?");
    $stmt->execute([$repair_id]);
    $avg_rating = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE repairs SET average_rating = ? WHERE id = ?");
    $stmt->execute([$avg_rating, $repair_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>