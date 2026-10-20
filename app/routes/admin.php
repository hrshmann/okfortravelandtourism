<?php
declare(strict_types=1);

/* ============================================================== helpers */
function admin_view(string $template, array $data = []): void
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    view('admin/' . $template, $data, 'admin');
}

function admin_post(string $role = 'agent'): array
{
    $u = require_role($role);
    csrf_check();
    return $u;
}

function lead_badge(string $status): string
{
    $c = LEAD_STATUSES[$status]['color'] ?? 'grey';
    return '<span class="badge ' . e($c) . '">' . e(lead_status_label($status)) . '</span>';
}

function new_leads_count(): int
{
    static $n = null;
    return $n ??= (int) val("SELECT COUNT(*) FROM leads WHERE status = 'new' AND channel = 'form'");
}

function staff_list(): array
{
    return rows('SELECT id, name, role FROM users WHERE active = 1 ORDER BY name');
}

function csv_download(string $filename, array $header, iterable $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel needs the BOM to read UTF-8
    fputcsv($out, $header);
    foreach ($rows as $r) {
        // Neutralise spreadsheet formulas in user-supplied text.
        fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $r));
    }
    fclose($out);
    exit;
}

/* ================================================================ auth */
route('GET|POST', '/admin/login', function () {
    if (auth_user()) {
        redirect('/admin');
    }
    $error = null;
    $email = '';
    if (is_post()) {
        csrf_check();
        $email = (string) input('email');
        $error = attempt_login($email, (string) ($_POST['password'] ?? ''));
        if (!$error) {
            $to = $_SESSION['intended'] ?? '';
            unset($_SESSION['intended']);
            redirect($to && str_starts_with(parse_url($to, PHP_URL_PATH) ?: '', base_path() . '/admin') ? $to : '/admin');
        }
    }
    echo render('admin/login', compact('error', 'email'));
});

route('POST', '/admin/logout', function () {
    csrf_check();
    logout_user();
    redirect('/admin/login');
});

route('GET|POST', '/admin/forgot', function () {
    $sent = false;
    if (is_post()) {
        csrf_check();
        $u = row('SELECT * FROM users WHERE email = ? AND active = 1', [mb_strtolower(trim((string) input('email')))]);
        if ($u && !login_throttled()) {
            $token = bin2hex(random_bytes(24));
            update('users', ['reset_token' => hash('sha256', $token), 'reset_expires' => date('Y-m-d H:i:s', time() + 3600)], 'id = ?', [$u['id']]);
            $link = abs_url('/admin/reset/' . $token);
            send_mail($u['email'], 'Reset your admin password', email_template('Reset your password', '<p>Someone asked to reset the password for your ' . e(setting('site_name')) . ' admin account. The link works for one hour.</p><p>If this was not you, ignore this email.</p>', ['label' => 'Choose a new password', 'url' => $link]));
        }
        insert('login_attempts', ['ip' => client_ip(), 'email' => 'reset', 'created_at' => now()]);
        $sent = true; // same answer either way, so emails cannot be guessed
    }
    echo render('admin/forgot', compact('sent'));
});

