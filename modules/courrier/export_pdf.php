<?php
// modules/courrier/export_pdf.php — Exporter le courrier en PDF

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();

// Filtres
$search   = trim($_GET['search'] ?? '');
$filtType = $_GET['type'] ?? '';
$filtStat = $_GET['statut'] ?? '';

$where = ["1=1"];
$params = [];
if ($search) { $where[] = "(c.objet LIKE ? OR c.reference LIKE ? OR c.expediteur LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($filtType) { $where[] = "c.type = ?"; $params[] = $filtType; }
if ($filtStat) { $where[] = "c.statut = ?"; $params[] = $filtStat; }

$sql = "SELECT c.*, p.nom AS agent_nom, p.prenom AS agent_prenom FROM courriers c LEFT JOIN personnel p ON c.affecte_a=p.id WHERE " . implode(' AND ', $where);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$courriers = $stmt->fetchAll();

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
$pdf->Cell(0, 8, 'Registre du Courrier', 0, 1, 'C');
$pdf->Ln(4);

// Tableau
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(46, 117, 182);
$pdf->SetTextColor(255, 255, 255);
$w = [18, 25, 30, 20, 25, 20, 17];
$headers = ['Ref', 'Type', 'Objet', 'Expéditeur', 'Affecté à', 'Date', 'Statut'];
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', TRUE);
}
$pdf->Ln();

// Contenu
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$fill = FALSE;
foreach ($courriers as $c) {
    $pdf->SetFillColor($fill ? 235 : 255, $fill ? 243 : 255, $fill ? 251 : 255);
    $pdf->Cell($w[0], 6, substr($c['reference'], 0, 10), 1, 0, 'L', $fill);
    $pdf->Cell($w[1], 6, strtoupper(substr($c['type'], 0, 8)), 1, 0, 'C', $fill);
    $pdf->Cell($w[2], 6, substr($c['objet'], 0, 18), 1, 0, 'L', $fill);
    $pdf->Cell($w[3], 6, substr($c['expediteur'], 0, 15), 1, 0, 'L', $fill);
    $agent = ($c['agent_nom'] && $c['agent_prenom']) ? substr($c['agent_nom'], 0, 7) . ' ' . substr($c['agent_prenom'], 0, 5) : '—';
    $pdf->Cell($w[4], 6, $agent, 1, 0, 'L', $fill);
    $pdf->Cell($w[5], 6, date('d/m/Y', strtotime($c['date_reception'])), 1, 0, 'C', $fill);
    $pdf->Cell($w[6], 6, strtoupper(substr($c['statut'], 0, 8)), 1, 1, 'C', $fill);
    $fill = !$fill;
}

// Total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(107, 107, 107);
$pdf->Ln(4);
$pdf->Cell(0, 5, 'Total: ' . count($courriers) . ' courrier(s)', 0, 1);

// Nom du fichier
$filename = 'Courrier_' . date('Y-m-d') . '.pdf';

// Télécharger
$pdf->Output($filename, 'D');
?>
