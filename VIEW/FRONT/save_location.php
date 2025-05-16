<?php
require_once __DIR__ . '/../../CONFIG/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['export_id'], $input['latitude'], $input['longitude'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO scan_locations (export_id, latitude, longitude, address, scan_time) VALUES (:export_id, :latitude, :longitude, :address, NOW())");
        $stmt->execute([
            'export_id' => $input['export_id'],
            'latitude' => $input['latitude'],
            'longitude' => $input['longitude'],
            'address' => $input['address'] ?? 'Inconnu'
        ]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
}
?>