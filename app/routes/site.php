<?php
declare(strict_types=1);

/* =================================================================== data */
function decode_package(array $p): array
{
    foreach (['highlights', 'inclusions', 'exclusions', 'itinerary', 'gallery'] as $k) {
        $p[$k] = json_dec($p[$k] ?? '', []);
    }
    $p['url'] = url('/packages/' . $p['slug']);
    $p['save'] = ($p['old_price'] ?? 0) > $p['price'] ? (int) round((1 - $p['price'] / $p['old_price']) * 100) : 0;
    return $p;
}

function active_packages(string $where = '1=1', array $params = [], string $order = 'featured DESC, sort_order ASC, id DESC', int $limit = 100): array
{
    return array_map('decode_package', rows("SELECT * FROM packages WHERE active = 1 AND ($where) ORDER BY $order" . limit_sql($limit), $params));
}

/** Budget bands in the base currency, labelled in the visitor's currency. */
function package_budgets(): array
{
    $bands = ['u750' => [0, 750], '750-1500' => [750, 1500], '1500-3000' => [1500, 3000], '3000' => [3000, 999999]];
    $out = [];
    foreach ($bands as $k => [$lo, $hi]) {
        $label = $lo === 0 ? 'Under ' . price($hi) : ($hi > 99999 ? price($lo) . '+' : price($lo) . ' – ' . price($hi));
        $out[$k] = [$lo, $hi, $label];
    }
    return $out;
}

function board_status(array $p): string
{
    if (!empty($p['board_status'])) {
        return $p['board_status'];
    }
    return $p['save'] >= 15 ? 'ON SALE' : 'BOARDING';
}

function active_testimonials(int $limit = 12): array
{
    return rows('SELECT * FROM testimonials WHERE active = 1 ORDER BY sort_order ASC, id DESC' . limit_sql($limit));
}

function active_faqs(?string $category = null, int $limit = 50): array
{
    return $category
        ? rows('SELECT * FROM faqs WHERE active = 1 AND category = ? ORDER BY sort_order ASC, id ASC' . limit_sql($limit), [$category])
        : rows('SELECT * FROM faqs WHERE active = 1 ORDER BY sort_order ASC, id ASC' . limit_sql($limit));
}

function faq_schema(array $faqs): array
{
    return [
        '@context' => 'https://schema.org', '@type' => 'FAQPage',
        'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]], $faqs),
    ];
}

function footer_pages(): array
{
    try {
        return rows('SELECT slug, title FROM pages WHERE active = 1 AND show_in_footer = 1 ORDER BY sort_order ASC');
    } catch (Throwable $e) {
        return [];
    }
}

