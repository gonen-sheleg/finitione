<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'sub_order_id',
        'product_id',
        'vendor_id',
        'quantity',
        'unit_price',
        'unit_final_price',
        'discounts',
    ];

    protected $casts = [
        'discounts' => 'array',
    ];

    public function subOrder()
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
