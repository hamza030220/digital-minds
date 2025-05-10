<?php
// voir_reclamation.php

// Start session (needed for user roles and potentially user ID)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Dependencies ---
define('ROOT_PATH', realpath(__DIR__ . '/..'));
require_once ROOT_PATH . '/config/database.php'; // Needed for Database class
require_once ROOT_PATH . '/models/Reclamation.php'; // Needed for Reclamation model

// --- Initialization ---
$reclamation = null;
$reponses = [];
$feedback_message = '';
$message_type = 'info'; // For styling feedback ('info', 'success', 'error')
$pdo = null; // Initialize PDO variable

// --- Handle Feedback Messages from Status Update ---
if (isset($_GET['status_update'])) {
    switch ($_GET['status_update']) {
        case 'success':
            $feedback_message = "Statut mis à jour avec succès.";
            $message_type = 'success';
            break;
        case 'error_model':
        case 'error_db':
            $feedback_message = "Erreur base de données lors de la mise à jour.";
            $message_type = 'error';
            break;
        case 'error_pdo':
        case 'error_system':
            $feedback_message = "Erreur système lors de la mise à jour.";
            $message_type = 'error';
            break;
        case 'error_input':
            $feedback_message = "Données fournies invalides.";
            $message_type = 'error';
            break;
        case 'error_missing':
            $feedback_message = "Données manquantes pour la mise à jour.";
            $message_type = 'error';
            break;
    }
}

