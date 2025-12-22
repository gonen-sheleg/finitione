<?php

namespace App\Services;

use App\Jobs\NotifyVendorJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVendor;
use App\Models\SubOrder;
use Illuminate\Support\Facades\Concurrency;

class VendorOrderProcessor
{

    public function process(Order $order, array $items): array
    {

        $vendors = collect($items)->groupBy('vendor_id');

        $subOrders = Concurrency::run(
            $vendors->map(fn($vendorItems, $vendorId) =>
            function() use($vendorItems, $vendorId, $order){

                $itemsCollection = collect($vendorItems);
                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'sub_total_price' => $itemsCollection->pluck('productVendor')->sum('price'),
                    'sub_total_final_price' => $itemsCollection->sum('final_price'),
                    'sub_total_quantity' => $itemsCollection->sum('quantity'),
                ]);

                $subOrderItems = [];
                foreach ($vendorItems as $item){

                    $subOrderItems[] = [
                        "sub_order_id" => $subOrder->id,
                        "product_id" => $item['productVendor']['product_id'],
                        "vendor_id" => $item['productVendor']['vendor_id'],
                        "quantity" => $item['quantity'],
                        "unit_price" => $item['productVendor']['price'],
                        "unit_final_price" => $item['final_price'],
                    ];
                }

                OrderItem::insert($subOrderItems);

                NotifyVendorJob::dispatch($subOrder);

                return $subOrder;
            }

            )->toArray()
        );

        return $subOrders;
    }
}
