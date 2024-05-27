<?php
/**
 * 线路
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 15:36
 */

namespace Merchantapi\Action;


use Merchantapi\Model\LineModel;

class LineAction extends BaseAction
{
    /**
     * 线路-线路列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qyx3tr
     * */
    public function getLineList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $mod = new LineModel();
        $result = $mod->getLineList($shopId);
        $this->ajaxReturn(returnData($result));
    }
}