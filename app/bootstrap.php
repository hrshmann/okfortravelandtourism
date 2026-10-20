<?php
declare(strict_types=1);

const APP_VERSION = '2.0.0';
define('ROOT', dirname(__DIR__));
define('APP', __DIR__);
define('STORAGE', ROOT . '/storage');
define('UPLOADS', ROOT . '/uploads');
define('CONFIG_FILE', APP . '/config.php');

mb_internal_encoding('UTF-8');

require APP . '/lib/helpers.php';
require APP . '/lib/db.php';
require APP . '/lib/schema.php';
require APP . '/lib/settings.php';
require APP . '/lib/currency.php';
require APP . '/lib/auth.php';
require APP . '/lib/view.php';
require APP . '/lib/html.php';
require APP . '/lib/mailer.php';
require APP . '/lib/upload.php';
require APP . '/lib/flights.php';
require APP . '/lib/leads.php';
require APP . '/lib/resources.php';
require APP . '/lib/router.php';
// Order matters: site.php ends with the catch-all "/{slug}" route for CMS pages.
require APP . '/routes/install.php';
require APP . '/routes/api.php';
require APP . '/routes/admin.php';
require APP . '/routes/site.php';

$GLOBALS['CONFIG'] = is_file(CONFIG_FILE) ? require CONFIG_FILE : null;

// Errors: show them while developing, log them quietly in production.
$debug = (bool) (config('debug') ?? false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE . '/logs/php-error.log');

set_exception_handler(function (Throwable $e) use ($debug) {
    error_log('[' . date('c') . '] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (is_api_request()) {
        json_out(['error' => $debug ? $e->getMessage() : 'Something went wrong on our side. Please try again.'], 500);
    }
    if ($debug) {
        echo '<pre style="padding:20px;font:14px/1.5 monospace;white-space:pre-wrap">' . e((string) $e) . '</pre>';
        exit;
    }
    $file = APP . '/views/site/500.php';
    include $file;
    exit;
});

date_default_timezone_set((string) (config('timezone') ?: 'Asia/Dubai'));
