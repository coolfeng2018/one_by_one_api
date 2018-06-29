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
use Storage;

/**
* 1比1 代理中心->接口类
*/
class OneByOneAgentsController extends Controller
{

    /**
     * [isAgent 是否代理]
     * @param  Request $request [description]
     * @return boolean          [description]
     */
    public function isAgent(Request $request){
        $error = headerValidation();
        if($error){
            return response()->json(['code'=>402,'msg'=>$error,'result'=>[]]);
        }
        $request = [];
        $request['uid'] = request()->header('uid');
        $agents = DB::table('agents')->where('UserId','=',$request['uid'])->first();
        $player = DB::table('agent_third_auth')->where('UserId','=',$request['uid'])->first();
        $isAgent = $agents ? true : false;
        $isAgentPlayer = $player ? true : false;
        return response()->json(['code'=>200,'msg'=>'OK','isAgent'=>$isAgent,'isAgentPlayer'=>$isAgentPlayer]);
    }

    /**
    * create new bingding.
    * 手动绑定代理
    * @param Request $request
    * @return Response
    */ 
    public function bingding(Request $request){
        $error = headerValidation();
        if($error){
            return response()->result(402, $error,[]);
        }
        //头信息
        $header['time'] = request()->header('time');
        $header['uid'] = request()->header('uid');
        $header['sign'] = request()->header('sign');


        $request = $request->json()->all();
        Log::debug($request);
        $request['uid'] = request()->header('uid');
        if(!isset($request['AgentId']) || !$request['AgentId']){
            return response()->json(['code'=>402,'msg'=>'代理ID不能为空！','result'=>[]]);
        }

        if(!isset($request['Nickname']) || !$request['Nickname']){
            return response()->json(['code'=>402,'msg'=>'游戏昵称不能为空！','result'=>[]]);
        }

        $agents = DB::table('agents')->where('AgentId','=',$request['AgentId'])->first();
        if(!$agents){
            return response()->json(['code'=>402,'msg'=>'代理不存在！','result'=>[]]);
        }

        if($agents->UserId==$request['uid']){
            return response()->json(['code'=>402,'msg'=>'代理不能绑定自己！','result'=>[]]);
        }

        $player = DB::table('agent_third_auth')->where('UserId','=',$request['uid'])->first();
        if($player){
            return response()->json(['code'=>402,'msg'=>'该用户已绑定代理！','result'=>[]]);
        }

        //日志记录
        Log::debug($request);
        DB::beginTransaction();
        try{
            $insert = [];
            $insert['ThirdUnionId'] = '';
            $insert['ThirdId'] = '';
            $insert['UserId'] = $request['uid'];
            $insert['AgentId'] = $request['AgentId'];
            $insert['Nickname'] = $request['Nickname'];

            DB::table('agent_third_auth')->insert($insert);
            Log::debug(print_r(DB::getQueryLog(),true));
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info($e->getMessage());
            return response()->json(['code'=>400,'msg'=>'绑定失败，请重新尝试。','result'=>[]]);
        }
        return response()->json(['code'=>200,'msg'=>'OK','result'=>[]]);
    }

