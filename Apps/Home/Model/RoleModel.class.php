<?php
namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员服务类
 */
class RoleModel extends BaseModel {

    /**
     * 获取
     */
    public function getlist($parameter=array(),&$msg=''){
        $where = array(
            'shopId'=>$parameter['shopId'],
        );
        isset($parameter['name'])?$where['name']=$parameter['name']:false;
        //用户搜索
        if($parameter['uid']){
            //获取角色id
            $m = M('user_role');
            $ruList = $m->where('uid='.(int)$parameter['uid'].' and shopId='.$parameter['shopId'])->select();
            if(!$ruList){
                return array();
            }
            $rid_arr = array_get_column($ruList,'rid');
            if(!$rid_arr){
                return array();
            }
            $where['id|in'] = $rid_arr;
        }
        //END
        $where_str = arrChangeSqlStr($where);
        if(count($rid_arr) > 0){
            $idStr = 0;
            $idStr = implode(',',$rid_arr);
            $where_str = " id in($idStr)";
        }
        $sql = "SELECT * FROM __PREFIX__role 
				WHERE {$where_str} ";
        $list = $this->pageQuery($sql,$parameter['page'],$parameter['pageSize']);
        foreach ($list['root'] as $k=>$v){
            $param = [];
            $param['rid'] = $v['rid'];
            $param['shopId'] = $parameter['shopId'];
            $ruleCount = $this->getUserRole($param);
            $list['root'][$k]['ruleCount'] = (int)$ruleCount;
        }
        return $list;
    }

    /**
     * @param $param
     * @return mixed
     * 获取职员角色数量
     */
    public function getUserRole($param){
        $where = "wur.rid = {$param['rid']} and wur.shopId = {$param['shopId']} and (wu.status = -2 or wu.status = 0)";
        $userRoleModel = M('user_role wur');
        $ruleCount = $userRoleModel
            ->join('left join wst_user wu on wu.id = wur.uid')
            ->where($where)
            ->count();
        return $ruleCount;
    }

    /**
     * 添加
     */
    public function addrole($parameter=array(),&$msg=''){
        #检测
        if(!$parameter){
            $msg = '数据为空';
            return returnData(false, -1, 'error', '操作失败');
        }
        if(isset($parameter['status']) && !in_array($parameter['status'],array(-1,1))){
            $msg = '状态不在可选范围';
            return returnData(false, -1, 'error', '操作失败');
        }
        #保存
        M()->startTrans();//开启事物
        $data = array(
            'name'=>$parameter['name'],
            'remark'=>$parameter['remark'],
            'status'=>$parameter['status']?$parameter['status']:1,
            'shopId'=>$parameter['shopId'],
            'createTime'=> date("Y-m-d H:i:s"),
        );
        $rs = $this->add($data);

        #节点添加
        $nflag = true;
        if(isset($parameter['node']) && $rs){
            $nflag = $this->saveRoleNode($parameter['node'],$rs,$parameter['shopId']);
        }
        if(!$nflag){
            $msg='节点添加失败';
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * @param $param
     * @return mixed
     * 编辑角色状态
     */
    public function upRoleStatus($param){
        $roleModel = M('role');
        $res = $roleModel->where(['shopId'=>$param['shopId'],'id'=>$param['id']])->save(['status'=>$param['status']]);
        if(empty($res)){
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * 编辑用户
     */
    public function edit($parameter=array(),&$msg=''){
        //检测
        if(isset($parameter['status']) && !in_array($parameter['status'],array(-1,1))){
            $msg = '状态不在可选范围';
            return returnData(false, -1, 'error', '操作失败');
        }
        //保存
        M()->startTrans();//开启事物
        $saveData = array();
        isset($parameter['name'])?$saveData['name']=$parameter['name']:false;
        isset($parameter['remark'])?$saveData['remark']=$parameter['remark']:false;
        isset($parameter['status'])?$saveData['status']=$parameter['status']:false;

        $rs = $this->where("shopId=".$parameter['shopId'].' and id='.$parameter['id'])->save($saveData);

        //添加节点
        if(isset($parameter['node']) && $parameter['id'] && $parameter['shopId']){
            $rs = $this->saveRoleNode($parameter['node'],$parameter['id'],$parameter['shopId']);
            //清除缓存
            $this->clearUserCache($parameter);
            //END
        }
        if(!$rs){
            $msg='节点添加失败';
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }

        M()->commit();
        return returnData(true);
    }

    //角色节点保存
    public function saveRoleNode($nid_str='',$rid='',$shopId='')
    {
        //检测
        $m = M('role_node');
        if(!$nid_str || !$rid || !$shopId){
            if(!$nid_str && $rid && $shopId){//没有勾选节点，清空
                $m->where('rid='.(int)$rid.' and shopId='.$shopId)->delete();
                return true;
            }
            return false;
        }
        //删除之前
        $m->where('rid='.(int)$rid.' and shopId='.$shopId)->delete();
        //保存现在
        $nid_arr = array_unique(explode(',',$nid_str));
        foreach ($nid_arr as $key => $nid) {
            if(!$nid){
                continue;
            }
            $save_data = array(
                'rid' => (int)$rid,
                'shopId' => $shopId,
                'nid' => $nid,
            );
            $m->add($save_data);
        }
        return true;
    }

    //
    /**
     * 删除角色
     */
    public function del($parameter=array(),&$msg=''){
        //检测是否存在绑定
//        $m = M('user_role');
//        $resp = $m->where("shopId=".$parameter['shopId'].' and rid='.$parameter['id'])->select();//存在
//        if($resp){
//            $msg='已有用户选择该角色，请先更改用户角色再进行删除操作';
//            return false;
//        }
        $rs = $this->where("shopId=".$parameter['shopId'].' and id='.$parameter['id'])->delete();
        if(empty($rs)){
            return returnData(false, -1, 'error', '操作失败');
        }else{
            return returnData(true);
        }
    }


    //修改角色节点时清理用户权限缓存
    public function clearUserCache($parameter=array(),&$msg=''){
        if(!$parameter['shopId'] || !$parameter['id']){
            return;
        }
        //数据库
        $m = M('user_role');
        //获取角色id
        $ruList = $m->where('rid='.(int)$parameter['id'].' and shopId='.$parameter['shopId'])->select();
        if(!$ruList){
            return;
        }
        foreach ($ruList as $key => $value) {
            S("merchatapi.shopid_{$parameter['shopId']}.userid_{$value['uid']}",null);
        }

    }

    /**
     * @param $param
     * @return mixed
     * 获取角色详情
     */
    public function getRoleInfo($param){
        $roleModel = M('role');
        $rs = $roleModel->where(['shopId'=>$param['shopId'],'id'=>$param['id']])->find();
        return $rs;
    }

}