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
    // Process the cart and create an order.
    public function processCart($cart): array
    {
        try {
            // Start a database transaction to ensure data integrity.
            // During processing, we reduce the vendor's available quantity.
            // If anything fails, the transaction rolls back and restores the original quantities.
            DB::beginTransaction();

            // Find the best price for each item in the cart.
            // Uses Concurrency::run to check all items in parallel for faster processing.
            // Each item is sent to PriceEngine to find the vendor with the lowest price.
            // Any available discounts are also applied at this stage.
            $productVendors = Concurrency::run(
                $cart->map(fn($item) => fn() => PriceEngine::findBestPrice($item['sku'], $item['quantity']))->toArray(),
            );

            $pvc = collect($productVendors);

            // Create the order with pending status.
            $order = Order::create([
                'user_id' => auth()->user()->id,
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
            // Uses Concurrency::run to create all sub-orders in parallel for faster processing.
            $subOrders = Concurrency::run(
                $vendors
                    ->map(
                        fn($vendorItems, $vendorId) => fn() => VendorOrderProcessor::process(
                            $order,
                            $vendorId,
                            $vendorItems,
                        ),
                    )
                    ->toArray(),
            );

            DB::commit();

            return $subOrders;
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
