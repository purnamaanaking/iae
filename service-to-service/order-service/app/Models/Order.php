<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'code',
        'product_uuid',
        'user_uuid',
        'status',
        'total_price',
        'quantity',
    ];
}
