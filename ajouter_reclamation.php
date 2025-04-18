<?php
// ajouter_reclamation.php (View - Now also the entry point)

// Start session at the very beginning ALWAYS
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Retrieve Flash Message and Form Data (if any) ---
$message = '';
$message_type = 'error'; // Default
$form_data = [];     // Default empty form data

// Check for flash message from session
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_message_type'] ?? 'error';
    // Clear the flash message from session so it doesn't show again
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}

// Check for form data from session (used on validation errors)
if (isset($_SESSION['form_data_flash'])) {
    $form_data = $_SESSION['form_data_flash'];
    // Clear the saved form data from session
    unset($_SESSION['form_data_flash']);
}
// --- End Flash Message/Data Retrieval ---


// Check login status (using session directly)
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Define page title (can be set based on context if needed, but simple is fine now)
$pageTitle = 'Ajouter une réclamation';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Green.tn</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* Feedback message styles */
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; border: 1px solid transparent; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        /* Basic Form Styling */
        body { font-family: sans-serif; background-color: #f4f4f4; padding: 20px; }
        form { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 600px; margin: 20px auto; }
        form label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        form input[type="text"], form textarea, form select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        form textarea { min-height: 100px; resize: vertical; }
        form button { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        form button:hover { background-color: #218838; }
        main h2 { text-align: center; color: #2C3E50; margin-bottom: 20px; }
        header { background: #eee; padding: 10px 20px; margin-bottom: 20px; border-radius: 5px; }
        footer { margin-top: 30px; text-align: center; color: #777; font-size: 0.9em; }
        nav a { margin-right: 10px; text-decoration: none; color: #337ab7; }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <?php include("menu.php") ?>

    <main>
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>

        <?php
        // Display feedback message if it exists (retrieved from session earlier)
        if (!empty($message)) {
            $msg_class = ($message_type === 'success') ? 'success' : 'error';
            echo "<div class='message " . $msg_class . "'>" . htmlspecialchars($message) . "</div>";
        }
        ?>

        <?php if ($isLoggedIn): // Only show form if user is logged in ?>
             <form action="./controllers/ReclamationController.php" method="POST">
                <label for="titre">Titre:</label>
                <input type="text" id="titre" name="titre" required value="<?php echo htmlspecialchars($form_data['titre'] ?? ''); ?>">
                <br><br>

                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                <br><br>

                <label for="lieu">Lieu:</label>
                <input type="text" id="lieu" name="lieu" required value="<?php echo htmlspecialchars($form_data['lieu'] ?? ''); ?>">
                <br><br>

                <label for="type_probleme">Type:</label>
                <select id="type_probleme" name="type_probleme" required>
                    <option value="">-- Sélectionnez --</option>
                    <?php
                        $current_type = $form_data['type_probleme'] ?? ''; // Use data retrieved from session
                        $options = ["mecanique" => "Mécanique", "batterie" => "Batterie", "ecran" => "Écran", "pneu" => "Pneu", "autre" => "Autre"];
                        foreach($options as $value => $label) {
                            $selected = ($value === $current_type) ? ' selected' : '';
                            echo "<option value=\"" . htmlspecialchars($value) . "\"$selected>" . htmlspecialchars($label) . "</option>";
                        }
                    ?>
                </select>
                <br><br>

                <button type="submit">Soumettre la réclamation</button>
            </form>
         <?php elseif (empty($message)): // Show login message only if no other error/success message exists ?>
             <p class="message error">Vous devez être <a href="login.php">connecté</a> pour ajouter une réclamation.</p>
         <?php endif; ?>
    </main>

     <footer>
        <p>© <?php echo date("Y"); ?> Green.tn</p>
    </footer>

</body>
</html>