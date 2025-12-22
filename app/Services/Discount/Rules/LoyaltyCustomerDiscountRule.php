<?php

namespace App\Services\Discount\Rules;

use App\Models\OrderItem;
use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;

class LoyaltyCustomerDiscountRule implements DiscountRuleInterface
{
    public function apply(ProductVendor $productVendor, int $quantity): float
    {
        return 0.0;
    }

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool
    {
        return false;
    }
}
