<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductQuantity;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Department;
use App\Models\ProductType;
use App\Models\ProductTag;
use App\Models\ProductWebImage;
use App\Models\ProductWebInfo;
use App\Models\ProductWebSpecification;
use App\Models\ProductTypeDepartment;
use App\Models\Size;
use App\Models\SizeScale;
use App\Models\StoreInventory;
use App\Models\Tax;
use App\Models\Tag;
use DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view products')) {
            abort(403);
        }

        $brands = Brand::select('id', 'name')->where('status', 'Active')->orderBy('name', 'ASC')->latest()->get();
        $departments = Department::select('id', 'name')->where('status', 'Active')->orderBy('name', 'ASC')->latest()->get();
        $productTypes = ProductType::select('id', 'name')->where('status', 'Active')->orderBy('name', 'ASC')->latest()->get();
        $tags = Tag::select('id', 'name')->where('status', 'Active')->orderBy('name', 'ASC')->latest()->get();

        return view('products.index', compact('brands', 'productTypes', 'departments', 'tags'));
    }

    public function create()
    {
        if (!Gate::allows('create products')) {
            abort(403);
        }
        $latestProduct = Product::orderBy('article_code', 'desc')->first();

        $latestNewCode = $latestProduct ? (int) $latestProduct->article_code : 300000;
        $brands = Brand::where('status', 'Active')->orderBy('name', 'ASC')->latest()->get();
        $departments = Department::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $taxes = Tax::where('status', '1')->orderBy('tax', 'ASC')->get();
        $tags = Tag::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $sizeScales = SizeScale::select('id', 'name', 'is_default')->where('status', 'Active')->orderBy('name', 'ASC')->latest()->with('sizes')->get();

        return view('products.create', compact('latestNewCode', 'brands', 'departments', 'taxes', 'tags', 'sizeScales'));
    }

    public function saveStep1(Request $request)
    {
        $request->validate([
            'article_code' => [
                'required',
                Rule::unique('products')->whereNull('deleted_at'),
            ],
            'name' => 'required',
            'manufacture_code' => 'required',
            'brand_id' => 'required',
            'department_id' => 'required',
            'product_type_id' => 'required',
            'size_scale_id' => 'required',
            'supplier_price' => 'required',
            'mrp' => 'required',
            'short_description' => 'required',
            'image' => 'nullable|image|max:2048',
            'price' => 'required|numeric',
            'sale_price' => 'nullable|numeric',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',
        ]);

        $productData = $request->all();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $request->article_code . '.' . $image->getClientOriginalExtension();
            $path = 'uploads/products/';
            $image->move(public_path($path), $imageName);

            // Store only the file path in the data array
            $productData['image_path'] = $path . $imageName;
            unset($productData['image']);
        }

        Session::put('savingProduct', $productData);
        return redirect()->route('products.create.step-2');
    }

    public function updateStep1(Request $request, Product $product)
    {
        $request->validate([
            /* 'manufacture_code' => [
                'required',
                Rule::unique('products')->ignore($product->id)->whereNull('deleted_at'),
            ],
            'brand_id'          => 'required',
            'department_id'     => 'required',
            'product_type_id'   => 'required',
            'size_scale_id'     => 'required', */
            'name' => 'required',
            'supplier_price' => 'required',
            'mrp' => 'required',
            'short_description' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'price' => 'required|numeric',
            'sale_price' => 'nullable|numeric',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $request->article_code . '.' . $image->getClientOriginalExtension();
            $path = 'uploads/products/';
            $image->move(public_path($path), $imageName);

            // Store only the file path in the data array
            $imagePath = $path . $imageName;
        }


        if (!$imagePath) {
            $imagePath = $product->image;
        }

        if ($product) {
            $product->update([
                /* 'manufacture_code' => $request->manufacture_code,
                'department_id' => $request->department_id,
                'brand_id' => $request->brand_id,
                'product_type_id' => $request->product_type_id, */
                'name' => $request->name,
                'price' => $request->price,
                'short_description' => $request->short_description,
                'mrp' => $request->mrp,
                'supplier_price' => $request->supplier_price,
                'season' => $request->season,
                'supplier_ref' => $request->supplier_ref,
                'tax_id' => $request->tax_id,
                //'in_date' => $request->in_date,
                'last_date' => $request->last_date ?? $product->last_date,
                //'size_scale_id' => $request->size_scale_id,
                'image' => $imagePath,
                'status' => $request->status,
                'sale_price' => $request->sale_price,
                'sale_start' => isset($request->sale_start) ? Carbon::parse($request->sale_start)->toDateString() : null,
                'sale_end' => isset($request->sale_end) ? Carbon::parse($request->sale_end)->toDateString() : null,
            ]);
        }

        if (isset($request->tags)) {
            foreach ($request->tags as $tagId) {
                ProductTag::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'tag_id' => $tagId
                    ],
                    [
                        'product_id' => $product->id,
                        'tag_id' => $tagId
                    ]
                );
            }
        }

        return redirect()->route('products.edit.step-2', $product->id);
    }

    public function editStep2(Product $product)
    {
        $sizes = $product->sizes;

        $savedColorIds = $product->colors->pluck('color_id')->toArray();
        $savedColors = Color::whereIn('id', $savedColorIds)->get();
        $savedColorsMapped = $savedColors->keyBy('id')->toArray();

        $savedColors = array_map(function ($colorId) use ($savedColorsMapped) {
            return $savedColorsMapped[$colorId] ?? null;
        }, $savedColorIds);

        $excludedSavedColors = array_keys($savedColorsMapped);
        $colors = Color::where('status', 'Active')->whereNotIn('id', $excludedSavedColors)->get();

        return view('products.steps.edit-step-2', compact('product', 'sizes', 'savedColors', 'colors'));
    }

    public function saveStep2(Request $request)
    {
        $product = Session::get('savingProduct');
        $product['size_range_min'] = $request->size_range_min;
        $product['size_range_max'] = $request->size_range_max;

        $product['supplier_color_codes'] = $request->supplier_color_code;
        $product['supplier_color_names'] = $request->supplier_color_name;
        $product['colors'] = $request->colors;
        Session::put('savingProduct', $product);

        $request->validate([
            'colors' => 'required|array',
            'colors.*' => 'required|distinct|exists:colors,id',
            'supplier_color_code' => 'required|array',
            'supplier_color_code.*' => 'required|distinct|string',
            'supplier_color_name' => 'required|array',
            'supplier_color_name.*' => 'required|distinct|string',
            'size_range_min' => 'required|exists:sizes,id',
            'size_range_max' => 'required|exists:sizes,id|gte:size_range_min',
        ]);
        return redirect()->route('products.create.step-3');
    }

    public function addVariant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_color_code' => 'required|string|max:255',
            'supplier_color_name' => 'required|string|max:255',
            'color_select' => 'required|exists:colors,id'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
        } else {
            $errors = new MessageBag();
        }

        $savingProduct = Session::get('savingProduct', []);

        if (empty($savingProduct)) {
            $savingProduct['supplier_color_codes'] = [];
            $savingProduct['supplier_color_names'] = [];
            $savingProduct['colors'] = [];
        }

        if (isset($request->product_id)) {
            $savedProduct = Product::with('colors')->find($request->product_id);
            if ($savedProduct) {
                $supplierColorCodes = $savedProduct->colors->pluck('supplier_color_code')->toArray();
                $supplierColorNames = $savedProduct->colors->pluck('supplier_color_name')->toArray();
                $savedColorCodes = $savedProduct->colors->pluck('color_id')->toArray();

                $savingProduct['supplier_color_codes'] = $supplierColorCodes;
                $savingProduct['supplier_color_names'] = $supplierColorNames;
                $savingProduct['colors'] = $savedColorCodes;
            }
        }

        if ($savingProduct) {
            if (is_array($savingProduct['supplier_color_codes']) && in_array($request->supplier_color_code, $savingProduct['supplier_color_codes'])) {
                $errors->add('supplier_color_code', 'Supplier Color Code already exists');
            }
            if (is_array($savingProduct['supplier_color_names']) && in_array($request->supplier_color_name, $savingProduct['supplier_color_names'])) {
                $errors->add('supplier_color_name', 'Supplier Color Name already exists');
            }
            if (is_array($savingProduct['colors']) && in_array($request->color_select, $savingProduct['colors'])) {
                $errors->add('color_select', 'Color already exists');
            }
        }

        if ($errors->isNotEmpty()) {
            return response()->json([
                'errors' => $errors
            ], 422);
        }

        if ($request->product_id) {
            ProductColor::updateOrCreate(
                [
                    'product_id' => $request->product_id,
                    'color_id' => $request->color_select,
                ],
                [
                    'supplier_color_code' => $request->supplier_color_code,
                    'supplier_color_name' => $request->supplier_color_name,
                ]
            );
        }

        $color = Color::where('id', $request->color_select)->first();
        array_push($savingProduct['supplier_color_codes'], $request->supplier_color_code);
        array_push($savingProduct['supplier_color_names'], $request->supplier_color_name);
        array_push($savingProduct['colors'], $request->color_select);
        $savingProduct = Session::put('savingProduct', $savingProduct);

        return response()->json([
            'success' => true,
            'data' => [
                'supplier_color_code' => $request->supplier_color_code,
                'supplier_color_name' => $request->supplier_color_name,
                'color_id' => $request->color_select,
                'color_name' => $color->name,
                'color_code' => $color->code,
                'ui_color_code' => $color->ui_color_code,
            ],
            'message' => 'Variant added successfully!'
        ]);
    }

    public function removeVariant(Request $request, $colorId)
    {
        // Retrieve the product session data
        $savingProduct = Session::get('savingProduct');

        if (!$savingProduct && !$request->productId) {
            return response()->json(['error' => 'No product session found.'], 404);
        }

        if ($request->productId) {
            $product = Product::find($request->productId);
            if ($product) {
                $product->colors()->where('color_id', $colorId)->delete();
            }
        }

        // Check if colorId exists in the 'colors' array
        $key = array_search($colorId, $savingProduct['colors']);

        if ($key === false) {
            return redirect()->back()->with('error', 'Color doesn\'t exist');
        }

        // Remove the colorId from the 'colors' array
        unset($savingProduct['colors'][$key]);

        // Remove the corresponding supplier color code from the 'supplier_color_codes' array
        unset($savingProduct['supplier_color_codes'][$key]);
        unset($savingProduct['supplier_color_names'][$key]);

        // Reindex the arrays to maintain numeric indexes
        $savingProduct['colors'] = array_values($savingProduct['colors']);
        $savingProduct['supplier_color_codes'] = array_values($savingProduct['supplier_color_codes']);
        $savingProduct['supplier_color_names'] = array_values($savingProduct['supplier_color_names']);

        Session::put('savingProduct', $savingProduct);

        return redirect()->back();
    }

    public function store(Request $request)
    {
        $productData = Session::get('savingProduct');

        // Exclude files from the request data
        $requestData = $request->except('image');

        $productData['variantData'] = $requestData;
        Session::put('savingProduct', $productData);

        $product = Product::create([
            'name' => $productData['name'],
            'manufacture_code' => $productData['manufacture_code'] ?? NULL,
            'department_id' => $productData['department_id'] ?? NULL,
            'brand_id' => $productData['brand_id'] ?? NULL,
            'product_type_id' => $productData['product_type_id'] ?? NULL,
            'short_description' => $productData['short_description'] ?? NULL,
            'mrp' => $productData['mrp'] ?? NULL,
            'supplier_price' => $productData['supplier_price'] ?? NULL,
            'season' => $productData['season'] ?? NULL,
            'supplier_ref' => $productData['supplier_ref'] ?? NULL,
            'tax_id' => $productData['tax_id'] ?? NULL,
            'in_date' => $productData['in_date'] ?? NULL,
            'last_date' => $productData['last_date'] ?? NULL,
            'size_scale_id' => $productData['size_scale_id'] ?? NULL,
            'image' => $productData['image_path'] ?? NULL,
            'min_size_id' => $productData['size_range_min'],
            'max_size_id' => $productData['size_range_max'],
            'price' => $productData['price'] ?? $productData['mrp'],
            'sale_price' => $productData['sale_price'] ?? null,
            'sale_start' => isset($productData['sale_start']) ? Carbon::parse($productData['sale_start'])->toDateString() : null,
            'sale_end' => isset($productData['sale_end']) ? Carbon::parse($productData['sale_end'])->toDateString() : null,
            'status' => $productData['status']
        ]);

        if (isset($productData['tags'])) {
            foreach ($productData['tags'] as $tagId) {
                ProductTag::create([
                    "product_id" => $product->id,
                    "tag_id" => $tagId
                ]);
            }
        }

        foreach ($productData['variantData']['mrp'] as $sizeId => $mrp) {
            ProductSize::create([
                'product_id' => $product->id,
                'size_id' => $sizeId,
                'mrp' => $mrp,
                'web_price' => $productData['variantData']['web_price'][$sizeId] ?? 0,
                'web_sale_price' => $productData['variantData']['sale_price'][$sizeId] ?? 0,
            ]);
        }

        foreach ($productData['supplier_color_codes'] as $index => $supplierColorCode) {
            $productColor = ProductColor::create([
                'product_id' => $product->id,
                'color_id' => $productData['colors'][$index],
                'supplier_color_code' => $supplierColorCode,
                'supplier_color_name' => $productData['supplier_color_names'][$index] ?? '',
            ]);

            $color_id = $productData['colors'][$index];

            //dd($productData);
            if ($request->hasFile("image") && isset($request->file("image")[$color_id])) {
                $file = $request->file('image')[$color_id];
                $imageName = $product->article_code . $productColor->colorDetail->code;
                $imagePath = uploadFile($file, 'uploads/pos/product-colors/', $imageName);
                
                $productColor->update([
                    "image_path" => $imagePath,
                ]);
            }

            foreach ($productData['variantData']['quantity'][$color_id] as $sizeId => $quantity) {
                $productSize = ProductSize::where('product_id', $product->id)->where('size_id', $sizeId)->first();

                ProductQuantity::create([
                    'product_id' => $product->id,
                    'product_color_id' => $productColor->id,
                    'product_size_id' => $productSize->id,
                    'quantity' => 0,
                    'total_quantity' => $quantity,
                ]);
            }
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        if (!Gate::allows('view products')) {
            abort(403);
        }

        $branches = Branch::with(['inventory' => function ($query) use ($product) {
            $query->where('product_id', $product->id);
        }])->get();
        return view('products.show', compact('product', 'branches'));
    }

    public function edit(Product $product)
    {
        if (!Gate::allows('edit products')) {
            abort(403);
        }

        $brands = Brand::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $departments = Department::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $productTypes = ProductType::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $taxes = Tax::where('status', '1')->orderBy('tax', 'ASC')->get();
        $tags = Tag::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $sizeScales = SizeScale::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $sizes = Size::where('status', 'Active')->orderBy('size', 'ASC')->get();
        $productTags = ProductTag::where('product_id', $product->id)->pluck('tag_id')->toArray();

        return view('products.edit', compact('brands', 'productTypes', 'departments', 'product', 'taxes', 'tags', 'sizes', 'sizeScales', 'productTags'));
    }

    public function update(Request $request, Product $product)
    {
        foreach ($request->mrp as $product_size_id => $mrp) {
            ProductSize::find($product_size_id)->update([
                'mrp' => $mrp,
                'web_price' => $request->web_price[$product_size_id] ?? 0,
                'web_sale_price' => $request->sale_price[$product_size_id] ?? 0,
            ]);
        }

        foreach ($request->quantity as $colorId => $tempQuantity) {
            $productColor = ProductColor::where('product_id', $product->id)->where('color_id', $colorId)->first();
            if ($productColor) {
                $productColorId = $productColor->id;

                $imagesToBeDeleted = explode(',', $request->color_images_to_be_deleted);
                if (in_array($productColor->color_id, $imagesToBeDeleted)) {
                    $imagePath = public_path($productColor->image_path);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }

                    $productColor->image_path = null;
                    $productColor->save();
                } else {
                    if ($request->hasFile("image") && isset($request->file("image")[$colorId])) {
                        $file = $request->file('image')[$colorId];

                        $imageName = $product->article_code . $productColor->colorDetail->code;
                        $imagePath = uploadFile($file, 'uploads/pos/product-colors/', $imageName);
                        $productColor->image_path = $imagePath;
                        $productColor->save();
                    }
                }

                foreach ($tempQuantity as $productSizeId => $newQuantity) {
                    $quantityRecord = ProductQuantity::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'product_color_id' => $productColorId,
                            'product_size_id' => $productSizeId,
                        ],
                        [
                            'quantity' => 0,
                            'total_quantity' => 0,
                        ]
                    );

                    if ($quantityRecord) {
                        // Quantity should added only when barcodes being printed
                        //$quantityRecord->quantity += $newQuantity;
                        $quantityRecord->total_quantity += $newQuantity;
                        $quantityRecord->save();
                    }
                }
            }
        }

        if ($product->quantities->sum('total_quantity') !== $product->quantities->sum('original_printed_barcodes')) {
            $product->barcodes_printed_for_all = 0;
            $product->save();
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if (!Gate::allows('delete products')) {
            abort(403);
        }

        if ($product->are_barcodes_printed || $product->barcodes_printed_for_all) {
            return response()->json([
                'success' => false,
                'message' => 'Product can\'t be deleted because you have printed the barcodes'
            ]);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.'
        ]);
    }

    public function getProducts(Request $request)
    {
        $query = Product::with(['brand', 'department', 'quantities', 'productType', 'colors.colorDetail', 'sizes.sizeDetail']);



        if ($request->has('brand_id') && $request->brand_id) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('id', $request->brand_id);
            });
        }

        if ($request->has('product_type_id') && $request->product_type_id) {
            $query->whereHas('productType', function ($q) use ($request) {
                $q->where('id', $request->product_type_id);
            });
        }

        if ($request->has('department_id') && $request->department_id) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('article_code', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('manufacture_code', 'like', "%{$search}%")
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('department', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('productType', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->new_products_only) {
            $query->where('are_barcodes_printed', 0)->orWhere('barcodes_printed_for_all', 0);
        }
        
        // Sorting logic
        $columns = [
            1 => 'article_code',
            2 => 'manufacture_code',
            3 => 'brands.name',
            4 => 'product_types.name',
            5 => 'departments.name',
            6 => 'short_description',
            7 => 'price',
        ];
        
        // Define the default sorting order
        $defaultOrder = [
            'brands.name',
            'product_types.name',
            'short_description',
            'price',
        ];
        
        // Apply sorting based on request
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc'); // 'asc' or 'desc'
        
            if (isset($columns[$orderColumnIndex])) {
                $primarySortColumn = $columns[$orderColumnIndex];
        
                // Apply primary sorting
                $query->orderBy($primarySortColumn, $orderDirection);
        
                // Apply secondary sorting for the remaining columns
                foreach ($defaultOrder as $column) {
                    if ($column !== $primarySortColumn) {
                        $query->orderBy($column, 'asc');
                    }
                }
            } else {
                // If the specified column index is not in the mapping, apply default sorting
                foreach ($defaultOrder as $column) {
                    $query->orderBy($column, 'asc');
                }
            }
        } else {
            // If no sorting is specified in the request, apply default sorting
            foreach ($defaultOrder as $column) {
                $query->orderBy($column, 'asc');
            }
        }
    
        $products = $query
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('product_types', 'products.product_type_id', '=', 'product_types.id')
            ->join('departments', 'products.department_id', '=', 'departments.id')
            ->select('products.*')
            ->paginate($request->input('length', 10));

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $products->total(),
            'recordsFiltered' => $products->total(),
            'data' => $products->items(),
        ]);
    }
    
    


    public function productStatus(Request $request)
    {
        $product = Product::find($request->id);
        if ($product) {
            $product->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.'
            ]);
        }

        return response()->json(['error' => 'Product not found.'], 404);
    }

    public function getDepartment($departmentId)
    {
        $productTypes = ProductTypeDepartment::with('productTypes')->where('department_id', $departmentId)->get();

        return response()->json($productTypes);
    }

    public function productStep1()
    {
        $latestProduct = Product::orderBy('article_code', 'desc')->first();

        $latestNewCode = $latestProduct ? (int) $latestProduct->article_code : 300000;
        $brands = Brand::where('status', 'Active')->whereNull('deleted_at')->latest()->get();
        $departments = Department::where('status', 'Active')->whereNull('deleted_at')->latest()->get();
        $taxes = Tax::where('status', '1')->latest()->get();
        $tags = Tag::where('status', 'Active')->latest()->get();
        $sizeScales = SizeScale::select('id', 'name', 'is_default')->where('status', 'Active')->latest()->with('sizes')->get();

        $product = (object) Session::get('savingProduct');

        return view('products.create', compact('latestNewCode', 'product', 'brands', 'departments', 'taxes', 'tags', 'sizeScales'));
    }

    public function productStep2()
    {
        $savingProduct = (object) Session::get('savingProduct');
        if (empty($savingProduct->size_scale_id)) {
            return redirect()->route('products.create.step-1');
        }
        $brand = Brand::where('id', $savingProduct->brand_id)->first();
        $sizeScale = SizeScale::where('id', $savingProduct->size_scale_id)->first();
        $colors = Color::where('status', 'Active')->orderBy('name', 'ASC')->get();
        $sizes = Size::where('status', 'Active')
            ->where('size_scale_id', $savingProduct->size_scale_id)
            ->orderBy('id', 'asc')
            ->get();

        return view('products.steps.step-2', compact('savingProduct', 'brand', 'sizeScale', 'colors', 'sizes'));
    }

    public function productStep3(Request $request)
    {
        $savingProduct = (object) Session::get('savingProduct');

        if (!$savingProduct || empty($savingProduct->size_scale_id)) {
            return redirect()->route('products.create.step-1');
        }

    
        $brand = \App\Models\Brand::find($savingProduct->brand_id);
        $productType = \App\Models\ProductType::find($savingProduct->product_type_id);

        $savingProduct->brand = (object) ['name' => $brand?->name ?? ''];
        $savingProduct->product_type = (object) ['name' => $productType?->name ?? ''];

    
        $sizes =Size::whereBetween('id', [$savingProduct->size_range_min, $savingProduct->size_range_max])->get();

        $reversedColors = array_reverse($savingProduct->colors ?? []);
        $savedColors = Color::whereIn('id', $reversedColors)->get();
        $savedColorsMapped = $savedColors->keyBy('id')->toArray();

        $savedColors = array_map(fn($id) => $savedColorsMapped[$id] ?? null, $reversedColors);
        $excludedSavedColors = array_keys($savedColorsMapped);
        $colors =Color::where('status', 'Active')->whereNotIn('id', $excludedSavedColors)->get();

        return view('products.steps.step-3', compact('savingProduct', 'sizes', 'savedColors', 'colors'));
    }
    /**
     * Generate a consistent two-digit code for the product ID.
     *
     * @param  mixed  $product_id
     * @return string
     */
    public static function generateRandomProductCode($product_id)
    {
        // Hash the product ID and convert it to a hexadecimal string
        $hash = hash('sha256', (string) $product_id);

        // Extract the first two characters and convert to integer
        $code = hexdec(substr($hash, 0, 2)) % 100;

        // Return the two-digit code as a string (padded if necessary)
        return str_pad($code, 2, '0', STR_PAD_LEFT);
    }

    public function downloadBarcodes()
    {
        if (!Session::get('barcodesToBePrinted')) {
            return redirect()->route('products.print-barcodes');
        }

        $barcodesQty = (object) Session::get('barcodesToBePrinted');

        if (!isset($barcodesQty->barcodesToBePrinted)) {
            return redirect()->route('products.print-barcodes');
        }

        $barcodes = [];
        $generator = new BarcodeGeneratorPNG();

        foreach ($barcodesQty->barcodesToBePrinted as $key => $data) {
            $skip = false;

            if (!isset($data['product'])) {
                $defaultProductsToBePrinted = Product::where('are_barcodes_printed', 0)->orWhere('barcodes_printed_for_all', 0)->with('quantities')->get();

                foreach ($defaultProductsToBePrinted as $product) {
                    $quantities = $product->quantities;

                    $totalQuantitySum = $quantities->sum(function ($quantity) {
                        return $quantity->total_quantity;
                    });

                    // If the sum of total_quantity is 0, skip this product
                    if ($totalQuantitySum == 0) {
                        $skip = true;
                        continue;
                    }

                    $filteredQuantities = $quantities->filter(function ($quantity) {
                        return ($quantity->total_quantity - $quantity->original_printed_barcodes) > 0;
                    });

                    if (count($filteredQuantities) === 0) {
                        $skip = true;
                        continue;
                    }

                    foreach ($filteredQuantities as $filteredQuantity) {
                        $data['product'][] = [
                            'id' => $filteredQuantity->id,
                            'orignalQty' => $filteredQuantity->total_quantity - $filteredQuantity->original_printed_barcodes,
                            'printQty' => $filteredQuantity->total_quantity - $filteredQuantity->original_printed_barcodes
                        ];
                    }
                }


                $barcodesQty->barcodesToBePrinted[$key] = $data;
                Session::put('barcodesToBePrinted', (array) $barcodesQty);
            }

            if ($skip) {
                $tempProductId = $data['productId'];
                $product = Product::find($tempProductId);

                if ($product) {
                    $product->update([
                        'are_barcodes_printed' => 1,
                        'barcodes_printed_for_all' => 1
                    ]);
                }
                continue;
            }

            if (isset($data['product'])) {
                foreach ($data['product'] as $quantityDetail) {

                    $products = ProductQuantity::with('product.department', 'product.brand', 'sizes.sizeDetail', 'colors.colorDetail')->where('id', $quantityDetail['id'])->get();

                    foreach ($products as $productDetail) {
                        $article_code = $productDetail->product->article_code;
                        $color_code = $productDetail->colors->colorDetail->code;
                        $new_code = $productDetail->sizes->sizeDetail->new_code;
                        $article_code = $article_code . $color_code . $new_code;

                        $checkCode = $this->generateCheckDigit($article_code);

                        for ($i = 0; $i < $quantityDetail['printQty']; $i++) {
                            $barcode = base64_encode($generator->getBarcode($article_code, $generator::TYPE_EAN_13, 1, 25, [0, 0, 0]));
                            $randomDigit = $this->generateRandomProductCode($productDetail->product->id);

                            $date = Carbon::parse($productDetail->first_barcode_printed_date);
                            $yearMonth = substr($date->format('ym'), 1);

                            $barcodes[] = [
                                'barcode' => $barcode,
                                'product_code' => $article_code . $checkCode,
                                'random_digits' => $randomDigit . $yearMonth,
                                'department' => $productDetail->product->department->name,
                                'type' => $productDetail->product->productType->name,
                                'product_type_short_name' => $productDetail->product->productType->short_name,
                                'manufacture_code' => $productDetail->product->manufacture_code,
                                'size' => $productDetail->sizes->sizeDetail->size,
                                'mrp' => $productDetail->sizes->mrp,
                                'article_code' => $productDetail->product->article_code,
                                'short_description' => $productDetail->product->short_description,
                                'color' => $productDetail->colors->colorDetail->name,
                                'color_short_name' => $productDetail->colors->colorDetail->short_name,
                                'brand_short_name' => $productDetail->product->brand->short_name ?? $productDetail->product->brand->name,
                                'brand_name' => $productDetail->product->brand->name,
                            ];
                        }
                    }
                }
            }
        }

        if (count($barcodes) === 0) {
            return redirect()->route('products.print-barcodes');
        }

        return view('products.barcodes.preview', ['barcodes' => $barcodes]);
    }

    public function generateCheckDigit($code)
    {

        if (strlen($code) !== 12) {
            throw new \Exception('Code must be 12 digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $remainder = $sum % 10;
        return $remainder === 0 ? 0 : 10 - $remainder;
    }

    public function setBarcodeSession(Request $request)
    {
        Session::put('barcodesToBePrinted', $request->all());
        return response()->json([
            "success" => true,
            "message" => "Session stored successfully"
        ]);
    }
    public function saveBarcodes()
    {
        $getPrinted = (object) Session::get('barcodesToBePrinted');

        foreach ($getPrinted->barcodesToBePrinted as $data) {
            if (!isset($data['product'])) {
                $product = Product::with('quantities')->find($data['productId']);

                $filteredQuantities = $product->quantities->filter(function ($quantity) {
                    return ($quantity->total_quantity - $quantity->original_printed_barcodes) > 0;
                });

                foreach ($filteredQuantities as $filteredQuantity) {
                    $data['product'][] = [
                        'id' => $filteredQuantity->id,
                        'orignalQty' => $filteredQuantity->total_quantity - $filteredQuantity->original_printed_barcodes,
                        'printQty' => $filteredQuantity->total_quantity - $filteredQuantity->original_printed_barcodes
                    ];
                }
            }

            if (isset($data['product'])) {
                foreach ($data['product'] as $quantityDetail) {
                    $productQuantity = ProductQuantity::where('id', $quantityDetail['id'])->first();

                    $updatedOriginalQuantity = $productQuantity->original_printed_barcodes + $quantityDetail['orignalQty'];

                    if ($productQuantity->total_quantity >= $updatedOriginalQuantity) {
                        $productQuantity->original_printed_barcodes = $updatedOriginalQuantity;
                        $productQuantity->quantity = $productQuantity->quantity + $quantityDetail['orignalQty'];
                    }

                    if (!$productQuantity->first_barcode_printed_date) {
                        $productQuantity->first_barcode_printed_date = Carbon::now();
                    }

                    $productQuantity->total_printed_barcodes = $productQuantity->total_printed_barcodes + $quantityDetail['printQty'];
                    $productQuantity->save();
                }

                $product = Product::find($data['productId']);
                $product->are_barcodes_printed = 1;

                if ($product->quantities->sum('total_quantity') === $product->quantities->sum('original_printed_barcodes')) {
                    $product->barcodes_printed_for_all = 1;
                }

                $product->save();
            }
        }

        Session::forget('barcodesToBePrinted');
        return redirect()->route('products.print-barcodes')->with('success', 'Barcodes Printed Successfully');
    }

    public function downloadExcel()
    {
        $spreadsheet = new Spreadsheet();

        $this->addDepartmentSheet($spreadsheet);
        $this->addProductType($spreadsheet);
        $this->addBrand($spreadsheet);
        $this->addSizeScale($spreadsheet);
        $this->addSizes($spreadsheet);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'product_configuration' . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addDepartmentSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Departments');

        $headers = ['ID', 'Name', 'Slug', 'Description', 'Status'];
        $sheet->fromArray($headers, NULL, 'A1');

        $departments = Department::all();

        foreach ($departments as $key => $department) {
            $sheet->fromArray(
                [$department->id, $department->name, $department->slug, $department->description, $department->status],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    protected function addProductType($spreadsheet)
    {
        $spreadsheet->setActiveSheetIndex(1);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Product Types');

        $headers = ['ID', 'Department Id', 'Department', 'Product Type Id', 'Product Name'];
        $sheet->fromArray($headers, NULL, 'A1');

        $productTypeDepartments = ProductTypeDepartment::with('productTypes', 'departments')->get();
        foreach ($productTypeDepartments as $key => $productType) {
            $sheet->fromArray(
                [$productType->id, $productType->department_id, $productType->departments->name, $productType->product_type_id, $productType->productTypes->name],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    protected function addBrand($spreadsheet)
    {
        $spreadsheet->setActiveSheetIndex(2);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Brands');

        $headers = ['ID', 'Name', 'Slug', 'Description', 'Margin', 'Status'];
        $sheet->fromArray($headers, NULL, 'A1');

        $brands = Brand::all();

        foreach ($brands as $key => $brand) {
            $sheet->fromArray(
                [$brand->id, $brand->name, $brand->slug, $brand->description, $brand->margin, $brand->status],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    protected function addSizeScale($spreadsheet)
    {
        $spreadsheet->setActiveSheetIndex(3);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Size Scales');

        $headers = ['ID', 'Size Scale', 'Status'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sizeScales = SizeScale::all();

        foreach ($sizeScales as $key => $sizeScale) {
            $sheet->fromArray(
                [$sizeScale->id, $sizeScale->name, $sizeScale->status],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    protected function addSizes($spreadsheet)
    {
        $spreadsheet->setActiveSheetIndex(4);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sizes');

        $headers = ['ID', 'Size Scale Id', 'Size', 'New Code', 'Old Code', 'Status'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sizes = Size::all();

        foreach ($sizes as $key => $size) {
            $sheet->fromArray(
                [$size->id, $size->size_scale_id, $size->size, $size->new_code, $size->old_code, $size->status],
                NULL,
                'A' . ($key + 2)
            );
        }
    }

    public function productValidate(Request $request, $barcode)
    {
        $fromStoreId = (int) ($request->from ?? 1);
        $products = ProductQuantity::with('product', 'product.brand', 'product.department', 'product.productType', 'sizes.sizeDetail', 'colors.colorDetail')->get();

        foreach ($products as $product) {
            if (is_null($product->product)) {
                continue;
            }

            $article_code = $product->product->article_code ?? '';
            $color_code = $product->colors->colorDetail->code;
            $new_code = $product->sizes->sizeDetail->new_code;
            $article_code = $article_code . $color_code . $new_code;
            $checkCode = $this->generateCheckDigit($article_code);

            $generated_code = $article_code . $checkCode;


            if ($generated_code == $barcode) {
                $availableQuantity = $product->quantity;

                if ($fromStoreId > 1) {
                    $fromStoreInventory = StoreInventory::where('store_id', $fromStoreId)->where('product_quantity_id', $product->id)->first();

                    if ($fromStoreInventory) {
                        $availableQuantity = $fromStoreInventory->quantity;
                    } else {
                        $availableQuantity = 0;
                    }
                }

                $item = [
                    'id' => $product->id,
                    'product_id' => $product->product->id,
                    'code' => $product->product->article_code,
                    'description' => $product->product->short_description,
                    'type' => $product->product->productType->name,
                    'product_quantity_id' => $product->id,
                    'color' => $product->colors->colorDetail->name,
                    'color_id' => $product->colors->colorDetail->id,
                    'size' => $product->sizes->sizeDetail->size,
                    'size_id' => $product->sizes->sizeDetail->id,
                    'brand' => $product->product->brand->name,
                    'brand_id' => $product->product->brand->id,
                    'price' => (float) $product->sizes->mrp,
                    'available_quantity' => $availableQuantity,
                    'manufacture_barcode' => $product->manufacture_barcode,
                    'manufacture_code' => $product->product->manufacture_code,
                    'department' => $product->product->department->name,
                    'barcode' => $barcode,
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Product barcode is valid.',
                    'product' => $item
                ], 200);
            }
        }

        return response()->json(['success' => false, 'message' => 'Product barcode is invalid.']);
    }

    public function checkManufactureCode($manufactureCode)
    {
        $exists = Product::where('manufacture_code', $manufactureCode)->whereNull('deleted_at')->exists();
        return response()->json(['exists' => $exists]);
    }

    public function editWebConfigration(Product $product)
    {
        $tags = Tag::where('status', 'Active')->orderBy('name', 'ASC')->get();
        return view('products.web-configuration.edit', compact('product', 'tags'));
    }

    protected function syncSpecifications($productId, $specifications)
    {
        foreach ($specifications as $specification) {
            ProductWebSpecification::updateOrCreate(
                [
                    'product_id' => $productId,
                    'key' => $specification['key'],
                ],
                [
                    'value' => $specification['value'],
                ]
            );
        }

        $currentSpecificationKeys = ProductWebSpecification::where('product_id', $productId)
            ->pluck('key')
            ->toArray();

        $newSpecificationKeys = array_column($specifications, 'key');

        $keysToDelete = array_diff($currentSpecificationKeys, $newSpecificationKeys);

        if (!empty($keysToDelete)) {
            ProductWebSpecification::where('product_id', $productId)
                ->whereIn('key', $keysToDelete)
                ->delete();
        }
    }

    public function updateWebConfigration(Request $request, Product $product)
    {
        if ($request->removed_color_images) {
            $productColorIds = explode(',', $request->removed_color_images);

            foreach ($productColorIds as $productColorId) {
                $productColor = ProductColor::find($productColorId);

                if ($productColor) {
                    $filePath = public_path($productColor->swatch_image_path);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    $productColor->update([
                        'swatch_image_path' => ''
                    ]);
                }
            }
        }

        if ($request->hasFile('color_images')) {
            foreach ($request->file('color_images') as $colorId => $image) {
                $imagePath = uploadFile($image, 'uploads/colors/swatches/');
                $productColor = ProductColor::find($colorId);

                if ($productColor->swatch_image_path) {
                    $filePath = public_path($productColor->swatch_image_path);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                if ($productColor) {
                    $productColor->update([
                        'swatch_image_path' => $imagePath
                    ]);
                }
            }
        }

        if ($request->removed_product_images) {
            $productImageIds = explode(',', $request->removed_product_images);

            foreach ($productImageIds as $imageId) {
                $productImage = ProductWebImage::find($imageId);

                if ($productImage) {
                    $filePath = public_path($productImage->path);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    $productImage->delete();
                }
            }
        }


        if ($request->images) {
            foreach ($request->images as $colorId => $images) {
                foreach ($images as $index => $image) {
                    if ($image->getSize() < 10240 * 1024) {
                        $imagePath = uploadFile($image, 'uploads/products/images/');

                        ProductWebImage::create(
                            [
                                'product_id' => $product->id,
                                'product_color_id' => $colorId > 0 ? $colorId : null,
                                'path' => $imagePath,
                                'alt' => $request->image_alt[$colorId][$index] ?? ''
                            ]
                        );
                    }
                }
            }
        }

        if ($request->saved_image_alt) {
            foreach ($request->saved_image_alt as $imageId => $image_alt) {
                $image = ProductWebImage::find($imageId);

                if ($image) {
                    $image->update([
                        'alt' => $image_alt
                    ]);
                }
            }
        }

        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'sale_price' => 'nullable|numeric',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',
            'heading' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'summary' => 'nullable|string',
            'description' => 'nullable|string',
            'specifications.*.key' => 'required|string',
            'specifications.*.value' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:10240',
        ]);

        $product->update([
            'name' => $request->name,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'sale_start' => isset($request->sale_start) ? Carbon::parse($request->sale_start)->toDateString() : null,
            'sale_end' => isset($request->sale_end) ? Carbon::parse($request->sale_end)->toDateString() : null,
        ]);

        $sizes = $request->sizes ?? [];

        foreach ($sizes as $productSizeId => $size) {
            $productSize = ProductSize::find($productSizeId);

            if ($productSize) {
                $productSize->update([
                    'web_price' => $size['web_price'],
                    'web_sale_price' => $size['web_sale_price'],
                ]);
            }
        }

        $currentTags = ProductTag::where('product_id', $product->id)->pluck('tag_id')->toArray();
        $selectedTags = $request->tags ?? [];

        foreach ($selectedTags as $tagId) {
            ProductTag::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'tag_id' => $tagId
                ],
                [
                    'product_id' => $product->id,
                    'tag_id' => $tagId
                ]
            );
        }

        $removedTags = array_diff($currentTags, $selectedTags);
        foreach ($removedTags as $tagId) {
            ProductTag::where('product_id', $product->id)
                ->where('tag_id', $tagId)
                ->delete();
        }

        if ($request->specifications) {
            $this->syncSpecifications($product->id, $request->specifications);
        }

        ProductWebInfo::updateOrCreate(
            ['product_id' => $product->id],
            [
                'is_splitted_with_colors' => $request->has('split_with_colors') ? 1 : 0,
                'heading' => $request->heading,
                'summary' => $request->summary,
                'description' => $request->description,
                'meta_title' => $request->meta_title,
                'meta_keywords' => $request->meta_keywords,
                'meta_description' => $request->meta_description,
                'status' => $request->visibilty,
            ]
        );

        return redirect()->back()->with('success', 'Product web configuration updated successfully.');
    }

    public function bulkUpdate(Request $request)
    {
        if ($request->action !== 'Assign Tags' && $request->action !== 'Unassign Tags') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid performed action'
            ]);
        }

        $selectedProducts = $request->selected_products ?? [];
        $unselectedProducts = $request->unselected_products ?? [];

        if (empty($selectedProducts) || (count($selectedProducts) == 1 && $selectedProducts[0] == -1)) {
            $products = Product::whereNotIn('id', $unselectedProducts)->get();
        } else {
            $products = Product::whereIn('id', $selectedProducts)->whereNotIn('id', $unselectedProducts)->get();
        }

        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No products found to update'
            ]);
        }

        if ($request->action === 'Assign Tags') {
            $tags = Tag::whereIn('id', $request->tags)->get();

            foreach ($products as $product) {
                foreach ($tags as $tag) {
                    if (!$product->tags()->where('tag_id', $tag->id)->exists()) {
                        $product->tags()->create([
                            'tag_id' => $tag->id
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Tags assigned successfully'
            ]);
        } elseif ($request->action === 'Unassign Tags') {
            $tags = Tag::whereIn('id', $request->tags)->get();

            foreach ($products as $product) {
                foreach ($tags as $tag) {
                    $product->tags()->where('tag_id', $tag->id)->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Tags unassigned successfully'
            ]);
        }
    }

    public function uploadColorImage(Request $request)
    {

        $productColor = ProductColor::where('product_id', $request->article_id)->where('color_id', $request->color_id)->first();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = 'color_' . $request->article_id . '_' . $request->color_id . '.' . $image->getClientOriginalExtension();
            $path = 'uploads/products/';
            $image->move(public_path($path), $imageName);

            $imagePath = $path . $imageName;
            $productColor->image_path = $imagePath;
            $productColor->save();
            return response()->json(['success' => true, 'message' => 'Successfully Updated']);
        }
        return response()->json(['success' => false, 'message' => 'Something went wrong']);
    }
    public function fetchColorImage(Request $request)
    {

        $productColor = ProductColor::where('product_id', $request->article_id)->where('color_id', $request->color_id)->first();
        if ($productColor->image_path) {
            return response()->json(['success' => true, 'imagePath' => asset($productColor->image_path)]);
        } else {
            return response()->json(['success' => false, 'imagePath' => 'Image not found']);
        }
    }
    public function deleteColorImage(Request $request)
    {
        $productColor = ProductColor::where('product_id', $request->article_id)->where('color_id', $request->color_id)->first();
        $path = public_path($productColor->image_path);
        if (file_exists($path)) {
            unlink($path);
            $productColor->image_path = '';
            $productColor->save();
            return response()->json(['success' => true, 'message' => 'Product Delete Successfully']);
        }
    }
    public function checkMfgCode($mfgCode = null){
        $products = Product::where('manufacture_code',$mfgCode)->first();

        if($products){
            return response()->json(["success" => true, "message" => "This code already existing"]);
        }else{
            return response()->json(["success" => false, "message" => "true"]);
        }
    }
}
