<?php

namespace Core\Maps;

class NoopUsageLimiter implements UsageLimiterInterface {
    public function incrementAndCheck(string $sku, int $amount = 1): bool {
        return true;
    }
}

