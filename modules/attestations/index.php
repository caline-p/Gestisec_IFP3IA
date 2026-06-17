<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Gestion des Attestations';
$activePage = 'attestations';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// ── Onglets : 'apprenants' | 'sirtech' | 'stagiaires' ───────────────────────
$allowed = ['apprenants', 'sirtech', 'stagiaires'];
$tab     = in_array($_GET['tab'] ?? '', $allowed, true) ? $_GET['tab'] : 'apprenants';

$search  = trim($_GET['search'] ?? '');
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ── Configuration par onglet ───────────────────────────────────────────────
$tabConfig = [
    'apprenants' => [
        'label'      => 'Apprenants inscrits',
        'color'      => 'var(--blue)',
        'where'      => "am.category = 'etudiant'",
        'sql_select' => "ap.matricule, f.nom AS filiere_nom",
        'sql_join'   => "LEFT JOIN apprenants ap ON ap.id = am.apprenant_id
                         LEFT JOIN filieres f   ON f.id  = ap.filiere_id",
        'cols'       => ['matricule', 'filiere'],
        'btn_color'  => '',
        'btn_label'  => 'Attestation apprenant',
    ],
    'sirtech' => [
        'label'      => 'Stagiaires Sir-Tech',
        'color'      => '#16a34a',
        'where'      => "am.category = 'stagiaire_externe'",
        'sql_select' => "se.etablissement, se.type AS stagiaire_type",
        'sql_join'   => "LEFT JOIN stagiaires_externes se ON se.id = am.stagiaire_externe_id",
        'cols'       => ['etablissement', 'type_sirtech'],
        'btn_color'  => 'background:#16a34a;border-color:#16a34a',
        'btn_label'  => 'Attestation Sir-Tech',
    ],
    'stagiaires' => [
        'label'      => 'Stagiaires externes',
        'color'      => '#e67e22',
        'where'      => "am.category IN ('stagiaire_academique','stagiaire_professionnel')",
        'sql_select' => "se.etablissement, se.type AS stagiaire_type",
        'sql_join'   => "LEFT JOIN stagiaires_externes se ON se.id = am.stagiaire_externe_id",
        'cols'       => ['etablissement', 'type_stage'],
        'btn_color'  => 'background:#e67e22;border-color:#e67e22',
        'btn_label'  => 'Attestation stagiaire',
    ],
];

$cfg = $tabConfig[$tab];

