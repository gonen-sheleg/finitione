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

    public function process(Order $order, int $vendorId, Collection $itemsCollection): SubOrder
    {

        $subOrder = SubOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendorId,
            'sub_total_price' => $itemsCollection->pluck('productVendor')->sum('price'),
            'sub_total_final_price' => $itemsCollection->pluck('discounts')->sum('final_price'),
            'sub_total_quantity' => $itemsCollection->sum('quantity'),
        ]);

        Concurrency::run(
            $itemsCollection->map(fn($item) =>
                fn() => OrderItem::create([
                    "sub_order_id" => $subOrder->id,
                    "product_id" => $item['productVendor']['product_id'],
                    "vendor_id" => $item['productVendor']['vendor_id'],
                    "quantity" => $item['quantity'],
                    "unit_price" => $item['productVendor']['price'],
                    "unit_final_price" => $item['discount']['price'],
                    "discounts" => $item['discount']['details'],
                ])
            )->toArray()
        );

        NotifyVendorJob::dispatch($subOrder);

        return $subOrder;

    }
}
