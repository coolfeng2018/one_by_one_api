<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use GuzzleHttp\Client;
use LookCloudsClient as SendMessage;

function p($arr){
    echo '<pre>';
    print_r($arr);
    echo '<pre>';
}
//交集差集
function arraypro($pre,$next){

    $mix=array_intersect_assoc($pre,$next);
    if(empty($mix)){
        $res=$pre;
    }else{
        $res=array_diff_assoc($pre,$mix);
    }
    return $res;
}
//日志
function myLog($string){

    $file='/storage/mylogs/'.date('Ymd').'.txt';

    if(!file_exists($file)){
        mkdir( $file,0777,true);
    }
    file_put_contents($file,$string);
}

/**
 * 数组分页方法 接口调用
 * @param  array $item "$item = array_slice($data, ($current_page-1)*$perPage, $perPage);" 切分的数据
 * @param  integer $total 总条数
 * @param  integer $perPage 显示页数
 * @param  integer $current_page 当前页
 * @return [type]
 */
function getPageApi($item = [],$total = 1,$perPage = 10,$current_page = 1){
    $paginator = new LengthAwarePaginator($item, $total, $perPage, $current_page, [
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
    ]);
    return $paginator;
}


function headerValidation()
{
    $header = [];
    $timeliness = '1800';//过期时间
    $spe_key = 'e948afae5761018e7af958e0a8bd675a';
    $header['time'] = request()->header('time');
    $header['uid'] = request()->header('uid');
    $header['sign'] = request()->header('sign');

    Log::debug($header);
    //验证头信息
    foreach ($header as $key => $value) {
        if(empty($value)){
            return '您输入的信息不完整，请核对后重新提交。';
        }
    }

    //时效性验证
    if(time()-$header['time']>$timeliness){
        return '签名超时.';
    }

    //验证签名
    $sign = md5($header['uid'].$header['time'].$spe_key);
    // Log::debug('uid:'.$header['uid']);
    // Log::debug('time:'.$header['time']);
    // Log::debug('key:'.$spe_key);
    // Log::debug('sign:'.$sign);
    // Log::debug('no-sign:'.$header['uid'].$header['time'].$spe_key);
    
    // echo $sign;exit;
    if($sign!=$header['sign']){
        return '签名验证失败.';
    }
    return false;
}

function getProp($id=''){
    $data = [
        1000 => [
            "name" => "RMB",
            "description" => "人民币",
            "icon" => "active/MTUyMDMxMjk0NDI3Nzk1NDM.jpeg",
        ],
        1001 => [
            "name" => "金币",
            "description" => "金币",
            "icon" => "active/MTUxNzU1OTAxNTEwODU5OTQ.png",
        ],
        1002 => [
            "name" => "钻石",
            "description" => "钻石",
            "icon" => "active/MTUxNzgzMzY4OTg4NzY0ODg.png",
        ],
        1003 => [
            "name" => "元宝",
            "description" => "元宝",
            "icon" => "Reward_icon_diamond.png",
        ],
    ];
    if(!$id){
        return;
    }
    if(array_key_exists($id, $data)){
        return $data[$id]['name'];
    }
    return;
}

/**
 * curl模拟GET/POST发送请求
 * @param string $url 请求的链接
 * @param array $data 请求的参数
 * @param string $timeout 请求超时时间
 * @return mixed
 * @since 1.0.1
 */
function curl($url, $data = array(), $timeout = 5) { 
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

/**
     * [sendAllEmail 发送邮件]
     * @param  Request $request [object] 对象数组,注意格式
     * // $email = (object) [
        //     'title' => '兑换申请已提交1123',
        //     'content' => '您申请兑换1元已提交，最快5分钟到账，请耐心等待。',
        //     'mail_type' => 2,
        //     'range' => 3961,
        //     'op_user' => 'GM',
        //     'coins' => 2,
        // ]; 
        
     * stdClass Object
        (
            [title] => 测试1  //邮件标题
            [content] => 测试1 //邮件标题
            [mail_type] => 2 //邮件类型，目前只支持指定玩家
            [range] => 1,20,2,10012 //收件人id，支持一封郵件多人收取
            [attach_list] => [{"id":1001,"count":100},{"id":1002,"count":100}] //可以为空
            [op_user] => admin //发件人昵称
            [coins] => 0 //派发金币 非必填
        )
     * @return [type]           [json]
     */
function sendEmail($request){
    $spe_key = 'e948afae5761018e7af958e0a8bd675a';

    $headers = [];
    $headers['uid'] = $request->range;
    $headers['time'] = time();
    $headers['sign'] = md5($headers['uid'].$headers['time'].$spe_key); 

    $url = env('PORJECT_ONE_BY_ONE_API').'/api/v1/server_api/send_mail';
    $client = new Client();

    $form = [];
    $form['title'] = $request->title;
    $form['content'] = $request->content;
    $form['mail_type'] = $request->mail_type ? $request->mail_type : 2;//1全服(目前不支持) 2指定玩家
    $form['op_user'] = $request->op_user;
    $form['range'] = $request->range;
    if(isset($request->coins)){
        $form['coins'] = $request->coins;
    }

    try{
        $responseUsers = $client->request('POST', $url, [
            'json' => $form,
            'headers' => $headers,
            'connect_timeout' => 2
        ]);
    }catch (\Exception $e) {
        Log::info($e->getMessage());
        return false;   
    }

    if ($responseUsers->getStatusCode()==200)
    {
        $resultUsers=$responseUsers->getBody()->getContents();
        
        $resultUsers=json_decode($resultUsers,true);
        if($resultUsers['code']==200){
            return true;
        }
    }
    return false;
}

function getIp(){
    if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $cip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $cip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (! empty($_SERVER['REMOTE_ADDR'])) {
        $cip = $_SERVER['REMOTE_ADDR'];
    } else {
        $cip = '';
    }
    return $cip;
}


/**
 * [sendMessageWidthdraw 提现收益短信通知]
 * @return [type] [description]
 */
function sendMessageWidthdraw($mobile,$templateId="4452",$content){
    $lookCloudsClient = new SendMessage();
    $matchTemplateResult = $lookCloudsClient->sendSmsByTemplate($mobile,$templateId,$content); 
    if($matchTemplateResult->result==1){
        $info['mobile'] = $mobile;
        $info['content'] = $content;
        $info['templateId'] = $templateId;
        Log::info($info);
        return true;
    }
    return false;
}


/**
 * [getMobileList 提现通知手机列表]
 * @return [type] [使用英文逗号隔开]
 */
function getMobileList(){
    return "";
}
