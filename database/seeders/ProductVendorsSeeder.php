<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductVendorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendorsQuantity = (int) $this->command->ask('How many vendors?', 1000);
        $productQuantity = (int) $this->command->ask('How many products?', 10000);

        // Seeding Vendors
        $this->command->info('Seeding Vendors...');
        $progressBar = $this->command->getOutput()->createProgressBar($vendorsQuantity);

        collect(range(1, $vendorsQuantity))
            ->chunk(100)
            ->each(function ($chunk) use ($progressBar) {
                $vendors = Vendor::factory()->count($chunk->count())->make()->toArray();
                Vendor::insert($vendors);
                $progressBar->advance($chunk->count());
            });

        $progressBar->finish();
        $this->command->newLine();

        // Seeding Products
        $this->command->info('Seeding Products...');
        $progressBar = $this->command->getOutput()->createProgressBar($productQuantity);

        collect(range(1, $productQuantity))
            ->chunk(1000)
            ->each(function ($chunk) use ($progressBar) {
                $products = Product::factory()->count($chunk->count())->make()->toArray();
                Product::insert($products);
                $progressBar->advance($chunk->count());
            });

        $progressBar->finish();
        $this->command->newLine();

        // Seeding Product Vendors
        $this->command->info('Seeding Product Vendors...');
        $totalProducts = Product::count();
        $progressBar = $this->command->getOutput()->createProgressBar($totalProducts);

        $vendorIds = Vendor::pluck('id')->toArray();
        Product::chunk(5000, function ($products) use ($vendorIds, $progressBar) {
            $productVendors = [];

            foreach ($products as $product) {
                $basePrice = rand(100, 1000);
                $listOfVendors = fake()->randomElements($vendorIds, rand(1, min(10, count($vendorIds))));

                foreach ($listOfVendors as $vendorId) {
                    $productVendors[] = ProductVendor::factory()
                        ->forProduct($product)
                        ->withBasePrice($basePrice)
                        ->make(['vendor_id' => $vendorId])
                        ->toArray();

                    if (count($productVendors) > 1000) {
                        ProductVendor::insert($productVendors);
                        $productVendors = [];
                    }
                }
                $progressBar->advance();
            }

            if (count($productVendors) > 0) {
                ProductVendor::insert($productVendors);
            }
        });

        $progressBar->finish();
        $this->command->newLine();
    }
}
