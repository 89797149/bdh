<?php
/**
 * 配送区域
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 15:36
 */

namespace Merchantapi\Action;

use Merchantapi\Model\RegionModel;

class RegionAction extends BaseAction
{
    /**
     * 区域-区域列表
     * 文档链接地址:
     * */
    public function getRegionList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $mod = new RegionModel();
        $result = $mod->getRegionList($shopId);
        $this->ajaxReturn(returnData($result));
    }
}