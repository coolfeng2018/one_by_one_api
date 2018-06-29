<?php
namespace App\Repositories;


use App\Models\Agent;
use App\Models\User;
use App\Models\ThirdAuth;
use Illuminate\Support\Facades\DB;

class OrderRepository {
    
    public function __construct(){}
    
    public function createSign($data, $key) {
        $signPars = "";
        ksort($data);
        foreach($data as $k => $v) {
                if("" != $v && "sign" != $k) {
                        $signPars .= $k . "=" . $v . "&";
                }
        }
        $signPars = substr($signPars,0,count($signPars)-2);
        $signPars .= $key;
        $sign = strtolower(md5($signPars));
        return $sign;
    }
}