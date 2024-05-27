<?php
/**
 * 供应商
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-19
 * Time: 16:27
 */

namespace App\Modules\Supplier;


use App\Models\SupplierModel;
use Think\Model;

class SupplierModule
{
    /**
     * 供应商-保存
     * @param array $paramsInput
     * -int shopId 门店id
     * -string supplierName 供应商名称
     * -string linkman 联系人
     * -string linkphone 联系手机
     * -string landlineNumber 电话/座机号
     * -string detailAddress 详细地址
     * -int enableStatus 启用状态(0:禁用 1:启用)
     * -int isDelete 删除状态(0:未删除 1:已删除)
     * @param object $trans
     * @return int
     * */
    public function saveSupplier(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'shopId' => null,
            'supplierName' => null,
            'linkman' => null,
            'linkphone' => null,
            'landlineNumber' => null,
            'detailAddress' => null,
            'enableStatus' => null,
            'isDelete' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = time();
            }
        }
        $model = new SupplierModel();
        if (empty($paramsInput['supplierId'])) {
            $saveParams['createTime'] = $datetime;
            $supplierId = $model->add($saveParams);
            if (!$supplierId) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $supplierId = $paramsInput['supplierId'];
            $where = array(
                'supplierId' => $supplierId
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$supplierId;
    }

    /**
     * 供应商-供应商名称是否已存在
     * @param int $shopId 门店id
     * @param string $supplierName 供应商名称
     * @return int
     * */
    public function isExistSupplierNameId(int $shopId, string $supplierName)
    {
        $model = new SupplierModel();
        $where = array(
            'shopId' => $shopId,
            'supplierName' => $supplierName,
            'isDelete' => 0,
        );
        $result = $model->where($where)->find();
        if (empty($result)) {
            return 0;
        }
        $supplierId = $result['supplierId'];
        return (int)$supplierId;
    }

    /**
     * 供应商-详情-id查找
     * @param int $supplierId 供应商id
     * @param string $field 表字段
     * @return array
     * */
    public function getSupplierDetailById(int $supplierId, $field = '*')
    {
        $model = new SupplierModel();
        $where = array(
            'supplierId' => $supplierId,
            'isDelete' => 0,
        );
        $result = $model->where($where)->field($field)->find();
        return (array)$result;
    }

    /**
     * 供应商-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string keywords 关键字(供应商名称/联系人/联系方式)
     * -int enableStatus 启用状态(0:禁用 1:启用)
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 是否导出(0:否 1:是)
     * @param string $field 表字段
     * @return array
     * */
    public function getSupplierList(array $paramsInput, $field = '*')
    {
        $shopId = $paramsInput['shopId'];
        $page = $paramsInput['page'];
        $pageSize = $paramsInput['pageSize'];
        $export = $paramsInput['export'];
        $usePage = $paramsInput['use_page'];
        $model = new SupplierModel();
        $where = "shopId={$shopId} and isDelete=0 ";
        if (isset($paramsInput['enableStatus'])) {
            if (is_numeric($paramsInput['enableStatus'])) {
                $where .= " and enableStatus={$paramsInput['enableStatus']} ";
            }
        }
        if (!empty($paramsInput['keywords'])) {
            $where .= " and (supplierName like '%{$paramsInput['keywords']}%' or linkman like '%{$paramsInput['keywords']}%' or landlineNumber like '%{$paramsInput['keywords']}%' ) or linkphone like '%{$paramsInput['keywords']}%'";
        }
        $sql = $model->where($where)->field($field)->order("createTime desc")->buildSql();
        if ($export == 1 || $usePage != 1) {
            $result = $model->query($sql);
        } else {
            $result = $model->pageQuery($sql, $page, $pageSize);
        }

        return $result;
    }

    /**
     * 供应商-删除
     * @param int|array $supplierId
     * @return bool
     * */
    public function delSupplier($supplierId)
    {
        if (is_array($supplierId)) {
            $supplierId = implode(',', $supplierId);
        }
        $where = array(
            'supplierId' => array('in', $supplierId)
        );
        $saveParams = array(
            'isDelete' => 1,
            'deleteTime' => time(),
        );
        $model = new SupplierModel();
        $result = $model->where($where)->save($saveParams);
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * 供应商-id批量查找
     * @param array $supplierIdArr 供应商id
     * @param string $field 表字段
     * @return array
     * */
    public function getSupplierListByIdArr($supplierIdArr, $field = '*')
    {
        $model = new SupplierModel();
        $where = array(
            'supplierId' => array('IN', $supplierIdArr),
            'isDelete' => 0,
        );
        $result = $model->where($where)->field($field)->select();
        return (array)$result;
    }
}