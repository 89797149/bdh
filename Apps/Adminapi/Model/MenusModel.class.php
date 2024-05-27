<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 菜谱
 */
class MenusModel extends BaseModel
{
    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $param = I('');
        $where = " WHERE m.state=0 ";
        if (!empty($param['title'])) {
            $where .= " AND m.title='" . $param['title'] . "'";
        }
        $sql = "SELECT m.*,c.catname FROM __PREFIX__menus m LEFT JOIN __PREFIX__menus_cat c ON m.catId=c.id $where ORDER BY m.id DESC";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     * 新增
     */
    public function insert($response)
    {
        $apiRet['code'] = -1;
        $apiRet['msg'] = '数据操作失败';
        $apiRet['data'] = array();
        if (!empty($response)) {
            $menuInfo = M('menus')->where("title='" . $response['title'] . "' AND state=0")->find();
            if ($menuInfo) {
                $apiRet['msg'] = $menuInfo['title'] . ' 菜谱已经存在';
                return $apiRet;
            }
            $ingredient = $response['ingredient'];
            $step = $response['step'];
            $catTab = M('menus_ingredientcat');
            $relationTab = M('menus_ingredientcat_relation');
            $ingreTab = M('menus_ingredient');
            unset($response['ingredient']);
            unset($response['step']);
            //写入菜谱
            $menuId = $this->add($response);
            if (!empty($ingredient)) {
                foreach ($ingredient as $key => $val) {
                    if (empty($val['catId'])) {
                        $catInfo = M("menus_ingredientcat_relation mr")
                            ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=mr.ingredientCatId")
                            ->where("c.catname='" . $val['catname'] . "' AND mr.state=0 AND mr.menuId='" . $response['id'] . "'")
                            ->find();
                        if ($catInfo['id'] > 0) {
                            $apiRet['msg'] = $val['catname'] . ' 食材已经存在';
                            //$rd['msg'] = $val['catname'].' 食材已经存在';
                            return $apiRet;
                        }
                        //写入菜谱分类
                        $add['catname'] = $val['catname'];
                        $add['state'] = 0;
                        $add['addTime'] = date('Y-m-d H:i:s', time());
                        $catId = $catTab->add($add);
                        unset($add);
                        if ($catId) {
                            //写入食材菜谱关系表
                            $relationAdd['menuId'] = $menuId;
                            $relationAdd['ingredientCatId'] = $catId;
                            $relationAdd['state'] = 0;
                            $relationTab->add($relationAdd);

                            //写入食材商品
                            if (!empty($val['goodsId'])) {
                                $goodsIdStr = implode(',', $val['goodsId']);
                                $ingreAdd['goodsId'] = rtrim($goodsIdStr, ',');
                                $ingreAdd['ingredientCatId'] = $catId;
                                $ingreTab->add($ingreAdd);
                            }
                        }
                    }
                }
            }
            //写入步骤
            if (!empty($step)) {
                $stepTab = M('menus_step');
                foreach ($step as $cv) {
                    if (empty($cv['stepId'])) {
                        $stepAdd['content'] = $cv['content'];
                        $stepAdd['pic'] = $cv['pic'];
                        $stepAdd['menuId'] = $menuId;
                        $stepAdd['sort'] = $cv['sort'];
                        $stepAdd['addTime'] = date('Y-m-d H:i:s', time());
                        $stepTab->add($stepAdd);
                    }
                }
            }
            if ($menuId) {
                $apiRet['code'] = 0;
                $apiRet['msg'] = '数据操作成功';
            }
        }
        return $apiRet;
    }

