<?php
declare(strict_types=1);

const SCHEMA_VERSION = 1;

/** Table definitions written once and adapted to MySQL or SQLite. */
function schema_tables(): array
{
    $ts = 'created_at DATETIME NOT NULL, updated_at DATETIME NULL';
    $seo = 'seo_title VARCHAR(190) NULL, seo_description VARCHAR(320) NULL';
    return [
        'users' => "id {PK}, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'admin', active TINYINT NOT NULL DEFAULT 1, reset_token VARCHAR(128) NULL,
            reset_expires DATETIME NULL, last_login_at DATETIME NULL, $ts, UNIQUE (email)",
        'settings' => "name VARCHAR(64) NOT NULL PRIMARY KEY, value {TEXT} NULL",
        'destinations' => "id {PK}, slug VARCHAR(190) NOT NULL, name VARCHAR(160) NOT NULL, country VARCHAR(120) NULL,
            region VARCHAR(80) NULL, airport_code VARCHAR(3) NULL, tagline VARCHAR(255) NULL, description {TEXT} NULL,
            best_time VARCHAR(160) NULL, cover_image VARCHAR(500) NULL, featured TINYINT NOT NULL DEFAULT 0,
            active TINYINT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, $seo, $ts, UNIQUE (slug)",
        'packages' => "id {PK}, slug VARCHAR(190) NOT NULL, title VARCHAR(190) NOT NULL, destination_id INT NULL,
            location VARCHAR(190) NULL, airport_code VARCHAR(3) NULL, category VARCHAR(80) NULL, days INT NOT NULL DEFAULT 1,
            nights INT NOT NULL DEFAULT 0, price DECIMAL(12,2) NOT NULL DEFAULT 0, old_price DECIMAL(12,2) NULL,
            price_note VARCHAR(160) NULL, rating DECIMAL(3,1) NULL, badge VARCHAR(60) NULL, board_status VARCHAR(30) NULL,
            summary VARCHAR(400) NULL, description {TEXT} NULL, highlights {TEXT} NULL, inclusions {TEXT} NULL,
            exclusions {TEXT} NULL, itinerary {TEXT} NULL, gallery {TEXT} NULL, cover_image VARCHAR(500) NULL,
            featured TINYINT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0,
            views INT NOT NULL DEFAULT 0, $seo, $ts, UNIQUE (slug)",
        'visas' => "id {PK}, slug VARCHAR(190) NOT NULL, country VARCHAR(120) NOT NULL, visa_type VARCHAR(120) NULL,
            processing_time VARCHAR(80) NULL, validity VARCHAR(80) NULL, stay VARCHAR(80) NULL, entry VARCHAR(40) NULL,
            price DECIMAL(12,2) NULL, summary VARCHAR(400) NULL, requirements {TEXT} NULL, description {TEXT} NULL,
            cover_image VARCHAR(500) NULL, active TINYINT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, $seo, $ts, UNIQUE (slug)",
        'posts' => "id {PK}, slug VARCHAR(190) NOT NULL, title VARCHAR(190) NOT NULL, excerpt VARCHAR(400) NULL, body {TEXT} NULL,
            cover_image VARCHAR(500) NULL, category VARCHAR(80) NULL, author VARCHAR(120) NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft',
            published_at DATETIME NULL, views INT NOT NULL DEFAULT 0, $seo, $ts, UNIQUE (slug)",
        'testimonials' => "id {PK}, name VARCHAR(120) NOT NULL, trip VARCHAR(160) NULL, location VARCHAR(120) NULL,
            rating INT NOT NULL DEFAULT 5, body {TEXT} NOT NULL, photo VARCHAR(500) NULL, active TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0, $ts",
        'faqs' => "id {PK}, question VARCHAR(255) NOT NULL, answer {TEXT} NOT NULL, category VARCHAR(80) NULL,
            active TINYINT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, $ts",
        'pages' => "id {PK}, slug VARCHAR(190) NOT NULL, title VARCHAR(190) NOT NULL, body {TEXT} NULL, active TINYINT NOT NULL DEFAULT 1,
            show_in_footer TINYINT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, $seo, $ts, UNIQUE (slug)",
        'leads' => "id {PK}, ref VARCHAR(20) NOT NULL, type VARCHAR(30) NOT NULL DEFAULT 'general', status VARCHAR(20) NOT NULL DEFAULT 'new',
            channel VARCHAR(20) NOT NULL DEFAULT 'form', name VARCHAR(160) NULL, email VARCHAR(190) NULL, phone VARCHAR(60) NULL,
            subject VARCHAR(255) NULL, package_id INT NULL, travel_date DATE NULL, return_date DATE NULL,
            adults INT NOT NULL DEFAULT 1, children INT NOT NULL DEFAULT 0, infants INT NOT NULL DEFAULT 0, budget VARCHAR(80) NULL,
            message {TEXT} NULL, details {TEXT} NULL, quoted_amount DECIMAL(12,2) NULL, paid_amount DECIMAL(12,2) NULL,
            currency VARCHAR(3) NULL, assigned_to INT NULL, follow_up_at DATETIME NULL, source_page VARCHAR(255) NULL,
            utm {TEXT} NULL, ip VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, $ts, UNIQUE (ref)",
        'lead_notes' => "id {PK}, lead_id INT NOT NULL, user_id INT NULL, kind VARCHAR(20) NOT NULL DEFAULT 'note', body {TEXT} NOT NULL, created_at DATETIME NOT NULL",
        'subscribers' => "id {PK}, email VARCHAR(190) NULL, phone VARCHAR(60) NULL, name VARCHAR(120) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active', source VARCHAR(60) NULL, created_at DATETIME NOT NULL",
        'media' => "id {PK}, path VARCHAR(500) NOT NULL, original_name VARCHAR(255) NULL, mime VARCHAR(60) NULL, size INT NULL,
            width INT NULL, height INT NULL, alt VARCHAR(255) NULL, created_at DATETIME NOT NULL",
        'activity_log' => "id {PK}, user_id INT NULL, action VARCHAR(60) NOT NULL, entity VARCHAR(60) NULL, entity_id INT NULL,
            summary VARCHAR(255) NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL",
        'login_attempts' => "id {PK}, ip VARCHAR(45) NOT NULL, email VARCHAR(190) NULL, created_at DATETIME NOT NULL",
        'visits' => "id {PK}, day DATE NOT NULL, path VARCHAR(190) NOT NULL, hits INT NOT NULL DEFAULT 0, UNIQUE (day, path)",
    ];
}

