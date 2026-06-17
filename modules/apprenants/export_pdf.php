<?php
// modules/apprenants/export_pdf.php
// Tableau 100% auto-adaptatif : colonnes et lignes s'ajustent au contenu

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$db = getDB();

// ── Filtres ───────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$filtStat = $_GET['statut'] ?? '';
$filtFil  = $_GET['filiere'] ?? '';

$where  = ["1=1"];
$params = [];
if ($search) {
    $where[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR a.matricule LIKE ? OR a.lieu_naissance LIKE ?)";
    $params  = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]);
}
if ($filtStat) { $where[] = "a.statut = ?";     $params[] = $filtStat; }
if ($filtFil)  { $where[] = "a.filiere_id = ?"; $params[] = $filtFil;  }

$sql  = "SELECT a.*, f.nom AS filiere_nom
         FROM apprenants a
         LEFT JOIN filieres f ON a.filiere_id = f.id
         WHERE " . implode(' AND ', $where) . " ORDER BY COALESCE(f.nom, 'Sans filière'), a.nom, a.prenom";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$apprenants = $stmt->fetchAll();

$filieres        = $db->query('SELECT * FROM filieres ORDER BY nom')->fetchAll();
$selectedFiliere = null;
foreach ($filieres as $f) {
    if ($f['id'] == $filtFil) { $selectedFiliere = $f; break; }
}

// ── Constantes en-tête ───────────────────────────────────────
define('HEADER_H',   60);
define('HEADER_GAP', 13);
define('HEADER_TOP', HEADER_H + HEADER_GAP);

// ── Classe PDF avec tableau adaptatif ─────────────────────────
require_once __DIR__ . '/../../config/pdf.php';

class ApprenantsPDF extends PDF
{
    const FONT      = 'helvetica';
    const FONT_SZ   = 8;
    const PAD_X     = 2.5;   // padding horizontal interne (mm)
    const PAD_Y     = 1.5;   // padding vertical interne (mm)
    const LINE_MIN  = 6;     // hauteur minimale d'une ligne (mm)

    private string $headerImagePath = '';
    private bool $firstPageOnly = true;

    public function setHeaderImage(string $path): void {
        $this->headerImagePath = $path;
    }

    // Redessine l'en-tête image uniquement sur la première page
    public function Header(): void {        parent::Header();
        // Première page avec en-tête image
        if ($this->PageNo() === 1) {
            if ($this->headerImagePath && file_exists($this->headerImagePath)) {
                $pageW = $this->getPageWidth();
                $this->Image($this->headerImagePath, 10, 10, $pageW - 20, HEADER_H);
            }
        } else {
            // Pages suivantes : marges réduites (pas d'espace pour l'en-tête)
          if ($this->GetTopMargin() != 10) {
                $this->SetTopMargin(10);
            }
        }
    }

    /**
     * Mesure la largeur naturelle d'un texte (police courante).
     */
    private function mesureTexte(string $txt): float {
        return $this->GetStringWidth($txt) + self::PAD_X * 2;
    }

    /**
     * Divise un texte en plusieurs lignes selon une largeur maximale.
     * Retourne un tableau des lignes.
     */
    private function splitTextToSize(string $text, float $maxWidth): array {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            $lineWidth = $this->GetStringWidth($testLine);

            if ($lineWidth <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }

        if ($currentLine) {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Calcule la largeur optimale de chaque colonne en fonction
     * du texte réellement présent (en-têtes + toutes les lignes).
     * Puis ajuste pour tenir dans $maxTotal mm.
     */
    public function calculerLargeurs(array $headers, array $rows, float $maxTotal): array
    {
        $n   = count($headers);
        $min = array_fill(0, $n, 0);

        // Mesure des en-têtes en gras
        $this->SetFont(self::FONT, 'B', self::FONT_SZ);
        foreach ($headers as $i => $h) {
            $min[$i] = max($min[$i], $this->mesureTexte($h));
        }

        // Mesure des données
        $this->SetFont(self::FONT, '', self::FONT_SZ);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                if (!isset($min[$i])) continue;
                $min[$i] = max($min[$i], $this->mesureTexte((string)$cell));
            }
        }

        // Arrondir + 1 mm de sécurité
        $min = array_map(fn($v) => ceil($v) + 1, $min);
        $sum = array_sum($min);

        if ($sum <= $maxTotal) {
            // Distribuer l'espace libre proportionnellement
            $extra = $maxTotal - $sum;
            foreach ($min as $i => $v) {
                $min[$i] = round($v + ($v / $sum) * $extra, 1);
            }
        } else {
            // Réduire proportionnellement
            $ratio = $maxTotal / $sum;
            foreach ($min as $i => $v) {
                $min[$i] = round($v * $ratio, 1);
            }
        }

        return $min;
    }

