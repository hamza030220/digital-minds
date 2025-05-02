<?php
session_start();
require_once __DIR__ . '/models/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';

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
    $target_user = $user;
    $target_user_id = $user_id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $Gouvernorats = $_POST['gouvernorats'];
    $telephone = $_POST['telephone'];
    $age = $_POST['age'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_name = $_FILES['photo']['name'];
        $photo_path = 'uploads/' . basename($photo_name);
        move_uploaded_file($photo_tmp, $photo_path);
    } else {
        $photo_path = $target_user['photo'];
    }

    $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, photo = ?, age = ?, gouvernorats = ? WHERE id = ?");
    $stmt->execute([$nom, $prenom, $email, $telephone, $photo_path, $age, $Gouvernorats, $target_user_id]);

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
    <title>Modifier les informations</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(to bottom, #e9f5ec, #a8e6a3, #60c26d); /* dégradé de verts */
    animation: fadeIn 1s ease-in;
}

        .profile-container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-bottom: 25px;
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #444;
        }
        input[type="text"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .error-message {
            color: red;
            font-size: 0.85em;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        button {
            background-color: #28a745; /* Vert */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: block;
            margin: 0 auto;
        }
        button:hover {
            background-color: #218838; /* Vert foncé */
        }
        .return-container {
            text-align: center;
            margin-top: 30px;
        }
        .return-button {
            background-color: #6c757d; /* Gris */
            padding: 10px 20px;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }
        .return-button:hover {
            background-color: #5a6268; /* Gris foncé */
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Modifier les informations</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <p class="success"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
            <?php
            $fields = [
                'nom' => 'Nom',
                'prenom' => 'Prénom',
                'email' => 'Email',
                'telephone' => 'Téléphone',
                'age' => 'Âge'
            ];

            foreach ($fields as $id => $label):
                $value = $target_user[$id];
            ?>
                <div class="form-group">
                    <label for="<?= $id ?>"><?= $label ?></label>
                    <input type="text" id="<?= $id ?>" name="<?= $id ?>" value="<?= htmlspecialchars($value) ?>">
                    <div id="error-<?= $id ?>" class="error-message"></div>
                </div>
            <?php endforeach; ?>

            <div class="form-group">
                <label for="gouvernorats">Gouvernorat</label>
                <select id="gouvernorats" name="gouvernorats">
                    <?php
                    $gouvernorats = [
                        'Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan',
                        'Kasserine', 'Kebili', 'La Manouba', 'Mahdia', 'Medenine', 'Monastir',
                        'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'
                    ];
                    foreach ($gouvernorats as $gouv) {
                        $selected = ($gouv == $target_user['gouvernorats']) ? 'selected' : '';
                        echo "<option value=\"$gouv\" $selected>$gouv</option>";
                    }
                    ?>
                </select>
                <div id="error-gouvernorats" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="photo">Photo de profil</label>
                <input type="file" id="photo" name="photo">
            </div>

            <button type="submit">Mettre à jour</button>
        </form>
    </div>

    <div class="return-container">
        <?php if ($is_admin): ?>
            <a href="dashboard.php"><button class="return-button">Retour au tableau de bord</button></a>
        <?php else: ?>
            <a href="info2.php?page=gestion_utilisateurs&action=infos"><button class="return-button">Retour à mes informations</button></a>
        <?php endif; ?>
    </div>

    <script>
    function validateForm() {
        let valid = true;

        document.querySelectorAll('.error-message').forEach(el => el.innerText = '');

        const fields = ['nom', 'prenom', 'email', 'telephone', 'age'];
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        fields.forEach(field => {
            const val = document.getElementById(field).value.trim();
            if (!val) {
                document.getElementById('error-' + field).innerText = `Le champ ${field} est requis.`;
                valid = false;
            }
        });

        const email = document.getElementById('email').value.trim();
        if (!emailRegex.test(email)) {
            document.getElementById('error-email').innerText = 'Veuillez entrer un email valide.';
            valid = false;
        }

        const telephone = document.getElementById('telephone').value.trim();
        if (!/^\d{8,15}$/.test(telephone)) {
            document.getElementById('error-telephone').innerText = 'Veuillez entrer un numéro de téléphone valide.';
            valid = false;
        }

        const age = parseInt(document.getElementById('age').value.trim(), 10);
        if (isNaN(age) || age < 1 || age > 120) {
            document.getElementById('error-age').innerText = 'Veuillez entrer un âge valide.';
            valid = false;
        }

        const gouv = document.getElementById('gouvernorats').value;
        if (!gouv) {
            document.getElementById('error-gouvernorats').innerText = 'Veuillez sélectionner un gouvernorat.';
            valid = false;
        }

        return valid;
    }
    </script>
</body>
</html>
