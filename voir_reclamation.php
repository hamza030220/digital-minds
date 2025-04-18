<?php
// voir_reclamation.php

// Start session (needed for user roles and potentially user ID)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Dependencies ---
// Adjust paths if this file is not in the web root
require_once __DIR__ . '/config/database.php'; // Needed for Database class
require_once __DIR__ . '/models/Reclamation.php'; // Needed for Reclamation model

// --- Initialization ---
$reclamation = null;
$reponses = [];
$feedback_message = '';
$message_type = 'info'; // For styling feedback ('info', 'success', 'error')
$pdo = null; // Initialize PDO variable

// --- Handle Feedback Messages from Status Update ---
if (isset($_GET['status_update'])) {
    switch ($_GET['status_update']) {
        case 'success':
            $feedback_message = "Statut mis à jour avec succès.";
            $message_type = 'success';
            break;
        case 'error_model':
        case 'error_db':
            $feedback_message = "Erreur base de données lors de la mise à jour.";
            $message_type = 'error';
            break;
        case 'error_pdo':
        case 'error_system':
            $feedback_message = "Erreur système lors de la mise à jour.";
            $message_type = 'error';
            break;
        case 'error_input':
            $feedback_message = "Données fournies invalides.";
            $message_type = 'error';
            break;
        case 'error_missing':
            $feedback_message = "Données manquantes pour la mise à jour.";
            $message_type = 'error';
            break;
    }
}

// --- Get Reclamation ID and Fetch Data ---
$reclamation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if ($reclamation_id) {
    try {
        // --- Use Reclamation Model to fetch details ---
        $reclamationModel = new Reclamation(); // Instantiates model (uses Database class inside)
        $reclamation = $reclamationModel->getParId($reclamation_id); // Fetch by ID using the model method

        if ($reclamation) {
            // --- Fetch associated responses ---
            $database = new Database();
            $pdo = $database->getConnection();

            if ($pdo) {
                $repStmt = $pdo->prepare("SELECT * FROM reponses WHERE reclamation_id = ? ORDER BY date_creation ASC");
                $repStmt->bindParam(1, $reclamation_id, PDO::PARAM_INT);
                $repStmt->execute();
                $reponses = $repStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                error_log("Failed to get DB connection for responses in voir_reclamation.php");
                $feedback_message .= ($feedback_message ? "<br>" : "") . "Erreur: Impossible de charger les réponses.";
                $message_type = 'error';
            }
        } else {
            $feedback_message = "Réclamation non trouvée.";
            $message_type = 'error';
        }
    } catch (Exception $e) {
        error_log("Error in voir_reclamation.php for ID {$reclamation_id}: " . $e->getMessage());
        $feedback_message = "Une erreur technique est survenue lors de la récupération des données.";
        $message_type = 'error';
        $reclamation = null;
    } finally {
        $repStmt = null;
        $pdo = null;
    }
} else {
    $feedback_message = "ID de réclamation invalide ou manquant.";
    $message_type = 'error';
}

// Determine the role safely
$role = $_SESSION['user_role'] ?? 'utilisateur'; // Assuming 'role' is the session key

// Set page title

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .feedback-message {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .feedback-message.success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .feedback-message.error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .feedback-message.info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .reclamation-details h2 {
            color: #34495e;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .reclamation-details p {
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .changer-statut {
            margin: 20px 0;
        }

        .changer-statut form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .changer-statut label {
            font-weight: bold;
            color: #2c3e50;
        }

        .changer-statut select {
            padding: 8px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .changer-statut select:focus {
            border-color: #3498db;
            outline: none;
        }

        button {
            background-color: #3498db;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .reponses h3 {
            color: #34495e;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .reponse {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .reponse p {
            margin: 5px 0;
            line-height: 1.6;
        }

        .reponse small {
            color: #7f8c8d;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }

        .formulaire-reponse h3 {
            color: #34495e;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .formulaire-reponse textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .formulaire-reponse textarea:focus {
            border-color: #3498db;
            outline: none;
        }

        .formulaire-reponse button {
            margin-top: 10px;
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
            .container {
                margin: 20px;
                padding: 15px;
            }

            header h1 {
                font-size: 20px;
            }

            .reclamation-details h2 {
                font-size: 18px;
            }

            .reponses h3, .formulaire-reponse h3 {
                font-size: 16px;
            }

            .changer-statut form {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Détails de la réclamation</h1>
        </header>

        <!-- Display Feedback Message -->
        <?php if ($feedback_message): ?>
            <div class="feedback-message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
        <?php endif; ?>

        <!-- Check if reclamation data was loaded -->
        <?php if ($reclamation): ?>

            <section class="reclamation-details">
                <h2><?php echo htmlspecialchars($reclamation['titre']); ?></h2>
                <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>
                <p>
                    <strong>Lieu :</strong> <?php echo htmlspecialchars($reclamation['lieu']); ?> |
                    <strong>Type :</strong> <?php echo htmlspecialchars($reclamation['type_probleme']); ?> |
                    <strong>Statut :</strong> <?php echo htmlspecialchars(ucfirst($reclamation['statut'])); ?> |
                    <strong>Posté le :</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($reclamation['date_creation']))); ?>
                </p>
            </section>

            <!-- Section de changement de statut pour admin -->
            <?php if ($role === 'admin'): ?>
                <section class="changer-statut">
                    <form method="post" action="controllers/changer_statut.php">
                        <input type="hidden" name="reclamation_id" value="<?php echo htmlspecialchars($reclamation['id']); ?>">
                        <label for="statut"><strong>Changer le statut :</strong></label>
                        <select name="statut" id="statut">
                            <option value="ouverte" <?php echo $reclamation['statut'] === 'ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                            <option value="en cours" <?php echo $reclamation['statut'] === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="résolue" <?php echo $reclamation['statut'] === 'résolue' ? 'selected' : ''; ?>>Résolue</option>
                        </select>
                        <button type="submit">Mettre à jour</button>
                    </form>
                </section>
            <?php endif; ?>

            <section class="reponses">
                <h3>Réponses :</h3>
                <?php if (empty($reponses)): ?>
                    <p>Aucune réponse pour l'instant.</p>
                <?php else: ?>
                    <?php foreach ($reponses as $r): ?>
                        <div class="reponse">
                            <p><?php echo nl2br(htmlspecialchars($r['contenu'])); ?></p>
                            <small>
                                Posté le <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['date_creation']))); ?>
                                <?php if (isset($r['role'])): ?>
                                    par <?php echo htmlspecialchars(ucfirst($r['role'])); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Formulaire pour répondre -->
            <section class="formulaire-reponse">
                <h3>Ajouter une réponse :</h3>
                <form method="post" action="ajouter_reponse.php">
                    <input type="hidden" name="reclamation_id" value="<?php echo htmlspecialchars($reclamation['id']); ?>">
                    <textarea name="contenu" rows="4" required placeholder="Votre réponse ici..."></textarea><br>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                    <button type="submit">Répondre</button>
                </form>
            </section>

            <p>
                <?php if ($role === 'admin'): ?>
                    <a href="reclamations_utilisateur.php">← Retour au tableau de bord</a>
                <?php else: ?>
                    <a href="liste_reclamations.php">← Retour à mes réclamations</a>
                <?php endif; ?>
            </p>

        <?php elseif (!$feedback_message): ?>
            <p>Les détails de cette réclamation ne sont pas disponibles.</p>
            <p><a href="index.php">← Retour à l'accueil</a></p>
        <?php endif; ?>
    </div>

</body>
</html>