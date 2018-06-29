<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use App\Repositories\AgentRepository;
use App\Repositories\WithdrawRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

class WithdrawController extends Controller
{
    private $withdrawRepository;
    private $agentRepository;

    public function __construct(WithdrawRepository $withdrawRepository,AgentRepository $agentRepository)
    {
        $this->agentRepository=$agentRepository;
        $this->withdrawRepository=$withdrawRepository;
    }

    /**
     * 获取代理提现记录列表
     * @param $token
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function getWithdrawRecord($token,$offset,$count)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            $withdrawRecords=$this->withdrawRepository->getWithdraw($agent->AgentId,$offset,$count);
            if ($withdrawRecords)
            {
                return response()->result(200,'OK',$withdrawRecords);
            }
            else
            {
                return response()->result(200,'OK',[]);
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }


    /**
     * 获取代理金币提取记录列表
     * @param $token
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function getWithdrawCoinRecord($token,$offset,$count)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            // $withdrawRecords=$this->withdrawRepository->getWithdraw($agent->AgentId,$offset,$count);

            $withdrawRecords = DB::table('agent_withdraw_coin')->where('AgentId',$agent->AgentId)
            ->orderBy('CreateAt','desc')
            ->skip($offset)
            ->take($count)
            ->get();
            if ($withdrawRecords)
            {
                return response()->result(200,'OK',$withdrawRecords);
            }
            else
            {
                return response()->result(200,'OK',[]);
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    /**
     * 前端页面提现接口
     * @param Request $request
     * @param $token
     * @return mixed
     */
    public function withdraw(Request $request,$token)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            $withdrawArr=$request->post();
            if ((float)($withdrawArr['Amount'])<=0||(float)($withdrawArr['Amount']>$agent->Balance))
            {
                return response()->result(501,'withdraw fails');
            }
            $agent=$this->agentRepository->findById($agent->AgentId);
            $OriginBalance = $agent->Balance;
            $agent->Balance-=$withdrawArr['Amount'];
            if ($agent->Balance<0)
            {
                return response()->result(501,'over the range');
            }
            $agent->FrozenAmount+=$withdrawArr['Amount'];
            $withdraw=new Withdraw();
            $withdraw->AgentId=$agent->AgentId;
            $withdraw->Amount=$withdrawArr['Amount'];
            $withdraw->WithdrawChannel=$withdrawArr['WithdrawChannel'];
            $withdraw->WithdrawInfo=$withdrawArr['WithdrawInfo'];
            $withdraw->CurrentBalance=$agent->Balance;
            DB::beginTransaction();
            try
            {
                $upData['Balance'] = $agent->Balance;
                $upData['FrozenAmount'] = $agent->FrozenAmount;
                $where['AgentId'] = $agent->AgentId; 
                $where['Balance'] = $OriginBalance;
                $result = DB::table('agents')->where($where)->where('Balance','>=',$withdrawArr['Amount'])->update($upData);
                if(!$result || !$this->withdrawRepository->withdraw($withdraw)){
                    throw new \Exception("更新失败");
                }
                DB::commit();
                Redis::set($token,json_encode($agent));
                return response()->result(200,'OK');
            }
            catch (\Exception $exception)
            {
                DB::rollBack();
                error_log(print_r($exception->getTraceAsString(),true));
                return response()->result(500,'withdraw fails');
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    /**
     * 前端页面回调接口
     * @param Request $request
     * @param $token
     * @return mixed
     */
    public function callBackUrl(Request $request)
    {
        error_log(print_r($request->post(),true));
        $resultData['code'] = 404;
        $resultData['uid'] = '';
        $resultData['coins'] = '';
        $resultData['msg'] = '';
        // $resultData['msg'] = '';
        $data = $request->post();

        //查询数据库是否存在该值
        if(!isset($data['seq'])){
            $resultData['msg'] = 'no seq';
            exit(json_encode($resultData));
        }
        $resutl = DB::table('agent_withdraw_coin')->where('Seq','=',$data['seq'])->first();
        if(!$resutl){
            $resultData['msg'] = 'no seq';
            exit(json_encode($resultData));
        }
        if($resutl->Status==2){
            $resultData['msg'] = 'is deal with.';
            exit(json_encode($resultData));
        }
        DB::beginTransaction();
        try
        {
            //订单状态已处理
            $upWhere['AgentId'] = $resutl->AgentId;
            $upWhere['Seq'] = $data['seq'];
            $upWhere['Status'] = 0;
            $resultUp = DB::table('agent_withdraw_coin')->where($upWhere)->update(['status' => 2]);
            if(!$resultUp){
                throw new \Exception("更新失败");
            }
            $agentsUp = DB::table('agents')->where(['AgentId'=>$resutl->AgentId])->decrement('FrozenCoinAmount', $resutl->Amount);
            if(!$agentsUp){
                throw new \Exception("更新失败");
            }
            DB::commit();
        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            error_log(print_r($exception->getTraceAsString(),true));
            return response()->result(500,'withdraw fails');
        }
        $resultData['code'] = 0;
        $resultData['uid'] = $resutl->Uid;
        $resultData['coins'] = $resutl->Amount;
        exit(json_encode($resultData));
    }

    /**
     * 前端页面金币提现接口
     * @param Request $request
     * @param $token
     * @return mixed
     */
    public function withdrawCoin(Request $request,$token)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);  
            $withdrawArr=$request->post();
            if ((float)($withdrawArr['Amount'])<=0||(float)($withdrawArr['Amount']>$agent->BalanceCoin))
            {
                return response()->result(501,'withdrawcoin fails');
            }
            $agent=$this->agentRepository->findById($agent->AgentId);
            $OriginBalanceCoin = $agent->BalanceCoin;
            $agent->BalanceCoin-=$withdrawArr['Amount'];
            // $agent->FrozenCoinAmount+=$withdrawArr['Amount'];
            if ($agent->BalanceCoin<0)
            {
                return response()->result(501,'over the range');
            }
            $agent->FrozenCoinAmount+=$withdrawArr['Amount'];//冻结金额
            $withCrawCoinData=Array();
            $withCrawCoinData['AgentId'] = $agent->AgentId;//代理ID
            $withCrawCoinData['Amount'] = $withdrawArr['Amount'];//提取金币金额
            $withCrawCoinData['CurrentBalance'] = $agent->BalanceCoin;//减去后当前账户金币余额
            $withCrawCoinData['Seq'] = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号
            $withCrawCoinData['Uid'] = $agent->UserId;
    
