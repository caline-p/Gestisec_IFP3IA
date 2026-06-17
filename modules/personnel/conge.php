<?php
// conge.php — Gérer congés d'un agent
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
$id = (int)($_GET['id']??0);
$stmt=$db->prepare("SELECT * FROM personnel WHERE id=?");$stmt->execute([$id]);$agent=$stmt->fetch();
if(!$agent){setFlash('error','Agent introuvable.');header('Location: index.php');exit;}
$pageTitle='Congés — '.$agent['prenom'].' '.$agent['nom'];
$activePage='personnel';
$breadcrumb='<a href="index.php">Personnel</a> › Congés';
$errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $type=$_POST['type_conge']??'annuel';
    $deb=$_POST['date_debut']??'';$fin=$_POST['date_fin']??'';
    $motif=trim($_POST['motif']??'');
    if(!$deb||!$fin){$errors[]="Dates obligatoires.";}
    else{
        $nbj=(int)((strtotime($fin)-strtotime($deb))/86400)+1;
        if($nbj<=0)$errors[]="La date de fin doit être après la date de début.";
        elseif($type==='annuel'&&$nbj>$agent['solde_conge'])$errors[]="Solde insuffisant ({$agent['solde_conge']} jours disponibles).";
    }
    if(empty($errors)){
        $nbj=(int)((strtotime($fin)-strtotime($deb))/86400)+1;
        $db->prepare("INSERT INTO conges (personnel_id,type_conge,date_debut,date_fin,nb_jours,motif,statut) VALUES (?,?,?,?,?,?,'en_attente')")
           ->execute([$id,$type,$deb,$fin,$nbj,$motif]);
        if($type==='annuel'){$db->prepare("UPDATE personnel SET solde_conge=solde_conge-? WHERE id=?")->execute([$nbj,$id]);}
        setFlash('success',"Demande de congé enregistrée ($nbj jours).");
        header('Location: index.php');exit;
    }
}

// Historique congés
$conges=$db->prepare("SELECT * FROM conges WHERE personnel_id=? ORDER BY created_at DESC");
$conges->execute([$id]);$conges=$conges->fetchAll();
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:760px">
  <!-- Info agent -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center">
      <div>
        <div style="font-size:15px;font-weight:600;color:var(--blue-dark)"><?= clean($agent['prenom'].' '.$agent['nom']) ?></div>
        <div style="font-size:12px;color:var(--gray-mid)"><?= clean($agent['poste']) ?> — <?= strtoupper($agent['type_contrat']) ?></div>
      </div>
      <div style="text-align:center;background:var(--blue-pale);padding:12px 24px;border-radius:8px">
        <div style="font-size:28px;font-weight:700;color:<?= $agent['solde_conge']>5?'var(--green)':'var(--red)' ?>"><?= $agent['solde_conge'] ?></div>
        <div style="font-size:11px;color:var(--gray-mid)">jours de congé restants</div>
      </div>
    </div>
  </div>

  <!-- Formulaire -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><h2>Nouvelle demande de congé</h2></div>
    <div class="card-body">
      <?php if($errors): ?><div style="background:#fce8e8;border-left:3px solid #c00;padding:12px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00"><?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Type de congé</label>
            <select name="type_conge" class="form-control">
              <?php foreach(['annuel'=>'Congé annuel','maladie'=>'Congé maladie','maternite'=>'Congé maternité','autre'=>'Autre'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($_POST['type_conge']??'annuel')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Date de début *</label><input type="date" name="date_debut" class="form-control" value="<?= $_POST['date_debut']??'' ?>" required></div>
          <div class="form-group"><label class="form-label">Date de fin *</label><input type="date" name="date_fin" class="form-control" value="<?= $_POST['date_fin']??'' ?>" required></div>
        </div>
        <div class="form-group"><label class="form-label">Motif</label><textarea name="motif" class="form-control" style="min-height:70px"><?= clean($_POST['motif']??'') ?></textarea></div>
        <div style="display:flex;gap:10px">
          <button type="submit" class="btn btn-primary">Enregistrer la demande</button>
          <a href="index.php" class="btn btn-outline">Retour</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Historique -->
  <?php if(!empty($conges)): ?>
  <div class="card">
    <div class="card-header"><h2>Historique des congés</h2></div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Type</th><th>Du</th><th>Au</th><th>Nb jours</th><th>Motif</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach($conges as $c):
        $sm=['en_attente'=>'badge-orange','approuve'=>'badge-green','refuse'=>'badge-red'];
        $sl=['en_attente'=>'En attente','approuve'=>'Approuvé','refuse'=>'Refusé'];
      ?>
      <tr>
        <td><?= ucfirst(str_replace('_',' ',$c['type_conge'])) ?></td>
        <td><?= dateFR($c['date_debut']) ?></td>
        <td><?= dateFR($c['date_fin']) ?></td>
        <td style="font-weight:600"><?= $c['nb_jours'] ?> j</td>
        <td style="font-size:12px;color:var(--gray-mid)"><?= clean($c['motif']??'—') ?></td>
        <td><span class="badge <?= $sm[$c['statut']] ?>"><?= $sl[$c['statut']] ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
