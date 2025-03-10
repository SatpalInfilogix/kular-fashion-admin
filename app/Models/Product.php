<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

        // Check if the slug already exists, and if so, append a number to make it unique
        $counter = 1;
        while (self::where('slug', $slug)->exists()) {
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
        return $this->belongsTo(ProductType::class);
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
        return $this->hasMany(ProductWebImage::class);
    }

    public function webInfo()
    {
        return $this->hasOne(ProductWebInfo::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductWebSpecification::class);
    }
}
