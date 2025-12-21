<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
    ];

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'product_vendors')->withPivot('price');
    }

    public function productVendors()
    {
        return $this->hasMany(ProductVendor::class);
    }
}
