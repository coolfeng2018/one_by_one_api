<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/28
 * Time: 15:32
 */

namespace App\Repositories;


use App\Models\Union;

class UnionRepository
{
    public function getUnionById($unionId)
    {
        return Union::query()->where('UnionId','=',$unionId)->first();
    }
}