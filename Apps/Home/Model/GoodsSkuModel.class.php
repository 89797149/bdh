<?php

namespace Home\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsModule;

/**
 * ===================================getGoodsSku=========================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品SKU
 */
class GoodsSkuModel extends BaseModel
{
    /*
     * 添加规格
     * @param string specName PS:规格名称
     * */
    public function goodsSpecInsert($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '添加失败';
        $apiRet['apiState'] = 'error';
        $tab = M("sku_spec");
        if (!empty($param['specName'])) {
            $where = [];
            $where['dataFlag'] = 1;
            $where['specName'] = $param['specName'];
            $where['shopId'] = $param['shopId'];
            $specCount = $tab->where($where)->count('specId');
            if ($specCount > 0) {
                $apiRet['apiInfo'] = $param['specName'] . "已经存在,不能重复添加";
                return $apiRet;
            }
        }
        $insert['specName'] = $param['specName'];
        $insert['shopId'] = $param['shopId'];
        $insert['sort'] = $param['sort'];
        $insert['addTime'] = date("Y-m-d H:i:s", time());
        $insertId = $tab->add($insert);
        if ($insertId) {
            $insert['specId'] = $insertId;
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '添加成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $insert;
        }
        return $apiRet;
    }

    /*
     * 编辑规格
     * @param string specName PS:规格名称
     * @param int sort PS:排序
     * @param int specId PS:规格id
     * */
    public function goodsSpecEdit($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '编辑失败';
        $apiRet['apiState'] = 'error';
        $tab = M("sku_spec");
        $specInfo = $tab->where(['specId' => $param['specId'], 'dataFlag' => 1])->find();
        if (!$specInfo) {
            $apiRet['apiInfo'] = '数据异常';
            return $apiRet;
        }
        if (!empty($param['specName'])) {
            $where = [];
            $where['dataFlag'] = 1;
            $where['specName'] = $param['specName'];
            $where['shopId'] = $param['shopId'];
            $specCountInfo = $tab->where($where)->getField('specId');
            if ($specCountInfo > 0 && $specInfo['specId'] != $specCountInfo) {
                $apiRet['apiInfo'] = $param['specName'] . "已经存在,不能重复添加";
                return $apiRet;
            }
        }
        $edit = [];
        $edit['specName'] = $param['specName'];
        $edit['sort'] = $param['sort'];
        $editRes = $tab->where(["specId" => $param['specId']])->save($edit);
        if ($editRes !== false) {
            $specInfo['specName'] = $param['specName'];
            $specInfo['sort'] = $param['sort'];
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '编辑成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $specInfo;
        }
        return $apiRet;
    }

    /*
     * 获取规格列表
     * @param string token
     * @param string sortField PS:排序字段
     * @param string sortWord PS:排序方法(ASC:正序 | DESC:倒叙)
     * @param int page PS:页码
     * @param int pageSize PS:分页条数
     * */
    public function goodsSpecList($param)
    {
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        $sortField = " sort ";
        $orderSort = " desc ";
        $where = " where dataFlag=1 and shopId='" . $param['shopId'] . "' ";
        if (!empty($param['sortField'])) {
            $sortField = ' ' . $param['sortField'] . ' ';
        }
        if (!empty($param['sortWord'])) {
            $orderSort = ' ' . $param['sortWord'] . ' ';
        }
        if (!empty($param['specName'])) {
            $where .= " and specName like '%" . $param['specName'] . "%' ";
        }
        $sql = "select specId,specName,shopId,sort,addTime from __PREFIX__sku_spec " . $where . " order by " . $sortField . $orderSort;
        $list = $this->pageQuery($sql, $page, $pageSize);
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;
        return $apiRet;
    }

    /*
     * 删除规格
     * @param string token
     * @param int specId PS:规格id,多个用英文逗号分隔
     * */
    public function goodsSpecDel($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '删除失败';
        $apiRet['apiState'] = 'error';
        $tab = M('sku_spec');
        $where = [];
        $where['dataFlag'] = 1;
        $where['specId'] = ["IN", $param['specId']];
        $specList = $tab->where($where)->select();
        if ($specList) {
            $attrTab = M('sku_spec_attr');
            foreach ($specList as $value) {
                $attrCount = $attrTab->where(['specId' => $value['specId'], 'dataFlag' => 1])->count('attrId');
                if ($attrCount > 0) {
                    $apiRet['apiInfo'] = '删除失败,' . $value['specName'] . "已经存在属性,不能直接删除,请先删除属性";
                    return $apiRet;
                }
            }

            $edit = [];
            $edit['dataFlag'] = -1;
            $deleteRes = $tab->where($where)->save($edit);
            if ($deleteRes) {
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '删除数据成功';
                $apiRet['apiState'] = 'success';
            }
        }
        return $apiRet;
    }