route('GET|POST', '/admin/reset/{token}', function (string $token) {
    $u = row('SELECT * FROM users WHERE reset_token = ? AND reset_expires > ?', [hash('sha256', $token), now()]);
    $error = null;
    if ($u && is_post()) {
        csrf_check();
        $pw = (string) ($_POST['password'] ?? '');
        $error = password_problem($pw) ?? ($pw !== ($_POST['password2'] ?? '') ? 'The two passwords do not match.' : null);
        if (!$error) {
            update('users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'reset_token' => null, 'reset_expires' => null, 'updated_at' => now()], 'id = ?', [$u['id']]);
            log_activity('password_reset', 'user', (int) $u['id'], 'Password reset by email link', (int) $u['id']);
            flash('success', 'Password changed. Sign in with your new password.');
            redirect('/admin/login');
        }
    }
    echo render('admin/reset', ['valid' => (bool) $u, 'error' => $error]);
});

/* =========================================================== dashboard */
route('GET', '/admin', function () {
    $u = require_role('agent');
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01 00:00:00');
    $lastMonthStart = date('Y-m-01 00:00:00', strtotime('first day of last month'));
    $since30 = date('Y-m-d', strtotime('-29 days'));

    $count = fn(string $where, array $p = []) => (int) val("SELECT COUNT(*) FROM leads WHERE $where", $p);
    $kpi = [
        'new' => $count("status = 'new' AND channel = 'form'"),
        'month' => $count("channel = 'form' AND created_at >= ?", [$monthStart]),
        'lastMonth' => $count("channel = 'form' AND created_at >= ? AND created_at < ?", [$lastMonthStart, $monthStart]),
        'confirmed' => $count("status IN ('confirmed','completed') AND updated_at >= ?", [$monthStart]),
        'revenue' => (float) val("SELECT COALESCE(SUM(paid_amount),0) FROM leads WHERE status IN ('confirmed','completed') AND updated_at >= ?", [$monthStart]),
        'pipeline' => (float) val("SELECT COALESCE(SUM(quoted_amount),0) FROM leads WHERE status IN ('quoted','contacted')"),
        'visits' => (int) val('SELECT COALESCE(SUM(hits),0) FROM visits WHERE day >= ?', [$since30]),
        'wa' => $count("channel = 'whatsapp' AND created_at >= ?", [$since30 . ' 00:00:00']),
    ];

    // 30-day series for the chart.
    $series = [];
    for ($i = 29; $i >= 0; $i--) {
        $series[date('Y-m-d', strtotime("-$i days"))] = ['visits' => 0, 'leads' => 0];
    }
    foreach (rows('SELECT day, SUM(hits) AS n FROM visits WHERE day >= ? GROUP BY day', [$since30]) as $r) {
        if (isset($series[$r['day']])) $series[$r['day']]['visits'] = (int) $r['n'];
    }
    foreach (rows("SELECT created_at FROM leads WHERE created_at >= ?", [$since30 . ' 00:00:00']) as $r) {
        $d = substr($r['created_at'], 0, 10);
        if (isset($series[$d])) $series[$d]['leads']++;
    }

    $funnel = [];
    foreach (LEAD_STATUSES as $k => $s) {
        $funnel[$k] = $count("status = ? AND channel = 'form'", [$k]);
    }
    admin_view('dashboard', [
        'u' => $u, 'kpi' => $kpi, 'series' => $series, 'funnel' => $funnel,
        'latest' => rows("SELECT l.*, u.name AS agent FROM leads l LEFT JOIN users u ON u.id = l.assigned_to WHERE l.channel = 'form' ORDER BY l.created_at DESC" . limit_sql(8)),
        'followups' => rows("SELECT * FROM leads WHERE follow_up_at IS NOT NULL AND follow_up_at <= ? AND status NOT IN ('completed','cancelled','lost') ORDER BY follow_up_at ASC" . limit_sql(6), [$today . ' 23:59:59']),
        'topPackages' => rows('SELECT id, title, slug, views, (SELECT COUNT(*) FROM leads WHERE package_id = packages.id) AS leads FROM packages ORDER BY views DESC' . limit_sql(5)),
        'topPages' => rows('SELECT path, SUM(hits) AS n FROM visits WHERE day >= ? GROUP BY path ORDER BY n DESC' . limit_sql(6), [$since30]),
        'checklist' => setup_checklist(),
    ]);
});

function setup_checklist(): array
{
    $isDefault = fn($k) => (string) setting($k) === (string) (settings_defaults()[$k] ?? '');
    return [
        ['label' => 'Add your real WhatsApp number', 'done' => !$isDefault('whatsapp'), 'url' => '/admin/settings#contact'],
        ['label' => 'Add your phone, email and office address', 'done' => !$isDefault('phone') && !$isDefault('address'), 'url' => '/admin/settings#contact'],
        ['label' => 'Set up email sending (SMTP)', 'done' => setting('smtp_host', '') !== '', 'url' => '/admin/settings#email'],
        ['label' => 'Replace the sample reviews with real ones', 'done' => !val("SELECT COUNT(*) FROM testimonials WHERE name IN ('Ahmed K.', 'Sara & Omar', 'Rajesh M.', 'Fatima Z.', 'Michael B.')"), 'url' => '/admin/testimonials'],
        ['label' => 'Check the homepage numbers are accurate', 'done' => !$isDefault('stats'), 'url' => '/admin/settings#homepage'],
        ['label' => 'Add your social media links', 'done' => (bool) social_links(), 'url' => '/admin/settings#social'],
        ['label' => 'Connect Google Analytics', 'done' => setting('ga4_id', '') !== '', 'url' => '/admin/settings#seo'],
        ['label' => 'Turn on HTTPS redirect once SSL is active', 'done' => setting('force_https') === '1', 'url' => '/admin/settings#system'],
    ];
}

/* =============================================================== leads */
route('GET', '/admin/leads', function () {
    $u = require_role('agent');
    $f = [
        'status' => (string) input('status'), 'type' => (string) input('type'), 'channel' => (string) input('channel', 'form'),
        'q' => (string) input('q'), 'agent' => (string) input('agent'),
    ];
    [$where, $params] = leads_filter_sql($f);
    $page = paginate("SELECT l.*, u.name AS agent_name FROM leads l LEFT JOIN users u ON u.id = l.assigned_to WHERE $where", $params, input_int('page', 1), 25, 'l.created_at DESC');
    $statusCounts = array_column(rows("SELECT status, COUNT(*) AS n FROM leads WHERE channel = ? GROUP BY status", [$f['channel'] === 'whatsapp' ? 'whatsapp' : 'form']), 'n', 'status');
    admin_view('leads', ['u' => $u, 'f' => $f, 'page' => $page, 'statusCounts' => $statusCounts, 'staff' => staff_list()]);
});

function leads_filter_sql(array $f): array
{
    $where = ['l.channel = ?'];
    $params = [$f['channel'] === 'whatsapp' ? 'whatsapp' : 'form'];
    if ($f['status'] !== '' && isset(LEAD_STATUSES[$f['status']])) {
        $where[] = 'l.status = ?';
        $params[] = $f['status'];
    } elseif ($f['status'] === 'open') {
        $where[] = "l.status IN ('new','contacted','quoted')";
    }
    if ($f['type'] !== '' && isset(LEAD_TYPES[$f['type']])) {
        $where[] = 'l.type = ?';
        $params[] = $f['type'];
    }
    if ($f['agent'] === 'me') {
        $where[] = 'l.assigned_to = ?';
        $params[] = auth_user()['id'];
    } elseif ($f['agent'] === 'none') {
        $where[] = 'l.assigned_to IS NULL';
    }
    if ($f['q'] !== '') {
        $where[] = '(l.ref LIKE ? OR l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.subject LIKE ?)';
        array_push($params, ...array_fill(0, 5, '%' . $f['q'] . '%'));
    }
    return [implode(' AND ', $where), $params];
}

route('GET', '/admin/leads/export', function () {
    require_role('editor');
    $f = ['status' => (string) input('status'), 'type' => (string) input('type'), 'channel' => (string) input('channel', 'form'), 'q' => (string) input('q'), 'agent' => (string) input('agent')];
    [$where, $params] = leads_filter_sql($f);
    $list = rows("SELECT l.*, u.name AS agent_name FROM leads l LEFT JOIN users u ON u.id = l.assigned_to WHERE $where ORDER BY l.created_at DESC", $params);
    log_activity('export', 'leads', null, 'Exported ' . count($list) . ' leads');
    csv_download('leads-' . date('Y-m-d') . '.csv',
        ['Reference', 'Created', 'Status', 'Type', 'Name', 'Phone', 'Email', 'Subject', 'Travel date', 'Adults', 'Children', 'Infants', 'Budget', 'Quoted', 'Paid', 'Assigned to', 'Message', 'Source page', 'UTM'],
        array_map(fn($l) => [$l['ref'], $l['created_at'], lead_status_label($l['status']), lead_type_label($l['type']), $l['name'], $l['phone'], $l['email'], $l['subject'], $l['travel_date'], $l['adults'], $l['children'], $l['infants'], $l['budget'], $l['quoted_amount'], $l['paid_amount'], $l['agent_name'], $l['message'], $l['source_page'], $l['utm']], $list));
});

route('GET|POST', '/admin/leads/new', function () {
    $u = require_role('agent');
    if (is_post()) {
        csrf_check();
        [$lead, $errors] = lead_from_request($_POST, 'form');
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/admin/leads/new');
        }
        update('leads', ['source_page' => 'Added manually by ' . $u['name'], 'assigned_to' => $u['id'], 'ip' => null, 'user_agent' => null], 'id = ?', [$lead['id']]);
        log_activity('create', 'lead', (int) $lead['id'], 'Added lead ' . $lead['ref']);
        flash('success', 'Lead ' . $lead['ref'] . ' added.');
        redirect('/admin/leads/' . $lead['id']);
    }
    admin_view('lead_new', ['u' => $u, 'packages' => rows('SELECT id, title FROM packages ORDER BY title')]);
});

