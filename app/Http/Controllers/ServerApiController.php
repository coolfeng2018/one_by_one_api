<?php

namespace App\Http\Controllers;


use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\BingdingValidatorRequest;
use App\Http\Requests\GetBingdingValidatorRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
* 1比1 提供服务端接口类
*/
class ServerApiController extends Controller
{
    static $MAIL_STATUS_TOBE = 0;//待发送
    static $MAIL_STATUS_ISTOBE = 1;//已发送
    static $MAIL_STATUS_DELETE = 2;//删除（不是已领取状态,可以修改邮件，修改后重置为状态为待发送,重置未读/已读）

    static $MAIL_TYPE_ALL = 1;//全服
    static $MAIL_TYPE_ONLY = 2;//指定玩家

    static $READ_STATE_TRUE = 0;//未读
    static $READ_STATE_FALSE = 1;//已读

    static $RECEIVE_STATE_FALSE = 0;//未领取
    static $RECEIVE_STATE_TRUE = 1;//已领取

    protected $key = 'e948afae5761018e7af958e0a8bd675a';
    private $timeliness = '1800';//token时效性,单位秒
    protected $header = [];

    public function headerValidation()
    {
        $this->header['time'] = request()->header('time');
        $this->header['uid'] = request()->header('uid');
        $this->header['sign'] = request()->header('sign');
        Log::debug($this->header);
        //验证头信息
        foreach ($this->header as $key => $value) {
            if(empty($value)){
                // return '参数'.$key.'是必填的.';
                return '您输入的信息不完整，请核对后重新提交。';
            }
        }

        //时效性验证
        if(time()-$this->header['time']>$this->timeliness){
            return '签名超时.';
        }

        //验证签名
        $sign = md5($this->header['uid'].$this->header['time'].$this->key);
        // echo $sign;exit;
        if($sign!=$this->header['sign']){
            return '签名验证失败.';
        }
        return false;
    }

   /**
    * get task info.
    * 获取任务公告分享信息
    * @param Request $request
    * @return Response
    */
    public function getTaskInfo(){
        $result=DB::table('channel_version')->select(
            // 'ChannelVersionId',
            // 'id',
            // 'curr_version',
            // 'title',
            // 'des',
            // 'targetUrl',
            // 'img',
            // 'shareImg',
            // 'sharetype',
            // 'sharetab',
            // 'task_share_title',
            // 'task_share_content',
            'task_share_url',
            'announcement_url',
            'kefu_url',
            'agent_url'
            // 'payment_ways',
            // 'created_at'
        )->get();
        return response()->json(['code'=>200,'msg'=>'OK','result'=>$result]);
    }

    /**
    * get gamelist info.
    * 大厅列表
    * @param Request $request
    * @return Response
    */
    public function getGameListInfo(){
        $result=DB::table('games')->select(
            'games.game_type',
            'games.game_name as name',
            'games.game_icon_url as icon'
        )->get();
        return response()->json(['code'=>200,'msg'=>'OK','result'=>$result]);
    }


