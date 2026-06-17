<?php
// edit.php — Modifier agent
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
$id = (int)($_GET['id']??0);
$stmt=$db->prepare("SELECT * FROM personnel WHERE id=?");$stmt->execute([$id]);$agent=$stmt->fetch();
if(!$agent){setFlash('error','Agent introuvable.');header('Location: index.php');exit;}
$pageTitle='Modifier agent';$activePage='personnel';
$breadcrumb='<a href="index.php">Personnel</a> › Modifier';
$errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $nom=$_POST['nom']??'';$prenom=$_POST['prenom']??'';$poste=$_POST['poste']??'';
    $dept=$_POST['departement']??'';$contrat=$_POST['type_contrat']??'CDI';
    $tel=$_POST['telephone']??'';$email=$_POST['email']??'';
    $embauche=$_POST['date_embauche']?:null;$salaire=(float)($_POST['salaire']??0);
    $statut=$_POST['statut']??'actif';$solde=(int)($_POST['solde_conge']??30);
    if(!$nom)$errors[]="Nom obligatoire.";
    if(!$prenom)$errors[]="Prénom obligatoire.";
    if(empty($errors)){
        $db->prepare("UPDATE personnel SET nom=?,prenom=?,poste=?,departement=?,type_contrat=?,telephone=?,email=?,date_embauche=?,salaire=?,statut=?,solde_conge=? WHERE id=?")
           ->execute([$nom,$prenom,$poste,$dept,$contrat,$tel,$email,$embauche,$salaire,$statut,$solde,$id]);
        setFlash('success','Agent modifié.');header('Location: index.php');exit;
    }
    $agent=array_merge($agent,$_POST);
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:720px">
<div class="card">
  <div class="card-header"><h2>Modifier : <?= clean($agent['prenom'].' '.$agent['nom']) ?></h2><code style="font-size:12px;color:var(--blue-mid)"><?= clean($agent['matricule']) ?></code></div>
  <div class="card-body">
    <?php if($errors): ?><div style="background:#fce8e8;border-left:3px solid #c00;padding:12px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00"><?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-grid-2">
        <div class="form-group"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" value="<?= clean($agent['nom']) ?>" required></div>
        <div class="form-group"><label class="form-label">Prénom *</label><input type="text" name="prenom" class="form-control" value="<?= clean($agent['prenom']) ?>" required></div>
      </div>
      <div class="form-grid-2">
        <div class="form-group"><label class="form-label">Poste</label><input type="text" name="poste" class="form-control" value="<?= clean($agent['poste']) ?>"></div>
        <div class="form-group"><label class="form-label">Département</label><input type="text" name="departement" class="form-control" value="<?= clean($agent['departement']) ?>"></div>
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Contrat</label>
          <select name="type_contrat" class="form-control">
            <?php foreach(['CDI','CDD','vacataire','stage'] as $ct): ?>
            <option value="<?= $ct ?>" <?= $agent['type_contrat']===$ct?'selected':'' ?>><?= strtoupper($ct) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-control">
            <option value="actif"   <?= $agent['statut']==='actif'?'selected':'' ?>>Actif</option>
            <option value="inactif" <?= $agent['statut']==='inactif'?'selected':'' ?>>Inactif</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Solde congés (jours)</label><input type="number" name="solde_conge" class="form-control" value="<?= $agent['solde_conge'] ?>" min="0"></div>
      </div>
      <div class="form-grid-3">
        <div class="form-group"><label class="form-label">Date embauche</label><input type="date" name="date_embauche" class="form-control" value="<?= $agent['date_embauche'] ?>"></div>
        <div class="form-group"><label class="form-label">Salaire (FCFA)</label><input type="number" name="salaire" class="form-control" value="<?= $agent['salaire'] ?>"></div>
        <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control" value="<?= clean($agent['telephone']) ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= clean($agent['email']) ?>"></div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
