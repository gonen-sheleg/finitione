<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVendor;

class PriceEngine
{

    public function findBestPrice(Product $product, int $quantity): ProductVendor
    {

        return $product->productVendors()
            ->where('quantity', '>=', $quantity)
            ->orderBy('price')
            ->first();

    }
}
