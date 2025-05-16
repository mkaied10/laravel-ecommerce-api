<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory,HasTranslations;
    public $translatable = ['name', 'description', 'status'];
    protected $fillable = ['name', 'description', 'image', 'status'];
    
      public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }
}
