<?php
declare(strict_types=1);

/**
 * Starter content loaded by the installer. Everything here can be edited or deleted in the admin panel.
 * Reviews and statistics are samples — replace them with real ones before launch.
 */
function seed_img(string $id, int $w = 1600): string
{
    return "https://images.unsplash.com/$id?auto=format&fit=crop&w=$w&q=80";
}

function seed_content(): void
{
    $now = now();
    $days = fn(array $d) => json_enc(array_map(fn($x) => ['title' => $x[0], 'text' => $x[1]], $d));

    /* ---------------------------------------------------------- destinations */
    $destinations = [
        ['Dubai', 'United Arab Emirates', 'Middle East', 'DXB', 'Skyline, desert and the world’s best shopping', 'November to March', 'photo-1512453979798-5ea266f8880c',
            'Dubai packs a lot into a long weekend: the view from the 124th floor of Burj Khalifa, dune bashing at sunset, abra rides across the Creek and some of the best hotels anywhere. It is also the easiest place to start a multi-city trip, with direct flights to more than 240 destinations.'],
        ['Maldives', 'Maldives', 'Asia', 'MLE', 'Overwater villas and house reefs', 'November to April', 'photo-1514282401047-d79a71a590e8',
            'One island, one resort: the Maldives is about doing very little in a very beautiful place. Choose between speedboat-transfer resorts close to Malé and seaplane resorts on remote atolls. We match the resort to your budget and the kind of reef you want to swim on.'],
        ['Türkiye', 'Türkiye', 'Europe', 'IST', 'Where two continents meet', 'April to June, September to November', 'photo-1524231757912-21f4fe3a7200',
            'Istanbul’s mosques and bazaars, hot-air balloons over Cappadocia, and the turquoise coast of Antalya — Türkiye works for families, couples and first-time travellers alike, with easy e-visas for many nationalities.'],
        ['Bali', 'Indonesia', 'Asia', 'DPS', 'Temples, rice terraces and private pool villas', 'April to October', 'photo-1537996194471-e657df975ab4',
            'Stay in a pool villa in Seminyak, spend a day in Ubud’s jungle and rice terraces, and watch the sun set over Uluwatu. Bali is excellent value once you are there, and visa on arrival keeps things simple.'],
        ['Switzerland', 'Switzerland', 'Europe', 'ZRH', 'Lakes, trains and the Top of Europe', 'June to September, December to March', 'photo-1530122037265-a5f1f91d3b99',
            'Lucerne, Interlaken, Jungfraujoch and Zermatt are all connected by some of the most scenic railways in the world. A Swiss Travel Pass covers trains, boats and most museums, which is how we build every Swiss itinerary.'],
        ['Thailand', 'Thailand', 'Asia', 'BKK', 'Street food, temples and island hopping', 'November to April', 'photo-1552465011-b4e21bf6e79a',
            'Bangkok for temples, markets and food; Phuket and Krabi for beaches and long-tail boat trips to Phi Phi. Thailand is one of the best-value family destinations from the Gulf.'],
        ['Malaysia', 'Malaysia', 'Asia', 'KUL', 'Petronas Towers to Langkawi beaches', 'December to April', 'photo-1596422846543-75c6fc197f07',
            'Kuala Lumpur’s skyline and food courts, the cool air of Genting Highlands and the duty-free beaches of Langkawi — Malaysia is halal-friendly, affordable and easy to travel around.'],
        ['Makkah & Madinah', 'Saudi Arabia', 'Middle East', 'JED', 'Umrah and Hajj with care and guidance', 'All year (avoid peak summer heat)', 'photo-1591604129939-f1efa4d9f7fa',
            'We arrange Umrah visas, hotels close to the Haram and Masjid an-Nabawi, transport between the holy cities and guided Ziyarat, so you can focus on your worship.'],
    ];
    $destIds = [];
    foreach ($destinations as $i => [$name, $country, $region, $code, $tagline, $best, $img, $desc]) {
        $destIds[$code] = insert('destinations', [
            'slug' => slugify($name), 'name' => $name, 'country' => $country, 'region' => $region, 'airport_code' => $code,
            'tagline' => $tagline, 'best_time' => $best, 'description' => '<p>' . e($desc) . '</p>', 'cover_image' => seed_img($img),
            'featured' => 1, 'active' => 1, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /* -------------------------------------------------------------- packages */
    $packages = [
        [
            'title' => 'Dubai Luxury Escape', 'dest' => 'DXB', 'location' => 'Dubai, UAE', 'category' => 'City break', 'days' => 5, 'nights' => 4,
            'price' => 899, 'old' => 1150, 'rating' => 4.9, 'badge' => 'Best seller', 'status' => 'BOARDING', 'featured' => 1, 'img' => 'photo-1512453979798-5ea266f8880c',
            'gallery' => ['photo-1518684079-3c830dcef090', 'photo-1451337516015-6b6e9a44a8a3', 'photo-1546412414-e1885259563a'],
            'summary' => 'Five-star stay with Burj Khalifa views, a desert safari with BBQ dinner and a sunset dhow cruise in Dubai Marina.',
            'description' => "Dubai at its most comfortable. You stay in a five-star hotel in Downtown with views of Burj Khalifa, and every transfer is private, so there is no waiting around.\n\nDays are planned but never rushed: the city tour and At the Top tickets are timed for sunset, the desert safari includes dune bashing and a BBQ dinner, and the last day is left free for shopping.",
            'highlights' => ['Burj Khalifa At the Top at sunset', 'Desert safari with dune bashing and BBQ dinner', 'Dhow cruise dinner in Dubai Marina', 'Optional day trip to Abu Dhabi and the Grand Mosque'],
            'inc' => ['4 nights in a 5-star Downtown hotel with breakfast', 'Private return airport transfers', 'Desert safari with BBQ dinner', 'Burj Khalifa At the Top tickets (level 124)', 'Dhow cruise dinner in Dubai Marina', 'UAE tourist visa assistance'],
            'exc' => ['International flights (we can add them)', 'Tourism Dirham fee paid at the hotel', 'Lunches and personal expenses'],
            'days_list' => [['Arrive in Dubai', 'Private transfer to your hotel. In the evening, walk to the Dubai Fountain show at Dubai Mall.'], ['City tour and Burj Khalifa', 'Old Dubai, the Gold and Spice Souks and an abra ride, then At the Top at sunset.'], ['Desert safari', 'Free morning. Afternoon pick-up for dune bashing, camel rides and a BBQ dinner under the stars.'], ['Abu Dhabi or leisure', 'Optional day trip to Sheikh Zayed Grand Mosque and Louvre Abu Dhabi, or a beach day.'], ['Departure', 'Breakfast, last-minute shopping and private transfer to the airport.']],
        ],
        [
            'title' => 'Maldives Overwater Paradise', 'dest' => 'MLE', 'location' => 'South Malé Atoll, Maldives', 'category' => 'Honeymoon', 'days' => 6, 'nights' => 5,
            'price' => 1890, 'old' => 2300, 'rating' => 5.0, 'badge' => 'Honeymoon', 'status' => 'FEW LEFT', 'featured' => 1, 'img' => 'photo-1514282401047-d79a71a590e8',
            'gallery' => ['photo-1573843981267-be1999ff37cd', 'photo-1540202404-a2f29016b523', 'photo-1590523277543-a94d2e4eb00b'],
            'summary' => 'Five nights in a water villa with all-inclusive dining, speedboat transfers and a sunset dolphin cruise.',
            'description' => "A water villa with steps straight into the lagoon, all meals and drinks included, and a resort only 40 minutes by speedboat from Malé — so no expensive seaplane.\n\nWe add honeymoon extras at no cost when you tell us it is a special trip: a decorated villa, a cake and a private beach dinner on one evening.",
            'highlights' => ['Water villa with direct lagoon access', 'All-inclusive meals and drinks', 'Sunset dolphin cruise', 'Couples spa session'],
            'inc' => ['5 nights in a water villa', 'All-inclusive meals and drinks', 'Return speedboat transfers', 'Sunset dolphin cruise', 'One 60-minute couples spa session', 'Snorkelling equipment'],
            'exc' => ['International flights', 'Green tax (USD 6 per person per night)', 'Diving and motorised water sports'],
            'days_list' => [['Arrive in Malé', 'Meet our representative and take the speedboat to your resort.'], ['House reef', 'Snorkel the house reef, home to turtles and reef sharks.'], ['Dolphin cruise', 'Relaxed morning, then a sunset cruise to spot spinner dolphins.'], ['Spa and beach dinner', 'Couples spa in the afternoon and a private dinner on the sand.'], ['Sandbank picnic', 'Boat trip to a sandbank for swimming and a picnic lunch.'], ['Departure', 'Speedboat back to Malé for your flight home.']],
        ],
        [
            'title' => 'Premium Umrah Package', 'dest' => 'JED', 'location' => 'Makkah & Madinah, Saudi Arabia', 'category' => 'Umrah', 'days' => 10, 'nights' => 9,
            'price' => 1450, 'old' => 1700, 'rating' => 4.9, 'badge' => 'Umrah', 'status' => 'ON SALE', 'featured' => 1, 'img' => 'photo-1591604129939-f1efa4d9f7fa',
            'gallery' => ['photo-1565552645632-d725f8bfc19a', 'photo-1542816417-0983c9c9ad53'],
            'summary' => 'Hotels within walking distance of the Haram and Masjid an-Nabawi, private transport and guided Ziyarat.',
            'description' => "A calm, fully guided Umrah. Your hotel in Makkah is about 300 metres from the Haram and your hotel in Madinah about 200 metres from Masjid an-Nabawi, so you can attend every prayer without long walks.\n\nA scholar accompanies the group for the first Umrah and for Ziyarat in both cities, and our team in Saudi Arabia is available on WhatsApp throughout.",
            'highlights' => ['Hotels within walking distance of both Harams', 'Guided first Umrah with a scholar', 'Private GMC transport between cities', 'Ziyarat in Makkah and Madinah'],
            'inc' => ['Umrah visa processing', '5 nights in Makkah (around 300 m from the Haram)', '4 nights in Madinah (around 200 m from the Masjid)', 'Return economy flights', 'Private Makkah–Madinah transport', 'Guided Ziyarat with a scholar'],
            'exc' => ['Meals other than breakfast', 'Qurbani / Hady', 'Personal expenses'],
            'days_list' => [['Arrive in Jeddah', 'Transfer to Makkah and check in.'], ['Umrah', 'Perform Umrah together with our guide.'], ['Makkah', 'Ibadah in Masjid al-Haram.'], ['Ziyarat in Makkah', 'Visit Jabal al-Nour, Mina, Muzdalifah and Arafat.'], ['Makkah', 'Ibadah in Masjid al-Haram.'], ['Travel to Madinah', 'Private transport to Madinah and check in.'], ['Ziyarat in Madinah', 'Visit Quba Mosque, Uhud and Masjid al-Qiblatayn.'], ['Madinah', 'Ibadah in Masjid an-Nabawi.'], ['Madinah', 'Ibadah and free time.'], ['Departure', 'Transfer to Madinah airport for your flight home.']],
        ],
        [
            'title' => 'Economy Umrah — 7 Nights', 'dest' => 'JED', 'location' => 'Makkah & Madinah, Saudi Arabia', 'category' => 'Umrah', 'days' => 8, 'nights' => 7,
            'price' => 820, 'old' => null, 'rating' => 4.7, 'badge' => 'Value', 'status' => 'ON SALE', 'featured' => 0, 'img' => 'photo-1565552645632-d725f8bfc19a',
            'gallery' => [],
            'summary' => 'Good-value Umrah with comfortable hotels, shuttle service to the Haram and group transport.',
            'description' => 'A well-organised Umrah at a sensible price. Hotels are a short shuttle ride from the Haram, transport is by air-conditioned coach, and our coordinator travels with the group.',
            'highlights' => ['Hotel shuttle to the Haram', 'Group coordinator', 'Ziyarat in Makkah and Madinah'],
            'inc' => ['Umrah visa processing', '4 nights in Makkah', '3 nights in Madinah', 'Coach transport between cities', 'Group Ziyarat'],
            'exc' => ['Flights (we quote them separately)', 'Meals', 'Personal expenses'],
            'days_list' => [['Arrive in Jeddah', 'Coach to Makkah and check in.'], ['Umrah', 'Perform Umrah with the group coordinator.'], ['Makkah', 'Ibadah.'], ['Ziyarat', 'Group Ziyarat in Makkah.'], ['Travel to Madinah', 'Coach to Madinah.'], ['Ziyarat', 'Group Ziyarat in Madinah.'], ['Madinah', 'Ibadah.'], ['Departure', 'Transfer to the airport.']],
        ],
        [
            'title' => 'Istanbul & Cappadocia Discovery', 'dest' => 'IST', 'location' => 'Istanbul & Cappadocia, Türkiye', 'category' => 'Culture', 'days' => 7, 'nights' => 6,
            'price' => 1120, 'old' => 1390, 'rating' => 4.8, 'badge' => 'Trending', 'status' => 'BOARDING', 'featured' => 1, 'img' => 'photo-1524231757912-21f4fe3a7200',
            'gallery' => ['photo-1641128324972-af3212f0f6bd', 'photo-1570939274717-7eda259b50ed'],
            'summary' => 'The Blue Mosque, the Grand Bazaar and a Bosphorus cruise, then sunrise balloons over Cappadocia.',
            'description' => 'Four nights in Istanbul’s old city and two nights in a cave hotel in Cappadocia, with a domestic flight in between so you do not lose a day on the road.',
            'highlights' => ['Hagia Sophia, Blue Mosque and Topkapı Palace', 'Bosphorus cruise', 'Cave hotel in Cappadocia', 'Optional sunrise balloon flight'],
            'inc' => ['6 nights in hotels with breakfast', 'Domestic flight Istanbul–Cappadocia', 'Bosphorus cruise', 'Guided old-city tour', 'Cappadocia red and green tours', 'All transfers'],
            'exc' => ['International flights', 'Balloon flight (from USD 180, subject to weather)', 'Lunches and dinners'],
            'days_list' => [['Arrive in Istanbul', 'Transfer to your hotel in Sultanahmet.'], ['Old city', 'Hagia Sophia, the Blue Mosque and Topkapı Palace with a licensed guide.'], ['Bosphorus and bazaar', 'Morning cruise, afternoon in the Grand Bazaar and Spice Market.'], ['Fly to Cappadocia', 'Morning flight and the red tour: Göreme, Uçhisar and Pasabag.'], ['Balloons and the green tour', 'Optional sunrise balloon flight, then the underground city and Ihlara Valley.'], ['Back to Istanbul', 'Fly back for a free afternoon in Galata and Karaköy.'], ['Departure', 'Transfer to the airport.']],
        ],
        [
            'title' => 'Bali Island Bliss', 'dest' => 'DPS', 'location' => 'Seminyak & Ubud, Bali', 'category' => 'Beach', 'days' => 6, 'nights' => 5,
            'price' => 760, 'old' => 950, 'rating' => 4.8, 'badge' => 'Hot deal', 'status' => 'ON SALE', 'featured' => 1, 'img' => 'photo-1537996194471-e657df975ab4',
            'gallery' => ['photo-1555400038-63f5ba517a47', 'photo-1518548419970-58e3b4079ab2'],
            'summary' => 'A private pool villa in Seminyak with Ubud’s rice terraces, Nusa Penida and an Uluwatu sunset.',
            'description' => 'Your own villa with a private pool, a driver for every excursion and an itinerary that balances sightseeing with time to do nothing at all.',
            'highlights' => ['Private pool villa', 'Ubud rice terraces and jungle swing', 'Nusa Penida day trip', 'Uluwatu sunset and Kecak dance'],
            'inc' => ['5 nights in a private pool villa', 'Daily breakfast', 'Airport transfers', 'Ubud full-day tour', 'Nusa Penida island trip', 'Uluwatu sunset and Kecak dance'],
            'exc' => ['International flights', 'Bali tourist levy', 'Visa on arrival fee'],
            'days_list' => [['Arrive in Bali', 'Transfer to your villa in Seminyak.'], ['Ubud', 'Tegallalang rice terraces, the Monkey Forest and a jungle swing.'], ['Nusa Penida', 'Fast boat to Kelingking Beach and Crystal Bay.'], ['Free day', 'Beach clubs, spa or a cooking class.'], ['Uluwatu', 'Clifftop temple at sunset and the Kecak fire dance.'], ['Departure', 'Transfer to the airport.']],
        ],
        [
            'title' => 'Swiss Alps Grand Tour', 'dest' => 'ZRH', 'location' => 'Lucerne & Interlaken, Switzerland', 'category' => 'Adventure', 'days' => 8, 'nights' => 7,
            'price' => 2450, 'old' => 2890, 'rating' => 4.9, 'badge' => 'Premium', 'status' => 'NEW', 'featured' => 1, 'img' => 'photo-1530122037265-a5f1f91d3b99',
            'gallery' => ['photo-1527668752968-14dc70a27c95', 'photo-1506905925346-21bda4d32df4'],
            'summary' => 'Zurich, Lucerne, Interlaken and Jungfraujoch by scenic train, with a Swiss Travel Pass included.',
            'description' => 'Switzerland is best seen from a train window. This itinerary uses the Swiss Travel Pass for every journey, including the GoldenPass line, with hotels close to the station in each town.',
            'highlights' => ['Jungfraujoch — the Top of Europe', 'Mt Titlis rotating cable car', 'Lake Lucerne cruise', 'GoldenPass scenic train'],
            'inc' => ['7 nights in 4-star hotels with breakfast', '8-day Swiss Travel Pass', 'Jungfraujoch excursion', 'Mt Titlis cable car', 'Lake Lucerne cruise', 'Schengen visa assistance'],
            'exc' => ['International flights', 'City taxes', 'Lunches and dinners'],
            'days_list' => [['Arrive in Zurich', 'Train to Lucerne and check in.'], ['Lucerne', 'Old town, Chapel Bridge and a lake cruise.'], ['Mt Titlis', 'Rotating cable car and the glacier park.'], ['Interlaken', 'Scenic train to Interlaken.'], ['Jungfraujoch', 'Cogwheel train to the Top of Europe.'], ['Lauterbrunnen', 'Waterfall valley and Grindelwald.'], ['GoldenPass', 'Panoramic train to Montreux and back.'], ['Departure', 'Train to Zurich airport.']],
        ],
        [
            'title' => 'Bangkok & Phuket Combo', 'dest' => 'BKK', 'location' => 'Bangkok & Phuket, Thailand', 'category' => 'Family', 'days' => 7, 'nights' => 6,
            'price' => 690, 'old' => 850, 'rating' => 4.7, 'badge' => 'Family', 'status' => 'ON SALE', 'featured' => 0, 'img' => 'photo-1552465011-b4e21bf6e79a',
            'gallery' => ['photo-1589394815804-964ed0be2eb5', 'photo-1506665531195-3566af2b4dfa'],
            'summary' => 'Temples and street food in Bangkok, then beaches and a Phi Phi island speedboat trip in Phuket.',
            'description' => 'Three nights in Bangkok and three in Phuket, connected by a short domestic flight. Family rooms and child prices are available at every hotel.',
            'highlights' => ['Grand Palace and Wat Arun', 'Floating market', 'Phi Phi islands by speedboat', 'James Bond Island'],
            'inc' => ['6 nights in hotels with breakfast', 'Bangkok–Phuket flight', 'Bangkok city and temple tour', 'Phi Phi island speedboat tour', 'All transfers', 'Thailand visa guidance'],
            'exc' => ['International flights', 'National park fees', 'Lunches and dinners'],
            'days_list' => [['Arrive in Bangkok', 'Transfer to your hotel.'], ['Temples', 'Grand Palace, Wat Pho and Wat Arun.'], ['Fly to Phuket', 'Morning flight and an afternoon on Patong Beach.'], ['Phi Phi islands', 'Full-day speedboat tour with lunch.'], ['James Bond Island', 'Phang Nga Bay by long-tail boat.'], ['Free day', 'Beach or a Thai cooking class.'], ['Departure', 'Transfer to Phuket airport.']],
        ],
        [
            'title' => 'Malaysia Explorer', 'dest' => 'KUL', 'location' => 'Kuala Lumpur & Langkawi', 'category' => 'Family', 'days' => 6, 'nights' => 5,
            'price' => 640, 'old' => 790, 'rating' => 4.7, 'badge' => 'Value', 'status' => 'ON SALE', 'featured' => 0, 'img' => 'photo-1596422846543-75c6fc197f07',
            'gallery' => [],
            'summary' => 'Petronas Towers, Batu Caves and Genting Highlands, then the beaches and Sky Bridge of Langkawi.',
            'description' => 'Halal-friendly, affordable and easy to get around: Malaysia is a favourite with families. Three nights in Kuala Lumpur and two in Langkawi.',
            'highlights' => ['Petronas Twin Towers', 'Batu Caves', 'Genting Highlands', 'Langkawi Sky Bridge'],
            'inc' => ['5 nights in hotels with breakfast', 'Kuala Lumpur–Langkawi flight', 'KL city tour and Batu Caves', 'Genting Highlands day trip', 'Langkawi Sky Cab and Sky Bridge', 'All transfers'],
            'exc' => ['International flights', 'Tourism tax', 'Lunches and dinners'],
            'days_list' => [['Arrive in Kuala Lumpur', 'Transfer to your hotel.'], ['City tour', 'Petronas Towers, Merdeka Square and Batu Caves.'], ['Genting Highlands', 'Cable car and theme park.'], ['Fly to Langkawi', 'Afternoon at Cenang Beach.'], ['Sky Cab', 'Sky Cab, Sky Bridge and island hopping.'], ['Departure', 'Transfer to the airport.']],
        ],
    ];
    foreach ($packages as $i => $p) {
        insert('packages', [
            'slug' => slugify($p['title']), 'title' => $p['title'], 'destination_id' => $destIds[$p['dest']] ?? null, 'location' => $p['location'],
            'airport_code' => $p['dest'], 'category' => $p['category'], 'days' => $p['days'], 'nights' => $p['nights'], 'price' => $p['price'],
            'old_price' => $p['old'], 'price_note' => 'per person, twin sharing', 'rating' => $p['rating'], 'badge' => $p['badge'],
            'board_status' => $p['status'], 'summary' => $p['summary'], 'description' => text_to_html($p['description']),
            'highlights' => json_enc($p['highlights']), 'inclusions' => json_enc($p['inc']), 'exclusions' => json_enc($p['exc']),
            'itinerary' => $days($p['days_list']), 'gallery' => json_enc(array_map(fn($g) => seed_img($g), $p['gallery'])),
            'cover_image' => seed_img($p['img']), 'featured' => $p['featured'], 'active' => 1, 'sort_order' => $i, 'views' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /* ----------------------------------------------------------------- visas */
    $visas = [
        ['United Arab Emirates', 'Tourist visa (30 or 60 days)', '2–4 working days', '60 days from issue', '30 or 60 days', 'Single', 95, 'photo-1518684079-3c830dcef090',
            'Tourist visas for visiting family or holidaying in the UAE, applied for online with no embassy visit.',
            ['Passport valid for at least 6 months', 'Passport-size photo on a white background', 'Confirmed return ticket', 'Hotel booking or host’s address in the UAE']],
        ['Saudi Arabia', 'Tourist e-visa / Umrah visa', '1–3 working days', '1 year', 'Up to 90 days', 'Multiple', 140, 'photo-1591604129939-f1efa4d9f7fa',
            'Eligible nationalities can perform Umrah on a tourist e-visa. Others need an Umrah visa, which we arrange together with your package.',
            ['Passport valid for at least 6 months', 'Passport-size photo', 'Vaccination certificate where required', 'Hotel booking in Makkah and Madinah']],
        ['Schengen Area', 'Short-stay visa (type C)', '15 working days (can be longer in peak season)', 'As granted', 'Up to 90 days in 180', 'Multiple', 160, 'photo-1499856871958-5b9627545d1a',
            'One visa for 29 European countries. We prepare the file, book your biometrics appointment and check every document before you go.',
            ['Passport issued within the last 10 years and valid 3 months after return', 'Two recent photos (35 × 45 mm)', 'Bank statements for the last 6 months', 'Salary certificate or trade licence', 'Travel insurance with EUR 30,000 cover', 'Flight and hotel reservations']],
        ['United Kingdom', 'Standard visitor visa', '15 working days', '6 months', 'Up to 6 months', 'Multiple', 220, 'photo-1513635269975-59663e0ac1ad',
            'For tourism, visiting family or short business trips. We complete the online application and prepare your supporting documents.',
            ['Valid passport and any previous passports', 'Bank statements for the last 6 months', 'Employment letter or business documents', 'Residence visa (for UAE residents)', 'Accommodation details']],
        ['Türkiye', 'e-Visa / sticker visa', '1–10 working days', '180 days', 'Up to 30 days', 'Single', 70, 'photo-1524231757912-21f4fe3a7200',
            'Many nationalities can get a Türkiye e-visa online in minutes; holders of valid US, UK or Schengen visas often qualify too. Others need a sticker visa, which we handle.',
            ['Passport valid for at least 6 months', 'Return ticket', 'Hotel booking', 'Valid US, UK or Schengen visa if applying on that basis']],
        ['Azerbaijan', 'ASAN e-visa', '3 working days', '90 days', '30 days', 'Single', 45, 'photo-1601314002592-b8734bca6604',
            'A quick e-visa for Baku and Gabala, one of the most popular short breaks from the Gulf.',
            ['Passport valid for at least 6 months', 'Residence visa copy (for GCC residents)', 'Hotel booking']],
    ];
    foreach ($visas as $i => [$country, $type, $proc, $validity, $stay, $entry, $price, $img, $summary, $req]) {
        insert('visas', [
            'slug' => slugify($country), 'country' => $country, 'visa_type' => $type, 'processing_time' => $proc, 'validity' => $validity,
            'stay' => $stay, 'entry' => $entry, 'price' => $price, 'summary' => $summary, 'requirements' => json_enc($req),
            'description' => '<p>Fees shown are our service fee plus government charges for most nationalities. Requirements can differ by nationality and residence — message us for an exact checklist.</p>',
            'cover_image' => seed_img($img), 'active' => 1, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /* ------------------------------------------------------------- journal */
    $posts = [
        ['Umrah checklist: documents, packing and what to expect', 'Umrah', 'photo-1565552645632-d725f8bfc19a',
            'Everything to prepare before you travel for Umrah — from visas and vaccinations to what to pack for Ihram.',
            "<h2>Before you book</h2><p>Check whether your nationality can perform Umrah on a Saudi tourist e-visa. If not, you will need an Umrah visa, which must be issued together with a package from a licensed agent.</p><h2>Documents</h2><ul><li>Passport valid for at least six months</li><li>Visa (tourist e-visa or Umrah visa)</li><li>Return ticket and hotel confirmations for Makkah and Madinah</li><li>Vaccination certificates where required</li></ul><h2>What to pack</h2><ul><li>Two sets of Ihram for men; comfortable, modest clothing for women</li><li>Unscented soap and toiletries</li><li>Comfortable sandals you have already worn in</li><li>A small backpack for the Haram</li><li>Any regular medication in its original packaging</li></ul><h2>On arrival</h2><p>Book your Rawdah visit in Madinah through the Nusuk app as early as you can — slots fill quickly. Drink plenty of water, rest between prayers and keep a copy of your hotel address in Arabic on your phone.</p>"],
        ['Schengen visa from the UAE: a step-by-step guide', 'Visas', 'photo-1499856871958-5b9627545d1a',
            'Which country to apply to, how early to book an appointment and the documents that are most often missing.',
            "<h2>Apply to the right country</h2><p>Apply to the country where you will spend the most nights. If nights are equal, apply to the country you will enter first.</p><h2>Book early</h2><p>Appointments in summer can be fully booked six weeks ahead. You can apply up to six months before travelling.</p><h2>Documents that are often missing</h2><ul><li>Bank statements stamped by the bank (online printouts are often refused)</li><li>A salary certificate addressed to the embassy</li><li>Travel insurance covering at least EUR 30,000 for the whole trip</li></ul><p>We review every file before your appointment, which avoids most refusals.</p>"],
        ['When to visit the Maldives — and how to save on it', 'Destinations', 'photo-1573843981267-be1999ff37cd',
            'Dry season, shoulder months and the transfer choice that makes the biggest difference to your budget.',
            "<h2>Weather</h2><p>November to April is the dry season with calm seas. May to October brings more rain but also lower prices and excellent manta ray season on the western atolls.</p><h2>Save on transfers</h2><p>Seaplane transfers can cost USD 600–900 per person. Resorts in North and South Malé Atoll are reachable by speedboat in under an hour for a fraction of that.</p><h2>All-inclusive or half board?</h2><p>Food and drink on resort islands are expensive. For stays of four nights or more, all-inclusive almost always works out cheaper.</p>"],
    ];
    foreach ($posts as $i => [$title, $cat, $img, $excerpt, $body]) {
        insert('posts', [
            'slug' => slugify($title), 'title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'cover_image' => seed_img($img), 'category' => $cat,
            'author' => 'OK Travel team', 'status' => 'published', 'published_at' => date('Y-m-d H:i:s', strtotime("-$i weeks")), 'views' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /* --------------------------------------------------- reviews (samples) */
    $reviews = [
        ['Ahmed K.', 'Premium Umrah, March 2026', 'Sharjah, UAE', 'The hotel in Makkah was a few minutes from the Haram and the team checked on us every day. My parents were comfortable the whole time.'],
        ['Sara & Omar', 'Maldives honeymoon', 'Dubai, UAE', 'They decorated our villa and arranged a beach dinner without us asking. Every transfer was on time.'],
        ['Rajesh M.', 'Swiss Alps Grand Tour', 'Abu Dhabi, UAE', 'Visa, flights, Swiss Pass and hotels all handled. It cost less than booking it myself, and the itinerary was well paced.'],
        ['Fatima Z.', 'Istanbul & Cappadocia', 'Karachi, Pakistan', 'The balloon flight was the best morning of the trip. Whenever we had a question, someone replied on WhatsApp within minutes.'],
        ['Michael B.', 'Dubai Luxury Escape', 'London, UK', 'Professional from start to finish. They found me a last-minute fare that was much cheaper than the airline website.'],
    ];
    foreach ($reviews as $i => [$name, $trip, $loc, $body]) {
        insert('testimonials', ['name' => $name, 'trip' => $trip, 'location' => $loc, 'rating' => 5, 'body' => $body, 'active' => 1, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now]);
    }

    /* ----------------------------------------------------------------- FAQs */
    $faqs = [
        ['Booking', 'How do I book a package or flight?', 'Send a booking request from the package page or message us on WhatsApp. A consultant confirms availability and the final price, then sends a quotation. Your booking is confirmed once the deposit is received.'],
        ['Booking', 'Do I pay anything when I send a request?', 'No. Requests are free and without obligation. You only pay once you have approved the quotation.'],
        ['Payments', 'How can I pay?', 'Bank transfer, debit or credit card, or cash at our office. Instalments are available on selected holiday and Umrah packages.'],
        ['Flights', 'Are the flight prices on the website live?', 'When a live fare connection is active, prices come from airline systems. Fares change quickly, so we always re-confirm the price before you pay.'],
        ['Visas', 'Can you help with visas?', 'Yes. We handle tourist, visit and business visas for the UAE, Saudi Arabia (including Umrah), Schengen countries, the UK, Türkiye and many more.'],
        ['Booking', 'Can I change a package?', 'Yes. Every package can be adjusted: hotel category, dates, number of nights, activities and flights. Tell us what you would like and we will re-quote.'],
        ['Payments', 'What is your cancellation policy?', 'It depends on the airline and hotel rules for your booking. We explain the exact terms in your quotation before you pay, and help you with any changes.'],
        ['Booking', 'How do I follow my request?', 'Use the reference number from your confirmation email on the Track page, or simply message us on WhatsApp with the reference.'],
    ];
    foreach ($faqs as $i => [$cat, $qq, $a]) {
        insert('faqs', ['question' => $qq, 'answer' => $a, 'category' => $cat, 'active' => 1, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now]);
    }

    /* ---------------------------------------------------------------- pages */
    $site = 'OK For Travel and Tourism Co.';
    $pages = [
        ['about', 'About us', 0, '<p>' . $site . ' is a travel and tourism company that plans flights, holidays, Umrah and visas for families, couples and companies.</p><p>We started with a simple idea: travel should feel OK at every step. That means clear prices before you pay, a real consultant who answers on WhatsApp, and someone to call if anything changes while you are away.</p><h2>What we do</h2><ul><li>Flight tickets on hundreds of airlines</li><li>Holiday packages with hotels, transfers and tours</li><li>Umrah and Hajj packages with visas and hotels close to the Haram</li><li>Visa applications for the UAE, Saudi Arabia, Schengen, the UK and more</li><li>Hotel bookings and travel insurance</li></ul>'],
        ['privacy-policy', 'Privacy policy', 1, '<p>This policy explains how ' . $site . ' collects and uses your personal information.</p><h2>What we collect</h2><p>When you send a request, we collect your name, contact details and trip details. We also collect basic, anonymous statistics about how the website is used.</p><h2>How we use it</h2><p>We use your information only to answer your request, arrange your booking and, if you subscribe, send you offers. We share it only with the airlines, hotels and authorities needed to complete your booking.</p><h2>Your choices</h2><p>You can ask us at any time to see, correct or delete your information, or to stop sending you offers, by emailing us.</p>'],
        ['terms-and-conditions', 'Terms and conditions', 2, '<p>These terms apply to bookings made with ' . $site . '.</p><h2>Prices</h2><p>Prices on the website are per person unless stated otherwise and are subject to availability. The price in your written quotation is final once your deposit is received.</p><h2>Payments</h2><p>A deposit confirms your booking. The balance is due on the date stated in your quotation.</p><h2>Changes and cancellations</h2><p>Changes and cancellations follow the rules of the airlines, hotels and suppliers involved. We explain these rules in your quotation.</p><h2>Travel documents</h2><p>You are responsible for holding a valid passport and the visas required for your trip. We are happy to help you apply.</p>'],
        ['refund-policy', 'Refund policy', 3, '<p>Refunds depend on the rules of the airline, hotel or supplier for each part of your booking. Non-refundable fares and rates are clearly marked in your quotation.</p><p>Where a refund is due, we process it within 7 working days of receiving it from the supplier, using the original payment method. Visa fees paid to governments are not refundable once submitted.</p>'],
    ];
    foreach ($pages as [$slug, $title, $order, $body]) {
        insert('pages', ['slug' => $slug, 'title' => $title, 'body' => $body, 'active' => 1, 'show_in_footer' => $slug === 'about' ? 0 : 1, 'sort_order' => $order, 'created_at' => $now, 'updated_at' => $now]);
    }
}
