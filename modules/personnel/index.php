<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Personnel';
$activePage = 'personnel';
require_once __DIR__ . '/../../includes/header.php';

$db     = getDB();
$search = trim($_GET['search'] ?? '');
$filtSt = $_GET['statut'] ?? '';
$filtCt = $_GET['contrat'] ?? '';
$perPage= 15;
$page   = max(1,(int)($_GET['page']??1));
$offset = ($page-1)*$perPage;

$where = ["1=1"]; $params = [];
if ($search) { $where[] = "(nom LIKE ? OR prenom LIKE ? OR poste LIKE ? OR matricule LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%","%$search%"]); }
if ($filtSt) { $where[] = "statut = ?"; $params[] = $filtSt; }
if ($filtCt) { $where[] = "type_contrat = ?"; $params[] = $filtCt; }

$sql   = "SELECT * FROM personnel WHERE ".implode(' AND ',$where);
$total = $db->prepare("SELECT COUNT(*) FROM ($sql) x"); $total->execute($params); $total=$total->fetchColumn();
$pages = ceil($total/$perPage);
$stmt  = $db->prepare("$sql ORDER BY nom,prenom LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$personnel = $stmt->fetchAll();
?>

<div class="page-actions">
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="text" name="search" class="search-input" placeholder="Rechercher un agent..." value="<?= clean($search) ?>" style="width:220px">
    <select name="contrat" class="form-control" style="width:auto">
      <option value="">Tous contrats</option>
      <?php foreach(['CDI','CDD','vacataire','stage'] as $ct): ?>
      <option value="<?= $ct ?>" <?= $filtCt===$ct?'selected':'' ?>><?= strtoupper($ct) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="statut" class="form-control" style="width:auto">
      <option value="">Tous statuts</option>
      <option value="actif"   <?= $filtSt==='actif'?'selected':'' ?>>Actif</option>
      <option value="inactif" <?= $filtSt==='inactif'?'selected':'' ?>>Inactif</option>
    </select>
    <button class="btn btn-outline btn-sm" type="submit">Filtrer</button>
    <?php if($search||$filtSt||$filtCt): ?><a href="?" class="btn btn-sm" style="color:var(--gray-mid)">Effacer</a><?php endif; ?>
  </form>
  <a href="create.php" class="btn btn-primary">+ Nouveau agent</a>
</div>

<div class="card">
  <div class="card-header">
    <h2>Liste du personnel <span style="font-weight:400;color:var(--gray-mid);font-size:12px">(<?= $total ?> agent<?= $total>1?'s':'' ?>)</span></h2>
  </div>
  <div class="table-wrap">
  <?php if(empty($personnel)): ?>
    <div class="empty-state"><p>Aucun agent enregistré.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Matricule</th><th>Nom & Prénom</th><th>Poste</th><th>Département</th><th>Contrat</th><th>Téléphone</th><th>Solde congés</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($personnel as $p): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($p['matricule']) ?></code></td>
      <td>
        <div style="font-weight:500"><?= clean($p['nom'].' '.$p['prenom']) ?></div>
        <?php if($p['email']): ?><div style="font-size:11px;color:var(--gray-mid)"><?= clean($p['email']) ?></div><?php endif; ?>
      </td>
      <td><?= clean($p['poste']) ?></td>
      <td><?= clean($p['departement'] ?? '—') ?></td>
      <td><span class="badge badge-blue"><?= strtoupper($p['type_contrat']) ?></span></td>
      <td><?= clean($p['telephone'] ?? '—') ?></td>
      <td>
        <span style="font-weight:600;color:<?= $p['solde_conge']>5?'var(--green)':'var(--red)' ?>">
          <?= $p['solde_conge'] ?> j
        </span>
      </td>
      <td><span class="badge <?= $p['statut']==='actif'?'badge-green':'badge-gray' ?>"><?= ucfirst($p['statut']) ?></span></td>
      <td>
        <div style="display:flex;gap:5px">
          <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2l3 3-7 7H1v-3L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
          </a>
          <a href="conge.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Gérer congé" style="color:var(--orange);border-color:var(--orange)">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><rect x="1" y="3" width="11" height="9" rx="1.5" stroke="currentColor" stroke-width="1.3"/><line x1="1" y1="6" x2="12" y2="6" stroke="currentColor" stroke-width="1.2"/><line x1="4.5" y1="1" x2="4.5" y2="5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><line x1="8.5" y1="1" x2="8.5" y2="5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          </a>
          <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-icon" style="color:var(--red);border:1px solid var(--gray-light)"
             onclick="return confirm('Supprimer cet agent ?')">
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

<?php if($pages>1): ?>
<div class="pagination">
  <?php for($i=1;$i<=$pages;$i++): ?>
  <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($filtSt) ?>&contrat=<?= urlencode($filtCt) ?>"
     class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