    /**
     * 修改
     */
    public function edit($response)
    {
        $apiRet['code'] = -1;
        $apiRet['msg'] = '数据操作失败';
        $apiRet['data'] = array();
        $ingredient = $response['ingredient'];
        $step = $response['step'];
        $catTab = M('menus_ingredientcat');
        $relationTab = M('menus_ingredientcat_relation');
        $ingreTab = M('menus_ingredient');
        unset($response['ingredient']);
        unset($response['step']);
        $rs = $this->where("id='" . $response['id'] . "'")->save($response);
        if (!empty($ingredient)) {
            $existCatArr = [];
            $existCat = $relationTab->where("menuId='" . $response['id'] . "' AND state=0")->select();
            if (!empty($existCat)) {
                foreach ($existCat as $val) {
                    $existCatArr[] = $val['ingredientCatId'];
                }
            }
            $requestCatArr = [];
            //后加 start
            $editLog = 0;
            foreach ($ingredient as $val) {
                if (!empty($val['catId'])) {
                    $editLog += 1;
                }
            }
            if ($editLog <= 0) {
                $catList = M("menus_ingredientcat_relation mr")
                    ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=mr.ingredientCatId")
                    ->where("c.state=0 AND mr.menuId='" . $response['id'] . "'")
                    ->select();
                foreach ($catList as $value) {
                    $catTab->where("id='" . $value['id'] . "'")->save(['state' => '-1']);
                    $relationTab->where("ingredientCatId='" . $value['id'] . "' AND menuId='" . $response['id'] . "'")->save(['state' => '-1']);
                }
            }
            //后加end
            foreach ($ingredient as $key => $val) {
                //新增
                if (empty($val['catId'])) {
                    $catInfo = M("menus_ingredientcat_relation mr")
                        ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=mr.ingredientCatId")
                        ->where("c.catname='" . $val['catname'] . "' AND mr.state=0 AND mr.menuId='" . $response['id'] . "'")
                        ->find();
                    if ($catInfo['id'] > 0) {
                        $apiRet['msg'] = $val['catname'] . ' 食材已经存在';
                        //$rd['msg'] = $val['catname'].' 食材已经存在';
                        return $apiRet;
                    }
                    //写入菜谱分类
                    $add['catname'] = $val['catname'];
                    $add['state'] = 0;
                    $add['addTime'] = date('Y-m-d H:i:s', time());
                    $catId = $catTab->add($add);
                    //$requestCatArr[] = $catId;
                    unset($add);
                    if ($catId) {
                        //写入食材菜谱关系表
                        $relationAdd['menuId'] = $response['id'];
                        $relationAdd['ingredientCatId'] = $catId;
                        $relationAdd['state'] = 0;
                        $relationTab->add($relationAdd);
                        //写入食材商品
                        if (!empty($val['goodsId'])) {
                            $goodsIdStr = implode(',', $val['goodsId']);
                            $ingreAdd['goodsId'] = rtrim($goodsIdStr, ',');
                            $ingreAdd['ingredientCatId'] = $catId;
                            $ingreTab->add($ingreAdd);
                        }
                    }
                } elseif (!empty($val['catId'])) {
                    $requestCatArr[] = $val['catId'];
                    //编辑
                    //修改菜谱分类
                    $add['catname'] = $val['catname'];
                    $catId = $catTab->where("id='" . $val['catId'] . "'")->save($add);
                    unset($add);
                    if ($catId !== false) {
                        //写入食材商品
                        if (!empty($val['goodsId'])) {
                            $goodsIdStr = implode(',', $val['goodsId']);
                            $ingreAdd['goodsId'] = rtrim($goodsIdStr, ',');
                            $ingreAdd['ingredientCatId'] = $val['catId'];
                            M('menus_ingredient')->where("ingredientCatId='" . $val['catId'] . "'")->save($ingreAdd);
                        }
                    }
                }
            }
            if (!empty($requestCatArr)) {
                foreach ($existCatArr as $rv) {
                    if (!in_array($rv, $requestCatArr)) {
                        $relationTab->where("menuId='" . $response['id'] . "' AND ingredientCatId='" . $rv . "'")->save(['state' => -1]);
                        $catTab->where("id='" . $rv . "'")->save(['state' => -1]);
                    }
                }
            }

        } else {
            //后加 删除
            M('menus_ingredientcat_relation')->where("menuId='" . $response['id'] . "'")->save(['state' => '-1']);
        }
        //写入步骤
        $stepTab = M('menus_step');
        $existStep = $stepTab->where("menuId='" . $response['id'] . "' AND state=0")->select();
        $requestStep = [];
        if (!empty($step)) {
            foreach ($step as $cv) {
                if (empty($cv['stepId'])) {
                    $stepAdd['content'] = $cv['content'];
                    $stepAdd['pic'] = $cv['pic'];
                    $stepAdd['menuId'] = $response['id'];
                    $stepAdd['sort'] = $cv['sort'];
                    $stepAdd['addTime'] = date('Y-m-d H:i:s', time());
                    $stepTab->add($stepAdd);
                } elseif (!empty($cv['stepId'])) {
                    $requestStep[] = $cv['stepId'];
                    $stepAdd['content'] = $cv['content'];
                    $stepAdd['pic'] = $cv['pic'];
                    $stepAdd['sort'] = $cv['sort'];
                    $stepTab->where("id='" . $cv['stepId'] . "'")->save($stepAdd);
                }
            }
            if (!empty($requestStep)) {
                foreach ($existStep as $val) {
                    if (!in_array($val['id'], $requestStep)) {
                        $stepTab->where("id='" . $val['id'] . "'")->save(['state' => '-1']);
                    }
                }
            }
        } else {
            //删除
            $stepTab->where("menuId='" . $response['id'] . "'")->save(['state' => '-1']);
        }
        if ($rs !== false) {
            $apiRet['code'] = 0;
            $apiRet['msg'] = '数据操作成功';
        }
        return $apiRet;
    }

