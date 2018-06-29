<?php
namespace App\Http\Controllers;

use App\Repositories\VersionRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class AppController extends Controller
{
    
    public function __construct(VersionRepository $versionRepository){
        $this->versionRepository = $versionRepository;
    }
    
    public function getVersions(Request $request) {
        $version = $request->header('Client-VersionId');
        $channel = $request->header('Client-ChannelId');
        if (empty($version)) {
            $version = trim(@$_GET['my_noisrev']);
        }
        if (empty($channel)) {
            $channel = trim(@$_GET['my_lennahc']);
        }
        if (empty($version) || empty($channel)) {
            return response()->json(['status'=>501,'msg'=>'参数错误']);
        }
        $ver = $this->versionRepository->implodeVer($version);
        $versionList = $this->versionRepository->getVersionList($ver);
        $gameInfo = $this->versionRepository->gameInfo();
        if (empty($versionList)) {
            return response()->json(['status'=>200,'msg'=>'ok','data'=>['hotfix'=>[]]]);
        }
        
        //获取请求ip 
        $cip = $this->versionRepository->getIp();
        //Log::info("cip:".$cip);
        $appupdate = $hotList = $lobbyList = [];
        foreach ($versionList as $verInfo) {
            if ($verInfo['is_public'] != '*') { // 不是所有更新
                $ipList = $this->versionRepository->explodStr($verInfo['is_public']);
                if ( ! in_array($cip, $ipList)) {
                    continue;
                }
            }
            // 已经找齐热更和apk更
            if ( ! empty($appupdate) && ! empty($hotList) && ! empty($lobbyList)) { 
                break;
            }
            if ($verInfo['allow_version'] != '*') { // 不是所有版本允许更新
                $allowVerList = $this->versionRepository->explodStr($verInfo['allow_version']);
                if ( ! in_array($version, $allowVerList)) {
                    continue;
                }
            }
            if ( ! empty($verInfo['deny_version'])) { // 在禁止更新的版本里
                $denyVerList = $this->versionRepository->explodStr($verInfo['deny_version']);
                if (in_array($version, $denyVerList)) {
                    continue;
                }
            }
            if ($verInfo['allow_channel'] != '*') { // 不是所有渠道允许更新
                $allowChannelList = $this->versionRepository->explodStr($verInfo['allow_channel']);
                if ( ! in_array($channel, $allowChannelList)) {
                    continue;
                }
            }
            if ( ! empty($verInfo['deny_channel'])) { // 在禁止更新的渠道里
                $denyChannelList = $this->versionRepository->explodStr($verInfo['deny_channel']);
                if (in_array($channel, $denyChannelList)) {
                    continue;
                }
            }
            
            
            if($verInfo['update_type'] == 0) { // apk更新
                if ( ! empty($appupdate)) { // 已经找到最大apk更新包，不再找apk更
                    continue;
                }
                $appupdate = [
                    'version' => $verInfo['version'],
                    'description' => $verInfo['description'],
                    'is_force' => $verInfo['is_force'],
                    'allowance' => null, // 没用字段，暂时保留
                    'update_url' => $verInfo['apk_update_url'],
                    'release_time' => $verInfo['release_time'],
                    'size' => $verInfo['size'],
                ];
            } elseif ($verInfo['update_type'] == 1) { // 热更
                if ( ! empty($hotList) ||  ! empty($lobbyList)) {
                    continue;
                }
                $hotUpdate = json_decode($verInfo['game_info'], true);
                
                foreach ($hotUpdate as $k => $value) {
                    $value['version'] = $verInfo['version'];
                    $value['release_time'] = $verInfo['release_time'];
                    $value['game_code'] = $value['gameCode'];
                    unset($value['gameCode']);
                    /*
                    if ( ! isset($gameInfo[$value['game_code']]['game_id'])) {
                        return response()->json(['status'=>503,'msg'=>'未知游戏code'.$value['game_code']]);
                    }*/
                    if (isset($gameInfo[$value['game_code']]['game_id'])) {
                        $value['game_id'] = $gameInfo[$value['game_code']]['game_id'];
                    } else {
                        $value['game_id'] = 0;
                    }
                    switch($k) {
                        case "lobby":
                            $lobbyList = $value;
                            break;
                        case "psz":
                        case "hhdz":
                        case "kpqz":
                        case "brnn":
                        case "ddz":
                            $hotList[] = $value;
                            break;
                        default:
                            $hotList[] = $value;
                            break;
                    }
                }
            }
        }
        return response()->json(['status'=>200, 'msg'=>'ok', 'data'=>['appupdate'=>$appupdate, 'hotfix'=>$hotList, 'lobbyhotfix'=>$lobbyList]]);
    }
    
    
    public function getVersionsV2(Request $request) {
        $version = $request->header('Client-VersionId');
        $channel = $request->header('Client-ChannelId');
        $platform = $request->header('Client-Platform');
        if (empty($version)) {
            $version = trim(@$_GET['my_noisrev']);
        }
        if (empty($channel)) {
            $channel = trim(@$_GET['my_lennahc']);
        }
        if (empty($platform)) {
            $platform = trim(@$_GET['mroftalp']);
        }
        if (empty($version) || empty($channel)) {
            return response()->json(['status'=>501,'msg'=>'参数错误']);
        }
        if ( ! in_array($platform, ['android', 'ios', 'windows'])) {
            return response()->json(['status'=>501,'msg'=> '平台有误']);
        }
        $ver = $this->versionRepository->implodeVer($version);
        $versionList = $this->versionRepository->getVersionListv2($ver, $platform);
        $gameInfo = $this->versionRepository->gameInfo();
        if (empty($versionList)) {
            return response()->json(['status'=>200, 'msg'=>'ok', 'data'=>['appupdate'=>[], 
                'hotfix'=>[], 'lobbyhotfix'=>[], 'updatehotfix' => [], 'apiUrl' => [env('PORJECT_ONE_BY_ONE_API')]]]);
        }
        
        //获取请求ip 
        $cip = $this->versionRepository->getIp();
        //Log::info("cip:".$cip);
        $appupdate = $hotList = $lobbyList = $updateFix = [];
        foreach ($versionList as $verInfo) {
            if ($verInfo['is_public'] != '*') { // 不是所有更新
                $ipList = $this->versionRepository->explodStr($verInfo['is_public']);
                if ( ! in_array($cip, $ipList)) {
                    continue;
                }
            }
            // 已经找齐热更和apk更
            if ( ! empty($appupdate) && ! empty($hotList) && ! empty($lobbyList)) { 
                break;
            }
            if ($verInfo['allow_version'] != '*') { // 不是所有版本允许更新
                $allowVerList = $this->versionRepository->explodStr($verInfo['allow_version']);
                if ( ! in_array($version, $allowVerList)) {
                    continue;
                }
            }
            if ( ! empty($verInfo['deny_version'])) { // 在禁止更新的版本里
                $denyVerList = $this->versionRepository->explodStr($verInfo['deny_version']);
                if (in_array($version, $denyVerList)) {
                    continue;
                }
            }
            if ($verInfo['allow_channel'] != '*') { // 不是所有渠道允许更新
                $allowChannelList = $this->versionRepository->explodStr($verInfo['allow_channel']);
                if ( ! in_array($channel, $allowChannelList)) {
                    continue;
                }
            }
            if ( ! empty($verInfo['deny_channel'])) { // 在禁止更新的渠道里
                $denyChannelList = $this->versionRepository->explodStr($verInfo['deny_channel']);
                if (in_array($channel, $denyChannelList)) {
                    continue;
                }
            }
            
            
            if($verInfo['update_type'] == 0) { // 整包更新
                if ( ! empty($appupdate)) { // 已经找到最大apk更新包，不再找apk更
                    continue;
                }
                if (version_compare($version, $verInfo['version'], '>=')) { // 大于等于当前版本，不返回
                    continue;
                }
                $apkUrlList = @json_decode($verInfo['apk_update_url'], true);
                $appupdate = [
                    'version' => $verInfo['version'],
                    'description' => $verInfo['description'],
                    'is_force' => $verInfo['is_force'],
                    'update_url' => isset($apkUrlList[$platform]) ? $apkUrlList[$platform] : "",
                    'release_time' => $verInfo['release_time'],
                    'size' => $verInfo['size'],
                ];
            } elseif ($verInfo['update_type'] == 1) { // 热更
                if ( ! empty($hotList) ||  ! empty($lobbyList)) {
                    continue;
                }
                $hotUpdate = json_decode($verInfo['game_info'], true);
                if (isset($hotUpdate[$platform])) {
                    $hotUpdate = $hotUpdate[$platform];
                }
                
                foreach ($hotUpdate as $k => $value) {
                    $value['version'] = $verInfo['version'];
                    $value['release_time'] = $verInfo['release_time'];
                    $value['game_code'] = $value['gameCode'];
                    unset($value['gameCode']);
                    //if( ! preg_match('/^http/', $value['resources_url'])) {
                    //    $value['resources_url'] = env('HOT_UPDATE_RESOURCE').$value['resources_url'];
                    //}
                    /*
                    if ( ! isset($gameInfo[$value['game_code']]['game_id'])) {
                        return response()->json(['status'=>503,'msg'=>'未知游戏code'.$value['game_code']]);
                    }*/
                    if (isset($gameInfo[$value['game_code']]['game_id'])) {
                        $value['game_id'] = $gameInfo[$value['game_code']]['game_id'];
                    } else {
                        $value['game_id'] = 0;
                    }
                    switch($k) {
                        case "lobby":
                            $lobbyList = $value;
                            break;
                        case "psz":
                        case "hhdz":
                        case "kpqz":
                        case "brnn":
                        case "ddz":
                        case "lhdz":
                        case "sgj":
                        case "hjk":
                            $hotList[] = $value;
                            break;
                        case "update":
                            $updateFix = $value;
                            break;
                    }
                }
            }
        }
        return response()->json(['status'=>200, 'msg'=>'ok', 'data'=>['appupdate'=>$appupdate, 
            'hotfix'=>$hotList, 'lobbyhotfix'=>$lobbyList, 'updatehotfix' => $updateFix, 
            'apiUrl' => [env('PORJECT_ONE_BY_ONE_API')], 'errUploadUrl' => env('ERR_UPLOAD_URL')]]);
    }
    
}