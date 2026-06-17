<?php
// delete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB(); $id=(int)($_GET['id']??0);
$stmt=$db->prepare("SELECT nom,prenom FROM personnel WHERE id=?");$stmt->execute([$id]);$p=$stmt->fetch();
if($p){$db->prepare("DELETE FROM personnel WHERE id=?")->execute([$id]);setFlash('success',"Agent {$p['prenom']} {$p['nom']} supprimé.");}
else{setFlash('error','Agent introuvable.');}
header('Location: index.php');exit;
