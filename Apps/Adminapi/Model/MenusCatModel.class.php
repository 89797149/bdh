<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 菜谱分类
 */
class MenusCatModel extends BaseModel
{
    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $param = I();
        $where = " WHERE state=0 ";
        if (!empty($param['catname'])) {
            $where .= " AND catname='" . $param['catname'] . "'";
        }
        $sql = "SELECT id,catname,state,pic,addTime FROM __PREFIX__menus_cat $where ORDER BY id DESC";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     * 新增
     */
    public function insert($response)
    {
        $rd = ['code' => -1, 'msg' => '添加失败', 'data' => []];
        $catInfo = $this->where("catname='" . $response['catname'] . "' and state=0")->find();
        if ($catInfo) {
            $rd['msg'] = '该分类已经存在';
            return $rd;
        }
        if (!empty($response)) {
            $rs = $this->add($response);
            if ($rs) {
                $rd['code'] = 0;
                $rd['msg'] = '添加成功';
            }
        }
        return $rd;
    }

    /**
     * 修改
     */
    public function edit($response)
    {
        $rd = ['code' => -1, 'msg' => '修改失败', 'data' => []];
        if (!empty($response)) {
            $rs = $this->where("id='" . $response['id'] . "'")->save($response);
            if ($rs !== false) {
                $rd['code'] = 0;
                $rd['msg'] = '修改成功';
            }
        }
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function getInfo($id)
    {
        return $this->where("id='" . $id . "'")->find();
    }

    /**
     * 获取指定对象
     */
    public function getList($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 删除
     */
    public function del($response)
    {
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if (!empty($response)) {
            $edit['state'] = -1;
            $rs = $this->where("id='" . $response['id'] . "'")->save($edit);
            if ($rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * 获取分类列表
     * $params
     */
    public function getCatList($params)
    {
        $tab = M('menus_cat');
        $whereFind = [];
        $whereFind['state'] = 0;
        $whereFind['catname'] = function () use ($params) {
            if (empty($params['catname'])) {
                return null;
            }
            return ['like', "%{$params['catname']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        $list = $tab->where($whereFind)->order('id desc')->select();
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $apiRet = arrayPage($list, $page, $pageSize);
        return returnData($apiRet);
    }
}