<?php
// recu.php — Impression reçu de paiement
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
$db = getDB();
$id = (int)($_GET['id']??0);
$stmt = $db->prepare("SELECT p.*,a.nom,a.prenom,a.matricule,f.nom AS filiere_nom,f.cout,u.nom AS agent_nom,u.prenom AS agent_prenom,
    COALESCE((SELECT SUM(montant) FROM paiements WHERE apprenant_id=a.id),0) AS total_paye
    FROM paiements p JOIN apprenants a ON p.apprenant_id=a.id LEFT JOIN filieres f ON a.filiere_id=f.id
    LEFT JOIN utilisateurs u ON p.enregistre_par=u.id WHERE p.id=?");
$stmt->execute([$id]); $p=$stmt->fetch();
if(!$p){die('Reçu introuvable.');}
$reste = $p['cout'] - $p['total_paye'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu <?= clean($p['reference']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#222;background:#fff;padding:20px}
.recu{max-width:500px;margin:0 auto;border:1px solid #ccc;border-radius:8px;overflow:hidden}
.recu-header{background:#1F3864;color:#fff;padding:20px 24px;text-align:center}
.recu-header h1{font-size:16px;font-weight:700;margin-bottom:4px}
.recu-header p{font-size:11px;opacity:.7}
.recu-ref{background:#2E75B6;color:#fff;padding:8px 24px;text-align:center;font-size:12px;font-weight:500}
.recu-body{padding:20px 24px}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.row:last-child{border-bottom:none}
.row .lbl{color:#666;font-weight:500}
.row .val{font-weight:600;color:#1F3864;text-align:right}
.montant-box{background:#EBF3FB;border:2px solid #2E75B6;border-radius:8px;padding:16px;text-align:center;margin:14px 0}
.montant-box .lbl{font-size:12px;color:#666;margin-bottom:4px}
.montant-box .val{font-size:24px;font-weight:700;color:#1F3864}
.reste-box{background:<?= $reste<=0?'#e8f5e9':'#fce8e8' ?>;border-radius:6px;padding:10px 14px;text-align:center;margin-bottom:14px;font-size:13px}
.recu-footer{background:#f9f9f9;padding:14px 24px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee}
.sig-area{display:flex;justify-content:space-between;margin-top:20px;padding-top:10px;border-top:1px dashed #ccc}
.sig-box{text-align:center;width:45%}
.sig-box .sig-label{font-size:11px;color:#666;margin-bottom:30px}
.sig-box .sig-line{border-top:1px solid #999;padding-top:4px;font-size:11px;color:#333}
@media print{body{padding:0}.no-print{display:none!important}}
</style>
</head>
<body>
<div class="no-print" style="max-width:500px;margin:0 auto 14px;display:flex;gap:10px">
  <button onclick="window.print()" style="background:#1F3864;color:#fff;border:none;padding:9px 20px;border-radius:7px;cursor:pointer;font-size:13px;font-weight:500">🖨 Imprimer</button>
  <a href="index.php" style="background:#f0f0f0;color:#333;border:none;padding:9px 20px;border-radius:7px;text-decoration:none;font-size:13px">← Retour</a>
</div>

<div class="recu">
  <div class="recu-header">
    <h1><?= APP_NAME ?></h1>
    <p>Centre de Formation Professionnelle en Génie Informatique</p>
    <p style="margin-top:6px;opacity:1;font-size:14px;font-weight:600">REÇU DE PAIEMENT</p>
  </div>
  <div class="recu-ref">Référence : <?= clean($p['reference']) ?> &nbsp;|&nbsp; Date : <?= dateFR($p['date_paiement']) ?></div>
  <div class="recu-body">
    <div class="row"><span class="lbl">Apprenant</span><span class="val"><?= clean($p['nom'].' '.$p['prenom']) ?></span></div>
    <div class="row"><span class="lbl">Matricule</span><span class="val"><?= clean($p['matricule']) ?></span></div>
    <div class="row"><span class="lbl">Filière</span><span class="val"><?= clean($p['filiere_nom']??'—') ?></span></div>
    <div class="row"><span class="lbl">Mois concerné</span><span class="val"><?= clean($p['mois_concerne']??'—') ?></span></div>
    <div class="row"><span class="lbl">Mode de paiement</span><span class="val"><?= ucfirst(str_replace('_',' ',$p['mode_paiement'])) ?></span></div>
    <?php if($p['observations']): ?>
    <div class="row"><span class="lbl">Observations</span><span class="val" style="font-size:12px"><?= clean($p['observations']) ?></span></div>
    <?php endif; ?>

    <div class="montant-box">
      <div class="lbl">MONTANT ENCAISSÉ</div>
      <div class="val"><?= money((float)$p['montant']) ?></div>
    </div>

    <div class="reste-box">
      <?php if($reste<=0): ?>
      <strong style="color:#1e7145">✓ Compte soldé — Aucun montant restant dû</strong>
      <?php else: ?>
      <strong style="color:#c00">Reste à payer : <?= money($reste) ?></strong>
      <?php endif; ?>
    </div>

    <div class="sig-area">
      <div class="sig-box">
        <div class="sig-label">Signature de l'apprenant</div>
        <div class="sig-line">Nom & Signature</div>
      </div>
      <div class="sig-box">
        <div class="sig-label">Cachet & Signature du responsable</div>
        <div class="sig-line"><?= clean(($p['agent_prenom']??'').' '.($p['agent_nom']??'Secrétariat')) ?></div>
      </div>
    </div>
  </div>
  <div class="recu-footer">
    Document généré le <?= date('d/m/Y à H:i') ?> — <?= APP_NAME ?> &nbsp;|&nbsp; Ce reçu fait foi de paiement
  </div>
</div>
</body>
</html>
