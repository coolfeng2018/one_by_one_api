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


class GetcfglistController extends Controller{    

    /**
     * 获取公告
     * @return unknown
     */
    public function getTips() {
        $campaign =  DB::table('campaign')->select(['CampaignId as Id','Title','Tag','Description','ImageUrl','Action','ActionType',DB::raw("2 as Type")])
        ->whereRaw('(Status = 0 and StartTime < NOW() and EndTime > NOW() )');
        //->get();
        $announcement =  DB::table('announcement')->select(['AnnouncementId as Id','Title','Tag','Description','ImageUrl','Action','ActionType',DB::raw("1 as Type")])
        ->whereRaw('(Status = 0 and StartTime < NOW() and EndTime > NOW() )')
        ->union($campaign)->get();       
        foreach ($announcement as $k =>$v) {
            if(isset($v->ImageUrl) && !preg_match("/^http+/", $v->ImageUrl)){
                $v->ImageUrl = 'http://resource.musky880.com:8080/'.$v->ImageUrl;;
            }
        }
        return response()->json(['status'=>200,'msg'=>'OK','data'=>['tips'=>$announcement]]);
        
    }
    
    /**
     * 获取广告
     */
    public function getAdvert(){
        
        $cid = request()->header('Client-ChannelId');
        
        $advert = array(
                array(
                    'id'   => 1,
                    'type' => 1,
                    'url'  => '',
                    'image_url' => 'http://res.lixuanjie.com/advert/new_lobby_ad_bg.png',
                ),
                array(
                    'id'   => 2,
                    'type' => 2,
                    'url'  => 'd.lixuanjie.com',
                    'image_url' => 'http://res.lixuanjie.com/advert/new_lobby_ad_bg2.png',
                ),

        );
        
        if($cid == 'daili01'){
            $advert = array(
                array(
                    'id'   => 1,
                    'type' => 1,
                    'url'  => '',
                    'image_url' => 'http://res.lixuanjie.com/advert/daili01_qrcode.png',
                ),
                array(
                    'id'   => 2,
                    'type' => 2,
                    'url'  => 'd.lixuanjie.com',
                    'image_url' => 'http://res.lixuanjie.com/advert/new_lobby_ad_bg2.png',
                ),
            
            );
        }
        
        return response()->json(['code'=>200,'msg'=>'OK','data'=>['advert'=>$advert]]);
    }
}
