<?php

namespace App\Services\Discount\Rules;

use App\Models\OrderItem;
use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;

class CategoryDiscountRule implements DiscountRuleInterface
{
    public function apply(ProductVendor $productVendor, int $quantity): float
    {
        return match ($productVendor->product->category_id) {
            2 => 0.05,
            5 => 0.07,
            7 => 0.09,
            9 => 0.11,
            default => 0.0,
        };
    }

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool
    {
        return in_array($productVendor->product->category_id, [2, 5, 7, 9]);
    }
}
