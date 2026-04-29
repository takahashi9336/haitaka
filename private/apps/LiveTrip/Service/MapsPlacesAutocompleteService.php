<?php

namespace App\LiveTrip\Service;

use Core\Maps\PlacesAutocompleteService as CorePlacesAutocompleteService;

/**
 * Google Places API (Legacy) Place Autocomplete 呼び出し
 * 住所・施設名のサジェストを取得。90% 制限を適用。
 */
class MapsPlacesAutocompleteService extends CorePlacesAutocompleteService {
    public function __construct() {
        parent::__construct(new LiveTripMapsUsageLimiter());
    }

    /**
     * 入力文字列から候補を取得
     * @return array<array{description: string, place_id: string}>
     */
}
