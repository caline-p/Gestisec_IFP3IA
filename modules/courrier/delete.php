<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT reference FROM courriers WHERE id=?"); $stmt->execute([$id]);
$c = $stmt->fetch();
if ($c) {
    $db->prepare("DELETE FROM courriers WHERE id=?")->execute([$id]);
    setFlash('success', "Courrier {$c['reference']} supprimé.");
} else {
    setFlash('error', 'Courrier introuvable.');
}
header('Location: index.php'); exit;
