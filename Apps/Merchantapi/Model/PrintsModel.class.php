<?php

namespace Merchantapi\Model;

use App\Modules\Shops\ShopsModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 小票机类
 */
class PrintsModel extends BaseModel
{

    /**
     * 新增小票机
     * @param array $requestParams <p>
     * int shopId 门店id
     * string equipmentNumber 小票机编号
     * string secretKey 小票机秘钥
     * int isDefault 是否默认【0:否|1:默认】
     * string remark 备注
     * int number 打印张数
     * </p>
     * @return bool $data
     */
    public function addPrints(array $requestParams)
    {
        $pritsTab = M('prints');
        $params = [];
        $params['shopId'] = null;
        $params['equipmentNumber'] = null;
        $params['secretKey'] = null;
        $params['isDefault'] = 0;
        $params['remark'] = null;
        $params['number'] = 1;
        parm_filter($params, $requestParams);
        if (empty($params['equipmentNumber'])) {
            return returnData(false, -1, 'error', '小票机编号不能为空');
        }
        if (empty($params['secretKey'])) {
            return returnData(false, -1, 'error', '小票机秘钥不能为空');
        }
        //判断打印机是否可用
        $getPrintsStatus = getPrintsStatus($params['equipmentNumber'], $params['secretKey']);
        if (empty($getPrintsStatus)) {
            return returnData(false, -1, 'error', '小票机编号或秘钥有误');
        }
        $where = [];
        $where['shopId'] = $params['shopId'];
        $where['equipmentNumber'] = $params['equipmentNumber'];
        $printInfo = $this->getPrintsInfo($where);
        if (!empty($printInfo)) {
            return returnData(false, -1, 'error', '小票机编号重复');
        }
        //验证是否存在默认打印机
        if ($params['isDefault'] == 0) {//是否默认【0:否|1:默认】
            $verifyPrintsStatus = $this->verifyPrintsStatus($params['shopId']);
            if ($verifyPrintsStatus['code'] != 0) {
                return $verifyPrintsStatus;
            }
        }
        $params['createTime'] = date('Y-m-d H:i:s');
        $params['updateTime'] = date('Y-m-d H:i:s');
        if ($params['isDefault'] == 1) {
            //只允许存在一个默认小票机
            $where = [];
            $where['shopId'] = $params['shopId'];
            $where['dataFlag'] = 1;
            $save = [];
            $save['isDefault'] = 0;
            $pritsTab->where($where)->save($save);
        }
        $data = $pritsTab->add($params);
        if (!$data) {
            return returnData(false, -1, 'error', '添加失败');
        }
        return returnData(true);
    }

    /**
     * 新增小票机
     * @param array $requestParams <p>
     * int shopId 门店id
     * int printId 小票机id
     * string equipmentNumber 小票机编号
     * string secretKey 小票机秘钥
     * int isDefault 是否默认【0:否|1:默认】
     * string remark 备注
     * int number 打印张数
     * </p>
     * @return bool $data
     */
    public function updatePrints(array $requestParams)
    {
        $pritsTab = M('prints');
        $params = [];
        $params['shopId'] = null;
        $params['printId'] = null;
        $params['equipmentNumber'] = null;
        $params['secretKey'] = null;
        $params['isDefault'] = 0;
        $params['remark'] = null;
        $params['number'] = 1;
        parm_filter($params, $requestParams);
        if (empty($params['equipmentNumber'])) {
            return returnData(false, -1, 'error', '小票机编号不能为空');
        }
        if (empty($params['secretKey'])) {
            return returnData(false, -1, 'error', '小票机秘钥不能为空');
        }
        //判断打印机是否可用
        $getPrintsStatus = getPrintsStatus($params['equipmentNumber'], $params['secretKey']);
        if (empty($getPrintsStatus)) {
            return returnData(false, -1, 'error', '小票机编号或秘钥有误');
        }
        $where = [];
        $where['shopId'] = $params['shopId'];
        $where['equipmentNumber'] = $params['equipmentNumber'];
        $printInfo = $this->getPrintsInfo($where);
        if (!empty($printInfo) && $printInfo['printId'] != $params['printId']) {
            return returnData(false, -1, 'error', '小票机编号重复');
        }
        //验证是否存在默认打印机
        if ($params['isDefault'] == 0) {//是否默认【0:否|1:默认】
            $verifyPrintsStatus = $this->verifyPrintsStatus($params['shopId']);
            if ($verifyPrintsStatus['code'] != 0) {
                return $verifyPrintsStatus;
            }
            if ($verifyPrintsStatus['data']['printId'] == $params['printId']) {
                return returnData(false, -1, 'error', '已开启自动受理功能,请配置默认打印机');
            }
        }
        if ($params['isDefault'] == 1) {
            //只允许存在一个默认小票机
            $where = [];
            $where['shopId'] = $params['shopId'];
            $where['dataFlag'] = 1;
            $save = [];
            $save['isDefault'] = 0;
            $pritsTab->where($where)->save($save);
        }
        $params['createTime'] = date('Y-m-d H:i:s');
        $params['updateTime'] = date('Y-m-d H:i:s');
        $data = $pritsTab->save($params);
        if (!$data) {
            return returnData(false, -1, 'error', '修改失败');
        }
        return returnData(true);
    }

