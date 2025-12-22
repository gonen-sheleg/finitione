<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Discount\DiscountEngine addRule(\App\Services\Discount\DiscountRuleInterface $rule)
 * @method static \Illuminate\Support\Collection applyDiscounts(\Illuminate\Support\Collection $items)
 *
 * @see \App\Services\Discount\DiscountEngine
 */
class DiscountEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Discount\DiscountEngine::class;
    }
}
