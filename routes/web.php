<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Route;

$router->get('/', function () use ($router) {

    $routeCollection = Route::getRoutes();

    echo '<h2>' . $router->app->version() . '</h2>';
    echo '<h3>Routes</h3>';
    $routes = [];
    foreach ($routeCollection as $key => $value) {
        array_push($routes, $value);
    }
    for ($i = 0; $i < count($routes); $i++) {
        echo $routes[$i]["method"] . " " . $routes[$i]["uri"];
        echo '<br>';
    }

});

/**
 * Unprotected routes
 */

$router->get('/app-check', 'UserController@appCheck');
$router->get('/test-date', 'DeliveryIntervalController@testDate');
$router->get('/test-email', 'SubscriptionController@testEmail');
$router->get('/forget-password', 'UserController@forgetPassword');
$router->get('/test-notification', 'AdminController@sendNoti');
$router->get('/send-reminder-email', 'AdminController@sendReminderEmail');
$router->get('/get-production-pizzas', 'AdminController@getProductionPizzas');
$router->get('/get-home-notice', 'AdminController@getNotice');
$router->put('/edit-notice', 'AdminController@editNotice');

$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('/login', 'UserController@login');
    $router->post('/register', 'UserController@register');
    $router->get('/logout', 'UserController@logout');
    // admin
    $router->post('/admin/login', 'UserController@adminLogin');
});

$router->group(['prefix' => 'admin'], function () use ($router) {
    // admin
    $router->post('/login', 'UserController@adminLogin');
    $router->get('/user/all', 'AdminController@getAllUsers');
    $router->get('/user/stripe/all', 'AdminController@getStripeUsers');
    $router->post('/interval/time', 'AdminController@createInterval');
    $router->delete('/interval/time/{interval_id}', 'AdminController@deleteInterval');
//    $router->put('/edit-notice', ['middleware' => 'auth:api', 'uses' => 'AdminController@editNotice']);
});

$router->group(['prefix' => 'user', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/me', 'UserController@me');
    $router->put('/reset-password', 'UserController@resetUserPassword');
    $router->put('/update', 'UserController@updateUserInfo');
    $router->put('/update-payment-method', 'PaymentController@updatePaymentMethod');
    $router->put('/update-app-info', 'UserController@updateAppInfo');
});

$router->group(['prefix' => 'subscribe'], function () use ($router) {
    $router->post('/', 'SubscriptionController@subscribe');
    $router->get('/', 'SubscriptionController@getUserSubscription');
    $router->post('/create', 'SubscriptionController@createSubscription');
    // the next three routes not yet implemented
    $router->delete('/', 'SubscriptionController@deleteSubscription');
    $router->put('/', 'SubscriptionController@updateSubscription');
    // subscription actions
    $router->put('/change-status', ['middleware' => 'auth:api', 'uses' => 'SubscriptionController@changeSubscriptionStatus']);
    $router->put('/stop', 'SubscriptionController@stopSubscription');
    $router->get('/stop-all', 'SubscriptionController@stopAllSubscriptions');
    $router->put('/pause/{subscription_id}', 'SubscriptionController@pauseSubscription');
    $router->get('/get-stripe-sub', ['middleware' => 'auth:api', 'uses' => 'SubscriptionController@getStripeSubscription']);
    $router->put('/update', ['middleware' => 'auth:api', 'uses' => 'SubscriptionController@updateSubscription']);
});
/**
 * Address routes
 */
$router->group(['prefix' => 'address', 'middleware' => 'auth:api'], function () use ($router) {
    $router->post('/', 'AddressController@createAddress');
    $router->get('/', 'AddressController@getUserAddress');
    $router->delete('/{id}', 'AddressController@deleteAddress');
    $router->put('/{id}', 'AddressController@updateAddress');
});
/**
 * delivery interval routes
 */
$router->group(['prefix' => 'delivery-interval'], function () use ($router) {
    $router->post('/', 'DeliveryIntervalController@createDeliveryInterval');
    $router->get('/', 'DeliveryIntervalController@getAllIntervals');
    $router->delete('/{id}', 'DeliveryIntervalController@deleteInterval');
    $router->put('/{id}', 'DeliveryIntervalController@updateInterval');
});
/**
 * box routes
 */
$router->group(['prefix' => 'box'], function () use ($router) {
    $router->post('/', ['middleware' => 'auth:api', 'uses' => 'BoxController@createBox']);
    $router->get('/', 'BoxController@getBoxes');
    $router->delete('/{id}', ['middleware' => 'auth:api', 'uses' => 'BoxController@deleteBox']);
    $router->put('/{id}', 'BoxController@updateBox');
});
/**
 * product routes
 */
$router->group(['prefix' => 'product'], function () use ($router) {
    $router->post('/', ['middleware' => 'auth:api', 'uses' => 'ProductController@createProduct']);
    $router->get('/', 'ProductController@getProducts');
    $router->delete('/{id}', ['middleware' => 'auth:api', 'uses' => 'ProductController@deleteProduct']);
    $router->put('/{id}', ['middleware' => 'auth:api', 'uses' => 'ProductController@updateProduct']);
    // get product image
    $router->get('/images', 'ProductController@showImage');
});

/**
 * box content routes
 */

$router->group(['prefix' => 'box-content'], function () use ($router) {
    $router->post('/', 'BoxContentController@createBoxContent');
    $router->get('/{subscription_id}', ['middleware' => 'auth:api', 'uses' => 'BoxContentController@getBoxContent']);
    $router->delete('/{id}', ['middleware' => 'auth:api', 'uses' => 'BoxContentController@deleteBoxContent']);
    $router->put('/{id}', ['middleware' => 'auth:api', 'uses' => 'BoxContentController@updateBoxContent']);
});

/**
 * delivery time
 */

$router->group(['prefix' => 'delivery-time'], function () use ($router) {
    $router->post('/', ['middleware' => 'auth:api', 'uses' => 'DeliveryTimeController@create']);
    $router->get('/', 'DeliveryTimeController@get');
    $router->delete('/{id}', ['middleware' => 'auth:api', 'uses' => 'DeliveryTimeController@delete']);
    $router->put('/{id}', ['middleware' => 'auth:api', 'uses' => 'DeliveryTimeController@update']);
});

/**
 * box has product
 */
$router->group(['prefix' => 'box-content-has-product', 'middleware' => 'auth:api'], function () use ($router) {
    $router->post('/', 'BoxContentHasProductController@create');
    $router->get('/', 'BoxContentHasProductController@get');
    $router->delete('/{id}', 'BoxContentHasProductController@delete');
    $router->put('/{id}', 'BoxContentHasProductController@update');
});