// ── Construction de la requête ─────────────────────────────────────────────
$params = [];
$searchClause = '';
if ($search) {
    if ($tab === 'apprenants') {
        $searchClause = " AND (att.name LIKE ? OR att.specialty LIKE ? OR att.place_birth LIKE ?)";
    } else {
        $searchClause = " AND (att.name LIKE ? OR att.specialty LIKE ? OR se.etablissement LIKE ?)";
    }
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql = "SELECT att.*, am.category, am.apprenant_id, am.stagiaire_externe_id,
               {$cfg['sql_select']}
        FROM attestation att
        INNER JOIN attestation_meta am ON am.attestation_id = att.id
        {$cfg['sql_join']}
        WHERE {$cfg['where']}{$searchClause}";

// Compter
$countStmt = $db->prepare("SELECT COUNT(*) FROM ($sql) x");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// Récupérer la page
$stmt = $db->prepare("$sql ORDER BY att.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$attestations = $stmt->fetchAll();

// ── Compteurs pour badges ─────────────────────────────────────────────────
$counts = [];
foreach ($tabConfig as $key => $c) {
    $cstmt = $db->prepare("SELECT COUNT(*) FROM attestation att INNER JOIN attestation_meta am ON am.attestation_id = att.id WHERE {$c['where']}");
    $cstmt->execute();
    $counts[$key] = (int)$cstmt->fetchColumn();
}
?>

<!-- ── Onglets ─────────────────────────────────────────────────────────── -->
<?php $tabClass = ['apprenants' => '', 'sirtech' => 'green', 'stagiaires' => 'orange']; ?>
<div class="tabs">
  <?php foreach ($tabConfig as $key => $c): ?>
    <a href="?tab=<?= $key ?>"
       class="tab <?= $tab===$key ? 'active '.$tabClass[$key] : '' ?>">
      <?= clean($c['label']) ?>
      <span class="tab-count"><?= $counts[$key] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- ── Barre d'actions ──────────────────────────────────────────────────── -->
<div class="page-actions">
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="tab" value="<?= $tab ?>">
    <input type="text" name="search" class="search-input"
           placeholder="Rechercher (nom, spécialité<?= $tab!=='apprenants'?', établissement':'' ?>)..."
           value="<?= clean($search) ?>" style="width:280px">
    <button class="btn btn-outline btn-sm" type="submit">Rechercher</button>
    <?php if ($search): ?>
      <a href="?tab=<?= $tab ?>" class="btn btn-sm" style="color:var(--gray-mid)">Effacer</a>
    <?php endif; ?>
  </form>
  <a href="create.php?tab=<?= $tab ?>" class="btn btn-primary"
     style="<?= $cfg['btn_color'] ?>">
    + <?= clean($cfg['btn_label']) ?>
  </a>
</div>

<!-- ── Tableau ──────────────────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <?= clean($cfg['label']) ?>
      <span style="font-weight:400;color:var(--gray-mid);font-size:12px">
        (<?= $total ?> enregistrement<?= $total>1?'s':'' ?>)
      </span>
    </h2>
  </div>
  <div class="table-wrap">
    <?php if (empty($attestations)): ?>
      <div class="empty-state">
        <p>Aucune attestation enregistrée pour cette catégorie.</p>
        <a href="create.php?tab=<?= $tab ?>" class="btn btn-primary btn-sm"
           style="<?= $cfg['btn_color'] ?>">Créer la première</a>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Nom complet</th>
          <th>Spécialité</th>
          <?php if ($tab === 'apprenants'): ?>
            <th>Matricule</th><th>Filière</th>
          <?php else: ?>
            <th>Établissement d'origine</th><th>Type</th>
          <?php endif; ?>
          <th>Période de stage</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($attestations as $att): ?>
      <tr>
        <td style="font-weight:600;font-size:12px"><?= clean($att['name']) ?></td>
        <td style="font-size:12px"><?= clean($att['specialty'] ?? '—') ?></td>

        <?php if ($tab === 'apprenants'): ?>
          <td style="font-size:11px;color:var(--gray-mid)"><?= clean($att['matricule'] ?? '—') ?></td>
          <td style="font-size:12px"><?= clean($att['filiere_nom'] ?? '—') ?></td>
        <?php else: ?>
          <td style="font-size:12px"><?= clean($att['etablissement'] ?? '—') ?></td>
          <td>
            <?php
              $typeLabel = match($att['category'] ?? '') {
                  'stagiaire_externe'       => ['label' => 'Sir-Tech',      'class' => 'badge-green'],
                  'stagiaire_academique'    => ['label' => 'Académique',    'class' => 'badge-blue'],
                  'stagiaire_professionnel' => ['label' => 'Professionnel', 'class' => 'badge-orange'],
                  default                   => ['label' => '—',             'class' => 'badge-gray'],
              };
            ?>
            <span class="badge <?= $typeLabel['class'] ?>"><?= $typeLabel['label'] ?></span>
          </td>
        <?php endif; ?>

        <td style="font-size:12px">
          <?= clean(($att['start_date'] ? dateFR($att['start_date']) : 'N/D') . ' – ' . ($att['end_date'] ? dateFR($att['end_date']) : 'N/D')) ?>
        </td>

        <td>
          <div style="display:flex;gap:5px">
            <a href="edit.php?id=<?= $att['id'] ?>&tab=<?= $tab ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier">
              <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2l3 3-7 7H1v-3L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
            </a>
            <a href="export_pdf.php?id=<?= $att['id'] ?>" class="btn btn-sm btn-icon"
               style="color:<?= $tab==='sirtech' ? '#16a34a' : 'var(--blue)' ?>;border:1px solid var(--gray-light)"
               title="Télécharger PDF" target="_blank">
              <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6 1v8m3-3L6 9l-3-3M1 10h11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="delete.php?id=<?= $att['id'] ?>&tab=<?= $tab ?>" class="btn btn-sm btn-icon"
               style="color:var(--red);border:1px solid var(--gray-light)"
               onclick="return confirm('Supprimer cette attestation ?')">
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
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?tab=<?= $tab ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>"
       class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
