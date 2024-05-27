<?php
namespace V3\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 智能箱类
 */
class BoxModel extends BaseModel {

    /**
     * 获得箱子订单列表
     * @param $param
     */
    public function getBoxOrderList($param){
        $where = " bo.userId = " . $param['userId'] . " ";
        $sql = "select bo.*,ua.userName,ua.userPhone,ua.userTel,ua.address,b.name,b.deposit,b.boxNo from __PREFIX__box_order as bo inner join __PREFIX__user_address as ua on bo.addressId = ua.addressId inner join __PREFIX__box as b on bo.boxId = b.boxId where " . $where . " order by bo.createTime desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 申请箱子
     * @param $userId
     * @param $shopId
     */
    public function applyBox($userId,$shopId,$addressId){
        $where = array();
        $where['shopId'] = $shopId;
        $where['state'] = 0;
        $box = M('box')->where($where)->order('createTime desc')->find();
        $result = false;
        if (!empty($box)) {
            M('box')->where(array('boxId'=>$box['boxId']))->save(array('state'=>1));
            M('box_order')->add(array(
                'boxId'         =>  $box['boxId'],
                'userId'        =>  $userId,
                'shopId'        =>  $shopId,
                'addressId'     =>  $addressId,
                'createTime'    =>  date('Y-m-d H:i:s'),
                'state'         =>  0
            ));
            $result = true;
        }
        return $result;
    }

    /**
     * 确认订单
     * @param $userId
     * @param $orderId
     */
    public function confirmOrder($userId,$orderId){
        $m = M('box_order');
        $where = array('orderId'=>$orderId,'state'=>1);
        $boxOrder = $m->where($where)->find();
        $result = false;
        if (!empty($boxOrder)) {
            $m->where($where)->save(array('state'=>2));
            M('user_box_relation')->add(array(
                'boxId'         =>  $boxOrder['boxId'],
                'userId'        =>  $userId,
                'shopId'        =>  $boxOrder['shopId'],
                'createTime'    =>  date('Y-m-d H:i:s'),
                'state'         =>  0
            ));
            $result = true;
        }
        return $result;
    }

    /**
     * 删除订单
     * @param $userId
     * @param $orderId
     */
    public function deleteOrder($userId,$orderId){
        $m = M('box_order');
        $where = array('orderId'=>$orderId);
        $boxOrder = $m->where($where)->find();
        $result = false;
        if (!empty($boxOrder)) {
            $m->where($where)->save(array('state'=>-1));
            M('user_box_relation')->where(array('userId'=>$userId,'boxId'=>$boxOrder['boxId'],'shopId'=>$boxOrder['shopId']))->save(array('state'=>-1));
            $result = true;
        }
        return $result;
    }

}