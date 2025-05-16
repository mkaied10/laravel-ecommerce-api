<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Order extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['order_status', 'payment_status'];

    protected $fillable = [
        'order_number',
        'user_id',
        'delivery_address',
        'order_status',
        'payment_method',
        'payment_status',
        'total_amount',
    ];

    protected $casts = [
        'delivery_address' => 'array',
        'order_status' => 'array',
        'payment_status' => 'array',
        'payment_method' => 'string',
        'total_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}