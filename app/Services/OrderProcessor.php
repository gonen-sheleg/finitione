<?php

namespace App\Services;

use App\Facades\PriceEngine;
use App\Facades\VendorOrderProcessor;
use App\Models\Order;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessor
{

    public function processCart($cart)
    {
        try {
            DB::beginTransaction();

            $productVendors = Concurrency::run(
                $cart->map(fn ($item) =>
                fn() => PriceEngine::findBestPrice($item['sku'], $item['quantity'])
                )->toArray()
            );



            $pvc = collect($productVendors);

            $order = Order::create([
                'total_price' => $pvc->pluck('productVendor')->sum('price'),
                'total_final_price' => $pvc->sum('final_price'),
                'total_quantity' => $pvc->sum('quantity'),
                'cart' => $cart
            ]);

            $subOrders = VendorOrderProcessor::process($order,$productVendors);

            DB::commit();

            return $subOrders;
        }catch (\Exception $e){
            DB::rollBack();
            Log::error($e);
            return response()->json(['error' => 'something went wrong'], 500);
        }
    }
}
