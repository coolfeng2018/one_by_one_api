<?php

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

Route::post('/applyagent/submit', 'AgentApplyController@agentApply');
Route::get('/applyagent','AgentApplyController@index');
Route::get('/share/callback','ShareController@shareCallback');
Route::get('/share/{agentId}/{unionId}','ShareController@share');
Route::get('/share/{agentId}','ShareController@share');


Route::post('/order/create','OrderController@createOrder'); // 高付通
Route::get('/order/create','OrderController@createOrder'); // 高付通
Route::post('/order/notify','OrderController@notify');
Route::get('/order/notify','OrderController@notify');
Route::get('/order/test','OrderController@test');
Route::post('/api/v1/app/update','AppController@getVersions');
Route::get('/api/v1/app/update','AppController@getVersions');
Route::post('/order/callback','OrderController@callback');
Route::get('/order/callback','OrderController@callback');
Route::get('/api/v1/server_api/task_info','ServerApiController@getTaskInfo');
Route::get('/api/v1/server_api/game_list','ServerApiController@getGameListInfo');
Route::post('/api/v1/app/updatev2','AppController@getVersionsV2');
Route::get('/api/v1/app/updatev2','AppController@getVersionsV2');

// 支付第二版
Route::get('/orderPay/create','Pay\BaseController@create'); 
Route::post('/orderPay/create','Pay\BaseController@create'); 
Route::get('/orderPay/callback','Pay\BaseController@callback');
Route::get('/orderPay/notify','Pay\BaseController@notify');
Route::post('/orderPay/create','Pay\BaseController@create'); 
Route::post('/orderPay/callback','Pay\BaseController@callback');
Route::post('/orderPay/notify','Pay\BaseController@notify');

Route::get('/orderPay/createWsf','Pay\WsfPayController@setOrder');
Route::post('/orderPay/createWsf','Pay\WsfPayController@setOrder');
Route::get('/orderPay/createUadd','Pay\UaddPayController@setOrder');
Route::post('/orderPay/createUadd','Pay\UaddPayController@setOrder');
Route::get('/orderPay/createWsfnew','Pay\WsfnewPayController@setOrder');
Route::post('/orderPay/createWsfnew','Pay\WsfnewPayController@setOrder');
