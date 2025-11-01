<?php

use App\Http\Controllers\Api\AccountDetailsController;
use App\Http\Controllers\Api\Admin\AdminBlogController;
use App\Http\Controllers\Api\Admin\AdminOrdersController;
use App\Http\Controllers\Api\Admin\BlogCategoryController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CallToActionController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DiscountController;
use App\Http\Controllers\Api\Admin\HeroController;
use App\Http\Controllers\Api\Admin\MenuController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\API\Admin\Settings\SocialMediaController;
use App\Http\Controllers\Api\Admin\ShippingMethodsController;
use App\Http\Controllers\Api\Admin\TaxRateController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\CustomerWishlistController;
use App\Http\Controllers\Api\Frontend\HomeController;
use App\Http\Controllers\Api\PublicTaxRateController;
use App\Http\Controllers\Api\ReviewsController;
use App\Http\Controllers\Api\ShopPageController;
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
use App\Http\Controllers\SpotlightController;
use App\Http\Controllers\SubscriberController;
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
    Route::get('/get-hero', [HeroController::class, 'index']);
    Route::get('/get-spotlight-frontpage', [SpotlightController::class, 'frontpage']);
    Route::get('/get-call-to-actions', [CallToActionController::class, 'index']);
    Route::post('/add-subscriber', [SubscriberController::class, 'store']);

    Route::get('/best-selling', [HomeController::class, 'bestSelling']);

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

    // Public Tax rate controller
    Route::get('tax-rates', [PublicTaxRateController::class, 'index']);
    Route::get('tax-rates/{countryCode}', [PublicTaxRateController::class, 'show']);
    Route::post('tax-rates/{countryCode}/calculate', [PublicTaxRateController::class, 'calculate']);
    Route::get('/get-menus', [MenuController::class, 'index']);
    Route::get('/user-shipping-methods/get', [ShippingMethodsController::class, 'index']);

    // shop page route
    Route::get('shop-products', [ShopPageController::class, 'index']);
    Route::get('shop-category', [ShopPageController::class, 'getCategory']);
    Route::get('shop-random-category', [ShopPageController::class, 'randomCategories']);

    Route::get('/get-brand-logos', [BrandController::class, 'getBrandsLogo']);

    // Blog
    Route::get('/blog/get', [AdminBlogController::class, 'index']);
    Route::get('/blog/view/{slug}', [AdminBlogController::class, 'view']);
    Route::post('/blog/view/{slug}/comment/store', [AdminBlogController::class, 'storeComment']);
    Route::get('/settings/social-links', [SocialMediaController::class, 'index']);
});

Route::middleware(['web', 'admin', 'prevent.back', EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function () {
    Route::post('/add-product', [ProductController::class, 'store']);
    Route::get('get-product', [ProductController::class, 'index']);
    Route::get('edit-product/{slug}', [ProductController::class, 'edit']);
    Route::post('update-product/{slug}', [ProductController::class, 'update']);
    Route::delete('/delete-product/{id}', [ProductController::class, 'destroy']);
    Route::post('/add-category', [CategoryController::class, 'store']);
    Route::get('/get-category', [CategoryController::class, 'index']);
    Route::post('/add-discount', [DiscountController::class, 'store']);
    Route::get('/get-discount', [DiscountController::class, 'index']);
    Route::post('/add-brand', [BrandController::class, 'store']);
    Route::get('/get-brands', [BrandController::class, 'index']);

    // customers
    Route::get('/customers', [CustomerController::class, 'index']);
    // menus
    Route::post('/add-menu', [MenuController::class, 'store']);

    // shipping
    Route::post('/settings/shipping-methods/add', [ShippingMethodsController::class, 'store']);
    Route::get('/settings/shipping-methods/get', [ShippingMethodsController::class, 'index']);

    // social media
    Route::post('/settings/social-media/store', [SocialMediaController::class, 'store']);
    Route::delete('/settings/social-media/delete/{id}', [SocialMediaController::class, 'destroy']);
    Route::get('/settings/social-media', [SocialMediaController::class, 'index']);

    Route::post('/store-hero', [HeroController::class, 'store']);
    Route::post('/add-spotlight', [SpotlightController::class, 'store']);
    Route::get('/get-spotlight', [SpotlightController::class, 'index']);
    Route::post('/add-call-to-action', [CallToActionController::class, 'store']);

    // Tax Rates
    Route::apiResource('tax-rates', TaxRateController::class);
    Route::post('tax-rates/bulk-import', [TaxRateController::class, 'bulkImport']);
    Route::get('tax-rates/export/csv', [TaxRateController::class, 'export']);

    // blog route
    Route::post('/store-post-category', [BlogCategoryController::class, 'store']);
    Route::get('/get-post-category', [BlogCategoryController::class, 'index']);
    Route::post('/blog/create', [AdminBlogController::class, 'store']);
    Route::get('/blog/comments', [AdminBlogController::class, 'fetchComments']);
    Route::post('/blog/comments/approve/{id}', [AdminBlogController::class, 'approveComment']);

    // Contact 
    Route::get('/admin/contact-us/get', [ContactController::class, 'index']);

    // Admin orders
    Route::get('/admin/all-orders', [AdminOrdersController::class, 'index']);
});


Route::middleware(['prevent.back', EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function () {

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
    Route::get('/get-single-shipping-address/{id}', [ShippingAddressController::class, 'singleAddress']);
    Route::get('/get-wishlists', [CustomerWishlistController::class, 'index']);
    Route::delete('/remove-from-wishlist/{id}', [CustomerWishlistController::class, 'destroy']);
    Route::post('/place-order', [CheckoutController::class, 'store']);
    Route::get('/get-single-order/{id}', [CheckoutController::class, 'getOrderSingle']);
    Route::get('/all-orders', [CheckoutController::class, 'allOrders']);
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
