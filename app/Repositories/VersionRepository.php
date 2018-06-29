<?php
namespace App\Repositories;


use App\Models\VersionList;
use App\Models\VersionListv2;
use Illuminate\Support\Facades\DB;

class VersionRepository {
    
    public function __construct(){}
    
    public function getVersionList($version) {
        //$data = VersionList::where("ver_int", '>', $version)->where("release_time", '<', date('Y-m-d H:i:s'))->orderBy('release_time','desc')->get();
        $data = VersionList::where("release_time", '<', date('Y-m-d H:i:s'))->orderBy('release_time','desc')->get();
        return $data;
    }
    
    public function getVersionListv2($version, $platform) {
        switch($platform) {
            case "android":
                $pf = 1;
                break;
            case "ios":
                $pf = 2;
                break;
            case "windows":
                $pf = 3;
                break;
        }
        $data = VersionListv2::where("ver_int", '>=', $version)->where('status', '=', '1')->where("release_time", '<', date('Y-m-d H:i:s'))->where('platform', 'like', '%'.$pf.'%')->orderBy('release_time','desc')->get();
        return $data;
    }
    
    
    public function implodeVer($ver) {
        $verArr = explode('.', $ver);
        $retVal = 0;
        $step = [100000000, 100000];
        if (count($verArr) != 3) {
            return false;
        }
        for ($i=0; $i<3; $i++) {
            if ($i == 2) {
                $retVal += $verArr[$i];
            } else {
                $retVal += $verArr[$i]*$step[$i];
            }
        } 
        return $retVal;
    }
    
    public static function getIp(){
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $cip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $cip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (! empty($_SERVER['REMOTE_ADDR'])) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } else {
            $cip = '';
        }
        return $cip;
    }
    
    public function explodStr($str) {
        $retArr = $tmp = [];
        $list = explode("\r\n", $str);
        foreach ($list as $val) {
            $tmp = explode(",", $val);
            if ( ! is_array($tmp)) {
                continue;
            }
            $retArr = array_merge($retArr, $tmp);
        }
        $retArr = array_map("trim", $retArr);
        return array_unique($retArr);
    }
    
    /**
     * 获取game_id
     * @return type
     */
    public function gameInfo() {
        return [
            'hhdz' => ['game_id' => '200000', 'name' => '红黑大战'],
            'lobby' => ['game_id' => '-1', 'name' => '大厅'],
            'psz' => ['game_id' => '1', 'name' => '拼三张'],
            'kpqz' => ['game_id' => '2', 'name' => '看牌强庄'],
            'brnn' => ['game_id' => '200001', 'name' => '百人牛牛'],
            'ddz' => ['game_id' => '3', 'name' => '斗地主'],
            'update' => ['game_id' => '-1', 'name' => ''],
            'lhdz' => ['game_id' => '200002', 'name' => ''],
            'sgj' => ['game_id' => '200003', 'name' => '水果机'],
            'hjk' => ['game_id' => '5', 'name' => ''],
        ];
    }

}