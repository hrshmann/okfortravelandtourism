<?php /** @var array $prefill @var string $provider @var array $popular */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head" style="padding-bottom:clamp(120px,12vw,160px)">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Flights</li></ol>
    <h1>Find your flight</h1>
    <p class="lead">Compare fares on hundreds of airlines, then send the one you like to a consultant who confirms the price and issues the ticket.</p>
  </div>
</section>

<div class="search-dock" style="margin-top:clamp(-120px,-10vw,-90px)">
  <div class="wrap">
    <form class="search-panel" action="<?= e(url('/flights')) ?>" method="get" data-flight-form="live" style="padding:16px">
      <?= partial('flight_fields', ['prefill' => $prefill]) ?>
      <div class="search-foot">
        <span><?= $provider === 'demo' ? 'Showing sample fares. A consultant confirms the live price before you pay.' : 'Live fares. Prices change quickly, so we re-confirm before you pay.' ?></span>
        <span>Popular:
          <?php foreach ($popular as $i => [$f, $t]): ?><?= $i ? ' · ' : '' ?><a href="<?= e(url('/flights', ['from' => $f, 'to' => $t, 'date' => date('Y-m-d', strtotime('+14 days')), 'return' => date('Y-m-d', strtotime('+21 days')), 'auto' => 1])) ?>"><?= e($f) ?> → <?= e($t) ?></a><?php endforeach; ?>
        </span>
      </div>
    </form>
  </div>
</div>

<section class="section-tight" id="flightResults" hidden>
  <div class="wrap results-layout" style="display:grid">
    <aside class="f-filters" id="fFilters">
      <h4>Stops</h4><div id="stopFilters"></div>
      <h4>Maximum price</h4><div class="range-label"><span id="priceMin"></span><span id="priceVal"></span></div><input type="range" id="priceRange" aria-label="Maximum price">
      <h4>Departure time</h4>
      <div id="timeFilters">
        <label class="check"><span><input type="checkbox" value="0" checked>Night (00–06)</span></label>
        <label class="check"><span><input type="checkbox" value="1" checked>Morning (06–12)</span></label>
        <label class="check"><span><input type="checkbox" value="2" checked>Afternoon (12–18)</span></label>
        <label class="check"><span><input type="checkbox" value="3" checked>Evening (18–24)</span></label>
      </div>
      <h4>Airlines</h4><div id="airlineFilters"></div>
    </aside>
    <div>
      <div class="listing-bar">
        <div><h2 class="h-s" id="resultsTitle" style="margin:0">Flights</h2><small class="muted" id="resultsSub"></small></div>
        <a class="btn btn-ghost btn-sm" id="flightHelp" href="<?= e(wa_link('Hello! Please help me find a flight.')) ?>" target="_blank" rel="noopener" data-wa="flight"><?= icon('whatsapp') ?>Ask a consultant</a>
      </div>
      <div class="sort-tabs">
        <button class="sort-tab" data-sort="price" aria-pressed="true"><strong>Cheapest</strong><small id="sortPrice">—</small></button>
        <button class="sort-tab" data-sort="duration" aria-pressed="false"><strong>Fastest</strong><small id="sortDuration">—</small></button>
        <button class="sort-tab" data-sort="depart" aria-pressed="false"><strong>Earliest</strong><small id="sortDepart">—</small></button>
      </div>
      <div id="resultsNotice"></div>
      <div id="resultsList" aria-live="polite"></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-m">Why book flights with a consultant?</h2></div></div>
    <ul class="steps plain">
      <li><?= icon('globe') ?><h3>Every airline in one place</h3><p>We search full-service and low-cost carriers, including fares that are not sold online.</p></li>
      <li><?= icon('shield') ?><h3>Checked before you pay</h3><p>Baggage, visas for transits and fare rules are confirmed with you before ticketing.</p></li>
      <li><?= icon('refresh') ?><h3>Changes handled for you</h3><p>Date changes, cancellations and refunds go through your consultant, not a call centre.</p></li>
      <li><?= icon('users') ?><h3>Group and family fares</h3><p>Booking for ten or more? Ask for a group fare — they are often much cheaper.</p></li>
    </ul>
  </div>
</section>
<?= partial('cta', ['ctaTitle' => 'Prefer someone to search for you?', 'ctaText' => 'Send your route and dates. We reply with the best options across airlines, usually within the hour.']) ?>
