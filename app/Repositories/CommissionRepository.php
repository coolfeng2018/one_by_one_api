<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/21
 * Time: 17:18
 */

namespace App\Repositories;

use DB;
use App\Models\Commission;

class CommissionRepository
{
    public function createCommission(Commission $commission)
    {
        return $commission->save();
    }

    public function commissionQuery($agentId,$userId,$searchAgentId,$startTime,$endTime,$offset,$count)
    {
        $query=DB::table('agent_blance_log');
        $query=$query->where('AgentId','=',$agentId);
        if ($userId)
        {
            $query=$query->where('source_user_id','=',$userId);
        }
        if ($searchAgentId)
        {
            if($userId){
                $query=$query->orwhere('source_user_id','=',$searchAgentId);
            }else{
                $query=$query->where('source_user_id','=',$searchAgentId);
            }
        }
        if ($startTime)
        {
            $query=$query->where('time','>=',$startTime);
        }
        if ($endTime)
        {
            $query=$query->where('time','<=',$endTime);
        }
        return $query->orderByDesc('time')->skip($offset)->take($count)->get();
    }

    public function commissionQueryCount($agentId,$userId,$startTime,$endTime)
    {
        $query=DB::table('agent_blance_log');
        $query=$query->where('AgentId','=',$agentId);
        if ($userId)
        {
            $query=$query->where('AgentId','=',$userId);
        }
        if ($startTime)
        {
            $query=$query->where('time','>=',$startTime);
        }
        if ($endTime)
        {
            $query=$query->where('time','<=',$endTime);
        }
        return $query->count();
    }

    /**
    *
    *   今日提成
    *   查找当月表，根据日期统计出当日的提成收入
    *   查询agent_blance_log 记得加入时间
    *   查询agent_commission 考虑是否加入
    */
    public function commissionSumAtToday($agentId)
    {
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        return  DB::table('agent_blance_log')
            ->whereBetween('time', [$beginToday, $endToday])
            ->where('AgentId','=',$agentId)
            ->where('type','=',1)
            ->sum('amount'); 
    }

    /**
     * 判断表是否存在
     * 
     * @return mixed
     */
    public function selectTable($select='mysql_data_center_pay',$table)
    {
        $results = false;
        $tables = array_map('reset', \DB::connection($select)->select('SHOW TABLES'));//获取所有表
        foreach ($tables as $key => $value) {
            if($value==$table){
                $results = true;
            }
        }  
        return $results;
    }

    public function getMonth($sign="0",$timestring="")  
    {
        //得到系统的年月  
        $tmp_date=date("Ym");
        if($timestring){
            $tmp_date=date('Ym',$timestring);
        }  
        //切割出年份  
        $tmp_year=substr($tmp_date,0,4);  
        //切割出月份  
        $tmp_mon =substr($tmp_date,4,2);  
        $tmp_nextmonth=mktime(0,0,0,$tmp_mon+1,1,$tmp_year);
        $tmp_forwardmonth=mktime(0,0,0,$tmp_mon-1,1,$tmp_year);
        $tmp_nowmonth=mktime(0,0,0,$tmp_mon,1,$tmp_year);
        if($sign==1){
            //得到当前月的下一个月
            return $fm_next_month=date("Ym",$tmp_nextmonth);
        }elseif($sign==-1){
            //得到当前月的上一个月   
            return $fm_forward_month=date("Ym",$tmp_forwardmonth);   
        }else{
            return $fm_now_month=date("Ym",$tmp_nowmonth);
        }
    }

    public function getDate($timestring="")  
    {
        //得到系统的日期  
        if($timestring){
            $tmp_date=date("Ymd",$timestring);
        }else{
            $tmp_date=date("Ymd");
        }
        return $tmp_date;
    }

    /*
    * 下级会员提成
    */
    public function commissionSumFromUser($agentId, $userId, $startTime, $endTime, $type)
    {
        return $this->commissionSum($agentId,1,$userId,$startTime,$endTime,$type);
    }

    /*
    * 下级代理提成
    */
    public function commissionSumFromAgent($agentId, $userId, $startTime, $endTime, $type)
    {
        return $this->commissionSum($agentId,2,$userId,$startTime,$endTime,$type);
    }

    private function commissionSum($agentId,$sourceType,$userId,$startTime,$endTime,$type)
    {
        $query=DB::table('agent_blance_log')
            ->where('source_type','=',$sourceType)
            ->where('type','=',$type)
            ->where('AgentId',$agentId);
        if ($userId)
        {
            $query=$query->where('source_user_id','=',$userId);
        }
        if ($startTime)
        {
            $query=$query->where('time','>=',$startTime);
        }
        if ($endTime)
        {
            $query=$query->where('time','<=',$endTime);
        }
        return $query->sum('amount');
    }
}