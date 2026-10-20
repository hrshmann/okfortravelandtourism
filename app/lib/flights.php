<?php
declare(strict_types=1);

const CABINS = ['economy' => 'Economy', 'premium_economy' => 'Premium economy', 'business' => 'Business', 'first' => 'First'];

function flight_provider(): string
{
    $p = (string) setting('flight_provider', 'auto');
    if (in_array($p, ['duffel', 'travelpayouts', 'demo'], true)) {
        return $p;
    }
    if (setting('duffel_token', '') !== '') return 'duffel';
    if (setting('travelpayouts_token', '') !== '') return 'travelpayouts';
    return 'demo';
}

function airports_all(): array
{
    static $list = null;
    if ($list === null) {
        $list = json_dec((string) @file_get_contents(APP . '/data/airports.json'), []);
    }
    return $list;
}

function airports_search(string $q, int $limit = 12): array
{
    $q = mb_strtolower(trim($q));
    $all = airports_all();
    if ($q === '') {
        return array_slice($all, 0, $limit);
    }
    $hits = array_filter($all, function ($a) use ($q) {
        foreach (['code', 'city', 'name', 'country'] as $k) {
            if (str_contains(mb_strtolower($a[$k] ?? ''), $q)) {
                return true;
            }
        }
        return false;
    });
    usort($hits, function ($a, $b) use ($q) {
        $score = fn($x) => (mb_strtolower($x['code']) === $q ? 0 : 2) + (str_starts_with(mb_strtolower($x['city']), $q) ? 0 : 1);
        return $score($a) <=> $score($b);
    });
    return array_slice(array_values($hits), 0, $limit);
}

function airport(string $code): ?array
{
    foreach (airports_all() as $a) {
        if ($a['code'] === strtoupper($code)) {
            return $a;
        }
    }
    return null;
}

function http_request(string $url, string $method = 'GET', ?string $body = null, array $headers = [], int $timeout = 30): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            throw new RuntimeException('Network error: ' . $err);
        }
        return [$code, (string) $resp];
    }
    $ctx = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $body ?? '', 'timeout' => $timeout, 'ignore_errors' => true]]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        throw new RuntimeException('Network error');
    }
    preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0] ?? '', $m);
    return [(int) ($m[1] ?? 0), $resp];
}

