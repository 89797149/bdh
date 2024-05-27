<?php
/**
 * 重新创建一个商品类来写后续新增的业务逻辑
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-14
 * Time: 11:18
 */

namespace Merchantapi\Model;


use App\Models\PurchasePriceChangeBillGoodsModel;
use App\Modules\Goods\GoodsModule;

class ShopGoodsModel extends BaseModel
{
    /**
     * 商品-现有商品库存列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int catid 分类id
     * -string keywords 商品关键字(商品名/编码)
     * -int hideZeroStock 是否隐藏0库存商品(0:不隐藏 1:隐藏)
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供应商)id
     * -int page 页码
     * -int pageSize 每页条数
     * -int export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function existingGoodsStockList(array $paramsInput)
    {
        $module = new GoodsModule();
        if ($paramsInput['export'] == 1) {
            $paramsInput['usePage'] = 0;
        }
        $result = $module->getShopGoodsAndSkuList($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportExistingGoodsStockList($result);
        }
        return $result;
    }

    /**
     * 商品-现有商品库存列表-导出
     * @param array $result
     * */
    public function exportExistingGoodsStockList(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/existing_goods_stock_list.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 3;
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 1, date('Y-m-d H:i:s') . " 现有库存导出");
        foreach ($result['list'] as $detail) {
            $keyTag++;
            $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:K{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['goodsName']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['goodsCode']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['describe']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['unitName']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['skuSpecStr']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['shopCatId1Name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['shopCatId2Name']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detail['warehouseNoStock']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detail['goodsStock']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detail['purchase_price']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $detail['goodsStockAmountTotal']);
        }
        $keyTag++;
        $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:K{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, "合计:");
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $result['goodsStockAmountTotal']);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . "现有库存导出" . date('YmdHis') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 现有库存-商品-成本变更记录
     * @param array $paramsInput
     * -int shopId 门店id
     * -int skuId 规格id
     * -int page 分页
     * -int pageSize 每页条数
     * @return array
     * */
    public function getGoodsPriceChangeList(array $paramsInput)
    {
        $model = new PurchasePriceChangeBillGoodsModel();
        $where = "b_goods.isDelete=0 ";
        $where .= " and bill.isDelete=0 and bill.billStatus=1 ";
        $field = "bill.examineTime,bill.creatorName";
        $field .= ",b_goods.originalPurchasePrice,b_goods.nowPurchasePrice";
        $sql = $model
            ->alias('b_goods')
            ->join("left join wst_purchase_price_change_bill bill on bill.changeId=b_goods.changeId")
            ->where($where)
            ->field($field)
            ->order('bill.examineTime desc')
            ->buildSql();
        $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        return $result;
    }
}