<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Discount\DiscountEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTest extends TestCase
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
     * Test that quantity discounts are correctly applied.
     *
     * This test verifies:
     * - No discount is applied for quantity < 10
     * - 5% discount is applied for quantity >= 10
     * - 7% discount is applied for quantity >= 20
     * - 9% discount is applied for quantity >= 30
     * - 11% discount is applied for quantity >= 40
     * - 15% discount is applied for quantity >= 50
     * - Discount details array contains correct name and discount percentage
     * - Final price is correctly calculated after applying quantity discount
 */
    public function test_quantity_discount_application(): void
    {
        $discountEngine = new DiscountEngine();

        // Get a product vendor to test with without category discount
        $productVendor = ProductVendor::where('product_id', $this->product1->id)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        // Test no discount for quantity < 10
        $result = $discountEngine->applyDiscounts($productVendor, 5);
        $this->assertEquals(100.00, $result['price']);
        $this->assertEmpty($result['details']);

        // Test 5% discount for quantity >= 10
        $result = $discountEngine->applyDiscounts($productVendor, 10);
        $this->assertEquals(95.00, $result['price']); // 100 * (1 - 0.05)
        $this->assertNotEmpty($result['details']);
        $this->assertEquals('quantity', $result['details'][0]['name']);
        $this->assertEquals(0.05, $result['details'][0]['discount']);

        // Test 7% discount for quantity >= 20
        $result = $discountEngine->applyDiscounts($productVendor, 20);
        $this->assertEquals(93.00, $result['price']); // 100 * (1 - 0.07)
        $this->assertEquals(0.07, $result['details'][0]['discount']);

        // Test 9% discount for quantity >= 30
        $result = $discountEngine->applyDiscounts($productVendor, 30);
        $this->assertEquals(91.00, $result['price']); // 100 * (1 - 0.09)
        $this->assertEquals(0.09, $result['details'][0]['discount']);

        // Test 11% discount for quantity >= 40
        $result = $discountEngine->applyDiscounts($productVendor, 40);
        $this->assertEquals(89.00, $result['price']); // 100 * (1 - 0.11)
        $this->assertEquals(0.11, $result['details'][0]['discount']);

        // Test 15% discount for quantity >= 50
        $result = $discountEngine->applyDiscounts($productVendor, 50);
        $this->assertEquals(85.00, $result['price']); // 100 * (1 - 0.15)
        $this->assertEquals(0.15, $result['details'][0]['discount']);
    }

    /**
     * Test that loyalty discounts are correctly applied based on user's order history.
     *
     * This test verifies:
     * - No loyalty discount when user has 0 orders
     * - 5% loyalty discount when user has > 5 orders
     * - 10% loyalty discount when user has >= 10 orders
     * - 12% loyalty discount when user has >= 20 orders
     * - 15% loyalty discount when user has >= 30 orders
     * - Discount details array contains 'loyaltycustomer' name with correct percentage
     * - Final price is correctly calculated after applying loyalty discount
     */
    public function test_loyalty_discount_application(): void
    {

        $discountEngine = new DiscountEngine();

        // Get a product vendor to test with
        $productVendor = ProductVendor::where('product_id', $this->product1->id)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        // Test no loyalty discount when user has 0 orders (combined with no quantity discount)
        $result = $discountEngine->applyDiscounts($productVendor, 5);
        $this->assertEquals(100.00, $result['price']);
        $this->assertEmpty($result['details']);

        // Create 6 orders for the user (> 5 required for loyalty discount)
        $this->createOrdersForUser(6);

        // Test 5% loyalty discount for > 5 orders (combined with no quantity discount)
        $result = $discountEngine->applyDiscounts($productVendor, 5);
        $loyaltyDiscount = collect($result['details'])->firstWhere('name', '=', 'loyaltycustomer');

        $this->assertNotNull($loyaltyDiscount);
        $this->assertEquals(0.05, $loyaltyDiscount['discount']);
        $this->assertEquals(95.00, $result['price']); // 100 * (1 - 0.05)

        // Create more orders to reach 10 total
        $this->createOrdersForUser(4);

        // Test 10% loyalty discount for >= 10 orders (combined with no quantity discount)
        $result = $discountEngine->applyDiscounts($productVendor, 5);
        $loyaltyDiscount = collect($result['details'])->firstWhere('name', '=', 'loyaltycustomer');
        $this->assertEquals(0.1, $loyaltyDiscount['discount']);
        $this->assertEquals(90.00, $result['price']); // 100 * (1 - 0.10)

        // Create more orders to reach 20 total
        $this->createOrdersForUser(10);

        // Test 12% loyalty discount for >= 20 orders (combined with no quantity discount)
        $result = $discountEngine->applyDiscounts($productVendor, 5);
        $loyaltyDiscount = collect($result['details'])->firstWhere('name', '=', 'loyaltycustomer');
        $this->assertEquals(0.12, $loyaltyDiscount['discount']);
        $this->assertEquals(88.00, $result['price']); // 100 * (1 - 0.12)

        // Create more orders to reach 30 total
        $this->createOrdersForUser(10);

        // Test 15% loyalty discount for >= 30 orders (combined with no quantity discount)
        $result = $discountEngine->applyDiscounts($productVendor, 5);
        $loyaltyDiscount = collect($result['details'])->firstWhere('name', '=', 'loyaltycustomer');
        $this->assertEquals(0.15, $loyaltyDiscount['discount']);
        $this->assertEquals(85.00, $result['price']); // 100 * (1 - 0.15)
    }


    /**
     * Test that category discounts are correctly applied based on product category.
     *
     * This test verifies:
     * - No category discount for category_id = 1
     * - 5% category discount for category_id = 2
     * - 7% category discount for category_id = 5
     * - 9% category discount for category_id = 7
     * - 11% category discount for category_id = 9
     * - Discount details array contains 'category' name with correct percentage
     * - Final price is correctly calculated after applying category discount
     */
    public function test_category_discount_application(): void
    {
        $discountEngine = new DiscountEngine();

        // Product 1 has category_id = 1 (no category discount)
        $productVendor1 = ProductVendor::where('product_id', $this->product1->id)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        $result = $discountEngine->applyDiscounts($productVendor1, 5);
        $categoryDiscount = collect($result['details'])->firstWhere('name', '=', 'category');
        $this->assertNull($categoryDiscount);
        $this->assertEquals(100.00, $result['price']);

        // Product 3 has category_id = 2 (5% category discount)
        $productVendor3 = ProductVendor::where('product_id', $this->product3->id)
            ->where('vendor_id', $this->vendor2->id)
            ->first();

        $result = $discountEngine->applyDiscounts($productVendor3, 5);
        $categoryDiscount = collect($result['details'])->firstWhere('name', '=', 'category');
        $this->assertNotNull($categoryDiscount);
        $this->assertEquals(0.05, $categoryDiscount['discount']);
        $this->assertEquals(190.00, $result['price']); // 200 * (1 - 0.05)

        // Create products with other discount categories
        $product5 = Product::create([
            'category_id' => 5,
            'sku' => 'PROD-005',
            'name' => 'Product Five',
            'description' => 'Category 5 product',
        ]);
        ProductVendor::create([
            'product_id' => $product5->id,
            'vendor_id' => $this->vendor1->id,
            'price' => 100.00,
            'quantity' => 50,
        ]);

        // Product 7 has category_id = 7 (9% category discount)
        $product7 = Product::create([
            'category_id' => 7,
            'sku' => 'PROD-007',
            'name' => 'Product Seven',
            'description' => 'Category 7 product',
        ]);
        ProductVendor::create([
            'product_id' => $product7->id,
            'vendor_id' => $this->vendor1->id,
            'price' => 100.00,
            'quantity' => 50,
        ]);

        // Product 9 has category_id = 9 (11% category discount)
        $product9 = Product::create([
            'category_id' => 9,
            'sku' => 'PROD-009',
            'name' => 'Product Nine',
            'description' => 'Category 9 product',
        ]);
        ProductVendor::create([
            'product_id' => $product9->id,
            'vendor_id' => $this->vendor1->id,
            'price' => 100.00,
            'quantity' => 50,
        ]);

        // Test category 5 -> 7% discount
        $pv5 = ProductVendor::where('product_id', $product5->id)->first();
        $result = $discountEngine->applyDiscounts($pv5, 5);
        $categoryDiscount = collect($result['details'])->firstWhere('name', '=', 'category');
        $this->assertEquals(0.07, $categoryDiscount['discount']);
        $this->assertEquals(93.00, $result['price']); // 100 * (1 - 0.07)

        // Test category 7 -> 9% discount
        $pv7 = ProductVendor::where('product_id', $product7->id)->first();
        $result = $discountEngine->applyDiscounts($pv7, 5);
        $categoryDiscount = collect($result['details'])->firstWhere('name', '=', 'category');
        $this->assertEquals(0.09, $categoryDiscount['discount']);
        $this->assertEquals(91.00, $result['price']); // 100 * (1 - 0.09)

        // Test category 9 -> 11% discount
        $pv9 = ProductVendor::where('product_id', $product9->id)->first();
        $result = $discountEngine->applyDiscounts($pv9, 5);
        $categoryDiscount = collect($result['details'])->firstWhere('name', '=', 'category');
        $this->assertEquals(0.11, $categoryDiscount['discount']);
        $this->assertEquals(89.00, $result['price']); // 100 * (1 - 0.11)
    }


    /**
     * Test combined quantity, loyalty, and category discounts.
     *
     * This test verifies:
     * - Multiple discount types can be applied simultaneously
     * - 5% quantity discount (qty >= 10) is applied
     * - 10% loyalty discount (>= 10 orders) is applied
     * - 5% category discount (category_id = 2) is applied
     * - All three discounts appear in the details array
     * - Final price is calculated as: base_price * (1 - sum_of_all_discounts)
     * - Example: 200 * (1 - 0.05 - 0.10 - 0.05) = 160.00
     */
    public function test_combined_quantity_loyalty_and_category_discounts(): void
    {
        $discountEngine = new DiscountEngine();

        // Get product vendor with category discount (product3 has category_id=2 -> 5% category discount)
        $productVendor = ProductVendor::where('product_id', $this->product3->id)
            ->where('vendor_id', $this->vendor2->id)
            ->first();

        // Create 10 orders for 10% loyalty discount
        $this->createOrdersForUser(10);

        // Test combined: 5% quantity (qty >= 10) + 10% loyalty (>= 10 orders) + 5% category (category_id=2)
        $result = $discountEngine->applyDiscounts($productVendor, 10);

        $quantityDiscount = collect($result['details'])->firstWhere('name', '=', 'quantity');
        $loyaltyDiscount = collect($result['details'])->firstWhere('name', '=', 'loyaltycustomer');
        $categoryDiscount = collect($result['details'])->firstWhere('name', '=', 'category');

        $this->assertNotNull($quantityDiscount);
        $this->assertNotNull($loyaltyDiscount);
        $this->assertNotNull($categoryDiscount);
        $this->assertEquals(0.05, $quantityDiscount['discount']);
        $this->assertEquals(0.1, $loyaltyDiscount['discount']);
        $this->assertEquals(0.05, $categoryDiscount['discount']);

        // Combined discount: 200 * (1 - 0.05 - 0.10 - 0.05) = 200 * 0.80 = 160
        $this->assertEquals(160.00, $result['price']);
    }

    /**
     * Helper to create orders for the authenticated user.
     */
    private function createOrdersForUser(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Order::create([
                'user_id' => $this->user->id,
                'total_price' => 100.00,
                'total_final_price' => 95.00,
                'total_quantity' => 1,
                'cart' => [['sku' => 'PROD-001', 'quantity' => 1]],
            ]);
        }

    }
}
