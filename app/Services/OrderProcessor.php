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

    public function processCart($cart) : array
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
                'user_id' => auth()->user()->id,
                'total_price' => $pvc->pluck('productVendor')->sum('price'),
                'total_final_price' => $pvc->pluck('discount')->sum('price'),
                'total_quantity' => $pvc->sum('quantity'),
                'cart' => $cart
            ]);

            $vendors = $pvc->groupBy('vendor_id');

            $subOrders = Concurrency::run(
                $vendors->map(fn($vendorItems, $vendorId) =>
                    fn() => VendorOrderProcessor::process($order, $vendorId, $vendorItems)
                )->toArray()
            );

            DB::commit();

            return $subOrders;
        }catch (\Exception $e){
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
