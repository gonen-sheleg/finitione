<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\ProductVendor findBestPrice(string $sku, int $quantity)
 *
 * @see \App\Services\PriceEngine
 */
class PriceEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\PriceEngine::class;
    }
}
