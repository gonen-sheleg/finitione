<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVendor>
 */
class ProductVendorFactory extends Factory
{
    protected $model = ProductVendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'vendor_id' => Vendor::factory(),
            'price' => round(rand(100, 1000) * (1 + rand(-10, 10) / 100), 2),
            'quantity' => rand(1, 100),
        ];
    }

    /**
     * Configure the factory to use existing product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Configure the factory with a base price (applies +-10% variation).
     */
    public function withBasePrice(float $basePrice): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => round($basePrice * (1 + rand(-10, 10) / 100), 2),
        ]);
    }

    /**
     * Configure the factory to use existing vendor.
     */
    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
        ]);
    }
}
