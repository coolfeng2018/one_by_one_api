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

class UaddPayController extends Controller
{
    private $orderRepository;
    private $_mch_id = '11883';// 商户号
    private $_secret = '8f7gxbtwakj97jdqzat82yiax76en11r'; // 秘钥
    private $_orderUrl = 'http://www.stfuu.com/Pay_Index.html'; // 下单地址
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
            'pay_memberid' => $this->_mch_id,
            'pay_applydate' => date('Y-m-d H:i:s'),
            'pay_orderid' => strval($request->orderCode),
            'pay_bankcode' => '904', // 支付宝h5
            'pay_notifyurl' => $this->_notifyUrl,
            'pay_callbackurl' => $this->_callbackUrl,
            'pay_amount' => $request->post('amount') / 100,
            //'pay_productname' => $request->goodsName, // 支付成功发货地址
        ];

        ksort($params);
        $params['pay_md5sign'] = $this->createSign($params);
        $params['pay_productname'] = $request->goodsName;
        
        $result = [
            'status' => 'T',
            'paymentUrl' => "http://api.musky880.com:8080/orderPay/createUadd?".http_build_query($params), 
            'orderNo' => $request->post('orderCode'), // 我们的订单id
            'errCode' => "", // 错误码
            'errMsg' => "",
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

    public function notify(Request $request) {
        $params = $_REQUEST;
        $returnSign = isset($params['sign']) ? $params['sign'] : 0;
        unset($params['sign']);
        unset($params['_url']);
        unset($params['attach']);
        if (isset($params['returncode']) && $params['returncode'] != "00") {
            die('fail-1');
        }
        if ($params['memberid'] != $this->_mch_id) {
            die('fail-2');
        }
        
        ksort($params); // 重新排序
        $sign = $this->createSign($params);
        
        if ($sign != $returnSign) {
            die('fail-3');
        }
        
        if (isset($params['returncode']) && $params['returncode'] == "00") {
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['orderid']]));
            die('ok');
        }
        die('fail');
        
    }
    
    public function setOrder(Request $request) {
        $body = '<html lang="zh-CN">'.
            '    <head>'.
            '        <meta charset="utf-8">'.
            '        <meta http-equiv="X-UA-Compatible" content="IE=edge">'.
            '        <meta name="viewport" content="width=device-width, initial-scale=1">'.
            '        <title></title>'.
            '    </head>'.
            '    <body>';
        $body .= '<form method="post" id="myForm" action="http://www.stfuu.com/Pay_Index.html">'.
        '    <input type="hidden" name="pay_memberid" value="'.$this->_mch_id.'">'.
        '    <input type="hidden" name="pay_applydate" value="'.$request->get('pay_applydate').'">'.
        '    <input type="hidden" name="pay_orderid" value="'.$request->get('pay_orderid').'">'.
        '    <input type="hidden" name="pay_bankcode" value="'.$request->get('pay_bankcode').'">'.
        '    <input type="hidden" name="pay_notifyurl" value="'.$this->_notifyUrl.'">'.
        '    <input type="hidden" name="pay_callbackurl" value="'.$this->_callbackUrl.'">'.
        '    <input type="hidden" name="pay_amount" value="'.$request->get('pay_amount').'">'.
        '    <input type="hidden" name="pay_md5sign" value="'.$request->get('pay_md5sign').'">'.
        '    <input type="hidden" name="pay_productname" value="'.$request->get('pay_productname').'">'.
        '</form>'.
        '<script>'.
        'document.getElementById("myForm").submit()'.
        '</script>'.
        '</body>'.
        '</html>';
        
        echo $body;
    }
    
}