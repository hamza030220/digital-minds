<?php // ajouter_reponse.php

// Start session if needed (e.g., for user authentication or flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include PHPMailer manually ---
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Database Connection ---
require_once __DIR__ . '/config/database.php';

// 1. Instantiate the Database class
$database = new Database();

// 2. Get the PDO connection object
$pdo = $database->getConnection();

// 3. Check if the connection was successful
if (!$pdo) {
    error_log("Database connection failed in ajouter_reponse.php");
    die("Database connection failed. Please try again later or contact support.");
}

// --- Input Handling ---
$reclamation_id = $_POST['reclamation_id'] ?? null;
$contenu = $_POST['contenu'] ?? null;
$role = $_POST['role'] ?? null;

if (empty($reclamation_id) || !is_numeric($reclamation_id) || empty(trim($contenu ?? '')) || empty($role)) {
    die("Erreur : Données manquantes ou invalides.");
}

if (!in_array($role, ['utilisateur', 'admin'])) {
    die("Erreur : Rôle invalide.");
}

// --- Database Operation ---
try {
    $sql = "INSERT INTO reponses (reclamation_id, contenu, role, date_creation) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reclamation_id, trim($contenu), $role]);

    // Send email if response is by admin
    if ($role === 'admin') {
        // Get reclamation details (including user_id and titre)
        $reclamationStmt = $pdo->prepare("SELECT utilisateur_id, titre FROM reclamations WHERE id = ?");
        $reclamationStmt->execute([$reclamation_id]);
        $reclamation = $reclamationStmt->fetch(PDO::FETCH_ASSOC);

        if ($reclamation && $reclamation['utilisateur_id']) {
            // Get user's email
            $userStmt = $pdo->prepare("SELECT email FROM utilisateurs WHERE id = ?");
            $userStmt->execute([$reclamation['utilisateur_id']]);
            $user_email = $userStmt->fetchColumn();

            if ($user_email) {
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Serveur SMTP (Gmail dans cet exemple)
                    $mail->SMTPAuth = true;
                    $mail->Username = 'habib.znaidi@gmail.com'; // Ton e-mail ou un e-mail système
                    $mail->Password = '1111'; // Ton mot de passe ou mot de passe d'application
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('ton.email@gmail.com', 'Green.tn'); // L'expéditeur (ton e-mail)
                    $mail->addAddress($user_email); // Destinataire (l'utilisateur qui a créé la réclamation)

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Nouvelle réponse à votre réclamation';
                    $mail->Body = "
                        <h2>Nouvelle réponse</h2>
                        <p>Bonjour,</p>
                        <p>Une réponse a été ajoutée à votre réclamation :</p>
                        <p><strong>Titre :</strong> " . htmlspecialchars($reclamation['titre']) . "</p>
                        <p><strong>Réponse :</strong> " . nl2br(htmlspecialchars($contenu)) . "</p>
                        <p>Consultez les détails ici : <a href='http://localhost/green-tn/voir_reclamation.php?id=" . $reclamation_id . "'>http://localhost/green-tn/voir_reclamation.php?id=" . $reclamation_id . "</a></p>
                        <p>Merci,<br>Green.tn</p>
                    ";
                    $mail->AltBody = strip_tags($mail->Body);

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $mail->ErrorInfo);
                }
            }
        }
    }

    // Redirect on success
    header("Location: voir_reclamation.php?id=" . urlencode($reclamation_id) . "&status_update=success");
    exit;

} catch (PDOException $e) {
    error_log("Error inserting response for reclamation ID $reclamation_id: " . $e->getMessage());
    header("Location: voir_reclamation.php?id=" . urlencode($reclamation_id) . "&status_update=error_db");
    exit;
}
?>