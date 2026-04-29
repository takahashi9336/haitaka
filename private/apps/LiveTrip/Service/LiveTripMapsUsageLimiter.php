<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;
use Core\Maps\UsageLimiterInterface;

/**
 * LiveTrip 用 Maps 利用量リミッタ（lt_maps_api_usage でカウント）
 */
class LiveTripMapsUsageLimiter implements UsageLimiterInterface {
    public function incrementAndCheck(string $sku, int $amount = 1): bool {
        return (new MapsApiUsageModel())->incrementAndCheck($sku, $amount);
    }
}

