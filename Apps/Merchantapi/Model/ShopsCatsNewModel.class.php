<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 新店铺分类功能类(主要用于洗衣服功能)
 */
class ShopsCatsNewModel extends BaseModel {

    /**
     * 获得商家分类列表
     * @param $param
     */
    public function getCatList($param){
        $where = " shopId = " . $param['shopId'] . " and scnFlag = 1 ";
        if (!empty($param['name'])) $where .= " and name like '%" . $param['name'] . "%' ";
        $sql = "select * from __PREFIX__shops_cats_new where " . $where . " order by sort desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 编辑分类
     * @param $where
     * @param $data
     */
    public function editCat($where,$data){
        return M('shops_cats_new')->where($where)->save($data);
    }

    /**
     * 新增分类
     * @param $data
     */
    public function addCat($data){
        return M('shops_cats_new')->add($data);
    }

    /**
     * 删除分类
     * @param $where
     * @param $data
     */
    public function deleteCat($where,$data){
        $scnm = M('shops_cats_new');
        $shopsCatsNewInfo = $scnm->where($where)->find();
        if (empty($shopsCatsNewInfo)) return 0;
        return $scnm->where($where)->save($data);
    }

    /**
     * 获取店铺一级分类
     * @param $where
     */
    public function getOneCatList($where){
        return M('shops_cats')->where($where)->order('catSort desc')->select();
    }

}