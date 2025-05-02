```php
<?php
session_start();
require_once __DIR__ . '/models/db.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Vérifier si l'ID de l'utilisateur à supprimer est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?section=users&error=ID utilisateur invalide");
    exit();
}

$user_id = (int)$_GET['id'];

// Vérifier si l'utilisateur existe
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: dashboard.php?section=users&error=Utilisateur introuvable");
    exit();
}

// Empêcher la suppression d'un admin (optionnel)
if ($user['role'] === 'admin') {
    header("Location: dashboard.php?section=users&error=Impossible de supprimer un administrateur");
    exit();
}

// Début de la transaction
$pdo->beginTransaction();

try {
    // Copier l'utilisateur dans la table trash_users
    $stmt = $pdo->prepare("
        INSERT INTO trash_users (id, nom, prenom, email, telephone, role, age, gouvernorats, cin, deleted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user['id'],
        $user['nom'],
        $user['prenom'],
        $user['email'],
        $user['telephone'],
        $user['role'],
        $user['age'],
        $user['gouvernorats'],
        $user['cin']
    ]);

    // Supprimer les notifications associées pour éviter la violation de contrainte
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Supprimer l'utilisateur de la table users
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Valider la transaction
    $pdo->commit();

    // Rediriger avec un message de succès
    header("Location: dashboard.php?section=users&message=Utilisateur déplacé vers la corbeille avec succès");
    exit();
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    // Rediriger avec un message d'erreur
    header("Location: dashboard.php?section=users&error=Erreur lors de la suppression : " . urlencode($e->getMessage()));
    exit();
}
?>
```