route('POST', '/admin/leads/bulk', function () {
    admin_post('editor');
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    $action = (string) input('action');
    if ($ids) {
        $in = implode(',', $ids);
        if ($action === 'delete') {
            q("DELETE FROM lead_notes WHERE lead_id IN ($in)");
            q("DELETE FROM leads WHERE id IN ($in)");
            log_activity('delete', 'lead', null, 'Deleted ' . count($ids) . ' leads');
            flash('success', plural(count($ids), 'lead') . ' deleted.');
        } elseif (isset(LEAD_STATUSES[$action])) {
            q("UPDATE leads SET status = ?, updated_at = ? WHERE id IN ($in)", [$action, now()]);
            flash('success', plural(count($ids), 'lead') . ' marked ' . lead_status_label($action) . '.');
        }
    }
    back('/admin/leads');
});

route('GET', '/admin/leads/{id}', function (string $id) {
    $u = require_role('agent');
    $lead = row('SELECT * FROM leads WHERE id = ?', [(int) $id]);
    if (!$lead) {
        not_found();
    }
    admin_view('lead', [
        'u' => $u, 'lead' => $lead,
        'package' => $lead['package_id'] ? row('SELECT id, title, slug, price FROM packages WHERE id = ?', [$lead['package_id']]) : null,
        'notes' => rows('SELECT n.*, u.name AS author FROM lead_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.lead_id = ? ORDER BY n.created_at DESC, n.id DESC', [$lead['id']]),
        'staff' => staff_list(),
        'history' => $lead['email'] || $lead['phone'] ? rows('SELECT id, ref, subject, status, created_at FROM leads WHERE id <> ? AND ((email IS NOT NULL AND email = ?) OR (phone IS NOT NULL AND phone = ?)) ORDER BY created_at DESC' . limit_sql(10), [$lead['id'], $lead['email'] ?? '', $lead['phone'] ?? '']) : [],
    ]);
});

