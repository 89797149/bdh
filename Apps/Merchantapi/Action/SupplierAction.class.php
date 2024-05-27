<?php
/**
 * 供应商
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-19
 * Time: 16:25
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Sms\SmsEnum;
use Merchantapi\Model\SupplierModel;

class SupplierAction extends BaseAction
{
    /**
     * 供应商-添加
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/iky6w8
     * */
    public function addSupplier()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'supplierName' => null,
            'linkman' => null,
            'linkphone' => null,
            'landlineNumber' => null,
            'detailAddress' => null,
            'enableStatus' => null,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['supplierName'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入供应商名称'));
        }
        if (empty($paramsInput['linkman'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入联系人'));
        }
        if (empty($paramsInput['linkphone'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入联系手机'));
        }
        if (!preg_match(SmsEnum::MOBILE_FORMAT, $paramsInput['linkphone'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '手机格式不正确'));
        }
        $mod = new SupplierModel();
        $result = $mod->addSupplier($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 供应商-修改
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xgf7cg
     * */
    public function udpateSupplier()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'supplierId' => null,
            'supplierName' => null,
            'linkman' => null,
            'linkphone' => null,
            'landlineNumber' => null,
            'detailAddress' => null,
            'enableStatus' => null,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['supplierId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-supplierId'));
        }
        if (isset($paramsInput['supplierName'])) {
            if (empty($paramsInput['supplierName'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入供应商名称'));
            }
        }
        if (isset($paramsInput['linkman'])) {
            if (empty($paramsInput['linkman'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入联系人'));
            }
        }
        if (isset($paramsInput['linkphone'])) {
            if (empty($paramsInput['linkphone'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入联系手机'));
            }
            if (!preg_match(SmsEnum::MOBILE_FORMAT, $paramsInput['linkphone'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '手机格式不正确'));
            }
        }
        $mod = new SupplierModel();
        $result = $mod->udpateSupplier($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 供应商-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/kvd0qg
     * */
    public function getSupplierList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'keywords' => '',//关键字(供应商名称/联系人/联系方式)
            'enableStatus' => '',//启用状态(0:禁用 1:启用)
            'use_page' => 1,//是否使用分页(0:不使用 1:使用)
            'page' => 1,
            'pageSize' => 15,
            'export' => 0,
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new SupplierModel();
        $result = $mod->getSupplierList($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 供应商-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bgwwue
     * */
    public function getSupplierDetail()
    {
        $this->MemberVeri();
        $supplierId = (int)I('supplierId');
        if (empty($supplierId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-缺少supplierId'));
        }
        $mod = new SupplierModel();
        $result = $mod->getSupplierDetail($supplierId);
        $this->ajaxReturn($result);
    }

    /**
     * 供应商-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fqmpsn
     * */
    public function delSupplier()
    {
        $this->MemberVeri();
        $supplierIdArr = (array)json_decode(htmlspecialchars_decode(I('supplierIdArr')), JSON_UNESCAPED_UNICODE);
        if (empty($supplierIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要删除的数据'));
        }
        $mod = new SupplierModel();
        $result = $mod->delSupplier($supplierIdArr);
        $this->ajaxReturn($result);
    }

    /**
     * 供应商-导入
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/rpuebo
     * */
    public function importSupplier()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $config = array(
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('xls', 'xlsx', 'xlsm'), //允许上传的文件后缀
            'rootPath' => './Upload/', //保存根路径
            'driver' => 'LOCAL', // 文件上传驱动
            'subName' => array('date', 'Y-m'),
            'savePath' => I('dir', 'uploads') . "/"
        );
        $upload = new \Think\Upload($config);
        $uploadRes = $upload->upload($_FILES);
        if (!$uploadRes) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', $upload->getError()));
        }
        $mod = new SupplierModel();
        $result = $mod->importSupplier($shopId, $uploadRes);
        $this->ajaxReturn($result);
    }
}