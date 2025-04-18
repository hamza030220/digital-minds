<?php
// Connexion à la base de données using Database class
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Erreur de connexion à la base de données. Impossible de continuer.");
}

// Initialize variables
$reclamation_id = null;
$reclamation = null;
$feedback_message = '';

if (isset($_GET['id'])) {
    $reclamation_id_temp = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    if ($reclamation_id_temp !== false) {
        $reclamation_id = $reclamation_id_temp;

        try {
            $stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
            $stmt->execute([$reclamation_id]);
            $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reclamation) {
                $feedback_message = "Réclamation non trouvée.";
            }
        } catch (PDOException $e) {
            error_log("Error fetching reclamation ID {$reclamation_id}: " . $e->getMessage());
            $feedback_message = "Erreur lors de la récupération de la réclamation.";
            $reclamation = null;
        }

        if ($reclamation && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $lieu = trim($_POST['lieu'] ?? '');
            $type_probleme = $_POST['type_probleme'] ?? '';

            $errors = [];
            if (empty($titre)) $errors[] = "Titre requis.";

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE reclamations SET titre = ?, description = ?, lieu = ?, type_probleme = ? WHERE id = ?");
                    $success = $stmt->execute([$titre, $description, $lieu, $type_probleme, $reclamation_id]);

                    if ($success) {
                        $feedback_message = "Réclamation modifiée avec succès !";
                        $stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
                        $stmt->execute([$reclamation_id]);
                        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $feedback_message = "Erreur lors de la mise à jour.";
                    }
                } catch (PDOException $e) {
                    error_log("Error updating reclamation ID {$reclamation_id}: " . $e->getMessage());
                    $feedback_message = "Erreur base de données lors de la mise à jour.";
                }
            } else {
                $feedback_message = "Erreur de validation: " . implode(', ', $errors);
            }
        }
    } else {
        $feedback_message = "ID de réclamation invalide.";
    }
} else {
    $feedback_message = "ID de réclamation manquant.";
}

$titre_value = $reclamation ? htmlspecialchars($reclamation['titre']) : '';
$description_value = $reclamation ? htmlspecialchars($reclamation['description']) : '';
$lieu_value = $reclamation ? htmlspecialchars($reclamation['lieu']) : '';
$type_probleme_value = $reclamation ? $reclamation['type_probleme'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la réclamation - Green.tn</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: rgb(10, 73, 15);
            padding: 20px;
            color: #fff;
            text-align: center;
        }

        header .logo h1 {
            margin: 0;
            font-size: 28px;
        }

        header .logo p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.8;
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 15px 0 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.2s;
        }

        nav ul li a:hover {
            color: #3498db;
        }

        main.container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .feedback {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 4px;
            text-align: center;
            border: 1px solid transparent;
        }

        .feedback.success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .feedback.error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
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

        a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.2s;
        }

        a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

       

        @media (max-width: 600px) {
            main.container {
                margin: 20px;
                padding: 15px;
            }

            h2 {
                font-size: 20px;
            }

            nav ul {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>Green.tn</h1>
            <p>Mobilité durable, énergie propre</p>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="liste_reclamations.php">Liste des réclamations</a></li>
                <li><a href="#">Mon profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2>Modifier la réclamation</h2>

        <?php if ($feedback_message): ?>
            <div class="feedback <?php echo (strpos(strtolower($feedback_message), 'erreur') !== false || strpos(strtolower($feedback_message), 'invalide') !== false || strpos(strtolower($feedback_message), 'manquant') !== false) ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($reclamation): ?>
            <form action="modifier_reclamation.php?id=<?php echo htmlspecialchars($reclamation_id); ?>" method="POST">
                <div class="form-group">
                    <label for="titre">Titre :</label>
                    <input type="text" id="titre" name="titre" value="<?php echo $titre_value; ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description" required><?php echo $description_value; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="lieu">Lieu :</label>
                    <input type="text" id="lieu" name="lieu" value="<?php echo $lieu_value; ?>" required>
                </div>

                <div class="form-group">
                    <label for="type_probleme">Type de problème :</label>
                    <select id="type_probleme" name="type_probleme" required>
                        <option value="mecanique" <?php if ($type_probleme_value == 'mecanique') echo 'selected'; ?>>Mécanique</option>
                        <option value="batterie" <?php if ($type_probleme_value == 'batterie') echo 'selected'; ?>>Batterie</option>
                        <option value="ecran" <?php if ($type_probleme_value == 'ecran') echo 'selected'; ?>>Écran</option>
                        <option value="pneu" <?php if ($type_probleme_value == 'pneu') echo 'selected'; ?>>Pneu</option>
                        <option value="Infrastructure" <?php if ($type_probleme_value == 'Infrastructure') echo 'selected'; ?>>Infrastructure</option>
                        <option value="Autre" <?php if ($type_probleme_value == 'Autre') echo 'selected'; ?>>Autre</option>
                    </select>
                </div>

                <button type="submit">Mettre à jour la réclamation</button>
                <a href="liste_reclamations.php?id=<?php echo htmlspecialchars($reclamation_id); ?>" style="margin-left: 10px;">Annuler</a>
            </form>
        <?php elseif (!$feedback_message): ?>
            <p>Chargement...</p>
        <?php endif; ?>
        <?php if ($reclamation === null && $feedback_message): ?>
            <p><a href="liste_reclamations.php">Retour à la liste</a></p>
        <?php endif; ?>
    </main>

    
</body>
</html>