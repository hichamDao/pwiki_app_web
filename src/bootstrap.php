<?php
/**
 * Pkiwi App - Bootstrap
 *
 * Main entry point for the application
 */

// Start session (user authentication)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Pkiwi\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($class, $prefix, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load configuration
$config = require __DIR__ . '/Config/app.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Allow enough time to aggregate multiple Matomo API calls
ini_set('max_execution_time', '300');

// Error handling
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../logs/app.log');
}

// Initialize database if configured
$db = null;
if (!empty($config['database']['host'])) {
    try {
        $db = \Pkiwi\Database\Connection::getInstance($config['database']);
        \Pkiwi\Modules\Installer::ensureInstalled($db);
    } catch (\Exception $e) {
        error_log('Database connection failed: ' . $e->getMessage());
    }
}

// Initialize Matomo client
$matomo = new \Pkiwi\Api\MatomoClient(
    $config['matomo']['base_url'],
    $config['matomo']['api_token'],
    $config['matomo']['site_id'],
    $config['matomo']['timeout']
);

// Initialize modules
$geolocation = new \Pkiwi\Modules\Geolocation($matomo, $db, $config['geolocation']['cache_ttl']);
$visits = new \Pkiwi\Modules\Visits($matomo);
$tracker = new \Pkiwi\Modules\Tracker($matomo, (string)$config['matomo']['site_id'], $config['matomo']['base_url']);
$users = new \Pkiwi\Modules\Users($db);
$sites = new \Pkiwi\Modules\Sites($db, $matomo);

// Make available globally
$container = [
    'config' => $config,
    'db' => $db,
    'matomo' => $matomo,
    'geolocation' => $geolocation,
    'visits' => $visits,
    'tracker' => $tracker,
    'users' => $users,
    'sites' => $sites,
];

return $container;