<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'success' => true,
            'draw' => $request->input('draw') ?? 0,
            'recordsTotal' => $this->resource->total(),
            'recordsFiltered' => $this->resource->total(),
            'data' => $this->collection->map(function ($product) {
                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'article_code' => $product->article_code,
                    'manufacture_code' => $product->manufacture_code,
                    'brand_id' => $product->brand_id,
                    'department_id' => $product->department_id,
                    'product_type_id' => $product->product_type_id,
                    'price'     => $product->price,
                    'sale_price' => $product->sale_price,
                    'sale_start' => $product->sale_start,
                    'sale_end' => $product->sale_end,
                    'season' => $product->season,
                    'size_scale_id' => $product->size_scale_id,
                    'min_size_id' => $product->min_size_id,
                    'max_size_id' => $product->max_size_id,
                    'brand' => [
                        'id' => optional($product->brand)->id,
                        'name' => optional($product->brand)->name,
                        'slug' => optional($product->brand)->slug,
                    ],
                    'department' => [
                        'id' => optional($product->department)->id,
                        'name' => optional($product->department)->name,
                        'slug' => optional($product->department)->slug,
                        'image' => optional($product->department)->image,
                    ],
                    
                    'productType' => [
                        'id' => optional($product->productType)->id,
                        'name' => optional($product->productType)->name,
                        'slug' => optional($product->productType)->slug,
                    ],
                    
                    'colors' => $product->colors->map(function ($color) {
                        return [
                            'id' => $color->id,
                            'color_id' => $color->color_id,
                            'supplier_color_code' => $color->supplier_color_code,
                            'supplier_color_name' => $color->supplier_color_name,
                            'swatch_image_path'=> $color->swatch_image_path,

                            'color_detail' => [
                                'id' => optional($color->colorDetail)->id,
                                'name' => optional($color->colorDetail)->name,
                                'slug' => optional($color->colorDetail)->slug,
                                'code' => optional($color->colorDetail)->code,
                                'ui_color_code' => optional($color->colorDetail)->ui_color_code,
                            ]
                           
                        ];
                    }),
                    'sizes' => $product->sizes->map(function ($size) {
                        return [
                            'id' => $size->id,
                            'size_id' => $size->size_id,
                            'web_price' => $size->web_price,
                            'web_sale_price' => $size->web_sale_price,
                            'size_detail' => [
                                'id' => optional($size->sizeDetail)->id,
                                'size' => optional($size->sizeDetail)->size,
                            ]
                        ];
                    }),
                ];
            }),
        ];
    }
}
