<?php
/**
 * Google Maps 経路URL解決 API（貼り付け→反映用）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\LiveTrip\Controller\LiveTripController;

(new LiveTripController())->resolveMapsLink();
