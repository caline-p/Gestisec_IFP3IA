<?php
// delete.php — Paiements
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db=(getDB());$id=(int)($_GET['id']??0);
$stmt=$db->prepare("SELECT reference FROM paiements WHERE id=?");$stmt->execute([$id]);$p=$stmt->fetch();
if($p){$db->prepare("DELETE FROM paiements WHERE id=?")->execute([$id]);setFlash('success',"Paiement {$p['reference']} supprimé.");}
else{setFlash('error','Paiement introuvable.');}
header('Location: index.php');exit;
