<?php

namespace App\Console\Commands;

use DB;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddMoneyRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:addmoneyrecord';

    /**
     * The console command description.
     *
     * @var string
     */
    
    protected $addMoneyAgentRecord = [];//代理流水

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        echo 'start...';
        parent::__construct();
        $this->addMoneyAgentRecord = DB::table('agent_bonus')->where('status','=',0)->get();
    }

    /**
     * Execute the console command.
     * 代理流水记录
     * 
     * @return mixed
     */
    
    public function handle()
    { 
        if(!$this->addMoneyAgentRecord){
            return;
        }
        echo 'loding...';
        foreach ($this->addMoneyAgentRecord as $key => $value) {
            $agents = [];
            $agents = DB::table('agents')->where('AgentId','=',$value->agent_id)->first();
            if(!$agents){
                log::info('代理ID: '.$value->agent_id.' 不存在!');
                continue;
            }
            $agentThirdAuth = DB::table('agent_third_auth')->where('UserId','=',$value->uid)->first();
            DB::beginTransaction();
            try{
                //更新状态
                $update = [];
                $update['status'] = 1;
                DB::connection()->enableQueryLog();
                DB::table('agent_bonus')->where('agent_id','=',$value->agent_id)->update($update);

                //金额变动记录
                $insert = [];
                $insert['incr_id'] = $value->id;
                $insert['AgentId'] = $value->agent_id;
                $insert['from_balance'] = $agents->Balance;
                $insert['to_balance'] = ($value->bonus)/1000 + $agents->Balance;
                $insert['amount'] = ($value->bonus)/1000;
                $insert['ratio'] = $agents->Ratio;
                $insert['source_user_id'] = $value->uid;
                if($agentThirdAuth->AgentId==$value->agent_id){
                    $insert['message'] = '代理会员返水:'.($value->bonus)/1000;
                    $insert['source_type'] = 1;
                }else{
                    $insert['message'] = '下级代理返水:'.($value->bonus)/1000;
                    $insert['source_type'] = 2;
                }
                DB::table('agent_blance_log')->insert($insert);
                
                //添加金额
                $updateAgents = [];
                $updateAgents['Balance'] = ($value->bonus)/1000 + $agents->Balance;
                $where = [];
                $where['AgentId'] = $value->agent_id;
                $where['Balance'] = $agents->Balance;
                DB::table('agents')->where($where)->update($updateAgents);
                DB::connection()->enableQueryLog();
                DB::commit();
            }catch(\Exception $e){
                DB::rollBack();
                log::info(DB::getQueryLog());
                log::info($e->getTraceAsString());
            }
        }
        echo 'end';
    }


   
}
