<?php
// Get error type from URL
$errorType = isset($_GET['error']) ? intval($_GET['error']) : 404;

// Set error messages based on error type
switch ($errorType) {
    case 500:
        $errorTitle = "Erreur Serveur";
        $errorMessage = "Une erreur interne du serveur s'est produite. Veuillez réessayer plus tard.";
        $errorHelp = "Si le problème persiste, contactez l'administrateur du site.";
        break;
    case 403:
        $errorTitle = "Accès Interdit";
        $errorMessage = "Vous n'avez pas l'autorisation d'accéder à cette ressource.";
        $errorHelp = "Veuillez vous connecter pour accéder à cette page ou contacter l'administrateur si vous pensez qu'il s'agit d'une erreur.";
        break;
    case 404:
    default:
        $errorTitle = "Page Non Trouvée";
        $errorMessage = "La page que vous recherchez n'existe pas ou a été déplacée.";
        $errorHelp = "Vérifiez l'URL ou utilisez les liens de navigation ci-dessous pour trouver ce que vous cherchez.";
        break;
}

// Set the HTTP response code
http_response_code($errorType);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorTitle; ?> - Green.tn</title>
    <link rel="stylesheet" href="../green.css">
    <link rel="stylesheet" href="forum.css">
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            text-align: center;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin: 30px auto;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .error-code {
            font-size: 120px;
            color: #60BA97;
            margin: 0;
            line-height: 1;
            font-weight: bold;
        }
        
        .error-title {
            font-size: 32px;
            color: #333;
            margin: 20px 0;
        }
        
        .error-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .error-help {
            font-size: 16px;
            color: #777;
            margin-bottom: 30px;
            font-style: italic;
        }
        
        .navigation-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .nav-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #60BA97;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .nav-btn:hover {
            background-color: #4d9c7e;
        }
        
        .nav-btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .nav-btn-secondary:hover {
            background-color: #e0e0e0;
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
                <li><a href="signup.php" class="signin">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="forum-container">
        <div class="error-container">
            <h1 class="error-code"><?php echo $errorType; ?></h1>
            <h2 class="error-title"><?php echo $errorTitle; ?></h2>
            <p class="error-message"><?php echo $errorMessage; ?></p>
            <p class="error-help"><?php echo $errorHelp; ?></p>
            
            <div class="navigation-links">
                <a href="forum.php" class="nav-btn">Retour au Forum</a>
                <a href="../green.html" class="nav-btn nav-btn-secondary">Page d'accueil</a>
                
                <?php if ($errorType == 403): ?>
                <a href="signin.php" class="nav-btn">Se connecter</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Search suggestions for 404 errors -->
    <?php if ($errorType == 404): ?>
    <div class="error-container" style="margin-top: 0;">
        <h3>Liens populaires</h3>
        <div class="navigation-links" style="margin-top: 10px;">
            <a href="forum.php" class="nav-btn nav-btn-secondary">Discussions récentes</a>
            <a href="../green.html#a-nos-velos" class="nav-btn nav-btn-secondary">Nos vélos</a>
            <a href="../green.html#pricing" class="nav-btn nav-btn-secondary">Tarifs</a>
            <a href="../green.html#contact" class="nav-btn nav-btn-secondary">Contact</a>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>

<?php
// Get error type from URL
$errorType = isset($_GET['error']) ? intval($_GET['error']) : 404;

// Set error messages based on error type
switch ($errorType) {
    case 500:
        $errorTitle = "Erreur Serveur";
        $errorMessage = "Une erreur interne du serveur s'est produite. Veuillez réessayer plus tard.";
        break;
    case 403:
        $errorTitle = "Accès Interdit";
        $errorMessage = "Vous n'avez pas l'autorisation d'accéder à cette ressource.";
        break;
    case 404:
    default:
        $errorTitle = "Page Non Trouvée";
        $errorMessage = "La page que vous recherchez n'existe pas ou a été déplacée.";
        break;
}

// Set the HTTP response code
http_response_code($errorType);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorTitle; ?> - Green.tn</title>
    <link rel="stylesheet" href="../green.css">
    <link rel="stylesheet" href="forum.css">
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            text-align: center;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin: 30px auto;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .error-code {
            font-size: 120px;
            color: #60BA97;
            margin: 0;
            line-height: 1;
            font-weight: bold;
        }
        
        .error-title {
            font-size: 32px;
            color: #333;
            margin: 20px 0;
        }
        
        .error-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #60BA97;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #4d9c7e;
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
                <li><a href="signup.php" class="signin">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="forum-container">
        <div class="error-container">
            <h1 class="error-code"><?php echo $errorType; ?></h1>
            <h2 class="error-title"><?php echo $errorTitle; ?></h2>
            <p class="error-message"><?php echo $errorMessage; ?></p>
            <a href="forum.php" class="back-btn">Retour au Forum</a>
        </div>
    </main>
</body>
</html>

