<?php
header('Content-Type: text/plain; charset=utf-8');

$soffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
if (!file_exists($soffice)) {
    die("❌ LibreOffice non trouvé : $soffice\n");
}

// Copier le template pour test
$src = __DIR__ . '\\templates\\template_etudiants.docx';
if (!file_exists($src)) {
    die("❌ Template introuvable : $src\n");
}

$workDir = sys_get_temp_dir();
$tmpDocx = $workDir . '\\test_template.docx';
copy($src, $tmpDocx);

echo "1. Template copié : $tmpDocx\n";
echo "   Taille : " . filesize($tmpDocx) . " octets\n\n";

// Lancer la conversion
echo "2. Lancement de LibreOffice...\n";
$cmd = "\"$soffice\" --headless --convert-to pdf --outdir \"$workDir\" \"$tmpDocx\" 2>&1";
echo "   Commande : $cmd\n\n";

$output = [];
$rc = 0;
exec($cmd, $output, $rc);

echo "3. Code retour : $rc\n";
echo "4. Sortie :\n" . implode("\n", $output) . "\n\n";

$pdf = $workDir . '\\test_template.pdf';
if (file_exists($pdf)) {
    echo "✅ PDF généré : $pdf\n";
    echo "   Taille : " . filesize($pdf) . " octets\n";

    // Proposer le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="test_libreoffice.pdf"');
    readfile($pdf);
    @unlink($pdf);
} else {
    echo "❌ PDF non généré\n";
}

@unlink($tmpDocx);
