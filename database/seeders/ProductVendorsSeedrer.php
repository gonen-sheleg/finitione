<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductVendorsSeedrer extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $vendorsQuantity = 1000;
        $productQuantity = 10000;

        // Seeding Vendors
        $this->command->info('Seeding Vendors...');
        $progressBar = $this->command->getOutput()->createProgressBar($vendorsQuantity);

        $vendors = [];
        for($i = 0; $i < $vendorsQuantity; $i++){

            $vendors[] = [
                'name' => fake()->company(),
                'email' => fake()->unique()->email(),
            ];

            $progressBar->advance();

            if(count($vendors) > 100){
                Vendor::insert($vendors);
                $vendors = [];
            }
        }
        Vendor::insert($vendors);
        $progressBar->finish();
        $this->command->newLine();


        // Seeding Products
        $this->command->info('Seeding Products...');
        $progressBar = $this->command->getOutput()->createProgressBar($productQuantity);

        $products = [];
        for($i = 0; $i < $productQuantity; $i++){

            $products[] = [
                'sku' => fake()->unique()->bothify('####-####-####-####'),
                'name' => fake()->slug(rand(5, 10)),
                'description' => fake()->text(rand(50, 150)),
            ];


            $progressBar->advance();

            if(count($products) > 1000){
                Product::insert($products);
                $products = [];
            }
        }
        Product::insert($products);
        $progressBar->finish();
        $this->command->newLine();



        // Seeding Product Vendors
        $this->command->info('Seeding Product Vendors...');
        $totalProducts = Product::count();
        $progressBar = $this->command->getOutput()->createProgressBar($totalProducts);

        $productVendors = [];
        $vendorIds = Vendor::pluck('id')->toArray();
        Product::chunk(5000, function ($products) use (&$productVendors, $vendorIds, $progressBar) {

            foreach ($products as $product) {
                $price = rand(100, 1000);
                $listOfVendors = fake()->randomElements($vendorIds, rand(1, min(10, count($vendorIds))));
                foreach ($listOfVendors as $vendorId) {
                    $productVendors[] = [
                        'product_id' => $product->id,
                        'vendor_id' => $vendorId,
                        'price' => round($price * (1 + rand(-10, 10) / 100), 2),
                        'quantity' => rand(1, 100),
                    ];

                    if(count($productVendors) > 1000){
                        ProductVendor::insert($productVendors);
                        $productVendors = [];
                    }
                }
                $progressBar->advance();
            }
        });
        ProductVendor::insert($productVendors);
        $progressBar->finish();
        $this->command->newLine();


    }
}
