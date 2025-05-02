<?php
header('Content-Type: application/json');
$repair_id = $_GET['repair_id'];

$conn = new mysqli('localhost', 'username', 'password', 'database');
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$stmt = $conn->prepare('SELECT AVG(rating) as average_rating FROM repair_ratings WHERE repair_id = ?');
$stmt->bind_param('i', $repair_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['average_rating' => $row['average_rating']]);
$stmt->close();
$conn->close();
?>