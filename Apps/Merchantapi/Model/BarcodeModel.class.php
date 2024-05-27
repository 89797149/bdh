<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 条码功能类
 */
class BarcodeModel extends BaseModel {

    /**
     * 添加条码
     * @param $data
     */
    public function createBarcode($data){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $m = M('barcode');
        $insert_id = $m->add($data);
        if ($insert_id > 0) {
//            $barcode = $this->doCreateBarcode($insert_id);
            $barcode = joinString($insert_id,0,18);
            $barcode = 'CZ-' . $barcode;
            if ($barcode) {
                $result = $m->where(array('id' => $insert_id))->save(array('barcode' => $barcode));
                if ($result) {
                    $data['barcode'] = $barcode;
                    $apiRet['apiCode'] = 0;
                    $apiRet['apiInfo'] = '操作成功';
                    $apiRet['apiState'] = 'success';
                    $apiRet['apiData'] = $data;
                    return $apiRet;
                }
            }
        }
        return $apiRet;
    }

    /**
     * 生成商品条码  - 动作
     * @param $id
     * @return bool|string
     */
    public function doCreateBarcode($id){
        $len = strlen($id);
        if ($len == 0) return false;
        $str = '';
        for ($i=0;$i<(18-$len);$i++) {
            $str .= '0';
        }
        return $str.$id;
    }

    /**
     * 条码列表
     * @param $param
     * @return array
     */
    public function getBarcodeList($param){
        $where = " b.shopId = " . $param['shopId'] . " ";
        if (!empty($param['startTime'])) $where .= " and b.createTime >= '" . $param['startTime'] . "' ";
        if (!empty($param['endTime'])) $where .= " and b.createTime <= '" . $param['endTime'] . "' ";
        if (!empty($param['barcode'])) $where .= " and b.barcode = '" . $param['barcode'] . "' ";
        $sql = "select b.*,g.goodsName from __PREFIX__barcode as b inner join __PREFIX__goods as g on b.goodsId = g.goodsId where " . $where . " order by b.createTime desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 条码详情
     * @param $param
     * @return array
     */
    public function getBarcodeManageDetail($param){
        $where = " b.id = " . $param['id'] . " and b.shopId = " . $param['shopId'] . " ";
        $sql = "select b.*,g.* from __PREFIX__barcode as b inner join __PREFIX__goods as g on b.goodsId = g.goodsId where " . $where . " order by b.createTime desc ";
        return $this->queryRow($sql);
    }

    /**
     * 销毁条码
     * @param $shopId
     * @param $id 条码id,多个以逗号连接
     * @return mixed
     */
    public function destroyBarcode($shopId,$id){
        return M('barcode')->where(array('id'=>array('in',$id),'shopId'=>$shopId))->save(array('bFlag'=>-1));
    }

    /**
     * 出库（销毁条码并修改库存）
     * @param $shopId
     * @param $barcode
     * @param $skuId
     * @return bool
     */
    public function outStock($shopId,$barcode,$orderNo,$skuId=0){
        $m = M('barcode');
        $gm = M('goods');

        //判断是否是线下称重商品 如果是从条码称重表取数据 包含CZ-即为查询称重商品
        if(strpos($barcode,'CZ-') !== false) {//是称重商品
            $bwhere = array('barcode' => $barcode, 'shopId' => $shopId, 'bFlag' => 1);
            $barcodeInfo = $m->where($bwhere)->find();
            if (empty($barcodeInfo)) return false;

            $gwhere = array('goodsId'=>$barcodeInfo['goodsId'],'shopId'=>$shopId);
            $goodsInfo = $gm->where($gwhere)->find();
            if (empty($goodsInfo)) return false;

            M()->startTrans();
            $result = $m->where($bwhere)->save(array('bFlag'=>-1,'orderNo'=>$orderNo));
			$barcodeInfo_weight = gChangeKg($barcodeInfo['goodsId'],$barcodeInfo['weight'],0);
            if(!empty($skuId)){
                //后加skuId
                $result1 = M('sku_goods_system')->where(['skuId'=>$skuId])->setDec('skuGoodsStock',$barcodeInfo_weight);
            }else{
                $result1 = $gm->where($gwhere)->setDec('goodsStock',$barcodeInfo_weight);
            }
            if ($result && $result1) {
                M()->commit();
                return true;
            } else {
                M()->rollback();
                return false;
            }
        } else {//标品商品
            $gwhere = array('goodsSn' => $barcode, 'shopId' => $shopId, 'goodsFlag' => 1);
            $goodsInfo = $gm->where($gwhere)->find();
            if (empty($goodsInfo)) return false;

            $ogwhere = array('pog.goodsId'=>$goodsInfo['goodsId'],'po.orderNO'=>$orderNo);
            $order_goods_info = M('pos_orders_goods as pog')->field('pog.*')->where($ogwhere)->join('left join wst_pos_orders as po on po.id = pog.orderid')->find();
            if (empty($order_goods_info)) return false;
			
			$order_goods_info_number = gChangeKg($goodsInfo['goodsId'],$order_goods_info['number'],0);
            if(!empty($skuId)){
                $result = M('sku_goods_system')->where(['skuId'=>$skuId])->setDec('skuGoodsStock',$order_goods_info_number);
            }else{
                $result = $gm->where(array('goodsId'=>$goodsInfo['goodsId']))->setDec('goodsStock',$order_goods_info_number);
            }
            if ($result) return true;
            else return false;
        }
    }

}