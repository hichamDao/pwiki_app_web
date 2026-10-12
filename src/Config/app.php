<?php
return [
    'app' => [
        'name' => 'Pkiwi App',
        'version' => '1.0.0',
        'debug' => true,
        'timezone' => 'Europe/Paris',
    ],
    'matomo' => [
        'base_url' => 'http://192.168.11.172:9094',
        'api_token' => '21232f297a57a5a743894a0e4a801fc3',
        'site_id' => 4,
        'timeout' => 30,
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'analytics',
        'username' => 'root',
        'password' => '',
        'prefix' => 'xdlu_',
        'charset' => 'utf8mb4',
    ],
    'geolocation' => [
        'provider' => 'geoip2php',
        'cache_ttl' => 86400, // 24 hours
    ],
    'cache' => [
        'enabled' => true,
        'path' => __DIR__ . '/../../logs/cache',
        'ttl' => 3600,
    ],
];