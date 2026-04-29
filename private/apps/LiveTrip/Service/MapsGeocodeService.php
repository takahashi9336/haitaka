<?php

namespace App\LiveTrip\Service;

use Core\Maps\GeocodeService as CoreGeocodeService;

/**
 * Google Geocoding API 呼び出し
 * 住所・施設名から緯度経度・place_id を取得。90% 制限を適用。
 */
class MapsGeocodeService extends CoreGeocodeService {
    public function __construct() {
        parent::__construct(new LiveTripMapsUsageLimiter());
    }

    /**
     * 住所・施設名から緯度経度・place_id を取得
     * 制限超過時・API 未設定・エラー時は null を返す
     * @return array{latitude: string, longitude: string, place_id: string|null, formatted_address: string|null}|null
     */
}
