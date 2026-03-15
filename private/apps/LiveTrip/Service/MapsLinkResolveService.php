<?php

namespace App\LiveTrip\Service;

/**
 * Google Maps 経路共有URLの解決・パース
 * 短縮URLを追い、発・着・所要時間・resolved_url を返す
 */
class MapsLinkResolveService {

    private const ALLOWED_HOSTS = ['maps.app.goo.gl', 'www.google.com', 'google.com'];
    private const MAX_REDIRECTS = 5;

    /**
     * URL を解決し、発・着・所要時間・日時・保存用URL を返す
     * @return array{status: string, origin?: string, destination?: string, duration_min?: int, departure_date?: string, departure_time?: string, resolved_url?: string, message?: string}
     */
    public function resolve(string $url): array {
        $url = trim($url);
        if ($url === '') {
            return ['status' => 'error', 'message' => 'URLを入力してください'];
        }

        if (!$this->isAllowedUrl($url)) {
            return ['status' => 'error', 'message' => 'Google Mapsの経路URLのみ利用できます'];
        }

        $resolved = $this->resolveRedirects($url);
        if ($resolved === null) {
            return ['status' => 'error', 'message' => 'URLの解決に失敗しました'];
        }

        $parsed = $this->parseMapsUrl($resolved);
        if (empty($parsed['origin']) || empty($parsed['destination'])) {
            return [
                'status' => 'error',
                'message' => '経路の出発地・目的地を取得できませんでした',
                'resolved_url' => $resolved,
            ];
        }

        $mode = $parsed['travelmode'] ?? 'transit';
        $departureDate = $parsed['departure_date'] ?? null;

        $directions = new MapsDirectionsService();
        $durationMin = $directions->getRouteDuration(
            $parsed['origin'],
            $parsed['destination'],
            $mode,
            $departureDate
        );

        $result = [
            'status' => 'ok',
            'origin' => $parsed['origin'],
            'destination' => $parsed['destination'],
            'resolved_url' => $resolved,
        ];
        if ($durationMin !== null) {
            $result['duration_min'] = $durationMin;
        }
        if (!empty($parsed['departure_date'])) {
            $result['departure_date'] = $parsed['departure_date'];
        }
        if (!empty($parsed['departure_time'])) {
            $result['departure_time'] = $parsed['departure_time'];
        }
        return $result;
    }

    private function isAllowedUrl(string $url): bool {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $scheme = strtolower($parsed['scheme'] ?? '');
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }
        $host = strtolower($host);
        return in_array($host, self::ALLOWED_HOSTS, true);
    }

    /**
     * リダイレクトを追って最終URLを取得
     */
    private function resolveRedirects(string $url): ?string {
        $current = $url;
        $count = 0;
        while ($count < self::MAX_REDIRECTS) {
            $headers = @get_headers($current, true);
            if ($headers === false) {
                return null;
            }
            $status = $headers[0] ?? '';
            if (preg_match('/^HTTP\/\d\.\d\s+3\d\d/', $status)) {
                $location = null;
                foreach ($headers as $k => $v) {
                    if (is_string($k) && strtolower($k) === 'location') {
                        $location = is_array($v) ? end($v) : $v;
                        break;
                    }
                }
                if (empty($location)) {
                    return $current;
                }
                $current = $this->resolveRelativeUrl($current, trim($location));
                $count++;
                continue;
            }
            return $current;
        }
        return $current;
    }

    private function resolveRelativeUrl(string $base, string $location): string {
        if (preg_match('/^https?:\/\//i', $location)) {
            return $location;
        }
        $p = parse_url($base);
        $scheme = $p['scheme'] ?? 'https';
        $host = $p['host'] ?? '';
        $path = $location;
        if (!str_starts_with($location, '/')) {
            $basePath = $p['path'] ?? '/';
            $path = rtrim(dirname($basePath), '/') . '/' . $location;
        }
        return $scheme . '://' . $host . $path;
    }

    /**
     * Google Maps 経路URLから origin, destination, travelmode, departure_time などをパース
     * @return array{origin?: string, destination?: string, travelmode?: string, departure_date?: string, departure_time?: string}
     */
    private function parseMapsUrl(string $url): array {
        $result = [];
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        parse_str($query, $params);

        if (!empty($params['origin'])) {
            $result['origin'] = trim($params['origin']);
        }
        if (!empty($params['destination'])) {
            $result['destination'] = trim($params['destination']);
        }
        if (!empty($params['travelmode'])) {
            $mode = strtolower($params['travelmode']);
            if (in_array($mode, ['driving', 'transit', 'walking', 'bicycling'], true)) {
                $result['travelmode'] = $mode;
            }
        }
        if (empty($result['travelmode'])) {
            $result['travelmode'] = 'transit';
        }

        if (!empty($params['departure_time'])) {
            $ts = (int) $params['departure_time'];
            if ($ts > 0) {
                $dt = new \DateTimeImmutable('@' . $ts, new \DateTimeZone('Asia/Tokyo'));
                $result['departure_date'] = $dt->format('Y-m-d');
                $result['departure_time'] = $dt->format('H:i');
            }
        }

        // パス形式: /maps/dir/Origin/Destination または /dir/Origin/Destination
        if ((empty($result['origin']) || empty($result['destination'])) && preg_match('#/maps/dir/(.+)#', $path, $m)) {
            $segments = explode('/', trim($m[1], '/'));
            $segments = array_map('rawurldecode', array_filter($segments));
            if (count($segments) >= 2) {
                if (empty($result['origin'])) {
                    $result['origin'] = $segments[0];
                }
                if (empty($result['destination'])) {
                    $result['destination'] = $segments[1];
                }
            }
        }

        return $result;
    }
}
