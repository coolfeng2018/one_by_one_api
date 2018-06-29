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
* 1比1 接口类
*/
class OneByOneController extends Controller
{
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
        // echo time();exit;
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
    * get bingding info.
    * 获取用户绑定信息
    * @param Request $request
    * @return Response
    */
    // public function getBingdingInfo(GetBingdingValidatorRequest $request){
    public function getBingdingInfo(Request $request){
        $error = $this->headerValidation();
        if($error){
            // return response()->result(402, $error,[]);
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }
        $request = [];
        $request['uid'] = request()->header('uid'); 
        //日志记录
        Log::debug($request);
        DB::beginTransaction();
        try{
            $where['uid'] = $request['uid'];
            $where['Status'] = 1;
            //create sql
            DB::connection()->enableQueryLog();
            $result = DB::table('credit')
                ->select('uid','account','Name','idCard','originBank','originProvince','originCity','branchBank','type','CreateAt')
                ->where($where)
                ->get();

            Log::debug(print_r(DB::getQueryLog(),true));
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($request);
            Log::info($e->getMessage());
            // return response()->result(400,'server error',[]);
            return response()->json(['code'=>400,'msg'=>'查询失败，请重新尝试。','result'=>[]]);
        }
        // return response()->result(200,'OK',$result);
        return response()->json(['code'=>200,'msg'=>'OK','result'=>$result]);
    }

    /**
    * create new bingding.
    * 绑定支付宝，或银行卡
    * @param Request $request
    * @return Response
    */
    // public function bingding(BingdingValidatorRequest $request){
    public function bingding(Request $request){
        $error = $this->headerValidation();
        if($error){
            return response()->result(402, $error,[]);
        }
        //头信息
        $header['time'] = request()->header('time');
        $header['uid'] = request()->header('uid');
        $header['sign'] = request()->header('sign');


        $request = $request->json()->all();
        Log::debug($request);
        //表单
        if($request['type']=='alipay'){
            $formString = 'type,account';
        }else{
            $formString = 'type,account,name,idCard,originBank,originProvince,originCity,branchBank';
        }
        $formData = explode(',', $formString);
        $request['uid'] = request()->header('uid');
        foreach ($formData as $k => $v) {
            if(!isset($request[$v]) || empty($request[$v])){
                // return response()->json(['code'=>402,'msg'=>'参数'.$v.'是必填的.','result'=>[]]);
                return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交.','result'=>[]]);
            }
        }
        //type 表单
        $typeData = ['alipay','bank'];
        if(!in_array($request['type'], $typeData)){
            // return response()->json(['code'=>402,'msg'=>'参数'.$request['type'].'不在范围内.','result'=>[]]);
            return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交。.','result'=>[]]);
        }

        if($request['type']=='alipay'){
            $request['type'] = 0;
        }else{
            $request['type'] = 1;
        }
        //日志记录
        Log::debug($request);
        DB::beginTransaction();
        try{
            $insert = [];
            if($request['type']=='alipay'){
                $insert['uid'] = $request['uid'];
                $insert['name'] = $request['name'];
                $insert['account'] = $request['account'];
                $insert['type'] = 0;
            }else{
                $insert['uid'] = $request['uid'];
                $insert['name'] = $request['name'];
                $insert['account'] = $request['account'];
                $insert['idCard'] = $request['idCard'];
                $insert['originBank'] = $request['originBank'];
                $insert['originProvince'] = $request['originProvince'];
                $insert['originCity'] = $request['originCity'];
                $insert['branchBank'] = $request['branchBank'];
                $insert['type'] = 1;
            }
            //create sql
            $where['uid'] = $insert['uid'];
            $where['type'] = $insert['type'];
            $isUserData = DB::table('credit')->where($where)->first();

            //insert or uodate Data
            DB::connection()->enableQueryLog();
            if($isUserData){
                $result = DB::table('credit')->where($where)->update($insert);
            }else{
                $result = DB::table('credit')->insert($insert);
            }
            Log::debug(print_r(DB::getQueryLog(),true));
            // if(!$result){
            //     throw new \Exception("绑定失败");
            // }
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($e->getMessage());
            // return response()->result(400,'server error',[]);
            return response()->json(['code'=>400,'msg'=>'绑定失败，请重新尝试。','result'=>[]]);
        }
        // return response()->result(200,'OK',[]);
        return response()->json(['code'=>200,'msg'=>'OK','result'=>[]]);
    }


