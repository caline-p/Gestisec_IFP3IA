<?php
// create.php — Nouveau paiement
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Nouveau paiement';
$activePage = 'paiements';
$breadcrumb = '<a href="index.php">Paiements</a> › Nouveau';
$db = getDB();
$apprenants = $db->query("SELECT a.*, f.nom AS filiere_nom, f.cout, COALESCE((SELECT SUM(montant) FROM paiements WHERE apprenant_id=a.id),0) AS total_paye FROM apprenants a LEFT JOIN filieres f ON a.filiere_id=f.id WHERE a.statut='inscrit' ORDER BY a.nom")->fetchAll();
$errors = []; $newId = null;

// Pré-sélection depuis impayés
$preselect = (int)($_GET['apprenant_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId  = (int)($_POST['apprenant_id'] ?? 0);
    $mont   = (float)($_POST['montant'] ?? 0);
    $mode   = $_POST['mode_paiement'] ?? 'especes';
    $date   = $_POST['date_paiement'] ?? date('Y-m-d');
    $mois   = trim($_POST['mois_concerne'] ?? '');
    $obs    = trim($_POST['observations'] ?? '');

    if (!$appId)  $errors[] = "Sélectionnez un apprenant.";
    if ($mont<=0) $errors[] = "Le montant doit être supérieur à 0.";

    if (empty($errors)) {
        $ref = genRef('PAI');
        $db->prepare("INSERT INTO paiements (reference,apprenant_id,montant,mode_paiement,date_paiement,mois_concerne,observations,enregistre_par)
            VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$ref,$appId,$mont,$mode,$date,$mois,$obs,$_SESSION['user_id']]);
        $newId = $db->lastInsertId();
        setFlash('success', "Paiement enregistré — Référence : $ref");
        header("Location: recu.php?id=$newId"); exit;
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:680px">
<div class="card">
  <div class="card-header"><h2>Enregistrer un paiement</h2></div>
  <div class="card-body">
    <?php if($errors): ?><div style="background:#fce8e8;border-left:3px solid #c00;padding:12px;border-radius:0 7px 7px 0;margin-bottom:16px;font-size:13px;color:#c00"><?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Apprenant *</label>
        <select name="apprenant_id" class="form-control" required id="sel_app" onchange="updateSolde(this)">
          <option value="">— Sélectionner un apprenant —</option>
          <?php foreach($apprenants as $a):
            $reste = $a['cout'] - $a['total_paye'];
          ?>
          <option value="<?= $a['id'] ?>"
            data-cout="<?= $a['cout'] ?>"
            data-paye="<?= $a['total_paye'] ?>"
            data-reste="<?= $reste ?>"
            <?= ($preselect==$a['id']||($_POST['apprenant_id']??0)==$a['id'])?'selected':'' ?>>
            <?= clean($a['nom'].' '.$a['prenom']) ?> — <?= clean($a['filiere_nom']??'') ?> | Reste: <?= number_format($reste,0,',',' ') ?> FCFA
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Solde info -->
      <div id="solde_info" style="display:none;background:var(--blue-pale);border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:13px">
        <div style="display:flex;justify-content:space-between">
          <span>Coût total : <strong id="info_cout"></strong></span>
          <span>Déjà payé : <strong style="color:var(--green)" id="info_paye"></strong></span>
          <span>Reste dû : <strong style="color:var(--red)" id="info_reste"></strong></span>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Montant encaissé (FCFA) *</label>
          <input type="number" name="montant" class="form-control" value="<?= $_POST['montant']??'' ?>" min="1" required placeholder="Ex : 75000">
        </div>
        <div class="form-group">
          <label class="form-label">Mode de paiement</label>
          <select name="mode_paiement" class="form-control">
            <?php foreach(['especes'=>'Espèces','mobile_money'=>'Mobile Money','virement'=>'Virement','cheque'=>'Chèque'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($_POST['mode_paiement']??'especes')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Date du paiement</label>
          <input type="date" name="date_paiement" class="form-control" value="<?= $_POST['date_paiement']??date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Mois concerné</label>
          <input type="text" name="mois_concerne" class="form-control" value="<?= clean($_POST['mois_concerne']??date('F Y')) ?>" placeholder="Ex : Avril 2026">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Observations</label>
        <textarea name="observations" class="form-control" style="min-height:60px"><?= clean($_POST['observations']??'') ?></textarea>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary">Enregistrer et imprimer le reçu</button>
        <a href="index.php" class="btn btn-outline">Annuler</a>
      </div>
    </form>
  </div>
</div>
</div>
<script>
function fmt(n){return new Intl.NumberFormat('fr-FR').format(n)+' FCFA';}
function updateSolde(sel){
  const opt=sel.options[sel.selectedIndex];
  const box=document.getElementById('solde_info');
  if(!opt.value){box.style.display='none';return;}
  document.getElementById('info_cout').textContent=fmt(opt.dataset.cout);
  document.getElementById('info_paye').textContent=fmt(opt.dataset.paye);
  document.getElementById('info_reste').textContent=fmt(opt.dataset.reste);
  box.style.display='block';
}
window.onload=()=>{const s=document.getElementById('sel_app');if(s.value)updateSolde(s);}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
