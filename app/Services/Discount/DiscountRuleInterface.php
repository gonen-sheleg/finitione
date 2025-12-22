<?php

namespace App\Services\Discount;

use App\Models\OrderItem;
use App\Models\ProductVendor;

interface DiscountRuleInterface
{
    public function apply(ProductVendor $productVendor, int $quantity): float;

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool;
}
