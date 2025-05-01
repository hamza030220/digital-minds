<?php
// Start output buffering
ob_start();
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', 'C:\xampp\php\logs\php_error_log'); // Set error log path

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$username = $isLoggedIn ? $_SESSION['username'] : null;
$isAdmin = $isLoggedIn && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1;

// Database connection
require_once 'db_connect.php';

// Include PHPMailer for email functionality
require 'C:/xampp/htdocs/old/Forum/phpmailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/old/Forum/phpmailer/src/SMTP.php';
require 'C:/xampp/htdocs/old/Forum/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// For AJAX requests
if (isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');

    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in']);
        exit;
    }

    try {
        switch ($_POST['action']) {
            case 'create_post': 
                if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }

                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                $isAnonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1' ? 1 : 0;

                if (empty($title) || empty($content)) {
                    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO post (user_id, title, content, created_at, is_deleted, is_anonymous) VALUES (?, ?, ?, NOW(), 0, ?)");
                $stmt->execute([$userId, $title, $content, $isAnonymous]);
                echo json_encode(['success' => true]);
                exit;
            case 'add_comment':
                handleAddComment($conn, $userId);
                break;
            case 'delete_post':
                if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                $postId = intval($_POST['post_id']);
                
                // Verify ownership or admin status
                $checkStmt = $conn->prepare("SELECT user_id FROM post WHERE post_id = ?");
                $checkStmt->execute([$postId]);
                $post = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$post) {
                    echo json_encode(['success' => false, 'message' => 'Post not found']);
                    exit;
                }
                
                // Check if user is owner or admin
                if ($post['user_id'] != $userId && !$isAdmin) {
                    echo json_encode(['success' => false, 'message' => 'Not authorized to delete this post']);
                    exit;
                }
                
                // Soft delete the post
                $stmt = $conn->prepare("UPDATE post SET is_deleted = 1 WHERE post_id = ?");
                $stmt->execute([$postId]);
                
                echo json_encode(['success' => true]);
                exit;
            case 'update_post':
                if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                $postId = intval($_POST['post_id']);
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                
                if (empty($title) || empty($content)) {
                    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
                    exit;
                }
                
                // Verify ownership or admin status
                $checkStmt = $conn->prepare("SELECT user_id FROM post WHERE post_id = ?");
                $checkStmt->execute([$postId]);
                $post = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$post) {
                    echo json_encode(['success' => false, 'message' => 'Post not found']);
                    exit;
                }
                
                // Check if user is owner or admin
                if ($post['user_id'] != $userId && !$isAdmin) {
                    echo json_encode(['success' => false, 'message' => 'Not authorized to edit this post']);
                    exit;
                }
                
                // Update the post
                $stmt = $conn->prepare("UPDATE post SET title = ?, content = ?, updated_at = NOW() WHERE post_id = ?");
                $stmt->execute([$title, $content, $postId]);
                
                echo json_encode(['success' => true]);
                exit;
            case 'update_comment':
                $commentId = intval($_POST['comment_id']);
                $content = trim($_POST['content']);

                if (empty($content)) {
                    echo json_encode(['success' => false, 'message' => 'Comment content is required']);
                    exit;
                }

                // Verify ownership or admin status
                $checkStmt = $conn->prepare("SELECT user_id FROM commentaire WHERE comment_id = ?");
                $checkStmt->execute([$commentId]);
                $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$comment) {
                    echo json_encode(['success' => false, 'message' => 'Comment not found']);
                    exit;
                }

                if ($comment['user_id'] != $userId && !$isAdmin) {
                    echo json_encode(['success' => false, 'message' => 'Not authorized to edit this comment']);
                    exit;
                }

                $stmt = $conn->prepare("UPDATE commentaire SET content = ?, updated_at = NOW() WHERE comment_id = ?");
                $stmt->execute([$content, $commentId]);

                echo json_encode(['success' => true]);
                exit;
            case 'delete_comment':
                $commentId = intval($_POST['comment_id']);

                // Verify ownership or admin status
                $checkStmt = $conn->prepare("SELECT user_id FROM commentaire WHERE comment_id = ?");
                $checkStmt->execute([$commentId]);
                $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$comment) {
                    echo json_encode(['success' => false, 'message' => 'Comment not found']);
                    exit;
                }

                if ($comment['user_id'] != $userId && !$isAdmin) {
                    echo json_encode(['success' => false, 'message' => 'Not authorized to delete this comment']);
                    exit;
                }

                $stmt = $conn->prepare("UPDATE commentaire SET is_deleted = 1 WHERE comment_id = ?");
                $stmt->execute([$commentId]);

                echo json_encode(['success' => true]);
                exit;
            default:
                throw new Exception('Unknown action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Function to handle comment submissions
function handleAddComment($conn, $userId) {
    if (!isset($_POST['post_id']) || !isset($_POST['content']) || !isset($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $postId = intval($_POST['post_id']);
    $content = trim($_POST['content']);

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment content is required']);
        exit;
    }

    // Insert the comment
    $stmt = $conn->prepare("INSERT INTO commentaire (post_id, user_id, content, created_at, is_deleted) VALUES (?, ?, ?, NOW(), 0)");
    $result = $stmt->execute([$postId, $userId, $content]);

    if ($result) {
        // Fetch post creator's email and name
        $postStmt = $conn->prepare("SELECT u.email, u.username, p.title 
                                    FROM post p 
                                    JOIN users u ON p.user_id = u.id 
                                    WHERE p.post_id = ?");
        $postStmt->execute([$postId]);
        $postCreator = $postStmt->fetch(PDO::FETCH_ASSOC);

        // Debug: Log the SQL query and the fetched data
        error_log("SQL Query: SELECT u.email, u.username, p.title FROM post p JOIN users u ON p.user_id = u.id WHERE p.post_id = $postId");
        error_log("Post Creator Data: " . print_r($postCreator, true));

        if ($postCreator) {
            // Send the email
            sendEmailToPostCreator($postCreator['email'], $postCreator['username'], $postCreator['title'], $content);
        } else {
            echo json_encode(['success' => false, 'message' => 'Post creator not found']);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save comment']);
    }
    exit;
}

// Function to send email notification to post creator
function sendEmailToPostCreator($recipientEmail, $recipientName, $postTitle, $commentContent) {
    $mail = new PHPMailer(true);

    try {
        // Debug: Log the email parameters
        error_log("Sending email to: $recipientEmail");
        error_log("Recipient Name: $recipientName");
        error_log("Post Title: $postTitle");
        error_log("Comment Content: $commentContent");

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'selminaama73@gmail.com';
        $mail->Password = 'zcij nscr ehle fosj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender
        $mail->setFrom('selminaama73@gmail.com', 'Forum Notifications');

        // Recipient
        $mail->addAddress($recipientEmail, $recipientName);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Nouveau commentaire sur votre post : ' . $postTitle;
        $mail->Body = "
            <p>Bonjour $recipientName,</p>
            <p>Un nouveau commentaire a été ajouté à votre post intitulé <strong>$postTitle</strong>.</p>
            <p><strong>Commentaire :</strong></p>
            <blockquote style='border-left: 4px solid #ccc; padding-left: 10px; color: #555;'>
                $commentContent
            </blockquote>
            <p>Vous pouvez consulter votre post et répondre au commentaire en vous rendant sur le forum.</p>
            <p>Meilleures salutations,<br>L'équipe du Forum</p>
        ";

        $mail->send();
        error_log("Email sent successfully to $recipientEmail");
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    }
}

// Add CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum de Discussion - Green In</title>
    <link rel="stylesheet" href="../green.css"> <!-- Include green.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional forum-specific styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #58b687;
            color: #333;
            line-height: 1.6;
            padding-top: 100px; /* Adjust this value to match the height of your header */
        }

        header {
            position: fixed; /* Keeps the header fixed at the top */
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background-color: white; /* Ensure the header has a background color */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Optional: Add a shadow for better visibility */
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .card {
            background-color: #fbf9f1;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #2e7d32;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            border-bottom: 1px solid #58b687;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            color: #378058;
            font-size: 24px;
            margin: 0;
            font-family: 'Comic Sans MS', cursive, sans-serif;
        }

        h2 {
            color: #378058;
            font-size: 20px;
            margin-top: 0;
        }

        h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .section-header {
            color: #378058;
            font-size: 22px;
            margin: 20px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #58b687;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            position: relative; /* Nécessaire pour positionner le pseudo-élément */
            z-index: 1; /* Assure que le texte reste au-dessus */
        }

        .section-header::after {
            content: ''; /* Nécessaire pour afficher le pseudo-élément */
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff; /* Couleur blanche */
            z-index: -1; /* Place l'élément derrière le texte */
            border-radius: 8px; /* Facultatif : coins arrondis */
            padding: 5px;
        }
        

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .required-label::after {
            content: " *";
            color: #e53935;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .error-message {
            color: #e53935;
            font-size: 14px;
            margin-top: 5px;
            display: none; /* Hidden by default */
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #60BA97; /* New background color */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #45a049; /* Slightly darker shade for hover effect */
        }

        .btn-return {
            background-color: #60BA97; /* New background color */
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-return:hover {
            background-color: #45a049; /* Slightly darker shade for hover effect */
        }

        .btn-secondary {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        /* Posts and Comments */
        .discussion-list {
            margin-top: 20px;
        
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        .discussion-item {
            background: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 5px solid #2e7d32;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .discussion-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .discussion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .discussion-title {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        
        .discussion-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .profile-picture {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .discussion-content {
            margin-bottom: 10px;
        }

        /* User Badge */
        .admin-badge {
            background-color: #f0ad4e;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
        }

        .anonymous-badge {
            background-color: #ccc;
            color: #333;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
        }
        
        /* Anonymous post styling */
        /* Anonymous post styling */
        .discussion-meta .anonymous-user {
            font-style: italic;
            color: #666;
            background-color: #f8f8f8;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .anonymous-post .discussion-meta {
            color: #777;
            background-color: #f7f7f7;
            padding: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .anonymous-post .profile-picture {
            opacity: 0.8;
            filter: grayscale(30%);
        }
        
        .anonymous-post .discussion-meta strong {
            font-style: italic;
            color: #666;
        }
        
        .discussion-meta span {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        /* Icon Buttons */
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-right: 5px;
        }

        .btn-icon.edit-post-btn {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .btn-icon.delete-post-btn {
            background: #FFEBEE;
            color: #e53935;
        }

        .btn-icon.reply-post-btn {
            background: #FBE9E7;
            color: #FF5722;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        .reply-icon {
            color: #f05d23;
            margin-right: 5px;
        }

        .no-comments {
            color: #666;
            font-style: italic;
        }

        /* Loading Indicator */
        .loading-container {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        .loading {
            width: 30px;
            height: 30px;
            border: 3px solid #ccc;
            border-top: 3px solid #2E7D32;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            padding: 10px 0;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .alert-info {
            background-color: #e3f2fd;
            color: #0d47a1;
            border-left: 4px solid #2196f3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .btn-icon {
                width: 30px;
                height: 30px;
            }
        }
        
        /* Comments Styles */
        .comments-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .comments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .comments-header h4 {
            margin: 0;
        }
        
        .comments-list {
            margin-top: 10px;
        }
        
        .comment {
            padding: 8px 12px;
            background-color: #f8f8f8;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid #58b687;
        }
        
        .comment-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .comment-content {
            font-size: 14px;
        }
        
        .reply-form {
            margin-top: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        
        .no-comments {
            font-style: italic;
            color: #666;
            margin: 10px 0;
        }
        
        /* Feedback Messages */
        .feedback-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
        }
        
        .feedback-message.success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }
        
        .feedback-message.error {
            background-color: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #F44336;
        }

        header {
            margin-bottom: 20px; /* Adds space below the header */
        }

        .login {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .login:hover {
            background-color: #45a049;
        }

        .logout {
            background-color: #60BA97; /* Couleur de fond */
            color: white; /* Couleur du texte */
            padding: 10px 20px; /* Espacement interne */
            border-radius: 5px; /* Coins arrondis */
            text-decoration: none; /* Supprime le soulignement */
            font-weight: bold; /* Texte en gras */
            border: 1px solid #60BA97; /* Bordure de la même couleur */
            transition: background-color 0.3s ease; /* Effet de transition */
        }

        .logout:hover {
            background-color: #45a049; /* Couleur au survol */
        }

        .profile-picture {
            width: 30px; /* Reduce the width */
            height: 30px; /* Reduce the height */
            border-radius: 50%; /* Keep it circular */
            object-fit: cover; /* Ensure the image fits within the circle */
            margin-right: 8px; /* Adjust spacing between the image and text */
            vertical-align: middle; /* Align it with the text */
        }

        .profile-picture-header {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Header from green.html -->
    <header>
        <section class="logo-nav-container">
            <section class="logo">
                <a href="../green.html">
                    <img src="../image/ve.png" alt="Green.tn Logo">
                </a>
            </section>
            <nav class="nav-left">
                <ul>
                    <li><a href="../green.html#accueil">Accueil</a></li>
                    <li><a href="../green.html#a-nos-velos">Nos vélos</a></li>
                    <li><a href="../green.html#a-propos-de-nous">À propos de nous</a></li>
                    <li><a href="../green.html#pricing">Tarifs</a></li>
                    <li><a href="../green.html#contact">Contact</a></li>
                    <li><a href="forum.php" class="active">Forum</a></li>
                </ul>
            </nav>
        </section>
        <nav class="nav-right">
            <ul>
                <?php if ($isLoggedIn): ?>
                    <li>
                        <img src="../image/profile.jpg" alt="User Profile" class="profile-picture-header">
                        <span class="user-name"><?php echo htmlspecialchars($username === 'root' ? 'user' : $username); ?></span>
                    </li>
                    <li><a href="signout.php" class="logout">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="signin.php" class="login">Connexion</a></li>
                    <li><a href="signup.php" class="signin">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="page-header">
                <h1>Forum de Discussion</h1>
                <a href="../green.html" class="btn-return">Retour à l'accueil</a>
            </div>
            <p>Bienvenue dans le forum de discussion Green In</p>
        </div>
        
        <div id="feedbackMessage" class="feedback-message"></div>
        
        <?php if ($isLoggedIn): ?>
            <div class="card">
                <h2>Nouvelle Discussion</h2>
                <form id="newPostForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label for="postTitle" class="required-label">Titre</label>
                        <input type="text" id="postTitle" class="form-control">
                        <div class="error-message" id="titleError"></div> <!-- Error message container -->
                    </div>
                    <div class="form-group">
                        <label for="postContent" class="required-label">Contenu</label>
                        <textarea id="postContent" class="form-control"></textarea>
                        <div class="error-message" id="contentError"></div> <!-- Error message container -->
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="isAnonymous" name="is_anonymous">
                            Poster anonymement
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Publier</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>Veuillez vous <a href="signin.php">connecter</a> ou vous <a href="signup.php">inscrire</a> pour participer au forum.</p>
            </div>
        <?php endif; ?>
        
        

        <h2 class="section-header">Discussions récentes</h2>
        
        <div class="discussion-list" id="postsList">
            <div id="postsLoading" class="loading-container">
                <div class="loading"></div>
            </div>
            <!-- Posts will be loaded here via JavaScript -->
            <div id="postsPlaceholder"></div>
        </div>
    </div>

    <script>
        // Utility function to escape HTML and prevent XSS
        function escapeHtml(string) {
            if (!string) return '';
            return String(string)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Format date function
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR') + ' ' + 
                   date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }

        // Load posts
        function loadPosts() {
            const postsList = document.getElementById('postsList');
            const loadingElement = document.getElementById('postsLoading');

            if (!postsList || !loadingElement) {
                console.error('Required elements not found');
                return;
            }

            // Show the loading spinner
            loadingElement.style.display = 'flex';

            // Fetch posts from the server
            fetch('get_posts.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide the loading spinner
                    loadingElement.style.display = 'none';

                    if (data.success && data.posts && data.posts.length > 0) {
                        let html = '';
                        data.posts.forEach(post => {
                            html += createPostHTML(post);
                        });
                        postsList.innerHTML = html;

                        // Add event listeners to the new buttons
                        addPostEventListeners();
                    } else {
                        postsList.innerHTML = '<p>Aucune discussion disponible.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading posts:', error);
                    loadingElement.style.display = 'none';
                    postsList.innerHTML = '<p>Une erreur est survenue lors du chargement des discussions.</p>';
                });
        }

        // Create HTML for a post
        // Create HTML for a post
        function createPostHTML(post) {
            // Convert anonymous flag to boolean using Number for reliable type conversion
            const isAnonymous = Number(post.is_anonymous) === 1;
            
            // Fix isAdmin logic
            const isAdmin = Number(post.is_admin) === 1;
            
            // Convert IDs to numbers to ensure proper comparison
            const postUserId = Number(post.user_id);
            const currentUserId = Number(post.current_user_id);
            
            // Fix canEditOrDelete logic
            const canEditOrDelete = post.is_logged_in && 
                (Number(post.current_user_id) === Number(post.user_id) || 
                (isAdmin && post.current_user_id));
            
            // Track if post has comments
            const hasComments = post.comments && post.comments.length > 0;
            
            return `
                <div class="discussion-item ${isAnonymous ? 'anonymous-post' : ''}" id="post-${post.post_id}" data-post-id="${post.post_id}">
    <div class="discussion-header">
        <h3 class="discussion-title">${escapeHtml(post.title)}</h3>
        <div class="discussion-meta">
            <img src="${isAnonymous ? '../image/anonymous.jpg' : '../image/profile.jpg'}" 
                 alt="${isAnonymous ? 'Anonymous User' : 'User Profile'}" 
                 class="profile-picture">
            Par <strong>${isAnonymous ? 'Anonyme' : escapeHtml(post.username)}</strong>
            ${isAdmin && !isAnonymous ? '<span class="admin-badge">Admin</span>' : ''} 
            le ${formatDate(post.created_at)}
        </div>
                    </div>
                    <div class="discussion-content">
                        <div class="post-content">
                            <p id="post-content-${post.post_id}">${escapeHtml(post.content)}</p>
                        
                        </div>
                    </div>
                    <div class="post-actions">
                        <div class="action-buttons">
                            ${post.is_logged_in ? `
                                <button class="btn-icon reply-post-btn" data-post-id="${post.post_id}" title="Répondre">
                                    <i class="fas fa-reply"></i>
                                </button>
                                ${canEditOrDelete ? `
                                    <button class="btn-icon edit-post-btn" data-post-id="${post.post_id}" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon delete-post-btn" data-post-id="${post.post_id}" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                ` : ''}
                                <button class="btn translate-btn" data-id="${post.post_id}" data-type="post">Traduire</button>
                            ` : `
                                <span class="login-prompt">Connectez-vous pour interagir</span>
                            `}
                        </div>
                    </div>
                    <div class="comments-section" id="comments-${post.post_id}">
                        ${hasComments ? `
                            <div class="comments-header">
                                <h4>Commentaires (${post.comments.length})</h4>
                                <button class="btn-icon view-comments-btn" 
                                        data-post-id="${post.post_id}" 
                                        title="Voir les commentaires" 
                                        data-state="closed"
                                        data-comment-count="${post.comments.length}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="comments-list" style="display: none;">
                                <!-- Comments will be loaded dynamically -->
                            </div>
                        ` : `
                            <div class="no-comments">Aucun commentaire pour le moment.</div>
                        `}
                    </div>
                </div>
            `;
        }
        // Add event listeners to post buttons
        function addPostEventListeners() {
            // Reply buttons
            document.querySelectorAll('.reply-post-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const postId = this.getAttribute('data-post-id');
                    showReplyForm(postId);
                });
            });

            // Edit buttons
            document.querySelectorAll('.edit-post-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const postId = this.getAttribute('data-post-id');
                    showEditForm(postId);
                });
            });

            // Delete buttons
            document.querySelectorAll('.delete-post-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const postId = this.getAttribute('data-post-id');
                    if (confirm('Êtes-vous sûr de vouloir supprimer cette discussion?')) {
                        deletePost(postId);
                    }
                });
            });
            // Edit comment buttons
    document.querySelectorAll('.edit-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.getAttribute('data-comment-id');
            showEditCommentForm(commentId);
        });
    });

    // Delete comment buttons
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.getAttribute('data-comment-id');
            if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire?')) {
                deleteComment(commentId);
            }
        });
    });
        }

        // Delete a post
        function deletePost(postId) {
            if (!postId) return;
            
            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            const formData = new FormData();
            formData.append('action', 'delete_post');
            formData.append('post_id', postId);
            formData.append('csrf_token', csrfToken);
            
            fetch('forum.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the post from DOM
                    const postElement = document.getElementById(`post-${postId}`);
                    if (postElement) {
                        postElement.remove();
                        showFeedback('Post supprimé avec succès', 'success');
                    }
                } else {
                    showFeedback(data.message || 'Erreur lors de la suppression', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting post:', error);
                showFeedback('Une erreur est survenue', 'error');
            });
        }
        
        // Show edit form for a post
        function showEditForm(postId) {
            const postElement = document.getElementById(`post-${postId}`);
            if (!postElement) return;
            
            // Get current post data
            const titleElement = postElement.querySelector('.discussion-title');
            const contentElement = postElement.querySelector('.discussion-content p');
            
            if (!titleElement || !contentElement) return;
            
            const currentTitle = titleElement.textContent;
            const currentContent = contentElement.textContent;
            
            // Store original content
            postElement.dataset.originalTitle = currentTitle;
            postElement.dataset.originalContent = currentContent;
            
            // Replace with edit form
            const formHTML = `
                <div class="edit-form" id="edit-form-${postId}">
                    <div class="form-group">
                        <label for="edit-title-${postId}">Titre</label>
                        <input type="text" id="edit-title-${postId}" class="form-control" value="${escapeHtml(currentTitle)}" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-content-${postId}">Contenu</label>
                        <textarea id="edit-content-${postId}" class="form-control" required>${escapeHtml(currentContent)}</textarea>
                    </div>
                    <button type="button" class="btn btn-primary save-edit-btn" data-post-id="${postId}">Enregistrer</button>
                    <button type="button" class="btn cancel-edit-btn" data-post-id="${postId}">Annuler</button>
                </div>
            `;
            
            // Hide original content
            titleElement.style.display = 'none';
            contentElement.parentElement.style.display = 'none';
            
            // Insert edit form
            contentElement.parentElement.insertAdjacentHTML('afterend', formHTML);
            
            // Add event listeners
            document.querySelector(`#edit-form-${postId} .save-edit-btn`).addEventListener('click', function() {
                saveEditedPost(postId);
            });
            
            document.querySelector(`#edit-form-${postId} .cancel-edit-btn`).addEventListener('click', function() {
                cancelEdit(postId);
            });
        }
        
        // Save edited post
        function saveEditedPost(postId) {
            const titleInput = document.getElementById(`edit-title-${postId}`);
            const contentInput = document.getElementById(`edit-content-${postId}`);
            
            if (!titleInput || !contentInput) return;
            
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            if (!title || !content) {
                alert('Veuillez remplir tous les champs requis.');
                return;
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            const formData = new FormData();
            formData.append('action', 'update_post');
            formData.append('post_id', postId);
            formData.append('title', title);
            formData.append('content', content);
            formData.append('csrf_token', csrfToken);
            
            fetch('forum.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the post in DOM
                    const postElement = document.getElementById(`post-${postId}`);
                    const titleElement = postElement.querySelector('.discussion-title');
                    const contentElement = postElement.querySelector('.discussion-content p');
                    
                    // Update content
                    titleElement.textContent = title;
                    contentElement.textContent = content;
                    
                    // Show original elements
                    titleElement.style.display = '';
                    contentElement.parentElement.style.display = '';
                    
                    // Remove edit form
                    const editForm = document.getElementById(`edit-form-${postId}`);
                    if (editForm) {
                        editForm.remove();
                    }
                    
                    showFeedback('Post modifié avec succès', 'success');
                } else {
                    showFeedback(data.message || 'Erreur lors de la modification', 'error');
                }
            })
            .catch(error => {
                console.error('Error updating post:', error);
                showFeedback('Une erreur est survenue', 'error');
            });
        }
        
        // Cancel post editing
        function cancelEdit(postId) {
            const postElement = document.getElementById(`post-${postId}`);
            if (!postElement) return;
            
            // Get the original elements
            const titleElement = postElement.querySelector('.discussion-title');
            const contentElement = postElement.querySelector('.discussion-content p');
            
            // Show original elements
            titleElement.style.display = '';
            contentElement.parentElement.style.display = '';
            
            // Remove edit form
            const editForm = document.getElementById(`edit-form-${postId}`);
            if (editForm) {
                editForm.remove();
            }
        }

        // Submit a comment
        function submitComment(postId) {
            const commentContent = document.getElementById(`comment-content-${postId}`);
            if (!commentContent) return;
            
            const content = commentContent.value.trim();
            if (!content) {
                alert('Le commentaire ne peut pas être vide.');
                return;
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('post_id', postId);
            formData.append('content', content);
            formData.append('csrf_token', csrfToken);
            
            fetch('forum.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the reply form
                    const replyForm = document.getElementById(`reply-form-${postId}`);
                    if (replyForm) {
                        replyForm.remove();
                    }
                    
                    // Reload posts to show the new comment
                    loadPosts();
                    showFeedback('Commentaire ajouté avec succès', 'success');
                } else {
                    showFeedback(data.message || 'Erreur lors de l\'ajout du commentaire', 'error');
                }
            })
            .catch(error => {
                console.error('Error submitting comment:', error);
                showFeedback('Une erreur est survenue', 'error');
            });
        }
        
        function showReplyForm(postId) {
            // Find the comments section for this post
            const commentsSection = document.querySelector(`#comments-${postId}`);
            if (!commentsSection) return;
            
            // Create form if it doesn't exist
            if (!document.getElementById(`reply-form-${postId}`)) {
                const replyForm = document.createElement('div');
                replyForm.id = `reply-form-${postId}`;
                replyForm.className = 'reply-form';
                replyForm.innerHTML = `
                    <h4>Répondre</h4>
                    <div class="form-group">
                        <textarea id="comment-content-${postId}" class="form-control" required></textarea>
                    </div>
                    <button type="button" class="btn btn-primary submit-comment" data-post-id="${postId}">Envoyer</button>
                    <button type="button" class="btn cancel-reply" data-post-id="${postId}">Annuler</button>
                `;
                
                commentsSection.appendChild(replyForm);
                
                // Add event listeners to the new form
                document.querySelector(`#reply-form-${postId} .submit-comment`).addEventListener('click', function() {
                    submitComment(postId);
                });
                
                document.querySelector(`#reply-form-${postId} .cancel-reply`).addEventListener('click', function() {
                    replyForm.remove();
                });
            }
        }
        
        // Submit new post
        document.addEventListener('DOMContentLoaded', function() {
            // Load posts when the page loads
            loadPosts();
            
            const newPostForm = document.getElementById('newPostForm');
            if (newPostForm) {
                newPostForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const title = document.getElementById('postTitle').value.trim();
                    const content = document.getElementById('postContent').value.trim();
                    const isAnonymous = document.getElementById('isAnonymous').checked ? '1' : '0';
                    const csrfToken = this.querySelector('input[name="csrf_token"]').value;
                    // Clear previous error messages
                    document.getElementById('titleError').style.display = 'none';
                    document.getElementById('contentError').style.display = 'none';

                    let hasError = false;

                    // Validation for title
                    if (!title) {
                        const titleError = document.getElementById('titleError');
                        titleError.textContent = 'Le titre est requis.';
                        titleError.style.display = 'block';
                        hasError = true;
                    }

                    // Validation for content
                    if (!content) {
                        const contentError = document.getElementById('contentError');
                        contentError.textContent = 'Le contenu est requis.';
                        contentError.style.display = 'block';
                        hasError = true;
                    }

                    // Stop form submission if there are errors
                    if (hasError) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'create_post');
                    formData.append('title', title);
                    formData.append('content', content);
                    formData.append('is_anonymous', isAnonymous);
                    formData.append('csrf_token', csrfToken);

                    fetch('forum.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('postTitle').value = '';
                            document.getElementById('postContent').value = '';
                            document.getElementById('isAnonymous').checked = false;
                            loadPosts();
                            showFeedback('Discussion publiée avec succès!', 'success');
                        } else {
                            showFeedback(data.message || 'Une erreur est survenue', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error creating post:', error);
                        showFeedback('Une erreur est survenue lors de la création du post', 'error');
                    });
                });
            }
            
            // Function to load comments for a post
            function loadComments(postId, offset = 0, limit = 4) {
                console.log(`Loading comments for post ID: ${postId}, offset: ${offset}, limit: ${limit}`); // Debugging
                const commentsList = document.querySelector(`#comments-${postId} .comments-list`);
                const viewCommentsBtn = document.querySelector(`#comments-${postId} .view-comments-btn`);
                
                if (!commentsList) {
                    console.error(`Comments list not found for post ID: ${postId}`);
                    showFeedback('Erreur lors du chargement des commentaires.', 'error');
                    return;
                }
                
                // Set view button to loading state
                if (viewCommentsBtn && offset === 0) {
                    viewCommentsBtn.setAttribute('disabled', 'disabled');
                    viewCommentsBtn.classList.add('loading');
                    const icon = viewCommentsBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-spinner fa-spin';
                    }
                }
                
                // Show comments list only after we start loading
                commentsList.style.display = 'block';
                
                // Clear comments list if this is the first batch (offset = 0)
                if (offset === 0) {
                    commentsList.innerHTML = '';
                } else {
                    // Remove existing load more button if present
                    const existingLoadMoreBtn = commentsList.querySelector('.load-more-comments');
                    if (existingLoadMoreBtn) {
                        existingLoadMoreBtn.remove();
                    }
                }

                const loadingSpinner = document.createElement('div');
                loadingSpinner.className = 'loading-spinner';
                loadingSpinner.innerHTML = '<div class="loading"></div>';
                commentsList.appendChild(loadingSpinner);

                fetch(`get_comments.php?post_id=${postId}&offset=${offset}&limit=${limit}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Comments data:', data); // Debugging
                        loadingSpinner.remove();
                        
                        // Reset view button state
                        if (viewCommentsBtn) {
                            viewCommentsBtn.removeAttribute('disabled');
                            viewCommentsBtn.classList.remove('loading');
                            viewCommentsBtn.setAttribute('data-state', 'open');
                            const icon = viewCommentsBtn.querySelector('i');
                            if (icon) {
                                icon.className = 'fas fa-eye-slash';
                            }
                            viewCommentsBtn.setAttribute('title', 'Masquer les commentaires');
                        }

                        if (data.success && data.comments && data.comments.length > 0) {
                            data.comments.forEach(comment => {
                                // Check if the user can edit or delete the comment
                                const isOwner = data.current_user_id && parseInt(comment.user_id) === parseInt(data.current_user_id);
                                const isAdmin = data.is_admin === 1;
                                const canModify = isOwner || isAdmin;
                                
                                const commentHTML = `
                                    <div class="comment" id="comment-${comment.comment_id}">
                                        <div class="comment-meta">
                                            <img src="../image/profile.jpg" alt="User Profile" class="profile-picture">
                                            ${escapeHtml(comment.username)} · ${formatDate(comment.created_at)}
                                            ${comment.is_admin === 1 ? '<span class="admin-badge">Admin</span>' : ''}
                                            ${canModify ? `
                                                <button class="btn-icon edit-comment-btn" data-comment-id="${comment.comment_id}" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon delete-comment-btn" data-comment-id="${comment.comment_id}" title="Supprimer">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            ` : ''}
                                            <button class="btn translate-btn" data-id="${comment.comment_id}" data-type="comment">Traduire</button>
                                        </div>
                                        <div class="comment-content">
                                            <p id="comment-content-${comment.comment_id}">${escapeHtml(comment.content)}</p>
                                            
                                        </div>
                                    </div>
                                `;
                                commentsList.insertAdjacentHTML('beforeend', commentHTML);
                            });
                            // Supprimer les anciens boutons "Charger plus de commentaires" et "Masquer"
                const existingLoadMoreBtn = commentsList.querySelector('.load-more-comments');
                const existingHideCommentsBtn = commentsList.querySelector('.hide-comments');
                if (existingLoadMoreBtn) existingLoadMoreBtn.remove();
                if (existingHideCommentsBtn) existingHideCommentsBtn.remove();
                            
                            // Add event listeners to new comment action buttons
                            const newCommentBtns = commentsList.querySelectorAll('.edit-comment-btn, .delete-comment-btn');
                            newCommentBtns.forEach(btn => {
                                if (btn.classList.contains('edit-comment-btn')) {
                                    btn.addEventListener('click', function() {
                                        const commentId = this.getAttribute('data-comment-id');
                                        showEditCommentForm(commentId);
                                    });
                                } else if (btn.classList.contains('delete-comment-btn')) {
                                    btn.addEventListener('click', function() {
                                        const commentId = this.getAttribute('data-comment-id');
                                        if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire?')) {
                                            deleteComment(commentId);
                                        }
                                    });
                                }
                            });

                            // Add "Load More" button if there are more comments to load
                            if (data.comments.length === limit) {
                                const loadMoreButton = document.createElement('button');
                                loadMoreButton.className = 'btn btn-primary load-more-comments';
                                loadMoreButton.textContent = 'Charger plus de commentaires';
                                loadMoreButton.addEventListener('click', function() {
                                    loadComments(postId, offset + limit, limit);
                                });
                                commentsList.appendChild(loadMoreButton);
                                // Ajouter le bouton "Masquer" si ce n'est pas déjà fait
                    
                            }
                            // Always add the "Masquer" button
            const hideCommentsButton = document.createElement('button');
            hideCommentsButton.className = 'btn btn-secondary hide-comments';
            hideCommentsButton.textContent = 'Masquer';
            hideCommentsButton.addEventListener('click', function () {
                hideComments(postId, limit);
            });
            commentsList.appendChild(hideCommentsButton);
                        
                        } else if (offset === 0) {
                            commentsList.innerHTML = '<div class="no-comments">Aucun commentaire pour le moment.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading comments:', error);
                        loadingSpinner.remove();
                        commentsList.innerHTML = '<div class="error-message">Erreur lors du chargement des commentaires.</div>';
                        
                        // Reset view button state
                        if (viewCommentsBtn) {
                            viewCommentsBtn.removeAttribute('disabled');
                            viewCommentsBtn.classList.remove('loading');
                            const icon = viewCommentsBtn.querySelector('i');
                            if (icon) {
                                icon.className = 'fas fa-eye';
                            }
                        }
                        
                        showFeedback('Erreur lors du chargement des commentaires.', 'error');
                    });
            }
            
            // Event delegation for view-comments-btn and load-more-comments
            document.addEventListener('click', function (event) {
                // Find the view-comments-btn target, even if clicked on an icon inside it
                if (event.target.classList.contains('view-comments-btn') || 
                    (event.target.tagName === 'I' && event.target.parentElement && 
                     event.target.parentElement.classList.contains('view-comments-btn'))) {
                    
                    const target = event.target.classList.contains('view-comments-btn') ? 
                                  event.target : event.target.parentElement;
                    
                    // Prevent actions if button is disabled or loading
                    if (target.hasAttribute('disabled') || target.classList.contains('loading')) {
                        return;
                    }
                    
                    const postId = target.getAttribute('data-post-id');
                    const commentsList = document.querySelector(`#comments-${postId} .comments-list`);
                    
                    if (!commentsList) {
                        console.error('Comments list not found for post', postId);
                        return;
                    }
                    
                    const buttonState = target.getAttribute('data-state') || 'closed';
                    
                    if (buttonState === 'closed') {
                        // Opening comments - check if we need to load them or just show them
                        const hasLoadedComments = commentsList.children.length > 0 && 
                                                !commentsList.querySelector('.no-comments');
                                                
                        if (!hasLoadedComments) {
                            // First load - fetch comments from server
                            loadComments(postId, 0, 4);
                        } else {
                            // Comments already loaded, just show them
                            commentsList.style.display = 'block';
                            
                            // Update button state
                            target.setAttribute('data-state', 'open');
                            const icon = target.querySelector('i');
                            if (icon) {
                                icon.className = 'fas fa-eye-slash';
                            }
                            target.setAttribute('title', 'Masquer les commentaires');
                        }
                    } else {
                        // Hiding comments
                        commentsList.style.display = 'none';
                        
                        // Update button state
                        target.setAttribute('data-state', 'closed');
                        const icon = target.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-eye';
                        }
                        target.setAttribute('title', 'Voir les commentaires');
                    }
                }
                
                // Handle clicks on load-more-comments button
                if (event.target.classList.contains('load-more-comments')) {
                    const postId = event.target.closest('.discussion-item').getAttribute('data-post-id');
                    const currentCount = event.target.closest('.comments-list').querySelectorAll('.comment').length;
                    event.target.remove(); // Remove the current load more button
                    loadComments(postId, currentCount, 4); // Load next batch of comments
                }
            });
        });

        // Show feedback message
        function showFeedback(message, type) {
            const feedbackElement = document.getElementById('feedbackMessage');
            if (feedbackElement) {
                feedbackElement.className = 'feedback-message ' + type;
                feedbackElement.textContent = message;
                feedbackElement.style.display = 'block';
                
                setTimeout(() => {
                    feedbackElement.style.display = 'none';
                }, 5000);
            }
        }

        // Edit comment functionality
        function showEditCommentForm(commentId) {
            const commentElement = document.getElementById(`comment-${commentId}`);
            if (!commentElement) return;

            const contentElement = commentElement.querySelector('.comment-content');
            if (!contentElement) return;

            const currentContent = contentElement.textContent;

            // Store original content
            commentElement.dataset.originalContent = currentContent;

            // Replace with edit form
            const formHTML = `
                <div class="edit-comment-form" id="edit-comment-form-${commentId}">
                    <textarea class="form-control" id="edit-comment-content-${commentId}" required>${escapeHtml(currentContent)}</textarea>
                    <button type="button" class="btn btn-primary save-edit-comment-btn" data-comment-id="${commentId}">Enregistrer</button>
                    <button type="button" class="btn cancel-edit-comment-btn" data-comment-id="${commentId}">Annuler</button>
                </div>
            `;

            contentElement.style.display = 'none';
            contentElement.insertAdjacentHTML('afterend', formHTML);

            // Add event listeners
            document.querySelector(`#edit-comment-form-${commentId} .save-edit-comment-btn`).addEventListener('click', function () {
                saveEditedComment(commentId);
            });

            document.querySelector(`#edit-comment-form-${commentId} .cancel-edit-comment-btn`).addEventListener('click', function () {
                cancelEditComment(commentId);
            });
        }
        
        function saveEditedComment(commentId) {
            const contentInput = document.getElementById(`edit-comment-content-${commentId}`);
            if (!contentInput) return;

            const content = contentInput.value.trim();
            if (!content) {
                alert('Le commentaire ne peut pas être vide.');
                return;
            }

            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            const formData = new FormData();
            formData.append('action', 'update_comment');
            formData.append('comment_id', commentId);
            formData.append('content', content);
            formData.append('csrf_token', csrfToken);

            fetch('forum.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentElement = document.getElementById(`comment-${commentId}`);
                    const contentElement = commentElement.querySelector('.comment-content');
                    
                    // Update content
                    contentElement.textContent = content;

                    // Show original content
                    contentElement.style.display = '';

                    // Remove edit form
                    const editForm = document.getElementById(`edit-comment-form-${commentId}`);
                    if (editForm) {
                        editForm.remove();
                    }

                    showFeedback('Commentaire modifié avec succès', 'success');
                } else {
                    showFeedback(data.message || 'Erreur lors de la modification', 'error');
                }
            })
            .catch(error => {
                console.error('Error updating comment:', error);
                showFeedback('Une erreur est survenue', 'error');
            });
        }
        
        function cancelEditComment(commentId) {
            const commentElement = document.getElementById(`comment-${commentId}`);
            if (!commentElement) return;

            const contentElement = commentElement.querySelector('.comment-content');
            contentElement.style.display = '';

            const editForm = document.getElementById(`edit-comment-form-${commentId}`);
            if (editForm) {
                editForm.remove();
            }
        }
        
        function deleteComment(commentId) {
            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            const formData = new FormData();
            formData.append('action', 'delete_comment');
            formData.append('comment_id', commentId);
            formData.append('csrf_token', csrfToken);

            fetch('forum.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentElement = document.getElementById(`comment-${commentId}`);
                    if (commentElement) {
                        commentElement.remove();
                        showFeedback('Commentaire supprimé avec succès', 'success');
                    }
                } else {
                    showFeedback(data.message || 'Erreur lors de la suppression', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting comment:', error);
                showFeedback('Une erreur est survenue', 'error');
            });
        }

        function hideComments(postId, limit = 4) {
            const commentsList = document.querySelector(`#comments-${postId} .comments-list`);
            if (!commentsList) {
                console.error(`Comments list not found for post ID: ${postId}`);
                return;
            }

            const comments = commentsList.querySelectorAll('.comment');
            const totalComments = comments.length;

            // Masquer les derniers commentaires par groupes de 4
            for (let i = totalComments - 1; i >= totalComments - limit && i >= 0; i--) {
                comments[i].remove();
            }

            // Remove existing "Load More" and "Masquer" buttons
            const existingLoadMoreBtn = commentsList.querySelector('.load-more-comments');
            const existingHideCommentsBtn = commentsList.querySelector('.hide-comments');
            if (existingLoadMoreBtn) existingLoadMoreBtn.remove();
            if (existingHideCommentsBtn) existingHideCommentsBtn.remove();

            // Add "Load More" button if there are still comments to load
            if (commentsList.querySelectorAll('.comment').length > 0) {
                const loadMoreButton = document.createElement('button');
                loadMoreButton.className = 'btn btn-primary load-more-comments';
                loadMoreButton.textContent = 'Charger plus de commentaires';
                loadMoreButton.addEventListener('click', function () {
                    loadComments(postId, totalComments - limit, limit);
                });
                commentsList.appendChild(loadMoreButton);
            }

            // Add "Masquer" button
            const hideCommentsButton = document.createElement('button');
            hideCommentsButton.className = 'btn btn-secondary hide-comments';
            hideCommentsButton.textContent = 'Masquer';
            hideCommentsButton.addEventListener('click', function () {
                hideComments(postId, limit);
            });
            commentsList.appendChild(hideCommentsButton);
        }
    </script>
    <script>
document.body.addEventListener('click', function (event) {
    if (event.target.classList.contains('translate-btn')) {
        const id = event.target.getAttribute('data-id');
        const type = event.target.getAttribute('data-type');
        const contentElement = document.getElementById(`${type}-content-${id}`);

        if (!contentElement) {
            alert('Contenu introuvable pour la traduction.');
            return;
        }

        // Fetch the content to translate
        const originalText = contentElement.textContent;

        // Send the translation request
        fetch('translate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                q: originalText,
                source: 'en', // Source language
                target: 'fr', // Target language
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the content with the translated text
                contentElement.textContent = data.translatedText;
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la traduction.');
        });
    }
});
</script>
</body>
</html>
