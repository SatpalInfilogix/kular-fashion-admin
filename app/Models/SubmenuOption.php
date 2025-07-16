<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmenuOption extends Model
{
    use HasFactory;

    protected $table = 'submenu_options';

    protected $fillable = [
        'icon',
        'text',
        
    ];
}
