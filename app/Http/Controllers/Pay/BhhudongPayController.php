<?php

namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BhhudongPayController extends Controller
{
    private $orderRepository;
    private $_notifyUrl = 'http://api.musky880.com:8080/orderPay/notify'; // 回调地址
    private $_callbackUrl = 'http://api.musky880.com:8080/orderPay/callback'; 
    private $_merchantNo = '1805041539862877';
    private $_secret = '97B35E0BC9DA43558234B59A302CF471'; // 老账号密码
    private $_appId = '18510904';
    //protected $_orderUrl = 'http://lpay-test.bhhudong.com/wap/alipay/gateway'; // 测试下单地址
    //private $_orderUrl = 'http://lpay.bhhudong.com/wap/alipay/gateway'; // 正式下单地址
    private $_orderUrl = 'http://lpay.shijitianhui.com/wap/alipay/gateway';
    
    public function __construct(OrderRepository $orderRepository){
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * 下单
     * @param Request $request
     */
    public function create(Request $request) {
        $params = [
            'merId' => $this->_merchantNo,
            'appId' => $this->_appId,
            'merOrderId' => $request->post('orderCode'),
            'reqFee' => $request->post('amount'),
            'payerId' => $request->post('orderCode'),
            'notifyUrl' => $this->_notifyUrl,
            'itemName' => $request->post('goodsName'),
            'extInfo' => 'order',
        ];
        
        if (empty($params['reqFee']) || empty($params['merId']) ) {
            die(json_encode(['status' => 'F', 'errMsg' => '参数错误', 'errCode' => '9014'], JSON_UNESCAPED_UNICODE));
        }

        ksort($params);
        $params['signValue'] = $this->createSign($params, $this->_secret);
        Log::info('createOrder params:'.json_encode($params));
        $str = http_build_query($params);
        $ret = file_get_contents($this->_orderUrl . "?" . $str);
        Log::info('createOrder ret:'.$ret);
        $ret = json_decode($ret, true);
        // 下单失败
        if ($ret['status'] != 'OK') {
            die(json_encode(['status' => 'F', 'errMsg' => $ret['message'], 'errCode' => $ret['retCode']], JSON_UNESCAPED_UNICODE));
        }
        $result = [
            'status' => 'T',
            'paymentUrl' => isset($ret['data']['payUrl']) ? $ret['data']['payUrl'] : "", 
            'orderNo' => isset($ret['data']['orderNo']) ? $ret['data']['orderNo'] : "", // 我们的订单id
            'errCode' => isset($ret['retCode']) ? $ret['retCode'] : "", // 错误码
            'errMsg' => isset($ret['message']) ? $ret['message'] : "",
        ];

        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    // 回调地址
    public function notify(Request $request) {
        $params = [
            'merId' => $request->post('merId'),
            'appId' => $request->post('appId'),
            'merOrderId' => $request->post('merOrderId'),
            'orderId' => $request->post('orderId'),
            'orderStatus' => $request->post('orderStatus'),
            'amount' => $request->post('amount'),
            'orderTime' => $request->post('orderTime'),
            'extInfo' => $request->post('extInfo'),
        ];
        ksort($params);
        $sign = $this->createSign($params, $this->_secret);
        if ($sign != $request->post('signValue')) {
            Log::info("notify error:".json_encode($params));
            die('fail');
        }
        if ($params['orderStatus'] == '0') {// 支付成功，返回
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['merOrderId']]));
            die('success'); // 处理成功打印大写OK
        }
    }
    
    private function createSign($data, $key) {
        $str = "";
        foreach($data as $k => $v) {
            if (empty($str)) {
                $str = $k."=".$v;
            } else {
                $str .= "&".$k."=".$v;
            }
        }
        
        return strtoupper(md5($str."&key=".$key));

    }

}
