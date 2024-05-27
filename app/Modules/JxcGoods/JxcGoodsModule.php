<?php
/**
 * 总仓商品
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-03-11
 * Time: 15:27
 */

namespace App\Modules\JxcGoods;

use App\Models\BaseModel;
use App\Models\JxcGoodsCatModel;
use App\Models\JxcGoodsGallerysModel;
use App\Models\JxcGoodsModel;
use App\Models\JxcGoodsUnitModel;
use App\Models\JxcSkuGoodsSelfModel;
use App\Models\JxcSkuGoodsSystemModel;

class JxcGoodsModule extends BaseModel
{
    /**
     * 商品-商品分类-根据分类id获取分类列表
     * @param array|string catid 分类id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsCatListById($catid, $field = '*')
    {
        if (is_array($catid)) {
            $catid = implode(',', array_unique($catid));
        }
        $where = array(
            'catid' => array('IN', $catid),
            'dataFlag' => 1
        );
        $model = new JxcGoodsCatModel();
        $data = $model->where($where)->where($where)->field($field)->order('sort desc')->select();
        return (array)$data;
    }

    /**
     * 商品-获取商品详情
     * @param int $goods_id 商品id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsDetailById(int $goods_id, $field = '*')
    {
        $model = new JxcGoodsModel();
        $where = array(
            'goodsId' => $goods_id,
            'dataFlag' => 1,
        );
        $data = $model->where($where)->field($field)->find();
        if (empty($data)) {
            return array();
        }
        if (isset($data['unitId'])) {
            $unitDetail = $this->getGoodsUnitDetailById($data['unitId'], 'goodsUnit');
            if (!empty($unitDetail)) {
                $data['unitName'] = $unitDetail['goodsUnit'];
            }
        }
        $data['gallery'] = $this->getGoodsGallery($goods_id);//商品轮播图
        if (isset($data['goodsCat1'])) {//商品分类名称
            $data['goodsCatName1'] = $this->getGoodsCatnameById($data['goodsCat1']);
        }
        if (isset($data['goodsCat2'])) {
            $data['goodsCatName2'] = $this->getGoodsCatnameById($data['goodsCat2']);
        }
        if (isset($data['goodsCat3'])) {
            $data['goodsCatName3'] = $this->getGoodsCatnameById($data['goodsCat3']);
        }
        $data['skuList'] = $this->getGoodsSkuList($goods_id);
        return $data;
    }

    /**
     * 商品-获取商品详情
     * @param array $params
     * -int goodsId 商品id
     * -string goodsSn 商品编码
     * @return array
     * */
    public function getGoodsDetailByParams(array $params)
    {
        $model = new JxcGoodsModel();
        $where = array(
            'goodsId' => null,
            'goodsSn' => null,
            'dataFlag' => 1,
        );
        parm_filter($where, $params);
        $data = $model->where($where)->find();
        if (empty($data)) {
            return array();
        }
        $goods_id = $data['goodsId'];
        if (isset($data['unitId'])) {
            $unitDetail = $this->getGoodsUnitDetailById($data['unitId'], 'goodsUnit');
            if (!empty($unitDetail)) {
                $data['unitName'] = $unitDetail['goodsUnit'];
            }
        }
        $data['gallery'] = $this->getGoodsGallery($goods_id);//商品轮播图
        if (isset($data['goodsCat1'])) {//商品分类名称
            $data['goodsCatName1'] = $this->getGoodsCatnameById($data['goodsCat1']);
        }
        if (isset($data['goodsCat2'])) {
            $data['goodsCatName2'] = $this->getGoodsCatnameById($data['goodsCat2']);
        }
        if (isset($data['goodsCat3'])) {
            $data['goodsCatName3'] = $this->getGoodsCatnameById($data['goodsCat3']);
        }
        $data['skuList'] = $this->getGoodsSkuList($goods_id);
        return $data;
    }

