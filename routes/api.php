<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WishlistController;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Route;

// --- RUTE PUBLIK (Tanpa Token) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Admin Login diletakkan terpisah
// Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// POST /api/contact
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/subscribe', [ContactController::class, 'subscribe']);

Route::get('/categories', [CategoryController::class, 'index']);

// =========================================================================
// RUTE KATALOG PRODUK (PUBLIK)
// Siapapun bisa melihat daftar produk dan detail produk tanpa login
// =========================================================================
// Route::get('/products', [ProductController::class, 'index']);
// Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/inactive', [ProductController::class, 'inactiveProducts']);

Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/products/presigned-url', [ProductController::class, 'getPresignedUrl'])->middleware('auth:sanctum');

Route::post('/biteship/callback', [TransactionController::class, 'biteshipCallback']);
Route::post('/payments/callback', [PaymentController::class, 'callback']);

// --- PROTECTED ROUTES (Butuh Token Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    // Profil & Users
    Route::get('/admin/users', [AuthController::class, 'getAllUsers']); // Idealnya dibungkus middleware admin lagi
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/admin/update-info', [AuthController::class, 'updateAdminProfileInfo']);
    Route::post('/admin/presigned-url', [AuthController::class, 'getProfilePresignedUrl']);
    Route::post('/admin/update-image', [AuthController::class, 'updateAdminImage']);
    Route::post('/admin/update-password', [AuthController::class, 'updateAdminPassword']);

    Route::get('/admin/messages', [ContactController::class, 'getInboundMessages']);
    Route::get('/admin/messages/{id}', [ContactController::class, 'showAdminMessage']);
    Route::post('/admin/messages/{id}/respond', [ContactController::class, 'respondMessage']);
    Route::get('/user/contact-history', [ContactController::class, 'userHistory']);

    // Addresses
    // Anda bisa mendefinisikannya satu-satu untuk mencerminkan Golang Mux:
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    // ATAU, menggunakan cara instan Laravel (Sangat disarankan):
    // Route::apiResource('addresses', AddressController::class)->except(['show']);

    // Categories (Admin)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // // Profil Pengguna (User biasa)
    // Route::put('/profile', [AuthController::class, 'updateProfile']);

    // // Rute Khusus Admin (Dalam praktiknya, Anda mungkin ingin menambahkan middleware role khusus untuk ini)
    // Route::get('/admin/users', [AuthController::class, 'getAllUsers']);

    // Route::post('/products', [ProductController::class, 'store']);
    // Route::put('/products/{id}', [ProductController::class, 'update']);
    // Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::put('/products/{id}/restore', [ProductController::class, 'restore']);
    Route::delete('/products/{id}/force', [ProductController::class, 'forceDelete']);

    Route::get('/admin/product-stocks', [ProductStockController::class, 'index']);
    Route::post('/admin/product-stocks/{productId}', [ProductStockController::class, 'store']);

    // =========================================================================
    // RUTE KERANJANG (CART)
    // =========================================================================
    Route::get('/carts', [CartController::class, 'index']);
    Route::post('/carts', [CartController::class, 'store']);
    Route::put('/carts/{id}', [CartController::class, 'update']);
    Route::delete('/carts/{id}', [CartController::class, 'destroy']);

    Route::post('/checkout', [TransactionController::class, 'checkout']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancelOrder']);
    Route::post('/transactions/{id}/confirm', [TransactionController::class, 'confirmComplete']);
    Route::post('/transactions/{id}/refund-request', [TransactionController::class, 'requestRefund']);
    Route::post('/transactions/{id}/refund-process', [TransactionController::class, 'processRefundUser']);
    Route::get('/transactions/{id}/tracking', [TransactionController::class, 'trackOrder']);
    Route::post('/transactions/tracking/bulk', [TransactionController::class, 'bulkTrackOrders']);

    Route::get('/admin/transactions', [TransactionController::class, 'allTransactions']);
    Route::get('/admin/transactions/{id}', [TransactionController::class, 'adminShow']);
    Route::post('/admin/transactions/tracking/bulk', [TransactionController::class, 'adminBulkTrackOrders']);
    Route::get('/admin/transactions/{id}/tracking', [TransactionController::class, 'adminTrackOrder']);
    Route::get('/admin/transactions/{id}/print-label', [TransactionController::class, 'printLabel']);

    Route::get('/admin/sales-report', [TransactionController::class, 'salesReport']);

    // Approval Refund (Berurusan dengan uang keluar)
    Route::post('/admin/transactions/{id}/refund-approve', [TransactionController::class, 'approveRefund']);
    Route::post('/admin/transactions/{id}/refund-reject', [TransactionController::class, 'rejectRefund']);

    Route::post('/payments/invoice', [PaymentController::class, 'createInvoice']);
    Route::post('/shipping/rates', [PaymentController::class, 'getShippingRates']);

    Route::get('/wishlists', [WishlistController::class, 'index']);

    Route::post('/wishlists/toggle', [WishlistController::class, 'toggle']);

    Route::get('/admin/subscribers', function () {
        return response()->json(Subscriber::latest()->get());
    });
});
