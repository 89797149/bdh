<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 收银订单服务类
 */
class PosOrdersModel extends BaseModel {

    /**
     * 订单分页列表
     */
    public function getPosOrderList($page=1,$pageSize=15){
        $param = I();
        if(empty($param['maxMoney'])){
            $param['maxMoney'] = 0;
        }
        if(empty($param['minMoney'])){
            $param['minMoney'] = 0;
        }
        $field = "po.*,u.name as user_name,u.username as user_useranme,u.phone as user_phone,(po.realpayment - po.cash) as trueRealpayment,s.shopName ";
        //$where = " where po.shopId='".$shopId."' and u.status != -1 and u.shopId='".$shopId."' ";
        $where = " where 1=1 ";
        if(!empty($param['orderNo'])){
            $where .= " and po.orderNO='".$param['orderNo']."' ";
        }
        if(!empty($param['startDate']) && !empty($param['endDate'])){
            $where .= " and po.addtime between '".$param['startDate']."' and '".$param['endDate']."' ";
        }
        if(!empty($param['maxMoney']) && !empty($param['minMoney'])){
            $where .= " and po.realpayment between '".$param['minMoney']."' and '".$param['maxMoney']."' ";
        }
        if(!empty($param['state'])){
            $where .= " and po.state='".$param['state']."' ";
        }
        if(!empty($param['pay'])){
            $where .= " and po.pay='".$param['pay']."' ";
        }
        if(!empty($param['areaId1'])){
            $where .= " and s.areaId1='".$param['areaId1']."' ";
        }
        if(!empty($param['areaId2'])){
            $where .= " and s.areaId2='".$param['areaId2']."' ";
        }
        if(!empty($param['areaId3'])){
            $where .= " and s.areaId3='".$param['areaId3']."' ";
        }
        if(!empty($param['shopName'])){
            $where .= " and s.shopName='".$param['shopName']."' ";
        }
        $sql  = "select $field from __PREFIX__pos_orders po 
        left join __PREFIX__user u on po.userId=u.id 
        left join __PREFIX__shops s on s.shopId=po.shopId ";
        $sql .= $where;
        $sql .= " order by po.id desc ";
        $list = $this->pageQuery($sql,$page,$pageSize);
        //获取涉及的订单及商品
        $orderGoodsTab = M('pos_orders_goods pg');
        if(count($list['root'])>0){
            foreach ($list['root'] as $key=>&$val){
                $val['payName'] = $this->getPayName($val['pay']);
                $val['stateName'] = $this->getStateName($val['state']);
                $val['goodslist'] = $orderGoodsTab
                    ->join("left join wst_goods g on g.goodsId=pg.goodsId")
                    ->where(['orderid'=>$val['id']])
                    ->field("pg.*,g.goodsThums")
                    ->select();
            }
        }
        unset($val);
        return $list;
    }

    public  function getPayName($pay){
        switch($pay)
        {
            case 1:
                $str = "现金支付";
                break;
            case 2:
                $str = "余额支付";
                break;
            case 3:
                $str = "银联支付";
                break;
            case 4:
                $str = "微信支付";
                break;
            case 5:
                $str = "支付宝支付";
                break;
            case 6:
                $str = "组合支付";
                break;
            default:
                $str = "";
                break;
        }
        return $str;
    }

    public  function getStateName($state){
        switch($state)
        {
            case 1:
                $str = "待结算";
                break;
            case 2:
                $str = "已取消";
                break;
            case 3:
                $str = "已结算";
                break;
            default:
                $str = "";
                break;
        }
        return $str;
    }

	/**
	 * 获取订单详细信息
	 */
	 public function getPosOrderDetail(){
	     $id = (int)I('id');
         $field = "po.*,u.name as user_name,u.username as user_useranme,u.phone as user_phone,(po.realpayment - po.cash) as trueRealpayment ";
         $where = " where po.id='".$id."' ";
         $sql  = "select $field from __PREFIX__pos_orders po 
        left join __PREFIX__user u on po.userId=u.id ";
         $sql .= $where;
         $res = $this->queryRow($sql);
         $res['payName'] = $this->getPayName($res['pay']);
         $res['stateName'] = $this->getStateName($res['state']);
         $orderGoodsTab = M('pos_orders_goods pg');
         $res['goodslist'] = $orderGoodsTab
             ->join("left join wst_goods g on g.goodsId=pg.goodsId")
             ->where(['orderid'=>$res['id']])
             ->field("pg.*,g.goodsThums")
             ->select();
         $openPresaleCash = M("sys_configs")->where("fieldCode='openPresaleCash'")->getField('fieldValue'); //是否开启预存款
         if($openPresaleCash == 1){
             $res['trueRealpayment'] = $res['realpayment'];
         }
         return $res;
	 }

