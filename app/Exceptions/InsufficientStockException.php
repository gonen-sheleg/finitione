<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $sku, int $requestedQuantity)
    {
        parent::__construct(
            "Insufficient stock for product {$sku}. Requested quantity: {$requestedQuantity}. Please reduce the quantity or check back later."
        );
    }
}
