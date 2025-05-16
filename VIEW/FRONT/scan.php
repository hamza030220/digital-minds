<?php
// Inclure la connexion à la base de données
require_once __DIR__ . '/../../CONFIG/db.php';

// Vérifier l'identifiant d'export
$exportId = isset($_GET['export_id']) ? $_GET['export_id'] : '';

if (empty($exportId)) {
    die("Identifiant d'export manquant.");
}

// Récupérer les données depuis la table temporaire
try {
    $stmt = $pdo->prepare("SELECT data FROM temp_exports WHERE export_id = :export_id");
    $stmt->execute(['export_id' => $exportId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        die("Export non trouvé.");
    }
    $users = json_decode($result['data'], true);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Données des Utilisateurs - Green.tn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Georgia', serif; }
        .container { max-width: 800px; margin: 2rem auto; padding: 1rem; background: white; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .table-header { background-color: #047857; color: white; }
        .table-row:nth-child(even) { background-color: #F3F4F6; }
        .table-row:hover { background-color: #E5E7EB; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Données des Utilisateurs - Green.tn</h1>
        <div id="location" class="text-center mb-4">
            <p class="text-sm text-gray-600">Chargement de la localisation...</p>
        </div>
        <table class="w-full border-collapse">
            <thead>
                <tr class="table-header">
                    <th class="py-2 px-4 text-left text-sm font-semibold">Nom</th>
                    <th class="py-2 px-4 text-left text-sm font-semibold">Prénom</th>
                    <th class="py-2 px-4 text-left text-sm font-semibold">CIN</th>
                    <th class="py-2 px-4 text-left text-sm font-semibold">Email</th>
                    <th class="py-2 px-4 text-left text-sm font-semibold">Téléphone</th>
                    <th class="py-2 px-4 text-left text-sm font-semibold">Rôle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="table-row border-b">
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
    <script>
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lon)
                        .then(response => response.json())
                        .then(data => {
                            const location = data.display_name || 'Localisation inconnue';
                            document.getElementById('location').innerHTML = `<p class="text-sm text-gray-600">Lieu de scan : ${location}</p>`;
                            // Envoyer la localisation au serveur
                            fetch('/save_location.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ export_id: '<?php echo $exportId; ?>', latitude: lat, longitude: lon, address: location })
                            });
                        })
                        .catch(() => {
                            document.getElementById('location').innerHTML = '<p class="text-sm text-red-600">Impossible de récupérer l\'adresse.</p>';
                        });
                },
                () => {
                    document.getElementById('location').innerHTML = '<p class="text-sm text-red-600">Localisation non autorisée.</p>';
                }
            );
        } else {
            document.getElementById('location').innerHTML = '<p class="text-sm text-red-600">Localisation non supportée.</p>';
        }
    </script>
</body>
</html>