    /**
     * Dessine une ligne de tableau en mode MultiCell :
     * - chaque cellule s'élargit sur plusieurs lignes si nécessaire
     * - toutes les cellules de la même ligne ont la MÊME hauteur
     *   (déterminée par la cellule avec le plus de contenu)
     */
    public function ligneTableau(
        array  $widths,
        array  $values,
        array  $aligns,
        bool   $fill,
        bool   $bold = false
    ): void {
        $this->SetFont(self::FONT, $bold ? 'B' : '', self::FONT_SZ);

        $xStart = $this->GetX();
        $yStart = $this->GetY();

        // ── Passe 1 : calculer la hauteur max de la ligne ──────
        $lineH = self::FONT_SZ * 0.352778 + 1.2; // hauteur d'une sous-ligne de texte
        $maxH  = self::LINE_MIN;
        $nbLignes = [];

        foreach ($values as $i => $val) {
            $innerW = $widths[$i] - self::PAD_X * 2;
            $lines  = $this->splitTextToSize((string)$val, $innerW);
            $nb     = count($lines);
            $nbLignes[$i] = $nb;
            $h = self::PAD_Y * 2 + $nb * $lineH;
            $maxH = max($maxH, $h);
        }

        // Vérifier si on dépasse la page
        if ($yStart + $maxH > $this->getPageHeight() - $this->getBreakMargin()) {
            $this->AddPage();
            $xStart = $this->GetX();
            $yStart = $this->GetY();
        }

        // ── Passe 2 : dessiner chaque cellule à la hauteur max ─
        $x = $xStart;
        foreach ($values as $i => $val) {
            $innerW = $widths[$i] - self::PAD_X * 2;
            $lines  = $this->splitTextToSize((string)$val, $innerW);
            $nb     = $nbLignes[$i];
            $align  = $aligns[$i] ?? 'L';

            // Fond
            if ($fill) {
                $this->Rect($x, $yStart, $widths[$i], $maxH, 'F');
            }
            // Bordure
            $this->SetLineWidth(0.2);
            $this->Rect($x, $yStart, $widths[$i], $maxH, 'D');

            // Centrage vertical du texte dans la cellule
            $textBlockH = $nb * $lineH;
            $yText      = $yStart + ($maxH - $textBlockH) / 2;

            foreach ($lines as $line) {
                $this->SetXY($x + self::PAD_X, $yText);
                $this->Cell($innerW, $lineH, $line, 0, 0, $align);
                $yText += $lineH;
            }

            $x += $widths[$i];
        }

        // Avancer le curseur après la ligne
        $this->SetXY($xStart, $yStart + $maxH);
    }
}

// ── Initialisation ────────────────────────────────────────────
// Orientation L (Paysage) pour maximiser l'espace horizontal
$pdf = new ApprenantsPDF('L', PDF_UNIT, 'A4');
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, HEADER_TOP, 10);
$pdf->SetAutoPageBreak(true, 15);

$headerImage = __DIR__ . '/../../assets/entete.PNG';
$pdf->setHeaderImage($headerImage);
$pdf->SetWatermarkImage($headerImage, 0.08);

$pdf->AddPage();
$pdf->SetY(HEADER_TOP);

// ── Titre ─────────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(31, 56, 100);
$titre = $selectedFiliere
    ? 'Liste des apprenants — ' . $selectedFiliere['nom']
    : 'Liste complète des apprenants';
