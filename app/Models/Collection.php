<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cviebrock\EloquentSluggable\Sluggable;

class Collection extends Model
{
    use SoftDeletes, Sluggable;
    protected $guarded =[];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function listingOption()
    {
        return $this->hasOne(ListingOption::class, 'listable_id','id');
    }
}
