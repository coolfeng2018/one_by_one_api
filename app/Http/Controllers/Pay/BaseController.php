<?php
/**
 * 支付
 */
namespace App\Http\Controllers\Pay;

use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    protected $_notifyUrl;
    protected $_callbackUrl;
    protected $_merchantNo;
    protected $_secret;
    protected $order;

    public function __construct(OrderRepository $orderRepository, Request $request) {
        Log::info("construct params:".json_encode($_REQUEST));
        $method = $this->_getMethod();
        if ($method == 'notify') {
            if (isset($_REQUEST['merchantNo']) && $_REQUEST['merchantNo'] == '32413110017678') { // 高付通
                $request->channel = "Gft";
            } elseif (isset($_REQUEST['bargainor_id']) && $_REQUEST['bargainor_id'] == '109124') { // 15173
                $request->channel = "Number";
            } elseif (isset($_REQUEST['merId']) && $_REQUEST['merId'] == '1805041539862877') { // 和支付
                $request->channel = "Bhhudong";
            } elseif (isset($_REQUEST['p1_usercode']) && $_REQUEST['p1_usercode'] == '5010208022') { // 旺实富
                $request->channel = "Wsf";
            } elseif (isset($_REQUEST['memberid']) && $_REQUEST['memberid'] == '11883') { // 优+
                $request->channel = "Uadd";
            } elseif (isset($_REQUEST['p1_yingyongnum']) && $_REQUEST['p1_yingyongnum'] == '69018065382101') { // 旺实富3.0
                $request->channel = "Wsfnew";
            }
        }
        
        if ($method != 'callback') {
            $this->autoload($request->channel, $orderRepository);
            if (method_exists($this->order, $method)) {
                $this->order->$method($request);
                die();
            }
        }
    }

    protected function _getMethod() {
        return explode('/', $_REQUEST['_url'])[2];
    }
    
    protected function autoload($classname, $orderRepository) {
        require (dirname(__file__)."/".$classname . "PayController.php");
        $class = "App\Http\Controllers\Pay\\".$classname."PayController";
        $this->order = new $class($orderRepository);
    }
    
    /**
     * 创建额订单地址
     * @param Request $request
     */
    public function create(Request $request){
        // 默认返回错误
        $result = [
            'status' => 'F',
            'paymentUrl' => "", 
            'orderNo' => "",
            'errCode' => "-200",
            'errMsg' => "找不到对应的支付方式",
        ];
        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 回调发货地址
     * @param Request $request
     */
    public function notify(Request $request){}
    
    /**
     * 支付回访地址
     * @param Request $request
     */
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