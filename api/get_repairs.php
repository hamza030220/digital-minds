<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../CONFIG/ok.php';

try {
    $stmt = $pdo->query("
        SELECT r.id, r.bike_id, r.bike_type, r.status, r.progression, r.stock_id, 
               s.item_name AS stock_item_name, s.quantity AS stock_quantity,
               AVG(rr.rating) AS average_rating
        FROM repairs r
        LEFT JOIN stock s ON r.stock_id = s.id
        LEFT JOIN repair_ratings rr ON r.id = rr.repair_id
        GROUP BY r.id
    ");
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($repairs as &$repair) {
        if (intval($repair['progression']) === 100) {
            $repair['status'] = 'Terminé';
        }
        $repair['average_rating'] = $repair['average_rating'] ? number_format(floatval($repair['average_rating']), 1) : null;
    }
    echo json_encode($repairs);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>