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
                'wishlist'
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

            if ($request->has('department_slug')) {
                $slug = $request->input('department_slug');
                $query->whereHas('department', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                });
            }

            if ($request->has('product_type_slug')) {
                $slug = $request->input('product_type_slug');
                $query->whereHas('productType', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                });
            }
            if ($request->has('brands')) {
                $brands = explode(',', $request->input('brands'));
                $query->whereIn('brand_id', $brands);
            }

            if ($request->has('product_ids')) {
                $product_ids = explode(',', $request->input('product_ids'));
                $query->whereIn('id', $product_ids);
            }

            if ($request->has('product_types') && !empty($request->input('product_types'))) {
                $product_types = explode(',', $request->input('product_types'));

                $query->whereHas('productType', function ($q) use ($product_types) {
                    $q->whereIn('id', $product_types);
                });
            }

            if ($request->has('sizes') && !empty($request->input('sizes')) && !is_null($request->input('sizes'))) {
                $sizes = explode(',', $request->input('sizes'));
                $query->whereHas('sizes', function ($q) use ($sizes) {
                    $q->whereIn('size_id', $sizes);
                });
            }


            if ($request->has('colors') && !empty($request->input('colors')) && !is_null($request->input('colors'))) {
                $colors = explode(',', $request->input('colors'));
                $query->whereHas('colors', function ($q) use ($colors) {
                    $q->whereIn('color_id', $colors);
                });
            }


            if ($request->has('min_price') && $request->has('max_price')) {
                $minPrice = $request->input('min_price');
                $maxPriceToFilter = $request->input('max_price');
                $query->whereHas('sizes', function ($q) use ($minPrice, $maxPriceToFilter) {
                    $q->whereBetween('web_price', [$minPrice, $maxPriceToFilter]);
                });
            }

            // Sort by a specific field (if provided)
            if ($request->has('sort_by')) {
                $sortField = $request->input('sort_by');
                $sortDirection = $request->input('sort_dir', 'asc');
                $query->orderBy($sortField, $sortDirection);
            }

            // Get all products (without pagination)
            if ($request->has('onlyFilter') && $request->onlyFilter) {
                $products = $query->paginate(10);
            } else {
                $products = $query->get();
            }
            

            // Transform products to handle `is_splitted_with_colors`
            $transformedProducts = $this->transformProducts($products);

            // Paginate the transformed products manually
            $perPage = $request->input('per_page', 20); // Default to 20 items per page
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
                ->with('productType')
                ->get()
                ->pluck('productType')
                ->filter()
                ->unique('id')
                ->values() 
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'name' => $type->name,
                    ];
                });


            $colorsQuery = Product::query();

            if ($request->has('sizes') && !empty($request->input('sizes')) && !is_null($request->input('sizes'))) {
                $colorsQuery = Product::whereIn('id', $products->pluck('id'));
            }

            $colors = $colorsQuery->with('colors.colorDetail')->get()
                ->pluck('colors')->flatten()
                ->unique(function ($color) {
                    return $color->colorDetail ? $color->colorDetail->id : null;
                })->values()
                ->map(function ($color) {
                    return [
                        'id' => $color->color_id,
                        'name' => $color->colorDetail ? $color->colorDetail->name : null,
                        'color_code' => $color->colorDetail ? $color->colorDetail->ui_color_code : null,
                    ];
                });

            $sizes = Product::whereIn('id', $products->pluck('id'))
                ->with('sizes.sizeDetail')
                ->get()
                ->pluck('sizes')
                ->flatten()
                ->unique(fn($size) => $size->sizeDetail->size)
                ->map(function ($size) {
                    return [
                        'id' => $size->size_id,
                        'name' => $size->sizeDetail->size,
                    ];
                });

            $perPage = 20;
            $page = request()->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedSizes = $sizes->slice($offset, $perPage)->values();
            $paginatedProductTypes = $productTypes->slice($offset, $perPage)->values();

            $pagination = [
                'total' => $sizes->count(),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($sizes->count() / $perPage),
            ];

            $minPrice = $products->min(function ($product) {
                return $product->sizes->min('web_sale_price');
            });

            $maxPrice = Product::with('sizes')
                ->get()
                ->pluck('sizes')
                ->flatten()
                ->max('web_price');

            // Return the paginated results as JSON
            $response = [
                'success' => true,
                'data' => $productCollection,
                'pagination' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                    'last_page' => $paginatedProducts->lastPage(),
                ],
            ];

            if ($request->filters == true) {
                $response['filters'] = [
                    'brands' => $brands,
                    'product_types' => [
                        'data' => $paginatedProductTypes,
                        // 'data' => [],
                        'pagination' => [
                            'total' => $productTypes->count(),
                            'per_page' => $perPage,
                            'current_page' => $page,
                            'last_page' => ceil($productTypes->count() / $perPage),
                        ]
                    ],
                    'colors' => $colors,
                    'sizes' => [
                        'data' => $paginatedSizes,
                        'pagination' => [
                            'total' => $sizes->count(),
                            'per_page' => $perPage,
                            'current_page' => $page,
                            'last_page' => ceil($sizes->count() / $perPage),
                        ],
                    ],
                    'price' => [
                        'min' => (float)$minPrice,
                        'max' => (float)$maxPrice,
                    ]
                ];
            }

            return response()->json($response);
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

    public function searchProduct($searchValue)
    {
        $query = Product::with(['brand', 'colors']);

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', '%' . $searchValue . '%')
                ->orWhereHas('brand', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                })
                ->orWhereHas('colors', function ($q) use ($searchValue) {
                    $q->where('name', 'LIKE', '%' . $searchValue . '%');
                });
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
