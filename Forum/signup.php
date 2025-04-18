<?php
// Start session
session_start();

// If user is already logged in, redirect to forum
if(isset($_SESSION['user_id'])) {
    header("Location: forum.php");
    exit();
}

// Database connection
require_once 'db_connect.php';

// Variables to store form data and errors
$username = "";
$email = "";
$errors = [];
$success = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $username = trim(htmlspecialchars($_POST['username'] ?? ''));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = "Nom d'utilisateur requis";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = "Le nom d'utilisateur ne peut contenir que des lettres, des chiffres et des underscores";
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide";
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = "Mot de passe requis";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = "Les mots de passe ne correspondent pas";
    }
    
    // If no validation errors, check if username or email already exists
    if (empty($errors)) {
        try {
            // Check for duplicate username
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors['username'] = "Ce nom d'utilisateur est déjà utilisé";
            }
            
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors['email'] = "Cet email est déjà utilisé";
            }
            
            // If no duplicates found, create the user
            if (empty($errors)) {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->execute();
                
                // Registration successful
                $success = true;
                
                // Clear form data
                $username = "";
                $email = "";
            }
        } catch(PDOException $e) {
            $errors['general'] = "Erreur lors de l'inscription: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Green.tn</title>
    <link rel="stylesheet" href="signin.css">
    <style>
        .success-message {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .error-feedback {
            color: #c62828;
            font-size: 0.85em;
            margin-top: 5px;
        }
        
        .form-container {
            max-width: 450px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo-nav-container">
            <div class="logo">
                <img src="../image/ve.png" alt="Green.tn Logo">
            </div>
            <nav class="nav-left">
                <ul>
                    <li><a href="../green.html">Home</a></li>
                    <li><a href="../green.html#a-nos-velos">Nos vélos</a></li>
                    <li><a href="../green.html#a-propos-de-nous">À propos de nous</a></li>
                    <li><a href="../green.html#pricing">Tarifs</a></li>
                    <li><a href="../green.html#contact">Contact</a></li>
                    <li><a href="forum.php">Forum</a></li>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li><a href="signin.php" class="login">Connexion</a></li>
                <li><a href="signup.php" class="signin active">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="signin-container">
        <div class="form-container">
            <div class="form-header">
                <h2>Créer un compte</h2>
                <p>Rejoignez notre communauté Green.tn</p>
            </div>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <p>Inscription réussie ! Vous pouvez maintenant <a href="signin.php">vous connecter</a> avec vos identifiants.</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])): ?>
                <div class="error-message">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="signupForm">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" value="<?php echo $username; ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="error-feedback"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-feedback"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-feedback"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="error-feedback"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit">S'inscrire</button>
                
                <div class="form-footer">
                    <p>Vous avez déjà un compte? <a href="signin.php">Connectez-vous</a></p>
                </div>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('signupForm');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Clear previous error messages
            document.querySelectorAll('.error-feedback').forEach(el => {
                el.remove();
            });
            
            // Validate username
            if (username === '') {
                showError('username', "Nom d'utilisateur requis");
                isValid = false;
            } else if (username.length < 3 || username.length > 50) {
                showError('username', "Le nom d'utilisateur doit contenir entre 3 et 50 caractères");
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showError('username', "Le nom d'utilisateur ne peut contenir que des lettres, des chiffres et des underscores");
                isValid = false;
            }
            
            // Validate email
            if (email === '') {
                showError('email', "Email requis");
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('email', "Format d'email invalide");
                isValid = false;
            }
            
            // Validate password
            if (password === '') {
                showError('password', "Mot de passe requis");
                isValid = false;
            } else if (password.length < 6) {
                showError('password', "Le mot de passe doit contenir au moins 6 caractères");
                isValid = false;
            }
            
            // Validate password confirmation
            if (password !== confirmPassword) {
                showError('confirm_password', "Les mots de passe ne correspondent pas");
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Function to show error message
        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-feedback';
            errorDiv.innerText = message;
            input.parentNode.appendChild(errorDiv);
        }
    });
    </script>
</body>
</html>