    /**
     * 获取指定对象
     */
    public function getInfo($id)
    {
        $menuStepTab = M('menus_step');
        $goodsTab = M('goods');
        $ingredientTab = M('menus_ingredient');
        $info = $this->where("id='" . $id . "'")->find();
        $catList = M('menus_ingredientcat_relation r')
            ->join("LEFT JOIN wst_menus m ON r.menuId=m.id")
            ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=r.ingredientCatId")
            ->field('c.catname,c.id')
            ->where("menuId='" . $id . "'")
            ->select();
        $hiddenCatlist = [];
        $hiddenStepId = [];
        foreach ($catList as $key => &$val) {
            $ingredientInfo = $ingredientTab->where("ingredientCatId='" . $val['id'] . "' AND state=0")->find();
            $goodsIds = $ingredientInfo['goodsId'];
            if (empty($goodsIds)) {
                $goodsIds = '0';
            }
            $goods = $goodsTab->where("goodsId IN($goodsIds)")->select();
            $val['goodsList'] = $goods;
            $hiddenCatlist[$key]['catId'] = $val['id'];
            $hiddenCatlist[$key]['catname'] = $val['catname'];
            $hiddenCatlist[$key]['goodsId'] = explode(',', $ingredientInfo['goodsId']);
        }
        unset($val);
        $stepList = $menuStepTab->where("menuId='" . $id . "' AND state=0")->order('sort DESC')->select();
        foreach ($stepList as $key => $val) {
            $stepList[$key]['stepId'] = $val['id'];
            $hiddenStepId[$key]['stepId'] = $val['id'];
            $hiddenStepId[$key]['content'] = $val['content'];
            $hiddenStepId[$key]['pic'] = $val['pic'];
            $hiddenStepId[$key]['sort'] = $val['sort'];
        }
//        $info['stepList'] = json_encode($stepList);
        $info['stepList'] = (array)$stepList;
//        $info['catList'] = json_encode($catList);
        $info['catList'] = (array)$catList;
//        $info['hiddenCatList'] = json_encode($hiddenCatlist); //用于编辑
        $info['hiddenCatList'] = (array)$hiddenCatlist; //用于编辑
//        $info['hiddenStepId'] = json_encode($hiddenStepId); //用于编辑
        $info['hiddenStepId'] = (array)$hiddenStepId; //用于编辑
        return $info;
    }

    /**
     * 删除
     */
    public function del($response)
    {
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if (!empty($response)) {
            $edit['state'] = -1;
            $rs = M('menus')->where("id='" . $response['id'] . "'")->save($edit);
            if ($rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     *菜单列表 分页
     */
    public function getList($page = 1, $pageDataNum = 15)
    {
        $apiRet['code'] = -1;
        $apiRet['msg'] = '获取数据失败';
        $apiRet['data'] = array();
//        $pageDataNum = 25;
        $mod = M('menus');
        $title = I('title');
        $where = " state=0 ";
        if (!empty($title)) {
            $where .= " AND title LIKE '%" . $title . "%'";
        }
        $count = $mod
            ->where($where)
            ->count();
        $dataok = $mod
            ->where($where)
            ->order("id DESC")
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();
        $pageCount = (int)ceil($count / $pageDataNum);
        $apiRet['data']['list'] = $dataok;
        $apiRet['data']['pageCount'] = $pageCount == 0 ? 1 : $pageCount;
        $apiRet['data']['dataNum'] = !empty($count) ? $count : 0;
        if ($dataok) {
            $apiRet['code'] = 0;
            $apiRet['msg'] = '获取数据成功';
        }
        return $apiRet;
    }

    /*
     * 菜谱详情
     * @param int $menuId
     * */
    public function menuInfo($menuId)
    {
        $apiRet['code'] = -1;
        $apiRet['msg'] = '数据获取失败';
        $apiRet['data'] = array();
        $menuTab = M('menus');
        $menuStepTab = M('menus_step');
        $goodsTab = M('goods');
        $ingredientTab = M('menus_ingredient');
        $info = $menuTab
            ->where("id='" . $menuId . "'")
            ->find();
        $menuTab->where("id='" . $menuId . "'")->setInc('click', 1); //浏览量
        $catList = M('menus_ingredientcat_relation r')
            ->join("LEFT JOIN wst_menus m ON r.menuId=m.id")
            ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=r.ingredientCatId")
            ->field('c.catname,c.id')
            ->where("menuId='" . $menuId . "' AND r.state=0")
            ->select();
        foreach ($catList as $key => &$val) {
            $ingredientInfo = $ingredientTab->where("ingredientCatId='" . $val['id'] . "' AND state=0")->find();
            $goodsIds = trim($ingredientInfo['goodsId'], ',');
            if (empty($goodsIds)) {
                $goodsIds = '0';
            }
            $goods = $goodsTab->where("goodsId IN($goodsIds)")->select();
            $val['goodsList'] = rankGoodsPrice($goods);
        }
        unset($val);
        $stepList = $menuStepTab->where("menuId='" . $menuId . "' AND state=0")->order('sort ASC')->select();
        $info['stepList'] = $stepList;
        $info['catList'] = $catList;
        if ($info) {
            $apiRet['code'] = 0;
            $apiRet['msg'] = '数据获取成功';
            $apiRet['data'] = $info;
        }
        return $apiRet;
    }

    /**
     * 修改菜谱列表状态为显示/隐藏
     */
    public function editiIsShow()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if (empty($id)) return $rd;
        $isShow = I('isShow', 0, 'intval');
        $rs = M('menus')->where(array('id' => $id))->save(array('isShow' => $isShow));
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }
}

?>