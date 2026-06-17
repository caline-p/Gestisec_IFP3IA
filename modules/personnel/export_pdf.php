<?php
// modules/personnel/export_pdf.php — Exporter le personnel en PDF

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();

// Filtres
$search = trim($_GET['search'] ?? '');
$filtSt = $_GET['statut'] ?? '';
$filtCt = $_GET['contrat'] ?? '';

$where = ["1=1"];
$params = [];
if ($search) { $where[] = "(nom LIKE ? OR prenom LIKE ? OR poste LIKE ? OR matricule LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]); }
if ($filtSt) { $where[] = "statut = ?"; $params[] = $filtSt; }
if ($filtCt) { $where[] = "type_contrat = ?"; $params[] = $filtCt; }

$sql = "SELECT * FROM personnel WHERE " . implode(' AND ', $where);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$personnel = $stmt->fetchAll();

// Créer le PDF
require_once __DIR__ . '/../../config/pdf.php';
$pdf = new PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 45, 10);
$pdf->SetAutoPageBreak(TRUE, 15);

$headerImage = __DIR__ . '/../../assets/entete.PNG';
$pdf->setWatermarkImage($headerImage, 0.08);

// Créer la première page
$pdf->AddPage();

// Ajouter l'image d'en-tête
$headerImage = __DIR__ . '/../../assets/entete.PNG';
if (file_exists($headerImage)) {
    $pdf->Image($headerImage, 10, 10, 190, 32);
}

// Repositionner après l'en-tête
$pdf->SetY(45);
$pdf->SetFont('helvetica', '', 10);

// Titre
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(31, 56, 100);
$pdf->Cell(0, 8, 'Liste du Personnel', 0, 1, 'C');
$pdf->Ln(4);

// Tableau
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(46, 117, 182);
$pdf->SetTextColor(255, 255, 255);
$w = [25, 25, 25, 20, 20, 20, 20];
$headers = ['Matricule', 'Nom', 'Prénom', 'Poste', 'Contrat', 'Statut', 'Téléphone'];
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', TRUE);
}
$pdf->Ln();

// Contenu
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$fill = FALSE;
foreach ($personnel as $p) {
    $pdf->SetFillColor($fill ? 235 : 255, $fill ? 243 : 255, $fill ? 251 : 255);
    $pdf->Cell($w[0], 6, $p['matricule'], 1, 0, 'L', $fill);
    $pdf->Cell($w[1], 6, substr($p['nom'], 0, 15), 1, 0, 'L', $fill);
    $pdf->Cell($w[2], 6, substr($p['prenom'], 0, 15), 1, 0, 'L', $fill);
    $pdf->Cell($w[3], 6, substr($p['poste'], 0, 12), 1, 0, 'L', $fill);
    $pdf->Cell($w[4], 6, strtoupper(substr($p['type_contrat'], 0, 8)), 1, 0, 'C', $fill);
    $pdf->Cell($w[5], 6, ucfirst($p['statut']), 1, 0, 'C', $fill);
    $pdf->Cell($w[6], 6, substr($p['telephone'] ?? '—', 0, 12), 1, 1, 'L', $fill);
    $fill = !$fill;
}

// Total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(107, 107, 107);
$pdf->Ln(4);
$pdf->Cell(0, 5, 'Total: ' . count($personnel) . ' agent(s)', 0, 1);

// Nom du fichier
$filename = 'Personnel_' . date('Y-m-d') . '.pdf';

// Télécharger
$pdf->Output($filename, 'D');
?>
