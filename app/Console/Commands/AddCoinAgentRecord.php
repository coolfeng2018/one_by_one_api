<?php

namespace App\Console\Commands;

use DB;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Console\Command;

class AddCoinAgentRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:addcoinagentrecord';

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
     * Execute the console command.
     * 充值记录
     * 
     * @return mixed
     */
    public function handle()
    { 
        
    }
}
