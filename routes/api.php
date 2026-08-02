<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PropertyFeatureController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\FlutterwaveWebhookController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\PromotionPackageController;
use App\Http\Controllers\ProfileController;
/*
|--------------------------------------------------------------------------
| API Routes - FIXED VERSION
|--------------------------------------------------------------------------
| Key fix: Specific routes BEFORE catch-all routes like /{id}/properties
*/

// ========================================
// PUBLIC ROUTES (No Authentication)
// ========================================

Route::post('/login', [AuthController::class, 'general_login']);
Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/signin', [AuthController::class, 'signin']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/auth/resend-otp', [OtpController::class, 'resendOtp']);
Route::post('/auth/verify-otp', [OtpController::class, 'verifyOtp']);
Route::post('/auth/setup-password', [AuthController::class, 'setupPassword']);

Route::post('/contact', [App\Http\Controllers\ContactController::class, 'store']);

Route::get('/roles', [RolesController::class, 'user_roles']);

// Public property routes
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/{slug}/detail', [PropertyController::class, 'propertyDetail']);
Route::get('/all-property-types', [PropertyController::class, 'propertyType']);
Route::get('/featured-properties', [PropertyController::class, 'featuredProperties']);

Route::get('/featured-plans', function(){
    $plans = \App\Models\PromotionPackages::where('promotionType', 'featured')
        ->orderBy('packageId')
        ->get()
        ->makeHidden(['created_at', 'updated_at', 'deleted_at']);
    return response()->json($plans);
});

// Payment callbacks (public)
Route::get('/promotion/verify-redirect', [PropertyFeatureController::class, 'handleCallback']);
Route::post('/webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handle']);
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');


// ========================================
// PROTECTED ROUTES (JWT Authentication)
// ========================================

Route::middleware(['auth.jwt'])->group(function () {
    
    // ========================================
    // USER PROFILE & AUTH
    // ========================================
    
    Route::get('/users/profile', [AuthController::class, 'profile']);
    
    Route::get('/user', function () {
        $user = auth()->user();
        return response()->json([
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'otherNames' => $user->otherNames,
            'email' => $user->email,
            'role' => $user->role,
            'roleName' => $user->user_role,
            'id' => $user->id,
            'message' => 'User authenticated successfully',
        ]);
    });

    Route::get('/promotion-packages', [PromotionPackageController::class, 'index']);
    Route::post('/promotion-packages', [PromotionPackageController::class, 'store']);
    Route::get('/promotion-packages/{id}', [PromotionPackageController::class, 'show']);
    Route::put('/promotion-packages/{id}', [PromotionPackageController::class, 'update']);
    Route::patch('/promotion-packages/{id}/toggle', [PromotionPackageController::class, 'toggleActive']);
    Route::delete('/promotion-packages/{id}', [PromotionPackageController::class, 'destroy']);

    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::post('/user/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::get('/user/profile/stats', [ProfileController::class, 'stats']);
    Route::post('/user/verify-email/resend', [ProfileController::class, 'resendVerification']);
    Route::put('/user/change-password', [ProfileController::class, 'changePassword']);

    // ========================================
    // DASHBOARD STATS
    // ========================================
    
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    
    // ========================================
    // PROPERTY ROUTES
    // ========================================
    
    // IMPORTANT: Specific property routes MUST come BEFORE general {slug} route
    Route::get('/properties/my', [PropertyController::class, 'myProperties']);
    
    Route::get('/my-properties/user', [PropertyController::class, 'userProperties']);
    Route::get('/my-properties/user/stats', [PropertyController::class, 'userStats']);
    Route::get('/my-properties/my', [PropertyController::class, 'myProperties']);
    Route::post('/my-properties', [PropertyController::class, 'store']);
    Route::put('/my-properties/{slug}', [PropertyController::class, 'update']);
    Route::delete('/my-properties/{slug}', [PropertyController::class, 'destroy']);
    Route::delete('/my-properties/{slug}/images/{imageId}', [PropertyController::class, 'deleteImage']);
    Route::patch('/my-properties/{slug}/status', [PropertyController::class, 'updateStatus']);

    Route::post('/properties', [PropertyController::class, 'store']);
    Route::post('/properties/{slug}/rate', [PropertyController::class, 'rate']);
    Route::get('/properties/{slug}', [PropertyController::class, 'show']);
    Route::put('/properties/{slug}/edit', [PropertyController::class, 'update'])->name('properties.update');
    Route::patch('/properties/{slug}/status', [PropertyController::class, 'updateStatus']);
    Route::delete('/properties/{slug}', [PropertyController::class, 'destroy']);
    Route::delete('/properties/{slug}/images/{imageId}', [PropertyController::class, 'deleteImage']);
    
    // Property feature/promotion payment
    Route::post('/properties/{slug}/initiate-feature-payment', [PropertyFeatureController::class, 'initiatePayment']);
    Route::get('/properties/feature/callback', [PropertyFeatureController::class, 'handleCallback'])->name('feature.callback');

    
    // ========================================
    // FAVORITES
    // ========================================
    
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::get('/favorites/check/{propertyId}', [FavoriteController::class, 'check']);
    Route::delete('/favorites/{propertyId}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites', [FavoriteController::class, 'index']); // Add this for saved properties page

    
    // ========================================
    // MESSAGES/INQUIRIES
    // ========================================
    
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/inbox', [MessageController::class, 'inbox']);
    Route::get('/messages/sent', [MessageController::class, 'sent']); // ← NEW
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply']);
    Route::patch('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);

    
    // ========================================
    // UTILITY ROUTES
    // ========================================
    
    Route::get('/currencies', function (){
        $currencies = \App\Models\Currency::all();
        return response()->json($currencies);
    });
    
    Route::get('/property-types', function (){
        $propertyTypes = \App\Models\PropertyType::all();
        return response()->json($propertyTypes);
    });

    
    // ========================================
    // USER MANAGEMENT
    // ========================================
    
    Route::get('/users', [UsersController::class, 'index']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::put('/users/{user}', [UsersController::class, 'update']);
    Route::delete('/users/{user}', [UsersController::class, 'destroy']);
    Route::patch('/users/{user}/status', [UsersController::class, 'updateStatus']);
    Route::get('/users/{user}/properties', [UsersController::class, 'properties']); // Specific route
    Route::get('/users/admins', [UsersController::class, 'admins']);
    Route::get('/users/admin_roles', [RolesController::class, 'admin_roles']);
    Route::delete('/users/{userId}/delete', [UsersController::class, 'destroy']);
    
    // MOVED TO END: Catch-all user properties route
    // This was causing issues because it was catching routes like /dashboard/...
    Route::get('/users/{id}/properties', [UsersController::class, 'properties']);

    
    // ========================================
    // ADMIN ROUTES
    // ========================================
    
    Route::prefix('admin')->group(function () {
        // Get all properties (admin view)
        Route::get('/properties', [PropertyController::class, 'adminIndex']);
        
        // Get revenue analytics
        Route::get('/revenue', [PropertyController::class, 'adminRevenue']);
        
        // Get analytics stats
        Route::get('/analytics/stats', [PropertyController::class, 'adminStats']);
        
        // Approve/Reject property
        Route::patch('/properties/{slug}/approve', [PropertyController::class, 'approveProperty']);
        
        // Feature/Unfeature property
        Route::patch('/properties/{slug}/feature', [PropertyController::class, 'featureProperty']);
        
        // Delete any property (admin)
        Route::delete('/properties/{slug}', [PropertyController::class, 'adminDestroy']);
    });
});