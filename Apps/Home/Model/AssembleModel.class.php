<?php
namespace Home\Model;
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
     * 获取拼团活动列表
     */
    public function getAssembleList($data){
        $sql = "select aa.*,g.goodsName from __PREFIX__assemble_activity as aa left join __PREFIX__goods as g on aa.goodsId = g.goodsId where aa.state = ".$data['state'];
        if (!empty($data['title'])) $sql .= " and aa.title like '%" . $data['title'] . "%'";
        if (!empty($data['shopId'])) $sql .= " and aa.shopId = " . $data['shopId'];

        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * 添加拼团活动
     */
    public function insertAssemble($data = array()){
        return M('assemble_activity')->add($data);
    }

    /**
     * 编辑拼团活动
     */
    public function updateAssemble($aid, $data = array()){
        return M('assemble_activity')->where('aid = ' . $aid)->save($data);
    }

    /**
     * 获取活动详情
     */
    public function assembleDetail($aid){
        return M('assemble_activity as aa')->join('wst_goods as g on aa.goodsId = g.goodsId')->where('aa.aid = '.$aid)->field('aa.*,g.goodsName')->find();
    }

    /**
     * 删除拼团活动
     */
    public function deleteAssemble($aid, $shopId){
        $where = 'aid = ' . $aid . ' and shopId = ' . $shopId;
        $result = M('assemble_activity')->where($where)->delete();
        if ($result) M('user_activity_relation')->where($where)->delete();
        return $result;
    }

    /**
     * 获取拼团用户
     */
    public function getAssembleUser($aid){
        return M('user_activity_relation as uar')->join('wst_users as u on uar.uid = u.userId')->where('uar.aid = '.$aid)->field('u.username,uar.*')->select();
    }

    /**
     * 拼团订单列表（正在进行中和拼团失败）
     */
    public function getAssembleOrderList($data){
        $sql = "select o.* from __PREFIX__orders as o left join __PREFIX__user_activity_relation as uar on o.orderId = uar.orderId left join __PREFIX__assemble_activity as aa on aa.aid = uar.aid where (aa.state = -1 or (aa.startTime <= '" . $data['curTime'] . "' and aa.endTime >= '" . $data['curTime'] . "' and aa.state = 0)) and aa.shopId = " . $data['shopId'];
        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * 店铺拼团商品
     */
    public function getShopAssembleGoods($data){
        $sql = "select aa.*,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.goodsStock from __PREFIX__goods as g left join __PREFIX__assemble_activity as aa on aa.goodsId = g.goodsId where aa.endTime >= '" . $data['curTime'] . "' and aa.state = 0 and g.isSale = 1";
        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * 拼团失败，自动退费用、退库存
     */
    public function autoReAssembleOrder(){
        $curTime = date('Y-m-d H:i:s');
        //$curTime = date('Y-m-d H:i:s',strtotime('39 days'));//需要删除
        //取出拼团失败的活动
        $where = "assemble.endTime <= '{$curTime}' and assemble.state = -1 and assemble.isRefund = 0 ";
        $assembleActivity = M('user_activity_relation relation')
            ->join("left join wst_assemble assemble on relation.pid = assemble.pid")
            ->where($where)
            ->field('relation.*')
            ->select();
        if (!empty($assembleActivity)) {
            $m = D('Adminapi/Orders');
//            $am = M('assemble');
//            $uarm = M('user_activity_relation');
//            $pid_arr = array();
//            foreach ($assembleActivity as $v){
//                $pid_arr[] = $v['pid'];
//            }
//            $pid_arr = array_unique($pid_arr);
//            $am->where(array('pid'=>array('in',$pid_arr)))->save(array('isRefund'=>1));
            $assembleTab = M('assemble');
            foreach ($assembleActivity as $v) {
//                $res = $m->assembleOrderCancel(array('userId'=>$v['uid'], 'orderId'=>$v['orderId']));
                $res = $m->assembleOrderCancel($v['uid'], $v['orderId']);
                if($res['status'] > 0 ){
                    $saveData = [];
                    $saveData['isRefund'] = 1;
                    $assembleTab->where(['pid'=>$v['pid']])->save($saveData);
                }
            }
        }
    }

}