<?php
$pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", "");

// Statistiques par statut
$stat = $pdo->query("SELECT statut, COUNT(*) as total FROM reclamations GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC);

// Filtres
$lieu = $_GET['lieu'] ?? '';
$type_probleme = $_GET['type_probleme'] ?? '';

$sql = "SELECT * FROM reclamations WHERE 1";
$params = [];

if (!empty($lieu)) {
    $sql .= " AND lieu LIKE ?";
    $params[] = "%$lieu%";
}

if (!empty($type_probleme)) {
    $sql .= " AND type_probleme = ?";
    $params[] = $type_probleme;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes réclamations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1, h2 {
            color: #2C3E50;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 10px 0;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        input, select {
            padding: 5px;
            margin: 10px 0;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat {
            background-color: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
        }

        .search-form input, .search-form select {
            width: 200px;
            margin-right: 10px;
        }

        .search-form button {
            padding: 8px 15px;
            background-color: #2ecc71;
            border: none;
            color: white;
            cursor: pointer;
        }

        .search-form button:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Mes réclamations</h1>

        <!-- ✅ Compteur -->
        <div class="stats">
            <?php foreach ($stat as $s): ?>
                <div class="stat">
                    <strong><?= ucfirst($s['statut']) ?> :</strong> <?= $s['total'] ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 🔘 Nouvelle réclamation -->
        <a href="ajouter_reclamation_utilisateur.php">
            <button>➕ Nouvelle réclamation</button>
        </a>

        <!-- 🔍 Barre de recherche -->
        <form method="get" class="search-form">
            <input type="text" name="lieu" placeholder="🔎 Rechercher par lieu" value="<?= htmlspecialchars($lieu) ?>">
            <select name="type_probleme">
                <option value="">🔖 Tous les types</option>
                <option value="mécanique" <?= $type_probleme === 'mécanique' ? 'selected' : '' ?>>Mécanique</option>
                <option value="batterie" <?= $type_probleme === 'batterie' ? 'selected' : '' ?>>Batterie</option>
                <option value="écran" <?= $type_probleme === 'écran' ? 'selected' : '' ?>>Écran</option>
                <option value="pneu" <?= $type_probleme === 'pneu' ? 'selected' : '' ?>>Pneu</option>
            </select>
            <button type="submit">Rechercher</button>
        </form>

        <!-- 📄 Tableau -->
        <table>
            <tr>
                <th>Titre</th>
                <th>Description</th>
                <th>Lieu</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($reclamations as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td><?= htmlspecialchars($r['lieu']) ?></td>
                    <td><?= htmlspecialchars($r['type_probleme']) ?></td>
                    <td><?= htmlspecialchars($r['statut']) ?></td>
                    <td>
                        <a href="voir_reclamation.php?id=<?= $r['id'] ?>">Voir</a> |
                        <a href="modifier_reclamation.php?id=<?= $r['id'] ?>">Modifier</a> |
                        <a href="supprimer_reclamation.php?id=<?= $r['id'] ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
<!-- 📤 Export PDF -->
<form method="get" action="export_pdf.php" style="margin-bottom: 20px;">
    <input type="text" name="lieu" placeholder="Lieu" value="<?= htmlspecialchars($lieu) ?>">
    <select name="type_probleme">
        <option value="">Tous les types</option>
        <option value="mécanique" <?= $type_probleme === 'mécanique' ? 'selected' : '' ?>>Mécanique</option>
        <option value="batterie" <?= $type_probleme === 'batterie' ? 'selected' : '' ?>>Batterie</option>
        <option value="écran" <?= $type_probleme === 'écran' ? 'selected' : '' ?>>Écran</option>
        <option value="pneu" <?= $type_probleme === 'pneu' ? 'selected' : '' ?>>Pneu</option>
    </select>
    <select name="statut">
        <option value="">Tous les statuts</option>
        <option value="ouverte">Ouverte</option>
        <option value="en cours">En cours</option>
        <option value="resolue">Résolue</option>
    </select>
    <button type="submit">📤 Exporter PDF</button>
</form>

