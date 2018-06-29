<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/21
 * Time: 20:04
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    const CREATED_AT = 'CreateAt';
    const UPDATED_AT = 'UpdateAt';
    protected $primaryKey='AgentWithdrawId';
    protected $table='agent_withdraw';
}