<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (login($email, $pass)) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
    $error = 'Identifiants incorrects. Vérifiez votre email et mot de passe.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Connexion — <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<style>
body { font-family:'Segoe UI',Arial,sans-serif; }
.login-page {
  background: linear-gradient(135deg, #1B3A6B 0%, #0F2447 60%, #2E6DB4 100%);
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
  position: relative; overflow: hidden;
}
.login-page::before {
  content: '';
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='1.5' fill='rgba(255,255,255,0.04)'/%3E%3C/svg%3E");
}
.login-card {
  background: #fff; border-radius: 18px;
  box-shadow: 0 12px 48px rgba(0,0,0,.35);
  width: 100%; max-width: 430px;
  overflow: hidden; position: relative; z-index: 1;
}
.login-header {
  background: linear-gradient(135deg, #1B3A6B 0%, #2E6DB4 100%);
  padding: 32px 32px 28px;
  text-align: center;
}
.login-logo-wrap {
  width: 72px; height: 72px; border-radius: 18px;
  background: rgba(255,255,255,.15);
  display: inline-flex; align-items: center; justify-content: center;
  margin-bottom: 14px;
  box-shadow: 0 4px 14px rgba(0,0,0,.2), inset 0 1px 0 rgba(255,255,255,.2);
}
.login-logo-wrap span {
  font-size: 26px; font-weight: 900; color: #fff; letter-spacing: -.02em;
}
.login-app-name {
  font-size: 22px; font-weight: 800; color: #fff;
  letter-spacing: -.01em; margin-bottom: 6px;
}
.login-app-short {
  display: inline-block;
  background: rgba(255,255,255,.15);
  color: rgba(255,255,255,.8);
  font-size: 11px; font-weight: 600; letter-spacing: .08em;
  padding: 3px 10px; border-radius: 20px; margin-bottom: 10px;
}
.login-inst-name {
  font-size: 11.5px; color: rgba(255,255,255,.5);
  line-height: 1.5; max-width: 280px; margin: 0 auto;
}
.login-body { padding: 30px 32px 36px; }
.login-subtitle {
  font-size: 15px; font-weight: 700; color: #1B3A6B;
  margin-bottom: 20px;
}
.lbl {
  display: block; font-size: 12px; font-weight: 600;
  color: #1B3A6B; margin-bottom: 6px;
}
.inp {
  width: 100%; padding: 11px 14px;
  border: 1.5px solid #E5E7EB; border-radius: 8px;
  font-size: 13.5px; font-family: inherit;
  color: #1F2937; background: #fff;
  transition: border-color .15s, box-shadow .15s;
  margin-bottom: 16px;
}
.inp:focus {
  outline: none;
  border-color: #2E6DB4;
  box-shadow: 0 0 0 3px rgba(46,109,180,.15);
}
.btn-login {
  width: 100%; padding: 13px;
  background: linear-gradient(90deg, #1B3A6B, #2E6DB4);
  color: #fff; border: none; border-radius: 8px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  letter-spacing: .03em; margin-top: 4px;
  transition: opacity .15s, transform .15s;
  box-shadow: 0 3px 12px rgba(27,58,107,.3);
}
.btn-login:hover { opacity: .92; transform: translateY(-1px); }
.err {
  background: #FFF1F1; border: 1px solid #FECACA;
  border-radius: 8px; padding: 10px 14px;
  font-size: 13px; color: #B91C1C; margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
}
.login-footer {
  text-align: center; padding: 12px 32px 20px;
  font-size: 11px; color: #9CA3AF;
  border-top: 1px solid #F3F4F6;
}
</style>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-header">
      <div class="login-logo-wrap"><span>G</span></div>
      <div class="login-app-name"><?= APP_NAME ?></div>
      <div class="login-app-short"><?= APP_SHORT ?></div>
      <div class="login-inst-name"><?= APP_FULL_NAME ?></div>
    </div>
    <div class="login-body">
      <div class="login-subtitle">Connexion à l'espace administratif</div>
      <?php if ($error): ?>
        <div class="err">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="#B91C1C" stroke-width="1.4"/><line x1="8" y1="5" x2="8" y2="8.5" stroke="#B91C1C" stroke-width="1.5" stroke-linecap="round"/><circle cx="8" cy="11" r=".8" fill="#B91C1C"/></svg>
          <?= clean($error) ?>
        </div>
      <?php endif; ?>
      <form method="POST" action="">
        <label class="lbl" for="email">Adresse e-mail</label>
        <input class="inp" type="email" id="email" name="email"
               value="<?= clean($_POST['email'] ?? '') ?>"
               placeholder="votre@email.com" required autofocus>

        <label class="lbl" for="password">Mot de passe</label>
        <input class="inp" type="password" id="password" name="password"
               placeholder="••••••••" required>

        <button type="submit" class="btn-login">Se connecter</button>
      </form>
    </div>
    <div class="login-footer">
      <?= APP_NAME ?> v<?= APP_VERSION ?> · <?= APP_SHORT ?> · Douala, Cameroun
    </div>
  </div>
</div>
</body>
</html>
