<?php

namespace App\Services;

use App\Facades\DiscountEngine;
use App\Models\Product;
use App\Models\ProductVendor;

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

        if (empty($productVendor)) {
            throw new \Exception(
                "Insufficient stock for product {$sku}. Please reduce the quantity or check back later.",
            );
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
