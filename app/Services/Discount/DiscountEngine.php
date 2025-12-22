<?php

namespace App\Services\Discount;

use App\Models\Product;
use App\Models\ProductVendor;
use App\Services\Discount\DiscountRuleInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class DiscountEngine
{
    private array $rules = [];

    public function addRule(DiscountRuleInterface $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function getRules(): array
    {
        $rules = [];
        $rulesPath = app_path('Services/Discount/Rules');
        $namespace = 'App\\Services\\Discount\\Rules\\';

        foreach (File::files($rulesPath) as $file) {
            $className = $namespace . $file->getFilenameWithoutExtension();

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);

                if (!$reflection->isAbstract() && $reflection->implementsInterface(DiscountRuleInterface::class)) {
                    $rules[] = new $className();
                }
            }
        }

        return $rules;
    }

    public function applyDiscounts(ProductVendor $productVendor, int $quantity): float
    {

        $rules = $this->getRules();
        $discounts = [];
        foreach ($rules as $rule){
            if ($rule->isApplicable($productVendor,$quantity)) {
                $discounts[] = $rule->apply($productVendor,$quantity);
            }
        }

        $highestDiscount = count($discounts) ? max($discounts) : 1;

        return $productVendor->price * $highestDiscount;
    }
}
