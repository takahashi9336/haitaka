<?php
/**
 * Google Maps API 利用量制限（90% 閾値）
 * 無料枠が変更された場合はこの設定を更新すること
 * @see https://developers.google.com/maps/billing-and-pricing/pricing
 */
return [
    'ratio' => 0.9,
    'limits' => [
        'geocoding' => 9000,        // 無料枠 10,000 の 90%
        'autocomplete' => 9000,
        'static_maps' => 9000,
        'distance_matrix' => 9000,  // 要素数
        'directions' => 9000,
    ],
];