    /**
     * 商品-计量单位-根据id获取详情
     * @param int $id 计量单位id
     * @param string 表字段
     * @return array
     * */
    public function getGoodsUnitDetailById(int $id, $field = '*')
    {
        $model = new JxcGoodsUnitModel();
        $where = array(
            'dataFlag' => 1,
            'id' => $id
        );
        $data = $model->where($where)->field($field)->find();
        if (empty($data)) {
            return array();
        }
        return $data;
    }

    /**
     * 商品-获取商品轮播图
     * @param int $goodsId 商品id
     * @return  array
     * */
    public function getGoodsGallery(int $goodsId)
    {
        $model = new JxcGoodsGallerysModel();
        $where = array(
            'type' => 1,
            'goodsId' => $goodsId,
        );
        $data = $model->where($where)->field('goodsImg')->order('id', 'asc')->select();
        if (empty($data)) {
            return array();
        }
        $gallerys = array_column($data, 'goodsImg');
        return $gallerys;
    }

    /**
     * 商品-商品分类-根据分类id获取分类名称
     * @param int catid 分类id
     * @return string
     * */
    public function getGoodsCatnameById($catid)
    {
        $where = array(
            'catid' => $catid,
            'dataFlag' => 1
        );
        $model = new JxcGoodsCatModel();
        $data = $model->where($where)->getField('catname');
        return (string)$data;
    }

    /**
     * 商品-商品分类-根据分类id获取分类详情
     * @param int catid 分类id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsCatDetailById($catid, $field = '*')
    {
        $where = array(
            'catid' => $catid,
            'dataFlag' => 1
        );
        $model = new JxcGoodsModel();
        $data = $model->where($where)->field($field)->find();
        if (empty($data)) {
            return array();
        }
        return $data;
    }

    /**
     * 商品-SKU-根据商品id获取商品SKU列表
     * @param int $goodsId 商品id
     * @return array
     * */
    public function getGoodsSkuList(int $goodsId)
    {
        $model = new JxcSkuGoodsSystemModel();
        $where = array(
            'goodsId' => $goodsId,
            'dataFlag' => 1,
            'examineStatus' => 1,
        );
        $data = $model->where($where)->order('skuId', 'asc')->select();
        if (empty($data)) {
            return array();
        }
        $sku_id_arr = array_column($data, 'skuId');
        $self_model = new JxcSkuGoodsSelfModel();
        $self_where = array(
            'self.dataFlag' => 1,
            'self.skuId' => array('IN', $sku_id_arr),
            'spec.dataFlag' => 1,
            'attr.dataFlag' => 1,
        );
        $field = 'self.id,self.skuId';
        $field .= ',spec.specId,spec.specName';
        $field .= ',attr.attrId,attr.attrName';
        $prefix = M()->tablePrefix;
        $self_list = $self_model
            ->alias('self')
            ->join("left join {$prefix}jxc_sku_spec spec on self.specId = spec.specId")
            ->join("left join {$prefix}jxc_sku_spec_attr attr on self.attrId = attr.attrId")
            ->field($field)
            ->where($self_where)
            ->select();
        if (empty($self_list)) {
            return array();
        }
        foreach ($data as &$item) {
            $item['endLiveDate'] = (string)$item['endLiveDate'];
            $item['skuStock'] = $item['stock'];//兼容之前的变量
            $sku_spec_attr = array();//规格属性(array)
            foreach ($self_list as $self_val) {
                if ($self_val['skuId'] == $item['skuId']) {
                    $sku_spec_attr[] = $self_val;
                }
            }
            if (empty($sku_spec_attr)) {
                unset($item);
                continue;
            }
            $spec_attr = array_column($sku_spec_attr, 'attrName');
            $sku_spec_str = implode('，', $spec_attr);//规格属性(string)
            $item['skuSpecAttr'] = $sku_spec_attr;
            $item['skuSpecStr'] = $sku_spec_str;
        }
        unset($item);
        return array_values($data);
    }

