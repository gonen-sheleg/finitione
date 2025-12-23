<?php

namespace App\Services\Discount\Rules;

use App\Models\OrderItem;
use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;

class LoyaltyCustomerDiscountRule implements DiscountRuleInterface
{

    private $userOrders = 0;

    public function apply(ProductVendor $productVendor, int $quantity): float
    {
        return match (true) {
            $this->userOrders >= 30 => 0.15,
            $this->userOrders >= 20 => 0.12,
            $this->userOrders >= 10 => 0.1,
            $this->userOrders >= 5 => 0.05,
            default => 0.0,
        };
    }

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool
    {
        $user = auth()->user();

        $this->userOrders = $user->orders()->where('created_at', '>=', now()->subMonths(6))->count();

        return $this->userOrders > 5;
    }
}
