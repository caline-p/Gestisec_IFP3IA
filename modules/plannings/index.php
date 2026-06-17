<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Plannings';
$activePage = 'plannings';
require_once __DIR__ . '/../../includes/header.php';
$db = getDB();

// Traitement formulaire
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $titre  = trim($_POST['titre'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $type   = $_POST['type'] ?? 'rendez_vous';
    $deb    = $_POST['date_debut'] ?? '';
    $fin    = $_POST['date_fin'] ?: null;
    $lieu   = trim($_POST['lieu'] ?? '');
    $part   = trim($_POST['participant'] ?? '');
    $statut = $_POST['statut'] ?? 'planifie';

    if (!$titre) $errors[] = "Le titre est obligatoire.";
    if (!$deb)   $errors[] = "La date de début est obligatoire.";

    if (empty($errors)) {
        if ($action === 'edit' && ($eid = (int)($_POST['edit_id']??0))) {
            $db->prepare("UPDATE plannings SET titre=?,description=?,type=?,date_debut=?,date_fin=?,lieu=?,participant=?,statut=? WHERE id=?")
               ->execute([$titre,$desc,$type,$deb,$fin,$lieu,$part,$statut,$eid]);
            setFlash('success','Événement modifié.');
        } else {
            $db->prepare("INSERT INTO plannings (titre,description,type,date_debut,date_fin,lieu,participant,statut) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$titre,$desc,$type,$deb,$fin,$lieu,$part,$statut]);
            setFlash('success','Événement ajouté au planning.');
        }
        header('Location: index.php'); exit;
    }
}

// Suppression
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM plannings WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','Événement supprimé.');
    header('Location: index.php'); exit;
}

// Édition
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt=$db->prepare("SELECT * FROM plannings WHERE id=?");$stmt->execute([(int)$_GET['edit']]);
    $editItem=$stmt->fetch();
}

