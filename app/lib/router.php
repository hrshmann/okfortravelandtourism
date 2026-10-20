<?php
declare(strict_types=1);

function route(string $methods, string $pattern, callable $handler): void
{
    $regex = '#^' . preg_replace('#\\\{(\w+)\\\}#', '(?P<$1>[^/]+)', preg_quote($pattern, '#')) . '$#';
    foreach (explode('|', $methods) as $m) {
        $GLOBALS['_routes'][] = [$m, $regex, $handler];
    }
}

function dispatch(string $method, string $path): void
{
    $allowed = false;
    foreach ($GLOBALS['_routes'] ?? [] as [$m, $regex, $handler]) {
        if (!preg_match($regex, $path, $match)) {
            continue;
        }
        if ($m !== $method && !($m === 'GET' && $method === 'HEAD')) {
            $allowed = true;
            continue;
        }
        $params = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
        $handler(...array_values($params));
        return;
    }
    if ($allowed) {
        http_response_code(405);
        exit('Method not allowed');
    }
    if (is_api_request()) {
        json_out(['error' => 'Not found'], 404);
    }
    not_found();
}

function app_run(): void
{
    $path = current_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (!is_installed()) {
        if (!str_starts_with($path, '/install')) {
            redirect('/install');
        }
        dispatch($method, $path);
        return;
    }

    date_default_timezone_set((string) setting('timezone', 'Asia/Dubai'));

    if (setting('force_https') === '1' && !is_https() && !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)) {
        redirect('https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), 301);
    }

    $isAdmin = str_starts_with($path, '/admin');
    if (setting('maintenance') === '1' && !$isAdmin && !str_starts_with($path, '/api/') && !auth_user()) {
        header('Retry-After: 3600');
        view('site/maintenance', ['seo' => seo(['title' => 'Back soon', 'noindex' => true])], null, 503);
    }

    if (!$isAdmin && $method === 'GET') {
        // Currency switcher: ?currency=INR sets a cookie, then returns to the clean URL.
        $want = strtoupper((string) ($_GET['currency'] ?? ''));
        if ($want !== '' && isset(enabled_currencies()[$want])) {
            setcookie(CURRENCY_COOKIE, $want, ['expires' => time() + 31536000, 'path' => base_path() ?: '/', 'samesite' => 'Lax', 'secure' => is_https()]);
            $q = $_GET;
            unset($q['currency']);
            redirect($path . ($q ? '?' . http_build_query($q) : ''));
        }
        remember_utm();
        if (!str_starts_with($path, '/api/')) {
            fx_schedule_refresh();
        }
    }
    dispatch($method, $path);
}

/** Keep campaign tags for the visit so they can be attached to any lead it produces. */
function remember_utm(): void
{
    $utm = [];
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'fbclid'] as $k) {
        if (!empty($_GET[$k])) {
            $utm[$k] = mb_substr((string) $_GET[$k], 0, 120);
        }
    }
    if ($utm) {
        session_boot();
        $_SESSION['utm'] = $utm;
    }
}

function count_visit(string $path): void
{
    if (is_bot() || str_starts_with($path, '/api/') || preg_match('#\.(xml|txt|ico|png|jpe?g|webp|svg|css|js)$#', $path)) {
        return;
    }
    try {
        $day = date('Y-m-d');
        $p = mb_substr($path, 0, 190);
        if (!q('UPDATE visits SET hits = hits + 1 WHERE day = ? AND path = ?', [$day, $p])->rowCount()) {
            insert('visits', ['day' => $day, 'path' => $p, 'hits' => 1]);
        }
    } catch (Throwable $e) {
        // A race on the unique key just means another request counted this one.
    }
}
