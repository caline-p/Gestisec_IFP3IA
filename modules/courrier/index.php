<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Gestion du Courrier';
$activePage = 'courrier';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$search   = trim($_GET['search'] ?? '');
$filtType = $_GET['type'] ?? '';
$filtStat = $_GET['statut'] ?? '';
$perPage  = 15;
$page     = max(1,(int)($_GET['page']??1));
$offset   = ($page-1)*$perPage;

$where  = ["1=1"]; $params = [];
if ($search)   { $where[] = "(c.objet LIKE ? OR c.reference LIKE ? OR c.expediteur LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($filtType) { $where[] = "c.type = ?"; $params[] = $filtType; }
if ($filtStat) { $where[] = "c.statut = ?"; $params[] = $filtStat; }

$sql   = "SELECT c.*, p.nom AS agent_nom, p.prenom AS agent_prenom FROM courriers c LEFT JOIN personnel p ON c.affecte_a=p.id WHERE ".implode(' AND ',$where);
$total = $db->prepare("SELECT COUNT(*) FROM ($sql) x"); $total->execute($params); $total = $total->fetchColumn();
$pages = ceil($total/$perPage);
$stmt  = $db->prepare("$sql ORDER BY c.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$courriers = $stmt->fetchAll();
?>

<div class="page-actions">
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="text" name="search" class="search-input" placeholder="Rechercher (objet, référence, expéditeur)..." value="<?= clean($search) ?>" style="width:260px">
    <select name="type" class="form-control" style="width:auto">
      <option value="">Tous types</option>
      <option value="entrant"  <?= $filtType==='entrant'?'selected':'' ?>>Entrant</option>
      <option value="sortant"  <?= $filtType==='sortant'?'selected':'' ?>>Sortant</option>
    </select>
    <select name="statut" class="form-control" style="width:auto">
      <option value="">Tous statuts</option>
      <option value="recu"          <?= $filtStat==='recu'?'selected':'' ?>>Reçu</option>
      <option value="en_traitement" <?= $filtStat==='en_traitement'?'selected':'' ?>>En traitement</option>
      <option value="traite"        <?= $filtStat==='traite'?'selected':'' ?>>Traité</option>
      <option value="archive"       <?= $filtStat==='archive'?'selected':'' ?>>Archivé</option>
    </select>
    <button class="btn btn-outline btn-sm" type="submit">Filtrer</button>
    <?php if($search||$filtType||$filtStat): ?><a href="?" class="btn btn-sm" style="color:var(--gray-mid)">Effacer</a><?php endif; ?>
  </form>
  <a href="create.php" class="btn btn-primary">+ Nouveau courrier</a>
</div>

<div class="card">
  <div class="card-header">
    <h2>Registre du courrier <span style="font-weight:400;color:var(--gray-mid);font-size:12px">(<?= $total ?> entrée<?= $total>1?'s':'' ?>)</span></h2>
  </div>
  <div class="table-wrap">
  <?php if(empty($courriers)): ?>
    <div class="empty-state"><p>Aucun courrier enregistré.</p></div>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>Référence</th><th>Type</th><th>Objet</th><th>Expéditeur/Destinataire</th><th>Date</th><th>Priorité</th><th>Statut</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php
    $sMap = ['recu'=>'badge-blue','en_traitement'=>'badge-orange','traite'=>'badge-green','archive'=>'badge-gray'];
    $sLbl = ['recu'=>'Reçu','en_traitement'=>'En cours','traite'=>'Traité','archive'=>'Archivé'];
    $pMap = ['normale'=>'badge-gray','urgente'=>'badge-red','confidentielle'=>'badge-orange'];
    foreach ($courriers as $c): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($c['reference']) ?></code></td>
      <td><?= $c['type']==='entrant' ? '<span class="badge badge-blue">↓ Entrant</span>' : '<span class="badge badge-gray">↑ Sortant</span>' ?></td>
      <td style="max-width:220px"><div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= clean($c['objet']) ?>"><?= clean($c['objet']) ?></div></td>
      <td style="font-size:12px"><?= clean($c['type']==='entrant' ? $c['expediteur'] : $c['destinataire']) ?></td>
      <td style="font-size:12px"><?= dateFR($c['date_courrier']) ?></td>
      <td><span class="badge <?= $pMap[$c['priorite']] ?>"><?= ucfirst($c['priorite']) ?></span></td>
      <td><span class="badge <?= $sMap[$c['statut']] ?>"><?= $sLbl[$c['statut']] ?></span></td>
      <td>
        <div style="display:flex;gap:5px">
          <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2l3 3-7 7H1v-3L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
          </a>
          <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-icon" style="color:var(--red);border:1px solid var(--gray-light)"
             onclick="return confirm('Supprimer ce courrier ?')">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 3h9M5 3V2h3v1M4 3l.5 8h4l.5-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          </a>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
</div>

<?php if ($pages > 1): ?>
<div class="pagination">
  <?php for ($i=1;$i<=$pages;$i++): ?>
  <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($filtType) ?>&statut=<?= urlencode($filtStat) ?>"
     class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
