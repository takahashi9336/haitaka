<?php

namespace App\LiveTrip\Service;

use Core\Maps\DistanceMatrixService as CoreDistanceMatrixService;

/**
 * Google Distance Matrix API 呼び出し
 * 2点間の距離・所要時間を取得。90% 制限を適用。
 */
class MapsDistanceMatrixService extends CoreDistanceMatrixService {
    public function __construct() {
        parent::__construct(new LiveTripMapsUsageLimiter());
    }

    /**
     * 2点間の車での距離・所要時間を取得
     * @param string $originLat  出発地の緯度
     * @param string $originLng  出発地の経度
     * @param string $destLat    目的地の緯度
     * @param string $destLng    目的地の経度
     * @return array{distance: string, duration: string}|null 距離（例: 2.3 km）、所要時間（例: 15 分）
     */
}
