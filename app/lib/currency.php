<?php
declare(strict_types=1);

/**
 * Multi-currency display.
 *
 * Prices are entered once in the base currency (Settings → Currencies). Visitors can view them in any
 * enabled currency. Rates are stored as "units per 1 USD" so the base currency can change at any time
 * without the rates going stale. Converted prices are approximate; quotations are issued by staff.
 */
const CURRENCIES = [
    // code => [name, symbol, default units per 1 USD, region codes used for auto-detection]
    'USD' => ['US dollar', '$', 1, ['US']],
    'AED' => ['UAE dirham', 'AED', 3.6725, ['AE']],
    'EUR' => ['Euro', '€', 0.92, ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'IE', 'PT', 'FI', 'GR']],
    'GBP' => ['British pound', '£', 0.78, ['GB']],
    'INR' => ['Indian rupee', '₹', 84.0, ['IN']],
    'SAR' => ['Saudi riyal', 'SAR', 3.75, ['SA']],
    'PKR' => ['Pakistani rupee', 'Rs', 280.0, ['PK']],
    'QAR' => ['Qatari riyal', 'QAR', 3.64, ['QA']],
    'OMR' => ['Omani rial', 'OMR', 0.385, ['OM']],
    'KWD' => ['Kuwaiti dinar', 'KWD', 0.307, ['KW']],
    'BHD' => ['Bahraini dinar', 'BHD', 0.376, ['BH']],
    'BDT' => ['Bangladeshi taka', 'Tk', 120.0, ['BD']],
    'LKR' => ['Sri Lankan rupee', 'Rs', 300.0, ['LK']],
    'MYR' => ['Malaysian ringgit', 'RM', 4.4, ['MY']],
    'TRY' => ['Turkish lira', '₺', 34.0, ['TR']],
];

const CURRENCY_COOKIE = 'ok_currency';
/** South Asian grouping: 1,22,000 rather than 122,000. */
const LAKH_CURRENCIES = ['INR', 'PKR', 'BDT'];

function lakh_format(float $amount): string
{
    $n = (string) (int) round($amount);
    if (strlen($n) <= 3) {
        return $n;
    }
    $last3 = substr($n, -3);
    $rest = substr($n, 0, -3);
    return preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
}

function base_currency(): string
{
    $c = strtoupper((string) setting('currency', 'USD'));
    return isset(CURRENCIES[$c]) ? $c : 'USD';
}

/** Merged config: code => [name, symbol, rate (per USD), enabled]. */
function currency_table(): array
{
    static $table = null;
    if ($table !== null) {
        return $table;
    }
    $saved = setting_json('currencies');
    $base = base_currency();
    $table = [];
    foreach (CURRENCIES as $code => [$name, $symbol, $rate]) {
        $s = $saved[$code] ?? [];
        $table[$code] = [
            'code' => $code,
            'name' => $name,
            'symbol' => (string) (($s['symbol'] ?? '') !== '' ? $s['symbol'] : $symbol),
            'rate' => (float) ($s['rate'] ?? 0) > 0 ? (float) $s['rate'] : (float) $rate,
            'enabled' => $code === $base || !empty($s['enabled']) || ($saved === [] && in_array($code, ['USD', 'AED', 'EUR', 'GBP', 'INR', 'SAR', 'PKR'], true)),
        ];
    }
    return $table;
}

function enabled_currencies(): array
{
    return array_filter(currency_table(), fn($c) => $c['enabled']);
}

/** The currency this visitor sees: their choice, a guess from their browser language, or the base. */
function visitor_currency(): string
{
    static $cur = null;
    if ($cur !== null) {
        return $cur;
    }
    $enabled = enabled_currencies();
    $chosen = strtoupper((string) ($_COOKIE[CURRENCY_COOKIE] ?? ''));
    if (isset($enabled[$chosen])) {
        return $cur = $chosen;
    }
    if (setting('currency_auto_detect', '1') === '1' && !is_bot()) {
        // "en-IN,en;q=0.9" -> IN. Only the first preference is trusted.
        if (preg_match('/^[a-z]{2,3}-([A-Z]{2})/i', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), $m)) {
            $region = strtoupper($m[1]);
            foreach ($enabled as $code => $c) {
                if (in_array($region, CURRENCIES[$code][3], true)) {
                    return $cur = $code;
                }
            }
        }
    }
    return $cur = base_currency();
}

