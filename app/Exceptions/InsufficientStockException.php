<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $skuOrMessage, ?int $requestedQuantity = null)
    {
        if ($requestedQuantity === null) {
            parent::__construct($skuOrMessage);

            return;
        }

        parent::__construct(
            "Insufficient stock for product {$skuOrMessage}. Requested quantity: {$requestedQuantity}. Please reduce the quantity or check back later."
        );
    }
}
