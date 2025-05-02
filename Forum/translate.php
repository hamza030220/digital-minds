<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Dictionnaire de traductions courantes
$translations = array(
    'en' => array(
        // Mots courants du forum
        'Post' => 'Publication',
        'Comment' => 'Commentaire',
        'Reply' => 'Répondre',
        'Edit' => 'Modifier',
        'Delete' => 'Supprimer',
        'Report' => 'Signaler',
        'Already reported' => 'Déjà signalé',
        'Hide' => 'Masquer',
        'Show' => 'Afficher',
        'Load more' => 'Charger plus',
        'No comments' => 'Aucun commentaire',
        'Posted on' => 'Publié le',
        'by' => 'par',
        'Anonymous' => 'Anonyme',
        'Admin' => 'Administrateur',
        'User' => 'Utilisateur',
        'Login' => 'Connexion',
        'Logout' => 'Déconnexion',
        'Register' => 'S\'inscrire',
        'Password' => 'Mot de passe',
        'Email' => 'Courriel',
        'Username' => 'Nom d\'utilisateur',
        'Submit' => 'Soumettre',
        'Cancel' => 'Annuler',
        'Error' => 'Erreur',
        'Success' => 'Succès',
        'Warning' => 'Avertissement',
        'Info' => 'Information',
        'Loading' => 'Chargement',
        'Translate' => 'Traduire',
        'More' => 'Plus',
        'Less' => 'Moins',
        'Back' => 'Retour',
        'Next' => 'Suivant',
        'Previous' => 'Précédent',
        
        // Messages d'erreur courants
        'An error occurred' => 'Une erreur est survenue',
        'Please try again' => 'Veuillez réessayer',
        'Loading failed' => 'Échec du chargement',
        'Connection error' => 'Erreur de connexion',
        'Invalid input' => 'Entrée invalide',
        'Required field' => 'Champ requis',
        'Not authorized' => 'Non autorisé',
        'Session expired' => 'Session expirée',
        
        // Messages de succès courants
        'Successfully saved' => 'Enregistré avec succès',
        'Changes applied' => 'Modifications appliquées',
        'Updated successfully' => 'Mis à jour avec succès',
        'Deleted successfully' => 'Supprimé avec succès',
        
        // Expressions courantes
        'Thank you' => 'Merci',
        'Please wait' => 'Veuillez patienter',
        'Are you sure?' => 'Êtes-vous sûr ?',
        'Yes, proceed' => 'Oui, continuer',
        'No, cancel' => 'Non, annuler',
        'Read more' => 'Lire la suite',
        'Show all' => 'Tout afficher',
        'Hide all' => 'Tout masquer',
        
        // Termes de base
        'Hello' => 'Bonjour',
        'Welcome' => 'Bienvenue',
        'Good morning' => 'Bonjour',
        'Good evening' => 'Bonsoir',
        'How are you?' => 'Comment allez-vous ?',
        'Please' => 'S\'il vous plaît',
        'Yes' => 'Oui',
        'No' => 'Non',
        'Maybe' => 'Peut-être',
        'Save' => 'Enregistrer',
        
        // Termes spécifiques au vélo et à l'environnement
        'bike' => 'vélo',
        'bicycle' => 'bicyclette',
        'cycling' => 'cyclisme',
        'ride' => 'balade',
        'rider' => 'cycliste',
        'helmet' => 'casque',
        'safety' => 'sécurité',
        'route' => 'itinéraire',
        'path' => 'chemin',
        'trail' => 'piste',
        'green' => 'vert',
        'environment' => 'environnement',
        'eco-friendly' => 'écologique',
        'sustainable' => 'durable',
        'pollution' => 'pollution',
        'clean energy' => 'énergie propre',
        'recycling' => 'recyclage',
        'nature' => 'nature',
        'climate' => 'climat',
        'maintenance' => 'entretien',
        'repair' => 'réparation',
        'rent' => 'louer',
        'rental' => 'location',
        'price' => 'prix',
        'rates' => 'tarifs',
        'booking' => 'réservation',
        'schedule' => 'horaire',
        'availability' => 'disponibilité',
        'equipment' => 'équipement',
        'accessories' => 'accessoires',

        // Phrases complètes courantes
        'How to rent a bike?' => 'Comment louer un vélo ?',
        'Where can I find a bike?' => 'Où puis-je trouver un vélo ?',
        'What are the rental rates?' => 'Quels sont les tarifs de location ?',
        'Is this bike available?' => 'Ce vélo est-il disponible ?',
        'I need help with my bike' => 'J\'ai besoin d\'aide avec mon vélo',
        'The bike is broken' => 'Le vélo est cassé',
        'Can you help me?' => 'Pouvez-vous m\'aider ?',
        'How does it work?' => 'Comment ça marche ?',
        'What are the safety rules?' => 'Quelles sont les règles de sécurité ?',
        'Where are the bike paths?' => 'Où sont les pistes cyclables ?'
    )
);

function translateText($text, $sourceLang = 'en', $targetLang = 'fr') {
    global $translations;
    
    try {
        // D'abord essayer la traduction locale avec phrases complètes
        if (isset($translations[$sourceLang])) {
            // Découper le texte en mots
            $words = explode(' ', strtolower($text));
            $translated_words = array();
            $i = 0;
            
            while ($i < count($words)) {
                $found = false;
                // Essayer de traduire des groupes de mots de plus en plus petits
                for ($j = 4; $j > 0; $j--) {
                    if ($i + $j <= count($words)) {
                        $phrase = implode(' ', array_slice($words, $i, $j));
                        if (isset($translations[$sourceLang][$phrase])) {
                            $translated_words[] = $translations[$sourceLang][$phrase];
                            $i += $j;
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    // Si aucune traduction n'est trouvée pour le mot, le garder tel quel
                    $translated_words[] = $words[$i];
                    $i++;
                }
            }
            
            $translated_text = implode(' ', $translated_words);
            
            // Si on a réussi à traduire quelque chose localement, retourner le résultat
            if ($translated_text !== strtolower($text)) {
                return array(
                    'success' => true,
                    'translatedText' => $translated_text
                );
            }
        }

        // Si la traduction locale n'est pas satisfaisante, essayer de trouver des correspondances partielles
        $modified_text = $text;
        foreach ($translations[$sourceLang] as $key => $value) {
            if (stripos($modified_text, $key) !== false) {
                $modified_text = str_ireplace($key, $value, $modified_text);
            }
        }

        // Si des modifications ont été faites, retourner le texte modifié
        if ($modified_text !== $text) {
            return array(
                'success' => true,
                'translatedText' => $modified_text
            );
        }

        // Si aucune traduction n'est trouvée, retourner le texte original
        return array(
            'success' => true,
            'translatedText' => $text,
            'note' => 'Traduction partielle ou texte original'
        );

    } catch (Exception $e) {
        error_log('Erreur de traduction: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => 'Erreur de traduction: ' . $e->getMessage(),
            'originalText' => $text
        );
    }
}

// Gérer les requêtes OPTIONS (pour CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Traiter la requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erreur de décodage des données d\'entrée: ' . json_last_error_msg());
        }
        
        if (!isset($data['q']) || empty(trim($data['q']))) {
            throw new Exception('Texte manquant');
        }

        $text = $data['q'];
        $sourceLang = isset($data['source']) ? $data['source'] : 'en';
        $targetLang = isset($data['target']) ? $data['target'] : 'fr';
        
        $result = translateText($text, $sourceLang, $targetLang);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
} else {
    echo json_encode(array(
        'success' => false,
        'message' => 'Méthode non autorisée'
    ));
}
