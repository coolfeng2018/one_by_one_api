<?php
/**
 * 15173支付
 */
namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NumberPayController extends Controller
{
    private $orderRepository;
    //private $_merchantNo = '109112';
    private $_merchantNo = '109124'; // 新
    //private $_secret = 'e3cf0e7d3c3ba57b3dbc2b8652de77a2'; // 商户号
    private $_secret = '594603ad61e882784566af6f3cea26c7'; // 商户号 新
    private $_orderUrl = 'http://wx.15173.com/WechatPayInterfacewap.aspx'; // 下单地址
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
            'bargainor_id' => $this->_merchantNo,
            'sp_billno' => $request->post('orderCode'),
            'total_fee' => $request->post('amount')/100,
            'pay_type' => 'a',
            'return_url' => $this->_callbackUrl, // 回访地址
            'select_url' => $this->_notifyUrl, // 支付成功发货地址
            'attach' => '1',
        ];
        Log::info('createOrder params:'.json_encode($_REQUEST));
        if (empty($params['total_fee']) || empty($params['sp_billno']) ) {
            die(json_encode(['status' => 'F', 'errMsg' => '参数错误', 'errCode' => '9014'], JSON_UNESCAPED_UNICODE));
        }

        $params['sign'] = $this->createSign($params, $this->_secret);
        $str = http_build_query($params);

        Log::info('createOrder ret:'.$this->_orderUrl . "?" . $str);
        $result = [
            'status' => 'T',
            'paymentUrl' => $this->_orderUrl . "?" . $str, 
            'orderNo' => $params['sp_billno'], // 我们的订单id
            'errCode' => "", // 错误码
            'errMsg' => "",
        ];

        die(json_encode($result, JSON_UNESCAPED_UNICODE));
        
    }
    
    private function createSign($data, $key) {
        $str = 'bargainor_id='.$data['bargainor_id'].'&sp_billno='.$data['sp_billno'].'&pay_type=a&return_url='.$data['return_url'].'&attach=1&key='.$key;
        return strtoupper(md5($str));
    }
    
    public function notify (Request $request) {
        $result = $request->get('pay_result');
        if (! isset($result) || $result != "0") {
            die('fail');
        }
        // 回调校验规则又是特殊的
        $str = 'pay_result='.$request->get('pay_result').'&bargainor_id='.$request->get('bargainor_id').'&sp_billno='.$request->get('sp_billno').'&total_fee='.$request->get('total_fee').'&attach='.$request->get('attach').'&key='.$this->_secret;
        $sign = $request->get('sign');
        if ($sign == strtoupper(md5($str))) {
            Redis::select(5);
            Redis::lpush('orderPayment', json_encode(['orderCode' => $request->get('sp_billno')]));
            die('OK');
        }
    }
    
    public function callback(Request $request) {
        Log::info("callback params:".json_encode($_REQUEST));
        echo '<html><body>'.
            '<style>'.
            'div {  '.
            ' position: absolute;'.
            ' left: 50%;'.
            ' top: 50%;'.
            'width:200px;'.
            'height:100px;'.
            'margin-left:-100px;'.
            'margin-top:-50px;'.
            'font-size:50px;'.
            '}  '.
            '</style>'.
            '<div id="wrapper">  '.
            '    付款成功'.
            '</div>  '.
            '</body></html>';
    }
}
