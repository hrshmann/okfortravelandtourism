<?php
$GLOBALS['_title'] = 'Settings';
$s = fn($k) => (string) setting($k, '');
$txt = function (string $k, string $label, string $help = '', string $type = 'text', int $col = 2, string $ph = '') use ($s) {
    return '<div class="fld c' . $col . '"><label for="s_' . $k . '">' . e($label) . '</label><input class="inp" id="s_' . $k . '" name="' . $k . '" type="' . $type . '" value="' . e($s($k)) . '" placeholder="' . e($ph) . '">' . ($help ? '<span class="help">' . $help . '</span>' : '') . '</div>';
};
$area = function (string $k, string $label, string $help = '', int $rows = 3) use ($s) {
    return '<div class="fld c4"><label for="s_' . $k . '">' . e($label) . '</label><textarea class="inp" id="s_' . $k . '" name="' . $k . '" rows="' . $rows . '">' . e($s($k)) . '</textarea>' . ($help ? '<span class="help">' . $help . '</span>' : '') . '</div>';
};
$secret = function (string $k, string $label, string $help = '') use ($s) {
    $has = $s($k) !== '';
    return '<div class="fld c2"><label for="s_' . $k . '">' . e($label) . '</label><input class="inp" id="s_' . $k . '" name="' . $k . '" type="password" autocomplete="new-password" placeholder="' . ($has ? '•••••••• saved — type to replace' : '') . '">'
        . '<span class="help">' . $help . ($has ? ' <label style="display:inline-flex;gap:4px;align-items:center;margin-left:6px"><input type="checkbox" name="clear_' . $k . '" value="1"> remove</label>' : '') . '</span></div>';
};
$toggle = fn(string $k, string $label, string $help = '') => '<div class="fld c4"><input type="hidden" name="' . $k . '" value="0"><label class="switch"><input type="checkbox" name="' . $k . '" value="1"' . (setting($k) === '1' ? ' checked' : '') . '><i></i>' . e($label) . '</label>' . ($help ? '<span class="help">' . $help . '</span>' : '') . '</div>';
$stats = setting_json('stats');
while (count($stats) < 4) $stats[] = ['value' => '', 'label' => ''];
$tabs = ['general' => 'Business', 'currencies' => 'Currencies', 'contact' => 'Contact', 'social' => 'Social', 'homepage' => 'Homepage', 'seo' => 'SEO & tracking', 'email' => 'Email', 'flights' => 'Flight search', 'system' => 'System'];
?>
<div class="page-top"><div><h1>Settings</h1><p>Business details, homepage content, email and integrations.</p></div></div>

