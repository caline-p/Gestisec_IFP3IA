<?php
// create.php — Nouvel agent
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Nouvel agent';
$activePage = 'personnel';
$breadcrumb = '<a href="index.php">Personnel</a> › Nouveau';
$db = getDB(); $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $poste    = trim($_POST['poste'] ?? '');
    $dept     = trim($_POST['departement'] ?? '');
    $contrat  = $_POST['type_contrat'] ?? 'CDI';
    $tel      = trim($_POST['telephone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $embauche = $_POST['date_embauche'] ?: null;
    $salaire  = (float)($_POST['salaire'] ?? 0);

    if (!$nom)   $errors[] = "Le nom est obligatoire.";
    if (!$prenom)$errors[] = "Le prénom est obligatoire.";
    if (!$poste) $errors[] = "Le poste est obligatoire.";

    if (empty($errors)) {
        $annee = date('Y');
        $count = $db->query("SELECT COUNT(*)+1 FROM personnel WHERE YEAR(created_at)=$annee")->fetchColumn();
        $mat   = 'PERS-'.$annee.'-'.str_pad($count,4,'0',STR_PAD_LEFT);
        $db->prepare("INSERT INTO personnel (matricule,nom,prenom,poste,departement,type_contrat,telephone,email,date_embauche,salaire)
            VALUES (?,?,?,?,?,?,?,?,?,?)")
           ->execute([$mat,$nom,$prenom,$poste,$dept,$contrat,$tel,$email,$embauche,$salaire]);
        setFlash('success',"Agent $prenom $nom enregistré ($mat).");
        header('Location: index.php'); exit;
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:720px">
<div class="card">
  <div class="card-header"><h2>Enregistrer un nouvel agent</h2></div>
  <div class="card-body">
    <?php if($errors): ?><div style="background:#fce8e8;border-left:3px solid #c00;padding:12px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00"><?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-grid-2">
        <div class="form-group"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" value="<?= clean($_POST['nom']??'') ?>" required></div>
        <div class="form-group"><label class="form-label">Prénom *</label><input type="text" name="prenom" class="form-control" value="<?= clean($_POST['prenom']??'') ?>" required></div>
      </div>
      <div class="form-grid-2">
        <div class="form-group"><label class="form-label">Poste *</label><input type="text" name="poste" class="form-control" value="<?= clean($_POST['poste']??'') ?>" required placeholder="Ex: Formateur, Comptable..."></div>
        <div class="form-group"><label class="form-label">Département</label><input type="text" name="departement" class="form-control" value="<?= clean($_POST['departement']??'') ?>" placeholder="Ex: Pédagogie, Administration..."></div>
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Type de contrat</label>
          <select name="type_contrat" class="form-control">
            <?php foreach(['CDI','CDD','vacataire','stage'] as $ct): ?>
            <option value="<?= $ct ?>" <?= ($_POST['type_contrat']??'CDI')===$ct?'selected':'' ?>><?= strtoupper($ct) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Date d'embauche</label><input type="date" name="date_embauche" class="form-control" value="<?= $_POST['date_embauche']??'' ?>"></div>
        <div class="form-group"><label class="form-label">Salaire (FCFA)</label><input type="number" name="salaire" class="form-control" value="<?= $_POST['salaire']??0 ?>" min="0"></div>
      </div>
      <div class="form-grid-2">
        <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control" value="<?= clean($_POST['telephone']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= clean($_POST['email']??'') ?>"></div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer l'agent</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
