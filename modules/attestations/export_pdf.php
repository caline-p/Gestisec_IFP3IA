<?php
/**
 * export_pdf.php — Génération PDF avec DomPDF
 * GestiSec IFP-3IA — 3 types d'attestations
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); exit('Attestation introuvable.'); }

// ── 1. Récupération ──────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT att.*,
            am.category,
            am.apprenant_id,
            am.stagiaire_externe_id,
            ap.matricule       AS apprenant_matricule,
            ap.nom             AS apprenant_nom,
            ap.prenom          AS apprenant_prenom,
            ap.date_naissance  AS apprenant_ddn,
            ap.lieu_naissance  AS apprenant_ldn,
            f.nom              AS filiere_nom,
            se.nom             AS stag_nom,
            se.prenom          AS stag_prenom,
            se.etablissement   AS stag_etablissement,
            se.specialty       AS stag_specialty,
            se.start_date      AS stag_start,
            se.end_date        AS stag_end,
            se.type            AS stagiaire_type
     FROM attestation att
     LEFT JOIN attestation_meta am ON am.attestation_id = att.id
     LEFT JOIN apprenants ap       ON ap.id = am.apprenant_id
     LEFT JOIN filieres f          ON f.id  = ap.filiere_id
     LEFT JOIN stagiaires_externes se ON se.id = am.stagiaire_externe_id
     WHERE att.id = ?"
);
$stmt->execute([$id]);
$att = $stmt->fetch();
if (!$att) { http_response_code(404); exit('Attestation introuvable (id='.$id.').'); }

$category = $att['category'] ?? 'etudiant';
$isStag   = in_array($category, ['stagiaire_externe','stagiaire_academique','stagiaire_professionnel'], true);

// ── 2. Données ───────────────────────────────────────────────
if ($isStag) {
    $nom        = strtoupper(trim(($att['stag_nom']??'').' '.($att['stag_prenom']??'')));
    if (!$nom)   $nom = strtoupper($att['name'] ?? 'INCONNU');
    $specialty  = $att['stag_specialty'] ?? ($att['specialty']  ?? '');
    $startDate  = $att['stag_start']     ?? ($att['start_date'] ?? null);
    $endDate    = $att['stag_end']       ?? ($att['end_date']   ?? null);
    $birthDate  = $att['date_birth']     ?? null;
    $birthPlace = $att['place_birth']    ?? '..........';
} else {
    $nom        = strtoupper(trim(($att['apprenant_nom']??'').' '.($att['apprenant_prenom']??'')));
    if (!$nom)   $nom = strtoupper($att['name'] ?? 'INCONNU');
    $specialty  = $att['specialty']     ?? '';
    $startDate  = $att['start_date']    ?? null;
    $endDate    = $att['end_date']      ?? null;
    $birthDate  = $att['apprenant_ddn'] ?? ($att['date_birth'] ?? null);
    $birthPlace = $att['apprenant_ldn'] ?? ($att['place_birth'] ?? '..........');
}

function fmtD($v): string {
    $v = trim((string)$v);
    if (!$v || $v === '0000-00-00') return '..........';
    if (preg_match('#\d{2}/\d{2}/\d{4}#', $v)) return $v;
    $ts = strtotime($v);
    return $ts ? date('d/m/Y', $ts) : $v;
}

$dNais     = fmtD($birthDate);
$dDebut    = fmtD($startDate);
$dFin      = fmtD($endDate);
$dateDeliv = date('d/m/Y');

// ── 3. Images base64 ─────────────────────────────────────────
$a = realpath(__DIR__ . '/../../assets') . '/';

function b64img(string $path, string $mime = 'image/png'): string {
    return file_exists($path)
        ? 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path))
        : '';
}

$imgBorderTop    = b64img($a . 'cert_border_top.png');
$imgBorderSide   = b64img($a . 'cert_border_side.png');
$imgLogoIFP      = b64img($a . 'cert_logo_ifp.png');
$imgLogoSIR      = b64img($a . 'cert_logo_sir.png');
$imgPhoneIcon    = b64img($a . 'cert_phone_icon.png');
$imgLocationIcon = b64img($a . 'cert_location_icon.png');

// ── 4. CSS commun ─────────────────────────────────────────────
$cssBase = "
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: Georgia, serif;
    font-size: 12pt;
    color: #1a2e6b;
    background: #fff;
    width: 270mm;
}
.page {
    width: 270mm;
    min-height: 185mm;
    position: relative;
}
.border-top {
    width: 270mm;
    height: auto;
    display: block;
}
.border-bottom {
    width: 270mm;
    height: auto;
    display: block;
    -dompdf-image-scale-factor: 1;
    transform: scaleY(-1);
}
.content-wrap {
    margin: 0 15mm;
    padding: 3mm 6mm;
}
.sep {
    border: none;
    border-top: 2px solid #1a2e6b;
    margin: 2mm 0;
}
";

// ════════════════════════════════════════════════════════════
// TEMPLATE 1 — IFP-3IA : Attestation de fin de formation
// ════════════════════════════════════════════════════════════
if ($category === 'etudiant') {

    $prefix = 'ATTEST_FORMATION_IFP';

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
    $cssBase
    .header-table { width:100%; border-collapse:collapse; margin-bottom:2mm; }
    .header-table td { vertical-align:middle; }
    .logo-cell { width:22mm; text-align:center; }
    .logo-cell img { width:18mm; height:auto; }
    .inst-cell { padding-left:4mm; }
    .inst-name {
        font-size: 15pt; font-weight: bold;
        color: #1a2e6b; text-transform: uppercase;
        line-height: 1.3;
    }
    .arrete {
        text-align: center; font-size: 8pt;
        font-style: italic; font-weight: bold;
        color: #1a2e6b; margin: 1.5mm 0 2mm;
    }
    .cert-title {
        text-align: center; font-size: 19pt;
        font-weight: bold; color: #1a2e6b;
        text-transform: uppercase; letter-spacing: 1pt;
        border-top: 2px solid #1a2e6b;
        border-bottom: 2px solid #1a2e6b;
        padding: 3mm 0; margin: 2mm 0 4mm;
    }
    .cert-body {
        font-size: 12pt; line-height: 2.2;
        text-align: center; color: #1a2e6b;
    }
    .footer-table { width:100%; border-collapse:collapse; margin-top:6mm; }
    .footer-table td { vertical-align:bottom; }
    .phone-wrap { font-size:10pt; font-weight:bold; color:#1a2e6b; }
    .phone-wrap img { width:6mm; height:auto; vertical-align:middle; margin-right:3px; }
    .fait { font-size:11pt; color:#1a2e6b; margin-bottom:10mm; }
    .sig {
        font-size: 13pt; font-style: italic;
        color: #1a2e6b; display: inline-block;
        border-top: 1px solid #1a2e6b;
        padding-top: 1.5mm; min-width: 38mm;
        text-align: center;
    }
    </style></head><body>
    <div class='page'>
      <img class='border-top' src='$imgBorderTop'>
      <div class='content-wrap'>

        <table class='header-table'>
          <tr>
            <td class='logo-cell'><img src='$imgLogoIFP' alt='Logo'></td>
            <td class='inst-cell'>
              <div class='inst-name'>INSTUTUT DE FORMATION PROFESSIONNEL<br>EN INGENIERIE INFORMATIQUE APPLIQUEE</div>
            </td>
          </tr>
        </table>

        <hr class='sep'>
        <div class='arrete'>ARRETE/ORDER N°000366/MINFOP/SG/DFOP/SDGSF/CSACD/CSACD/CBAC du/of 10 juin 2025</div>

        <div class='cert-title'>ATTESTATION DE FIN DE FORMATION</div>

        <div class='cert-body'>
          Nous soussignons, IFP-3IA, certifions par la présente que <b>$nom</b>, né le<br>
          <b>$dNais</b> à <b>$birthPlace</b>, a suivie régulièrement une<br>
          formation professionnelle au sein de notre institut du <b>$dDebut</b> au<br>
          <b>$dFin</b> en <b>$specialty</b>. L'intéressé a achevé la formation et à composer un<br>
          examen de Certificat de Qualification Professionnel (CQP) dont les résultats<br>
          sont en attente de publication.
        </div>

        <table class='footer-table'>
          <tr>
            <td style='width:50%'>
              <div class='phone-wrap'>
                <img src='$imgPhoneIcon' alt='Tel'> 699159058 / 6 52430272
              </div>
            </td>
            <td style='width:50%; text-align:right;'>
              <div class='fait'>Fait à Dschang, le _______________</div>
              <div class='sig'>Signature</div>
            </td>
          </tr>
        </table>

      </div>
      <img class='border-bottom' src='$imgBorderTop'>
    </div>
    </body></html>";

// ════════════════════════════════════════════════════════════
// TEMPLATE 2 — SIR-TECH : Attestation fin de formation externe
// ════════════════════════════════════════════════════════════
} elseif ($category === 'stagiaire_externe') {

    $prefix = 'ATTEST_FORMATION_SIRTECH';

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
    $cssBase
    .header-table { width:100%; border-collapse:collapse; margin-bottom:1mm; }
    .header-table td { vertical-align:middle; }
    .logo-cell { width:28mm; text-align:center; }
    .logo-cell img { width:24mm; height:auto; }
    .ets-name {
        font-size: 22pt; font-weight: bold;
        color: #1a2e6b; letter-spacing: 1pt;
    }
    .rccm {
        text-align:center; font-size:9pt;
        font-weight:bold; color:#1a2e6b; margin:1mm 0;
    }
    .cert-title {
        font-size: 17pt; font-weight: bold;
        color: #1a2e6b; text-align: center;
        text-decoration: underline;
        margin: 2mm 0 4mm;
    }
    .cert-body {
        font-size: 12pt; line-height: 2.1;
        text-align: center; color: #1a2e6b;
        font-style: italic;
    }
    .cert-body b { font-style: italic; }
    .spec { font-size:13pt; font-weight:bold; }
    .footer-table { width:100%; border-collapse:collapse; margin-top:5mm; }
    .footer-table td { vertical-align:bottom; }
    .contact-line { font-size:9.5pt; font-weight:bold; color:#1a2e6b; margin-bottom:2mm; }
    .contact-line img { width:5mm; height:auto; vertical-align:middle; margin-right:3px; }
    .website { font-size:9pt; font-weight:bold; color:#1a2e6b; text-align:center; margin-top:2mm; }
    .fait { font-size:10.5pt; color:#1a2e6b; margin-bottom:10mm; }
    .sig {
        font-size:12pt; font-style:italic; color:#1a2e6b;
        display:inline-block; border-top:1px solid #1a2e6b;
        padding-top:1.5mm; min-width:35mm; text-align:center;
    }
    </style></head><body>
    <div class='page'>
      <img class='border-top' src='$imgBorderTop'>
      <div class='content-wrap'>

        <table class='header-table'>
          <tr>
            <td class='logo-cell'><img src='$imgLogoSIR' alt='Logo SIR'></td>
            <td style='padding-left:4mm;'>
              <div class='ets-name'>ETS SIR-TECH</div>
            </td>
          </tr>
        </table>
        <div class='rccm'>RCCM: RC/Dschang/2021/A/05</div>
        <hr class='sep'>

        <div class='cert-title'>Attestation De Fin De Formation</div>

        <div class='cert-body'>
          Nous soussignons, ETS SIR-TECH, certifions par la présente que<br>
          <b>$nom</b>, né le <b>$dNais</b> à <b>$birthPlace</b>,<br>
          a effectué une formation au sein de notre entreprise<br>
          du <b>$dDebut</b> au <b>$dFin</b> en<br>
          <span class='spec'>$specialty</span>
        </div>
        <div class='cert-body' style='margin-top:3mm;'>
          En foi de quoi, le présent document lui est délivré pour servir et valoir
        </div>

        <table class='footer-table'>
          <tr>
            <td style='width:45%'>
              <div class='contact-line'><img src='$imgPhoneIcon' alt='Tel'> 699159058 / 6 52430272</div>
              <div class='contact-line'><img src='$imgLocationIcon' alt='Loc'> DSCHANG, MARCHE FOTO</div>
            </td>
            <td style='width:20%; text-align:center;'>
              <div class='website'>WWW.SIR-TECH.ORG</div>
            </td>
            <td style='width:35%; text-align:right;'>
              <div class='fait'>Fait à Dschang, le _______________</div>
              <div class='sig'>Signature</div>
            </td>
          </tr>
        </table>

      </div>
      <img class='border-bottom' src='$imgBorderTop'>
    </div>
    </body></html>";

// ════════════════════════════════════════════════════════════
// TEMPLATE 3 — SIR-TECH : Attestation fin de stage
// ════════════════════════════════════════════════════════════
} else {

    $prefix = 'ATTEST_STAGE_SIRTECH';

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
    $cssBase
    .header-table { width:100%; border-collapse:collapse; margin-bottom:1mm; }
    .header-table td { vertical-align:middle; }
    .logo-cell { width:28mm; text-align:center; }
    .logo-cell img { width:24mm; height:auto; }
    .ets-name {
        font-size: 22pt; font-weight: bold;
        color: #1a2e6b; letter-spacing: 1pt;
    }
    .rccm {
        text-align:center; font-size:9pt;
        font-weight:bold; color:#1a2e6b; margin:1mm 0;
    }
    .cert-title {
        font-size: 17pt; font-weight: bold;
        color: #1a2e6b; text-align: center;
        text-decoration: underline;
        margin: 2mm 0 4mm;
    }
    .cert-body {
        font-size: 12pt; line-height: 2.1;
        text-align: center; color: #1a2e6b;
        font-style: italic;
    }
    .cert-body b { font-style: italic; }
    .spec { font-size:13pt; font-weight:bold; }
    .footer-table { width:100%; border-collapse:collapse; margin-top:5mm; }
    .footer-table td { vertical-align:bottom; }
    .contact-line { font-size:9.5pt; font-weight:bold; color:#1a2e6b; margin-bottom:2mm; }
    .contact-line img { width:5mm; height:auto; vertical-align:middle; margin-right:3px; }
    .website { font-size:9pt; font-weight:bold; color:#1a2e6b; text-align:center; margin-top:2mm; }
    .fait { font-size:10.5pt; color:#1a2e6b; margin-bottom:10mm; }
    .sig {
        font-size:12pt; color:#1a2e6b;
        display:inline-block; border-top:1px solid #1a2e6b;
        padding-top:1.5mm; min-width:35mm; text-align:center;
    }
    </style></head><body>
    <div class='page'>
      <img class='border-top' src='$imgBorderTop'>
      <div class='content-wrap'>

        <table class='header-table'>
          <tr>
            <td class='logo-cell'><img src='$imgLogoSIR' alt='Logo SIR'></td>
            <td style='padding-left:4mm;'>
              <div class='ets-name'>ETS SIR-TECH</div>
            </td>
          </tr>
        </table>
        <div class='rccm'>RCCM: RC/Dschang/2021/A/05</div>
        <hr class='sep'>

        <div class='cert-title'>Attestation De Fin De Stage</div>

        <div class='cert-body'>
          Nous soussignons, ETS SIR-TECH, certifions par la présente que<br>
          <b>$nom</b>, né le <b>$dNais</b> à <b>$birthPlace</b>,<br>
          a effectué un stage académique au sein de notre entreprise<br>
          du <b>$dDebut</b> au <b>$dFin</b> en<br>
          <span class='spec'>$specialty</span>
        </div>
        <div class='cert-body' style='margin-top:3mm;'>
          En foi de quoi, le présent document lui est délivré pour servir et valoir
        </div>

        <table class='footer-table'>
          <tr>
            <td style='width:45%'>
              <div class='contact-line'><img src='$imgPhoneIcon' alt='Tel'> 699159058 / 6 52430272</div>
              <div class='contact-line'><img src='$imgLocationIcon' alt='Loc'> DSCHANG, MARCHE FOTO</div>
            </td>
            <td style='width:20%; text-align:center;'>
              <div class='website'>WWW.SIR-TECH.ORG</div>
            </td>
            <td style='width:35%; text-align:right;'>
              <div class='fait'>Fait à Dschang, le _______________</div>
              <div class='sig'>Signature</div>
            </td>
          </tr>
        </table>

      </div>
      <img class='border-bottom' src='$imgBorderTop'>
    </div>
    </body></html>";
}

// ── 5. Génération avec DomPDF ────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Georgia');
$options->set('dpi', 150);

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

$filename = $prefix
    . '_' . preg_replace('/[^A-Za-z0-9_]/', '_', substr($nom, 0, 25))
    . '_' . date('Ymd') . '.pdf';

$dompdf->stream($filename, ['Attachment' => true]);
exit;
