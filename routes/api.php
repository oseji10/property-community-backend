<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BeneficiariesController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\LgaController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MinistryController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\JAMBController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseTypeController;
use App\Http\Controllers\LevelController;

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
    Route::post('/auth/signup', [AuthController::class, 'signup']);
    Route::post('/auth/signin', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/users/profile', [AuthController::class, 'profile'])->middleware('auth.jwt'); // Use auth.jwt instead of auth:api

    Route::post('/auth/resend-otp', [OtpController::class, 'resendOtp']);
    Route::post('/auth/verify-otp', [OtpController::class, 'verifyOtp']);
    Route::post('/auth/setup-password', [AuthController::class, 'setupPassword']);


    Route::get('/roles', [RolesController::class, 'user_roles']);

    Route::get('/announcement', [AnnouncementController::class, 'index']);
   
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

        // Application routes
    Route::get('/users/admins', [UsersController::class, 'admins']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::get('/users/admin_roles', [RolesController::class, 'admin_roles']);
    Route::delete('/users/{userId}/delete', [UsersController::class, 'destroy']);

    Route::post('/jamb/upload', [JAMBController::class, 'upload']);
    Route::delete('/jamb/{jambId}', [JAMBController::class, 'destroy']);
    Route::get('/jamb/search', [JambController::class, 'search']);
       
    Route::post('/apply', [ApplicationController::class, 'apply']);
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/application/status/{email}', [ApplicationController::class, 'status']);
    Route::get('/my-slips', [ApplicationController::class, 'mySlips']);
    Route::get('/application/status', [ApplicationController::class, 'applicationStatus']);
    Route::put('/application/{applicationId}/change-batch', [ApplicationController::class, 'changeBatch']);
    Route::get('/attendance', [ApplicationController::class, 'attendance']);
    // Route::post('/attendance/print', [ApplicationController::class, 'printAttendance'])->name('attendance.print');

    Route::get('/admission-status', [AdmissionsController::class, 'status']);
     Route::get('/admission-letter/{applicationId}', [AdmissionsController::class, 'showAdmissionLetter'])->name('admission.letter');
     Route::get('/admissions/{applicationId}/letter', [AdmissionsController::class, 'adminDownloadAdmissionLetter']);
     Route::get('/admissions', [AdmissionsController::class, 'index']);
     Route::post('/admissions/bulk-upload', [AdmissionsController::class, 'bulkUpload']);
     Route::get('/admission/programmes', [AdmissionsController::class, 'programmes']);
     Route::get('/admission/sessions', [AdmissionsController::class, 'sessions']);
     Route::get('admission/status', [AdmissionsController::class, 'admissionStatus']);

    Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment'])->middleware('auth.jwt');
    Route::post('/payment/verify', [PaymentController::class, 'verify'])->middleware('auth.jwt');
    Route::get('/my-payments', [PaymentController::class, 'myPayments']);
    Route::get('/payments', [PaymentController::class, 'index']);
    
    Route::get('/all-batches', [BatchController::class, 'batches']);
    Route::get('/batches', [BatchController::class, 'index']);
    Route::post('/batches', [BatchController::class, 'store']);
    Route::put('/batches/{batchId}', [BatchController::class, 'update']);
    Route::delete('/batches/{batchId}', [BatchController::class, 'destroy']);
    Route::get('/rebatched', [ApplicationController::class, 'rebatched_candidates']);
    Route::patch('/batches/{batchId}/verification', [BatchController::class, 'changeStatus']);
    
     Route::get('/halls', [HallController::class, 'index']);
     Route::get('/all-halls', [HallController::class, 'halls']);
    Route::post('/halls', [HallController::class, 'store']);
    Route::put('/halls/{hallId}', [HallController::class, 'update']);
    Route::patch('/halls/{hallId}/status', [HallController::class, 'changeStatus']);
    Route::delete('/halls/{hallId}', [HallController::class, 'destroy']);

    Route::get('/batched-applicants', function () {
        return \App\Models\BatchAssignment::with('applicants')->latest()->paginate(20);
    });
    
    Route::get('/verify/slip', function () {
        return "This is authentic!";
    })->name('verify.slip');
    
    Route::get('/all-payments', [PaymentController::class, 'all_payments']);
    Route::get('/analytics', [ApplicationController::class, 'analytics']);
    Route::get('/payments/recent', [PaymentController::class, 'recentPayments']);

    Route::get('/applicant/verify/{identifier}', [VerificationController::class, 'verifyCandidate']);
    Route::post('/applicant/mark-present', [VerificationController::class, 'markPresent']);

    Route::post('/attendance/print', [ApplicationController::class, 'printAttendance'])->name('attendance.print');
Route::get('/candidates_states', [ApplicationController::class, 'candidates_states']);
Route::get('/candidates_gender', [ApplicationController::class, 'candidates_gender']);
Route::get('/batched_candidates', [ApplicationController::class, 'batched_candidates']);
Route::get('/verified_candidates', [ApplicationController::class, 'verified_candidates']);

Route::get('programmes', [ProgrammeController::class, 'index']);
Route::post('programmes', [ProgrammeController::class, 'store']);
Route::put('programmes/{programmeId}', [ProgrammeController::class, 'update']);
Route::delete('programmes/{programmeId}', [ProgrammeController::class, 'destroy']);

Route::get('sessions', [SessionController::class, 'index']);
Route::post('sessions', [SessionController::class, 'store']);
Route::put('sessions/{sessionId}', [SessionController::class, 'update']);
Route::delete('sessions/{sessionId}', [SessionController::class, 'destroy']);
Route::get('sessions/active', [SessionController::class, 'activeSession']);

Route::get('semesters', [SemesterController::class, 'index']);
Route::post('semesters', [SemesterController::class, 'store']);
Route::put('semesters/{semesterId}', [SemesterController::class, 'update']);
Route::delete('semesters/{semesterId}', [SemesterController::class, 'destroy']);
Route::get('semesters/active', [SemesterController::class, 'activeSemesters']);

Route::get('course-types', [CourseTypeController::class, 'index']);
Route::post('course-types', [CourseTypeController::class, 'store']);
Route::put('course-types/{courseId}', [CourseTypeController::class, 'update']);
Route::delete('course-types/{courseId}', [CourseTypeController::class, 'destroy']);
Route::get('courses/available', [CourseController::class, 'availableCourses']);


Route::get('courses', [CourseController::class, 'index']);
Route::post('courses', [CourseController::class, 'store']);
Route::put('courses/{courseId}', [CourseController::class, 'update']);
Route::delete('courses/{courseId}', [CourseController::class, 'destroy']);

Route::get('levels', [LevelController::class, 'index']);
Route::post('levels', [LevelController::class, 'store']);
Route::put('levels/{levelId}', [LevelController::class, 'update']);
Route::delete('levels/{levelId}', [LevelController::class, 'destroy']);
Route::get('levels/{level}', [LevelController::class, 'currentLevel']);


});

Route::get('/application/slip/{applicationId}', [PDFController::class, 'generateExamSlip']);
Route::get('analytics/total-users', [AnalyticsController::class, 'getTotalBeneficiaries']);

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');
