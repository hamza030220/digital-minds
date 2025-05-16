<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour supprimer un commentaire.';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$translations_file = __DIR__ . '/assets/translations.json';
$translations = json_decode(file_get_contents($translations_file), true);
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

function getTranslation($key, $lang, $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Handle POST request for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'])) {
    $comment_id = $_POST['comment_id'];

    // Fetch comment to verify ownership and get post_id
    $stmt = $pdo->prepare("SELECT id, post_id FROM comments WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if ($comment && $comment['id'] === $user_id) {
        try {
            // Delete comment
            $stmt = $pdo->prepare("DELETE FROM comments WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            $_SESSION['success_message'] = getTranslation('comment_deleted_success', $language, $translations);
            header("Location: forum.php#post-" . $comment['post_id']);
            exit();
        } catch (Exception $e) {
            error_log("Failed to delete comment_id $comment_id: " . $e->getMessage());
            $_SESSION['error_message'] = getTranslation('error_delete_failed', $language, $translations);
            header("Location: forum.php#post-" . $comment['post_id']);
            exit();
        }
    } else {
        $_SESSION['error_message'] = getTranslation('error_unauthorized', $language, $translations);
        header("Location: forum.php");
        exit();
    }
}

// If accessed via GET, display confirmation form
if (isset($_GET['comment_id'])) {
    $comment_id = $_GET['comment_id'];
    $stmt = $pdo->prepare("SELECT id, post_id FROM comments WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment || $comment['id'] !== $user_id) {
        $_SESSION['error_message'] = getTranslation('error_unauthorized', $language, $translations);
        header("Location: forum.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getTranslation('delete_comment_title', $language, $translations); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #60BA97;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .confirmation-box {
            background: #F9F5E8;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .confirmation-box h2 {
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
            margin-bottom: 1rem;
        }
        .confirmation-box p {
            color: #333;
            margin-bottom: 1.5rem;
        }
        .confirmation-box form {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        .confirmation-box button, .confirmation-box a {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            font-family: "Bauhaus 93", Arial, sans-serif;
            color: white;
        }
        .confirmation-box button.confirm {
            background-color: #FF0000;
        }
        .confirmation-box button.confirm:hover {
            background-color: #CC0000;
        }
        .confirmation-box a.cancel {
            background-color: #2e7d32;
        }
        .confirmation-box a.cancel:hover {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h2><?php echo getTranslation('delete_comment_title', $language, $translations); ?></h2>
        <p><?php echo getTranslation('confirm_delete_comment', $language, $translations); ?></p>
        <form method="POST" action="delete_supprimer.php">
            <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars($comment_id); ?>">
            <button type="submit" class="confirm"><?php echo getTranslation('delete', $language, $translations); ?></button>
            <a href="forum.php#post-<?php echo $comment['post_id']; ?>" class="cancel"><?php echo getTranslation('cancel', $language, $translations); ?></a>
        </form>
    </div>
</body>
</html>