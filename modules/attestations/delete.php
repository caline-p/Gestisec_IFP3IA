<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$tab = in_array($_GET['tab'] ?? '', ['stagiaires']) ? 'stagiaires' : 'apprenants';

if (!$id) {
    setFlash('error', 'Attestation non trouvée.');
    header('Location: ' . APP_URL . '/modules/attestations/index.php?tab=' . $tab);
    exit;
}

$stmt = $db->prepare("SELECT id FROM attestation WHERE id=?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    setFlash('error', 'Attestation non trouvée.');
    header('Location: ' . APP_URL . '/modules/attestations/index.php?tab=' . $tab);
    exit;
}

// Supprimer la méta en premier (si pas de FK CASCADE)
$db->prepare("DELETE FROM attestation_meta WHERE attestation_id=?")->execute([$id]);
$db->prepare("DELETE FROM attestation WHERE id=?")->execute([$id]);

setFlash('success', 'Attestation supprimée avec succès !');
header('Location: ' . APP_URL . '/modules/attestations/index.php?tab=' . $tab);
exit;
