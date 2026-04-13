<?php
/**
 * Google Maps Directions overview_polyline API（地図描画用）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\LiveTrip\Controller\LiveTripController;

(new LiveTripController())->directionsPolyline();

