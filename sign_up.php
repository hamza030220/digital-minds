<?php
// Inclure db.php pour la connexion 
require 'db.php';

$blacklist = ['example@domain.com', 'test@domain.com'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_BCRYPT);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $role = $_POST['role'];
    $telephone = trim($_POST['telephone']);
    $gouvernorats = $_POST['gouvernorats'];
    $age = $_POST['age'];

    // Contrôles de validation
    if (empty($nom) || strlen($nom) < 2) {
        $error = "Le nom doit contenir au moins 2 caractères.";
    } elseif (empty($prenom) || strlen($prenom) < 2) {
        $error = "Le prénom doit contenir au moins 2 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (in_array($email, $blacklist)) {
        $error = "Cet email est interdit.";
    } elseif (strlen($password_raw) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (empty($telephone)) {
        $error = "Le numéro de téléphone ne peut pas être vide.";
    } elseif (!preg_match('/^[0-9]{8}$/', $telephone)) {
        $error = "Le numéro de téléphone doit comporter exactement 8 chiffres.";
    } elseif (!in_array($role, ['user', 'technicien'])) {
        $error = "Rôle invalide.";
    } elseif (!is_numeric($age) || $age < 5 || $age > 80) {
        $error = "Âge invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email déjà utilisé.";
        }
    }

    if (!isset($error)) {
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoTmpPath = $_FILES['photo']['tmp_name'];
$photoName = uniqid() . '_' . basename($_FILES['photo']['name']);
move_uploaded_file($_FILES['photo']['tmp_name'], 'uploads/' . $photoName);
            $photoType = $_FILES['photo']['type'];
            $allowedExtensions = ['image/jpeg', 'image/png', 'image/gif'];

            if (in_array($photoType, $allowedExtensions)) {
                $newPhotoName = uniqid() . "_" . basename($photoName);
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $uploadFilePath = $uploadDir . $newPhotoName;

                if (move_uploaded_file($photoTmpPath, $uploadFilePath)) {
                    $photoPath = $uploadFilePath;
                } else {
                    $error = "Erreur lors du téléchargement de l'image.";
                }
            } else {
                $error = "Veuillez télécharger une image valide (JPG, PNG, GIF).";
            }
        }
    }

    if (!isset($error)) {
        $stmt = $pdo->prepare("INSERT INTO users (email, mot_de_passe, nom, prenom, role, telephone, gouvernorats, photo, age) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $password, $nom, $prenom, $role, $telephone, $gouvernorats, $photoPath, $age]);
        $success = "Inscription réussie. Vous pouvez maintenant vous connecter.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<center><img src="logo.jpg" alt="Logo" class="logo" width="220px"></center>

<div class="container">
    <h2>Inscription</h2>
    <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
    <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>

    <div class="registration-frame">
        <form method="POST" action="sign_up.php" enctype="multipart/form-data">
            <table class="registration-table">
                <tr><td><label for="nom">Nom</label></td><td><input type="text" name="nom" required></td></tr>
                <tr><td><label for="prenom">Prénom</label></td><td><input type="text" name="prenom" required></td></tr>
                <tr><td><label for="email">Email</label></td><td><input type="email" name="email" required></td></tr>
                <tr><td><label for="password">Mot de passe</label></td><td><input type="password" name="password" required></td></tr>
                <tr><td><label for="telephone">Téléphone</label></td><td><input type="text" name="telephone" pattern="[0-9]{8}" title="8 chiffres requis" required></td></tr>
                <tr>
                    <td><label for="role">Rôle</label></td>
                    <td><select name="role"><option value="user">Utilisateur</option><option value="technicien">Technicien</option></select></td>
                </tr>
                <tr><td><label for="gouvernorats">Gouvernorat</label></td>
                    <td><select name="gouvernorats" required>
                        <option value="Ariana">Ariana</option><option value="Beja">Beja</option>
                        <option value="Ben Arous">Ben Arous</option><option value="Bizerte">Bizerte</option>
                        <option value="Gabes">Gabes</option><option value="Gafsa">Gafsa</option>
                        <option value="Jendouba">Jendouba</option><option value="Kairouan">Kairouan</option>
                        <option value="Kasserine">Kasserine</option><option value="Kebili">Kebili</option>
                        <option value="Kef">Kef</option><option value="Mahdia">Mahdia</option>
                        <option value="Manouba">Manouba</option><option value="Medenine">Medenine</option>
                        <option value="Monastir">Monastir</option><option value="Nabeul">Nabeul</option>
                        <option value="Sfax">Sfax</option><option value="Sidi Bouzid">Sidi Bouzid</option>
                        <option value="Siliana">Siliana</option><option value="Sousse">Sousse</option>
                        <option value="Tataouine">Tataouine</option><option value="Tozeur">Tozeur</option>
                        <option value="Tunis">Tunis</option><option value="Zaghouan">Zaghouan</option>
                    </select></td></tr>
                <tr><td><label for="age">Âge</label></td>
                    <td><select name="age" required><?php for ($i=5;$i<=80;$i++) echo "<option value='$i'>$i</option>"; ?></select></td>
                </tr>
                <tr><td><label for="photo">Photo</label></td><td><input type="file" name="photo" accept="image/*"></td></tr>
            </table>
            <button type="submit">S'inscrire</button>
        </form>
    </div>

    <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
</div>

<script>
document.querySelector("form").addEventListener("submit", function(event) {
    let nom = document.querySelector("[name='nom']").value.trim();
    let prenom = document.querySelector("[name='prenom']").value.trim();
    let email = document.querySelector("[name='email']").value.trim();
    let password = document.querySelector("[name='password']").value;
    let telephone = document.querySelector("[name='telephone']").value.trim();
    let age = document.querySelector("[name='age']").value;

    let errors = [];

    if (nom.length < 2) errors.push("Nom trop court.");
    if (prenom.length < 2) errors.push("Prénom trop court.");
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.push("Email invalide.");
    if (password.length < 6) errors.push("Mot de passe trop court.");
    if (!telephone.match(/^\d{8}$/)) errors.push("Téléphone invalide (8 chiffres).\n");
    if (age < 5 || age > 80) errors.push("Âge invalide.");

    if (errors.length > 0) {
        event.preventDefault();
        alert(errors.join("\n"));
    }
});
</script>
</body>
</html>