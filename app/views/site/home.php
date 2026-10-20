<?php
/** @var array $board @var array $featured @var array $destinations @var array $umrah @var array $visas @var array $reviews @var array $faqs @var array $posts @var array $stats */
$GLOBALS['_layout_dark'] = true;
$words = preg_split('/\s+/', trim((string) setting('hero_title', 'Every journey is OK.')));
$lines = count($words) <= 4 ? array_filter([$words[0] ?? '', $words[1] ?? '', implode(' ', array_slice($words, 2))]) : array_map(fn($c) => implode(' ', $c), array_chunk($words, (int) ceil(count($words) / 3)));
$boardLabel = function (array $p): string {
    $name = $p['dest_name'] ?? '' ?: (explode(',', (string) $p['location'])[0] ?: $p['title']);
    return trim((string) preg_split('/\s*[&,(]\s*/', (string) $name)[0]);
};
$destNames = array_column(rows('SELECT id, name FROM destinations'), 'name', 'id');
$categories = array_column(rows("SELECT DISTINCT category FROM packages WHERE active = 1 AND category IS NOT NULL AND category <> '' ORDER BY category"), 'category');
?>
<section class="hero" id="hero">
  <div class="flightpath" aria-hidden="true">
    <svg viewBox="0 0 1000 1000" preserveAspectRatio="none">
      <path class="trail trail-b" id="trailB" d="M-20 1010 C 330 965, 600 850, 735 585 S 860 235, 915 185"/>
      <path class="trail trail-a" id="trailA" d="M-20 975 C 300 930, 565 815, 700 555 S 815 215, 862 162"/>
    </svg>
    <div class="plane" id="plane" style="opacity:0"><svg viewBox="-32 -32 64 64"><path d="M30 0c0-3-4-4-8-4H6L-8-22h-6l8 18h-14l-6-7h-4l3 11-3 11h4l6-7h14l-8 18h6L6 4h16c4 0 8-1 8-4z"/></svg></div>
  </div>
  <div class="wrap hero-grid">
    <div>
      <h1><?php foreach ($lines as $l): ?><span class="line"><span><?= e($l) ?></span></span><?php endforeach; ?></h1>
      <p class="lead"><?= e(setting('hero_subtitle')) ?></p>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('/packages')) ?>">Browse holidays</a>
        <a class="btn btn-ghost" href="<?= e(wa_link('Hello! I would like help planning a trip.')) ?>" target="_blank" rel="noopener" data-wa="holiday" style="color:#fff"><?= icon('whatsapp') ?>WhatsApp a consultant</a>
      </div>
      <div class="hero-meta">
        <span><?= icon('checkcircle') ?>Free quotes, no obligation</span>
        <span><?= icon('checkcircle') ?>Pay only after you approve</span>
        <span><?= icon('checkcircle') ?>WhatsApp support while you travel</span>
      </div>
    </div>

    <?php if ($board): ?>
    <div class="board" id="board" data-tz="<?= e(setting('timezone', 'Asia/Dubai')) ?>">
      <div class="board-head">
        <div class="board-title"><?= icon('takeoff') ?>Departures</div>
        <div class="board-clock"><span class="live-dot" aria-hidden="true"></span><span id="boardClock"><?= e(date('D j M · H:i')) ?></span></div>
      </div>
      <table>
        <caption class="sr-only">Featured trips this week</caption>
        <thead><tr><th class="c-code" scope="col">To</th><th class="c-dest" scope="col">Destination</th><th class="c-days" scope="col">Trip</th><th class="c-price" scope="col">From <?= e(visitor_currency()) ?></th><th class="c-status" scope="col">Status</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($board, 0, 6) as $p): $p['dest_name'] = $destNames[$p['destination_id']] ?? ''; ?>
          <tr class="row" data-href="<?= e($p['url']) ?>">
            <td class="c-code"><span class="flap-text"><?= e(strtoupper($p['airport_code'] ?: substr($boardLabel($p), 0, 3))) ?></span></td>
            <td class="c-dest"><a class="flap-text" href="<?= e($p['url']) ?>" style="text-decoration:none" aria-label="<?= e($p['title']) ?>"><?= e(mb_strtoupper($boardLabel($p))) ?></a></td>
            <td class="c-days"><span class="flap-text"><?= (int) $p['days'] ?>D</span></td>
            <td class="c-price"><span class="flap-text"><?= e((string) round(price_value($p['price']))) ?></span></td>
            <td class="c-status"><span class="flap-text"><?= e(board_status($p)) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div class="board-foot"><span>Prices per person</span><a href="<?= e(url('/packages')) ?>">All departures<?= icon('arrow') ?></a></div>
      <script type="application/json" id="boardPool"><?= json_encode(array_map(fn($p) => [
          'code' => strtoupper($p['airport_code'] ?: ''), 'dest' => mb_strtoupper($boardLabel($p + ['dest_name' => $destNames[$p['destination_id']] ?? ''])),
          'days' => $p['days'] . 'D', 'price' => (string) round(price_value($p['price'])), 'status' => board_status($p), 'url' => $p['url'], 'title' => $p['title'],
      ], $board), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    </div>
    <?php endif; ?>
  </div>
</section>

<div class="search-dock">
  <div class="wrap">
    <div class="search-panel" data-tabs>
      <div class="search-tabs" role="tablist" aria-label="What are you planning?">
        <button class="search-tab" role="tab" aria-selected="true" aria-controls="paneHoliday" id="tabHoliday"><?= icon('palm') ?>Holidays</button>
        <button class="search-tab" role="tab" aria-selected="false" aria-controls="paneFlight" id="tabFlight" tabindex="-1"><?= icon('plane') ?>Flights</button>
        <button class="search-tab" role="tab" aria-selected="false" aria-controls="paneUmrah" id="tabUmrah" tabindex="-1"><?= icon('kaaba') ?>Umrah</button>
        <button class="search-tab" role="tab" aria-selected="false" aria-controls="paneVisa" id="tabVisa" tabindex="-1"><?= icon('passport') ?>Visas</button>
      </div>

      <form class="search-pane" id="paneHoliday" role="tabpanel" aria-labelledby="tabHoliday" action="<?= e(url('/packages')) ?>" method="get">
        <div class="search-row" style="--cols:4">
          <div class="field"><label for="hq">Where to?</label><input id="hq" name="q" placeholder="Maldives, Dubai, Bali…" list="destList" autocomplete="off">
            <datalist id="destList"><?php foreach (rows('SELECT name FROM destinations WHERE active = 1 ORDER BY name') as $d): ?><option value="<?= e($d['name']) ?>"><?php endforeach; ?></datalist></div>
          <div class="field"><label for="hc">Trip type</label><select id="hc" name="category"><option value="">Any</option><?php foreach ($categories as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label for="hd">Length</label><select id="hd" name="duration"><option value="">Any length</option><option value="short">Up to 4 days</option><option value="week">5–8 days</option><option value="long">9 days or more</option></select></div>
          <div class="field"><label for="hb">Budget per person</label><select id="hb" name="budget"><option value="">Any budget</option><?php foreach (package_budgets() as $k => $b): ?><option value="<?= e($k) ?>"><?= e($b[2]) ?></option><?php endforeach; ?></select></div>
          <button class="btn" type="submit"><?= icon('search') ?>Find holidays</button>
        </div>
      </form>

      <form class="search-pane" id="paneFlight" role="tabpanel" aria-labelledby="tabFlight" action="<?= e(url('/flights')) ?>" method="get" hidden data-flight-form>
        <?= partial('flight_fields', ['compact' => true]) ?>
      </form>

      <div class="search-pane" id="paneUmrah" role="tabpanel" aria-labelledby="tabUmrah" hidden>
        <form action="<?= e(url('/enquire')) ?>" method="post" data-ajax="enquiry" novalidate>
          <?= csrf_field() ?><?= form_guard() ?>
          <input type="hidden" name="type" value="umrah"><input type="hidden" name="subject" value="Umrah quote from homepage">
          <div class="search-row" style="--cols:5">
            <div class="field"><label for="ul">Package</label><select id="ul" name="details[Package level]"><option>Economy</option><option selected>Premium</option><option>VIP</option><option>Hajj</option></select></div>
            <div class="field"><label for="um">Month</label><input id="um" name="travel_date" type="month" min="<?= date('Y-m') ?>" value="<?= date('Y-m', strtotime('+1 month')) ?>"></div>
            <div class="field"><label for="up">Pilgrims</label><input id="up" name="adults" type="number" min="1" max="60" value="2"></div>
            <div class="field"><label for="un">Your name</label><input id="un" name="name" required autocomplete="name"></div>
            <div class="field"><label for="uw">WhatsApp number</label><input id="uw" name="phone" type="tel" required autocomplete="tel" placeholder="+971…"></div>
            <button class="btn" type="submit">Get Umrah quote</button>
          </div>
        </form>
      </div>

      <div class="search-pane" id="paneVisa" role="tabpanel" aria-labelledby="tabVisa" hidden>
        <form action="<?= e(url('/enquire')) ?>" method="post" data-ajax="enquiry" novalidate>
          <?= csrf_field() ?><?= form_guard() ?>
          <input type="hidden" name="type" value="visa">
          <div class="search-row" style="--cols:5">
            <div class="field"><label for="vc">Visa for</label><select id="vc" name="subject"><?php foreach ($visas as $v): ?><option><?= e($v['country'] . ' — ' . $v['visa_type']) ?></option><?php endforeach; ?><option>Another country</option></select></div>
            <div class="field"><label for="vn">Nationality</label><input id="vn" name="details[Nationality]" placeholder="e.g. Pakistani"></div>
            <div class="field"><label for="va">Applicants</label><input id="va" name="adults" type="number" min="1" max="60" value="1"></div>
            <div class="field"><label for="vname">Your name</label><input id="vname" name="name" required autocomplete="name"></div>
            <div class="field"><label for="vw">WhatsApp number</label><input id="vw" name="phone" type="tel" required autocomplete="tel" placeholder="+971…"></div>
            <button class="btn" type="submit">Check my visa</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($featured): ?>
<section class="section">
  <div class="wrap">
    <div class="sec-head">
      <div><h2 class="h-l">Trips people are booking now</h2><p>Flights, hotels, transfers and tours in one price. Every package can be changed to suit you.</p></div>
      <a class="link" href="<?= e(url('/packages')) ?>">All holiday packages<?= icon('arrow') ?></a>
    </div>
    <div class="pkg-feature">
      <?php foreach (array_slice($featured, 0, 5) as $i => $p): ?>
        <?= partial('pkg_card', ['p' => $p, 'big' => $i === 0, 'withSummary' => $i === 0]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($destinations): ?>
<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head">
      <div><h2 class="h-l">Where to next?</h2><p>Guides, packages and flights for the places our travellers love most.</p></div>
      <a class="link" href="<?= e(url('/destinations')) ?>">All destinations<?= icon('arrow') ?></a>
    </div>
    <ul class="dest-list" data-peek>
      <?php foreach ($destinations as $d): ?>
        <li class="dest-row">
          <a href="<?= e(url('/destinations/' . $d['slug'])) ?>" data-img="<?= e(img_w($d['cover_image'], 600)) ?>">
            <span class="dest-code"><?= e($d['airport_code']) ?></span>
            <span class="dest-thumb"><img src="<?= e(img_w($d['cover_image'], 160)) ?>" alt="" loading="lazy" width="64" height="64"></span>
            <span class="dest-name"><?= e($d['name']) ?><small><?= e($d['tagline']) ?></small></span>
            <span class="dest-stat"><?php if ($d['trips']): ?><b>From <?= e(price($d['from_price'])) ?></b><?= plural((int) $d['trips'], 'package') ?><?php else: ?><b>Tailor-made</b>Ask for a quote<?php endif; ?></span>
            <span class="dest-go"><?= icon('arrow') ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="section section-night" id="umrah">
  <div class="wrap umrah-band">
    <div class="umrah-visual">
      <img src="<?= e(img_w('https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?auto=format&fit=crop&w=1200&q=80', 1100)) ?>" alt="Masjid an-Nabawi in Madinah at sunset" loading="lazy" width="1100" height="1375">
    </div>
    <div>
      <h2 class="h-l">Umrah, handled with care</h2>
      <p class="lead">Visas, hotels within walking distance of the Haram, transport between Makkah and Madinah, and guided Ziyarat — so your time is spent on worship, not logistics.</p>
      <?php if ($umrah): ?>
      <ul class="umrah-list">
        <?php foreach ($umrah as $p): ?>
          <li><a href="<?= e($p['url']) ?>"><span><strong><?= e($p['title']) ?></strong><small><?= (int) $p['nights'] ?> nights · <?= e(str_limit($p['summary'], 70)) ?></small></span><span class="price"><?= e(price($p['price'])) ?><small>per person</small></span></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('/umrah')) ?>">See Umrah packages</a>
        <a class="btn btn-ghost" href="<?= e(wa_link('Assalamu alaikum! I would like details about Umrah packages.')) ?>" target="_blank" rel="noopener" data-wa="umrah"><?= icon('whatsapp') ?>Ask on WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<?php if ($visas): ?>
<section class="section">
  <div class="wrap">
    <div class="sec-head">
      <div><h2 class="h-l">Visas without the guesswork</h2><p>We prepare and check every document before it is submitted. Fees include our service and government charges for most nationalities.</p></div>
      <a class="link" href="<?= e(url('/visas')) ?>">All visa services<?= icon('arrow') ?></a>
    </div>
    <table class="visa-table">
      <thead><tr><th scope="col">Country</th><th scope="col">Processing</th><th scope="col">Stay</th><th scope="col">Fee from</th><th><span class="sr-only">Details</span></th></tr></thead>
      <tbody>
      <?php foreach ($visas as $v): ?>
        <tr>
          <td><a class="country" href="<?= e(url('/visas/' . $v['slug'])) ?>"><?= e($v['country']) ?><small><?= e($v['visa_type']) ?></small></a></td>
          <td><?= e($v['processing_time']) ?></td>
          <td><?= e($v['stay']) ?></td>
          <td class="fee"><?= $v['price'] ? e(price($v['price'])) : 'On request' ?></td>
          <td style="text-align:right"><a class="link" href="<?= e(url('/visas/' . $v['slug'])) ?>" aria-label="<?= e($v['country']) ?> visa details">Requirements<?= icon('arrow') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="visa-cards">
      <?php foreach ($visas as $v): ?>
        <a href="<?= e(url('/visas/' . $v['slug'])) ?>"><strong><?= e($v['country']) ?></strong><small><?= e($v['visa_type']) ?> · <?= e($v['processing_time']) ?></small><b><?= $v['price'] ? e(price($v['price'])) : '—' ?></b></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-l">How booking works</h2><p>No payment until you have a written quotation you are happy with.</p></div></div>
    <ol class="steps">
      <li><h3>Tell us your plans</h3><p>Send a request on the website or message us on WhatsApp with dates and travellers.</p></li>
      <li><h3>Get a quotation</h3><p>A consultant replies with options, final prices and what is included — usually within the hour.</p></li>
      <li><h3>Confirm with a deposit</h3><p>Pay by bank transfer, card or at our office. Tickets, vouchers and visas follow.</p></li>
      <li><h3>Travel with backup</h3><p>Your consultant stays on WhatsApp throughout the trip in case anything changes.</p></li>
    </ol>
  </div>
</section>

<?php if ($reviews): ?>
<section class="section">
  <div class="wrap">
    <div class="sec-head">
      <div><h2 class="h-l">From our travellers</h2></div>
      <div class="carousel-btns"><button class="icon-btn" data-scroll="#reviews" data-dir="-1" aria-label="Previous reviews"><?= icon('left') ?></button><button class="icon-btn" data-scroll="#reviews" data-dir="1" aria-label="Next reviews"><?= icon('right') ?></button></div>
    </div>
    <div class="reviews" id="reviews" tabindex="0" aria-label="Customer reviews">
      <?php foreach ($reviews as $r): ?>
        <figure class="review">
          <?= partial('stars', ['rating' => $r['rating']]) ?>
          <blockquote><?= e($r['body']) ?></blockquote>
          <footer>
            <span class="avatar"><?php if ($r['photo']): ?><img src="<?= e(img_w($r['photo'], 120)) ?>" alt="" loading="lazy" width="46" height="46"><?php else: ?><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?><?php endif; ?></span>
            <figcaption><strong><?= e($r['name']) ?></strong><small><?= e(implode(' · ', array_filter([$r['trip'], $r['location']]))) ?></small></figcaption>
          </footer>
        </figure>
      <?php endforeach; ?>
    </div>
    <?php if ($stats): ?>
    <div class="stats">
      <?php foreach ($stats as $st): ?><div class="stat"><b><?= e($st['value'] ?? '') ?></b><span><?= e($st['label'] ?? '') ?></span></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head">
      <div><h2 class="h-l">Travel journal</h2><p>Visa guides, Umrah checklists and honest destination advice from our consultants.</p></div>
      <a class="link" href="<?= e(url('/blog')) ?>">All articles<?= icon('arrow') ?></a>
    </div>
    <div class="posts"><?php foreach ($posts as $post): ?><?= partial('post_card', ['post' => $post]) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($faqs): ?>
<section class="section">
  <div class="wrap faq-layout">
    <div>
      <h2 class="h-l">Questions, answered</h2>
      <p class="lead">Still unsure? Our consultants are a message away.</p>
      <div class="hero-actions">
        <a class="btn btn-wa" href="<?= e(wa_link('Hello! I have a question about booking.')) ?>" target="_blank" rel="noopener" data-wa="contact"><?= icon('whatsapp') ?>Ask on WhatsApp</a>
        <a class="btn btn-ghost" href="<?= e(url('/faq')) ?>">All questions</a>
      </div>
    </div>
    <div class="faq"><?php foreach ($faqs as $i => $f): ?><details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($f['question']) ?><span class="pm"><?= icon('plus') ?></span></summary><div class="answer"><?= nl2br(e($f['answer'])) ?></div></details><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?= partial('cta') ?>
