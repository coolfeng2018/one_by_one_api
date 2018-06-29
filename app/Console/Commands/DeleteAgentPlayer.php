<?php

namespace App\Console\Commands;

use DB;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Storage;

class DeleteAgentPlayer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:deleteagentplayer';

    /**
     * The console command description.
     *
     * @var string
     */
    
    protected $deleteAgentPlayer = [];//需要删除代理是自己会员的关系记录

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        echo 'start...';
        parent::__construct();
        $where['a.AgentId'] = 'b.AgentId';
        DB::connection()->enableQueryLog();
        $sql = 'SELECT `b`.`ThirdAuthId` FROM `agents` AS `a` INNER JOIN `agent_third_auth` AS `b` ON `b`.`UserId` = `a`.`UserId` WHERE `a`.`AgentId` = `b`.`AgentId`';
        $results = DB::select($sql);
        $this->deleteAgentPlayer = $results;
    }

    /**
     * Execute the console command.
     * 代理流水记录
     * 
     * @return mixed
     */
    
    public function handle()
    {  
        if(!$this->deleteAgentPlayer){
            return;
        }
        echo 'loding...';
        foreach ($this->deleteAgentPlayer as $key => $value) {
            DB::beginTransaction();
            try{
                //更新状态
                $where = [];
                $where['ThirdAuthId'] = $value->ThirdAuthId;
                DB::connection()->enableQueryLog();
                DB::table('agent_third_auth')->where($where)->delete();
                Storage::append('agent_players_delete.log', date('Y-m-d H:i:s',time())." : ".json_encode(DB::getQueryLog()));
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
