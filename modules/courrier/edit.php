<?php
// edit.php — Modifier un courrier
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM courriers WHERE id=?"); $stmt->execute([$id]);
$courrier = $stmt->fetch();
if (!$courrier) { setFlash('error','Courrier introuvable.'); header('Location: index.php'); exit; }

$pageTitle  = 'Modifier courrier';
$activePage = 'courrier';
$breadcrumb = '<a href="index.php">Courrier</a> › Modifier';
$personnel  = $db->query("SELECT * FROM personnel WHERE statut='actif' ORDER BY nom")->fetchAll();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objet        = trim($_POST['objet'] ?? '');
    $type         = $_POST['type'] ?? 'entrant';
    $expediteur   = trim($_POST['expediteur'] ?? '');
    $destinataire = trim($_POST['destinataire'] ?? '');
    $date         = $_POST['date_courrier'] ?? date('Y-m-d');
    $dateRec      = $_POST['date_reception'] ?: null;
    $priorite     = $_POST['priorite'] ?? 'normale';
    $statut       = $_POST['statut'] ?? 'recu';
    $affecte      = $_POST['affecte_a'] ?: null;
    $observations = trim($_POST['observations'] ?? '');

    if (!$objet) $errors[] = "L'objet est obligatoire.";

    if (empty($errors)) {
        $db->prepare("
            UPDATE courriers SET type=?,objet=?,expediteur=?,destinataire=?,
            date_courrier=?,date_reception=?,priorite=?,statut=?,affecte_a=?,observations=?
            WHERE id=?
        ")->execute([$type,$objet,$expediteur,$destinataire,$date,$dateRec,$priorite,$statut,$affecte,$observations,$id]);
        setFlash('success','Courrier modifié avec succès.');
        header('Location: index.php'); exit;
    }
    $courrier = array_merge($courrier,$_POST);
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:720px">
<div class="card">
  <div class="card-header">
    <h2>Modifier le courrier</h2>
    <code style="font-size:12px;color:var(--blue-mid)"><?= clean($courrier['reference']) ?></code>
  </div>
  <div class="card-body">
    <?php if($errors): ?>
    <div style="background:#fce8e8;border-left:3px solid #c00;padding:12px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00">
      <?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="type" class="form-control">
            <option value="entrant" <?= $courrier['type']==='entrant'?'selected':'' ?>>Entrant</option>
            <option value="sortant" <?= $courrier['type']==='sortant'?'selected':'' ?>>Sortant</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Priorité</label>
          <select name="priorite" class="form-control">
            <option value="normale"        <?= $courrier['priorite']==='normale'?'selected':'' ?>>Normale</option>
            <option value="urgente"        <?= $courrier['priorite']==='urgente'?'selected':'' ?>>Urgente</option>
            <option value="confidentielle" <?= $courrier['priorite']==='confidentielle'?'selected':'' ?>>Confidentielle</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-control">
            <option value="recu"          <?= $courrier['statut']==='recu'?'selected':'' ?>>Reçu</option>
            <option value="en_traitement" <?= $courrier['statut']==='en_traitement'?'selected':'' ?>>En traitement</option>
            <option value="traite"        <?= $courrier['statut']==='traite'?'selected':'' ?>>Traité</option>
            <option value="archive"       <?= $courrier['statut']==='archive'?'selected':'' ?>>Archivé</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Objet *</label>
        <input type="text" name="objet" class="form-control" value="<?= clean($courrier['objet']) ?>" required>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Expéditeur</label>
          <input type="text" name="expediteur" class="form-control" value="<?= clean($courrier['expediteur']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Destinataire</label>
          <input type="text" name="destinataire" class="form-control" value="<?= clean($courrier['destinataire']) ?>">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Date du courrier</label>
          <input type="date" name="date_courrier" class="form-control" value="<?= $courrier['date_courrier'] ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Date de réception</label>
          <input type="date" name="date_reception" class="form-control" value="<?= $courrier['date_reception'] ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Affecter à</label>
        <select name="affecte_a" class="form-control">
          <option value="">— Non affecté —</option>
          <?php foreach($personnel as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $courrier['affecte_a']==$p['id']?'selected':'' ?>>
            <?= clean($p['prenom'].' '.$p['nom']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Observations</label>
        <textarea name="observations" class="form-control"><?= clean($courrier['observations']) ?></textarea>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
