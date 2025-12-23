<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'total_price', 'total_final_price', 'total_quantity', 'cart', 'status'];

    protected $casts = [
        'cart' => 'array',
    ];

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
