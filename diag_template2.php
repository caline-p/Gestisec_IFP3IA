<?php
header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/templates/template_etudiants.docx';
if (!file_exists($file)) { echo "❌ Introuvable : $file"; exit; }

$zip = new ZipArchive();
$zip->open($file);
$content = $zip->getFromName('word/document.xml');
$zip->close();

echo "=== Recherche des balises ===\n\n";

// 1. Recherche classique
preg_match_all('/\{\{[^}]+\}\}/', $content, $m1);
echo "Balises {{...}} complètes : " . count($m1[0]) . "\n";
foreach (array_unique($m1[0]) as $b) echo "  ✅ $b\n";

if (empty($m1[0])) {
    echo "\n⚠️ Aucune balise complète trouvée ! Test de fragmentation :\n\n";

    // 2. Cherche le début {{NOM_
    $debut = '{{NOM_';
    $pos = 0;
    $i = 0;
    while (($pos = strpos($content, $debut, $pos)) !== false) {
        $sample = substr($content, $pos, 150);
        // Nettoyer les balises XML pour lisibilité
        $sample = preg_replace('/<[^>]+>/', '|', $sample);
        $sample = preg_replace('/\s+/', ' ', $sample);
        echo "Fragment #$i à la position $pos :\n";
        echo "  " . substr($sample, 0, 120) . "\n\n";
        $pos += strlen($debut);
        $i++;
        if ($i > 5) break;
    }
}

echo "\n=== Texte visible du document (1er 800 chars) ===\n";
$text = preg_replace('/<[^>]+>/', ' ', $content);
$text = preg_replace('/\s+/', ' ', $text);
echo substr(trim($text), 0, 800) . "\n";
