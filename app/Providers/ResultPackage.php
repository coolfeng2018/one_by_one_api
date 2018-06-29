<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\ResponseFactory;

class ResultPackage extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     * @param $response
     * @return void
     */
    public function boot(ResponseFactory $response)
    {
        $response->macro('result', function ($status,$msg,$result=null) {
            return response()->json(array('status'=>$status,'msg'=>$msg,'result'=>$result));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}