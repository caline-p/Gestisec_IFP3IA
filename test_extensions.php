<?php
echo "<h2>Test des extensions PHP</h2>";
echo "zip : " . (extension_loaded('zip') ? '✅ chargé' : '❌ MANQUANT') . "<br>";
echo "gd  : " . (extension_loaded('gd')  ? '✅ chargé' : '❌ MANQUANT') . "<br>";

echo "<h3>php.ini utilisé :</h3>";
echo php_ini_loaded_file();
