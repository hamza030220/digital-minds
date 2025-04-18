<?php
// Include necessary files
require('fpdf/fpdf.php');
require('../config/database.php'); // Include the Database class file

// Establish database connection using the Database class
$database = new Database();
$pdo = $database->getConnection();

// Check if the connection was successful
if (!$pdo) {
    // You might want to log this error or display a more user-friendly message
    die("Erreur de connexion à la base de données.");
}

// --- The rest of your script remains largely the same ---

// Récupération des filtres
$lieu = $_GET['lieu'] ?? '';
$type_probleme = $_GET['type_probleme'] ?? '';
$statut = $_GET['statut'] ?? '';

// Prepare SQL query with placeholders
$sql = "SELECT * FROM reclamations WHERE 1=1";
$params = [];

if (!empty($lieu)) {
    $sql .= " AND lieu LIKE ?";
    $params[] = "%$lieu%";
}
if (!empty($type_probleme)) {
    $sql .= " AND type_probleme = ?";
    $params[] = $type_probleme;
}
if (!empty($statut)) {
    $sql .= " AND statut = ?";
    $params[] = $statut;
}

// Prepare and execute the statement
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle potential query errors
    die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
}


// Création du PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Liste des Reclamations', 0, 1, 'C');
$pdf->Ln(5);

// En-tête du tableau
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(30, 10, 'Titre', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Description', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Lieu', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Type', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Statut', 1, 1, 'C', true); // Changed last 0 to 1 for line break

// Contenu du tableau
$pdf->SetFont('Arial', '', 10);
if (empty($reclamations)) {
    $pdf->Cell(160, 10, 'Aucune reclamation trouvee.', 1, 1, 'C'); // Adjusted colspan width (30+40+30+30+30 = 160)
} else {
    foreach ($reclamations as $r) {
        // Handle potential multi-byte characters in substr if needed (using mb_substr)
        $description_short = mb_substr($r['description'], 0, 35, 'UTF-8');
        if (mb_strlen($r['description'], 'UTF-8') > 35) {
            $description_short .= '...';
        }

        // Use MultiCell for description if it can wrap - otherwise stick with Cell
        // For simplicity, keeping Cell as in the original example
        $pdf->Cell(30, 10, utf8_decode($r['titre']), 1); // Use utf8_decode for FPDF if needed
        $pdf->Cell(40, 10, utf8_decode($description_short), 1);
        $pdf->Cell(30, 10, utf8_decode($r['lieu']), 1);
        $pdf->Cell(30, 10, utf8_decode($r['type_probleme']), 1);
        $pdf->Cell(30, 10, utf8_decode($r['statut']), 1);
        $pdf->Ln(); // Ensure Ln() is called after each full row
    }
}

// Output the PDF
$pdf->Output('D', 'reclamations.pdf');
exit; // Exit script after sending PDF
?>