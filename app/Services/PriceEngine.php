<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Facades\DiscountEngine;
use App\Models\Product;

class PriceEngine
{
    public function findBestPrice(string $sku, int $quantity): array
    {
        $product = Product::bySku($sku)->first();

        $productVendor = $product
            ->productVendors()
            ->where('quantity', '>=', $quantity)
            ->orderBy('price', 'asc')
            ->first();

        // If there is no available stock, throw an exception.
        if (empty($productVendor)) {
            throw new InsufficientStockException($sku, $quantity);
        }

        $productVendor->decrement('quantity', $quantity);

        return [
            'productVendor' => $productVendor,
            'sku' => $sku,
            'discount' => DiscountEngine::applyDiscounts($productVendor, $quantity),
            'quantity' => $quantity,
            'vendor_id' => $productVendor->vendor_id,
        ];
    }
}
