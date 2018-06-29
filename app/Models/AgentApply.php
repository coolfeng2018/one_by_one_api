<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/26
 * Time: 18:15
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class AgentApply extends Model
{
    protected $primaryKey='Id';
    protected $table='apply_agent';
    public $timestamps = false;
}