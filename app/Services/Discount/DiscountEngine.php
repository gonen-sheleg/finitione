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
            try{
                $className = $namespace . $file->getFilenameWithoutExtension();

                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    if (!$reflection->isAbstract() && $reflection->implementsInterface(DiscountRuleInterface::class)) {
                        $rules[] = new $className();
                    }
                }
            }catch (\Exception $e){
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

        foreach ($rules as $rule){
            try{
                $discountName = Str::lower(Str::before(class_basename($rule), 'DiscountRule'));
                logInfo("Checking discount rule for $discountName", 'green');
                if ($rule->isApplicable($productVendor,$quantity)) {
                    logInfo("Discount rule applied to $discountName", 'green');
                    $discountDetails[] = [
                        'name' => $discountName,
                        'discount' => $rule->apply($productVendor,$quantity),
                    ];
                    $discount += $rule->apply($productVendor,$quantity);
                    logInfo("Discount applied to $discountName: $discount", 'green');
                }else{
                    logInfo("Not applicable for $discountName", 'red');
                }
            }catch (\Exception $e){
                Log::error($e);
            }

        }

        return [
            'details' => $discountDetails,
            'price' => $productVendor->price * (1 - $discount),
        ] ;
    }
}
