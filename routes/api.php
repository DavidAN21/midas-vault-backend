<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarterController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TradeInController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/reviews/{userId}', [ReviewController::class, 'userReviews']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/user', [AuthController::class, 'updateUser']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Products
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::get('/my-products', [ProductController::class, 'myProducts']);
        Route::get('/all-products', [ProductController::class, 'getAllProducts']);


        // Transactions
        Route::apiResource('transactions', TransactionController::class)->only(['store', 'show']);
        Route::get('/my-transactions', [TransactionController::class, 'myTransactions']);
        Route::patch('/transactions/{transaction}/confirm', [TransactionController::class, 'confirm']);
        Route::patch('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);

        
        // Barters
        Route::apiResource('barters', BarterController::class)->only(['store', 'show']);
        Route::get('/my-barters', [BarterController::class, 'myBarters']);
        Route::patch('/barters/{barter}/accept', [BarterController::class, 'accept']);
        Route::patch('/barters/{barter}/reject', [BarterController::class, 'reject']);
        Route::patch('/barters/{barter}/complete', [BarterController::class, 'complete']);
        Route::patch('/barters/{barter}/cancel', [BarterController::class, 'cancel']); // ✅ PASTIKAN ADA

        // Trade-ins
        Route::apiResource('trade-ins', TradeInController::class)->only(['store', 'show']);
        Route::get('/my-trade-ins', [TradeInController::class, 'myTradeIns']);
        Route::patch('/trade-ins/{tradeIn}/accept', [TradeInController::class, 'accept']);
        Route::patch('/trade-ins/{tradeIn}/reject', [TradeInController::class, 'reject']);
        Route::patch('/trade-ins/{tradeIn}/pay', [TradeInController::class, 'pay']);
        Route::patch('/trade-ins/{tradeIn}/cancel', [TradeInController::class, 'cancel']); // Tambah ini

        // Reviews
        Route::apiResource('reviews', ReviewController::class)->only(['store']);

        // Admin Verification
        Route::middleware('admin')->group(function () {
            Route::get('/admin/overview', [AdminController::class, 'overview']);
            Route::get('/verifications/pending', [VerificationController::class, 'pending']);
            Route::get('/verifications/verified', [VerificationController::class, 'verifiedProducts']);
            Route::get('/verifications/rejected', [VerificationController::class, 'rejectedProducts']); // Tambah ini
            Route::patch('/verifications/{product}', [VerificationController::class, 'update']);
        });
        Route::get('/debug-storage', function() {
            // Test write file
            $testContent = 'Test file content ' . time();
            Storage::disk('public')->put('test.txt', $testContent);
            
            // Test list files
            $files = Storage::disk('public')->files('products');
            
            return response()->json([
                'app_url' => config('app.url'),
                'storage_url' => config('app.url') . '/storage',
                'storage_path' => storage_path('app/public'),
                'public_storage_path' => public_path('storage'),
                'test_file_exists' => Storage::disk('public')->exists('test.txt'),
                'test_file_content' => Storage::disk('public')->get('test.txt'),
                'product_files' => $files,
                'product_files_urls' => array_map(function($file) {
                    return Storage::url($file);
                }, $files)
            ]);
        });
    });
});
