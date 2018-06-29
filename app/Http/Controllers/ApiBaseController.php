<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiBaseController extends Controller {
    /**
     * 游戏列表
     * @var array
     */
    protected $gamelist = array();
    /**
     * 服务器请求列表
     * @var array
     */
    protected $c_requrl = array(
        'upload' => '',
        'gm' => ''
    );
    
    
    /**
     * 房间类型
     * @var array
     */
    protected $tablelist = array(
        100 => '金花初级',
        101 => '金花普通',
        102 => '金花精英',
        103 => '金花土豪',
        200 => '牛牛新手',
        201 => '牛牛精英',
        202 => '牛牛大师',
        203 => '牛牛土豪',
        200001 => '百人',
        200000 => '红黑',
        200002 => '龙凤对决',
        200003 => '水果机',
        300 => '斗地主初级',
        301 => '斗地主中级',
        302 => '斗地主高级',
    );
    /**
     * 金币变化原因
     * @var array
     */
    protected $gold_reasonlist = array(
        1=>array('en'=>'BET_COIN','cn'=>'押注'),
        2=>array('en'=>'WIN_COIN','cn'=>'赢金币'),//+
        3=>array('en'=>'BET_COIN_BACK','cn'=>'押注失败金币返回'),
        4=>array('en'=>'COST_COIN_ERROR_BACK','cn'=>'扣除金币失败返还'),
        6=>array('en'=>'PAY_FEE','cn'=>'扣台费'),
        7=>array('en'=>'USE_MAGIC_PICTRUE','cn'=>'使用魔法表情'),
        15=>array('en'=>'PAY_COMMISSION','cn'=>'抽取佣金-红黑大战抽取台费'),//红黑大战抽取台费
        
        100000=>array('en'=>'GAMECOIN_BANKRUPT','cn'=>'破产补助'),
        100001=>array('en'=>'GAMECOIN_REGISTER','cn'=>'注册送金币'),
        100002=>array('en'=>'GAMECOIN_SYS_ADD','cn'=>'后台加金币'),
        100003=>array('en'=>'GAMECOIN_SYS_MINUS','cn'=>'后台减金币'),
        100004=>array('en'=>'GAMECOIN_SHARE','cn'=>'分享奖励'),
        100005=>array('en'=>'GAMECOIN_BIND','cn'=>'绑定奖励'),
        100006=>array('en'=>'GAMECOIN_PROMOTION_LST','cn'=>'活动推广金币消耗'),
        100007=>array('en'=>'GAMECOIN_PROMOTION_WIN','cn'=>'活动推广金币奖励'),
        100008=>array('en'=>'GAMECOIN_CHARGE_REWARD','cn'=>'首充送金币'),
        100019=>array('en'=>'SIGN_IN','cn'=>'签到'),
        100020=>array('en'=>'TAKE_SIGN_AWARD','cn'=>'领取签到奖励'),
        100021=>array('en'=>'TAKE_TASK_AWARD','cn'=>'领取任务奖励'),
        100022=>array('en'=>'TAKE_MAIL_ATTACH','cn'=>'领取邮件奖励'),
        100023=>array('en'=>'GM','cn'=>'GM操作'),
        100025=>array('en'=>'BUY_FROM_SHOP','cn'=>'商城购买'),
        100027=>array('en'=>'NEWBIE_AWARD','cn'=>'新手奖励'),
        100028=>array('en'=>'COST_COIN_ERROR_BACK','cn'=>'充值'),//+
        100029=>array('en'=>'COST_COIN_ERROR_BACK','cn'=>'为机器人增加金币'),
        100030=>array('en'=>'ADD_COINS_FOR_ROBOT','cn'=>'领取任务奖励'),
        
        100040=>array('en'=>'EXCHANGE_COINS','cn'=>'兑换金币'),//+
        100041=>array('en'=>'BIND_PHONE_REWARD','cn'=>'绑定手机奖励'),
        100039=>array('en'=>'DESPOSIT_SAFE_BOX','cn'=>'保险箱操作'),
        
    );
    /**
     * 初始化游戏列表
     * (non-PHPdoc)
     * @see Home_Wxbase_Controller::init()
     */
    public function __construct(){
        $games_obj = DB::table('games')->get();
        foreach ($games_obj as $k => $v) {
            $this->gamelist[$v->game_type] = $v->game_name;
        }
    }
    
    /**
     * 写db的操作日志
     * @param unknown $string
     */
    public function saveLog($msg){
        $arr['uid'] = session('admin')['id'];
        $arr['mark'] = $msg;
        $results = DB::table('logger')->insertGetId($arr);  
    }
    
    /**
     * 记录文件日志
     * @param string $content  内容
     * @param string $filename 路径
     * @param string
     */
    public function wlog($content,$filename = '') {
        if(!empty($filename)) {
            $path = 'logs/'.$filename.'.log';
            Log::useDailyFiles(storage_path($path));
        }
        Log::info("(".session('admin')["id"].")".session('admin')["username"]." : ".$content);
    }
    
    /**
     * 获取游戏服用户信息直接查mongo
     * @return array
     */
    protected function getMonUserInfo($uid = 0){
        $manager = new \MongoDB\Driver\Manager(env('MONGOAPI'));// 10.0.0.4:27017
        $filter = ['uid' => ['$eq' => (int)$uid]];
        // 查询数据
        $query = new \MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery('yange_data.base', $query);
        
        $base = [];
        foreach ($cursor as $document) {
            $rs =  json_decode( json_encode( $document),true);
            $base = $rs;
        }
        $cursor2 = $manager->executeQuery('yange_data.users', $query);    
        $users = [];
        foreach ($cursor2 as $document) {
            $rs =  json_decode( json_encode( $document),true);
            $users = $rs;
        }
        return $data=array_merge($base,$users);        
    }
    
    /**
     * 查询用户昵称
     * @param number $uid
     */
    protected function getUserNick($uid = 0) {
        $nick = '';
        $res = $this->getMonUserInfo($uid);
        if(isset($res['uid']) && !empty($res['uid'])) {
            $nick = isset($res['name']) ? $res['name'] : '';
        }
        return $nick;
    }
    
    /**
     * 获取修改内容
     * @param unknown $preArr
     * @param unknown $data
     * @param unknown $string
     * @param unknown $id
     */
    public function getWhatIsModify($pre_arr,$now_arr){
        $tmpPre=json_encode(arraypro($pre_arr,$now_arr));
        $tmpNext=json_encode(arraypro($now_arr,$pre_arr));
        return $tmpPre.'修改为'.$tmpNext;
    }
    

    
    /**
     * curl模拟GET/POST发送请求
     * @param string $url 请求的链接
     * @param array $data 请求的参数
     * @param string $timeout 请求超时时间
     * @return mixed
     * @since 1.0.1
     */
    public function curl($url, $data = array(), $timeout = 5) {
        $ch = curl_init();
        if (!empty($data) && $data) {
            if(is_array($data)){
                $formdata = http_build_query($data);
            } else {
                $formdata = $data;
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
}