    /*
     * 添加属性
     * @param string specId PS:规格id
     * @param string attrName PS:属性名称
     * @param int sort PS:排序
     * */
    public function goodsSpecAttrInsert($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '添加失败';
        $apiRet['apiState'] = 'error';
        $tab = M("sku_spec_attr");
        if (!empty($param['attrName'])) {
            $where = [];
            $where['dataFlag'] = 1;
            $where['attrName'] = $param['attrName'];
            $where['specId'] = $param['specId'];
            $specCount = $tab->where($where)->count('attrId');
            if ($specCount > 0) {
                $apiRet['apiInfo'] = $param['attrName'] . "已经存在,不能重复添加";
                return $apiRet;
            }
        }
        $insert['attrName'] = $param['attrName'];
        $insert['specId'] = $param['specId'];
        $insert['sort'] = $param['sort'];
        $insert['addTime'] = date("Y-m-d H:i:s", time());
        $insertId = $tab->add($insert);
        if ($insertId) {
            $insert['attrId'] = $insertId;
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '添加成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $insert;
        }
        return $apiRet;
    }

    /*
     * 编辑属性
     * @param string attrId PS:属性id
     * @param string specId PS:规格id
     * @param string attrName PS:属性名称
     * @param int sort PS:排序
     * */
    public function goodsSpecAttrEdit($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '编辑失败';
        $apiRet['apiState'] = 'error';
        $tab = M("sku_spec_attr");
        $specInfo = $tab->where(['attrId' => $param['attrId'], 'dataFlag' => 1])->find();
        if (!$specInfo) {
            $apiRet['apiInfo'] = '数据异常';
            return $apiRet;
        }
        if (!empty($param['attrName'])) {
            $where = [];
            $where['dataFlag'] = 1;
            $where['attrName'] = $param['attrName'];
            $where['specId'] = $param['specId'];
            $specCountInfo = $tab->where($where)->getField('attrId');
            if ($specCountInfo > 0 && $specInfo['attrId'] != $specCountInfo) {
                $apiRet['apiInfo'] = $param['attrName'] . "已经存在,不能重复添加";
                return $apiRet;
            }
        }
        $edit = [];
        $edit['attrName'] = $param['attrName'];
        $edit['sort'] = $param['sort'];
        $where = [];
        $where['specId'] = $param['specId'];
        $where['attrId'] = $param['attrId'];
        $editRes = $tab->where($where)->save($edit);
        if ($editRes !== false) {
            $specInfo['attrName'] = $param['attrName'];
            $specInfo['sort'] = $param['sort'];
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '编辑成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $specInfo;
        }
        return $apiRet;
    }

