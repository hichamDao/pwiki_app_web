<?php
namespace Pkiwi\Modules;

use Pkiwi\Api\MatomoClient;

class Tracker
{
    /** @var MatomoClient */
    private $matomo;
    /** @var string */
    private $siteId;
    /** @var string */
    private $baseUrl;

    /**
     * @param MatomoClient $matomo
     * @param string $siteId
     * @param string $baseUrl
     */
    public function __construct(MatomoClient $matomo, $siteId, $baseUrl)
    {
        $this->matomo = $matomo;
        $this->siteId = $siteId;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Resolve the site id from the shared Matomo client
     * (so per-user site selection applies to tracking too)
     *
     * @return string
     */
    private function getSiteId()
    {
        $clientSiteId = $this->matomo->getSiteId();
        if (!empty($clientSiteId)) {
            return (string)$clientSiteId;
        }
        return $this->siteId;
    }

    /**
     * Track a page view
     *
     * @param array $data
     * @return bool
     */
    public function trackPageView(array $data)
    {
        $params = [
            'idsite' => $this->getSiteId(),
            'rec' => 1,
            'action_name' => $data['title'] ?? 'Page View',
            'url' => $data['url'] ?? '',
            'rand' => mt_rand(),
        ];

        // Optional parameters
        if (!empty($data['ip'])) {
            $params['cip'] = $data['ip'];
        }
        if (!empty($data['user_id'])) {
            $params['uid'] = $data['user_id'];
        }
        if (!empty($data['referrer_url'])) {
            $params['urlref'] = $data['referrer_url'];
        }
        if (!empty($data['custom_vars'])) {
            foreach ($data['custom_vars'] as $index => $var) {
                $params["_cvar{$index}"] = json_encode([$var['name'], $var['value']]);
            }
        }
        if (!empty($data['dimension'])) {
            foreach ($data['dimension'] as $index => $value) {
                $params["dimension{$index}"] = $value;
            }
        }
        if (!empty($data['search_keyword'])) {
            $params['search'] = $data['search_keyword'];
        }
        if (!empty($data['search_category'])) {
            $params['search_cat'] = $data['search_category'];
        }
        if (!empty($data['search_count'])) {
            $params['search_count'] = $data['search_count'];
        }
        if (!empty($data['generation_time_ms'])) {
            $params['gt_ms'] = $data['generation_time_ms'];
        }

        return $this->sendTracking($params);
    }

    /**
     * Track an event
     *
     * @param array $data
     * @return bool
     */
    public function trackEvent(array $data)
    {
        $params = [
            'idsite' => $this->getSiteId(),
            'rec' => 1,
            'e_c' => $data['category'] ?? 'Event',
            'e_a' => $data['action'] ?? 'Action',
            'e_n' => $data['name'] ?? '',
            'e_v' => $data['value'] ?? 0,
            'rand' => mt_rand(),
        ];

        if (!empty($data['ip'])) {
            $params['cip'] = $data['ip'];
        }
        if (!empty($data['user_id'])) {
            $params['uid'] = $data['user_id'];
        }
        if (!empty($data['url'])) {
            $params['url'] = $data['url'];
        }

        return $this->sendTracking($params);
    }

    /**
     * Track a site search
     *
     * @param array $data
     * @return bool
     */
    public function trackSiteSearch(array $data)
    {
        $params = [
            'idsite' => $this->getSiteId(),
            'rec' => 1,
            'action_name' => 'Search: ' . ($data['keyword'] ?? ''),
            'url' => $data['url'] ?? '',
            'search' => $data['keyword'] ?? '',
            'search_cat' => $data['category'] ?? '',
            'search_count' => $data['count'] ?? 0,
            'rand' => mt_rand(),
        ];

        if (!empty($data['ip'])) {
            $params['cip'] = $data['ip'];
        }
        if (!empty($data['user_id'])) {
            $params['uid'] = $data['user_id'];
        }

        return $this->sendTracking($params);
    }

    /**
     * Track an ecommerce order
     *
     * @param array $data
     * @return bool
     */
    public function trackOrder(array $data)
    {
        $params = [
            'idsite' => $this->getSiteId(),
            'rec' => 1,
            'idorder' => $data['order_id'] ?? '',
            'revenue' => $data['revenue'] ?? 0,
            'ec_id' => $data['order_id'] ?? '',
            'ec_items' => json_encode($data['items'] ?? []),
            'rand' => mt_rand(),
        ];

        if (!empty($data['ip'])) {
            $params['cip'] = $data['ip'];
        }
        if (!empty($data['user_id'])) {
            $params['uid'] = $data['user_id'];
        }

        return $this->sendTracking($params);
    }

    /**
     * Track a cart update
     *
     * @param array $data
     * @return bool
     */
    public function trackCart(array $data)
    {
        $params = [
            'idsite' => $this->getSiteId(),
            'rec' => 1,
            'e_c' => 'Ecommerce',
            'e_a' => 'CartUpdate',
            'e_n' => '',
            'e_v' => $data['revenue'] ?? 0,
            'rand' => mt_rand(),
        ];

        if (!empty($data['items'])) {
            $params['ec_items'] = json_encode($data['items']);
        }

        if (!empty($data['ip'])) {
            $params['cip'] = $data['ip'];
        }

        return $this->sendTracking($params);
    }

    /**
     * Generate JavaScript tracking code for frontend
     *
     * @param array $options
     * @return string
     */
    public function getTrackingCode(array $options = [])
    {
        $siteId = $options['site_id'] ?? $this->getSiteId();
        $trackerUrl = $options['tracker_url'] ?? $this->baseUrl . '/piwik.php';
        $matomoUrl = $options['matomo_url'] ?? $this->baseUrl;

        $code = <<<JS
<!-- Matomo -->
<script>
    var _paq = window._paq = window._paq || [];
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
        var u="{$matomoUrl}/";
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['setSiteId', '{$siteId}']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true;
        g.src=u+'matomo.js';
        s.parentNode.insertBefore(g,s);
    })();
</script>
<!-- End Matomo Code -->
JS;

