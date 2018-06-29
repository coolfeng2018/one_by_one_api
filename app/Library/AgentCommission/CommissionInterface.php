<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/16
 * Time: 11:59
 */

namespace App\Library\AgentCommission;


use App\Models\Agent;

interface CommissionInterface
{
    public function purchase($userId,$number,$orderId,$nickname,$agent);
    public function consume($userId,$number,$game,$nickname,$agent);
}