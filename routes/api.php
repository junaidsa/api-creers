<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UtilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    return response()->json(['message' => 'All caches cleared successfully!']);
});
Route::group(['middleware' => 'guest'], function () {
    Route::get('/uploads/profile_image/thum/{filename}', function ($filename) {
        $path = base_path("../uploads/profile_image/thum/" . $filename);

        if (!File::exists($path)) {
            abort(404);
        }

        return response()->file($path);
    });
Route::post('/authenticated', [AuthenticationController::class, 'sendOtp']);
Route::post('/verify/otp', [AuthenticationController::class, 'verifyOtp']);
Route::get('/show/otp', [AuthenticationController::class, 'show_opt']);
Route::get('/languages', [UtilityController::class, 'getLanguages']);
});
Route::middleware('auth:sanctum')->group(function () {
    // Chat api routes
    Route::post('/chats/{jobId}', [ChatController::class, 'startChat']);
    Route::get('/messages/list/{id}', [ChatController::class, 'getMessages']);
    Route::get('/contact/list', [ChatController::class, 'getContacts']);
    Route::post('/message/send', [ChatController::class, 'sendMessage']);
    // Chat api routes end
    Route::post('/view/profile/{id}', [UserController::class, 'showProfile']);
    Route::get('/category/list', [UtilityController::class, 'getCategories']);
    Route::post('/profile/image', [UserController::class, 'updateProfilepic']);
    Route::post('/update/profile', [UserController::class, 'updateProfile']);
    Route::post('/job/store', [JobController::class, 'store']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::post('/job/applied', [EmployeeController::class, 'applyJob']);
    Route::get('/jobs', [JobController::class, 'getJob']);
    Route::get('/myjob', [JobController::class, 'myJob']);
    Route::get('/job/details', [JobController::class, 'jobDetails']);
    Route::post('/fillter/jobs', [JobController::class, 'filterJobs']);
    Route::post('/close/job/{id}', [JobController::class, 'updateStatus']);
    Route::get('/job/applicant', [JobController::class, 'applicantList']);
    Route::get('/recommended/applicant', [JobController::class, 'recommendedApplicants']);
    Route::get('/subscriptions', [UtilityController::class, 'getsubscriptions']);
    Route::post('/invite/create', [JobController::class, 'createInvite']);
    Route::get('/invite/list', [JobController::class, 'getUserInvites']);
    Route::post('/invite/status/{id}', [JobController::class, 'updateInviteStatus']);
    Route::post('/invite/status/{id}', [JobController::class, 'updateInviteStatus']);
});
