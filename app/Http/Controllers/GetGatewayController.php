<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\BingdingValidatorRequest;
use App\Http\Requests\GetBingdingValidatorRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GetGatewayController extends CfgBaseController{
    /**
     * 生效配置的o_status值
     * @var integer
     */
    protected $o_isvalid = 2;
    
    /**
     * 网关列表的typeid=1
     * @var integer
     */
    protected $typeid = 1;
    

    /**
     * 获取网关接口
     * @param Request $request
     * @return unknown
     */
    public function getlist(Request $request){
        $ucode = request()->header('Client-Ucode');
        $cid = request()->header('Client-ChannelId');
        $ver = request()->header('Client-VersionId');
        
        $clientip = getIp();
        //配置的memo非空 就是针对不同的ip开放  memo中存放的就是白名单ip用逗号分割
        foreach ($this->cfg_obj as $key =>$val) {   
            if(!empty($val->memo)){
                $arr = explode(';', $val->memo);
                if(!in_array($clientip,$arr)) {
                    unset($this->cfg_obj[$key]);
                }
            }
        }
        $prev_cfg = $this->getPrevDataArr();
        $list_all = $this->getRes($prev_cfg);
        //设置默认返回值
        $gamecfg = $res_all = $list_all['all'];//第一个all是默认的必须配置
        //第1层过滤规则   Ucode
        if(isset($list_all[$ucode])) {
            $gamecfg = $list_all[$ucode] ;
            $res_all = isset($list_all[$ucode]['all']) ? $list_all[$ucode]['all'] : $res_all;
            
            if($cid && !empty($gamecfg)) {
                $gamecfg = $this->_setFilter($gamecfg, $cid,$res_all);//第2层过滤规则   ChannelId
                //var_dump($gamecfg);exit;
                $res_all = isset($list_all[$ucode][$cid]['all']) ? $list_all[$ucode][$cid]['all'] : $res_all;
                if($ver && !empty($gamecfg)) {
                    $gamecfg = $this->_setFilter($gamecfg, $ver,$res_all, true);//第3层过滤规则   VersionId
                }
            }
        }
        if(empty($gamecfg)) {
            $gamecfg = $res_all;
        }
        if(isset($gamecfg['host'])) {
            $back = $gamecfg;
        }else{
            $keys_arr = array_keys($gamecfg);
            $max = count($keys_arr);
            $key_num = rand(0,$max-1);
            $key = $keys_arr[$key_num];
            $back = $gamecfg[$key];
        }
        return response()->json(['status'=>200,'msg'=>'OK','result'=>['gateway_list'=>$back]]);
        
    }
    
    /**
     * 筛选规则
     * @param array $conf
     * @param string $key
     * @return array
     */
    protected function _setFilter($conf, $key, $default, $isend = false) {
        $back = array();
        if(isset($conf[$key])) {
            $back = $conf[$key];
        }
        //过滤规则是否结束，结束了， 没有拿到数据，就给默认配置
        if($isend) {
            $back = !empty($back) ? $back : $default;
        }
        
        return $back;
    }
    
    /**
     * 查看是否是json
     * @param unknown $val
     * @return mixed
     */
    private function getRes($val) {
        if(is_array($val)) {
            foreach ($val as $k =>$v) {
                if(is_array($v)) {
                    $val[$k] =  $this->getRes($v);
                }elseif(is_string($v) && preg_match("/^\{[a-zA-Z0-9\,\: \"\-\.]*\}$/",$v)) {
                    $val[$k] =  json_decode($v,true);
                }
            }
        }elseif(is_string($val) && preg_match("/^\{[a-zA-Z0-9\,\: \"\-\.]*\}$/",$v)) {
            return json_decode($val,true);
        }
        return $val;
    }
    
 
}
