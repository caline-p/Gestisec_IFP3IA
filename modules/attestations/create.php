<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'Nouvelle Attestation';
$activePage = 'attestations';

$db = getDB();

// ── Onglet actif ──────────────────────────────────────────────────────────
// 3 valeurs possibles : 'apprenants' | 'sirtech' | 'stagiaires'
$allowed = ['apprenants', 'sirtech', 'stagiaires'];
$tab     = in_array($_GET['tab'] ?? '', $allowed, true) ? $_GET['tab'] : 'apprenants';

// ── Listes pour les <select> ──────────────────────────────────────────────
$apprenants = $db->query(
    "SELECT a.id, a.nom, a.prenom, a.matricule, a.date_naissance, a.lieu_naissance,
            f.nom AS filiere_nom
     FROM apprenants a
     LEFT JOIN filieres f ON a.filiere_id = f.id
     WHERE a.statut = 'inscrit'
     ORDER BY a.nom, a.prenom"
)->fetchAll();

// Stagiaires dont l'établissement = Sir-Tech
$stagiairesSirTech = $db->query(
    "SELECT id, nom, prenom, etablissement, specialty, start_date, end_date, type
     FROM stagiaires_externes
     WHERE etablissement LIKE '%Sir-Tech%'
        OR etablissement LIKE '%SIRTECH%'
        OR etablissement LIKE '%SIR-TECH%'
     ORDER BY nom, prenom"
)->fetchAll();

$errors = [];

