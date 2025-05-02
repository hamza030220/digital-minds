<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Green.tn</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 8px rgba(0,0,0,0.1);
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            margin-bottom: 5px;
            padding: 8px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: green;
            border: none;
            color: white;
            font-weight: bold;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .error-message {
            color: red;
            font-size: 0.85em;
            margin-bottom: 10px;
            display: none;
        }
        .input-error {
            border-color: red;
        }
        .input-valid {
            border-color: green;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Connexion</h2>
        <form method="POST" action="./controllers/AuthController.php" id="loginForm" novalidate>
            <input type="email" name="email" id="email" placeholder="Email">
            <div class="error-message" id="email-error"></div>
            <input type="password" name="mot_de_passe" id="mot_de_passe" placeholder="Mot de passe">
            <div class="error-message" id="mot_de_passe-error"></div>
            <button type="submit">Se connecter</button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();
            let isValid = true;
            const errors = {};

            const email = document.getElementById('email').value.trim();
            if (!email) {
                errors.email = 'L\'email est requis.';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.email = 'Veuillez entrer un email valide.';
                isValid = false;
            }

            const motDePasse = document.getElementById('mot_de_passe').value.trim();
            if (!motDePasse) {
                errors.mot_de_passe = 'Le mot de passe est requis.';
                isValid = false;
            }
            ['email', 'mot_de_passe'].forEach(field => {
                const errorElement = document.getElementById(`${field}-error`);
                const inputElement = document.getElementById(field);
                if (errors[field]) {
                    errorElement.textContent = errors[field];
                    errorElement.style.display = 'block';
                    inputElement.classList.add('input-error');
                    inputElement.classList.remove('input-valid');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    inputElement.classList.remove('input-error');
                    inputElement.classList.add('input-valid');
                }
            });

            if (isValid) {
                this.submit();
            } else {
                const firstInvalidField = ['email', 'mot_de_passe'].find(field => errors[field]);
                if (firstInvalidField) {
                    document.getElementById(firstInvalidField).focus();
                    document.getElementById(firstInvalidField).scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        ['email', 'mot_de_passe'].forEach(field => {
            document.getElementById(field).addEventListener('input', function() {
                const errorElement = document.getElementById(`${field}-error`);
                let error = '';

                if (field === 'email') {
                    const value = this.value.trim();
                    if (!value) error = 'L\'email est requis.';
                    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) error = 'Veuillez entrer un email valide.';
                } else if (field === 'mot_de_passe') {
                    const value = this.value.trim();
                    if (!value) error = 'Le mot de passe est requis.';
                   
                }

                if (error) {
                    errorElement.textContent = error;
                    errorElement.style.display = 'block';
                    this.classList.add('input-error');
                    this.classList.remove('input-valid');
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                    this.classList.remove('input-error');
                    this.classList.add('input-valid');
                }
            });
        });
    </script>
</body>
</html>