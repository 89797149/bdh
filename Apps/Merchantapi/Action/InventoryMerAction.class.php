<?php

namespace Merchantapi\Action;

use App\Modules\Inventory\InventoryLossModule;
use App\Modules\Inventory\InventoryServiceModule;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Inventory\LocationModule;
use function App\Util\responseError;
use function App\Util\responseSuccess;
use Merchantapi\Model\InventoryMerModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 新开一个文件专门用来写后台的接口,以前是移动端接口和后台接口都写在同一个文件,太恶心
 * 管理端-盘点
 */
class InventoryMerAction extends BaseAction
{
    /**
     * 盘点库存-获取盘点记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yp7teb
     * @param string token
     * @param string inventory_user_name 盘点人员姓名
     * @param string confirm_user_name 确认人员姓名
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param string goodsName 商品名
     * @param string bill_no 盘点单号
     * @param int confirm_status 处理状态(0:未确认 1:已确认)
     * @param int page 页码
     * @param int pageSize 分页条数
     * @return json
     * */
    public function getInventoryBillList()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $params = I();
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $params['shop_id'] = $shop_id;
        $mod = new InventoryMerModel();
        $result = $mod->getInventoryBillList($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点库存-获取盘点记录详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vk7cy8
     * @param string token
     * @param int bill_id 盘点单id
     * @return json
     * */
    public function getInventoryBillDetail()
    {
        $this->MemberVeri();
        $bill_id = (int)I('bill_id');
        if (empty($bill_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryServiceModule();
        $field = 'bill_id,bill_no,total_goods_num,total_profit_loss,inventory_user_name,inventory_time,remark,confirm_status,confirm_user_name,confirm_time,create_time';
        $result = $mod->getInventoryBillDetail($bill_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点库存-确认完成盘点
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dix0nf
     * @param string token
     * @param int bill_id 盘点单id
     * @return json
     * */
    public function completeInventoryBill()
    {
        $login_info = $this->MemberVeri();
        $bill_id = (int)I('bill_id');
        if (empty($bill_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryMerModel();
        $result = $mod->completeInventoryBill($bill_id, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 获取货位列表-树状
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yt4q6g
     * @param string token
     * @return json
     * */
    public function getLocationListTree()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $mod = new LocationModule();
        $field = 'lid,name';
        $result = $mod->getLocationListTree($shop_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 报损-报损记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xxyieg
     * @param string token
     * @param string inventory_user_name 报损人员姓名
     * @param string keyword 关键字(商品名称,编码)
     * @param int confirm_status 确认状态(0:未确认 1:已确认)
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int one_lid 一级货位id
     * @param int two_lid 二级货位id
     * @param int page
     * @param int pageSize
     * @return json
     * */
    public function getLossList()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $params = I();
        $params['shop_id'] = $shop_id;
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $mod = new InventoryMerModel();
        $result = $mod->getLossList($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 报损-报损记录详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/aevvym
     * @param int loss_id 报损id
     * @return json
     * */
    public function getLossInfo()
    {
        $this->MemberVeri();
        $loss_id = (int)I('loss_id');
        if (empty($loss_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryLossModule();
        $field = 'loss_id,goods_id,sku_id,sku_spec_attr,code,one_lid,two_lid,loss_num,loss_reason,remark,loss_pic,inventory_user_name,confirm_status,confirm_user_name,confirm_time,create_time';
        $result = $mod->getLossInfoById($loss_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 报损-确认报损
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xmit92
     * @param string token
     * @param int loss_id 报损id
     * @return json
     * */
    public function completeLoss()
    {
        $login_info = $this->MemberVeri();
        $loss_id = (int)I('loss_id');
        if (empty($loss_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryMerModel();
        $result = $mod->completeLoss($loss_id, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 报损-删除报损
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qi8l5f
     * @param string token
     * @param int loss_id 报损id,多个用英文逗号分隔
     * @return json
     * */
    public function deleteLoss()
    {
        $this->MemberVeri();
        $loss_id = rtrim(I('loss_id'), ',');
        if (empty($loss_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryServiceModule();
        $result = $mod->deleteLoss($loss_id);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }
}