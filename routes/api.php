<?php

use Illuminate\Http\Request;

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

Route::group(['prefix' => 'v1'], function() {
    Route::group(['middleware' => ['api']], function() {
        Route::get('/server_api/task_info','ServerApiController@getTaskInfo');
        Route::get('/server_api/game_list','ServerApiController@getGameListInfo');

        //邮件
        Route::post('/server_api/get_mail_list','ServerApiController@getMailList');
        Route::post('/server_api/send_mail','ServerApiController@sendMail');
        Route::post('/server_api/modify_mail','ServerApiController@modifyMail');
        Route::post('/server_api/receive_mail','ServerApiController@receiveMail');
        Route::post('/server_api/req_read_mail','ServerApiController@reqReadMail');

        Route::post('/login','AuthController@login');
        Route::get('/refresh/{token}','AuthController@refresh');
        Route::get('/agent/check/{agentId}','AgentController@checkAgent');
        Route::post('/agent/isagent','AgentController@isAgent');
        Route::post('/auth/check','AgentController@checkAuth');
        Route::get('/agent/info/{token}','AgentController@getAgentInfo');
        Route::get('/agent/resultAtToday/{token}','AgentController@getResultAtToday');
        Route::post('/agent/profile/info/{token}','AgentController@modifyAgentProfile');
        Route::post('/agent/profile/withdraw/bank/{token}','AgentController@modifyWithdrawCredit');
        Route::post('/agent/profile/withdraw/alipay/{token}','AgentController@modifyWithdrawAlipay');
        Route::post('/agent/profile/withdraw/wechat/{token}','AgentController@modifyWithdrawWechat');
        Route::post('/agent/withdraw/{token}','WithdrawController@withdraw');
        Route::any('/agent/callback','WithdrawController@callBackUrl');
        Route::post('/agent/withdrawcoin/{token}','WithdrawController@withdrawCoin');
        Route::get('/agent/withdraw/list/{token}/{offset}/{count}','WithdrawController@getWithdrawRecord');
        Route::get('/agent/withdrawcoin/list/{token}/{offset}/{count}','WithdrawController@getWithdrawCoinRecord');
        Route::get('/agent/subordinate/count/{token}','SubordinateController@getSubordinateCount');
        Route::get('/agent/subordinate/user/{token}/{offset}/{count}','SubordinateController@getSubordinateUser');
        Route::get('/agent/subordinate/agent/{token}/{offset}/{count}','SubordinateController@getSubordinateAgent');
        Route::get('/agent/subordinate/today/{token}/{offset}/{count}','SubordinateController@getSubordinateUserAtToday');
        Route::get('/agent/subordinate/roomcard/{userId}','SubordinateController@getSubordinateRoomCard');
        Route::get('/agent/commission/query/{token}','CommissionController@queryCommission');
        Route::post('/agent/commission','CommissionController@addCommission');
        Route::post('/agent/modifypassword','AgentController@modifyAgentPassword');
        Route::post('/agent/exchange/{token}','AgentController@exchangeRoomCard');
        Route::get('/image/true/{token}','AuthController@trunImageHead');

        Route::get('/image/true/{token}','AuthController@trunImageHead');

        Route::post('/onebyone/post_form','OneByOneController@postForm');
        Route::get('/onebyone/create','OneByOneController@create');
        Route::post('/onebyone/store','OneByOneController@store');

        //绑定支付宝/微信
        Route::any('/onebyone/user_info','OneByOneController@getBingdingInfo');

        Route::post('/onebyone/bingding','OneByOneController@bingding');
        Route::post('/onebyone/withdraw','OneByOneController@withdrawOrder');
        Route::post('/onebyone/send_message','OneByOneController@sendMessage');
        Route::post('/onebyone/get_message','OneByOneController@getMessage');

        //代理相关
        Route::post('/onebyoneagents/is_agent','OneByOneAgentsController@isAgent');
        Route::post('/onebyoneagents/bingding','OneByOneAgentsController@bingding');
      
        Route::post('/onebyone/getwxlist','GetWxlistController@getlist');//获取wxlist和paylist
        Route::post('/app/getgateway','GetGatewayController@getlist');//获取网关列表
        Route::get('/app/tips','GetcfglistController@getTips');//公告
        Route::post('/app/advert','GetcfglistController@getAdvert');

        //代理二维码链接绑定
        Route::get('onebyone/get_bind_agent','OneByOneAgentsController@getBindAgentIp');
        Route::post('onebyoneagents/bind_agent','OneByOneAgentsController@bindAgent');
    });
});
