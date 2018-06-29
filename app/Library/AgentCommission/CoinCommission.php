<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/17
 * Time: 11:22
 */

namespace App\Library\AgentCommission;


use App\Models\Agent;
use App\Models\Commission;

class CoinCommission extends AbstractCommission
{
    private $commissionRatio;

    public function __construct($exchangeRate,$superiorCommission,$commissionRatio)
    {
        $this->superiorCommissionRatio=$superiorCommission;
        $this->commissionRatio=$commissionRatio;
        $this->exchangeRate=$exchangeRate;
    }

    //购买
    public function purchase($userId, $number, $orderId, $nickname, $agent)
    {
        // TODO: Implement purchase() method.
    }

    //消耗
    public function consume($userId, $number, $game, $nickname,$agent)
    {
        $commission=new Commission();
        $commission->CommissionType=0;
        $commission->CommissionCurrencyType=2;
        $commission->AgentId=$agent->AgentId;
        $commission->SourceUserId=$userId;
        $commission->Game=$game;
        $commission->Number=$number;
        $commission->SourceUserName=$nickname;
        $commission->Amount=$number/$this->exchangeRate;
        $commission->Ratio=$this->commissionRatio;
        $commission->CommissionAmount=$commission->Amount*(1-0.012)*$commission->Ratio;
        return $commission;
    }
}