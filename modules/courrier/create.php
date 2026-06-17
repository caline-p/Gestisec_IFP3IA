<?php
// create.php — Nouveau courrier
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Nouveau courrier';
$activePage = 'courrier';
$breadcrumb = '<a href="index.php">Courrier</a> › Nouveau';
$db         = getDB();
$personnel  = $db->query("SELECT * FROM personnel WHERE statut='actif' ORDER BY nom")->fetchAll();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type        = $_POST['type'] ?? 'entrant';
    $objet       = trim($_POST['objet'] ?? '');
    $expediteur  = trim($_POST['expediteur'] ?? '');
    $destinataire= trim($_POST['destinataire'] ?? '');
    $date        = $_POST['date_courrier'] ?? date('Y-m-d');
    $dateRec     = $_POST['date_reception'] ?: null;
    $priorite    = $_POST['priorite'] ?? 'normale';
    $affecte     = $_POST['affecte_a'] ?: null;
    $observations= trim($_POST['observations'] ?? '');

    if (!$objet) $errors[] = "L'objet est obligatoire.";

    if (empty($errors)) {
        $ref = genRef('COU');
        $db->prepare("
            INSERT INTO courriers (reference,type,objet,expediteur,destinataire,date_courrier,date_reception,priorite,affecte_a,observations)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([$ref,$type,$objet,$expediteur,$destinataire,$date,$dateRec,$priorite,$affecte,$observations]);
        setFlash('success', "Courrier enregistré avec la référence $ref.");
        header('Location: index.php'); exit;
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:720px">
<div class="card">
  <div class="card-header"><h2>Enregistrer un nouveau courrier</h2></div>
  <div class="card-body">
    <?php if($errors): ?>
    <div style="background:#fce8e8;border-left:3px solid #c00;padding:12px 14px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00">
      <?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Type de courrier *</label>
          <select name="type" class="form-control" required>
            <option value="entrant" <?= ($_POST['type']??'entrant')==='entrant'?'selected':'' ?>>↓ Courrier entrant</option>
            <option value="sortant" <?= ($_POST['type']??'')==='sortant'?'selected':'' ?>>↑ Courrier sortant</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Priorité</label>
          <select name="priorite" class="form-control">
            <option value="normale"       <?= ($_POST['priorite']??'normale')==='normale'?'selected':'' ?>>Normale</option>
            <option value="urgente"       <?= ($_POST['priorite']??'')==='urgente'?'selected':'' ?>>Urgente</option>
            <option value="confidentielle"<?= ($_POST['priorite']??'')==='confidentielle'?'selected':'' ?>>Confidentielle</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Objet *</label>
        <input type="text" name="objet" class="form-control" value="<?= clean($_POST['objet']??'') ?>" required placeholder="Objet du courrier">
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Expéditeur</label>
          <input type="text" name="expediteur" class="form-control" value="<?= clean($_POST['expediteur']??'') ?>" placeholder="Nom ou organisation">
        </div>
        <div class="form-group">
          <label class="form-label">Destinataire</label>
          <input type="text" name="destinataire" class="form-control" value="<?= clean($_POST['destinataire']??'') ?>" placeholder="Nom ou service">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Date du courrier</label>
          <input type="date" name="date_courrier" class="form-control" value="<?= $_POST['date_courrier']??date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Date de réception</label>
          <input type="date" name="date_reception" class="form-control" value="<?= $_POST['date_reception']??'' ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Affecter à</label>
        <select name="affecte_a" class="form-control">
          <option value="">— Non affecté —</option>
          <?php foreach ($personnel as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($_POST['affecte_a']??'')==$p['id']?'selected':'' ?>>
            <?= clean($p['prenom'].' '.$p['nom']) ?> — <?= clean($p['poste']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Observations</label>
        <textarea name="observations" class="form-control"><?= clean($_POST['observations']??'') ?></textarea>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer le courrier</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
