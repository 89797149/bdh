<?php


namespace App\Modules\Goods;

use App\Models\BaseModel;
use App\Models\GoodsCatModel;
use App\Models\ShopCatsModel;

//商品商城分类
class GoodsCatModule extends BaseModel
{
    /**
     * 商城分类列表-根据分类id获取
     * @param string|array $goods_cat_id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsCatListById($goods_cat_id, $field = '*')
    {
        if (empty($goods_cat_id)) {
            return array();
        }
        if (is_array($goods_cat_id)) {
            $cat_id_str = implode(',', array_unique($goods_cat_id));
        } else {
            $cat_id_str = $goods_cat_id;
        }
        $model = new GoodsCatModel();
        $where = array(
            'catId' => array('IN', $cat_id_str),
            'catFlag' => 1,
        );
        $list = $model->where($where)->field($field)->select();
        return (array)$list;
    }

    /**
     * 商城分类详情-根据分类id获取
     * @param int $goods_cat_id 商品商城分类id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsCatDetailById(int $goods_cat_id, $field = '*')
    {
        if (empty($goods_cat_id)) {
            return array();
        }
        $model = new GoodsCatModel();
        $where = array(
            'catId' => $goods_cat_id,
            'catFlag' => 1,
        );
        $detail = $model->where($where)->field($field)->find();
        if (empty($detail)) {
            return array();
        }
        $detail['level'] = 1;
        if ($detail['parentId'] > 0) {
            $detail['level'] = 2;
            $where['catId'] = $detail['parentId'];
            $parent_detail = $model->where($where)->field($field)->find();
            if (empty($parent_detail)) {
                return $detail;
            }
            if ($parent_detail['parentId'] > 0) {
                $detail['level'] = 3;
            }
        }
        return (array)$detail;
    }

    /**
     * 商城分类详情-根据分类id获取
     * @param int $cat_name 商品商城分类名称
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsCatDetailByName(string $cat_name, $field = '*')
    {
        if (empty($cat_name)) {
            return array();
        }
        $model = new GoodsCatModel();
        $where = array(
            'catName' => $cat_name,
            'catFlag' => 1,
        );
        $detail = $model->where($where)->field($field)->find();
        if (empty($detail)) {
            return array();
        }
        unset($where['catName']);
        $detail['level'] = 1;
        if ($detail['parentId'] > 0) {
            $detail['level'] = 2;
            $where['catId'] = $detail['parentId'];
            $parent_detail = $model->where($where)->field($field)->find();
            if (empty($parent_detail)) {
                return $detail;
            }
            if ($parent_detail['parentId'] > 0) {
                $detail['level'] = 3;
            }
        }
        return (array)$detail;
    }

    /**
     * 店铺分类列表-根据店铺分类id查找
     * @param string|array $shop_cat_id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopCatListById($shop_cat_id, $field = '*')
    {
        if (empty($shop_cat_id)) {
            return array();
        }
        if (is_array($shop_cat_id)) {
            $cat_id_str = implode(',', array_unique($shop_cat_id));
        } else {
            $cat_id_str = $shop_cat_id;
        }
        $model = new ShopCatsModel();
        $where = array(
            'catId' => array('IN', $cat_id_str),
            'catFlag' => 1,
        );
        $list = $model->where($where)->field($field)->select();
        return (array)$list;
    }

    /**
     * 店铺分类详情-根据店铺分类id查找
     * @param int $cat_id 分类id
     * @param string $field 表字段
     * @return array $result
     * */
    public function getShopCatDetailById(int $cat_id, $field = '*')
    {
        $model = new ShopCatsModel();
        $where = array(
            'catId' => $cat_id,
            'catFlag' => 1,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        if (isset($result['parentId'])) {
            if ($result['parentId'] == 0) {
                $result['level'] = 1;
            } else {
                $result['level'] = 2;
                $where['catId'] = $result['parentId'];
                $parent_detail = $model->where($where)->field($field)->find();
                $result['parent_name'] = $parent_detail['catName'];
            }
        }
        return $result;
    }

    /**
     * 商城分类-列表
     * @return array
     * */
    public function getGoodsCatList()
    {
        $model = new GoodsCatModel();
        $where = array(
            'catFlag' => 1,
            'parentId' => 0,
        );
        $field = 'catId,parentId,isShow,isShowIndex,catName,catSort,typeimg,appTypeSmallImg';
        $first_list = $model->where($where)->field($field)->select();
        if (empty($first_list)) {
            return array();
        }
        $first_id = array_column($first_list, 'catId');
        $where['parentId'] = array('in', $first_id);
        $second_list = $model->where($where)->field($field)->select();
        foreach ($first_list as &$item) {
            $item['child'] = array();
            foreach ($second_list as $second_val) {
                if ($second_val['parentId'] == $item['catId']) {
                    $item['child'][] = $second_val;
                }
            }
        }
        unset($item);
        if (empty($second_list)) {
            return $first_list;
        }
        $second_id = array_column($second_list, 'catId');
        $where['parentId'] = array('in', $second_id);
        $third_list = $model->where($where)->field($field)->select();
        foreach ($first_list as $f_key => $first_val) {
            foreach ($first_val['child'] as $s_key => $second_val) {
                $second_val['child'] = array();
                foreach ($third_list as $third_val) {
                    if ($third_val['parentId'] == $second_val['catId']) {
                        $second_val['child'][] = $third_val;
                    }
                }
                $first_val['child'][$s_key] = $second_val;
            }
            $first_list[$f_key] = $first_val;
        }
        if (empty($third_list)) {
            return $first_id;
        }
        return $first_list;
    }


}