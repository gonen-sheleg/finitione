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
        $subOrder = $this->subOrder->load(['vendor', 'order.user', 'items.product']);
        $vendor = $subOrder->vendor;
        $order = $subOrder->order;
        $user = $order->user;

        $address = $user->address ?? [];
        $addressFormatted = collect([
            $address['street'] ?? null,
            $address['building_number'] ?? null,
            isset($address['floor']) ? "Floor {$address['floor']}" : null,
            isset($address['apartment']) ? "Apt {$address['apartment']}" : null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ])->filter()->join(', ');

        $itemsData = $subOrder->items->map(function($item) {
            $discountDetails = collect($item->discounts)->map(fn($d) => "{$d['name']}: " . ($d['discount'] * 100) . "%")->join(' | ');
            return [
                'name' => substr($item->product->name, 0, 30),
                'sku' => substr($item->product->sku, 0, 20),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'unit_final_price' => $item->unit_final_price,
                'discount_details' => $discountDetails,
            ];
        });

        $discountColWidth = max(16, $itemsData->max(fn($item) => strlen($item['discount_details'])));
        $itemsHeader = sprintf("| Product                        | SKU                  | Qty | Unit Price | After Discounted | %-{$discountColWidth}s |", "Discount Details");
        $itemsSeparator = "+--------------------------------+----------------------+-----+------------+------------------+" . str_repeat('-', $discountColWidth + 2) . "+";
        $itemsRows = $itemsData->map(function($item) use ($discountColWidth) {
            return sprintf(
                "| %-30s | %-20s | %3d | $%9.2f | $%15.2f | %-{$discountColWidth}s |",
                $item['name'],
                $item['sku'],
                $item['quantity'],
                $item['unit_price'],
                $item['unit_final_price'],
                $item['discount_details']
            );
        })->join("\n");
        $itemsDetails = "\n{$itemsSeparator}\n{$itemsHeader}\n{$itemsSeparator}\n{$itemsRows}\n{$itemsSeparator}";

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

            CUSTOMER INFORMATION
            --------------------
            Name: {$user->name}
            Email: {$user->email}
            Address: {$addressFormatted}

            ITEMS ORDERED
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


        logInfo("Vendor notification sending to {$vendor->email}", "purple");
        logInfo("\n\n{$message}", "yellow");
    }
}