    /**
    * get mail_list
    * 邮件列表
    * @param Request $request
    * @return Response
    */
    public function getMailList(Request $request){ 
        $request = $request->json()->all();  
        $readState = $request['is_new']==1 ? "AND read_state = ".self::$READ_STATE_TRUE : '';
        $results = [];
        $error = headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }
        $uid = request()->header('uid');
        // 运行数据库查询语句
        $sql = "SELECT * from platform_mail where (SELECT FIND_IN_SET($uid,`range`)) 
            AND status = ".self::$MAIL_STATUS_ISTOBE." AND DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= DATE(create_at) ".$readState." ORDER BY create_at desc LIMIT 30";
        $data = DB::select($sql);
        if($data){
            foreach ($data as $key => $value) {
                $data[$key]->attach_list = json_decode($value->attach_list,true); 
            } 
            $results = $data;
        }
        return response()->json(['code'=>200,'msg'=>'OK','result'=>$results]);
    }

    /**
    *  sendMail
    * 发送邮件
    * @param Request $request
    * @return Response
    */
    public function sendMail(Request $request){
        $results = [];
        $error = headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }

        $request = $request->json()->all();
        $request['uid'] = request()->header('uid');
        Log::debug($request);
        //验证参数
        $formString = 'title,content,mail_type,op_user,range';
        $formData = explode(',', $formString);
        foreach ($formData as $k => $v) {
            if(!isset($request[$v]) || empty($request[$v])){
                Log::debug("参数(".$v.")不在范围内");
                return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交.','result'=>[]]);
            }
        }

        $typeData = [self::$MAIL_TYPE_ONLY];
        if(!in_array($request['mail_type'], $typeData)){
            Log::debug("参数mail_type不在范围内");
            return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交。','result'=>[]]);
        }

        //道具验证
        if(isset($request['attach_list'])){
            foreach ($request['attach_list'] as $key => $value) {
                if(!getProp($value['id'])){
                    return response()->json(['code'=>402,'msg'=>'道具不存在。','result'=>[]]);
                }
            }  
        }

        //请求邮件接口start
        $uidList = explode(',', $request['uid']); 

        DB::beginTransaction();
        try{
            $insert = [];
            foreach ($uidList as $key => $value) {
                $insertWhere = [];
                $insertWhere['range'] = $value;
                $insertWhere['title'] = $request['title'];
                $insertWhere['content'] = $request['content'];
                $insertWhere['mail_type'] = $request['mail_type'];
                $insertWhere['attach_list'] = isset($request['attach_list']) ? json_encode($request['attach_list']) : '';
                $insertWhere['status'] = self::$MAIL_STATUS_ISTOBE;
                $insertWhere['op_user'] = $request['op_user'];
                $insertWhere['coins'] = isset($request['coins']) ? $request['coins'] : 0;
                $insert[] = $insertWhere;
            }
                
            //insert  Data
            DB::connection()->enableQueryLog();
            $result = DB::table('platform_mail')->insert($insert);

            Log::debug(print_r(DB::getQueryLog(),true));
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($insert);
            Log::info($e->getMessage());
            return response()->json(['code'=>400,'msg'=>'邮件发送失败,请重试。','result'=>[]]);
        }

        //请求邮件领取接口start
        $url = env('SERVER_MAIL_API_URL');
        $param = [
            'cmd' => 'notifynewmail',
            'range' => $request['uid'],
            'mail_type' => 2
        ];
 
        $dataRequest['data'] = json_encode($param); 
        Log::debug($dataRequest);
 
        $result = curl($url,$dataRequest);
        $res = json_decode($result,true);  
        Log::debug($res);
        //请求邮件领取接口end

        return response()->json(['code'=>200,'msg'=>'OK','result'=>$results]);
    }

    /**
    *  modifyMail
    * 修改邮件
    * @param Request $request
    * @return Response
    */
    public function modifyMail(Request $request){
        $results = [];
        $error = headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }

        $request = $request->json()->all();
        $request['uid'] = request()->header('uid');
        Log::debug($request);
        //验证参数
        $formString = 'title,content,mail_type,op_user,range';
        $formData = explode(',', $formString);
        foreach ($formData as $k => $v) {
            if(!isset($request[$v]) || empty($request[$v])){
                Log::debug("参数(".$v.")不在范围内");
                return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交.','result'=>[]]);
            }
        }

        $typeData = [self::$MAIL_TYPE_ONLY];
        if(!in_array($request['mail_type'], $typeData)){
            Log::debug("参数mail_type不在范围内");
            return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交。','result'=>[]]);
        }

        //道具验证
        if(isset($request['attach_list'])){
            foreach ($request['attach_list'] as $key => $value) {
                if(!getProp($value['id'])){
                    return response()->json(['code'=>402,'msg'=>'道具不存在。','result'=>[]]);
                }
            }  
        }

        $where['range'] = $request['uid'];
        $where['id'] = $request['seq'];
        $where['receive_state'] = self::$RECEIVE_STATE_FALSE;
        Log::debug($where);
        //验证邮件合法性
        $mail = DB::table('platform_mail')->where($where)->first();
        if(!$mail){
            return response()->json(['code'=>402,'msg'=>'邮件不存在,或者已收取','result'=>[]]);
        }

        DB::beginTransaction();
        try{
            $update = [];
            $update['title'] = $request['title'];
            $update['content'] = $request['content'];
            $update['mail_type'] = $request['mail_type'];
            $update['attach_list'] = isset($request['attach_list']) ? json_encode($request['attach_list']) : '';
            $update['status'] = self::$MAIL_STATUS_ISTOBE;
            $update['op_user'] = $request['op_user'];
            $update['coins'] = isset($request['coins']) ? $request['coins'] : 0;
            $update['receive_state'] = self::$RECEIVE_STATE_TRUE;
                
            DB::connection()->enableQueryLog();
            $result = DB::table('platform_mail')->where($where)->update($update);

            Log::debug(print_r(DB::getQueryLog(),true));
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($update);
            Log::info($e->getMessage());
            return response()->json(['code'=>400,'msg'=>'邮件修改失败,请重试。','result'=>[]]);
        }

        //请求邮件领取接口start
        $url = env('SERVER_MAIL_API_URL');
        $param = [
            'cmd' => 'modifymail',
            'range' => $request['uid'],
            'mail_type' => 2
        ];
 
        Log::debug($param);
        $dataRequest['data'] = json_encode($param); 
        Log::debug($dataRequest);
 
        $result = curl($url,$dataRequest);
        $res = json_decode($result,true);
        Log::debug($res);
        //请求邮件领取接口end

        return response()->json(['code'=>200,'msg'=>'OK','result'=>$results]);
    }

    /**
     * 邮件领取接口
     * @param  Request uid,seq,attach_list,coins
     * @return [type] 15:05
     */
    public function receiveMail(Request $request){
        $results = [];
        $error = headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }

        $request = $request->json()->all();
        $request['uid'] = request()->header('uid');

        if($request['type']=='all'){
            $sql =  "SELECT * from platform_mail 
                    where 
                        (SELECT FIND_IN_SET(".$request['uid'].",`range`)) 
                        AND status = ".self::$MAIL_STATUS_ISTOBE."
                        AND receive_state = ".self::$RECEIVE_STATE_FALSE."
                        AND coins <> 0
                    ";
            Log::debug($sql);
            $allData = DB::select($sql);
            if(!$allData){
                return response()->json(['code'=>402,'msg'=>'您没有需要领取的邮件','result'=>[]]);
            }
            foreach ($allData as $key => $value) {
                $allData[$key] = $value->id;
            }
        }

        $ids = $request['type']=='all' ? $allData : explode(',', $request['ids']);
   
        Log::debug($request);
        //查询邮件是否存在,未领取，已发送,金币
        $requestForm = [];
        foreach ($ids as $key => $value) {
            $where = [];
            $where['range'] = $request['uid'];
            $where['id'] = $value;
            $sql =  "SELECT * from platform_mail 
                    where 
                        (SELECT FIND_IN_SET(".$where['range'].",`range`)) 
                        AND status = ".self::$MAIL_STATUS_ISTOBE."
                        AND receive_state = ".self::$RECEIVE_STATE_FALSE."
                        AND id = ".$where['id']."
                        AND coins <> 0
                    ";
            Log::debug($sql);
            $isMail = [];
            $requestWhere = [];
            $isMail = DB::select($sql); 
            if(!$isMail){
                return response()->json(['code'=>402,'msg'=>'不存在的邮件:ids='.$value,'result'=>[]]);
            }
            if($isMail[0]->receive_state!=self::$RECEIVE_STATE_FALSE){  
                return response()->json(['code'=>402,'msg'=>'您已经领取过奖励了。','result'=>[]]);
            }
            $requestWhere['uid'] = (int)$where['range'];
            $requestWhere['seq'] = $isMail[0]->id;
            if($isMail[0]->attach_list){
               $requestWhere['attach_list'] =  json_decode($isMail[0]->attach_list,true); 
            }
            $requestWhere['coins'] = (int)$isMail[0]->coins;
            $requestForm[] = $requestWhere;
        }

        foreach ($requestForm as $key => $value) {  
            //校验返回值
            $val = DB::table('platform_mail')->where('id',$value['seq'])->first();
            if($value['coins'] != $val->coins){
                return response()->json(['code'=>402,'msg'=>'邮件领取失败,请重试。:(','result'=>[]]);
            }
            if($val->receive_state!=self::$RECEIVE_STATE_FALSE){  
                return response()->json(['code'=>402,'msg'=>'您已经领取过奖励了。','result'=>[]]);
            }
            DB::beginTransaction();
            try{
                $update = [];
                $update['receive_state'] = self::$RECEIVE_STATE_TRUE;

                $where = [];
                $where['id'] = $value['seq'];
                $where['receive_state'] = self::$RECEIVE_STATE_FALSE;

                DB::connection()->enableQueryLog();
                    DB::table('platform_mail')->where($where)->update($update);
                Log::debug(print_r(DB::getQueryLog(),true));

                //请求邮件领取接口start
                $url = env('SERVER_MAIL_API_URL');
                $requestFormOnly = [];
                $requestFormOnly[] = $requestForm[$key];
                $param = [
                    'cmd' => 'takeattach',
                    'record_list' => [$requestForm[$key]]
                ];
                $dataRequest['data'] = json_encode($param); 
                Log::debug($dataRequest);
                $result = curl($url,$dataRequest);
                $res = json_decode($result,true);
                Log::debug($res);
                if(isset($res['code']) && $res['code'] != 0) {
                    Log::info($res);
                    $error = '';
                    return response()->json(['code'=>402,'msg'=>'邮件领取失败'.$error,'result'=>[]]);
                }
                //请求邮件领取接口end

                DB::commit();
            }catch (\Exception $e){ 
                DB::rollBack();
                Log::info($e->getMessage());
                return response()->json(['code'=>400,'msg'=>'邮件领取失败,请重试。','result'=>[]]);
            }
        }

        return response()->json(['code'=>200,'msg'=>'OK','result'=>$results]);

    }

    /**
    * get gamelist info.
    * 邮件已读接口
    * @param Request $request
    * @return Response
    */
    public function reqReadMail(Request $request){
        $result = [];
        $error = headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }

        $request = $request->json()->all();
        $request['uid'] = request()->header('uid');

        if($request['type']=='all'){
            $sql =  "SELECT * from platform_mail 
                    where 
                        (SELECT FIND_IN_SET(".$request['uid'].",`range`)) 
                        AND status = ".self::$MAIL_STATUS_ISTOBE."
                        AND read_state = ".self::$READ_STATE_TRUE.";
                    ";
            Log::debug($sql);
            $allData = DB::select($sql); 
            if(!$allData){
                return response()->json(['code'=>402,'msg'=>'您目前没有未读的邮件','result'=>[]]);
            }
            foreach ($allData as $key => $value) {
                $allData[$key] = $value->id;
            }
        }

        $ids = $request['type']=='all' ? $allData : explode(',', $request['ids']);

        foreach ($ids as $key => $value) {
            $where['read_state'] = self::$READ_STATE_TRUE;
            $where['status'] = self::$MAIL_STATUS_ISTOBE;
            $where['id'] = $value;
            $mail = DB::table('platform_mail')->where($where)->first();
            if($mail){
                DB::beginTransaction();
                try{
                    $update = [];
                    $update['read_state'] = self::$READ_STATE_FALSE;

                    $where = [];
                    $where['id'] = $value;

                    DB::connection()->enableQueryLog();
                        DB::table('platform_mail')->where($where)->update($update);
                    Log::debug(print_r(DB::getQueryLog(),true));
                    DB::commit();
                }catch (\Exception $e){ 
                    DB::rollBack();
                    Log::info($e->getMessage());
                    return response()->json(['code'=>400,'msg'=>'已读取失败,请重试。','result'=>[]]);
                }
            }
        }

        return response()->json(['code'=>200,'msg'=>'OK','result'=>$result]);
    }


}
