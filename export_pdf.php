<?php
require('fpdf/fpdf.php');

// Connexion à la base de données
$pdo = new PDO("mysql:host=localhost;dbname=green_tn", "root", "");

// Récupération des filtres
$lieu = $_GET['lieu'] ?? '';
$type_probleme = $_GET['type_probleme'] ?? '';
$statut = $_GET['statut'] ?? '';

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$pdf->Cell(30, 10, 'Statut', 1, 1, 'C', true);

// Contenu du tableau
$pdf->SetFont('Arial', '', 10);
foreach ($reclamations as $r) {
    $pdf->Cell(30, 10, $r['titre'], 1);
    $pdf->Cell(40, 10, substr($r['description'], 0, 35) . '...', 1);
    $pdf->Cell(30, 10, $r['lieu'], 1);
    $pdf->Cell(30, 10, $r['type_probleme'], 1);
    $pdf->Cell(30, 10, $r['statut'], 1);
    $pdf->Ln();
}

$pdf->Output('D', 'reclamations.pdf');
exit;
