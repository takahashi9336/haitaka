<?php
/**
 * Places Autocomplete API（住所・施設名のサジェスト）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\LiveTrip\Controller\LiveTripController;

(new LiveTripController())->placesAutocomplete();