route('POST', '/admin/leads/{id}', function (string $id) {
    $u = admin_post('agent');
    $lead = row('SELECT * FROM leads WHERE id = ?', [(int) $id]);
    if (!$lead) {
        not_found();
    }
    $data = [];
    $status = (string) input('status', $lead['status']);
    if (isset(LEAD_STATUSES[$status])) $data['status'] = $status;
    foreach (['name', 'email', 'phone', 'subject', 'budget'] as $k) {
        if (isset($_POST[$k])) $data[$k] = mb_substr(trim((string) $_POST[$k]), 0, 255) ?: null;
    }
    foreach (['adults', 'children', 'infants'] as $k) {
        if (isset($_POST[$k])) $data[$k] = max(0, min(99, (int) $_POST[$k]));
    }
    foreach (['quoted_amount', 'paid_amount'] as $k) {
        if (isset($_POST[$k])) $data[$k] = trim((string) $_POST[$k]) === '' ? null : round((float) str_replace(',', '', (string) $_POST[$k]), 2);
    }
    foreach (['travel_date', 'return_date'] as $k) {
        if (isset($_POST[$k])) $data[$k] = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST[$k]) ? $_POST[$k] : null;
    }
    if (isset($_POST['follow_up_at'])) $data['follow_up_at'] = trim((string) $_POST['follow_up_at']) !== '' && strtotime((string) $_POST['follow_up_at']) ? date('Y-m-d H:i:s', strtotime((string) $_POST['follow_up_at'])) : null;
    if (isset($_POST['assigned_to'])) $data['assigned_to'] = (int) $_POST['assigned_to'] ?: null;
    $data['updated_at'] = now();
    update('leads', $data, 'id = ?', [$lead['id']]);

    if (($data['status'] ?? $lead['status']) !== $lead['status']) {
        insert('lead_notes', ['lead_id' => $lead['id'], 'user_id' => $u['id'], 'kind' => 'status', 'body' => lead_status_label($data['status'], true), 'created_at' => now()]);
        log_activity('status', 'lead', (int) $lead['id'], $lead['ref'] . ': ' . lead_status_label($lead['status']) . ' → ' . lead_status_label($data['status']));
    }
    if (isset($data['assigned_to']) && $data['assigned_to'] !== ($lead['assigned_to'] === null ? null : (int) $lead['assigned_to'])) {
        $who = $data['assigned_to'] ? val('SELECT name FROM users WHERE id = ?', [$data['assigned_to']]) : 'nobody';
        insert('lead_notes', ['lead_id' => $lead['id'], 'user_id' => $u['id'], 'kind' => 'system', 'body' => 'Assigned to ' . $who . '.', 'created_at' => now()]);
    }
    if (is_api_request()) {
        json_out(['ok' => true]);
    }
    flash('success', 'Saved.');
    redirect('/admin/leads/' . $lead['id']);
});

route('POST', '/admin/leads/{id}/note', function (string $id) {
    $u = admin_post('agent');
    $body = trim((string) input('body'));
    if ($body !== '' && val('SELECT id FROM leads WHERE id = ?', [(int) $id])) {
        insert('lead_notes', ['lead_id' => (int) $id, 'user_id' => $u['id'], 'kind' => 'note', 'body' => mb_substr($body, 0, 5000), 'created_at' => now()]);
        update('leads', ['updated_at' => now()], 'id = ?', [(int) $id]);
    }
    redirect('/admin/leads/' . (int) $id . '#notes');
});

route('POST', '/admin/leads/{id}/delete', function (string $id) {
    admin_post('editor');
    $lead = row('SELECT ref FROM leads WHERE id = ?', [(int) $id]);
    if ($lead) {
        delete_rows('lead_notes', 'lead_id = ?', [(int) $id]);
        delete_rows('leads', 'id = ?', [(int) $id]);
        log_activity('delete', 'lead', (int) $id, 'Deleted lead ' . $lead['ref']);
        flash('success', 'Lead ' . $lead['ref'] . ' deleted.');
    }
    redirect('/admin/leads');
});

route('GET', '/admin/leads/{id}/print', function (string $id) {
    require_role('agent');
    $lead = row('SELECT l.*, u.name AS agent_name, u.email AS agent_email FROM leads l LEFT JOIN users u ON u.id = l.assigned_to WHERE l.id = ?', [(int) $id]);
    if (!$lead) {
        not_found();
    }
    $package = $lead['package_id'] ? row('SELECT * FROM packages WHERE id = ?', [$lead['package_id']]) : null;
    echo render('admin/lead_print', ['lead' => $lead, 'package' => $package, 'kind' => input('kind', 'quote') === 'invoice' ? 'invoice' : 'quote']);
});

