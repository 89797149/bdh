<?php

namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 投筐功能类
 */
class BasketModel extends BaseModel
{

    /**
     * 分区列表 - 不带分页
     */
    public function getPartitionList($shopId)
    {
        $m = M('partition');
        $data = $m->where(array('shopId' => $shopId, 'pid' => 0, 'pFlag' => 1))->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['childPartition'] = (array)$m->where(array('shopId' => $shopId, 'pid' => $v['id'], 'pFlag' => 1))->select();
            }
        }
        return $data;
    }

    /**
     * 编辑分区
     * @param $where
     * @param $data
     * @return mixed
     */
    public function editPartition($where, $data)
    {
//        return M('partition')->where($where)->save($data);
        $data = M('partition')->where($where)->save($data);
        if($data === false){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 新增分区
     * @param $data
     * @return mixed
     */
    public function addPartition($data)
    {
        return M('partition')->add($data);
    }

    /**
     * 删除分区
     * @param $where
     * @return bool
     */
    public function deletePartition($where)
    {
        $m = M('partition');
        $bm = M('basket');
        $partitionInfo = $m->where($where)->find();
        if (empty($partitionInfo) || $partitionInfo['pFlag'] == -1) return false;
        $result = $m->where($where)->save(array('pFlag' => -1));
        if ($partitionInfo['pid'] == 0) {//一级分区
            $m->where(array('pid' => $where['id'], 'pFlag' => 1))->save(array('pFlag' => -1));
            $bm->where(array('pid' => $where['id'], 'bFlag' => 1))->save(array('bFlag' => -1));
        } else {//二级分区
            $bm->where(array('partitionId' => $where['id'], 'bFlag' => 1))->save(array('bFlag' => -1));
        }
        return $result;
    }

    /**
     * 获得分区详情
     * @param $where
     * @return mixed
     */
    public function getPartitionDetail($where)
    {
        return M('partition')->where($where)->find();
    }

    /**
     * 投筐列表
     * @param array $params<p>
     * int shopId
     * int page
     * int pageSize
     * </p>
     * @return array
     */
    public function getBasketList($param)
    {
        $where = " b.shopId = " . $param['shopId'] . " and b.bFlag = 1 ";
        $sql = "select b.*,p.name as partitionName from __PREFIX__basket as b inner join __PREFIX__partition as p on b.partitionId = p.id where " . $where . " order by b.bid desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);
        if (!empty($list['root'])) {
            $partitionKeyValueArr = $this->getPartitionKeyValueArr();
            $isOccupy = 0;//用于判断当前框位是否占用【0:没有|1:有】
            foreach ($list['root'] as $k => $v) {
                $list['root'][$k]['PpartitionName'] = $partitionKeyValueArr[$v['pid']];
                //获取当前框位下的所有订单数量
                $getBasketOrderCount = M('orders')->where(['basketId' => $v['bid'],'orderStatus' => 2,'shopId' => $v['shopId']])->count();
                if($getBasketOrderCount > 0){
                    $isOccupy = 1;
                }
                $list['root'][$k]['orderCount'] = (int)$getBasketOrderCount;//框位下订单数量
                $list['root'][$k]['isOccupy'] = (int)$isOccupy;//用于判断当前框位是否占用【0:没有|1:有】
            }
        }
        return $list;
    }

    /**
     * @param $params
     * @return array
     * 获取框位下订单【打包中的】
     */
    public function getBasketOrderList($params){
        $where = " shopId = {$params['shopId']} and orderStatus = 2 and basketId = {$params['bid']} ";
        $sql = "select * from __PREFIX__orders  where " . $where . " order by orderId desc ";
        $list = $this->query($sql);
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $orderGoods = M('order_goods')->where(['orderId'=>$v['orderId']])->select();
                foreach ($orderGoods as $key=>$val){
                    $orderGoods[$key]['orderNo'] = $v['orderNo'];
                }
                $list[$k]['goods'] = $orderGoods;
            }
        }
        return (array)$list;
    }
    /**
     * 分区键值对  格式：array('id'=>'name');
     * @return mixed
     */
    public function getPartitionKeyValueArr()
    {
        return M('partition')->getField('id,name');
    }

    /**
     * 根据条件获得分区列表
     * @param $where
     * @return mixed
     */
    public function getPartitionListByCondition($where)
    {
        return M('partition')->where($where)->select();
    }

    /**
     * @param $shopId
     * @return mixed
     * 根据一级框位分类id获得二级框位分类
     */
    public function getTwoFrameList($shopId)
    {
        $where = array(
            'p.shopId' => $shopId,
            'p.pid' => I('id'),
            'p.pFlag' => 1
        );
        $res = M('partition p')
            ->join('left join wst_partition wp on wp.id = p.pid')
            ->where($where)
            ->field('p.*,wp.name as pidName')
            ->select();
        return $res;
    }

    /**
     * 编辑投筐
     * @param $where
     * @param $data
     * @return mixed
     */
    public function editBasket($where, $data)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $res = M('basket')->where(['basketSn'=>$data['basketSn'],'bFlag'=>1,'shopId'=>$data['shopId']])->find();
        if(!empty($res) && $where['bid'] != $res['bid']){
            $apiRet['apiInfo'] = '编码重复';
            return $apiRet;
        }
        $rest = M('basket')->where(['name'=>$data['name'],'bFlag'=>1,'shopId'=>$data['shopId']])->find();
        if(!empty($rest) && $where['bid'] != $rest['bid']){
            $apiRet['apiInfo'] = '筐名称重复';
            return $apiRet;
        }
        return M('basket')->where($where)->save($data);
    }

    /**
     * 添加投筐
     * @param $data
     * @return mixed
     */
    public function addBasket($data)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $res = M('basket')->where(['basketSn'=>$data['basketSn'],'bFlag'=>1,'shopId'=>$data['shopId']])->find();
        if(!empty($res)){
            $apiRet['apiInfo'] = '编码重复';
            return $apiRet;
        }
        $rest = M('basket')->where(['name'=>$data['name'],'bFlag'=>1,'shopId'=>$data['shopId']])->find();
        if(!empty($rest)){
            $apiRet['apiInfo'] = '筐名称重复';
            return $apiRet;
        }
        return M('basket')->add($data);
    }

    /**
     * 删除投筐
     * @param $where
     * @param $data
     * @return bool
     */
    public function deleteBasket($where, $data)
    {
        $m = M('basket');
        $basketInfo = $m->where($where)->find();
        if (empty($basketInfo)) return false;
        return $m->where($where)->save($data);
    }

    /**
     * 获得投筐详情
     * @param $where
     * @return mixed
     */
    public function getBasketDetail($where)
    {
        return M('basket')->where($where)->find();
    }

    /**
     * (订单受理时)分配筐位
     * @param $shopId
     * @param $orderId
     */
    public function distributionBasket($shopId, $orderId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '分配失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $om = M('orders');
        $owhere = array('orderId' => $orderId, 'shopId' => $shopId, 'orderFlag' => 1);
        $orderInfo = $om->where($owhere)->find();
        if (empty($orderInfo)) {
            $apiRet['apiInfo'] = '订单不存在';
            return $apiRet;
        }

        if ($orderInfo['orderStatus'] !== 0) {
            $apiRet['apiInfo'] = '订单不符合要求';
            return $apiRet;
        }

        if (!empty($orderInfo['basketId']) && $orderInfo['basketId'] > 0) {
            $apiRet['apiInfo'] = '筐位已分配';
            return $apiRet;
        }
        $basketInfo = autoDistributionBasket($shopId);
        if ($basketInfo['apiCode'] !== 0) {
            return $basketInfo;
        }
        $result = $om->where($owhere)->save(array('basketId' => $basketInfo['apiData']['basketId']));
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '分配成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

}