<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\LoginRegisterController;

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

// Public routes of authtication
Route::controller(LoginRegisterController::class)->group(function() {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/googlelogin', 'googlelogin');
    Route::post('/changePassword', 'changePassword');
});

Route::get('/appMeta', [ProductController::class, 'appMeta']);

// Public routes of product
Route::controller(ProductController::class)->group(function() {
    Route::get('/products', 'index');
    Route::get('/products/{id}', 'show');
    Route::get('/products/search/{name}', 'search');
});

// Protected routes of product and logout
Route::middleware('auth:sanctum')->group( function () {
    Route::post('/logout', [LoginRegisterController::class, 'logout']);
    Route::post('/editProfile', [ProductController::class, 'editProfile']);
    Route::get('/getUserProfile', [ProductController::class, 'getUserProfile']);
    Route::post('/totalSummary', [ProductController::class, 'totalSummary']);
    Route::post('/clockIn', [ProductController::class, 'attendance']);
    Route::post('/clockOut', [ProductController::class, 'clockOut']);
    Route::get('/attendanceStatus', [ProductController::class, 'getCurrentDayAttendance']);
    Route::post('/tasklist', [ProductController::class, 'tasklist']);
    Route::post('/createtask', [ProductController::class, 'createtask']);
    Route::post('/getTaskDetails', [ProductController::class, 'getTaskDetails']);
    Route::get('/attendance/view', [ProductController::class, 'viewAttendance']);
    Route::get('/getUserBranch', [ProductController::class, 'branchDetail']);
    Route::get('/getLeaves', [ProductController::class, 'getLeaves']);
    Route::get('/deleteLeave', [ProductController::class, 'deleteLeave']);
    Route::get('/getLeavesHistory', [ProductController::class, 'getLeavesHistory']); // Added for leave history
    Route::post('/createLeave', [ProductController::class, 'createLeave']);
    Route::post('/userDetail', [ProductController::class, 'userDetail']);
    Route::post('/regionDetail', [ProductController::class, 'regionDetail']);
    Route::post('/branchDetail', [ProductController::class, 'branchDetailByID']);
    Route::post('/TaskStatusChange', [ProductController::class, 'TaskStatusChange']);
    Route::get('/LeaveTypeDetail', [ProductController::class, 'LeaveTypeDetail']);
    Route::get('/deleteAttendance', [ProductController::class, 'deleteAttendanceRecord']);


    Route::controller(ProductController::class)->group(function() {
        Route::post('/products', 'store');
        Route::post('/products/{id}', 'update');
        Route::delete('/products/{id}', 'destroy');
    });
});