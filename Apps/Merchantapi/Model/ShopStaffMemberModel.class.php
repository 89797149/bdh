<?php
/**
 * 门店职员
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-20
 * Time: 18:47
 */

namespace Merchantapi\Model;


use App\Modules\ShopStaffMember\ShopStaffMemberModule;

class ShopStaffMemberModel extends BaseModel
{
    /**
     * 门店职员-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string keywords 关键字(姓名/手机号)
     * -int status 状态(0:正常 -2:禁用)
     * -int use_page 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getShopStaffMemberList(array $paramsInput)
    {
        $module = new ShopStaffMemberModule();
        $result = $module->getShopStaffMemberList($paramsInput);
        return returnData($result);
    }
}