// --- Get Reclamation ID and Fetch Data ---
$reclamation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if ($reclamation_id) {
    try {
        // --- Use Reclamation Model to fetch details ---
        $reclamationModel = new Reclamation();
        $reclamation = $reclamationModel->getParId($reclamation_id);

        if ($reclamation) {
            // --- Fetch associated responses with pagination ---
            $database = new Database();
            $pdo = $database->getConnection();

            if ($pdo) {
                // Pagination for responses
                $reponses_per_page = 3; // 3 responses per page
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Current page
                $offset = ($page - 1) * $reponses_per_page; // Calculate offset

                // Count total responses
                $total_reponses_query = "SELECT COUNT(*) FROM reponses WHERE reclamation_id = ?";
                $stmt = $pdo->prepare($total_reponses_query);
                $stmt->bindParam(1, $reclamation_id, PDO::PARAM_INT);
                $stmt->execute();
                $total_reponses = $stmt->fetchColumn();
                $total_pages = ceil($total_reponses / $reponses_per_page);

                // Fetch responses for the current page
                $repStmt = $pdo->prepare("
                    SELECT * FROM reponses 
                    WHERE reclamation_id = ? 
                    ORDER BY date_creation ASC 
                    LIMIT ? OFFSET ?
                ");
                $repStmt->bindParam(1, $reclamation_id, PDO::PARAM_INT);
                $repStmt->bindParam(2, $reponses_per_page, PDO::PARAM_INT);
                $repStmt->bindParam(3, $offset, PDO::PARAM_INT);
                $repStmt->execute();
                $reponses = $repStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                error_log("Failed to get DB connection for responses in voir_reclamation.php");
                $feedback_message .= ($feedback_message ? "<br>" : "") . "Erreur: Impossible de charger les réponses.";
                $message_type = 'error';
            }
        } else {
            $feedback_message = "Réclamation non trouvée.";
            $message_type = 'error';
        }
    } catch (Exception $e) {
        error_log("Error in voir_reclamation.php for ID {$reclamation_id}: " . $e->getMessage());
        $feedback_message = "Une erreur technique est survenue lors de la récupération des données.";
        $message_type = 'error';
        $reclamation = null;
    } finally {
        $repStmt = null;
        $pdo = null;
    }
} else {
    $feedback_message = "ID de réclamation invalide ou manquant.";
    $message_type = 'error';
}

// Determine the role safely
$role = $_SESSION['user_role'] ?? 'utilisateur';

// Configuration de l'API Gemini
$api_key = "AIzaSyABlV8PDgpUhcUV9GLGD_w_s8dpQ6LAeHQ"; // Clé API fournie
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($api_key);

// Set page title
$pageTitle = $reclamation ? 'Détails de la réclamation - Green.tn' : 'Erreur - Green.tn';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="../image/ve.png" type="image/png">
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

        .sidebar {
            width: 200px;
            background-color: #60BA97;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            padding-top: 20px;
            color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .logo img {
            width: 150px;
            height: auto;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            font-size: 1em;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.3s ease;
        }

        .sidebar ul li a:hover {
            background-color: #1b5e20;
            border-radius: 0 20px 20px 0;
        }

        .container {
            margin-left: 220px;
            width: calc(90% - 220px);
            max-width: 900px;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #4CAF50;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            color: #2e7d32;
            font-size: 24px;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .feedback-message {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .feedback-message.success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .feedback-message.error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .feedback-message.info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .reclamation-details h2 {
            color: #2e7d32;
            font-size: 20px;
            margin-bottom: 15px;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .reclamation-details p {
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .changer-statut {
            margin: 20px 0;
        }

        .changer-statut form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .changer-statut label {
            font-weight: bold;
            color: #2e7d32;
        }

        .changer-statut select {
            padding: 8px;
            border: 1px solid #4CAF50;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .changer-statut select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        button {
            background-color: #2e7d32;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #1b5e20;
        }

        .reponses h3 {
            color: #2e7d32;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 15px;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .reponse {
            background-color: #fff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #4CAF50;
        }

        .reponse p {
            margin: 5px 0;
            line-height: 1.6;
        }

        .reponse small {
            color: #7f8c8d;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            background-color: #4CAF50;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #2e7d32;
        }

        .pagination a.disabled {
            background-color: #ccc;
            pointer-events: none;
        }

        .pagination a.current {
            background-color: #2e7d32;
        }

        .formulaire-reponse h3 {
            color: #2e7d32;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 15px;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .formulaire-reponse textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .formulaire-reponse textarea:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .formulaire-reponse button {
            margin-top: 10px;
        }

        .suggest-response-btn {
            background-color: #4CAF50;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-family: "Bauhaus 93", Arial, sans-serif;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .suggest-response-btn:hover {
            background-color: #2e7d32;
        }

        a {
            color: #2e7d32;
            text-decoration: none;
            transition: color 0.2s;
        }

        a:hover {
            color: #1b5e20;
            text-decoration: underline;
        }

        footer {
            background-color: #F9F5E8;
            padding: 20px 0;
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

        .error-message {
            color: #721c24;
            font-size: 0.85em;
            margin-top: 5px;
            display: none;
        }

        .input-error {
            border-color: #721c24;
        }

        .input-valid {
            border-color: #155724;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding-bottom: 20px;
            }

            .container {
                margin-left: 0;
                width: 90%;
                margin: 20px auto;
            }

            header h1 {
                font-size: 20px;
            }

            .reclamation-details h2 {
                font-size: 18px;
            }

            .reponses h3, .formulaire-reponse h3 {
                font-size: 16px;
            }

            .changer-statut form {
                flex-direction: column;
                align-items: flex-start;
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
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <img src="../image/ve.png" alt="Green.tn Logo">
        </div>
        <ul>
            <li><a href="../views/stats.php">🏠 Dashboard</a></li>
            <li><a href="">🚲 Reservation</a></li>
            <li><a href="../views/reclamations_utilisateur.php">📋 Reclamation</a></li>
            <li><a href="../views/liste_avis.php">⭐ Avis</a></li>
            <li><a href="../logout.php">🔓 Déconnexion</a></li>
        </ul>
    </div>

    <div class="container">
        <header>
            <h1>Détails de la réclamation</h1>
        </header>

        <!-- Display Feedback Message -->
        <?php if ($feedback_message): ?>
            <div class="feedback-message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
        <?php endif; ?>

        <!-- Check if reclamation data was loaded -->
        <?php if ($reclamation): ?>
            <section class="reclamation-details">
                <h2><?php echo htmlspecialchars($reclamation['titre']); ?></h2>
                <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>
                <p>
                    <strong>Lieu :</strong> <?php echo htmlspecialchars($reclamation['lieu']); ?> |
                    <strong>Type :</strong> <?php echo htmlspecialchars($reclamation['type_probleme']); ?> |
                    <strong>Statut :</strong> <?php echo htmlspecialchars(ucfirst($reclamation['statut'])); ?> |
                    <strong>Posté le :</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($reclamation['date_creation']))); ?>
                </p>
            </section>

            <!-- Section de changement de statut pour admin -->
            <?php if ($role === 'admin'): ?>
                <section class="changer-statut">
                    <form method="post" action="../controllers/changer_statut.php">
                        <input type="hidden" name="reclamation_id" value="<?php echo htmlspecialchars($reclamation['id']); ?>">
                        <label for="statut"><strong>Changer le statut :</strong></label>
                        <select name="statut" id="statut">
                            <option value="ouverte" <?php echo $reclamation['statut'] === 'ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                            <option value="en cours" <?php echo $reclamation['statut'] === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="résolue" <?php echo $reclamation['statut'] === 'résolue' ? 'selected' : ''; ?>>Résolue</option>
                        </select>
                        <button type="submit">Mettre à jour</button>
                    </form>
                </section>
            <?php endif; ?>

            <section class="reponses">
                <h3>Réponses :</h3>
                <?php if (empty($reponses) && $total_reponses == 0): ?>
                    <p>Aucune réponse pour l'instant.</p>
                <?php else: ?>
                    <?php foreach ($reponses as $r): ?>
                        <div class="reponse">
                            <p><?php echo nl2br(htmlspecialchars($r['contenu'])); ?></p>
                            <small>
                                Posté le <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['date_creation']))); ?>
                                <?php if (isset($r['role'])): ?>
                                    par <?php echo htmlspecialchars(ucfirst($r['role'])); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination for responses -->
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="../views/voir_reclamation.php?id=<?php echo $reclamation_id; ?>&page=<?php echo $page - 1; ?>">Précédent</a>
                        <?php else: ?>
                            <a href="#" class="disabled">Précédent</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="../views/voir_reclamation.php?id=<?php echo $reclamation_id; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="../views/voir_reclamation.php?id=<?php echo $reclamation_id; ?>&page=<?php echo $page + 1; ?>">Suivant</a>
                        <?php else: ?>
                            <a href="#" class="disabled">Suivant</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Formulaire pour répondre -->
            <section class="formulaire-reponse">
                <h3>Ajouter une réponse :</h3>
                <form method="post" action="../views/ajouter_reponse.php" id="responseForm" novalidate>
                    <input type="hidden" name="reclamation_id" value="<?php echo htmlspecialchars($reclamation['id']); ?>">
                    <textarea name="contenu" id="contenu" rows="4" placeholder="Votre réponse ici..."></textarea>
                    <div class="error-message" id="contenu-error"></div>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                    <?php if ($role === 'admin'): ?>
                        <button type="button" class="suggest-response-btn" onclick="suggestResponse()">Suggérer une réponse</button>
                    <?php endif; ?>
                    <button type="submit">Répondre</button>
                </form>
            </section>

            <p>
                <a href="../views/reclamations_utilisateur.php">← Retour au tableau de bord</a>
            </p>

            <script>
                // API Configuration
                const apiUrl = '<?php echo $api_url; ?>';
                const reclamationDescription = '<?php echo addslashes($reclamation['description']); ?>';

                // Suggest Response using Gemini API
                async function suggestResponse() {
                    console.log('Suggesting response for description:', reclamationDescription);
                    const prompt = `Suggest a professional and concise response to the following reclamation complaint: "${reclamationDescription}". The response should address the issue, propose a solution, and maintain a polite tone. Return a JSON object with a "response" field containing the suggested reply.`;

                    const requestBody = {
                        contents: [{
                            parts: [{ text: prompt }]
                        }]
                    };

                    try {
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(requestBody)
                        });

                        console.log('Fetch response status:', response.status);
                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('Fetch error response:', errorText);
                            throw new Error('Failed to fetch suggestion from Gemini API: ' + response.status + ' ' + errorText);
                        }

                        const data = await response.json();
                        console.log('Gemini API raw response:', data);

                        if (!data.candidates || !data.candidates[0] || !data.candidates[0].content || !data.candidates[0].content.parts || !data.candidates[0].content.parts[0]) {
                            throw new Error('Invalid response structure from Gemini API');
                        }

                        const textResponse = data.candidates[0].content.parts[0].text;
                        console.log('Gemini API text response:', textResponse);

                        // Clean the response to extract valid JSON
                        let jsonString = textResponse.trim();
                        if (jsonString.startsWith('```json') && jsonString.endsWith('```')) {
                            jsonString = jsonString.replace(/```json/, '').replace(/```/, '').trim();
                        } else if (jsonString.startsWith('{') && jsonString.endsWith('}')) {
                            // If it's already a raw JSON object, take it as is
                        } else {
                            throw new Error('Unexpected response format: ' + jsonString);
                        }

                        let parsedResponse;
                        try {
                            parsedResponse = JSON.parse(jsonString);
                        } catch (e) {
                            console.error('Error parsing Gemini response as JSON:', e, 'Raw response:', jsonString);
                            throw new Error('Failed to parse Gemini response as JSON: ' + jsonString);
                        }

                        if (parsedResponse.response) {
                            document.getElementById('contenu').value = parsedResponse.response;
                            console.log('Set suggested response:', parsedResponse.response);
                            // Dispatch input event to trigger validation
                            const inputEvent = new Event('input');
                            document.getElementById('contenu').dispatchEvent(inputEvent);
                        } else {
                            throw new Error('No "response" field in Gemini API response');
                        }
                    } catch (error) {
                        console.error('Error in suggestResponse:', error);
                        alert('Erreur lors de la suggestion de réponse : ' + error.message);
                    }
                }

                // Form validation
                document.getElementById('responseForm').addEventListener('submit', function(event) {
                    event.preventDefault();
                    let isValid = true;
                    const errors = {};

                    const contenu = document.getElementById('contenu').value.trim();
                    if (!contenu) {
                        errors.contenu = 'La réponse est requise.';
                        isValid = false;
                    } else if (contenu.length < 10 || contenu.length > 1000) {
                        errors.contenu = 'La réponse doit contenir entre 10 et 1000 caractères.';
                        isValid = false;
                    }

                    const errorElement = document.getElementById('contenu-error');
                    const inputElement = document.getElementById('contenu');
                    if (errors.contenu) {
                        errorElement.textContent = errors.contenu;
                        errorElement.style.display = 'block';
                        inputElement.classList.add('input-error');
                        inputElement.classList.remove('input-valid');
                    } else {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                        inputElement.classList.remove('input-error');
                        inputElement.classList.add('input-valid');
                    }

                    if (isValid) {
                        this.submit();
                    } else {
                        inputElement.focus();
                        inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });

                document.getElementById('contenu').addEventListener('input', function() {
                    const errorElement = document.getElementById('contenu-error');
                    let error = '';

                    const value = this.value.trim();
                    if (!value) error = 'La réponse est requise.';
                    else if (value.length < 10 || value.length > 1000) error = 'La réponse doit contenir entre 10 et 1000 caractères.';

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
            </script>

        <?php elseif (!$feedback_message): ?>
            <p>Les détails de cette réclamation ne sont pas disponibles.</p>
            <p><a href="../views/index.php">← Retour à l'accueil</a></p>
        <?php endif; ?>
    </div>
</body>
</html>