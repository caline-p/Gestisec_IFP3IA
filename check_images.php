<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = realpath(__DIR__ . '/assets/');
if (!$dir) { die("❌ Dossier /assets/ introuvable\n"); }

echo "=== Taille des images ===\n\n";

$total = 0;
$files = glob($dir . '/cert_*.png');
foreach ($files as $f) {
    $size = filesize($f);
    $total += $size;
    $sizeKb = round($size / 1024, 1);
    $sizeMb = round($size / 1024 / 1024, 2);
    $base64 = round($size * 1.37 / 1024, 1); // base64 ajoute ~37%
    $flag = ($size > 200 * 1024) ? '⚠️  LOURD' : '✅ OK';
    echo sprintf("  %-30s %8.1f Ko  (base64: %6.1f Ko)  %s\n",
        basename($f), $sizeKb, $base64, $flag);
}

$totalMb = round($total / 1024 / 1024, 2);
echo sprintf("\nTotal images : %d fichiers, %.2f Mo\n", count($files), $totalMb);

echo "\n=== Recommandations ===\n";
if ($total > 2 * 1024 * 1024) {
    echo "⚠️  Vos images pèsent plus de 2 Mo au total.\n";
    echo "   Dompdf va avoir du mal. Optimisez-les :\n";
    echo "   - Réduisez la résolution (max 150 dpi)\n";
    echo "   - Convertissez en JPG (logo, photos)\n";
    echo "   - Gardez PNG uniquement pour QR codes et bordures\n";
    echo "   - Visez < 100 Ko par image\n";
}
