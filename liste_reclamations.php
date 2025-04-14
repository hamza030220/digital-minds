<?php
// Connexion à la base de données
include 'connexion.php';

// Récupérer toutes les réclamations
$stmt = $pdo->query("SELECT * FROM reclamations ORDER BY date_creation DESC");
$reclamations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des réclamations - Green.tn</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- En-tête du site -->
    <header>
        <div class="logo">
            <h1>Green.tn</h1>
            <p>Mobilité durable, énergie propre</p>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="ajouter_reclamation.php">Nouvelle réclamation</a></li>
                <li><a href="#">Mon profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <!-- Section principale -->
    <main>
        <div class="content-container">
            <h2>Liste des réclamations</h2>

            <!-- Filtres de recherche -->
            <div class="search-bar">
                <label for="search">Recherche par lieu:</label>
                <input type="text" id="search" placeholder="Entrez un lieu">
                <button type="button" onclick="searchReclamation()">Rechercher</button>
            </div>

            <!-- Table des réclamations -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Lieu</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Répondre</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reclamations as $reclamation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reclamation['titre']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['description']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['lieu']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['type_probleme']); ?></td>
                                <td>
                                    <?php echo ucfirst($reclamation['statut']); ?>
                                    <!-- Formulaire pour modifier le statut -->
                                    <form action="modifier_statut.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="reclamation_id" value="<?php echo $reclamation['id']; ?>">
                                        <select name="statut" onchange="this.form.submit()">
                                            <option value="ouverte" <?php if ($reclamation['statut'] == 'ouverte') echo 'selected'; ?>>Ouverte</option>
                                            <option value="en cours" <?php if ($reclamation['statut'] == 'en cours') echo 'selected'; ?>>En cours</option>
                                            <option value="résolue" <?php if ($reclamation['statut'] == 'résolue') echo 'selected'; ?>>Résolue</option>
                                        </select>
                                    </form>
                                </td>
                                <td><a href="repondre_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn">Répondre</a></td>
                                <td>
                                    <a href="modifier_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn">Modifier</a> | 
                                    <a href="supprimer_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn btn-danger">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Pied de page -->
    <footer>
        <p>&copy; 2025 Green.tn - Tous droits réservés.</p>
    </footer>

</body>
</html>
