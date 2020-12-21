<?php

use Illuminate\Support\Facades\Route;

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

/*
Route::get('/', [
    'as' => 'index', 'uses' => 'App\Http\Controllers\HomeController@index',
]);
*/
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])
    ->name('home.index');
Route::post('/', [App\Http\Controllers\HomeController::class, 'process'])
    ->name('home.process');