    /**
     * [getBindAgent 二维码加绑IP]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getBindAgentIp(Request $request){
        $locationUrl = 'http://ldy.lixuanjie.com/hsgm/html/obyo/install_bdss02.html';
        $request = $request->all();
        Log::debug($request);
        //获取ip地址
        $ip = getIp();
        $request['ip'] = $ip;
        //自动跳转落地页
        if(!isset($request['agent_id']) || !$ip){
            header("Location: $locationUrl");
        }

        $endTime = date("Y-m-d H:i:s",time());
        $startTime = date("Y-m-d H:i:s",time()-3600*24);
        DB::connection()->enableQueryLog();
        //是否有已处理过的IP
        $queryOld = DB::table('agent_ip_select')
            ->where('ip','=',$ip)
            ->where('status','=',1)
            ->first();
        //二十四小时内,是否有未处理过的ip
        $query = DB::table('agent_ip_select')
            ->where('ip','=',$ip)
            ->whereBetween('create_at',[$startTime,$endTime])
            ->where('status','=',0)
            ->where('agent_id','=',$request['agent_id'])
            ->orderBy('create_at','desc')
            ->first();
        //代理是否存在
        $agents = DB::table('agents')->where('AgentId','=',$request['agent_id'])->first();
 
        if(!$queryOld && !$query && $agents){
            Log::debug($request);
            //插入该数据
            $insert['ip'] = $request['ip'];
            $insert['agent_id'] = $request['agent_id'];
            Storage::append('agent_qrcode.log', $endTime." : ".json_encode($request));
            DB::beginTransaction();
            try{
                $insert = [];
                $insert['ip'] = $request['ip'];
                $insert['agent_id'] = $request['agent_id'];
                DB::table('agent_ip_select')->insert($insert);
                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                Log::info($e->getMessage());
                header("Location: $locationUrl");
            }
        }  
        header("Location: $locationUrl");
    }

    /**
    * create new bindAgent.
    * IP绑定代理
    * @param Request $request
    * @return Response
    */ 
    public function bindAgent(Request $request){
        $error = headerValidation();
        if($error){
            return response()->result(402, $error,[]);
        }
        //头信息
        $header['time'] = request()->header('time');
        $header['uid'] = request()->header('uid');
        $header['sign'] = request()->header('sign');


        $request = $request->json()->all();
        Log::debug($request);
        Storage::append('agent_qrcode_access.log', date("Y-m-d H:i:s",time())." : ".json_encode($request));
        $request['uid'] = request()->header('uid');

        if(!isset($request['ip']) || !$request['ip']){
            return response()->json(['code'=>402,'msg'=>'ip不能为空！','result'=>[]]);
        }

        if(!isset($request['uid']) || !$request['uid']){
            return response()->json(['code'=>402,'msg'=>'uid不能为空！','result'=>[]]);
        }

        if(!isset($request['Nickname']) || !$request['Nickname']){
            return response()->json(['code'=>402,'msg'=>'昵称不能为空！','result'=>[]]);
        }

        //用户是否已绑定过
        $isBindPlayer = DB::table('agent_ip_select')->where('uid','=',$request['uid'])->where('status','=',1)->first();
        if($isBindPlayer){
            return response()->json(['code'=>402,'msg'=>'该用户已绑定代理！','result'=>[]]);
        }

        //ip是否已绑定过
        $isBindIp = DB::table('agent_ip_select')->where('ip','=',$request['ip'])->where('status','=',1)->first();
        if($isBindIp){
            return response()->json(['code'=>402,'msg'=>'该用户已绑定代理！','result'=>[]]);
        }

        //二十四小时内,是否有未处理过的ip
        $endTime = date("Y-m-d H:i:s",time());
        $startTime = date("Y-m-d H:i:s",time()-3600*24);
        $query = DB::table('agent_ip_select')
            ->where('ip','=',$request['ip'])
            ->whereBetween('create_at',[$startTime,$endTime])
            ->where('status','=',0)
            ->orderBy('create_at','desc')
            ->first();

        if($query){
            //代理不能扫码绑定
            $agents = DB::table('agents')->where('UserId','=',$request['uid'])->first();
            if($agents){
                return response()->json(['code'=>200,'msg'=>'OK','result'=>[]]);
            }

            Log::debug($request);
            DB::beginTransaction();
            try{
                //更新数据agent_ip_select
                $update['uid'] = $request['uid'];
                $update['update_at'] = $endTime;
                $update['status'] = 1;
                DB::table('agent_ip_select')->where('agent_ip_id','=',$query->agent_ip_id)->update($update);
                Storage::append('agent_qrcode.log', $endTime." : ".json_encode($request));

                $insert = [];
                $insert['ThirdUnionId'] = '';
                $insert['ThirdId'] = '';
                $insert['UserId'] = $request['uid'];
                $insert['AgentId'] = $query->agent_id;
                $insert['Nickname'] = $request['Nickname'];

                DB::table('agent_third_auth')->insert($insert);
                Log::debug(print_r(DB::getQueryLog(),true));
                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                Log::info($e->getMessage());
                return response()->json(['code'=>400,'msg'=>'绑定失败，请重新尝试。','result'=>[]]);
            }
        } 
        return response()->json(['code'=>200,'msg'=>'OK','result'=>[]]);
    }

}
