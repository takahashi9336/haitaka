<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use App\LiveTrip\Controller\LiveTripController;

(new LiveTripController())->deleteHotel();
