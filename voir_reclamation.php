<?php
session_start();

// Vérification de la session et récupération du rôle
$role = $_SESSION['role'] ?? 'utilisateur'; // Récupérer le rôle de la session (admin ou utilisateur)

// Connexion à la base de données
$pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", "");

// Récupérer l'ID de la réclamation depuis l'URL
$id = $_GET['id'] ?? 0;

// Récupérer la réclamation
$stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ?");
$stmt->execute([$id]);
$reclamation = $stmt->fetch();

// Récupérer les réponses associées
$repStmt = $pdo->prepare("SELECT * FROM reponses WHERE reclamation_id = ? ORDER BY date_creation ASC");
$repStmt->execute([$id]);
$reponses = $repStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails réclamation</title>
    <link rel="stylesheet" href="style.css"> <!-- Inclure le fichier CSS -->
</head>
<body>
    <div class="container">
        <header>
            <h1>Détails de la réclamation</h1>
        </header>

        <section class="reclamation-details">
            <h2><?= htmlspecialchars($reclamation['titre']) ?></h2>
            <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($reclamation['description'])) ?></p>
            <p><strong>Lieu :</strong> <?= htmlspecialchars($reclamation['lieu']) ?> |
               <strong>Type :</strong> <?= htmlspecialchars($reclamation['type_probleme']) ?> |
               <strong>Statut :</strong> <?= htmlspecialchars($reclamation['statut']) ?></p>
        </section>

        <section class="reponses">
            <h3>Réponses :</h3>
            <?php if (count($reponses) === 0): ?>
                <p>Aucune réponse pour l'instant.</p>
            <?php else: ?>
                <?php foreach ($reponses as $r): ?>
                    <div class="reponse">
                        <p><?= nl2br(htmlspecialchars($r['contenu'])) ?></p>
                        <small>Posté le <?= $r['date_creation'] ?> par <?= $r['role'] === 'admin' ? 'Admin' : 'Utilisateur' ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Formulaire pour répondre -->
        <section class="formulaire-reponse">
            <h3>Ajouter une réponse :</h3>
            <form method="post" action="ajouter_reponse.php">
                <input type="hidden" name="reclamation_id" value="<?= $reclamation['id'] ?>">
                <textarea name="contenu" rows="4" cols="60" required></textarea><br>
                <input type="hidden" name="role" value="<?= $role === 'admin' ? 'admin' : 'utilisateur' ?>">
                <button type="submit">Répondre</button>
            </form>
        </section>

        <!-- Section de changement de statut pour admin -->
        <?php if ($role === 'admin'): ?>
            <section class="changer-statut">
                <form method="post" action="changer_statut.php">
                    <input type="hidden" name="reclamation_id" value="<?= $reclamation['id'] ?>">
                    <label for="statut">Changer le statut :</label>
                    <select name="statut" id="statut">
                        <option value="ouverte" <?= $reclamation['statut'] === 'ouverte' ? 'selected' : '' ?>>Ouverte</option>
                        <option value="en cours" <?= $reclamation['statut'] === 'en cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="resolue" <?= $reclamation['statut'] === 'resolue' ? 'selected' : '' ?>>Résolue</option>
                    </select>
                    <button type="submit">Mettre à jour</button>
                </form>
            </section>
        <?php endif; ?>

        <p><a href="reclamations_utilisateur.php">← Retour à la liste</a></p>
    </div>
</body>
</html>