    /**
     * 获取结算信息
     */
    public function toSettlement(){
        $id = (int)I('id');
        $info = M("pos_order_settlements")->where("settlementId='".$id."'")->find();
        return $info;
    }

    /**
     * 订单结算分页列表
     */
    public function getPosOrderSettlementList($page=1,$pageSize=15){
        $param = I();
        if(empty($param['maxMoney'])){
            $param['maxMoney'] = 0;
        }
        if(empty($param['minMoney'])){
            $param['minMoney'] = 0;
        }

        $where = " where 1=1 ";
        if(!empty($param['settlementNo'])){
            $where .= " and os.settlementNo='".$param['settlementNo']."' ";
        }
        if(!empty($param['startDate']) && !empty($param['endDate'])){
            $where .= " and os.createTime between '".$param['startDate']."' and '".$param['endDate']."' ";
        }
        if(!empty($param['maxMoney']) && !empty($param['minMoney'])){
            $where .= " and os.settlementMoney between '".$param['minMoney']."' and '".$param['maxMoney']."' ";
        }
        if($param['isFinish'] != ''){
            $where .= " and os.isFinish='".$param['isFinish']."' ";
        }
        if(!empty($param['shopName'])){
            $where .= " and s.shopName like '%".$param['shopName']."%' ";
        }
        $sql = "select os.* ,s.shopName from __PREFIX__pos_order_settlements os left join __PREFIX__shops s on s.shopId=os.shopId ";
        $sql .= $where;
        $sql .= " order by os.settlementId desc ";
        $res = $this->pageQuery($sql,$page,$pageSize);
        if(count($res['root'])>0){
            foreach ($res['root'] as $key=>&$val){
                if(is_null($val['finishTime'])){
                    $val['finishTime'] = '';
                }
                if(is_null($val['remarks'])){
                    $val['remarks'] = '';
                }
                if($val['isFinish'] == 1){
                    $val['isFinishName'] = '未结算';
                }else{
                    $val['isFinishName'] = '已结算';
                }
            }
            unset($val);
        }
        unset($val);
        return $res;
    }


    /**
     * 结算
     */
    public function settlement(){
        $id = (int)I('id');
        $rd = array('code'=>-1,'msg'=>'结算失败','data'=>array());
        $rs = $this->toSettlement();
        $data = [];
        $data['isFinish'] = 2;
        $data['finishTime'] = date("Y-m-d H:i:s",time());
        if($rs['settlementId']!=''){
            if(I('autoReturn') == 1){
                //自动退款
                $m = D('Adminapi/Orders');
                $payData['amount'] = $rs['settlementMoney'];
                $payData['orderNo'] = $rs['settlementNo'];
                $shopInfo = M('shops')->where("shopId='".$rs['shopId']."'")->find();
                $userInfo = M('users')->where("userId='".$shopInfo['userId']."'")->find();
                if(empty($userInfo['openId'])){
                    $rd['msg'] = '该用户缺少openid';
                    return $rd;
                }
                $payData['openid'] = $userInfo['openId'];
                $result = $m->wxPayTransfers($payData);
                M()->startTrans();//开启事物 注意数据表是否开启Innodb
                $rss = M("pos_order_settlements")->where("settlementId=".$id)->save($data);
                if($result['result_code'] == 'SUCCESS' && $rss !== false){
                    M()->commit();
                    $rd['code'] = 0;
                    $rd['msg'] = '结算成功';
                }else{
                    M()->rollback();
                }
                return $rd;
            }
            $rss = M("pos_order_settlements")->where("settlementId=".$id)->save($data);
            if($rss){
                $rd['code'] = 0;
                $rd['msg'] = '结算成功';
            }

        }
        return $rd;
    }

    /**
     * 获取结算详情
     */
    public function settlementInfo(){
        $id = (int)I('id');
        $rs = M("pos_order_settlements os")
            ->join("left join wst_shops s on s.shopId=os.shopId")
            ->where(["settlementId"=>$id])
            ->field("os.*,s.shopName")
            ->find();
        //获取订单列表
        $rs['List'] = M("pos_orders po")
            ->join("left join wst_user u on po.userId=u.id")
            ->where(["settlementId"=>$id])
            ->field("po.*,u.username,u.phone")
            ->select();
        $settlementsTab = M("pos_order_settlements");
        foreach ($rs['List'] as $key=>&$val){
            $settlementsInfo = $settlementsTab->where("settlementId='".$val['settlementId']."'")->field("settlementId,poundageRate")->find();
            $val['poundageRate'] = $settlementsInfo['poundageRate'];
            $val['poundageMoney'] = WSTBCMoney($val["realpayment"] * $settlementsInfo['poundageRate'] / 100,0,2);
        }
        unset($val);
        return $rs;
    }
}
?>