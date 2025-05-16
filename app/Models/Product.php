<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory,HasTranslations;
    
    public $translatable = ['name', 'description', 'status']; 
    protected $fillable = [
        'name',
        'description',
        'images',
        'price',
        'discounted_price',
        'quantity',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
         'price' => 'float', 
        'discounted_price' => 'float', 
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }
     public function getBuyableIdentifier($options = null)
    {
        return $this->id;
    }

    public function getBuyableDescription($options = null)
    {
         return $this->getTranslation('name', app()->getLocale());
    }

   
    public function getBuyablePrice($options = null) {
        return $this->price;
    }
}