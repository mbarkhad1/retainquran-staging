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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/checkUser', [App\Http\Controllers\API\CheckActiveUserController::class, 'check_user']);
Route::post('/logout', [App\Http\Controllers\API\LoginController::class, 'logout_user']);
Route::post('/register', [App\Http\Controllers\API\RegisterController::class, 'createUser'])->name('register');
Route::post('/login', [App\Http\Controllers\API\LoginController::class, 'login'])->name('login');
Route::post('/social-login', [App\Http\Controllers\API\LoginController::class, 'socialLogin'])->name('social-login');

Route::post('password/email', [App\Http\Controllers\API\ForgotPasswordController::class, 'sendResetLinkResponse']);
Route::post('password/reset', [App\Http\Controllers\API\ResetPasswordController::class, 'sendResetResponse']);
Route::post('password/update', [App\Http\Controllers\API\UpdatePasswordController::class, 'sendUpdateResponse']);

Route::get('surah/list', [App\Http\Controllers\API\SurahController::class, 'getAllSurah']);

Route::get('language/list', [App\Http\Controllers\API\LanguageController::class, 'getAllLangauges']);

Route::post('card/create', [App\Http\Controllers\API\CardController::class, 'create_cards']);
Route::post('card/view', [App\Http\Controllers\API\CardController::class, 'view_cards']);
Route::post('card/DeckView', [App\Http\Controllers\API\CardController::class, 'deck_view']);
Route::post('card/update', [App\Http\Controllers\API\CardController::class, 'update_card']);
Route::post('card/getAllCards', [App\Http\Controllers\API\CardController::class, 'review_all_cards']);
Route::post('card/getUserHistory', [App\Http\Controllers\API\CardController::class, 'get_all_history']);
Route::get('test-user-history', [App\Http\Controllers\API\CardController::class, 'TestGetAllHistory']);
Route::post('card/delAllCards', [App\Http\Controllers\API\CardController::class, 'delete_all_cards']);
Route::post('card/delSurahCards', [App\Http\Controllers\API\CardController::class, 'delete_surah_cards']);
Route::post('card/getCardsWithSurah', [App\Http\Controllers\API\CardController::class, 'get_cards_with_surah']);
Route::post('card/getCardsWithDeck', [App\Http\Controllers\API\CardController::class, 'get_cards_with_deck']);
Route::post('OfflineData', [App\Http\Controllers\API\OfflineFeatureController::class, 'insert_offline_data']);
Route::get('ProcessOfflineData', [App\Http\Controllers\API\OfflineFeatureController::class, 'processLogRequests']);

Route::post('translators/getTranslators', [App\Http\Controllers\API\TranslatorController::class, 'get_translator_by_language']);
Route::get('translators/getAllTranslators', [App\Http\Controllers\API\TranslatorController::class, 'get_list_translators']);


Route::get('translation/GetListTranslation', [App\Http\Controllers\API\TranslationController::class, 'get_list_translations']);
Route::get('/getspace', [App\Http\Controllers\API\TranslationController::class, 'get_disk_storage']);
Route::post('translation/GetTranslation', [App\Http\Controllers\API\TranslationController::class, 'get_translation']);
Route::post('translation/GetTranslationBySurah', [App\Http\Controllers\API\TranslationController::class, 'get_translation_by_surah']);
Route::post('translation/GetTranslationByTranslator', [App\Http\Controllers\API\TranslationController::class, 'get_list_by_translator']);

Route::post('reciters/GetReciterBySurah', [App\Http\Controllers\API\ReciterController::class, 'get_reciter_by_surah']);
Route::post('reciters/GetReciterByCountry', [App\Http\Controllers\API\ReciterController::class, 'get_all_reciters_by_country']);
Route::get('reciters/GetAllReciters', [App\Http\Controllers\API\ReciterController::class, 'get_all_reciters']);

Route::post('statistics/getStats', [App\Http\Controllers\API\StatsController::class, 'get_stats']);
Route::post('statistics/getHeatMap', [App\Http\Controllers\API\StatsController::class, 'get_heat_map']);
Route::post('statistics/getForecast', [App\Http\Controllers\API\StatsController::class, 'forecast_graph']);

Route::post('setting/DefaultReciterUpdate', [App\Http\Controllers\API\UserSettingController::class, 'update_default_reciter']);
Route::post('setting/DefaultNotificationUpdate', [App\Http\Controllers\API\UserSettingController::class, 'update_default_notification']);
Route::post('setting/DefaultViewUpdate', [App\Http\Controllers\API\UserSettingController::class, 'update_default_view']);
Route::post('setting/DefaultTranslatorUpdate', [App\Http\Controllers\API\UserSettingController::class, 'update_default_translator']);
Route::post('setting/GetSettings', [App\Http\Controllers\API\UserSettingController::class, 'get_settings']);

Route::post('statistics/getColors', [App\Http\Controllers\API\StatsController::class, 'get_colors']);
Route::get('card/get-weekly-stats', [App\Http\Controllers\API\StatsController::class, 'GetWeeklyStats']);
Route::post('card/delUserCards', [App\Http\Controllers\API\CardController::class, 'delete_user_cards']);
Route::post('card/delUserHistory', [App\Http\Controllers\API\CardController::class, 'delete_user_history']);
Route::post('delete-user', [App\Http\Controllers\API\CardController::class, 'DeleteUser']);

Route::post('get-audio-size-by-reciter', [App\Http\Controllers\API\ReciterController::class, 'GetAudioSizeByReciter']);

// RECENT PAGE ROUTES
Route::post('last-view', [App\Http\Controllers\API\LastViewController::class, 'createLastView']);
Route::get('last-view', [App\Http\Controllers\API\LastViewController::class, 'getLastView']);

// Test Route update content after every code push to verify the push has made it to the server
Route::get('testing-route', function () {
    return 'v1';
});
