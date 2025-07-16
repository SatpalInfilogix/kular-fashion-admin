<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubmenuOption;

class SubmenuOptionController extends Controller
{
    public function index()
    {
        return SubmenuOption::all()->map(function ($option) {
            return [
                'id' => $option->id,
                'name' => $option->text,
                'icon' => $option->icon,
            ];
        });
    }
}
