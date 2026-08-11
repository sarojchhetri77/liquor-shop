<?php

use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\DashboardController as ShopDashboardController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\OrderController as ShopOrderController;
use App\Http\Controllers\Shop\ProductController as ShopProductController;
use App\Http\Controllers\Shop\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (public + customer)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ShopProductController::class, 'index'])->name('shop.products.index');
Route::get('/search/suggestions', [ShopProductController::class, 'suggestions'])->name('shop.search.suggestions');
Route::get('/products/{product:slug}', [ShopProductController::class, 'show'])->name('shop.products.show');

// Post-login landing: staff/admins go to the panel, customers to their account dashboard.
Route::get('/dashboard', [ShopDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('shop.cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('shop.cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('shop.cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('shop.cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('shop.checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('shop.checkout.store');

    Route::get('/orders', [ShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('/orders/{order}', [ShopOrderController::class, 'show'])->name('shop.orders.show');
    Route::post('/orders/{order}/cancel', [ShopOrderController::class, 'cancel'])->name('shop.orders.cancel');

    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('shop.reviews.store');
});

/*
|--------------------------------------------------------------------------
| Admin panel (admin + staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin,staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::post('brands', [AdminBrandController::class, 'store'])->name('brands.store');

        Route::post('products/discount', [AdminProductController::class, 'applyDiscount'])->name('products.discount');
        Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        // Product update uses POST + _method=PUT so multipart image uploads work reliably.
        Route::post('products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('promotions', [AdminPromotionController::class, 'index'])->name('promotions.index');
        Route::get('promotions/create', [AdminPromotionController::class, 'create'])->name('promotions.create');
        Route::post('promotions', [AdminPromotionController::class, 'store'])->name('promotions.store');
        Route::get('promotions/{promotion}/edit', [AdminPromotionController::class, 'edit'])->name('promotions.edit');
        // POST + _method=PUT so multipart image uploads work reliably.
        Route::post('promotions/{promotion}', [AdminPromotionController::class, 'update'])->name('promotions.update');
        Route::delete('promotions/{promotion}', [AdminPromotionController::class, 'destroy'])->name('promotions.destroy');

        // Staff management is restricted to administrators only.
        Route::middleware('role:admin')->group(function () {
            Route::get('staff', [AdminStaffController::class, 'index'])->name('staff.index');
            Route::get('staff/create', [AdminStaffController::class, 'create'])->name('staff.create');
            Route::post('staff', [AdminStaffController::class, 'store'])->name('staff.store');
            Route::get('staff/{staff}/edit', [AdminStaffController::class, 'edit'])->name('staff.edit');
            Route::put('staff/{staff}', [AdminStaffController::class, 'update'])->name('staff.update');
            Route::delete('staff/{staff}', [AdminStaffController::class, 'destroy'])->name('staff.destroy');
        });
    });

require __DIR__.'/settings.php';
