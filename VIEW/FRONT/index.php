<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green.tn - Location de Vélos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #60BA97;
            color: #333;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background-color: #F9F5E8;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            border-bottom: none;
        }

        .logo-nav-container {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 200px;
            height: auto;
            margin-right: 20px;
        }

        .nav-left ul {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-left ul li a {
            text-decoration: none;
            color: #2e7d32;
            font-weight: 500;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .nav-right ul {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        nav ul li a.login,
        nav ul li a.signin {
            color: #fff;
            background-color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .hero {
            display: flex;
            padding: 0;
            height: 100vh;
            background-color: #60BA97;
            border-bottom: 5px solid #2e7d32;
        }

        .hero-image img {
            width: 100%;
            max-width: 800px;
            height: auto;
            object-fit: cover;
            border-radius: 10px;
            background-color: #60BA97;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            position: relative;
            top: 150px;
            left: 300px;
            z-index: 2;
        }

        .hero-box {
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 270px;
            right: 350px;
            width: 700px;
            height: 550px;
            text-align: left;
            animation: slide-from-behind 2s ease-in-out;
            z-index: 1;
        }

        .hero-box h1 {
            font-size: 50px;
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .hero-box p {
            font-size: 16px;
            color: #333;
        }

        .bike-section {
            padding: 50px;
            background-color: #60BA97;
            border-bottom: none;
        }

        .bike-section h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2e7d32;
        }

        .bike-section h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #2e7d32;
        }

        #velo-de-montagne h3,
        #velo-de-ville h3,
        #velo-de-course h3 {
            font-family: "Bauhaus 93", Arial, sans-serif;
            color: #fff;
            background-color: #2e7d32;
            padding: 15px 0;
            width: 100%;
            margin: 0 auto;
            text-align: center;
        }

        .bike-grid {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .bike-card {
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 300px;
            height: 500px;
        }

        .bike-card img {
            width: 100%;
            height: 350px;
            background-color: #60BA97;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 15px;
        }

        .buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
        }

        .detail {
            background-color: #1E90FF;
        }

        .reserve {
            background-color: #32CD32;
        }

        #a-nos-velos h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2e7d32;
        }

        .about {
            position: relative;
            margin-top: 0;
        }

        .about .hero-box {
            top: 270px;
            right: 350px;
            width: 700px;
            height: 550px;
            z-index: 1;
        }

        .about .hero-image img {
            top: 150px;
            left: 300px;
            z-index: 2;
        }

        .pricing {
            padding: 50px;
            text-align: center;
            background-color: #60BA97;
            border-bottom: none;
        }

        .pricing h2 {
            margin-bottom: 30px;
            color: #2e7d32;
            font-size: 24px;
            font-weight: bold;
        }

        #pricing h2 {
            font-family: "Bauhaus 93", Arial, sans-serif;
            color: #2e7d32;
            background-color: #F9F5E8;
            padding: 10px 20px;
            display: inline-block;
            border-radius: 5px;
        }

        .pricing-grid {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .pricing-card {
            width: 200px;
            text-align: center;
            border: none;
            box-shadow: none;
        }

        .pricing-card h3 {
            background-color: #fff;
            color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 15px;
            margin: 0;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .pricing-card .price {
            background-color: #4CAF50;
            color: #fff;
            font-family: "Bauhaus 93", Arial, sans-serif;
            font-size: 24px;
            font-weight: bold;
            padding: 15px;
            margin: 0;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .pricing-card .description {
            font-family: "Bauhaus 93", Arial, sans-serif;
            color: #555;
            font-size: 14px;
            padding: 10px 0;
        }

        .contact {
            padding: 50px;
            text-align: center;
            background-color: #60BA97;
            border-bottom: none;
        }

        .contact h2 {
            margin-bottom: 30px;
            color: #2e7d32;
        }

        #contact h2 {
            font-family: "Bauhaus 93", Arial, sans-serif;
            color: #2e7d32;
            background-color: #F9F5E8;
            padding: 10px 20px;
            display: inline-block;
            border-radius: 5px;
        }

        .contact form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 500px;
            margin: 0 auto;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .contact form label {
            color: #2e7d32;
            font-weight: bold;
            font-size: 16px;
            text-align: left;
        }

        .contact form input,
        .contact form textarea {
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
            width: 100%;
            box-sizing: border-box;
        }

        .contact form input::placeholder,
        .contact form textarea::placeholder {
            color: transparent;
        }

        .contact form textarea {
            height: 150px;
            resize: none;
        }

        .contact form button {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-end;
            font-size: 16px;
            font-weight: bold;
        }

        .name-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .name-field {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        footer {
            background-color: #F9F5E8;
            padding: 20px 0;
            border-top: none;
            font-family: "Berlin Sans FB", Arial, sans-serif;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-logo img {
            width: 200px;
            height: auto;
            margin-bottom: 15px;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .social-icons a img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: opacity 0.3s ease;
        }

        .social-icons a img:hover {
            opacity: 0.8;
        }

        .footer-section {
            margin-left: 40px;
        }

        .footer-section h3 {
            font-size: 18px;
            color: #333;
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section ul li a {
            text-decoration: none;
            color: #555;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #4CAF50;
        }

        .footer-section p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .footer-section p img {
            margin-right: 8px;
            width: 16px;
            height: 16px;
        }

        .footer-section p a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section p a:hover {
            color: #4CAF50;
        }

        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer-left {
                margin-bottom: 20px;
            }

            .footer-section {
                margin-left: 0;
                margin-bottom: 20px;
            }

            header {
                flex-direction: column;
                gap: 10px;
                padding: 15px 20px;
            }

            .logo-nav-container {
                flex-direction: column;
                align-items: center;
            }

            .logo img {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .nav-left ul,
            .nav-right ul {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            .hero-image img {
                position: static;
                width: 100%;
                max-width: 500px;
                margin: 20px auto;
            }

            .hero-box {
                position: static;
                width: 100%;
                height: auto;
                margin: 20px auto;
                animation: none;
            }

            .bike-grid {
                flex-direction: column;
                align-items: center;
            }

            .pricing-grid {
                flex-direction: column;
                align-items: center;
            }

            .pricing-card {
                width: 100%;
                max-width: 300px;
            }

            .about .hero-box,
            .about .hero-image img {
                position: static;
                width: 100%;
                max-width: 500px;
                margin: 20px auto;
            }
        }

        @keyframes slide-from-behind {
            0% {
                transform: translateX(-300px);
                opacity: 0;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo-nav-container">
            <div class="logo">
                <img src="image/ve.png" alt="Green.tn Logo">
            </div>
            <nav class="nav-left">
                <ul>
                    <li><a href="#">Accueil</a></li>
                    <li><a href="#a-nos-velos">Nos vélos</a></li>
                    <li><a href="#a-propos-de-nous">À propos de nous</a></li>
                    <li><a href="#pricing">Tarifs</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li><a href="login.php" class="login">Connexion</a></li>
                <li><a href="sign_up.php" class="signin">Inscription</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-box">
                <h1>Bienvenue chez Green.tn</h1>
                <p>Découvrez nos vélos et nos services</p>
            </div>
            <div class="hero-image">
                <img src="image/hero.png" alt="Hero Bike Image">
            </div>
        </section>

        <!-- À nos vélos Section -->
        <section id="a-nos-velos">
            <h2>Nos vélos</h2>
            <div>
                <!-- Vélo de ville Section -->
                <section class="bike-section" id="velo-de-ville">
                    <h3>Vélo de ville</h3>
                    <div class="bike-grid">
                        <div class="bike-card">
                            <img src="image/velo-de-ville.png" alt="Vélo de ville 1">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                        <div class="bike-card">
                            <img src="image/velo-de-ville.png" alt="Vélo de ville 2">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                        <div class="bike-card">
                            <img src="image/velo-de-ville.png" alt="Vélo de ville 3">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Vélo de course Section -->
                <section class="bike-section" id="velo-de-course">
                    <h3>Vélo de course</h3>
                    <div class="bike-grid">
                        <div class="bike-card">
                            <img src="image/bike.png" alt="Vélo de course 1">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                        <div class="bike-card">
                            <img src="image/bike.png" alt="Vélo de course 2">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                        <div class="bike-card">
                            <img src="image/bike.png" alt="Vélo de course 3">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Vélo de montagne Section -->
                <section class="bike-section" id="velo-de-montagne">
                    <h3>Vélo de montagne</h3>
                    <div class="bike-grid">
                        <div class="bike-card">
                            <img src="image/bike.png" alt="Vélo de montagne 1">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                        <div class="bike-card">
                            <img src="image/bike.png" alt="Vélo de montagne 2">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                        <div class="bike-card">
                            <img src="image/bike.png" alt="Vélo de montagne 3">
                            <div class="buttons">
                                <button class="detail">Détail</button>
                                <button class="reserve">Réserver</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <!-- À propos de nous Section -->
        <section class="hero about" id="a-propos-de-nous">
            <div class="hero-box">
                <h1>À propos de nous</h1>
                <p>Découvrez qui nous sommes et notre mission</p>
            </div>
            <div class="hero-image">
                <img src="image/hero.png" alt="About Us Bike Image">
            </div>
        </section>

        <!-- Tarifs Section -->
        <section class="pricing" id="pricing">
            <h2>Tarifs</h2>
            <div class="pricing-grid">
                <div class="pricing-card">
                    <h3>Jour</h3>
                    <p class="price">30 DT</p>
                    <p class="description">Location à la journée</p>
                </div>
                <div class="pricing-card">
                    <h3>Semaine</h3>
                    <p class="price">160 DT</p>
                    <p class="description">Location à la semaine</p>
                </div>
                <div class="pricing-card">
                    <h3>Mois</h3>
                    <p class="price">630 DT</p>
                    <p class="description">Location au mois</p>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact" id="contact">
            <h2>Contact</h2>
            <form>
                <div class="name-row">
                    <div class="name-field">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" placeholder="Nom" required>
                    </div>
                    <div class="name-field">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" placeholder="Prénom" required>
                    </div>
                </div>
                <label for="mail">Mail</label>
                <input type="email" id="mail" placeholder="Mail" required>
                <label for="telephone">Numéro téléphone</label>
                <input type="tel" id="telephone" placeholder="Numéro téléphone" required>
                <label for="message">Message</label>
                <textarea id="message" placeholder="Message" required></textarea>
                <button type="submit">Envoyer</button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo">
                    <img src="image/ho.png" alt="Green.tn Logo">
                </div>
                <div class="social-icons">
                    <a href="https://instagram.com"><img src="image/insta.png" alt="Instagram"></a>
                    <a href="https://facebook.com"><img src="image/fb.png" alt="Facebook"></a>
                    <a href="https://twitter.com"><img src="image/x.png" alt="Twitter"></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="#">Accueil</a></li>
                    <li><a href="#a-nos-velos">Nos vélos</a></li>
                    <li><a href="#a-propos-de-nous">À propos de nous</a></li>
                    <li><a href="#pricing">Tarifs</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p>
                    <img src="image/location.png" alt="Location Icon">
                    Eprit technopole
                </p>
                <p>
                    <img src="image/telephone.png" alt="Phone Icon">
                    +216245678
                </p>
                <p>
                    <img src="image/mail.png" alt="Email Icon">
                    <a href="mailto:Green@green.com">Green@green.com</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>