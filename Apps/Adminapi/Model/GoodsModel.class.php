<?php

namespace Adminapi\Model;

use App\Modules\Goods\GoodsServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品服务类
 */
class GoodsModel extends BaseModel
{

    /**
     * 获取商品信息
     */
    public function get()
    {
        $id = (int)I('id', 0);

        $goodsServiceModel = new GoodsServiceModule();
        $goodsInfo = $goodsServiceModel->getGoodsCat($id);
        if ($goodsInfo['code'] != 0) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $goods = $goodsInfo['data'];

        $goodsGalleryList = $goodsServiceModel->getGoodsGalleryList($id);//获取相册
        $goods['gallery'] = $goodsGalleryList['data'];

        $goodsSku = $goodsServiceModel->getGoodsSku($id);//根据商品ID获取商品sku信息
        $goods['goodsSku'] = $goodsSku['data'];

        return returnData($goods);
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return array
     * 分页列表[获取待审核列表]
     */
    public function queryPendDingByPage($page = 1, $pageSize = 15)
    {
        $shopName = WSTAddslashes(I('shopName'));
        $goodsName = WSTAddslashes(I('goodsName'));
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $goodsCatId1 = (int)I('goodsCatId1', 0);
        $goodsCatId2 = (int)I('goodsCatId2', 0);
        $goodsCatId3 = (int)I('goodsCatId3', 0);
        $field = "g.*,gc.catName,sc.catName shopCatName,p.shopName";
        $sql = "select {$field} from __PREFIX__goods g 
	 	      left join __PREFIX__goods_cats gc on g.goodsCatId3 = gc.catId 
	 	      left join __PREFIX__shops_cats sc on sc.catId = g.shopCatId2,__PREFIX__shops p 
	 	      where goodsStatus = 0 and goodsFlag = 1 and p.shopId = g.shopId and isBecyclebin = 0 ";
        if ($areaId1 > 0) $sql .= " and p.areaId1=" . $areaId1;
        if ($areaId2 > 0) $sql .= " and p.areaId2=" . $areaId2;
        if ($goodsCatId1 > 0) $sql .= " and g.goodsCatId1=" . $goodsCatId1;
        if ($goodsCatId2 > 0) $sql .= " and g.goodsCatId2=" . $goodsCatId2;
        if ($goodsCatId3 > 0) $sql .= " and g.goodsCatId3=" . $goodsCatId3;
        if ($shopName != '') $sql .= " and (p.shopName like '%" . $shopName . "%' or p.shopSn like '%" . $shopName . "%')";
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%')";
        $sql .= "  order by goodsId desc";
        return $this->pageQuery($sql, $page, $pageSize);
    }

    /**
     * 分页列表[获取已审核列表] 条件查询
     */
    public function queryByPage($params)
    {
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = "goods.goodsFlag = 1 and goods.goodsStatus = 1 ";

        $whereFind = [];
        //顶级商品分类ID
        $whereFind['goods.goodsCatId1'] = function () use ($params) {
            if (empty($params['goodsCatId1'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId1']}", 'and'];
        };
        //第二级商品分类ID
        $whereFind['goods.goodsCatId2'] = function () use ($params) {
            if (empty($params['goodsCatId2'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId2']}", 'and'];
        };
        //第三级商品分类ID
        $whereFind['goods.goodsCatId3'] = function () use ($params) {
            if (empty($params['goodsCatId3'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId3']}", 'and'];
        };
        //店铺商品分类第一级
        $whereFind['goods.shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return ['=', "{$params['shopCatId1']}", 'and'];
        };
        //店铺商品分类第二级
        $whereFind['goods.shopCatId2'] = function () use ($params) {
            if (empty($params['shopCatId2'])) {
                return null;
            }
            return ['=', "{$params['shopCatId2']}", 'and'];
        };
        //是否上架(0:不上架 1:上架)
        $whereFind['goods.isSale'] = function () use ($params) {
            if (!is_numeric($params['isSale'])) {
                return null;
            }
            return ['=', "{$params['isSale']}", 'and'];
        };
        //商品状态(-1:禁售 0:未审核 1:已审核)
        $whereFind['goods.goodsStatus'] = function () use ($params) {
            if (!is_numeric($params['goodsStatus'])) {
                return null;
            }
            return ['=', "{$params['goodsStatus']}", 'and'];
        };
        //称重补差价[-1：否 1：是]
        $whereFind['goods.SuppPriceDiff'] = function () use ($params) {
            if (!is_numeric($params['SuppPriceDiff'])) {
                return null;
            }
            return ['=', "{$params['SuppPriceDiff']}", 'and'];
        };
        //非常规商品条件
        $whereFind['goods.isConvention'] = function () use ($params) {
            if (!is_numeric($params['isConvention'])) {
                return null;
            }
            return ['=', "{$params['isConvention']}", 'and'];
        };
        //所属省份ID
        $whereFind['shops.areaId1'] = function () use ($params) {
            if (!is_numeric($params['areaId1'])) {
                return null;
            }
            return ['=', "{$params['areaId1']}", 'and'];
        };
        //所属市区ID
        $whereFind['shops.areaId2'] = function () use ($params) {
            if (!is_numeric($params['areaId2'])) {
                return null;
            }
            return ['=', "{$params['areaId2']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = " {$where} and {$whereFind} ";
        }
        //商品名称|编码
        if (!empty($params['keywords'])) {
            $whereInfo .= " and (goods.goodsName like '%{$params['keywords']}%' or goods.goodsSn like '%{$params['keywords']}%') ";
        }
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $whereInfo .= " and (shops.shopName like '%{$params['shopWords']}%' or shops.shopSn like '%{$params['shopWords']}%') ";
        }
        $field = 'goods.goodsId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.shopId,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.stockWarningNum,goods.isSale,goods.shopGoodsSort,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3,goods.shopCatId1,goods.shopCatId2  ';
        $field .= ' ,shops.shopSn,shops.areaId1,shops.areaId2,shops.areaId3,shops.shopName ';
        //【goodsList:商品列表|soldOut:已售罄商品|earlyWarning:预警商品|recyclebin:商品回收站】
        if ($params['code'] == 'soldOut') {
            //已售罄商品
            $whereInfo .= " and goods.isBecyclebin=0 ";
            $whereInfo .= " and goods.goodsStock <= 0 ";
            $sql = "select {$field} from __PREFIX__goods goods left join __PREFIX__shops shops on shops.shopId = goods.shopId where {$whereInfo} ";
            $sql .= " order by goods.shopGoodsSort desc";
        } elseif ($params['code'] == 'earlyWarning') {
            //预警商品
            $whereInfo .= " and goods.isBecyclebin = 0";
            $whereInfo .= " and goods.goodsStock <= goods.stockWarningNum ";
            $sql = "select {$field} from __PREFIX__goods goods left join __PREFIX__shops shops on shops.shopId = goods.shopId where {$whereInfo} ";
            $sql .= " order by goods.shopGoodsSort desc";
        } elseif ($params['code'] == 'recyclebin') {
            //商品回收站
            $whereInfo .= " and bin.status = 0 and bin.tableName = 'wst_goods' ";
            $sql = "select {$field} from __PREFIX__recycle_bin bin left join __PREFIX__goods goods on goods.goodsId = bin.dataId left join __PREFIX__shops shops on shops.shopId = goods.shopId where {$whereInfo} ";
            $sql .= " order by bin.id desc";
        } else {
            //商品列表
            $whereInfo .= " and goods.isBecyclebin = 0 ";
            $sql = "select {$field} from __PREFIX__goods goods left join __PREFIX__shops shops on shops.shopId = goods.shopId where {$whereInfo} ";
            $sql .= " order by goods.createTime asc";
        }
        if ((int)$params['export'] == 1) {
            $list['root'] = (array)$this->query($sql);
        } elseif (empty($params['specId'])) {
            $list = $this->pageQuery($sql, $page, $pageSize);
        } elseif (!empty($params['specId'])) {
            $list['root'] = (array)$this->query($sql);
        }
        //添加sku规格属性查询===============start==========================================
        if (!empty($params['specId']) && !empty($list['root'])) {
            $systemTab = M('sku_goods_system');
            $whereSku = [];
            $whereSku['specId'] = $params['specId'];
            if (!empty($params['attrId'])) {
                $whereSku['attrId'] = $params['attrId'];
            }
            $skuList = [];
            foreach ($list['root'] as $k => $v) {
                $systemSpec = $systemTab->where(['goodsId' => $v['goodsId'], 'dataFlag' => 1])->select();
                if (!empty($systemSpec)) {
                    $skuInfo = array_unique(array_get_column($systemSpec, 'skuId'));
                    $whereSku['skuId'] = ['IN', $skuInfo];
                    $whereSku['dataFlag'] = 1;
                    $selfSpec = M("sku_goods_self")->where($whereSku)->find();
                    $v['specId'] = $selfSpec['specId'];
                    $v['attrId'] = $selfSpec['attrId'];
                    if (!empty($selfSpec)) {
                        $skuList[] = $v;
                    }
                }
            }
            $count = count($skuList);
            $pageData = array_slice($skuList, ($page - 1) * $pageSize, $pageSize);
            $list['total'] = $count;
            $list['pageSize'] = $pageSize;
            $list['start'] = ($page - 1) * $pageSize;
            $list['root'] = $pageData;
            $list['totalPage'] = ($list['total'] % $pageSize == 0) ? ($list['total'] / $pageSize) : (intval($list['total'] / $pageSize) + 1);
            $list['currPage'] = $page;
        }
        //===============================end==========================================================
        $goodsModel = new GoodsServiceModule();
        if (!empty($list['root'])) {
            $goods = $list['root'];
            $shopCatIdArr = [];//店铺分类id
            $goodsCatIdArr = [];//商城分类id
            foreach ($goods as $value) {
                $shopCatIdArr[] = $value['shopCatId1'];
                $shopCatIdArr[] = $value['shopCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId1'];
                $goodsCatIdArr[] = $value['goodsCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId3'];
            }
            $shopCatIdArr = array_unique($shopCatIdArr);
            $shopCatIdStr = implode(',', $shopCatIdArr);
            $shopCatLists = $goodsModel->getShopCatList($shopCatIdStr);
            $shopCatList = $shopCatLists['data'];

            $goodsCatIdArr = array_unique($goodsCatIdArr);
            $goodsCatIdStr = implode(',', $goodsCatIdArr);
            $goodsCatLists = $goodsModel->getGoodsCatList($goodsCatIdStr);
            $goodsCatIdList = $goodsCatLists['data'];

            foreach ($goods as $key => $value) {
                $where = [];
                $where['dataFlag'] = 1;
                $where['goodsId'] = $value['goodsId'];
                foreach ($shopCatList as $shopCat) {//店铺商品分类
                    if ($value['shopCatId1'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId1Name'] = $shopCat['catName'];
                    }
                    if ($value['shopCatId2'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId2Name'] = $shopCat['catName'];
                    }
                }

                foreach ($goodsCatIdList as $goodsCat) {//商品分类
                    if ($value['goodsCatId1'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId1Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId2'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId2Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId3'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId3Name'] = $goodsCat['catName'];
                    }
                }
            }
            $list['root'] = $goods;
        }
        if ((int)$params['export'] == 1) {
            $this->exportGoods($list['root']);
        }
        return $list;
    }

    /**
     * 导出商品
     */
    public function exportGoods($goodsData)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $objPHPExcel = new \PHPExcel();
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("goodsList")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
        //设置excel工作表名及文件名
        $title = '商品列表';
        $excel_filename = '商品列表_' . date('Ymd_His');
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        //第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $excel_filename);
        //合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
        //设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        //设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置excel的表头
//          $sheet_title = array('商品ID','门店ID','商品编号','商品名称','商品图片','商品缩略图','店铺价格','商品总库存','销售量','是否上架','是否店铺精品','是否热销产品','是否精品','是否新品','上架时间','创建时间','称重补差价','店铺商品排序');
        $sheet_title = array('商品ID', '商品编号', '商品名称', '市场价格', '会员价格', '店铺价格', '进货价格', '库存', '库存预警', 'sku-ID', 'sku-店铺价格', 'sku-会员价格', 'sku-单价', 'sku-库存', '推荐', '精品', '新品', '热销', '会员专享', '商品分销', '一级分销金', '二级分销金');
        // 设置第一行和第一行的行高
//          $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
//          $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
//          $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V');
        //设置单元格
//          $objPHPExcel->getActiveSheet()->getStyle('A2:AC2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //首先是赋值表头
        for ($k = 0; $k < 22; $k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        }
        //开始赋值
        for ($i = 0; $i < count($goodsData); $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $goodsData[$i];
            for ($j = 0; $j < 22; $j++) {
                switch ($j) {
                    case 0 :
                        //商品ID
                        $cellvalue = $temp['goodsId'];
                        break;
                    case 1 :
                        //商品编号
                        $cellvalue = $temp['goodsSn'];
                        break;
                    case 2 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 3 :
                        //市场价格
                        $cellvalue = $temp['marketPrice'];
                        break;
                    case 4 :
                        //会员价格
                        $cellvalue = $temp['memberPrice'];
                        break;
                    case 5 :
                        //店铺价格
                        $cellvalue = $temp['shopPrice'];
                        break;
                    case 6 :
                        //进货价格
                        $cellvalue = $temp['goodsUnit'];
                        break;
                    case 7 :
                        //库存
                        $cellvalue = $temp['goodsStock'];
                        break;
                    case 8 :
                        //库存预警
                        $cellvalue = $temp['stockWarningNum'];
                        break;
                    case 9 :
                        //sku-Id
                        $goodsAttrData = $temp['goodsAttrData'];
                        $cellvalue = '';
                        if (!empty($goodsAttrData)) {
                            foreach ($goodsAttrData as $v) {
                                $cellvalue .= $v['skuId'] . " ";
                            }
                        }
                        $cellvalue = rtrim($cellvalue);
                        break;
                    case 10 :
                        //sku-店铺价格
                        $goodsAttrData = $temp['goodsAttrData'];
                        $cellvalue = '';
                        if (!empty($goodsAttrData)) {
                            foreach ($goodsAttrData as $v) {
                                $cellvalue .= $v['skuShopPrice'] . " ";
                            }
                        }
                        $cellvalue = rtrim($cellvalue);
                        break;
                    case 11 :
                        //sku-会员价格
                        $goodsAttrData = $temp['goodsAttrData'];
                        $cellvalue = '';
                        if (!empty($goodsAttrData)) {
                            foreach ($goodsAttrData as $v) {
                                $cellvalue .= $v['skuMemberPrice'] . " ";
                            }
                        }
                        $cellvalue = rtrim($cellvalue);
                        break;
                    case 12 :
                        //sku-单价
                        $goodsAttrData = $temp['goodsAttrData'];
                        $cellvalue = '';
                        if (!empty($goodsAttrData)) {
                            foreach ($goodsAttrData as $v) {
                                $cellvalue .= $v['UnitPrice'] . " ";
                            }
                        }
                        $cellvalue = rtrim($cellvalue);
                        break;
                    case 13 :
                        //sku-库存
                        $goodsAttrData = $temp['goodsAttrData'];
                        $cellvalue = '';
                        if (!empty($goodsAttrData)) {
                            foreach ($goodsAttrData as $v) {
                                $cellvalue .= $v['skuGoodsStock'] . " ";
                            }
                        }
                        $cellvalue = rtrim($cellvalue);
                        break;
                    case 14 :
                        //推荐
                        $cellvalue = (int)$temp['isRecomm'];
                        break;
                    case 15 :
                        //精品
                        $cellvalue = (int)$temp['isBest'];
                        break;
                    case 16 :
                        //新品
                        $cellvalue = (int)$temp['isNew'];
                        break;
                    case 17 :
                        //热销
                        $cellvalue = (int)$temp['isHot'];
                        break;
                    case 18 :
                        //会员专享
                        $cellvalue = (int)$temp['isMembershipExclusive'];
                        break;
                    case 19 :
                        //分销商品
                        $cellvalue = (int)$temp['isDistribution'];
                        break;
                    case 20 :
                        //一级分销金额
                        $cellvalue = (float)$temp['firstDistribution'];
                        break;
                    case 21 :
                        //二级分销金额
                        $cellvalue = (float)$temp['SecondaryDistribution'];
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
        }
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle($title);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 获取列表
     */
    public function queryByList()
    {
        $sql = "select * from __PREFIX__goods order by goodsId desc";
        return $this->pageQuery($sql);
    }

    /**
     * 修改商品状态
     */
    public function changeGoodsStatus($loginUserInfo)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = (int)I('id', 0);
        $where = [];
        $where['goodsFlag'] = 1;
        $where['goodsId'] = $id;
        $goodsInfo = $this->where($where)->find();
        $this->goodsStatus = (int)I('status', 0);
        if (I('status') == -1) {
            $this->isSale = 0;
        }
        $rs = $this->where('goodsId=' . $id)->save();
        if (false !== $rs) {
            $sql = "select goodsName,userId from __PREFIX__goods g,__PREFIX__shops s where g.shopId=s.shopId and g.goodsId=" . $id;
            $goods = $this->query($sql);
            $msg = "";
            if (I('status', 0) == 0) {
                $msg = "商品[" . $goods[0]['goodsName'] . "]已被商城下架";
            } else {
                $msg = "商品[" . $goods[0]['goodsName'] . "]已通过审核";
            }
            $yj_data = array(
                'msgType' => 0,
                'sendUserId' => session('WST_STAFF.staffId'),
                'receiveUserId' => $goods[0]['userId'],
                'msgContent' => $msg,
                'createTime' => date('Y-m-d H:i:s'),
                'msgStatus' => 0,
                'msgFlag' => 1,
            );
            M('messages')->add($yj_data);
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
            if (I('status') == -1) {
                $this->addGoodsLog($loginUserInfo, $goodsInfo, '商品禁售');
            } elseif (I('status') == 1) {
                $this->addGoodsLog($loginUserInfo, $goodsInfo, '商品审核通过');
            }
        }
        return $rd;
    }

    /**
     * @param $loginUserInfo
     * @return mixed
     * 商品审核
     */
    public function changeGoodsStatusNew($loginUserInfo)
    {
        $goodsId = I('id', 0);
        $status = I('status');//商品状态（-1:禁售 0:未审核 1:已审核）
        if (empty($goodsId)) {
            return returnData(false, -1, 'error', '请选择操作商品');
        }
        if (empty($status)) {
            return returnData(false, -1, 'error', '请选择变更状态');
        }
        $where = [];
        $where['g.goodsFlag'] = 1;
        $where['g.goodsId'] = ['IN', $goodsId];
        $goodsList = M('goods g')
            ->join('left join wst_shops s on s.shopId = g.shopId')
            ->where($where)
            ->field('g.*,s.userId')
            ->select();
        $goodsIds = array_get_column($goodsList, 'goodsId');
        $saveWhere = [];
        $saveWhere['goodsId'] = ['IN', $goodsIds];
        $save = [];
        $save['goodsStatus'] = $status;//商品状态(-1:禁售 0:未审核 1:已审核)
        if ($status == -1) {
            $save['isSale'] = 0;//是否上架(0:不上架 1:上架)
        }
        $rs = M('goods')->where($saveWhere)->save($save);
        if (false !== $rs) {
            $goodsOperationLog = [];
            foreach ($goodsList as $k => $v) {
                if ($status == -1) {
                    $msg = "商品[" . $v['goodsName'] . "]已被商城下架";
                } else {
                    $msg = "商品[" . $v['goodsName'] . "]已通过审核";
                }
                $yj_data = array(
                    'msgType' => 0,
                    'sendUserId' => session('WST_STAFF.staffId'),
                    'receiveUserId' => $v['userId'],
                    'msgContent' => $msg,
                    'createTime' => date('Y-m-d H:i:s'),
                    'msgStatus' => 0,
                    'msgFlag' => 1,
                );
                $goodsOperationLog[] = $yj_data;
                if ($status == -1) {
                    $this->addGoodsLog($loginUserInfo, $v, '商品禁售');
                } elseif (I('status') == 1) {
                    $this->addGoodsLog($loginUserInfo, $v, '商品审核通过');
                }
                $describe = "[{$loginUserInfo['loginName']}]编辑了{$msg}";
                addOperationLog($loginUserInfo['loginName'], $loginUserInfo['staffId'], $describe, 3);
            }
            M('messages')->addAll($goodsOperationLog);
        }
        return returnData(true);
    }

    /**
     * 添加商品日志
     * @param array $actionUserInfo
     * @param array $primaryGoodsInfo 原商品信息
     * @param string remark 操作描述
     * @return int $logId
     * */
    public function addGoodsLog(array $actionUserInfo, array $primaryGoodsInfo, $remark = '')
    {
        if (empty($primaryGoodsInfo['goodsId'])) {
            return 0;
        }
        $tab = M('goods_log');
        $goodsTab = M('goods');
        $where = [];
        $where['goodsId'] = $primaryGoodsInfo['goodsId'];
        $nowGoodsInfo = $goodsTab->where($where)->find();
        if (empty($nowGoodsInfo)) {
            return false;
        }
        $data = [
            'goodsId' => (int)$nowGoodsInfo['goodsId'],
            'primaryShopPrice' => (float)$primaryGoodsInfo['shopPrice'],
            'nowShopPrice' => (float)$nowGoodsInfo['shopPrice'],
            'primaryMemberPrice' => (float)$primaryGoodsInfo['memberPrice'],
            'nowMemberPrice' => (float)$nowGoodsInfo['memberPrice'],
            'primaryBuyPrice' => (float)$primaryGoodsInfo['goodsUnit'],
            'nowBuyPrice' => (float)$nowGoodsInfo['goodsUnit'],
            'primaryIntegralReward' => (float)$primaryGoodsInfo['integralReward'],
            'nowIntegralReward' => (int)$nowGoodsInfo['integralReward'],
            'primaryGoodsStock' => (float)$primaryGoodsInfo['goodsStock'],
            'nowGoodsStock' => (float)$nowGoodsInfo['goodsStock'],
            'primarySaleStatus' => (int)$primaryGoodsInfo['isSale'],
            'nowSaleStatus' => (int)$nowGoodsInfo['isSale'],
            'primaryGoodsStatus' => (float)$primaryGoodsInfo['goodsStatus'],
            'nowGoodsStatus' => (int)$nowGoodsInfo['goodsStatus'],
            'actionUserId' => (int)$actionUserInfo['user_id'],
            'actionUserName' => (string)$actionUserInfo['user_username'],
            'createTime' => date('Y-m-d H:i:s'),
            'remark' => $remark,
        ];
        $insertId = $tab->add($data);
        return (int)$insertId;
    }

    /**
     * 获取待审核的商品数量
     */
    public function queryPenddingGoodsNum()
    {
        $rd = array('status' => -1);
        $sql = "select count(*) counts from __PREFIX__goods where goodsStatus = 0 and goodsFlag = 1 and isSale = 1";
        $rs = $this->query($sql);
        $rd['num'] = $rs[0]['counts'];
        return $rd;
    }

    /**
     * @param $loginUserInfo
     * @return array
     * 批量修改精品状态
     */
    public function changeBestStatus($loginUserInfo)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0);
        $id = self::formatIn(",", $id);
        $this->isAdminBest = (int)I('status', 0);
        $rs = $this->where('goodsId in(' . $id . ")")->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
            $describe = "[{$loginUserInfo['loginName']}]批量修改了精品状态,商品id:[{$id}]";
            addOperationLog($loginUserInfo['loginName'], $loginUserInfo['staffId'], $describe, 3);
        }
        return $rd;
    }

    /**
     * @param $loginUserInfo
     * @return array
     * 批量修改推荐状态
     */
    public function changeRecomStatus($loginUserInfo)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0);
        $id = self::formatIn(",", $id);
        $this->isAdminRecom = (int)I('status', 0);
        $rs = $this->where('goodsId in(' . $id . ")")->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
            $describe = "[{$loginUserInfo['loginName']}]批量修改了精品状态,商品id:[{$id}]";
            addOperationLog($loginUserInfo['loginName'], $loginUserInfo['staffId'], $describe, 3);
        }
        return $rd;
    }

    /**
     * 批量修改商品秒杀状态
     */
    public function setGoodsSecKillStatus()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0);
        $id = self::formatIn(",", $id);

        $isDeckillId = explode(",", $id);

        for ($i = 0; $i < count($isDeckillId); $i++) {
            $where['goodsId'] = $isDeckillId[$i];
            $goodsRes = $this->where($where)->find();
            //return $goodsRes['isShopSecKill'];
            if ($goodsRes['isShopSecKill'] == 0) {
                $rd = array('code' => -2, 'msg' => '商品#' . $goodsRes['goodsName'] . '#未在店铺中设置为秒杀。店铺设置秒杀状态后，后台才能进行秒杀设置');
                return $rd;

            }
        }


        $isAdminShopSecKill = (int)I('isAdminShopSecKill', 0);
        $AdminShopGoodSecKillStartTime = I('AdminShopGoodSecKillStartTime');
        $AdminShopGoodSecKillEndTime = I('AdminShopGoodSecKillEndTime');
        if (!empty($isAdminShopSecKill)) {
            if (empty($AdminShopGoodSecKillStartTime) || empty($AdminShopGoodSecKillEndTime)) {
                $rd['msg'] = '参数不全';
                return $rd;
            }
        }


        $this->isAdminShopSecKill = $isAdminShopSecKill;
        $this->AdminShopGoodSecKillStartTime = $AdminShopGoodSecKillStartTime;
        $this->AdminShopGoodSecKillEndTime = $AdminShopGoodSecKillEndTime;
        //$this->goodsStock = (int)I('goodsStock'); 后台不修改库存

        $rs = $this->where('goodsId in(' . $id . ")")->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }


    /**
     * 批量修改商品预售状态
     */
    public function setGoodsPreSaleStatus()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0);
        $id = self::formatIn(",", $id);

        $isDeckillId = explode(",", $id);

        for ($i = 0; $i < count($isDeckillId); $i++) {
            $where['goodsId'] = $isDeckillId[$i];
            $goodsRes = $this->where($where)->find();
            //return $goodsRes['isShopSecKill'];
            if ($goodsRes['isShopPreSale'] == 0) {
                $rd = array('code' => -2, 'msg' => '商品#' . $goodsRes['goodsName'] . '#未在店铺中设置为预售。店铺设置预售状态后，后台才能进行预售设置');
                return $rd;

            }

            if ($goodsRes['isShopSecKill'] == 1) {
                $rd = array('code' => -2, 'msg' => '商品#' . $goodsRes['goodsName'] . '#已在店铺中设置为秒杀，秒杀和预售不能同时进行！');
                return $rd;
            }

        }

        $isAdminShopPreSale = (int)I('isAdminShopPreSale', 0);
        $AdminShopGoodPreSaleStartTime = I('AdminShopGoodPreSaleStartTime');
        $AdminShopGoodPreSaleEndTime = I('AdminShopGoodPreSaleEndTime');
        if (!empty($isAdminShopPreSale)) {
            if (empty($AdminShopGoodPreSaleStartTime) || empty($AdminShopGoodPreSaleEndTime)) {
                $rd['msg'] = '参数不全';
                return $rd;
            }
        }

        $this->isAdminShopPreSale = $isAdminShopPreSale;
        $this->AdminShopGoodPreSaleStartTime = $AdminShopGoodPreSaleStartTime;
        $this->AdminShopGoodPreSaleEndTime = $AdminShopGoodPreSaleEndTime;
        //$this->goodsStock = (int)I('goodsStock'); 后台不修改库存

        $rs = $this->where('goodsId in(' . $id . ")")->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }


    /**
     * 批量取消商品秒杀状态
     */
    public function CGoodsSecKillStatus()
    {
        $rd = array('status' => -1);
        $id = I('id', 0);
        $id = self::formatIn(",", $id);
        $this->isAdminShopSecKill = 0;
        $rs = $this->where('goodsId in(' . $id . ")")->save();
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }


    /**
     * 批量取消商品预售状态
     */
    public function CGoodsPreSaleStatus()
    {
        $rd = array('status' => -1);
        $id = I('id', 0);
        $id = self::formatIn(",", $id);
        $this->isAdminShopPreSale = 0;
        $rs = $this->where('goodsId in(' . $id . ")")->save();
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }


    /**
     * 跳去限时秒杀编辑弹框
     */
    public function toEdittlement($id)
    {
        $rs = $this->where("goodsId = '{$id}'")->find();
        if (empty($rs)) {
            $rd['status'] = -1;
            return $rd;
        }
        return $rs;
    }

    /**
     * 跳去预售编辑弹框
     */
    public function toEditystlement($id)
    {
        $rs = $this->where("goodsId = '{$id}'")->find();
        if (empty($rs)) {
            $rd['status'] = -1;
            return $rd;
        }
        return $rs;
    }


    /**
     *商品列表 分页
     */
    public function getList($page)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $pageDataNum = 25;
        $mod = M('goods');
        $goodsName = I('goodsName');
        $where = " isSale=1 ";
        if (!empty($goodsName)) {
            $where .= " AND goodsName LIKE '%" . $goodsName . "%'";
        }
        $count = $mod
            ->where($where)
            ->count();
        $dataok = $mod
            ->where($where)
            ->order("goodsId DESC")
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();
        $pageCount = (int)ceil($count / $pageDataNum);
        $apiRet['pageCount'] = $pageCount == 0 ? 1 : $pageCount;
        $apiRet['dataNum'] = !empty($count) ? $count : 0;
        if ($dataok) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $dataok;
        }
        return $apiRet;
    }

    /**
     *商品列表 不带分页
     */
    public function ajaxGoodsList()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $mod = M('goods');
        $goodsName = I('goodsName');
        $where = " isSale=1 ";
        if (!empty($goodsName)) {
            $where .= " AND goodsName LIKE '%" . $goodsName . "%'";
        }
        $list = $mod
            ->where($where)
            ->field('goodsId,goodsName,goodsImg,shopPrice')
            ->order("goodsId DESC")
            ->select();
        if ($list) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
        }
        return $apiRet;
    }
}

?>