<?php
namespace Pkiwi\Modules;

use Pkiwi\Api\MatomoClient;

class Visits
{
    /** @var MatomoClient */
    private $matomo;
    /** @var array */
    private $cache = [];

    /**
     * @param MatomoClient $matomo
     */
    public function __construct(MatomoClient $matomo)
    {
        $this->matomo = $matomo;
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getSummary($period = 'day', $date = 'today')
    {
        $cacheKey = "summary_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($period, $date) {
            return $this->matomo->getVisitsSummary($period, $date);
        });
    }

    /**
     * @param int $limit
     * @param string $date
     * @param string $period
     * @return array
     */
    public function getLiveVisits($limit = 20, $date = 'today', $period = 'day')
    {
        $cacheKey = "live_{$limit}_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($limit, $period, $date) {
            return $this->matomo->getLastVisits($limit, $date, $period);
        });
    }

    /**
     * @param int $limit
     * @param string $date
     * @param string $period
     * @return array
     */
    public function getLiveVisitsFormatted($limit = 20, $date = 'today', $period = 'day')
    {
        $raw = $this->getLiveVisits($limit, $date, $period);
        
        $formatted = [];
        foreach ($raw as $visit) {
            $actions = $visit['actionDetails'] ?? [];
            $firstAction = $actions[0] ?? [];
            $lastAction = end($actions);
            
            $searchKeywords = [];
            foreach ($actions as $action) {
                if (!empty($action['siteSearchKeyword'])) {
                    $searchKeywords[] = $action['siteSearchKeyword'];
                }
            }

            $formatted[] = [
                'id' => $visit['idVisit'] ?? '',
                'ip' => $visit['visitIp'] ?? '',
                'country' => $visit['country'] ?? '',
                'city' => $visit['city'] ?? '',
                'region' => $visit['region'] ?? '',
                'latitude' => $visit['latitude'] ?? null,
                'longitude' => $visit['longitude'] ?? null,
                'visit_time' => $visit['firstActionTimestamp'] ?? '',
                'last_action_time' => $visit['lastActionTimestamp'] ?? '',
                'duration' => $visit['visitDuration'] ?? 0,
                'actions_count' => $visit['actions'] ?? 0,
                'searches' => $visit['searches'] ?? 0,
                'search_keywords' => $searchKeywords,
                'referrer_type' => $visit['referrerType'] ?? '',
                'referrer_name' => $visit['referrerName'] ?? '',
                'entry_page' => $firstAction['url'] ?? '',
                'entry_title' => $firstAction['pageTitle'] ?? '',
                'exit_page' => $lastAction['url'] ?? '',
                'device_type' => $visit['deviceType'] ?? 'Unknown',
                'browser' => $visit['browserName'] ?? 'Unknown',
                'os' => $visit['operatingSystemName'] ?? 'Unknown',
                'is_new_visitor' => ($visit['visitCount'] ?? 0) === 1,
            ];
        }

        return $formatted;
    }

    /**
     * @param string $period
     * @param string $date
     * @param int $limit
     * @return array
     */
    public function getPageViews($period = 'day', $date = 'today', $limit = 50)
    {
        $cacheKey = "pages_{$period}_{$date}_{$limit}";
        return $this->getCached($cacheKey, function () use ($period, $date, $limit) {
            return $this->matomo->getPageUrls($period, $date, $limit);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @param int $limit
     * @return array
     */
    public function getSearchKeywords($period = 'day', $date = 'today', $limit = 50)
    {
        $cacheKey = "keywords_{$period}_{$date}_{$limit}";
        return $this->getCached($cacheKey, function () use ($period, $date, $limit) {
            return $this->matomo->getSearchKeywords($period, $date, $limit);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getReferrers($period = 'day', $date = 'today')
    {
        $cacheKey = "referrers_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($period, $date) {
            return $this->matomo->getReferrers($period, $date);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getDevices($period = 'day', $date = 'today')
    {
        $cacheKey = "devices_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($period, $date) {
            return $this->matomo->getDevices($period, $date);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getBrowsers($period = 'day', $date = 'today')
    {
        $cacheKey = "browsers_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($period, $date) {
            return $this->matomo->getBrowsers($period, $date);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getOs($period = 'day', $date = 'today')
    {
        $cacheKey = "os_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($period, $date) {
            return $this->matomo->getOs($period, $date);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getDashboardData($period = 'day', $date = 'today')
    {
        return [
            'summary' => $this->getSummary($period, $date),
            'live_visits' => $this->getLiveVisitsFormatted(10, $date, $period),
            'top_pages' => $this->getPageViews($period, $date, 10),
            'search_keywords' => $this->getSearchKeywords($period, $date, 10),
            'referrers' => $this->getReferrers($period, $date),
            'devices' => $this->getDevices($period, $date),
            'browsers' => $this->getBrowsers($period, $date),
            'os' => $this->getOs($period, $date),
        ];
    }

    /**
     * @param int $visitId
     * @return array|null
     */
    public function getVisitDetails($visitId)
    {
        $visits = $this->getLiveVisits(100);
        
        foreach ($visits as $visit) {
            if (($visit['idVisit'] ?? '') == $visitId) {
                $actions = $visit['actionDetails'] ?? [];
                
                $actionDetails = [];
                foreach ($actions as $action) {
                    $actionDetails[] = [
                        'time' => $action['serverTimePretty'] ?? '',
                        'timestamp' => $action['timestamp'] ?? '',
                        'url' => $action['url'] ?? '',
                        'title' => $action['pageTitle'] ?? '',
                        'type' => $action['type'] ?? '',
                        'time_spent' => $action['timeSpent'] ?? 0,
                        'search_keyword' => $action['siteSearchKeyword'] ?? null,
                    ];
                }

                return [
                    'id' => $visit['idVisit'] ?? '',
                    'ip' => $visit['visitIp'] ?? '',
                    'visitor_id' => $visit['visitorId'] ?? '',
                    'country' => $visit['country'] ?? '',
                    'city' => $visit['city'] ?? '',
                    'region' => $visit['region'] ?? '',
                    'latitude' => $visit['latitude'] ?? null,
                    'longitude' => $visit['longitude'] ?? null,
                    'visit_time' => $visit['firstActionTimestamp'] ?? '',
                    'last_action_time' => $visit['lastActionTimestamp'] ?? '',
                    'duration' => $visit['visitDuration'] ?? 0,
                    'actions_count' => $visit['actions'] ?? 0,
                    'searches' => $visit['searches'] ?? 0,
                    'referrer_type' => $visit['referrerType'] ?? '',
                    'referrer_name' => $visit['referrerName'] ?? '',
                    'referrer_url' => $visit['referrerUrl'] ?? '',
                    'device_type' => $visit['deviceType'] ?? 'Unknown',
                    'browser' => $visit['browserName'] ?? 'Unknown',
                    'browser_version' => $visit['browserVersion'] ?? '',
                    'os' => $visit['operatingSystemName'] ?? 'Unknown',
                    'os_version' => $visit['operatingSystemVersion'] ?? '',
                    'screen_resolution' => $visit['resolution'] ?? '',
                    'actions' => $actionDetails,
                ];
            }
        }

        return null;
    }

    /**
     * @param string $key
     * @param callable $callback
     * @return array
     */
    private function getCached($key, callable $callback)
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        try {
            $result = $callback();
            $this->cache[$key] = $result;
            return $result;
        } catch (\Exception $e) {
            error_log("Visits error for {$key}: " . $e->getMessage());
            return [];
        }
    }

    public function clearCache()
    {
        $this->cache = [];
    }
}