<?php
/**
 * 司机
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 15:36
 */

namespace Merchantapi\Action;


use Merchantapi\Model\DriverModel;

class DriverAction extends BaseAction
{
    /**
     * 司机-司机列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ffmc7x
     * */
    public function getDriverList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $mod = new DriverModel();
        $result = $mod->getDriverList($shopId);
        $this->ajaxReturn(returnData($result));
    }
}