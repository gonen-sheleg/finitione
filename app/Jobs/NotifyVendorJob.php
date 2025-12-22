<?php

namespace App\Jobs;

use App\Models\SubOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyVendorJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SubOrder $subOrder
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subOrder = $this->subOrder->load(['vendor', 'order', 'items.product']);
        $vendor = $subOrder->vendor;
        $order = $subOrder->order;

        $itemsDetails = '';
        foreach ($subOrder->items as $item) {
            $itemsDetails .= sprintf(
                "  - %s (SKU: %s)\n    Quantity: %d | Unit Price: $%.2f | Total: $%.2f\n",
                $item->product->name,
                $item->product->sku,
                $item->quantity,
                $item->unit_price,
                $item->unit_final_price
            );
        }

        $message = <<<EOT
========================================
NEW ORDER NOTIFICATION
========================================

Dear {$vendor->name},

You have received a new order! Here are the details:

ORDER INFORMATION
-----------------
Order ID: #{$order->id}
Sub-Order ID: #{$subOrder->id}
Order Date: {$order->created_at->format('F j, Y \\a\\t g:i A')}
Status: {$subOrder->status}

ITEMS ORDERED
-------------
{$itemsDetails}
ORDER SUMMARY
-------------
Total Items: {$subOrder->sub_total_quantity}
Total Price: \${$subOrder->sub_total_price}
After Discount: \${$subOrder->sub_total_final_price}

Please process this order at your earliest convenience.

Thank you for your partnership!

========================================
EOT;

        Log::info("Vendor notification sent to {$vendor->email}", [
            'vendor_id' => $vendor->id,
            'sub_order_id' => $subOrder->id,
            'message' => $message,
        ]);
    }
}
