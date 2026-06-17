<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Paiements';
$activePage = 'paiements';
require_once __DIR__ . '/../../includes/header.php';

$db      = getDB();
$search  = trim($_GET['search'] ?? '');
$filtMod = $_GET['mode'] ?? '';
$filtImp = $_GET['filtre'] ?? '';
$perPage = 15;
$page    = max(1,(int)($_GET['page']??1));
$offset  = ($page-1)*$perPage;

// Totaux du mois
$totMois  = $db->query("SELECT COALESCE(SUM(montant),0) FROM paiements WHERE MONTH(date_paiement)=MONTH(NOW()) AND YEAR(date_paiement)=YEAR(NOW())")->fetchColumn();
$totAnnee = $db->query("SELECT COALESCE(SUM(montant),0) FROM paiements WHERE YEAR(date_paiement)=YEAR(NOW())")->fetchColumn();

// Liste des impayés
if ($filtImp === 'impayes') {
    $sql = "SELECT a.id, a.matricule, a.nom, a.prenom, f.nom AS filiere_nom, f.cout,
            COALESCE(SUM(p.montant),0) AS total_paye,
            (f.cout - COALESCE(SUM(p.montant),0)) AS reste
            FROM apprenants a
            JOIN filieres f ON a.filiere_id=f.id
            LEFT JOIN paiements p ON p.apprenant_id=a.id
            WHERE a.statut='inscrit'
            GROUP BY a.id HAVING reste > 0 ORDER BY reste DESC";
    $impayes = $db->query($sql)->fetchAll();
} else {
    $where = ["1=1"]; $params = [];
    if ($search) { $where[]="(a.nom LIKE ? OR a.prenom LIKE ? OR p.reference LIKE ?)"; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
    if ($filtMod){ $where[]="p.mode_paiement=?"; $params[]=$filtMod; }

    $sql   = "SELECT p.*, a.nom, a.prenom, a.matricule, f.nom AS filiere_nom FROM paiements p JOIN apprenants a ON p.apprenant_id=a.id LEFT JOIN filieres f ON a.filiere_id=f.id WHERE ".implode(' AND ',$where);
    $total = $db->prepare("SELECT COUNT(*) FROM ($sql) x"); $total->execute($params); $total=$total->fetchColumn();
    $pages = ceil($total/$perPage);
    $stmt  = $db->prepare("$sql ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params); $paiements = $stmt->fetchAll();
}
?>

<!-- KPI mini -->
<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="kpi-card">
    <div class="kpi-label">Recettes du mois</div>
    <div class="kpi-value" style="font-size:20px"><?= money((float)$totMois) ?></div>
    <div class="kpi-sub"><?= date('F Y') ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Recettes de l'année</div>
    <div class="kpi-value" style="font-size:20px"><?= money((float)$totAnnee) ?></div>
    <div class="kpi-sub"><?= date('Y') ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Actions rapides</div>
    <div style="display:flex;gap:8px;margin-top:8px">
      <a href="create.php" class="btn btn-primary btn-sm">+ Nouveau paiement</a>
      <a href="?filtre=impayes" class="btn btn-outline btn-sm" style="color:var(--red);border-color:var(--red)">Impayés</a>
    </div>
  </div>
</div>

<?php if ($filtImp === 'impayes'): ?>
<!-- VUE IMPAYÉS -->
<div class="page-actions">
  <h2 style="color:var(--red);font-size:14px">⚠ Liste des impayés (<?= count($impayes) ?> apprenant<?= count($impayes)>1?'s':'' ?>)</h2>
  <a href="?" class="btn btn-outline btn-sm">← Retour aux paiements</a>
</div>
<div class="card">
  <div class="table-wrap">
  <?php if(empty($impayes)): ?>
    <div class="empty-state" style="padding:40px"><p>Aucun impayé. Tous les apprenants sont à jour ✓</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Matricule</th><th>Apprenant</th><th>Filière</th><th>Coût total</th><th>Payé</th><th>Reste dû</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($impayes as $i): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($i['matricule']) ?></code></td>
      <td style="font-weight:500"><?= clean($i['nom'].' '.$i['prenom']) ?></td>
      <td><?= clean($i['filiere_nom']) ?></td>
      <td><?= money((float)$i['cout']) ?></td>
      <td style="color:var(--green)"><?= money((float)$i['total_paye']) ?></td>
      <td style="font-weight:700;color:var(--red)"><?= money((float)$i['reste']) ?></td>
      <td><a href="create.php?apprenant_id=<?= $i['id'] ?>" class="btn btn-primary btn-sm">Encaisser</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" style="text-align:right">Total impayés :</td>
        <td style="color:var(--red)"><?= money(array_sum(array_column($impayes,'reste'))) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- VUE NORMALE -->
<div class="page-actions">
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="text" name="search" class="search-input" placeholder="Rechercher apprenant ou référence..." value="<?= clean($search) ?>" style="width:240px">
    <select name="mode" class="form-control" style="width:auto">
      <option value="">Tous modes</option>
      <?php foreach(['especes'=>'Espèces','mobile_money'=>'Mobile Money','virement'=>'Virement','cheque'=>'Chèque'] as $v=>$l): ?>
      <option value="<?= $v ?>" <?= $filtMod===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm">Filtrer</button>
    <?php if($search||$filtMod): ?><a href="?" class="btn btn-sm" style="color:var(--gray-mid)">Effacer</a><?php endif; ?>
  </form>
  <a href="create.php" class="btn btn-primary">+ Nouveau paiement</a>
</div>

<div class="card">
  <div class="card-header">
    <h2>Journal des paiements <span style="font-weight:400;color:var(--gray-mid);font-size:12px">(<?= $total ?? 0 ?> entrée<?= ($total??0)>1?'s':'' ?>)</span></h2>
  </div>
  <div class="table-wrap">
  <?php if(empty($paiements)): ?>
    <div class="empty-state"><p>Aucun paiement enregistré.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Référence</th><th>Apprenant</th><th>Filière</th><th>Montant</th><th>Mode</th><th>Mois</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($paiements as $p): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($p['reference']) ?></code></td>
      <td>
        <div style="font-weight:500"><?= clean($p['nom'].' '.$p['prenom']) ?></div>
        <div style="font-size:11px;color:var(--gray-mid)"><?= clean($p['matricule']) ?></div>
      </td>
      <td style="font-size:12px"><?= clean($p['filiere_nom']??'—') ?></td>
      <td><strong style="color:var(--green)"><?= money((float)$p['montant']) ?></strong></td>
      <td><span class="badge badge-blue"><?= ucfirst(str_replace('_',' ',$p['mode_paiement'])) ?></span></td>
      <td style="font-size:12px"><?= clean($p['mois_concerne']??'—') ?></td>
      <td style="font-size:12px"><?= dateFR($p['date_paiement']) ?></td>
      <td>
        <div style="display:flex;gap:5px">
          <a href="recu.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Imprimer reçu" target="_blank">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/><line x1="4" y1="5" x2="9" y2="5" stroke="currentColor" stroke-width="1.2"/><line x1="4" y1="7" x2="9" y2="7" stroke="currentColor" stroke-width="1.2"/><line x1="4" y1="9" x2="7" y2="9" stroke="currentColor" stroke-width="1.2"/></svg>
          </a>
          <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-icon" style="color:var(--red);border:1px solid var(--gray-light)"
             onclick="return confirm('Supprimer ce paiement ?')">
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

<?php if(($pages??1)>1): ?>
<div class="pagination">
  <?php for($i=1;$i<=($pages??1);$i++): ?>
  <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&mode=<?= urlencode($filtMod) ?>"
     class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