// ── Traitement POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form_tab   = $_POST['form_tab'] ?? 'apprenants';
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date']   ?? '');
    $origin     = trim($_POST['origin']     ?? '');   // "remarques" en base

    // ═══════════════════════════════════════════════════════════════════
    // CAS 1 : Apprenant inscrit IFP-3IA
    // ═══════════════════════════════════════════════════════════════════
    if ($form_tab === 'apprenants') {

        $apprenant_id = isset($_POST['apprenant_id']) && $_POST['apprenant_id'] !== ''
                        ? (int)$_POST['apprenant_id'] : null;

        if (!$apprenant_id) $errors[] = "Veuillez sélectionner un apprenant dans la liste.";
        if (!$start_date)   $errors[] = "La date de début de stage est requise.";
        if (!$end_date)     $errors[] = "La date de fin de stage est requise.";

        if (empty($errors)) {
            $s = $db->prepare(
                "SELECT a.*, f.nom AS filiere_nom
                 FROM apprenants a
                 LEFT JOIN filieres f ON a.filiere_id = f.id
                 WHERE a.id = ?"
            );
            $s->execute([$apprenant_id]);
            $a = $s->fetch();

            if (!$a) {
                $errors[] = "Apprenant introuvable en base de données.";
            } else {
                $name        = trim($a['nom'] . ' ' . $a['prenom']);
                $date_birth  = $a['date_naissance'] ?: null;
                $place_birth = $a['lieu_naissance'] ?: null;
                $specialty   = $a['filiere_nom']     ?: '';

                $ins = $db->prepare(
                    "INSERT INTO attestation (name, date_birth, place_birth, specialty, start_date, end_date, origin)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->execute([$name, $date_birth, $place_birth, $specialty,
                               $start_date ?: null, $end_date ?: null, $origin ?: null]);
                $attestation_id = (int)$db->lastInsertId();

                $db->prepare(
                    "INSERT INTO attestation_meta (attestation_id, category, apprenant_id, stagiaire_externe_id, created_at)
                     VALUES (?, 'etudiant', ?, NULL, NOW())"
                )->execute([$attestation_id, $apprenant_id]);

                setFlash('success', 'Attestation créée avec succès pour ' . htmlspecialchars($name) . ' !');
                header('Location: ' . APP_URL . '/modules/attestations/index.php?tab=apprenants');
                exit;
            }
        }
        $tab = 'apprenants';
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAS 2 : Stagiaire Sir-Tech (attestation délivrée par Sir-Tech)
    // ═══════════════════════════════════════════════════════════════════
    elseif ($form_tab === 'sirtech') {

        $stagiaire_id = isset($_POST['stagiaire_externe_id']) && $_POST['stagiaire_externe_id'] !== ''
                        ? (int)$_POST['stagiaire_externe_id'] : null;
        // L'onglet "Stagiaires Sir-Tech" de la liste filtre sur cette catégorie.
        $category = 'stagiaire_externe';

        if (!$stagiaire_id) $errors[] = "Veuillez sélectionner un stagiaire Sir-Tech dans la liste.";

        if (empty($errors)) {
            // Récupération des infos du stagiaire Sir-Tech
            $s = $db->prepare("SELECT * FROM stagiaires_externes WHERE id = ?");
            $s->execute([$stagiaire_id]);
            $stg = $s->fetch();

            if (!$stg) {
                $errors[] = "Stagiaire Sir-Tech introuvable en base.";
            } else {
                // On ne (re)crée PAS le stagiaire : on utilise la fiche existante
                $name        = trim($stg['nom'] . ' ' . $stg['prenom']);
                $specialty   = $stg['specialty']  ?: '';
                $startDateDb = $stg['start_date'] ?: null;
                $endDateDb   = $stg['end_date']   ?: null;

                // Dates : on prend celles du formulaire si remplies, sinon celles de la BD
                $finalStart = $start_date ?: $startDateDb;
                $finalEnd   = $end_date   ?: $endDateDb;

                $ins = $db->prepare(
                    "INSERT INTO attestation (name, specialty, start_date, end_date, origin)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $ins->execute([$name, $specialty ?: null,
                               $finalStart ?: null, $finalEnd ?: null, $origin ?: null]);
                $attestation_id = (int)$db->lastInsertId();

                $db->prepare(
                      "INSERT INTO attestation_meta (attestation_id, category, apprenant_id, stagiaire_externe_id, created_at)
                      VALUES (?, ?, NULL, ?, NOW())"
                )->execute([$attestation_id, $category, $stagiaire_id]);

                setFlash('success', 'Attestation Sir-Tech créée pour ' . htmlspecialchars($name) . ' !');
                header('Location: ' . APP_URL . '/modules/attestations/index.php?tab=sirtech');
                exit;
            }
        }
        $tab = 'sirtech';
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAS 3 : Stagiaire externe (autre — IFP-3IA)
    // ═══════════════════════════════════════════════════════════════════
    else { // $form_tab === 'stagiaires'

        $ext_nom     = trim($_POST['ext_nom'] ?? '');
        $ext_prenom  = trim($_POST['ext_prenom'] ?? '');
        $ext_etab    = trim($_POST['ext_etablissement'] ?? '');
        $ext_type    = in_array($_POST['ext_type'] ?? '', ['academique', 'professionnel'])
                       ? $_POST['ext_type'] : 'academique';
        $specialty   = trim($_POST['specialty'] ?? '');
        $date_birth  = trim($_POST['date_birth'] ?? '');
        $place_birth = trim($_POST['place_birth'] ?? '');

        if (!$ext_nom)    $errors[] = "Le nom du stagiaire est requis.";
        if (!$start_date) $errors[] = "La date de début de stage est requise.";
        if (!$end_date)   $errors[] = "La date de fin de stage est requise.";
        if (!$specialty)  $errors[] = "La spécialité / domaine du stage est requis.";

        if (empty($errors)) {
            $name     = trim($ext_nom . ' ' . $ext_prenom);
            $category = ($ext_type === 'professionnel')
                        ? 'stagiaire_professionnel'
                        : 'stagiaire_academique';

            $ins = $db->prepare(
                "INSERT INTO stagiaires_externes (nom, prenom, etablissement, specialty, start_date, end_date, type, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $ins->execute([$ext_nom, $ext_prenom ?: null, $ext_etab ?: null, $specialty ?: null,
                           $start_date ?: null, $end_date ?: null, $ext_type]);
            $stagiaire_id = (int)$db->lastInsertId();

            $ins2 = $db->prepare(
                "INSERT INTO attestation (name, date_birth, place_birth, specialty, start_date, end_date, origin)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $ins2->execute([$name, $date_birth ?: null, $place_birth ?: null, $specialty,
                            $start_date ?: null, $end_date ?: null, $origin ?: null]);
            $attestation_id = (int)$db->lastInsertId();

            $db->prepare(
                "INSERT INTO attestation_meta (attestation_id, category, apprenant_id, stagiaire_externe_id, created_at)
                 VALUES (?, ?, NULL, ?, NOW())"
            )->execute([$attestation_id, $category, $stagiaire_id]);

            setFlash('success', 'Attestation créée avec succès pour ' . htmlspecialchars($name) . ' !');
            header('Location: ' . APP_URL . '/modules/attestations/index.php?tab=stagiaires');
            exit;
        }
        $tab = 'stagiaires';
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.form-panel { display: none; }
.form-panel.active { display: block; }
.preview-box {
  background: var(--blue-pale);
  border: 1px solid var(--blue-light);
  border-radius: var(--radius);
  padding: 14px 18px;
  margin-bottom: 20px;
}
.preview-box.sirtech { background: #F0FDF4; border-color: #BBF7D0; }
.preview-box .preview-title {
  font-size: 12px; font-weight: 700; margin-bottom: 8px; color: var(--blue);
}
.preview-box.sirtech .preview-title { color: var(--green); }
</style>

<div style="max-width:860px">

  <!-- ── En-tête ──────────────────────────────────────────────────────── -->
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
    <a href="index.php?tab=<?= $tab ?>" class="btn btn-outline btn-sm">&larr; Retour</a>
    <h2 class="page-title" style="margin:0">Nouvelle Attestation</h2>
  </div>

  <!-- ── Onglets ──────────────────────────────────────────────────────── -->
  <div class="tabs">
    <button type="button" class="tab <?= $tab==='apprenants'?'active':'' ?>"
            id="btn-apprenant" onclick="switchTab('apprenants')">
      Apprenant IFP-3IA
    </button>
    <button type="button" class="tab <?= $tab==='sirtech'?'active green':'' ?>"
            id="btn-sirtech" onclick="switchTab('sirtech')">
      Stagiaire Sir-Tech
    </button>
    <button type="button" class="tab <?= $tab==='stagiaires'?'active orange':'' ?>"
            id="btn-stagiaire" onclick="switchTab('stagiaires')">
      Stagiaire externe
    </button>
  </div>

  <!-- ── Erreurs ──────────────────────────────────────────────────────── -->
  <?php if (!empty($errors)): ?>
  <div class="flash flash-error" style="display:block">
    <strong>Veuillez corriger les erreurs suivantes :</strong>
    <?php foreach ($errors as $err): ?>
      <div>&bull; <?= clean($err) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>


  <!-- ════════════════════════════════════════════════════════════════════
       PANEL 1 — APPRENANT IFP-3IA
  ═════════════════════════════════════════════════════════════════════════ -->
  <div class="form-panel <?= $tab==='apprenants'?'active':'' ?>" id="panel-apprenants">
    <div class="card">
      <div class="card-header" style="border-left:4px solid var(--blue)">
        <h3 class="card-title" style="margin:0">
          Attestation pour un apprenant IFP-3IA
        </h3>
        <small style="color:var(--gray-mid)">
          Fin de formation — données récupérées depuis la fiche apprenant.
        </small>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_tab" value="apprenants">

          <div class="field" style="margin-bottom:20px">
            <label>Sélectionner l'apprenant *</label>
            <select name="apprenant_id" id="apprenant-select" class="form-control"
                    onchange="fillApprenantData(this)">
              <option value="">— Choisir un apprenant inscrit —</option>
              <?php foreach ($apprenants as $a): ?>
              <option value="<?= $a['id'] ?>"
                data-nom="<?= clean($a['nom'] . ' ' . $a['prenom']) ?>"
                data-naissance="<?= clean($a['date_naissance'] ?? '') ?>"
                data-lieu="<?= clean($a['lieu_naissance'] ?? '') ?>"
                data-filiere="<?= clean($a['filiere_nom'] ?? '') ?>"
                data-matricule="<?= clean($a['matricule'] ?? '') ?>"
                <?= (isset($_POST['apprenant_id']) && (int)$_POST['apprenant_id'] === (int)$a['id']) ? 'selected' : '' ?>>
                <?= clean($a['nom'] . ' ' . $a['prenom']) ?> — <?= clean($a['filiere_nom'] ?? 'Sans filière') ?>
                (<?= clean($a['matricule']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="apprenant-preview" class="preview-box" style="display:none">
            <div class="preview-title">Données récupérées automatiquement</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;font-size:12px">
              <div><span style="color:var(--gray-mid)">Nom complet :</span><br>
                   <strong id="prev-nom">—</strong></div>
              <div><span style="color:var(--gray-mid)">Date de naissance :</span><br>
                   <strong id="prev-naissance">—</strong></div>
              <div><span style="color:var(--gray-mid)">Lieu de naissance :</span><br>
                   <strong id="prev-lieu">—</strong></div>
              <div><span style="color:var(--gray-mid)">Filière :</span><br>
                   <strong id="prev-filiere">—</strong></div>
              <div><span style="color:var(--gray-mid)">Matricule :</span><br>
                   <strong id="prev-matricule">—</strong></div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="field">
              <label>Date de début du stage *</label>
              <input type="date" name="start_date" class="form-control"
                     value="<?= clean($_POST['start_date'] ?? '') ?>" required>
            </div>
            <div class="field">
              <label>Date de fin du stage *</label>
              <input type="date" name="end_date" class="form-control"
                     value="<?= clean($_POST['end_date'] ?? '') ?>" required>
            </div>
          </div>

          <div class="field" style="margin-bottom:20px">
            <label>Remarques / Observations</label>
            <textarea name="origin" class="form-control" rows="2"
                      placeholder="Ex : mention bien, spécialisation..."><?= clean($_POST['origin'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:8px;justify-content:flex-end">
            <a href="index.php?tab=apprenants" class="btn btn-outline">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer l'attestation</button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- ════════════════════════════════════════════════════════════════════
       PANEL 2 — STAGIAIRE SIR-TECH
  ═════════════════════════════════════════════════════════════════════════ -->
  <div class="form-panel <?= $tab==='sirtech'?'active':'' ?>" id="panel-sirtech">
    <div class="card">
      <div class="card-header" style="border-left:4px solid var(--green)">
        <h3 class="card-title" style="margin:0;color:var(--green)">
          Attestation pour un stagiaire Sir-Tech
        </h3>
        <small style="color:var(--gray-mid)">
          Délivrée par Sir-Tech — sélectionnez un stagiaire déjà enregistré.
        </small>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_tab" value="sirtech">

          <?php if (empty($stagiairesSirTech)): ?>
            <div class="flash flash-info" style="display:block;margin-bottom:16px">
              Aucun stagiaire Sir-Tech n'est encore enregistré. Ajoutez-en un d'abord dans le module
              <a href="../stagiaires/create.php" style="text-decoration:underline">Stagiaires</a>.
            </div>
          <?php endif; ?>

          <div class="field" style="margin-bottom:20px">

            <div class="field" style="margin-bottom:16px">
                <label>Type d'attestation *</label>

                <select name="sirtech_type" class="form-control">
                    <option value="stage_sirtech">
                        Attestation de fin de stage
                    </option>

                    <option value="formation_sirtech">
                        Attestation de fin de formation
                    </option>
                </select>
            </div>
            <label>Sélectionner le stagiaire Sir-Tech *</label>
            <select name="stagiaire_externe_id" id="sirtech-select" class="form-control"
                    onchange="fillSirtechData(this)">
              <option value="">— Choisir un stagiaire Sir-Tech —</option>
              <?php foreach ($stagiairesSirTech as $s): ?>
              <option value="<?= $s['id'] ?>"
                data-nom="<?= clean($s['nom'] . ' ' . $s['prenom']) ?>"
                data-specialty="<?= clean($s['specialty']) ?>"
                data-start="<?= clean($s['start_date']) ?>"
                data-end="<?= clean($s['end_date']) ?>"
                data-type="<?= clean($s['type']) ?>"
                data-etablissement="<?= clean($s['etablissement']) ?>"
                <?= (isset($_POST['stagiaire_externe_id']) && (int)$_POST['stagiaire_externe_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                <?= clean($s['nom'] . ' ' . $s['prenom']) ?>
                — <?= clean($s['specialty']) ?>
                (<?= clean($s['type']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="sirtech-preview" class="preview-box sirtech" style="display:none">
            <div class="preview-title">Données récupérées depuis la fiche stagiaire</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px">
              <div><span style="color:var(--gray-mid)">Nom complet :</span><br>
                   <strong id="sprev-nom">—</strong></div>
              <div><span style="color:var(--gray-mid)">Établissement :</span><br>
                   <strong id="sprev-etablissement">—</strong></div>
              <div><span style="color:var(--gray-mid)">Spécialité :</span><br>
                   <strong id="sprev-specialty">—</strong></div>
              <div><span style="color:var(--gray-mid)">Type :</span><br>
                   <strong id="sprev-type">—</strong></div>
              <div><span style="color:var(--gray-mid)">Période :</span><br>
                   <strong id="sprev-periode">—</strong></div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="field">
              <label>Date de début <small style="color:var(--gray-mid)">(modifiable si besoin)</small></label>
              <input type="date" name="start_date" id="sirtech-start" class="form-control"
                     value="<?= clean($_POST['start_date'] ?? '') ?>">
            </div>
            <div class="field">
              <label>Date de fin <small style="color:var(--gray-mid)">(modifiable si besoin)</small></label>
              <input type="date" name="end_date" id="sirtech-end" class="form-control"
                     value="<?= clean($_POST['end_date'] ?? '') ?>">
            </div>
          </div>

          <div class="field" style="margin-bottom:20px">
            <label>Remarques / Observations</label>
            <textarea name="origin" class="form-control" rows="2"
                      placeholder="Ex : validation par Sir-Tech, mention..."><?= clean($_POST['origin'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:8px;justify-content:flex-end">
            <a href="index.php?tab=sirtech" class="btn btn-outline">Annuler</a>
            <button type="submit" class="btn btn-success"
                    <?= empty($stagiairesSirTech) ? 'disabled' : '' ?>>
              Créer l'attestation Sir-Tech
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- ════════════════════════════════════════════════════════════════════
       PANEL 3 — STAGIAIRE EXTERNE (autre)
  ═════════════════════════════════════════════════════════════════════════ -->
  <div class="form-panel <?= $tab==='stagiaires'?'active':'' ?>" id="panel-stagiaires">
    <div class="card">
      <div class="card-header" style="border-left:4px solid var(--orange)">
        <h3 class="card-title" style="margin:0;color:var(--orange)">
          Attestation pour un stagiaire externe
        </h3>
        <small style="color:var(--gray-mid)">
          IFP-3IA délivre l'attestation — pour stagiaires académiques ou professionnels.
        </small>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="form_tab" value="stagiaires">

          <div class="field" style="margin-bottom:16px">
            <label>Type de stage *</label>
            <div style="display:flex;gap:16px;margin-top:8px">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:400">
                <input type="radio" name="ext_type" value="academique"
                       <?= ($_POST['ext_type']??'academique')==='academique'?'checked':'' ?>>
                <span class="badge badge-blue">Académique</span>
                <span style="color:var(--gray-mid);font-size:11px">(lycée, université, école...)</span>
              </label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:400">
                <input type="radio" name="ext_type" value="professionnel"
                       <?= ($_POST['ext_type']??'')==='professionnel'?'checked':'' ?>>
                <span class="badge badge-orange">Professionnel</span>
                <span style="color:var(--gray-mid);font-size:11px">(entreprise, organisation...)</span>
              </label>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="field">
              <label>Nom *</label>
              <input type="text" name="ext_nom" class="form-control"
                     value="<?= clean($_POST['ext_nom'] ?? '') ?>"
                     placeholder="Ex : KAMGA" required>
            </div>
            <div class="field">
              <label>Prénom(s)</label>
              <input type="text" name="ext_prenom" class="form-control"
                     value="<?= clean($_POST['ext_prenom'] ?? '') ?>"
                     placeholder="Ex : Jean-Paul">
            </div>
            <div class="field">
              <label>Date de naissance</label>
              <input type="date" name="date_birth" class="form-control"
                     value="<?= clean($_POST['date_birth'] ?? '') ?>">
            </div>
            <div class="field">
              <label>Lieu de naissance</label>
              <input type="text" name="place_birth" class="form-control"
                     value="<?= clean($_POST['place_birth'] ?? '') ?>"
                     placeholder="Ex : Bafoussam">
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="field">
              <label>Établissement / Entreprise d'origine</label>
              <input type="text" name="ext_etablissement" class="form-control"
                     value="<?= clean($_POST['ext_etablissement'] ?? '') ?>"
                     placeholder="Ex : Lycée de Dschang">
            </div>
            <div class="field">
              <label>Spécialité / Domaine du stage *</label>
              <input type="text" name="specialty" class="form-control"
                     value="<?= clean($_POST['specialty'] ?? '') ?>"
                     placeholder="Ex : Génie Logiciel, Réseau et Sécurité..."
                     required>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="field">
              <label>Date de début du stage *</label>
              <input type="date" name="start_date" class="form-control"
                     value="<?= clean($_POST['start_date'] ?? '') ?>" required>
            </div>
            <div class="field">
              <label>Date de fin du stage *</label>
              <input type="date" name="end_date" class="form-control"
                     value="<?= clean($_POST['end_date'] ?? '') ?>" required>
            </div>
          </div>

          <div class="field" style="margin-bottom:20px">
            <label>Remarques / Observations</label>
            <textarea name="origin" class="form-control" rows="2"
                      placeholder="Ex : stage d'observation, projet académique..."><?= clean($_POST['origin'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:8px;justify-content:flex-end">
            <a href="index.php?tab=stagiaires" class="btn btn-outline">Annuler</a>
            <button type="submit" class="btn btn-primary"
                    style="background:var(--orange);box-shadow:none">
              Créer l'attestation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div><!-- /max-width -->

<script>
// ── Bascule entre les 3 onglets ────────────────────────────────────────────
function switchTab(tab) {
  ['apprenants', 'sirtech', 'stagiaires'].forEach(t => {
    document.getElementById('panel-' + t).classList.toggle('active', t === tab);
  });
  setTabState('btn-apprenant', tab === 'apprenants', null);
  setTabState('btn-sirtech',   tab === 'sirtech',    'green');
  setTabState('btn-stagiaire', tab === 'stagiaires', 'orange');
}

function setTabState(id, isActive, colorClass) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.toggle('active', isActive);
  if (colorClass) el.classList.toggle(colorClass, isActive);
}

// ── Prévisualisation Apprenant IFP-3IA ──────────────────────────────────────
function fillApprenantData(select) {
  const opt = select.options[select.selectedIndex];
  const preview = document.getElementById('apprenant-preview');
  if (!opt || !opt.value) { preview.style.display = 'none'; return; }

  document.getElementById('prev-nom').textContent       = opt.dataset.nom       || '—';
  document.getElementById('prev-naissance').textContent = opt.dataset.naissance || '—';
  document.getElementById('prev-lieu').textContent      = opt.dataset.lieu      || '—';
  document.getElementById('prev-filiere').textContent   = opt.dataset.filiere   || '—';
  document.getElementById('prev-matricule').textContent = opt.dataset.matricule || '—';
  preview.style.display = 'block';
}

// ── Prévisualisation Sir-Tech ───────────────────────────────────────────────
function fillSirtechData(select) {
  const opt = select.options[select.selectedIndex];
  const preview = document.getElementById('sirtech-preview');
  if (!opt || !opt.value) { preview.style.display = 'none'; return; }

  document.getElementById('sprev-nom').textContent          = opt.dataset.nom          || '—';
  document.getElementById('sprev-etablissement').textContent = opt.dataset.etablissement || '—';
  document.getElementById('sprev-specialty').textContent    = opt.dataset.specialty    || '—';
  document.getElementById('sprev-type').textContent         = opt.dataset.type         || '—';
  document.getElementById('sprev-periode').textContent      =
       (opt.dataset.start || '?') + ' → ' + (opt.dataset.end || '?');
  preview.style.display = 'block';

  // Pré-remplir les dates (modifiables par l'utilisateur)
  if (opt.dataset.start) document.getElementById('sirtech-start').value = opt.dataset.start;
  if (opt.dataset.end)   document.getElementById('sirtech-end').value   = opt.dataset.end;
}

// Initialisation si déjà sélectionné (erreur POST)
document.addEventListener('DOMContentLoaded', function() {
  const sel = document.getElementById('apprenant-select');
  if (sel && sel.value) fillApprenantData(sel);

  const sel2 = document.getElementById('sirtech-select');
  if (sel2 && sel2.value) fillSirtechData(sel2);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
