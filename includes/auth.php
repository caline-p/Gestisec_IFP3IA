<?php
// ============================================================
// includes/auth.php — Gestion des sessions et authentification
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function hasRole(string ...$roles): bool {
    return in_array($_SESSION['user']['role'] ?? '', $roles, true);
}

function login(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, prenom, nom, role, mot_de_passe FROM utilisateurs WHERE email = ? AND statut = 'actif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'prenom' => $user['prenom'],
            'nom' => $user['nom'],
            'role' => $user['role'],
        ];
        return true;
    }
    return false;
}

function logout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

function csrfToken(): string {
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return !empty($token) && hash_equals($_SESSION['_csrf_token'] ?? '', $token);
}

function requireCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            http_response_code(403);
            echo '<div style="font-family:Arial,sans-serif;padding:20px;background:#fee;border:1px solid #c00;border-radius:8px;margin:20px;">'
                . '<strong>Requête invalide :</strong> jeton CSRF manquant ou invalide.'
                . '</div>';
            exit;
        }
    }
}

function csrfInput(): string {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function genRef(string $prefix): string {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function dateFR(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

function money(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}
