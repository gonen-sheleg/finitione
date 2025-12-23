<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_vendors')->withPivot('price');
    }

    public function productVendors()
    {
        return $this->hasMany(ProductVendor::class);
    }

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class);
    }
}
