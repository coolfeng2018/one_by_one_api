<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/15
 * Time: 15:02
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ThirdAuth extends Model
{
    protected $primaryKey='ThirdAuthId';
    protected $table='agent_third_auth';
    public $timestamps = false;
}