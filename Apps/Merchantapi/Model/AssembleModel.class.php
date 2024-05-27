<?php
namespace Merchantapi\Model;
use Symfony\Component\DependencyInjection\Tests\DefinitionDecoratorTest;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 拼团活动类
 */
class AssembleModel extends BaseModel {

    /**
     * 获取拼团活动列表 (废弃)
     */
//    public function getAssembleList($data){
//        $cur_time = date('Y-m-d H:i:s');
//        $sql = "select aa.*,g.goodsName from __PREFIX__assemble_activity as aa left join __PREFIX__goods as g on aa.goodsId = g.goodsId where aa.shopId = " . $data['shopId'];
//        if (!empty($data['title'])) $sql .= " and aa.title like '%" . $data['title'] . "%'";
//        if (!empty($data['goodsName'])) {
//            $sql .= " and g.goodsName like '%" . $data['goodsName'] . "%' ";
//        }
//        if ($data['state'] > 0) {
//            if (in_array($data['state'],array(1))) {//未开始
//                $sql .= " and startTime > '" . $cur_time . "' ";
//            }
//            if (in_array($data['state'],array(2))) {//进行中
//                $sql .= " and startTime <= '" . $cur_time . "' and endTime >= '" . $cur_time . "' ";
//            }
//            if (in_array($data['state'],array(3))) {//已结束
//                $sql .= " and endTime < '" . $cur_time . "' ";
//            }
//        }
//        if (!empty($data['start_time'])) {
//            $sql .= " and aa.createTime >= '".$data['start_time']."' ";
//        }
//        if (!empty($data['end_time'])) {
//            $sql .= " and aa.createTime <= '".$data['end_time']."' ";
//        }
//
//        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
//    }

