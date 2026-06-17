<?php
// ============================================================
// config/database.php — Connexion à la base de données
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'secretariat_cfp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',      'GestiSec IFP-3IA');
define('APP_FULL_NAME', 'Institut de Formation Professionnelle en Ingénierie Informatique Appliquée');
define('APP_SHORT',     'IFP-3IA');
define('APP_VERSION',   '2.0');
define('APP_URL',       'http://localhost/secretariat_ifp3ia');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:Arial;padding:20px;background:#fee;border:1px solid #c00;border-radius:8px;margin:20px;">
                <strong>Erreur de connexion :</strong> ' . $e->getMessage() . '
                <br><small>Vérifiez que MySQL est démarré et que la base <b>' . DB_NAME . '</b> existe.</small>
                </div>');
        }
    }
    return $pdo;
}


