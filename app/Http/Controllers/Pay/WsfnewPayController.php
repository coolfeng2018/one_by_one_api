<?php
/**
 * 旺实富3.0 - 支付宝
 */

namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WsfnewPayController extends Controller
{
    private $orderRepository;
    private $_notifyUrl = 'http://api.musky880.com:8080/orderPay/notify'; // 回调地址
    private $_callbackUrl = 'http://api.musky880.com:8080/orderPay/callback'; // 不知啥用
    private $_usercode = '69018065382101'; // 商户号
    private $_CompKey = '046909170421WspRDVdG'; // 秘钥
    private $_orderUrl = 'http://order.paywap.cn/jh-web-order/order/receiveOrder'; // 下单地址
    
    
    public function __construct(OrderRepository $orderRepository){
        $this->orderRepository = $orderRepository;
    }
    
    public function create(Request $request) {
        $platform = $request->post('dev_channel');
        if (preg_match('/ios/', $platform)) {
            $p25_terminal = '2';
        } elseif (preg_match('/android/', $platform)) {
            $p25_terminal = '3';
        } else {
            $p25_terminal = '1';
        }
        $params = [
            'p1_yingyongnum' => $this->_usercode,
            'p2_ordernumber' => $request->post('orderCode'), // 订单号
            'p3_money' => $request->post('amount') / 100, // 订单金额，单位元
            'p6_ordertime' => date('YmdHis'),
            'p7_productcode' => 'ZFBZZWAP',
            'p14_customname' => $request->post('uid'),
            'p16_customip' => getIp(),
            'p25_terminal' => $p25_terminal,
        ];
     
        $params['p8_sign'] = $this->createSign($params);
        $result = [
            'status' => 'T',
            'paymentUrl' => "http://api.musky880.com:8080/orderPay/createWsfnew?".http_build_query($params), 
            'orderNo' => $request->post('orderCode'), // 我们的订单id
            'errCode' => "", // 错误码
            'errMsg' => "",
        ];

        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    private function createSign ($params) {
        $str = $params['p1_yingyongnum'] . "&" . $params['p2_ordernumber'] . "&" . $params['p3_money'] .
            "&" . $params['p6_ordertime'] . "&" . $params['p7_productcode'] ."&". $this->_CompKey;
        return md5($str);
    }
    
    
    public function setOrder(Request $request) {
        $body = '<form method="post" id="myForm" action="'.$this->_orderUrl.'">'.
        '    <input type="hidden" name="p1_yingyongnum" value="'.$this->_usercode.'">'.
        '    <input type="hidden" name="p2_ordernumber" value="'.$request->get('p2_ordernumber').'">'.
        '    <input type="hidden" name="p3_money" value="'.$request->get('p3_money').'">'.
        '    <input type="hidden" name="p6_ordertime" value="'.$request->get('p6_ordertime').'">'.
        '    <input type="hidden" name="p7_productcode" value="'.$request->get('p7_productcode').'">'.
        '    <input type="hidden" name="p14_customname" value="'.$request->get('p14_customname').'">'.
        '    <input type="hidden" name="p16_customip" value="'.$request->get('p16_customip').'">'.
        '    <input type="hidden" name="p25_terminal" value="'.$request->get('p25_terminal').'">'.
        '    <input type="hidden" name="p8_sign" value="'.$request->get('p8_sign').'">'.
        '</form>'.
        '<script>'.
        'document.getElementById("myForm").submit()'.
        '</script>';
        
        echo $body;
    }
    
    public function notify(Request $request) {
        $params = $_REQUEST;
        if (!isset($params['p4_zfstate']) || $params['p4_zfstate'] != 1) {
            die('fail');
        }
        if ($params['p1_yingyongnum'] != $this->_usercode) {
            die('fail2');
        }
        $str = $params['p1_yingyongnum'] . "&" . $params['p2_ordernumber'] . "&" . $params['p3_money'] . "&" . $params['p4_zfstate'] .
                "&" . $params['p5_orderid'] . "&" . $params['p6_productcode'] . "&" . $params['p7_bank_card_code'] . "&" .
                $params['p8_charset'] . "&". $params['p9_signtype'] . "&" . $params['p11_pdesc'] . "&" . $this->_CompKey;
        // sign验证不通过
        if (strtoupper(md5($str)) != $params['p10_sign']) { 
            die('fail3');
        }
        if ($params['p4_zfstate'] == 1) {
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['p2_ordernumber']]));
            die('success'); // 处理成功打印success
        }
    }
    
}