/* ========================================================= content CRUD */
foreach (array_keys(resources()) as $resName) {
    route('GET', '/admin/' . $resName, function () use ($resName) {
        $res = resource($resName);
        $u = require_role($res['role']);
        $where = ['1=1'];
        $params = [];
        $qs = trim((string) input('q'));
        if ($qs !== '') {
            $where[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $res['search'])) . ')';
            array_push($params, ...array_fill(0, count($res['search']), '%' . $qs . '%'));
        }
        $filters = [];
        foreach ($res['filters'] ?? [] as $col => $label) {
            $filters[$col] = ['label' => $label, 'options' => array_column(rows("SELECT DISTINCT $col AS v FROM $resName WHERE $col IS NOT NULL AND $col <> '' ORDER BY $col"), 'v'), 'value' => (string) input($col)];
            if ($filters[$col]['value'] !== '') {
                $where[] = "$col = ?";
                $params[] = $filters[$col]['value'];
            }
        }
        $page = paginate("SELECT * FROM $resName WHERE " . implode(' AND ', $where), $params, input_int('page', 1), 50, $res['order']);
        admin_view('resource_list', ['u' => $u, 'res' => $res, 'page' => $page, 'qs' => $qs, 'filters' => $filters]);
    });

    route('GET|POST', '/admin/' . $resName . '/new', function () use ($resName) {
        $res = resource($resName);
        $u = require_role($res['role']);
        $item = null;
        $errors = [];
        if (is_post()) {
            csrf_check();
            [$data, $errors] = resource_input($res);
            if (!$errors) {
                $data['created_at'] = now();
                $data['updated_at'] = now();
                if (!empty($res['sortable'])) {
                    $data['sort_order'] = (int) val("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM $resName");
                }
                $id = insert($resName, $data);
                log_activity('create', $resName, $id, 'Created ' . $res['singular'] . ' “' . ($data[$res['title']] ?? '') . '”');
                flash('success', ucfirst($res['singular']) . ' created.');
                redirect('/admin/' . $resName . '/' . $id);
            }
            $item = $data;
            $GLOBALS['_old'] = $_POST;
        }
        admin_view('resource_form', ['u' => $u, 'res' => $res, 'item' => $item, 'errors' => $errors, 'isNew' => true]);
    });

    route('POST', '/admin/' . $resName . '/reorder', function () use ($resName) {
        $res = resource($resName);
        admin_post($res['role']);
        $ids = array_map('intval', (array) (request_json()['ids'] ?? $_POST['ids'] ?? []));
        $offset = max(0, input_int('offset', 0));
        db()->beginTransaction();
        foreach ($ids as $i => $rid) {
            update($resName, ['sort_order' => $offset + $i], 'id = ?', [$rid]);
        }
        db()->commit();
        json_out(['ok' => true]);
    });

    route('GET|POST', '/admin/' . $resName . '/{id}', function (string $id) use ($resName) {
        $res = resource($resName);
        $u = require_role($res['role']);
        $item = row("SELECT * FROM $resName WHERE id = ?", [(int) $id]);
        if (!$item) {
            not_found();
        }
        $errors = [];
        if (is_post()) {
            csrf_check();
            [$data, $errors] = resource_input($res, $item);
            if (!$errors) {
                $data['updated_at'] = now();
                update($resName, $data, 'id = ?', [$item['id']]);
                log_activity('update', $resName, (int) $item['id'], 'Updated ' . $res['singular'] . ' “' . ($data[$res['title']] ?? '') . '”');
                flash('success', 'Changes saved.');
                redirect('/admin/' . $resName . '/' . $item['id']);
            }
            $item = array_merge($item, $data);
        }
        admin_view('resource_form', ['u' => $u, 'res' => $res, 'item' => $item, 'errors' => $errors, 'isNew' => false]);
    });

    route('POST', '/admin/' . $resName . '/{id}/delete', function (string $id) use ($resName) {
        $res = resource($resName);
        admin_post($res['role']);
        $item = row("SELECT * FROM $resName WHERE id = ?", [(int) $id]);
        if ($item) {
            delete_rows($resName, 'id = ?', [(int) $id]);
            if ($resName === 'destinations') {
                q('UPDATE packages SET destination_id = NULL WHERE destination_id = ?', [(int) $id]);
            }
            log_activity('delete', $resName, (int) $id, 'Deleted ' . $res['singular'] . ' “' . ($item[$res['title']] ?? '') . '”');
            flash('success', ucfirst($res['singular']) . ' deleted.');
        }
        redirect('/admin/' . $resName);
    });

    route('POST', '/admin/' . $resName . '/{id}/duplicate', function (string $id) use ($resName) {
        $res = resource($resName);
        admin_post($res['role']);
        $item = row("SELECT * FROM $resName WHERE id = ?", [(int) $id]);
        if (!$item) {
            not_found();
        }
        unset($item['id']);
        $item[$res['title']] = $item[$res['title']] . ' (copy)';
        if (array_key_exists('slug', $item)) $item['slug'] = unique_slug($resName, $item['slug'] . '-copy');
        if (array_key_exists('active', $item)) $item['active'] = 0;
        if (array_key_exists('status', $item) && $resName === 'posts') $item['status'] = 'draft';
        if (array_key_exists('views', $item)) $item['views'] = 0;
        $item['created_at'] = now();
        $item['updated_at'] = now();
        $newId = insert($resName, $item);
        log_activity('create', $resName, $newId, 'Duplicated ' . $res['singular']);
        flash('success', 'Copy created and hidden from the website. Edit it, then switch it on.');
        redirect('/admin/' . $resName . '/' . $newId);
    });

    route('POST', '/admin/' . $resName . '/{id}/toggle', function (string $id) use ($resName) {
        $res = resource($resName);
        admin_post($res['role']);
        $field = (string) input('field');
        $toggles = array_column(array_filter(resource_fields($res), fn($f) => $f['type'] === 'toggle'), 'name');
        if (!in_array($field, $toggles, true)) {
            json_out(['error' => 'Unknown field'], 400);
        }
        $value = input('value') === '1' ? 1 : 0;
        update($resName, [$field => $value, 'updated_at' => now()], 'id = ?', [(int) $id]);
        json_out(['ok' => true, 'value' => $value]);
    });

}

