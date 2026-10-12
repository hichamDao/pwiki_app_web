<?php
namespace Pkiwi\Modules;

use Pkiwi\Api\MatomoClient;
use Pkiwi\Database\Connection;

class Geolocation
{
    /** @var MatomoClient */
    private $matomo;
    /** @var Connection|null */
    private $db;
    /** @var array */
    private $cache = [];
    /** @var int */
    private $cacheTtl;

    /**
     * @param MatomoClient $matomo
     * @param Connection|null $db
     * @param int $cacheTtl
     */
    public function __construct(MatomoClient $matomo, Connection $db = null, $cacheTtl = 86400)
    {
        $this->matomo = $matomo;
        $this->db = $db;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getCountries($period = 'day', $date = 'today')
    {
        $cacheKey = "countries_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($period, $date) {
            return $this->matomo->getCountries($period, $date);
        });
    }

    /**
     * @param string $countryCode
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getRegions($countryCode, $period = 'day', $date = 'today')
    {
        $cacheKey = "regions_{$countryCode}_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($countryCode, $period, $date) {
            return $this->matomo->getRegions($countryCode, $period, $date);
        });
    }

    /**
     * @param string $countryCode
     * @param string $regionCode
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getCities($countryCode, $regionCode, $period = 'day', $date = 'today')
    {
        $cacheKey = "cities_{$countryCode}_{$regionCode}_{$period}_{$date}";
        return $this->getCached($cacheKey, function () use ($countryCode, $regionCode, $period, $date) {
            return $this->matomo->getCities($countryCode, $regionCode, $period, $date);
        });
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getLocationSummary($period = 'day', $date = 'today')
    {
        $countries = $this->getCountries($period, $date);
        
        $summary = [
            'total_countries' => count($countries),
            'total_visits' => 0,
            'total_unique_visitors' => 0,
            'countries' => [],
        ];

        foreach ($countries as $country) {
            $summary['total_visits'] += $country['nb_visits'] ?? 0;
            $summary['total_unique_visitors'] += $country['nb_uniq_visitors'] ?? 0;
            
            $countryData = [
                'code' => $country['code'] ?? 'unknown',
                'name' => $country['label'] ?? 'Unknown',
                'visits' => $country['nb_visits'] ?? 0,
                'unique_visitors' => $country['nb_uniq_visitors'] ?? 0,
                'actions' => $country['nb_actions'] ?? 0,
                'bounce_rate' => $country['bounce_rate'] ?? '0%',
                'avg_time' => $country['avg_time_on_page'] ?? 0,
            ];

            // Get regions for top countries
            if (($countryData['visits'] > 10 || count($countries) <= 5) && $countryData['code'] !== 'unknown') {
                try {
                    $regions = $this->getRegions($countryData['code'], $period, $date);
                    $countryData['regions'] = array_slice($regions, 0, 5);
                } catch (\Exception $e) {
                    $countryData['regions'] = [];
                }
            }

            $summary['countries'][] = $countryData;
        }

        // Sort by visits descending
        usort($summary['countries'], function ($a, $b) {
            return $b['visits'] - $a['visits'];
        });

        return $summary;
    }

    /**
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getWorldMapData($period = 'day', $date = 'today')
    {
        $countries = $this->getCountries($period, $date);
        
        $mapData = [];
        foreach ($countries as $country) {
            $code = strtolower($country['code'] ?? '');
            if ($code && $code !== 'unknown') {
                $mapData[] = [
                    'code' => $code,
                    'country' => $country['label'] ?? 'Unknown',
                    'visits' => (int)($country['nb_visits'] ?? 0),
                    'visitors' => (int)($country['nb_uniq_visitors'] ?? 0),
                ];
            }
        }

        return $mapData;
    }

    /**
     * @param string $countryCode
     * @param string $period
     * @param string $date
     * @return array
     */
    public function getCountryDetails($countryCode, $period = 'day', $date = 'today')
    {
        $countries = $this->getCountries($period, $date);
        $country = null;
        
        foreach ($countries as $c) {
            if (strtolower($c['code'] ?? '') === strtolower($countryCode)) {
                $country = $c;
                break;
            }
        }

        if (!$country) {
            return ['error' => 'Country not found'];
        }

        $regions = $this->getRegions($countryCode, $period, $date);
        $regionDetails = [];

        foreach (array_slice($regions, 0, 10) as $region) {
            $regionCode = $region['code'] ?? '';
            if ($regionCode) {
                try {
                    $cities = $this->getCities($countryCode, $regionCode, $period, $date);
                    $regionDetails[] = [
                        'code' => $regionCode,
                        'name' => $region['label'] ?? 'Unknown',
                        'visits' => (int)($region['nb_visits'] ?? 0),
                        'top_cities' => array_slice($cities, 0, 5),
                    ];
                } catch (\Exception $e) {
                    $regionDetails[] = [
                        'code' => $regionCode,
                        'name' => $region['label'] ?? 'Unknown',
                        'visits' => (int)($region['nb_visits'] ?? 0),
                        'top_cities' => [],
                    ];
                }
            }
        }

        return [
            'country' => $country,
            'regions' => $regionDetails,
        ];
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
            error_log("Geolocation error for {$key}: " . $e->getMessage());
            return [];
        }
    }

    public function clearCache()
    {
        $this->cache = [];
    }
}