<?php
namespace Merchantapi\Model;
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
     * 获得商家箱子列表
     * @param $param
     */
    public function getBoxList($param){
        $where = " shopId = " . $param['shopId'] . " and state != -1 ";
        if (!empty($param['name'])) $where .= " and name like '%" . $param['name'] . "%' ";
        $sql = "select * from __PREFIX__box where " . $where . " order by createTime desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 编辑箱子
     * @param $where
     * @param $data
     */
    public function editBox($where,$data){
        return M('box')->where($where)->save($data);
    }

    /**
     * 新增箱子
     * @param $data
     */
    public function addBox($data){
        $insertId = M('box')->add($data);
        if ($insertId > 0) {
            $m = M('orderids');
            //生成箱子编号
            $boxSrcNo = $m->add(array('rnd' => microtime(true)));
            $boxNo = $boxSrcNo . "" . (fmod($boxSrcNo, 7));

            M('box')->where(array('boxId'=>$insertId))->save(array('boxNo'=>$boxNo));
        }
        return $insertId;
    }

    /**
     * 删除箱子
     * @param $where
     */
    public function deleteBox($where,$data){
        $m = M('box');
        $box = $m->where($where)->find();
        if (empty($box)) return array('code'=>1,'msg'=>'箱子不存在');
        if ($box['state'] == 1) return array('code'=>2,'msg'=>'箱子还在出租中，不可删除');
        if ($box['state'] == -1) return array('code'=>0);
        if ($m->where($where)->save($data)) return array('code'=>0);
        else return array('code'=>-1,'msg'=>'删除失败');
    }

    /**
     * 获得箱子订单列表
     * @param $param
     */
    public function getBoxOrderList($param){
        $where = " bo.shopId = " . $param['shopId'] . " and bo.state != -1 ";
        $sql = "select bo.*,ua.userName,ua.userPhone,ua.userTel,ua.address,b.name,b.deposit,b.boxNo from __PREFIX__box_order as bo inner join __PREFIX__user_address as ua on bo.addressId = ua.addressId inner join __PREFIX__box as b on bo.boxId = b.boxId where " . $where . " order by bo.createTime desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 箱子订单 - 受理
     * @param $where
     */
    public function acceptanceOrder($where){
        $m = M('box_order');
        $boxOrderData = $m->where($where)->find();
        $result = 0;
        if (!empty($boxOrderData) && empty($boxOrderData['state']))
            $result = $m->where($where)->save(array('state'=>1));

        return $result;
    }

    /**
     * 箱子订单 - 主动完成订单
     * @param $where
     */
    public function completeOrder($where){
        $m = M('box_order');
        $boxOrderData = $m->where($where)->find();
        $result = 0;
        if (!empty($boxOrderData) && $boxOrderData['state'] == 1) {
            $result = $m->where($where)->save(array('state'=>2));
            if ($result) {
                M('box')->where(array('boxId'=>$boxOrderData['boxId'],'shopId'=>$boxOrderData['shopId']))->save(array('state'=>0));
                M('user_box_relation')->add(array(
                    'boxId'         =>  $boxOrderData['boxId'],
                    'userId'        =>  $boxOrderData['userId'],
                    'shopId'        =>  $boxOrderData['shopId'],
                    'createTime'    =>  date('Y-m-d H:i:s'),
                    'state'         =>  0
                ));
            }
        }

        return $result;
    }

    /**
     * 删除出借记录
     * @param $where
     * @param $data
     */
    public function editUserBox($where,$data){
        $m = M('user_box_relation');
        $relation_data = $m->where($where)->find();
        if (empty($relation_data) || $relation_data['state'] == -1) return -1;

        $result = $m->where($where)->save($data);
        if ($result) M('box')->where(array('boxId'=>$relation_data['boxId']))->save(array('state'=>0));

        return $result?0:-1;
    }

    /**
     * 出借列表
     */
    public function getUserBoxList($param){
        $where = " ubr.shopId = " . $param['shopId'] . "  and ubr.state != -1 ";
        $sql = "select ubr.*,b.name,u.userName from __PREFIX__user_box_relation as ubr inner join __PREFIX__box as b on ubr.boxId = b.boxId inner join __PREFIX__users u on ubr.userId = u.userId where " . $where . " order by ubr.createTime desc";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

}