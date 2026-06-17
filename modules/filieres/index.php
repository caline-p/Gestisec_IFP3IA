<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Filières';
$activePage = 'filieres';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $nom   = trim($_POST['nom'] ?? '');
    $duree = (int)($_POST['duree'] ?? 0);
    $cout  = trim($_POST['cout'] ?? '');
    $cout  = str_replace([' ',','], ['','.'], $cout);

    if (!$code)  $errors[] = 'Le code de la filière est obligatoire.';
    if (!$nom)   $errors[] = 'Le nom de la filière est obligatoire.';
    if ($duree <= 0) $errors[] = 'La durée doit être un nombre positif.';
    if (!is_numeric($cout) || (float)$cout <= 0) $errors[] = 'Le coût doit être un montant valide.';

    if (empty($errors)) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM filieres WHERE code = ?');
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Ce code de filière existe déjà.';
        }
    }

    if (empty($errors)) {
        $db->prepare('INSERT INTO filieres (code, nom, duree, cout) VALUES (?, ?, ?, ?)')
           ->execute([$code, $nom, $duree, (float)$cout]);
        setFlash('success', "Filière $nom enregistrée avec succès.");
        header('Location: index.php');
        exit;
    }
}

$filieres = $db->query('SELECT * FROM filieres ORDER BY nom')->fetchAll();
?>

<div class="page-actions">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <h2 style="margin:0">Gestion des filières</h2>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2>Ajouter une filière</h2>
  </div>
  <div class="card-body">
    <?php if ($errors): ?>
    <div style="background:#fce8e8;border-left:3px solid #c00;padding:12px 14px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00">
      <?php foreach ($errors as $e): ?><div>• <?= clean($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Code <span style="color:red">*</span></label>
          <input type="text" name="code" class="form-control" value="<?= clean($_POST['code'] ?? '') ?>" placeholder="GL" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nom <span style="color:red">*</span></label>
          <input type="text" name="nom" class="form-control" value="<?= clean($_POST['nom'] ?? '') ?>" placeholder="Génie Logiciel" required>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Durée (mois) <span style="color:red">*</span></label>
          <input type="number" name="duree" class="form-control" min="1" value="<?= clean($_POST['duree'] ?? '24') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Coût <span style="color:red">*</span></label>
          <input type="text" name="cout" class="form-control" value="<?= clean($_POST['cout'] ?? '400000') ?>" placeholder="400000" required>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer la filière</button>
      </div>
    </form>
  </div>
</div>

<div class="card" style="margin-top:20px;">
  <div class="card-header">
    <h2>Filières enregistrées <span style="font-weight:400;color:var(--gray-mid);font-size:12px">(<?= count($filieres) ?>)</span></h2>
  </div>
  <div class="table-wrap">
    <?php if (empty($filieres)): ?>
    <div class="empty-state"><p>Aucune filière enregistrée.</p></div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Code</th><th>Nom</th><th>Durée</th><th>Coût</th></tr>
      </thead>
      <tbody>
        <?php foreach ($filieres as $f): ?>
        <tr>
          <td><?= clean($f['code']) ?></td>
          <td><?= clean($f['nom']) ?></td>
          <td><?= clean($f['duree']) ?> mois</td>
          <td><?= money((float)$f['cout']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>