/* =============================================================== media */
route('GET', '/admin/media', function () {
    $u = require_role('editor');
    $page = paginate('SELECT * FROM media', [], input_int('page', 1), 48, 'id DESC');
    admin_view('media', ['u' => $u, 'page' => $page]);
});

route('GET', '/admin/media.json', function () {
    require_role('editor');
    $page = paginate('SELECT id, path, original_name, width, height FROM media', [], input_int('page', 1), 60, 'id DESC');
    $page['items'] = array_map(fn($m) => $m + ['url' => img($m['path'])], $page['items']);
    json_out($page);
});

route('POST', '/admin/media/upload', function () {
    admin_post('editor');
    $done = [];
    $errors = [];
    foreach (files_list('files') as $file) {
        try {
            $m = store_upload($file);
            $done[] = $m + ['url' => img($m['path'])];
        } catch (RuntimeException $e) {
            $errors[] = $file['name'] . ': ' . $e->getMessage();
        }
    }
    if ($done) {
        log_activity('upload', 'media', (int) $done[0]['id'], 'Uploaded ' . plural(count($done), 'image'));
    }
    if (is_api_request()) {
        json_out(['items' => $done, 'errors' => $errors], $done || !$errors ? 200 : 422);
    }
    $done && flash('success', plural(count($done), 'image') . ' uploaded.');
    $errors && flash('error', implode(' ', $errors));
    redirect('/admin/media');
});

route('POST', '/admin/media/{id}/delete', function (string $id) {
    admin_post('editor');
    $m = row('SELECT * FROM media WHERE id = ?', [(int) $id]);
    if ($m) {
        $file = realpath(ROOT . '/' . $m['path']);
        if ($file && str_starts_with($file, realpath(UPLOADS)) && is_file($file)) {
            @unlink($file);
        }
        delete_rows('media', 'id = ?', [(int) $id]);
        log_activity('delete', 'media', (int) $id, 'Deleted image ' . $m['original_name']);
        flash('success', 'Image deleted. Pages that used it will show a placeholder until you choose another.');
    }
    redirect('/admin/media');
});

/* ========================================================= subscribers */
route('GET', '/admin/subscribers', function () {
    $u = require_role('editor');
    $qs = trim((string) input('q'));
    $page = paginate('SELECT * FROM subscribers' . ($qs !== '' ? ' WHERE email LIKE ? OR name LIKE ?' : ''), $qs !== '' ? ["%$qs%", "%$qs%"] : [], input_int('page', 1), 50, 'id DESC');
    admin_view('subscribers', ['u' => $u, 'page' => $page, 'qs' => $qs]);
});

route('GET', '/admin/subscribers/export', function () {
    require_role('editor');
    csv_download('subscribers-' . date('Y-m-d') . '.csv', ['Email', 'Name', 'Phone', 'Status', 'Source', 'Joined'],
        array_map(fn($s) => [$s['email'], $s['name'], $s['phone'], $s['status'], $s['source'], $s['created_at']], rows('SELECT * FROM subscribers ORDER BY id DESC')));
});

route('POST', '/admin/subscribers/{id}/delete', function (string $id) {
    admin_post('editor');
    delete_rows('subscribers', 'id = ?', [(int) $id]);
    flash('success', 'Subscriber removed.');
    back('/admin/subscribers');
});

/* =============================================================== users */
route('GET', '/admin/users', function () {
    $u = require_role('admin');
    admin_view('users', ['u' => $u, 'users' => rows('SELECT * FROM users ORDER BY active DESC, name ASC')]);
});

