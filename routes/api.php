<?php

use App\Http\Controllers\Api\AccountDetailsController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DiscountController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Frontend\HomeController;
use App\Http\Controllers\Api\ReviewsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BillingAddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ShippingAddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;


Route::middleware(['web'])->group(function () {
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    Route::get('/csrf-token', function () {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    });
    // Public route
    Route::post('/contact-us', [ContactController::class, 'store']);
    Route::get('/get-latest-products', [HomeController::class, 'latestProducts']);
    Route::get('/shop-by-category', [HomeController::class, 'shopByCategory']);
    Route::get('/shop/{slug}', [ProductController::class, 'show']);
    Route::post('/store-reviews/{id}', [ReviewsController::class, 'store']);
    Route::post('/store/add-to-wishlist/{id}', [ProductController::class, 'addToWishlist']);
    Route::get('/store/check-wishlist/{id}', [ProductController::class, 'checkWishlist']);

    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/update/{id}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    Route::get('/cart/total', [CartController::class, 'cartTotal']);
    Route::get('/cart/item-exists/{id}', [CartController::class, 'itemExists']);
    Route::put('/cart/update-quantity/{id}', [CartController::class, 'updateQuantity']);
    // get quantity
    Route::get('/cart/get-cart/{id}', [CartController::class, 'getCart']);
});

Route::middleware(['web', 'admin', EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function () {
    Route::post('/add-product', [ProductController::class, 'store']);
    Route::get('get-product', [ProductController::class, 'index']);
    Route::delete('/delete-product/{id}', [ProductController::class, 'destroy']);
    Route::post('/add-category', [CategoryController::class, 'store']);
    Route::get('/get-category', [CategoryController::class, 'index']);
    Route::post('/add-discount', [DiscountController::class, 'store']);
    Route::get('/get-discount', [DiscountController::class, 'index']);
    Route::post('/add-brand', [BrandController::class, 'store']);
    Route::get('/get-brands', [BrandController::class, 'index']);

    // customers
    Route::get('/customers', [CustomerController::class, 'index']);
});


Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function () {

    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    Route::post('/update-account-details', [AccountDetailsController::class, 'store']);
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/get-account-details', [AccountDetailsController::class, 'getAccountDetails']);
    Route::post('/update-billing-address', [BillingAddressController::class, 'store']);
    Route::get('/get-billing-address', [BillingAddressController::class, 'billingAddress']);
    Route::post('/update-shipping-address', [ShippingAddressController::class, 'store']);
    Route::get('/get-shipping-address', [ShippingAddressController::class, 'shippingAddress']);
});



// Route::middleware('auth')->group(function () {
//     Route::get('verify-email', EmailVerificationPromptController::class)
//         ->name('verification.notice');

//     Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
//         ->middleware(['signed', 'throttle:6,1'])
//         ->name('verification.verify');

//     Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
//         ->middleware('throttle:6,1')
//         ->name('verification.send');

//     Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
//         ->name('password.confirm');

//     Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

//     Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
//         ->name('logout');
// });
