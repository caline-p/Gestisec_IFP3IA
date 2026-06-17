<?php
// modules/paiements/export_pdf.php — Exporter les paiements en PDF

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();

// Filtres
$search  = trim($_GET['search'] ?? '');
$filtMod = $_GET['mode'] ?? '';
$filtImp = $_GET['filtre'] ?? '';

$where = ["1=1"];
$params = [];
if ($search) { $where[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR p.reference LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($filtMod) { $where[] = "p.mode_paiement = ?"; $params[] = $filtMod; }

$sql = "SELECT p.*, a.nom, a.prenom, a.matricule, f.nom AS filiere_nom FROM paiements p JOIN apprenants a ON p.apprenant_id=a.id LEFT JOIN filieres f ON a.filiere_id=f.id WHERE " . implode(' AND ', $where);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll();

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
$pdf->Cell(0, 8, 'Registre des Paiements', 0, 1, 'C');
$pdf->Ln(4);

// Tableau
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(46, 117, 182);
$pdf->SetTextColor(255, 255, 255);
$w = [18, 20, 20, 25, 20, 25, 20, 18];
$headers = ['Ref', 'Matricule', 'Nom', 'Prénom', 'Montant', 'Date', 'Mode', 'Filière'];
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', TRUE);
}
$pdf->Ln();

// Contenu
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$fill = FALSE;
$total = 0;
foreach ($paiements as $p) {
    $pdf->SetFillColor($fill ? 235 : 255, $fill ? 243 : 255, $fill ? 251 : 255);
    $pdf->Cell($w[0], 6, substr($p['reference'], 0, 8), 1, 0, 'L', $fill);
    $pdf->Cell($w[1], 6, $p['matricule'], 1, 0, 'L', $fill);
    $pdf->Cell($w[2], 6, substr($p['nom'], 0, 12), 1, 0, 'L', $fill);
    $pdf->Cell($w[3], 6, substr($p['prenom'], 0, 12), 1, 0, 'L', $fill);
    $pdf->Cell($w[4], 6, number_format($p['montant'], 0, ',', ' '), 1, 0, 'R', $fill);
    $pdf->Cell($w[5], 6, date('d/m/Y', strtotime($p['date_paiement'])), 1, 0, 'C', $fill);
    $pdf->Cell($w[6], 6, substr($p['mode_paiement'], 0, 8), 1, 0, 'C', $fill);
    $pdf->Cell($w[7], 6, substr($p['filiere_nom'] ?? '—', 0, 8), 1, 1, 'L', $fill);
    $total += $p['montant'];
    $fill = !$fill;
}

// Total
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(31, 56, 100);
$pdf->Cell(array_sum(array_slice($w, 0, 4)), 6, 'TOTAL:', 1, 0, 'R');
$pdf->Cell($w[4], 6, number_format($total, 0, ',', ' '), 1, 0, 'R');
$pdf->Cell(array_sum(array_slice($w, 5)), 6, '', 1, 1);

// Nom du fichier
$filename = 'Paiements_' . date('Y-m-d') . '.pdf';

// Télécharger
$pdf->Output($filename, 'D');
?>