    /**
    * create new withdrawOrder.
    * 提现订单接口
    * @param Request $request
    * @return Response
    */
    public function withdrawOrder(Request $request){
        $error = $this->headerValidation();
        if($error){
            // return response()->result(402, $error,[]);
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }

        //表单
        $formString = 'currentAmount,withdrawAmount,type';
        $formData = explode(',', $formString);
        $request = $copyRequest = $request->json()->all();
        $request['uid'] = request()->header('uid');
        foreach ($formData as $k => $v) {
            if(!isset($request[$v]) || empty($request[$v])){
                // return response()->result(402, '参数'.$v.'是必填的.',[]);
                return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交。','result'=>[]]);
            }
        }
        //type 表单
        $typeData = ['alipay','bank'];
        if(!in_array($request['type'], $typeData)){
            // return response()->result(402, '参数'.$request['type'].'不在范围内.',[]);
            return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交。','result'=>[]]);
        }

        if($request['type']=='alipay'){
            $request['type'] = 0;
        }else{
            $request['type'] = 1;
        }

        $where['type'] = $request['type'];
        $where['uid'] = $request['uid'];
        $where['Status'] = 1;
        $select = $request['type']==0 ? ['account','Name'] : ['account','Name','originBank','originProvince','originCity','idCard','branchBank'];
        $infoCredit = DB::table('credit')->select($select)->where($where)->first();
        if(!$infoCredit){
            return response()->json(['code'=>402,'msg'=>'不存在支付数据,请先绑定.','result'=>[]]);
        } 
        Log::debug($request);
        //初始化金额单位为分
        $request['withdrawAmount'] = $request['withdrawAmount']/100;
 
        //验证提取范围
        $rangeCurrentAmount = Redis::get('rangeCurrentAmount');
        $minAmount = Redis::get('minAmount');
        if(empty($rangeCurrentAmount) || empty($minAmount)){
            return response()->json(['code'=>402,'msg'=>'未设置下限范围,请联系管理员.','result'=>[]]);
        }

        if($request['withdrawAmount']<$rangeCurrentAmount || ($request['currentAmount']/100)-$request['withdrawAmount']<$minAmount){
            return response()->json(['code'=>402,'msg'=>'兑换额度最小为'.$rangeCurrentAmount.'元,至少保留'.$minAmount.'元','result'=>[]]);
        }

        //向服务端申请 start
        $postAddCoin['uid'] = $request['uid'];
        $postAddCoin['coins'] = $request['withdrawAmount']*100;

        Log::info($postAddCoin);
        try{
            $url = env('WITHDRAW_URL').'?'.'uid='.$postAddCoin['uid'].'&coins='.$postAddCoin['coins'];
            $client = new Client(['timeout' => 2.0]);
            $responseNormal = $client->request('GET', $url, ['headers' => null]); 
            if ($responseNormal->getStatusCode()==200)
            {
                $resultNormal=$responseNormal->getBody()->getContents(); 
                Log::info($resultNormal);//记录返回信息日志
                $resultNormal=json_decode($resultNormal,true);
                if($resultNormal['code']!=0){
                    return response()->json(['code'=>$resultNormal['code']]);
                }
            }else{
                $resultNormal=$responseNormal->getBody()->getContents(); 
                Log::info($responseNormal->getStatusCode());
                Log::info($resultNormal);
                return response()->json(['code'=>400,'msg'=>'提取失败，请重新尝试。','result'=>[]]);
                // return response()->json(['code'=>400,'msg'=>'client server time out.','result'=>[]]);
            }
        }catch (\Exception $e) { 
            Log::info($e->getMessage());
            return response()->json(['code'=>400,'msg'=>'提取失败，请重新尝试。','result'=>[]]);
            // return response()->json(['code'=>400,'msg'=>'client server time out.','result'=>[]]);
        }

        //日志记录
        // Log::debug($request);
        DB::beginTransaction();
        try{
            $insert = [];
            $insert['uid'] = $request['uid'];//用户id
            $insert['Amount'] = $request['withdrawAmount'];//提现金额
            $insert['CurrentBalance'] = $request['currentAmount']/100;//当前金额
            $insert['Balance'] = $resultNormal['curr_coins']/100;//余额
            $insert['WithdrawChannel'] = $request['type'];//提现渠道
            $insert['WithdrawInfo'] = json_encode($infoCredit);//提款账号信息
            $insert['IsRead'] = 0;//新消息提示(未读),用于声音提醒标记
            //insert Data
            DB::connection()->enableQueryLog();
            $result = DB::table('withdraw')->insert($insert);
            Log::debug(print_r(DB::getQueryLog(),true));
            if(!$result){
                throw new \Exception("提取失败，请重新尝试。");
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($e->getMessage());
            // return response()->json(['code'=>400,'msg'=>'server error','result'=>[]]);
            return response()->json(['code'=>400,'msg'=>'提取失败，请重新尝试。','result'=>[]]);
        }
        //发送邮件通知
        $url = env('PORJECT_ONE_BY_ONE_API');
        $email = (object) [
            'title' => '兑换申请已提交',
            'content' => '您申请兑换'.$request['withdrawAmount'].'元已提交，最快5分钟到账，请耐心等待。',
            'mail_type' => 2,
            'range' => $request['uid'],
            'op_user' => 'GM',
        ]; 
        sendEmail($email);
        //发送短信 注：第一次执行需要在根目录执行：composer dump-autoload ,才能引入类
        $mobile = getMobileList();
        if($mobile){
            sendMessageWidthdraw($mobile,"4452",$request['uid']);
        }
        return response()->json(['code'=>200,'msg'=>'OK','result'=>[]]);
    }


    /**
    * send message api
    * 发消息接口
    * @param Request $request
    * @return Response
    */
    public function sendMessage(Request $request){
        $error = $this->headerValidation();
        if($error){
            // return response()->result(402, $error,[]);
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }

        //表单
        $formString = 'message';
        $formData = explode(',', $formString);
        $request = $copyRequest = $request->json()->all();
        $request['FromUid'] = request()->header('uid');
        $request['ToUid'] = isset($request['ToUid']) ? $request['ToUid'] : 888888;//客服ID
        foreach ($formData as $k => $v) {
            if(!isset($request[$v]) || empty($request[$v])){
                // return response()->result(402, '参数'.$v.'是必填的.',[]);
                // return response()->json(['code'=>402,'msg'=>'参数'.$v.'是必填的.','result'=>[]]);
                return response()->json(['code'=>402,'msg'=>'您输入的信息不完整，请核对后重新提交。','result'=>[]]);
            }
        }
          
        //日志记录
        Log::debug($request);
        DB::beginTransaction();
        try{
            $insert = [];
            $insert['FromUid'] = $request['FromUid'];
            $insert['ToUid'] = $request['ToUid'];
            $insert['message'] = $request['message'];
            //insert Data
            DB::connection()->enableQueryLog();
            $result['MessageId'] = DB::table('message')->insertGetId($insert);
            Log::debug(print_r(DB::getQueryLog(),true));
            if(!$result){
                throw new \Exception("发送失败，请重新尝试。");
            }
            if(!DB::table('customer')->where('uid','=',$request['FromUid'])->first()){
                $messageInsert['uid'] = $request['FromUid'];
                DB::table('customer')->insert($messageInsert);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($e->getMessage());
            // return response()->result(400,'server error',[]);
            return response()->json(['code'=>400,'msg'=>'发送失败，请重新尝试。','result'=>[]]);
        }
        // return response()->result(200,'OK',[]);
        return response()->json(['code'=>200,'msg'=>'OK','result'=>$result]);
    }

    /**
    * get message api
    * 获取消息接口
    * @param Request $request
    * @return Response
    */
    public function getMessage(Request $request){
        $error = $this->headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }
  
        //表单
        $request = $copyRequest = $request->json()->all();
        $request['FromUid'] = request()->header('uid');
        $request['ToUid'] = isset($request['ToUid']) ? $request['ToUid'] : 888888;//客服ID
        $request['search_time'] = isset($request['search_time']) ? $request['search_time'] : '';

        if(isset($request['read_state']) && $request['read_state']==1){
            $update['read_state'] = 1;//标记未已读
            DB::connection()->enableQueryLog();
            DB::table('message')->where('FromUid','=',$request['FromUid'])->update($update);
            DB::table('message')->where('ToUid','=',$request['FromUid'])->update($update);
            log::debug(DB::getQueryLog()); 
        }
        if(isset($request['is_new']) && $request['is_new']==1){
            $result = [];
            $msg = 'NO';
            $sql = 'select * from `message` where (`FromUid` = '.$request['FromUid'].' or `ToUid` = '.$request['FromUid'].') AND read_state = 0  order by `MessageId` desc';
            $data = DB::select($sql);
            if(count($data)){
                $msg = 'OK';
            }
            return response()->json(['code'=>200,'msg'=>$msg,'result'=>$result]);
        }

        //日志记录
        Log::debug($request);
        DB::beginTransaction();
        try{
            $insert = [];
            $where['FromUid'] = $request['FromUid'];
            $this->fromUid = $where['FromUid'];
            //search Data
            DB::connection()->enableQueryLog();
            if(isset($request['MessageId'])){
                $result = DB::table('message')
                    ->where('MessageId','>',$request['MessageId'])
                    ->where(function($query){$query->where('FromUid',$this->fromUid)->orWhere('ToUid', $this->fromUid);})->orderBy('MessageId','desc')->limit(20)->get();

            }else{
                $result = DB::table('message')->where($where)
            ->orWhere('ToUid','=',$where['FromUid'])->orderBy('MessageId','desc')->limit(20)->get();
            }
            Log::debug(print_r(DB::getQueryLog(),true));
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($e->getMessage());
            return response()->json(['code'=>400,'msg'=>'查询失败，请重新尝试。','result'=>[]]);
        }

        if($result){
            foreach ($result as $key => $value) {
                if(isset($value->ToUid)){
                    $result[$key]->ToUid = (int)$value->ToUid;
                }
            }
        }
        return response()->json(['code'=>200,'msg'=>'OK','result'=>$result]);
    }

    

}
