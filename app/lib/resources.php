<?php
declare(strict_types=1);

/**
 * Content types managed by the admin panel. Each definition drives the list screen,
 * the edit form, validation and saving, so adding a field is a one-line change.
 *
 * Field types: text, textarea, richtext, number, money, select, toggle, image, gallery,
 *              lines (one item per line, stored as JSON), itinerary (day repeater), datetime, slug, relation
 */
function resources(): array
{
    static $r = null;
    if ($r !== null) {
        return $r;
    }
    $seo = [
        ['section' => 'Search engines', 'help' => 'Leave empty to use the title and summary automatically.'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'text', 'col' => 4, 'max' => 190, 'counter' => 60],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'textarea', 'col' => 4, 'max' => 320, 'counter' => 160, 'rows' => 2],
    ];
    $r = [
        'packages' => [
            'label' => 'Packages', 'singular' => 'package', 'icon' => 'palm', 'role' => 'editor',
            'title' => 'title', 'public' => '/packages/{slug}', 'sortable' => true,
            'order' => 'sort_order ASC, id DESC', 'search' => ['title', 'location', 'category'],
            'filters' => ['category' => 'Category'],
            'list' => [
                'cover_image' => ['type' => 'image'],
                'title' => ['type' => 'title', 'sub' => '{location} · {days}D/{nights}N'],
                'category' => ['type' => 'text', 'label' => 'Category'],
                'price' => ['type' => 'money', 'label' => 'From'],
                'featured' => ['type' => 'toggle', 'label' => 'Featured'],
                'active' => ['type' => 'toggle', 'label' => 'Live'],
            ],
            'fields' => array_merge([
                ['section' => 'Basics'],
                ['name' => 'title', 'label' => 'Package title', 'type' => 'text', 'required' => true, 'col' => 3, 'max' => 190],
                ['name' => 'slug', 'label' => 'URL', 'type' => 'slug', 'source' => 'title', 'col' => 1],
                ['name' => 'destination_id', 'label' => 'Destination', 'type' => 'relation', 'table' => 'destinations', 'display' => 'name', 'col' => 2, 'help' => 'Links the package to a destination page.'],
                ['name' => 'location', 'label' => 'Location shown on the card', 'type' => 'text', 'col' => 2, 'placeholder' => 'e.g. Malé, Maldives'],
                ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'col' => 1, 'datalist' => 'packages.category', 'placeholder' => 'Honeymoon'],
                ['name' => 'days', 'label' => 'Days', 'type' => 'number', 'min' => 1, 'col' => 1, 'default' => 5, 'required' => true],
                ['name' => 'nights', 'label' => 'Nights', 'type' => 'number', 'min' => 0, 'col' => 1, 'default' => 4, 'empty' => 0],
                ['name' => 'airport_code', 'label' => 'Airport code', 'type' => 'text', 'col' => 1, 'max' => 3, 'upper' => true, 'placeholder' => 'MLE', 'help' => 'Shown on the departures board.'],
                ['section' => 'Pricing'],
                ['name' => 'price', 'label' => 'Price from', 'type' => 'money', 'required' => true, 'col' => 1],
                ['name' => 'old_price', 'label' => 'Was (optional)', 'type' => 'money', 'col' => 1, 'help' => 'Shown crossed out.'],
                ['name' => 'price_note', 'label' => 'Price note', 'type' => 'text', 'col' => 2, 'placeholder' => 'per person, twin sharing', 'default' => 'per person, twin sharing'],
                ['name' => 'badge', 'label' => 'Badge', 'type' => 'text', 'col' => 1, 'placeholder' => 'Best seller'],
                ['name' => 'board_status', 'label' => 'Board status', 'type' => 'select', 'col' => 1, 'options' => ['' => 'Automatic', 'ON SALE' => 'On sale', 'BOARDING' => 'Boarding', 'FEW LEFT' => 'Few left', 'NEW' => 'New', 'SOLD OUT' => 'Sold out']],
                ['name' => 'rating', 'label' => 'Rating (0–5)', 'type' => 'number', 'step' => '0.1', 'min' => 0, 'max' => 5, 'col' => 1],
                ['name' => 'featured', 'label' => 'Featured on the homepage', 'type' => 'toggle', 'col' => 1],
                ['name' => 'active', 'label' => 'Live on the website', 'type' => 'toggle', 'col' => 1, 'default' => 1],
                ['section' => 'Photos'],
                ['name' => 'cover_image', 'label' => 'Cover photo', 'type' => 'image', 'col' => 2, 'help' => 'Landscape, at least 1600px wide.'],
                ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'gallery', 'col' => 2],
                ['section' => 'Content'],
                ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'col' => 4, 'rows' => 2, 'max' => 400, 'help' => 'One or two sentences for cards and search results.'],
                ['name' => 'description', 'label' => 'Overview', 'type' => 'richtext', 'col' => 4],
                ['name' => 'highlights', 'label' => 'Highlights', 'type' => 'lines', 'col' => 4, 'rows' => 4, 'help' => 'One per line.'],
                ['name' => 'inclusions', 'label' => 'What is included', 'type' => 'lines', 'col' => 2, 'rows' => 7, 'help' => 'One per line.'],
                ['name' => 'exclusions', 'label' => 'Not included', 'type' => 'lines', 'col' => 2, 'rows' => 7, 'help' => 'One per line.'],
                ['name' => 'itinerary', 'label' => 'Day-by-day itinerary', 'type' => 'itinerary', 'col' => 4],
            ], $seo),
        ],
        'destinations' => [
            'label' => 'Destinations', 'singular' => 'destination', 'icon' => 'globe', 'role' => 'editor',
            'title' => 'name', 'public' => '/destinations/{slug}', 'sortable' => true,
            'order' => 'sort_order ASC, name ASC', 'search' => ['name', 'country', 'region'],
            'filters' => ['region' => 'Region'],
            'list' => [
                'cover_image' => ['type' => 'image'],
                'name' => ['type' => 'title', 'sub' => '{country} · {airport_code}'],
                'region' => ['type' => 'text', 'label' => 'Region'],
                'featured' => ['type' => 'toggle', 'label' => 'Featured'],
                'active' => ['type' => 'toggle', 'label' => 'Live'],
            ],
            'fields' => array_merge([
                ['section' => 'Basics'],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'col' => 2],
                ['name' => 'slug', 'label' => 'URL', 'type' => 'slug', 'source' => 'name', 'col' => 2],
                ['name' => 'country', 'label' => 'Country', 'type' => 'text', 'col' => 2],
                ['name' => 'region', 'label' => 'Region', 'type' => 'text', 'col' => 1, 'datalist' => 'destinations.region', 'placeholder' => 'Asia'],
                ['name' => 'airport_code', 'label' => 'Main airport code', 'type' => 'text', 'col' => 1, 'max' => 3, 'upper' => true, 'help' => 'Used for “Search flights”.'],
                ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'col' => 2, 'placeholder' => 'Overwater villas and house reefs'],
                ['name' => 'best_time', 'label' => 'Best time to visit', 'type' => 'text', 'col' => 2, 'placeholder' => 'November to April'],
                ['name' => 'cover_image', 'label' => 'Cover photo', 'type' => 'image', 'col' => 2],
                ['name' => 'featured', 'label' => 'Featured on the homepage', 'type' => 'toggle', 'col' => 1],
                ['name' => 'active', 'label' => 'Live on the website', 'type' => 'toggle', 'col' => 1, 'default' => 1],
                ['name' => 'description', 'label' => 'About this destination', 'type' => 'richtext', 'col' => 4],
            ], $seo),
        ],
        'visas' => [
            'label' => 'Visas', 'singular' => 'visa', 'icon' => 'passport', 'role' => 'editor',
            'title' => 'country', 'public' => '/visas/{slug}', 'sortable' => true,
            'order' => 'sort_order ASC, country ASC', 'search' => ['country', 'visa_type'],
            'list' => [
                'cover_image' => ['type' => 'image'],
                'country' => ['type' => 'title', 'sub' => '{visa_type}'],
                'processing_time' => ['type' => 'text', 'label' => 'Processing'],
                'price' => ['type' => 'money', 'label' => 'Fee from'],
                'active' => ['type' => 'toggle', 'label' => 'Live'],
            ],
            'fields' => array_merge([
                ['section' => 'Basics'],
                ['name' => 'country', 'label' => 'Country', 'type' => 'text', 'required' => true, 'col' => 2],
                ['name' => 'slug', 'label' => 'URL', 'type' => 'slug', 'source' => 'country', 'col' => 2],
                ['name' => 'visa_type', 'label' => 'Visa type', 'type' => 'text', 'col' => 2, 'placeholder' => 'Tourist visa'],
                ['name' => 'price', 'label' => 'Fee from', 'type' => 'money', 'col' => 1],
                ['name' => 'processing_time', 'label' => 'Processing time', 'type' => 'text', 'col' => 1, 'placeholder' => '3–5 working days'],
                ['name' => 'validity', 'label' => 'Validity', 'type' => 'text', 'col' => 1, 'placeholder' => '60 days'],
                ['name' => 'stay', 'label' => 'Maximum stay', 'type' => 'text', 'col' => 1, 'placeholder' => '30 days'],
                ['name' => 'entry', 'label' => 'Entries', 'type' => 'select', 'col' => 1, 'options' => ['Single' => 'Single entry', 'Multiple' => 'Multiple entry']],
                ['name' => 'active', 'label' => 'Live on the website', 'type' => 'toggle', 'col' => 1, 'default' => 1],
                ['name' => 'cover_image', 'label' => 'Photo', 'type' => 'image', 'col' => 2],
                ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'col' => 2, 'rows' => 3, 'max' => 400],
                ['name' => 'requirements', 'label' => 'Documents required', 'type' => 'lines', 'col' => 4, 'rows' => 7, 'help' => 'One per line.'],
                ['name' => 'description', 'label' => 'Details', 'type' => 'richtext', 'col' => 4],
            ], $seo),
        ],
        'posts' => [
            'label' => 'Travel journal', 'singular' => 'article', 'icon' => 'book', 'role' => 'editor',
            'title' => 'title', 'public' => '/blog/{slug}',
            'order' => 'COALESCE(published_at, created_at) DESC', 'search' => ['title', 'category'],
            'filters' => ['status' => 'Status', 'category' => 'Category'],
            'list' => [
                'cover_image' => ['type' => 'image'],
                'title' => ['type' => 'title', 'sub' => '{category}'],
                'status' => ['type' => 'badge', 'label' => 'Status'],
                'published_at' => ['type' => 'date', 'label' => 'Published'],
                'views' => ['type' => 'text', 'label' => 'Views'],
            ],
            'fields' => array_merge([
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'col' => 3],
                ['name' => 'slug', 'label' => 'URL', 'type' => 'slug', 'source' => 'title', 'col' => 1],
                ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'col' => 1, 'datalist' => 'posts.category', 'placeholder' => 'Travel tips'],
                ['name' => 'author', 'label' => 'Author', 'type' => 'text', 'col' => 1],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'col' => 1, 'options' => ['draft' => 'Draft', 'published' => 'Published']],
                ['name' => 'published_at', 'label' => 'Publish date', 'type' => 'datetime', 'col' => 1],
                ['name' => 'cover_image', 'label' => 'Cover photo', 'type' => 'image', 'col' => 2],
                ['name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'col' => 2, 'rows' => 4, 'max' => 400],
                ['name' => 'body', 'label' => 'Article', 'type' => 'richtext', 'col' => 4, 'tall' => true],
            ], $seo),
        ],
        'testimonials' => [
            'label' => 'Reviews', 'singular' => 'review', 'icon' => 'quote', 'role' => 'editor',
            'title' => 'name', 'sortable' => true, 'order' => 'sort_order ASC, id DESC', 'search' => ['name', 'trip', 'body'],
            'list' => [
                'photo' => ['type' => 'image', 'round' => true],
                'name' => ['type' => 'title', 'sub' => '{trip}'],
                'rating' => ['type' => 'stars', 'label' => 'Rating'],
                'active' => ['type' => 'toggle', 'label' => 'Live'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Customer name', 'type' => 'text', 'required' => true, 'col' => 2],
                ['name' => 'location', 'label' => 'From', 'type' => 'text', 'col' => 2, 'placeholder' => 'Sharjah, UAE'],
                ['name' => 'trip', 'label' => 'Trip', 'type' => 'text', 'col' => 2, 'placeholder' => 'Maldives honeymoon, March 2026'],
                ['name' => 'rating', 'label' => 'Stars', 'type' => 'select', 'col' => 1, 'options' => ['5' => '5 stars', '4' => '4 stars', '3' => '3 stars', '2' => '2 stars', '1' => '1 star']],
                ['name' => 'active', 'label' => 'Live on the website', 'type' => 'toggle', 'col' => 1, 'default' => 1],
                ['name' => 'body', 'label' => 'Review', 'type' => 'textarea', 'required' => true, 'col' => 4, 'rows' => 5],
                ['name' => 'photo', 'label' => 'Photo (optional)', 'type' => 'image', 'col' => 2],
            ],
        ],
        'faqs' => [
            'label' => 'FAQs', 'singular' => 'question', 'icon' => 'help', 'role' => 'editor',
            'title' => 'question', 'sortable' => true, 'order' => 'sort_order ASC, id ASC', 'search' => ['question', 'answer'],
            'filters' => ['category' => 'Topic'],
            'list' => [
                'question' => ['type' => 'title', 'sub' => '{category}'],
                'active' => ['type' => 'toggle', 'label' => 'Live'],
            ],
            'fields' => [
                ['name' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true, 'col' => 3],
                ['name' => 'category', 'label' => 'Topic', 'type' => 'text', 'col' => 1, 'datalist' => 'faqs.category', 'placeholder' => 'Booking'],
                ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'required' => true, 'col' => 4, 'rows' => 6],
                ['name' => 'active', 'label' => 'Live on the website', 'type' => 'toggle', 'col' => 2, 'default' => 1],
            ],
        ],
        'pages' => [
            'label' => 'Pages', 'singular' => 'page', 'icon' => 'file', 'role' => 'editor',
            'title' => 'title', 'public' => '/{slug}', 'sortable' => true, 'order' => 'sort_order ASC, title ASC', 'search' => ['title'],
            'list' => [
                'title' => ['type' => 'title', 'sub' => '/{slug}'],
                'show_in_footer' => ['type' => 'toggle', 'label' => 'In footer'],
                'active' => ['type' => 'toggle', 'label' => 'Live'],
            ],
            'fields' => array_merge([
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'col' => 3],
                ['name' => 'slug', 'label' => 'URL', 'type' => 'slug', 'source' => 'title', 'col' => 1],
                ['name' => 'show_in_footer', 'label' => 'Link in the footer', 'type' => 'toggle', 'col' => 2, 'default' => 1],
                ['name' => 'active', 'label' => 'Live on the website', 'type' => 'toggle', 'col' => 2, 'default' => 1],
                ['name' => 'body', 'label' => 'Content', 'type' => 'richtext', 'col' => 4, 'tall' => true],
            ], $seo),
        ],
    ];
    return $r;
}

