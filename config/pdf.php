<?php
// config/pdf.php — Configuration et classe pour les PDF

require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

class PDF extends \TCPDF {
    protected string $watermarkImagePath = '';
    protected float $watermarkAlpha = 0.08;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        parent::__construct($orientation, $unit, $format);
    }

    public function setWatermarkImage(string $path, float $alpha = 0.08): void {
        $this->watermarkImagePath = $path;
        $this->watermarkAlpha = $alpha;
    }

    public function Header() {
        if ($this->watermarkImagePath && file_exists($this->watermarkImagePath)) {
            $this->SetAlpha($this->watermarkAlpha);
            $pageW = $this->getPageWidth();
            $pageH = $this->getPageHeight();
            $imgW  = $pageW * 0.7;
            $imgH  = $pageH * 0.7;
            $x     = ($pageW - $imgW) / 2;
            $y     = ($pageH - $imgH) / 2;
            $this->Image($this->watermarkImagePath, $x, $y, $imgW, $imgH, '', '', '', false, 300, '', false, false, 0, false, false, false);
            $this->SetAlpha(1);
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(107, 107, 107);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        $this->Cell(0, 10, '— Généré le ' . date('d/m/Y à H:i'), 0, 0, 'R');
    }
}

// Données d'établissement
define('ETABLISSEMENT_NOM', 'INSTITUT DE FORMATION PROFESSIONNELLE EN INGENIERIE INFORMATIQUE APPLIQUEE (IFP-3IA)');
define('ETABLISSEMENT_ADRESSE', 'DSCHANG, Cameroun');
define('ETABLISSEMENT_TEL', '+237 672051100');
define('ETABLISSEMENT_EMAIL', 'institut3ia.com');
