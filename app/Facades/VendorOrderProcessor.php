<?php

namespace App\Facades;

use App\Models\Order;
use App\Models\ProductVendor;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed process(Order $order, ProductVendor ...$items)
 *
 * @see \App\Services\VendorOrderProcessor
 */
class VendorOrderProcessor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\VendorOrderProcessor::class;
    }
}
