<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BeneficiariesController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\PromotionPackages;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PropertyFeatureController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MinistryController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\FlutterwaveWebhookController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseTypeController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\FavoriteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// Route::middleware(['cors'])->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'general_login']);
    Route::post('/auth/signup', [AuthController::class, 'signup']);
    Route::post('/auth/signin', [AuthController::class, 'signin']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/users/profile', [AuthController::class, 'profile'])->middleware('auth.jwt'); // Use auth.jwt instead of auth:api

    Route::post('/auth/resend-otp', [OtpController::class, 'resendOtp']);
    Route::post('/auth/verify-otp', [OtpController::class, 'verifyOtp']);
    Route::post('/auth/setup-password', [AuthController::class, 'setupPassword']);

    Route::post('/contact', [App\Http\Controllers\ContactController::class, 'store']);

    Route::get('/roles', [RolesController::class, 'user_roles']);

    Route::get('/announcement', [AnnouncementController::class, 'index']);
   Route::get('/properties', [PropertyController::class, 'index']);
   Route::get('/properties/{slug}/detail', [PropertyController::class, 'propertyDetail']);
    Route::get('/all-property-types', [PropertyController::class, 'propertyType']);

    // Protected routes with JWT authentication
    Route::middleware(['auth.jwt'])->group(function () {
        Route::get('/user', function () {
            $user = auth()->user();
            return response()->json([
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'otherNames' => $user->otherNames,
                'email' => $user->email,
                'role' => $user->role,
                'id' => $user->id,
                'message' => 'User authenticated successfully',
            ]);
        });

    Route::get('/currencies', function (){
        $currencies = \App\Models\Currency::all();
        return response()->json($currencies);
    });
    
    Route::get('/property-types', function (){
        $propertyTypes = \App\Models\PropertyType::all();
        return response()->json($propertyTypes);
    });

        // Application routes
    Route::get('/users/admins', [UsersController::class, 'admins']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::get('/users/admin_roles', [RolesController::class, 'admin_roles']);
    Route::delete('/users/{userId}/delete', [UsersController::class, 'destroy']);

  

    // Route::get('/property-types', [PropertyController::class, 'propertyType']);
    Route::post('/properties', [PropertyController::class, 'store']);
    
    Route::get('/properties/my', [PropertyController::class, 'myProperties']);
    Route::get('/properties/{slug}', [PropertyController::class, 'show']);
    // Route::put('/properties/{slug}', [PropertyController::class, 'update']);
    Route::PUT('/properties/{slug}/edit', [PropertyController::class, 'update'])
    ->name('properties.update');
    Route::PATCH('/properties/{slug}/status', [PropertyController::class, 'updateStatus']);
     Route::delete('/properties/{slug}', [PropertyController::class, 'destroy']);
     Route::delete('/properties/{slug}/images/{imageId}', [PropertyController::class, 'deleteImage']);
    

     Route::post('/favorites', [FavoriteController::class, 'store']);
     Route::get('/favorites/check/{propertyId}', [FavoriteController::class, 'check']);
    
    Route::delete('/favorites/{propertyId}', [FavoriteController::class, 'destroy']);

    // Messages
    Route::post('/messages', [MessageController::class, 'store']);
    // Inbox - messages sent to the authenticated user
    Route::get('/messages/inbox', [MessageController::class, 'inbox']);

    // Reply to a specific message
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply']);

    // Mark message as read
    Route::patch('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);


    // Initiate payment
Route::post('/properties/{slug}/initiate-feature-payment', [PropertyFeatureController::class, 'initiatePayment']);

// Callback after payment (redirect from Flutterwave)
Route::get('/properties/feature/callback', [PropertyFeatureController::class, 'handleCallback'])
    ->name('feature.callback');

// Webhook (Flutterwave → your server)
Route::post('/webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handle'])
    ->name('flutterwave.webhook');

  

});

Route::get('/featured-plans', function(){
    $plans = \App\Models\PromotionPackages::where('promotionType', 'featured')->orderBy('packageId')->get()->makeHidden([ 'created_at', 'updated_at', 'deleted_at']);
    return response()->json($plans);
    });
    
    Route::options('{any}', function () {
        return response()->json([], 200);
        })->where('any', '.*');
        
Route::get('/promotion/verify-redirect', [PropertyFeatureController::class, 'handleCallback']);
Route::post('/webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handle']);
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);
