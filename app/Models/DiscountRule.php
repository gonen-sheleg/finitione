<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'config',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
