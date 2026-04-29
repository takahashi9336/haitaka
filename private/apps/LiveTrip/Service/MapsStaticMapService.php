<?php

namespace App\LiveTrip\Service;

use Core\Maps\StaticMapService as CoreStaticMapService;

/**
 * Google Maps Static API による地図画像 URL 生成
 * 90% 制限を適用。制限超過時は null を返す。
 */
class MapsStaticMapService extends CoreStaticMapService {
    public function __construct() {
        parent::__construct(new LiveTripMapsUsageLimiter());
    }

    /**
     * 緯度経度を中心とした地図画像の URL を取得
     * 制限超過・未設定時は null
     * @param string $lat  緯度
     * @param string $lng  経度
     * @param int    $w    幅（px、最大640）
     * @param int    $h    高さ（px、最大640）
     */
}
