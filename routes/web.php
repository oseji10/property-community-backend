<?php

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/property_images/{filename}', function ($filename) {
    $path = storage_path('app/public/property_images/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});