<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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



Route::get("product-list",[\App\Http\Controllers\ProductsController::class,"index"]);

Route::post("place-order",[\App\Http\Controllers\OrderController::class,'deferredPaymentOrder']);

Route::middleware('auth:sanctum')->group(function (){
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post("product",[\App\Http\Controllers\ProductsController::class,'store']);
    Route::post("product/{product}",[\App\Http\Controllers\ProductsController::class,'update']);
    Route::delete("product/{product}",[\App\Http\Controllers\ProductsController::class,'destroy']);
    Route::get("my-product-list",[\App\Http\Controllers\ProductsController::class,"userIndex"]);
    Route::get("my-orders-list",[\App\Http\Controllers\OrderController::class,"viewMyOrders"]);


});
