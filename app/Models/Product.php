<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\ProductType;
use Auth;

class Product extends Model
{
    use SoftDeletes, Sluggable;
    protected $guarded = [];
    public $timestamps = true;

    protected $casts = [
        'in_date' => 'datetime',
        'last_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            $lastProduct = self::orderBy('id', 'desc')->first();
            $product->article_code = $lastProduct ? $lastProduct->article_code + 1 : 300001;
        });

        static::saving(function ($product) {
            $product->slug = $product->generateSlug();
        });
    }
    public function scopeWithAvailableSizes($query)
    {
        return $query->whereHas('sizes.quantities', function($q) {
            $q->where('total_quantity', '>', 0);
        });
    }
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    protected function generateSlug()
    {
        $slug = Str::slug($this->name);
        $originalSlug = $slug;

        $counter = 1;
        while (self::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function tags()
    {
        return $this->hasMany(ProductTag::class);
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function quantities()
    {
        return $this->hasMany(ProductQuantity::class);
    }

    public function webSpecification()
    {
        return $this->hasMany(ProductWebSpecification::class);
    }

    public function webImage()
    {
        return $this->hasMany(ProductWebImage::class,'product_id');
    }

    public function webInfo()
    {
        return $this->hasOne(ProductWebInfo::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductWebSpecification::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class, 'product_id');
    }
}
