<?php
/**
 * OK Travel & Tourism — front controller.
 * Every request that is not a real file is routed through here (see .htaccess).
 */
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (preg_match('#^/(app|storage)(/|$)#', $path) || preg_match('#/\.#', $path)) {
        http_response_code(403);
        exit('Forbidden');
    }
    if ($path !== '/' && is_file(__DIR__ . $path) && !preg_match('/\.php$/', $path)) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';
app_run();
