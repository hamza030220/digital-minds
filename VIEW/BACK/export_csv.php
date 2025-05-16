<?php
// Inclure la connexion à la base de données
require_once __DIR__ . '/../../CONFIG/db.php';

// Définir les en-têtes pour télécharger un fichier HTML
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="export_users.html"');

// Récupérer les utilisateurs depuis la base de données, incluant le champ cin
$stmt = $pdo->prepare("SELECT nom, prenom, cin, email, telephone, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Début du contenu HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportation des Utilisateurs - Green.tn</title>
    <!-- Inclure Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Styles personnalisés pour la signature, le tableau et le logo */
        .signature {
            font-family: 'Georgia', serif;
            color: #1F2937;
            border-top: 2px solid #10B981;
            padding-top: 1rem;
        }
        .signature h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #047857;
        }
        .signature p {
            font-size: 1rem;
            margin: 0.25rem 0;
        }
        .table-header {
            background-color: #047857;
            color: white;
        }
        .table-row:nth-child(even) {
            background-color: #F3F4F6;
        }
        .table-row:hover {
            background-color: #E5E7EB;
        }
        .admin-row {
            background-color: #991B1B !important;
            color: #FFFFFF !important;
        }
        .technicien-row {
            background-color: #10B981 !important;
            color: #FFFFFF !important;
        }
        .logo-text {
            font-family: 'Georgia', serif;
            font-size: 2.5rem;
            font-weight: bold;
            color: #047857;
            background-color: #ECFDF5;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="w-full max-w-5xl mx-auto p-8 bg-white rounded-lg shadow-xl my-8">
        <!-- Logo textuel -->
        <div class="flex justify-center mb-6">
            <div class="logo-text">Green.tn</div>
        </div>

        <!-- Titre -->
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Liste des Utilisateurs - Green.tn</h1>

        <!-- Tableau des utilisateurs -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="table-header">
                        <th class="py-3 px-4 text-left text-sm font-semibold">Nom</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold">Prénom</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold">CIN</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold">Email</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold">Téléphone</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold">Rôle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        // Déterminer la classe CSS en fonction du rôle
                        $rowClass = '';
                        if (strtolower($user['role']) === 'admin') {
                            $rowClass = 'admin-row';
                        } elseif (strtolower($user['role']) === 'technicien') {
                            $rowClass = 'technicien-row';
                        }
                        ?>
                        <tr class="table-row border-b <?php echo $rowClass; ?>">
                            <td class="py-2 px-4"><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($user['prenom']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($user['cin']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($user['telephone']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($user['role']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Signature -->
        <div class="mt-12 text-center signature">
            <h3>Green.tn</h3>
            <p>Rapport généré le <?php echo date('d/m/Y'); ?></p>
            <p>Contactez-nous : <a href="mailto:contact@green.tn" class="text-emerald-600 hover:underline">contact@green.tn</a></p>
            <p>Téléphone : +216 71 234 567</p>
            <p>Adresse : 123 Avenue de l'Environnement, Tunis, Tunisie</p>
            <p class="mt-2 italic text-sm">Engagés pour un avenir durable</p>
        </div>
    </div>
</body>
</html>
<?php
// Envoyer le contenu HTML et arrêter la mise en mémoire tampon
echo ob_get_clean();
?>