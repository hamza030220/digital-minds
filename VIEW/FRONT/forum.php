<?php
session_start();
require_once __DIR__ . '/../../CONFIG/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../CONTROLLER/PostController.php'; // Post-related logic
require_once __DIR__ . '/../../CONTROLLER/CommentController.php'; // Comment-related logic
require_once __DIR__ . '/../../MODEL/Post.php'; // Post model for database operations
require_once __DIR__ . '/../../MODEL/Comment.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load translations
$translations_file = __DIR__ . '/assets/translations.json';
$translations = json_decode(file_get_contents($translations_file), true);

// Determine current language
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    $language = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// Function to get translated text
function getTranslation($key, $lang = 'fr', $translations) {
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve logged-in user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    error_log("User not found for user_id: $user_id");
    echo "Error: User not found.";
    exit();
}

// Initialize success message
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear after displaying
}

// Fetch unread notif_comm count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notif_comm WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_notif_comm_count = $stmt->fetchColumn();

// Fetch recent notif_comm
$stmt = $pdo->prepare("
    SELECT n.*, u.nom, u.prenom
    FROM notif_comm n
    JOIN users u ON n.commenter_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$notif_comm = $stmt->fetchAll();

// Mark notif_comm as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_notif_comm_read') {
    $stmt = $pdo->prepare("UPDATE notif_comm SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: forum.php");
    exit();
}

// Pagination
$posts_per_page = 3;
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $posts_per_page;

// Handle post creation
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create_post') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if (empty($title) || strlen($title) < 5) {
        $errors['title'] = getTranslation('error_post_title_short', $language, $translations);
    }
    if (empty($content) || strlen($content) < 10) {
        $errors['content'] = getTranslation('error_post_content_short', $language, $translations);
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO posts (id, title, content, created_at, is_anonymous) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$user_id, $title, $content, $is_anonymous]);
        $_SESSION['success_message'] = getTranslation('post_created_success', $language, $translations);
        header("Location: forum.php");
        exit();
    }
}

// Handle post editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit_post') {
    $post_id = $_POST['post_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if (empty($title) || strlen($title) < 5) {
        $errors['title'] = getTranslation('error_post_title_short', $language, $translations);
    }
    if (empty($content) || strlen($content) < 10) {
        $errors['content'] = getTranslation('error_post_content_short', $language, $translations);
    }

    $stmt = $pdo->prepare("SELECT id FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    if (!$post || $post['id'] !== $user_id) {
        $errors['post'] = getTranslation('error_unauthorized', $language, $translations);
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, is_anonymous = ?, updated_at = NOW() WHERE post_id = ?");
        $stmt->execute([$title, $content, $is_anonymous, $post_id]);
        $_SESSION['success_message'] = getTranslation('post_updated_success', $language, $translations);
        header("Location: forum.php");
        exit();
    }
}

// Handle post deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_post' && isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    if ($post && $post['id'] === $user_id) {
        try {
            // Delete associated likes
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ?");
            $stmt->execute([$post_id]);
            // Delete associated comments
            $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt->execute([$post_id]);
            // Delete associated notif_comm
            $stmt = $pdo->prepare("DELETE FROM notif_comm WHERE post_id = ?");
            $stmt->execute([$post_id]);
            // Delete the post
            $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $_SESSION['success_message'] = getTranslation('post_deleted_success', $language, $translations);
            error_log("Post $post_id deleted successfully");
        } catch (Exception $e) {
            error_log("Failed to delete post_id $post_id: " . $e->getMessage());
            $errors['delete_post'] = getTranslation('error_delete_failed', $language, $translations);
        }
    } else {
        $errors['delete_post'] = getTranslation('error_unauthorized', $language, $translations);
        error_log("Unauthorized attempt to delete post_id $post_id by user_id $user_id");
    }
    session_write_close();
    header("Cache-Control: no-cache, must-revalidate");
    header("Location: forum.php");
    exit();
}