function schema_indexes(): array
{
    return [
        'CREATE INDEX idx_packages_active ON packages (active, sort_order)',
        'CREATE INDEX idx_packages_dest ON packages (destination_id)',
        'CREATE INDEX idx_posts_status ON posts (status, published_at)',
        'CREATE INDEX idx_leads_status ON leads (status, created_at)',
        'CREATE INDEX idx_leads_email ON leads (email)',
        'CREATE INDEX idx_lead_notes_lead ON lead_notes (lead_id)',
        'CREATE INDEX idx_login_attempts ON login_attempts (ip, created_at)',
        'CREATE INDEX idx_activity_created ON activity_log (created_at)',
    ];
}

function schema_statements(string $driver): array
{
    $out = [];
    foreach (schema_tables() as $table => $cols) {
        if ($driver === 'sqlite') {
            $cols = str_replace(['{PK}', '{TEXT}'], ['INTEGER PRIMARY KEY AUTOINCREMENT', 'TEXT'], $cols);
            $out[] = "CREATE TABLE IF NOT EXISTS $table ($cols)";
        } else {
            $cols = str_replace(['{PK}', '{TEXT}'], ['INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 'MEDIUMTEXT'], $cols);
            $out[] = "CREATE TABLE IF NOT EXISTS $table ($cols) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
    }
    return $out;
}

function schema_install(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    foreach (schema_statements($driver) as $sql) {
        $pdo->exec($sql);
    }
    foreach (schema_indexes() as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Index already exists — safe to ignore on re-install.
        }
    }
}
