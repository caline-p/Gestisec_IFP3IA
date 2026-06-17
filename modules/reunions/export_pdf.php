<?php
// modules/reunions/export_pdf.php — Exporter les réunions en PDF

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();

// Récupérer toutes les réunions
$reunions = $db->query("SELECT * FROM reunions ORDER BY date_reunion DESC")->fetchAll();

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
$pdf->Cell(0, 8, 'Agenda des Réunions', 0, 1, 'C');
$pdf->Ln(4);

// Tableau
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(46, 117, 182);
$pdf->SetTextColor(255, 255, 255);
$w = [30, 25, 30, 25, 30];
$headers = ['Titre', 'Date', 'Lieu', 'Statut', 'Compte Rendu'];
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', TRUE);
}
$pdf->Ln();

// Contenu
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$fill = FALSE;
foreach ($reunions as $r) {
    $pdf->SetFillColor($fill ? 235 : 255, $fill ? 243 : 255, $fill ? 251 : 255);
    $pdf->Cell($w[0], 7, substr($r['titre'], 0, 20), 1, 0, 'L', $fill);
    $pdf->Cell($w[1], 7, date('d/m/Y', strtotime($r['date_reunion'])), 1, 0, 'C', $fill);
    $pdf->Cell($w[2], 7, substr($r['lieu'] ?? '—', 0, 18), 1, 0, 'L', $fill);
    $pdf->Cell($w[3], 7, ucfirst($r['statut']), 1, 0, 'C', $fill);
    $cr = !empty($r['compte_rendu']) ? '✓ Oui' : '— Non';
    $pdf->Cell($w[4], 7, $cr, 1, 1, 'C', $fill);
    $fill = !$fill;
}

// Total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(107, 107, 107);
$pdf->Ln(4);
$pdf->Cell(0, 5, 'Total: ' . count($reunions) . ' réunion(s)', 0, 1);

// Nom du fichier
$filename = 'Reunions_' . date('Y-m-d') . '.pdf';

// Télécharger
$pdf->Output($filename, 'D');
?>