    /**
     * 获取拼团活动列表
     * @param $params<p>
     * int shopId 店铺id
     * varchar title 活动标题
     * varchar goodsName 商品名称
     * dateTime startDate 创建时间->开始时间
     * dateTime endDate 创建时间->结束时间
     * int state 状态【1：未开始|2：进行中|3：已结束】
     * int activityStatus 活动状态【-1：关闭|1：开启】
     * int page 页码
     * int pageSize 分页条数
     * </p>
     */
    public function getAssembleActivityList(array $params){
        $where = "activity.aFlag=1 and activity.shopId={$params['shopId']} ";
        $params['currentTime'] = date('Y-m-d H:i:s');
        $whereFind = [];
        $whereFind['activity.title'] = function ()use($params){
            if(empty($params['title'])){
                return null;
            }
            return ['like',"%{$params['title']}%",'and'];
        };
        $whereFind['goods.goodsName'] = function ()use($params){
            if(empty($params['goodsName'])){
                return null;
            }
            return ['like',"%{$params['goodsName']}%",'and'];
        };
        $whereFind['activity.createTime'] = function ()use($params){
            if(empty($params['startDate']) || empty($params['endDate'])){
                return null;
            }
            return ['between',"{$params['startDate']}' and '{$params['endDate']}",'and'];
        };
        $whereFind['activity.startTime'] = function ()use($params){
            if(empty($params['state'])){
                return null;
            }
            if($params['state'] == 1){
                return ['>',"{$params['currentTime']}",'and'];
            }
            if($params['state'] == 2){
                return ['<=',"{$params['currentTime']}' and activity.endTime>='{$params['currentTime']}",'and'];
            }
        };
        $whereFind['activity.endTime'] = function ()use($params){
            if(empty($params['state'])){
                return null;
            }
            if($params['state'] == 3){
                return ['<',"{$params['currentTime']}",'and'];
            }
        };
        $whereFind['activity.activityStatus'] = function ()use($params){
            if(empty($params['activityStatus'])){
                return null;
            }
            return ['=',"{$params['activityStatus']}",'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind,' and');
        if(empty($whereFind) || $whereFind == ' '){
            $whereInfo = "{$where}";
        }else{
            $whereFind = rtrim($whereFind,' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select activity.*,goods.goodsName,goods.goodsImg from __PREFIX__assemble_activity activity left join __PREFIX__goods as goods on activity.goodsId = goods.goodsId where {$whereInfo} order by activity.createTime desc ";
        $data = $this->pageQuery($sql, $params['page'], $params['pageSize']);
        if(!empty($data['root'])){
            $list = $data['root'];
            $goodsSystemTab = M('sku_goods_system');
            foreach ($list as $key=>$value){
                $list[$key]['state'] = '';
                if($value['startTime'] > $params['currentTime']){
                    //未开始
                    $list[$key]['state'] = 1;
                }
                if(($value['startTime'] <= $params['currentTime']) && ($value['endTime'] >= $params['currentTime'])){
                    //进行中
                    $list[$key]['state'] = 2;
                }
                if(($value['endTime'] < $params['currentTime'])){
                    //进行中
                    $list[$key]['state'] = 3;
                }
                $list[$key]['hasSku'] = -1;//商品本身是否支持sku【-1：不支持|1：支持】 PS:该字段和拼团商品是否设置了商品SKU无关
                $where = [];
                $where['goodsId'] = $value['goodsId'];
                $where['dataFlag'] = 1;
                $systemGoodsCount = $goodsSystemTab->where($where)->count();
                if($systemGoodsCount > 0 ){
                    $list[$key]['hasSku'] = 1;
                }
            }
            $data['root'] = (array)$list;
        }
        return returnData($data);
    }

    /**
     * 添加拼团活动
     */
    public function insertAssemble($data = array()){
        return M('assemble_activity')->add($data);
    }

    /**
     * 添加拼团活动
     * @param array $params<p>
     * varchar title 活动标题
     * int groupPeopleNum 成团人数
     * int limitNum 限购数量
     * int goodsId 商品id
     * float tprice 拼团价格
     * dateTime startTime 活动开始时间
     * dateTime endTime 活动结束时间
     * varchar describle 活动说明
     * int limitHour 成团限期 PS:单位(小时)
     * </p>
     * @param array $shopInfo
     */
    public function addAssembleActivity($params,$shopInfo){
        $saveData = [];
        $saveData['title'] = null;
        $saveData['groupPeopleNum'] = null;
        $saveData['limitNum'] = null;
        $saveData['goodsId'] = null;
        $saveData['tprice'] = null;
        $saveData['startTime'] = null;
        $saveData['endTime'] = null;
        $saveData['describle'] = null;
        $saveData['limitHour'] = null;
        parm_filter($saveData,$params);
        $saveData['shopId'] = $shopInfo['shopId'];
        $saveData['createTime'] = date('Y-m-d H:i:s',time());
        $res = M('assemble_activity')->add($saveData);
        if(!$res){
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * 编辑拼团活动
     * @param array $params<p>
     * varchar aid 活动id
     * varchar title 活动标题
     * int groupPeopleNum 成团人数
     * int limitNum 限购数量
     * float tprice 拼团价格
     * dateTime startTime 活动开始时间
     * dateTime endTime 活动结束时间
     * varchar describle 活动说明
     * int limitHour 成团限期 PS:单位(小时)
     * int activityStatus 活动状态【-1:关闭|1:开启】
     * jsonString activityGoodsSku 拼团商品信息
     * </p>
     * @param array $shopInfo
     */
    public function updateAssembleActivity($params,$shopInfo){
        $saveData = [];
        $saveData['title'] = null;
        $saveData['groupPeopleNum'] = null;
        $saveData['limitNum'] = null;
        $saveData['goodsId'] = null;
        $saveData['tprice'] = null;
        $saveData['startTime'] = null;
        $saveData['endTime'] = null;
        $saveData['describle'] = null;
        $saveData['limitHour'] = null;
        $saveData['activityStatus'] = null;
        parm_filter($saveData,$params);
        M()->startTrans();
        $activityTab = M('assemble_activity');
        $where = [];
        $where['aid'] = $params['aid'];
        $activityInfo = $activityTab->where($where)->find();
        if(empty($activityInfo)){
            return returnData(false, -1, 'error', '操作失败，活动id校验失败');
        }
        $res = $activityTab->where($where)->save($saveData);
        if($res === false){
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }
        $skuSystemTab = M('sku_goods_system');
        $activityGoodsTab = M('assemble_activity_goods_sku');
        $goodsId = $params['goodsId'];
        if(!empty($goodsId)){
            $activityGoodsSku = json_decode(htmlspecialchars_decode($params['activityGoodsSku']),true);
            $sku = $activityGoodsSku;
            if(!empty($sku)){
                //有规格
                $where = [];
                $where['aid'] = $activityInfo['aid'];
                $activityGoodsTab->where($where)->delete();
                $insertArr = [];
                foreach ($sku as $key=>$value){
                    $activityGoodsInsert = [];
                    $activityGoodsInsert['aid'] = $activityInfo['aid'];
                    $activityGoodsInsert['goodsId'] = $goodsId;
                    $activityGoodsInsert['skuId'] = $value['skuId'];
                    $activityGoodsInsert['tprice'] = $value['tprice'];
                    $insertArr[] = $activityGoodsInsert;
                }
                $insertGoodsRes = $activityGoodsTab->addAll($insertArr);
                if(!$insertGoodsRes){
                    M()->rollback();
                    return returnData(false, -1, 'error', '操作失败');
                }
            }
            //如果商品本身无规格清除之前增加的规格信息
            $where = [];
            $where['goodsId'] = $goodsId;
            $where['dataFlag'] = 1;
            $systemGoodsCount = $skuSystemTab->where($where)->count();
            if((int)$systemGoodsCount <= 0){
                $where = [];
                $where['aid'] = $activityInfo['aid'];
                $activityGoodsTab->where($where)->delete();
            }
        }
        if($saveData['activityStatus'] == 1){
            //如果开启拼团活动,需要验证拼团商品信息是否正确
            $where = [];
            $where['goodsId'] = $activityInfo['goodsId'];
            $where['dataFlag'] = 1;
            $goodsSystemSkuCount = $skuSystemTab->where($where)->count();
            $where = [];
            $where['aid'] = $activityInfo['aid'];
            $where['goodsId'] = $activityInfo['goodsId'];
            $activityGoodsSkuCount = $activityGoodsTab->where($where)->count();
            if($goodsSystemSkuCount > 0 && $activityGoodsSkuCount <=0){
                M()->rollback();
                return returnData(false, -1, 'error', '请为该商品设置需要参与拼团的规格');
            }
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 编辑拼团活动 (废弃)
     */
    public function updateAssemble($aid, $data = array()){
        return M('assemble_activity')->where('aid = ' . $aid)->save($data);
    }

    /**
     * 获取活动详情 (废弃)
     */
    public function assembleDetail($aid){
        $list = M('assemble_activity as aa')->join('wst_goods as g on aa.goodsId = g.goodsId')->where('aa.aid = ' . $aid . ' and g.isSale = 1')->field('aa.*,g.*')->find();
        return $list;
    }

    /**
     * 获取活动详情
     */
    public function getAssembleActivityDetail($aid){
        $activityTab = M('assemble_activity activity');
        $where = [];
        $where['activity.aid'] = $aid;
        $where['activity.aFlag'] = 1;
        $where['goods.goodsFlag'] = 1;
        $field = "activity.*,";
        $field .= "goods.goodsName,goods.goodsImg";
        $activityInfo = $activityTab->join('left join wst_goods goods on activity.goodsId = goods.goodsId')->where($where)->field($field)->find();
        if(empty($activityInfo)){
            return returnData([]);
        }
        $goodsId = $activityInfo['goodsId'];
        $activityInfo['hasSku'] = -1;//商品本身是否支持sku【-1：不支持|1：支持】 PS:该字段和拼团商品是否设置了商品SKU无关
        $activityInfo['goodsSku'] = [];
        $where = [];
        $where['goodsId'] = $goodsId;
        $where['dataFlag'] = 1;
        $goodsSystemTab = M('sku_goods_system');
        $goodsSelfTab = M('sku_goods_self self');
        $systemGoodsCount = $goodsSystemTab->where($where)->count();
        if($systemGoodsCount > 0 ){
            $activityInfo['hasSku'] = 1;
        }
        if($activityInfo['hasSku'] >= 1){
            $goodsSkuModel = D('Home/GoodsSku');
            $goodsSkuData = $goodsSkuModel->getGoodsSku(['goodsId'=>$goodsId]);
            $goodsSku = $goodsSkuData['data'];
            $activityInfo['goodsSku'] = $goodsSku;
        }
        $activityInfo['activityGoodsSku'] = [];//参与拼团的sku信息
        $activityGoodsTab = M('assemble_activity_goods_sku');
        $where = [];
        $where['aid'] = $activityInfo['aid'];
        $activityGoodsSku = $activityGoodsTab->where($where)->field('skuId,tprice')->select();
        if(!empty($activityGoodsSku)){
            foreach ($activityGoodsSku as $key=>$value){
                $where = [];
                $where['skuId'] = $value['skuId'];
                $where['dataFlag'] = 1;
                $goodsSystemInfo = $goodsSystemTab->where($where)->find();
                if(empty($goodsSystemInfo)){
                    unset($activityGoodsSku[$key]);
                    continue;
                }
                $activityGoodsSku[$key]['systemSpec'] = $goodsSystemInfo;
                $selfSpec = $goodsSelfTab
                    ->join("left join wst_sku_spec sp on self.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=self.attrId")
                    ->where(['self.skuId'=>$value['skuId'],'self.dataFlag'=>1,'sp.dataFlag'=>1,'sr.dataFlag'=>1])
                    ->field('self.id,self.skuId,self.specId,self.attrId,sp.specName,sr.attrName')
                    ->order('sp.sort asc')
                    ->select();
                if(empty($selfSpec)){
                    unset($activityGoodsSku[$key]);
                    continue;
                }
                $activityGoodsSku[$key]['selfSpec'] = $selfSpec;
                $activityGoodsSku[$key]['specAttrNameStr'] = '';
                foreach ($selfSpec as $selfKey=>$selfVal){
                    $activityGoodsSku[$key]['specAttrNameStr'] .= $selfVal['attrName'].'，';
                }
                $activityGoodsSku[$key]['specAttrNameStr'] = rtrim($activityGoodsSku[$key]['specAttrNameStr'],'，');
            }
            if(!empty($activityGoodsSku)){
                $activityGoodsSku = array_values($activityGoodsSku);
                $activityInfo['activityGoodsSku'] = $activityGoodsSku;
            }
        }
        return returnData($activityInfo);
    }

    /**
     * 删除拼团活动
     */
    public function deleteAssemble($aid, $shopId){
        $where = 'aid = ' . $aid . ' and shopId = ' . $shopId;
        $result = M('assemble_activity')->where($where)->delete();
        if ($result){
            M('user_activity_relation')->where($where)->delete();
            M('assemble')->where($where)->delete();
        }
        return $result;
    }

    /**
     * 删除拼团活动
     * @param int $aid 活动id
     * @param array $shopInfo
     */
    public function delAssembleActivity($aid, $shopInfo){
        $shopId = $shopInfo['shopId'];
        $where['aid'] = $aid;
        $where['shopId'] = $shopId;
        $saveData = [];
        $saveData['aFlag'] = -1;
        $delRes = M('assemble_activity')->where($where)->save($saveData);
        if(!$delRes){
            return returnData(false, -1, 'error', '操作失败');
        }
        M('user_activity_relation')->where($where)->delete();
        $saveData = [];
        $saveData['pFlag'] = -1;
        M('assemble')->where($where)->save($saveData);
        return returnData($delRes);
    }

    /**
     * 获取拼团用户
     * $param int $aid 活动id
     */
    public function getAssembleUser(int $pid){
        $where = [];
        $where['relation.pid'] = $pid;
        $field = 'relation.createTime,';
        $field .= 'user.userPhoto,user.userName,';
        $field .= 'ogoods.goodsName,ogoods.goodsThums,ogoods.skuId,';
        $field .= 'orders.orderNo,orders.realTotalMoney,orders.totalMoney,orders.isPay,orders.deliverMoney,orders.setDeliveryMoney';
        $list = M('user_activity_relation relation')
            ->join('left join wst_users user on relation.uid = user.userId')
            ->join("left join wst_order_goods ogoods on relation.orderId = ogoods.orderId")
            ->join('left join wst_orders orders on orders.orderId = relation.orderId')
            ->where($where)
            ->field($field)
            ->select();
        //后加,兼容统一运费 start
        if(!empty($list)){
            $config = $GLOBALS["CONFIG"];
            $goodsSkuModel = D('Home/GoodsSku');
            foreach ($list as $key=>$value){
                $list[$key]['specAttrNameStr'] = '';
                $skuId = $value['skuId'];
                if($skuId > 0 ){
                    $skuDetail = $goodsSkuModel->getSkuDetailSkuId($skuId);
                    $list[$key]['specAttrNameStr'] = $skuDetail['specAttrNameStr'];
                }
                //后加,兼容统一运费 end
                if($config['setDeliveryMoney'] == 2){
                    if($value['isPay'] == 1){
                        //$list[$key]['realTotalMoney'] = $value['realTotalMoney']+$value['deliverMoney'];
                        $list[$key]['realTotalMoney'] = $value['realTotalMoney'];
                    }else{
                        if($value['realTotalMoney'] < $config['deliveryFreeMoney']){
                            $list[$key]['realTotalMoney'] = $value['realTotalMoney']+$value['setDeliveryMoney'];
                            $list[$key]['deliverMoney'] = $value['setDeliveryMoney'];
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 拼团订单列表（正在进行中和拼团失败）
     */
//    public function getAssembleOrderList($data){
//        $sql = "select o.*,u.userName as buyUserName,u.userPhone as buyUserPhone from __PREFIX__orders as o left join __PREFIX__user_activity_relation as uar on o.orderId = uar.orderId left join __PREFIX__assemble as a on a.pid = uar.pid left join __PREFIX__users as u on u.userId = o.userId where o.orderStatus = 15 and (a.state = -1 or (a.startTime <= '" . $data['curTime'] . "' and a.endTime >= '" . $data['curTime'] . "' and a.state = 0)) and a.shopId = " . $data['shopId'];
//        if (!empty($data['orderNo'])) $sql .= " and o.orderNo = '" . $data['orderNo'] . "' ";
//        if (!empty($data['userName'])) $sql .= " and o.userName like '%" . $data['userName'] . "%' ";
//        if (!empty($data['userPhone'])) $sql .= " and o.userPhone like '%" . $data['userPhone'] . "%' ";
//        $list = $this->pageQuery($sql, $data['page'], $data['pageSize']);
//        if(!empty($list['root'])){
//            $config = $GLOBALS['CONFIG'];
//            if($config['setDeliveryMoney'] == 2){
//                $orderList = $list['root'];
//                foreach ($orderList as $key=>$value){
//                    if($value['isPay'] == 1){
//                        $orderList[$key]['realTotalMoney'] = $value['realTotalMoney']+$value['deliverMoney'];
//                    }else{
//                        $orderList[$key]['realTotalMoney'] = $value['realTotalMoney']+$value['setDeliveryMoney'];
//                    }
//                }
//                $list['root'] = $orderList;
//            }
//        }
//        return $list;
//    }

    /**
     * 店铺拼团商品
     */
    public function getShopAssembleGoods($data){
        $sql = "select aa.*,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.goodsStock from __PREFIX__goods as g left join __PREFIX__assemble_activity as aa on aa.goodsId = g.goodsId where aa.endTime >= '" . $data['curTime'] . "' and aa.state = 0 and g.isSale = 1";
        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * （商家管理端）通过关键字获得商品列表 (废弃)
     */
    public function getShopGoodsListByKeywords($data){
        return M('goods')
            ->where("(goodsName like '%" . $data['keywords'] . "%' or goodsSn like '%" . $data['keywords'] . "%') and isSale = 1 and goodsFlag = 1 and shopId = " . $data['shopId'])
            ->select();
    }

    /**
     * 店铺商品搜索
     * @param varchar keywords 商品关键字
     * @param array $shopInfo
     */
    public function searchShopGoods(string $keywords,array $shopInfo){
        $shopId = $shopInfo['shopId'];
        $goodsTab = M('goods');
        $where = "(goodsName like '%{$keywords}%' or goodsSn like '%{$keywords}%') and isSale = 1 and goodsFlag = 1 and shopId = {$shopId} ";
        $field = 'goodsId,goodsName,goodsStock,shopPrice,goodsImg,goodsThums';
        $data = $goodsTab->where($where)->field($field)->select();
        return returnData((array)$data);
    }

    /**
     * 获取拼团列表 (废弃)
     */
    public function getAssembleListData($data){
        $cur_time = date('Y-m-d H:i:s');
        $sql = "select a.*,g.goodsName,u.loginName,u.userName from __PREFIX__assemble as a left join __PREFIX__goods as g on a.goodsId = g.goodsId left join __PREFIX__users as u on a.userId = u.userId where a.shopId = " . $data['shopId'];
        if (!empty($data['title'])) $sql .= " and a.title like '%" . $data['title'] . "%'";
        if (!empty($data['goodsName'])) {
            $sql .= " and g.goodsName like '%" . $data['goodsName'] . "%' ";
        }
        if ($data['state'] > -2) {
            $sql .= " and state = ".$data['state'];
        }
        if (!empty($data['start_time'])) {
            $sql .= " and a.createTime >= '".$data['start_time']."' ";
        }
        if (!empty($data['end_time'])) {
            $sql .= " and a.createTime <= '".$data['end_time']."' ";
        }

        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * 获取拼团列表
     * @param array $params <p>
     * varchar title 活动标题
     * varchar goodsName 商品名称
     * int state 状态【-1:拼团失败,0:拼团中,1:拼团成功】
     * varchar userName 团长名称
     * dateTime startDate 开始时间
     * dateTime endDate 结束时间
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * @param array $shopInfo
     */
    public function getAssembleList(array $params,array $shopInfo){
        $shopId = $shopInfo['shopId'];
        $where = " assemble.pFlag=1 and assemble.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['assemble.title'] = function ()use($params){
            if(empty($params['title'])){
                return null;
            }
            return ['like',"%{$params['title']}%",'and'];
        };
        $whereFind['goods.goodsName'] = function ()use($params){
            if(empty($params['goodsName'])){
                return null;
            }
            return ['like',"%{$params['goodsName']}%",'and'];
        };
        $whereFind['assemble.state'] = function ()use($params){
            if(!is_numeric($params['state'])){
                return null;
            }
            return ['=',"{$params['state']}",'and'];
        };
        $whereFind['user.userName'] = function ()use($params){
            if(empty($params['userName'])){
                return null;
            }
            return ['like',"%{$params['userName']}%",'and'];
        };
        $whereFind['assemble.createTime'] = function ()use($params){
            if(empty($params['startDate']) || empty($params['endDate'])){
                return null;
            }
            return ['between',"{$params['startDate']}' and '{$params['endDate']}",'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind,' and');
        if(empty($whereFind) || $whereFind == ' '){
            $whereInfo = "{$where}";
        }else{
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select assemble.*,goods.goodsName,goods.goodsImg,user.userName from __PREFIX__assemble as assemble left join __PREFIX__goods as goods on assemble.goodsId = goods.goodsId left join __PREFIX__users `user` on assemble.userId = user.userId where {$whereInfo} order by assemble.createTime desc ";
        $data = $this->pageQuery($sql, $params['page'], $params['pageSize']);
        if(!empty($data['root'])){
            $list = $data['root'];
            $goodsSkuModel = D('Home/GoodsSku');
            foreach ($list as $key=>$value){
                $list[$key]['specAttrNameStr'] = '';
                $skuId = $value['skuId'];
                if(empty($skuId)){
                    continue;
                }
                $skuDetail = $goodsSkuModel->getSkuDetailSkuId($skuId);
                $list[$key]['specAttrNameStr'] = $skuDetail['specAttrNameStr'];
            }
            $data['root'] = $list;
        }
        return returnData($data);
    }

    /**
     * 拼团详情
     * @param int $pid 拼团id
     */
    public function getAssembleDetail($pid){
        $assembleTab = M('assemble assemble');
        $where = [];
        $where['assemble.pid'] = $pid;
        $where['assemble.pFlag'] = 1;
        $where['goods.goodsFlag'] = 1;
        $field = "assemble.*,";
        $field .= "goods.goodsName,goods.goodsImg";
        $info = $assembleTab
            ->join('left join wst_goods goods on assemble.goodsId = goods.goodsId')
            ->where($where)
            ->field($field)
            ->find();
        if(empty($info)){
            return returnData([]);
        }
        $info['specAttrNameStr'] = '';//规格
        $skuId = $info['skuId'];
        if($skuId > 0 ){
            $goodsSkuModel = D('Home/GoodsSku');
            $skuDetail = $goodsSkuModel->getSkuDetailSkuId($skuId);
            if(!empty($skuDetail)){
                $info['specAttrNameStr'] = $skuDetail['specAttrNameStr'];
            }
        }
        //拼团用户信息
        $info['assembleUser'] = [];
        $assembleUser = $this->getAssembleUser($info['pid']);
        if(!empty($assembleUser)){
            $info['assembleUser'] = $assembleUser;
        }
        return returnData($info);
    }

    /**
     * 拼团订单列表
     * @param array $params <p>
     * varchar orderNo 订单号
     * varchar userName 收货人姓名
     * varchar userPhone 收货人手机号
     * varchar assembleUserName 团长名称
     * varchar assembleUserPhone 团长手机号
     * int page 页码
     * int pageSize 分页条数,默认15条
     * </p>
     * @param array $shopInfo
     * */
    public function getAssembleOrderList(array $params,array $shopInfo){
        $shopId = $shopInfo['shopId'];
        $currentTime = date('Y-m-d H:i:s');
        $where = " orders.orderFlag=1 and orders.orderType=2 ";
        $where .= " and (assemble.state=-1 or (assemble.startTime <= '{$currentTime}' and assemble.endTime >= '{$currentTime}' and assemble.state=0 )) and assemble.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['orders.orderNo'] = function ()use($params){
            if(empty($params['orderNo'])){
                return null;
            }
            return ['like',"%{$params['orderNo']}%",'and'];
        };
        $whereFind['users.userName'] = function ()use($params){
            if(empty($params['userName'])){
                return null;
            }
            return ['like',"%{$params['userName']}%",'and'];
        };
        $whereFind['users.userPhone'] = function ()use($params){
            if(empty($params['userPhone'])){
                return null;
            }
            return ['like',"%{$params['userPhone']}%",'and'];
        };
        $whereFind['assemble.assembleUserName'] = function ()use($params){
            if(empty($params['assembleUserName'])){
                return null;
            }
            return ['like',"%{$params['assembleUserName']}%",'and'];
        };
        $whereFind['assemble.assembleUserPhone'] = function ()use($params){
            if(empty($params['assembleUserPhone'])){
                return null;
            }
            return ['like',"%{$params['assembleUserPhone']}%",'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind,'and ');
        if(empty($whereFind) || $whereFind == ' '){
            $whereInfo = $where;
        }else{
            $whereInfo = $where.' and '.$whereFind;
        }
        $field = "orders.orderId,orders.orderNo,orders.shopId,orders.orderStatus,orders.totalMoney,orders.deliverMoney,orders.payType,orders.payFrom,orders.isSelf,orders.isPay,orders.deliverType,orders.userId,orders.userAddress,orders.orderScore,orders.orderRemarks,orders.requireTime,orders.createTime,orders.orderFlag,orders.needPay,orders.realTotalMoney,orders.useScore,orders.scoreMoney,orders.setDeliveryMoney,orders.userName as buyUserName,orders.userPhone as buyUserPhone";
        $field .= ',users.userName,users.userPhone';
        $field .= ',assemble.userId as assembleUserId,assemble.assembleUserName,assemble.assembleUserPhone';
        $sql = "select $field from __PREFIX__orders orders ";
        $sql .= " left join __PREFIX__user_activity_relation relation on orders.orderId = relation.orderId";
        $sql .= " left join __PREFIX__assemble assemble on assemble.pid = relation.pid ";
        $sql .= " left join __PREFIX__users users on users.userId = orders.userId ";
        $sql .= " where $whereInfo order by orders.orderId desc ";
        $data = $this->pageQuery($sql,$params['page'],$params['pageSize']);
        if(!empty($data['root'])){
            $list = $data['root'];
            $config = $GLOBALS['CONFIG'];
            foreach ($list as $key=>$value){
                if($config['setDeliveryMoney'] == 2){
                    if($value['isPay'] == 1){
                        //$orderList[$key]['realTotalMoney'] = $value['realTotalMoney']+$value['deliverMoney'];
                        $orderList[$key]['realTotalMoney'] = $value['realTotalMoney'];
                    }else{
                        $orderList[$key]['realTotalMoney'] = $value['realTotalMoney']+$value['setDeliveryMoney'];
                    }
                }
            }
            $data['root'] = $list;
        }
        return returnData($data);
    }

}