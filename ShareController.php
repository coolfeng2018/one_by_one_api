<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/3
 * Time: 16:29
 */

namespace App\Http\Controllers;


use App\Models\ThirdAuth;
use App\Repositories\AgentRepository;
use App\Repositories\ThirdAuthRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Psr\Http\Message\RequestInterface;
use DB;

class ShareController extends Controller
{
    private $agentRepository;
    private $thirdAuthRepository;

    public function __construct(AgentRepository $agentRepository,ThirdAuthRepository $thirdAuthRepository)
    {
        $this->agentRepository=$agentRepository;
        $this->thirdAuthRepository=$thirdAuthRepository;
    }

    /**
     * 推广二维码入口
     * @param $agent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function share($agent)
    {
        $key=$agent.time().rand(10000,99999);
        Redis::set($key,json_encode(['agent'=>$agent]));
        return response()->redirectTo(config('app.ucUrl').'api/v1/thirdauth/webauth?callback='.urlencode(config('app.partnerUrl').'share/callback?state='.$key));
    }

    /**
     * 第三方授权回调
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function shareCallback(Request $request)
    {
        $state=$request->get('state');
        $params=$request->get('params');
        $stateData=Redis::get($state);
        if ($stateData && $params)
        {
            $stateData = json_decode($stateData);
            $agent=$stateData->agent;
            //有代理关系,加入数据库,已存在代理关系的,不重复加数据
            if($agent && $params){
                $params=json_decode($params); 
                $thirdAuthData = DB::table('agent_third_auth')->where('ThirdUnionId','=',$params->unionid)->first();  
                if(!$thirdAuthData){
                    $thirdAuth=new ThirdAuth();
                    $thirdAuth->ThirdId=$params->openid;
                    $thirdAuth->ThirdUnionId=$params->unionid;
                    $thirdAuth->NickName=$params->nickname;
                    $thirdAuth->AgentId=$agent;
                    $this->thirdAuthRepository->createThirdAuth($thirdAuth);
                }
            }
        }
        return response()->redirectTo('http://app.suit.wang/psz/install.html');
    }
}