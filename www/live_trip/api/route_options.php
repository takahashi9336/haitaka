<?php
/**
 * 経路候補取得 API（車/電車/徒歩）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\LiveTrip\Controller\LiveTripController;

(new LiveTripController())->routeOptions();
