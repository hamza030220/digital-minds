<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$error = '';
$username = '';

// Ensure CSRF token exists
if (!isset($_SESSION['csrf_token'])) {
    generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log('Processing login form submission');
    error_log('Session Token: ' . $_SESSION['csrf_token']);
    error_log('POST Token: ' . ($_POST['csrf_token'] ?? 'Not set'));
    
    // Validate CSRF token
    $post_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    if (!validateCSRFToken($post_token)) {
        $error = "Erreur de sécurité: Le jeton CSRF est invalide ou expiré.";
        error_log('CSRF validation failed during login');
    } else {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Tous les champs sont requis.";
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password'])) {
                    // Success! Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Generate new CSRF token after successful login
                    $csrf_token = regenerateCSRFToken();
                    error_log('Generated new CSRF token after successful login');
                    
                    // Redirect to dashboard
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Nom d'utilisateur ou mot de passe incorrect.";
                }
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Green Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Green Admin</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

