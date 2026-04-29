<?php

namespace Core\Maps;

final class ApiKeyProvider {
    public static function getGoogleMapsApiKey(): string {
        $apiKey = (string)($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
        if ($apiKey !== '') {
            return $apiKey;
        }

        $envPath = dirname(__DIR__, 2) . '/.env';
        if (!is_file($envPath)) {
            return '';
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            if (trim($key) === 'GOOGLE_MAPS_API_KEY') {
                return trim($val);
            }
        }
        return '';
    }
}

