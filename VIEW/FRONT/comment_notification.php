<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load translations
$translations_file = __DIR__ . '/assets/translations.json';
$translations = json_decode(file_get_contents($translations_file), true);
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

// Function to get translated text
function getTranslation($key, $lang, $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Handle comment creation with email notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_comment') {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = trim(filter_input(INPUT_POST, 'comment_content', FILTER_SANITIZE_STRING));

    if (!$post_id) {
        $errors['comment_content'] = getTranslation('error_invalid_post', $language, $translations);
    }
    if (empty($content) || strlen($content) < 5) {
        $errors['comment_content'] = getTranslation('error_comment_content_short', $language, $translations);
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Insert comment
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, $user_id, $content]);

            // Fetch post author and commenter's details
            $stmt = $pdo->prepare("
                SELECT p.id, p.is_anonymous, u.email, u.nom, u.prenom 
                FROM posts p 
                JOIN users u ON p.id = u.id 
                WHERE p.post_id = ?
            ");
            $stmt->execute([$post_id]);
            $post_author = $stmt->fetch();

            // Fetch commenter details
            $stmt = $pdo->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $commenter = $stmt->fetch();

            if ($post_author && !$post_author['is_anonymous'] && $post_author['id'] !== $user_id) {
                // Send email notification
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = getenv('SMTP_USERNAME') ?: 'your_email@gmail.com'; // Use environment variable
                    $mail->Password = getenv('SMTP_PASSWORD') ?: 'your_app_password'; // Use environment variable
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->CharSet = 'UTF-8';

                    // Recipients
                    $mail->setFrom('no-reply@green.tn', 'Green.tn Forum');
                    $mail->addAddress($post_author['email'], $post_author['nom'] . ' ' . $post_author['prenom']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = getTranslation('new_comment_notification_subject', $language, $translations);
                    $mail->Body = sprintf(
                        getTranslation('new_comment_notification_body', $language, $translations),
                        htmlspecialchars($post_author['nom'] . ' ' . $post_author['prenom']),
                        htmlspecialchars($commenter['nom'] . ' ' . $commenter['prenom']),
                        htmlspecialchars($content),
                        'http://localhost/projet/forum.php#post-' . $post_id
                    );
                    $mail->AltBody = strip_tags($mail->Body);

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Failed to send email for comment on post_id $post_id: " . $mail->ErrorInfo);
                    $errors['email'] = getTranslation('error_email_failed', $language, $translations);
                }
            }

            $pdo->commit();
            $_SESSION['success_message'] = getTranslation('comment_added_success', $language, $translations);
            session_write_close();
            header("Location: forum.php#post-$post_id");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Failed to add comment for post_id $post_id: " . $e->getMessage());
            $errors['comment_content'] = getTranslation('error_comment_failed', $language, $translations);
        }
    }
}
?>