<?php
/**
 * 供应商
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-19
 * Time: 16:26
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Sms\SmsEnum;
use App\Modules\Supplier\SupplierModule;

class SupplierModel extends BaseModel
{
    /**
     * 供应商-添加
     * @param array $paramsInput
     * -int shopId 门店id
     * -string supplierName 供应商名称
     * -string linkman 联系人
     * -string linkphone 联系手机
     * -string [landlineNumber] 电话/座机号
     * -string [detailAddress] 详细地址
     * -int [enableStatus] 启用状态(0:禁用 1:启用)
     * @return array
     * */
    public function addSupplier(array $paramsInput)
    {
        $module = new SupplierModule();
        if ($module->isExistSupplierNameId($paramsInput['shopId'], $paramsInput['supplierName'])) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '供应商名称已存在');
        }
        $result = $module->saveSupplier($paramsInput);
        if (!$result) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '添加失败');
        }
        return returnData(true);
    }

    /**
     * 供应商-修改
     * @param array $paramsInput
     * -int supplierId 供应商id
     * -string [supplierName] 供应商名称
     * -string [linkman] 联系人
     * -string [linkphone] 联系手机
     * -string [landlineNumber] 电话/座机号
     * -string [detailAddress] 详细地址
     * -int [enableStatus] 启用状态(0:禁用 1:启用)
     * @return array
     * */
    public function udpateSupplier(array $paramsInput)
    {
        $module = new SupplierModule();
        $supplierId = $paramsInput['supplierId'];
        $supplierDetail = $module->getSupplierDetailById($supplierId, 'supplierId,supplierName');
        if (empty($supplierDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '供应商不存在');
        }
        if (isset($paramsInput['supplierName'])) {
            $existSupplierId = $module->isExistSupplierNameId($paramsInput['shopId'], $paramsInput['supplierName']);
            if ($existSupplierId > 0 && $existSupplierId != $supplierId) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '供应商名称已存在');
            }
        }
        $result = $module->saveSupplier($paramsInput);
        if (!$result) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '修改失败');
        }
        return returnData(true);
    }

    /**
     * 供应商-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string keywords 关键字(供应商名称/联系人/联系方式)
     * -int enableStatus 启用状态(0:禁用 1:启用)
     * -int use_page 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 是否导出(0:否 1:是)
     * @return array
     * */
    public function getSupplierList(array $paramsInput)
    {
        $module = new SupplierModule();
        $field = 'supplierId,supplierName,linkman,linkphone,landlineNumber,detailAddress,enableStatus,createTime';
        $result = $module->getSupplierList($paramsInput, $field);
        if ($paramsInput['export'] == 1) {
            $this->exportSupplierList($result);
        }
        return returnData($result);
    }

    /**
     * 供应商-详情
     * @param int $supplierId 供应商id
     * @return array
     * */
    public function getSupplierDetail(int $supplierId)
    {
        $module = new SupplierModule();
        $field = 'supplierId,supplierName,linkman,linkphone,landlineNumber,detailAddress,enableStatus,createTime';
        $result = $module->getSupplierDetailById($supplierId, $field);
        return returnData($result);
    }

    /**
     * 供应商-删除
     * @param array $supplierIdArr 供应商id
     * @return array
     * */
    public function delSupplier(array $supplierIdArr)
    {
        $module = new SupplierModule();
        $result = $module->delSupplier($supplierIdArr);
        if (!$result) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        return returnData(true);
    }

    /**
     * 供应商-列表-导出
     * @param array $supplierList 供应商数据列表
     * */
    public function exportSupplierList(array $supplierList)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/supplier_export.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 2;
        foreach ($supplierList as $detail) {
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['supplierName']);//供应商名称
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['linkman']);//联系人
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['linkphone']);//联系手机
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['landlineNumber']);//电话/座机号
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['detailAddress']);//详细地址
        }
        $savefileName = '供应商导出' . date('YmdHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 供应商-导入
     * @param int $shopId
     * @param file $file
     * */
    public function importSupplier($shopId, $file)
    {
        $objReader = WSTReadExcel($file['file']['savepath'] . $file['file']['savename']);
        $objReader->setActiveSheetIndex(0);
        $sheet = $objReader->getActiveSheet();
        $rows = $sheet->getHighestRow();
        if ($rows < 3) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "请完善表格信息");
        }
        $addData = array();//新增数据
        $updateData = array();//修改数据
        $module = new SupplierModule();
        $model = new \App\Models\SupplierModel();
        for ($i = 3; $i <= $rows; $i++) {
            $supplierName = trim($objReader->getActiveSheet()->getCell("A" . $i)->getValue());//供应商名称
            $linkman = trim($objReader->getActiveSheet()->getCell("B" . $i)->getValue());//联系人
            $linkphone = trim($objReader->getActiveSheet()->getCell("C" . $i)->getValue());//联系手机
            $landlineNumber = trim($objReader->getActiveSheet()->getCell("D" . $i)->getValue());//电话/座机号
            $detailAddress = trim($objReader->getActiveSheet()->getCell("E" . $i)->getValue());//详细地址
            if (empty($supplierName)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$i}行，供应商名称不能为空");
            }
            if (empty($linkman)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$i}行，联系人不能为空");
            }
            if (empty($linkphone)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$i}行，联系手机不能为空");
            }
            if (!preg_match(SmsEnum::MOBILE_FORMAT, $linkphone)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$i}行，联系手机格式错误");
            }
            $detail = array(
                'shopId' => $shopId,
                'supplierName' => $supplierName,
                'linkman' => $linkman,
                'linkphone' => $linkphone,
                'landlineNumber' => $landlineNumber,
                'detailAddress' => $detailAddress,
            );
            $supplierId = $module->isExistSupplierNameId($shopId, $supplierName);
            if ($supplierId > 0) {
                $detail['supplierId'] = $supplierId;
                $updateData[$supplierId] = $detail;
            } else {
                $detail['createTime'] = date('Y-m-d H:i:s');
                $detail['updateTime'] = date('Y-m-d H:i:s');
                $addData[$supplierName] = $detail;
            }
        }
        if (!empty($addData)) {
            $addData = array_values($addData);
            $model->addAll($addData);
        }
        if (!empty($updateData)) {
            $model->saveAll($updateData, 'wst_supplier', 'supplierId');
        }
        return returnData(true);
    }

}