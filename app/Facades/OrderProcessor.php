<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed processOrder(array $cart)
 *
 * @see \App\Services\OrderProcessor
 */
class OrderProcessor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\OrderProcessor::class;
    }
}
