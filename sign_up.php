<?php
require_once __DIR__ . '/models/db.php';
// Include PHPMailer
require_once __DIR__ . '/PHPMailer-PHPMailer-19debc7/src/Exception.php';
require_once __DIR__ . '/PHPMailer-PHPMailer-19debc7/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-PHPMailer-19debc7/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$blacklist = array('example@domain.com', 'test@domain.com');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_BCRYPT);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';
    $telephone = trim($_POST['telephone']);
    $gouvernorats = $_POST['gouvernorats'];
    $age = $_POST['age'];
    $cin = trim($_POST['cin']);

    // Vérifier l'unicité de l'email et du CIN
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR cin = ?");
    $stmt->execute(array($email, $cin));
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['email'] === $email) {
            $error = "Email déjà utilisé.";
        } elseif ($row['cin'] === $cin) {
            $error = "CIN déjà utilisé.";
        }
    } else {
        $photoPath = null;

        // Handle webcam photo (base64)
        if (!empty($_POST['photo_base64'])) {
            $base64_string = $_POST['photo_base64'];
            $base64_string = str_replace('data:image/jpeg;base64,', '', $base64_string);
            $base64_string = str_replace(' ', '+', $base64_string);
            $photo_data = base64_decode($base64_string);
            $uploadDir = 'Uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $photoName = uniqid() . '.jpg';
            $photoPath = $uploadDir . $photoName;
            if (file_put_contents($photoPath, $photo_data) === false) {
                $error = "Erreur lors de l'enregistrement de la photo.";
            }
        }
        // Fallback to file upload
        elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoTmpPath = $_FILES['photo']['tmp_name'];
            $photoType = mime_content_type($photoTmpPath);
            $allowedExtensions = array('image/jpeg', 'image/png', 'image/gif');

            if (in_array($photoType, $allowedExtensions)) {
                $photoName = uniqid() . "_" . basename($_FILES['photo']['name']);
                $uploadDir = 'Uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $uploadFilePath = $uploadDir . $photoName;

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
        $stmt = $pdo->prepare("
            INSERT INTO users (email, mot_de_passe, nom, prenom, role, telephone, gouvernorats, photo, age, cin, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute(array($email, $password, $nom, $prenom, $role, $telephone, $gouvernorats, $photoPath, $age, $cin));
        $user_id = $pdo->lastInsertId();

        // Insert notification for admin
        $message = "Nouvel utilisateur inscrit : " . htmlspecialchars($prenom . ' ' . $nom);
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, is_read, created_at)
            VALUES (?, ?, FALSE, NOW())
        ");
        $stmt->execute(array($user_id, $message));

        // Send welcome email with PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'emnajouinii2000@gmail.com';
            $mail->Password = 'ggzhgbhgmaqzsnjc'; // Remplacez par le mot de passe d'application
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('emnajouinii2000@gmail.com', 'Location de Vélos');
            $mail->addAddress($email, "$prenom $nom");
            $mail->addReplyTo('emnajouinii2000@gmail.com', 'Location de Vélos');

            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Bienvenue sur notre plateforme de location de vélos !';
            $mail->Body = "
            <!DOCTYPE html>
            <html lang='fr'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { font-family: 'Montserrat', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #2ecc71, #27ae60); padding: 20px; text-align: center; }
                    .header img { max-width: 150px; }
                    .content { padding: 30px; }
                    h1 { color: #2c3e50; font-size: 24px; margin-bottom: 20px; }
                    p { color: #555; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
                    .button { display: inline-block; padding: 12px 24px; background-color: #27ae60; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; }
                    .button:hover { background-color: #219150; }
                    .footer { background: #2c3e50; color: #ffffff; text-align: center; padding: 15px; font-size: 14px; }
                    @media screen and (max-width: 600px) {
                        .container { margin: 10px; }
                        .content { padding: 20px; }
                        h1 { font-size: 20px; }
                        p { font-size: 14px; }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='http://localhost/projet/logo.jpg' alt='Logo'>
                    </div>
                    <div class='content'>
                        <h1>Bienvenue, $prenom $nom !</h1>
                        <p>Merci de vous être inscrit sur notre plateforme de location de vélos. Vous pouvez maintenant explorer votre ville de manière écologique et amusante !</p>
                        <p>Connectez-vous pour réserver votre vélo dès aujourd'hui :</p>
                        <p style='text-align: center;'>
                            <a href='http://localhost/projet/login.php' class='button'>Se connecter</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " Location de Vélos. Tous droits réservés.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Échec de l'envoi de l'email de bienvenue à $email: " . $mail->ErrorInfo);
        }

        $success = "Inscription réussie. Vous pouvez maintenant vous connecter.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.0/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #e8f5e9, #a8e6a3, #60c26d, #2e7d32);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #333;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(6px);
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .error-text {
            color: red;
            font-size: 0.9rem;
            margin-top: 5px;
            display: block;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            margin-bottom: 20px;
        }

        td {
            padding: 8px;
        }

        label {
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #219150;
        }

        p {
            text-align: center;
            margin-top: 15px;
        }

        a {
            color: #2980b9;
            text-decoration: none;
        }

        .logo {
            display: block;
            margin: 0 auto 20px;
        }

        .scan-button, .photo-button {
            cursor: pointer;
            color: #2980b9;
            margin-left: 10px;
            font-size: 1.2rem;
        }

        .cin-container, .photo-container {
            display: flex;
            align-items: center;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            max-width: 90%;
            width: 400px;
        }

        #scanner, #photo-capture {
            width: 100%;
            max-width: 300px;
            height: auto;
            border: 2px solid #ccc;
            border-radius: 8px;
        }

        #scanner-canvas, #photo-canvas {
            display: none;
        }

        .modal-content button {
            margin-top: 10px;
            padding: 10px 20px;
        }

        .modal-content button.capture {
            background-color: #27ae60;
        }

        .modal-content button.capture:hover {
            background-color: #219150;
        }

        .modal-content button.cancel {
            background-color: #e74c3c;
        }

        .modal-content button.cancel:hover {
            background-color: #c0392b;
        }

        .scan-error, .photo-error {
            color: red;
            margin-top: 10px;
        }

        .scan-mode {
            margin-top: 10px;
        }

        .scan-mode button {
            background-color: #2980b9;
            margin: 0 5px;
        }

        .scan-mode button:hover {
            background-color: #1f6391;
        }

        .scan-mode button.active {
            background-color: #1f6391;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 20px;
            }

            table, td {
                display: block;
                width: 100%;
            }

            td {
                margin-bottom: 10px;
            }

            .cin-container, .photo-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .scan-button, .photo-button {
                margin-left: 0;
                margin-top: 5px;
            }

            .modal-content {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.jpg" alt="Logo" class="logo" width="200">
        <h2>Inscription</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

        <form method="POST" action="sign_up.php" enctype="multipart/form-data" id="signupForm">
            <input type="hidden" name="photo_base64" id="photo_base64">
            <table>
                <tr>
                    <td><label for="nom">Nom</label></td>
                    <td>
                        <input type="text" name="nom" id="nom">
                        <span class="error-text" id="nomError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="prenom">Prénom</label></td>
                    <td>
                        <input type="text" name="prenom" id="prenom">
                        <span class="error-text" id="prenomError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="email">Email</label></td>
                    <td>
                        <input type="email" name="email" id="email">
                        <span class="error-text" id="emailError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="password">Mot de passe</label></td>
                    <td>
                        <input type="password" name="password" id="password">
                        <span class="error-text" id="passwordError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="telephone">Téléphone</label></td>
                    <td>
                        <input type="text" name="telephone" id="telephone" pattern="[0-9]{8}" title="8 chiffres requis">
                        <span class="error-text" id="telephoneError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="role">Rôle</label></td>
                    <td>
                        <select name="role" id="role">
                            <option value="user">Utilisateur</option>
                            <option value="technicien">Technicien</option>
                        </select>
                        <span class="error-text" id="roleError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="gouvernorats">Gouvernorat</label></td>
                    <td>
                        <select name="gouvernorats" id="gouvernorats">
                            <?php
                            $gouvernorats = array("Ariana","Beja","Ben Arous","Bizerte","Gabes","Gafsa","Jendouba","Kairouan","Kasserine","Kebili","Kef","Mahdia","Manouba","Medenine","Monastir","Nabeul","Sfax","Sidi Bouzid","Siliana","Sousse","Tataouine","Tozeur","Tunis","Zaghouan");
                            foreach ($gouvernorats as $gov) echo "<option value=\"$gov\">$gov</option>";
                            ?>
                        </select>
                        <span class="error-text" id="gouvernoratsError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="age">Âge</label></td>
                    <td>
                        <select name="age" id="age">
                            <?php for ($i=5;$i<=80;$i++) echo "<option value='$i'>$i</option>"; ?>
                        </select>
                        <span class="error-text" id="ageError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="cin">CIN</label></td>
                    <td>
                        <div class="cin-container">
                            <input type="text" name="cin" id="cin" pattern="[0-9]{8}" title="Le CIN doit comporter 8 chiffres">
                            <i class="fas fa-camera scan-button" title="Scanner le CIN"></i>
                        </div>
                        <span class="error-text" id="cinError"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="photo">Photo</label></td>
                    <td>
                        <div class="photo-container">
                            <input type="file" name="photo" id="photo" accept="image/*">
                            <i class="fas fa-camera photo-button" title="Prendre une photo"></i>
                        </div>
                        <span class="error-text" id="photoError"></span>
                    </td>
                </tr>
            </table>
            <button type="submit">S'inscrire</button>
        </form>
        <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
    </div>

    <!-- CIN Scanner Modal -->
    <div class="modal" id="scanModal">
        <div class="modal-content">
            <h3>Scanner le numéro de CIN</h3>
            <video id="scanner" autoplay></video>
            <canvas id="scanner-canvas"></canvas>
            <p class="scan-error" id="scanError"></p>
            <div class="scan-mode">
                <button id="ocrMode" class="active">OCR (Texte)</button>
                <button id="barcodeMode">Code-barres</button>
            </div>
            <button id="closeModal" class="cancel">Annuler</button>
        </div>
    </div>

    <!-- Photo Capture Modal -->
    <div class="modal" id="photoModal">
        <div class="modal-content">
            <h3>Prendre une photo</h3>
            <video id="photo-capture" autoplay></video>
            <canvas id="photo-canvas"></canvas>
            <p class="photo-error" id="photoError"></p>
            <button id="capturePhoto" class="capture">Capturer</button>
            <button id="closePhotoModal" class="cancel">Annuler</button>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById("signupForm").addEventListener("submit", function(event) {
            event.preventDefault();
            let isValid = true;

            // Reset error messages
            document.querySelectorAll(".error-text").forEach(span => span.textContent = "");

            // Get form values
            const nom = document.getElementById("nom").value.trim();
            const prenom = document.getElementById("prenom").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const telephone = document.getElementById("telephone").value.trim();
            const role = document.getElementById("role").value;
            const gouvernorats = document.getElementById("gouvernorats").value;
            const age = parseInt(document.getElementById("age").value);
            const cin = document.getElementById("cin").value.trim();
            const photoBase64 = document.getElementById("photo_base64").value;
            const photoFile = document.getElementById("photo").files[0];
            const blacklist = ['example@domain.com', 'test@domain.com'];

            // Validation rules
            if (nom.length < 2) {
                document.getElementById("nomError").textContent = "Le nom doit contenir au moins 2 caractères.";
                isValid = false;
            }
            if (prenom.length < 2) {
                document.getElementById("prenomError").textContent = "Le prénom doit contenir au moins 2 caractères.";
                isValid = false;
            }
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                document.getElementById("emailError").textContent = "Email invalide.";
                isValid = false;
            } else if (blacklist.includes(email)) {
                document.getElementById("emailError").textContent = "Cet email est interdit.";
                isValid = false;
            }
            if (password.length < 6) {
                document.getElementById("passwordError").textContent = "Le mot de passe doit contenir au moins 6 caractères.";
                isValid = false;
            }
            if (!telephone.match(/^\d{8}$/)) {
                document.getElementById("telephoneError").textContent = "Le numéro de téléphone doit comporter exactement 8 chiffres.";
                isValid = false;
            }
            if (!["user", "technicien"].includes(role)) {
                document.getElementById("roleError").textContent = "Rôle invalide.";
                isValid = false;
            }
            const validGouvernorats = <?php echo json_encode($gouvernorats); ?>;
            if (!validGouvernorats.includes(gouvernorats)) {
                document.getElementById("gouvernoratsError").textContent = "Gouvernorat invalide.";
                isValid = false;
            }
            if (isNaN(age) || age < 5 || age > 80) {
                document.getElementById("ageError").textContent = "Âge invalide (5 à 80 ans).";
                isValid = false;
            }
            if (!cin.match(/^\d{8}$/)) {
                document.getElementById("cinError").textContent = "Le CIN doit comporter exactement 8 chiffres.";
                isValid = false;
            }
            if (!photoBase64 && !photoFile) {
                document.getElementById("photoError").textContent = "Une photo est requise.";
                isValid = false;
            }

            // Submit form if valid
            if (isValid) {
                this.submit();
            }
        });

        // CIN Scanner
        const cinInput = document.getElementById("cin");
        const scanButton = document.querySelector(".scan-button");
        const scanModal = document.getElementById("scanModal");
        const closeModalButton = document.getElementById("closeModal");
        const scanError = document.getElementById("scanError");
        const video = document.getElementById("scanner");
        const canvas = document.getElementById("scanner-canvas");
        const ocrModeButton = document.getElementById("ocrMode");
        const barcodeModeButton = document.getElementById("barcodeMode");
        let stream = null;
        let scanning = false;
        let mode = "ocr";

        scanButton.addEventListener("click", function() {
            scanModal.style.display = "flex";
            ocrModeButton.classList.add("active");
            barcodeModeButton.classList.remove("active");
            mode = "ocr";
            startScanner();
        });

        closeModalButton.addEventListener("click", function() {
            stopScanner();
            scanModal.style.display = "none";
            scanError.textContent = "";
        });

        ocrModeButton.addEventListener("click", function() {
            mode = "ocr";
            ocrModeButton.classList.add("active");
            barcodeModeButton.classList.remove("active");
            stopScanner();
            startScanner();
        });

        barcodeModeButton.addEventListener("click", function() {
            mode = "barcode";
            barcodeModeButton.classList.add("active");
            ocrModeButton.classList.remove("active");
            stopScanner();
            startScanner();
        });

        function startScanner() {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment", width: 320, height: 240 } })
                .then(function(mediaStream) {
                    stream = mediaStream;
                    video.srcObject = stream;
                    video.play();
                    scanning = true;
                    console.log("Caméra démarrée en mode : " + mode);
                    if (mode === "ocr") {
                        scanFrameOCR();
                    } else {
                        scanFrameBarcode();
                    }
                })
                .catch(function(err) {
                    scanError.textContent = "Erreur d'accès à la caméra : " + err.message;
                    console.error("Erreur caméra :", err);
                });
        }

        function stopScanner() {
            scanning = false;
            if (mode === "barcode") {
                Quagga.stop();
            }
            if (stream) {
                stream.getTracks().forEach(function(track) { track.stop(); });
                stream = null;
            }
        }

        function preprocessImage(context, width, height) {
            var imageData = context.getImageData(0, 0, width, height);
            var data = imageData.data;

            for (var i = 0; i < data.length; i += 4) {
                var avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
                data[i] = data[i + 1] = data[i + 2] = avg;
            }

            var contrastFactor = 1.5;
            for (var i = 0; i < data.length; i += 4) {
                data[i] = Math.min(255, Math.max(0, (data[i] - 128) * contrastFactor + 128));
                data[i + 1] = Math.min(255, Math.max(0, (data[i + 1] - 128) * contrastFactor + 128));
                data[i + 2] = Math.min(255, Math.max(0, (data[i + 2] - 128) * contrastFactor + 128));
            }

            context.putImageData(imageData, 0, 0);
        }

        function scanFrameOCR() {
            if (!scanning) return;

            var ctx = canvas.getContext("2d");
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            preprocessImage(ctx, canvas.width, canvas.height);

            Tesseract.recognize(canvas, 'eng', {
                tessedit_char_whitelist: '0123456789',
                tessedit_pageseg_mode: 6
            }).then(function(result) {
                console.log("Texte détecté :", result.data.text);
                var cinMatch = result.data.text.match(/\b\d{8}\b/);
                if (cinMatch) {
                    cinInput.value = cinMatch[0];
                    stopScanner();
                    scanModal.style.display = "none";
                    scanError.textContent = "";
                    console.log("CIN valide détecté :", cinMatch[0]);
                } else {
                    scanError.textContent = "Aucun numéro de CIN valide détecté (8 chiffres requis).";
                    console.log("Aucun CIN valide trouvé dans :", result.data.text);
                    setTimeout(scanFrameOCR, 1000);
                }
            }).catch(function(err) {
                scanError.textContent = "Erreur OCR : " + err.message;
                console.error("Erreur OCR :", err);
                setTimeout(scanFrameOCR, 1000);
            });
        }

        function scanFrameBarcode() {
            if (!scanning) return;

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: video,
                    constraints: {
                        width: 320,
                        height: 240,
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: ["code_128_reader", "ean_reader"]
                }
            }, function(err) {
                if (err) {
                    scanError.textContent = "Erreur QuaggaJS : " + err.message;
                    console.error("Erreur QuaggaJS :", err);
                    return;
                }
                Quagga.start();
                console.log("QuaggaJS démarré");
            });

            Quagga.onDetected(function(result) {
                var code = result.codeResult.code;
                console.log("Code-barres détecté :", code);
                if (code.match(/^\d{8}$/)) {
                    cinInput.value = code;
                    stopScanner();
                    scanModal.style.display = "none";
                    scanError.textContent = "";
                    console.log("CIN valide détecté :", code);
                } else {
                    scanError.textContent = "Code-barres invalide (doit contenir 8 chiffres).";
                    console.log("Code-barres invalide :", code);
                }
            });
        }

        scanModal.addEventListener("click", function(e) {
            if (e.target === scanModal) {
                stopScanner();
                scanModal.style.display = "none";
                scanError.textContent = "";
            }
        });

        // Photo Capture
        const photoButton = document.querySelector(".photo-button");
        const photoModal = document.getElementById("photoModal");
        const photoCapture = document.getElementById("photo-capture");
        const photoCanvas = document.getElementById("photo-canvas");
        const capturePhotoButton = document.getElementById("capturePhoto");
        const closePhotoModalButton = document.getElementById("closePhotoModal");
        const photoError = document.getElementById("photoError");
        const photoBase64Input = document.getElementById("photo_base64");
        let photoStream = null;

        photoButton.addEventListener("click", function() {
            console.log("Bouton photo cliqué");
            photoModal.style.display = "flex";
            startPhotoCapture();
        });

        function startPhotoCapture() {
            console.log("Tentative d'accès à la caméra...");
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: { ideal: "user" }, 
                    width: { ideal: 320 }, 
                    height: { ideal: 240 } 
                } 
            })
                .then(function(mediaStream) {
                    console.log("Flux vidéo obtenu");
                    photoStream = mediaStream;
                    photoCapture.srcObject = mediaStream;
                    photoCapture.play().then(() => {
                        console.log("Vidéo en cours de lecture");
                    }).catch(err => {
                        console.error("Erreur lors de la lecture de la vidéo :", err);
                        photoError.textContent = "Erreur lors de la lecture de la vidéo : " + err.message;
                    });
                })
                .catch(function(err) {
                    console.error("Erreur d'accès à la caméra :", err);
                    photoError.textContent = "Impossible d'accéder à la caméra : " + err.message;
                    if (err.name === "NotAllowedError") {
                        photoError.textContent += " (Vérifiez les permissions de la caméra dans votre navigateur)";
                    } else if (err.name === "NotFoundError") {
                        photoError.textContent += " (Aucune caméra détectée)";
                    }
                });
        }

        capturePhotoButton.addEventListener("click", function() {
            if (!photoStream) {
                photoError.textContent = "Aucun flux vidéo actif. Essayez de rouvrir la caméra.";
                console.error("Aucun flux vidéo pour la capture");
                return;
            }
            console.log("Capture de la photo...");
            var ctx = photoCanvas.getContext("2d");
            photoCanvas.width = photoCapture.videoWidth;
            photoCanvas.height = photoCapture.videoHeight;
            ctx.drawImage(photoCapture, 0, 0, photoCanvas.width, photoCanvas.height);
            var dataUrl = photoCanvas.toDataURL("image/jpeg");
            photoBase64Input.value = dataUrl;
            console.log("Photo capturée et stockée en base64");
            stopPhotoCapture();
            photoModal.style.display = "none";
            photoError.textContent = "";
        });

        closePhotoModalButton.addEventListener("click", function() {
            console.log("Fermeture de la modale photo");
            stopPhotoCapture();
            photoModal.style.display = "none";
            photoError.textContent = "";
        });

        photoModal.addEventListener("click", function(e) {
            if (e.target === photoModal) {
                console.log("Clic à l'extérieur de la modale photo");
                stopPhotoCapture();
                photoModal.style.display = "none";
                photoError.textContent = "";
            }
        });

        function stopPhotoCapture() {
            if (photoStream) {
                console.log("Arrêt du flux vidéo");
                photoStream.getTracks().forEach(function(track) {
                    track.stop();
                });
                photoStream = null;
            }
        }

        function drawGuide() {
            if (!scanning) return;
            var ctx = canvas.getContext("2d");
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.strokeStyle = "red";
            ctx.lineWidth = 2;
            ctx.strokeRect(50, 50, canvas.width - 100, canvas.height - 100);
            requestAnimationFrame(drawGuide);
        }
        video.addEventListener("play", function() { requestAnimationFrame(drawGuide); });
    </script>
</body>
</html>