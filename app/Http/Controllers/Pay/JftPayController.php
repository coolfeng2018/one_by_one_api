<?php
/**
 * 金付通
 */
namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class JftPayController extends Controller
{
    private $orderRepository;
    private $_mch_id = '201805231115081';
    private $_secret = 'f978fe6aa5d845fcafc463b30b4b8d5e'; // 商户号
    private $_orderUrl = 'https://pay.echase.cn/jygatewayPn/api'; // 下单地址
    protected $_notifyUrl = 'http://api.musky880.com:8080/orderPay/notify'; // 回调地址
    protected $_callbackUrl = 'http://api.musky880.com:8080/orderPay/callback'; // 支付成功跳转页面
    
    public function __construct(OrderRepository $orderRepository){
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * 下单
     * @param Request $request
     */
    public function create(Request $request) {
        $params = [
            'service' => 'create',
            'trade_type' => 'pay.weixin.h5',
            'mch_id' => strval($this->_mch_id),
            'nonce_str' => strval(time()), // 随机字符
            'body' => $request->goodsName,
            'out_trade_no' => strval($request->orderCode), // 订单
            'total_fee' => strval($request->amount),
            'mch_create_ip' => getIp(),
            //'callback_url' => $this->_callbackUrl, // 回访地址
            'notify_url' => $this->_notifyUrl, // 支付成功发货地址
            'scene_info' => $this->_getSceneInfo($request->dev_channel),
            'wap_url' => 'dj.com'
        ];
        // 商户要求添加头
        $headers = array(
            "Content-Type:application/json; charset=utf-8",
            "User-Agent:ua",
        );

        ksort($params);
        $params['sign'] = $this->createSign($params);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $this->_orderUrl);
        //curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $ret = curl_exec($ch);//运行curl
        curl_close($ch);
        $ret = json_decode($ret, true);
        Log::info('createOrder params:'.json_encode($ret));
        // 下单失败
        if ($ret['status'] != "0" || $ret['result_code'] != "0") {
            die(json_encode(['status' => 'F', 'errMsg' => @$ret['err_msg'], 'errCode' => @$ret['err_code']], JSON_UNESCAPED_UNICODE));
        }
        $payInfo = json_decode($ret['pay_info'], true);
        $result = [
            'status' => 'T',
            'paymentUrl' => isset($payInfo['mweb_url']) ? $payInfo['mweb_url'] : "", 
            'orderNo' => isset($ret['out_trade_no']) ? $ret['out_trade_no'] : "", // 我们的订单id
            'errCode' => "", // 错误码
            'errMsg' => isset($ret['message']) ? $ret['message'] : "",
        ];
        
        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 创建sign
     */
    private function createSign($data) {
        $str = "";
        foreach($data as $k => $v) {
            if (empty($str)) {
                $str = $k."=".$v;
            } else {
                $str .= "&".$k."=".$v;
            }
        }
        
        return strtoupper(md5($str."&key=".$this->_secret));
    }
    
    /**
     * 根据客户端返回场景
     */
    private function _getSceneInfo($platform) {
        $pf = strtolower($platform);
        if (preg_match('/android/', $pf)) {
            $str = "app_name=onetwogame&package_name=one.two.game";
        } elseif (preg_match('/android/', $pf)) {
            $str = "app_name=xxxx&bundle_id=xxxx";
        } else{
            $str = "wap_url=http://www.baidu.com&wap_name=one.two.game";
        }
        return $str;
    }
    
    public function notify(Request $request) {
        $params = $_REQUEST;
        $returnSign = isset($params['sign']) ? $params['sign'] : 0;
        unset($params['sign']);
        unset($params['_url']);
        if (isset($params['stauts']) && $params['stauts'] != "0") {
            die(json_encode(['status' => '-1', 'sign' => $returnSign, 'message' => '返回状态有误']));
        }
        if ($params['mch_id'] != $this->_mch_id) {
            die(json_encode(['status' => '-2', 'sign' => $returnSign, 'message' => '商户号有误']));
        }
        
        ksort($params); // 重新排序
        $sign = $this->createSign($params);
        
        if ($sign != $returnSign) {
            die(json_encode(['status' => '-3', 'sign' => $returnSign, 'message' => '签名错误']));
        }
        
        if (isset($params['result_code']) && $params['result_code'] == "0") {
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['orderNo']]));
            die(json_encode(['status' => '0', 'sign' => $returnSign]));
        }
        
    }
    
}