function resource(string $name): ?array
{
    $r = resources()[$name] ?? null;
    if ($r) {
        $r['name'] = $name;
    }
    return $r;
}

function resource_fields(array $res): array
{
    return array_values(array_filter($res['fields'], fn($f) => isset($f['name'])));
}

/** Read, validate and normalise posted values. Returns [data, errors]. */
function resource_input(array $res, ?array $existing = null): array
{
    $data = [];
    $errors = [];
    foreach (resource_fields($res) as $f) {
        $n = $f['name'];
        $raw = $_POST[$n] ?? null;
        switch ($f['type']) {
            case 'toggle':
                $v = !empty($raw) ? 1 : 0;
                break;
            case 'number':
            case 'money':
                $v = is_string($raw) && trim($raw) !== '' ? (float) str_replace(',', '', $raw) : null;
                if ($v !== null && isset($f['min']) && $v < $f['min']) $errors[$n] = $f['label'] . ' must be at least ' . $f['min'] . '.';
                if ($v !== null && isset($f['max']) && $v > $f['max']) $errors[$n] = $f['label'] . ' must be at most ' . $f['max'] . '.';
                if ($v !== null && ($f['type'] === 'number' && empty($f['step']))) $v = (int) $v;
                if ($v === null && array_key_exists('empty', $f)) $v = $f['empty']; // NOT NULL columns
                break;
            case 'relation':
                $v = is_numeric($raw) && (int) $raw > 0 ? (int) $raw : null;
                break;
            case 'richtext':
                $v = clean_html(is_string($raw) ? $raw : '');
                break;
            case 'lines':
                $v = json_enc(lines_to_array(is_string($raw) ? $raw : ''));
                break;
            case 'gallery':
                $v = json_enc(array_values(array_filter(array_map('trim', (array) $raw), fn($x) => $x !== '' && safe_url($x, true))));
                break;
            case 'itinerary':
                $days = [];
                foreach ((array) ($_POST[$n . '_title'] ?? []) as $i => $title) {
                    $title = trim((string) $title);
                    $text = trim((string) ($_POST[$n . '_text'][$i] ?? ''));
                    if ($title !== '' || $text !== '') {
                        $days[] = ['title' => mb_substr($title, 0, 190), 'text' => mb_substr($text, 0, 2000)];
                    }
                }
                $v = json_enc($days);
                break;
            case 'datetime':
                $v = is_string($raw) && trim($raw) !== '' && strtotime($raw) ? date('Y-m-d H:i:s', strtotime($raw)) : null;
                break;
            case 'image':
                $v = is_string($raw) ? trim($raw) : '';
                if (mb_strlen($v) > 500) {
                    $errors[$n] = 'This image link is too long. Upload the image instead.';
                } elseif ($v !== '' && !safe_url($v, true)) {
                    $errors[$n] = 'Use an uploaded image or an https:// link.';
                }
                $v = $v ?: null;
                break;
            case 'select':
                $v = is_string($raw) && array_key_exists($raw, $f['options']) ? $raw : (string) array_key_first($f['options']);
                break;
            default:
                $v = is_string($raw) ? trim($raw) : '';
                if (!empty($f['upper'])) $v = strtoupper($v);
                // Matches the column sizes: VARCHAR(190) for text inputs, TEXT for textareas without a max.
                $max = $f['max'] ?? ($f['type'] === 'textarea' ? 20000 : 190);
                if (mb_strlen($v) > $max) $errors[$n] = $f['label'] . ' is too long (max ' . $max . ' characters).';
                $v = $v === '' ? null : $v;
        }
        if (!empty($f['required']) && ($v === null || $v === '' || $v === '[]')) {
            $errors[$n] = $f['label'] . ' is required.';
        }
        $data[$n] = $v;
    }
    // Slugs: generate from the source field when left blank, keep them unique.
    foreach (resource_fields($res) as $f) {
        if ($f['type'] === 'slug') {
            $base = $data[$f['name']] ?: ($data[$f['source']] ?? '');
            $data[$f['name']] = $base ? unique_slug($res['name'], (string) $base, $existing['id'] ?? null) : null;
            if (!$data[$f['name']]) $errors[$f['name']] = 'Add a title so the URL can be created.';
        }
    }
    if ($res['name'] === 'posts' && $data['status'] === 'published' && !$data['published_at']) {
        $data['published_at'] = now();
    }
    return [$data, $errors];
}

/** Value prepared for a form input. */
function resource_form_value(array $f, ?array $item)
{
    $v = $item[$f['name']] ?? ($item === null ? ($f['default'] ?? null) : null);
    switch ($f['type']) {
        case 'lines':
            return implode("\n", json_dec($v, []));
        case 'gallery':
        case 'itinerary':
            return json_dec($v, []);
        case 'datetime':
            return $v ? date('Y-m-d\TH:i', strtotime((string) $v)) : '';
        case 'money':
            return amount_input($v);
        default:
            return $v;
    }
}

function datalist_values(string $spec): array
{
    [$table, $col] = explode('.', $spec);
    return array_column(rows("SELECT DISTINCT $col AS v FROM $table WHERE $col IS NOT NULL AND $col <> '' ORDER BY $col"), 'v');
}

function fill_template(string $tpl, array $item): string
{
    $out = preg_replace_callback('/\{(\w+)\}/', fn($m) => (string) ($item[$m[1]] ?? ''), $tpl);
    return trim(preg_replace('/(\s·\s)+$|^(\s·\s)+/', '', (string) $out));
}
