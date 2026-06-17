<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Modifier Attestation';
$activePage = 'attestations';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Attestation non trouvée.');
    header('Location: ' . APP_URL . '/modules/attestations/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM attestation WHERE id=?");
$stmt->execute([$id]);
$att = $stmt->fetch();
if (!$att) {
    setFlash('error', 'Attestation non trouvée.');
    header('Location: ' . APP_URL . '/modules/attestations/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $date_birth  = trim($_POST['date_birth'] ?? '');
    $place_birth = trim($_POST['place_birth'] ?? '');
    $specialty   = trim($_POST['specialty'] ?? '');
    $start_date  = trim($_POST['start_date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '');
    $origin      = trim($_POST['origin'] ?? '');

    $errors = [];
    if (!$name) $errors[] = "Le nom est requis.";
    if (!$date_birth) $errors[] = "La date de naissance est requise.";
    if (!$specialty) $errors[] = "La spécialité est requise.";

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE attestation SET name=?, date_birth=?, place_birth=?, specialty=?, start_date=?, end_date=?, origin=? WHERE id=?");
        $stmt->execute([
            $name,
            $date_birth ?: null,
            $place_birth ?: null,
            $specialty,
            $start_date ?: null,
            $end_date ?: null,
            $origin ?: null,
            $id
        ]);

        setFlash('success', 'Attestation mise à jour avec succès !');
        header('Location: ' . APP_URL . '/modules/attestations/index.php');
        exit;
    } else {
        $att = array_merge($att, [
            'name' => $name,
            'date_birth' => $date_birth,
            'place_birth' => $place_birth,
            'specialty' => $specialty,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'origin' => $origin
        ]);
    }
}
?>

<div style="max-width:900px">
<div class="card">
  <div class="card-header">
    <h2 class="card-title">Modifier Attestation</h2>
  </div>
  <div class="card-body">
    <?php if (!empty($errors)): ?>
    <div class="flash flash-error" style="display:block">
      <strong>Veuillez corriger les erreurs suivantes :</strong>
      <?php foreach ($errors as $err): ?>
      <div>&bull; <?= clean($err) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label>Nom complet *</label>
          <input type="text" name="name" class="form-control" value="<?= clean($att['name'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>Date de naissance *</label>
          <input type="date" name="date_birth" class="form-control" value="<?= clean($att['date_birth'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>Lieu de naissance</label>
          <input type="text" name="place_birth" class="form-control" value="<?= clean($att['place_birth'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Spécialité *</label>
          <input type="text" name="specialty" class="form-control" value="<?= clean($att['specialty'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label>Date de début</label>
          <input type="date" name="start_date" class="form-control" value="<?= clean($att['start_date'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Date de fin</label>
          <input type="date" name="end_date" class="form-control" value="<?= clean($att['end_date'] ?? '') ?>">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Origine / Remarques</label>
          <textarea name="origin" class="form-control" rows="3"><?= clean($att['origin'] ?? '') ?></textarea>
        </div>
      </div>

      <div style="margin-top:24px;display:flex;gap:8px;justify-content:flex-end">
        <a href="index.php" class="btn btn-outline">Annuler</a>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
      </div>
    </form>
  </div>
</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
