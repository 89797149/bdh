<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 限量购类
 */
class GoodsCountSnappedModel extends BaseModel
{
    /**
     * @return array
     * 获取限量购商品列表
     */
    public function getGoodsCountSnappedList()
    {
        $page = I('page',1);
        $pageSize = I('pageSize',15);
        $field = "wg.goodsName,gcs.*";
        $sql = "select {$field} from wst_goods_count_snapped gcs left join wst_goods wg ON wg.goodsId = gcs.goodsId 
	 	    where gcs.dataFalg = 1 and gcs.shopId = 0 ";
        //活动库存
        if(I('activeInventory')!=''){
            $sql.=" and gcs.activeInventory = ".I('activeInventory');
        }
        //商品名称
        if(I('goodsName')!=''){
            $sql.=" and wg.goodsName like '%".WSTAddslashes(I('goodsName'))."%'";
        }
        //编码
        if(I('goodsSn')!=''){
            $sql.=" and wg.goodsSn like '%".WSTAddslashes(I('goodsSn'))."%'";
        }
        //顶级商品分类ID
        if(I('goodsCatId1')!=''){
            $sql.=" and wg.goodsCatId1 = ".I('goodsCatId1');
        }
        //第二级商品分类ID
        if(I('goodsCatId2')!=''){
            $sql.=" and wg.goodsCatId2 = ".I('goodsCatId2');
        }
        //第三级商品分类ID
        if(I('goodsCatId3')!=''){
            $sql.=" and wg.goodsCatId3 = ".I('goodsCatId3');
        }
        $sql.=' order by gcs.csId desc';
        return $this->pageQuery($sql,(int)$page,(int)$pageSize);
    }


    /**
     * @param $csId
     * @return array
     * 删除限量购商品
     */
    public function deleteGoodsCountSnapped($csId)
    {
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $goodsCountSnapped = M('goods_count_snapped');
        $where['csId'] = $csId;
        $where['shopId'] = 0;
        $data = $goodsCountSnapped->where($where)->save(['dataFalg'=>-1]);
        if($data){
            $rd['code']= 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @param $goodsInfo
     * @return array
     * 新增限量购商品
     */
    public function addGoodsCountSnapped($goodsInfo)
    {
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $goodsCountSnapped = M('goods_count_snapped');
        $goodsIds = [];
        foreach ($goodsInfo as $k=>$v){
            $goodsIds[] = $v['goodsId'];
            $dateInfo = $goodsCountSnapped
                ->alias('gcs')
                ->join('left join wst_goods wg ON wg.goodsId = gcs.goodsId')
                ->where("gcs.shopId = 0 and gcs.dataFalg = 1 and gcs.goodsId = ".$v['goodsId'])
                ->find();
            if(!empty($dateInfo)){
                $rd['msg'] = "商品:".$dateInfo['goodsName']."已添加";
                return $rd;
            }
            if($v['minBuyNum'] >= $v['activeInventory'] && $v['minBuyNum'] != -1){
                $rd['msg'] = "最小起订量不能大于活动库存";
                return $rd;
            }
            if($v['marketPrice'] < $v['activityPrice']){
                $rd['msg'] = "商品:".$dateInfo['goodsName']."市场价必须大于活动价";
                return $rd;
            }
            if($v['salesInventory'] > $v['activeInventory']){
                $rd['msg'] = "已售库存不能大于活动库存";
                return $rd;
            }
            $goodsInfo[$k]['createTime'] = date('Y-m-d H:i:s');
            $goodsInfo[$k]['shopId'] = 0;
        }
        if (count($goodsIds) != count(array_unique($goodsIds))) {
            $rd['msg'] = "请查看商品是否重复添加";
            return $rd;
        }
        $data = $goodsCountSnapped->addAll($goodsInfo);
        if($data){
            $rd['code']= 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @return array
     * 修改限量购商品
     */
    public function editGoodsCountSnapped()
    {
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $goodsCountSnapped = M('goods_count_snapped');
        $requestParam = I('');
        //备参与过滤
        $save = [];
        $save['marketPrice'] = null;
        $save['activityPrice'] = null;
        $save['minBuyNum'] = null;
        $save['salesInventory'] = null;
        parm_filter($save,$requestParam);
        $where['csId'] = I('csId');
        $where['dataFalg'] = 1;
        $where['shopId'] = 0;
        $goodsInfo = $goodsCountSnapped->where($where)->find();
        if(empty($goodsInfo)){
            $rd['msg'] = "请查看商品是否存在";
            return $rd;
        }
        if(!empty($save['marketPrice'])){
            if($save['marketPrice'] <= $goodsInfo['activityPrice']){
                $rd['msg'] = "市场价不能小于或等于活动价";
                return $rd;
            }
        }
        if(!empty($save['activityPrice'])){
            if($save['activityPrice'] >= $goodsInfo['marketPrice']){
                $rd['msg'] = "活动价不能大于或等于市场价";
                return $rd;
            }
        }
        if(!empty($save['minBuyNum'])){
            if($save['minBuyNum'] >= $goodsInfo['activeInventory']){
                $rd['msg'] = "最小起订量不能大于活动库存";
                return $rd;
            }
        }
        if(!empty($save['salesInventory'])){
            if($save['salesInventory'] > $goodsInfo['activeInventory']){
                $rd['msg'] = "已售库存不能大于活动库存";
                return $rd;
            }
        }
        $data = $goodsCountSnapped->where($where)->save($save);
        if($data){
            $rd['code']= 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }
}