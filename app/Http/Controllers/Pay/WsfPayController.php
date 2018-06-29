<?php
/**
 * 旺实富 - 支付宝
 */

namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WsfPayController extends Controller
{
    private $orderRepository;
    private $_notifyUrl = 'http://api.musky880.com:8080/orderPay/notify'; // 回调地址
    private $_callbackUrl = 'http://api.musky880.com:8080/orderPay/callback'; // 不知啥用
    private $_usercode = '5010208022'; // 商户号
    private $_CompKey = 'B5D2110CE70E3DE937A2E37FED7E52A1'; // 秘钥
    private $_orderUrl = 'https://pay.paywap.cn/form/pay'; // 下单地址
    
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
            $p25_terminal = '3';
        }
        $params = [
            'p1_usercode' => $this->_usercode,
            'p2_order' => $request->post('orderCode'), // 订单号
            'p3_money' => $request->post('amount') / 100, // 订单金额，单位元
            'p4_returnurl' => $this->_callbackUrl,
            'p5_notifyurl' => $this->_notifyUrl,
            'p6_ordertime' => date('YmdHis'),
            'p9_paymethod' => '4',
            'p14_customname' => $request->post('uid'),
            'p17_customip' => getIp(),
            'p25_terminal' => $p25_terminal,
            'p26_iswappay' => '3',
        ];
     
        $params['p7_sign'] = $this->createSign($params);
        unset($params['p4_returnurl']);
        unset($params['p5_notifyurl']);
        
        $result = [
            'status' => 'T',
            'paymentUrl' => "http://api.musky880.com:8080/orderPay/createWsf?".http_build_query($params), 
            'orderNo' => $request->post('orderCode'), // 我们的订单id
            'errCode' => "", // 错误码
            'errMsg' => "",
        ];

        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    private function createSign ($params) {
        $str = $params['p1_usercode'] . "&" . $params['p2_order'] . "&" . $params['p3_money'] .
            "&" . $params['p4_returnurl'] . "&" . $params['p5_notifyurl'] . "&" . $params['p6_ordertime'] . $this->_CompKey;
        
        return strtoupper(md5($str));
    }
    
    
    public function setOrder(Request $request) {
        $body = '<form method="post" id="myForm" action="https://pay.paywap.cn/form/pay">'.
        '    <input type="hidden" name="p1_usercode" value="'.$this->_usercode.'">'.
        '    <input type="hidden" name="p2_order" value="'.$request->get('p2_order').'">'.
        '    <input type="hidden" name="p3_money" value="'.$request->get('p3_money').'">'.
        '    <input type="hidden" name="p4_returnurl" value="'.$this->_callbackUrl.'">'.
        '    <input type="hidden" name="p5_notifyurl" value="'.$this->_notifyUrl.'">'.
        '    <input type="hidden" name="p6_ordertime" value="'.$request->get('p6_ordertime').'">'.
        '    <input type="hidden" name="p9_paymethod" value="'.$request->get('p9_paymethod').'">'.
        '    <input type="hidden" name="p14_customname" value="'.$request->get('p14_customname').'">'.
        '    <input type="hidden" name="p17_customip" value="'.$request->get('p17_customip').'">'.
        '    <input type="hidden" name="p25_terminal" value="'.$request->get('p25_terminal').'">'.
        '    <input type="hidden" name="p26_iswappay" value="'.$request->get('p26_iswappay').'">'.
        '    <input type="hidden" name="p7_sign" value="'.$request->get('p7_sign').'">'.
        '</form>'.
        '<script>'.
        'document.getElementById("myForm").submit()'.
        '</script>';
        
        echo $body;
    }
    
    public function notify(Request $request) {
        $params = $_REQUEST;
        if (!isset($params['p4_status']) || $params['p4_status'] != 1) {
            die('fail');
        }
        if ($params['p1_usercode'] != $this->_usercode) {
            die('fail2');
        }
        $str = $params['p1_usercode'] . "&" . $params['p2_order'] . "&" . $params['p3_money'] . "&" . $params['p4_status'] .
                "&" . $params['p5_payorder'] . "&" . $params['p6_paymethod'] . "&&" . $params['p8_charset'] . "&" .
                $params['p9_signtype'] . "&" . $this->_CompKey;
        // sign验证不通过
        if (strtoupper(md5($str)) != $params['p10_sign']) { 
            die('fail3');
        }
        if ($params['p4_status'] == 1) {
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $params['p2_order']]));
            die('success'); // 处理成功打印success
        }
    }
    
}