$pdf->Cell(0, 8, $titre, 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 9);
$pdf->SetTextColor(107, 107, 107);
$pdf->Cell(0, 5,
    'Édité le ' . date('d/m/Y à H:i')
    . '   —   ' . count($apprenants) . ' apprenant(s)',
    0, 1, 'C');
$pdf->Ln(5);

// ── Définition des colonnes ───────────────────────────────────
$headers = ['Matricule', 'Nom',  'Prénom', 'Date Naiss.', 'Lieu naiss.', 'Filière', 'Niv.', 'Inscription', 'Statut'];
$aligns  = ['C',         'L',    'L',      'C',           'L',           'L',       'C',    'C',           'C'     ];

// ── Grouper les apprenants par filière ──────────────────────
$groupedByFil = [];
foreach ($apprenants as $a) {
    $filiere = $a['filiere_nom'] ?? 'Sans filière';
    if (!isset($groupedByFil[$filiere])) {
        $groupedByFil[$filiere] = [];
    }
    $groupedByFil[$filiere][] = $a;
}

// ── Calcul automatique des largeurs (sur tous les apprenants) ──
$largeurDispo = 277;
$rows = [];
foreach ($apprenants as $a) {
    $rows[] = [
        $a['matricule'],
        $a['nom'],
        $a['prenom'],
        $a['date_naissance']   ? date('d/m/Y', strtotime($a['date_naissance']))   : '—',
        $a['lieu_naissance']   ?? '—',
        $a['filiere_nom']      ?? '—',
        'N' . $a['niveau'],
        $a['date_inscription'] ? date('d/m/Y', strtotime($a['date_inscription'])) : '—',
        ucfirst($a['statut']),
    ];
}
$widths = $pdf->calculerLargeurs($headers, $rows, $largeurDispo);

// ── Affichage par filière ──────────────────────────────────
foreach ($groupedByFil as $filiere => $apprenantsList) {
    // Titre de section
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(31, 56, 100);
    $pdf->SetY($pdf->GetY() + 3);
    $pdf->Cell(0, 7, '► ' . $filiere . ' (' . count($apprenantsList) . ')', 0, 1, 'L');
    $pdf->SetY($pdf->GetY() + 2);

    // En-tête du tableau
    $pdf->SetFillColor(46, 117, 182);       // Bleu
    $pdf->SetTextColor(255, 255, 255);      // Blanc
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->ligneTableau($widths, $headers, array_fill(0, count($headers), 'C'), true, true);

    // Lignes de données pour cette filière
    $fill = false;
    foreach ($apprenantsList as $a) {
        $row = [
            $a['matricule'],
            $a['nom'],
            $a['prenom'],
            $a['date_naissance']   ? date('d/m/Y', strtotime($a['date_naissance']))   : '—',
            $a['lieu_naissance']   ?? '—',
            $a['filiere_nom']      ?? '—',
            'N' . $a['niveau'],
            $a['date_inscription'] ? date('d/m/Y', strtotime($a['date_inscription'])) : '—',
            ucfirst($a['statut']),
        ];

        if ($fill) {
            $pdf->SetFillColor(235, 243, 251); // Bleu très pâle
        } else {
            $pdf->SetFillColor(255, 255, 255); // Blanc
        }
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->ligneTableau($widths, $row, $aligns, $fill);
        $fill = !$fill;
    }

    $pdf->Ln(3);
}

// ── Ligne total ───────────────────────────────────────────────
$totalW = array_sum($widths);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(31, 56, 100);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetX(10);
$pdf->Cell($totalW, 7, 'TOTAL : ' . count($apprenants) . ' apprenant(s)', 1, 1, 'R', true);

// ── Pied de page ──────────────────────────────────────────────
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5,
    'Document généré par Secrétariat Numérique — Centre de Formation Professionnelle en Génie Informatique',
    0, 1, 'C');

// ── Téléchargement ────────────────────────────────────────────
$filename = $selectedFiliere
    ? 'Apprenants_' . str_replace(' ', '_', $selectedFiliere['nom']) . '.pdf'
    : 'Apprenants_liste_complete.pdf';

$pdf->Output($filename, 'D');