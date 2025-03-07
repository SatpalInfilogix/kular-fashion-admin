<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListCollection;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductQuantity;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Department;
use App\Models\Coupon;
use Illuminate\Support\Carbon;
use App\Models\ProductType;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Base query with eager loading
            $query = Product::with([
                'brand',
                'department',
                'productType',
                'webImage',
                'specifications',
                'quantities',
                'colors.colorDetail',
                'sizes.sizeDetail',
                'webInfo'
            ]);

            // Filter products based on conditions
            $query->where(function ($q) {
                // Include products where `web_info.status` is 1
                $q->whereHas('webInfo', function ($q) {
                    $q->where('status', 1);
                });

                // Include products where `web_info.status` is 2 AND sum of `quantities.quantity` > 0
                $q->orWhereHas('webInfo', function ($q) {
                    $q->where('status', 2);
                })->whereHas('quantities', function ($q) {
                    $q->select('product_id')
                        ->groupBy('product_id')
                        ->havingRaw('SUM(quantity) > 0');
                });
            });

            // Filter by brand_id (if provided)
            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->input('brand_id'));
            }

            // Sort by a specific field (if provided)
            if ($request->has('sort_by')) {
                $sortField = $request->input('sort_by');
                $sortDirection = $request->input('sort_dir', 'asc'); // Default to 'asc' if not provided
                $query->orderBy($sortField, $sortDirection);
            }

            // Get all products (without pagination)
            $products = $query->get();

            // Transform products to handle `is_splitted_with_colors`
            $transformedProducts = $this->transformProducts($products);

            // Paginate the transformed products manually
            $perPage = $request->input('per_page', 10); // Default to 10 items per page
            $currentPage = $request->input('page', 1); // Default to page 1
            $paginatedProducts = $this->paginateCollection($transformedProducts, $perPage, $currentPage);

            $productCollection = new ProductListCollection($paginatedProducts);

            // Fetch all distinct brands and product types
            $brands = Product::whereIn('id', $products->pluck('id'))
                ->with('brand')->get()->pluck('brand')
                ->unique('id')->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                    ];
                });

            $productTypes = Product::whereIn('id', $products->pluck('id'))
                ->with('productType')->get()
                ->pluck('productType')->unique('id')->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                    ];
                });


            $colors = Product::whereIn('id', $products->pluck('id'))
                ->with('colors.colorDetail')
                ->get()
                ->pluck('colors')
                ->flatten()
                ->unique('id') // Ensure uniqueness by color id
                ->map(function ($color) {
                    return [
                        'id' => $color->id,
                        'name' => $color->colorDetail->name, // Assuming 'colorDetail' has the 'name'
                    ];
                });


            $sizes = Product::whereIn('id', $products->pluck('id'))
                ->with('sizes.sizeDetail')
                ->get()
                ->pluck('sizes')
                ->flatten()
                ->unique('id') // Ensure uniqueness by size id
                ->map(function ($size) {
                    return [
                        'id' => $size->id,
                        'name' => $size->sizeDetail->size, // Assuming 'sizeDetail' has the 'name'
                    ];
                });


            $minPrice = $products->min(function ($product) {
                return $product->sizes->min('web_price');
            });

            $maxPrice = $products->max(function ($product) {
                return $product->sizes->max('web_price');
            });

            // Return the paginated results as JSON
            return response()->json([
                'success' => true,
                'data' => $productCollection,
                'pagination' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                    'last_page' => $paginatedProducts->lastPage(),
                ],
                'filter' => [
                    'brands' => $brands,
                    'product_types' => $productTypes,
                    'colors' => $colors,
                    'sizes' => $sizes,
                    'price' => [
                        'min' => $minPrice,
                        'max' => $maxPrice,
                    ]
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform products to handle `is_splitted_with_colors`.
     *
     * @param \Illuminate\Support\Collection $products
     * @return \Illuminate\Support\Collection
     */
    protected function transformProducts($products)
    {
        $transformed = new Collection();

        foreach ($products as $product) {
            // Check if the product should be split by colors
            if ($product->webInfo && $product->webInfo->is_splitted_with_colors == 1) {
                // Repeat the product for each color
                foreach ($product->colors as $color) {
                    $clonedProduct = clone $product;
                    $clonedProduct->color = $color; // Attach the specific color
                    $transformed->push($clonedProduct);
                }
            } else {
                // Add the product as is
                $transformed->push($product);
            }
        }

        return $transformed;
    }

    /**
     * Paginate a collection manually.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param int $perPage
     * @param int $currentPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
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

    public function showProduct(Request $request, $product)
    {
        try {

            $product = Product::with('brand', 'department', 'webInfo', 'webImage', 'specifications', 'productType', 'colors.colorDetail', 'sizes.sizeDetail')
                ->where('id', $product)->first();
            if (!$product) {
                return response()->json(['success' => false, 'data' => (object)[]]);
            }

            $sizes = $product->sizes()->with('sizeDetail')->paginate($request->input('sizes_length', 10)) ?? collect([]);

            $colors = $product->colors()->with('colorDetail')->paginate($request->input('colors_length', 10)) ?? collect([]);

            return new ProductResource($product, $sizes, $colors);
        } catch (Exception  $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => (object)[]]);
        }
    }
}
