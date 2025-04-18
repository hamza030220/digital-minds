<?php
// Start the session
session_start();

// Set the current page for the sidebar
$currentPage = 'dashboard';

// Base path for the project
$basePath = '/old/Forum/'; // Adjust this path to match your project structure

// Function to check if user is admin
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Check if user is logged in and is an admin
if (!isAdminLoggedIn()) {
    // Not logged in or not an admin, redirect to login page
    header("Location: signin.php?error=unauthorized");
    exit();
}

// Handle logout if requested
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Clear all session variables
    $_SESSION = array();
    
    // If a session cookie is used, clear it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Delete remember_me token if exists
    if (isset($_COOKIE['remember_me_token'])) {
        // Remove from database
        require_once 'db_connect.php';
        $token = $_COOKIE['remember_me_token'];
        try {
            $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
        } catch(PDOException $e) {
            // Fail silently
        }
        
        // Delete cookie
        setcookie('remember_me_token', '', time() - 3600, "/");
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: signin.php?logout=1");
    exit();
}

// Get user information for display
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Forum Green.tn</title>
    <link rel="stylesheet" href="forum.css">
    <style>
        /* Admin specific styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #ffffff; /* Changed to white */
            margin: 0;
            padding: 0;
        }

        .admin-container {
            margin-left: var(--sidebar-width); /* Adjust content to account for sidebar width */
            padding: 40px;
            max-width: calc(100% - var(--sidebar-width));
            background: linear-gradient(135deg, #F9F5E8 0%, #ffffff 100%);
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }

        .admin-user-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: #2c3e50;
        }

        .logout-btn {
            color: #fff;
            background: linear-gradient(to right, #2e7d32, #219150);
            border: 1px solid #2e7d32;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: "Arial", sans-serif;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            letter-spacing: 0.5px;
            display: inline-block;
            min-width: 65px;
            text-align: center;
            margin: 0 5px;
            white-space: nowrap;
        }

        .logout-btn:hover {
            background-color: #219150;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }

        h2 {
            font-size: 28px;
            color: #2e7d32;
            margin-bottom: 30px;
            text-align: center;
            font-family: "Bauhaus 93", Arial, sans-serif;
            position: relative;
            padding-bottom: 15px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, #2e7d32, #219150);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Admin Main Content -->
    <main class="admin-container">
        <div class="admin-user-section">
            <div>
                <h2>Dashboard</h2>
                <p>Bienvenue dans votre tableau de bord d'administration, <?php echo $username; ?>.</p>
            </div>
        </div>
        
        <!-- Empty content area -->
        <div style="text-align: center; padding: 50px; color: #2c3e50;">
            <p>Zone de contenu disponible pour futurs développements.</p>
        </div>
        <a href="<?php echo $basePath; ?>dashboard.php">Go to Dashboard</a>
    </main>
</body>
<script src="../Forum/sidebar.js"></script>

</html>

