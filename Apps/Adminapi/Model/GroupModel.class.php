<?php
namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 团长
 */
class GroupModel extends BaseModel {
    /**
     * 分页列表
     */
    public function queryByPage($page=1,$pageSize=15){
        $param = I();
        $where = " WHERE status IN(0,1) ";
        if(!empty($param['name'])){
            $where .= " AND name='".$param['name']."'";
        }
        $sql = "SELECT * FROM __PREFIX__group $where ORDER BY id DESC";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        return $rs;
    }


    /**
     * 获取详情
     * @param array $request
     */
    public function getInfo($request){
        $id = $request['id'];
        $groupTab = M('group');
        $info = $groupTab->where("id='".$id."'")->find();
        return $info;
    }

    /**
     * 修改
     */
    public function edit($request){
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $groupTab = M('group');
        $editRest = $groupTab->where("id='".$request['id']."'")->save($request);
        if($editRest !== false){
            $rs['code'] = 0;
            $rs['msg'] = '操作成功';
        }
        return $rs;
    }

    /**
     * 删除
     */
    public function del($response){
        $rd = ['code' => -1,'msg'=>'操作失败','data'=>[]];
        if(!empty($response)){
            $edit['status'] = -1;
            $rs = $this->where("id='".$response['id']."'")->save($edit);
            if($rs){
                $rd['code']= 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }
}
?>