<?php

namespace App\Http\Controllers;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Pay\BhhudongPayController;
use App\Http\Controllers\Pay\NumberPayController;

class OrderController extends Controller
{
    private $orderRepository;
    private $_notifyUrl = 'http://api.musky880.com:8080/order/notify'; // 回调地址
    private $_callbackUrl = 'http://api.musky880.com:8080/order/callback'; // 不知啥用
    //private $_merchantNo = '32413110017699';
    private $_merchantNo = '32413110017678'; // 老账号
    //private $_secret = 'j36vrxfg5wawl1unukgjjhhfkji3a4hi';
    private $_secret = 'rx465tvkayrmvmtfvkemlfvwopwhldlq'; // 老账号密码
    private $_orderUrl = 'https://pay.166985.com/wappay/payapi/order'; // 高付通下单地址
    
    public function __construct(OrderRepository $orderRepository, Request $request){
        $this->orderRepository = $orderRepository;
        $methods = $_REQUEST['_url'];
        $list = explode('/', $methods);
        $method = $list[2];
        
//        if ($method == 'notify') {
//            if (isset($_REQUEST['merId']) && $_REQUEST['merId'] == '1805041539862877') {
//                $order = new BhhudongPayController($orderRepository);
//                $order->$method($request);
//                die;
//            }
//        }
        if ($request->way == 'xiaoqian_wx' || $request->get('sp_billno')) {
            $order = new NumberPayController($orderRepository);
            $order->$method($request);
            die;
        }
    }
    
    /**
     * 下单
     * @param Request $request
     */
    public function createOrder(Request $request) {
        $params = [
            'merchantNo' => $this->_merchantNo,
            'orderAmount' => $request->post('amount'),
            'orderNo' => $request->post('orderCode'),
            'notifyUrl' => $this->_notifyUrl,
            'callbackUrl' => $this->_callbackUrl,
            'payType' => $request->post('way'), // 支付方式3微信h5, 7 QQ钱包Wap支付， 9 银联二维码支付
            'productName' => $request->post('goodsName'),
            'mchAppId' => 'psz'.$request->post('channel'),
            'mchAppName' => 'game-psz'.$request->post('channel'),
            'deviceType' => $request->post('dev_channel'),
        ];

        if (empty($params['orderAmount']) || empty($params['orderNo']) || empty($params['deviceType']) || empty($params['payType']) ) {
            die(json_encode(['status' => 'F', 'errMsg' => '参数错误', 'errCode' => '9014'], JSON_UNESCAPED_UNICODE));
        }

        // 支付方式转义
        switch ($params['payType']) {
            case 'xiaoqian_wx':
                $params['payType'] = '3';
                break;
            case 'xiaoqian_qq':
                $params['payType'] = '7';
                break;
            case 'xiaoqian_union':
                $params['payType'] = '9';
                break;
            case 'xiaoqian_alipay':
                $params['payType'] = '13';
                break;
            default:
                die(json_encode(['status' => 'F', 'errMsg' => '未知支付方式', 'errCode' => '5021'], JSON_UNESCAPED_UNICODE));
                break;
        }
        
        foreach ($params as $k => $v) {
            if ($v == "") {
                unset($params[$k]);
            }
        }
        ksort($params);
        $params['sign'] = $this->orderRepository->createSign($params, $this->_secret);
        Log::info('createOrder params:'.json_encode($params)); 
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $this->_orderUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); 
        $ret = curl_exec($ch);//运行curl
        curl_close($ch);
        Log::info('createOrder ret:'.json_encode($ret)); 
        $ret = json_decode($ret, true);
        if (empty($ret) || !isset($ret['status'])) {
            die(json_encode(['status' => 'F', 'errMsg' => '下单失败', 'errCode' => '5020'], JSON_UNESCAPED_UNICODE));
        }
        
        $result = [
            'status' => $ret['status'],
            'paymentUrl' => isset($ret['payUrl']) ? $ret['payUrl'] : "", 
            'orderNo' => isset($ret['orderNo']) ? $ret['orderNo'] : "", // 我们的订单id
            'errCode' => isset($ret['errCode']) ? $ret['errCode'] : "", // 错误码
            'errMsg' => isset($ret['errMsg']) ? $ret['errMsg'] : "",
        ];
        //Redis::select(5);
        //Redis::lpush('orderPayment', json_encode(['orderCode' => $ret['orderNo']]));
        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    // 回调地址
    public function notify(Request $request) {
        $params = [
            'merchantNo' => $request->post('merchantNo'),
            'orderAmount' => $request->post('orderAmount'),
            'orderNo' => $request->post('orderNo'),
            'wtfOrderNo' => $request->post('wtfOrderNo'),
            'orderStatus' => $request->post('orderStatus'),
            'payTime' => $request->post('payTime'),
            'productName' => $request->post('productName'),
            'productDesc' => $request->post('productDesc'),
            'remark' => $request->post('remark'),
            //'sign' => $request->post('sign'),
        ];
        
        Log::info("notify params:".json_encode($_REQUEST));
        $sign = $this->orderRepository->createSign($params, $this->_secret);
        if ($sign != $request->post('sign')) {
            Log::info("notify error:".json_encode($params));
            die('error');
        }
        if ($params['orderStatus'] == 'SUCCESS') {// 支付成功，返回
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['orderNo']]));
            die('OK'); // 处理成功打印大写OK
        }
    }

    public function callback(Request $request) {
        Log::info("callback params:".json_encode($_REQUEST));
        echo '<html><body> 
<style>

div {  
 position: absolute;
 left: 50%;
 top: 50%;
width:200px;
height:100px;
margin-left:-100px;
margin-top:-50px;
font-size:50px;
}  

</style>

 
<div id="wrapper">  
</div>  
</body></html>';
    }
    
}
