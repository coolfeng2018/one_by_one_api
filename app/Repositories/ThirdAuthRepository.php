<?php
/**
 * Created by PhpStorm.
 * User: LegendX
 * Date: 2018/1/15
 * Time: 15:01
 */

namespace App\Repositories;


use App\Models\ThirdAuth;

class ThirdAuthRepository
{
    public function createThirdAuth(ThirdAuth $thirdAuth)
    {
        return $thirdAuth->save();
    }

    public function findThirdAuthByUnionId($unionId)
    {
        return ThirdAuth::query()->where(['ThirdUnionId'=>$unionId])->first();
    }
}