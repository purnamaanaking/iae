<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('/products', ProductController::class);
Route::post('/products/{uuid}/update-stock', [ProductController::class, 'updateStock']);

Route::get('/test', function () {
    return response()->json([
        'message' => 'Hello from Product Service',
    ]);
});

Route::get('/try', function () {
    return response()->json([
        'message' => 'Hello from Product Service (Try)',
    ]);
});
