<?php

namespace Core\Maps;

interface UsageLimiterInterface {
    public function incrementAndCheck(string $sku, int $amount = 1): bool;
}