<form method="post" action="<?= e(url('/admin/settings')) ?>" data-dirty data-settings>
  <?= csrf_field() ?>
  <input type="hidden" name="_tab" value="">
  <div class="tabs" role="tablist">
    <?php foreach ($tabs as $k => $l): ?><button type="button" role="tab" id="tab-<?= $k ?>" aria-controls="panel-<?= $k ?>" aria-selected="<?= $k === 'general' ? 'true' : 'false' ?>" data-tab="<?= $k ?>"><?= e($l) ?></button><?php endforeach; ?>
  </div>

  <section class="card tab-panel" id="panel-general" role="tabpanel" aria-labelledby="tab-general">
    <div class="form-grid">
      <?= $txt('site_name', 'Website name', 'Shown in the browser tab, emails and search results.') ?>
      <?= $txt('legal_name', 'Registered company name', 'Used on quotations, invoices and the footer.') ?>
      <?= $txt('tagline', 'Tagline') ?>
      <?= $txt('license_no', 'Trade / tourism licence number', 'Shown in the footer and on invoices. Builds trust.') ?>
      <div class="fld c2"><label for="s_timezone">Time zone</label><select class="inp" id="s_timezone" name="timezone"><?php foreach (timezone_identifiers_list() as $tz): ?><option<?= $s('timezone') === $tz ? ' selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?></select></div>
      <div class="form-section"><h3>Logo</h3><p>Upload a new logo in the Media library, then choose it here. Use a transparent PNG.</p></div>
      <?php foreach (['logo' => 'Logo for light backgrounds', 'logo_light' => 'Logo for dark backgrounds'] as $k => $l): ?>
        <div class="fld c2"><label for="s_<?= $k ?>"><?= e($l) ?></label>
          <div class="img-field" data-image-field><img class="preview" src="<?= e(img($s($k))) ?>" alt="" style="object-fit:contain;<?= $k === 'logo_light' ? 'background:#000C64' : 'background:#fff' ?>"><div class="controls"><input class="inp" id="s_<?= $k ?>" name="<?= $k ?>" value="<?= e($s($k)) ?>"><div class="row"><button type="button" class="btn btn-ghost btn-sm" data-pick-image><?= icon('image') ?>Choose image</button></div></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card tab-panel" id="panel-currencies" role="tabpanel" aria-labelledby="tab-currencies" hidden>
    <?php $table = currency_table(); $base = base_currency(); $baseRate = $table[$base]['rate']; ?>
    <div class="form-grid">
      <div class="fld c2"><label for="s_currency">Main currency</label>
        <select class="inp" id="s_currency" name="currency"><?php foreach ($table as $c): ?><option value="<?= e($c['code']) ?>"<?= $c['code'] === $base ? ' selected' : '' ?>><?= e($c['code'] . ' — ' . $c['name']) ?></option><?php endforeach; ?></select>
        <span class="help">Enter all package and visa prices in this currency. Visitors can switch to the currencies ticked below; prices are converted automatically.</span></div>
      <div class="fld c2"><label for="s_fx_margin">Safety margin on converted prices (%)</label><input class="inp" id="s_fx_margin" name="fx_margin" type="number" min="0" max="20" step="0.1" value="<?= e($s('fx_margin') ?: '0') ?>"><span class="help">Optional. Adds a small buffer for exchange-rate movement, e.g. 2. Prices in your main currency are never changed.</span></div>
      <?= $toggle('currency_auto_detect', 'Show visitors their local currency automatically', 'Based on their browser’s country (for example India → INR, UAE → AED). They can always change it in the header.') ?>
      <?= $toggle('fx_auto', 'Update exchange rates automatically every day', 'Uses free rates from open.er-api.com. Turn off to keep your own rates below.') ?>
      <div class="fld c4">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <button type="button" class="btn btn-ghost btn-sm" data-fx-refresh><?= icon('refresh') ?>Update rates now</button>
          <span class="help">Last updated: <strong><?= $s('fx_updated_at') ? e(fmt_date($s('fx_updated_at'), 'j M Y, H:i')) : 'never — using built-in rates' ?></strong><?= $s('fx_error') ? ' · <span style="color:var(--red)">' . e($s('fx_error')) . '</span>' : '' ?></span>
        </div>
      </div>
      <div class="fld c4">
        <div class="table-wrap">
          <table class="t">
            <thead><tr><th>Show</th><th>Currency</th><th>Symbol</th><th>Rate: 1 <?= e($base) ?> =</th><th class="num">Example: <?= e(money(1000)) ?> becomes</th></tr></thead>
            <tbody>
            <?php foreach ($table as $c): $rel = $c['rate'] / $baseRate; ?>
              <tr>
                <td><label class="switch toggle-cell"><input type="checkbox" name="cur_enabled[<?= e($c['code']) ?>]" value="1"<?= $c['enabled'] ? ' checked' : '' ?><?= $c['code'] === $base ? ' disabled' : '' ?> aria-label="Offer <?= e($c['name']) ?>"><i></i></label></td>
                <td><strong><?= e($c['code']) ?></strong><span class="sub"><?= e($c['name']) ?><?= $c['code'] === $base ? ' · main currency' : '' ?></span></td>
                <td style="width:110px"><input class="inp" name="cur_symbol[<?= e($c['code']) ?>]" value="<?= e($c['symbol']) ?>" maxlength="5" aria-label="<?= e($c['code']) ?> symbol"></td>
                <td style="width:180px"><input class="inp" name="cur_rate[<?= e($c['code']) ?>]" value="<?= e(rtrim(rtrim(number_format($rel, 6, '.', ''), '0'), '.')) ?>" inputmode="decimal" aria-label="1 <?= e($base) ?> in <?= e($c['code']) ?>"<?= $c['code'] === $base ? ' readonly' : '' ?>></td>
                <td class="num"><?= e(format_currency(price_value(1000, $c['code']), $c['code'])) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <span class="help">Converted prices are rounded up to a tidy figure (e.g. ₹74,617 → ₹74,700) and marked as approximate on the website.</span>
      </div>
    </div>
  </section>

  <section class="card tab-panel" id="panel-contact" role="tabpanel" aria-labelledby="tab-contact" hidden>
    <div class="form-grid">
      <div class="fld c2"><label for="s_whatsapp">WhatsApp number</label><input class="inp" id="s_whatsapp" name="whatsapp" value="<?= e($s('whatsapp')) ?>" inputmode="tel" placeholder="971501234567"><span class="help">International format, digits only — no + or spaces. Every WhatsApp button on the site uses this. <a href="#" data-test-wa>Test it</a></span></div>
      <?= $txt('phone', 'Phone number (as displayed)', '', 'text', 2, '+971 50 123 4567') ?>
      <?= $txt('phone_alt', 'Second phone (optional)') ?>
      <?= $txt('email', 'Public email', '', 'email') ?>
      <?= $txt('address', 'Office address', '', 'text', 4) ?>
      <?= $txt('office_hours', 'Office hours', '', 'text', 2, 'Sat – Thu, 9:00 – 21:00') ?>
      <?= $txt('map_query', 'Map location', 'What to search on Google Maps for the Contact page map, e.g. your business name and area. Leave empty to hide the map.') ?>
    </div>
  </section>

  <section class="card tab-panel" id="panel-social" role="tabpanel" aria-labelledby="tab-social" hidden>
    <div class="form-grid">
      <?php foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X (Twitter)', 'linkedin' => 'LinkedIn'] as $k => $l): ?>
        <?= $txt($k, $l . ' page link', '', 'url', 2, 'https://') ?>
      <?php endforeach; ?>
      <div class="fld c4"><span class="help">Only links you fill in appear in the footer.</span></div>
    </div>
  </section>

  <section class="card tab-panel" id="panel-homepage" role="tabpanel" aria-labelledby="tab-homepage" hidden>
    <div class="form-grid">
      <?= $txt('hero_title', 'Main headline', 'Short and bold works best — four words or fewer fill the hero beautifully.', 'text', 4) ?>
      <?= $area('hero_subtitle', 'Text under the headline', '', 2) ?>
      <?= $txt('announcement', 'Announcement bar (optional)', 'A one-line message above the header, e.g. “Ramadan Umrah dates now open”. Leave empty to hide it.', 'text', 4) ?>
      <div class="form-section"><h3>Numbers</h3><p>Shown under the reviews and on the About page. Use only figures you can stand behind.</p></div>
      <?php foreach ($stats as $i => $st): ?>
        <div class="fld c1"><label for="sv<?= $i ?>">Figure <?= $i + 1 ?></label><input class="inp" id="sv<?= $i ?>" name="stat_value[]" value="<?= e($st['value']) ?>" placeholder="15,000+"></div>
        <div class="fld c1"><label for="sl<?= $i ?>">Label</label><input class="inp" id="sl<?= $i ?>" name="stat_label[]" value="<?= e($st['label']) ?>" placeholder="travellers looked after"></div>
      <?php endforeach; ?>
      <div class="fld c4"><span class="help">The departures board on the homepage shows your packages automatically — featured ones first. Set each package’s board status in Packages.</span></div>
    </div>
  </section>

  <section class="card tab-panel" id="panel-seo" role="tabpanel" aria-labelledby="tab-seo" hidden>
    <div class="form-grid">
      <?= $txt('meta_title', 'Homepage title in Google', 'About 60 characters.', 'text', 4) ?>
      <?= $area('meta_description', 'Homepage description in Google', 'About 155 characters. Also used when pages have no description of their own.', 2) ?>
      <?= $txt('site_url', 'Website address', 'Your full domain, e.g. https://okfortravel.com — used in emails, the sitemap and social previews.', 'url', 4, 'https://') ?>
      <div class="form-section"><h3>Tracking</h3><p>Paste only the ID. Leads and WhatsApp clicks are sent to these as conversion events automatically.</p></div>
      <?= $txt('ga4_id', 'Google Analytics 4 measurement ID', 'Starts with G-', 'text', 2, 'G-XXXXXXXXXX') ?>
      <?= $txt('meta_pixel_id', 'Meta (Facebook) Pixel ID', 'Numbers only', 'text', 2) ?>
      <?= $txt('gsc_verification', 'Google Search Console verification code', 'The content value of the HTML tag method. Then submit ' . e(abs_url('/sitemap.xml')) . ' in Search Console.', 'text', 4) ?>
      <?= $area('head_code', 'Extra code in <head> (advanced)', 'For other tools such as TikTok Pixel or a chat widget. Only paste code from sources you trust.', 4) ?>
    </div>
  </section>

  <section class="card tab-panel" id="panel-email" role="tabpanel" aria-labelledby="tab-email" hidden>
    <div class="form-grid">
      <?= $txt('notify_email', 'Send new lead alerts to', 'Separate inbox for your sales team. Defaults to the public email.', 'email') ?>
      <?= $toggle('customer_emails', 'Email customers a confirmation with their reference number', 'Only when they enter an email address.') ?>
      <div class="form-section"><h3>Sending (SMTP)</h3><p>On Hostinger: create a mailbox in hPanel → Emails, then use host <code>smtp.hostinger.com</code>, port <code>465</code>, security SSL, and the mailbox address and password below.</p></div>
      <?= $txt('smtp_host', 'SMTP host', '', 'text', 2, 'smtp.hostinger.com') ?>
      <?= $txt('smtp_port', 'Port', '', 'text', 1, '465') ?>
      <div class="fld c1"><label for="s_smtp_secure">Security</label><select class="inp" id="s_smtp_secure" name="smtp_secure"><?php foreach (['ssl' => 'SSL (port 465)', 'tls' => 'STARTTLS (port 587)', 'none' => 'None'] as $k => $l): ?><option value="<?= $k ?>"<?= $s('smtp_secure') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <?= $txt('smtp_user', 'Mailbox / username', '', 'text', 2, 'bookings@yourdomain.com') ?>
      <?= $secret('smtp_pass', 'Mailbox password', 'Stored on your server only.') ?>
      <?= $txt('mail_from', 'From address', 'Usually the same as the mailbox.', 'email') ?>
      <?= $txt('mail_from_name', 'From name') ?>
      <div class="fld c4"><div><button type="button" class="btn btn-ghost btn-sm" data-test-email><?= icon('send') ?>Send a test email to me</button> <span class="help">Save your changes first.</span></div></div>
    </div>
  </section>

  <section class="card tab-panel" id="panel-flights" role="tabpanel" aria-labelledby="tab-flights" hidden>
    <div class="form-grid">
      <div class="fld c2"><label for="s_flight_provider">Fare source</label><select class="inp" id="s_flight_provider" name="flight_provider"><?php foreach (['auto' => 'Automatic (first connected provider)', 'duffel' => 'Duffel', 'travelpayouts' => 'Travelpayouts / Aviasales', 'demo' => 'Sample fares only'] as $k => $l): ?><option value="<?= $k ?>"<?= $s('flight_provider') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><span class="help">Currently using: <strong><?= e(flight_provider()) ?></strong>. Without a key the site shows clearly labelled sample fares and sends visitors to WhatsApp to confirm.</span></div>
      <div class="form-section"><h3>Travelpayouts (free, earns commission)</h3><p>Sign up at travelpayouts.com, join the Aviasales programme, then copy the API token (Tools → API) and your marker (partner ID).</p></div>
      <?= $secret('travelpayouts_token', 'API token') ?>
      <?= $txt('travelpayouts_marker', 'Marker (partner ID)') ?>
      <div class="form-section"><h3>Duffel (real-time airline offers)</h3><p>Create an access token at duffel.com → Developers. Test tokens start with duffel_test_.</p></div>
      <?= $secret('duffel_token', 'Access token') ?>
    </div>
  </section>

  <section class="card tab-panel" id="panel-system" role="tabpanel" aria-labelledby="tab-system" hidden>
    <div class="form-grid">
      <?= $toggle('force_https', 'Always use HTTPS', 'Turn on once your SSL certificate is active (Hostinger: hPanel → Security → SSL). Visitors on http:// are redirected.') ?>
      <?= $toggle('maintenance', 'Maintenance mode', 'Visitors see a “back soon” page with your WhatsApp and phone. You stay signed in and can still browse the site.') ?>
      <?= $area('maintenance_message', 'Maintenance message', '', 2) ?>
      <div class="fld c4"><span class="help">Version <?= e(APP_VERSION) ?> · PHP <?= e(PHP_VERSION) ?> · <?= e(db_driver() === 'mysql' ? 'MySQL' : 'SQLite') ?> database</span></div>
    </div>
  </section>

  <div class="form-actions">
    <span class="muted small" data-dirty-note>All changes saved.</span>
    <div class="right"><button class="btn btn-dark" type="submit"><?= icon('save') ?>Save settings</button></div>
  </div>
</form>
