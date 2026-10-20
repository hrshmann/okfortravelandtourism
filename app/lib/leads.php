<?php
declare(strict_types=1);

/** Pipeline stages. "public" is what the customer sees on the Track page. */
const LEAD_STATUSES = [
    'new' => ['label' => 'New', 'public' => 'Received', 'color' => 'blue'],
    'contacted' => ['label' => 'Contacted', 'public' => 'A consultant is on it', 'color' => 'violet'],
    'quoted' => ['label' => 'Quoted', 'public' => 'Quotation sent', 'color' => 'amber'],
    'confirmed' => ['label' => 'Confirmed', 'public' => 'Booking confirmed', 'color' => 'green'],
    'completed' => ['label' => 'Travelled', 'public' => 'Trip completed', 'color' => 'teal'],
    'cancelled' => ['label' => 'Cancelled', 'public' => 'Cancelled', 'color' => 'grey'],
    'lost' => ['label' => 'Lost', 'public' => 'Closed', 'color' => 'grey'],
];

const LEAD_TYPES = [
    'package' => 'Holiday package',
    'flight' => 'Flight',
    'holiday' => 'Custom holiday',
    'umrah' => 'Umrah / Hajj',
    'visa' => 'Visa',
    'hotel' => 'Hotel',
    'contact' => 'General enquiry',
];

function lead_status_label(string $s, bool $public = false): string
{
    return LEAD_STATUSES[$s][$public ? 'public' : 'label'] ?? ucfirst($s);
}

function lead_type_label(string $t): string
{
    return LEAD_TYPES[$t] ?? ucfirst($t);
}

/* ------------------------------------------------------------ spam guard */
function app_key(): string
{
    return (string) config('app_key', 'dev-key');
}

/** Hidden fields for public forms: a honeypot and a signed timestamp. */
function form_guard(): string
{
    $t = (string) time();
    $sig = substr(hash_hmac('sha256', $t, app_key()), 0, 16);
    return '<div class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="_ts" value="' . $t . '.' . $sig . '">';
}

function form_guard_problem(): ?string
{
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        return 'spam';
    }
    $parts = explode('.', (string) ($_POST['_ts'] ?? ''));
    if (count($parts) !== 2 || !hash_equals(substr(hash_hmac('sha256', $parts[0], app_key()), 0, 16), $parts[1])) {
        return 'Refresh the page and send the form again.';
    }
    $age = time() - (int) $parts[0];
    if ($age < 2) {
        return 'spam';
    }
    if ($age > 86400) {
        return 'This form was open for too long. Refresh the page and send it again.';
    }
    $since = date('Y-m-d H:i:s', time() - 600);
    if ((int) val("SELECT COUNT(*) FROM leads WHERE ip = ? AND channel = 'form' AND created_at > ?", [client_ip(), $since]) >= 6) {
        return 'You have sent several requests in a few minutes. Message us on WhatsApp and we will help right away.';
    }
    return null;
}

/* ---------------------------------------------------------------- create */
function lead_ref(): string
{
    do {
        $ref = 'OK' . date('ym') . '-' . random_code(5);
    } while (val('SELECT id FROM leads WHERE ref = ?', [$ref]));
    return $ref;
}

/**
 * Validate and save a lead from a public form. Returns [lead, errors].
 */
