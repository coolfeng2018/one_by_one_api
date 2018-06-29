<?php

namespace App\Http\Controllers;

use App\Repositories\AgentRepository;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use DB;

class AuthController extends Controller
{
    private $agentRepository;

    public function __construct(AgentRepository $agentRepository)
    {
        $this->agentRepository=$agentRepository;
    }
    public function login(Request $request)
    {

        $telephone=$request->post('UserName');
        $pwd=$request->post('Password');
        $agent=$this->agentRepository->findByTelephone($telephone);
        if ($agent)
        {
            if($agent->Password==md5($pwd))
            {
                if($agent->Status!=1){
                    return response()->result(402,'account disable');
                }
                if ($preToken=Redis::get('Agent#'.$agent->AgentId))
                {
                    Redis::delete($preToken);
                }
                $token=md5($telephone.rand(10000,99999));
                $gameUserInfo=$this->getAgentGameUserInfo($agent->UserId);
                $v=['token'=>$token,'agent'=>$agent->toArray(),'gameInfo'=>$gameUserInfo];
                Redis::set($token,json_encode($agent->toArray()),7*24*3600);
                Redis::set('Agent#'.$agent->AgentId,$token);
                //start 
                DB::beginTransaction();
                try{
                    DB::connection()->enableQueryLog();
                    $datetime = new \DateTime();
                    $datetime->setTimestamp(time());
                    $reuslt = DB::table('agents')->where('AgentId','=',$agent->AgentId)->update(['LastLoginTime' => $datetime->format('Y-m-d H:i:s')]);
                    if(!$reuslt){
                        throw new \Exception("登录失败");
                    }
                    DB::commit();
                }catch (\Exception $exception){
                    DB::rollBack();
                    error_log(print_r($exception->getTraceAsString(),true));
                    return response()->result(404,'systerm error');
                }
                //end
                return response()->result(200,'OK',$v);
            }
            else
            {
                return response()->result(403,'Password is invalid');
            }
        }
        else
        {
            return response()->result(404,'Agent is not found');
        }
    }

    public function refresh($token)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            $agent=$this->agentRepository->findById($agent->AgentId);
            if ($agent)
            {
                $gameUserInfo=$this->getAgentGameUserInfo($agent->UserId);
                $v=['token'=>$token,'agent'=>$agent->toArray(),'gameInfo'=>$gameUserInfo];
                Redis::set($token,json_encode($agent->toArray()),7*24*3600);
                Redis::set('Agent#'.$agent->AgentId,$token);
                return response()->result(200,'OK',$v);
            }
            else
            {
                return response()->result(500,'Not Found Agent');
            }
        }
        else
        {
            return response()->result(404,'Token is Invalid');
        }
    }

    private function getAgentGameUserInfo($userId)
    {
        $mapping['RoomCardNum'] = 0;
        return $mapping;
        //加载接口url
        $urlBase = $this->agentRepository->getConfigUrl('GameUserInfoBase');
        $urlUsers = $this->agentRepository->getConfigUrl('GameUserInfoUsers'); 
        $client=new Client(['verify'=>false]);
        $responseBase = $client->request('POST', $urlBase->url, [
            'form_params' => [
                'uid' => $userId
            ],
            'connect_timeout' => 0.8
        ]);
        $responseUsers = $client->request('POST', $urlUsers->url, [
            'form_params' => [
                'uid' => $userId
            ],
            'connect_timeout' => 0.8
        ]);
        if ($responseBase->getStatusCode()==200&&$responseUsers->getStatusCode()==200)
        {
            $resultBase=$responseBase->getBody()->getContents();
            $resultUsers=$responseUsers->getBody()->getContents();
            
            $resultBase=json_decode($resultBase,true);
            $resultUsers=json_decode($resultUsers,true);
            if($resultBase['code']==-1||$resultUsers['code']==-1){
                $mapping['RoomCardNum'] = 0;
                return $mapping;
            }
            //base table
            $resultBase=$resultBase['base'];
            //users table
            $resultUsers=$resultUsers['user'];
            //映射
            $mapping['UserId'] = $userId;
            $mapping['Gender'] = isset($resultUsers['sex']) ? $resultUsers['sex'] : 0;
            $mapping['NickName'] = isset($resultUsers['name']) ? $resultUsers['name'] : '';
            $mapping['AvatarUrl'] = isset($resultUsers['icon']) ? $resultUsers['icon'] : '';
            $mapping['Score'] = isset($resultBase['coins']) ? $resultBase['coins'] : 0;
            $mapping['RoomCardNum'] = isset($resultBase['roomcards']) ? $resultBase['roomcards'] : 0;
            // $mapping['diamond'] = isset($resultBase['gems']) ? $resultBase['gems'];
            // $mapping['roundsum'] = $resultBase['rate_of_winning'] ? $resultBase['rate_of_winning'][1]['win_times']+$resultBase['rate_of_winning'][1]['fail_times'] : 0;
            // $mapping['winroundsum'] = $resultBase['rate_of_winning'] ? $resultBase['rate_of_winning'][1]['win_times'] : 0;
            // $mapping['winning'] = $resultBase['rate_of_winning'] ? sprintf("%.2f",(int)$resultBase['rate_of_winning'][1]['win_times']/(int)$mapping['roundsum']) : 0;
            // $mapping['losesum'] = $resultBase['rate_of_winning'] ? $resultBase['rate_of_winning'][1]['fail_times'] : 0;
            return $mapping;
        }
        $mapping['RoomCardNum'] = 0;
        return $mapping;
    }

    /**
    *  @img 图片地址
    *  @return $url 合成图片地址
    */
    public function trunImageHead($token)
    {
        $agent=Redis::get($token);
        if($agent){
            $agent = json_decode($agent);
            $bigImgPath = './img/agent/agent.jpg';
            $qCodePath = $agent->QRCodeUrl;
            $this->changImg($bigImgPath,$qCodePath);
        }
    }

    /**
    *  合成图片
    */
    protected function changImg($bigImgPath,$qCodePath)
    {
        $bigImg = imagecreatefromstring(file_get_contents($bigImgPath));
        $qCodeImg = imagecreatefromstring(file_get_contents($qCodePath));
         
        list($qCodeWidth, $qCodeHight, $qCodeType) = getimagesize($qCodePath);
        // imagecopymerge使用注解
        imagecopymerge($bigImg, $qCodeImg, 630, 710, 0, 0, $qCodeWidth, $qCodeHight, 100);
        list($bigWidth, $bigHight, $bigType) = getimagesize($bigImgPath);
        switch ($bigType) {
            case 1: //gif
                header('Content-Type:image/gif');
                imagegif($bigImg);
                break;
            case 2: //jpg
                header('Content-Type:image/jpg');
                imagejpeg($bigImg);
                break;
            case 3: //jpg
                header('Content-Type:image/png');
                imagepng($bigImg);
                break;
            default:
                # code...
                break;
        }
        imagedestroy($bigImg);
        imagedestroy($qcodeImg);
    }
}
