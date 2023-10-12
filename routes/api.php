<?php


use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::post('resend-token', [LoginController::class, 'resendToken'])->name('resend-token')->block();
    Route::post('register', [RegisterController::class, 'register'])->name('register')->block();

    Route::prefix('reset-password')->name('reset-password.')->group(function () {
        Route::post('reset', [ResetPasswordController::class, 'reset'])->name('reset')->block();
        Route::post('send-code', [ResetPasswordController::class, 'sendCode'])->name('send-code')->block();
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('auth', [UserController::class, 'auth'])->name('auth');
        Route::post('upload-picture', [UserController::class, 'uploadPicture'])->name('upload-picture')->block();
        Route::post('update', [UserController::class, 'update'])->name('update')->block();
        Route::post('change-password', [UserController::class, 'changePassword'])->name('change-password')->block();
        Route::post('verify-email-with-token', [UserController::class, 'verifyEmailWithToken'])->name('verify-email-with-token');
        Route::post('set-online', [UserController::class, 'setOnline'])->name('set-online');
        Route::post('set-away', [UserController::class, 'setAway'])->name('set-away');
        Route::post('set-offline', [UserController::class, 'setOffline'])->name('set-offline');
    });


    Route::prefix('product')->name('product.')->group(function () {
        Route::get('get', [ProductController::class, 'get'])->name('get');
        Route::post('purchase', [ProductController::class, 'purchase'])->name('purchase')->block();
        Route::post('paginate', [ProductController::class, 'paginate'])->name('paginate');

        Route::prefix('brand')->name('brand.')->group(function () {
            Route::get('all', [ProductController::class, 'getBrands'])->name('all');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::post('paginate', [AdminProductController::class, 'paginate'])->name('paginate');
            Route::post('create', [AdminProductController::class, 'create'])->name('create')->block();
    
            Route::prefix('{product}')->group(function () {
                Route::put('update', [AdminProductController::class, 'update'])->name('update')->block();
                Route::post('upload-thumbnail', [AdminProductController::class, 'uploadThumbnail'])->name('upload-thumbnail')->block();
                Route::delete('delete', [AdminProductController::class, 'delete'])->name('delete')->block();    
            });
        
            Route::prefix('brand')->name('brand.')->group(function () {
                Route::get('all', [AdminProductController::class, 'getBrands'])->name('all');
                Route::post('paginate', [AdminProductController::class, 'brandPaginate'])->name('paginate');
                Route::post('create', [AdminProductController::class, 'createBrand'])->name('create')->block();
    
                Route::prefix('{brand}')->group(function () {
                    Route::delete('delete', [AdminProductController::class, 'deleteBrand'])->name('delete')->block();
                    Route::put('update', [AdminProductController::class, 'updateBrand'])->name('update')->block();
                });
            });
        });        
    });

});
