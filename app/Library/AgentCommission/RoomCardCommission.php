<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/17
 * Time: 11:24
 */

namespace App\Library\AgentCommission;


use App\Models\Agent;
use App\Models\Commission;

class RoomCardCommission extends AbstractCommission
{
    private $commissionRatioArr;

    public function __construct($exchangeRate,$superiorCommission,$commissionRatioArr)
    {
        $this->superiorCommissionRatio=$superiorCommission;
        $this->commissionRatioArr=$commissionRatioArr;
        $this->exchangeRate=$exchangeRate;
    }

    public function purchase($userId, $number, $orderId, $nickname,$agent)
    {
        $commission=new Commission();
        $commission->CommissionType=0;
        $commission->CommissionCurrencyType=1;
        $commission->AgentId=$agent->AgentId;
        $commission->SourceUserId=$userId;
        $commission->Number=$number;
        $commission->SourceUserName=$nickname;
        $commission->Amount=$number/$this->exchangeRate;
        $commission->Ratio=$this->commissionRatioArr[$agent->Level-1];
        $commission->CommissionAmount=$commission->Amount*(1-0.012)*$commission->Ratio;
        return $commission;
    }

    public function consume($userId, $number, $game, $nickname,$agent)
    {
        // TODO: Implement consume() method.
    }
}