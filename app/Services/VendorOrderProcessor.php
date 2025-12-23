<?php

namespace App\Services;

use App\Jobs\NotifyVendorJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVendor;
use App\Models\SubOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Concurrency;

class VendorOrderProcessor
{
    /**
     * Process the order items for a specific vendor.
     *
     * This function handles the vendor-specific part of the order:
     * 1. Creates a sub-order with the total price, discounted price, and quantity.
     * 2. Creates individual order items for each product in parallel.
     * 3. Dispatches a job to notify the vendor about their new order.
     */
    public function process(Order $order, int $vendorId, Collection $itemsCollection): SubOrder
    {
        // Create a sub-order for the vendor.
        $subOrder = SubOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendorId,
            'sub_total_price' => $itemsCollection->pluck('productVendor')->sum('price'),
            'sub_total_final_price' => $itemsCollection->pluck('discounts')->sum('final_price'),
            'sub_total_quantity' => $itemsCollection->sum('quantity'),
        ]);

        // Create order items in parallel for faster processing.
        Concurrency::run(
            $itemsCollection
                ->map(
                    fn($item) => fn() => OrderItem::create([
                        'sub_order_id' => $subOrder->id,
                        'product_id' => $item['productVendor']['product_id'],
                        'vendor_id' => $item['productVendor']['vendor_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['productVendor']['price'],
                        'unit_final_price' => $item['discount']['price'],
                        'discounts' => $item['discount']['details'],
                    ]),
                )
                ->toArray(),
        );

        logInfo("Suborder created: {$subOrder->id}", 'orange', $subOrder->toArray());

        // Notify the vendor about the sub-order.
        NotifyVendorJob::dispatch($subOrder);

        return $subOrder;
    }
}
