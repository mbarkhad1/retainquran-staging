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

Route::get('/', function () {
    return view('welcome');
});

Route::get('cache-clear', function () {

    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('config:cache');
    return '<h1>Cache facade value cleared</h1>';
});

Route::get('schedule-list', function () {

    $exitCode = Artisan::call('process:logs');
    return $exitCode;
});
/*Route::get('cache-clear', function () {

    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    return '<h1>Cache facade value cleared</h1>';
});
*/
// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// Route::get('/auth/redirect', [App\Http\Controllers\API\LoginController::class, 'googleRedirect']);
// Route::get('/google-callback', [App\Http\Controllers\API\LoginController::class, 'googleLogin'])->name('google-callback');
Route::get('dailyData', [App\Http\Controllers\API\StatsController::class, 'daily_data']);

Route::get('please-login', function () {

    $response['response'] = "please login first";
    $response['result'] = 'failed';

    return json_encode($response);
})->name('please-login');

Route::post('card/getCardsWithSurah', [App\Http\Controllers\API\CardController::class, 'get_cards_with_surah']);
