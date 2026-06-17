<?php
// delete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT nom,prenom FROM apprenants WHERE id=?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if ($a) {
    $db->prepare("DELETE FROM apprenants WHERE id=?")->execute([$id]);
    setFlash('success', "Apprenant {$a['prenom']} {$a['nom']} supprimé.");
} else {
    setFlash('error', 'Apprenant introuvable.');
}
header('Location: index.php'); exit;
