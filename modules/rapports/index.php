<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Rapports';
$activePage = 'rapports';
require_once __DIR__ . '/../../includes/header.php';
$db = getDB();

$mois  = (int)($_GET['mois'] ?? date('n'));
$annee = (int)($_GET['annee'] ?? date('Y'));
$moisNoms=['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

// ── Stats générales
$nbApp    = $db->query("SELECT COUNT(*) FROM apprenants WHERE statut='inscrit'")->fetchColumn();
$nbPerso  = $db->query("SELECT COUNT(*) FROM personnel WHERE statut='actif'")->fetchColumn();
$nbCourr  = $db->query("SELECT COUNT(*) FROM courriers WHERE MONTH(created_at)=$mois AND YEAR(created_at)=$annee")->fetchColumn();

// ── Recettes du mois sélectionné
$recMois  = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM paiements WHERE MONTH(date_paiement)=? AND YEAR(date_paiement)=?");
$recMois->execute([$mois,$annee]); $recMois=$recMois->fetchColumn();

// ── Recettes par mois (12 derniers)
$recChart = $db->query("SELECT MONTH(date_paiement) AS m, YEAR(date_paiement) AS y, SUM(montant) AS total FROM paiements WHERE date_paiement >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY y,m ORDER BY y,m")->fetchAll();

// ── Inscriptions par filière
$parFil = $db->query("SELECT f.nom, COUNT(a.id) AS nb FROM filieres f LEFT JOIN apprenants a ON a.filiere_id=f.id AND a.statut='inscrit' GROUP BY f.id ORDER BY nb DESC")->fetchAll();

// ── Recettes par filière
$recFil = $db->query("SELECT f.nom, COALESCE(SUM(p.montant),0) AS total FROM filieres f LEFT JOIN apprenants a ON a.filiere_id=f.id LEFT JOIN paiements p ON p.apprenant_id=a.id GROUP BY f.id ORDER BY total DESC")->fetchAll();

// ── Impayés total
$impayes = $db->query("SELECT COALESCE(SUM(f.cout - COALESCE(pp.total,0)),0) AS total FROM apprenants a JOIN filieres f ON a.filiere_id=f.id LEFT JOIN (SELECT apprenant_id, SUM(montant) AS total FROM paiements GROUP BY apprenant_id) pp ON pp.apprenant_id=a.id WHERE a.statut='inscrit' HAVING total>0")->fetchColumn();

// ── Paiements du mois
$paiMois=$db->prepare("SELECT p.*,a.nom,a.prenom FROM paiements p JOIN apprenants a ON p.apprenant_id=a.id WHERE MONTH(p.date_paiement)=? AND YEAR(p.date_paiement)=? ORDER BY p.date_paiement");
$paiMois->execute([$mois,$annee]);$paiMois=$paiMois->fetchAll();

// ── Courriers du mois
$courrMois=$db->prepare("SELECT * FROM courriers WHERE MONTH(created_at)=? AND YEAR(created_at)=? ORDER BY created_at");
$courrMois->execute([$mois,$annee]);$courrMois=$courrMois->fetchAll();
?>

<!-- Sélecteur mois/année -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
  <form method="GET" style="display:flex;gap:8px;align-items:center">
    <label class="form-label" style="margin:0">Période :</label>
    <select name="mois" class="form-control" style="width:auto">
      <?php for($m=1;$m<=12;$m++): ?>
      <option value="<?= $m ?>" <?= $m==$mois?'selected':'' ?>><?= $moisNoms[$m] ?></option>
      <?php endfor; ?>
    </select>
    <select name="annee" class="form-control" style="width:auto">
      <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
      <option value="<?= $y ?>" <?= $y==$annee?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn btn-primary btn-sm">Générer le rapport</button>
  </form>
  <button onclick="window.print()" class="btn btn-outline btn-sm">🖨 Imprimer</button>
</div>

<!-- KPIs -->
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="kpi-card">
    <div class="kpi-label">Apprenants inscrits</div>
    <div class="kpi-value"><?= $nbApp ?></div>
    <div class="kpi-sub">Total actifs</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Recettes — <?= $moisNoms[$mois] ?></div>
    <div class="kpi-value" style="font-size:18px"><?= money((float)$recMois) ?></div>
    <div class="kpi-sub">Encaissées</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Impayés cumulés</div>
    <div class="kpi-value" style="font-size:18px;color:var(--red)"><?= money((float)$impayes) ?></div>
    <div class="kpi-sub">Montant total dû</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Courriers traités</div>
    <div class="kpi-value"><?= $nbCourr ?></div>
    <div class="kpi-sub"><?= $moisNoms[$mois] ?> <?= $annee ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

  <!-- Inscriptions par filière -->
  <div class="card">
    <div class="card-header"><h2>Apprenants par filière</h2></div>
    <div style="padding:14px">
      <?php foreach($parFil as $f): $pct=$nbApp>0?round($f['nb']/$nbApp*100):0; ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
          <span style="font-weight:500"><?= clean($f['nom']) ?></span>
          <span style="color:var(--blue-mid);font-weight:600"><?= $f['nb'] ?> (<?= $pct ?>%)</span>
        </div>
        <div style="height:6px;background:var(--gray-light);border-radius:3px">
          <div style="width:<?= $pct ?>%;height:100%;background:var(--blue-mid);border-radius:3px"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Recettes par filière -->
  <div class="card">
    <div class="card-header"><h2>Recettes encaissées par filière</h2></div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Filière</th><th>Montant encaissé</th></tr></thead>
      <tbody>
      <?php foreach($recFil as $r): ?>
      <tr>
        <td><?= clean($r['nom']) ?></td>
        <td style="font-weight:600;color:var(--green)"><?= money((float)$r['total']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td style="font-weight:600">Total général</td>
          <td style="font-weight:700;color:var(--green)"><?= money(array_sum(array_column($recFil,'total'))) ?></td>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>
</div>

<!-- Journal paiements du mois -->
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <h2>Journal des paiements — <?= $moisNoms[$mois] ?> <?= $annee ?></h2>
    <span style="font-size:12px;color:var(--gray-mid)"><?= count($paiMois) ?> transaction<?= count($paiMois)>1?'s':'' ?> — Total : <?= money((float)$recMois) ?></span>
  </div>
  <?php if(empty($paiMois)): ?>
    <div class="empty-state" style="padding:30px"><p>Aucun paiement ce mois-ci.</p></div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence</th><th>Apprenant</th><th>Montant</th><th>Mode</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($paiMois as $p): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($p['reference']) ?></code></td>
      <td><?= clean($p['nom'].' '.$p['prenom']) ?></td>
      <td style="font-weight:600;color:var(--green)"><?= money((float)$p['montant']) ?></td>
      <td><?= ucfirst(str_replace('_',' ',$p['mode_paiement'])) ?></td>
      <td><?= dateFR($p['date_paiement']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="2" style="text-align:right;font-weight:600">Total du mois :</td><td style="font-weight:700;color:var(--green)"><?= money((float)$recMois) ?></td><td colspan="2"></td></tr></tfoot>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- Journal courriers du mois -->
<div class="card">
  <div class="card-header"><h2>Courriers traités — <?= $moisNoms[$mois] ?> <?= $annee ?></h2></div>
  <?php if(empty($courrMois)): ?>
    <div class="empty-state" style="padding:30px"><p>Aucun courrier ce mois-ci.</p></div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence</th><th>Type</th><th>Objet</th><th>Expéditeur</th><th>Statut</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($courrMois as $c):
      $sl=['recu'=>'Reçu','en_traitement'=>'En cours','traite'=>'Traité','archive'=>'Archivé'];
      $sb=['recu'=>'badge-blue','en_traitement'=>'badge-orange','traite'=>'badge-green','archive'=>'badge-gray'];
    ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($c['reference']) ?></code></td>
      <td><?= $c['type']==='entrant'?'↓ Entrant':'↑ Sortant' ?></td>
      <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= clean($c['objet']) ?></td>
      <td style="font-size:12px"><?= clean($c['expediteur']??'—') ?></td>
      <td><span class="badge <?= $sb[$c['statut']] ?>"><?= $sl[$c['statut']] ?></span></td>
      <td style="font-size:12px"><?= dateFR($c['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<style>@media print{.sidebar,.topbar,.no-print{display:none!important}.main{margin-left:0!important}}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
