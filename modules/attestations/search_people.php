<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();
$q = trim($_GET['q'] ?? '');
header('Content-Type: application/json; charset=utf-8');
if ($q === '') { echo json_encode([]); exit; }

$stmt = $db->prepare("SELECT a.id, a.nom, a.prenom, a.date_naissance, a.lieu_naissance, f.nom AS filiere_nom
    FROM apprenants a
    LEFT JOIN filieres f ON a.filiere_id = f.id
    WHERE a.nom LIKE ? OR a.prenom LIKE ? OR CONCAT(a.nom,' ',a.prenom) LIKE ?
    ORDER BY a.nom, a.prenom LIMIT 20");
$like = "%$q%";
$stmt->execute([$like, $like, $like]);
$rows = $stmt->fetchAll();
echo json_encode($rows, JSON_UNESCAPED_UNICODE);

?>