function convert_amount(float $amount, string $from, string $to): float
{
    if ($from === $to) {
        return $amount;
    }
    $t = currency_table();
    if (!isset($t[$from], $t[$to])) {
        return $amount;
    }
    return $amount / $t[$from]['rate'] * $t[$to]['rate'];
}

/** Round converted prices up to three significant figures: 74,617 -> 74,700; 6,941 -> 6,950. */
function neat_price(float $v): float
{
    if ($v < 100) {
        return ceil($v);
    }
    $step = 10 ** (floor(log10($v)) - 2);
    return ceil($v / $step) * $step;
}

/** A base-currency amount converted to the visitor's currency (as a number). */
function price_value($amount, ?string $to = null): float
{
    $to = $to ?? visitor_currency();
    $base = base_currency();
    $v = (float) $amount;
    if ($to === $base) {
        return $v;
    }
    $v = convert_amount($v, $base, $to) * (1 + max(0, (float) setting('fx_margin', '0')) / 100);
    return neat_price($v);
}

function format_currency(float $amount, string $code): string
{
    $sym = currency_table()[$code]['symbol'] ?? $code;
    $decimals = in_array($code, ['KWD', 'BHD', 'OMR'], true) && $amount < 100 ? 2 : 0;
    $num = in_array($code, LAKH_CURRENCIES, true) ? lakh_format($amount) : number_format($amount, $decimals);
    return preg_match('/^[A-Za-z.]+$/', $sym) ? $sym . ' ' . $num : $sym . $num;
}

/** Public price: a base amount shown in the visitor's currency. Admin screens use money() instead. */
function price($amount): string
{
    return format_currency(price_value($amount), visitor_currency());
}

function is_converted(): bool
{
    return visitor_currency() !== base_currency();
}

/* ------------------------------------------------------------ live rates */
/** Fetch fresh rates (free, no key: open.er-api.com). Returns null on success or an error message. */
function fx_refresh(): ?string
{
    try {
        [$code, $body] = http_request('https://open.er-api.com/v6/latest/USD', 'GET', null, ['Accept: application/json'], 8);
        $data = json_dec($body, []);
        if ($code !== 200 || ($data['result'] ?? '') !== 'success' || empty($data['rates'])) {
            throw new RuntimeException('Rate service returned HTTP ' . $code);
        }
        $saved = setting_json('currencies');
        foreach (array_keys(CURRENCIES) as $c) {
            if (!empty($data['rates'][$c])) {
                $saved[$c] = array_merge(['enabled' => currency_table()[$c]['enabled'] ? 1 : 0], $saved[$c] ?? [], ['rate' => round((float) $data['rates'][$c], 6)]);
            }
        }
        settings_save(['currencies' => json_enc($saved), 'fx_updated_at' => now(), 'fx_error' => '']);
        return null;
    } catch (Throwable $e) {
        settings_save(['fx_error' => mb_substr($e->getMessage(), 0, 200)]);
        error_log('FX refresh failed: ' . $e->getMessage());
        return 'Could not update rates: ' . $e->getMessage();
    }
}

/**
 * Daily automatic refresh, run after the page has been sent so no visitor waits for it.
 * The attempt time is saved first so parallel requests do not all fetch at once.
 */
function fx_schedule_refresh(): void
{
    if (setting('fx_auto', '1') !== '1') {
        return;
    }
    $last = strtotime((string) setting('fx_attempt_at', '')) ?: 0;
    if (time() - $last < 86400) {
        return;
    }
    settings_save(['fx_attempt_at' => now()]);
    register_shutdown_function(function () {
        if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        fx_refresh();
    });
}

/** Data the browser needs to format and convert prices (flight fares come in their own currency). */
function currency_js(): array
{
    $cur = visitor_currency();
    return [
        'currency' => $cur,
        'base' => base_currency(),
        'symbol' => currency_table()[$cur]['symbol'],
        'margin' => (float) setting('fx_margin', '0'),
        'rates' => array_map(fn($c) => $c['rate'], currency_table()),
    ];
}
