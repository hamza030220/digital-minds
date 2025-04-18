<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once './controllers/ResponseController.php';
require_once 'config/database.php';

// Initialize database and controller
$database = new Database();
$db = $database->getConnection();
$responseController = new ResponseController();

// Vérifier si l'ID de la réclamation est passé
if (!isset($_GET['id'])) {
    echo "ID de réclamation manquant.";
    exit;
}

$reclamation_id = $_GET['id'];

// Récupérer les informations de la réclamation
$stmt = $db->prepare("SELECT * FROM reclamations WHERE id = ?");
$stmt->execute([$reclamation_id]);
$reclamation = $stmt->fetch();

if (!$reclamation) {
    echo "Réclamation non trouvée.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $responseController->createResponse(
        $reclamation_id,
        $_SESSION['user_id'],
        $_POST['reponse'] // You can modify this based on your role system
    );
    
    if ($result['status'] === 'success') {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

// Get existing responses
$reponses = $responseController->getResponsesByReclamation($reclamation_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Répondre à la réclamation - Green.tn</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        main {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
            border-bottom: 2px solid #1=    margin-bottom: 20px;
            padding-bottom: 10px;
        }

        h3 {
            color: #34495e;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .success, .error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        p {
            line-height: 1.6;
            margin-bottom: 15px;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        textarea:focus {
            border-color: #3498db;
            outline: none;
        }

        button {
            background-color: #3498db;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .reponse {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .reponse p {
            margin: 5px 0;
        }

        .reponse i {
            color: #7f8c8d;
            font-size: 12px;
        }

        hr {
            border: 0;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #2c3e50;
            color: #fff;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 600px) {
            main {
                margin: 20px;
                padding: 15px;
            }

            h2 {
                font-size: 20px;
            }

            h3 {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
<?php include("menu.php") ?>

    <main>
        <h2>Répondre à la réclamation</h2>

        <?php if (isset($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <h3>Réclamation : <?php echo htmlspecialchars($reclamation['titre']); ?></h3>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>

        <form action="repondre_reclamation.php?id=<?php echo $reclamation['id']; ?>" method="POST">
            <label for="reponse">Votre réponse :</label>
            <textarea id="reponse" name="reponse" required></textarea><br><br>
            <button type="submit">Répondre</button>
        </form>

        <br><br>

        <h3>Réponses déjà ajoutées :</h3>
        <?php foreach ($reponses as $reponse): ?>
            <div class='reponse'>
                <p><strong>Réponse de l'<?php echo htmlspecialchars($reponse['role']); ?>:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($reponse['contenu'])); ?></p>
                <p><i>Réponse donnée le <?php echo $reponse['date_creation']; ?></i></p>
            </div><hr>
        <?php endforeach; ?>
    </main>

    <footer>
        <p>© 2025 Green.tn - Tous droits réservés.</p>
    </footer>
</body>
</html>