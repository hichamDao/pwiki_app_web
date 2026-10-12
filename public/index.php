<?php
/**
 * Pkiwi App - Front controller
 *
 * User registration / login, website management and dashboard.
 * All analytics data is processed on the analytics (Matomo) server
 * and fetched through its API endpoints.
 */

$container = require __DIR__ . '/../src/bootstrap.php';
$config = $container['config'];
$matomo = $container['matomo'];
$geolocation = $container['geolocation'];
$visits = $container['visits'];
$tracker = $container['tracker'];
$users = $container['users'];
$sites = $container['sites'];

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$period = isset($_GET['period']) ? $_GET['period'] : 'day';
$date = isset($_GET['date']) ? $_GET['date'] : 'today';

/**
 * Redirect helper
 */
function pkiwi_redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * CSRF token
 */
function pkiwi_csrf_token()
{
    if (empty($_SESSION['pkiwi_csrf'])) {
        $_SESSION['pkiwi_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['pkiwi_csrf'];
}

function pkiwi_csrf_check()
{
    $token = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    return $token !== '' && hash_equals($_SESSION['pkiwi_csrf'], $token);
}

/**
 * Resolve the website the current user is viewing
 */
function pkiwi_selected_site($user, $sitesModule, $matomo)
{
    $userSites = $sitesModule->getSites($user['id']);

    if (empty($userSites)) {
        return ['site' => null, 'sites' => []];
    }

    $selected = null;
    if (isset($_GET['site'])) {
        $selected = $sitesModule->getSite($user['id'], (int)$_GET['site']);
    }
    if (!$selected && !empty($_SESSION['pkiwi_site_id'])) {
        $selected = $sitesModule->getSite($user['id'], (int)$_SESSION['pkiwi_site_id']);
    }
    if (!$selected) {
        $selected = $userSites[0];
    }

    $_SESSION['pkiwi_site_id'] = (int)$selected['id'];
    $matomo->setSiteId((int)$selected['matomo_site_id']);

    return ['site' => $selected, 'sites' => $userSites];
}

// -----------------------------------------------------------------
// Public actions: login / register / logout
// -----------------------------------------------------------------
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!pkiwi_csrf_check()) {
            $error = 'Invalid form token.';
        } else {
            $result = $users->login(
                isset($_POST['login']) ? $_POST['login'] : '',
                isset($_POST['password']) ? $_POST['password'] : ''
            );
            if ($result['ok']) {
                $users->setSession($result['user']);
                pkiwi_redirect('?action=dashboard');
            }
            $error = $result['error'];
        }
    }
    include __DIR__ . '/../templates/login.php';
    exit;
}

if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!pkiwi_csrf_check()) {
            $error = 'Invalid form token.';
        } else {
            $result = $users->register(
                isset($_POST['username']) ? $_POST['username'] : '',
                isset($_POST['email']) ? $_POST['email'] : '',
                isset($_POST['password']) ? $_POST['password'] : ''
            );
            if ($result['ok']) {
                $users->setSession($result['user']);
                pkiwi_redirect('?action=dashboard');
            }
            $error = $result['error'];
        }
    }
    include __DIR__ . '/../templates/register.php';
    exit;
}

if ($action === 'logout') {
    $users->logout();
    pkiwi_redirect('?action=login');
}

// -----------------------------------------------------------------
// Everything below requires authentication
// -----------------------------------------------------------------
$user = $users->getCurrentUser();
if (!$user) {
    pkiwi_redirect('?action=login');
}

$selection = pkiwi_selected_site($user, $sites, $matomo);
$currentSite = $selection['site'];
$userSites = $selection['sites'];

