<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ config */
function config(string $key, $default = null)
{
    $c = $GLOBALS['CONFIG'] ?? null;
    return is_array($c) && array_key_exists($key, $c) ? $c[$key] : $default;
}

function is_installed(): bool
{
    return is_array($GLOBALS['CONFIG'] ?? null);
}

/* ------------------------------------------------------------------ output */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_out($payload, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function redirect(string $to, int $status = 302): void
{
    if (!preg_match('#^https?://#', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $status);
    exit;
}

function back(string $fallback = '/'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    redirect($ref && $host === ($_SERVER['HTTP_HOST'] ?? '') ? $ref : $fallback);
}

/* -------------------------------------------------------------------- urls */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        // SCRIPT_NAME is /index.php (or /subfolder/index.php). PHP's dev server reports the request
        // path instead for URLs with a file extension, so ignore anything that is not index.php.
        $name = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $script = str_replace('\\', '/', dirname(basename($name) === 'index.php' ? $name : '/index.php'));
        $base = rtrim($script === '.' ? '' : $script, '/');
    }
    return $base;
}

function base_url(): string
{
    $configured = rtrim((string) setting('site_url', ''), '/');
    if ($configured !== '') {
        return $configured;
    }
    $https = is_https();
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . base_path();
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function url(string $path = '/', array $query = []): string
{
    if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, 'mailto:') || str_starts_with($path, 'tel:') || str_starts_with($path, '#')) {
        return $path;
    }
    $u = base_path() . '/' . ltrim($path, '/');
    $query = array_filter($query, fn($v) => $v !== null && $v !== '');
    return $u . ($query ? '?' . http_build_query($query) : '');
}

function abs_url(string $path = '/'): string
{
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', base_url());
    return $origin . url($path);
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string) filemtime($file), -6) : APP_VERSION;
    return url('/assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/** Image URL for stored paths (uploads/…) or remote URLs. */
function img(?string $path, string $fallback = 'assets/img/placeholder.svg'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return url($fallback);
    }
    return preg_match('#^(https?:)?//#', $path) || str_starts_with($path, 'data:') ? $path : url($path);
}

/** Resize hint for Unsplash URLs so we never ship 4000px images to phones. */
function img_w(?string $path, int $w): string
{
    $u = img($path);
    if (str_contains($u, 'images.unsplash.com')) {
        $u = preg_replace('/([?&])w=\d+/', '$1w=' . $w, $u);
        if (!preg_match('/[?&]w=/', $u)) {
            $u .= (str_contains($u, '?') ? '&' : '?') . 'w=' . $w;
        }
    }
    return $u;
}

function current_path(): string
{
    static $path = null;
    if ($path === null) {
        $p = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $base = base_path();
        if ($base !== '' && str_starts_with($p, $base)) {
            $p = substr($p, strlen($base));
        }
        $p = '/' . trim($p, '/');
        $path = $p === '/index.php' ? '/' : $p;
    }
    return $path;
}

function is_active(string $prefix): bool
{
    $p = current_path();
    return $prefix === '/' ? $p === '/' : ($p === $prefix || str_starts_with($p, $prefix . '/'));
}

function is_api_request(): bool
{
    return str_starts_with(current_path(), '/api/')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

/* ------------------------------------------------------------------ request */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    $v = input($key, '');
    return is_numeric($v) ? (int) $v : $default;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function request_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* ----------------------------------------------------------------- session */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = STORAGE . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
    }
    session_name('oktravel_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() ?: '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '28800');
    // Some hosts disable session clean-up for custom save paths; switch it back on.
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '200');
    session_start();
}

function flash(string $type, string $message): void
{
    session_boot();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE && !isset($_COOKIE['oktravel_sid'])) {
        return [];
    }
    session_boot();
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function old(string $key, $default = '')
{
    return $GLOBALS['_old'][$key] ?? $default;
}

/* -------------------------------------------------------------------- csrf */
function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    session_boot();
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $sent);
}

function csrf_check(): void
{
    if (!csrf_valid()) {
        if (is_api_request()) {
            json_out(['error' => 'Your session expired. Refresh the page and try again.'], 419);
        }
        flash('error', 'Your session expired. Please try again.');
        back();
    }
}

/* ------------------------------------------------------------------ strings */
function slugify(string $text): string
{
    $text = str_replace(['&', '@'], [' and ', ' at '], $text);
    if (function_exists('transliterator_transliterate')) {
        $t = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        if (is_string($t)) {
            $text = $t;
        }
    } else {
        $text = mb_strtolower($text);
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'item';
}

function str_limit(?string $text, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)) . '…' : $text;
}

function json_dec($value, $default = [])
{
    if (is_array($value)) {
        return $value;
    }
    $d = json_decode((string) $value, true);
    return $d === null ? $default : $d;
}

function json_enc($value): string
{
    // Invalid UTF-8 (e.g. text pasted from a legacy encoding) is replaced instead of failing the save.
    return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}

function lines_to_array(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $text)), fn($l) => $l !== ''));
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function plural(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

/* ------------------------------------------------------------------- money */
/** An amount in the base currency (admin screens, quotations, emails). Public pages use price(). */
function money($amount, ?string $currency = null, bool $decimals = false): string
{
    $amount = (float) $amount;
    $symbol = currency_symbol($currency ?? base_currency());
    $num = number_format($amount, $decimals ? 2 : 0);
    // Symbols made of letters (AED, PKR, SAR) read better with a space.
    return preg_match('/^[A-Za-z.]+$/', $symbol) ? $symbol . ' ' . $num : $symbol . $num;
}

/** Plain number for form inputs: 4200.00 -> "4200", 99.50 -> "99.5", null -> "". */
function amount_input($v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
}

function currency_symbol(string $code): string
{
    $code = strtoupper($code);
    if (isset(currency_table()[$code])) {
        return currency_table()[$code]['symbol'];
    }
    return ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'AED', 'SAR' => 'SAR', 'PKR' => 'Rs', 'INR' => '₹', 'QAR' => 'QAR', 'OMR' => 'OMR', 'KWD' => 'KWD', 'BHD' => 'BHD', 'TRY' => '₺'][$code] ?? $code;
}

/* ---------------------------------------------------------------- dates */
function fmt_date(?string $date, string $format = 'j M Y'): string
{
    if (!$date) {
        return '';
    }
    $t = strtotime($date);
    return $t ? date($format, $t) : '';
}

function time_ago(?string $date): string
{
    $t = $date ? strtotime($date) : false;
    if (!$t) {
        return '';
    }
    $d = time() - $t;
    if ($d < 60) return 'just now';
    if ($d < 3600) return plural((int) floor($d / 60), 'min') . ' ago';
    if ($d < 86400) return plural((int) floor($d / 3600), 'hour') . ' ago';
    if ($d < 86400 * 7) return plural((int) floor($d / 86400), 'day') . ' ago';
    return fmt_date($date);
}

/* --------------------------------------------------------------- whatsapp */
function wa_number(): string
{
    return preg_replace('/\D/', '', (string) setting('whatsapp', ''));
}

function wa_link(string $message = ''): string
{
    $n = wa_number();
    if ($n === '') {
        return url('/contact');
    }
    return 'https://wa.me/' . $n . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function tel_link(?string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', (string) $phone);
}

/* --------------------------------------------------------------- misc */
function is_bot(): bool
{
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse/', $ua);
}

function storage_writable(): bool
{
    return is_writable(STORAGE) && is_writable(UPLOADS);
}

function random_code(int $len = 6): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $len; $i++) {
        $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $s;
}
