<?php
declare(strict_types=1);

/* Public JSON endpoints used by the website's JavaScript. */

route('GET', '/api/airports', function () {
    header('Cache-Control: public, max-age=86400');
    json_out(airports_search((string) input('q')));
});

route('GET', '/api/flights', function () {
    try {
        json_out(search_flights($_GET));
    } catch (InvalidArgumentException $e) {
        json_out(['error' => $e->getMessage()], 400);
    }
});

/** Logs a WhatsApp click so the sales team can see what people asked about. */
route('POST', '/api/whatsapp', function () {
    $in = request_json();
    if (is_bot()) {
        json_out(['ok' => true]);
    }
    $since = date('Y-m-d H:i:s', time() - 3600);
    if ((int) val("SELECT COUNT(*) FROM leads WHERE ip = ? AND channel = 'whatsapp' AND created_at > ?", [client_ip(), $since]) >= 20) {
        json_out(['ok' => true]);
    }
    $type = array_key_exists($in['type'] ?? '', LEAD_TYPES) ? $in['type'] : 'contact';
    lead_from_request([
        'type' => $type,
        'subject' => mb_substr((string) ($in['subject'] ?? ''), 0, 255),
        'package_id' => (int) ($in['package_id'] ?? 0) ?: null,
        'message' => mb_substr((string) ($in['message'] ?? ''), 0, 2000),
        'source_page' => mb_substr((string) ($in['page'] ?? ''), 0, 255),
    ], 'whatsapp');
    json_out(['ok' => true]);
});
