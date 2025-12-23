<?php

namespace App\Services\Discount\Rules;

use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;

class QuantityDiscountRule implements DiscountRuleInterface
{
    public function apply(ProductVendor $productVendor, int $quantity): float
    {
        return match (true) {
            $quantity >= 50 => 0.15,
            $quantity >= 40 => 0.11,
            $quantity >= 30 => 0.09,
            $quantity >= 20 => 0.07,
            $quantity >= 10 => 0.05,
            default => 0.0,
        };
    }

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool
    {
        logInfo("Quantity: {$quantity}", 'blue');
        return $quantity >= 10;
    }
}