    /**
     * 商品-商品是否存在
     * @param int $goods_id
     * @return bool
     * */
    public function isExistJxcGoods(int $goods_id)
    {
        $model = new JxcGoodsModel();
        $count = $model->where(array(
            'goodsId' => $goods_id,
            'dataFlag' => 1,
        ))->count();
        if ($count > 0) {//存在
            return true;
        }
        return false;//不存在
    }

    /**
     * 商品-获取商品id
     * @param array $params
     * -int goodsCat1 一级分类id
     * -int goodsCat2 二级分类id
     * -int goodsCat3 三级分类id
     * @return array
     * */
    public function getGoodsIdArr(array $params)
    {
        $where = array(
            'isSale' => 1,
            'dataFlag' => 1
        );
        $where_find = array();
        $where_find['goodsCat1'] = function () use ($params) {
            if (empty($params['goodsCat1'])) {
                return null;
            }
            return array('=', "{$params['goodsCat1']}", 'and');
        };
        $where_find['goodsCat2'] = function () use ($params) {
            if (empty($params['goodsCat2'])) {
                return null;
            }
            return array('=', "{$params['goodsCat2']}", 'and');
        };
        $where_find['goodsCat3'] = function () use ($params) {
            if (empty($params['goodsCat3'])) {
                return null;
            }
            return array('=', "{$params['goodsCat3']}", 'and');
        };
        where($where_find);
        $where_find = rtrim($where_find, ' and');
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_info = "{$where} and {$where_find} ";
        }
        $model = new JxcGoodsModel();
        $data = $model->where($where_info)->field('goodsId')->select();
        if (empty($data)) {
            return array();
        }
        $goods_id_arr = array_column($data, 'goodsId');
        return $goods_id_arr;
    }

    /**
     * 商品-商品分类-根据父级id获取分类类表
     * @param int|array $parent_id 父级分类id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsCatListByParentId($parent_id, $field = '*')
    {
        if (!is_array($parent_id)) {
            $parent_id = explode(',', $parent_id);
        }
        $where = array(
            'parentId' => array('IN', $parent_id),
            'isShow' => 1,
            'dataFlag' => 1
        );
        $model = new JxcGoodsCatModel();
        $data = $model->where($where)->where($where)->field($field)->order('sort desc')->select();
        return (array)$data;
    }

    /**
     * 商品-SKU-根据sku编码获取商品sku详情
     * @param string $code sku编码
     * @param string $field
     * @return array
     * */
    public function getGoodsSkuDetailByCode(string $code)
    {
        $model = new JxcSkuGoodsSystemModel();
        $where = array(
            'skuBarcode' => $code,
            'dataFlag' => 1,
            'examineStatus' => 1,
        );
        $system_detail = $model->where($where)->find();
        if (empty($system_detail)) {
            return array();
        }
        $sku_id = $system_detail['skuId'];
        $self_model = new JxcSkuGoodsSelfModel();
        $self_where = array(
            'self.dataFlag' => 1,
            'self.skuId' => $sku_id,
            'spec.dataFlag' => 1,
            'attr.dataFlag' => 1,
        );
        $field = 'self.id,self.skuId';
        $field .= ',spec.specId,spec.specName';
        $field .= ',attr.attrId,attr.attrName';
        $prefix = M()->tablePrefix;
        $self_list = $self_model
            ->alias('self')
            ->join("left join {$prefix}jxc_sku_spec spec on self.specId = spec.specId")
            ->join("left join {$prefix}jxc_sku_spec_attr attr on self.attrId = attr.attrId")
            ->field($field)
            ->where($self_where)
            ->select();
        if (empty($self_list)) {
            return array();
        }
        $system_detail['skuSpecAttr'] = $self_list;
        $system_detail['skuSpecStr'] = implode('，', array_column($self_list, 'attrName'));
        return $system_detail;
    }
}