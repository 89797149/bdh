<?php
/**
 * 身份等级
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-16
 * Time: 14:34
 */

namespace Merchantapi\Action;


use Merchantapi\Model\RankModel;

class RankAction extends BaseAction
{
    /**
     * 身份-身份列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hoywu6
     * */
    public function getRankList()
    {
        $this->MemberVeri();
        $mod = new RankModel();
        $res = $mod->getRankList();
        $this->ajaxReturn(returnData($res));
    }
}