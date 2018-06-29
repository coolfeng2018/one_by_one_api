<?php

namespace App\Http\Controllers;

use App\Library\AgentCommission\CoinCommission;
use App\Library\AgentCommission\RoomCardCommission;
use App\Models\Commission;
use App\Repositories\AgentRepository;
use App\Repositories\CommissionRepository;
use App\Repositories\UnionRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;

class CommissionController extends Controller
{
    private $commissionRepository;
    private $agentRepository;
    private $unionRepository;

    public function __construct(CommissionRepository $commissionRepository,AgentRepository $agentRepository,UnionRepository $unionRepository)
    {
        $this->commissionRepository=$commissionRepository;
        $this->agentRepository=$agentRepository;
        $this->unionRepository=$unionRepository;
    }

    /**
     * 提成明细查询
     * @param Request $request
     * @param $token
     * @return mixed
     */
    public function queryCommission(Request $request,$token)
    {
        if ($agent=Redis::get($token))
        {
            $agent=json_decode($agent);
            $userId=$request->get('UserId');
            $agentId=$request->get('AgentId');
            $t = strtotime($request->get('StartTime'));
            $e = strtotime($request->get('EndTime'));
            $startTime = $request->get('StartTime') ? mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t)) : '';
            $endTime = $request->get('EndTime') ? mktime(23,59,59,date("m",$e),date("d",$e),date("Y",$e)) : '';
            $offset=$request->get('Offset');
            $count=$request->get('Count');
            $commissions=$this->commissionRepository->commissionQuery($agent->AgentId,$userId,$agentId,$startTime,$endTime,$offset,$count);
            $commissionSumFromUser=$this->commissionRepository->commissionSumFromUser($agent->AgentId,$userId,$startTime,$endTime,$type=1);
            $commissionSumFromAgent=$this->commissionRepository->commissionSumFromAgent($agent->AgentId,$userId,$startTime,$endTime,$type=1);

            $commissionSumCoinFromUser=$this->commissionRepository->commissionSumFromUser($agent->AgentId,$userId,$startTime,$endTime,$type=2);
            $commissionSumCoinFromAgent=$this->commissionRepository->commissionSumFromAgent($agent->AgentId,$userId,$startTime,$endTime,$type=2);
            if ($commissions)
            {
                return response()->result(200,'OK',[
                    'commissions'=>$commissions,
                    'commissionSumFromUser'=>$commissionSumFromUser,//下级会员提成
                    'commissionSumFromAgent'=>$commissionSumFromAgent,//下级代理提成
                    'commissionSumCoinFromUser'=>$commissionSumCoinFromUser,//下级会员提成（金币）
                    'commissionSumCoinFromAgent'=>$commissionSumCoinFromAgent//下级代理提成（金币）
                ]);
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
     * 服务端调用，新增提成接口
     * @param Request $request
     * @return mixed
     */
    public function addCommission(Request $request)
    {
        $commissionType=$request->post('CommissionType');//提成来源类型，1为购买，2为消耗
        $commissionCurrencyType=$request->post('CommissionCurrencyType');
        $sourceUserId=$request->post('UserId');
        $number=$request->post('Number');
        $sourceOrderId=$request->post('OrderId');
        $sourceNickName=$request->post('NickName');
        $sourceGame=$request->post('Game');
        $commissionSetting=json_decode(Redis::connection('setting')->get('AgentCommission'));
        $agent=$this->agentRepository->findBySubordinate($sourceUserId);
        $superiorAgent=$this->agentRepository->findById($agent->ParentId);
        $commission=[];
        if ($commissionCurrencyType==1&&$agent->IsRoomCardAgent==1)
        {
            $commission=new RoomCardCommission($commissionSetting->ExchangeRate->RoomCard,$commissionSetting->SuperiorCommissionRatio->RoomCard[$superiorAgent->Level-1],$commissionSetting->CommissionRatio->RoomCard);
        }
        else if ($commissionCurrencyType==2&&$agent->IsCoinAgent==1)
        {
            $commission=new CoinCommission($commissionSetting->ExchangeRate->Coin,$commissionSetting->superiorCommissionRatio->Coin,$commissionSetting->CommissionRatio->Coin);
        }
        else
        {
            return response()->result(500,'Commission Currency Type is wrong');
        }
        $commissionModel=[];
        if($commissionType==1)
        {
            $commissionModel=$commission->purchase($sourceUserId,$number,$sourceOrderId,$sourceNickName,$agent);
        }
        else if($commissionType==2)
        {
            $commissionModel=$commission->consume($sourceUserId,$number,$sourceGame,$sourceNickName,$agent);
        }
        if($this->commissionRepository->createCommission($commissionModel))
        {
            $commission->superiorCommission($agent->ParentId,$commissionModel->Amount,$agent->AgentId,$commissionModel->AgentCommissionId,$agent->AgentName);
            return response()->result(200,'OK');
        }
        else
        {
            return response()->result(501,'Create Commission Fails');
        }
    }
}
