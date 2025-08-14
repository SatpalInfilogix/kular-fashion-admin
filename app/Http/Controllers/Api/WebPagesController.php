<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebPages;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Str;

class WebPagesController extends Controller
{
    public function index()
    {
        try {
            $pages = WebPages::whereNotNull('published_at')
                ->orderBy('published_at', 'desc')
                ->get(['id', 'title', 'slug', 'description', 'image_small', 'published_at']);

            return response()->json([
                'success' => true,
                'data' => $pages,
                'meta' => [
                    'count' => $pages->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve web pages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $slug)
    {
        try {
            $webPage = WebPages::where('slug', $slug)->firstOrFail();

            $rules = is_array($webPage->rules) ? $webPage->rules : json_decode($webPage->rules, true);
            $filters = is_array($webPage->filters) ? $webPage->filters : json_decode($webPage->filters, true);

            $productsQuery = $this->initializeProductsQuery();

            if (!empty($rules)) {
                $this->applyRules($productsQuery, $rules);
            }

            if (!empty($filters)) {
                $this->applyFilters($productsQuery, $filters);
            }

            $this->applyRequestFilters($productsQuery, $request);

            $perPage = $request->input('per_page', 12);
            $products = $productsQuery->paginate($perPage);
            $availableFilters = $this->buildProductFilters(
                $products->items(),
                $visibleFilters ?? [],
                $webPage->collection
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'page' => $this->formatWebPageData($webPage),
                    'products' => $products->items(),
                    'filters' => $availableFilters,
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'last_page' => $products->lastPage(),
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Web page not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve web page',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function initializeProductsQuery()
    {
        return Product::with([
            'brand',
            'department',
            'productType',
            'webImage',
            'quantities',
            'colors.colorDetail',
            'sizes.sizeDetail',
            'webInfo',
            'wishlist'
        ])->where(function ($query) {
            $query->whereHas('webInfo', function ($q) {
                $q->where('status', 1);
            })
                ->orWhereHas('webInfo', function ($q) {
                    $q->where('status', 2);
                })->whereHas('quantities', function ($q) {
                    $q->select('product_id')
                        ->groupBy('product_id')
                        ->havingRaw('SUM(quantity) > 0');
                })
                ->orWhereDoesntHave('webInfo');
        });
    }

    protected function buildProductFilters($products, $visibleFilters = [], $collection = null)
    {
        $allFilters = [
            'price' => ['min' => PHP_FLOAT_MAX, 'max' => 0],
            'brands' => [],
            'sizes' => [],
            'colors' => [],
            'product_types' => [],
            'tags' => []
        ];

        foreach ($products as $product) {
            $price = $product->sale_price ?? $product->price;
            $allFilters['price']['min'] = min($allFilters['price']['min'], $price);
            $allFilters['price']['max'] = max($allFilters['price']['max'], $price);

            if ($product->brand) {
                $allFilters['brands'][$product->brand->id] = [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                    'slug' => $product->brand->slug ?? Str::slug($product->brand->name)
                ];
            }

            if ($product->productType) {
                $allFilters['product_types'][$product->productType->id] = [
                    'id' => $product->productType->id,
                    'name' => $product->productType->name,
                    'slug' => $product->productType->slug ?? Str::slug($product->productType->name)
                ];
            }

            foreach ($product->sizes ?? [] as $size) {
                if ($size->sizeDetail) {
                    $sizeName = $size->sizeDetail->size ?? $size->sizeDetail->new_code ?? $size->sizeDetail->old_code ?? "Size {$size->sizeDetail->id}";
                    $allFilters['sizes'][$size->sizeDetail->id] = [
                        'id' => $size->sizeDetail->id,
                        'size' => $sizeName,
                        'name' => $sizeName
                    ];
                }
            }

            foreach ($product->colors ?? [] as $color) {
                if ($color->colorDetail) {
                    $allFilters['colors'][$color->colorDetail->id] = [
                        'id' => $color->colorDetail->id,
                        'name' => $color->colorDetail->name,
                        'hex' => $color->colorDetail->ui_color_code ?? $color->colorDetail->hex ?? '#000000'
                    ];
                }
            }

            foreach ($product->tags ?? [] as $tag) {
                $allFilters['tags'][$tag->id] = [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                ];
            }
        }

        $allFilters['price'] = [
            'min' => $allFilters['price']['min'] === PHP_FLOAT_MAX ? 0 : (float)number_format($allFilters['price']['min'], 2, '.', ''),
            'max' => $allFilters['price']['min'] === $allFilters['price']['max']
                ? $allFilters['price']['min'] + 100
                : (float)number_format($allFilters['price']['max'], 2, '.', '')
        ];

        if ($collection?->listingOption?->hide_filters) {
            return [];
        }

        return $allFilters;
    }

    protected function applyRules($query, array $rules)
    {
        foreach ($rules as $rule) {
            if (!is_array($rule)) continue;

            $type = $rule['type'] ?? null;
            $condition = $rule['condition'] ?? null;

            if ($type === 'must' && $condition === 'has_tags' && !empty($rule['tag_ids'])) {
                $query->whereHas('tags', function ($q) use ($rule) {
                    $q->whereIn('tag_id', $rule['tag_ids']);
                });
            }

            if ($type === 'must_not' && $condition === 'has_tags' && !empty($rule['tag_ids'])) {
                $query->whereDoesntHave('tags', function ($q) use ($rule) {
                    $q->whereIn('tag_id', $rule['tag_ids']);
                });
            }

            if ($type === 'must' && $condition === 'has_all_tags' && !empty($rule['tag_ids'])) {
                $query->whereHas('tags', function ($q) use ($rule) {
                    $q->whereIn('tag_id', $rule['tag_ids']);
                });
            }

            switch ($type) {
                case 'department':
                    if (isset($rule['value'])) {
                        $query->whereHas('department', function ($q) use ($rule) {
                            $q->where('slug', $rule['value']);
                        });
                    }
                    break;

                case 'product_type':
                    if (isset($rule['value'])) {
                        $query->whereHas('productType', function ($q) use ($rule) {
                            $q->where('slug', $rule['value']);
                        });
                    }
                    break;

                case 'brand':
                    if (isset($rule['value'])) {
                        $query->whereHas('brand', function ($q) use ($rule) {
                            $q->where('slug', $rule['value']);
                        });
                    }
                    break;

                case 'tag':
                    if (isset($rule['value'])) {
                        $query->whereHas('tags', function ($q) use ($rule) {
                            $q->where('slug', $rule['value']);
                        });
                    }
                    break;

                case 'product_ids':
                    if (isset($rule['values']) && is_array($rule['values'])) {
                        $query->whereIn('id', $rule['values']);
                    }
                    break;
            }
        }
    }

    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter) {
            if (!is_array($filter)) continue;

            switch ($filter['type'] ?? null) {
                case 'brands':
                    if (!empty($filter['values'])) {
                        $query->whereIn('brand_id', $filter['values']);
                    }
                    break;

                case 'product_types':
                    if (!empty($filter['values'])) {
                        $query->whereHas('productType', function ($q) use ($filter) {
                            $q->whereIn('id', $filter['values']);
                        });
                    }
                    break;

                case 'sizes':
                    if (!empty($filter['values'])) {
                        $query->whereHas('sizes', function ($q) use ($filter) {
                            $q->whereIn('size_id', $filter['values']);
                        });
                    }
                    break;

                case 'colors':
                    if (!empty($filter['values'])) {
                        $query->whereHas('colors', function ($q) use ($filter) {
                            $q->whereIn('color_id', $filter['values']);
                        });
                    }
                    break;

                case 'price_range':
                    if (isset($filter['min'], $filter['max'])) {
                        $query->whereHas('sizes', function ($q) use ($filter) {
                            $q->whereBetween('web_price', [$filter['min'], $filter['max']]);
                        });
                    }
                    break;
            }
        }
    }

    protected function applyRequestFilters($query, Request $request)
    {
        if ($request->filled('brands')) {
            $brands = explode(',', $request->input('brands'));
            $query->whereIn('brand_id', $brands);
        }

        if ($request->filled('product_types')) {
            $productTypes = explode(',', $request->input('product_types'));
            $query->whereHas('productType', function ($q) use ($productTypes) {
                $q->whereIn('id', $productTypes);
            });
        }

        if ($request->filled('sizes')) {
            $sizes = explode(',', $request->input('sizes'));
            $query->whereHas('sizes', function ($q) use ($sizes) {
                $q->whereIn('size_id', $sizes);
            });
        }

        if ($request->filled('colors')) {
            $colors = explode(',', $request->input('colors'));
            $query->whereHas('colors', function ($q) use ($colors) {
                $q->whereIn('color_id', $colors);
            });
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereHas('sizes', function ($q) use ($request) {
                $q->whereBetween('web_price', [
                    $request->input('min_price'),
                    $request->input('max_price')
                ]);
            });
        }

        if ($request->filled('sort_by')) {
            $sortField = $request->input('sort_by');
            $sortDirection = $request->input('sort_dir', 'asc');
            $query->orderBy($sortField, $sortDirection);
        }
    }

    protected function formatWebPageData(WebPages $webPage)
    {
        return [
            'id' => $webPage->id,
            'title' => $webPage->title,
            'slug' => $webPage->slug,
            'heading' => $webPage->heading,
            'content' => $webPage->page_content,
            'description' => $webPage->description,
            'summary' => $webPage->summary,
            'meta_title' => $webPage->meta_title,
            'meta_description' => $webPage->meta_description,
            'meta_keywords' => $webPage->meta_keywords,
            'image_small' => $webPage->image_small,
            'image_medium' => $webPage->image_medium,
            'image_large' => $webPage->image_large,
            'published_at' => $webPage->published_at,
            'hide_categories' => $webPage->hide_categories,
            'hide_all_filters' => $webPage->hide_all_filters,
            'show_all_filters' => $webPage->show_all_filters,
            'rules' => is_array($webPage->rules) ? $webPage->rules : json_decode($webPage->rules, true)
        ];
    }
}