<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CurrencyController;
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
Route::get('/clear-config', function () {
    $exitCode = Artisan::call('config:cache');
    return 'Application cache cleared';
});
Route::group([

    'middleware' => ['api', 'localization'],
    // 'namespace' => 'App\Http\Controllers',
    'prefix' => 'auth'

], function ($router) {
    // Route::post('/login', [AuthController::class, 'login']);
    Route::post('login', 'App\Http\Controllers\Api\AuthController@login');
    Route::post('register', 'App\Http\Controllers\Api\AuthController@register');
    Route::post('send_otp', 'App\Http\Controllers\Api\ForgotPasswordController@send_otp');
    Route::post('forgot_password', 'App\Http\Controllers\Api\ForgotPasswordController@forgot_password');
    Route::post('verify_otp', 'App\Http\Controllers\Api\ForgotPasswordController@verify_otp');
    Route::post('reset_password', 'App\Http\Controllers\Api\ForgotPasswordController@reset_password');
    Route::post('social_media_login', 'App\Http\Controllers\Api\SocialLoginController@loginWithSocialMedia');

    // /******notification****/
    // Route::get('notification', 'App\Http\Controllers\Api\UserController@notification');
});
Route::group([ 'middleware' => ['jwt.verify', 'localization'] ], function ($router) {
    // Route::post('refresh', 'App\Http\Controllers\Api\AuthController@refresh');
    Route::post('logout', 'App\Http\Controllers\Api\AuthController@logout');
    Route::post('change_password', 'App\Http\Controllers\Api\UserController@change_password');
    Route::get('get_profile', 'App\Http\Controllers\Api\UserController@getProfile');
    Route::post('update_profile', 'App\Http\Controllers\Api\UserController@update_profile');
    Route::post('upload_image', 'App\Http\Controllers\Api\UserController@upload_image');
    Route::post('create_profile', 'App\Http\Controllers\Api\UserController@create_profile');
    Route::get('get_card', 'App\Http\Controllers\Api\CardController@getCard');
    Route::post('add_card', 'App\Http\Controllers\Api\CardController@addCard');
    Route::post('make_card_primary', 'App\Http\Controllers\Api\CardController@makeCardPrimary');
    Route::post('delete_card', 'App\Http\Controllers\Api\CardController@deleteCard');
    Route::post('payment', 'App\Http\Controllers\Api\PaymentController@sendRequestPayment');
    Route::post('add_phone_number', 'App\Http\Controllers\Api\UserController@add_phone_number');
    Route::post('create_password', 'App\Http\Controllers\Api\UserController@create_password');

    Route::post('transaction/create', [TransactionController::class, 'make_transaction']);
    Route::get('transactions', [TransactionController::class, 'list_transactions']);
    Route::post('notifications', [TransactionController::class, 'list_notifications']);
    Route::post('support/email', [SupportController::class, 'support_form_submit']);
    Route::post('users/search_by_phone', [UserController::class, 'search_users_by_phone']);
    Route::post('users/search_by_multiple_phones', [UserController::class, 'multiSearchByPhone']);
    Route::get('users', [UserController::class, 'get_registered_users']);
    Route::get('notifications/read', [TransactionController::class, 'read_notifications']);
    Route::post('setings/notifications', [UserController::class, 'notification_setting']);
});


Route::get('currency_conversion/update', [CurrencyController::class, 'updateConversionRate']);
Route::get('currency_conversion/get', [CurrencyController::class, 'getCurrencyConversion']);