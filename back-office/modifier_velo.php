<?php
session_start();
require_once __DIR__ . '/models/db.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

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

// Vérifier si les données de l'utilisateur sont disponibles
if (!$user) {
    echo "Erreur : utilisateur introuvable.";
    exit();
}

// Initialiser les messages d'erreur ou de succès
$message = '';

// Vérifier si l'ID de la réservation est passé dans l'URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $message = "ID de la réservation manquant ou invalide.";
} else {
    $id_reservation = (int)$_GET['id'];
    
    // Requête pour récupérer les informations de la réservation
    $query = "SELECT r.*, v.nom_velo, u.prenom, u.nom 
              FROM reservation r 
              JOIN velos v ON r.id_velo = v.id_velo 
              JOIN users u ON r.id_client = u.id 
              WHERE r.id_reservation = :id_reservation";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $message = "Réservation introuvable.";
    }
}

// Récupérer la liste des vélos et des clients pour les dropdowns
try {
    $stmt_velos = $pdo->query("SELECT id_velo, nom_velo FROM velos ORDER BY nom_velo");
    $velos = $stmt_velos->fetchAll(PDO::FETCH_ASSOC);

    $stmt_users = $pdo->query("SELECT id, prenom, nom FROM users ORDER BY nom, prenom");
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des vélos ou utilisateurs : " . $e->getMessage(), 3, __DIR__ . '/errors.log');
    $message = "Erreur lors du chargement des données.";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reservation = isset($_POST['id_reservation']) ? (int)$_POST['id_reservation'] : 0;
    $id_velo = isset($_POST['id_velo']) ? (int)$_POST['id_velo'] : 0;
    $id_client = isset($_POST['id_client']) ? (int)$_POST['id_client'] : 0;
    $date_debut = isset($_POST['date_debut']) ? trim($_POST['date_debut']) : '';
    $date_fin = isset($_POST['date_fin']) ? trim($_POST['date_fin']) : '';
    $gouvernorat = isset($_POST['gouvernorat']) ? trim($_POST['gouvernorat']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $statut = isset($_POST['statut']) ? trim($_POST['statut']) : '';

    // Validation côté serveur
    $errors = [];
    if ($id_velo <= 0) {
        $errors[] = "Veuillez sélectionner un vélo.";
    }
    if ($id_client <= 0) {
        $errors[] = "Veuillez sélectionner un client.";
    }
    if (empty($date_debut)) {
        $errors[] = "La date de début est requise.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_debut)) {
        $errors[] = "Format de la date de début invalide (YYYY-MM-DD).";
    }
    if (empty($date_fin)) {
        $errors[] = "La date de fin est requise.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_fin)) {
        $errors[] = "Format de la date de fin invalide (YYYY-MM-DD).";
    } elseif (strtotime($date_fin) < strtotime($date_debut)) {
        $errors[] = "La date de fin doit être postérieure à la date de début.";
    }
    if (empty($gouvernorat)) {
        $errors[] = "Le gouvernorat est requis.";
    }
    if (empty($telephone)) {
        $errors[] = "Le numéro de téléphone est requis.";
    } elseif (!preg_match("/^\+?\d{8,15}$/", $telephone)) {
        $errors[] = "Le numéro de téléphone est invalide.";
    }
    if (empty($statut) || !in_array($statut, ['en_attente', 'acceptee', 'refusee'])) {
        $errors[] = "Le statut est invalide.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            // Requête pour mettre à jour les informations de la réservation
            $query = "UPDATE reservation 
                      SET id_velo = :id_velo,
                          id_client = :id_client,
                          date_debut = :date_debut,
                          date_fin = :date_fin,
                          gouvernorat = :gouvernorat,
                          telephone = :telephone,
                          statut = :statut
                      WHERE id_reservation = :id_reservation";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
            $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
            $stmt->bindParam(':id_client', $id_client, PDO::PARAM_INT);
            $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
            $stmt->bindParam(':date_fin', $date_fin, PDO::PARAM_STR);
            $stmt->bindParam(':gouvernorat', $gouvernorat, PDO::PARAM_STR);
            $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $pdo->commit();
                $message = "Réservation modifiée avec succès. Redirection...";
                echo '<meta http-equiv="refresh" content="2;url=reservations.php">';
            } else {
                $pdo->rollBack();
                $message = "Erreur lors de la modification.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur de base de données dans modifier_reservation.php : " . $e->getMessage(), 3, __DIR__ . '/errors.log');
            $message = "Une erreur est survenue. Veuillez réessayer.";
        }
    } else {
        $message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Réservation - Green.tn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #008080, #1E3A8A, #4FD1C5);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            color: #333;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: -250px;
            background-color: #1E3A8A;
            transition: left 0.3s ease;
            z-index: 1000;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar-brand img {
            width: 60%;
            height: auto;
        }

        .sidebar-content {
            padding: 20px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav-item {
            margin-bottom: 10px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .sidebar-nav-link:hover {
            background-color: #006666;
        }

        .sidebar-nav-link.active {
            background-color: #008080;
        }

        .sidebar-nav-icon {
            margin-right: 10px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        .sidebar-footer .btn {
            font-size: 14px;
            width: 100%;
            margin-bottom: 10px;
            background-color: #008080;
            border-radius: 5px;
            padding: 8px;
        }

        .sidebar-footer .btn:hover {
            background-color: #006666;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggler {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: #008080;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sidebar-toggler:hover {
            background-color: #006666;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            padding: 40px;
            min-height: 100vh;
            background: #F8FAFC;
            border-radius: 12px;
            transition: margin-left 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .main-content-expanded {
            margin-left: 250px;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-header {
            width: 110px;
            height: auto;
        }

        .main-content h1 {
            color: #008080;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }

        /* Form Styles */
        .section-content {
            background-color: #F8FAFC;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid #008080;
            animation: slideIn 0.5s ease;
            margin-bottom: 40px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: 600;
            color: #333;
            position: relative;
        }

        label .tooltip {
            position: absolute;
            top: 0;
            right: -20px;
            font-size: 12px;
            color: #008080;
            cursor: pointer;
        }

        input, select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #008080;
            font-size: 14px;
            width: 100%;
        }

        button {
            padding: 10px;
            background-color: #008080;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #006666;
        }

        .back-btn {
            background-color: #1E3A8A;
        }

        .back-btn:hover {
            background-color: #162D6D;
        }

        /* Alerts */
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            animation: slideInAlert 0.3s ease;
            text-align: center;
        }

        .alert.success {
            background-color: #E6F3F3;
            color: #008080;
            border: 1px solid #008080;
        }

        .alert.error {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #991B1B;
        }

        @keyframes slideInAlert {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Error Messages */
        .error-message {
            color: #991B1B;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px 0;
            background-color: #F8FAFC;
            border-top: 1px solid #008080;
            margin-top: 40px;
        }

        .footer-text {
            color: #008080;
            font-size: 14px;
        }

        .footer-text a {
            color: #008080;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        /* Dark Mode */
        body.dark-mode {
            background: linear-gradient(135deg, #004d4d, #1c2526, #2a6f6b);
            color: #e0e0e0;
        }

        body.dark model .sidebar {
            background-color: #1c2526;
        }

        body.dark-mode .sidebar-nav-link {
            color: #e0e0e0;
        }

        body.dark-mode .main-content,
        body.dark-mode .section-content {
            background-color: #2a2a2a;
            border: 1px solid #008080;
        }

        body.dark-mode .main-content h1,
        body.dark-mode label,
        body.dark-mode .footer-text,
        body.dark-mode .footer-text a {
            color: #008080;
        }

        body.dark-mode input,
        body.dark-mode select {
            background-color: #444;
            color: #e0e0e0;
            border-color: #008080;
        }

        body.dark-mode button {
            background-color: #008080;
        }

        body.dark-mode button:hover {
            background-color: #006666;
        }

        body.dark-mode .back-btn {
            background-color: #1E3A8A;
        }

        body.dark-mode .back-btn:hover {
            background-color: #162D6D;
        }

        body.dark-mode .alert.success {
            background-color: #006666;
            border-color: #008080;
            color: #e0e0e0;
        }

        body.dark-mode .alert.error {
            background-color: #991B1B;
            border-color: #FEE2E2;
            color: #e0e0e0;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .section-content {
                padding: 20px;
            }

            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="dashboard.php?section=stats">
                <img src="logo.jpg" alt="Green.tn">
            </a>
        </div>
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="dashboard.php?section=stats" data-translate="home">
                        <span class="sidebar-nav-icon"><i class="bi bi-house-door"></i></span>
                        <span class="sidebar-nav-text">Accueil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="gestion_utilisateurs.php" data-translate="profile_management">
                        <span class="sidebar-nav-icon"><i class="bi bi-person"></i></span>
                        <span class="sidebar-nav-text">Gestion de votre profil</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link active" href="reservations.php" data-translate="reservations">
                        <span class="sidebar-nav-icon"><i class="bi bi-calendar"></i></span>
                        <span class="sidebar-nav-text">Réservations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="reclamation.php" data-translate="complaints">
                        <span class="sidebar-nav-icon"><i class="bi bi-envelope"></i></span>
                        <span class="sidebar-nav-text">Réclamations</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="velos.php" data-translate="bikes_batteries">
                        <span class="sidebar-nav-icon"><i class="bi bi-bicycle"></i></span>
                        <span class="sidebar-nav-text">Vélos & Batteries</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="mb-2">
                <span class="text-white">Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline-light" data-translate="logout">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
            <a href="#" id="darkModeToggle" class="btn btn-outline-light mt-2" data-translate="dark_mode">
                <i class="bi bi-moon"></i> Mode Sombre
            </a>
            <a href="#" id="toggle-language" class="btn btn-outline-light mt-2" data-translate="language">
                <i class="fas fa-globe"></i> Français
            </a>
        </div>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggler" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <div class="header-logo">
            <img src="logo.jpg" alt="Logo Green.tn" class="logo-header">
        </div>
        <h1 data-translate="edit_reservation">Modifier une Réservation</h1>

        <div class="section-content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($reservation)): ?>
                <form method="POST" id="reservation-form">
                    <input type="hidden" name="id_reservation" value="<?php echo htmlspecialchars($reservation['id_reservation']); ?>">
                    
                    <label for="id_velo" data-translate="bike">Vélo <span class="tooltip" title="Sélectionnez le vélo réservé">?</span></label>
                    <select id="id_velo" name="id_velo" required>
                        <option value="" disabled>Sélectionner un vélo</option>
                        <?php foreach ($velos as $velo): ?>
                            <option value="<?php echo $velo['id_velo']; ?>" <?php echo $reservation['id_velo'] == $velo['id_velo'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($velo['nom_velo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="id_velo-error"></span>

                    <label for="id_client" data-translate="client">Client <span class="tooltip" title="Sélectionnez le client">?</span></label>
                    <select id="id_client" name="id_client" required>
                        <option value="" disabled>Sélectionner un client</option>
                        <?php foreach ($users as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $reservation['id_client'] == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="id_client-error"></span>

                    <label for="date_debut" data-translate="start_date">Date de Début <span class="tooltip" title="Entrez la date de début (YYYY-MM-DD)">?</span></label>
                    <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($reservation['date_debut']); ?>" required>
                    <span class="error-message" id="date_debut-error"></span>

                    <label for="date_fin" data-translate="end_date">Date de Fin <span class="tooltip" title="Entrez la date de fin (YYYY-MM-DD)">?</span></label>
                    <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($reservation['date_fin']); ?>" required>
                    <span class="error-message" id="date_fin-error"></span>

                    <label for="gouvernorat" data-translate="governorate">Gouvernorat <span class="tooltip" title="Entrez le gouvernorat">?</span></label>
                    <input type="text" id="gouvernorat" name="gouvernorat" value="<?php echo htmlspecialchars($reservation['gouvernorat']); ?>" placeholder="Ex. Tunis" required>
                    <span class="error-message" id="gouvernorat-error"></span>

                    <label for="telephone" data-translate="phone">Téléphone <span class="tooltip" title="Entrez le numéro de téléphone (+216xxxxxxxx)">?</span></label>
                    <input type="text" id="telephone" name="telephone" value="<?php echo htmlspecialchars($reservation['telephone']); ?>" placeholder="Ex. +21612345678" required>
                    <span class="error-message" id="telephone-error"></span>

                    <label for="statut" data-translate="status">Statut <span class="tooltip" title="Sélectionnez le statut de la réservation">?</span></label>
                    <select id="statut" name="statut" required>
                        <option value="en_attente" <?php echo $reservation['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="acceptee" <?php echo $reservation['statut'] === 'acceptee' ? 'selected' : ''; ?>>Acceptée</option>
                        <option value="refusee" <?php echo $reservation['statut'] === 'refusee' ? 'selected' : ''; ?>>Refusée</option>
                    </select>
                    <span class="error-message" id="statut-error"></span>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" data-translate="update">Mettre à Jour</button>
                        <a href="reservations.php" class="btn back-btn" data-translate="back">Retour</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert error" data-translate="reservation_not_found">Réservation introuvable ou ID invalide.</div>
                <a href="reservations.php" class="btn back-btn" data-translate="back">Retour</a>
            <?php endif; ?>
        </div>

        <footer class="footer">
            <div class="container">
                <p class="footer-text">© 2025 <a href="https://green.tn">Green.tn</a>. Tous droits réservés.</p>
            </div>
        </footer>
    </div>

    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const sidebarToggle = document.getElementById('sidebarToggle');

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                main.classList.toggle('main-content-expanded');
            });

            function handleResize() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('show');
                    main.classList.remove('main-content-expanded');
                } else {
                    sidebar.classList.add('show');
                    main.classList.add('main-content-expanded');
                }
            }
            handleResize();
            window.addEventListener('resize', handleResize);

            // Dark Mode
            const toggleButton = document.getElementById('darkModeToggle');
            const body = document.body;
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
            }
            toggleButton.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : '');
                updateTranslations();
            });

            // Client-side validation
            const form = document.getElementById('reservation-form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    let valid = true;
                    const idVelo = document.getElementById('id_velo');
                    const idClient = document.getElementById('id_client');
                    const dateDebut = document.getElementById('date_debut');
                    const dateFin = document.getElementById('date_fin');
                    const gouvernorat = document.getElementById('gouvernorat');
                    const telephone = document.getElementById('telephone');
                    const statut = document.getElementById('statut');

                    // Reset error messages
                    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

                    // Vélo
                    if (!idVelo.value) {
                        document.getElementById('id_velo-error').textContent = translations[currentLanguage].error_bike_required;
                        valid = false;
                    }

                    // Client
                    if (!idClient.value) {
                        document.getElementById('id_client-error').textContent = translations[currentLanguage].error_client_required;
                        valid = false;
                    }

                    // Date de début
                    if (!dateDebut.value) {
                        document.getElementById('date_debut-error').textContent = translations[currentLanguage].error_start_date_required;
                        valid = false;
                    }

                    // Date de fin
                    if (!dateFin.value) {
                        document.getElementById('date_fin-error').textContent = translations[currentLanguage].error_end_date_required;
                        valid = false;
                    } else if (dateDebut.value && new Date(dateFin.value) < new Date(dateDebut.value)) {
                        document.getElementById('date_fin-error').textContent = translations[currentLanguage].error_end_date_invalid;
                        valid = false;
                    }

                    // Gouvernorat
                    if (!gouvernorat.value.trim()) {
                        document.getElementById('gouvernorat-error').textContent = translations[currentLanguage].error_governorate_required;
                        valid = false;
                    }

                    // Téléphone
                    if (!telephone.value.trim()) {
                        document.getElementById('telephone-error').textContent = translations[currentLanguage].error_phone_required;
                        valid = false;
                    } else if (!/^\+?\d{8,15}$/.test(telephone.value)) {
                        document.getElementById('telephone-error').textContent = translations[currentLanguage].error_phone_invalid;
                        valid = false;
                    }

                    // Statut
                    if (!statut.value) {
                        document.getElementById('statut-error').textContent = translations[currentLanguage].error_status_required;
                        valid = false;
                    }

                    if (!valid) {
                        event.preventDefault();
                    }
                });
            }

            // Translation
            const translations = {
                fr: {
                    home: "Accueil",
                    profile_management: "Gestion de votre profil",
                    reservations: "Réservations",
                    complaints: "Réclamations",
                    bikes_batteries: "Vélos & Batteries",
                    logout: "Déconnexion",
                    dark_mode: "Mode Sombre",
                    language: "Français",
                    edit_reservation: "Modifier une Réservation",
                    bike: "Vélo",
                    client: "Client",
                    start_date: "Date de Début",
                    end_date: "Date de Fin",
                    governorate: "Gouvernorat",
                    phone: "Téléphone",
                    status: "Statut",
                    update: "Mettre à Jour",
                    back: "Retour",
                    reservation_not_found: "Réservation introuvable ou ID invalide.",
                    error_bike_required: "Veuillez sélectionner un vélo.",
                    error_client_required: "Veuillez sélectionner un client.",
                    error_start_date_required: "La date de début est requise.",
                    error_end_date_required: "La date de fin est requise.",
                    error_end_date_invalid: "La date de fin doit être postérieure à la date de début.",
                    error_governorate_required: "Le gouvernorat est requis.",
                    error_phone_required: "Le numéro de téléphone est requis.",
                    error_phone_invalid: "Le numéro de téléphone est invalide.",
                    error_status_required: "Le statut est requis."
                },
                en: {
                    home: "Home",
                    profile_management: "Profile Management",
                    reservations: "Reservations",
                    complaints: "Complaints",
                    bikes_batteries: "Bikes & Batteries",
                    logout: "Logout",
                    dark_mode: "Dark Mode",
                    language: "English",
                    edit_reservation: "Edit a Reservation",
                    bike: "Bike",
                    client: "Client",
                    start_date: "Start Date",
                    end_date: "End Date",
                    governorate: "Governorate",
                    phone: "Phone",
                    status: "Status",
                    update: "Update",
                    back: "Back",
                    reservation_not_found: "Reservation not found or invalid ID.",
                    error_bike_required: "Please select a bike.",
                    error_client_required: "Please select a client.",
                    error_start_date_required: "The start date is required.",
                    error_end_date_required: "The end date is required.",
                    error_end_date_invalid: "The end date must be after the start date.",
                    error_governorate_required: "The governorate is required.",
                    error_phone_required: "The phone number is required.",
                    error_phone_invalid: "The phone number is invalid.",
                    error_status_required: "The status is required."
                }
            };

            let currentLanguage = localStorage.getItem('language') || 'fr';

            function updateTranslations() {
                document.querySelectorAll('[data-translate]').forEach(element => {
                    const key = element.getAttribute('data-translate');
                    if (translations[currentLanguage][key]) {
                        element.textContent = translations[currentLanguage][key];
                    }
                });
                const toggleLanguageBtn = document.getElementById('toggle-language');
                if (toggleLanguageBtn) {
                    toggleLanguageBtn.innerHTML = `<i class="fas fa-globe"></i> ${translations[currentLanguage].language}`;
                }
            }

            const toggleLanguageBtn = document.getElementById('toggle-language');
            if (toggleLanguageBtn) {
                toggleLanguageBtn.addEventListener('click', () => {
                    currentLanguage = currentLanguage === 'fr' ? 'en' : 'fr';
                    localStorage.setItem('language', currentLanguage);
                    updateTranslations();
                });
            }

            updateTranslations();
        });
    </script>
</body>
</html>