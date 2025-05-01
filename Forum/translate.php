<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum - Traduction</title>
</head>
<body>
    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');
    require_once 'db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['q']) || !isset($data['source']) || !isset($data['target'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $text = $data['q'];
        $sourceLang = $data['source'];
        $targetLang = $data['target'];

        // Use the translateText function
        $translatedText = translateText($text, $sourceLang, $targetLang);

        if ($translatedText) {
            echo json_encode(['success' => true, 'translatedText' => $translatedText]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Translation failed']);
        }
        exit;
    }

    // Fonction pour appeler l'API LibreTranslate
    function translateText($text, $sourceLang, $targetLang) {
        $url = 'https://libretranslate.com/translate';

        $data = [
            'q' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => 'text'
        ];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            return null; // Handle error
        }

        $response = json_decode($result, true);
        return $response['translatedText'] ?? null;
    }
    ?>
</body>
</html>