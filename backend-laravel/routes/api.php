<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RouteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::get('/login', function() {
    return response([
        'status' => false,
        'message' => "Unautenticated"
    ], 200);
})->name('login');

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::get('/user', [UserController::class, 'user_info']);
    Route::group(['prefix' => 'organization', 'middleware' => 'role:organization'], function() {
        Route::apiResource('routes', RouteController::class);
    });
    Route::post('/logout', [UserController::class, 'logout']);
});
