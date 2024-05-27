<?php

namespace Adminapi\Model;

use App\Modules\Goods\GoodsServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品分类服务类
 */
class GoodsCatsModel extends BaseModel
{
    /**
     * 新增
     */
    public function insert()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $data = array();
        $data["catName"] = I("catName");
        if ($this->checkEmpty($data, true)) {
            $data["typeimg"] = I("typeimg");
            $data["appTypeSmallImg"] = I("appTypeSmallImg");//app分类小图标
            $data["parentId"] = I("parentId", 0);
            $data["isShow"] = (int)I("isShow", 0);
            $data["isShowIndex"] = (int)I("isShowIndex", 0);//是否显示在首页 0：否 1：是
            $data["catSort"] = (int)I("catSort", 0);
            $data["catFlag"] = 1;;
            $rs = $this->add($data);
            if ($rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * 修改
     */
    public function edit()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = (int)I("id", 0);
        $data = array();
        $data["catName"] = I("catName");
        if ($this->checkEmpty($data)) {
            $data["typeimg"] = I("typeimg");
            $data["appTypeSmallImg"] = I("appTypeSmallImg");//app分类小图标
            $data["isShow"] = (int)I("isShow", 0);
            $data["isShowIndex"] = (int)I("isShowIndex", 0);//是否显示在首页 0：否 1：是
            $data["catSort"] = (int)I("catSort", 0);
            $rs = $this->where("catFlag=1 and catId=" . $id)->save($data);
            if (false !== $rs) {
                if ($data['isShow'] == 0) {//修改子栏目是否隐藏
                    $this->editiIsShow();
                }
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * 修改名称
     */
    public function editName()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = (int)I("id", 0);
        $data = array();
        $data["catName"] = I("catName");
        if ($this->checkEmpty($data)) {
            $rs = $this->where("catFlag=1 and catId=" . $id)->save($data);
            if (false !== $rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function get($id)
    {
        return $this->where("catId=" . (int)$id)->find();
    }

    /**
     * 获取列表
     */
    public function queryByList($pid = 0)
    {
        $rs = $this->where('catFlag=1 and parentId=' . (int)$pid)->select();
        return $rs;
    }

    /**
     * 获取树形分类
     */
    public function getCatAndChild()
    {
        //获取第一级分类
        $sql = "select * from __PREFIX__goods_cats where catFlag=1 and parentId =0 order by catSort asc";
        $rs1 = $this->query($sql);
        if (count($rs1) > 0) {
            $ids = array();
            foreach ($rs1 as $key => $v) {
                $ids[] = $v['catId'];
            }
            //获取第二级分类
            $sql = "select * from __PREFIX__goods_cats where catFlag=1 and parentId in (" . implode(',', $ids) . ")  order by catSort asc";
            $rs2 = $this->query($sql);
            if (count($rs2) > 0) {
                $ids = array();
                foreach ($rs2 as $key => $v) {
                    $ids[] = $v['catId'];
                }
                //获取第三级分类
                $sql = "select * from __PREFIX__goods_cats where catFlag=1 and parentId in (" . implode(',', $ids) . ")  order by catSort asc";
                $rs3 = $this->query($sql);
                $tmpArr = array();
                if (count($rs3) > 0) {
                    foreach ($rs3 as $key => $v) {
                        $tmpArr[$v['parentId']][] = $v;
                    }
                }
                //把第三级归类到第二级下
                foreach ($rs2 as $key => $v) {
                    $rs2[$key]['child'] = $tmpArr[$v['catId']];
                    $rs2[$key]['childNum'] = count($tmpArr[$v['catId']]);
                }
                $tmpArr = array();
                foreach ($rs2 as $key => $v) {
                    $tmpArr[$v['parentId']][] = $v;
                }
            }

            //把二季归类到第一级下
            foreach ($rs1 as $key => $v) {
                $rs1[$key]['child'] = $tmpArr[$v['catId']];
                $rs1[$key]['childNum'] = count($tmpArr[$v['catId']]);
            }
        }
        return $rs1;
    }

    /**
     * @param $parentId
     * @return array
     * 获取分类【新】
     */
    public function getCatsList($parentId)
    {
        $goodsServiceModule = new GoodsServiceModule();
        $goodsCatsList = $goodsServiceModule->getGoodsCatListPid((int)$parentId);
        return (array)$goodsCatsList['data'];
    }

    /**
     * 迭代获取下级
     */
    public function getChild($ids = array(), $pids = array())
    {
        $sql = "select catId from __PREFIX__goods_cats where catFlag=1 and parentId in(" . implode(',', $pids) . ")";
        $rs = $this->query($sql);
        if (count($rs) > 0) {
            $cids = array();
            foreach ($rs as $key => $v) {
                $cids[] = $v['catId'];
            }
            $ids = array_merge($ids, $cids);
            return $this->getChild($ids, $cids);

        } else {
            return $ids;
        }
    }

    /**
     * 删除
     */
    public function del()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        //获取子集
        $ids = array();
        $ids[] = (int)I('id');
        $ids = $this->getChild($ids, $ids);

        //把相关的商品下架了
        $sql = "update __PREFIX__goods set isSale=0 where goodsCatId1 in(" . implode(',', $ids) . ")";
        $this->execute($sql);
        $sql = "update __PREFIX__goods set isSale=0 where goodsCatId2 in(" . implode(',', $ids) . ")";
        $this->execute($sql);
        $sql = "update __PREFIX__goods set isSale=0 where goodsCatId3 in(" . implode(',', $ids) . ")";
        $this->execute($sql);
        //设置商品分类为删除状态
        $this->catFlag = -1;
        $rs = $this->where(" catId in(" . implode(',', $ids) . ")")->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * 显示分类是否显示/隐藏
     */
    public function editiIsShow()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (I('id', 0) == 0) return $rd;
        $isShow = (int)I('isShow');
        //获取子集
        $ids = array();
        $ids[] = (int)I('id');
        $ids = $this->getChild($ids, $ids);

        if ($isShow != 1) {
            //把相关的商品下架了
            $sql = "update __PREFIX__goods set isSale=0 where goodsCatId1 in(" . implode(',', $ids) . ")";
            $this->execute($sql);
            $sql = "update __PREFIX__goods set isSale=0 where goodsCatId2 in(" . implode(',', $ids) . ")";
            $this->execute($sql);
            $sql = "update __PREFIX__goods set isSale=0 where goodsCatId3 in(" . implode(',', $ids) . ")";
            $this->execute($sql);
        }
        $this->isShow = ($isShow == 1) ? 1 : 0;
        $rs = $this->where("catId in(" . implode(',', $ids) . ")")->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @param $params
     * @return mixed
     * 修改排序
     */
    public function editSort($params)
    {
        $goodsServiceModel = new GoodsServiceModule();
        $getGoodsCatInfo = $goodsServiceModel->getGoodsCatInfo($params['catId']);
        if ($getGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        if ((int)$getGoodsCatInfo['data']['catSort'] == (int)$params['catSort']) {
            return returnData(false, -1, 'error', '暂无修改数据');
        }
        $editGoodsCatInfo = $goodsServiceModel->editGoodsCatInfo($params);
        if ($editGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '变更数据失败,请重新尝试');
        }
        return returnData(true);
    }

    /**
     * @param $params
     * @return mixed
     * 修改typeImg 分类图片
     */
    public function editTypeImg($params)
    {
        $goodsServiceModel = new GoodsServiceModule();
        $getGoodsCatInfo = $goodsServiceModel->getGoodsCatInfo($params['catId']);
        if ($getGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        if ($getGoodsCatInfo['data']['typeimg'] == $params['typeimg']) {
            return returnData(false, -1, 'error', '暂无修改数据');
        }
        $editGoodsCatInfo = $goodsServiceModel->editGoodsCatInfo($params);
        if ($editGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '变更数据失败,请重新尝试');
        }
        return returnData(true);
    }

    /**
     * @param $params
     * @return mixed
     * 修改App分类小图标
     */
    public function editAppTypeSmallImg($params)
    {
        $goodsServiceModel = new GoodsServiceModule();
        $getGoodsCatInfo = $goodsServiceModel->getGoodsCatInfo($params['catId']);
        if ($getGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        if ($getGoodsCatInfo['data']['appTypeSmallImg'] == $params['appTypeSmallImg']) {
            return returnData(false, -1, 'error', '暂无修改数据');
        }
        $editGoodsCatInfo = $goodsServiceModel->editGoodsCatInfo($params);
        if ($editGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '变更数据失败,请重新尝试');
        }
        return returnData(true);
    }

    /**
     * @param $params
     * @return mixed
     * 修改首页显示状态
     */
    public function editIsShowIndexType($params)
    {
        $goodsServiceModel = new GoodsServiceModule();
        $getGoodsCatInfo = $goodsServiceModel->getGoodsCatInfo($params['catId']);
        if ($getGoodsCatInfo['code'] != 0) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $editGoodsCatInfo = $goodsServiceModel->editGoodsCatInfo($params);
        if ($editGoodsCatInfo['code'] !== 0) {
            return returnData(false, -1, 'error', '变更数据失败,请重新尝试');
        }
        return returnData(true);
    }
}