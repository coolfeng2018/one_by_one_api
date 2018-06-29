<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/20
 * Time: 17:29
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $primaryKey='AgentCommissionId';
    protected $table='agent_commission';
    public $timestamps = false;
}