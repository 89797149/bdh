<?php

namespace Merchantapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Chain\ChainServiceModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Orders\OrdersServiceModule;
use App\Modules\Shops\ShopsServiceModule;
use function Couchbase\fastlzDecompress;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 分拣APP
 */
class SortingApiModel extends BaseModel
{
    /**
     * 登陆
     */
    public function sortingLogin($request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '登陆失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sortingpersonnel');
        $where['account'] = $request['account'];
        $where['password'] = $request['password'];
        $where['isdel'] = 1;
        $info = $tab->where($where)->find();
        if (!$info) {
            $apiRet['apiInfo'] = '账号或密码不正确';
            return $apiRet;
        }
        //生成用唯一token
        $memberToken = md5(uniqid('', true) . $request['account'] . $request['password'] . (string)microtime());
        if (userTokenAdd($memberToken, $info)) {
            $info['memberToken'] = $memberToken;
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '登陆成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $info;
        }
        return $apiRet;
    }

    /*
     *获取分拣员信息
     * @param int sortId PS:分拣员id
     * */
    public function getSortingInfo($sortId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取分拣员信息失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sortingpersonnel');
        $where['id'] = $sortId;
        $where['isdel'] = 1;
        $info = $tab->where($where)->find();
        if ($info) {
            $info['location'] = [];
            //获取所有相关货位数据
            $relationTab = M('sorting_location_relation');
            $relationWhere['personId'] = $info['id'];
            $relationWhere['isDelete'] = 0;
            $relationList = $relationTab->where($relationWhere)->order('id asc')->select();
            if ($relationList) {
                foreach ($relationList as $key => $value) {
                    $relationId[] = $value['locationId'];
                }
                sort($relationId);
                //获取一级
                $locationTab = M('location');
                $firstWhere['lFlag'] = 1;
                $firstWhere['parentId'] = 0;
                $firstWhere['lid'] = array('IN', $relationId);
                $firstLocation = $locationTab->where($firstWhere)->select();
                foreach ($relationList as $key => $value) {
                    foreach ($firstLocation as $k => $val) {
                        //$firstLocation[$k]['secondLocation'] = [];
                        if ($value['locationId'] == $val['lid']) {
                            unset($relationList[$key]);
                        }
                    }
                }
                $relationList = array_values($relationList);
                foreach ($relationList as $key => $val) {
                    $locationInfo = $locationTab->where(['lid' => $val['locationId'], 'lFlag' => 1])->find();
                    $relationList[$key]['parentId'] = $locationInfo['parentId'];
                    $relationList[$key]['name'] = $locationInfo['name'];
                    $relationList[$key]['sort'] = $locationInfo['sort'];
                }
                foreach ($firstLocation as $key => $value) {
                    foreach ($relationList as $val) {
                        if ($value['lid'] == $val['parentId']) {
                            $firstLocation[$key]['secondLocation'][] = $val;
                        }
                    }
                }
                $info['location'] = $firstLocation;
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取分拣员信息成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $info;
        }
        return $apiRet;
    }

    /*
     *更新分拣员信息
     * @param int sortId PS:分拣员id
     * @param array $request PS:参数
     * */
    public function editSortingInfo($sortId, $request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sortingpersonnel');
        //where
        $where['id'] = $sortId;
        //edit
        $res = $tab->where($where)->save($request);
        if ($res !== false) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     * 获取分拣员任务
     * */
    public function getSortingList($sortId, $status, $page, $pageDataNum)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        //where
        if ($status !== '' && $status != 10) {
            $where['s.status'] = $status;
        }
        $field = [
            's.*',
            'o.orderNo',
            'o.basketId',
            'o.shopId',
            'o.orderStatus',
            'o.totalMoney',
            'o.payType',
            'o.isSelf',
            'o.isPay',
            'o.deliverType',
            'o.createTime',
            'o.needPay',
            'o.defPreSale',
            'o.PreSalePay',
            'o.PreSalePayPercen',
            'o.deliverMoney',
            'o.isAppraises',
            'u.userName as buyer_userName',
            'u.userPhone as buyer_userPhone',
            'u.userPhoto as buyer_userPhoto',
        ];
        $where['s.personId'] = $sortId;
        $where['s.sortingFlag'] = 1;
        $where['o.orderFlag'] = 1;
        $where['o.'] = 1;
        $list = M('sorting s')
            ->join("left join wst_orders o on o.orderId=s.orderId")
            ->join("left join wst_users u on o.userId=u.userId")
            ->where($where)
            ->field($field)
            ->order('s.addtime desc')
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();
        $sortingGoodsTab = M('sorting_goods_relation');
        $shopTab = M('shops');
        foreach ($list as $key => $val) {
            //shop
            $shopInfo = $shopTab->where(['shopId' => $val['shopId']])->find();
            $list[$key]['shopName'] = $shopInfo['shopName'];
            $list[$key]['shopImg'] = $shopInfo['shopImg'];
            //goods
            $swhere['dataFlag'] = 1;
            $swhere['sortingId'] = $val['id'];
            $list[$key]['goods'] = $sortingGoodsTab->where($swhere)->select();
            //框位
            $list[$key]['basketInfo'] = $this->getBasketInfo($val['basketId']); //获取框位信息
        }
        $goodsTab = M('goods');
        foreach ($list as $key => $val) {
            $list[$key]['totalSoringGoods'] = 0;//该分拣任务下的所有商品数量的总和
            $list[$key]['totalSortingGoodsNum'] = 0;//该分拣任务下已完成分拣的商品数量总和
            foreach ($val['goods'] as $k => $v) {
                $list[$key]['totalSoringGoods'] += $v['goodsNum'];
                $list[$key]['totalSortingGoodsNum'] += $v['sortingGoodsNum'];
                $goodsInfo = $goodsTab->where(['goodsId' => $v['goodsId']])->field("recommDesc,goodsUnit,goodsSpec,goodsDesc,saleCount,goodsImg,goodsThums")->find();
                $list[$key]['goods'][$k]['recommDesc'] = $goodsInfo['recommDesc'];
                $list[$key]['goods'][$k]['goodsUnit'] = $goodsInfo['goodsUnit'];
                $list[$key]['goods'][$k]['goodsSpec'] = $goodsInfo['goodsSpec'];
                $list[$key]['goods'][$k]['goodsDesc'] = $goodsInfo['goodsDesc'];
                $list[$key]['goods'][$k]['saleCount'] = $goodsInfo['saleCount'];
                $list[$key]['goods'][$k]['goodsImg'] = $goodsInfo['goodsImg'];
                $list[$key]['goods'][$k]['goodsThums'] = $goodsInfo['goodsThums'];
                unset($orderGoodsWhere);
                $orderGoodsWhere['orderId'] = $val['orderId'];
                $orderGoodsWhere['goodsId'] = $v['goodsId'];
                if (isset($v['skuId']) && !empty($v['skuId'])) {
                    $orderGoodsWhere['skuId'] = $v['skuId'];
                }
                $orderGoodsInfo = M('order_goods')->where($orderGoodsWhere)->find();
                $list[$key]['goods'][$k]['goodsPrice'] = $orderGoodsInfo['goodsPrice'];
                $list[$key]['goods'][$k]['skuSpecAttr'] = $orderGoodsInfo['skuSpecAttr'];
                //$list[$key]['goods'][$k]['location'] = []; //商品对应的货位信息
                //location 商品对应的货位信息,目前只做两级
                $list[$key]['goods'][$k]['location'] = getGoodsLocation($v['goodsId']); //商品对应的货位信息
            }
        }
        if ($list !== false) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
        }
        return $apiRet;
    }

    /**
     * @param $params
     * @return mixed
     * 获取分拣任务下所有的商品数据
     */
    public function getSortingGoodsList($params)
    {
        $sortId = $params['sortId'];
        $status = $params['status'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $isConformity = $params['isConformity'];//是否聚合,默认0：不聚合|1：聚合


        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        //where
        $where = " where sgr.dataFlag=1 and s.sortingFlag=1 and s.personId='" . $sortId . "' ";
        if ($status !== '' && $status != 10) {
            $where .= " and sgr.status='" . $status . "' ";//分拣商品状态
        }
        //添加框位|商品分类查询
        if (!empty($params['basketId'])) {
            $where .= " and s.basketId='" . $params['basketId'] . "' ";//框位
        }
        if (!empty($params['shopCatId1'])) {
            $where .= " and g.shopCatId1='" . $params['shopCatId1'] . "' ";//店铺商品分类第一级
        }
        if (!empty($params['shopCatId2'])) {
            $where .= " and g.shopCatId2='" . $params['shopCatId2'] . "' ";//店铺商品分类第二级
        }
        if (!empty($params['orderId'])) {
            $where .= " and s.orderId='" . $params['orderId'] . "' ";//订单ID
        }
        //field
        $field = " sgr.*,s.basketId,s.settlementNo,s.orderId,s.type,s.addtime,s.shopid,s.status as sortingStatus,s.settlement,o.orderNo ";
        $field .= ",(select lparentId from __PREFIX__location_goods where goodsId=sgr.goodsId limit 1 ) as goodsLocation ";
        $sql = " select " . $field . " from __PREFIX__sorting_goods_relation sgr 
        left join __PREFIX__sorting s on s.id=sgr.sortingId 
        left join __PREFIX__goods g on sgr.goodsId=g.goodsId 
        left join __PREFIX__orders o on s.orderId=o.orderId";
        $sql .= $where;
        $sql .= " order by s.addtime desc ";

        $list['root'] = $this->query($sql);

        $list['root'] = getCartGoodsSku($list['root']);
        if ($list['root'] !== false) {
            $fieldInfo = "wg.goodsId,wg.goodsSn,wg.goodsName,wg.goodsImg,wg.goodsThums,wg.shopId,wg.shopPrice,wg.goodsStock,wg.goodsSpec,wg.SuppPriceDiff,wg.weightG,wog.remarks,wg.shopCatId1,wg.shopCatId2";
            $list['root'] = array_filter($list['root']);//去空
            $goodsNum = 0;//商品数量合计
            foreach ($list['root'] as $key => &$val) {
                $goodsNum += $val['goodsNum'];
                $goodsInfo = M('goods wg')
                    ->join("left join wst_order_goods wog on wog.goodsId = wg.goodsId")
                    ->where(['wg.goodsId' => $val['goodsId'], 'wog.goodsId' => $val['goodsId'], 'wog.skuId' => $val['skuId'], 'wog.orderId' => $val['orderId']])
                    ->field($fieldInfo)
                    ->find();
                $goodsInfo['location'] = getGoodsLocation($goodsInfo['goodsId']); //商品对应的货位信息
                $val['goodsInfo'] = $goodsInfo;//商品详情
                $val['basketInfo'] = $this->getBasketInfo($val['basketId']); //获取框位信息
            }
            unset($val);
            $list['root'] = array_values($list['root']);
            //***************************聚合操作******start*****************************************
//            if ($isConformity == 1 && $status == 0) {
//                $list['root'] = $this->conformityGoods($list['root']);
//            }
//            if ($isConformity == 1 && $status == 1) {
//                $list['root'] = $this->conformitySortingGoods($list['root']);
//            }
            if ($isConformity == 1) {
                $list['root'] = $this->conformityGoods($list['root']);
            }
            //***************************聚合操作******end********************************************
            $count = count($list['root']);
            $pageData = array_slice($list['root'], ($page - 1) * $pageSize, $pageSize);
            $list['root'] = $pageData;
            $list['totalPage'] = ceil($count / $pageSize);//总页数
            $list['currentPage'] = $page;//当前页码
            $list['total'] = (float)$count;//总数量
            $list['totalGoodsNum'] = (float)$goodsNum;//总商品数量
            $list['pageSize'] = $pageSize;//页码条数
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
        }
        return $apiRet;
    }

    /**
     * @param $params
     * @return mixed
     * 一键开始分拣
     */
    public function updateGoodsStatus($params)
    {
        $sortModel = M('sorting s');
        $sortGoodsModel = M('sorting_goods_relation');
        $where = [];
        $where['s.personId'] = $params['sortId'];
        $where['sgr.status'] = 0;
        $where['s.sortingFlag'] = 1;
        //添加框位|商品分类查询
        if (!empty($params['basketId'])) {
            $where['s.basketId'] = $params['basketId'];//框位
        }
        if (!empty($params['shopCatId1'])) {
            $where['g.shopCatId1'] = $params['shopCatId1'];//店铺商品分类第一级
        }
        if (!empty($params['shopCatId2'])) {
            $where['g.shopCatId2'] = $params['shopCatId2'];//店铺商品分类第二级
        }
        if (!empty($params['orderId'])) {
            $where .= " and s.orderId='" . $params['orderId'] . "' ";//订单ID
        }
        $sortList = $sortModel
            ->join("left join wst_sorting_goods_relation sgr on sgr.sortingId = s.id")
            ->join("left join wst_goods g on sgr.goodsId = g.goodsId")
            ->where($where)
            ->field('sgr.*')
            ->select();
        if (empty($sortList)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请查看是否存在待分拣商品';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $sortId = array_unique(array_get_column($sortList, "sortingId"));
        //记录分拣任务操作日志 start
        foreach ($sortId as $v) {
            //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
            $param = [];
            $param['sortingId'] = $v;
            $param['status'] = 0;
            checkSortGoodsStatus($param);
        }
        //记录分拣任务操作日志 end
        $sortGoodsId = array_unique(array_get_column($sortList, "id"));
        $addtime = date('Y-m-d H:i:s');
        $where = [];
        $where['id'] = ['IN', $sortGoodsId];
        $where['status'] = 0;
        $where['dataFlag'] = 1;
        //变更分拣任务商品状态
        $sortGoodsModel->where($where)->save(['status' => 1, 'startDate' => $addtime]);


        //记录分拣商品任务操作日志 start
        foreach ($sortGoodsId as $v) {
            $sortingInfo = M('sorting_goods_relation sgr')
                ->join("left join wst_sorting ws on ws.id = sgr.sortingId")
                ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
                ->join("left join wst_order_goods wog on wog.orderId = ws.orderId and wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
                ->where(['sgr.id' => $v])
                ->field("sgr.sortingId,wog.goodsName,wsu.userName,ws.orderId,wog.id as orderGoodsId")
                ->find();
            //记录分拣商品任务操作日志 start
            $param = [];
            $param['sortingId'] = $sortingInfo['sortingId'];
            $param['content'] = "[{$sortingInfo['userName']}]:开始分拣商品[ " . $sortingInfo['goodsName'] . " ]";
            insertSortingActLog($param);
            //更改订单商品表中的分拣状态为分拣中
            $orderGoodsTab = M('order_goods');
            $save = [];
            $save['actionStatus'] = 1;
            $orderGoodsTab->where(['id' => $sortingInfo['orderGoodsId']])->save($save);
        }
        //记录分拣商品任务操作日志 end

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        return $apiRet;
    }

    /**
     * @param $params
     * @return mixed
     * 待分拣--扫码开始分拣
     */
    public function editSortOrderStatus($params)
    {
        $sortModel = M('sorting s');
        $sortGoodsModel = M('sorting_goods_relation');
        $where = [];
        $where['s.personId'] = $params['sortId'];
        $where['sgr.status'] = 0;
        $where['s.sortingFlag'] = 1;
        $where['wo.orderNo'] = $params['orderNo'];
        $sortList = $sortModel
            ->join("left join wst_sorting_goods_relation sgr on sgr.sortingId = s.id")
            ->join("left join wst_orders wo on wo.orderId = s.orderId")
            ->where($where)
            ->field('sgr.*')
            ->select();

        if (empty($sortList)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请查看当订单是否已开始分拣';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $sortId = array_unique(array_get_column($sortList, "sortingId"));
        //记录分拣任务操作日志 start
        foreach ($sortId as $v) {
            //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
            $param = [];
            $param['sortingId'] = $v;
            $param['status'] = 0;
            checkSortGoodsStatus($param);
        }
        //记录分拣任务操作日志 end
        $sortGoodsId = array_unique(array_get_column($sortList, "id"));
        $addtime = date('Y-m-d H:i:s');
        $where = [];
        $where['id'] = ['IN', $sortGoodsId];
        $where['status'] = 0;
        $where['dataFlag'] = 1;
        //变更分拣任务商品状态
        $sortGoodsModel->where($where)->save(['status' => 1, 'startDate' => $addtime]);


        //记录分拣商品任务操作日志 start
        foreach ($sortGoodsId as $v) {
            $sortingInfo = M('sorting_goods_relation sgr')
                ->join("left join wst_sorting ws on ws.id = sgr.sortingId")
                ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
                ->join("left join wst_order_goods wog on wog.orderId = ws.orderId and wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
                ->where(['sgr.id' => $v])
                ->field("sgr.sortingId,wog.goodsName,wsu.userName,ws.orderId,wog.id as orderGoodsId")
                ->find();
            //记录分拣商品任务操作日志 start
            $param = [];
            $param['sortingId'] = $sortingInfo['sortingId'];
            $param['content'] = "[{$sortingInfo['userName']}]:开始分拣商品[ " . $sortingInfo['goodsName'] . " ]";
            insertSortingActLog($param);
            //更改订单商品表中的分拣状态为分拣中
            $orderGoodsTab = M('order_goods');
            $save = [];
            $save['actionStatus'] = 1;
            $orderGoodsTab->where(['id' => $sortingInfo['orderGoodsId']])->save($save);
        }
        //记录分拣商品任务操作日志 end

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        return $apiRet;
    }

    /**
     * @param $params
     * @return mixed
     * 获取框位|店铺分类列表
     */
    public function getShopCatList($params)
    {
        $shopId = $params['shopId'];
        $sortId = $params['sortId'];
        $status = $params['status'];
        $basketModel = M('basket');
        $shopsCatsModel = M('shops_cats');

        $where = " where sgr.dataFlag = 1 and s.sortingFlag = 1 and s.personId = {$sortId} and sgr.status = {$status}";

        //field
        $field = " s.basketId,s.status as sortingStatus,s.orderId ";
        $field .= ",sgr.status as sortingGoodsStatus ";
        $field .= ",g.shopCatId1,g.shopCatId2";
        $sql = " select " . $field . " from __PREFIX__sorting_goods_relation sgr 
        left join __PREFIX__sorting s on s.id = sgr.sortingId 
        left join __PREFIX__goods g on sgr.goodsId = g.goodsId ";
        $sql .= $where;
        $sortList = $this->query($sql);

        $orderId = array_unique(array_get_column($sortList, "orderId"));
        $basketId = array_unique(array_get_column($sortList, "basketId"));
        $shopCatId1 = array_unique(array_get_column($sortList, "shopCatId1"));
        $shopCatId2 = array_unique(array_get_column($sortList, "shopCatId2"));
        $shopCatId = array_merge($shopCatId1, $shopCatId2);
        //订单ID
        $orderIds = [];
        $orderIdList = [];
        foreach ($orderId as $k => $v) {
            $orderIds['orderId'] = $v;
            $orderIdList[] = $orderIds;
        }

        //获取框位列表
        $basket = $basketModel->where(['shopId' => $shopId, 'bFlag' => 1])->field('bid as basketId , name')->select();
        $basketList = [];
        foreach ($basket as $v) {
            if (in_array($v['basketId'], $basketId)) {
                $basketList[] = $v;
            }
        }

        //获取店铺分类列表
        $shopCat = $shopsCatsModel->where(['shopId' => $shopId, 'catFlag' => 1])->field('catId,catName,parentId')->select();
        $shopCatsList = [];
        foreach ($shopCat as $v) {
            if (in_array($v['catId'], $shopCatId)) {
                $shopCatsList[] = $v;
            }
        }
        $shopCatList = $this->getCatAndChild($shopCatsList);

        $list = [];
        $list['basketList'] = (array)$basketList;
        $list['shopCatList'] = (array)$shopCatList;
        $list['orderIdList'] = (array)$orderIdList;

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;
        return $apiRet;
    }

    /**
     * @param $shopCatsList
     * @return array
     * 获取店铺分类
     */
    public function getCatAndChild($shopCatsList)
    {
        $tree = [];
        $newData = [];
        //循环重新排列
        foreach ($shopCatsList as $datum) {
            $newData[$datum['catId']] = $datum;
        }

        foreach ($newData as $key => $datum) {
            if ($datum['parentId'] > 0) {
                //不是根节点的将自己的地址放到父级的child节点
                $newData[$datum['parentId']]['children'][] = &$newData[$key];
            } else {
                //根节点直接把地址放到新数组中
                $tree[] = &$newData[$datum['catId']];
            }
        }
        return $tree;
    }

    /**
     * @param $list
     * @return array
     * 待分拣聚合数据
     */
    public function conformityGoods($list)
    {
        if (empty($list)) {
            return [];
        }
        $rest = [];
        foreach ($list as $v) {
            $res = [];
            $res['goodsId'] = $v['goodsId'];
            $res['skuId'] = $v['skuId'];
            $rest[] = $res;
        }
        $res = [];
        foreach ($rest as $key => $value) {
            //重新排序value
            ksort($value);
            //获取key ，判断是否存在的依据
            $key = implode("_", $value);   //name1_10
            //md5 为了防止字段内容过长特殊字符等
            $res[md5($key)] = $value;
        }
        //重置索引
        $res = array_values($res);
        $goodsList = [];
        foreach ($res as $v) {
            $sortingGoodsId = [];
            $basketList = [];//框位信息
            $goods = [];
            $goodsNum = 0;//需要分拣的数量
            $orderWeight = 0;//需要分拣的重量
            $weightG = 0;//已分拣的重量
            foreach ($list as $value) {
                if ($v['goodsId'] == $value['goodsId'] && $v['skuId'] == $value['skuId']) {
                    $orderWeight += $value['orderWeight'];
                    $weightG += $value['weightG'];
                    $goodsNum += $value['goodsNum'];
                    $sortingGoodsId[] = $value['id'];
                    $goodsId = $value['goodsId'];
                    $sortingGoodsNum = $value['sortingGoodsNum'];
                    $skuId = $value['skuId'];
                    $goodsSku = $value['goodsSku'];
                    $goodsInfo = $value['goodsInfo'];
                    $basketList[] = $value['basketInfo'];
                }
            }
            $goods['goodsNum'] = $goodsNum;
            $goods['orderWeight'] = $orderWeight;
            $goods['weightG'] = $weightG;
            $goods['sortingGoodsNum'] = $sortingGoodsNum;
            $goods['goodsId'] = $goodsId;
            $goods['skuId'] = $skuId;
            $goods['goodsSku'] = $goodsSku;
            $goods['goodsInfo'] = $goodsInfo;
            $goods['basketInfo'] = (array)array_unique($basketList);
            $goods['sortingGoodsId'] = implode(',', $sortingGoodsId);
            $goodsList[] = $goods;
        }
        return (array)$goodsList;
    }

    /**
     * @param $list
     * @return array
     * 分拣中聚合数据
     */
    public function conformitySortingGoods($list)
    {
        if (empty($list)) {
            return [];
        }
        $rest = [];
        foreach ($list as $v) {
            $rest[] = $v['basketId'];
        }
        $rest = array_values(array_unique($rest));
        $basketList = [];
        foreach ($rest as $v) {
            foreach ($list as $value) {
                if ($v == $value['basketId']) {
                    $basket = $value['basketInfo'];
                }
            }
            $basketList[] = $basket;
        }
        return (array)$basketList;
    }

    /**
     * @param $params
     * @return array
     * 分拣中---获取商品详情
     */
    public function getSortGoodsInfo($params)
    {
        $sortingGoodsId = $params['sortingGoodsId'];
        $where = [];
        $where['sgr.id'] = $sortingGoodsId;
        $where['sgr.dataFlag'] = 1;

        $sortGoodsModel = M('sorting_goods_relation sgr');
        $field = "wo.orderNo,sgr.*,wog.skuSpecAttr,ws.basketId,wo.userName,wo.userPhone, ";
        $field .= "wg.goodsName,wg.goodsSn,wg.goodsStock,wg.goodsImg,wg.goodsThums, ";
        $field .= "wog.id as orderGoodsId ";

        $sortGoodsList = $sortGoodsModel
            ->join("left join wst_sorting ws on ws.id = sgr.sortingId")
            ->join("left join wst_goods wg on wg.goodsId = sgr.goodsId")
            ->join("left join wst_orders wo on wo.orderId = ws.orderId")
            ->join("left join wst_order_goods wog on wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
            ->where($where)
            ->field($field)
            ->find();
        if (!empty($sortGoodsList)) {
            $sortGoodsList = getCartGoodsSku($sortGoodsList);//获取当前分拣sku商品的信息
            $sortGoodsList['sortingGoodsNumNot'] = (float)$sortGoodsList['goodsNum'] - (float)$sortGoodsList['sortingGoodsNum'];//未分拣数量【非称重商品(标品)】
            $weightGNot = (float)$sortGoodsList['orderWeight'] - (float)$sortGoodsList['weightG'];//未分拣重量【称重商品(非标品)】
            if ($weightGNot <= 0) {
                $weightGNot = 0;
            }
            $sortGoodsList['weightGNot'] = $weightGNot;
            $sortGoodsList['basketGoodsNumNot'] = (float)$sortGoodsList['sortingGoodsNum'] - (float)$sortGoodsList['basketGoodsNum'];//未入框数量【非称重商品(标品)】
            $sortGoodsList['location'] = getGoodsLocation($sortGoodsList['goodsId']); //商品对应的货位信息
            $sortGoodsList['basketInfo'] = $this->getBasketInfo($sortGoodsList['basketId']); //获取框位信息
            $sortGoodsList['sortGoodsBarcode'] = $this->getSortGoodsBarcode($sortingGoodsId);//获取入框信息【称重商品(非标品)】
            $isUnusual = 0;//是否异常(用于区分当前商品是否全部都补差价)【0:正常|1:异常】
            if ($sortGoodsList['status'] == 3) {
                if ($sortGoodsList['SuppPriceDiff'] == 1) {//称重商品
                    $goodsInfo = M('sorting_goods_barcode sgb')
                        ->join("left join wst_goods wg on wg.goodsId = {$sortGoodsList['goodsId']}")
                        ->join("left join wst_order_goods og on og.id = {$sortGoodsList['orderGoodsId']}")
                        ->where(['sgb.sortingGoodsId' => $sortGoodsList['id']])
                        ->field('sgb.*,wg.goodsName,wg.goodsImg,wg.goodsThums,og.goodsPrice,og.skuSpecAttr')
                        ->select();
                    if (empty($goodsInfo)) {
                        $isUnusual = 1;
                    }
                } else {
                    if ($sortGoodsList['basketGoodsNum'] == 0) {
                        $isUnusual = 1;
                    }
                }
            }
            $sortGoodsList['isUnusual'] = $isUnusual;//是否异常(用于区分当前商品是否全部都补差价)【0:正常|1:异常】
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = (array)$sortGoodsList;
        return (array)$apiRet;
    }

    /**
     * @param $sortingGoodsId
     * @return array
     * 获取入框信息
     */
    public function getSortGoodsBarcode($sortingGoodsId)
    {
        $sortGoodsBarcode = M('sorting_goods_barcode');
        $basketNum = $sortGoodsBarcode->where(['sortingGoodsId' => $sortingGoodsId])->field('sum(isPackType = -2) as basketNot,sum(isPackType >= -1) as basketSent,count(isPackType) as basketNum')->select();
        $basketBarcodeList = $sortGoodsBarcode->where(['sortingGoodsId' => $sortingGoodsId])->field('barcode')->select();
        $param = [];
        $param['basketNum'] = (float)$basketNum[0]['basketNum'];//需入框数量
        $param['basketNot'] = (float)$basketNum[0]['basketNot'];//未入框数量
        $param['basketSent'] = (float)$basketNum[0]['basketSent'];//已入框数量
        $param['basketBarcodeList'] = (array)$basketBarcodeList;//入框条码
        return $param;
    }

    /**
     * @param $params
     * @return array
     * 分拣中---获取聚合商品详情
     */
    public function getConformitySortGoodsInfo($params)
    {
        $sortingGoodsId = $params['sortingGoodsId'];
        $where = [];
        $where['sgr.id'] = ['IN', $sortingGoodsId];
        $where['sgr.dataFlag'] = 1;

        $sortGoodsModel = M('sorting_goods_relation sgr');
        $field = "wo.orderNo,sgr.*,wog.skuSpecAttr,ws.basketId,wo.userName,wo.userPhone, ";
        $field .= "wg.goodsName,wg.goodsSn,wg.goodsStock,wg.goodsImg,wg.goodsThums, ";
        $field .= "wog.id as orderGoodsId ";

        $sortGoodsList = $sortGoodsModel
            ->join("left join wst_sorting ws on ws.id = sgr.sortingId")
            ->join("left join wst_goods wg on wg.goodsId = sgr.goodsId")
            ->join("left join wst_orders wo on wo.orderId = ws.orderId")
            ->join("left join wst_order_goods wog on wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
            ->where($where)
            ->field($field)
            ->group('sgr.id')
            ->select();
        if (!empty($sortGoodsList)) {
            $sortGoodsList = getCartGoodsSku($sortGoodsList);
            foreach ($sortGoodsList as $k => $v) {
                $sortGoodsList[$k]['sortingGoodsNumNot'] = (float)$v['goodsNum'] - (float)$v['sortingGoodsNum'];//未分拣数量【非称重商品(标品)】
                $weightGNot = (float)$v['orderWeight'] - (float)$v['weightG'];//未分拣重量【称重商品(非标品)】
                if ($weightGNot <= 0) {
                    $weightGNot = 0;
                }
                $sortGoodsList[$k]['weightGNot'] = $weightGNot;//未分拣重量【称重商品(非标品)】
                $sortGoodsList[$k]['basketGoodsNumNot'] = (float)$v['sortingGoodsNum'] - (float)$v['basketGoodsNum'];//未入框数量【非称重商品(标品)】
                $sortGoodsList[$k]['basketGoodsNumNot'] = (float)$v['sortingGoodsNum'] - (float)$v['basketGoodsNum'];//未入框数量【非称重商品(标品)】
                $sortGoodsList[$k]['location'] = getGoodsLocation($v['goodsId']); //商品对应的货位信息
                $sortGoodsList[$k]['basketInfo'] = $this->getBasketInfo($v['basketId']); //获取框位信息
                $sortGoodsList[$k]['sortGoodsBarcode'] = $this->getSortGoodsBarcode($v['id']);//获取入框信息【称重商品(非标品)】
                $isUnusual = 0;//是否异常(用于区分当前商品是否全部都补差价)【0:正常|1:异常】
                if ($v['status'] == 3) {
                    if ($v['SuppPriceDiff'] == 1) {//称重商品
                        $goodsInfo = M('sorting_goods_barcode sgb')
                            ->join("left join wst_goods wg on wg.goodsId = {$v['goodsId']}")
                            ->join("left join wst_order_goods og on og.id = {$v['orderGoodsId']}")
                            ->where(['sgb.sortingGoodsId' => $v['id']])
                            ->field('sgb.*,wg.goodsName,wg.goodsImg,wg.goodsThums,og.goodsPrice,og.skuSpecAttr')
                            ->select();
                        if (empty($goodsInfo)) {
                            $isUnusual = 1;
                        }
                    } else {
                        if ($v['basketGoodsNum'] == 0) {
                            $isUnusual = 1;
                        }
                    }
                }
                $sortGoodsList[$k]['isUnusual'] = $isUnusual;//是否异常(用于区分当前商品是否全部都补差价)【0:正常|1:异常】
            }
        }

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = (array)$sortGoodsList;
        return (array)$apiRet;
    }

    /**
     * @param $sortId
     * @param $basketId
     * @return array
     * 分拣中---框位详情
     */
    public function getBasketSortInfo($sortId, $basketId)
    {
        $where = [];
        $where['personId'] = $sortId;
        $where['basketId'] = $basketId;
        $where['status'] = 1;
        $where['sortingFlag'] = 1;

        $sortModel = M('sorting');
        $sortGoodsModel = M('sorting_goods_relation sgr');
        $res = $sortModel->where($where)->select();
        $field = "wg.goodsName,wg.goodsImg,wg.SuppPriceDiff,sgr.*,wog.skuSpecAttr";
        $goods = [];
        foreach ($res as $k => $v) {
            $whereInfo = [];
            $whereInfo['sgr.sortingId'] = $v['id'];
            $whereInfo['sgr.status'] = 1;
            $whereInfo['sgr.dataFlag'] = 1;
            //商品
            $whereInfo['wg.shopId'] = $v['shopid'];
            $whereInfo['wg.goodsFlag'] = 1;
            //订单商品
            $whereInfo['wog.orderId'] = $v['orderId'];

            $sortGoodsList = $sortGoodsModel
                ->join("left join wst_goods wg on wg.goodsId = sgr.goodsId")
                ->join("left join wst_order_goods wog on wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
                ->where($whereInfo)
                ->field($field)
                ->select();
            foreach ($sortGoodsList as $value) {
                $goods[] = $value;
            }
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $goods;
        return (array)$apiRet;
    }

    /**
     * @param $sortingGoodsId
     * @return mixed
     * 更改分拣商品的状态---单商品
     */
    public function editSortGoodsStatus($sortingGoodsId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $sgr = M('sorting_goods_relation');
        //where
        $where = [];
        $where['id'] = $sortingGoodsId;
        $where['status'] = 0;

        //edit
        $edit = [];
        $edit['status'] = 1;
        $edit['startDate'] = date('Y-m-d H-i-s', time());

        $res = $sgr->where($where)->save($edit);

        if (!empty($res)) {
            $sortingInfo = M('sorting_goods_relation sgr')
                ->join("left join wst_sorting ws on ws.id = sgr.sortingId")
                ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
                ->join("left join wst_order_goods wog on wog.orderId = ws.orderId and wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
                ->where(['sgr.id' => $sortingGoodsId])
                ->field("sgr.sortingId,wog.goodsName,wsu.userName,ws.orderId,wog.id as orderGoodsId")
                ->find();
            //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
            $param = [];
            $param['sortingId'] = $sortingInfo['sortingId'];
            $param['status'] = 0;
            checkSortGoodsStatus($param);
            //记录分拣商品任务操作日志 start
            $param = [];
            $param['sortingId'] = $sortingInfo['sortingId'];
            $param['content'] = "[{$sortingInfo['userName']}]:开始分拣商品[ " . $sortingInfo['goodsName'] . " ]";
            insertSortingActLog($param);
            //记录分拣商品任务操作日志 end
            //更改订单商品表中的分拣状态为分拣中
            $orderGoodsTab = M('order_goods');
            $save = [];
            $save['actionStatus'] = 1;
            $orderGoodsTab->where(['id' => $sortingInfo['orderGoodsId']])->save($save);


            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        } else {
            $apiRet['apiInfo'] = '请查看当前任务商品状态';
        }
        return $apiRet;
    }

    /**
     * @param $sortingGoodsId
     * @return mixed
     * 更改分拣商品的状态---聚合商品
     */
    public function editConformityGoodsStatus($sortingGoodsId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $sgr = M('sorting_goods_relation');
        //where
        $where = [];
        $where['id'] = ['IN', $sortingGoodsId];
        $where['status'] = 0;
        $sortGoodsList = $sgr->where($where)->select();
        if (empty($sortGoodsList)) {
            $apiRet['apiInfo'] = '请查看当前聚合商品是否已开始分拣';
            return $apiRet;
        }

        //edit
        $edit = [];
        $edit['status'] = 1;
        $edit['startDate'] = date('Y-m-d H-i-s', time());

        $res = $sgr->where($where)->save($edit);

        if ($res !== false) {
            $sortingGoodsId = explode(',', $sortingGoodsId);
            foreach ($sortingGoodsId as $v) {
                $sortingInfo = M('sorting_goods_relation sgr')
                    ->join("left join wst_sorting ws on ws.id = sgr.sortingId")
                    ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
                    ->join("left join wst_order_goods wog on wog.orderId = ws.orderId and wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
                    ->where(['sgr.id' => $v])
                    ->field("sgr.sortingId,wog.goodsName,wsu.userName,wog.id as orderGoodsId")
                    ->find();
                //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
                $param = [];
                $param['sortingId'] = $sortingInfo['sortingId'];
                $param['status'] = 0;
                checkSortGoodsStatus($param);
                //记录分拣商品任务操作日志 start
                $param = [];
                $param['sortingId'] = $sortingInfo['sortingId'];
                $param['content'] = "[{$sortingInfo['userName']}]:开始分拣商品[ " . $sortingInfo['goodsName'] . " ]";
                insertSortingActLog($param);


                //更改订单商品表中的分拣状态为分拣中
                $orderGoodsTab = M('order_goods');
                $save = [];
                $save['actionStatus'] = 1;
                $orderGoodsTab->where(['id' => $sortingInfo['orderGoodsId']])->save($save);
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     *更改分拣任务的状态
     * */
    public function editSoritingStatus($sortId, $sortindId, $status)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sorting');
        //where
        $where['id'] = $sortindId;
        $where['personId'] = $sortId;
        //edit
        $edit['status'] = $status;
        $edit['startDate'] = date('Y-m-d H-i-s', time());
        if ($status == 2) {
            $edit['endDate'] = date('Y-m-d H-i-s', time());
        }
        $edit['updatetime'] = date('Y-m-d H-i-s', time());
        $res = $tab->where($where)->save($edit);
        if ($res !== false) {
            $statusName = "待分拣";
            if ($edit['status'] == 1) {
                //如果分拣任务更改为分拣中,其任务下面的商品状态也更改为分拣中
                $sortingInfo = $tab->where(['id' => $sortindId])->find();
                $gedit['status'] = 1;
                $gedit['startDate'] = date('Y-m-d H-i-s', time());
                M('sorting_goods_relation')->where(['sortingId' => $sortindId])->save($gedit);
                M('order_goods')->where(['orderId' => $sortingInfo['orderId']])->save(['actionStatus' => 1]);
                $statusName = "分拣中";
            } elseif ($edit['status'] == 2) {
                $statusName = "分拣完成";
            }
            //记录分拣任务操作日志 start
            $settlementNo = M('sorting')->where(["id" => $sortindId])->getField('settlementNo');
            $param = [];
            $param['sortingId'] = $sortindId;
            $param['content'] = "更改分拣任务[ $settlementNo ]状态为" . $statusName;
            insertSortingActLog($param);
            //记录分拣任务操作日志 end
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     *获取分拣任务的详情
     * */
    public function getSortingWorkInfo($sortId, $sortingWorkId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $field = [
            's.*',
            'o.orderNo',
            'o.orderId',
            'o.basketId',
            'o.shopId',
            'o.orderStatus',
            'o.totalMoney',
            'o.payType',
            'o.isSelf',
            'o.isPay',
            'o.deliverType',
            'o.createTime',
            'o.needPay',
            'o.defPreSale',
            'o.PreSalePay',
            'o.PreSalePayPercen',
            'o.deliverMoney',
            'o.isAppraises',
            'u.userName as buyer_userName',
            'u.userPhone as buyer_userPhone',
            'u.userPhoto as buyer_userPhoto',
        ];
        $where['s.personId'] = $sortId;
        $where['s.id'] = $sortingWorkId;
        $where['s.sortingFlag'] = 1;
        $info = M('sorting s')
            ->join("left join wst_orders o on o.orderId=s.orderId")
            ->join("left join wst_users u on o.userId=u.userId")
            ->where($where)
            ->field($field)
            ->find();
        $sortingGoodsTab = M('sorting_goods_relation');
        $shopTab = M('shops');
        //shop
        $shopInfo = $shopTab->where(['shopId' => $info['shopId']])->find();
        $info['shopName'] = $shopInfo['shopName'];
        $info['shopImg'] = $shopInfo['shopImg'];
        //goods
        $swhere['dataFlag'] = 1;
        $swhere['sortingId'] = $info['id'];
        $info['goods'] = $sortingGoodsTab->where($swhere)->select();
        $info['goods'] = getCartGoodsSku($info['goods']); //后加sku信息
        $info['countGoods'] = count($info['goods']);
        $goodsTab = M('goods wg');
        $info['totalSoringGoods'] = 0;//该分拣任务下的所有商品数量(需要分拣的数量)的总和
        $info['totalSortingGoodsNum'] = 0;//该分拣任务下已完成分拣的商品数量(需要分拣的数量)总和
        foreach ($info['goods'] as $k => &$v) {
            $info['totalSoringGoods'] += $v['goodsNum'];
            $info['totalSortingGoodsNum'] += $v['sortingGoodsNum'];
            $goodsInfo = $goodsTab
                ->join("left join wst_order_goods wog on wog.orderId = {$info['orderId']}")
                ->where(['wg.goodsId' => $v['goodsId'], 'wog.goodsId' => $v['goodsId'], 'wog.skuId' => $v['skuId']])
                ->field('wg.*,wog.remarks')
                ->find();
            $v['goodsCatId1Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId1']);
            $v['goodsCatId2Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId2']);
            $v['goodsCatId3Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId3']);
            $v['shopCatId1Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId1']);
            $v['shopCatId2Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId2']);
            $v['goodsName'] = $goodsInfo['goodsName'];
            $v['recommDesc'] = $goodsInfo['recommDesc'];
            $v['goodsUnit'] = $goodsInfo['goodsUnit'];
            $v['goodsSpec'] = $goodsInfo['goodsSpec'];
            $v['goodsDesc'] = $goodsInfo['goodsDesc'];
            $v['saleCount'] = $goodsInfo['saleCount'];
            $v['goodsImg'] = $goodsInfo['goodsImg'];
            $v['goodsThums'] = $goodsInfo['goodsThums'];
            $v['goodsStock'] = $goodsInfo['goodsStock'];
            $v['goodsSpec'] = $goodsInfo['goodsSpec'];
            $v['remarks'] = $goodsInfo['remarks'];
//            $v['SuppPriceDiff'] = $goodsInfo['SuppPriceDiff'];
            $v['goodsWeightG'] = $goodsInfo['weightG']; //商品重量
            $v['goodsWeightGTotal'] = $goodsInfo['weightG'] * $v['goodsNum']; //预计重量
            //$v['weightG'] = $goodsInfo['weightG'];
//            $v['weightG'] = 0;//条码称重
            $barcodeInfo = M('barcode')->where(['goodsId' => $goodsInfo['goodsId']])->find();
//            if ($barcodeInfo) {
//                $v['weightG'] = $barcodeInfo['weight'];
//            }
            $v['weightGTotal'] = $v['weightG'] * $v['goodsNum'];//预计总重(条码)
            $v['barcode'] = '';
            if ($goodsInfo['SuppPriceDiff'] == 1) {
                $v['barcode'] = (string)$barcodeInfo['barcode'];
            } else {
                $v['barcode'] = (string)$goodsInfo['goodsSn'];
            }
            $orderGoodsInfo = M('order_goods')->where(['orderId' => $info['orderId'], 'goodsId' => $v['goodsId']])->find();
            $v['goodsPrice'] = $orderGoodsInfo['goodsPrice'];
            $v['location'] = getGoodsLocation($v['goodsId']); //商品对应的货位信息
        }
        unset($v);
        //框位
        $info['basketInfo'] = $this->getBasketInfo($info['basketId']); //获取框位信息
        if ($info) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $info;
        }
        return $apiRet;
    }

    /*
     *更改分拣商品已完成分拣的数量
     * 【弃用】
     * */
    public function editSortingGoodsNum($sortId, $request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $basketSn = $request['basketSn'];//框位编号
        $getBarcode = $request['barcode'];//条码[分拣时的条码]
        $sortTab = M('sorting');
        $swhere['sortingFlag'] = 1;
        $swhere['id'] = $request['sortingWorkId'];
        $swhere['personId'] = $sortId;
        $sortInfo = $sortTab->where($swhere)->find();
        if (!$sortInfo || $sortInfo['status'] != 1) {
            $apiRet['apiInfo'] = '该分拣任务的状态异常,非分拣中状态';
            return $apiRet;
        }
        $sortingGoodsTab = M('sorting_goods_relation');
        $gwhere['sortingId'] = $request['sortingWorkId'];
        $gwhere['goodsId'] = $request['goodsId'];
        if (isset($request['skuId']) && !empty($request['skuId'])) {
            $gwhere['skuId'] = $request['skuId'];//后加skuId
        }
        $sortingGoodsInfo = $sortingGoodsTab->where($gwhere)->find();
        $goodsInfo = M('goods')->where(['goodsId' => $request['goodsId'], 'goodsFlag' => 1])->find();
        if (!$sortingGoodsInfo || !$goodsInfo) {
            $apiRet['apiInfo'] = '分拣商品有误,请核对';
            return $apiRet;
        }
        if ($sortingGoodsInfo['skuId'] != $request['skuId']) {
            $apiRet['apiInfo'] = "分拣商品有误,请核对";
            return $apiRet;
        }
        //核对框位-----start-------
        $rest = $this->getBasketInfo($sortInfo['basketId']);
        if ($rest['basketSn'] != $basketSn) {
            $apiRet['apiInfo'] = '请确认放入的框是否正确';
            return $apiRet;
        }
        $sortGoodsBarcode = M('sorting_goods_barcode');
        //------------end---------
        if ($sortingGoodsInfo['SuppPriceDiff'] == 1) {
            //分拣数量重置----由于是系统预打包，每次商品重量不确定，最后判断重置
            $request['goodsNum'] = 0;

            $sortingGoodsId = $sortingGoodsInfo['id'];
            $barcodeList = $sortGoodsBarcode->where(['sortingGoodsId' => $sortingGoodsId])->field('sum(weightG) as weightG')->select();
            $newWeight = (float)$barcodeList[0]['weightG'];

            if ($request['isNativeGoods'] == 0) {
                unset($barcodeWhere);
                $barcodeWhere['goodsId'] = $goodsInfo['goodsId'];
                $barcodeWhere['barcode'] = $request['barcode'];
                $barcodeWhere['bFlag'] = 1;
                if (isset($request['skuId']) && !empty($request['skuId'])) {
                    $barcodeWhere['skuId'] = $request['skuId'];//后加skuId
                }
                $barcode = M('barcode')->where($barcodeWhere)->find();
                if (!$barcode) {
                    $apiRet['apiInfo'] = '条码信息有误,请核对';
                    return $apiRet;
                }
            } elseif ($request['isNativeGoods'] == 1) {
                if (preg_match("/^\d*$/", $request['barcode']) && strlen($request['barcode']) == 18) {
                    $str = $request['barcode'];
                    $codeF = (int)substr($str, 2);
                    $codeW = (int)substr($str, 2, 5);//商品库编码
                    $codeE = (int)substr($str, 7, 5);//金额单位 为 分
                    $codeN = (int)substr($str, 12, 5);//重量为G 应该 待测试------
                    $codeC = (int)substr($str, 17);

                    if (!empty($codeW)) {
                        $where = [];
                        $where['goodsFlag'] = 1;
                        $where['goodsSn'] = $codeW;
                        $where['shopId'] = $sortInfo['shopid'];
                        $barcodeInfo = M('goods')->where($where)->find();
                        if (empty($barcodeInfo)) {
                            //後加，兼容商品sku
                            $skuBarcodeInfo = M('sku_goods_system sy')
                                ->join("left join wst_goods g on g.goodsId=sy.goodsId")
                                ->where(["sy.skuBarcode" => $codeW, "g.shopId" => $request['shopId'], "g.goodsFlag" => 1])
                                ->field('sy.goodsId,sy.skuId')
                                ->find();
                            if (!empty($skuBarcodeInfo)) {
                                $barcodeInfo = $skuBarcodeInfo;
                            } else {
                                $apiRet['apiInfo'] = '条码信息有误';
                                return $apiRet;
                            }
                        }
                        if (!empty($request['skuId'])) {
                            if ($barcodeInfo['skuId'] != $request['skuId']) {
                                $apiRet['apiInfo'] = '条码信息有误,请核对';
                                return $apiRet;
                            }
                        }
                        $nativeGoodsInfo = [];
                        // 单位为G
                        setObj($nativeGoodsInfo, "weight", $codeN, null);
                        //重置分拣数量 用于本地分拣商品的全部数量
                        $request['goodsNum'] = 0;

                        //用于变更称重重量
                        $barcode['weight'] = $nativeGoodsInfo['weight'];
                    } else {
                        $apiRet['apiInfo'] = '条码信息有误';
                        return $apiRet;
                    }
                } else {
                    $apiRet['apiInfo'] = '条码信息有误';
                    return $apiRet;
                }
            }
            //===============判断是否超重==========start======2020-9-15 10:56:26=======================================
            $sortOverweightG = $newWeight + $barcode['weight'];//当前订单商品重量
            $sortWeight = $sortOverweightG - $sortingGoodsInfo['orderWeight'];//判断是否超出订单商品重量
            //超出订单商品重量|小于或等于0时不做处理
            if ($sortWeight > 0) {
                if (empty($goodsInfo['sortOverweightG'])) {
                    $apiRet['apiInfo'] = '预打包商品重量有误';
                    return $apiRet;
                } else {
                    if ($sortOverweightG > $goodsInfo['sortOverweightG']) {
                        $apiRet['apiInfo'] = '超出系统非标品出库正负值标准,请核实';
                        return $apiRet;
                    }
                }
            }
            if ($sortWeight < 0) {
                $apiRet['apiInfo'] = '超出分拣商品重量,请查看';
                return $apiRet;
            }
            //================================end======================================================================
        } else {
            if ($request['barcode'] != $goodsInfo['goodsSn']) {
                $apiRet['apiInfo'] = '条码信息有误,请核对';
                return $apiRet;
            }
        }
        if (($request['goodsNum'] + $sortingGoodsInfo['sortingGoodsNum']) > $sortingGoodsInfo['goodsNum']) {
            $diffNums = $sortingGoodsInfo['goodsNum'] - $sortingGoodsInfo['sortingGoodsNum'];
            $apiRet['apiInfo'] = "该商品总分拣数为" . $sortingGoodsInfo['goodsNum'] . "件,已分拣" . $sortingGoodsInfo['sortingGoodsNum'] . "件,剩余分拣数为" . $diffNums . "件";
            return $apiRet;
        }

        $sql = "update __PREFIX__sorting_goods_relation set sortingGoodsNum=sortingGoodsNum+" . $request['goodsNum'] . " where sortingId='" . $request['sortingWorkId'] . "' and goodsId='" . $request['goodsId'] . "'";

        if (isset($request['skuId']) && !empty($request['skuId'])) {
            $sql .= " and skuId='" . $request['skuId'] . "' ";
        }
        $editRes = $this->execute($sql);
        //$editRes = true;//删
        $orderGoodsTab = M('order_goods');
        if ($editRes !== false) {
            //更改订单商品表中的分拣状态为分拣中
            $oWhere = [];
            $oWhere['orderId'] = $sortInfo['orderId'];
            $oWhere['goodsId'] = $goodsInfo['goodsId'];
            $oWhere['skuId'] = $request['skuId'];
            $orderGoodsInfo = $orderGoodsTab->where($oWhere)->find();
            $ogData = [];
            $ogData['actionStatus'] = 1;
            $ogData['sortingNum'] = $ogData['sortingNum'] + $request['goodsNum'];
            $orderGoodsTab->where(['id' => $orderGoodsInfo['id']])->save($ogData);
            //记录分拣任务操作日志 start
            $param = [];
            $param['sortingId'] = $request['sortingWorkId'];
            $param['content'] = "成功分拣商品[ " . $goodsInfo['goodsName'] . " ] " . $request['goodsNum'] . "份";
            insertSortingActLog($param);
            //记录分拣任务操作日志 end

            //已分拣后销毁条码
            if ($sortingGoodsInfo['SuppPriceDiff'] == 1) {
                //后加,如果是称重商品则记录补差价记录
                $orderInfo = M('orders')->where(['orderId' => $sortInfo['orderId']])->find();
                //系统预打包存在称重补差价，目前本地预打包存在称重补差价
                if ($request['isNativeGoods'] == 0) {
                    $totalMoney_order = $orderGoodsInfo['goodsPrice'];//当前商品总价 原来
                    $totalMoney_order1 = $orderGoodsInfo['goodsPrice'] / (float)$goodsInfo['weightG'] * $barcode['weight'];//根据重量得出的总价
                    if ($totalMoney_order > $totalMoney_order1 && $totalMoney_order1 > 0) {
                        $add_data['orderId'] = $sortInfo['orderId'];
                        $add_data['tradeNo'] = 0;
                        $add_data['goodsId'] = $goodsInfo['goodsId'];
                        $add_data['skuId'] = $orderGoodsInfo['skuId'];
                        $add_data['money'] = $totalMoney_order - $totalMoney_order1;//修复差价
                        $add_data['addTime'] = date('Y-m-d H:i:s');
                        $add_data['userId'] = $orderInfo['userId'];
                        $add_data['weightG'] = (float)$barcode['weight'];
                        $add_data['goosNum'] = 1;
                        $add_data['unitPrice'] = $orderGoodsInfo['goodsPrice'];
                        $res = M('goods_pricediffe')->add($add_data);
                    }

                    //where
                    $codeWhere['barcode'] = $request['barcode'];
                    $codeWhere['goodsId'] = $request['goodsId'];
                    if (isset($request['skuId']) && !empty($request['skuId'])) {
                        $codeWhere['skuId'] = $request['skuId'];//后加skuId
                    }
                    //edit
                    $orderData = M('orders')->where(['orderId' => $sortInfo['orderId']])->field('orderNo,basketId')->find();
                    $sortPerson = M('sortingpersonnel')->where(['id' => $sortInfo['personId']])->find();
                    $editData['bFlag'] = -1;
                    $editData['orderNo'] = $orderData['orderNo'];
                    $editData['basketId'] = $orderData['basketId'];
                    $editData['sid'] = $sortPerson['id'];
                    $editData['suserName'] = $sortPerson['userName'];
                    $editData['smobile'] = $sortPerson['mobile'];
                    $editBarcode = M('barcode')->where($codeWhere)->save($editData);
                }
                //记录称重
                $where = [];
                $where['id'] = $sortingGoodsInfo['id'];
                $save = [];
                $save['weightG'] = $sortingGoodsInfo['weightG'] + $barcode['weight'];
                $sortingGoodsTab->where($where)->save($save);
                //添加记录
                $add = [];
                $add['sortingGoodsId'] = $sortingGoodsInfo['id'];
                $add['barcode'] = $request['barcode'];
                $add['weightG'] = $barcode['weight'];
                $add['skuId'] = $request['skuId'];//辉辉-20220414
                $sortGoodsBarcode->add($add);
            }

            $nums = $sortingGoodsInfo['goodsNum'] - ($sortingGoodsInfo['sortingGoodsNum'] + $request['goodsNum']); //剩余待分拣的数量
            if ($sortingGoodsInfo['SuppPriceDiff'] == 1) {
                $sortingGoods = $sortingGoodsTab->where($gwhere)->find();
                $nums = $sortingGoodsInfo['orderWeight'] - $sortingGoods['weightG']; //剩余待分拣的重量

            }
            if ($nums <= 0) {
                //更改订单商品表中的状态为分拣完成
                $ogData = [];
                $ogData['actionStatus'] = 2;
                $ogData['sortingNum'] = $sortingGoodsInfo['goodsNum'];
                $orderGoodsTab->where(['id' => $orderGoodsInfo['id']])->save($ogData);

                //更改分拣商品的状态
                unset($swhere);
                $swhere['id'] = $sortingGoodsInfo['id'];

                $save = [];
                $save['status'] = 2;
                $save['sortingGoodsNum'] = $sortingGoodsInfo['goodsNum'];

                $sortingGoodsTab->where($swhere)->save($save);
                //记录分拣任务操作日志 start
                $param = [];
                $param['sortingId'] = $request['sortingWorkId'];
                $param['content'] = "商品[ " . $goodsInfo['goodsName'] . " ]已分拣完成,分拣数量" . $sortingGoodsInfo['goodsNum'];
                insertSortingActLog($param);
                //记录分拣任务操作日志 end
                //如果该分拣任务下的商品全部分拣完成,则改变分拣任务的状态为分拣完成
                checkSortingStatus($request['sortingWorkId']);
                $msg = "操作成功,该商品已经完成分拣";
            } else {
                $msg = "操作成功,该商品剩余待分拣数为" . $nums . "件";
                if ($sortingGoodsInfo['SuppPriceDiff'] == 1) {
                    $msg = "操作成功,该商品剩余待分拣重量为" . $nums . "g";
                }
            }
            //后加开始时间结束时间
            $sgEdit = [];
            $sgEdit['endDate'] = date('Y-m-d H:i:s', time());
            if ($sortingGoodsInfo['SuppPriceDiff'] == -1) {
                $sgEdit['barcode'] = $getBarcode;//条码[分拣时的条码]普通商品的条码
            }
            $sortingGoodsTab->where(['id' => $sortingGoodsInfo['id']])->save($sgEdit);

            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = $msg;
            $apiRet['goodsNum'] = (float)$nums;
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * @param $sortId
     * @param $request
     * @return mixed
     * @throws \Exception
     * 扫码直接分拣商品
     */
    public function editSortingGoods($sortId, $request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $getBarcode = $request['barcode'];//条码[分拣时的条码]

        $sortingGoodsTab = M('sorting_goods_relation sgr');
        $sortGoodsBarcode = M('sorting_goods_barcode');

        $where = [];
        $where['sgr.dataFlag'] = 1;
        $where['sgr.id'] = $request['sortingGoodsId'];
        $where['ws.shopid'] = $request['shopId'];
        $sortInfo = $sortingGoodsTab
            ->join("left join wst_sorting ws on ws.id = sgr.sortingId and ws.personId = {$sortId}")
            ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
            ->where($where)
            ->field('sgr.*,ws.status as sortingStatus,ws.orderId,wsu.userName')
            ->find();
        if (empty($sortInfo) || $sortInfo['status'] != 1) {
            $apiRet['apiInfo'] = '该分拣任务的状态异常,非分拣中状态';
            return $apiRet;
        }

        $goodsInfo = M('goods')->where(['goodsId' => $sortInfo['goodsId'], 'goodsFlag' => 1])->find();
        if (empty($goodsInfo)) {
            $apiRet['apiInfo'] = '请查看分拣商品是否存在';
            return $apiRet;
        }

        if($sortInfo["skuId"] > 0 ){//之前的没有处理sku
            $goodsModule = new GoodsModule();
            $skuInfo = $goodsModule->getSkuSystemInfoById($sortInfo["skuId"],2);
            if (empty($skuInfo)){
                $apiRet['apiInfo'] = '请查看分拣商品是否存在';
                return $apiRet;
            }
            $goodsInfo["goodsSn"] = $skuInfo["skuBarcode"];
        }

        //非标商品【称重商品】
        if ($sortInfo['SuppPriceDiff'] == 1) {

            $barcodeArr = explode('-', $request['barcode']);
            //系统预打包条码--存在数据库中
            if ($barcodeArr[0] == 'CZ') {
                $barcodeWhere = [];
                $barcodeWhere['goodsId'] = $sortInfo['goodsId'];
                $barcodeWhere['barcode'] = $request['barcode'];
                $barcodeWhere['bFlag'] = 1;
                if (!empty($sortInfo['skuId'])) {
                    $barcodeWhere['skuId'] = $sortInfo['skuId'];
                }
                $barcode = M('barcode')->where($barcodeWhere)->find();
                if (empty($barcode)) {
                    $apiRet['apiInfo'] = '条码信息有误,请核对';
                    return $apiRet;
                }
                $isNativeGoods = 0;//用于判断是否是本地预打包商品【0:不是|1:是】
            } elseif (preg_match("/^\d*$/", $request['barcode']) && strlen($request['barcode']) == 18) {
                //本地预打包条码--不存在数据库中
                $str = $request['barcode'];
                $codeF = (int)substr($str, 2);
                $codeW = (int)substr($str, 2, 5);//商品库编码
                $codeE = (int)substr($str, 7, 5);//金额单位 为 分
                $codeN = (int)substr($str, 12, 5);//重量为G 应该 待测试------
                $codeC = (int)substr($str, 17);

                if (!empty($codeW)) {
                    if (empty($sortInfo['skuId'])) {
                        $where = [];
                        $where['goodsFlag'] = 1;
                        $where['goodsSn'] = $codeW;
                        $where['shopId'] = $request['shopId'];
                        $barcodeInfo = M('goods')->where($where)->find();
                    } elseif (!empty($sortInfo['skuId'])) {
                        $barcodeInfo = M('sku_goods_system sy')
                            ->join("left join wst_goods g on g.goodsId=sy.goodsId")
                            ->where(["sy.skuBarcode" => $codeW, "g.shopId" => $request['shopId'], "g.goodsFlag" => 1, 'sy.skuId' => $sortInfo['skuId']])
                            ->field('sy.goodsId,sy.skuId')
                            ->find();
                    }
                    if (empty($barcodeInfo)) {
                        $apiRet['apiInfo'] = '条码信息有误';
                        return $apiRet;
                    }
                    $nativeGoodsInfo = [];
                    // 单位为G
                    setObj($nativeGoodsInfo, "weight", $codeN, null);
                    //用于变更称重重量
                    $barcode['weight'] = $nativeGoodsInfo['weight'];

                } else {
                    $apiRet['apiInfo'] = '条码信息有误';
                    return $apiRet;
                }
                $isNativeGoods = 1;//用于判断是否是本地预打包商品【0:不是|1:是】
            } else {
                $apiRet['apiInfo'] = '条码信息有误';
                return $apiRet;
            }

            //===============判断是否超重==========start======2020-9-15 10:56:26=======================================
            $sortOverweightG = $sortInfo['weightG'] + $barcode['weight'];//当前订单商品重量
            $sortWeight = $sortOverweightG - $sortInfo['orderWeight'];//判断是否超出订单商品重量
            //超出订单商品重量|小于或等于0时不做处理
            if ($sortWeight > 0) {
                if (empty($goodsInfo['sortOverweightG'])) {
                    $apiRet['apiInfo'] = '预打包商品重量有误';
                    return $apiRet;
                } else {
                    if ($sortWeight > $goodsInfo['sortOverweightG']) {
                        $apiRet['apiInfo'] = '超出系统非标品出库正负值标准,请核实';
                        return $apiRet;
                    }
                }
            }
            $editRes = true;
            $goodsNum = 1;
            //================================end======================================================================
        } else {
            $goodsBarcode = $request['barcode'];
            //有18位条码时
            if (preg_match("/^\d*$/", $request['barcode']) && strlen($request['barcode']) == 18) {
                //本地预打包条码--不存在数据库中
                $str = $request['barcode'];
                $codeF = (int)substr($str, 2);
                $codeW = (int)substr($str, 2, 5);//商品库编码
                $codeE = (int)substr($str, 7, 5);//金额单位 为 分
                $codeN = (int)substr($str, 12, 5);//重量为G 应该 待测试------
                $codeC = (int)substr($str, 17);

                if (!empty($codeW)) {
                    if (empty($sortInfo['skuId'])) {
                        $where = [];
                        $where['goodsFlag'] = 1;
                        $where['goodsSn'] = $codeW;
                        $where['shopId'] = $request['shopId'];
                        $barcodeInfo = M('goods')->where($where)->find();
                    } elseif (!empty($sortInfo['skuId'])) {
                        $barcodeInfo = M('sku_goods_system sy')
                            ->join("left join wst_goods g on g.goodsId=sy.goodsId")
                            ->where(["sy.skuBarcode" => $codeW, "g.shopId" => $request['shopId'], "g.goodsFlag" => 1, 'sy.skuId' => $sortInfo['skuId']])
                            ->field('sy.goodsId,sy.skuId')
                            ->find();
                    }
                    if (empty($barcodeInfo)) {
                        $apiRet['apiInfo'] = '条码信息有误';
                        return $apiRet;
                    }
                    $goodsBarcode = $codeW;
                } else {
                    $apiRet['apiInfo'] = '条码信息有误';
                    return $apiRet;
                }
            }
            //标品商品条码
            if ($goodsBarcode != $goodsInfo['goodsSn']) {
                $apiRet['apiInfo'] = '条码信息有误,请核对';
                return $apiRet;
            }
            //判断已分拣数量是否超出总分拣数量
            if (($sortInfo['sortingGoodsNum'] + 1) > $sortInfo['goodsNum']) {
                $diffNums = $sortInfo['goodsNum'] - $sortInfo['sortingGoodsNum'];
                $apiRet['apiInfo'] = "该商品总分拣数为" . $sortInfo['goodsNum'] . "件,已分拣" . $sortInfo['sortingGoodsNum'] . "件,剩余分拣数为" . $diffNums . "件";
                return $apiRet;
            }
            $editRes = $sortingGoodsTab->where(['sgr.id' => $request['sortingGoodsId'], 'sgr.goodsId' => $sortInfo['goodsId']])->setInc('sortingGoodsNum', 1);
            $goodsNum = 1;
        }

        //$editRes = true;//删
        $orderGoodsTab = M('order_goods');
        if ($editRes !== false) {
            //更改订单商品表中的分拣状态为分拣中
            $orderWhere = [];
            $orderWhere['orderId'] = $sortInfo['orderId'];
            $orderWhere['goodsId'] = $sortInfo['goodsId'];
            $orderWhere['skuId'] = $sortInfo['skuId'];
            $orderGoodsInfo = $orderGoodsTab->where($orderWhere)->find();
            $orderSave = [];
            $orderSave['actionStatus'] = 1;
            $orderSave['sortingNum'] = $orderGoodsInfo['sortingNum'] + $goodsNum;
            $orderGoodsTab->where(['id' => $orderGoodsInfo['id']])->save($orderSave);
            //记录分拣任务操作日志 start
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            if ($sortInfo['SuppPriceDiff'] == 1) {
                $content = "[{$sortInfo['userName']}]:成功分拣商品[ " . $goodsInfo['goodsName'] . " ] " . $barcode['weight'] . "g";
            } else {
                $content = "[{$sortInfo['userName']}]:成功分拣商品[ " . $goodsInfo['goodsName'] . " ] " . $goodsNum . "份";
            }
            $param['content'] = $content;
            insertSortingActLog($param);
            //记录分拣任务操作日志 end


            if ($sortInfo['SuppPriceDiff'] == 1) {
                if ($isNativeGoods == 0) {
                    //已分拣后销毁条码
                    //where
                    $codeWhere = [];
                    $codeWhere['barcode'] = $request['barcode'];
                    $codeWhere['goodsId'] = $sortInfo['goodsId'];
                    if (!empty($sortInfo['skuId'])) {
                        $codeWhere['skuId'] = $sortInfo['skuId'];//后加skuId
                    }
                    //edit
                    $orderData = M('orders')->where(['orderId' => $sortInfo['orderId']])->field('orderNo,basketId')->find();
                    $sortPerson = M('sortingpersonnel')->where(['id' => $sortInfo['personId']])->find();
                    $editData['bFlag'] = -1;
                    $editData['orderNo'] = $orderData['orderNo'];
                    $editData['basketId'] = $orderData['basketId'];
                    $editData['sid'] = $sortPerson['id'];
                    $editData['suserName'] = $sortPerson['userName'];
                    $editData['smobile'] = $sortPerson['mobile'];
                    M('barcode')->where($codeWhere)->save($editData);
                }
                //记录称重
                $where = [];
                $where['id'] = $sortInfo['id'];
                $save = [];
                $save['sgr.weightG'] = $sortInfo['weightG'] + $barcode['weight'];
                $sortingGoodsTab->where($where)->save($save);
            }

            //添加记录---后改,所有的条码都要记录
            $add = [];
            $add['sortingGoodsId'] = $sortInfo['id'];
            $add['barcode'] = $request['barcode'];
            $add['weightG'] = $barcode['weight'];
            $add['skuId'] = (int)$request['skuId'];//辉辉-20220414
            $sortGoodsBarcode->add($add);

            $nums = $sortInfo['goodsNum'] - ($sortInfo['sortingGoodsNum'] + 1); //剩余待分拣的数量
            if ($sortInfo['SuppPriceDiff'] == 1) {
                $where = [];
                $where['sgr.dataFlag'] = 1;
                $where['sgr.id'] = $request['sortingGoodsId'];
                $sortingGoods = $sortingGoodsTab->where($where)->find();
                $nums = $sortInfo['orderWeight'] - $sortingGoods['weightG']; //剩余待分拣的重量

            }
            if ($nums <= 0) {
                $msg = "操作成功,该商品已经完成分拣";
            } else {
                $msg = "操作成功,该商品剩余待分拣数为" . $nums . "件";
                if ($sortInfo['SuppPriceDiff'] == 1) {
                    $msg = "操作成功,该商品剩余待分拣重量为" . $nums . "g";
                }
            }
            //后加开始时间结束时间
            $sgEdit = [];
            $sgEdit['endDate'] = date('Y-m-d H:i:s', time());
//            if ($sortInfo['SuppPriceDiff'] == -1) {【弃用】
//                $sgEdit['barcode'] = $getBarcode;//条码[分拣时的条码]普通商品的条码
//            }
            $sortingGoodsTab->where(['sgr.id' => $sortInfo['id']])->save($sgEdit);

            $sortDate = $sortingGoodsTab->where(['sgr.id' => $sortInfo['id']])->find();
            if ($sortDate['SuppPriceDiff'] == -1) {
                $sortGoodsNum = $sortDate['goodsNum'];
                $sortingGoodsNum = $sortDate['sortingGoodsNum'];
                $sortGoodsNotNum = $sortDate['goodsNum'] - $sortDate['sortingGoodsNum'];
            } else {
                $sortGoodsNum = $sortDate['orderWeight'];
                $sortingGoodsNum = $sortDate['weightG'];
                $sortGoodsNotNum = $sortDate['orderWeight'] - $sortDate['weightG'];
                if ($sortGoodsNotNum <= 0) {
                    $sortGoodsNotNum = 0;
                }
            }

            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = $msg;
            $apiRet['apiState'] = 'success';
            $apiRet['goodsNum'] = $sortGoodsNum;//应分拣数量
            $apiRet['sortingGoodsNum'] = $sortingGoodsNum;//已分拣数量
            $apiRet['sortGoodsNotNum'] = $sortGoodsNotNum;//未分拣数量
        }
        return $apiRet;
    }

    /**
     * @param $sortId
     * @param $params
     * @return mixed
     * 确定完成分拣时-----获取未分拣商品数量
     */
    public function getSortGoodsNum($sortId, $params)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $serverTime = $params['server_time'];
        $confirm = $params['isConfirm'];

        $sortingGoodsTab = M('sorting_goods_relation sgr');

        $where = [];
        $where['sgr.dataFlag'] = 1;
        $where['sgr.id'] = $params['sortingGoodsId'];
        $where['ws.shopid'] = $params['shopId'];
        $where['sgr.status'] = 1;
        $field = "sgr.*, ";
        $field .= "ws.status as sortingStatus,ws.orderId, ";
        $field .= "wg.sortOverweightG,wg.weightG as goodsWeightG, ";
        $field .= "wo.userId,wo.tradeNo,wo.orderStatus,wo.shopId, ";
        $field .= "wog.goodsPrice,wog.couponMoney,wog.scoreMoney,wog.goodsName, ";
        $field .= "wsu.userName";

        $sortInfo = $sortingGoodsTab
            ->join("left join wst_sorting ws on ws.id = sgr.sortingId and ws.personId = {$sortId}")
            ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
            ->join("left join wst_goods wg on wg.goodsId = sgr.goodsId")
            ->join("left join wst_orders wo on wo.orderId = ws.orderId")
            ->join("left join wst_order_goods wog on wog.orderId = ws.orderId and wog.goodsId = sgr.goodsId and wog.skuId = sgr.skuId")
            ->where($where)
            ->field($field)
            ->find();

        if (empty($sortInfo)) {
            $apiRet['apiInfo'] = '请检查分拣任务商品的状态';
            return $apiRet;
        }

        //补差价操作==========start========================================================================
        if (!empty($serverTime) && $confirm == 1 && $serverTime < time()) {
            $status = 2;//分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成(待入框),3=>已入框(已完成))
            $add = [];
            $totalMoney_order = $sortInfo['goodsPrice'] * (float)$sortInfo['goodsNum'];//当前商品总价 原来
            if ($sortInfo['SuppPriceDiff'] == -1) {
                $totalMoney_order1 = $sortInfo['goodsPrice'] * $sortInfo['sortingGoodsNum'];//根据已分拣数量得出价格
                //如果已分拣数量为0，则直接标识当前分拣商品的分拣状态为已入框
                if ($sortInfo['sortingGoodsNum'] == 0) {
                    $status = 3;
                }
            } else {
                $totalMoney_order1 = $sortInfo['goodsPrice'] / (float)$sortInfo['goodsWeightG'] * $sortInfo['weightG'];//根据重量得出的总价
                if ($sortInfo['weightG'] == 0) {
                    $status = 3;
                }
            }

            $add['orderId'] = $sortInfo['orderId'];
            $add['tradeNo'] = $sortInfo['tradeNo'] ? $sortInfo['tradeNo'] : 0;
            $add['goodsId'] = $sortInfo['goodsId'];
            $add['skuId'] = $sortInfo['skuId'];
            $add['money'] = $totalMoney_order - $totalMoney_order1;//修复差价
            $add['money'] = bc_math($add['money'], bc_math($sortInfo['couponMoney'], $sortInfo['scoreMoney'], 'bcadd', 2), 'bcsub', 2);
            $add['addTime'] = date('Y-m-d H:i:s');
            $add['userId'] = $sortInfo['userId'];
            $add['weightG'] = (float)$sortInfo['weightG'];//已分拣重量
            $add['goosNum'] = $sortInfo['goodsNum'];//商品数量
            $add['unitPrice'] = $sortInfo['goodsPrice'];//下单后的商品单价
            M('goods_pricediffe')->add($add);

            $where = [];
            $where['sgr.dataFlag'] = 1;
            $where['sgr.id'] = $params['sortingGoodsId'];
            $where['sgr.status'] = 1;
            $sortingGoodsTab->where($where)->save(['status' => $status, 'endDate' => date('Y-m-d H:i:s')]);

            if ($status == 3) {
                //售后记录
                $addData = [];
                $addData['orderId'] = $sortInfo['orderId'];
                $addData['complainType'] = 5;//5：其他
                $addData['deliverRespondTime'] = date('Y-m-d H:i:s');
                $addData['complainContent'] = "商品缺货";
                $addData['complainAnnex'] = " ";
                $addData['goodsId'] = (int)$sortInfo['goodsId'];
                $addData['skuId'] = (int)$sortInfo['skuId'];
                $addData['complainTargetId'] = (int)$sortInfo['userId'];
                $addData['respondTargetId'] = (int)$sortInfo['shopId'];
                $addData['returnFreight'] = -1;//商家是否退运费【-1：不退运费|1：退运费】
                $addData['returnAmountStatus'] = 1;//退款状态【-1：已拒绝|0：待处理|1：已处理】
                $addData['needRespond'] = 1;//系统自动递交给店家
                $addData['complainStatus'] = 2;//投诉/退货状态【-1：已拒绝|0：待处理|1：退货中|2：已完成】
                $addData['returnAmount'] = 0;//实际退款金额 统计重复需要修改
                $addData['createTime'] = date('Y-m-d H:i:s');
                $addData['updateTime'] = date('Y-m-d H:i:s');
                $addData['respondTime'] = date('Y-m-d H:i:s');//应诉时间
                $res = M('order_complains')->add($addData);

                //写入退款记录表，并标注已退款
                $add_data['orderId'] = $sortInfo['orderId'];//订单id
                $add_data['goodsId'] = $sortInfo['goodsId'];
                $add_data['money'] = 0;
                $add_data['addTime'] = date('Y-m-d H:i:s');
                $add_data['payType'] = 1;
                $add_data['userId'] = $sortInfo['userId'];
                $add_data['skuId'] = (int)$sortInfo['skuId'];
                $add_data['complainId'] = (int)$res;
                M('order_complainsrecord')->add($add_data);


                //写入售后状态变动记录表
                $logParams = [
                    'tableName' => 'wst_order_complains',
                    'dataId' => $res,
                    'actionUserId' => $sortInfo['userId'],
                    'actionUserName' => $sortInfo['userName'],
                    'fieldName' => 'complainStatus',
                    'fieldValue' => 2,
                    'remark' => "分拣员：{$sortInfo['userName']}记录,当前商品缺货,全部补差价",
                    'createTime' => date('Y-m-d H:i:s'),
                ];
                M('table_action_log')->add($logParams);
            }


            //记录订单日志中
            $content = $sortInfo['goodsName'] . '#分拣补差价：' . $add['money'] . '元。确认收货后返款！';
            $logParams = [
                'orderId' => $sortInfo['orderId'],
                'logContent' => $content,
                'logUserId' => $sortId,
                'logUserName' => $sortInfo['userName'],
                'orderStatus' => $sortInfo['orderStatus'],
                'payStatus' => 1,
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);

            //记录分拣商品任务操作日志 start
            $msgInfo = "当前商品为部分补差价";
            if ($status == 3) {
                $msgInfo = "当前商品为全部补差价";
            }
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            $param['content'] = "[{$sortInfo['userName']}]:成功分拣完成商品[ " . $sortInfo['goodsName'] . " ],{$msgInfo}";
            insertSortingActLog($param);
            //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            $param['status'] = $status;
            checkSortGoodsStatus($param);
            $msg = "当前分拣商品已分拣完成";
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = $msg;
            $apiRet['is_popOut'] = 0;//是否弹窗1:是|0:否
            $apiRet['apiState'] = 'success';
            $apiRet['server_time'] = time();
            return $apiRet;
        }
        //==========================================end===============================================================

        //称重补差价[-1：否 1：是]
        if ($sortInfo['SuppPriceDiff'] == -1) {
            $diffNum = $sortInfo['goodsNum'] - $sortInfo['sortingGoodsNum'];
            $msg = "当前分拣商品应分拣数量{$sortInfo['goodsNum']},已分拣数量{$sortInfo['sortingGoodsNum']},未分拣数量" . $diffNum . "件";
        } else {
            $diffNum = $sortInfo['orderWeight'] - $sortInfo['weightG'];
            if ($diffNum < 0) {
                $diffNum = 0;
            }
            $msg = "当前分拣商品应分拣重量{$sortInfo['orderWeight']}g,已分拣重量{$sortInfo['weightG']}g,未分拣重量" . $diffNum . "g";
        }
        $is_popOut = 1;
        if ($diffNum == 0) {
            $where = [];
            $where['sgr.dataFlag'] = 1;
            $where['sgr.id'] = $params['sortingGoodsId'];
            $where['sgr.status'] = 1;
            $sortingGoodsTab->where($where)->save(['status' => 2, 'endDate' => date('Y-m-d H:i:s')]);
            //记录分拣商品任务操作日志 start
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            $param['content'] = "[{$sortInfo['userName']}]:成功分拣完成商品[ " . $sortInfo['goodsName'] . " ]";
            insertSortingActLog($param);

            //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            $param['status'] = 2;
            checkSortGoodsStatus($param);
            $msg = "当前分拣商品已分拣完成";
            $is_popOut = 0;
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = $msg;
        $apiRet['is_popOut'] = $is_popOut;//是否弹窗1:是|0:否
        $apiRet['apiState'] = 'success';
        $apiRet['server_time'] = time();
        return $apiRet;
    }

    /**
     * @param $sortId
     * @param $params
     * @return mixed
     * 入框时----商品扫码验证
     */
    public function getBasketGoodsVerify($sortId, $params)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $sortingGoodsTab = M('sorting_goods_relation sgr');
        $sortGoodsBarcode = M('sorting_goods_barcode');

        $where = [];
        $where['sgr.dataFlag'] = 1;
        $where['sgr.id'] = $params['sortingGoodsId'];
        $where['ws.shopid'] = $params['shopId'];
        $sortInfo = $sortingGoodsTab
            ->join("left join wst_sorting ws on ws.id = sgr.sortingId and ws.personId = {$sortId}")
            ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
            ->where($where)
            ->field('sgr.*,ws.status as sortingStatus,ws.orderId,ws.basketId,ws.orderId,wsu.userName')
            ->find();
        if (empty($sortInfo) || $sortInfo['status'] != 2) {
            $apiRet['apiInfo'] = '该分拣任务商品的状态异常,非待入框状态';
            return $apiRet;
        }
        $goodsInfo = M('goods')->where(['goodsId' => $sortInfo['goodsId'], 'goodsFlag' => 1])->find();
        if (empty($goodsInfo)) {
            $apiRet['apiInfo'] = '请查看分拣商品是否存在';
            return $apiRet;
        }
        //后改 标品【非称重商品】和 非标品【称重商品】条码都记录在同一个表中
        $where = [];
        $where['sortingGoodsId'] = $sortInfo['id'];
        $where['barcode'] = $params['barcode'];
        $where['isPackType'] = -2;
        $rest = $sortGoodsBarcode->where($where)->find();

        if (empty($rest)) {
            $apiRet['apiInfo'] = '商品条码信息有误,请核对';
            return $apiRet;
        }

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = "是该分拣任务下的商品";
        $apiRet['apiState'] = 'success';
        return $apiRet;
    }

    /**
     * @param $sortId
     * @param $params
     * @return mixed
     * 入框时----商品扫码入框
     */
    public function editSortGoodsBasket($sortId, $params)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $sortingGoodsTab = M('sorting_goods_relation sgr');
        $sortGoodsBarcode = M('sorting_goods_barcode');

        $where = [];
        $where['sgr.dataFlag'] = 1;
        $where['sgr.id'] = $params['sortingGoodsId'];
        $where['ws.shopid'] = $params['shopId'];
        $sortInfo = $sortingGoodsTab
            ->join("left join wst_sorting ws on ws.id = sgr.sortingId and ws.personId = {$sortId}")
            ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
            ->where($where)
            ->field('sgr.*,ws.status as sortingStatus,ws.orderId,ws.basketId,ws.orderId,wsu.userName')
            ->find();
        if (empty($sortInfo) || $sortInfo['status'] != 2) {
            $apiRet['apiInfo'] = '该分拣任务的状态异常,非待入框状态';
            return $apiRet;
        }
        $order_id = $sortInfo['orderId'];
        $goods_id = $sortInfo['goodsId'];
        $sku_id = $sortInfo['skuId'];

        $goodsInfo = M('goods')->where(['goodsId' => $sortInfo['goodsId'], 'goodsFlag' => 1])->find();
        if (empty($goodsInfo)) {
            $apiRet['apiInfo'] = '请查看分拣商品是否存在';
            return $apiRet;
        }
        $shop_id = $goodsInfo['shopId'];
        $shop_service_module = new ShopsServiceModule();
        $field = 'configId,open_suspension_chain';
        $shop_config_result = $shop_service_module->getShopConfig($shop_id, $field);
        $shop_config = $shop_config_result['data'];
        $title = '入框';
        if ($shop_config['open_suspension_chain'] == 1) {
            $title = '绑钩';
            //核对钩子条码
            $chain_service_module = new ChainServiceModule();
            $where = array(
                'shop_id' => $shop_id,
                'hook_code' => $params['basketSn'],
            );
            $hook_result = $chain_service_module->getHookInfoByParams($where);
            if ($hook_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $apiRet['apiInfo'] = '请确认钩子是否正确';
                return $apiRet;
            }
            $hook_data = $hook_result['data'];
        } else {
            //核对框位-----start-------
            $basketInfo = $this->getBasketInfo($sortInfo['basketId']);
            if ($basketInfo['basketSn'] != $params['basketSn']) {
                $apiRet['apiInfo'] = '请确认放入的框是否正确';
                return $apiRet;
            }
        }
        //后改--将标品的条码记录在表中了
        $where = [];
        $where['sortingGoodsId'] = $sortInfo['id'];
        $where['barcode'] = $params['barcode'];
        $where['isPackType'] = -2;
        $rest = $sortGoodsBarcode->where($where)->find();
        if (empty($rest)) {
            $apiRet['apiInfo'] = '商品条码信息有误,请核对';
            return $apiRet;
        }
        $sortGoodsBarcode->where(['goodsBarcodeId' => $rest['goodsBarcodeId']])->save(['isPackType' => -1]);
        //------------end---------
        $sortCount = 1;
        if ($sortInfo['SuppPriceDiff'] == -1) {//标品【非称重商品】
//            if ($params['barcode'] != $sortInfo['barcode']) {
//                $apiRet['apiInfo'] = '商品条码信息有误,请核对';
//                return $apiRet;
//            }
            $where = [];
            $where['sgr.dataFlag'] = 1;
            $where['sgr.id'] = $params['sortingGoodsId'];
            $sortingGoodsTab->where($where)->setInc('basketGoodsNum', 1);//商品入框变更入框数量
            $sortingInfo = $sortingGoodsTab->where($where)->find();
            $sortCount = (float)$sortingInfo['sortingGoodsNum'] - (float)$sortingInfo['basketGoodsNum'];//获取未入框数量
            $basketGoodsNum = $sortingInfo['basketGoodsNum'];//已入框数量
            $sortInfo['basketGoodsNum'] = (float)$sortingInfo['sortingGoodsNum'];//应入框数量
            $basketNum = "入框数量:1件";
        } elseif ($sortInfo['SuppPriceDiff'] == 1) {//非标品【称重商品】

            $where = [];
            $where['sgr.dataFlag'] = 1;
            $where['sgr.id'] = $params['sortingGoodsId'];
            $sortingGoodsTab->where($where)->setInc('basketGoodsNum', 1);//商品入框变更入框数量
            $where = [];
            $where['sortingGoodsId'] = $sortInfo['id'];
            //获取未入框|已入框数量
            $sortCountInfo = $sortGoodsBarcode->where($where)->field("sum(isPackType = -2) as sortCount,sum(isPackType = -1) as basketGoodsNums,count(isPackType) as basketGoodsNum")->select();
            $sortInfo['basketGoodsNum'] = (float)$sortCountInfo[0]['basketGoodsNum'];//获取应入框数量
            $sortCount = (float)$sortCountInfo[0]['sortCount'];//获取未入框数量
            $basketGoodsNum = (float)$sortCountInfo[0]['basketGoodsNums'];//已入框数量
            $basketNum = "入框重量:{$rest['weightG']}g";
        }

        //记录分拣任务商品操作日志 start
        $param = [];
        $param['sortingId'] = $sortInfo['sortingId'];
        $param['content'] = "[{$sortInfo['userName']}]:入框商品[ " . $goodsInfo['goodsName'] . " ]," . $basketNum;
        insertSortingActLog($param);
        //记录分拣任务商品操作日志 end
        //判断商品是否完成分拣
        if ($sortCount == 0) {
            $where = [];
            $where['sgr.dataFlag'] = 1;
            $where['sgr.id'] = $params['sortingGoodsId'];
            $sortingGoodsTab->where($where)->save(['status' => 3, 'endDate' => date('Y-m-d H:i:s')]);
            //记录分拣任务商品操作日志 start
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            $param['content'] = "[{$sortInfo['userName']}]:入框商品[ " . $goodsInfo['goodsName'] . " ]完成,入框数量" . $basketGoodsNum;
            insertSortingActLog($param);
            //记录分拣任务商品操作日志 end
            //如果该分拣任务下的商品全部完成状态变更,则改变分拣任务的状态
            $param = [];
            $param['sortingId'] = $sortInfo['sortingId'];
            $param['status'] = 3;
            checkSortGoodsStatus($param);
        }

        //商品绑钩
        if ($shop_config['open_suspension_chain'] == 1) {
            $where = array(
                'orderId' => $order_id,
                'goodsId' => $goods_id,
                'skuId' => $sku_id
            );
            $order_service_module = new OrdersServiceModule();
            $order_goods_info_result = $order_service_module->getOrderGoodsInfoByParams($where);
            $order_goods_info_data = $order_goods_info_result['data'];
            if (empty($order_goods_info_data['hook_id'])) {
                $save = array(
                    'id' => $order_goods_info_data['id'],
                    'hook_id' => $hook_data['hook_id'],
                    'bind_hook_date' => date('Y-m-d H:i:s'),
                );
                $save_result = $order_service_module->updateOrderGoodsInfo($save);
                if ($save_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $apiRet['apiInfo'] = '商品绑钩失败';
                    return $apiRet;
                }
            }
        }

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = "{$title}成功,剩余{$title}数量:{$sortCount}";
        $apiRet['apiState'] = 'success';
        $apiRet['basketNum'] = (float)$sortInfo['basketGoodsNum'];//需入框数量
        $apiRet['basketNot'] = (float)$sortCount;//未入框数量
        $apiRet['basketSent'] = (float)$basketGoodsNum;//已入框数量
        return $apiRet;
    }

    /*
     *获取商品详情
     * */
    public function getGoodsInfo($request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $goodsTab = M('goods');
        $barcodeArr = explode('-', $request['barcode']);
        //后加本地预打包条码【纯18位数字】
        if (preg_match("/^\d*$/", $request['barcode']) && strlen($request['barcode']) == 18) {
            $str = $request['barcode'];
            $codeF = (int)substr($str, 2);
            $codeW = (int)substr($str, 2, 5);//商品库编码
            $codeE = (int)substr($str, 7, 5);//金额单位 为 分
            $codeN = (int)substr($str, 12, 5);//重量为G 应该 待测试------
            $codeC = (int)substr($str, 17);


            if (!empty($codeW)) {
                $where = [];
                $where['goodsFlag'] = 1;
                $where['goodsSn'] = $codeW;
                $where['shopId'] = $request['shopId'];
                $barcodeInfo = M('goods')->where($where)->find();

                $goodsId = $barcodeInfo['goodsId'];
                if (empty($barcodeInfo)) {
                    //後加，兼容商品sku
                    $skuBarcodeInfo = M('sku_goods_system sy')
                        ->join("left join wst_goods g on g.goodsId = sy.goodsId")
                        ->where(["sy.skuBarcode" => $codeW, "g.shopId" => $request['shopId'], "g.goodsFlag" => 1])
                        ->field('sy.goodsId,sy.skuId')
                        ->find();
                    if (!empty($skuBarcodeInfo)) {
                        $barcodeInfo = $skuBarcodeInfo;
                        $goodsId = $skuBarcodeInfo['goodsId'];
                    } else {
                        $apiRet['apiInfo'] = '条码信息有误';
                        return $apiRet;
                    }
                }
                $nativeGoodsInfo = [];

                // 单位为G
                setObj($nativeGoodsInfo, "weight", $codeN, null);

                setObj($nativeGoodsInfo, "SuppPriceDiff", -1, null);//如果为预打包商品改为 -1

                setObj($nativeGoodsInfo, "goodsType", 1, null);//设置是预打包商品

                //称重结果金额分转为元
                setObj($nativeGoodsInfo, "price", $codeE / 100);

                setObj($nativeGoodsInfo, "shopPrice", $codeE / 100, null);//替换店铺价格

                //替换goodsSn
                setObj($nativeGoodsInfo, "goodsSn", $str, null);
                $SuppPriceDiff = 1;
            }
        } elseif ($barcodeArr[0] == 'CZ') {
            //称重
            $barcodeInfo = M('barcode')->where(['barcode' => $request['barcode'], 'shopId' => $request['shopId'], 'bFlag' => 1])->field('goodsId,skuId')->find();
            $goodsId = $barcodeInfo['goodsId'];
            if (!$goodsId) {
                $apiRet['apiInfo'] = '条码信息有误';
                return $apiRet;
            }
            $SuppPriceDiff = 1;
        } else {
            //标品
            $barcodeInfo = M('goods')->where(['goodsSn' => $request['barcode'], 'shopId' => $request['shopId'], 'goodsFlag' => 1])->field('goodsId')->find();
            $goodsId = $barcodeInfo['goodsId'];
            if (empty($barcodeInfo)) {
                //後加，兼容商品sku
                $skuBarcodeInfo = M('sku_goods_system sy')
                    ->join("left join wst_goods g on g.goodsId=sy.goodsId")
                    ->where(["sy.skuBarcode" => $request['barcode'], "g.shopId" => $request['shopId'], "g.goodsFlag=1"])
                    ->field('sy.goodsId,sy.skuId')
                    ->find();
                if (!empty($skuBarcodeInfo)) {
                    $barcodeInfo = $skuBarcodeInfo;
                    $goodsId = $skuBarcodeInfo['goodsId'];
                }
            }
            $SuppPriceDiff = -1;
        }
        $where = [];
        $where['goodsId'] = $goodsId;
        $goodsInfo = $goodsTab->where($where)->field(array("goodsDesc"), true)->find();
        if ($goodsInfo) {
            $goodsInfo['skuId'] = $barcodeInfo['skuId'];
            $goodsInfo = getCartGoodsSku($goodsInfo);
            $goodsInfo['goodsCatId1Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId1']);
            $goodsInfo['goodsCatId2Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId2']);
            $goodsInfo['goodsCatId3Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId3']);
            $goodsInfo['shopCatId1Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId1']);
            $goodsInfo['shopCatId2Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId2']);
            $goodsInfo['weightG'] = 0;
            $goodsInfo['isNativeGoods'] = 0;//用于判断是否是本地预打包商品【0:不是|1:是】
            //系统预打包称重商品
            if ($barcodeArr[0] == 'CZ') {
                $barcodeInfo = M('barcode')->where(['goodsId' => $goodsInfo['goodsId'], 'bFlag' => 1])->find();//条码称重
                if ($barcodeInfo) {
                    $goodsInfo['weightG'] = $barcodeInfo['weight'];
                }
            }
            //本地预打包商品
            if (!empty($nativeGoodsInfo)) {
                $goodsInfo['weightG'] = $nativeGoodsInfo['weight'];
                $goodsInfo['isNativeGoods'] = 1;//用于判断是否是本地预打包商品【0:不是|1:是】
            }
            $goodsInfo['barcode'] = $request['barcode'];
//            if ($goodsInfo['SuppPriceDiff'] == 1) {
//                $goodsInfo['barcode'] = $request['barcode'];
//            } else {
//                $goodsInfo['barcode'] = $goodsInfo['goodsSn'];
//            }
            if ($SuppPriceDiff == 1) {
                $goodsInfo['SuppPriceDiff'] = 1;
            } else {
                $goodsInfo['SuppPriceDiff'] = -1;
            }
            $goodsInfo['gallerys'] = [];//商品相册
            $gallerysTab = M('goods_gallerys');
            $gallerysList = $gallerysTab->where(['goodsId' => $goodsId])->order('id asc')->select();
            if ($gallerysList) {
                $goodsInfo['gallerys'] = $gallerysList;
            }
            //货位
            $goodsInfo['location'] = getGoodsLocation($goodsInfo['goodsId']);
            if ($goodsInfo) {
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
                $apiRet['apiData'] = $goodsInfo;
            }
        }
        return $apiRet;
    }

    /**
     * 根据商品名称获取商品详情
     * @param string keyword PS:商品名称|编码
     * */
    public function getGoodsInfoByName($request)
    {
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = [];

        $goodsTab = M('goods');
        //获取分拣员未完成的分拣任务
        $where = [];
        $where['personId'] = $request['sortId'];
        $where['status'] = ["IN", [0, 1]];
        $sortingList = M('sorting')->where($where)->select();
        $sortingId = [];
        foreach ($sortingList as $value) {
            $sortingId[] = $value['id'];
        }
        $keyword = $request['keyword'];
        //获取相关的商品
        $where = " shopId='" . $request['shopid'] . "' and goodsFlag=1 and isSale=1 ";
        $where .= " and (goodsSn like '%" . $keyword . "%' or goodsName like '%" . $keyword . "%')";
        $goodsList = $goodsTab->where($where)->field('goodsId')->select();
        $goodsId = [];
        if (!empty($goodsList)) {
            foreach ($goodsList as $value) {
                $goodsId[] = $value['goodsId'];
            }
        }
        if (!empty($sortingId) && !empty($goodsId)) {
            $where = [];
            $where['sortingId'] = ["IN", $sortingId];
            $where['goodsId'] = ["IN", $goodsId];
            $where['status'] = ["IN", [0, 1]];
            $where['dataFlag'] = 1;
            $sortingGoods = M('sorting_goods_relation')->where($where)->select();
            if (!empty($sortingGoods)) {
                $goodsList = [];
                foreach ($sortingGoods as $key => $val) {
                    $where = [];
                    $where['goodsId'] = $val['goodsId'];
                    $goodsInfo = $goodsTab->where($where)->field(array("goodsDesc"), true)->find();
                    if ($goodsInfo) {
                        $goodsInfo['skuId'] = $val['skuId'];
                        $goodsInfo = getCartGoodsSku($goodsInfo);
                        $goodsInfo['goodsCatId1Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId1']);
                        $goodsInfo['goodsCatId2Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId2']);
                        $goodsInfo['goodsCatId3Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId3']);
                        $goodsInfo['shopCatId1Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId1']);
                        $goodsInfo['shopCatId2Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId2']);
                        $goodsInfo['weightG'] = 0;
                        $goodsInfo['sortingWorkId'] = $val['sortingId'];
                        $goodsInfo['goodsNum'] = $val['goodsNum'];
                        $goodsInfo['sortingGoodsNum'] = $val['sortingGoodsNum'];
                        $goodsInfo['sortingStatus'] = $val['status'];
                        $barcodeInfo = M('barcode')->where(['goodsId' => $goodsInfo['goodsId']])->find();//条码称重
                        if ($barcodeInfo) {
                            $goodsInfo['weightG'] = $barcodeInfo['weight'];
                        }
                        if ($goodsInfo['SuppPriceDiff'] == 1) {
                            $goodsInfo['barcode'] = (string)$barcodeInfo['barcode'];
                        } else {
                            $goodsInfo['barcode'] = (string)$goodsInfo['goodsSn'];
                        }
                        $goodsInfo['gallerys'] = [];//商品相册
                        $gallerysTab = M('goods_gallerys');

                        //添加商户ID条件
                        $whereGallery = [];
                        $whereGallery['goodsId'] = $val['goodsId'];
                        $whereGallery['shopId'] = $goodsInfo['shopId'];
                        $gallerysList = $gallerysTab->where($whereGallery)->order('id asc')->select();

                        if ($gallerysList) {
                            $goodsInfo['gallerys'] = $gallerysList;
                        }
                        //货位
                        $goodsInfo['location'] = getGoodsLocation($goodsInfo['goodsId']);
                        $goodsList[] = $goodsInfo;
                    }
                }
                $apiRet['apiData'] = $goodsList;
            }
        }
        return $apiRet;
    }

    /*
     *获取框位
     * @param int $basketId PS:框位id
     * */
    public function getBasketInfo($basketId)
    {
        $basketInfo = [];
        if ($basketId > 0) {
            $basketInfo = M('basket')->where(['bid' => $basketId])->field('pid,partitionId,name,basketSn')->find();
            $partitionTab = M('partition');
            $basketInfo['firstPartitionName'] = $partitionTab->where(['id' => $basketInfo['pid']])->getField('name');
            $basketInfo['secondPartitionName'] = $partitionTab->where(['id' => $basketInfo['partitionId']])->getField('name');
            $basketInfo['basketId'] = $basketId;
        }
        return $basketInfo;
    }


    /*
     * 申请结算
     * @param int sortingWorkId PS:任务id
     * @param int sortId PS:分拣员id
     * */
    public function subSettlement($request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $sortTab = M('sorting');
        $where['settlement'] = 0;
        $where['sortingFlag'] = 1;
        $where['id'] = $request['sortingWorkId'];
        $where['personId'] = $request['sortId'];
        $sortingInfo = $sortTab->where($where)->find();
        if (!$sortingInfo) {
            $apiRet['apiInfo'] = '无效的数据';
            return $apiRet;
        }
        $edit['id'] = $sortingInfo['id'];
        $editStatus = $sortTab->where($edit)->save(['settlement' => 1]);
        if ($editStatus !== false) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     * 获取结算列表
     * */
    public function getSettlementList($sortId, $settlement, $page, $pageDataNum)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        //where
        $where['s.settlement'] = ["IN", [1, 2]];
        if ($settlement != 10) {
            $where['s.settlement'] = $settlement;
        }
        $field = [
            's.*',
            'o.orderNo',
            'o.basketId',
            'o.shopId',
            'o.orderStatus',
            'o.totalMoney',
            'o.payType',
            'o.isSelf',
            'o.isPay',
            'o.deliverType',
            'o.createTime',
            'o.needPay',
            'o.defPreSale',
            'o.PreSalePay',
            'o.PreSalePayPercen',
            'o.deliverMoney',
            'o.isAppraises',
            'u.userName as buyer_userName',
            'u.userPhone as buyer_userPhone',
            'u.userPhoto as buyer_userPhoto',
        ];
        $where['s.personId'] = $sortId;
        $where['s.sortingFlag'] = 1;
        $list = M('sorting s')
            ->join("left join wst_orders o on o.orderId=s.orderId")
            ->join("left join wst_users u on o.userId=u.userId")
            ->where($where)
            ->field($field)
            ->order('s.addtime desc')
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();

        $where['s.settlement'] = 1;
        $countNo = M('sorting s')
            ->join("left join wst_orders o on o.orderId=s.orderId")
            ->where($where)
            ->field($field)
            ->count();
        $where['s.settlement'] = 2;
        $countYes = M('sorting s')
            ->join("left join wst_orders o on o.orderId=s.orderId")
            ->join("left join wst_users u on o.userId=u.userId")
            ->where($where)
            ->count();

        $sortingGoodsTab = M('sorting_goods_relation');
        $shopTab = M('shops');
        foreach ($list as $key => $val) {
            //shop
            $shopInfo = $shopTab->where(['shopId' => $val['shopId']])->find();
            $list[$key]['shopName'] = $shopInfo['shopName'];
            $list[$key]['shopImg'] = $shopInfo['shopImg'];
            //goods
            $swhere['dataFlag'] = 1;
            $swhere['sortingId'] = $val['id'];
            $list[$key]['goods'] = $sortingGoodsTab->where($swhere)->select();
            //框位
            $list[$key]['basketInfo'] = $this->getBasketInfo($val['basketId']); //获取框位信息
        }
        $goodsTab = M('goods');
        foreach ($list as $key => $val) {
            $list[$key]['totalSoringGoods'] = 0;//该分拣任务下的所有商品数量的总和
            $list[$key]['totalSortingGoodsNum'] = 0;//该分拣任务下已完成分拣的商品数量总和
            foreach ($val['goods'] as $k => $v) {
                $list[$key]['totalSoringGoods'] += $v['goodsNum'];
                $list[$key]['totalSortingGoodsNum'] += $v['sortingGoodsNum'];
                $goodsInfo = $goodsTab->where(['goodsId' => $v['goodsId']])->field("recommDesc,goodsName,goodsUnit,goodsSpec,goodsDesc,saleCount,goodsImg,goodsThums")->find();
                $list[$key]['goods'][$k]['goodsName'] = $goodsInfo['goodsName'];
                $list[$key]['goods'][$k]['recommDesc'] = $goodsInfo['recommDesc'];
                $list[$key]['goods'][$k]['goodsUnit'] = $goodsInfo['goodsUnit'];
                $list[$key]['goods'][$k]['goodsSpec'] = $goodsInfo['goodsSpec'];
                $list[$key]['goods'][$k]['goodsDesc'] = $goodsInfo['goodsDesc'];
                $list[$key]['goods'][$k]['saleCount'] = $goodsInfo['saleCount'];
                $list[$key]['goods'][$k]['goodsImg'] = $goodsInfo['goodsImg'];
                $list[$key]['goods'][$k]['goodsThums'] = $goodsInfo['goodsThums'];
                $orderGoodsInfo = M('order_goods')->where(['orderId' => $val['orderId'], 'goodsId' => $v['goodsId']])->find();
                $list[$key]['goods'][$k]['goodsPrice'] = $orderGoodsInfo['goodsPrice'];
                //$list[$key]['goods'][$k]['location'] = []; //商品对应的货位信息
                //location 商品对应的货位信息,目前只做两级
                $list[$key]['goods'][$k] = getCartGoodsSku($list[$key]['goods'][$k]);
                $list[$key]['goods'][$k]['location'] = getGoodsLocation($v['goodsId']); //商品对应的货位信息
            }
        }
        if ($list !== false) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
            $apiRet['countYes'] = $countYes;
            $apiRet['countNo'] = $countNo;
        }
        return $apiRet;
    }

    /*
     *一键结算 PS(自动将分拣完成状态的任务申请结算)
     * */
    public function autoSortingSettlement($sortId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sorting');
        //where
        $where['personId'] = $sortId;
        $where['settlement'] = 0;
        $where['status'] = 2;
        //edit
        $edit['settlement'] = 1;
        $edit['updatetime'] = date('Y-m-d H-i-s', time());
        $res = $tab->where($where)->save($edit); //待申请->待结算
        if ($res !== false) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /*
     *获取商品商城分类
     * */
    public function getGoodsCatName($catId)
    {
        $catname = '';
        if ($catId > 0) {
            $catname = M('goods_cats')->where(['catId' => $catId])->getField('catName');
        }
        return $catname;
    }

    /*
     *获取商品店铺分类
     * */
    public function getGoodsShopCatName($catId)
    {
        $catname = '';
        if ($catId > 0) {
            $catname = M('shops_cats')->where(['catId' => $catId])->getField('catName');
        }
        return $catname;
    }

    /*
     *获取分拣任务的操作日志
     * */
    public function getSortingActLog($sortingId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sorting_action_log');
        //where
        $where['sortingId'] = $sortingId;

        $list = $tab->where($where)->order('id asc')->select();
        if ($list) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
        }
        return $apiRet;
    }

    /*
     *获取分拣任务各个状态下的数量
     * */
    public function getSortingCount($sortId)
    {
        $info['all'] = 0;
        $info['statusNo'] = 0;
        $info['statusWaiting'] = 0;
        $info['statusYes'] = 0;

        $where = " where personId='" . $sortId . "' and sortingFlag=1 ";
        $sql = "select count(personId) from __PREFIX__sorting " . $where;
        $count = $this->queryRow($sql)['count(personId)'];
        if (!is_null($count)) {
            $info['all'] = $count;
        }

        $sql = "select count(personId) from __PREFIX__sorting " . $where . " and status=0 ";
        $count = $this->queryRow($sql)['count(personId)'];
        if (!is_null($count)) {
            $info['statusNo'] = $count;
        }

        $sql = "select count(personId) from __PREFIX__sorting " . $where . " and status=1 ";
        $count = $this->queryRow($sql)['count(personId)'];
        if (!is_null($count)) {
            $info['statusWaiting'] = $count;
        }

        $sql = "select count(personId) from __PREFIX__sorting " . $where . " and status=2 ";
        $count = $this->queryRow($sql)['count(personId)'];
        if (!is_null($count)) {
            $info['statusYes'] = $count;
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $info;
        return $apiRet;
    }

    /**
     * @param $param
     * @param $sortId
     * @return mixed
     * 获取打包订单列表
     */
    public function getSortingPackList($param, $sortId)
    {
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        $packType = I('packType', 0);//合并打包状态[1:待打包|2:已打包]
        $orderNo = I('orderNo', 0);//订单编号
        $where = [];
        $where['s.status'] = 3;
        $where['s.sortingFlag'] = 1;
        $where['s.isPack'] = -1;

        if (!empty($packType)) {
            //是否打包[-1:未进入|1:已进入]
            $where['s.isPack'] = 1;
            $where['wsp.packType'] = $packType;
            $where['wsp.personId'] = $sortId;//进入打包后，需要根据分拣员来获取
        }
        if (!empty($orderNo)) {
            $where['o.orderNo'] = ['like', "%" . $orderNo . "%"];
        }
        $sortModel = M('sorting s');
        $field = "s.orderId,s.basketId,o.orderNo,wsp.packType,wsp.updateTime,s.id";
        $data = $sortModel
            ->join('left join wst_sorting_packaging wsp on wsp.orderId = s.orderId')
            ->join('left join wst_orders o on o.orderId = s.orderId')
            ->where($where)
            ->field($field)
            ->order('wsp.updateTime desc')
            ->group('o.orderId')
            ->select();
        $rest = [];
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $sortOrderVerify = $this->sortOrderVerify($v['orderId']);
                if ($sortOrderVerify['apiCode'] != 0) {//如果订单下存在未完成的分拣任务,就跳出当前
                    continue;
                }
                $goodsCount = $this->getOrderBasketGoods($v['orderId'], 0);//获取入框商品数量
                if ($goodsCount == 0) {//如果入框商品数量为0,则跳出当前订单
                    continue;
                }
                $packGoodsCount = M('sorting_pack_goods')->where(['orderId' => $v['orderId']])->count();
                $v['goodsCount'] = (float)$goodsCount;//打包商品数量
                $v['packGoodsCount'] = (float)$packGoodsCount;//已打包商品数量
                $v['basketInfo'] = $this->getOrderBasket($v['orderId']); //获取框位信息
                $rest[] = $v;
            }
        }

        $count = count($rest);
        $pageData = array_slice($rest, ($page - 1) * $pageSize, $pageSize);
        $res = [];
        $res['root'] = $pageData;
        $res['totalPage'] = ceil($count / $pageSize);//总页数
        $res['currentPage'] = $page;//当前页码
        $res['total'] = (float)$count;//总数量
        $res['pageSize'] = $pageSize;//页码条数

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $res;
        return $apiRet;
    }

    /**
     * @param $orderId
     * @param $type 非常规订单入框需要验证
     * @return int
     * 通过订单ID获取入框商品数量
     */
    public function getOrderBasketGoods($orderId, $type)
    {
        $where = [];
        $where['s.orderId'] = $orderId;
        $sortModel = M('sorting s');
        $field = "sgr.*,s.settlementNo ";
        $data = $sortModel
            ->join("left join wst_sorting_goods_relation sgr on sgr.sortingId = s.id")
            ->where($where)
            ->field($field)
            ->select();
        $num = 0;//统计商品数量
        if (!empty($data)) {
            foreach ($data as $v) {
                if ($v['SuppPriceDiff'] == -1 && $v['sortingGoodsNum'] != 0) {//标品
                    $num += 1;
                }
                if ($v['SuppPriceDiff'] == 1) {//非标品
                    $barcodeCount = M('sorting_goods_barcode')->where(['sortingGoodsId' => $v['id']])->count();
                    if ($barcodeCount > 0) {
                        $num += 1;
                    }
                }
            }
        }
        //用于非常规订单验证
        if ($type == 1) {
            return (float)$num;
        }
        //如果统计的商品数量为0,则将当前分拣任务进行是否进入打包状态【isPack是否打包[-1:未进入|1:已进入]】变更。
        if ($num == 0) {
            $sortModel->where($where)->save(['isPack' => 1]);
            foreach ($data as $v) {
                $param = [];
                $param['sortingId'] = $v['sortingId'];
                $param['content'] = "【分拣任务下所有商品都已经补差价】系统自动更改分拣任务[ {$v['settlementNo']} ]状态为已打包";
                insertSortingActLog($param);
            }
//            editOrderStatus($orderId);//打包完成后触发---改变订单状态为配送中
            //记录分拣任务操作日志 end
        }
        return (float)$num;
    }

    /**
     * @param $orderId
     * @return array
     * 通过订单id获取分拣任务的框位
     */
    public function getOrderBasket($orderId)
    {
        $where = [];
        $where['sortingFlag'] = 1;
        $where['orderId'] = $orderId;

        $sortModel = M('sorting');
        $data = $sortModel->where($where)->select();
        $basketList = [];
        if ($data > 0) {
            $basketId = array_unique(array_get_column($data, "basketId"));
            $where = [];
            $where['bid'] = ['IN', $basketId];
            $basketList = M('basket')->where($where)->field('pid,partitionId,name,basketSn,bid as basketId')->select();
            $partitionTab = M('partition');
            foreach ($basketList as $k => $v) {
                $basketList[$k]['firstPartitionName'] = $partitionTab->where(['id' => $v['pid']])->getField('name');
                $basketList[$k]['secondPartitionName'] = $partitionTab->where(['id' => $v['partitionId']])->getField('name');
                $basketList[$k]['basketId'] = $v['basketId'];
            }
        }
        return $basketList;
    }

    /**
     * @param $orderId
     * @return mixed
     * 检查订单下的分拣任务是否全部分拣完成
     */
    public function sortOrderVerify($orderId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $where = [];
        $where['s.sortingFlag'] = 1;
        $where['s.orderId'] = $orderId;

        $sortModel = M('sorting s');
        $field = "s.*";
        $data = $sortModel
            ->join('left join wst_orders o on o.orderId = s.orderId')
            ->where($where)
            ->field($field)
            ->select();
        foreach ($data as $v) {
            if ($v['status'] != 3) {
                $apiRet['apiInfo'] = '订单还没有分拣完';
                return $apiRet;
            }
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '订单没问题';
        $apiRet['apiState'] = 'success';
        return $apiRet;
    }

    /**
     * @param $sortId
     * @param $params
     * @return mixed
     * 变更打包订单
     */
    public function updateSortingPack($sortId, $params)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $where = [];
        if (!empty($params['orderId'])) {
            $where['wst_sorting.orderId'] = (int)$params['orderId'];
        } else {
            $where['wo.orderNo'] = $params['orderNo'];
        }
        $where['wst_sorting.status'] = 3;
        $where['wst_sorting.isPack'] = -1;
        $sortModel = M('sorting');
        $sortPackModel = M('sorting_packaging');
        $data = $sortModel
            ->join("left join wst_sortingpersonnel ws on ws.id = {$sortId}")
            ->join("left join wst_orders wo on wo.orderId = wst_sorting.orderId")
            ->where($where)
            ->field('wst_sorting.id,ws.userName,wst_sorting.settlementNo,wst_sorting.orderId')
            ->select();
        if (empty($data)) {
            $apiRet['apiInfo'] = '该订单已被领取或未分拣完';
            return $apiRet;
        }
        foreach ($data as $v) {
            $sortOrderVerify = $this->sortOrderVerify($v['orderId']);
            if ($sortOrderVerify['apiCode'] != 0) {//如果订单下存在未完成的分拣任务
                $apiRet['apiInfo'] = '该订单还存在未分拣完的任务';
                return $apiRet;
            }
        }
        $orderId = $data[0]['orderId'];
        $where = [];
//        $where['personId'] = (int)$sortId;
        $where['orderId'] = (int)$orderId;
        $sortPackInfo = $sortPackModel->where($where)->find();
        if (!empty($sortPackInfo)) {
            $apiRet['apiInfo'] = '该订单已被领取';
            return $apiRet;
        }
        if (!empty($data)) {
            M()->startTrans();
            $save = [];
            $save['orderId'] = $orderId;
            $upSortPack = $sortModel->where($save)->save(['isPack' => 1]);
            if (empty($upSortPack)) {
                M()->rollback();
                $apiRet['apiInfo'] = '领取失败';
                return $apiRet;
            }
            $save['personId'] = $sortId;
            $save['createTime'] = date('Y-m-d H:i:s');
            $res = $sortPackModel->add($save);
            if (empty($res)) {
                M()->rollback();
                $apiRet['apiInfo'] = '领取失败';
                return $apiRet;
            }
            M()->commit();
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '领取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res;
            //记录分拣任务操作日志 start
            foreach ($data as $v) {
                $param = [];
                $param['sortingId'] = $v['id'];//分拣任务id
                $param['content'] = "[{$v['userName']}]:领取分拣任务[ {$v['settlementNo']} ]";
                insertSortingActLog($param);
            }
        }
        return $apiRet;
    }

    /**
     * @param $sortId
     * @param $orderNo
     * @return mixed
     * 获取打包订单详情
     */
    public function getSortingPackInfo($sortId, $orderNo)
    {
        $where = [];
//        $where['wsp.personId'] = (int)$sortId;
        $where['o.orderNo'] = $orderNo;
        $sortModel = M('sorting s');
        $field = "s.orderId,o.orderNo,wsp.packType,o.userPhone,o.userName ";
        $data = $sortModel
            ->join('left join wst_orders o on o.orderId = s.orderId')
            ->join("left join wst_sorting_packaging wsp on wsp.orderId = s.orderId")
            ->where($where)
            ->field($field)
            ->group("o.orderId")
            ->find();

        if (!empty($data)) {
            $orderId = $data['orderId'];
            $goodsCount = $this->getOrderBasketGoods($orderId, 0);//获取入框商品数量

            $packGoodsCount = M('sorting_pack_goods')->where(['orderId' => $orderId])->count();
            $data['goodsCount'] = (float)$goodsCount;//打包商品数量
            $data['packGoodsCount'] = (float)$packGoodsCount;//已打包商品数量
            //商品
            $data['goodsList'] = $this->getGoodsList($orderId); //获取商品信息
            //框位
            $data['basketInfo'] = $this->getOrderBasket($orderId); //获取框位信息
        } else {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请查看分拣订单是否存在';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $data;
        return $apiRet;
    }

    /**
     * @param $orderId
     * @return mixed
     * 获取商品信息
     */
    public function getGoodsList($orderId)
    {
        $sortModel = M('sorting s');
        $field = "og.goodsName,wg.goodsImg,wg.goodsThums,og.goodsNums,og.goodsId,og.skuSpecAttr,og.skuId,og.goodsPrice,og.id as orderGoodsId";
        $data = M('order_goods og')
            ->join("left join wst_goods wg on wg.goodsId = og.goodsId")
            ->where(['og.orderId' => $orderId])
            ->field($field)
            ->select();
        $data = getCartGoodsSku($data);
        foreach ($data as $k => $v) {
            $sortGoodsInfo = $sortModel
                ->join("left join wst_sorting_goods_relation sgr on sgr.sortingId = s.id and sgr.goodsId = {$v['goodsId']} and sgr.skuId = {$v['skuId']}")
                ->where(['s.orderId' => $orderId])
                ->field("sgr.*")
                ->find();
            $data[$k]['basketGoodsNum'] = (float)$sortGoodsInfo['basketGoodsNum'];//已入框数量
            $data[$k]['packGoodsNum'] = (float)$sortGoodsInfo['packGoodsNum'];//已打包数量
            $data[$k]['packGoodsNotNum'] = (float)$sortGoodsInfo['basketGoodsNum'] - (float)$sortGoodsInfo['packGoodsNum'];//未打包数量
            //称重补差价[-1：否 1：是]
            $goodsInfo = M('sorting_goods_barcode sgb')
                ->join("left join wst_goods wg on wg.goodsId = {$sortGoodsInfo['goodsId']}")
                ->join("left join wst_order_goods og on og.id = {$v['orderGoodsId']}")
                ->where(['sgb.sortingGoodsId' => $sortGoodsInfo['id']])
                ->field('sgb.*,wg.goodsName,wg.goodsImg,wg.goodsThums,og.goodsPrice,og.skuSpecAttr')
                ->select();
            $isUnusual = 0;//是否异常(用于区分当前商品是否全部都补差价)【0:正常|1:异常】
            if ($sortGoodsInfo['SuppPriceDiff'] == 1) {//称重商品
                if (empty($goodsInfo)) {
                    $isUnusual = 1;
                }
            } else {
                if ($sortGoodsInfo['basketGoodsNum'] == 0) {
                    $isUnusual = 1;
                }
            }
            $data[$k]['isUnusual'] = $isUnusual;//是否异常(用于区分当前商品是否全部都补差价)【0:正常|1:不正常】
            $where = [];
            $where['orderId'] = $orderId;
            $where['goodsId'] = $v['goodsId'];
            $where['skuId'] = $v['skuId'];
            $packGoods = M('sorting_pack_goods')->where($where)->find();
            if (empty($packGoods)) {
                $data[$k]['isPut'] = 0;//未打包
            } else {
                $data[$k]['isPut'] = 1;//已打包
            }
            $data[$k]['goodsInfo'] = (array)$goodsInfo;//称重商品下的子商品【分拣时扫的条码信息】
        }
        //进行数据排序【异常商品,排在后面】
        $isUnusualAsc = array_column($data, 'isUnusual');
        array_multisort($isUnusualAsc, SORT_ASC, $data);
        return (array)$data;
    }

    /**
     * @param $params
     * @return mixed
     * 商品打包---旧
     */
    public function addSortingPackGoods($params)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $orderNo = $params['orderNo'];
        $skuId = $params['skuId'];
        $orderModel = M('orders o');
        $order = $orderModel
            ->join("left join wst_sorting ws on ws.orderId = o.orderId")
            ->where(['orderNo' => $orderNo])
            ->field("o.*,ws.id as sortingId")
            ->find();

        //分拣商品信息
        $sortingGoodsTab = M('sorting_goods_relation');
        $sortingGoodsBarcode = M('sorting_goods_barcode');
        $where = [];
        $where['sortingId'] = $order['sortingId'];
        $where['goodsId'] = $params['goodsId'];
        if (isset($params['skuId']) && !empty($params['skuId'])) {
            $where['skuId'] = $params['skuId'];//后加skuId
        }
        $sortingGoodsInfo = $sortingGoodsTab->where($where)->find();
        $goodsInfo = M('goods')->where(['goodsId' => $params['goodsId'], 'goodsFlag' => 1])->find();
        if (!$sortingGoodsInfo || !$goodsInfo) {
            $apiRet['apiInfo'] = '分拣商品有误,请核对';
            return $apiRet;
        }
        if ($sortingGoodsInfo['skuId'] != $skuId) {
            $apiRet['apiInfo'] = '分拣商品有误,请核对';
            return $apiRet;
        }
        //条码核对
        if ($sortingGoodsInfo['SuppPriceDiff'] == -1) {
            if ($params['barcode'] != $sortingGoodsInfo['barcode']) {
                $apiRet['apiInfo'] = '商品条码信息有误,请核对';
                return $apiRet;
            }
            $goodsBarcodeInfo = [];
            $goodsBarcodeInfo['type'] = 0;//用于判断条码是否是标品商品【0:标品|1:非标品】
        } elseif ($sortingGoodsInfo['SuppPriceDiff'] == 1) {
            $where = [];
            $where['sortingGoodsId'] = $sortingGoodsInfo['id'];
            $where['barcode'] = $params['barcode'];
            $where['isPackType'] = -1;
            $rest = $sortingGoodsBarcode->where($where)->find();
            if (empty($rest)) {
                $apiRet['apiInfo'] = '商品条码信息有误,请核对';
                return $apiRet;
            }
            $where = [];
            $where['sortingGoodsId'] = $sortingGoodsInfo['id'];
            $where['isPackType'] = -1;
            //获取当前非标品商品存在数量
            $sortCount = $sortingGoodsBarcode->where($where)->count();
            $goodsBarcodeInfo = [];
            $goodsBarcodeInfo['goodsBarcodeId'] = $rest['goodsBarcodeId'];
            $goodsBarcodeInfo['type'] = 1;//用于判断条码是否是标品商品【0:标品|1:非标品】
        }


        $orderId = $order['orderId'];
        $save = [];
        $save['orderId'] = $orderId;
        $save['goodsId'] = $goodsInfo['goodsId'];
        if (!empty($skuId)) {
            $save['skuId'] = $skuId;
        }
        $packGoodsModel = M('sorting_pack_goods');
        $res = $packGoodsModel->where($save)->find();
        if (!empty($res)) {
            $apiRet['apiInfo'] = '商品已打包';
            return $apiRet;
        }
        $save['createTime'] = date('Y-m-d H:i:s');
        if ($goodsBarcodeInfo['type'] == 1) {
            $data = 1;
            //判断是否打包完成
            if ($sortCount == 1) {
                $data = $packGoodsModel->add($save);
            }
        } else {
            $data = $packGoodsModel->add($save);
        }
        if (!empty($data)) {
            //----------------------- 记录打包商品任务操作日志 start ------------------------------
            $where = [];
            $where['s.orderId'] = $orderId;
            $sortModel = M('sorting s');
            $sortData = $sortModel->join("left join wst_sortingpersonnel ws on ws.id = s.uid")->where($where)->field('s.*,ws.userName')->find();
            $param = [];
            $param['sortingId'] = $sortData['id'];//分拣任务id
            $param['content'] = "[{$sortData['userName']}]:打包任务商品[ {$goodsInfo['goodsName']} ]";
            insertSortingActLog($param);
            //----------------------------end-------------------------------------------------------
            //-------------------start判断是否打包完成--------2020-8-18 20:49:07------------------
            if ($goodsBarcodeInfo['type'] == 0) {//标品时直接打包
                $this->verifySortPack($orderId);
            }
            //------------------end--------------------------------------------------------------
            if ($goodsBarcodeInfo['type'] == 1) {//非标品打包时，进行数据判断
                $sortingGoodsBarcode->where(['goodsBarcodeId' => $goodsBarcodeInfo['goodsBarcodeId']])->save(['isPackType' => 1]);
                //判断是否打包完成
                if ($sortCount == 1) {//当打包时当前商品只存在一条数据时，进行数据判断
                    $this->verifySortPack($orderId);
                }
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $data;
        } else {
            $apiRet['apiInfo'] = '打包失败';
        }
        return $apiRet;
    }

    /**
     * @param $params
     * @return mixed
     * 商品扫码打包--新
     */
    public function editSortingPackGoods($params)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $orderNo = $params['orderNo'];
        $barcode = $params['barcode'];
        $orderModel = M('orders o');
        $order = $orderModel
            ->join("left join wst_sorting ws on ws.orderId = o.orderId")
            ->where(['orderNo' => $orderNo])
            ->field("o.*,ws.id as sortingId")
            ->select();
        if (empty($order)) {
            $apiRet['apiInfo'] = '请确定当前订单是否存在';
            return $apiRet;
        }
        $sortGoodsIds = array_get_column($order, 'sortingId');
        //分拣商品信息
        $sortingGoodsTab = M('sorting_goods_relation');
        $sortingGoodsBarcode = M('sorting_goods_barcode');
        $where = [];
        $where['sortingId'] = ['IN', $sortGoodsIds];
        $sortingGoodsList = $sortingGoodsTab->where($where)->select();
        //取值
        $sortingGoodsIds = array_get_column($sortingGoodsList, 'id');
        //条码核对================================================================start================================
        $where = [];
        $where['barcode'] = $params['barcode'];
        $where['isPackType'] = -1;
        //获取全部相同的条码
        $sortBarcodeList = $sortingGoodsBarcode->where($where)->select();
        if (empty($sortBarcodeList)) {
            $apiRet['apiInfo'] = '商品条码信息有误,请核对';
            return $apiRet;
        }

        //获取当前分拣任务下的分拣商品
        $packBarcodeList = [];
        foreach ($sortBarcodeList as $v) {
            if (in_array($v['sortingGoodsId'], $sortingGoodsIds)) {
                $packBarcodeList[] = $v;
            }
        }

        //当条码库中不存在当前当前订单下的任务商品id时
        if (empty($packBarcodeList)) {
            $apiRet['apiInfo'] = '商品条码信息有误,请核对';
            return $apiRet;
        }

        $where = [];
        $where['id'] = $packBarcodeList[0]['sortingGoodsId'];//存在的话取第一个,这里是不确定打包的是哪个条码,同一个任务下的任务商品可能有多个相同的条码
        $sortGoodsInfo = $sortingGoodsTab->where($where)->find();
        $barcodeGoodsInfo = $packBarcodeList[0];//条码信息


        //==================================end============================================================================
        $orderId = $order[0]['orderId'];
        $save = [];
        $save['orderId'] = $orderId;
        $save['goodsId'] = $sortGoodsInfo['goodsId'];
        $save['skuId'] = $sortGoodsInfo['skuId'];

        $packGoodsModel = M('sorting_pack_goods');
        $res = $packGoodsModel->where($save)->find();
        if (!empty($res)) {
            $apiRet['apiInfo'] = '当前商品已打包';
            return $apiRet;
        }

        $save['createTime'] = date('Y-m-d H:i:s');
        $packNotNum = 0;
        $msg = "";
        //称重补差价[-1：否 1：是]
        if ($sortGoodsInfo['SuppPriceDiff'] == -1) {
            $packNotNum = (float)$sortGoodsInfo['basketGoodsNum'] - (float)$sortGoodsInfo['packGoodsNum'];//当前商品未打包数量
            if ($packNotNum <= 0) {
                $apiRet['apiInfo'] = '当前商品已打包';
                return $apiRet;
            }
            $sortingGoodsTab->where(['id' => $sortGoodsInfo['id']])->setInc('packGoodsNum', 1);
            $msg = "打包数量:1件";
        } elseif ($sortGoodsInfo['SuppPriceDiff'] == 1) {
            $where = [];
            $where['sortingGoodsId'] = $sortGoodsInfo['id'];
            $where['isPackType'] = -1;
            //获取当前商品还剩多少没有打包
            $packNotNum = $sortingGoodsBarcode->where($where)->count();

            $sortingGoodsTab->where(['id' => $sortGoodsInfo['id']])->setInc('packGoodsNum', 1);
            $msg = "打包重量:{$barcodeGoodsInfo['weightG']}g";
        }
        //变更条码状态为已打包
        $where = [];
        $where['goodsBarcodeId'] = $barcodeGoodsInfo['goodsBarcodeId'];
        $where['isPackType'] = -1;
        $sortingGoodsBarcode->where($where)->save(['isPackType' => 1]);
        //===========================
        $goodsInfo = M('goods')->where(['goodsId' => $sortGoodsInfo['goodsId'], 'goodsFlag' => 1])->find();
        //----------------------- 记录打包商品任务操作日志 start ------------------------------
        $where = [];
        $where['s.id'] = $sortGoodsInfo['sortingId'];
        $sortModel = M('sorting s');
        $sortData = $sortModel->join("left join wst_sortingpersonnel ws on ws.id = {$params['sortUserId']}")->where($where)->field('s.*,ws.userName')->find();

        $param = [];
        $param['sortingId'] = $sortData['id'];//分拣任务id
        $param['content'] = "[{$sortData['userName']}]:打包任务商品[ {$goodsInfo['goodsName']} ]," . $msg;
        insertSortingActLog($param);
        //----------------------------end-------------------------------------------------------

        //判断是否打包完成
        if ($packNotNum == 1) {
            $packGoodsModel->add($save);
            $param = [];
            $param['sortingId'] = $sortData['id'];//分拣任务id
            $param['content'] = "[{$sortData['userName']}]:完成打包任务商品[ {$goodsInfo['goodsName']} ]";
            insertSortingActLog($param);
            //start更改订单商品表中的分拣状态为分拣完成【(psd)任务状态(0:待分拣|1:分拣中|2:未核货|3:核货差异|4已核货|5已装车)】
            $orderGoodsTab = M('order_goods');
            $orderWhere = [];
            $orderWhere['orderId'] = $orderId;
            $orderWhere['goodsId'] = $sortGoodsInfo['goodsId'];
            $orderWhere['skuId'] = $sortGoodsInfo['skuId'];
            $orderGoodsInfo = $orderGoodsTab->where($orderWhere)->find();
            $orderSave = [];
            $orderSave['actionStatus'] = 2;
            $orderSave['weight'] = $sortGoodsInfo['weightG'];//称重的重量
            $orderGoodsTab->where(['id' => $orderGoodsInfo['id']])->save($orderSave);
            //end==================
        }
        //返回数据:应打包数量|待打包数量|已打包数量
        $sortInfo = $sortingGoodsTab->where(['sortingId' => $sortGoodsInfo['sortingId'], 'id' => $sortGoodsInfo['id']])->find();
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '打包成功';
        $apiRet['apiState'] = 'success';
        $apiRet['basketGoodsNum'] = (float)$sortInfo['basketGoodsNum'];//已入框数量【应打包数量】
        $apiRet['packGoodsNum'] = (float)$sortInfo['packGoodsNum'];//已打包数量
        $apiRet['packGoodsNotNum'] = (float)$sortInfo['basketGoodsNum'] - (float)$sortInfo['packGoodsNum'];//未打包数量
        return $apiRet;
    }

    /**
     * @param $params
     * @param $sortingGoodsIds
     * @return mixed
     * 打包--用于标品校验
     */
    public function productGoods($params, $sortingGoodsIds)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $sortingGoodsTab = M('sorting_goods_relation');
        $barcodeInfo = M('goods')->where(['goodsSn' => $params['barcode'], 'shopId' => $params['shopId'], 'goodsFlag' => 1])->field('goodsId')->find();
        if (empty($barcodeInfo)) {
            $barcodeInfo['skuId'] = 0;
            $skuBarcodeInfo = M('sku_goods_system sy')
                ->join("left join wst_goods g on g.goodsId=sy.goodsId")
                ->where(["sy.skuBarcode" => $params['barcode'], "g.shopId" => $params['shopId'], "g.goodsFlag" => 1])
                ->field('sy.goodsId,sy.skuId')
                ->find();
            if (empty($skuBarcodeInfo)) {
                $apiRet['apiInfo'] = '商品条码信息有误,请核对';
                return $apiRet;
            }
            $barcodeInfo = $skuBarcodeInfo;
        }
        $where = [];
        $where['goodsId'] = $barcodeInfo['goodsId'];
        $where['skuId'] = (int)$barcodeInfo['skuId'];
        $where['status'] = 3;
        $sortBarcodeList = $sortingGoodsTab->where($where)->select();

        //获取当前分拣任务下的分拣商品
        $packBarcodeList = [];
        foreach ($sortBarcodeList as $v) {
            if (in_array($v['id'], $sortingGoodsIds)) {
                $packBarcodeList[] = $v;
            }
        }
        if (empty($packBarcodeList)) {
            $apiRet['apiInfo'] = '商品条码信息有误,请核对';
            return $apiRet;
        }
        $where = [];
        $where['id'] = $packBarcodeList[0]['id'];//存在的话取第一个,这里是不确定打包的是哪个条码,同一个任务下的任务商品可能有多个相同的条码
        $sortGoodsInfo = $sortingGoodsTab->where($where)->find();
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '成功获取商品信息';
        $apiRet['apiState'] = 'success';
        $apiRet['sortGoodsInfo'] = $sortGoodsInfo;
        return $apiRet;
    }

    /**
     * @param $params
     * @return mixed
     * 确认完成打包-----获取未打包商品数量
     */
    public function getPackGoodsNum($params)
    {
        $orderId = $params['orderId'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';

        $where = [];
        $where['s.orderId'] = $orderId;
        $sortModel = M('sorting s');
        $sortData = $sortModel
            ->join("left join wst_sortingpersonnel ws on ws.id = {$params['sortUserId']}")
            ->join("left join wst_sorting_packaging wsp on wsp.orderId = s.orderId")
            ->where($where)
            ->field('s.*,ws.userName,wsp.packType')
            ->select();

        if (!empty($sortData) && (int)$sortData[0]['packType'] == 2) {
            $apiRet['apiInfo'] = "当前任务已打包";
            return $apiRet;
        }

        $packGoodsModel = M('sorting_pack_goods');
        $where = [];
        $where['orderId'] = $orderId;
        $goodsCount = $this->getOrderBasketGoods($orderId, 0);//获取入框商品数量
        $packGoodsCount = $packGoodsModel->where($where)->count();//获取已打包数量
        if ((float)$goodsCount === (float)$packGoodsCount) {
            M('sorting_packaging')->where($where)->save(['packType' => 2, 'updateTime' => date("Y-m-d H:i:s")]);
            foreach ($sortData as $v) {
                //记录分拣任务操作日志 start
                $param = [];
                $param['sortingId'] = $v['id'];//分拣任务id
                $param['content'] = "[{$v['userName']}]:更改分拣任务[ {$v['settlementNo']} ]状态为:打包完成";
                insertSortingActLog($param);
            }
//            editOrderStatus($orderId);//打包完成后触发---改变订单状态为配送中
            $msg = '打包完成';
            //打包完成释放下链口当前的订单量-start
            $shop_id = (int)$params['shopId'];
            $shop_service_module = new ShopsServiceModule();
            $shop_config_result = $shop_service_module->getShopConfig($shop_id);
            $shop_config_data = $shop_config_result['data'];
            if ($shop_config_data['open_suspension_chain'] == 1) {
                $order_service_module = new OrdersServiceModule();
                $order_result = $order_service_module->getOrderInfoById($orderId);
                if ($order_result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $order_data = $order_result['data'];
                    $chain_id = $order_data['chain_id'];
                    $service_module = new ChainServiceModule();
                    $service_module->setDecChainOrderNum($chain_id, 1);
                }
            }
            //打包完成释放下链口当前的订单量-end
        } else {
            $packGoodsNotNum = (float)$goodsCount - (float)$packGoodsCount;
            $apiRet['apiInfo'] = "当前打包订单应打包数量{$goodsCount},已打包数量{$packGoodsCount},未打包数量" . $packGoodsNotNum . "件";
            return $apiRet;
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = $msg;
        $apiRet['apiState'] = 'success';
        return $apiRet;
    }

    /**
     * @param $orderId
     * 判断是否打包完成
     */
    public function verifySortPack($orderId)
    {
        $where = [];
        $where['s.orderId'] = $orderId;
        $sortModel = M('sorting s');
        $sortData = $sortModel->join("left join wst_sortingpersonnel ws on ws.id = s.uid")->where($where)->field('s.*,ws.userName')->find();

        $packGoodsModel = M('sorting_pack_goods');
        $where = [];
        $where['orderId'] = $orderId;
        $goodsCount = $this->getOrderBasketGoods($orderId, 0);//获取入框商品数量
        $packGoodsCount = $packGoodsModel->where($where)->count();
        if ((float)$goodsCount === (float)$packGoodsCount) {
            M('sorting_packaging')->where($where)->save(['packType' => 2]);
            //记录分拣任务操作日志 start
            $param = [];
            $param['sortingId'] = $sortData['id'];//分拣任务id
            $param['content'] = "[{$sortData['userName']}]:更改分拣任务[ {$sortData['settlementNo']} ]状态为:打包完成";
            insertSortingActLog($param);
        }
    }
}