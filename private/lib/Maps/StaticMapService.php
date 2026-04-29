<?php

namespace Core\Maps;

/**
 * Google Maps Static API による地図画像 URL 生成
 */
class StaticMapService {
    private string $apiKey = '';
    private UsageLimiterInterface $limiter;
    private const BASE_URL = 'https://maps.googleapis.com/maps/api/staticmap';

    public function __construct(?UsageLimiterInterface $limiter = null) {
        $this->limiter = $limiter ?? new NoopUsageLimiter();
        $this->apiKey = ApiKeyProvider::getGoogleMapsApiKey();
    }

    public function isConfigured(): bool {
        return $this->apiKey !== '';
    }

    public function getStaticMapUrl(string $lat, string $lng, int $w = 320, int $h = 120): ?string {
        $lat = trim($lat);
        $lng = trim($lng);
        if ($lat === '' || $lng === '') return null;
        if (!$this->isConfigured()) return null;
        if (!$this->limiter->incrementAndCheck('static_maps', 1)) return null;

        try {
            $w = max(100, min(640, $w));
            $h = max(60, min(640, $h));

            $center = $lat . ',' . $lng;
            $params = [
                'center' => $center,
                'zoom' => 15,
                'size' => $w . 'x' . $h,
                'maptype' => 'roadmap',
                'markers' => 'color:red|' . $center,
                'key' => $this->apiKey,
            ];

            return self::BASE_URL . '?' . http_build_query($params);
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Static Map URL failed', $e);
            return null;
        }
    }
}

