<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Facades\Redis;

class UpdatefillingAgentThirdAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updatefillingagentthirdauth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update 填充 ThirdId、ThirdUnionId,清理重复数据';

    protected $selectDb = 'mysql_data_online_platform_money';//数据库

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function handle()
    {

    }


}