    /*
     * 删除属性
     * @param int attrId PS:属性id
     * */
    public function goodsSpecAttrDel($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '删除失败';
        $apiRet['apiState'] = 'error';
        $tab = M('sku_spec_attr');
        $where = [];
        $where['attrId'] = ["IN", $param['attrId']];
        $where['dataFlag'] = 1;
        $edit = [];
        $edit['dataFlag'] = -1;
        //如果该属性下面已经关联了商品,需要先解除与该商品之间的关联才能删除该属性
        $skuSystem = M('sku_goods_self');
        $skuSystemInfo = $skuSystem->where(['attrId' => $param['attrId'], 'dataFlag' => 1])->select();
        if (count($skuSystemInfo) > 0) {
            $apiRet['apiInfo'] = '该sku属性已与商品关联,请先删除和商品的关联';
            return $apiRet;
        }
        $deleteRes = $tab->where($where)->save($edit);
        if ($deleteRes) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '删除数据成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     * 获取属性列表
     * @param int specId PS:规格id
     * @param string sortField PS:排序字段
     * @param string sortWord PS:排序方法(ASC:正序 | DESC:倒叙)
     * @param int page PS:页码
     * */
    public function goodsSpecAttrList($param)
    {
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        $sortField = " sort ";
        $orderSort = " desc ";
        $where = " where spa.dataFlag=1 and spa.specId='" . $param['specId'] . "' ";
        if (!empty($param['specName'])) {
            $where .= " and spa.specName like '%" . $param['specName'] . "%' ";
        }
        if (!empty($param['sortField'])) {
            $sortField = ' ' . $param['sortField'] . ' ';
        }
        if (!empty($param['sortWord'])) {
            $orderSort = ' ' . $param['sortWord'] . ' ';
        }
        $sql = "select spa.attrId,spa.specId,spa.attrName,sp.specName,spa.sort,spa.addTime from __PREFIX__sku_spec_attr spa left join __PREFIX__sku_spec sp on sp.specId = spa.specId " . $where . " order by " . 'spa.' . $sortField . $orderSort;
        $list = $this->pageQuery($sql, $page, $pageSize);
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;
        return $apiRet;
    }

    /*
     * 获取规格属性列表(无分页)
     * @param string specSortField PS:规格排序字段
     * @param string specSortWord PS:规格排序方法(ASC:正序 | DESC:倒叙)
     * @param string attrSortField PS:属性排序字段
     * @param string attrSortWord PS:属性排序方法(ASC:正序 | DESC:倒叙)
     * */
    public function goodsSpecAttrMergeList($param)
    {
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = [];

        $specTab = M('sku_spec');
        $attrTab = M('sku_spec_attr');
        $specOrder = $param['specSortField'] . " " . $param['specSortWord'];
        $attrOrder = $param['attrSortField'] . " " . $param['attrSortWord'];
        $where = [];
        $where['shopId'] = $param['shopId'];
        $where['dataFlag'] = 1;
        $specList = $specTab
            ->where($where)
            ->field('specId,specName,shopId,sort,addTime')
            ->order("$specOrder")
            ->select();
        if (count($specList) > 0) {
            $specIdArr = [];
            foreach ($specList as $key => $val) {
                $specList[$key]['attrList'] = [];
                $specIdArr[] = $val['specId'];
            }
            $attrWhere = [];
            $attrWhere['dataFlag'] = 1;
            $attrWhere['specId'] = ["IN", $specIdArr];
            $attrList = $attrTab
                ->where($attrWhere)
                ->field('attrId,specId,attrName,sort,addTime')
                ->order($attrOrder)
                ->select();
            if (count($attrList) > 0) {
                foreach ($specList as $key => $val) {
                    foreach ($attrList as $v) {
                        if ($v['specId'] == $val['specId']) {
                            $specList[$key]['attrList'][] = $v;
                        }
                    }
                }
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = (array)$specList;
        }
        return $apiRet;
    }

    /*
     * 商品添加sku属性
     * @param int goodsId PS:商品id
     * @param string specAttrString PS:规格属性组合}
     * */
    public function insertGoodsSku($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '添加失败';
        $apiRet['apiState'] = 'error';
        $goodsId = $param['goodsId'];
        $specAttrString = $param['specAttrString'];
        $response = $this->insertGoodsSkuModel($goodsId, $specAttrString, $param['shopId']);
        if ($response['apiCode'] == 0) {
            //$this->updateGoodsStock($goodsId);
        }
        return $response;
    }

    /*
     * 编辑商品sku属性
     * @param int goodsId PS:商品id
     * @param string specAttrString PS:规格属性组合}
     * */
    public function editGoodsSku($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '添加失败';
        $apiRet['apiState'] = 'error';
        $goodsId = $param['goodsId'];
        $specAttrString = $param['specAttrString'];
        $shopId = $param['shopId'];
        $res = $this->editGoodsSkuModel($goodsId, $specAttrString, $shopId);
        if ($res['apiCode'] == 0) {
            //重新计算商品的库存
            //$this->updateGoodsStock($goodsId);
        }
        return $res;
    }

    /**
     * 更新商品库存
     * @param int $goodsId 商品id
     * */
    public function updateGoodsStock($goodsId = 0)
    {
        $where = [];
        $where['goodsId'] = $goodsId;
        $goodsSkuList = $this->getGoodsSku($where)['apiData'];
        if (!empty($goodsSkuList)) {
            //PS:如果商品sku库存没有设置,则默认使用商品表(wst_goods)的goodsStock字段
            //$goodsInfo = M('goods')->where(['goodsId'=>$goodsId])->field('goodsId,goodsName,goodsStock')->find();
            $totalGoodsStock = 0;
            foreach ($goodsSkuList as $value) {
                $totalGoodsStock += $value['systemSpec']['skuGoodsStock'];
            }
            if ($totalGoodsStock <= 0) {
                $totalGoodsStock = 0;
            }
            M('goods')->where(['goodsId' => $goodsId])->save(['goodsStock' => $totalGoodsStock]);
        }

    }

    /**
     *删除商品sku
     * @param string skuId PS:skuId,多个用英文逗号拼接
     * */
    public function deleteGoodsSku($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '删除数据失败';
        $apiRet['apiState'] = 'error';
        $skuId = trim($param['skuId'], ',');
        $systemTab = M('sku_goods_system');
        $systemInfo = $systemTab->where(['skuId' => $skuId])->find();
        if (empty($systemInfo)) {
            $apiRet['apiInfo'] = '无效的skuId';
            return $apiRet;
        }
        $selfTab = M('sku_goods_self');
        $where = [];
        $where['skuId'] = $skuId;
//        $edit['dataFlag'] = -1;
//        $deleteRes = $systemTab
//            ->where($where)
//            ->save($edit);
        $deleteRes = $systemTab
            ->where($where)
            ->delete();
        if ($deleteRes !== false) {
//            $editSelf = [];
//            $editSelf['dataFlag'] = -1;
//            $selfTab->where($where)->save($editSelf);
            $selfTab->where($where)->delete();
            //后加 start
//            if ($systemInfo['skuGoodsStock'] > 0) {
//                $goodsInfo = M('goods')->where(['goodsId' => $systemInfo['goodsId']])->field('goodsId,goodsName,goodsStock')->find();
//                $goodsStock = $goodsInfo['goodsStock'] - $systemInfo['skuGoodsStock'];
//                $goodsStock = $goodsStock > 0 ? $goodsStock : 0;
//                M('goods')->where(['goodsId' => $goodsInfo['goodsId']])->save(['goodsStock' => $goodsStock]);
//            }
            //后加 end
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '删除数据成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     * 获取商品的sku
     * @param string skuId PS:skuId
     * */
    public function getGoodsSku($param)
    {
        $systemTab = M('sku_goods_system');
        //$selfTab = M('sku_goods_self');
        $goodsId = $param['goodsId'];
        $sysWhere = [];
        $sysWhere ['dataFlag'] = 1;
        $sysWhere ['goodsId'] = $goodsId;
        $systemSpec = $systemTab->where($sysWhere)->order('skuId asc')->select();
        $systemSpec = $this->returnSystemSkuValue($systemSpec);
        $goodsModule = new GoodsModule();
        if ($systemSpec) {
            $response = [];
            foreach ($systemSpec as $value) {
                $spec = [];
                $spec['skuId'] = $value['skuId'];
                $spec['systemSpec']['skuShopPrice'] = (float)$value['skuShopPrice'];
                $spec['systemSpec']['skuMemberPrice'] = $value['skuMemberPrice'];
                $spec['systemSpec']['skuGoodsStock'] = $value['skuGoodsStock'];
                $spec['systemSpec']['selling_stock'] = $value['selling_stock'];
                $spec['systemSpec']['skuGoodsImg'] = $value['skuGoodsImg'];
                $spec['systemSpec']['skuBarcode'] = $value['skuBarcode'];
                $spec['systemSpec']['skuMarketPrice'] = $value['skuMarketPrice'];
                $spec['systemSpec']['minBuyNum'] = $value['minBuyNum'];
                $spec['systemSpec']['WeighingOrNot'] = $value['WeighingOrNot'];
                $spec['systemSpec']['weigetG'] = $value['weigetG'];
                $spec['systemSpec']['unit'] = $value['unit'];
                $spec['systemSpec']['purchase_price'] = (float)$value['purchase_price'];
                $spec['systemSpec']['UnitPrice'] = $value['UnitPrice'];
                //$selfSpec = $selfTab->where(['skuId'=>$value['skuId'],'dataFlag'=>1])->select();

                $spec['selfSpec'] = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName')
                    ->order('se.id asc')
                    ->group('se.id')
                    ->select();
                if (empty($spec['selfSpec'])) {
                    continue;
                }
                $spec['specAttrNameStrTwo'] = '';
                foreach ($spec['selfSpec'] as $specVal) {
                    $spec['specAttrNameStrTwo'] .= "{$specVal['specName']}#{$specVal['attrName']},";
                }
                $spec['specAttrNameStr'] = implode(',', array_column($spec['selfSpec'], 'attrName'));
                $spec['specAttrNameStrTwo'] = rtrim($spec['specAttrNameStrTwo'], ',');
//                $spec['specAttrNameStr'] = rtrim($spec['specAttrNameStr'], '，');
//                $spec['specAttrNameStr'] = mb_substr($spec['specAttrNameStr'], 0, -1, 'utf-8');
                $spec['has_rank'] = 0;
                //$spec['rankList'] = array();
                $spec['rankArr'] = array();
                $rankList = $goodsModule->getSkuRankListBySkuId($value['skuId']);
                if (!empty($rankList)) {
                    $spec['has_rank'] = 1;
                    $spec['rankArr'] = $rankList;
                }
                $response[] = $spec;
            }
        }
        return returnData((array)$response);
    }

    /*
     * 后加,如果传过来的sku属性值为空,则字段值以商品原来的同意义字段值为准
     * @param array systemSpec PS:非自定义参数处理
     * */
    public function checkSystemSkuValue($systemSpec)
    {
        if (!empty($systemSpec)) {
            foreach ($systemSpec as $key => $val) {
                if ($key == 'skuGoodsImg' && $val != '') {
                    $nums = substr_count($val, 'undefined');
                    if ($nums >= 1) {
                        $systemSpec[$key] = '';
                    }
                }
//                if (is_null($val) || empty($val)) {
//                    $systemSpec[$key] = '-1';
//                }
            }
        }
        return $systemSpec;
    }

    /*
     * 后加,将checkSystemSkuValue转换的-1值替换为空字符串
     * @param array systemSpec PS:非自定义参数处理
     * */
    public function returnSystemSkuValue($systemSpec)
    {
        if (!empty($systemSpec)) {
            foreach ($systemSpec as $key => $val) {
                foreach ($val as $k => $v) {
                    if (in_array($k, ['dataFlag', 'addTime', 'minBuyNum'])) {
                        continue;
                    }
//                    if ((int)$v == -1) {
//                        $systemSpec[$key][$k] = '';
//                    }
                    if ((int)$v == -1 && in_array($k, array('skuShopPrice', 'skuMemberPrice', 'skuGoodsStock', 'skuMarketPrice', 'selling_stock'))) {
                        $systemSpec[$key][$k] = 0;
                    }
                    if ((int)$v == -1 && in_array($k, array('skuGoodsImg', 'skuBarcode'))) {
                        $systemSpec[$key][$k] = '';
                    }
                }
            }
        }
        return $systemSpec;
    }

    /*
     * 添加商品sku封装
     *@param int goodsId
     *@param array specAttrArr
     * */
    public function insertGoodsSkuModel($goodsId, $specAttrArr, $shopId = 0)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '添加失败';
        $apiRet['apiState'] = 'error';
        if (!empty($specAttrArr) && !empty($goodsId)) {
            $repeatSkuArr = [];
            $systemTab = M('sku_goods_system system');
            foreach ($specAttrArr as $key => $val) {
                foreach ($val as $v) {
                    $repeatSkuArr[] = $v['systemSpec']['skuBarcode'];
                    if ($v['systemSpec']['skuGoodsStock'] < 0) {
                        $apiRet['apiInfo'] = '添加失败,商品sku库存有误';
                        return $apiRet;
                    }
//                    if ((float)$v['systemSpec']['weigetG'] <= 0) {
//                        $apiRet['apiInfo'] = '添加失败,请填写正确的商品sku包装系数';
//                        return $apiRet;
//                    }
                    if (empty($v['systemSpec']['unit'])) {
                        $apiRet['apiInfo'] = '添加失败,请填写正确的商品单位';
                        return $apiRet;
                    }
//                    if ((float)$v['systemSpec']['purchase_price'] <= 0) {
//                        $apiRet['apiInfo'] = '添加失败,sku进货价必须大于0';
//                        return $apiRet;
//                    }
                    if (empty($v['selfSpec']) || empty($v['systemSpec'])) {
                        $apiRet['apiInfo'] = '添加失败,请填写完整的sku信息';
                        return $apiRet;
                    }
                    $skuBarcode = $v['systemSpec']['skuBarcode'];
                    if (!empty($skuBarcode)) {
                        $systemWhere = [];
                        $systemWhere['system.skuBarcode'] = $skuBarcode;
                        $systemWhere['system.dataFlag'] = 1;
                        $systemWhere['goods.goodsFlag'] = 1;
                        $systemWhere['goods.shopId'] = $shopId;
                        $systemWhere['shop.shopFlag'] = 1;
                        $systemInfo = $systemTab
                            ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
                            ->join('left join wst_shops shop on shop.shopId=goods.shopId')
                            ->where($systemWhere)
                            ->field('goods.goodsId,goods.goodsName,system.skuId')
                            ->find();
                        if ($systemInfo) {
                            $apiRet['apiCode'] = -1;
                            $apiRet['apiInfo'] = "商品sku编码【{$skuBarcode}】已存在，请更换其他编码";
                            $apiRet['apiState'] = 'error';
                            return $apiRet;
                        }
                    }
                }
            }
            $uniqueArr = array_unique($repeatSkuArr);
            $repeatArr = array_diff_assoc($repeatSkuArr, $uniqueArr);
            if (count($repeatArr) > 0) {
                $apiRet['apiInfo'] = 'sku编码不能重复';
                return $apiRet;
            }
            //$specAttrString = json_decode($accepSpecAttrString,true);
            $specAttrString = $specAttrArr;
            $selfSpecTab = M("sku_goods_self");
            $systemSpecTab = M("sku_goods_system");
            $systemWhere = [];
            $systemWhere['dataFlag'] = 1;
            $systemWhere['goodsId'] = $goodsId;
            //已存在的系统规格
            $existSpecSystem = $systemSpecTab->where($systemWhere)->select();
            if (count($existSpecSystem) > 0) {
                //检验是否有重复添加的sku组合
                foreach ($existSpecSystem as $key => &$value) {
                    $where = [];
                    $where['se.skuId'] = $value['skuId'];
                    $where['se.dataFlag'] = 1;
                    $value['specSelf'] = M("sku_goods_self se")
                        ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                        ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                        ->where($where)
                        ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName')
                        ->order('se.id asc')
                        ->select();
                    if ($value['specSelf']) {
                        $specSelfString = [];
                        $specAttrName = '';
                        foreach ($value['specSelf'] as $val) {
                            $specAttr = [];
                            $specAttr['specId'] = (int)$val['specId'];
                            $specAttr['attrId'] = (int)$val['attrId'];
                            $specSelfString[] = $specAttr;
                            $specAttrName .= $val['attrName'] . ",";
                        }
                        $value['specSelfString'] = json_encode($specSelfString);
                        $value['specAttrName'] = trim($specAttrName, ',');
                    }
                }
                unset($value);
            }
            $self_attr = array();
            foreach ($specAttrString as $skey => $value) {
                //$specAttrString[$skey]['attrId_str'] = [];
                foreach ($value as $vk => $vv) {
                    $self_attr[] = $vv['selfSpec'];
                    $attrId_str_info = [];
                    $newSpecAttrArr = [];
                    foreach ($vv['selfSpec'] as $wk => $wv) {
                        $specId = M('sku_spec_attr')->where(['attrId' => $wv['attrId']])->getField('specId');
                        $attrId = $wv['attrId'];
                        $newSpecAttrRow = [
                            'specId' => (int)$specId,
                            'attrId' => (int)$attrId,
                        ];
                        $newSpecAttrArr[] = $newSpecAttrRow;
                        $attrId_str_info[] = $attrId;
                    }
                    $specAttrString[$skey][$vk]['selfSpec'] = $newSpecAttrArr;
                    $nowSystemString = json_encode($specAttrString[$skey][$vk]['selfSpec']);
                    foreach ($existSpecSystem as $sv) {
                        if ($sv['specSelfString'] == $nowSystemString) {
                            $apiRet['apiInfo'] = '添加失败,' . $sv['specAttrName'] . '自定义属性组合已经存在';
                            return $apiRet;
                        }
                    }
                    //$specAttrString[$skey]['attrId_str'][] = $attrId_str_info;
                }
            }
            $unique_self_arr_str = array();
            foreach ($self_attr as $item) {
                $unique_self_arr_str[] = implode(',', array_column($item, 'attrId'));
            }
            $unique_arr = array_values(array_unique($unique_self_arr_str));
            // 获取重复数据的数组
            $repeat_arr = array_values(array_diff_assoc($unique_self_arr_str, $unique_arr));
            $attr_tab = M('sku_spec_attr');
            if (!empty($repeat_arr)) {
                foreach ($repeat_arr as $re_v) {
                    $attr_list = $attr_tab->where(array('attrId' => array('IN', $re_v), 'dataFlag' => 1))->select();
                    if (!empty($attr_list)) {
                        M()->rollback();
                        $repeat_attr_name = implode(',', array_column($attr_list, 'attrName'));
                        $apiRet['apiInfo'] = '添加失败,' . $repeat_attr_name . '自定义属性不能提交重复';
                        return $apiRet;
                    }
                }
            }
            M()->startTrans();
            $goodsModule = new GoodsModule();
            foreach ($specAttrString as $key => $value) {
//                if (!empty($value['attrId_str'])) {
//                    $unique_arr = array_unique($value['attrId_str']);
//                    $repeat_arr = array_diff_assoc($value['attrId_str'], $unique_arr);
//                    if ($repeat_arr) {
//                        foreach ($repeat_arr as $re_v) {
//                            $attr_list = $attr_tab->where(array('attrId' => array('IN', $re_v), 'dataFlag' => 1))->select();
//                            if (!empty($attr_list)) {
//                                M()->rollback();
//                                $repeat_attr_name = implode('，', array_column($attr_list, 'attrName'));
//                                $apiRet['apiInfo'] = '添加失败,' . $repeat_attr_name . '自定义属性不能提交重复';
//                                return $apiRet;
//                            }
//                        }
//
//                    }
//                }
                //系统规格
                foreach ($value as $vk => $vv) {
                    $systemSpec = $vv['systemSpec'];
                    $systemSpec = $this->checkSystemSkuValue($systemSpec);
                    $systemSpec['goodsId'] = $goodsId;
                    $systemSpec['addTime'] = date('Y-m-d H:i:s', time());
                    $skuId = $systemSpecTab->add($systemSpec);
                    if ($skuId) {
                        //自定义规格
                        $selfSpec = $vv['selfSpec'];
                        foreach ($selfSpec as $sk => $sv) {
                            $selfSpec = [];
                            $selfSpec['skuId'] = $skuId;
                            $selfSpec['specId'] = $sv['specId'];
                            $selfSpec['attrId'] = $sv['attrId'];
                            $insertGoodsSelf = $selfSpecTab->add($selfSpec);
                        }
                        //sku身份价格-start
                        $rankArr = $vv['rankArr'];
                        $saveGoodsRankRes = $goodsModule->addGoodsRank($goodsId, $skuId, $rankArr, M());
                        if ($saveGoodsRankRes['code'] != ExceptionCodeEnum::SUCCESS) {
                            M()->rollback();
                            $apiRet['apiInfo'] = "操作失败，身份价格更新失败";
                            return $apiRet;
                        }
                        //sku身份价格-end
                    }
                }
            }
            if ($insertGoodsSelf) {
                M()->commit();
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '添加数据成功';
                $apiRet['apiState'] = 'success';
            } else {
                M()->rollback();
            }
        }
        return $apiRet;
    }


    /*
     * 编辑商品sku封装
     *@param int goodsId
     *@param array specAttrArr
     * */
    public function editGoodsSkuModel($goodsId, $specAttrArr, $shopId = 0)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if (empty($specAttrArr)) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '无规格可以添加';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $specAttrString = $specAttrArr;
        $selfSpecTab = M("sku_goods_self");
        $systemSpecTab = M("sku_goods_system");
        $systemWhere = [];
        $systemWhere['dataFlag'] = 1;
        $systemWhere['goodsId'] = $goodsId;
        $existSpecSystem = $systemSpecTab->where($systemWhere)->select();//已存在的系统规格

        //后加
        $systemTab = M('sku_goods_system system');
        $newSpecAttrArr = [];
        foreach ($specAttrString as $skey => $value) {
            foreach ($value as $wk => $wv) {
//                if ($wv['systemSpec']['skuGoodsStock'] <= 0) {
//                    $apiRet['apiCode'] = -1;
//                    $apiRet['apiInfo'] = '商品sku库存不能为空，请检查';
//                    $apiRet['apiState'] = 'error';
//                    return $apiRet;
//                }
//                if ((float)$wv['systemSpec']['weigetG'] <= 0) {
//                    $apiRet['apiCode'] = -1;
//                    $apiRet['apiInfo'] = '请填写正确的商品包装系数';
//                    $apiRet['apiState'] = 'error';
//                    return $apiRet;
//                }
                if (empty($wv['systemSpec']['unit'])) {
                    $apiRet['apiCode'] = -1;
                    $apiRet['apiInfo'] = '请填写正确的商品sku单位';
                    $apiRet['apiState'] = 'error';
                    return $apiRet;
                }
//                if ((float)$wv['systemSpec']['purchase_price'] <= 0) {
//                    $apiRet['apiCode'] = -1;
//                    $apiRet['apiInfo'] = 'sku进货价必须大于0';
//                    $apiRet['apiState'] = 'error';
//                    return $apiRet;
//                }
                $systemSpec = $wv['systemSpec'];
                $skuBarcode = $systemSpec['skuBarcode'];
                if (!empty($skuBarcode)) {
                    $systemWhere = [];
                    $systemWhere['system.skuBarcode'] = $skuBarcode;
                    $systemWhere['system.dataFlag'] = 1;
                    $systemWhere['goods.goodsFlag'] = 1;
                    $systemWhere['goods.shopId'] = $shopId;
                    $systemWhere['shop.shopFlag'] = 1;
                    $systemInfo = $systemTab
                        ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
                        ->join('left join wst_shops shop on shop.shopId=goods.shopId')
                        ->where($systemWhere)
                        ->field('goods.goodsId,goods.goodsName,system.skuId')
                        ->find();
                    if ($systemInfo && $systemInfo['goodsId'] != $goodsId) {
                        $apiRet['apiCode'] = -1;
                        $apiRet['apiInfo'] = "商品sku编码【{$skuBarcode}】已存在，请更换其他编码";
                        $apiRet['apiState'] = 'error';
                        return $apiRet;
                    }
                    if ($systemInfo && $systemInfo['skuId'] != $wv['skuId']) {
                        $apiRet['apiCode'] = -1;
                        $apiRet['apiInfo'] = "商品sku编码【{$skuBarcode}】已存在，请更换其他编码";
                        $apiRet['apiState'] = 'error';
                        return $apiRet;
                    }
                }
                foreach ($wv['selfSpec'] as $yk => $yv) {
                    $specId = M('sku_spec_attr')->where(['attrId' => $yv['attrId']])->getField('specId');
                    $attrId = $yv['attrId'];
                    $newSpecAttrRow = [
                        'specId' => (int)$specId,
                        'attrId' => (int)$attrId,
                    ];
                    $newSpecAttrArr[] = $newSpecAttrRow;
                }
            }
            $specAttrString[$skey][$wk]['selfSpec'] = $newSpecAttrArr;
        }
        if (count($existSpecSystem) > 0) {
            //检验是否有重复添加的sku组合
            foreach ($existSpecSystem as $key => &$value) {
                $where = [];
                $where['se.skuId'] = $value['skuId'];
                $where['se.dataFlag'] = 1;
                $value['specSelf'] = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where($where)
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName')
                    ->order('se.id asc')
                    ->select();
                if ($value['specSelf']) {
                    $specSelfString = [];
                    $specAttrName = '';
                    foreach ($value['specSelf'] as $val) {
                        $specAttr = [];
                        $specAttr['specId'] = (int)$val['specId'];
                        $specAttr['attrId'] = (int)$val['attrId'];
                        $specSelfString[] = $specAttr;
                        $specAttrName .= $val['attrName'] . ",";
                    }
                    $value['specSelfString'] = json_encode($specSelfString);
                    $value['specAttrName'] = trim($specAttrName, ',');
                }
            }
            unset($value);
            foreach ($specAttrString as $value) {
                foreach ($value as $wk => $wv) {
                    //新增
                    if (empty($wv['skuId'])) {
                        $nowSystemString = json_encode($wv['selfSpec']);
                        foreach ($existSpecSystem as $sv) {
                            if ($sv['specSelfString'] == $nowSystemString) {
                                $apiRet['apiInfo'] = '操作失败,' . $sv['specAttrName'] . '自定义属性组合已经存在';
                                return $apiRet;
                            }
                        }
                    }
                }
            }
        }
        M()->startTrans();
        $goodsModule = new GoodsModule();
        $attr_tab = M('sku_spec_attr');
        foreach ($specAttrString as $key => $value) {
            foreach ($value as $wk => $wv) {
                $systemSpec = $wv['systemSpec'];
                $systemSpec = $this->checkSystemSkuValue($systemSpec);
                if (empty($wv['skuId'])) {
                    //新增
                    $systemSpec['goodsId'] = $goodsId;
                    $systemSpec['addTime'] = date('Y-m-d H:i:s', time());
                    $skuId = $systemSpecTab->add($systemSpec);
                } else {
                    //编辑
                    $skuId = $wv['skuId'];
                    $systemSpecTab->where(['skuId' => $skuId])->save($systemSpec);
                }
                if ($skuId) {
                    $selfSpec = $wv['selfSpec'];
                    $attrId_arr = array_column($selfSpec, 'attrId');
                    $attr_list = $attr_tab->where(array('attrId' => array('IN', $attrId_arr), 'dataFlag' => 1))->select();
                    $attr_name = implode(',', array_column($attr_list, 'attrName'));
                    foreach ($existSpecSystem as $exist_key => $exist_val) {
                        if ($attr_name == $exist_val['specAttrName'] && $exist_val['skuId'] != $skuId) {
                            M()->rollback();
                            $apiRet['apiInfo'] = '操作失败,' . $attr_name . '自定义属性组合已经存在';
                            return $apiRet;
                        }
                    }
                    $selfSpecTab->where(['skuId' => $skuId])->delete();
                    foreach ($selfSpec as $sk => $sv) {
                        $selfSpec = [];
                        $selfSpec['skuId'] = $skuId;
                        $selfSpec['specId'] = $sv['specId'];
                        $selfSpec['attrId'] = $sv['attrId'];
                        $insertGoodsSelf = $selfSpecTab->add($selfSpec);
                    }
                }
                //sku身份价格-start
                $rankArr = $wv['rankArr'];
                $saveGoodsRankRes = $goodsModule->addGoodsRank($goodsId, $skuId, $rankArr, M());
                if ($saveGoodsRankRes['code'] != ExceptionCodeEnum::SUCCESS) {
                    M()->rollback();
                    $apiRet['apiInfo'] = "操作失败，身份价格更新失败";
                    return $apiRet;
                }
                //sku身份价格-end

            }
        }
        if ($insertGoodsSelf) {
            M()->commit();
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '编辑数据成功';
            $apiRet['apiState'] = 'success';
        } else {
            M()->rollback();
        }
        return $apiRet;
    }

    /**
     * 根据skuId获取sku信息详情
     * @param int $skuId
     * */
    public function getSkuDetailSkuId(int $skuId)
    {
        if (empty($skuId)) {
            return [];
        }
        $systemTab = M('sku_goods_system');
        $selfTab = M('sku_goods_self self');
        $where = [];
        $where['skuId'] = $skuId;
        $where['dataFlag'] = 1;
        $systemInfo = $systemTab->where($where)->find();
        if (empty($systemInfo)) {
            return [];
        }
        $selfSpec = $selfTab
            ->join("left join wst_sku_spec sp on self.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=self.attrId")
            ->where(['self.skuId' => $systemInfo['skuId'], 'self.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
            ->field('self.id,self.skuId,self.specId,self.attrId,sp.specName,sr.attrName')
            ->order('sp.sort asc')
            ->select();
        if (empty($selfSpec)) {
            return [];
        }
        $systemInfo['selfSpec'] = $selfSpec;
        $specAttrNameStr = '';
        foreach ($selfSpec as $key => $value) {
            $specAttrNameStr .= $value['attrName'] . ',';
        }
        $systemInfo['specAttrNameStr'] = rtrim($specAttrNameStr, ',');
        return $systemInfo;
    }
}

?>