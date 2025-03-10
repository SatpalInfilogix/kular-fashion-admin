<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListCollection;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
                'webInfo'
            ]);

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

                $q->orWhereDoesntHave('webInfo');
            });

            if ($request->has('brands')) {
                $brands = explode(',', $request->input('brands'));
                $query->whereIn('brand_id', $brands);
            }

            /* if ($request->has('categories')) {
                $categories = explode(',', $request->input('categories'));
                $query->whereHas('productType', function ($q) use ($categories) {
                    $q->whereIn('id', $categories);
                });
            } */

            /* if ($request->has('sizes')) {
                $sizes = explode(',', $request->input('sizes'));
                $query->whereHas('sizes', function ($q) use ($sizes) {
                    $q->whereIn('id', $sizes);
                });
            }


            if ($request->has('colors')) {
                $colors = explode(',', $request->input('colors'));
                $query->whereHas('colors', function ($q) use ($colors) {
                    $q->whereIn('id', $colors);
                });
            } */
            
            if ($request->has('min_price') && $request->has('max_price')) {
                $minPrice = $request->input('min_price');
                $maxPrice = $request->input('max_price');
                $query->whereHas('sizes', function ($q) use ($minPrice, $maxPrice) {
                    $q->whereBetween('web_price', [$minPrice, $maxPrice]);
                });
            }

            // Sort by a specific field (if provided)
            if ($request->has('sort_by')) {
                $sortField = $request->input('sort_by');
                $sortDirection = $request->input('sort_dir', 'asc');
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
                ->with('brand')->get()
                ->pluck('brand')->filter()
                ->unique('id')->values()->take(8)
                ->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                    ];
                });

            $productTypes = Product::whereIn('id', $products->pluck('id'))
                ->with('productType')->get()->take(8)
                ->pluck('productType')->unique('id')->flatten()->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                    ];
                });


            $colors = Product::whereIn('id', $products->pluck('id'))
                ->with('colors.colorDetail')->get()
                ->pluck('colors')->flatten()
                ->unique(function ($color) {
                    return $color->colorDetail ? $color->colorDetail->id : null;
                })->values()->take(9)
                ->map(function ($color) {
                    return [
                        'id' => $color->id,
                        'name' => $color->colorDetail ? $color->colorDetail->name : null,
                        'color_code' => $color->colorDetail ? $color->colorDetail->ui_color_code : null,
                    ];
                });

            $sizes = Product::whereIn('id', $products->pluck('id'))
                ->with('sizes.sizeDetail')->get()->pluck('sizes')->flatten()->unique('id')->take(10)
                ->map(function ($size) {
                    return [
                        'id' => $size->id,
                        'name' => $size->sizeDetail->size, // Assuming 'sizeDetail' has the 'name'
                    ];
                });


            $minPrice = $products->min(function ($product) {
                return $product->sizes->min('web_sale_price');
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
                    $clonedProduct->color = $color;
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

    public function show(Request $request, $slug)
    {
        try {

            $product = Product::with('brand', 'department', 'webInfo', 'webImage', 'specifications', 'productType', 'colors.colorDetail', 'sizes.sizeDetail')
                ->where('slug', $slug)->first();
            if (!$product) {
                return response()->json(['success'=> false, 'error' => 'Product not found'], 404);
            }

            return response()->json(['success'=> true, 'data' => new ProductResource($product)]);
        } catch (Exception  $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => (object)[]]);
        }
    }
}
