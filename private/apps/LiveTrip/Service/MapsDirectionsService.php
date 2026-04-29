<?php

namespace App\LiveTrip\Service;

use Core\Maps\DirectionsService as CoreDirectionsService;

/**
 * Google Directions API 呼び出し
 * 車・電車・徒歩・自転車の経路候補を取得。90% 制限を適用。
 */
class MapsDirectionsService extends CoreDirectionsService {
    public function __construct() {
        parent::__construct(new LiveTripMapsUsageLimiter());
    }
}
