<?php
/**
 * 高付通 - xiaoqiuan
 */

namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GftPayController extends Controller
{
    private $orderRepository;
    private $_notifyUrl = 'http://api.musky880.com:8080/orderPay/notify'; // 回调地址
    private $_callbackUrl = 'http://api.musky880.com:8080/orderPay/callback'; // 不知啥用
    private $_merchantNo = '32413110017678'; // 老账号
    private $_secret = 'rx465tvkayrmvmtfvkemlfvwopwhldlq'; // 老账号密码
    private $_orderUrl = 'https://pay.166985.com/wappay/payapi/order'; // 高付通下单地址
    
    public function __construct(OrderRepository $orderRepository){
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * 下单
     * @param Request $request
     */
    public function create(Request $request) {
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
        Log::info('createOrder params:'.json_encode($params));
        if (empty($params['orderAmount']) || empty($params['orderNo']) || empty($params['payType']) ) {
            die(json_encode(['status' => 'F', 'errMsg' => '参数错误', 'errCode' => '9014'], JSON_UNESCAPED_UNICODE));
        }

        // 支付方式转义
        switch ($params['payType']) {
            case 'xiaoqian_wx':
            case 'wx':
                $params['payType'] = '3';
                break;
            case 'xiaoqian_qq':
            case 'qq':
                $params['payType'] = '7';
                break;
            case 'xiaoqian_union':
            case 'union':
                $params['payType'] = '9';
                break;
            case 'xiaoqian_alipay':
            case 'alipay':    
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

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $this->_orderUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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
        // 校验支付是否成功
        if ($sign != $request->post('sign') || $params['merchantNo'] != $this->_merchantNo) {
            Log::info("notify error:".json_encode($params));
            die('error');
        }
        if ($params['orderStatus'] == 'SUCCESS') {// 支付成功，返回
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['orderNo']]));
            die('OK'); // 处理成功打印大写OK
        }
    }
    
    /**
     * 小钱微信支付，因为上家无法解决取消订单会跳到成功付款页，所以返回空白
     * @param Request $request
     */
    public function callback(Request $request) {}

}
