<?php
// edit.php — Modifier un apprenant
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$apprenant = $db->prepare("SELECT * FROM apprenants WHERE id=?");
$apprenant->execute([$id]);
$apprenant = $apprenant->fetch();
if (!$apprenant) { setFlash('error','Apprenant introuvable.'); header('Location: index.php'); exit; }

$pageTitle  = 'Modifier apprenant';
$activePage = 'apprenants';
$breadcrumb = '<a href="index.php">Apprenants</a> › Modifier';
$filieres   = $db->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom           = trim($_POST['nom'] ?? '');
    $prenom        = trim($_POST['prenom'] ?? '');
    $ddn           = $_POST['date_naissance'] ?: null;
    $lieuNaissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe          = $_POST['sexe'] ?? 'M';
    $tel           = trim($_POST['telephone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $adresse       = trim($_POST['adresse'] ?? '');
    $filiere       = $_POST['filiere_id'] ?? null;
    $niveau        = $_POST['niveau'] ?? '1';
    $statut        = $_POST['statut'] ?? 'inscrit';

    if (!$nom)    $errors[] = 'Le nom est obligatoire.';
    if (!$prenom) $errors[] = 'Le prénom est obligatoire.';

    if (empty($errors)) {
        $db->prepare("
            UPDATE apprenants SET nom=?,prenom=?,date_naissance=?,lieu_naissance=?,sexe=?,telephone=?,
            email=?,adresse=?,filiere_id=?,niveau=?,statut=? WHERE id=?
        ")->execute([$nom,$prenom,$ddn,$lieuNaissance?:null,$sexe,$tel,$email,$adresse,$filiere,$niveau,$statut,$id]);
        setFlash('success', "Apprenant modifié avec succès.");
        header('Location: index.php');
        exit;
    }
    $apprenant = array_merge($apprenant, $_POST);
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:760px">
<div class="card">
  <div class="card-header">
    <h2>Modifier : <?= clean($apprenant['prenom'].' '.$apprenant['nom']) ?></h2>
    <code style="font-size:12px;color:var(--blue-mid)"><?= clean($apprenant['matricule']) ?></code>
  </div>
  <div class="card-body">
    <?php if ($errors): ?>
    <div style="background:#fce8e8;border-left:3px solid #c00;padding:12px 14px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00">
      <?php foreach ($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Nom *</label>
          <input type="text" name="nom" class="form-control" value="<?= clean($apprenant['nom']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Prénom *</label>
          <input type="text" name="prenom" class="form-control" value="<?= clean($apprenant['prenom']) ?>" required>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Date de naissance</label>
          <input type="date" name="date_naissance" class="form-control" value="<?= $apprenant['date_naissance'] ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Lieu de naissance</label>
          <input type="text" name="lieu_naissance" class="form-control" value="<?= clean($apprenant['lieu_naissance'] ?? '') ?>">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Sexe</label>
          <select name="sexe" class="form-control">
            <option value="M" <?= $apprenant['sexe']==='M'?'selected':'' ?>>Masculin</option>
            <option value="F" <?= $apprenant['sexe']==='F'?'selected':'' ?>>Féminin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="text" name="telephone" class="form-control" value="<?= clean($apprenant['telephone']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= clean($apprenant['email']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Adresse</label>
        <input type="text" name="adresse" class="form-control" value="<?= clean($apprenant['adresse']) ?>">
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Filière</label>
          <select name="filiere_id" class="form-control">
            <option value="">— Choisir —</option>
            <?php foreach ($filieres as $f): ?>
            <option value="<?= $f['id'] ?>" <?= $apprenant['filiere_id']==$f['id']?'selected':'' ?>><?= clean($f['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Niveau</label>
          <select name="niveau" class="form-control">
            <?php foreach(['1','2','3'] as $n): ?>
            <option value="<?= $n ?>" <?= $apprenant['niveau']==$n?'selected':'' ?>>Niveau <?= $n ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-control">
            <?php foreach(['inscrit','suspendu','diplome','abandonne'] as $s): ?>
            <option value="<?= $s ?>" <?= $apprenant['statut']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
