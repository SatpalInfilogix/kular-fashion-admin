<?php

namespace App\Imports;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductColor;
use App\Models\ProductQuantity;
use App\Models\Department;
use App\Models\Brand;
use App\Models\ProductType;
use App\Models\Size;
use App\Models\SizeScale;
use App\Models\Color;
use App\Models\Branch;
use App\Models\StoreInventory;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    ShouldQueue,
    WithValidation,
    SkipsOnFailure,
    SkipsOnError
{
    use SkipsFailures, SkipsErrors;

    protected $brandCache = [];
    protected $colorCache = [];
    protected $sizeScaleCache = [];
    protected $sizeCache = [];
    protected $typeCache = [];
    protected $departmentCache = [];
    protected $branchCache = [];

    public function model(array $row)
    {
        try {
            $row = collect($row)->map(fn($val) => is_string($val) ? trim($val) : $val);

            if (empty($row['itemdesc']) || empty($row['typename']) || empty($row['colname'])) {
                return null;
            }

            // Parse & cache
            $articleCode = $row['itemref'] ?? '';
            $name = $row['itemdesc'];
            $shortDesc = $row['itemdesc'];
            $exManufactureCode = explode(" ", $row['suppref']) ?? '';
            $manufactureCode = $exManufactureCode[0] ?? '';
            $supplier_color_code = $exManufactureCode[1] ?? '';
            $colorCode = $row['colcode'] ?? '';
            $colorName = $row['colname'] ?? '';
            $quantity = $row['stockqty'] ?? 0;
            $price = $row['sell1'] ?? 0;

            // Branch
            $branchKey = $row['branchname'] ?? 'Main Branch';
            if (!isset($this->branchCache[$branchKey])) {
                $this->branchCache[$branchKey] = Branch::firstOrCreate(
                    ['name' => $branchKey],
                    ['short_name' => $row['branch'] ?? 'main-branch']
                )->id;
            }
            $branch_id = $this->branchCache[$branchKey];

            // Color
            if (!isset($this->colorCache[$colorName])) {
                $this->colorCache[$colorName] = Color::firstOrCreate(
                    ['name' => $colorName],
                    ['slug' => Str::slug($colorName), 'code' => $colorCode]
                )->id;
            }
            $color_id = $this->colorCache[$colorName];

            // Brand
            $brandKey = $row['supplrname'] ?? 'Unknown';
            if (!isset($this->brandCache[$brandKey])) {
                $this->brandCache[$brandKey] = Brand::firstOrCreate(
                    ['name' => $brandKey, 'short_name' => $row['supplier']],
                    ['slug' => Str::slug($brandKey)]
                )->id;
            }
            $brand_id = $this->brandCache[$brandKey];

            // SizeScale
            $scaleKey = $row['sizerange'] ?? 'Default';
            if (!isset($this->sizeScaleCache[$scaleKey])) {
                $this->sizeScaleCache[$scaleKey] = SizeScale::firstOrCreate(
                    ['name' => $scaleKey]
                )->id;
            }
            $sizeScale_id = $this->sizeScaleCache[$scaleKey];

            // Size
            $sizeKey = $row['size'] . '-' . $sizeScale_id;
            if (!isset($this->sizeCache[$sizeKey])) {
                $this->sizeCache[$sizeKey] = Size::firstOrCreate(
                    ['size' => $row['size'], 'size_scale_id' => $sizeScale_id],
                    ['new_code' => $colorCode, 'status' => 'Active']
                )->id;
            }
            $size_id = $this->sizeCache[$sizeKey];

            // ProductType
            $typeKey = $row['typename'];
            if (!isset($this->typeCache[$typeKey])) {
                $this->typeCache[$typeKey] = ProductType::firstOrCreate(
                    ['name' => $typeKey],
                    ['slug' => Str::slug($typeKey), 'short_name' => $row['stype']]
                )->id;
            }
            $productType_id = $this->typeCache[$typeKey];

            // Department
            $departmentKey = $row['groupname'] ?? 'General';
            if (!isset($this->departmentCache[$departmentKey])) {
                $this->departmentCache[$departmentKey] = Department::firstOrCreate(
                    ['name' => $departmentKey]
                )->id;
            }
            $department_id = $this->departmentCache[$departmentKey];

            // Product
            $product = Product::firstOrCreate(
                ['name' => $name, 'manufacture_code' => $manufactureCode],
                [
                    'article_code' => $articleCode,
                    'slug' => Str::slug($name),
                    'brand_id' => $brand_id,
                    'department_id' => $department_id,
                    'product_type_id' => $productType_id,
                    'short_description' => $shortDesc,
                    'price' => $price,
                    'mrp' => $price,
                    'size_scale_id' => $sizeScale_id,
                ]
            );

            $productSize = ProductSize::firstOrCreate(
                ['product_id' => $product->id, 'size_id' => $size_id],
                ['mrp' => $price, 'web_price' => $price]
            );

            $productColor = ProductColor::firstOrCreate(
                ['product_id' => $product->id, 'color_id' => $color_id],
                [
                    'supplier_color_code' => $supplier_color_code ?: 'N/A',
                    'supplier_color_name' => $row['supplier'] ?? 'N/A',
                ]
            );

            $productQuantity = ProductQuantity::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'product_color_id' => $productColor->id,
                    'product_size_id' => $productSize->id,
                ],
                [
                    'quantity' => $quantity,
                    'total_quantity' => $quantity
                ]
            );

            StoreInventory::firstOrCreate(
                [
                    'store_id' => $branch_id,
                    'product_id' => $product->id,
                    'product_quantity_id' => $productQuantity->id,
                ],
                [
                    'product_color_id' => $productColor->id,
                    'product_size_id' => $productSize->id,
                    'brand_id' => $brand_id,
                    'quantity' => $quantity,
                    'total_quantity' => $quantity
                ]
            );

        } catch (\Throwable $e) {
            Log::error("Import failed at row", [
                'row' => $row,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function rules(): array
    {
        return [
            'itemdesc' => 'required',
            'typename' => 'required',
            'colname' => 'required',
        ];
    }
}
