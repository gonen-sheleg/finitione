<?php

namespace App\Services\Discount;

use App\Models\ProductVendor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;

class DiscountEngine
{
    public function getRules(): array
    {
        $rulesPath = app_path('Services/Discount/Rules');
        $namespace = 'App\\Services\\Discount\\Rules\\';
        $rules = [];
        foreach (File::files($rulesPath) as $file) {
            try {
                $className = $namespace . $file->getFilenameWithoutExtension();

                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    if (!$reflection->isAbstract() && $reflection->implementsInterface(DiscountRuleInterface::class)) {
                        $rules[] = new $className();
                    }
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        return $rules;
    }

    public function applyDiscounts(ProductVendor $productVendor, int $quantity): array
    {
        $rules = $this->getRules();
        $discount = 0;
        $discountDetails = [];


        logInfo("Discount processing started for product {$productVendor->product->sku}", 'magenta');

        foreach ($rules as $index => $rule) {
            try {
                $discountName = Str::lower(Str::before(class_basename($rule), 'DiscountRule'));
                logInfo("Discount name: $discountName", 'green');
                if ($rule->isApplicable($productVendor, $quantity)) {

                    $value = $rule->apply($productVendor, $quantity);

                    $discountDetails[] = [
                        'name' => $discountName,
                        'discount' => $value,
                    ];

                    $discount += $value;
                    logInfo("Discount value: $value", 'green');
                } else {
                    logInfo("Not applicable for $discountName", 'red');
                }


                // Print separator sign between rules
                if($index + 1 != count($rules)){
                    logInfo(str_repeat('-',50));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        // Print separator sign after all rules separated products
        logInfo(str_repeat('-',50));

        return [
            'details' => $discountDetails,
            'price' => $productVendor->price * (1 - $discount),
        ];
    }
}
