<?php
require __DIR__ . '/product.php';
require __DIR__ . '/pos.php';

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BestBrandOverallController;
use App\Http\Controllers\BestBrandsPerProductTypeController;
use App\Http\Controllers\WeekelyTurnoverController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\SizeScaleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleAndPermissionController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ChangePriceReasonController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\InventoryTransferController;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\ProductImportExportController;
use App\Http\Controllers\CouponDiscountController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PickListController;
use App\Http\Controllers\WebPagesController;
use App\Http\Controllers\WebsiteOrdersController;
use App\Http\Controllers\WebsiteGiftVoucherController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SubmenuOptionController;
use App\Http\Controllers\FooterLinkController;


Route::get('/', function () {
    return redirect()->route('dashboard');
});
Route::get('/generate-barcode', [ProductController::class, 'generateBarcode']);

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/products/export', [ProductImportExportController::class, 'exportProductsToCSV'])->name('products.export');
Route::post('/products/import', [ProductImportExportController::class, 'importProductsFromCSV'])->name('products.import');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resources([
        'departments'   => DepartmentController::class,
        'product-types' => ProductTypeController::class,
        'settings'      => SettingController::class,
        'brands'        => BrandController::class,
        'colors'        => ColorController::class,
        'size-scales'   => SizeScaleController::class,
        'suppliers'     => SupplierController::class,
        'tax-settings'  => TaxController::class,
        'change-price-reasons'  => ChangePriceReasonController::class,
        'products'      => ProductController::class,
        'tags'          => TagController::class,
        'users'         => UserController::class,
        'roles-and-permissions' => RoleAndPermissionController::class,
        'branches' => BranchController::class,
        'inventory-transfer' => InventoryTransferController::class,
        'purchase-orders' => PurchaseOrderController::class,
        'collections' => CollectionController::class,
        'coupons' => CouponController::class,
        'categories' => CategoryController::class,
        'pick-list' => PickListController::class,
        'best-brands-overall' => BestBrandOverallController::class,
        'best-brands-per-product-type' => BestBrandsPerProductTypeController::class,
        'weekely-turnover' => WeekelyTurnoverController::class,
        'webpages' => WebPagesController::class,
        'contact-us' => ContactUsController::class,
        'orders' => WebsiteOrdersController::class,
        'gift-voucher' => WebsiteGiftVoucherController::class,
        'customers' => CustomerController::class,
        'submenu-options' => SubmenuOptionController::class,

    ]);

    Route::resource('footer-links', FooterLinkController::class)->parameters([
        'footer-links' => 'block'
    ]);

    

    Route::get('inventory-history', [InventoryTransferController::class, 'inventoryHistory'])->name('inventory-history');

    Route::get('inventory-transfer-view/{id}', [InventoryTransferController::class, 'inventoryTransferShow'])->name('inventory-transfer-view');
    Route::get('product-validate/{barcode}', [ProductController::class, 'productValidate']);
    Route::post('/inventory-transfer-items', [InventoryTransferController::class, 'inventoryTransferItems']);

    Route::get('roles-and-permissions/role-list', [RoleAndPermissionController::class, 'show'])->name('roles-and-permissions.role-list');
    Route::post('roles-and-permissions/store-role', [RoleAndPermissionController::class, 'storeRole'])->name('roles-and-permissions.store-role');

    Route::post('/test-products', [ProductController::class, 'testing'])->name('test.products');

    Route::get('general-settings', [SettingController::class, 'generalSetting'])->name('general-settings.index');
    Route::post('general-settings', [SettingController::class, 'generalSettingStore'])->name('general-settings.store');

    Route::get('web-settings', [SettingController::class, 'webSetting'])->name('web-settings.index');
    Route::post('web-settings', [SettingController::class, 'webSettingStore'])->name('web-settings.store');

    Route::get('shipping-methods', [SettingController::class, 'shippingMethodSettings'])->name('shipping-methods.index');
    Route::put('shipping-methods/{method}', [SettingController::class, 'shippingMethodUpdate'])->name('shipping-methods.update');

    Route::get('payment-methods', [SettingController::class, 'paymentMethodSettings'])->name('payment-methods.index');
    Route::put('payment-methods/{method}', [SettingController::class, 'paymentMethodUpdate'])->name('payment-methods.update');

    Route::get('size-scales/sizes/{sizeScaleId}', [SizeController::class, 'index'])->name('sizes.index');
    Route::get('size-scales/sizes/{sizeScaleId}/create', [SizeController::class, 'create'])->name('sizes.create');
    Route::post('sizes/{sizeScaleId}', [SizeController::class, 'store'])->name('sizes.store');
    Route::get('size-scales/{sizeScaleId}/sizes/{size}/edit', [SizeController::class, 'edit'])->name('sizes.edit');
    Route::put('size-scales/{sizeScaleId}/sizes/{size}', [SizeController::class, 'update'])->name('sizes.update');
    Route::delete('size-scales/{sizeScaleId}/sizes/{size}', [SizeController::class, 'destroy'])->name('sizes.destroy');

    //************************Status Route********************/
    Route::post('/department-status', [DepartmentController::class, 'updateStatus'])->name('department-status');
    Route::post('/product-types-status', [ProductTypeController::class, 'productTypeStatus'])->name('product-types-status');
    Route::post('/brand-status', [BrandController::class, 'updateStatus'])->name('brand-status');
    Route::post('/branch-status', [BranchController::class, 'updateStatus'])->name('branch-status');
    Route::post('/color-status', [ColorController::class, 'colorStatus'])->name('color-status');
    Route::post('/size-scale-status', [SizeScaleController::class, 'sizeScaleStatus'])->name('size-scale-status');
    Route::post('/size-status', [SizeController::class, 'sizeStatus'])->name('size-status');
    Route::post('/supplier-status', [SupplierController::class, 'supplierStatus'])->name('supplier-status');
    Route::post('/tax-status', [TaxController::class, 'taxStatus'])->name('tax-status');
    Route::post('/product-status', [ProductController::class, 'productStatus'])->name('product-status');
    Route::post('/tag-status', [TagController::class, 'tagStatus'])->name('tag-status');
    Route::post('/categories/update-status', [CategoryController::class, 'updateStatus'])->name('categories.updateStatus');

    Route::post('product-category-status-bulk', [ProductController::class, 'categoryBulkUpdate'])->name('category.bulkUpdate');

    Route::get('/get-states/{countryId}', [SupplierController::class, 'getStates']);
    Route::get('/get-product-type/{departmentId}', [ProductController::class, 'getDepartment']);

    //************************Import Csv Route********************/
    Route::post('import-brands', [BrandController::class, 'importBrands'])->name('import.brands');

    Route::post('printbarcode-store-session', [ProductController::class, 'setBarcodeSession'])->name('printbarcode.store.session');

    Route::get('products/print-barcodes/preview', [ProductController::class, 'downloadBarcodes'])->name('download.barcodes');
    Route::get('products/print-barcodes/save', [ProductController::class, 'saveBarcodes'])->name('save.barcodes');

    Route::get('/export/csv', [ProductImportExportController::class, 'downloadExcel'])->name('export.csv');

    Route::get('download-brand-sample', function () {
        $file = public_path('assets/samples/brand.csv');
        return Response::download($file);
    });

    Route::get('download-product-sample', function () {
        $file = public_path('assets/samples/products.csv');
        return Response::download($file);
    });

    // Profile Routes
    Route::get('/profile', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/check-manufacture-code/{manufactureCode}', [ProductController::class, 'checkManufactureCode'])->name('check.manufacture.code');
    Route::get('/get-size-range', [PurchaseOrderController::class, 'getSizeRange']);

    //Best Brands overall routes
    Route::post('/best-brands-overall/filter', [BestBrandOverallController::class, 'filterData'])->name('best.brands.filter');
    Route::get('/best-brands-overall/export/{type}', [BestBrandOverallController::class, 'exportData'])->name('best.brands.export');
    Route::post('/best-brands-per-product-type/filter', [BestBrandsPerProductTypeController::class, 'filterData'])->name('best.brands.per.product.type.filter');
    Route::post('/weekely-turnover/filter', [WeekelyTurnoverController::class, 'filterData'])->name('weekely-turnover.filter');
});


Route::get('/update-data',[ProductController::class,'updateData']);
Route::get('/import-size', [ProductImportExportController::class, 'showSizeCodeImportForm'])->name('import.size.codes.form');
Route::post('/import-size-codes', [ProductImportExportController::class, 'importSizeCodes'])->name('import.size.codes');
