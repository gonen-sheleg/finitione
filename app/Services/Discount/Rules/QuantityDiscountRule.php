<?php

namespace App\Services\Discount\Rules;

use App\Models\OrderItem;
use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;

class QuantityDiscountRule implements DiscountRuleInterface
{
    public function apply(ProductVendor $productVendor, int $quantity): float
    {
        return 0.3;
    }

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool
    {
        return true;
    }
}
