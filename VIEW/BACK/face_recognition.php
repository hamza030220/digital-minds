<?php
require_once __DIR__ . '/../../CONFIG/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['image'])) {
        echo json_encode(['success' => false, 'message' => 'Aucune image fournie.']);
        exit;
    }

    // Save captured image temporarily
    $base64_string = str_replace('data:image/jpeg;base64,', '', $input['image']);
    $base64_string = str_replace(' ', '+', $base64_string);
    $image_data = base64_decode($base64_string);
    $temp_image = 'Uploads/temp_' . uniqid() . '.jpg';
    file_put_contents($temp_image, $image_data);

    // Load face-api.js in PHP context (using a headless browser or API is complex; here we assume client-side descriptors)
    // For simplicity, we compare images using a basic similarity check (replace with face-api.js server-side if needed)
    $stmt = $pdo->query("SELECT id, email, role, photo FROM users WHERE photo IS NOT NULL");
    $users = $stmt->fetchAll();
    $matched_user = null;

    foreach ($users as $user) {
        if (!file_exists($user['photo'])) continue;

        // Placeholder for face recognition (requires server-side face-api.js or external API)
        // Here, we assume a simple file comparison or external API call
        // Replace with actual face-api.js integration or API like AWS Rekognition
        // For demo, assume a match if filenames are processed (not secure; implement proper face matching)
        $similarity = rand(0, 100); // Placeholder; replace with real face-api.js comparison
        if ($similarity > 80) { // Threshold for match
            $matched_user = $user;
            break;
        }
    }

    // Clean up
    unlink($temp_image);

    if ($matched_user) {
        $_SESSION['user_id'] = $matched_user['id'];
        $_SESSION['role'] = strtolower(trim($matched_user['role']));
        session_regenerate_id(true);
        echo json_encode(['success' => true, 'role' => $matched_user['role']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun utilisateur correspondant trouvé.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>