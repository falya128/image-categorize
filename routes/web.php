<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::controller(ImageController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/reset', 'reset');
    Route::post('/upload', 'upload');
    Route::get('/face/{filename}', 'showFace');
});
