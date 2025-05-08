<?php
// translate.php
// Translation helper for Green.tn

// Start session
ob_start(); // Start output buffering
session_start();

// Debug: Log session start
error_log('Session started, lang: ' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'not set'));

// Set default language to French if not set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr';
    error_log('Default language set to fr');
}

// Define BASE_URL for redirects
define('BASE_URL', '/green-tn/');

// Change language if requested via POST, but avoid redirect loop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang']) && in_array($_POST['lang'], ['fr', 'en'])) {
    if ($_SESSION['lang'] !== $_POST['lang']) { // Only redirect if lang changes
        $_SESSION['lang'] = $_POST['lang'];
        error_log('Language changed to: ' . $_POST['lang']);
        // Redirect to the same page
        $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $redirect_url = BASE_URL . ltrim(str_replace(BASE_URL, '', $current_path), '/');
        header('Location: ' . $redirect_url);
        ob_end_flush();
        exit;
    }
}

// Load translations from JSON file
$translation_file = __DIR__ . '/lang/' . $_SESSION['lang'] . '.json';
error_log('Loading translation file: ' . $translation_file);
if (file_exists($translation_file)) {
    $translations = json_decode(file_get_contents($translation_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON error in ' . $translation_file . ': ' . json_last_error_msg());
        $translations = [];
    }
} else {
    error_log('Translation file not found: ' . $translation_file);
    $translations = [];
}

/**
 * Translate a key to the current language
 * @param string $key The translation key
 * @return string The translated string or the key if not found
 */
function t($key) {
    global $translations;
    if (!isset($translations[$key])) {
        error_log("Translation key '$key' not found for lang: " . $_SESSION['lang']);
        return $key;
    }
    return $translations[$key];
}

ob_end_flush(); // End output buffering
?>