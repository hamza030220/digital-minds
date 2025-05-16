<?php
require_once __DIR__ . '/../../CONFIG/db.php';
require_once __DIR__ . '/../../CONTROLLER/UserController.php'; // User-related logic
require_once __DIR__ . '/../../MODEL/User.php'; // User model for database operations


session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Generate CAPTCHA math question if not set
if (!isset($_SESSION['captcha_answer'])) {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_question'] = "Combien font $num1 + $num2 ?";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}

if (isset($_SESSION['user_id'])) {
    $role = strtolower(trim($_SESSION['role']));
    header("Location: " . ($role === 'admin' ? 'dashboard.php' : 'info2.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $captcha_input = trim($_POST['captcha']);

    // Verify CAPTCHA
    if (!is_numeric($captcha_input) || (int)$captcha_input !== $_SESSION['captcha_answer']) {
        $error = "Réponse CAPTCHA incorrecte.";
        // Regenerate CAPTCHA
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $_SESSION['captcha_question'] = "Combien font $num1 + $num2 ?";
        $_SESSION['captcha_answer'] = $num1 + $num2;
    } else {
        // Verify email and password
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['mot_de_passe']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = strtolower(trim($user['role']));
            session_regenerate_id(true);
            header("Location: " . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : 'info2.php'));
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
            // Regenerate CAPTCHA
            $num1 = rand(1, 9);
            $num2 = rand(1, 9);
            $_SESSION['captcha_question'] = "Combien font $num1 + $num2 ?";
            $_SESSION['captcha_answer'] = $num1 + $num2;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Location de Vélos</title>

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- TensorFlow.js and face-api.js for Face ID -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.18.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f5e9, #4caf50, #2e7d32);
            animation: fadeIn 1s ease-in-out;
            transition: background 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            display: flex;
            width: 85%;
            max-width: 1200px;
            height: 650px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(5px);
            background-color: rgba(255, 255, 255, 0.8);
            position: relative;
        }

        .left {
            flex: 1;
            padding: 60px;
            background-color: rgba(255,255,255,0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .left img.logo {
            width: 150px;
            margin-bottom: 30px;
        }

        .left h1 {
            font-size: 2.6rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .left p {
            font-size: 1rem;
            color: #555;
            line-height: 1.8;
        }

        .right {
            flex: 1;
            background-color: rgba(255,255,255,0.6);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .connection-box {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(6px);
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .right h2 {
            text-align: center;
            margin-bottom: 35px;
            color: #2c3e50;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
            animation: fadeInError 0.5s ease;
        }

        .error-text {
            color: red;
            font-size: 0.9rem;
            margin-top: 5px;
            display: block;
        }

        @keyframes fadeInError {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .login-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .login-tab {
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 8px 8px 0 0;
            margin: 0 5px;
            cursor: pointer;
            font-weight: 600;
            color: #2c3e50;
            transition: background 0.3s;
        }

        .login-tab.active {
            background: #2ecc71;
            color: white;
        }

        .login-tab:hover {
            background: #27ae60;
            color: white;
        }

        .login-content {
            display: none;
        }

        .login-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 25px;
        }

        input[type="email"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 14px 14px 14px 40px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus {
            border-color: #4caf50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #2ecc71;
            border: none;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        button:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .right p {
            text-align: center;
            margin-top: 15px;
        }

        .right a {
            color: #2980b9;
            text-decoration: none;
        }

        .theme-toggle-wrapper {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .theme-toggle {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            line-height: 48px;
            text-align: center;
            font-size: 1.4rem;
            color: #2c3e50;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: background 0.3s, color 0.3s, transform 0.3s;
        }

        .theme-toggle:hover {
            background: #4caf50;
            color: #fff;
            transform: scale(1.1);
        }

        .theme-toggle i {
            line-height: inherit;
        }

        /* Dark mode */
        body.dark-mode {
            background: linear-gradient(135deg, #1a3c34, #2e7d32);
        }

        body.dark-mode .container {
            background-color: rgba(30, 30, 30, 0.85);
        }

        body.dark-mode .left,
        body.dark-mode .right {
            background-color: rgba(30, 30, 30, 0.9);
            color: white;
        }

        body.dark-mode .left h1,
        body.dark-mode .right h2 {
            color: white;
        }

        body.dark-mode input[type="email"],
        body.dark-mode input[type="password"],
        body.dark-mode input[type="number"] {
            background-color: #333;
            border: 1px solid #555;
            color: white;
        }

        body.dark-mode input[type="email"]:focus,
        body.dark-mode input[type="password"]:focus,
        body.dark-mode input[type="number"]:focus {
            border-color: #4caf50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
        }

        body.dark-mode .right a {
            color: #1abc9c;
        }

        body.dark-mode .theme-toggle {
            background: #2e7d32;
            border: 1px solid #4caf50;
            color: #fff;
        }

        body.dark-mode .theme-toggle:hover {
            background: #4caf50;
            color: #fff;
        }

        body.dark-mode .error-message {
            background: #4b1c1c;
            color: #f87171;
        }

        body.dark-mode .error-text {
            color: #f87171;
        }

        body.dark-mode .login-tab {
            background: #444;
            color: #fff;
        }

        body.dark-mode .login-tab.active {
            background: #4caf50;
        }

        body.dark-mode .login-tab:hover {
            background: #4caf50;
        }

        .social-icons {
            text-align: center;
            margin-top: 25px;
        }

        .social-icons a {
            margin: 0 12px;
            font-size: 1.6rem;
            color: #333;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: #4caf50;
        }

        .fa-instagram { color: #e1306c; }
        .fa-facebook { color: #3b5998; }

        body.dark-mode .social-icons a {
            color: white;
        }

        body.dark-mode .social-icons a:hover {
            color: #4caf50;
        }

        /* CAPTCHA Styling */
        .captcha-container {
            margin-bottom: 25px;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        .captcha-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .captcha-title::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #4caf50;
            margin-right: 8px;
            font-size: 1rem;
        }

        .captcha-question {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2c3e50;
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            padding: 12px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 15px;
            border: 1px solid #d0d0d0;
        }

        .captcha-input-wrapper {
            position: relative;
            width: 100%;
        }

        .captcha-input-wrapper::before {
            content: '\f1ec';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #4caf50;
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
        }

        body.dark-mode .captcha-container {
            background: rgba(50, 50, 50, 0.9);
            border: 1px solid #555;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        body.dark-mode .captcha-title,
        body.dark-mode .captcha-question {
            color: #ffffff;
            background: linear-gradient(135deg, #444, #333);
            border: 1px solid #666;
        }

        body.dark-mode .captcha-title::before,
        body.dark-mode .captcha-input-wrapper::before {
            color: #4caf50;
        }

        /* Face ID Modal */
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

        #face-scanner {
            width: 100%;
            max-width: 300px;
            height: auto;
            border: 2px solid #ccc;
            border-radius: 8px;
        }

        #face-canvas {
            display: none;
        }

        .face-error {
            color: red;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .modal-content button {
            margin-top: 10px;
            padding: 10px 20px;
        }

        .modal-content button.scan {
            background-color: #27ae60;
        }

        .modal-content button.scan:hover {
            background-color: #219150;
        }

        .modal-content button.cancel {
            background-color: #e74c3c;
        }

        .modal-content button.cancel:hover {
            background-color: #c0392b;
        }

        body.dark-mode .modal-content {
            background: #333;
            color: white;
        }

        @media screen and (max-width: 900px) {
            .container {
                flex-direction: column;
                height: auto;
                margin: 20px;
            }

            .left, .right {
                padding: 40px 30px;
            }

            .captcha-container {
                padding: 15px;
            }

            .theme-toggle-wrapper {
                top: 10px;
                left: 10px;
            }

            .theme-toggle {
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 1.2rem;
            }

            .modal-content {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="theme-toggle-wrapper">
        <button class="theme-toggle"><i class="fas fa-moon"></i></button>
    </div>
    <div class="container">
        <div class="left">
            <img src="logo.jpg" alt="Logo" class="logo">
            <h1>Bienvenue !</h1>
            <p>
                Louez un vélo en toute simplicité. Grâce à notre plateforme, explorez votre ville de manière écologique,
                économique et amusante. Connectez-vous pour réserver votre vélo dès maintenant !
            </p>
        </div>
        <div class="right">
            <div class="connection-box">
                <h2>Connexion</h2>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div class="login-tabs">
                    <div class="login-tab active" data-tab="email">Email & Mot de passe</div>
                    <div class="login-tab" data-tab="faceid">Face ID</div>
                </div>
                <div class="login-content active" id="email-login">
                    <form method="POST" action="login.php" id="loginForm">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Adresse e-mail">
                            <span class="error-text" id="emailError"></span>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Mot de passe">
                            <span class="error-text" id="passwordError"></span>
                        </div>
                        <!-- CAPTCHA Widget -->
                        <div class="captcha-container">
                            <div class="captcha-title">Vérifiez que vous n'êtes pas un robot</div>
                            <div class="captcha-question"><?php echo isset($_SESSION['captcha_question']) ? htmlspecialchars($_SESSION['captcha_question']) : 'Chargement...'; ?></div>
                            <div class="captcha-input-wrapper">
                                <input type="number" name="captcha" placeholder="Entrez la réponse">
                                <span class="error-text" id="captchaError"></span>
                            </div>
                        </div>
                        <button type="submit">Se connecter</button>
                    </form>
                </div>
                <div class="login-content" id="faceid-login">
                    <button id="faceid-button">Se connecter avec Face ID</button>
                </div>
                <p><a href="forgot_password.php">Mot de passe oublié ?</a></p>
                <p>Pas encore inscrit ? <a href="sign_up.php">Créer un compte</a></p>
                <div class="social-icons">
                    <a href="insta.html" target="_blank" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="fac.html" target="_blank" aria-label="Facebook">
                        <i class="fab fa-facebook"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Face ID Modal -->
    <div class="modal" id="faceModal">
        <div class="modal-content">
            <h3>Connexion avec Face ID</h3>
            <video id="face-scanner" autoplay></video>
            <canvas id="face-canvas"></canvas>
            <p class="face-error" id="faceError"></p>
            <button id="scanFace" class="scan">Scanner</button>
            <button id="closeFaceModal" class="cancel">Annuler</button>
        </div>
    </div>

    <script>
        // Mode sombre toggle
        document.querySelector(".theme-toggle").addEventListener("click", () => {
            const isDark = document.body.classList.toggle("dark-mode");
            localStorage.setItem("theme", isDark ? "dark" : "light");
            const icon = document.querySelector(".theme-toggle i");
            if (isDark) {
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
                console.log("Switched to dark mode, icon: fa-sun");
            } else {
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
                console.log("Switched to light mode, icon: fa-moon");
            }
        });

        window.addEventListener("DOMContentLoaded", () => {
            const theme = localStorage.getItem("theme");
            const icon = document.querySelector(".theme-toggle i");
            if (theme === "dark") {
                document.body.classList.add("dark-mode");
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
                console.log("Loaded dark mode, icon: fa-sun");
            } else {
                document.body.classList.remove("dark-mode");
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
                console.log("Loaded light mode, icon: fa-moon");
            }

            // Load face-api.js models
            Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri('/projetweb/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/projetweb/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/projetweb/models')
            ]).then(() => {
                console.log("face-api.js models loaded");
            }).catch(err => {
                console.error("Error loading face-api.js models:", err);
            });
        });

        // Form validation
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            event.preventDefault();
            let isValid = true;

            // Reset error messages
            document.querySelectorAll(".error-text").forEach(span => span.textContent = "");

            // Get form values
            const email = document.querySelector("input[name='email']").value.trim();
            const password = document.querySelector("input[name='password']").value;
            const captcha = document.querySelector("input[name='captcha']").value.trim();
            const captchaAnswer = <?php echo json_encode($_SESSION['captcha_answer']); ?>;

            // Validation rules
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                document.getElementById("emailError").textContent = "Email invalide.";
                isValid = false;
            }
            if (password.length < 1) {
                document.getElementById("passwordError").textContent = "Le mot de passe ne peut pas être vide.";
                isValid = false;
            }
            if (!captcha.match(/^\d+$/) || parseInt(captcha) !== captchaAnswer) {
                document.getElementById("captchaError").textContent = "Réponse CAPTCHA incorrecte.";
                isValid = false;
            }

            // Submit form if valid
            if (isValid) {
                this.submit();
            }
        });

        // Login tabs
        const tabs = document.querySelectorAll(".login-tab");
        const contents = document.querySelectorAll(".login-content");
        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                tabs.forEach(t => t.classList.remove("active"));
                contents.forEach(c => c.classList.remove("active"));
                tab.classList.add("active");
                document.getElementById(`${tab.dataset.tab}-login`).classList.add("active");
            });
        });

        // Face ID
        const faceButton = document.getElementById("faceid-button");
        const faceModal = document.getElementById("faceModal");
        const faceScanner = document.getElementById("face-scanner");
        const faceCanvas = document.getElementById("face-canvas");
        const scanFaceButton = document.getElementById("scanFace");
        const closeFaceModalButton = document.getElementById("closeFaceModal");
        const faceError = document.getElementById("faceError");
        let faceStream = null;

        faceButton.addEventListener("click", () => {
            faceModal.style.display = "flex";
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "user", width: 320, height: 240 } })
                .then((mediaStream) => {
                    faceStream = mediaStream;
                    faceScanner.srcObject = faceStream;
                    faceScanner.play();
                    console.log("Caméra Face ID démarrée");
                })
                .catch((err) => {
                    faceError.textContent = "Erreur d'accès à la caméra : " + err.message;
                    console.error("Erreur caméra Face ID :", err);
                });
        });

        scanFaceButton.addEventListener("click", async () => {
            const ctx = faceCanvas.getContext("2d");
            faceCanvas.width = faceScanner.videoWidth;
            faceCanvas.height = faceScanner.videoHeight;
            ctx.drawImage(faceScanner, 0, 0, faceCanvas.width, faceCanvas.height);
            const imageData = faceCanvas.toDataURL("image/jpeg");

            // Send image to server for face recognition
            try {
                const response = await fetch('face_recognition.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: imageData })
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = result.role === 'admin' ? 'dashboard.php' : 'info2.php';
                } else {
                    faceError.textContent = result.message || "Échec de la reconnaissance faciale.";
                }
            } catch (err) {
                faceError.textContent = "Erreur serveur : " + err.message;
                console.error("Erreur Face ID :", err);
            }
        });

        closeFaceModalButton.addEventListener("click", () => {
            stopFaceScanner();
            faceModal.style.display = "none";
            faceError.textContent = "";
        });

        faceModal.addEventListener("click", (e) => {
            if (e.target === faceModal) {
                stopFaceScanner();
                faceModal.style.display = "none";
                faceError.textContent = "";
            }
        });

        function stopFaceScanner() {
            if (faceStream) {
                faceStream.getTracks().forEach(track => track.stop());
                faceStream = null;
            }
        }
    </script>
</body>
</html>