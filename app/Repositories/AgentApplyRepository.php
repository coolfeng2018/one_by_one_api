<?php
/**
 * Created by PhpStorm.
 * User: legendx
 * Date: 2017/11/26
 * Time: 18:15
 */

namespace App\Repositories;


use App\Models\AgentApply;

class AgentApplyRepository
{
//    public function save($telephone,$userId,$uploadFileUrl,$members,$description)
//    {
//        return AgentApply::query()->insert(['UserId'=>$userId,'Phone'=>$telephone,'Img'=>$uploadFileUrl,'Group'=>$members,'Reason'=>$description]);
//    }
    public function save($telephone,$userId,$description)
    {
        return AgentApply::query()->insert(['UserId'=>$userId,'Phone'=>$telephone,'Reason'=>$description]);
    }
}