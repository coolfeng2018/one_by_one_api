<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/16
 * Time: 16:12
 */

namespace App\Library\AgentCommission;


use App\Models\Commission;

abstract class AbstractCommission implements CommissionInterface
{
    protected $exchangeRate;
    protected $superiorCommissionRatio;

    public function superiorCommission($superiorId,$amount,$agentId,$sourceCommissionId,$sourceNickName):Commission
    {
        $commission=new Commission();
        $commission->CommissionType=1;
        $commission->CommissionCurrencyType=0;
        $commission->AgentId=$superiorId;
        $commission->SourceOrderId=$sourceCommissionId;
        $commission->SourceUserId=$agentId;
        $commission->Number=0;
        $commission->SourceUserName=$sourceNickName;
        $commission->Amount=$amount;
        $commission->CommissionAmount=$amount-$amount*$this->superiorCommissionRatio;
        return $commission;
    }
}