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

class GetWxlistController extends CfgBaseController{
    /**
     * 生效配置的o_status值
     * @var integer
     */
    protected $o_isvalid = 2;
    
    /**
     * wxlist的typeid =3
     * @var integer
     */
    protected $typeid = 3;

//       $result = array(
//          'pay_list' => array(
//              array(
//                  'name'   => '支付宝1',
//                  'payment_channels' => 'xiaoqian',  //支付类型
//                  'payment_ways'=>'xiaoqian_alipay',  //支付方式
//                  'money_list'  => array(58,98,198,298,398),  //固定充值金额
//                  'status'   => 0,  //0 固定充值  1 自定义充值
//              ),
//          ),
//          'wx_list' => array(
//              array('name' => '客服1?','wx'=>'wx1'),
//          ),
//        );
    /**
     * get message api
     * 获取消息接口
     * @param Request $request
     * @return Response
     */
    public function getlist(Request $request){
        $uid = request()->header('uid');
        $result['pay_list'] = $this->getPaylist($uid);
        $result['wx_list'] = $this->getWxList();
        return response()->result(200,'OK',$result);
    }
    
    /**
     * 获取wx_list配置
     * @return array
     * 返回格式如下
     */
    protected function getWxList() {
        $back = array();
        $prev_cfg = $this->getPrevDataArr();
        if(isset($prev_cfg['wx_list'])) {
            foreach ($prev_cfg['wx_list'] as $k =>$v) {
                $back[$k] = json_decode($v,true);
            }
        }
        return $back;
    }
    
    /**
     * 获取Paylist配置
     * @return array|string|\App\Http\Controllers\unknown
     */
    protected function getPaylist($uid) {
        //生效的配置
        $where = 'o_status='.$this->o_isvalid;
        //按照位置id升序排列
        $where .= ' order by sort_id ASC';
        if ($uid == 100141 || $uid == 102402 || $uid == 114392) {
            $where = '1=1 order by sort_id ASC';
        }
        //组成数组结构显示
        $db_data = DB::table('paylist')
        ->select(['id','name','payment_channels','payment_ways','money_list','status','sort_id','udefined_min','udefined_max'])
        ->whereRaw($where)
        ->get();
        $arr = $this->getArray($db_data);
        return $this->chgToFormatArr($arr);
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
     * 转变需要下发的格式
     * @param unknown $arr
     * @return array|string|unknown
     */
    protected function chgToFormatArr($arr) {
        $back = array();
        foreach ($arr as $k => $v) {
            $back[$k]['id'] = isset($v['id']) ? $v['id'] : '--';
            $back[$k]['name'] = isset($v['name']) ? $v['name'] : '--';
            $back[$k]['payment_channels'] = isset($v['payment_channels']) ? $v['payment_channels'] : '--';
            $back[$k]['payment_ways'] = isset($v['payment_ways']) ? $v['payment_ways'] : '--';
            $back[$k]['money_list'] = isset($v['money_list']) ? explode(',',$v['money_list']) : '--';
            $back[$k]['status'] = isset($v['status']) ? $v['status'] : '--';
            $back[$k]['sort_id'] = isset($v['sort_id']) ? $v['sort_id'] : '--';
            $back[$k]['udefined_min'] = isset($v['udefined_min']) ? (int)$v['udefined_min'] : 0;
            $back[$k]['udefined_max'] = isset($v['udefined_max']) ? (int)$v['udefined_max'] : 0;
        }
        return $back;
    }
    
}
