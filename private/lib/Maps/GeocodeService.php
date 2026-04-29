<?php

namespace Core\Maps;

/**
 * Google Geocoding API 呼び出し
 * 住所・施設名から緯度経度・place_id を取得。
 */
class GeocodeService {
    private string $apiKey = '';
    private UsageLimiterInterface $limiter;
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct(?UsageLimiterInterface $limiter = null) {
        $this->limiter = $limiter ?? new NoopUsageLimiter();
        $this->apiKey = ApiKeyProvider::getGoogleMapsApiKey();
    }

    public function isConfigured(): bool {
        return $this->apiKey !== '';
    }

    /**
     * @return array{latitude: string, longitude: string, place_id: string|null, formatted_address: string|null}|null
     */
    public function geocode(string $address): ?array {
        $address = trim($address);
        if ($address === '') return null;
        if (!$this->isConfigured()) return null;
        if (!$this->limiter->incrementAndCheck('geocoding', 1)) return null;

        try {
            $url = self::GEOCODE_URL . '?address=' . rawurlencode($address)
                . '&key=' . rawurlencode($this->apiKey)
                . '&language=ja';

            $ctx = stream_context_create([
                'http' => ['timeout' => 5],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json === false) return null;

            $data = json_decode($json, true);
            if (!isset($data['results'][0])) return null;

            $r = $data['results'][0];
            $loc = $r['geometry']['location'] ?? null;
            if (!$loc || !isset($loc['lat'], $loc['lng'])) return null;

            return [
                'latitude' => (string)$loc['lat'],
                'longitude' => (string)$loc['lng'],
                'place_id' => $r['place_id'] ?? null,
                'formatted_address' => $r['formatted_address'] ?? null,
            ];
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Geocoding failed', $e);
            return null;
        }
    }

    /**
     * @return array{latitude: string, longitude: string, place_id: string|null, formatted_address: string|null}|null
     */
    public function geocodeByPlaceId(string $placeId): ?array {
        $placeId = trim($placeId);
        if ($placeId === '') return null;
        if (!$this->isConfigured()) return null;
        if (!$this->limiter->incrementAndCheck('geocoding', 1)) return null;

        try {
            $url = self::GEOCODE_URL . '?place_id=' . rawurlencode($placeId)
                . '&key=' . rawurlencode($this->apiKey)
                . '&language=ja';

            $ctx = stream_context_create([
                'http' => ['timeout' => 5],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json === false) return null;

            $data = json_decode($json, true);
            if (!isset($data['results'][0])) return null;

            $r = $data['results'][0];
            $loc = $r['geometry']['location'] ?? null;
            if (!$loc || !isset($loc['lat'], $loc['lng'])) return null;

            return [
                'latitude' => (string)$loc['lat'],
                'longitude' => (string)$loc['lng'],
                'place_id' => $r['place_id'] ?? $placeId,
                'formatted_address' => $r['formatted_address'] ?? null,
            ];
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Geocoding by place_id failed', $e);
            return null;
        }
    }
}

