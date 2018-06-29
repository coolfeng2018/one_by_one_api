<?php

namespace App\Http\Controllers;

use App\Repositories\AgentRepository;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;

class SubordinateController extends Controller
{
    private $agentRepository;

    public function __construct(AgentRepository $agentRepository)
    {
        $this->agentRepository=$agentRepository;
    }

    /**
     * 获取下级用户房卡数量，现在需要在服务端获取
     * @param $userId
     * @return mixed
     */
    public function getSubordinateRoomCard($userId)
    {
        $gameUserInfo=$this->getAgentGameUserInfo($userId);
        if ($gameUserInfo)
        {
            return response()->result(200,'OK',[
                'RoomCardNumber'=>$gameUserInfo->RoomCardNum
            ]);
        }
        else
        {
            return response()->result(500,'error');
        }
    }

    /**
     * 获取下级用户及代理数量
     * @param $token
     * @return mixed
     */
    public function getSubordinateCount($token)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            //下級玩家总数
            $subordinateUserCount=$this->agentRepository->getSubordinateUserCount($agent->AgentId);
            //下级代理总数
            $subordinateAgentCount=$this->agentRepository->getSubordinateAgentCount($agent->AgentId);
            return response()->result(200,'OK',[
                'SubordinateUserCount'=>$subordinateUserCount,
                'SubordinateAgentCount'=>$subordinateAgentCount
            ]);
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    /**
     * 获取下级用户列表
     * @param $token
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function getSubordinateUser($token,$offset,$count)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            $subordinateUser=$this->agentRepository->getSubordinateUser($agent->AgentId,$offset,$count);
            if ($subordinateUser)
            {
                //参数映射
                foreach ($subordinateUser as $key => $value) {
                    $subordinateUser[$key]->BindAgentTime = $value->CreateTime;
                    $subordinateUser[$key]->CreateAt = $value->CreateTime;
                    $subordinateUser[$key]->CreateNum = 0;
                    $subordinateUser[$key]->JoinNum = 0;
                    $subordinateUser[$key]->NickName = $value->Nickname;
                    $subordinateUser[$key]->RegisterTime = $value->CreateTime;
                    $subordinateUser[$key]->UserId = $value->UserId; 
                }
                return response()->result(200,'OK',$subordinateUser);
            }
            else
            {
                return response()->result(200,'OK',[]);
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    /**
     * 获取下级代理列表
     * @param $token
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function getSubordinateAgent($token,$offset,$count)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            $subordinateAgent=$this->agentRepository->getSubordinateAgent($agent->AgentId,$offset,$count);
            if ($subordinateAgent)
            {
                return response()->result(200,'OK',$subordinateAgent);
            }
            else
            {
                return response()->result(200,'OK',[]);
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    /**
     * 获取今天新增的下级代理列表
     * @param $token
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function getSubordinateUserAtToday($token,$offset,$count)
    {
        $agent=Redis::get($token);
        if ($agent)
        {
            $agent=json_decode($agent);
            $subordinate=$this->agentRepository->getSubordinateUserAtToday($agent->AgentId,$offset,$count);
            foreach ($subordinate as $key => $value) {
                $userInfo = $this->getUserInfo($value->UserId);
                $subordinate[$key]->NickName = isset($userInfo['NickName']) ? $userInfo['NickName'] : ''; 
            }
            if ($subordinate)
            {
                return response()->result(200,'OK',$subordinate);
            }
            else
            {
                return response()->result(200,'OK',[]);
            }
        }
        else
        {
            return response()->result(404,'Token is invalid');
        }
    }

    /**
    *   获取玩家信息
    */
    protected function getUserInfo($userId='')
    {
        if(!$userId){
            return null;
        }
        //加载接口url
        $urlBase = $this->agentRepository->getConfigUrl('GameUserInfoBase');
        $urlUsers = $this->agentRepository->getConfigUrl('GameUserInfoUsers'); 
        $client=new Client(['verify'=>false]);
        $responseBase = $client->request('POST', $urlBase->url, [
            'form_params' => [
                'uid' => $userId
            ]
        ]);
        $responseUsers = $client->request('POST', $urlUsers->url, [
            'form_params' => [
                'uid' => $userId
            ]
        ]);
        if ($responseBase->getStatusCode()==200&&$responseUsers->getStatusCode()==200)
        {
            $resultBase=$responseBase->getBody()->getContents();
            $resultUsers=$responseUsers->getBody()->getContents();
            
            $resultBase=json_decode($resultBase,true);
            $resultUsers=json_decode($resultUsers,true);
            if($resultBase['code']==-1||$resultUsers['code']==-1){
                return null;
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
            $mapping['Score'] = $resultBase['coins'];
            $mapping['RoomCardNum'] = $resultBase['roomcards'];
            $mapping['diamond'] = $resultBase['gems'];
            return $mapping;
        }
        return null;
    }

    /**
     * 需要向服务端获取下级玩家的房卡（元宝）数量
     * @param $userId
     * @return null
     */
    private function getAgentGameUserInfo($userId)
    {
        //加载接口url
        $urlBase = $this->agentRepository->getConfigUrl('GameUserInfoBase');
        $urlUsers = $this->agentRepository->getConfigUrl('GameUserInfoUsers'); 
        $client=new Client(['verify'=>false]);
        $responseBase = $client->request('POST', $urlBase->url, [
            'form_params' => [
                'uid' => $userId
            ]
        ]);
        $responseUsers = $client->request('POST', $urlUsers->url, [
            'form_params' => [
                'uid' => $userId
            ]
        ]);
        if ($responseBase->getStatusCode()==200&&$responseUsers->getStatusCode()==200)
        {
            $resultBase=$responseBase->getBody()->getContents();
            $resultUsers=$responseUsers->getBody()->getContents();
            
            $resultBase=json_decode($resultBase,true);
            $resultUsers=json_decode($resultUsers,true);
            if($resultBase['code']==-1||$resultUsers['code']==-1){
                return null;
            }
            //base table
            $resultBase=$resultBase['base'];
            //users table
            $resultUsers=$resultUsers['user'];
            //映射
            $mapping['UserId'] = $userId;
            $mapping['Gender'] = $resultUsers['sex'];
            $mapping['NickName'] = $resultUsers['name'];
            $mapping['AvatarUrl'] = $resultUsers['icon'];
            $mapping['Score'] = $resultBase['coins'];
            $mapping['RoomCardNum'] = $resultBase['roomcards'];
            $mapping['diamond'] = $resultBase['gems'];
            $mapping['roundsum'] = $resultBase['rate_of_winning'] ? $resultBase['rate_of_winning'][1]['win_times']+$resultBase['rate_of_winning'][1]['fail_times'] : 0;
            $mapping['winroundsum'] = $resultBase['rate_of_winning'] ? $resultBase['rate_of_winning'][1]['win_times'] : 0;
            $mapping['winning'] = $resultBase['rate_of_winning'] ? sprintf("%.2f",(int)$resultBase['rate_of_winning'][1]['win_times']/(int)$mapping['roundsum']) : 0;
            $mapping['losesum'] = $resultBase['rate_of_winning'] ? $resultBase['rate_of_winning'][1]['fail_times'] : 0;
            return $mapping;
        }
        return null;
    }
}
