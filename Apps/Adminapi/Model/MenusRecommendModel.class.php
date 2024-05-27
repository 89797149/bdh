<?php
namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 每日推荐
 */
class MenusRecommendModel extends BaseModel {
    /**
     * 分页列表
     */
    public function queryByPage($page=1,$pageSize=15){
        $param = I();
        $where = " WHERE state=0 ";
        if(!empty($param['title'])){
            $where .= " AND title like '%".$param['title']."%'";
        }
        $sql = "SELECT id,title,addTime FROM __PREFIX__menus_recommend $where ORDER BY id DESC";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        return $rs;
    }

    /**
     * 新增
     */
    public function insert($response){
        $rd = ['code' => -1,'msg' => '添加失败','data'=>[]];
        $recommnedInfo = $this->where("title='".$response['title']."'")->find();
        if($recommnedInfo){
            $rd['msg'] = '该标题已经存在';
            return $rd;
        }
        if(!empty($response)){
            $rs = $this->add($response);
            if($rs){
                $rd['code']= 0;
                $rd['msg'] = '添加成功';
            }
        }
        return $rd;
    }
    /**
     * 修改
     */
    public function edit($response){
        $rd = ['code' => -1,'msg'=>'操作失败','data'=>[]];
        if(!empty($response)){
            $rs = $this->where("id='".$response['id']."'")->save($response);
            if($rs !== false){
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function getInfo($id){
        $info = $this->where("id='".$id."'")->find();
        $menuId = 0;
        if(!empty($info['menuId'])){
            $menuId = $info['menuId'];
        }
        $menus = M('menus')->where("id IN($menuId)")->select();
        $menuId = [];
        foreach ($menus as $key=>$val){
            $menuId[] = $val['id'];
        }
        $info['menus'] = json_encode($menus);
        $info['menuId'] = json_encode($menuId);
        return $info;
    }

    /**
     * 删除
     */
    public function del($response){
        $rd = ['code' => -1,'msg'=>'操作失败','data'=>[]];
        if(!empty($response)){
            $edit['state'] = -1;
            $rs = $this->where("id='".$response['id']."'")->save($edit);
            if($rs){
                $rd['code']= 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }
};
?>