function lead_from_request(array $in, string $channel = 'form'): array
{
    // Accept a type key ("umrah") or its label ("Umrah / Hajj") from select boxes.
    $type = (string) ($in['type'] ?? '');
    if (!array_key_exists($type, LEAD_TYPES)) {
        $type = array_search($type, LEAD_TYPES, true) ?: 'contact';
    }
    $name = mb_substr(trim((string) ($in['name'] ?? '')), 0, 160);
    $email = mb_substr(mb_strtolower(trim((string) ($in['email'] ?? ''))), 0, 190);
    $phone = mb_substr(trim((string) ($in['phone'] ?? '')), 0, 60);
    $errors = [];
    if ($channel === 'form') {
        if ($name === '') $errors['name'] = 'Enter your name.';
        if ($email === '' && $phone === '') $errors['phone'] = 'Enter a phone/WhatsApp number or an email so we can reply.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if ($phone !== '' && !preg_match('/^[+\d][\d\s().-]{6,}$/', $phone)) $errors['phone'] = 'Enter a valid phone number, including the country code.';
    }
    $date = fn($k) => preg_match('/^\d{4}-\d{2}(-\d{2})?$/', (string) ($in[$k] ?? '')) ? (strlen($in[$k]) === 7 ? $in[$k] . '-01' : $in[$k]) : null;
    if ($date('travel_date') && $date('travel_date') < date('Y-m-01')) {
        $errors['travel_date'] = 'Choose a travel date in the future.';
    }
    if ($errors) {
        return [null, $errors];
    }

    $package = !empty($in['package_id']) ? row('SELECT id, title, price FROM packages WHERE id = ?', [(int) $in['package_id']]) : null;
    $details = [];
    foreach ((array) ($in['details'] ?? []) as $k => $v) {
        $k = mb_substr(trim((string) $k), 0, 60);
        $v = mb_substr(trim((string) $v), 0, 500);
        if ($k !== '' && $v !== '') {
            $details[$k] = $v;
        }
    }
    if ($channel === 'form' && is_converted() && !str_starts_with(current_path(), '/admin')) {
        $details['Prices viewed in'] = visitor_currency();
    }
    $subject = trim((string) ($in['subject'] ?? '')) ?: ($package ? $package['title'] : lead_type_label($type));

    $lead = [
        'ref' => lead_ref(),
        'type' => $type,
        'status' => 'new',
        'channel' => $channel,
        'name' => $name ?: null,
        'email' => $email ?: null,
        'phone' => $phone ?: null,
        'subject' => mb_substr($subject, 0, 255),
        'package_id' => $package['id'] ?? null,
        'travel_date' => $date('travel_date'),
        'return_date' => $date('return_date'),
        'adults' => max(1, min(99, (int) ($in['adults'] ?? 1))),
        'children' => max(0, min(99, (int) ($in['children'] ?? 0))),
        'infants' => max(0, min(20, (int) ($in['infants'] ?? 0))),
        'budget' => mb_substr(trim((string) ($in['budget'] ?? '')), 0, 80) ?: null,
        'message' => mb_substr(trim((string) ($in['message'] ?? '')), 0, 5000) ?: null,
        'details' => $details ? json_enc($details) : null,
        'currency' => (string) setting('currency', 'USD'),
        'source_page' => mb_substr((string) ($in['source_page'] ?? ($_SERVER['HTTP_REFERER'] ?? '')), 0, 255),
        'utm' => ($u = utm_from_session()) ? json_enc($u) : null,
        'ip' => client_ip(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => now(),
        'updated_at' => now(),
    ];
    $lead['id'] = insert('leads', $lead);
    insert('lead_notes', ['lead_id' => $lead['id'], 'user_id' => null, 'kind' => 'system', 'body' => $channel === 'whatsapp' ? 'Customer opened WhatsApp from the website.' : 'Request received from the website.', 'created_at' => now()]);

    if ($channel === 'form') {
        lead_notify($lead, $package);
    }
    return [$lead, []];
}

function utm_from_session(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    return (array) ($_SESSION['utm'] ?? []);
}

function lead_summary_rows(array $lead, ?array $package = null): array
{
    $pax = $lead['adults'] . ' adult' . ($lead['adults'] > 1 ? 's' : '')
        . ($lead['children'] ? ', ' . $lead['children'] . ' child' . ($lead['children'] > 1 ? 'ren' : '') : '')
        . ($lead['infants'] ? ', ' . $lead['infants'] . ' infant' . ($lead['infants'] > 1 ? 's' : '') : '');
    $rows = [
        'Reference' => $lead['ref'],
        'Request' => lead_type_label($lead['type']) . ($lead['subject'] ? ' — ' . $lead['subject'] : ''),
        'Name' => $lead['name'],
        'Phone' => $lead['phone'],
        'Email' => $lead['email'],
        'Travel date' => $lead['travel_date'] ? fmt_date($lead['travel_date'], 'D j M Y') : null,
        'Return date' => $lead['return_date'] ? fmt_date($lead['return_date'], 'D j M Y') : null,
        'Travellers' => $pax,
        'Budget' => $lead['budget'],
    ];
    foreach (json_dec($lead['details'] ?? '', []) as $k => $v) {
        $rows[$k] = $v;
    }
    $rows['Message'] = $lead['message'];
    return $rows;
}

function lead_notify(array $lead, ?array $package = null): void
{
    $rows = lead_summary_rows($lead, $package);
    $to = trim((string) setting('notify_email', '')) ?: trim((string) setting('email', ''));
    if ($to !== '') {
        $html = email_template('New ' . strtolower(lead_type_label($lead['type'])) . ' request', '<p>A new request just came in from the website.</p>' . email_table($rows), ['label' => 'Open in admin', 'url' => abs_url('/admin/leads/' . $lead['id'])]);
        $err = send_mail($to, 'New request ' . $lead['ref'] . ': ' . ($lead['subject'] ?: lead_type_label($lead['type'])), $html, $lead['email']);
        if ($err) {
            error_log('Lead notification failed: ' . $err);
        }
    }
    if ($lead['email'] && setting('customer_emails', '1') === '1') {
        $first = explode(' ', (string) $lead['name'])[0] ?: 'there';
        $body = '<p>Hi ' . e($first) . ',</p><p>Thank you for contacting ' . e(setting('site_name')) . '. A travel consultant will get back to you shortly, usually within the hour during office hours (' . e(setting('office_hours')) . ').</p>'
            . '<p>Your reference number is <strong style="color:#FF6600">' . e($lead['ref']) . '</strong>. Use it to follow your request online or when you message us.</p>' . email_table($rows);
        $err = send_mail($lead['email'], 'We received your request — ' . $lead['ref'], email_template('We have your request', $body, ['label' => 'Track your request', 'url' => abs_url('/track?ref=' . rawurlencode($lead['ref']))]), setting('email'));
        if ($err) {
            error_log('Customer confirmation failed: ' . $err);
        }
    }
}

/** WhatsApp message that carries the reference, so agents can match chats to leads. */
function lead_whatsapp_message(array $lead): string
{
    $lines = ['Hello ' . setting('site_name') . '! I just sent a request on your website.', 'Reference: ' . $lead['ref']];
    if ($lead['subject']) $lines[] = 'Request: ' . $lead['subject'];
    if ($lead['travel_date']) $lines[] = 'Travel date: ' . fmt_date($lead['travel_date'], 'j M Y');
    $lines[] = 'Travellers: ' . $lead['adults'] . ' adult(s)' . ($lead['children'] ? ', ' . $lead['children'] . ' child(ren)' : '');
    if ($lead['name']) $lines[] = 'Name: ' . $lead['name'];
    return implode("\n", $lines);
}
