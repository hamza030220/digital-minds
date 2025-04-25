<?php
// liste_reclamations.php
// ****** THIS FILE NOW ACTS AS BOTH CONTROLLER LOGIC & VIEW ******

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include translation helper
require_once __DIR__ . '/translate.php';

// Include the Model
require_once __DIR__ . '/models/Reclamation.php';

// Authentication Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php?error=' . urlencode(t('error_session_required')));
    exit;
}

// Instantiate the Model
$reclamationModel = null;
$reclamations = [];
$errorMessage = null;
$pageTitle = t('my_reclamations') . ' - Green.tn';

try {
    $reclamationModel = new Reclamation();
    $user_id = $_SESSION['user_id'];
    $reclamations = $reclamationModel->getByUserId($user_id);
} catch (PDOException $e) {
    error_log("View/Controller Error: Database error fetching reclamations for user {$user_id} - " . $e->getMessage());
    $errorMessage = t('error_database_reclamations');
} catch (Exception $e) {
    error_log("View/Controller Error: Unexpected error - " . $e->getMessage());
    $errorMessage = t('error_unexpected_reclamations');
}

// --- Step 2: The View / Presentation Logic ---
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="image/ve.png" type="image/png">
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

        .nav-right ul li a.login,
        .nav-right ul li a.signin,
        .nav-right ul li button.lang-toggle {
            color: #fff;
            background-color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            cursor: pointer;
        }

        .nav-right ul li button.lang-toggle {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        .nav-right ul li button.lang-toggle:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }

        main {
            padding: 50px;
            text-align: center;
            background-color: #60BA97;
            margin-top: 100px;
        }

        main h2 {
            margin-bottom: 30px;
            color: #2e7d32;
            font-size: 24px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
            background-color: #F9F5E8;
            padding: 10px 20px;
            display: inline-block;
            border-radius: 5px;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .error-message,
        .info-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            border: 1px solid transparent;
            text-align: center;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .info-message {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .info-message a {
            color: #2e7d32;
            text-decoration: none;
        }

        .info-message a:hover {
            text-decoration: underline;
        }

        .search-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar label {
            color: #2e7d32;
            font-weight: bold;
            font-size: 16px;
            line-height: 40px;
        }

        .search-bar input,
        .search-bar select {
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            background-color: #fff;
            width: 200px;
            box-sizing: border-box;
        }

        .search-bar input::placeholder,
        .search-bar select::placeholder {
            color: transparent;
        }

        .search-bar button {
            padding: 10px 20px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .search-bar button.clear-btn {
            background-color: #C62828;
        }

        .search-bar button.clear-btn:hover {
            background-color: #b71c1c;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: #F9F5E8;
            border: 1px solid #4CAF50;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #4CAF50;
        }

        th {
            background: linear-gradient(to bottom, #2e7d32, #1b5e20);
            color: #fff;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        tr:nth-child(odd) {
            background-color: #F9F5E8;
        }

        tr:nth-child(even) {
            background-color: #E8F5E8;
        }

        tr:hover {
            background-color: #d4edda;
        }

        .title-link {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
        }

        .title-link:hover {
            text-decoration: underline;
        }

        .btn {
            padding: 8px 0;
            width: 100px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            background-color: #2e7d32;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
            text-align: center;
            display: inline-block;
        }

        .btn:hover {
            background-color: #1b5e20;
        }

        .btn-danger {
            padding: 8px 0;
            width: 100px;
            background-color: #C62828;
            text-align: center;
        }

        .btn-danger:hover {
            background-color: #b71c1c;
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

            main {
                padding: 20px;
                margin-top: 150px;
            }

            .content-container {
                padding: 15px;
            }

            .search-bar {
                flex-direction: column;
                align-items: center;
            }

            .search-bar input,
            .search-bar select {
                width: 100%;
            }

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
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-nav-container">
            <div class="logo">
                <img src="image/ve.png" alt="Green.tn Logo">
            </div>
            <nav class="nav-left">
                <ul>
                    <li><a href="index.php"><?php echo t('home'); ?></a></li>
                    <li><a href="ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="liste_reclamations.php"><?php echo t('view_reclamations'); ?></a></li>
                    <li><a href="ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                </ul>
            </nav>
        </div>
        <nav class="nav-right">
            <ul>
                <li>
                    <form action="liste_reclamations.php" method="POST" id="lang-toggle-form">
                        <input type="hidden" name="lang" value="<?php echo $_SESSION['lang'] === 'en' ? 'fr' : 'en'; ?>">
                        <button type="submit" class="lang-toggle"><?php echo $_SESSION['lang'] === 'en' ? t('toggle_language') : t('toggle_language_en'); ?></button>
                    </form>
                </li>
                <li><a href="logout.php" class="login"><?php echo t('logout'); ?></a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
        <div class="content-container">
            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="search-bar">
                <label for="search"><?php echo t('search_by_location'); ?>:</label>
                <input type="text" id="search" placeholder="<?php echo t('enter_location'); ?>">
                <label for="sort-status"><?php echo t('sort_by_status'); ?>:</label>
                <select id="sort-status">
                    <option value=""><?php echo t('all'); ?></option>
                    <option value="ouverte"><?php echo t('open'); ?></option>
                    <option value="En cours"><?php echo t('in_progress'); ?></option>
                    <option value="résolue"><?php echo t('resolved'); ?></option>
                </select>
                <label for="sort-type"><?php echo t('search_by_type'); ?>:</label>
                <select id="sort-type">
                    <option value=""><?php echo t('all'); ?></option>
                    <option value="mécanique"><?php echo t('mechanical'); ?></option>
                    <option value="batterie"><?php echo t('battery'); ?></option>
                    <option value="écran"><?php echo t('screen'); ?></option>
                    <option value="pneu"><?php echo t('tire'); ?></option>
                    
                    <option value="autre"><?php echo t('other'); ?></option>
                </select>
                <button type="button" onclick="filterTable()"><?php echo t('search'); ?></button>
                <button type="button" class="clear-btn" onclick="clearFilters()"><?php echo t('clear'); ?></button>
            </div>

            <div class="table-container">
                <?php if (!$errorMessage && empty($reclamations)): ?>
                    <p class="info-message"><?php echo t('no_reclamations'); ?> <a href="ajouter_reclamation.php"><?php echo t('submit_new_reclamation'); ?></a></p>
                <?php elseif (!empty($reclamations)): ?>
                    <table id="reclamations-table">
                        <thead>
                            <tr>
                                <th><?php echo t('title'); ?></th>
                                <th><?php echo t('description'); ?></th>
                                <th><?php echo t('location'); ?></th>
                                <th><?php echo t('type'); ?></th>
                                <th><?php echo t('status'); ?></th>
                                <th><?php echo t('respond'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reclamations as $reclamation): ?>
                                <tr data-type="<?php echo htmlspecialchars($reclamation['type_probleme']); ?>" data-status="<?php echo htmlspecialchars($reclamation['statut']); ?>">
                                    <td><a href="detail.php?id=<?php echo $reclamation['id']; ?>" class="title-link"><?php echo htmlspecialchars($reclamation['titre']); ?></a></td>
                                    <td><?php echo htmlspecialchars(substr($reclamation['description'], 0, 100)) . (strlen($reclamation['description']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['lieu']); ?></td>
                                    <td><?php echo htmlspecialchars(t($reclamation['type_probleme'])); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars(t($reclamation['statut']))); ?></td>
                                    <td>
                                        <a href="repondre_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn"><?php echo t('respond'); ?></a>
                                    </td>
                                    <td>
                                        <a href="modifier_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn"><?php echo t('edit'); ?></a>
                                        <a href="controllers/supprimer_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="btn btn-danger" onclick="return confirm('<?php echo t('confirm_delete'); ?>');"><?php echo t('delete'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

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
                <h3><?php echo t('navigation'); ?></h3>
                <ul>
                    <li><a href="index.php"><?php echo t('home'); ?></a></li>
                    <li><a href="ajouter_reclamation.php"><?php echo t('new_reclamation'); ?></a></li>
                    <li><a href="#a-propos-de-nous"><?php echo t('about_us'); ?></a></li>
                    <li><a href="#contact"><?php echo t('contact'); ?></a></li>
                    <li><a href="ajouter_avis.php"><?php echo t('submit_review'); ?></a></li>
                    <li><a href="mes_avis.php"><?php echo t('my_reviews'); ?></a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3><?php echo t('contact'); ?></h3>
                <p>
                    <img src="image/location.png" alt="Location Icon">
                    <?php echo t('address'); ?>
                </p>
                <p>
                    <img src="image/telephone.png" alt="Phone Icon">
                    <?php echo t('phone'); ?>
                </p>
                <p>
                    <img src="image/mail.png" alt="Email Icon">
                    <a href="mailto:Green@green.com"><?php echo t('email'); ?></a>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Pass PHP translations to JavaScript
        const translations = {
            confirm_delete: '<?php echo t('confirm_delete'); ?>',
            enter_location: '<?php echo t('enter_location'); ?>',
            all: '<?php echo t('all'); ?>',
            open: '<?php echo t('open'); ?>',
            in_progress: '<?php echo t('in_progress'); ?>',
            resolved: '<?php echo t('resolved'); ?>',
            mechanical: '<?php echo t('mechanical'); ?>',
            battery: '<?php echo t('battery'); ?>',
            screen: '<?php echo t('screen'); ?>',
            tire: '<?php echo t('tire'); ?>',
            infrastructure: '<?php echo t('infrastructure'); ?>',
            other: '<?php echo t('other'); ?>',
            mecanique: 'mecanique',
            batterie: 'batterie',
            ecran: 'ecran',
            pneu: 'pneu',
            autre: 'autre',
            ouverte: 'ouverte',
            en_cours: 'en_cours',
            resolue: 'resolue'
        };

        function filterTable() {
            const searchValue = document.getElementById('search').value.toLowerCase();
            const statusValue = document.getElementById('sort-status').value.toLowerCase();
            const typeValue = document.getElementById('sort-type').value.toLowerCase();
            const table = document.getElementById('reclamations-table');
            const rows = table ? table.getElementsByTagName('tbody')[0].getElementsByTagName('tr') : [];

            for (let i = 0; i < rows.length; i++) {
                const lieu = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                const type = rows[i].getAttribute('data-type').toLowerCase();
                const statut = rows[i].getAttribute('data-status').toLowerCase();
                const lieuMatch = lieu.includes(searchValue);
                const statusMatch = statusValue === '' || statut === statusValue;
                const typeMatch = typeValue === '' || type === typeValue;

                rows[i].style.display = lieuMatch && statusMatch && typeMatch ? '' : 'none';
            }
        }

        function clearFilters() {
            document.getElementById('search').value = '';
            document.getElementById('sort-status').value = '';
            document.getElementById('sort-type').value = '';
            const table = document.getElementById('reclamations-table');
            const rows = table ? table.getElementsByTagName('tbody')[0].getElementsByTagName('tr') : [];

            for (let i = 0; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }

        // Trigger filter on input change or status/type selection
        document.getElementById('search').addEventListener('input', filterTable);
        document.getElementById('sort-status').addEventListener('change', filterTable);
        document.getElementById('sort-type').addEventListener('change', filterTable);
    </script>
</body>
</html>