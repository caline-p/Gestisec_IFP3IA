<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Apprenants';
$activePage = 'apprenants';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Filtres
$search   = trim($_GET['search'] ?? '');
$filtStat = $_GET['statut'] ?? '';
$filtFil  = $_GET['filiere'] ?? '';
$isPrint  = isset($_GET['print']);

// Pagination (only for non-print)
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ["1=1"];
$params = [];
if ($search) { $where[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR a.matricule LIKE ? OR a.lieu_naissance LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); }
if ($filtStat){ $where[] = "a.statut = ?"; $params[] = $filtStat; }
if ($filtFil) { $where[] = "a.filiere_id = ?"; $params[] = $filtFil; }

$sql   = "SELECT a.*, f.nom AS filiere_nom FROM apprenants a LEFT JOIN filieres f ON a.filiere_id=f.id WHERE ".implode(' AND ',$where);
$total = $db->prepare("SELECT COUNT(*) FROM ($sql) x"); $total->execute($params); $total = $total->fetchColumn();
$pages = ceil($total / $perPage);

// Fetch data
if ($isPrint) {
    // For print: fetch all records
    $stmt = $db->prepare("$sql ORDER BY COALESCE(f.nom, 'Sans filière'), a.nom, a.prenom");
    $stmt->execute($params);
    $apprenants = $stmt->fetchAll();
} else {
    // For screen: paginated
    $stmt = $db->prepare("$sql ORDER BY a.nom, a.prenom LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $apprenants = $stmt->fetchAll();
}

$filieres = $db->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();
$selectedFiliere = null;
foreach ($filieres as $f) {
  if ($f['id'] == $filtFil) { $selectedFiliere = $f; break; }
}

// Group by filiere for print
$groupedByFil = [];
if ($isPrint) {
    foreach ($apprenants as $a) {
        $filiere = $a['filiere_nom'] ?? 'Sans filière';
        if (!isset($groupedByFil[$filiere])) {
            $groupedByFil[$filiere] = [];
        }
        $groupedByFil[$filiere][] = $a;
    }
}
?>

<style>
@media print {
  .sidebar, .topbar, .no-print { display: none !important; }
  .main { margin-left: 0 !important; }
  body { background: #fff !important; }
}

<?php if ($isPrint): ?>
.print-layout { display: block !important; }
.screen-layout { display: none !important; }
<?php else: ?>
.print-layout { display: none !important; }
<?php endif; ?>

.print-header {
  text-align: center;
  margin-bottom: 20px;
}

.print-header img {
  max-width: 100%;
  height: auto;
}

.print-title {
  font-size: 18px;
  font-weight: bold;
  color: #1f3864;
  margin: 10px 0;
}

.print-subtitle {
  font-size: 12px;
  font-style: italic;
  color: #6b6b6b;
  margin-bottom: 20px;
}

.print-title {
  font-size: 18px;
  font-weight: bold;
  color: #1f3864;
  margin: 10px 0;
}

.print-subtitle {
  font-size: 12px;
  font-style: italic;
  color: #6b6b6b;
  margin-bottom: 20px;
}

.print-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.print-table th,
.print-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
  font-size: 10px;
}

.print-table th {
  background-color: #2e75b6;
  color: white;
  text-align: center;
}

.print-table tr:nth-child(even) {
  background-color: #ebf3fb;
}

.print-table tr:nth-child(odd) {
  background-color: #ffffff;
}

.print-section-title {
  font-size: 14px;
  font-weight: bold;
  color: #1f3864;
  margin: 20px 0 10px 0;
  padding: 5px 0;
  border-bottom: 1px solid #ccc;
}

.print-total {
  text-align: right;
  font-weight: bold;
  background-color: #1f3864;
  color: white;
  padding: 10px;
  margin: 20px 0;
}

.print-footer {
  text-align: center;
  font-size: 10px;
  font-style: italic;
  color: #969696;
  margin-top: 30px;
}
</style>

<div class="screen-layout">
<div class="page-actions no-print">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="text" name="search" class="search-input" placeholder="Rechercher un apprenant..." value="<?= clean($search) ?>" style="width:240px">
      <select name="filiere" class="form-control" style="width:auto">
        <option value="">Toutes les spécialités</option>
        <?php foreach ($filieres as $f): ?>
        <option value="<?= $f['id'] ?>" <?= $filtFil==$f['id']?'selected':'' ?>><?= clean($f['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="statut" class="form-control" style="width:auto">
        <option value="">Tous statuts</option>
        <option value="inscrit"   <?= $filtStat==='inscrit'?'selected':'' ?>>Inscrit</option>
        <option value="suspendu"  <?= $filtStat==='suspendu'?'selected':'' ?>>Suspendu</option>
        <option value="diplome"   <?= $filtStat==='diplome'?'selected':'' ?>>Diplômé</option>
        <option value="abandonne" <?= $filtStat==='abandonne'?'selected':'' ?>>Abandonné</option>
      </select>
      <button class="btn btn-outline btn-sm" type="submit">Filtrer</button>
      <?php if($search||$filtStat||$filtFil): ?>
      <a href="?" class="btn btn-sm" style="color:var(--gray-mid)">Effacer</a>
      <?php endif; ?>
    </form>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <a href="export_pdf.php?filiere=<?= urlencode($filtFil) ?>&statut=<?= urlencode($filtStat) ?>&search=<?= urlencode($search) ?>" class="btn btn-outline btn-sm">📄 Télécharger PDF</a>
    <a href="?print=1&filiere=<?= urlencode($filtFil) ?>&statut=<?= urlencode($filtStat) ?>&search=<?= urlencode($search) ?>" class="btn btn-outline btn-sm" onclick="window.open(this.href, '_blank'); return false;">🖨 Imprimer</a>
    <a href="create.php" class="btn btn-primary">+ Nouvel apprenant</a>
  </div>
</div>

<div class="no-print" style="margin-bottom:14px; display:flex;flex-wrap:wrap;gap:8px">
  <a href="index.php" class="btn btn-sm <?= !$filtFil ? 'btn-primary' : 'btn-outline' ?>">Tous les apprenants</a>
  <?php foreach ($filieres as $f): ?>
  <a href="?filiere=<?= $f['id'] ?>" class="btn btn-sm <?= $filtFil==$f['id'] ? 'btn-primary' : 'btn-outline' ?>"><?= clean($f['code']) ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <h2>Liste des apprenants
      <?php if ($selectedFiliere): ?>
      <span style="font-weight:400;color:var(--gray-mid);font-size:12px">› <?= clean($selectedFiliere['nom']) ?></span>
      <?php else: ?>
      <span style="font-weight:400;color:var(--gray-mid);font-size:12px">› Liste globale</span>
      <?php endif; ?>
      <span style="font-weight:400;color:var(--gray-mid);font-size:12px">(<?= $total ?> résultat<?= $total>1?'s':'' ?>)</span>
    </h2>
  </div>
  <div class="table-wrap">
  <?php if (empty($apprenants)): ?>
    <div class="empty-state"><p>Aucun apprenant trouvé.</p></div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Matricule</th><th>Nom & Prénom</th><th>Date de naissance</th><th>Lieu de naissance</th><th>Filière</th>
        <th>Niveau</th><th>Téléphone</th><th>Inscription</th>
        <th>Statut</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($apprenants as $a): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($a['matricule']) ?></code></td>
      <td>
        <div style="font-weight:500"><?= clean($a['nom'].' '.$a['prenom']) ?></div>
        <?php if($a['email']): ?><div style="font-size:11px;color:var(--gray-mid)"><?= clean($a['email']) ?></div><?php endif; ?>
      </td>
      <td><?= $a['date_naissance'] ? dateFR($a['date_naissance']) : '—' ?></td>
      <td><?= clean($a['lieu_naissance'] ?? '—') ?></td>
      <td><?= clean($a['filiere_nom'] ?? '—') ?></td>
      <td>Niveau <?= clean($a['niveau']) ?></td>
      <td><?= clean($a['telephone'] ?? '—') ?></td>
      <td><?= dateFR($a['date_inscription']) ?></td>
      <td>
        <?php
        $map = ['inscrit'=>'badge-green','suspendu'=>'badge-orange','diplome'=>'badge-blue','abandonne'=>'badge-gray'];
        echo '<span class="badge '.$map[$a['statut']].'">'.ucfirst($a['statut']).'</span>';
        ?>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="show.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Voir">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.3"/><circle cx="6.5" cy="6.5" r="2" fill="currentColor"/></svg>
          </a>
          <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2l3 3-7 7H1v-3L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
          </a>
          <a href="delete.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-icon" style="color:var(--red);border:1px solid var(--gray-light)"
             onclick="return confirm('Supprimer cet apprenant ?')" title="Supprimer">
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

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination no-print">
  <?php for ($i=1; $i<=$pages; $i++): ?>
  <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($filtStat) ?>&filiere=<?= urlencode($filtFil) ?>"
     class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<!-- Print Layout -->
<div class="print-layout">
  <div class="print-header">
    <img src="../../assets/entete.PNG" alt="Header">
    <div class="print-title">
      <?= $selectedFiliere ? 'Liste des apprenants — ' . $selectedFiliere['nom'] : 'Liste complète des apprenants' ?>
    </div>
    <div class="print-subtitle">
      Édité le <?= date('d/m/Y à H:i') ?> — <?= count($apprenants) ?> apprenant(s)
    </div>
  </div>

  <?php foreach ($groupedByFil as $filiere => $apprenantsList): ?>
  <div class="print-section">
    <div class="print-section-title">► <?= $filiere ?> (<?= count($apprenantsList) ?>)</div>
    <table class="print-table">
      <thead>
        <tr>
          <th>Matricule</th>
          <th>Nom</th>
          <th>Prénom</th>
          <th>Date Naiss.</th>
          <th>Lieu naiss.</th>
          <th>Filière</th>
          <th>Niv.</th>
          <th>Inscription</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($apprenantsList as $a): ?>
        <tr>
          <td style="text-align: center;"><?= clean($a['matricule']) ?></td>
          <td><?= clean($a['nom']) ?></td>
          <td><?= clean($a['prenom']) ?></td>
          <td style="text-align: center;"><?= $a['date_naissance'] ? date('d/m/Y', strtotime($a['date_naissance'])) : '—' ?></td>
          <td><?= clean($a['lieu_naissance'] ?? '—') ?></td>
          <td><?= clean($a['filiere_nom'] ?? '—') ?></td>
          <td style="text-align: center;">N<?= clean($a['niveau']) ?></td>
          <td style="text-align: center;"><?= $a['date_inscription'] ? date('d/m/Y', strtotime($a['date_inscription'])) : '—' ?></td>
          <td style="text-align: center;"><?= ucfirst($a['statut']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>

  <div class="print-total">
    TOTAL : <?= count($apprenants) ?> apprenant(s)
  </div>

  <div class="print-footer">
    Document généré par Secrétariat Numérique — Centre de Formation Professionnelle en Génie Informatique
  </div>
</div>

<script>
<?php if ($isPrint): ?>
// Auto-print when in print mode
window.onload = function() {
  window.print();
};
<?php endif; ?>
</script>
