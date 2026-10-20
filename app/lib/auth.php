<?php
declare(strict_types=1);

/**
 * Roles, from most to least access:
 *   admin  – everything, including users, settings and backups
 *   editor – website content (packages, destinations, blog …) and leads
 *   agent  – leads / bookings only
 */
const ROLES = ['admin' => 'Administrator', 'editor' => 'Content editor', 'agent' => 'Sales agent'];
const ROLE_RANK = ['agent' => 1, 'editor' => 2, 'admin' => 3];
const LOGIN_MAX_ATTEMPTS = 6;
const LOGIN_WINDOW_MIN = 15;

function auth_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    session_boot();
    $id = (int) ($_SESSION['uid'] ?? 0);
    $user = null;
    if ($id) {
        $u = row('SELECT * FROM users WHERE id = ? AND active = 1', [$id]);
        // Sessions are bound to the password hash: changing a password signs out other devices.
        if ($u && hash_equals((string) ($_SESSION['uhash'] ?? ''), substr(hash('sha256', $u['password_hash']), 0, 16))) {
            $user = $u;
        } else {
            unset($_SESSION['uid'], $_SESSION['uhash']);
        }
    }
    return $user;
}

function auth_can(string $role): bool
{
    $u = auth_user();
    return $u && (ROLE_RANK[$u['role']] ?? 0) >= (ROLE_RANK[$role] ?? 99);
}

function require_role(string $role = 'agent'): array
{
    $u = auth_user();
    if (!$u) {
        session_boot();
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? url('/admin');
        if (is_api_request()) {
            json_out(['error' => 'Please sign in again.'], 401);
        }
        redirect('/admin/login');
    }
    if (!auth_can($role)) {
        if (is_api_request()) {
            json_out(['error' => 'You do not have permission to do that.'], 403);
        }
        flash('error', 'Your account does not have access to that section.');
        redirect('/admin');
    }
    return $u;
}

function login_throttled(): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MIN * 60);
    return (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?', [client_ip(), $since]) >= LOGIN_MAX_ATTEMPTS;
}

function attempt_login(string $email, string $password): ?string
{
    if (login_throttled()) {
        return 'Too many attempts. Wait ' . LOGIN_WINDOW_MIN . ' minutes and try again.';
    }
    $u = row('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        insert('login_attempts', ['ip' => client_ip(), 'email' => mb_substr($email, 0, 190), 'created_at' => now()]);
        return 'That email and password do not match an account.';
    }
    if (!(int) $u['active']) {
        return 'This account has been disabled. Ask an administrator to re-enable it.';
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        $u['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        update('users', ['password_hash' => $u['password_hash']], 'id = ?', [$u['id']]);
    }
    login_user($u);
    delete_rows('login_attempts', 'ip = ?', [client_ip()]);
    update('users', ['last_login_at' => now()], 'id = ?', [$u['id']]);
    log_activity('login', 'user', (int) $u['id'], 'Signed in', (int) $u['id']);
    return null;
}

function login_user(array $u): void
{
    session_boot();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['uhash'] = substr(hash('sha256', $u['password_hash']), 0, 16);
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    session_boot();
    $_SESSION = [];
    session_regenerate_id(true);
}

function password_problem(string $password): ?string
{
    if (mb_strlen($password) < 10) {
        return 'Use at least 10 characters for the password.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Use both letters and numbers in the password.';
    }
    return null;
}

function log_activity(string $action, ?string $entity = null, ?int $entityId = null, string $summary = '', ?int $userId = null): void
{
    try {
        insert('activity_log', [
            'user_id' => $userId ?? (auth_user()['id'] ?? null),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'summary' => mb_substr($summary, 0, 255),
            'ip' => client_ip(),
            'created_at' => now(),
        ]);
    } catch (Throwable $e) {
        error_log('activity log failed: ' . $e->getMessage());
    }
}
