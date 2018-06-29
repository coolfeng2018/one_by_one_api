<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/21
 * Time: 20:07
 */

namespace App\Repositories;


use App\Models\Withdraw;

class WithdrawRepository
{
    public function withdraw(Withdraw $withdraw)
    {
        return $withdraw->save();
    }

    public function getWithdraw($agentId,$offset,$count)
    {
        return Withdraw::where('AgentId',$agentId)
            ->orderBy('CreateAt','desc')
            ->skip($offset)
            ->take($count)
            ->get();
    }
}