<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class CfgBaseController extends ApiBaseController{
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
     * 配置
     * @var array
     */
    protected $cfg_obj = '';
    
    /**
     * 查询的表名
     * @var string
     */
    protected $tabalename = 'gamecfg';
    
    public function __construct(){
        parent::__construct();
        $this->cfg_obj = $this->getDataByDb();     
    }

    /**
     * 转换成数组
     * @param unknown $data
     * @return array[]
     */
    protected function getArray($data) {
        $back = array();
        if(is_object($data)){
            $data = $data->toArray();
            foreach ($data as $k =>$v) {
                $back[$k]=(array)$v;
            }
        }
        return $back;
    }
    
    
    /**
     * 将，分割的字符串变为数组
     * @param unknown $str
     * @return array
     */
    protected function chgStrToArr($str) {
        $back = array();
        $back = explode(',', $str);
        return $back;
    }
    
    /**
     * 将key拆分成数组
     * @param array $array
     * @return array:
     */
    private function _easyToComplex($array){
        $data = array();
        if(!empty($array)) {
            foreach($array as $k=>$val) {
                $str = '';
                $key = explode('>', $k);
                foreach($key as $ke=>$v) {
                    $str .= '[\''.$v.'\']';
                }
                eval("\$data".$str.'=\''.$val.'\';');
            }
        }
        return $data;
    }
    
    /**
     * 获取生效的配置信息 并组成数组
     * @param unknown $typeid
     * @return array();
     */
    protected function changeToArr($all_cfg) {
        $arr = array();
        //过滤查询有效配置信息
        foreach($all_cfg as $k => $v) {
            if(isset($v->key_col) && isset($v->val_col)) {
                $arr[$v->key_col] = $v->val_col;
            }
        }
        return $arr;
    }
    
    /**
     * 将DB中的数据组成数组
     * @return array();
     */
    protected function getPrevDataArr() {
        $data_arr = $this->changeToArr($this->cfg_obj);
        return $this->_easyToComplex($data_arr);
    }
    
    /**
     * 从db中获取需要的数据
     */
    protected function getDataByDb() {
        $where['typeid'] = $this->typeid;//1 是iplist
        $where['o_status'] = $this->o_isvalid ==2 ? 2 : $this->o_status;//是否是全部生效的  如果不是再读取
        return DB::table($this->tabalename)->where($where)->get();
    }
    
    
    
 
    
    

}
