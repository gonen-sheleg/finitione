<?php

namespace App\Services;

use App\Facades\PriceEngine;
use App\Facades\VendorOrderProcessor;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessor
{
    // Process the cart and create an order.
    public function processCart($cart): Order
    {
        try {
            // Start a database transaction to ensure data integrity.
            // During processing, we reduce the vendor's available quantity.
            // If anything fails, the transaction rolls back and restores the original quantities.
            DB::beginTransaction();

            // Find the best price for each item in the cart.
            // Each item is sent to PriceEngine to find the vendor with the lowest price.
            // Any available discounts are also applied at this stage.
            $productVendors = $cart
                ->map(fn ($item) => PriceEngine::findBestPrice($item['sku'], $item['quantity']))
                ->toArray();

            $pvc = collect($productVendors);

            $userId = auth()->user()->id;
            // Create the order with pending status.
            $order = Order::create([
                'user_id' => $userId,
                'total_price' => $pvc->pluck('productVendor')->sum('price'),
                'total_final_price' => $pvc->pluck('discount')->sum('price'),
                'total_quantity' => $pvc->sum('quantity'),
                'cart' => $cart,
            ]);

            logInfo("Order created: {$order->id}", 'magenta', $order->toArray());

            // Group the items by vendor.
            $vendors = $pvc->groupBy('vendor_id');

            // Create a sub-order for each vendor and notify them.
            // Each vendor receives a list of items that belong only to them.
            $vendors->map(fn ($vendorItems, $vendorId)=>
                     VendorOrderProcessor::process(
                        $order,
                        $vendorId,
                        $vendorItems,
                    )
                );

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            // Roll back the transaction if an error occurs.
            DB::rollBack();
            // Log the error.
            Log::error($e);
            // Re-throw the exception to be handled by the controller.
            throw $e;
        }
    }
}
