<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Nouvel apprenant';
$activePage = 'apprenants';
$breadcrumb = '<a href="index.php">Apprenants</a> › Nouveau';

$db       = getDB();
$filieres = $db->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom           = trim($_POST['nom'] ?? '');
    $prenom        = trim($_POST['prenom'] ?? '');
    $ddn           = $_POST['date_naissance'] ?? null;
    $lieuNaissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe          = $_POST['sexe'] ?? 'M';
    $tel           = trim($_POST['telephone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $adresse       = trim($_POST['adresse'] ?? '');
    $filiere       = $_POST['filiere_id'] ?? null;
    $niveau        = $_POST['niveau'] ?? '1';
    $dateIns       = $_POST['date_inscription'] ?? date('Y-m-d');

    if (!$nom)    $errors[] = 'Le nom est obligatoire.';
    if (!$prenom) $errors[] = 'Le prénom est obligatoire.';
    if (!$filiere) $errors[] = 'La filière est obligatoire.';

    if (empty($errors)) {
        // Générer matricule
        $annee = date('Y');
        $filiereData = $db->prepare("SELECT code FROM filieres WHERE id=?");
        $filiereData->execute([$filiere]);
        $code = $filiereData->fetch()['code'] ?? 'XX';
        $stmt = $db->prepare("SELECT COUNT(*)+1 FROM apprenants WHERE YEAR(created_at)=? AND filiere_id=?");
        $stmt->execute([$annee, $filiere]);
        $count = $stmt->fetchColumn();
        $matricule = 'CM-3IA-' . date('y') . $code . str_pad($count, 4, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO apprenants (matricule,nom,prenom,date_naissance,lieu_naissance,sexe,telephone,email,adresse,filiere_id,niveau,date_inscription)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$matricule,$nom,$prenom,$ddn?:null,$lieuNaissance?:null,$sexe,$tel,$email,$adresse,$filiere,$niveau,$dateIns]);
        setFlash('success', "Apprenant $prenom $nom enregistré avec le matricule $matricule.");
        header('Location: index.php');
        exit;
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="max-width:760px">
<div class="card">
  <div class="card-header">
    <h2>Enregistrer un nouvel apprenant</h2>
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
          <label class="form-label">Nom <span style="color:red">*</span></label>
          <input type="text" name="nom" class="form-control" value="<?= clean($_POST['nom']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Prénom <span style="color:red">*</span></label>
          <input type="text" name="prenom" class="form-control" value="<?= clean($_POST['prenom']??'') ?>" required>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Date de naissance</label>
          <input type="date" name="date_naissance" class="form-control" value="<?= $_POST['date_naissance']??'' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Lieu de naissance</label>
          <input type="text" name="lieu_naissance" class="form-control" value="<?= clean($_POST['lieu_naissance']??'') ?>">
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Sexe</label>
          <select name="sexe" class="form-control">
            <option value="M" <?= ($_POST['sexe']??'')==='M'?'selected':'' ?>>Masculin</option>
            <option value="F" <?= ($_POST['sexe']??'')==='F'?'selected':'' ?>>Féminin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="text" name="telephone" class="form-control" placeholder="6XX XXX XXX" value="<?= clean($_POST['telephone']??'') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= clean($_POST['email']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Adresse</label>
        <input type="text" name="adresse" class="form-control" value="<?= clean($_POST['adresse']??'') ?>">
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Filière <span style="color:red">*</span></label>
          <select name="filiere_id" class="form-control" required>
            <option value="">— Choisir —</option>
            <?php foreach ($filieres as $f): ?>
            <option value="<?= $f['id'] ?>" <?= ($_POST['filiere_id']??'')==$f['id']?'selected':'' ?>>
              <?= clean($f['nom']) ?> — <?= money((float)$f['cout']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Niveau</label>
          <select name="niveau" class="form-control">
            <option value="1" <?= ($_POST['niveau']??'1')==='1'?'selected':'' ?>>Niveau 1</option>
            <option value="2" <?= ($_POST['niveau']??'')==='2'?'selected':'' ?>>Niveau 2</option>
            <option value="3" <?= ($_POST['niveau']??'')==='3'?'selected':'' ?>>Niveau 3</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Date d'inscription</label>
          <input type="date" name="date_inscription" class="form-control" value="<?= $_POST['date_inscription']??date('Y-m-d') ?>">
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer l'apprenant</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
