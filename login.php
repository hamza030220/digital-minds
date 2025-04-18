
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
            margin-bottom: 15px;
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
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Connexion</h2>
   
           
     
        <form method="POST"action="./controllers/AuthController.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
