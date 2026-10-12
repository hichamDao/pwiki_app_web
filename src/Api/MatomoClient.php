<?php
namespace Pkiwi\Api;

class MatomoClient
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $apiToken;
    /** @var int */
    private $siteId;
    /** @var int */
    private $timeout;
    /** @var array */
    private $defaultParams;
    /** @var array */
    private $requestCache = [];
    /** @var string */
    private $cacheDir;
    /** @var int */
    private $cacheTtl = 120;
    /** @var int */
    private $liveCacheTtl = 30;
    /** @var array */
    private $cacheablePrefixes = [
        'VisitsSummary.',
        'Actions.',
        'UserCountry.',
        'Referrers.',
        'DevicesDetection.',
        'Live.',
    ];

    /**
     * @param string $baseUrl
     * @param string $apiToken
     * @param int $siteId
     * @param int $timeout
     */
    public function __construct($baseUrl, $apiToken, $siteId, $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = $apiToken;
        $this->siteId = $siteId;
        $this->timeout = $timeout;
        $this->defaultParams = [
            'module' => 'API',
            'format' => 'JSON',
            'token_auth' => $this->apiToken,
            'idSite' => $this->siteId,
        ];

        $this->setCacheDirForSite($this->siteId);
    }

    public function getApiToken()
    {
        return $this->apiToken;
    }

    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * Change the site id used for subsequent API calls
     *
     * @param int $siteId
     * @return $this
     */
    public function setSiteId($siteId)
    {
        $this->siteId = (int)$siteId;
        $this->defaultParams['idSite'] = $this->siteId;
        $this->requestCache = [];
        $this->setCacheDirForSite($this->siteId);
        return $this;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $method
     * @param array $params
     * @return array
     */
    public function call($method, array $params = [])
    {
        $query = array_merge($this->defaultParams, [
            'method' => $method,
        ], $params);

        $cacheKey = md5(serialize($query));
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        $ttl = 0;
        if ($this->isCacheableMethod($method)) {
            $ttl = $this->isLiveMethod($method) ? $this->liveCacheTtl : $this->cacheTtl;
        }

        if ($ttl > 0) {
            $cached = $this->readCache($cacheKey, $ttl);
            if ($cached !== null) {
                $this->requestCache[$cacheKey] = $cached;
                return $cached;
            }
        }

        $url = $this->baseUrl . '/index.php?' . http_build_query($query);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => [
                    'Accept: application/json',
                    'User-Agent: Pkiwi-App/1.0',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        // The Matomo dev server is single-threaded and may drop connections
        // under rapid sequential requests; retry transient failures.
        $attempts = 0;
        while ($response === false && $attempts < 3) {
            $attempts++;
            usleep(500000 * $attempts);
            $response = @file_get_contents($url, false, $context);
        }

        if ($response === false) {
            throw new \RuntimeException("Matomo API request failed: {$url}");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Matomo API returned invalid JSON: ' . json_last_error_msg());
        }

        if (isset($data['result']) && $data['result'] === 'error') {
            throw new \RuntimeException('Matomo API error: ' . ($data['message'] ?? 'Unknown error'));
        }

        $this->requestCache[$cacheKey] = $data;

        if ($ttl > 0) {
            $this->writeCache($cacheKey, $data);
        }

        return $data;
    }

    /**
     * @param string $method
     * @return bool
     */
    private function isCacheableMethod($method)
    {
        foreach ($this->cacheablePrefixes as $prefix) {
            if (strpos($method, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $method
     * @return bool
     */
    private function isLiveMethod($method)
    {
        return strpos($method, 'Live.') === 0;
    }

    /**
     * @param int $siteId
     */
    private function setCacheDirForSite($siteId)
    {
        $this->cacheDir = __DIR__ . '/../../tmp/cache/site' . (int)$siteId;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * @param string $key
     * @param int $ttl
     * @return array|null
     */
    private function readCache($key, $ttl)
    {
        $file = $this->cacheDir . '/' . $key . '.json';
        if (!is_file($file)) {
            return null;
        }
        if ((time() - filemtime($file)) > $ttl) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * @param string $key
     * @param array $data
     */
    private function writeCache($key, array $data)
    {
        $file = $this->cacheDir . '/' . $key . '.json';
        @file_put_contents($file, json_encode($data));
    }

    /**
     * Drop the cached API responses for the current site
     */
    public function clearSiteCache()
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }
        foreach (glob($this->cacheDir . '/*.json') as $file) {
            @unlink($file);
        }
    }

    // Visits Summary
    public function getVisitsSummary($period = 'day', $date = 'today')
    {
        return $this->call('VisitsSummary.get', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    // Live visits
    public function getLastVisits($limit = 10, $date = 'today', $period = 'day')
    {
        return $this->call('Live.getLastVisitsDetails', [
            'filter_limit' => $limit,
            'date' => $date,
            'period' => $period,
        ]);
    }

    // Geolocation
    public function getCountries($period = 'day', $date = 'today')
    {
        return $this->call('UserCountry.getCountry', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    public function getRegions($countryCode, $period = 'day', $date = 'today')
    {
        return $this->call('UserCountry.getRegion', [
            'period' => $period,
            'date' => $date,
            'segment' => 'countryCode==' . $countryCode,
        ]);
    }

    public function getCities($countryCode, $regionCode, $period = 'day', $date = 'today')
    {
        return $this->call('UserCountry.getCity', [
            'period' => $period,
            'date' => $date,
            'segment' => 'countryCode==' . $countryCode . ',regionCode==' . $regionCode,
        ]);
    }

    // Page URLs
    public function getPageUrls($period = 'day', $date = 'today', $limit = 50)
    {
        return $this->call('Actions.getPageUrls', [
            'period' => $period,
            'date' => $date,
            'filter_limit' => $limit,
        ]);
    }

    // Site Search Keywords
    public function getSearchKeywords($period = 'day', $date = 'today', $limit = 50)
    {
        return $this->call('Actions.getSiteSearchKeywords', [
            'period' => $period,
            'date' => $date,
            'filter_limit' => $limit,
        ]);
    }

    // Referrers
    public function getReferrers($period = 'day', $date = 'today')
    {
        return $this->call('Referrers.getReferrerType', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    // Devices
    public function getDevices($period = 'day', $date = 'today')
    {
        return $this->call('DevicesDetection.getType', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    // Browsers
    public function getBrowsers($period = 'day', $date = 'today')
    {
        return $this->call('DevicesDetection.getBrowsers', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    // OS
    public function getOs($period = 'day', $date = 'today')
    {
        return $this->call('DevicesDetection.getOsFamilies', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    // Evolution / Trends
    public function getVisitsEvolution($period = 'day', $date = 'today')
    {
        return $this->call('VisitsSummary.get', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    public function getActionsEvolution($period = 'day', $date = 'today')
    {
        return $this->call('VisitsSummary.get', [
            'period' => $period,
            'date' => $date,
        ]);
    }

    // SitesManager: register a new website on the analytics server
    public function addSite($name, $urls, array $options = [])
    {
        $params = [
            'siteName' => $name,
            'urls' => is_array($urls) ? implode(',', $urls) : $urls,
        ];

        $result = $this->api('SitesManager.addSite', array_merge($params, $options));

        // Matomo wraps the new site id in a "value" key
        if (is_array($result) && isset($result['value'])) {
            return $result['value'];
        }

        return $result;
    }

    // Custom segment
    public function withSegment($segment)
    {
        $clone = clone $this;
        $clone->defaultParams['segment'] = $segment;
        return $clone;
    }

    // Raw API access for any method
    public function api($method, array $params = [])
    {
        return $this->call($method, $params);
    }
}
