<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_input'])) {
    $user_input = trim(strtolower($_POST['user_input']));

    // Réponses prédéfinies pour les questions fréquentes
    $predefined_responses = [
        "comment ajouter une reclamation" => "Pour ajouter une réclamation sur Green.tn, connectez-vous à votre compte, allez sur la page 'Ajouter une réclamation', remplissez le formulaire avec le titre, la description et le type de problème (par exemple, pneu, batterie), puis soumettez-le.",
        "comment voir mes reclamations" => "Pour voir vos réclamations sur Green.tn, connectez-vous à votre compte et allez sur la page 'Liste des réclamations'. Vous y trouverez toutes vos réclamations avec leur statut (ouverte, en cours, résolue).",
        "comment laisser un avis" => "Pour laisser un avis sur Green.tn, connectez-vous à votre compte, allez sur la page 'Ajouter un avis', remplissez le formulaire avec votre note (de 1 à 5) et votre commentaire, puis soumettez-le.",
        "comment voir mes avis" => "Pour voir vos avis sur Green.tn, connectez-vous à votre compte et allez sur la page 'Mes avis'. Vous y trouverez tous les avis que vous avez soumis avec leurs notes et commentaires.",
        "comment contacter le support" => "Vous pouvez contacter le support de Green.tn via email à Green@green.com ou par téléphone (voir les coordonnées dans la section 'Contact' en bas de page).",
        "quel est le statut de ma reclamation" => "Pour vérifier le statut de votre réclamation, connectez-vous à votre compte et allez sur la page 'Liste des réclamations'. Vous verrez le statut (ouverte, en cours, résolue) à côté de chaque réclamation.",
        "comment modifier une reclamation" => "Actuellement, Green.tn ne permet pas de modifier une réclamation après soumission. Contactez le support à Green@green.com pour demander une modification.",
        "comment supprimer une reclamation" => "Pour supprimer une réclamation, connectez-vous à votre compte, allez sur la page 'Liste des réclamations', et cliquez sur l'option 'Supprimer' si elle est disponible. Sinon, contactez le support à Green@green.com.",
        "comment modifier un avis" => "Actuellement, Green.tn ne permet pas de modifier un avis après soumission. Contactez le support à Green@green.com pour demander une modification.",
        "comment supprimer un avis" => "Pour supprimer un avis, connectez-vous à votre compte, allez sur la page 'Mes avis', et cliquez sur l'option 'Supprimer' si elle est disponible. Sinon, contactez le support à Green@green.com.",
        "quels sont les types de problemes" => "Les types de problèmes sur Green.tn incluent : mécanique (problèmes avec les pédales, chaîne), batterie (problèmes de charge), écran (écran cassé ou non fonctionnel), pneu (crevaison, usure), et autre (problèmes divers).",
        "combien de temps pour traiter une reclamation" => "Le temps de traitement d'une réclamation sur Green.tn dépend du type de problème. En général, les réclamations sont traitées sous 3 à 5 jours ouvrables. Vérifiez le statut dans 'Liste des réclamations'.",
        "comment changer ma langue" => "Pour changer la langue sur Green.tn, utilisez le bouton de changement de langue en haut à droite de la page. Vous pouvez passer du français à l'anglais ou vice versa.",
        "comment me deconnecter" => "Pour vous déconnecter de Green.tn, cliquez sur le bouton 'Déconnexion' situé en haut à droite de la page.",
        "comment changer mon mot de passe" => "Actuellement, Green.tn ne propose pas d'option directe pour changer votre mot de passe. Contactez le support à Green@green.com pour demander une réinitialisation.",
        "qu'est-ce que green.tn" => "Green.tn est une plateforme de mobilité verte qui vous permet de signaler des problèmes avec des vélos ou d'autres services de mobilité, de laisser des avis, et de suivre vos réclamations.",
        "comment fonctionne green.tn" => "Sur Green.tn, vous pouvez vous connecter, soumettre des réclamations pour des problèmes (comme un pneu crevé), laisser des avis sur les services, et suivre l'état de vos réclamations. Les administrateurs traitent vos réclamations et vous tiennent informé.",
        "quels services proposez-vous" => "Green.tn propose des services liés à la mobilité verte, principalement pour les vélos. Vous pouvez signaler des problèmes (pneus, batterie, etc.), laisser des avis, et suivre vos réclamations.",
        "comment signaler un bug sur le site" => "Pour signaler un bug sur Green.tn, contactez le support à Green@green.com en décrivant le problème rencontré (par exemple, une page qui ne charge pas).",
        "je ne peux pas me connecter" => "Si vous ne pouvez pas vous connecter à Green.tn, vérifiez votre email et mot de passe. Si le problème persiste, contactez le support à Green@green.com pour demander une réinitialisation de mot de passe.",
        "pourquoi ma réclamation prend-elle autant de temps" => "Certaines réclamations peuvent prendre plus de temps en raison de la complexité du problème ou du volume de réclamations en cours. En général, cela prend 3 à 5 jours ouvrables. Contactez le support à Green@green.com si cela dépasse 7 jours.",
        "que signifie le statut 'en cours'" => "Le statut 'en cours' signifie qu'un administrateur ou technicien a pris en charge votre réclamation et travaille à la résoudre. Vous serez informé une fois qu'elle sera résolue.",
        "puis-je signaler un problème avec les freins" => "Oui, vous pouvez signaler un problème avec les freins. Sélectionnez le type de problème 'mécanique' lors de la soumission de votre réclamation et précisez dans la description qu'il s'agit des freins.",
        "que faire si mon problème n'est pas dans la liste" => "Si votre problème n'est pas dans la liste, sélectionnez 'autre' comme type de problème et décrivez-le en détail dans la description de votre réclamation. Un administrateur l'examinera.",
        "mon vélo ne charge plus, que faire" => "Si votre vélo ne charge plus, soumettez une réclamation en sélectionnant 'batterie' comme type de problème. Décrivez le problème en détail, et un technicien vous contactera pour organiser une réparation.",
        "le pneu est crevé, comment le signaler" => "Pour signaler un pneu crevé, connectez-vous à votre compte, allez sur 'Ajouter une réclamation', sélectionnez 'pneu' comme type de problème, décrivez le souci, et soumettez la réclamation.",
        "je n'arrive pas à soumettre une réclamation, que faire" => "Si vous n'arrivez pas à soumettre une réclamation, vérifiez que tous les champs obligatoires (titre, description, type de problème) sont remplis. Si le problème persiste, contactez le support à Green@green.com.",
        "pourquoi ma réclamation a-t-elle été refusée" => "Une réclamation peut être refusée si les informations fournies sont insuffisantes ou si le problème ne relève pas de nos services. Vérifiez les détails dans 'Liste des réclamations' ou contactez le support à Green@green.com pour plus d'informations.",
        "est-ce que vous réparez les écrans cassés" => "Oui, Green.tn prend en charge les réparations d'écrans cassés. Soumettez une réclamation avec le type de problème 'écran' et précisez les détails. Un technicien vous contactera.",
        "puis-je demander un remplacement de pièce" => "Oui, vous pouvez demander un remplacement de pièce via une réclamation. Précisez dans la description que vous souhaitez un remplacement (par exemple, un nouveau pneu), et un administrateur examinera votre demande.",
        "comment savoir si ma réclamation a été lue" => "Une fois votre réclamation soumise, son statut passera à 'ouverte' dans 'Liste des réclamations'. Lorsqu'un administrateur la prend en charge, le statut changera à 'en cours'.",
        "puis-je ajouter des détails à ma réclamation" => "Actuellement, vous ne pouvez pas modifier une réclamation après soumission. Cependant, vous pouvez contacter le support à Green@green.com pour ajouter des détails ou soumettre une nouvelle réclamation.",
        "que faire si mon vélo fait du bruit" => "Si votre vélo fait du bruit, soumettez une réclamation en sélectionnant 'mécanique' comme type de problème. Décrivez le bruit en détail (par exemple, grincement ou cliquetis), et un technicien vous contactera pour organiser une réparation."
    ];

    // Vérifier si la question correspond à une réponse prédéfinie
    foreach ($predefined_responses as $question => $answer) {
        if (strpos($user_input, $question) !== false) {
            echo json_encode(['response' => $answer]);
            exit;
        }
    }

    // Configuration de l'API Gemini
    $api_key = "AIzaSyABlV8PDgpUhcUV9GLGD_w_s8dpQ6LAeHQ"; // Clé API fournie
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($api_key);

    // Ajouter un contexte pour que le modèle réponde dans le cadre de Green.tn
    $context = "Tu es un assistant pour Green.tn, une plateforme de mobilité verte dédiée aux vélos. Réponds aux questions des utilisateurs concernant les réclamations, les avis, ou les services de Green.tn. Ne parle pas de sujets non pertinents comme les voitures.";
    $prompt = $context . "\n\nUtilisateur : " . $user_input;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false) {
        error_log("cURL Error: " . curl_error($ch) . " (HTTP Code: $http_code)");
        echo json_encode(['response' => "Désolé, je n'ai pas compris votre question. Essayez de la reformuler, ou consultez les pages 'Ajouter une réclamation', 'Liste des réclamations', ou 'Mes avis' pour plus d'informations. Vous pouvez aussi contacter le support à Green@green.com."]);
        exit;
    }

    $response_data = json_decode($result, true);
    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        $response = $response_data['candidates'][0]['content']['parts'][0]['text'];
        // Supprimer le prompt de la réponse si nécessaire
        $response = str_replace($prompt, '', $response);
        echo json_encode(['response' => trim($response)]);
    } else if (isset($response_data['error'])) {
        error_log("API Error: " . $response_data['error']['message'] . " (HTTP Code: $http_code)");
        echo json_encode(['response' => "Désolé, je n'ai pas compris votre question. Essayez de la reformuler, ou consultez les pages 'Ajouter une réclamation', 'Liste des réclamations', ou 'Mes avis' pour plus d'informations. Vous pouvez aussi contacter le support à Green@green.com."]);
    } else {
        error_log("Unexpected API response: " . $result . " (HTTP Code: $http_code)");
        echo json_encode(['response' => "Désolé, je n'ai pas compris votre question. Essayez de la reformuler, ou consultez les pages 'Ajouter une réclamation', 'Liste des réclamations', ou 'Mes avis' pour plus d'informations. Vous pouvez aussi contacter le support à Green@green.com."]);
    }
} else {
    echo json_encode(['response' => null]);
}
?>
