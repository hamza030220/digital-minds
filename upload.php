<?php
require_once __DIR__ . '/models/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['photo'])) {
        // Récupérer l'image en Base64
        $photo = $_POST['photo'];

        // Nettoyer l'image (enlever la partie "data:image/png;base64,")
        $photo = str_replace('data:image/png;base64,', '', $photo);
        $photo = base64_decode($photo); // Décoder l'image

        // Générer un nom de fichier unique pour l'image
        $photoName = uniqid() . '.png';
        $photoPath = 'uploads/' . $photoName;

        // Enregistrer l'image dans le dossier "uploads/"
        if (file_put_contents($photoPath, $photo)) {
            // Enregistrer le chemin dans la base de données
            $stmt = $pdo->prepare("INSERT INTO users (photo) VALUES (?)");
            if ($stmt->execute([$photoPath])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement en base de données.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucune photo reçue.']);
    }
}
?>
