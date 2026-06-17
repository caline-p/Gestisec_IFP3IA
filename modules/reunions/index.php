<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Réunions';
$activePage = 'reunions';
require_once __DIR__ . '/../../includes/header.php';
$db = getDB(); $errors = [];

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    requireCsrfToken();
    $db->prepare("DELETE FROM reunions WHERE id=?")->execute([(int)$_POST['delete_id']]);
    setFlash('success', 'Réunion supprimée.');
    header('Location: index.php');
    exit;
}

// Edition
$editItem = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM reunions WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editItem = $s->fetch();
}

// Sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    requireCsrfToken();
    $titre = trim($_POST['titre'] ?? '');
    $date  = $_POST['date_reunion'] ?? '';
    $lieu  = trim($_POST['lieu'] ?? '');
    $odj   = trim($_POST['ordre_du_jour'] ?? '');
    $cr    = trim($_POST['compte_rendu'] ?? '');
    $statut = $_POST['statut'] ?? 'planifiee';

    if (!$titre) {
        $errors[] = "Titre obligatoire.";
    }
    if (!$date) {
        $errors[] = "Date obligatoire.";
    }

    if (empty($errors)) {
        if (!empty($_POST['edit_id'])) {
            $db->prepare("UPDATE reunions SET titre=?,date_reunion=?,lieu=?,ordre_du_jour=?,compte_rendu=?,statut=? WHERE id=?")
               ->execute([$titre, $date, $lieu, $odj, $cr, $statut, (int)$_POST['edit_id']]);
            setFlash('success', 'Réunion modifiée.');
        } else {
            $db->prepare("INSERT INTO reunions (titre,date_reunion,lieu,ordre_du_jour,statut) VALUES (?,?,?,?,?)")
               ->execute([$titre, $date, $lieu, $odj, $statut]);
            setFlash('success', 'Réunion enregistrée.');
        }
        header('Location: index.php');
        exit;
    }
}

$reunions=$db->query("SELECT * FROM reunions ORDER BY date_reunion DESC")->fetchAll();
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px">

  <div>
    <div class="card">
      <div class="card-header"><h2>Liste des réunions</h2></div>
      <?php if(empty($reunions)): ?>
        <div class="empty-state" style="padding:40px"><p>Aucune réunion enregistrée.</p></div>
      <?php else: ?>
      <div class="table-wrap">
      <table>
        <thead><tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Statut</th><th>Compte rendu</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($reunions as $r):
          $sm=['planifiee'=>'badge-blue','tenue'=>'badge-green','annulee'=>'badge-red'];
          $sl=['planifiee'=>'Planifiée','tenue'=>'Tenue','annulee'=>'Annulée'];
        ?>
        <tr>
          <td style="font-weight:500"><?= clean($r['titre']) ?></td>
          <td><?= dateFR($r['date_reunion']) ?><div style="font-size:11px;color:var(--gray-mid)"><?= date('H:i',strtotime($r['date_reunion'])) ?></div></td>
          <td style="font-size:12px"><?= clean($r['lieu']??'—') ?></td>
          <td><span class="badge <?= $sm[$r['statut']] ?>"><?= $sl[$r['statut']] ?></span></td>
          <td style="font-size:12px;max-width:160px">
            <?php if($r['compte_rendu']): ?>
              <span style="color:var(--green)">✓ Disponible</span>
            <?php else: ?>
              <span style="color:var(--gray-mid)">— Non saisi</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:5px;align-items:center">
              <a href="?edit=<?= $r['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2l3 3-7 7H1v-3L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
              </a>
              <form method="POST" style="display:inline;margin:0">
                <?= csrfInput() ?>
                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-icon" style="color:var(--red);border:1px solid var(--gray-light);background:none;padding:6px 8px" onclick="return confirm('Supprimer cette réunion ?')" title="Supprimer">
                  <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 3h9M5 3V2h3v1M4 3l.5 8h4l.5-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </button>
              </form>
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

  <!-- Formulaire -->
  <div class="card">
    <div class="card-header"><h2><?= $editItem?'Modifier la réunion':'Planifier une réunion' ?></h2></div>
    <div class="card-body">
      <?php if($errors): ?><div style="background:#fce8e8;border-left:3px solid #c00;padding:10px;border-radius:0 6px 6px 0;margin-bottom:12px;font-size:12.5px;color:#c00"><?php foreach($errors as $e): ?><div>• <?= $e ?></div><?php endforeach; ?></div><?php endif; ?>
      <form method="POST">
        <?php if($editItem): ?><input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>"><?php endif; ?>
        <div class="form-group"><label class="form-label">Titre *</label><input type="text" name="titre" class="form-control" value="<?= clean($editItem['titre']??'') ?>" required></div>
        <div class="form-group"><label class="form-label">Date & heure *</label><input type="datetime-local" name="date_reunion" class="form-control" value="<?= $editItem?date('Y-m-d\TH:i',strtotime($editItem['date_reunion'])):'' ?>" required></div>
        <div class="form-group"><label class="form-label">Lieu</label><input type="text" name="lieu" class="form-control" value="<?= clean($editItem['lieu']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Statut</label>
          <select name="statut" class="form-control">
            <?php foreach(['planifiee'=>'Planifiée','tenue'=>'Tenue','annulee'=>'Annulée'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($editItem['statut']??'planifiee')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Ordre du jour</label><textarea name="ordre_du_jour" class="form-control" style="min-height:80px"><?= clean($editItem['ordre_du_jour']??'') ?></textarea></div>
        <div class="form-group"><label class="form-label">Compte rendu</label><textarea name="compte_rendu" class="form-control" style="min-height:80px"><?= clean($editItem['compte_rendu']??'') ?></textarea></div>
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary btn-sm"><?= $editItem?'Modifier':'Enregistrer' ?></button>
          <?php if($editItem): ?><a href="?" class="btn btn-outline btn-sm">Annuler</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
