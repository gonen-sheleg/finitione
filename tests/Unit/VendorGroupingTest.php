<?php

namespace Tests\Unit;

use App\Facades\OrderProcessor;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorGroupingTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        // Act as authenticated user
        $this->actingAs($this->user);

        // Create vendors
        $this->vendor1 = Vendor::create([
            'name' => 'Vendor One',
            'email' => 'vendor1@test.com',
        ]);

        $this->vendor2 = Vendor::create([
            'name' => 'Vendor Two',
            'email' => 'vendor2@test.com',
        ]);

        // Create products
        $this->product1 = Product::create([
            'category_id' => 1,
            'sku' => 'PROD-001',
            'name' => 'Product One',
            'description' => 'First test product',
        ]);

        $this->product2 = Product::create([
            'category_id' => 1,
            'sku' => 'PROD-002',
            'name' => 'Product Two',
            'description' => 'Second test product',
        ]);

        $this->product3 = Product::create([
            'category_id' => 2,
            'sku' => 'PROD-003',
            'name' => 'Product Three',
            'description' => 'Third test product',
        ]);

        // Create product-vendor relationships
        // Vendor 1 sells Product 1 and Product 2
        ProductVendor::create([
            'product_id' => $this->product1->id,
            'vendor_id' => $this->vendor1->id,
            'price' => 100.00,
            'quantity' => 50,
        ]);

        ProductVendor::create([
            'product_id' => $this->product2->id,
            'vendor_id' => $this->vendor1->id,
            'price' => 150.00,
            'quantity' => 30,
        ]);

        // Vendor 2 sells Product 2 (cheaper) and Product 3
        ProductVendor::create([
            'product_id' => $this->product2->id,
            'vendor_id' => $this->vendor2->id,
            'price' => 120.00,
            'quantity' => 40,
        ]);

        ProductVendor::create([
            'product_id' => $this->product3->id,
            'vendor_id' => $this->vendor2->id,
            'price' => 200.00,
            'quantity' => 25,
        ]);
    }

    /**
     * Test that OrderProcessor::processCart groups items by vendor correctly.
     *
     * This test verifies:
     * - Cart with 3 products from 2 vendors is processed correctly
     * - PriceEngine selects the cheapest vendor for each product
     * - Sub-orders are created per vendor (one sub-order per vendor)
     * - Each sub-order contains only items belonging to that vendor
     * - Item counts and quantities are correctly assigned to each sub-order
     * - Database records are created for sub_orders table
     */
    public function test_proper_vendor_grouping(): void
    {
        // Verify data was inserted correctly
        $this->assertDatabaseCount('vendors', 2);
        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('product_vendors', 4);

        // Create a cart with items from different vendors
        // PROD-001 -> Vendor 1 only
        // PROD-002 -> Vendor 2 (cheaper at 120) vs Vendor 1 (150)
        // PROD-003 -> Vendor 2 only
        $cart = collect([
            ['sku' => 'PROD-001', 'quantity' => 5],  // Will go to Vendor 1
            ['sku' => 'PROD-002', 'quantity' => 3],  // Will go to Vendor 2 (cheaper)
            ['sku' => 'PROD-003', 'quantity' => 2],  // Will go to Vendor 2
        ]);

        // Process the cart
        $subOrders = OrderProcessor::processCart($cart);

        // Should create 2 sub-orders (one per vendor)
        $this->assertCount(2, $subOrders);

        // Verify sub-orders were created in database
        $this->assertDatabaseCount('sub_orders', 2);

        // Get sub-orders grouped by vendor
        $vendor1SubOrder = SubOrder::where('vendor_id', $this->vendor1->id)->first();
        $vendor2SubOrder = SubOrder::where('vendor_id', $this->vendor2->id)->first();

        // Vendor 1 should have 1 item (PROD-001)
        $this->assertNotNull($vendor1SubOrder);
        $this->assertEquals(1, $vendor1SubOrder->items()->count());
        $this->assertEquals($this->product1->id, $vendor1SubOrder->items()->first()->product_id);
        $this->assertEquals(5, $vendor1SubOrder->sub_total_quantity);

        // Vendor 2 should have 2 items (PROD-002 and PROD-003)
        $this->assertNotNull($vendor2SubOrder);
        $this->assertEquals(2, $vendor2SubOrder->items()->count());
        $this->assertEquals(5, $vendor2SubOrder->sub_total_quantity); // 3 + 2

        // Verify items in Vendor 2's sub-order
        $vendor2ProductIds = $vendor2SubOrder->items()->pluck('product_id')->toArray();
        $this->assertContains($this->product2->id, $vendor2ProductIds);
        $this->assertContains($this->product3->id, $vendor2ProductIds);
    }
}
