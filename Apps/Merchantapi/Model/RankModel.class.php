<?php
/**
 * 身份
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-16
 * Time: 14:36
 */

namespace Merchantapi\Model;


use App\Modules\Rank\RankModule;

class RankModel extends BaseModel
{
    /**
     * 身份-身份列表
     * @return array
     * */
    public function getRankList()
    {
        $rankModule = new RankModule();
        $res = $rankModule->getRankList(array(), 'rankId,rankName');
        return $res;
    }
}