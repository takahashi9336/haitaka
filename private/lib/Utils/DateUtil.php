<?php

namespace Core\Utils;

class DateUtil {
    public static function format(string $date, string $format = 'Y/m/d'): string {
        if ($date === '') {
            return '';
        }
        $ts = strtotime($date);
        return $ts !== false ? date($format, $ts) : '';
    }

    public static function diffDays(string $targetDate): int {
        $today = new \DateTime('today');
        $target = new \DateTime($targetDate);
        $interval = $today->diff($target);
        return (int)$interval->format('%r%a');
    }
}