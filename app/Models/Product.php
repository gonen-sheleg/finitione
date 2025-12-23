<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',

    ];

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'product_vendors')->withPivot('price');
    }

    public function productVendors()
    {
        return $this->hasMany(ProductVendor::class);
    }

    public function scopeBySku($query, $sku)
    {
        return $query->where('sku', $sku);
    }
}