// Handle comment creation with email and in-app notif_comm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_comment') {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['comment_content']);

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
            $stmt = $pdo->prepare("SELECT p.id, p.is_anonymous, u.email, u.nom, u.prenom FROM posts p JOIN users u ON p.id = u.id WHERE p.post_id = ?");
            $stmt->execute([$post_id]);
            $post_author = $stmt->fetch();

            // Send email notif_comm to post author if not anonymous and not the commenter
            if ($post_author && !$post_author['is_anonymous'] && $post_author['id'] !== $user_id) {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'your_email@gmail.com'; // Replace with your Gmail address
                    $mail->Password = 'your_app_password'; // Replace with your Gmail App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->CharSet = 'UTF-8';

                    // Recipients
                    $mail->setFrom('no-reply@green.tn', 'Green.tn Forum');
                    $mail->addAddress($post_author['email'], $post_author['nom'] . ' ' . $post_author['prenom']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = getTranslation('new_comment_notif_comm_subject', $language, $translations);
                    $mail->Body = sprintf(
                        getTranslation('new_comment_notif_comm_body', $language, $translations),
                        htmlspecialchars($post_author['nom'] . ' ' . $post_author['prenom']),
                        htmlspecialchars($user['nom'] . ' ' . $user['prenom']),
                        htmlspecialchars($content),
                        'http://localhost/projet/forum.php#post-' . $post_id
                    );
                    $mail->AltBody = strip_tags($mail->Body);

                    $mail->send();
                    error_log("Email notif_comm sent to {$post_author['email']} for comment on post_id $post_id");
                } catch (Exception $e) {
                    error_log("Failed to send email notif_comm for comment on post_id $post_id: " . $mail->ErrorInfo);
                }
            }

            // Insert in-app notif_comm
            if ($post_author && $post_author['id'] !== $user_id) {
                $notif_content = sprintf(
                    getTranslation('new_comment_notif_comm_content', $language, $translations),
                    htmlspecialchars($user['nom'] . ' ' . $user['prenom'])
                );
                $stmt = $pdo->prepare("INSERT INTO notif_comm (user_id, post_id, commenter_id, content, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$post_author['id'], $post_id, $user_id, $notif_content]);
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

// Handle comment deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_comment' && isset($_GET['comment_id'])) {
    $comment_id = $_GET['comment_id'];
    $stmt = $pdo->prepare("SELECT id, post_id FROM comments WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    if ($comment && $comment['id'] === $user_id) {
        try {
            // Delete associated likes
            $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            // Delete the comment
            $stmt = $pdo->prepare("DELETE FROM comments WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            $_SESSION['success_message'] = getTranslation('comment_deleted_success', $language, $translations);
            error_log("Comment $comment_id deleted successfully");
        } catch (Exception $e) {
            error_log("Failed to delete comment_id $comment_id: " . $e->getMessage());
            $errors['delete_comment'] = getTranslation('error_delete_failed', $language, $translations);
        }
    } else {
        $errors['delete_comment'] = getTranslation('error_unauthorized', $language, $translations);
        error_log("Unauthorized attempt to delete comment_id $comment_id by user_id $user_id");
    }
    session_write_close();
    header("Cache-Control: no-cache, must-revalidate");
    header("Location: forum.php#post-" . $comment['post_id']);
    exit();
}

// Handle post like/dislike
if (isset($_GET['action']) && in_array($_GET['action'], ['like_post', 'dislike_post']) && isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    $is_like = $_GET['action'] === 'like_post' ? 1 : 0;

    $stmt = $pdo->prepare("SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing_like = $stmt->fetch();

    $pdo->beginTransaction();
    try {
        if ($existing_like) {
            if ($existing_like['is_like'] == $is_like) {
                $stmt = $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$user_id, $post_id]);
                $stmt = $pdo->prepare("UPDATE posts SET " . ($is_like ? "likes" : "dislikes") . " = " . ($is_like ? "likes" : "dislikes") . " - 1 WHERE post_id = ?");
                $stmt->execute([$post_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE post_likes SET is_like = ? WHERE user_id = ? AND post_id = ?");
                $stmt->execute([$is_like, $user_id, $post_id]);
                $stmt = $pdo->prepare("UPDATE posts SET likes = likes + ?, dislikes = dislikes + ? WHERE post_id = ?");
                $stmt->execute([$is_like ? 1 : -1, $is_like ? -1 : 1, $post_id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO post_likes (user_id, post_id, is_like) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $post_id, $is_like]);
            $stmt = $pdo->prepare("UPDATE posts SET " . ($is_like ? "likes" : "dislikes") . " = " . ($is_like ? "likes" : "dislikes") . " + 1 WHERE post_id = ?");
            $stmt->execute([$post_id]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Like/dislike failed for post_id $post_id: " . $e->getMessage());
        $errors['like'] = getTranslation('error_like_failed', $language, $translations);
    }
    session_write_close();
    header("Location: forum.php");
    exit();
}

// Handle comment like/dislike
if (isset($_GET['action']) && in_array($_GET['action'], ['like_comment', 'dislike_comment']) && isset($_GET['comment_id'])) {
    $comment_id = $_GET['comment_id'];
    $is_like = $_GET['action'] === 'like_comment' ? 1 : 0;

    $stmt = $pdo->prepare("SELECT * FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$user_id, $comment_id]);
    $existing_like = $stmt->fetch();

    $pdo->beginTransaction();
    try {
        if ($existing_like) {
            if ($existing_like['is_like'] == $is_like) {
                $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
                $stmt->execute([$user_id, $comment_id]);
                $stmt = $pdo->prepare("UPDATE comments SET " . ($is_like ? "likes" : "dislikes") . " = " . ($is_like ? "likes" : "dislikes") . " - 1 WHERE comment_id = ?");
                $stmt->execute([$comment_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE comment_likes SET is_like = ? WHERE user_id = ? AND comment_id = ?");
                $stmt->execute([$is_like, $user_id, $comment_id]);
                $stmt = $pdo->prepare("UPDATE comments SET likes = likes + ?, dislikes = dislikes + ? WHERE comment_id = ?");
                $stmt->execute([$is_like ? 1 : -1, $is_like ? -1 : 1, $comment_id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO comment_likes (user_id, comment_id, is_like) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $comment_id, $is_like]);
            $stmt = $pdo->prepare("UPDATE comments SET " . ($is_like ? "likes" : "dislikes") . " = " . ($is_like ? "likes" : "dislikes") . " + 1 WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Like/dislike failed for comment_id $comment_id: " . $e->getMessage());
        $errors['like'] = getTranslation('error_like_failed', $language, $translations);
    }
    session_write_close();
    header("Location: forum.php");
    exit();
}

// Fetch posts
$stmt = $pdo->prepare("
    SELECT p.*, u.nom, u.prenom, u.photo,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id AND is_like = 1 AND user_id = ?) as user_liked,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id AND is_like = 0 AND user_id = ?) as user_disliked
    FROM posts p
    LEFT JOIN users u ON p.id = u.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $user_id, PDO::PARAM_INT);
$stmt->bindParam(3, $posts_per_page, PDO::PARAM_INT);
$stmt->bindParam(4, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Fetch total posts for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts");
$stmt->execute();
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

// Fetch comments
$comments = [];
foreach ($posts as $post) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.photo,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.comment_id AND is_like = 1 AND user_id = ?) as user_liked,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.comment_id AND is_like = 0 AND user_id = ?) as user_disliked
        FROM comments c
        LEFT JOIN users u ON c.id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$user_id, $user_id, $post['post_id']]);
    $comments[$post['post_id']] = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="forum_title">Green.tn - Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #60BA97;
            color: #333;
            min-height: 100vh;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .topbar {
            width: 100%;
            background-color: #F9F5E8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .topbar.hidden {
            transform: translateY(-100%);
        }

        .topbar .logo {
            height: 40px;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: #2e7d32;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
            font-size: 16px;
        }

        .nav-links a:hover, .nav-links .active {
            background-color: #4CAF50;
            color: #fff;
        }

        .nav-links a#toggle-language {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-icon {
            position: relative;
        }

        .top-profile-pic {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4CAF50;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .top-profile-pic:hover {
            transform: scale(1.1);
        }

        .notif-comm-icon {
            position: relative;
            margin-right: 1rem;
        }

        .notif-comm-bell {
            font-size: 1.2rem;
            color: #2e7d32;
            cursor: pointer;
            transition: color 0.3s;
        }

        .notif-comm-bell:hover {
            color: #4CAF50;
        }

        .notif-comm-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #FF0000;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            display: none;
        }

        .notif-comm-count.show {
            display: block;
        }

        .notif-comm-menu {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-width: 250px;
            z-index: 999;
        }

        .notif-comm-menu.show {
            display: block;
        }

        .notif-comm-item {
            padding: 0.75rem 1rem;
            color: #2e7d32;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .notif-comm-item:last-child {
            border-bottom: none;
        }

        .notif-comm-item:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .notif-comm-item.read {
            background-color: #f0f0f0;
        }

        .profile-menu {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-width: 180px;
            z-index: 999;
        }

        .profile-menu.show {
            display: block;
        }

        .profile-menu-item {
            padding: 0.75rem 1rem;
            color: #2e7d32;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .profile-menu-item:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .toggle-topbar {
            cursor: pointer;
            font-size: 1.2rem;
            color: #2e7d32;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .toggle-topbar:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .show-topbar-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background-color: #F9F5E8;
            padding: 0.5rem;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 1001;
            display: none;
        }

        .show-topbar-btn.show {
            display: block;
        }

        .show-topbar-btn span {
            font-size: 1.5rem;
            color: #2e7d32;
        }

        .hamburger-menu {
            display: none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            cursor: pointer;
        }

        .hamburger-icon {
            width: 30px;
            height: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hamburger-icon span {
            width: 100%;
            height: 3px;
            background-color: #2e7d32;
            transition: all 0.3s ease;
        }

        .hamburger-icon.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-icon.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-icon.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        .nav-menu {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            width: 250px;
            height: 100%;
            background-color: #F9F5E8;
            box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
            padding: 2rem 1rem;
            z-index: 999;
            flex-direction: column;
            gap: 1rem;
        }

        .nav-menu.show {
            display: flex;
        }

        .nav-menu a {
            color: #2e7d32;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .nav-menu a:hover {
            background-color: #4CAF50;
            color: #fff;
        }

        .nav-menu a#toggle-language-mobile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .main-content {
            padding: 5rem 2rem 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .forum-section {
            background: #F9F5E8;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .forum-section h2 {
            color: #2e7d32;
            font-size: 1.8rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .create-post-form {
            max-width: 600px;
            margin: 0 auto 2rem;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .create-post-form label {
            font-weight: 600;
            color: #333;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .create-post-form input,
        .create-post-form textarea {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #4CAF50;
            font-size: 14px;
            width: 100%;
        }

        .create-post-form textarea {
            resize: vertical;
            min-height: 100px;
        }

        .create-post-form .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
        }

        .create-post-form .error {
            color: #FF0000;
            font-size: 12px;
            margin-top: 5px;
        }

        .create-post-form button {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .create-post-form button:hover {
            background-color: #4CAF50;
        }

        .post-card {
            background: #F9F5E8;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .post-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
            border: 2px solid #4CAF50;
        }

        .post-header .post-author {
            color: #2e7d32;
            font-weight: 600;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .post-header .post-date {
            color: #555;
            font-size: 0.9rem;
            margin-left: auto;
        }

        .post-title {
            font-size: 1.4rem;
            color: #2e7d32;
            margin-bottom: 0.5rem;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .post-content {
            font-size: 1rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .post-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .post-actions a, .post-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
            text-decoration: none;
            color: white;
        }

        .post-actions .edit-btn {
            background-color: #2e7d32;
        }

        .post-actions .edit-btn:hover {
            background-color: #4CAF50;
        }

        .post-actions .delete-btn {
            background-color: #FF0000;
        }

        .post-actions .delete-btn:hover {
            background-color: #CC0000;
        }

        .post-actions .like-btn, .post-actions .dislike-btn {
            background-color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .post-actions .like-btn.active, .post-actions .dislike-btn.active {
            background-color: #2e7d32;
        }

        .post-actions .like-btn:hover, .post-actions .dislike-btn:hover {
            background-color: #388e3c;
        }

        .comment-section {
            margin-top: 1.5rem;
            padding-left: 2rem;
            border-left: 2px solid #4CAF50;
        }

        .comment-card {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .comment-header img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 0.75rem;
            border: 2px solid #4CAF50;
        }

        .comment-header .comment-author {
            color: #2e7d32;
            font-weight: 600;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .comment-header .comment-date {
            color: #555;
            font-size: 0.8rem;
            margin-left: auto;
        }

        .comment-content {
            font-size: 0.9rem;
            color: #333;
        }

        .comment-actions {
            display: flex;
            gap: 10px;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .comment-actions a {
            font-size: 0.8rem;
            text-decoration: none;
            font-family: "Bauhaus 93", Arial, sans-serif;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .comment-actions .delete-btn {
            background-color: #FF0000;
        }

        .comment-actions .delete-btn:hover {
            background-color: #CC0000;
        }

        .comment-actions .like-btn, .comment-actions .dislike-btn {
            background-color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .comment-actions .like-btn.active, .comment-actions .dislike-btn.active {
            background-color: #2e7d32;
        }

        .comment-actions .like-btn:hover, .comment-actions .dislike-btn:hover {
            background-color: #388e3c;
        }

        .comment-form {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .comment-form textarea {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #4CAF50;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
        }

        .comment-form button {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .comment-form button:hover {
            background-color: #4CAF50;
        }

        .comment-form .error {
            color: #FF0000;
            font-size: 12px;
            margin-top: 5px;
        }

        .edit-post-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 1.5rem;
            background: #F9F5E8;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .edit-post-form label {
            font-weight: 600;
            color: #333;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .edit-post-form input,
        .edit-post-form textarea {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #4CAF50;
            font-size: 14px;
            width: 100%;
        }

        .edit-post-form textarea {
            resize: vertical;
            min-height: 100px;
        }

        .edit-post-form .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
        }

        .edit-post-form .error {
            color: #FF0000;
            font-size: 12px;
            margin-top: 5px;
        }

        .edit-post-form .btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .edit-post-form .btn {
            padding: 10px 20px;
            font-size: 14px;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .edit-post-form .btn.submit {
            background-color: #2e7d32;
        }

        .edit-post-form .btn.submit:hover {
            background-color: #4CAF50;
        }

        .edit-post-form .btn.cancel {
            background-color: #FF0000;
        }

        .edit-post-form .btn.cancel:hover {
            background-color: #CC0000;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 8px 16px;
            background-color: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s;
        }

        .pagination a:hover {
            background-color: #4CAF50;
        }

        .pagination a.active {
            background-color: #4CAF50;
            cursor: default;
        }

        .pagination a.disabled {
            background-color: #a0a0a0;
            cursor: not-allowed;
        }

        .footer {
            background-color: #F9F5E8;
            color: #333;
            padding: 2rem;
            text-align: center;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        .footer-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .footer-column h3 {
            font-size: 1.2rem;
            color: #2e7d32;
            margin-bottom: 0.5rem;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .footer-column p, .footer-column a {
            font-size: 0.9rem;
            color: #2e7d32;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        .footer-column a:hover {
            color: #4CAF50;
        }

        .footer-bottom {
            margin-top: 1rem;
            font-size: 0.9rem;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        body.dark-mode {
            background-color: #2e7d32;
            color: #F9F5E8;
        }

        body.dark-mode .topbar,
        body.dark-mode .show-topbar-btn,
        body.dark-mode .nav-menu,
        body.dark-mode .footer {
            background-color: #F9F5E8;
        }

        body.dark-mode .forum-section,
        body.dark-mode .post-card,
        body.dark-mode .edit-post-form {
            background: #F9F5E8;
        }

        body.dark-mode .comment-card {
            background: #f9fafb;
        }

        body.dark-mode .forum-section h2,
        body.dark-mode .post-title,
        body.dark-mode .post-author,
        body.dark-mode .comment-author {
            color: #4CAF50;
        }

        body.dark-mode .post-content,
        body.dark-mode .comment-content,
        body.dark-mode .create-post-form label,
        body.dark-mode .edit-post-form label {
            color: #333;
        }

        body.dark-mode .create-post-form input,
        body.dark-mode .create-post-form textarea,
        body.dark-mode .edit-post-form input,
        body.dark-mode .edit-post-form textarea,
        body.dark-mode .comment-form textarea {
            background: #F9F5E8;
            color: #333;
            border-color: #4CAF50;
        }

        @media (max-width: 768px) {
            .topbar {
                display: none;
            }

            .hamburger-menu {
                display: block;
            }

            .main-content {
                padding: 4rem 1.5rem 1.5rem;
            }

            .forum-section h2 {
                font-size: 1.6rem;
            }

            .post-title {
                font-size: 1.2rem;
            }

            .comment-section {
                padding-left: 1rem;
            }

            .nav-menu {
                width: 100%;
            }

            .show-topbar-btn {
                top: 0.5rem;
                right: 3.5rem;
            }
        }

        @media (max-width: 480px) {
            .post-card,
            .edit-post-form {
                padding: 1rem;
            }

            .post-header img,
            .comment-header img {
                width: 30px;
                height: 30px;
            }

            .create-post-form,
            .edit-post-form {
                margin: 0 1rem;
            }

            .post-actions,
            .comment-actions {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
<!-- Topbar -->

<div class="topbar">
    <img src="logo.jpg" alt="Logo Green.tn" class="logo">
    <div class="nav-links">
        <a href="info2.php?page=accueil" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
        <a href="info2.php#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
        <a href="info2.php#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
        <a href="info2.php#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
        <a href="/projetweb/VIEW/FRONT/reparation-etat.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reparation-etat.php' ? 'active' : ''; ?>" data-translate="reparation"><i class="fas fa-wrench"></i> Réparation</a>
        <a href="../reclamation/liste_reclamations.php" data-translate="Reclamation"><i class="fas fa-envelope"></i> Reclamation</a>
        <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
        <a href="forum.php" class="active" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
        <a href="trajet-et-station.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'trajet-et-station.php' ? 'active' : ''; ?>" data-translate="trajets"><i class="fas fa-route"></i> Trajets et Stations</a>
        <a href="javascript:void(0)" id="toggle-dark-mode" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
        <a href="javascript:void(0)" id="toggle-language" data-translate="language">🌐 Français</a>
    </div>
    <div class="notif-comm-icon">
        <i class="fas fa-bell notif-comm-bell"></i>
        <span class="notif-comm-count <?php echo $unread_notif_comm_count > 0 ? 'show' : ''; ?>">
            <?php echo $unread_notif_comm_count; ?>
        </span>
        <div class="notif-comm-menu">
            <?php if (empty($notif_comm)): ?>
                <div class="notif-comm-item" data-translate="no_notif_comm">Aucune notification</div>
            <?php else: ?>
                <?php foreach ($notif_comm as $notif): ?>
                    <a href="forum.php#post-<?php echo $notif['post_id']; ?>" class="notif-comm-item <?php echo $notif['is_read'] ? 'read' : ''; ?>">
                        <?php echo htmlspecialchars($notif['content']); ?>
                        <br>
                        <small><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                    </a>
                <?php endforeach; ?>
                <a href="?action=mark_notif_comm_read" class="notif-comm-item" data-translate="mark_notif_comm_read">Marquer tout comme lu</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="profile-icon">
        <a href="javascript:void(0)">
            <img src="user_images/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profil" class="top-profile-pic">
        </a>
        <div class="profile-menu">
            <a href="info2.php?page=gestion_utilisateurs&action=infos" class="profile-menu-item" data-translate="profile_info">📄 Mes informations</a>
            <a href="logout.php" class="profile-menu-item logout" data-translate="logout">🚪 Déconnexion</a>
        </div>
    </div>
    <div class="toggle-topbar" onclick="toggleTopbar()">▼</div>
</div>

<!-- Show Topbar Button -->
<div class="show-topbar-btn" onclick="toggleTopbar()">
    <span>▲</span>
</div>

<!-- Hamburger Menu -->
<div class="hamburger-menu">
    <div class="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
    </div>
</div>
<div class="nav-menu">
    <a href="index.php?page=accueil" data-translate="home"><i class="fas fa-home"></i> Accueil</a>
    <a href="index.php#a-nos-velos" data-translate="bikes"><i class="fas fa-bicycle"></i> Nos vélos</a>
    <a href="index.php#a-propos-de-nous" data-translate="about"><i class="fas fa-info-circle"></i> À propos de nous</a>
    <a href="index.php#pricing" data-translate="pricing"><i class="fas fa-dollar-sign"></i> Tarifs</a>
    <a href="index.php#contact" data-translate="contact"><i class="fas fa-envelope"></i> Contact</a>
    <a href="reservationuser.php" data-translate="reservations"><i class="fas fa-calendar"></i> Réservations</a>
    <a href="forum.php" class="active" data-translate="forum"><i class="fas fa-comments"></i> Forum</a>
    <a href="javascript:void(0)" id="toggle-dark-mode-mobile" data-translate="dark_mode"><i class="fas fa-moon"></i> Mode Sombre</a>
    <a href="javascript:void(0)" id="toggle-language-mobile" data-translate="language">🌐 Français</a>
    <a href="index.php?page=gestion_utilisateurs&action=infos" data-translate="profile_info"><i class="fas fa-user"></i> Mes informations</a>
    <a href="logout.php" data-translate="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="forum-section">
        <h2 data-translate="forum_title">Forum</h2>
        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <!-- Error Messages -->
        <?php if (isset($errors['delete_post'])): ?>
            <div class="error"><?php echo htmlspecialchars($errors['delete_post']); ?></div>
        <?php endif; ?>
        <?php if (isset($errors['delete_comment'])): ?>
            <div class="error"><?php echo htmlspecialchars($errors['delete_comment']); ?></div>
        <?php endif; ?>
        <?php if (isset($errors['comment_content'])): ?>
            <div class="error"><?php echo htmlspecialchars($errors['comment_content']); ?></div>
        <?php endif; ?>
        <!-- Create Post Form -->
        <form method="POST" action="?action=create_post" class="create-post-form">
            <label for="post-title" data-translate="post_title">Titre</label>
            <input type="text" id="post-title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
            <?php if (isset($errors['title'])): ?>
                <div class="error"><?php echo $errors['title']; ?></div>
            <?php endif; ?>
            <label for="post-content" data-translate="post_content">Contenu</label>
            <textarea id="post-content" name="content" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
            <?php if (isset($errors['content'])): ?>
                <div class="error"><?php echo $errors['content']; ?></div>
            <?php endif; ?>
            <label class="checkbox-label">
                <input type="checkbox" name="is_anonymous" <?php echo isset($_POST['is_anonymous']) ? 'checked' : ''; ?>>
                <span data-translate="post_anonymously">Publier anonymement</span>
            </label>
            <button type="submit" data-translate="submit">Publier</button>
        </form>
        <!-- Edit Post Form -->
        <?php if (isset($_GET['action']) && $_GET['action'] === 'edit_post_form' && isset($_GET['post_id'])): ?>
            <?php
            $post_id = $_GET['post_id'];
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE post_id = ? AND id = ?");
            $stmt->execute([$post_id, $user_id]);
            $post_to_edit = $stmt->fetch();
            if ($post_to_edit):
            ?>
                <form method="POST" action="?action=edit_post" class="edit-post-form">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <label for="edit-title" data-translate="post_title">Titre</label>
                    <input type="text" id="edit-title" name="title" value="<?php echo htmlspecialchars($post_to_edit['title']); ?>" required>
                    <?php if (isset($errors['title'])): ?>
                        <div class="error"><?php echo $errors['title']; ?></div>
                    <?php endif; ?>
                    <label for="edit-content" data-translate="post_content">Contenu</label>
                    <textarea id="edit-content" name="content" required><?php echo htmlspecialchars($post_to_edit['content']); ?></textarea>
                    <?php if (isset($errors['content'])): ?>
                        <div class="error"><?php echo $errors['content']; ?></div>
                    <?php endif; ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_anonymous" <?php echo $post_to_edit['is_anonymous'] ? 'checked' : ''; ?>>
                        <span data-translate="post_anonymously">Publier anonymement</span>
                    </label>
                    <div class="btn-container">
                        <button type="submit" class="btn submit" data-translate="submit">Publier</button>
                        <a href="forum.php" class="btn cancel" data-translate="cancel">Annuler</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="error"><?php echo getTranslation('error_unauthorized', $language, $translations); ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <!-- Posts -->
        <?php foreach ($posts as $post): ?>
            <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
                <div class="post-header">
                    <img src="user_images/<?php echo htmlspecialchars($post['photo']); ?>" alt="Profil">
                    <span class="post-author"><?php echo $post['is_anonymous'] ? 'Anonymous' : htmlspecialchars($post['nom'] . ' ' . $post['prenom']); ?></span>
                    <span class="post-date"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                </div>
                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <div class="post-actions">
                    <?php if ($post['id'] === $user_id): ?>
                        <a href="?action=edit_post_form&post_id=<?php echo $post['post_id']; ?>" class="edit-btn" data-translate="edit">Modifier</a>
                        <a href="?action=delete_post&post_id=<?php echo $post['post_id']; ?>" class="delete-btn" data-translate="delete" onclick="return confirm('<?php echo getTranslation('confirm_delete_post', $language, $translations); ?>');">Supprimer</a>
                    <?php endif; ?>
                    <a href="?action=like_post&post_id=<?php echo $post['post_id']; ?>" class="like-btn <?php echo $post['user_liked'] ? 'active' : ''; ?>">
                        <i class="fas fa-thumbs-up"></i> <?php echo $post['likes']; ?>
                    </a>
                    <a href="?action=dislike_post&post_id=<?php echo $post['post_id']; ?>" class="dislike-btn <?php echo $post['user_disliked'] ? 'active' : ''; ?>">
                        <i class="fas fa-thumbs-down"></i> <?php echo $post['dislikes']; ?>
                    </a>
                </div>
                <!-- Comment Section -->
                <div class="comment-section">
                    <?php foreach ($comments[$post['post_id']] as $comment): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <img src="user_images/<?php echo htmlspecialchars($comment['photo']); ?>" alt="Profil">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['nom'] . ' ' . $comment['prenom']); ?></span>
                                <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <p class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            <div class="comment-actions">
                                <?php if ($comment['id'] === $user_id): ?>
                                    <a href="?action=delete_comment&comment_id=<?php echo $comment['comment_id']; ?>" class="delete-btn" data-translate="delete" onclick="return confirm('<?php echo getTranslation('confirm_delete_comment', $language, $translations); ?>');">Supprimer</a>
                                <?php endif; ?>
                                <a href="?action=like_comment&comment_id=<?php echo $comment['comment_id']; ?>" class="like-btn <?php echo $comment['user_liked'] ? 'active' : ''; ?>">
                                    <i class="fas fa-thumbs-up"></i> <?php echo $comment['likes']; ?>
                                </a>
                                <a href="?action=dislike_comment&comment_id=<?php echo $comment['comment_id']; ?>" class="dislike-btn <?php echo $comment['user_disliked'] ? 'active' : ''; ?>">
                                    <i class="fas fa-thumbs-down"></i> <?php echo $comment['dislikes']; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- Comment Form -->
                    <form method="POST" action="?action=add_comment" class="comment-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                        <textarea name="comment_content" placeholder="<?php echo getTranslation('comment_placeholder', $language, $translations); ?>" required></textarea>
                        <?php if (isset($errors['comment_content'])): ?>
                            <div class="error"><?php echo $errors['comment_content']; ?></div>
                        <?php endif; ?>
                        <button type="submit" data-translate="comment">Commenter</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page_num=<?php echo $page - 1; ?>" data-translate="previous_page">Page Précédente</a>
            <?php else: ?>
                <a class="disabled" data-translate="previous_page">Page Précédente</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page_num=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page_num=<?php echo $page + 1; ?>" data-translate="next_page">Page Suivante</a>
            <?php else: ?>
                <a class="disabled" data-translate="next_page">Page Suivante</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <div class="footer-container">
        <div class="footer-column">
            <h3 data-translate="footer_about">À propos</h3>
            <p data-translate="footer_about_text">Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.</p>
        </div>
        <div class="footer-column">
            <h3 data-translate="footer_contact">Contact</h3>
            <p><a href="tel:+21624531890">📞 2453 1890</a></p>
            <p><a href="mailto:contact@green.tn">📧 contact@green.tn</a></p>
            <p><a href="https://www.facebook.com/GreenTN" target="_blank">📱 Facebook</a></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p data-translate="footer_copyright">© 2025 Green.tn – Tous droits réservés</p>
    </div>
</div>

<script>
// Topbar Show/Hide Toggle
let isTopbarVisible = true;
function toggleTopbar() {
    const topbar = document.querySelector('.topbar');
    const showBtn = document.querySelector('.show-topbar-btn');
    if (isTopbarVisible) {
        topbar.classList.add('hidden');
        showBtn.classList.add('show');
    } else {
        topbar.classList.remove('hidden');
        showBtn.classList.remove('show');
    }
    isTopbarVisible = !isTopbarVisible;
}

// Scroll-based Topbar Hide/Show
let lastScrollTop = 0;
window.addEventListener('scroll', function() {
    let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    const topbar = document.querySelector('.topbar');
    const showBtn = document.querySelector('.show-topbar-btn');
    
    if (currentScroll > lastScrollTop && currentScroll > 100) {
        topbar.classList.add('hidden');
        showBtn.classList.add('show');
        isTopbarVisible = false;
    } else if (currentScroll < lastScrollTop) {
        topbar.classList.remove('hidden');
        showBtn.classList.remove('show');
        isTopbarVisible = true;
    }
    lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
});

// Profile Menu Toggle
document.querySelector('.top-profile-pic').addEventListener('click', function(event) {
    event.stopPropagation();
    document.querySelector('.profile-menu').classList.toggle('show');
    document.querySelector('.notif-comm-menu').classList.remove('show');
});

document.addEventListener('click', function(event) {
    if (!event.target.closest('.profile-icon')) {
        document.querySelector('.profile-menu').classList.remove('show');
    }
    if (!event.target.closest('.notif-comm-icon')) {
        document.querySelector('.notif-comm-menu').classList.remove('show');
    }
});

// Notif Comm Menu Toggle
document.querySelector('.notif-comm-bell').addEventListener('click', function(event) {
    event.stopPropagation();
    document.querySelector('.notif-comm-menu').classList.toggle('show');
    document.querySelector('.profile-menu').classList.remove('show');
});

// Hamburger Menu Toggle
document.querySelector('.hamburger-menu').addEventListener('click', function() {
    document.querySelector('.nav-menu').classList.toggle('show');
    document.querySelector('.hamburger-icon').classList.toggle('active');
});

// Dark Mode Toggle
if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark-mode');
}

document.getElementById('toggle-dark-mode').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
});

document.getElementById('toggle-dark-mode-mobile').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
});

// Translation functionality
let currentLanguage = localStorage.getItem('language') || '<?php echo $language; ?>';
let translations = {
    fr: {
        home: "Accueil",
        bikes: "Nos vélos",
        about: "À propos de nous",
        pricing: "Tarifs",
        contact: "Contact",
        reservations: "Réservations",
        forum: "Forum",
        dark_mode: "Mode Sombre",
        language: "Français",
        profile_info: "Mes informations",
        logout: "Déconnexion",
        forum_title: "Green.tn - Forum",
        create_post: "Créer un post",
        post_title: "Titre",
        post_content: "Contenu",
        post_anonymously: "Publier anonymement",
        submit: "Publier",
        edit: "Modifier",
        delete: "Supprimer",
        like: "J'aime",
        dislike: "Je n'aime pas",
        comment: "Commenter",
        comment_placeholder: "Écrivez votre commentaire...",
        post_deleted_success: "Post supprimé avec succès.",
        comment_deleted_success: "Commentaire supprimé avec succès.",
        error_delete_failed: "Échec de la suppression.",
        footer_about: "À propos",
        footer_about_text: "Green.tn promeut une mobilité durable à travers des solutions de location de vélos écologiques en Tunisie.",
        footer_contact: "Contact",
        footer_copyright: "© 2025 Green.tn – Tous droits réservés",
        error_post_title_short: "Le titre doit contenir au moins 5 caractères",
        error_post_content_short: "Le contenu doit contenir au moins 10 caractères",
        error_comment_content_short: "Le commentaire doit contenir au moins 5 caractères",
        error_unauthorized: "Action non autorisée",
        error_like_failed: "Échec de l'action like/dislike",
        previous_page: "Page Précédente",
        next_page: "Page Suivante",
        new_comment_notif_comm_subject: "Nouveau commentaire sur votre publication",
        new_comment_notif_comm_body: "Bonjour %s,<br><br>%s a ajouté un nouveau commentaire sur votre publication : <br><blockquote>%s</blockquote><br><a href='%s'>Voir la publication</a><br><br>Cordialement,<br>L'équipe Green.tn",
        new_comment_notif_comm_content: "%s a commenté votre publication.",
        comment_added_success: "Commentaire ajouté avec succès.",
        confirm_delete_comment: "Êtes-vous sûr de vouloir supprimer ce commentaire ?",
        confirm_delete_post: "Êtes-vous sûr de vouloir supprimer cette publication ?",
        error_comment_failed: "Échec de l'ajout du commentaire.",
        no_notif_comm: "Aucune notification",
        mark_notif_comm_read: "Marquer tout comme lu"
    },
    en: {
        home: "Home",
        bikes: "Bikes",
        about: "About Us",
        pricing: "Pricing",
        contact: "Contact",
        reservations: "Reservations",
        forum: "Forum",
        dark_mode: "Dark Mode",
        language: "English",
        profile_info: "My Information",
        logout: "Logout",
        forum_title: "Green.tn - Forum",
        create_post: "Create a Post",
        post_title: "Title",
        post_content: "Content",
        post_anonymously: "Post Anonymously",
        submit: "Post",
        edit: "Edit",
        delete: "Delete",
        like: "Like",
        dislike: "Dislike",
        comment: "Comment",
        comment_placeholder: "Write your comment...",
        post_deleted_success: "Post deleted successfully.",
        comment_deleted_success: "Comment deleted successfully.",
        error_delete_failed: "Failed to delete.",
        footer_about: "About",
        footer_about_text: "Green.tn promotes sustainable mobility through eco-friendly bike rental solutions in Tunisia.",
        footer_contact: "Contact",
        footer_copyright: "© 2025 Green.tn – All rights reserved",
        error_post_title_short: "Title must be at least 5 characters long",
        error_post_content_short: "Content must be at least 10 characters long",
        error_comment_content_short: "Comment must be at least 5 characters long",
        error_unauthorized: "Unauthorized action",
        error_like_failed: "Failed to perform like/dislike action",
        previous_page: "Previous Page",
        next_page: "Next Page",
        new_comment_notif_comm_subject: "New Comment on Your Post",
        new_comment_notif_comm_body: "Hello %s,<br><br>%s has added a new comment on your post: <br><blockquote>%s</blockquote><br><a href='%s'>View the post</a><br><br>Best regards,<br>The Green.tn Team",
        new_comment_notif_comm_content: "%s commented on your post.",
        comment_added_success: "Comment added successfully.",
        confirm_delete_comment: "Are you sure you want to delete this comment?",
        confirm_delete_post: "Are you sure you want to delete this post?",
        error_comment_failed: "Failed to add the comment.",
        no_notif_comm: "No notifications",
        mark_notif_comm_read: "Mark all as read"
    }
};

fetch('/assets/translations.json')
    .then(response => response.json())
    .then(data => {
        translations = { ...translations, ...data };
        applyTranslations(currentLanguage);
    })
    .catch(error => {
        console.error('Error loading translations:', error);
        applyTranslations(currentLanguage);
    });

function applyTranslations(lang) {
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translations[lang] && translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });

    document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
        const key = element.getAttribute('data-translate-placeholder');
        if (translations[lang] && translations[lang][key]) {
            element.placeholder = translations[lang][key];
        }
    });

    const langButton = document.getElementById('toggle-language');
    const langButtonMobile = document.getElementById('toggle-language-mobile');
    if (langButton) {
        langButton.textContent = `🌐 ${translations[lang]['language']}`;
    }
    if (langButtonMobile) {
        langButtonMobile.textContent = `🌐 ${translations[lang]['language']}`;
    }

    document.title = translations[lang]['forum_title'] || 'Green.tn - Forum';
    localStorage.setItem('language', lang);
}

function toggleLanguage() {
    currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
    applyTranslations(currentLanguage);
    window.location.href = `?lang=${currentLanguage}${window.location.search.replace(/lang=[a-z]{2}/, '')}`;
}

document.getElementById('toggle-language')?.addEventListener('click', toggleLanguage);
document.getElementById('toggle-language-mobile')?.addEventListener('click', toggleLanguage);

applyTranslations(currentLanguage);
</script>
</body>
</html>