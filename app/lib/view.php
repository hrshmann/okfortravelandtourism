<?php
declare(strict_types=1);

/**
 * Render a template inside a layout.
 *   view('site/home', ['packages' => …])           -> layouts/site.php
 *   view('admin/leads', [...], 'admin')             -> layouts/admin.php
 */
function view(string $template, array $data = [], ?string $layout = 'site', int $status = 200): void
{
    http_response_code($status);
    send_security_headers();
    if ($layout === 'site' && $status === 200 && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && is_installed()) {
        count_visit(current_path());
    }
    $content = render($template, $data);
    if ($layout) {
        $data['content'] = $content;
        echo render('layouts/' . $layout, $data);
    } else {
        echo $content;
    }
    exit;
}

function render(string $template, array $data = []): string
{
    $file = APP . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: $template");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $file;
    return (string) ob_get_clean();
}

function partial(string $name, array $data = []): string
{
    return render('partials/' . $name, $data);
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

/* ------------------------------------------------------------------- SEO */
function seo(array $over = []): array
{
    $site = (string) setting('site_name');
    $title = $over['title'] ?? null;
    return [
        'title' => $title ? $title . ' | ' . $site : (string) setting('meta_title', $site),
        'description' => str_limit($over['description'] ?? (string) setting('meta_description'), 300),
        'image' => abs_url(img($over['image'] ?? (string) setting('og_image'))),
        'canonical' => $over['canonical'] ?? abs_url(current_path()),
        'type' => $over['type'] ?? 'website',
        'noindex' => $over['noindex'] ?? false,
        'schema' => $over['schema'] ?? [],
    ];
}

function org_schema(): array
{
    $sameAs = array_values(array_map(fn($s) => $s['url'], social_links()));
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'TravelAgency',
        '@id' => abs_url('/') . '#agency',
        'name' => setting('site_name'),
        'legalName' => setting('legal_name'),
        'url' => abs_url('/'),
        'logo' => abs_url(img(setting('logo'))),
        'image' => abs_url(img(setting('og_image'))),
        'telephone' => setting('phone'),
        'email' => setting('email'),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('address')],
        'priceRange' => '$$',
        'sameAs' => $sameAs ?: null,
    ]);
}

function breadcrumb_schema(array $crumbs): array
{
    $items = [];
    $i = 1;
    foreach ($crumbs as $label => $path) {
        $items[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $label, 'item' => abs_url($path)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

/* ----------------------------------------------------------------- icons */
function icon(string $name, string $class = ''): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = require APP . '/lib/icons.php';
    }
    $cls = 'i' . ($class ? ' ' . $class : '');
    if ($name === 'whatsapp') {
        return '<svg class="' . $cls . ' i-fill" viewBox="0 0 24 24" aria-hidden="true">' . $icons['whatsapp'] . '</svg>';
    }
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
}

/* --------------------------------------------------------------- pagination */
function pager(array $p, array $query = []): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $path = current_path();
    $link = fn($n) => e(url($path, array_merge($query, ['page' => $n > 1 ? $n : null])));
    $html = '<nav class="pager" aria-label="Pagination">';
    if ($p['page'] > 1) {
        $html .= '<a href="' . $link($p['page'] - 1) . '" rel="prev">' . icon('left') . '<span>Previous</span></a>';
    }
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($i === 1 || $i === $p['pages'] || abs($i - $p['page']) <= 1) {
            $html .= $i === $p['page'] ? '<span aria-current="page">' . $i . '</span>' : '<a href="' . $link($i) . '">' . $i . '</a>';
        } elseif (abs($i - $p['page']) === 2) {
            $html .= '<span class="gap">…</span>';
        }
    }
    if ($p['page'] < $p['pages']) {
        $html .= '<a href="' . $link($p['page'] + 1) . '" rel="next"><span>Next</span>' . icon('right') . '</a>';
    }
    return $html . '</nav>';
}

function not_found(): void
{
    view('site/404', ['seo' => seo(['title' => 'Page not found', 'noindex' => true])], 'site', 404);
}
