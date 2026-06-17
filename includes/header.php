<?php
// includes/header.php — En-tête commun à toutes les pages
requireLogin();
$flash = getFlash();
$user  = currentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<div class="app-shell">

  <!-- ── SIDEBAR ─────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-top">
        <div class="brand-logo">G</div>
        <div>
          <div class="brand-name"><?= APP_NAME ?></div>
          <div class="brand-tagline">Gestion Administrative</div>
        </div>
      </div>
      <div class="brand-full"><?= APP_FULL_NAME ?></div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">Principal</div>
      <a href="<?= APP_URL ?>/dashboard.php"
         class="nav-item <?= ($activePage??'')==='dashboard'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <rect x="1" y="1" width="6" height="6" rx="1.5" fill="currentColor"/>
          <rect x="9" y="1" width="6" height="6" rx="1.5" fill="currentColor"/>
          <rect x="1" y="9" width="6" height="6" rx="1.5" fill="currentColor"/>
          <rect x="9" y="9" width="6" height="6" rx="1.5" fill="currentColor"/>
        </svg>
        Tableau de bord
      </a>

      <div class="nav-section">Scolarité</div>
      <a href="<?= APP_URL ?>/modules/apprenants/index.php"
         class="nav-item <?= ($activePage??'')==='apprenants'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <circle cx="8" cy="6" r="3.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
        Apprenants
      </a>
      <a href="<?= APP_URL ?>/modules/filieres/index.php"
         class="nav-item <?= ($activePage??'')==='filieres'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
        Filières
      </a>

      <div class="nav-section">Secrétariat</div>
      <a href="<?= APP_URL ?>/modules/attestations/index.php"
         class="nav-item <?= ($activePage??'')==='attestations'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M3 2h7l3 3v9H3V2z" stroke="currentColor" stroke-width="1.4"/>
          <path d="M10 2v3h3" stroke="currentColor" stroke-width="1.2"/>
          <path d="M5 7h6M5 9.5h6M5 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
        Attestations
      </a>
      <a href="<?= APP_URL ?>/modules/courrier/index.php"
         class="nav-item <?= ($activePage??'')==='courrier'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <rect x="1" y="3" width="14" height="10" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M1 5l7 5 7-5" stroke="currentColor" stroke-width="1.4"/>
        </svg>
        Courrier
      </a>
      <a href="<?= APP_URL ?>/modules/reunions/index.php"
         class="nav-item <?= ($activePage??'')==='reunions'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <circle cx="5" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.3"/>
          <circle cx="11" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.3"/>
          <path d="M1 13c0-2.2 1.8-4 4-4s4 1.8 4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
          <path d="M9 13c0-2.2 1.8-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
        Réunions
      </a>
      <a href="<?= APP_URL ?>/modules/plannings/index.php"
         class="nav-item <?= ($activePage??'')==='plannings'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <rect x="1" y="3" width="14" height="11" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
          <line x1="1" y1="7" x2="15" y2="7" stroke="currentColor" stroke-width="1.3"/>
          <line x1="5" y1="1" x2="5" y2="5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="11" y1="1" x2="11" y2="5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Plannings
      </a>

      <div class="nav-section">Ressources Humaines</div>
      <a href="<?= APP_URL ?>/modules/personnel/index.php"
         class="nav-item <?= ($activePage??'')==='personnel'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <circle cx="6" cy="6" r="3" stroke="currentColor" stroke-width="1.4"/>
          <circle cx="11.5" cy="5" r="2.5" stroke="currentColor" stroke-width="1.2"/>
          <path d="M1 14c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          <path d="M11.5 9c2 0 3.5 1.6 3.5 3.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
        Personnel
      </a>

      <div class="nav-section">Finances</div>
      <a href="<?= APP_URL ?>/modules/paiements/index.php"
         class="nav-item <?= ($activePage??'')==='paiements'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <rect x="1" y="4" width="14" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
          <line x1="1" y1="7.5" x2="15" y2="7.5" stroke="currentColor" stroke-width="1.3"/>
          <circle cx="5" cy="10" r="1.2" fill="currentColor"/>
        </svg>
        Paiements
      </a>
      <a href="<?= APP_URL ?>/modules/rapports/index.php"
         class="nav-item <?= ($activePage??'')==='rapports'?'active':'' ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <polyline points="2,13 5,8 8,10 11,5 14,7" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Rapports
      </a>
    </nav>

    <div class="sidebar-user">
      <div class="avatar"><?= strtoupper(substr($user['prenom'],0,1).substr($user['nom'],0,1)) ?></div>
      <div class="user-info">
        <div class="uname"><?= clean($user['prenom']) ?> <?= clean($user['nom']) ?></div>
        <div class="urole"><?= ucfirst(clean($user['role'])) ?></div>
      </div>
      <a href="<?= APP_URL ?>/logout.php" class="logout-btn" title="Déconnexion">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M5 2H2a1 1 0 00-1 1v8a1 1 0 001 1h3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
          <path d="M9 10l3-3-3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
          <line x1="5" y1="7" x2="12" y2="7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
        </svg>
      </a>
    </div>
  </aside>

  <!-- ── MAIN ──────────────────────────────────────────────── -->
  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title"><?= clean($pageTitle ?? 'Tableau de bord') ?></h1>
        <?php if(!empty($breadcrumb)): ?>
        <span class="breadcrumb">/ <?= $breadcrumb ?></span>
        <?php endif; ?>
      </div>
      <div class="topbar-right">
        <span class="date-badge"><?= date('d/m/Y') ?></span>
      </div>
    </header>

    <?php if($flash): ?>
    <div style="padding:16px 24px 0">
    <div class="flash flash-<?= $flash['type'] ?>">
      <?= clean($flash['msg']) ?>
      <button onclick="this.parentElement.remove()" class="flash-close">×</button>
    </div>
    </div>
    <?php endif; ?>

    <div class="content">
