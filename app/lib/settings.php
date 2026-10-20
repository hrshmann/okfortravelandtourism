<?php
declare(strict_types=1);

function settings_defaults(): array
{
    return [
        // Business
        'site_name' => 'OK Travel & Tourism',
        'legal_name' => 'OK For Travel and Tourism Co.',
        'tagline' => 'Every journey is "OK"',
        'site_url' => '',
        'license_no' => '',
        'currency' => 'USD',            // base currency: prices are entered in this
        'currency_symbol' => '$',       // legacy; symbols now live in the currencies table
        'currencies' => '',             // JSON: code => {enabled, rate (per USD), symbol}
        'currency_auto_detect' => '1',  // pick the visitor's currency from their browser region
        'fx_auto' => '1',               // refresh rates daily
        'fx_margin' => '0',             // % added to converted prices
        'fx_updated_at' => '',
        'fx_attempt_at' => '',
        'fx_error' => '',
        'timezone' => 'Asia/Dubai',
        'logo' => 'assets/img/logo.png',
        'logo_light' => 'assets/img/logo-light.png',
        // Contact
        'phone' => '+971 50 000 0000',
        'phone_alt' => '',
        'whatsapp' => '971500000000',
        'email' => 'info@okfortravel.com',
        'address' => 'Office 101, Business Bay, Dubai, UAE',
        'office_hours' => 'Sat – Thu, 9:00 – 21:00',
        'map_query' => 'Business Bay, Dubai',
        // Social
        'facebook' => '', 'instagram' => '', 'tiktok' => '', 'youtube' => '', 'x' => '', 'linkedin' => '',
        // Homepage
        'hero_title' => 'Every journey is OK.',
        'hero_subtitle' => 'Flights, holidays, Umrah and visas planned by people who answer on WhatsApp — and stay with you until you are home.',
        'hero_image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2000&q=80',
        'announcement' => '',
        'stats' => json_enc([
            ['value' => '15,000+', 'label' => 'travellers looked after'],
            ['value' => '120+', 'label' => 'destinations booked'],
            ['value' => '12 yrs', 'label' => 'in business'],
            ['value' => '< 10 min', 'label' => 'typical WhatsApp reply'],
        ]),
        // SEO & tracking
        'meta_title' => 'OK Travel & Tourism — Flights, Holidays, Umrah & Visas',
        'meta_description' => 'Book flights, holiday packages, Umrah and visas with OK Travel & Tourism. Real consultants, transparent prices and WhatsApp support from enquiry to return.',
        'og_image' => 'assets/img/og-default.jpg',
        'ga4_id' => '',
        'meta_pixel_id' => '',
        'gsc_verification' => '',
        'head_code' => '',
        // Email
        'notify_email' => '',
        'mail_from' => '',
        'mail_from_name' => 'OK Travel & Tourism',
        'smtp_host' => '',
        'smtp_port' => '465',
        'smtp_secure' => 'ssl',
        'smtp_user' => '',
        'smtp_pass' => '',
        'customer_emails' => '1',
        // Flights
        'flight_provider' => 'auto',
        'duffel_token' => '',
        'travelpayouts_token' => '',
        'travelpayouts_marker' => '',
        // System
        'force_https' => '0',
        'maintenance' => '0',
        'maintenance_message' => 'We are updating our website and will be back shortly. For bookings, message us on WhatsApp.',
        'schema_version' => (string) SCHEMA_VERSION,
    ];
}

/** Keys that must never be sent to the browser. */
function settings_secret_keys(): array
{
    return ['smtp_pass', 'duffel_token', 'travelpayouts_token', 'head_code'];
}

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }
    $cache = settings_defaults();
    if (is_installed() || ($GLOBALS['_pdo'] ?? null)) {
        try {
            foreach (rows('SELECT name, value FROM settings') as $r) {
                $cache[$r['name']] = $r['value'];
            }
        } catch (Throwable $e) {
            // Database not reachable yet: fall back to defaults so error pages still render.
        }
    }
    return $cache;
}

function setting(string $key, $default = null)
{
    $all = settings_all();
    $v = $all[$key] ?? null;
    return ($v === null || $v === '') && $default !== null ? $default : $v;
}

function setting_json(string $key, array $default = []): array
{
    return json_dec(setting($key, ''), $default);
}

function settings_save(array $values): void
{
    foreach ($values as $name => $value) {
        $value = is_array($value) ? json_enc($value) : (string) $value;
        if (val('SELECT COUNT(*) FROM settings WHERE name = ?', [$name])) {
            update('settings', ['value' => $value], 'name = ?', [$name]);
        } else {
            insert('settings', ['name' => $name, 'value' => $value]);
        }
    }
    settings_all(true);
}

function social_links(): array
{
    $out = [];
    foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X', 'linkedin' => 'LinkedIn'] as $k => $label) {
        $u = trim((string) setting($k, ''));
        if ($u !== '' && $u !== '#') {
            $out[$k] = ['label' => $label, 'url' => $u];
        }
    }
    return $out;
}
