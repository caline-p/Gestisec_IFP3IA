<?php
/**
 * export_pdf.php — Génération PDF des attestations (DomPDF)
 * GestiSec IFP-3IA — 3 modèles calqués sur le dossier /templates :
 *   - etudiant            -> IFP-3IA  : Attestation de fin de formation
 *   - stagiaire_externe   -> SIR-TECH : Attestation de fin de formation
 *   - (autre stagiaire)   -> SIR-TECH : Attestation de fin de stage
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
    "SELECT att.*, am.category
     FROM attestation att
     LEFT JOIN attestation_meta am ON am.attestation_id = att.id
     WHERE att.id = ?"
);
$stmt->execute([$id]);
$att = $stmt->fetch();
if (!$att) { http_response_code(404); exit('Attestation introuvable (id='.$id.').'); }

$category = $att['category'] ?? 'etudiant';

// ── 2. Données ───────────────────────────────────────────────
// On utilise l'instantané enregistré dans `attestation` au moment de la
// création (cf. create.php) : c'est la source affichée dans la liste, et
// elle reste stable même si la fiche apprenant/stagiaire liée évolue.
$nom        = strtoupper(trim($att['name'] ?? ''));
if (!$nom)   $nom = 'INCONNU';
$specialty  = $att['specialty']   ?? '';
$startDate  = $att['start_date']  ?? null;
$endDate    = $att['end_date']    ?? null;
$birthDate  = $att['date_birth']  ?? null;
$birthPlace = $att['place_birth'] ?? '..........';

function fmtD($v): string {
    $v = trim((string)$v);
    if (!$v || $v === '0000-00-00') return '..........';
    if (preg_match('#\d{2}/\d{2}/\d{4}#', $v)) return $v;
    $ts = strtotime($v);
    return $ts ? date('d/m/Y', $ts) : $v;
}

$data = [
    'nom'        => $nom,
    'dNais'      => fmtD($birthDate),
    'birthPlace' => $birthPlace ?: '..........',
    'dDebut'     => fmtD($startDate),
    'dFin'       => fmtD($endDate),
    'specialty'  => $specialty,
];

// ── 3. Images (encodées en base64 pour DomPDF) ───────────────
// NB : les noms de fichiers dans /assets sont historiques et ne
// correspondent pas toujours à leur contenu réel (corrigé ici).
$assets = realpath(__DIR__ . '/../../assets') . '/';

function b64img(string $path, string $mime = 'image/png'): string {
    return file_exists($path)
        ? 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path))
        : '';
}

$img = [
    'frame'   => b64img($assets . 'cert_frame.png'),       // cadre ornemental (4 coins)
    'logoIFP' => b64img($assets . 'cert_border_top.png'),  // logo 3iA
    'logoSIR' => b64img($assets . 'cert_logo_sir.png'),    // logo SIR-TECH
    'phone'   => b64img($assets . 'cert_logo.png'),        // icône téléphone
    'loc'     => b64img($assets . 'cert_location_icon.png'), // icône localisation
    'qr'      => b64img($assets . 'cert_phone.png'),       // QR code
];

// ── 4. Construction du HTML calqué sur /templates ────────────
/**
 * Renvoie le HTML complet d'une attestation selon sa catégorie.
 */