        return $code;
    }

    /**
     * Get tracking code with site search detection
     *
     * @param array $options
     * @return string
     */
    public function getTrackingCodeWithSearch(array $options = [])
    {
        $siteId = $options['site_id'] ?? $this->getSiteId();
        $trackerUrl = $options['tracker_url'] ?? $this->baseUrl . '/piwik.php';
        $matomoUrl = $options['matomo_url'] ?? $this->baseUrl;
        $searchParam = $options['search_param'] ?? 'motCle';

        $code = <<<JS
<!-- Matomo -->
<script>
    var _paq = window._paq = window._paq || [];
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
        var u="{$matomoUrl}/";
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['setSiteId', '{$siteId}']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true;
        g.src=u+'matomo.js';
        s.parentNode.insertBefore(g,s);
    })();

    // Detect site search via URL parameter
    (function() {
        var match = location.search.match(/[?&]{$searchParam}=([^&]+)/);
        if (match && match[1]) {
            var keyword = decodeURIComponent(match[1].replace(/\+/g, ' '));
            _paq.push(['trackSiteSearch', keyword]);
        }
    })();
</script>
<!-- End Matomo Code -->
JS;

        return $code;
    }

    /**
     * @param array $params
     * @return bool
     */
    private function sendTracking(array $params)
    {
        // Add token_auth for authentication
        $params['token_auth'] = $this->matomo->getApiToken();

        $query = http_build_query($params);
        $url = $this->baseUrl . '/piwik.php?' . $query;

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => [
                    'Accept: image/gif',
                    'User-Agent: Pkiwi-Tracker/1.0',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        // Retry transient connection failures (single-threaded Matomo dev server)
        $attempts = 0;
        while ($response === false && $attempts < 3) {
            $attempts++;
            usleep(500000 * $attempts);
            $response = @file_get_contents($url, false, $context);
        }

        if ($response === false) {
            error_log("Tracking failed: {$url}");
            return false;
        }

        // Matomo returns a 1x1 GIF
        return strlen($response) > 0;
    }
}