function iso_minutes(?string $d): ?int
{
    if (!$d || !preg_match('/P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?/', $d, $m)) {
        return null;
    }
    return ((int) ($m[1] ?? 0)) * 1440 + ((int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
}

function search_duffel(array $q): array
{
    $slices = [['origin' => $q['from'], 'destination' => $q['to'], 'departure_date' => $q['date']]];
    if ($q['return']) {
        $slices[] = ['origin' => $q['to'], 'destination' => $q['from'], 'departure_date' => $q['return']];
    }
    $passengers = array_merge(array_fill(0, $q['adults'], ['type' => 'adult']), array_fill(0, $q['children'], ['age' => 8]));
    $body = json_enc(['data' => ['slices' => $slices, 'passengers' => $passengers, 'cabin_class' => $q['cabin']]]);
    [$code, $resp] = http_request('https://api.duffel.com/air/offer_requests?return_offers=true&supplier_timeout=20000', 'POST', $body, [
        'Authorization: Bearer ' . setting('duffel_token'),
        'Duffel-Version: v2',
        'Content-Type: application/json',
        'Accept: application/json',
    ], 40);
    if ($code >= 400) {
        throw new RuntimeException("Duffel returned HTTP $code: " . substr($resp, 0, 200));
    }
    $payload = json_dec($resp, []);
    $out = [];
    foreach (array_slice($payload['data']['offers'] ?? [], 0, 40) as $offer) {
        $s = $offer['slices'][0];
        $segs = $s['segments'];
        $carrier = $segs[0]['marketing_carrier'] ?? [];
        $out[] = [
            'id' => $offer['id'],
            'airline' => $offer['owner']['name'] ?? ($carrier['name'] ?? ''),
            'airlineCode' => $offer['owner']['iata_code'] ?? ($carrier['iata_code'] ?? ''),
            'flightNumber' => ($carrier['iata_code'] ?? '') . ($segs[0]['marketing_carrier_flight_number'] ?? ''),
            'from' => $segs[0]['origin']['iata_code'],
            'to' => end($segs)['destination']['iata_code'],
            'departAt' => $segs[0]['departing_at'],
            'arriveAt' => end($segs)['arriving_at'],
            'durationMin' => iso_minutes($s['duration'] ?? null),
            'stops' => count($segs) - 1,
            'price' => (float) $offer['total_amount'],
            'currency' => $offer['total_currency'],
            'cabin' => $q['cabin'],
            'returnTrip' => count($offer['slices']) > 1,
            'bookingUrl' => null,
        ];
    }
    return $out;
}

function search_travelpayouts(array $q): array
{
    $params = [
        'origin' => $q['from'], 'destination' => $q['to'], 'departure_at' => $q['date'],
        'currency' => strtolower($q['currency']), 'sorting' => 'price', 'direct' => 'false', 'limit' => 30,
        'one_way' => $q['return'] ? 'false' : 'true', 'token' => setting('travelpayouts_token'),
    ];
    if ($q['return']) {
        $params['return_at'] = $q['return'];
    }
    [$code, $resp] = http_request('https://api.travelpayouts.com/aviasales/v3/prices_for_dates?' . http_build_query($params), 'GET', null, ['Accept: application/json']);
    if ($code >= 400) {
        throw new RuntimeException("Travelpayouts returned HTTP $code");
    }
    $payload = json_dec($resp, []);
    $marker = (string) setting('travelpayouts_marker', '');
    $out = [];
    foreach ($payload['data'] ?? [] as $i => $item) {
        $depart = $item['departure_at'] ?? '';
        $dur = (int) ($item['duration_to'] ?? $item['duration'] ?? 0);
        $arrive = $depart && $dur ? date('c', strtotime($depart) + $dur * 60) : '';
        $link = $item['link'] ?? null;
        $booking = null;
        if ($link) {
            $booking = 'https://www.aviasales.com' . $link;
            if ($marker !== '') {
                $booking .= (str_contains($booking, '?') ? '&' : '?') . 'marker=' . rawurlencode($marker);
            }
        }
        $out[] = [
            'id' => 'tp-' . $i,
            'airline' => $item['airline'] ?? '',
            'airlineCode' => $item['airline'] ?? '',
            'flightNumber' => ($item['airline'] ?? '') . ($item['flight_number'] ?? ''),
            'from' => $item['origin_airport'] ?? $item['origin'] ?? $q['from'],
            'to' => $item['destination_airport'] ?? $item['destination'] ?? $q['to'],
            'departAt' => $depart,
            'arriveAt' => $arrive,
            'durationMin' => $dur ?: null,
            'stops' => (int) ($item['transfers'] ?? 0),
            'price' => (float) ($item['price'] ?? 0) * ($q['adults'] + $q['children']),
            'currency' => strtoupper($payload['currency'] ?? $q['currency']),
            'cabin' => 'economy',
            'returnTrip' => (bool) $q['return'],
            'bookingUrl' => $booking,
        ];
    }
    return $out;
}

const DEMO_AIRLINES = [
    ['Emirates', 'EK'], ['Qatar Airways', 'QR'], ['Etihad Airways', 'EY'], ['Turkish Airlines', 'TK'],
    ['flydubai', 'FZ'], ['Saudia', 'SV'], ['PIA', 'PK'], ['Air Arabia', 'G9'], ['Lufthansa', 'LH'],
    ['British Airways', 'BA'], ['Singapore Airlines', 'SQ'], ['Oman Air', 'WY'],
];

/** Deterministic sample fares so the engine works before an API key is added. */
function search_demo(array $q): array
{
    mt_srand(crc32($q['from'] . $q['to'] . $q['date'] . $q['cabin']));
    $codes = array_sum(array_map('ord', str_split($q['from'] . $q['to'])));
    $base = 180 + ($codes % 9) * 85;
    $mult = ['economy' => 1, 'premium_economy' => 1.7, 'business' => 3.4, 'first' => 5.5][$q['cabin']];
    $pax = $q['adults'] + $q['children'] * 0.75;
    $airlines = DEMO_AIRLINES;
    for ($i = count($airlines) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        [$airlines[$i], $airlines[$j]] = [$airlines[$j], $airlines[$i]];
    }
    $day = strtotime($q['date'] . ' 00:00:00');
    $out = [];
    foreach (array_slice($airlines, 0, 8) as $i => [$name, $code]) {
        $dep = $day + mt_rand(0, 22) * 3600 + [0, 10, 25, 40, 55][mt_rand(0, 4)] * 60;
        $stops = [0, 0, 1, 1, 2][mt_rand(0, 4)];
        $dur = mt_rand(150, 420) + $stops * mt_rand(70, 200);
        $price = ($base + mt_rand(-60, 260) - $stops * 35) * $mult * $pax * ($q['return'] ? 1.8 : 1);
        $out[] = [
            'id' => 'demo-' . $i,
            'airline' => $name,
            'airlineCode' => $code,
            'flightNumber' => $code . mt_rand(100, 999),
            'from' => $q['from'],
            'to' => $q['to'],
            'departAt' => date('Y-m-d\TH:i:s', $dep),
            'arriveAt' => date('Y-m-d\TH:i:s', $dep + $dur * 60),
            'durationMin' => $dur,
            'stops' => $stops,
            'price' => round(max($price, 79), 2),
            'currency' => 'USD',
            'cabin' => $q['cabin'],
            'returnTrip' => (bool) $q['return'],
            'bookingUrl' => null,
        ];
    }
    mt_srand();
    return $out;
}

function search_flights(array $in): array
{
    $q = [
        'from' => strtoupper(substr(trim((string) ($in['from'] ?? '')), 0, 3)),
        'to' => strtoupper(substr(trim((string) ($in['to'] ?? '')), 0, 3)),
        'date' => trim((string) ($in['date'] ?? '')),
        'return' => trim((string) ($in['return'] ?? '')),
        'adults' => max(1, min(9, (int) ($in['adults'] ?? 1))),
        'children' => max(0, min(8, (int) ($in['children'] ?? 0))),
        'cabin' => strtolower((string) ($in['cabin'] ?? 'economy')),
        'currency' => strtoupper((string) setting('currency', 'USD')),
    ];
    if (!preg_match('/^[A-Z]{3}$/', $q['from']) || !preg_match('/^[A-Z]{3}$/', $q['to'])) {
        throw new InvalidArgumentException('Choose a departure and a destination airport.');
    }
    if ($q['from'] === $q['to']) {
        throw new InvalidArgumentException('Departure and destination must be different.');
    }
    $valid = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
    if (!$valid($q['date']) || ($q['return'] !== '' && !$valid($q['return']))) {
        throw new InvalidArgumentException('Choose a valid travel date.');
    }
    if (strtotime($q['date']) < strtotime('today')) {
        throw new InvalidArgumentException('The departure date is in the past.');
    }
    if ($q['return'] !== '' && $q['return'] < $q['date']) {
        throw new InvalidArgumentException('The return date must be after the departure date.');
    }
    if (!isset(CABINS[$q['cabin']])) {
        $q['cabin'] = 'economy';
    }

    $provider = flight_provider();
    $started = microtime(true);
    $note = null;
    try {
        $results = match ($provider) {
            'duffel' => search_duffel($q),
            'travelpayouts' => search_travelpayouts($q),
            default => search_demo($q),
        };
    } catch (Throwable $e) {
        error_log("[flights] $provider failed: " . $e->getMessage());
        $note = 'Live fares are unavailable right now, so these are sample fares. Message us for confirmed prices.';
        $provider = 'demo';
        $results = search_demo($q);
    }
    usort($results, fn($a, $b) => $a['price'] <=> $b['price']);
    return [
        'provider' => $provider,
        'live' => $provider !== 'demo',
        'note' => $note,
        'query' => $q,
        'tookMs' => (int) ((microtime(true) - $started) * 1000),
        'results' => $results,
    ];
}