function buildAttestationHtml(string $category, array $d, array $img): string
{
    // -- CSS commun aux 3 modèles -----------------------------
    $cssBase = "
    * { margin:0; padding:0; box-sizing:border-box; }
    @page { margin:0; }
    body { font-family: Georgia, serif; color:#1a2e6b; }
    .page { position:relative; width:297mm; height:210mm; overflow:hidden; }
    .frame { position:absolute; top:15mm; left:15mm; width:267mm; height:180mm; }
    .frame-line  { position:absolute; top:15mm; left:15mm; width:267mm; height:180mm; border:1.2px solid #1a2e6b; }
    .frame-line2 { position:absolute; top:18mm; left:18mm; width:261mm; height:174mm; border:1px solid #1a2e6b; }
    .watermark { position:absolute; top:60mm; left:0; width:297mm; text-align:center; white-space:nowrap;
                 font-size:175pt; font-weight:bold; color:#1a2e6b; opacity:0.07;
                 letter-spacing:4pt; transform:rotate(-20deg); }
    .vbox { position:absolute; top:20mm; left:34mm; width:229mm; height:168mm; border-collapse:collapse; }
    .vcell { height:168mm; vertical-align:middle; }
    .header-table { width:100%; border-collapse:collapse; }
    .header-table td { vertical-align:middle; }
    .sep { border:none; border-top:2px solid #1a2e6b; margin:2mm 0; }
    .cert-body { text-align:center; color:#1a2e6b; }
    .footer-table { width:130mm; margin:0 auto; border-collapse:collapse; }
    .footer-table td { vertical-align:bottom; }
    .fait { font-size:11pt; margin-bottom:11mm; }
    .sig { font-size:13pt; font-style:italic; display:inline-block;
           border-top:1px solid #1a2e6b; padding-top:1.5mm; min-width:42mm; text-align:center; }
    .ico { width:6mm; height:auto; vertical-align:middle; margin-right:3px; }
    ";

    $nom = $d['nom']; $dNais = $d['dNais']; $birthPlace = $d['birthPlace'];
    $dDebut = $d['dDebut']; $dFin = $d['dFin']; $specialty = $d['specialty'];

    // ── Modèle 1 — IFP-3IA : fin de formation ────────────────
    if ($category === 'etudiant') {
        $watermark = 'IFP-3IA';
        $css = $cssBase . "
        .logo-head { text-align:center; margin-bottom:2mm; }
        .logo-head img { width:30mm; height:auto; vertical-align:middle; margin-right:6mm; }
        .inst-name { display:inline-block; vertical-align:middle; text-align:center; max-width:160mm;
                     font-size:16pt; font-weight:bold; text-transform:uppercase; line-height:1.25; }
        .arrete { text-align:center; font-size:8pt; font-style:italic; font-weight:bold; margin:1.5mm 0 2mm; }
        .cert-title { text-align:center; font-size:26pt; font-weight:bold; text-transform:uppercase;
                      letter-spacing:1pt; border-top:2px solid #1a2e6b; border-bottom:2px solid #1a2e6b;
                      padding:3mm 0; margin:2mm 0 5mm; }
        .cert-body { font-size:14pt; line-height:1.9; }
        .qr { width:20mm; height:auto; }
        .phone-line { font-size:10pt; font-weight:bold; }
        ";
        $body = "
        <div class='logo-head'>
          <img src='{$img['logoIFP']}' alt='Logo'>
          <span class='inst-name'>INSTUTUT DE FORMATION PROFESSIONNEL<br>EN INGENIERIE INFORMATIQUE APPLIQUEE</span>
        </div>
        <hr class='sep'>
        <div class='arrete'>ARRETE/ORDER N&deg;000366/MINFOP/SG/DFOP/SDGSF/CSACD/CSACD/CBAC du/of 10 juin 2025</div>
        <div class='cert-title'>ATTESTATION DE FIN DE FORMATION</div>
        <div class='cert-body'>
          Nous soussignons, IFP-3IA, certifions par la pr&eacute;sente que <b>$nom</b>, n&eacute; le
          <b>$dNais</b> &agrave; <b>$birthPlace</b>, a suivie r&eacute;guli&egrave;rement une formation
          professionnelle au sein de notre institut du <b>$dDebut</b> au <b>$dFin</b> en <b>$specialty</b>.
        </div>
        <div class='cert-body' style='margin-top:5mm;'>
          En foi de quoi, le pr&eacute;sent document lui est d&eacute;livr&eacute; pour servir et valoir ce que de droit.
        </div>";
        $footer = "
        <table class='footer-table'>
          <tr>
            <td style='width:40%'>
              <div class='phone-line'><img class='ico' src='{$img['phone']}' alt='Tel'> 699159058 / 6 52430272</div>
            </td>
            <td style='width:24%; text-align:center;'><img class='qr' src='{$img['qr']}' alt='QR'></td>
            <td style='width:36%; text-align:right;'>
              <div class='fait'>Fait &agrave; Dschang, le _______________</div>
              <div class='sig'>Signature</div>
            </td>
          </tr>
        </table>";

    // ── Modèles 2 & 3 — SIR-TECH : formation / stage ─────────
    } else {
        $watermark = 'SIR-TECH';
        if ($category === 'stagiaire_externe') {
            $titre  = 'Attestation De Fin De Formation';
            $phrase = 'a effectu&eacute; une formation au sein de notre entreprise';
        } else {
            $titre  = 'Attestation De Fin De Stage';
            $phrase = 'a effectu&eacute; un stage acad&eacute;mique au sein de notre entreprise';
        }
        $css = $cssBase . "
        .logo-head { text-align:center; margin-bottom:2mm; }
        .logo-head img { width:40mm; height:auto; vertical-align:middle; margin-right:6mm; }
        .ets-name { display:inline-block; vertical-align:middle; font-size:38pt; font-weight:bold; letter-spacing:1pt; }
        .rccm { text-align:center; font-size:9pt; font-weight:bold; margin:1mm 0; }
        .cert-title { text-align:center; font-size:22pt; font-weight:bold; text-decoration:underline; margin:3mm 0 5mm; }
        .cert-body { font-size:14pt; line-height:1.9; font-style:italic; }
        .spec { font-size:13pt; font-weight:bold; }
        .contact-line { font-size:9.5pt; font-weight:bold; margin-bottom:2mm; }
        .website { font-size:8pt; font-weight:bold; text-align:center; white-space:nowrap; }
        .vcell { vertical-align:top; padding-top:10mm; }
        ";
        $body = "
        <div class='logo-head'>
          <img src='{$img['logoSIR']}' alt='Logo SIR'>
          <span class='ets-name'>ETS SIR-TECH</span>
        </div>
        <div class='rccm'>RCCM: RC/Dschang/2021/A/05</div>
        <hr class='sep'>
        <div class='cert-title'>$titre</div>
        <div class='cert-body'>
          Nous soussignons, ETS SIR-TECH, certifions par la pr&eacute;sente que <b>$nom</b>, n&eacute; le
          <b>$dNais</b> &agrave; <b>$birthPlace</b>, $phrase du <b>$dDebut</b> au <b>$dFin</b> en
          <span class='spec'>$specialty</span>
        </div>
        <div class='cert-body' style='font-style:normal; margin-top:5mm;'>
          En foi de quoi, le pr&eacute;sent document lui est d&eacute;livr&eacute; pour servir et valoir ce que de droit.
        </div>";
        $footer = "
        <table class='footer-table' style='margin-top:30mm;'>
          <tr>
            <td style='width:40%'>
              <div class='contact-line'><img class='ico' src='{$img['phone']}' alt='Tel'> 699159058 / 6 52430272</div>
              <div class='contact-line'><img class='ico' src='{$img['loc']}' alt='Loc'> DSCHANG, MARCHE FOTO</div>
            </td>
            <td style='width:26%; text-align:center;'><div class='website'>WWW.SIR-TECH.ORG</div></td>
            <td style='width:34%; text-align:right;'>
              <div class='fait'>Fait &agrave; Dschang, le _______________</div>
              <div class='sig'>Signature</div>
            </td>
          </tr>
        </table>";
    }

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>$css</style></head><body>
      <div class='page'>
        <div class='frame-line'></div>
        <div class='frame-line2'></div>
        <img class='frame' src='{$img['frame']}'>
        <div class='watermark'>$watermark</div>
        <table class='vbox'><tr><td class='vcell'>$body $footer</td></tr></table>
      </div>
    </body></html>";
}

// ── 5. Préfixe du fichier selon le modèle ────────────────────
if ($category === 'etudiant') {
    $prefix = 'ATTEST_FORMATION_IFP';
} elseif ($category === 'stagiaire_externe') {
    $prefix = 'ATTEST_FORMATION_SIRTECH';
} else {
    $prefix = 'ATTEST_STAGE_SIRTECH';
}

// ── 6. Génération avec DomPDF ────────────────────────────────
$html = buildAttestationHtml($category, $data, $img);

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
