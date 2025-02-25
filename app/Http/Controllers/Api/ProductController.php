<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductQuantity;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Department;
use App\Models\ProductType;


class ProductController extends Controller
{
          
    public function index(Request $request){

        $query = Product::with('brand', 'department', 'quantities', 'productType', 'colors', 'colors.colorDetail', 'sizes', 'sizes.sizeDetail');

        if ($request->has('product_id') && $request->product_id) {
            $query->where('id', $request->product_id);
        }

        if ($request->has('brand_id') && $request->brand_id) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('id', $request->brand_id);
            });
        }

        if ($request->has('department_id') && $request->department_id) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }
        if ($request->has('color_id') && $request->color_id) {
            $query->whereHas('colors', function ($q) use ($request) {
                $q->where('color_id', $request->color_id);
            });
        }
       
        if ($request->has('size_id') && $request->size_id) {
            $query->whereHas('sizes', function ($q) use ($request) {
                $q->where('size_id', $request->size_id);
            });
        }
        $products = $query->orderBy('updated_at', 'desc') 
            ->paginate($request->input('length', 2));
            
        return new ProductCollection($products);

    }

}
