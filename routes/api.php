<?php

use App\Http\Controllers\API\LanguageController;
use App\Http\Controllers\API\UserSettingController;
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
Route::post('setting/set-mushaf-id', [App\Http\Controllers\API\UserSettingController::class, 'save_mushaf_id']);

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
Route::get('tafseer', [LanguageController::class, 'getTafseers']);
Route::post('setting/set-tafseer-id', [UserSettingController::class, 'saveTafseerSetting'])->middleware('auth:api');
Route::get('testing-route', [LanguageController::class, 'testingRoute']);

// Stripe payments
Route::post('payments/stripe/create-intent', [App\Http\Controllers\API\StripeController::class, 'createIntent']);
Route::post('payments/stripe/webhook', [App\Http\Controllers\API\StripeController::class, 'webhook']);

// Saved cards and subscriptions (auth required)
Route::middleware('auth:api')->group(function () {
	Route::post('payments/stripe/ensure-customer', [App\Http\Controllers\API\StripeController::class, 'ensureCustomer']);
	Route::post('payments/stripe/create-setup-intent', [App\Http\Controllers\API\StripeController::class, 'createSetupIntent']);
	Route::get('payments/stripe/payment-methods', [App\Http\Controllers\API\StripeController::class, 'listSavedCards']);
	Route::post('payments/stripe/charge-saved', [App\Http\Controllers\API\StripeController::class, 'chargeWithSavedCard']);
	Route::post('payments/stripe/subscribe', [App\Http\Controllers\API\StripeController::class, 'createSubscription']);
});

// PayPal payments
Route::post('payments/paypal/create-order', [App\Http\Controllers\API\PaypalController::class, 'createOrder']);
Route::post('payments/paypal/capture/{orderId}', [App\Http\Controllers\API\PaypalController::class, 'captureOrder']);
Route::post('payments/paypal/webhook', [App\Http\Controllers\API\PaypalController::class, 'webhook']);

Route::middleware('auth:api')->group(function () {
	Route::get('payments/paypal/ensure-payer', [App\Http\Controllers\API\PaypalController::class, 'ensurePayer']);
});

// Flutterwave payments
Route::post('payments/flutterwave/create-payment', [App\Http\Controllers\API\FlutterwaveController::class, 'createPayment']);
Route::post('payments/flutterwave/verify-payment', [App\Http\Controllers\API\FlutterwaveController::class, 'verifyPayment']);
Route::post('payments/flutterwave/webhook', [App\Http\Controllers\API\FlutterwaveController::class, 'webhook']);
Route::get('payments/flutterwave/callback', [App\Http\Controllers\API\FlutterwaveController::class, 'flutterwaveCallback'])
    ->name('flutterwave.callback');
Route::middleware('auth:api')->group(function () {
	Route::post('payments/flutterwave/ensure-customer', [App\Http\Controllers\API\FlutterwaveController::class, 'ensureCustomer']);
	Route::post('payments/flutterwave/save-card', [App\Http\Controllers\API\FlutterwaveController::class, 'saveCard']);
	Route::post('payments/flutterwave/charge-saved-card', [App\Http\Controllers\API\FlutterwaveController::class, 'chargeSavedCard']);
	Route::post('payments/flutterwave/create-subscription', [App\Http\Controllers\API\FlutterwaveController::class, 'createSubscription']);
});

// Donations module - unified payment interface
Route::middleware('auth:api')->group(function () {
	Route::post('donations/initiate', [App\Http\Controllers\API\DonationsController::class, 'initiatePayment']);
	Route::post('donations/one-time', [App\Http\Controllers\API\DonationsController::class, 'processOneTimePayment']);
	Route::post('donations/monthly', [App\Http\Controllers\API\DonationsController::class, 'setupMonthlyDonation']);
	Route::get('donations/history', [App\Http\Controllers\API\DonationsController::class, 'getDonationHistory']);
	Route::post('donations/cancel-monthly', [App\Http\Controllers\API\DonationsController::class, 'cancelMonthlyDonation']);
});


// Xendit Payments
Route::post('/payments/xendit/webhook', [PaymentController::class, 'xenditWebhook']);
Route::prefix('xendit')->group(function () {
    Route::post('create-invoice', [App\Http\Controllers\API\XenditController::class, 'createInvoice']);
    Route::post('webhook', [App\Http\Controllers\API\XenditController::class, 'webhook']);
    Route::get('invoice/{id}', [App\Http\Controllers\API\XenditController::class, 'getInvoice']);
});