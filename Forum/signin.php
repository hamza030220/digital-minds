<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in production

// Start the session with enhanced security settings
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.use_only_cookies', 1); // Forces sessions to only use cookies
ini_set('session.cookie_secure', 1); // Only transmit cookies over HTTPS when possible
ini_set('session.gc_maxlifetime', 3600); // Session timeout after 1 hour of inactivity
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF attacks

// Set session timeout to 1 hour
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
require_once 'db_connect.php';

// Initialize variables
$error = null;
$debug_info = '';

// Check for existing remember-me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me_token'])) {
    $token = $_COOKIE['remember_me_token'];
    
    try {
        $stmt = $conn->prepare("SELECT users.* FROM users 
                              JOIN remember_tokens ON users.id = remember_tokens.user_id 
                              WHERE remember_tokens.token = :token 
                              AND remember_tokens.expires_at > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // If already logged in via remember me, set admin status and redirect only if not submitting form
            $_SESSION['is_admin'] = $user['is_admin'];
            if (!isset($_POST['username'])) {
                // Only redirect if not currently submitting the form
                header("Location: back.php");
                exit();
            }
        }
    } catch(PDOException $e) {
        // Fail silently, user will need to log in normally
        $debug_info .= "<p>Remember-me cookie error: " . $e->getMessage() . "</p>";
    }
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) && $_POST['remember'] == 'on';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        try {
            // Fetch user by username
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();
            
            // Debug information (without exposing sensitive data)
            $debug_info .= "<div style='background-color: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin-bottom: 20px;'>";
            $debug_info .= "<strong>Debug Information:</strong><br>";
            
            if ($user) {
                $debug_info .= "User found: " . htmlspecialchars($user['username']) . "<br>";
                $debug_info .= "Password format: " . (strpos($user['password'], '$2y$') === 0 ? 'Bcrypt (Secure)' : 'Other format') . "<br>";
                $debug_info .= "Password length: " . strlen($user['password']) . " characters<br>";
                $debug_info .= "Is admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "<br>";
                $debug_info .= "Password verification result: " . (password_verify($password, $user['password']) ? 'SUCCESS' : 'FAILED') . "<br>";
            } else {
                $debug_info .= "User not found with username: " . htmlspecialchars($username) . "<br>";
            }
            
            $debug_info .= "</div>";
            
            // Verify credentials
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Additional admin validation - Only allow users with is_admin=1
                if (!$user['is_admin']) {
                    $error = "You do not have admin privileges. This portal is for administrators only.";
                    error_log("Non-admin user attempted to access admin area: " . $username);
                    // Clear any partially set session data
                    unset($_SESSION['user_id']);
                    unset($_SESSION['username']);
                    unset($_SESSION['is_admin']);
                } else {
                    // Handle "Remember Me" functionality
                    if ($remember) {
                        // Delete any existing remember tokens for this user
                        $deleteStmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
                        $deleteStmt->bindParam(':user_id', $user['id']);
                        $deleteStmt->execute();
                        
                        // Generate token
                        $token = bin2hex(random_bytes(32));
                        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Store token in database
                        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) 
                                              VALUES (:user_id, :token, :expires_at)");
                        $stmt->bindParam(':user_id', $user['id']);
                        $stmt->bindParam(':token', $token);
                        $stmt->bindParam(':expires_at', $expires_at);
                        $stmt->execute();
                        
                        // Set cookie with token
                        setcookie('remember_me_token', $token, time() + (86400 * 30), "/"); // 30 days
                    }
                    
                    // Redirect to dashboard only when authentication is successful
                    header("Location: back.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            $debug_info .= "<p>Database error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In</title>
    <link rel="stylesheet" href="signin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="../image/ve.png" alt="Website Logo">
            </div>
            <h2>Admin Sign In</h2>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
        <div class="success-alert">
            You have been successfully logged out.
        </div>
        <?php endif; ?>
        
        <?php echo $debug_info; // Display debug information ?>
        <div class="login-info">
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="success-alert">
                You are already logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <?php if ($_SESSION['is_admin']): ?>
                <span class="admin-badge">Admin</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <form class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <i class="fas fa-user"></i>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <i class="fas fa-lock"></i>
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <div class="forgot-password">
                    <a href="#">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="login-button">Sign In</button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }
        
        // Add success message handling
        document.addEventListener('DOMContentLoaded', function() {
            const errorAlert = document.querySelector('.error-alert');
            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.style.opacity = '0';
                    setTimeout(function() {
                        errorAlert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>