route('GET|POST', '/admin/users/{id}', function (string $id) {
    $u = require_role('admin');
    $isNew = $id === 'new';
    $item = $isNew ? null : row('SELECT * FROM users WHERE id = ?', [(int) $id]);
    if (!$isNew && !$item) {
        not_found();
    }
    $errors = [];
    if (is_post()) {
        csrf_check();
        $data = [
            'name' => mb_substr(trim((string) input('name')), 0, 120),
            'email' => mb_substr(mb_strtolower(trim((string) input('email'))), 0, 190),
            'role' => array_key_exists((string) input('role'), ROLES) ? (string) input('role') : 'agent',
            'active' => input('active') ? 1 : 0,
        ];
        $pw = (string) ($_POST['password'] ?? '');
        if ($data['name'] === '') $errors['name'] = 'Enter a name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        elseif (val('SELECT id FROM users WHERE email = ? AND id <> ?', [$data['email'], $item['id'] ?? 0])) $errors['email'] = 'Another account already uses this email.';
        if ($isNew || $pw !== '') {
            if ($p = password_problem($pw)) $errors['password'] = $p;
        }
        if (!$isNew && (int) $item['id'] === (int) $u['id'] && ($data['role'] !== 'admin' || !$data['active'])) {
            $errors['role'] = 'You cannot remove your own administrator access.';
        }
        if (!$errors) {
            if ($pw !== '') $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
            $data['updated_at'] = now();
            if ($isNew) {
                $data['created_at'] = now();
                $newId = insert('users', $data);
                log_activity('create', 'user', $newId, 'Added team member ' . $data['name']);
                flash('success', 'Team member added. Share their email and password with them securely.');
            } else {
                update('users', $data, 'id = ?', [$item['id']]);
                log_activity('update', 'user', (int) $item['id'], 'Updated team member ' . $data['name']);
                flash('success', 'Saved.');
            }
            redirect('/admin/users');
        }
        $item = array_merge($item ?? [], $data);
    }
    admin_view('user_form', ['u' => $u, 'item' => $item, 'isNew' => $isNew, 'errors' => $errors]);
});

route('POST', '/admin/users/{id}/delete', function (string $id) {
    $u = admin_post('admin');
    if ((int) $id === (int) $u['id']) {
        flash('error', 'You cannot delete your own account.');
    } elseif ($t = row('SELECT name FROM users WHERE id = ?', [(int) $id])) {
        q('UPDATE leads SET assigned_to = NULL WHERE assigned_to = ?', [(int) $id]);
        delete_rows('users', 'id = ?', [(int) $id]);
        log_activity('delete', 'user', (int) $id, 'Removed team member ' . $t['name']);
        flash('success', 'Team member removed. Their leads are now unassigned.');
    }
    redirect('/admin/users');
});

route('GET|POST', '/admin/profile', function () {
    $u = require_role('agent');
    $errors = [];
    if (is_post()) {
        csrf_check();
        $name = mb_substr(trim((string) input('name')), 0, 120);
        $email = mb_substr(mb_strtolower(trim((string) input('email'))), 0, 190);
        $current = (string) ($_POST['current_password'] ?? '');
        $pw = (string) ($_POST['password'] ?? '');
        if ($name === '') $errors['name'] = 'Enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        elseif (val('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $u['id']])) $errors['email'] = 'Another account already uses this email.';
        if (($email !== $u['email'] || $pw !== '') && !password_verify($current, $u['password_hash'])) $errors['current_password'] = 'Enter your current password to change your email or password.';
        if ($pw !== '' && ($p = password_problem($pw))) $errors['password'] = $p;
        if (!$errors) {
            $data = ['name' => $name, 'email' => $email, 'updated_at' => now()];
            if ($pw !== '') $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
            update('users', $data, 'id = ?', [$u['id']]);
            if ($pw !== '') {
                login_user(array_merge($u, $data)); // keep this session valid after the hash changes
                log_activity('password_change', 'user', (int) $u['id'], 'Changed own password');
            }
            flash('success', 'Profile saved.');
            redirect('/admin/profile');
        }
    }
    admin_view('profile', ['u' => $u, 'errors' => $errors]);
});