// -----------------------------------------------------------------
// AJAX endpoints (authenticated, scoped to the selected site)
// -----------------------------------------------------------------
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    switch ($_GET['ajax']) {
        case 'countries':
            echo json_encode($geolocation->getCountries($period, $date));
            exit;
        case 'country_details':
            $code = isset($_GET['code']) ? $_GET['code'] : '';
            echo json_encode($geolocation->getCountryDetails($code, $period, $date));
            exit;
        case 'world_map':
            echo json_encode($geolocation->getWorldMapData($period, $date));
            exit;
        case 'location_summary':
            echo json_encode($geolocation->getLocationSummary($period, $date));
            exit;
        case 'live_visits':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            echo json_encode($visits->getLiveVisitsFormatted($limit, $date, $period));
            exit;
        case 'visit_detail':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            echo json_encode($visits->getVisitDetails($id));
            exit;
        case 'search_keywords':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            echo json_encode($visits->getSearchKeywords($period, $date, $limit));
            exit;
        case 'page_views':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            echo json_encode($visits->getPageViews($period, $date, $limit));
            exit;
        case 'referrers':
            echo json_encode($visits->getReferrers($period, $date));
            exit;
        case 'devices':
            echo json_encode($visits->getDevices($period, $date));
            exit;
        case 'browsers':
            echo json_encode($visits->getBrowsers($period, $date));
            exit;
        case 'os':
            echo json_encode($visits->getOs($period, $date));
            exit;
        case 'dashboard':
            echo json_encode($visits->getDashboardData($period, $date));
            exit;
        case 'my_sites':
            echo json_encode($userSites);
            exit;
        case 'tracking_code':
            $searchParam = isset($_GET['search']) ? $_GET['search'] : 'motCle';
            $options = ['site_id' => (string)$matomo->getSiteId(), 'search_param' => $searchParam];
            echo json_encode(['code' => $tracker->getTrackingCodeWithSearch($options)]);
            exit;
        case 'test_visit':
            // Send a test visit so the user sees data immediately
            // (unique demo IP so each test creates a distinct visit)
            $result = $tracker->trackPageView([
                'title' => 'Test visit - ' . ($currentSite ? $currentSite['name'] : 'site'),
                'url' => $currentSite ? $currentSite['url'] : 'https://example.com/',
                'ip' => '203.0.113.' . mt_rand(1, 254),
            ]);
            if ($result) {
                // New visit invalidates the API cache so it shows up immediately
                $matomo->clearSiteCache();
            }
            echo json_encode(['success' => $result]);
            exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Unknown ajax action']);
    exit;
}

// -----------------------------------------------------------------
// Website management actions
// -----------------------------------------------------------------
$message = '';
$error = '';

if ($action === 'add_site' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pkiwi_csrf_check()) {
        $error = 'Invalid form token.';
    } else {
        $result = $sites->addSite(
            $user['id'],
            isset($_POST['name']) ? $_POST['name'] : '',
            isset($_POST['url']) ? $_POST['url'] : ''
        );
        if ($result['ok']) {
            $message = 'Website added. The analytics server is now ready to track it - copy the tracking code below into your site.';
            pkiwi_redirect('?action=dashboard&site=' . $result['site']['id'] . '&added=1');
        }
        $error = $result['error'];
    }
}

if ($action === 'delete_site' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pkiwi_csrf_check()) {
        $error = 'Invalid form token.';
    } else {
        $sites->removeSite($user['id'], isset($_POST['id']) ? (int)$_POST['id'] : 0);
        $_SESSION['pkiwi_site_id'] = null;
        pkiwi_redirect('?action=dashboard');
    }
}

if ($action === 'tracking_code') {
    include __DIR__ . '/../templates/tracking_code.php';
    exit;
}

// -----------------------------------------------------------------
// Dashboard
// -----------------------------------------------------------------
if ($currentSite) {
    $dashboard = $visits->getDashboardData($period, $date);
    $geoSummary = $geolocation->getLocationSummary($period, $date);
} else {
    $dashboard = null;
    $geoSummary = null;
}

include __DIR__ . '/../templates/dashboard.php';