/* ================================================================= pages */
route('GET', '/', function () {
    $featured = active_packages('featured = 1', [], 'sort_order ASC, id DESC', 12);
    // The departures board shows six rows and rotates through the rest of this pool.
    $board = active_packages('1=1', [], 'featured DESC, sort_order ASC, id DESC', 12);
    view('site/home', [
        'board' => $board,
        'featured' => array_slice($featured ?: $board, 0, 6),
        'destinations' => rows('SELECT d.*, (SELECT COUNT(*) FROM packages p WHERE p.destination_id = d.id AND p.active = 1) AS trips,
                                       (SELECT MIN(price) FROM packages p WHERE p.destination_id = d.id AND p.active = 1) AS from_price
                                FROM destinations d WHERE d.active = 1 AND d.featured = 1 ORDER BY d.sort_order ASC, d.name ASC' . limit_sql(8)),
        'umrah' => active_packages("category = 'Umrah'", [], 'price ASC', 3),
        'visas' => rows('SELECT * FROM visas WHERE active = 1 ORDER BY sort_order ASC' . limit_sql(6)),
        'reviews' => active_testimonials(),
        'faqs' => active_faqs(null, 6),
        'posts' => rows("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC" . limit_sql(3), [now()]),
        'stats' => setting_json('stats'),
        'seo' => seo(['schema' => [org_schema()]]),
    ]);
});

route('GET', '/packages', function () {
    $where = ['p.active = 1'];
    $params = [];
    $f = [
        'q' => mb_substr((string) input('q'), 0, 80),
        'category' => (string) input('category'),
        'destination' => (string) input('destination'),
        'duration' => (string) input('duration'),
        'budget' => (string) input('budget'),
        'sort' => (string) input('sort', 'recommended'),
    ];
    if ($f['q'] !== '') {
        $where[] = '(p.title LIKE ? OR p.location LIKE ? OR p.category LIKE ? OR p.summary LIKE ?)';
        array_push($params, ...array_fill(0, 4, '%' . $f['q'] . '%'));
    }
    if ($f['category'] !== '') {
        $where[] = 'p.category = ?';
        $params[] = $f['category'];
    }
    if ($f['destination'] !== '') {
        $where[] = 'd.slug = ?';
        $params[] = $f['destination'];
    }
    $durations = ['short' => [1, 4, 'Up to 4 days'], 'week' => [5, 8, '5–8 days'], 'long' => [9, 99, '9 days or more']];
    if (isset($durations[$f['duration']])) {
        $where[] = 'p.days BETWEEN ? AND ?';
        array_push($params, $durations[$f['duration']][0], $durations[$f['duration']][1]);
    }
    $budgets = package_budgets();
    if (isset($budgets[$f['budget']])) {
        $where[] = 'p.price >= ? AND p.price < ?';
        array_push($params, $budgets[$f['budget']][0], $budgets[$f['budget']][1]);
    }
    $sorts = [
        'recommended' => ['p.featured DESC, p.sort_order ASC, p.id DESC', 'Recommended'],
        'price-asc' => ['p.price ASC', 'Price: low to high'],
        'price-desc' => ['p.price DESC', 'Price: high to low'],
        'duration' => ['p.days ASC', 'Shortest first'],
        'rating' => ['p.rating DESC', 'Top rated'],
    ];
    $order = $sorts[$f['sort']][0] ?? $sorts['recommended'][0];
    $page = paginate('SELECT p.* FROM packages p LEFT JOIN destinations d ON d.id = p.destination_id WHERE ' . implode(' AND ', $where), $params, input_int('page', 1), 12, $order);
    $page['items'] = array_map('decode_package', $page['items']);

    $categories = array_column(rows("SELECT category, COUNT(*) AS n FROM packages WHERE active = 1 AND category IS NOT NULL AND category <> '' GROUP BY category ORDER BY category"), 'n', 'category');
    $destinations = rows('SELECT d.slug, d.name FROM destinations d WHERE d.active = 1 AND EXISTS (SELECT 1 FROM packages p WHERE p.destination_id = d.id AND p.active = 1) ORDER BY d.name');
    $title = $f['category'] !== '' ? $f['category'] . ' packages' : 'Holiday packages';
    view('site/packages', [
        'page' => $page, 'f' => $f, 'categories' => $categories, 'destinations' => $destinations,
        'durations' => $durations, 'budgets' => $budgets, 'sorts' => $sorts, 'title' => $title,
        'seo' => seo(['title' => $title, 'description' => 'Holiday packages with flights, hotels, transfers and tours. Compare prices and send a free booking request.', 'canonical' => abs_url('/packages' . ($f['category'] !== '' ? '?category=' . rawurlencode($f['category']) : ''))]),
    ]);
});

route('GET', '/packages/{slug}', function (string $slug) {
    $p = row('SELECT * FROM packages WHERE slug = ? AND active = 1', [$slug]);
    if (!$p && auth_user()) {
        $p = row('SELECT * FROM packages WHERE slug = ?', [$slug]); // admins can preview hidden packages
    }
    if (!$p) {
        not_found();
    }
    $p = decode_package($p);
    if (!is_bot()) {
        q('UPDATE packages SET views = views + 1 WHERE id = ?', [$p['id']]);
    }
    $dest = $p['destination_id'] ? row('SELECT * FROM destinations WHERE id = ?', [$p['destination_id']]) : null;
    $related = active_packages('id <> ? AND (category = ? OR destination_id = ?)', [$p['id'], $p['category'], $p['destination_id'] ?? 0], 'featured DESC, sort_order ASC', 3);
    if (count($related) < 3) {
        $ids = array_merge([$p['id']], array_column($related, 'id'));
        $related = array_merge($related, active_packages('id NOT IN (' . implode(',', array_map('intval', $ids)) . ')', [], 'featured DESC, sort_order ASC', 3 - count($related)));
    }
    $schema = [
        '@context' => 'https://schema.org', '@type' => ['Product', 'TouristTrip'], 'name' => $p['title'],
        'description' => $p['summary'] ?: str_limit($p['description'], 300), 'image' => array_values(array_filter(array_merge([img($p['cover_image'])], $p['gallery']))),
        'touristType' => $p['category'],
        'offers' => ['@type' => 'Offer', 'price' => number_format((float) $p['price'], 2, '.', ''), 'priceCurrency' => setting('currency', 'USD'), 'availability' => 'https://schema.org/InStock', 'url' => abs_url('/packages/' . $p['slug'])],
        'provider' => ['@id' => abs_url('/') . '#agency'],
    ];
    if ($p['itinerary']) {
        $schema['itinerary'] = ['@type' => 'ItemList', 'itemListElement' => array_map(fn($d, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $d['title'] ?: 'Day ' . ($i + 1)], $p['itinerary'], array_keys($p['itinerary']))];
    }
    view('site/package', [
        'p' => $p, 'dest' => $dest, 'related' => $related,
        'seo' => seo([
            'title' => $p['seo_title'] ?: $p['title'] . ' — ' . $p['days'] . ' days from ' . money($p['price']),
            'description' => $p['seo_description'] ?: ($p['summary'] ?: str_limit($p['description'])),
            'image' => $p['cover_image'], 'type' => 'product',
            'schema' => [$schema, breadcrumb_schema(['Home' => '/', 'Packages' => '/packages', $p['title'] => '/packages/' . $p['slug']])],
        ]),
    ]);
});

route('GET', '/destinations', function () {
    $list = rows('SELECT d.*, (SELECT COUNT(*) FROM packages p WHERE p.destination_id = d.id AND p.active = 1) AS trips,
                         (SELECT MIN(price) FROM packages p WHERE p.destination_id = d.id AND p.active = 1) AS from_price
                  FROM destinations d WHERE d.active = 1 ORDER BY d.sort_order ASC, d.name ASC');
    view('site/destinations', ['list' => $list, 'seo' => seo(['title' => 'Destinations', 'description' => 'Where to go next: destination guides with the best time to visit, packages and flights.'])]);
});

route('GET', '/destinations/{slug}', function (string $slug) {
    $d = row('SELECT * FROM destinations WHERE slug = ? AND active = 1', [$slug]);
    if (!$d) {
        not_found();
    }
    $packages = active_packages('destination_id = ?', [$d['id']], 'featured DESC, sort_order ASC', 24);
    view('site/destination', [
        'd' => $d, 'packages' => $packages,
        'seo' => seo(['title' => $d['seo_title'] ?: $d['name'] . ' holidays & packages', 'description' => $d['seo_description'] ?: ($d['tagline'] . '. ' . str_limit($d['description'], 200)), 'image' => $d['cover_image'],
            'schema' => [['@context' => 'https://schema.org', '@type' => 'TouristDestination', 'name' => $d['name'], 'description' => str_limit($d['description'], 300), 'image' => img($d['cover_image'])], breadcrumb_schema(['Home' => '/', 'Destinations' => '/destinations', $d['name'] => '/destinations/' . $d['slug']])]]),
    ]);
});

route('GET', '/flights', function () {
    $to = strtoupper((string) input('to'));
    view('site/flights', [
        'prefill' => ['from' => airport((string) input('from', 'DXB')), 'to' => $to ? airport($to) : null, 'auto' => input('auto') === '1'],
        'provider' => flight_provider(),
        'popular' => [['DXB', 'LHR'], ['KHI', 'JED'], ['DXB', 'IST'], ['LHE', 'DXB'], ['DXB', 'MLE'], ['AUH', 'BKK']],
        'seo' => seo(['title' => 'Cheap flights', 'description' => 'Compare fares on hundreds of airlines and book with a consultant who confirms every detail on WhatsApp.']),
    ]);
});

route('GET', '/umrah', function () {
    view('site/umrah', [
        'packages' => active_packages("category IN ('Umrah', 'Hajj')", [], 'price ASC', 12),
        'faqs' => active_faqs(),
        'seo' => seo(['title' => 'Umrah & Hajj packages', 'description' => 'Umrah packages with visas, hotels near the Haram, transport and guided Ziyarat. Economy to premium, from any departure city.']),
    ]);
});

route('GET', '/visas', function () {
    view('site/visas', [
        'list' => rows('SELECT * FROM visas WHERE active = 1 ORDER BY sort_order ASC, country ASC'),
        'seo' => seo(['title' => 'Visa services', 'description' => 'Tourist, visit and business visas for the UAE, Saudi Arabia, Schengen, the UK, Türkiye and more — prepared and checked by our visa team.']),
    ]);
});

route('GET', '/visas/{slug}', function (string $slug) {
    $v = row('SELECT * FROM visas WHERE slug = ? AND active = 1', [$slug]);
    if (!$v) {
        not_found();
    }
    $v['requirements'] = json_dec($v['requirements'], []);
    view('site/visa', [
        'v' => $v,
        'others' => rows('SELECT slug, country, visa_type, price FROM visas WHERE active = 1 AND id <> ? ORDER BY sort_order' . limit_sql(5), [$v['id']]),
        'seo' => seo(['title' => $v['seo_title'] ?: $v['country'] . ' ' . mb_strtolower((string) $v['visa_type']), 'description' => $v['seo_description'] ?: $v['summary'], 'image' => $v['cover_image'],
            'schema' => [breadcrumb_schema(['Home' => '/', 'Visas' => '/visas', $v['country'] => '/visas/' . $v['slug']])]]),
    ]);
});

route('GET', '/blog', function () {
    $cat = (string) input('category');
    $where = "status = 'published' AND published_at <= ?";
    $params = [now()];
    if ($cat !== '') {
        $where .= ' AND category = ?';
        $params[] = $cat;
    }
    $page = paginate("SELECT * FROM posts WHERE $where", $params, input_int('page', 1), 9, 'published_at DESC');
    view('site/blog', [
        'page' => $page, 'cat' => $cat,
        'categories' => array_column(rows("SELECT DISTINCT category FROM posts WHERE status = 'published' AND category IS NOT NULL AND category <> '' ORDER BY category"), 'category'),
        'seo' => seo(['title' => 'Travel journal', 'description' => 'Visa guides, Umrah checklists and destination advice from our travel consultants.']),
    ]);
});

route('GET', '/blog/{slug}', function (string $slug) {
    $post = row("SELECT * FROM posts WHERE slug = ? AND status = 'published' AND published_at <= ?", [$slug, now()]);
    if (!$post && auth_user()) {
        $post = row('SELECT * FROM posts WHERE slug = ?', [$slug]);
    }
    if (!$post) {
        not_found();
    }
    if (!is_bot()) {
        q('UPDATE posts SET views = views + 1 WHERE id = ?', [$post['id']]);
    }
    view('site/post', [
        'post' => $post,
        'more' => rows("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? AND id <> ? ORDER BY published_at DESC" . limit_sql(3), [now(), $post['id']]),
        'seo' => seo(['title' => $post['seo_title'] ?: $post['title'], 'description' => $post['seo_description'] ?: $post['excerpt'], 'image' => $post['cover_image'], 'type' => 'article',
            'schema' => [['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'], 'image' => img($post['cover_image']), 'datePublished' => date('c', strtotime((string) $post['published_at'])), 'dateModified' => date('c', strtotime((string) ($post['updated_at'] ?: $post['published_at']))), 'author' => ['@type' => 'Organization', 'name' => setting('site_name')], 'publisher' => ['@id' => abs_url('/') . '#agency']]]]),
    ]);
});

route('GET', '/about', function () {
    $page = row("SELECT * FROM pages WHERE slug = 'about' AND active = 1");
    view('site/about', [
        'page' => $page, 'stats' => setting_json('stats'), 'reviews' => active_testimonials(6),
        'seo' => seo(['title' => $page['seo_title'] ?? 'About us', 'description' => $page['seo_description'] ?? str_limit($page['body'] ?? '', 160)]),
    ]);
});

route('GET', '/contact', function () {
    view('site/contact', [
        'type' => array_key_exists((string) input('type'), LEAD_TYPES) ? input('type') : 'contact',
        'seo' => seo(['title' => 'Contact us', 'description' => 'Call, email or WhatsApp our travel consultants, or visit our office. ' . setting('address')]),
    ]);
});

route('GET', '/faq', function () {
    $faqs = active_faqs();
    view('site/faq', ['faqs' => $faqs, 'seo' => seo(['title' => 'Frequently asked questions', 'schema' => [faq_schema($faqs)]])]);
});

route('GET', '/track', function () {
    $ref = strtoupper(trim((string) input('ref')));
    $contact = trim((string) input('contact'));
    $lead = null;
    $error = null;
    $timeline = [];
    if ($ref !== '' && $contact !== '') {
        $lead = row('SELECT * FROM leads WHERE ref = ?', [$ref]);
        $digits = fn($s) => substr(preg_replace('/\D/', '', (string) $s), -7);
        $matches = $lead && (
            (filter_var($contact, FILTER_VALIDATE_EMAIL) && mb_strtolower($contact) === mb_strtolower((string) $lead['email']))
            || (strlen($digits($contact)) >= 7 && $digits($contact) === $digits($lead['phone']))
        );
        if (!$matches) {
            $lead = null;
            $error = 'We could not find a request with that reference and contact detail. Check both and try again, or message us on WhatsApp.';
        } else {
            $timeline = rows("SELECT body, created_at FROM lead_notes WHERE lead_id = ? AND kind = 'status' ORDER BY created_at ASC", [$lead['id']]);
        }
    }
    view('site/track', ['ref' => $ref, 'contact' => $contact, 'lead' => $lead, 'error' => $error, 'timeline' => $timeline, 'seo' => seo(['title' => 'Track your request', 'noindex' => $ref !== ''])]);
});

route('POST', '/enquire', function () {
    csrf_check();
    $problem = form_guard_problem();
    $fetch = is_api_request();
    if ($problem === 'spam') {
        // Pretend success so bots learn nothing.
        $fetch ? json_out(['ok' => true, 'ref' => 'OK-' . random_code(5)]) : redirect('/thank-you');
    }
    if ($problem) {
        $fetch ? json_out(['error' => $problem], 422) : (flash('error', $problem) || back());
    }
    [$lead, $errors] = lead_from_request($_POST);
    if ($errors) {
        if ($fetch) {
            json_out(['error' => 'Check the highlighted fields.', 'fields' => $errors], 422);
        }
        flash('error', implode(' ', $errors));
        back();
    }
    session_boot();
    $_SESSION['last_lead'] = $lead['id'];
    $wa = wa_link(lead_whatsapp_message($lead));
    if ($fetch) {
        json_out(['ok' => true, 'ref' => $lead['ref'], 'whatsapp' => $wa, 'redirect' => url('/thank-you')]);
    }
    redirect('/thank-you');
});

route('GET', '/thank-you', function () {
    session_boot();
    $lead = !empty($_SESSION['last_lead']) ? row('SELECT * FROM leads WHERE id = ?', [(int) $_SESSION['last_lead']]) : null;
    view('site/thanks', ['lead' => $lead, 'seo' => seo(['title' => 'Request received', 'noindex' => true])]);
});

route('POST', '/subscribe', function () {
    csrf_check();
    $email = mb_substr(mb_strtolower(trim((string) input('email'))), 0, 190);
    $fetch = is_api_request();
    if (form_guard_problem() === 'spam') {
        $fetch ? json_out(['ok' => true]) : redirect('/');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fetch ? json_out(['error' => 'Enter a valid email address.'], 422) : (flash('error', 'Enter a valid email address.') || back());
    }
    if (!val('SELECT id FROM subscribers WHERE email = ?', [$email])) {
        insert('subscribers', ['email' => $email, 'name' => mb_substr((string) input('name'), 0, 120) ?: null, 'status' => 'active', 'source' => mb_substr((string) input('source', 'footer'), 0, 60), 'created_at' => now()]);
    }
    $msg = 'You are on the list. Expect a few good offers a month — never spam.';
    $fetch ? json_out(['ok' => true, 'message' => $msg]) : (flash('success', $msg) || back());
});

/* ============================================================ SEO files */
route('GET', '/sitemap.xml', function () {
    $urls = [['/', null, '1.0'], ['/packages', null, '0.9'], ['/destinations', null, '0.8'], ['/flights', null, '0.8'], ['/umrah', null, '0.9'], ['/visas', null, '0.8'], ['/blog', null, '0.6'], ['/about', null, '0.5'], ['/contact', null, '0.5'], ['/faq', null, '0.5']];
    foreach (rows('SELECT slug, updated_at FROM packages WHERE active = 1') as $r) $urls[] = ['/packages/' . $r['slug'], $r['updated_at'], '0.8'];
    foreach (rows('SELECT slug, updated_at FROM destinations WHERE active = 1') as $r) $urls[] = ['/destinations/' . $r['slug'], $r['updated_at'], '0.7'];
    foreach (rows('SELECT slug, updated_at FROM visas WHERE active = 1') as $r) $urls[] = ['/visas/' . $r['slug'], $r['updated_at'], '0.7'];
    foreach (rows("SELECT slug, updated_at FROM posts WHERE status = 'published' AND published_at <= ?", [now()]) as $r) $urls[] = ['/blog/' . $r['slug'], $r['updated_at'], '0.6'];
    foreach (rows("SELECT slug, updated_at FROM pages WHERE active = 1 AND slug <> 'about'") as $r) $urls[] = ['/' . $r['slug'], $r['updated_at'], '0.3'];
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as [$path, $mod, $prio]) {
        echo '  <url><loc>' . e(abs_url($path)) . '</loc>' . ($mod ? '<lastmod>' . date('Y-m-d', strtotime((string) $mod)) . '</lastmod>' : '') . '<priority>' . $prio . "</priority></url>\n";
    }
    echo '</urlset>';
    exit;
});

route('GET', '/robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /api/\nDisallow: /thank-you\nDisallow: /track\n\nSitemap: " . abs_url('/sitemap.xml') . "\n";
    exit;
});

route('GET', '/site.webmanifest', function () {
    header('Content-Type: application/manifest+json');
    echo json_enc([
        'name' => setting('site_name'), 'short_name' => 'OK Travel', 'start_url' => url('/'), 'display' => 'standalone',
        'background_color' => '#ffffff', 'theme_color' => '#000C64',
        'icons' => [['src' => url('/assets/img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'], ['src' => url('/assets/img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png']],
    ]);
    exit;
});

/* ======================================================= CMS pages (last) */
route('GET', '/{slug}', function (string $slug) {
    $page = row('SELECT * FROM pages WHERE slug = ? AND active = 1', [$slug]);
    if (!$page) {
        not_found();
    }
    view('site/page', ['page' => $page, 'seo' => seo(['title' => $page['seo_title'] ?: $page['title'], 'description' => $page['seo_description'] ?: str_limit($page['body'])])]);
});
