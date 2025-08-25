<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListCollection;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use App\Models\Wishlist;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Auth;

class ProductController extends Controller
{
    public function index(Request $request)
{
    try {
        $query = Product::with([
            'brand',
            'department',
            'productType',
            'webImage',
            'quantities',
            'colors.colorDetail',
            'sizes.sizeDetail',
            'webInfo',
            'wishlist',
            'tags'
        ]);


        $query->where(function ($q) {
            $q->whereHas('webInfo', fn($q) => $q->where('status', 1))
                ->orWhere(function ($q) {
                    $q->whereHas('webInfo', fn($q) => $q->where('status', 2))
                        ->whereHas('quantities', function ($q) {
                            $q->select('product_id')
                                ->groupBy('product_id')
                                ->havingRaw('SUM(quantity) > 0');
                        });
                })
                ->orWhereDoesntHave('webInfo');
        });


        if ($request->filled('department_slug')) {
            $query->whereHas('department', fn($q) => $q->where('slug', $request->department_slug));
        }


        if ($request->filled('product_type_slug')) {
            $query->whereHas('productType', fn($q) => $q->where('slug', $request->product_type_slug));
        }

        if ($request->filled('brands')) {
            $query->whereIn('brand_id', explode(',', $request->brands));
        }

        if ($request->filled('product_ids')) {
            $query->whereIn('id', explode(',', $request->product_ids));
        }


        if ($request->filled('product_types')) {
            $query->whereHas('productType', fn($q) => $q->whereIn('id', explode(',', $request->product_types)));
        }


        if ($request->filled('sizes')) {
            $query->whereHas('sizes', fn($q) => $q->whereIn('size_id', explode(',', $request->sizes)));
        }


        if ($request->filled('colors')) {
            $query->whereHas('colors', fn($q) => $q->whereIn('color_id', explode(',', $request->colors)));
        }


        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereHas('sizes', fn($q) => $q->whereBetween('web_price', [
                $request->min_price,
                $request->max_price
            ]));
        }


        if ($request->filled('sort_by')) {
            $query->orderBy($request->sort_by, $request->input('sort_dir', 'asc'));
        } else {
            $query->orderBy('created_at', 'desc');
        }


        $perPage = $request->input('per_page', 20);
        $products = $query->paginate($perPage);


        $brands = Product::whereIn('id', $products->pluck('id'))
            ->with('brand')->get()
            ->pluck('brand')->filter()->unique('id')->values()
            ->map(fn($brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ]);

        $productTypes = Product::whereIn('id', $products->pluck('id'))
            ->with('productType')->get()
            ->pluck('productType')->filter()->unique('id')->values()
            ->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
            ]);

        $colors = Product::whereIn('id', $products->pluck('id'))
            ->with('colors.colorDetail')->get()
            ->pluck('colors')->flatten()->unique(fn($c) => $c->colorDetail?->id)
            ->map(fn($color) => [
                'id' => $color->color_id,
                'name' => $color->colorDetail?->name,
                'color_code' => $color->colorDetail?->ui_color_code,
            ]);

        $sizes = Product::whereIn('id', $products->pluck('id'))
            ->with('sizes.sizeDetail')->get()
            ->pluck('sizes')->flatten()->unique(fn($s) => $s->sizeDetail?->size)
            ->map(fn($s) => [
                'id' => $s->size_id,
                'name' => $s->sizeDetail?->size,
            ]);

        $minPrice = $products->min(fn($p) => $p->sizes->min('web_sale_price'));
        $maxPrice = $products->max(fn($p) => $p->sizes->max('web_price'));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
            'filters' => [
                'brands' => $brands,
                'product_types' => $productTypes,
                'colors' => $colors,
                'sizes' => $sizes,
                'price' => [
                    'min' => (float)$minPrice,
                    'max' => (float)$maxPrice,
                ]
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    protected function transformProducts($products)
    {
        $transformed = new Collection();
        foreach ($products as $product) {
            if ($product->webInfo && $product->webInfo->is_splitted_with_colors == 1) {
                foreach ($product->colors as $color) {
                    $clonedProduct = clone $product;
                    $clonedProduct->color = $color;
                    $transformed->push($clonedProduct);
                }
            } else {
                $transformed->push($product);
            }
        }
        return $transformed;
    }
    protected function paginateCollection($collection, $perPage, $currentPage)
    {
        $items = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $currentPage,
        );

        return $paginator;
    }
    public function show(Request $request, $slug)
    {
        try {
            $product = Product::with('brand', 'department', 'quantities', 'webInfo', 'webImage', 'specifications', 'productType', 'colors.colorDetail', 'sizes.sizeDetail')
                ->where('slug', $slug)->first();

            $relatedProducts = Product::with('brand', 'department', 'webInfo', 'webImage', 'specifications', 'productType', 'colors.colorDetail', 'sizes.sizeDetail')
                ->where('product_type_id', $product->product_type_id)
                ->where('id', '!=', $product->id)
                ->take(8)
                ->get();

            return response()->json(['success' => true, 'data' => new ProductResource($product, $relatedProducts)]);
        } catch (Exception  $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => (object)[]]);
        }
    }

    public function searchProduct(Request $request)
    {
        $searchValue = $request->get('searchValue');
        $brandId     = $request->get('brand_id');
        $colorId     = $request->get('color_id');
        $sizeId      = $request->get('size_id');

        $query = Product::with(['brand', 'colors', 'sizes']);

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', '%' . $searchValue . '%')
                ->orWhereHas('brand', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                })
                ->orWhereHas('colors', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                })
                ->orWhereHas('sizes', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                });
            });
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($colorId) {
            $query->whereHas('colors', function ($q) use ($colorId) {
                $q->where('id', $colorId);
            });
        }

        if ($sizeId) {
            $query->whereHas('sizes', function ($q) use ($sizeId) {
                $q->where('id', $sizeId);
            });
        }

        $products = $query->where('are_barcodes_printed', '>', 0)
                        ->where('status', 'Active')
                        ->latest()
                        ->take(50)
                        ->get();

        $brands = Brand::where('status', 'Active')->whereNull('deleted_at')->get();
        $colors = Color::where('status', 'Active')->whereNull('deleted_at')->get();
        $sizes  = Size::where('status', 'Active')->whereNull('deleted_at')->get();

        return response()->json([
            'products' => $products,
            'brands'   => $brands,
            'colors'   => $colors,
            'sizes'    => $sizes,
        ]);
    }

}
