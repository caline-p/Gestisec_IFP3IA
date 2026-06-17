<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle  = 'Tableau de bord';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Statistiques principales
$nbApprenants = $db->query("SELECT COUNT(*) FROM apprenants WHERE statut='inscrit'")->fetchColumn();
$nbPersonnel  = $db->query("SELECT COUNT(*) FROM personnel WHERE statut='actif'")->fetchColumn();
$nbCourriers  = $db->query("SELECT COUNT(*) FROM courriers WHERE statut IN ('recu','en_traitement')")->fetchColumn();
$recettesMois = $db->query("SELECT COALESCE(SUM(montant),0) FROM paiements WHERE MONTH(date_paiement)=MONTH(NOW()) AND YEAR(date_paiement)=YEAR(NOW())")->fetchColumn();
$nbImpayés    = $db->query("
  SELECT COUNT(DISTINCT a.id) FROM apprenants a
  JOIN filieres f ON a.filiere_id = f.id
  LEFT JOIN (SELECT apprenant_id, SUM(montant) AS total FROM paiements GROUP BY apprenant_id) p ON p.apprenant_id = a.id
  WHERE a.statut = 'inscrit' AND COALESCE(p.total, 0) < f.cout
")->fetchColumn();

// Derniers courriers
$courriers = $db->query("SELECT * FROM courriers ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Prochains plannings
$plannings = $db->query("SELECT * FROM plannings WHERE date_debut >= NOW() ORDER BY date_debut ASC LIMIT 5")->fetchAll();

// Derniers paiements
$paiements = $db->query("
  SELECT p.*, a.nom, a.prenom FROM paiements p
  JOIN apprenants a ON p.apprenant_id = a.id
  ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();
?>

<!-- KPI GRID -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-label">Apprenants inscrits</div>
    <div class="kpi-value"><?= $nbApprenants ?></div>
    <div class="kpi-sub">Total actifs</div>
    <div class="kpi-bar"><div class="kpi-fill" style="width:75%"></div></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Personnel actif</div>
    <div class="kpi-value"><?= $nbPersonnel ?></div>
    <div class="kpi-sub">Agents en service</div>
    <div class="kpi-bar"><div class="kpi-fill" style="width:60%;background:#1E7145"></div></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Recettes du mois</div>
    <div class="kpi-value" style="font-size:18px"><?= money((float)$recettesMois) ?></div>
    <div class="kpi-sub">Encaissées ce mois</div>
    <div class="kpi-bar"><div class="kpi-fill" style="width:55%"></div></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Courriers en attente</div>
    <div class="kpi-value" style="color:<?= $nbCourriers>0?'#C00000':'#1E7145' ?>"><?= $nbCourriers ?></div>
    <div class="kpi-sub">À traiter</div>
    <div class="kpi-bar"><div class="kpi-fill" style="width:<?= min(100,$nbCourriers*15) ?>%;background:#C00000"></div></div>
  </div>
</div>

<?php if ($nbImpayés > 0): ?>
<div style="background:#EBF3FB;border-left:4px solid #2E75B6;border-radius:0 8px 8px 0;padding:12px 16px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">
  <div>
    <strong style="color:#1F3864;font-size:13px"><?= $nbImpayés ?> apprenant(s) avec solde impayé</strong>
    <div style="font-size:12px;color:#6B6B6B;margin-top:2px">Consultez le module Paiements pour effectuer les relances.</div>
  </div>
  <a href="<?= APP_URL ?>/modules/paiements/index.php?filtre=impayes" class="btn btn-outline btn-sm">Voir les impayés</a>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:16px">

  <!-- Courriers récents -->
  <div class="card">
    <div class="card-header">
      <h2>Courriers récents</h2>
      <a href="<?= APP_URL ?>/modules/courrier/index.php" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <?php if (empty($courriers)): ?>
    <div class="empty-state" style="padding:30px"><p>Aucun courrier enregistré.</p></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Référence</th><th>Objet</th><th>Type</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($courriers as $c): ?>
      <tr>
        <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($c['reference']) ?></code></td>
        <td style="max-width:180px"><div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= clean($c['objet']) ?></div></td>
        <td><?= $c['type']==='entrant'
              ? '<span class="badge badge-blue">Entrant</span>'
              : '<span class="badge badge-gray">Sortant</span>' ?></td>
        <td>
          <?php
          $badges = ['recu'=>'badge-blue','en_traitement'=>'badge-orange','traite'=>'badge-green','archive'=>'badge-gray'];
          $labels = ['recu'=>'Reçu','en_traitement'=>'En cours','traite'=>'Traité','archive'=>'Archivé'];
          echo '<span class="badge '.$badges[$c['statut']].'">'.$labels[$c['statut']].'</span>';
          ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Agenda du jour -->
  <div class="card">
    <div class="card-header">
      <h2>Agenda du directeur</h2>
      <a href="<?= APP_URL ?>/modules/plannings/index.php" class="btn btn-outline btn-sm">Calendrier</a>
    </div>
    <?php if (empty($plannings)): ?>
    <div class="empty-state" style="padding:30px"><p>Aucun événement prévu.</p></div>
    <?php else: ?>
    <div style="padding:4px 0">
    <?php foreach ($plannings as $p): ?>
    <div style="display:flex;gap:12px;padding:11px 16px;border-bottom:1px solid #F5F6F8;align-items:flex-start">
      <div style="min-width:48px;font-size:11px;font-weight:600;color:var(--blue-mid);padding-top:2px">
        <?= date('H:i', strtotime($p['date_debut'])) ?>
      </div>
      <div style="width:8px;height:8px;border-radius:50%;background:var(--blue-mid);margin-top:4px;flex-shrink:0"></div>
      <div>
        <div style="font-size:13px;font-weight:500;color:var(--gray-dark)"><?= clean($p['titre']) ?></div>
        <div style="font-size:11px;color:var(--gray-mid);margin-top:2px">
          <?= dateFR($p['date_debut']) ?>
          <?php if($p['lieu']): echo ' · '.clean($p['lieu']); endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Derniers paiements -->
<div class="card">
  <div class="card-header">
    <h2>Derniers paiements enregistrés</h2>
    <a href="<?= APP_URL ?>/modules/paiements/index.php" class="btn btn-outline btn-sm">Voir tout</a>
  </div>
  <?php if (empty($paiements)): ?>
  <div class="empty-state" style="padding:30px"><p>Aucun paiement enregistré.</p></div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence</th><th>Apprenant</th><th>Montant</th><th>Mode</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach ($paiements as $p): ?>
    <tr>
      <td><code style="font-size:11px;color:var(--blue-mid)"><?= clean($p['reference']) ?></code></td>
      <td><?= clean($p['prenom'].' '.$p['nom']) ?></td>
      <td><strong style="color:var(--green)"><?= money((float)$p['montant']) ?></strong></td>
      <td><?= ucfirst(str_replace('_',' ',$p['mode_paiement'])) ?></td>
      <td><?= dateFR($p['date_paiement']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