            //生成订单数据,get请求
            DB::beginTransaction();
            try
            {
                //更新后，再判断
                //增加冻结金额 减少金币
                $upData['BalanceCoin'] = $agent->BalanceCoin;
                $upData['FrozenCoinAmount'] = $agent->FrozenCoinAmount;
                $where['AgentId'] = $agent->AgentId; 
                $where['BalanceCoin'] = $OriginBalanceCoin;
                DB::connection()->enableQueryLog();
                $result = DB::table('agents')->where($where)->where('BalanceCoin','>=',$withdrawArr['Amount'])->update($upData);
                // dump(DB::getQueryLog());
                // error_log(print_r(DB::getQueryLog(),true));
                if($result){
                    //添加记录
                    DB::table('agent_withdraw_coin')->insert($withCrawCoinData);

                    //加金币处理 {"code":0}
                    $data['seq'] = $withCrawCoinData['Seq'];
                    $data['host'] = config('app.callBackUrl');
                    $data['path'] = '/api/v1/agent/callback';
                    DB::commit();

                    Redis::set($token,json_encode($agent));
                    // error_log(print_r($data,true));

                    //服务端加金币
                    $curl = $this->httpRequest(config('app.serverUrl'),'get',$data);
                    return response()->result(200,'OK');
                }else{
                    DB::rollBack();
                    error_log(print_r($result,true));
                    return response()->result(500,'withdraw fails');
                }
            }
            catch (\Exception $exception)
            {
                DB::rollBack();
                error_log(print_r($exception->getTraceAsString(),true));
                return response()->result(500,'withdraw fails');
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    function httpRequest($url,$method,$params=array()){
        if(trim($url)==''||!in_array($method,array('get','post'))||!is_array($params)){
            return false;
        }
        $str='?';
        foreach($params as $k=>$v){
            $str.=$k.'='.$v.'&';
        }
        $str=substr($str,0,-1);
        $url.=$str;//$url=$url.$str;
        // echo $url;exit;
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_NOSIGNAL,1);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS,300);
        curl_setopt($curl,CURLOPT_HEADER,0 );
        curl_setopt($curl,CURLOPT_URL,$url);
        $data =curl_exec($curl);
        $curl_errno = curl_errno($curl);  
        $curl_error = curl_error($curl);
        curl_close($curl);
        if($curl_errno >0){  
                return  "cURL Error ($curl_errno): $curl_error\n";  
        }
        return $data;
    }
}
