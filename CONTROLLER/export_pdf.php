<?php
// CONTROLLER/export_pdf.php

// Include FPDF library and Database class
require_once '../CONTROLLER/fpdf/fpdf.php';
require_once '../CONFIG/database.php';

// Establish database connection using the Database class
$database = new Database();
$pdo = $database->getConnection();

// Check if the connection was successful
if (!$pdo) {
    error_log("Database connection failed in CONTROLLER/export_pdf.php");
    die("Erreur de connexion à la base de données.");
}

// --- Fetch Filters ---
$lieu = $_GET['lieu'] ?? '';
$type_probleme = $_GET['type_probleme'] ?? '';
$statut = $_GET['statut'] ?? '';

// Check if at least one filter is provided
/*if (empty($lieu) && empty($type_probleme) && empty($statut)) {
    error_log("export_pdf.php: No filters provided");
    die("Erreur : Veuillez spécifier au moins un filtre pour générer le PDF.");
}*/

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
    // Log number of reclamations for debugging
    error_log("export_pdf.php: Fetched " . count($reclamations) . " reclamations");
} catch (PDOException $e) {
    error_log("export_pdf.php: Query error - " . $e->getMessage());
    die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
}

// --- Create PDF ---
class CustomFPDF extends FPDF {
    function SetTextColorHex($hex) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $this->SetTextColor($r, $g, $b);
    }

    function SetFillColorHex($hex) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $this->SetFillColor($r, $g, $b);
    }

    function SetDrawColorHex($hex) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $this->SetDrawColor($r, $g, $b);
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1) {
        // Convert UTF-8 to ISO-8859-1 for FPDF
        $txt = mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8');
        parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln);
    }

    // Get the number of lines needed for MultiCell
    function GetMultiCellLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

$pdf = new CustomFPDF();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

// Approximate page break trigger (A4 height = 297mm, bottom margin = 10mm)
$pageBreakTrigger = 287; // 297 - 10

// Set page background color (#F9F5E8)
$pdf->SetFillColorHex('#F9F5E8');
$pdf->Rect(0, 0, 210, 297, 'F'); // A4: 210mm x 297mm

// Add logo at the top (centered)
$logoPath = '../image/ve.png'; // Adjusted path
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 80, 10, 50); // Centered (210-50)/2 = 80mm, 50mm wide
    $pdf->Ln(30); // Space after logo (logo height ~20mm + 10mm padding)
} else {
    error_log("export_pdf.php: Logo not found at $logoPath");
}

// --- Dynamic Title ---
$title = 'Liste des Réclamations';
$filters = [];
if (!empty($lieu)) {
    $filters[] = "Lieu: " . htmlspecialchars($lieu);
}
if (!empty($type_probleme)) {
    $filters[] = "Type: " . htmlspecialchars($type_probleme);
}
if (!empty($statut)) {
    $filters[] = "Statut: " . htmlspecialchars(ucfirst($statut));
}
if (!empty($filters)) {
    $title .= ' (' . implode(', ', $filters) . ')';
}

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColorHex('#2e7d32');
$pdf->MultiCell(0, 10, htmlspecialchars($title), 0, 'C');
$pdf->Ln(5);

// --- Table Styling ---
$columnWidths = [
    'Titre' => 40,
    'Description' => 60,
    'Lieu' => 30,
    'Type' => 30,
    'Statut' => 20
];

// Map database fields to table headers
$fieldMap = [
    'titre' => 'Titre',
    'description' => 'Description',
    'lieu' => 'Lieu',
    'type_probleme' => 'Type',
    'statut' => 'Statut'
];

// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColorHex('#F9F5E8');
$pdf->SetTextColorHex('#2e7d32');
$pdf->SetDrawColorHex('#4CAF50');
$pdf->SetLineWidth(0.2);

foreach ($columnWidths as $header => $width) {
    $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
}
$pdf->Ln();

// Table Body
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColorHex('#333333');
$pdf->SetFillColorHex('#FFFFFF');

if (empty($reclamations)) {
    $pdf->SetTextColorHex('#721c24');
    $pdf->MultiCell(180, 10, 'Aucune réclamation trouvée.', 1, 'C');
} else {
    foreach ($reclamations as $index => $r) {
        // Prepare fields
        $fields = [
            'titre' => $r['titre'] ?? '',
            'description' => $r['description'] ?? '',
            'lieu' => $r['lieu'] ?? '',
            'type_probleme' => $r['type_probleme'] ?? '',
            'statut' => ucfirst($r['statut'] ?? '')
        ];

        // Log row data for debugging
        error_log("export_pdf.php: Rendering row $index - Titre: {$fields['titre']}, Description: {$fields['description']}");

        // Calculate row height
        $lineHeight = 5; // Height per line
        $maxLines = 0;
        
        foreach ($fields as $key => $value) {
            $text = htmlspecialchars($value);
            $header = $fieldMap[$key];
            $width = $columnWidths[$header] - 2; // Account for padding
            $lines = $pdf->GetMultiCellLines($width, $text);
            $maxLines = max($maxLines, $lines);
        }
        $rowHeight = $maxLines * $lineHeight;

        // Check if row fits on current page
        if ($pdf->GetY() + $rowHeight > $pageBreakTrigger) {
            $pdf->AddPage();
            // Redraw background on new page
            $pdf->SetFillColorHex('#F9F5E8');
            $pdf->Rect(0, 0, 210, 297, 'F');
            // Redraw logo on new page
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 80, 10, 50);
                $pdf->Ln(30);
            }
            // Redraw table header on new page
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColorHex('#F9F5E8');
            $pdf->SetTextColorHex('#2e7d32');
            $pdf->SetDrawColorHex('#4CAF50');
            foreach ($columnWidths as $header => $width) {
                $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColorHex('#333333');
            $pdf->SetFillColorHex('#FFFFFF');
        }

        // Draw row borders with Cell
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        foreach ($fields as $key => $value) {
            $header = $fieldMap[$key];
            $pdf->Cell($columnWidths[$header], $rowHeight, '', 1, 0, 'L', true);
            $x += $columnWidths[$header];
            $pdf->SetXY($x, $y);
        }

        // Reset to start of row and render text with MultiCell
        $x = 10; // Start at left margin
        $y = $pdf->GetY();
        foreach ($fields as $key => $value) {
            $header = $fieldMap[$key];
            $pdf->SetXY($x, $y);
            $pdf->MultiCell($columnWidths[$header], $lineHeight, htmlspecialchars($value), 0, 'L', false);
            $x += $columnWidths[$header];
        }

        // Move to next row
        $pdf->SetXY(10, $y + $rowHeight);
    }
}

// Output the PDF
$filename = 'reclamations_' . date('Ymd_His') . '.pdf';
$pdf->Output('D', $filename);
exit;
?>