/* ============================================================ settings */
route('GET|POST', '/admin/settings', function () {
    $u = require_role('admin');
    if (is_post()) {
        csrf_check();
        $defaults = settings_defaults();
        $save = [];
        // Only fields that were actually submitted are changed, so a partial post can never wipe settings.
        // Switches post a hidden "0" before the checkbox, so an unticked switch still arrives.
        foreach ($defaults as $k => $_) {
            if (in_array($k, ['schema_version', 'stats', 'currencies', 'currency_symbol', 'fx_updated_at', 'fx_attempt_at', 'fx_error'], true)) continue;
            if (!array_key_exists($k, $_POST)) continue;
            $v = trim((string) $_POST[$k]);
            if (in_array($k, ['force_https', 'maintenance', 'customer_emails', 'currency_auto_detect', 'fx_auto'], true)) {
                $save[$k] = $v === '1' ? '1' : '0';
                continue;
            }
            // Secrets: an empty field keeps the stored value.
            if (in_array($k, ['smtp_pass', 'duffel_token', 'travelpayouts_token'], true) && $v === '') continue;
            $save[$k] = $v;
        }
        if (isset($save['whatsapp'])) $save['whatsapp'] = preg_replace('/\D/', '', $save['whatsapp']);
        if (isset($save['site_url'])) $save['site_url'] = rtrim($save['site_url'], '/');
        if (isset($save['currency']) && !isset(CURRENCIES[$save['currency']])) unset($save['currency']);
        if (isset($save['fx_margin'])) $save['fx_margin'] = (string) max(0, min(20, (float) $save['fx_margin']));
        if (isset($save['timezone']) && !in_array($save['timezone'], timezone_identifiers_list(), true)) unset($save['timezone']);

        if (isset($_POST['cur_rate']) && is_array($_POST['cur_rate'])) {
            // Rates are typed as "1 main currency = x" relative to the main currency shown on the page
            // (the one saved before this request), and stored per 1 USD so the main currency can change freely.
            $newBase = $save['currency'] ?? base_currency();
            $table = currency_table();
            $shownRate = $table[base_currency()]['rate'];
            $curr = [];
            foreach ($table as $code => $c) {
                $rel = (float) str_replace(',', '', (string) ($_POST['cur_rate'][$code] ?? ''));
                $curr[$code] = [
                    'enabled' => $code === $newBase || !empty($_POST['cur_enabled'][$code]) ? 1 : 0,
                    'rate' => $code === base_currency() || $rel <= 0 ? $c['rate'] : round($rel * $shownRate, 6),
                    'symbol' => mb_substr(trim((string) ($_POST['cur_symbol'][$code] ?? $c['symbol'])), 0, 5),
                ];
            }
            $save['currencies'] = json_enc($curr);
            $save['currency_symbol'] = $curr[$newBase]['symbol'] ?: CURRENCIES[$newBase][1];
        }
        if (isset($_POST['stat_value']) && is_array($_POST['stat_value'])) {
            $stats = [];
            foreach ($_POST['stat_value'] as $i => $v) {
                $label = trim((string) ($_POST['stat_label'][$i] ?? ''));
                if (trim((string) $v) !== '' && $label !== '') $stats[] = ['value' => mb_substr(trim((string) $v), 0, 20), 'label' => mb_substr($label, 0, 60)];
            }
            $save['stats'] = json_enc($stats);
        }
        foreach (['clear_smtp_pass' => 'smtp_pass', 'clear_duffel_token' => 'duffel_token', 'clear_travelpayouts_token' => 'travelpayouts_token'] as $flag => $key) {
            if (input($flag)) $save[$key] = '';
        }
        settings_save($save);
        log_activity('update', 'settings', null, 'Updated settings');
        flash('success', 'Settings saved.');
        redirect('/admin/settings' . (preg_match('/^#[a-z]+$/', (string) input('_tab')) ? input('_tab') : ''));
    }
    admin_view('settings', ['u' => $u]);
});

route('POST', '/admin/settings/fx-refresh', function () {
    admin_post('admin');
    $err = fx_refresh();
    log_activity('update', 'settings', null, 'Updated exchange rates');
    json_out($err ? ['error' => $err] : ['ok' => true, 'message' => 'Exchange rates updated.'], $err ? 422 : 200);
});

route('POST', '/admin/settings/test-email', function () {
    $u = admin_post('admin');
    $err = send_mail($u['email'], 'Test email from your website', email_template('Email is working', '<p>This test was sent from the admin panel of ' . e(setting('site_name')) . '. Lead notifications and customer confirmations will be delivered the same way.</p>'));
    json_out($err ? ['error' => $err] : ['ok' => true, 'message' => 'Test email sent to ' . $u['email'] . '. Check the inbox (and spam folder).'], $err ? 422 : 200);
});

/* ============================================================ activity */
route('GET', '/admin/activity', function () {
    $u = require_role('admin');
    $page = paginate('SELECT a.*, u.name AS user_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id', [], input_int('page', 1), 50, 'a.id DESC');
    admin_view('activity', ['u' => $u, 'page' => $page]);
});

/* ============================================================== backup */
route('GET', '/admin/backup', function () {
    $u = require_role('admin');
    $counts = [];
    foreach (array_keys(schema_tables()) as $t) {
        $counts[$t] = (int) val("SELECT COUNT(*) FROM $t");
    }
    $uploadsSize = 0;
    if (is_dir(UPLOADS)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOADS, FilesystemIterator::SKIP_DOTS)) as $f) {
            $uploadsSize += $f->getSize();
        }
    }
    admin_view('backup', ['u' => $u, 'counts' => $counts, 'uploadsSize' => $uploadsSize]);
});

route('GET', '/admin/backup/download', function () {
    require_role('admin');
    $dump = ['app' => 'oktravel', 'version' => APP_VERSION, 'schema' => SCHEMA_VERSION, 'exported_at' => date('c'), 'tables' => []];
    foreach (array_keys(schema_tables()) as $t) {
        if (in_array($t, ['login_attempts', 'visits'], true)) continue;
        $dump['tables'][$t] = rows("SELECT * FROM $t");
    }
    foreach ($dump['tables']['users'] as &$usr) {
        unset($usr['reset_token'], $usr['reset_expires']);
    }
    unset($usr);
    log_activity('backup', null, null, 'Downloaded a data backup');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="oktravel-backup-' . date('Y-m-d-His') . '.json"');
    echo json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
});
