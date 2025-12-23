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

        // Format the user address for display in the message.
        // Example: "Main St, 42, Floor 3, Apt 12, New York, NY, 10001, USA"
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

        // Build the items table for the notification message.
        $itemsDetails = $this->buildItemsTable($subOrder);

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


        // Log (send) the notification message.
        logInfo("Vendor notification sending to {$vendor->email}", "purple");
        logInfo("\n\n{$message}", "yellow");
    }

    /**
     * Build a formatted table of items for the notification message.
     *
     * Creates a text-based table showing each item's product name, SKU,
     * quantity, unit price, discounted price, and applied discounts.
     *
     * Example output:
     * +------------+-----------+-----+------------+------------------+------------------+
     * | Product    | SKU       | Qty | Unit Price | After Discounted | Discount Details |
     * +------------+-----------+-----+------------+------------------+------------------+
     * | iPhone 15  | IPH-15    |   2 |    $999.00 |          $899.10 | Holiday: 10%     |
     * +------------+-----------+-----+------------+------------------+------------------+
     */
    private function buildItemsTable(SubOrder $subOrder): string
    {
        // Format the items data for display in the table.
        $itemsData = $subOrder->items->map(function($item) {
            // Format the discount details for display.
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

        // Calculate the maximum width for the discount details column.
        // This ensures the table column is wide enough to fit the longest discount text.
        $discountColWidth = max(16, $itemsData->max(fn($item) => strlen($item['discount_details'])));
        $itemsHeader = sprintf("| Product                        | SKU                  | Qty | Unit Price | After Discounted | %-{$discountColWidth}s |", "Discount Details");
        $itemsSeparator = "+--------------------------------+----------------------+-----+------------+------------------+" . str_repeat('-', $discountColWidth + 2) . "+";

        // Format each item as a row in the table.
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

        return "\n{$itemsSeparator}\n{$itemsHeader}\n{$itemsSeparator}\n{$itemsRows}\n{$itemsSeparator}";
    }
}
