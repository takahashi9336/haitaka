<?php

namespace Core\Maps;

/**
 * Google Distance Matrix API 呼び出し
 * 2点間の距離・所要時間を取得。
 */
class DistanceMatrixService {
    private string $apiKey = '';
    private UsageLimiterInterface $limiter;
    private const DM_URL = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    public function __construct(?UsageLimiterInterface $limiter = null) {
        $this->limiter = $limiter ?? new NoopUsageLimiter();
        $this->apiKey = ApiKeyProvider::getGoogleMapsApiKey();
    }

    public function isConfigured(): bool {
        return $this->apiKey !== '';
    }

    /**
     * @return array{distance: string, duration: string}|null
     */
    public function getDistanceAndDuration(string $originLat, string $originLng, string $destLat, string $destLng): ?array {
        if (!$this->isConfigured()) return null;
        if (!$this->limiter->incrementAndCheck('distance_matrix', 1)) return null;

        $origin = $originLat . ',' . $originLng;
        $dest = $destLat . ',' . $destLng;

        try {
            $url = self::DM_URL . '?origins=' . rawurlencode($origin)
                . '&destinations=' . rawurlencode($dest)
                . '&mode=driving'
                . '&language=ja'
                . '&key=' . rawurlencode($this->apiKey);

            $ctx = stream_context_create([
                'http' => ['timeout' => 8],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json === false) return null;

            $data = json_decode($json, true);
            $status = $data['status'] ?? '';
            if ($status !== 'OK') return null;

            $element = $data['rows'][0]['elements'][0] ?? null;
            if (!$element) return null;

            $status = $element['status'] ?? '';
            if ($status !== 'OK') return null;

            $distance = $element['distance']['text'] ?? null;
            $duration = $element['duration']['text'] ?? null;
            if (!$distance || !$duration) return null;

            return [
                'distance' => (string)$distance,
                'duration' => (string)$duration,
            ];
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Distance Matrix failed', $e);
            return null;
        }
    }
}