    /**
     * 获取小票机详情
     * @param array $params <p>
     * int printId 小票机id
     * string equipmentNumber 小票机编号
     * </p>
     * @return array $data
     * */
    public function getPrintsInfo(array $params)
    {
        $where = [];
        $where['shopId'] = null;
        $where['dataFlag'] = 1;
        $where['printId'] = null;
        $where['equipmentNumber'] = null;
        parm_filter($where, $params);
        $printsTab = M('prints');
        $data = $printsTab->where($where)->find();
        return (array)$data;
    }

    /**
     * 获取小票机列表
     * @param int $shopId
     * @param string $equipmentNumber 小票机编号
     */
    public function getPrintsList(int $shopId, string $equipmentNumber, $page, $pageSize)
    {
        $where = "shopId={$shopId} and dataFlag=1 ";
        if (!empty($equipmentNumber)) {
            $where .= " and equipmentNumber like '%{$equipmentNumber}%' ";
        }
        $sql = "select * from __PREFIX__prints where $where order by printId desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        return $data;
    }

    /**
     * 删除小票机
     * @param int $shopId
     * @param array $printId 小票机id
     */
    public function delPrints(int $shopId, array $printId)
    {
        $where['shopId'] = $shopId;
        $where['dataFlag'] = 1;
        $where['printId'] = ['IN', $printId];
        $save = [];
        $save['dataFlag'] = -1;
        $save['updateTime'] = date('Y-m-d H:i:s');
        $data = M('prints')
            ->where($where)
            ->save($save);
        if (!$data) {
            return returnData(false, -1, 'error', '删除失败');
        }
        return returnData(true);
    }

    /**
     * 获取默认小票机
     * @param int $shopId
     * @return array $data
     * */
    public function getPrintsDefault(int $shopId)
    {
        $tab = M('prints');
        $where = [];
        $where['shopId'] = $shopId;
        $where['isDefault'] = 1;
        $where['dataFlag'] = 1;
        $data = $tab->where($where)->find();
        return (array)$data;
    }

    /**
     * @param $shopId
     * @return mixed
     * 验证是否存在默认打印机
     */
    public function verifyPrintsStatus($shopId)
    {
        $shopsModule = new ShopsModule();
        $getShopConfig = $shopsModule->getShopConfig($shopId, 'isReceipt');
        $printsInfo = [];
        if ($getShopConfig['data']['isReceipt'] == 1) {
            $getPrintsInfo = $shopsModule->getPrintsList($shopId);
            $isDefaultType = 0;//如果存在打印机判断是否存在默认【0:不存在|1:存在】
            if (!empty($getPrintsInfo)) {
                foreach ($getPrintsInfo as $key => $value) {
                    if ($value['isDefault'] == 1) {//是否默认【0:否|1:默认】
                        $isDefaultType = 1;
                        $printsInfo = $value;
                    }
                }
            }
            if ($isDefaultType == 0) {
                return returnData(false, -1, 'error', '已开启自动受理功能,请配置默认打印机');
            }
        }
        return returnData($printsInfo);
    }
}