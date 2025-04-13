<?php
session_start();
require 'db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Vérifier si l'utilisateur existe
if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Vérifier si l'utilisateur est un administrateur
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

// Récupérer l'ID de l'utilisateur à modifier (si administrateur)
if ($is_admin && isset($_GET['id'])) {
    $target_user_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        echo "Erreur : utilisateur cible introuvable.";
        exit();
    }
} else {
    // Si l'utilisateur n'est pas administrateur, on permet de modifier ses propres informations
    $target_user = $user;
    $target_user_id = $user_id;
}

// Traitement du formulaire de mise à jour des informations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $Gouvernorat = $_POST['gouvernorat']; // ✅ Correspond au name du <select>
    $telephone = $_POST['telephone'];
    $age = $_POST['age'];

    // Gestion de la photo de profil
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_name = $_FILES['photo']['name'];
        $photo_path = 'uploads/' . basename($photo_name);
        move_uploaded_file($photo_tmp, $photo_path);
    } else {
        $photo_path = $target_user['photo'];
    }

    // ✅ Requête avec le nom correct de la colonne : gouvernorats
    $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, photo = ?, age = ?, gouvernorats = ? WHERE id = ?");
    $stmt->execute([$nom, $prenom, $email, $telephone, $photo_path, $age, $Gouvernorat, $target_user_id]);

    $_SESSION['success_message'] = "Les informations ont été mises à jour avec succès.";

    if ($is_admin) {
        header("Location: dashboard.php");
    } else {
        header("Location: info2.php?page=gestion_utilisateurs&action=infos");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier les informations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-info">
            <h2>Modifier les informations</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <h3>Informations personnelles</h3>

                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($target_user['nom']); ?>" required>

                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($target_user['prenom']); ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($target_user['email']); ?>" required>

                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" value="<?php echo htmlspecialchars($target_user['telephone']); ?>" required>

                <label for="age">Âge</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($target_user['age']); ?>" required>

                <label for="gouvernorat">Gouvernorat</label>
                <select id="gouvernorat" name="gouvernorat" required>
                    <?php
                    $gouvernorats = [
                        'Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan',
                        'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Manouba', 'Medenine', 'Monastir',
                        'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'
                    ];

                    foreach ($gouvernorats as $gouv) {
                        $selected = ($gouv == $target_user['gouvernorats']) ? 'selected' : '';
                        echo "<option value=\"$gouv\" $selected>$gouv</option>";
                    }
                    ?>
                </select>

                <label for="photo">Photo de profil</label>
                <input type="file" id="photo" name="photo">

                <button type="submit">Mettre à jour</button>
            </form>
        </div>
    </div>

    <div class="logout-container">
        <a href="logout.php"><button class="logout-button">Déconnexion</button></a>
    </div>
</body>
</html>
