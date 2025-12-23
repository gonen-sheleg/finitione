<?php

namespace App\Services\Discount\Rules;

use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;
use Illuminate\Support\Facades\Cache;

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

        $this->userOrders = Cache::remember("loyalty-customer-discount-count-orders-{$user->id}",60*60,fn() => $user
            ->orders()
            ->where('created_at', '>=', now()->subMonths(6))
            ->count()
        );

        logInfo("User orders: {$this->userOrders}", 'blue');

        return $this->userOrders > 5;
    }
}
