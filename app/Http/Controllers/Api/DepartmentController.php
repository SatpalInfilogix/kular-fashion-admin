<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DepartmentCollection;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function departments(Request $request)
    {
        $departments = Department::where('status','Active')->paginate($request->input('length', 10));
        if($departments)
        {
            return new DepartmentCollection($departments); 
        }
    }
}