$mois  = (int)($_GET['mois']??date('n'));
$annee = (int)($_GET['annee']??date('Y'));
$prev  = $mois==1 ? ['mois'=>12,'annee'=>$annee-1] : ['mois'=>$mois-1,'annee'=>$annee];
$next  = $mois==12? ['mois'=>1,'annee'=>$annee+1] : ['mois'=>$mois+1,'annee'=>$annee];
$events= $db->prepare("SELECT * FROM plannings WHERE MONTH(date_debut)=? AND YEAR(date_debut)=? ORDER BY date_debut");
$events->execute([$mois,$annee]); $events=$events->fetchAll();
$upcoming=$db->query("SELECT * FROM plannings WHERE date_debut>=NOW() ORDER BY date_debut LIMIT 8")->fetchAll();
$moisNoms=['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$typeColors=['rendez_vous'=>'#2E75B6','reunion'=>'#1E7145','deplacement'=>'#C55A11','echeance'=>'#C00000'];
$typeLabels=['rendez_vous'=>'Rendez-vous','reunion'=>'Réunion','deplacement'=>'Déplacement','echeance'=>'Échéance'];
?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px">

  <!-- CALENDRIER + LISTE -->
  <div>
    <!-- Navigation mois -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h2 style="font-size:16px;font-weight:600;color:var(--blue-dark)"><?= $moisNoms[$mois] ?> <?= $annee ?></h2>
      <div style="display:flex;gap:8px">
        <a href="?mois=<?= $prev['mois'] ?>&annee=<?= $prev['annee'] ?>" class="btn btn-outline btn-sm">← Précédent</a>
        <a href="?" class="btn btn-outline btn-sm">Aujourd'hui</a>
        <a href="?mois=<?= $next['mois'] ?>&annee=<?= $next['annee'] ?>" class="btn btn-outline btn-sm">Suivant →</a>
      </div>
    </div>

    <!-- Événements du mois -->
    <div class="card" style="margin-bottom:14px">
      <div class="card-header">
        <h2>Événements — <?= $moisNoms[$mois] ?> <?= $annee ?> <span style="font-weight:400;color:var(--gray-mid);font-size:12px">(<?= count($events) ?> événement<?= count($events)>1?'s':'' ?>)</span></h2>
      </div>
      <?php if(empty($events)): ?>
        <div class="empty-state" style="padding:30px"><p>Aucun événement ce mois-ci.</p></div>
      <?php else: ?>
      <div class="table-wrap">
      <table>
        <thead><tr><th>Date & Heure</th><th>Titre</th><th>Type</th><th>Lieu</th><th>Participant</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($events as $e):
          $sc=['planifie'=>'badge-blue','confirme'=>'badge-green','annule'=>'badge-red','effectue'=>'badge-gray'];
          $sl=['planifie'=>'Planifié','confirme'=>'Confirmé','annule'=>'Annulé','effectue'=>'Effectué'];
        ?>
        <tr>
          <td>
            <div style="font-weight:600;color:var(--blue-dark)"><?= date('d/m',strtotime($e['date_debut'])) ?></div>
            <div style="font-size:11px;color:var(--gray-mid)"><?= date('H:i',strtotime($e['date_debut'])) ?></div>
          </td>
          <td style="font-weight:500"><?= clean($e['titre']) ?></td>
          <td><span style="background:<?= $typeColors[$e['type']] ?>20;color:<?= $typeColors[$e['type']] ?>;font-size:11px;padding:3px 8px;border-radius:5px;font-weight:500"><?= $typeLabels[$e['type']] ?></span></td>
          <td style="font-size:12px"><?= clean($e['lieu']??'—') ?></td>
          <td style="font-size:12px"><?= clean($e['participant']??'—') ?></td>
          <td><span class="badge <?= $sc[$e['statut']] ?>"><?= $sl[$e['statut']] ?></span></td>
          <td>
            <div style="display:flex;gap:5px">
              <a href="?edit=<?= $e['id'] ?>&mois=<?= $mois ?>&annee=<?= $annee ?>" class="btn btn-outline btn-sm btn-icon">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2l3 3-7 7H1v-3L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
              </a>
              <a href="?delete=<?= $e['id'] ?>&mois=<?= $mois ?>&annee=<?= $annee ?>" class="btn btn-sm btn-icon" style="color:var(--red);border:1px solid var(--gray-light)" onclick="return confirm('Supprimer ?')">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 3h9M5 3V2h3v1M4 3l.5 8h4l.5-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- FORMULAIRE LATÉRAL -->
  <div>
    <div class="card" style="margin-bottom:14px">
      <div class="card-header"><h2><?= $editItem ? 'Modifier l\'événement' : 'Ajouter un événement' ?></h2></div>
      <div class="card-body">
        <?php if($errors): ?><div style="background:#fce8e8;border-left:3px solid #c00;padding:10px;border-radius:0 6px 6px 0;margin-bottom:12px;font-size:12.5px;color:#c00"><?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="action" value="<?= $editItem?'edit':'create' ?>">
          <?php if($editItem): ?><input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>"><?php endif; ?>
          <div class="form-group">
            <label class="form-label">Titre *</label>
            <input type="text" name="titre" class="form-control" value="<?= clean($editItem['titre']??($_POST['titre']??'')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
              <?php foreach($typeLabels as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($editItem['type']??$_POST['type']??'rendez_vous')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date & heure début *</label>
            <input type="datetime-local" name="date_debut" class="form-control" value="<?= $editItem?date('Y-m-d\TH:i',strtotime($editItem['date_debut'])):($_POST['date_debut']??'') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Date & heure fin</label>
            <input type="datetime-local" name="date_fin" class="form-control" value="<?= $editItem&&$editItem['date_fin']?date('Y-m-d\TH:i',strtotime($editItem['date_fin'])):($_POST['date_fin']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Lieu</label>
            <input type="text" name="lieu" class="form-control" value="<?= clean($editItem['lieu']??($_POST['lieu']??'')) ?>" placeholder="Bureau, salle...">
          </div>
          <div class="form-group">
            <label class="form-label">Participant</label>
            <input type="text" name="participant" class="form-control" value="<?= clean($editItem['participant']??($_POST['participant']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-control">
              <?php foreach(['planifie'=>'Planifié','confirme'=>'Confirmé','annule'=>'Annulé','effectue'=>'Effectué'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($editItem['statut']??'planifie')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" style="min-height:60px"><?= clean($editItem['description']??($_POST['description']??'')) ?></textarea>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $editItem?'Modifier':'Ajouter' ?></button>
            <?php if($editItem): ?><a href="?" class="btn btn-outline btn-sm">Annuler</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Prochains événements -->
    <div class="card">
      <div class="card-header"><h2>Prochains événements</h2></div>
      <?php if(empty($upcoming)): ?>
        <div class="empty-state" style="padding:20px"><p>Aucun événement prévu.</p></div>
      <?php else: ?>
      <?php foreach($upcoming as $u): ?>
      <div style="padding:10px 14px;border-bottom:1px solid var(--gray-light);display:flex;gap:10px;align-items:flex-start">
        <div style="min-width:36px;text-align:center;background:var(--blue-pale);border-radius:6px;padding:4px">
          <div style="font-size:16px;font-weight:700;color:var(--blue-dark)"><?= date('d',strtotime($u['date_debut'])) ?></div>
          <div style="font-size:10px;color:var(--gray-mid)"><?= date('M',strtotime($u['date_debut'])) ?></div>
        </div>
        <div>
          <div style="font-size:13px;font-weight:500"><?= clean($u['titre']) ?></div>
          <div style="font-size:11px;color:var(--gray-mid)"><?= date('H:i',strtotime($u['date_debut'])) ?><?= $u['lieu']?' · '.clean($u['lieu']):'' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
