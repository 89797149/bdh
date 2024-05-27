<?php
/**
 * 门店职员
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-20
 * Time: 18:45
 */

namespace Merchantapi\Action;


use Merchantapi\Model\ShopStaffMemberModel;

class ShopStaffMemberAction extends BaseAction
{
    /**
     * 门店职员-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tw8uvd
     * */
    public function getShopStaffMemberList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'keywords' => '',
            'status' => '',
            'use_page' => 1,//是否使用分页(0:否 1:是)
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new ShopStaffMemberModel();
        $result = $mod->getShopStaffMemberList($paramsInput);
        $this->ajaxReturn($result);
    }
}