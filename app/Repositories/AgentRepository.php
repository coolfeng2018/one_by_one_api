<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/15
 * Time: 12:08
 */

namespace App\Repositories;


use App\Models\Agent;
use App\Models\User;
use App\Models\ThirdAuth;
use Illuminate\Support\Facades\DB;

class AgentRepository
{
    public function __construct()
    {
    }

    public function findById($id)
    {
        return Agent::query()->find($id);
    }

    public function findByTelephone($telephone)
    {
        return Agent::query()->where(['Telephone'=>$telephone])->first();
    }

    public function findByUserId($userId)
    {
        return Agent::query()->where(['UserId'=>$userId])->first();
    }

    public function findBySubordinate($userId)
    {
        return Agent::query()
            ->join('agent_third_auth','agent_third_auth.AgentId','=','agents.AgentId')
            ->where('agent_third_auth.UserId','=',$userId)
            ->first();
    }

    public function modifyProfile(Agent $agent)
    {
        return Agent::query()
            ->where('AgentId','=',$agent->AgentId)
            ->update([
                'RealName'=>$agent->RealName,
                'WechatCode'=>$agent->WechatCode,
                'QQCode'=>$agent->QQCode,
                'Address'=>$agent->Address,
                'Email'=>$agent->Email
            ]);
    }

    public function create(Agent $agent)
    {
        $agent->save();
    }

    public function updateWithdrawCredit($agentId, $creditCode, $creditBank, $name)
    {
        return Agent::where('AgentId',$agentId)
            ->update([
                'CreditCode'=>$creditCode,
                'CreditBank'=>$creditBank,
                'CreditName'=>$name
            ]);
    }

    public function updateWithdrawAlipay($agentId,$alipayCode,$alipayName)
    {
        return Agent::where('AgentId',$agentId)
            ->update([
                'AlipayCode'=>$alipayCode,
                'AlipayName'=>$alipayName
            ]);
    }

    public function updateWithdrawWechat($agentId,$wechatCode)
    {
        return Agent::where('AgentId',$agentId)
            ->update([
                'WithdrawWechatCode'=>$wechatCode
            ]);
    }

    public function bindTelephone($agentId,$telephone)
    {
        return Agent::where('AgentId',$agentId)
            ->update([
                'Telephone'=>$telephone
            ]);
    }

    public function getSubordinateAgent($agentId,$offset,$count)
    {
        return Agent::where('ParentId',$agentId)
            ->orderBy('CreateAt','desc')
            ->skip($offset)
            ->take($count)
            ->get();
    }

    public function getSubordinateUser($agentId,$offset,$count)
    {
        return DB::table('agent_third_auth')->where(['AgentId'=>$agentId])
                    ->orderBy('CreateTime','desc')
                    ->skip($offset)
                    ->take($count)
                    ->get();
    }

    public function getSubordinateUserByAgentAndUserId($agentId,$userId)
    {
        return User::query()->where(['AgentId'=>$agentId,'UserId'=>$userId])->first();
    }

    /**
    * 下级代理总数
    */
    public function getSubordinateAgentCount($agentId)
    {
        return DB::table('agents')->where('ParentId',$agentId)
            ->count();
    }

     /**
     * 下级玩家总数
     */
    public function getSubordinateUserCount($agentId)
    {
        // return User::where('AgentId',$agentId)
        //     ->count();
        return DB::table('agent_third_auth')->where('AgentId',$agentId)
            ->count();
    }

    public function getSubordinateUserAtTodayCount($agentId)
    {
        // return User::query()
        //     ->where('AgentId','=',$agentId)
        //     ->whereDate('BindAgentTime','=',date('Y-m-d'))
        //     ->count();
        return DB::table('agent_third_auth')
            ->where('AgentId','=',$agentId)
            ->whereDate('CreateTime','=',date('Y-m-d'))
            ->count();
    }

    /**
     * 获取今天新增的代理总数
     * @param $count
     * @return mixed
     */
    public function getSubordinateUserCountAtToday($agentId)
    {
        return DB::table('agents')->where('ParentId','=',$agentId)
                ->whereDate('CreateAt','=',date('Y-m-d'))
                ->where('status','=',1)
                ->count();

    }

    /**
     * 获取今天新增的下级代理列表
     * @param $token
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function getSubordinateUserAtToday($agentId,$offset,$count)
    {
        // return User::query()
        //     ->select(DB::raw('users.UserId,max(users.NickName) as NickName,max(BindAgentTime) as BindAgentTime,count(distinct TableHistoryId) as CreateNum,count(distinct DetailId) as JoinNum,max(RegisterTime) as RegisterTime'))
        //     ->leftjoin('table_history','users.UserId','=','table_history.Owner')
        //     ->leftjoin('table_history_detail','users.UserId','=','table_history_detail.UserId')
        //     ->where('AgentId','=',$agentId)
        //     ->whereDate('BindAgentTime','=',date('Y-m-d'))
        //     ->groupBy(['UserId'])
        //     ->orderBy('RegisterTime','desc')
        //     ->skip($offset)
        //     ->take($count)
        //     ->get();

        return DB::table('agents')->where('ParentId','=',$agentId)
                ->whereDate('CreateAt','=',date('Y-m-d'))
                ->where('status','=',1)
                ->orderBy('CreateAt','desc')
                ->skip($offset)
                ->take($count)
                ->get();

    }

    public function updateAgentBalance($agentId,$balance,$frozenAmount=null,$commissionAmountFromAgent=null,$commissionAmountFromUser=null)
    {
        $updateFields=[];
        if (!is_null($balance))
        {
            $updateFields['Balance']=$balance;
        }
        if (!is_null($commissionAmountFromAgent))
        {
            $updateFields['CommissionAmountFromAgent']=$commissionAmountFromAgent;
        }
        if (!is_null($commissionAmountFromUser))
        {
            $updateFields['CommissionAmountFromUser']=$commissionAmountFromUser;
        }
        if (!is_null($frozenAmount))
        {
            $updateFields['FrozenAmount']=$frozenAmount;
        }
        return Agent::query()
            ->where('AgentId','=',$agentId)
            ->update($updateFields);
    }

    public function modifyPassword($agentId,$newPassword)
    {
        return Agent::query()->where('AgentId','=',$agentId)->update(['Password'=>$newPassword]);
    }

    public function getConfigUrl($url='')
    {
        if(!$url){
            return null;
        }
        $result=DB::table('url_config')->where('url_sub',$url)->first();
        return $result;
    }
}