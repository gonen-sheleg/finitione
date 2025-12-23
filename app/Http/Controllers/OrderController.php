<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Facades\OrderProcessor;
use App\Facades\PriceEngine;
use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Create a new order from the customer's cart.
     *
     * This function handles the complete order creation process:
     * 1. Validates the cart to ensure it has items with valid products and quantities.
     * 2. For each product, finds the vendor offering the best price.
     * 3. Applies any available discounts to reduce the final price.
     * 4. Groups the items by vendor and creates separate sub-orders for each.
     * 5. Notifies each vendor about their sub-order.
     * 6. Returns the order details including original and discounted prices.
     */
    public function create(Request $request)
    {
        // Validate the incoming cart request.
        // The cart must have at least one item.
        // Each item must have a valid product SKU that exists in the database.
        // Each item must have a quantity of at least 1.
        $validator = Validator::make(
            $request->all(),
            [
                'cart' => 'required|array|min:1',
                'cart.*.sku' => 'required|string|exists:products,sku',
                'cart.*.quantity' => 'required|integer|min:1',
            ],
            [
                'cart.*.sku.exists' => "The selected product is currently unavailable or doesn't exist.",
                'cart.*.sku.required' => 'Please select a product for this item.',
                'cart.*.quantity.required' => 'Please enter the amount you wish to order.',
                'cart.*.quantity.integer' => 'The quantity must be a whole number.',
                'cart.*.quantity.min' => 'The minimum order quantity is :min.',
            ],
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $cart = collect($validated['cart']);

            // Process the cart and create an order.
            $subOrders = OrderProcessor::processCart($cart);

            // Build the response by going through all sub-orders.
            // For each item, return the product SKU, ordered quantity,
            // original price, and the final price after any discounts applied.
            $response = collect($subOrders)->flatMap(
                fn($suborder) => $suborder->items()->with('product')->get()->map(
                    fn($item) => [
                        'sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'price_after_discount' => $item->unit_final_price,
                    ],
                ),
            );

            return response()->json($response);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
