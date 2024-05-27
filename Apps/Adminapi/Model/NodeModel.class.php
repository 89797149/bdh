<?php
namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商家节点控制器
 */
class NodeModel extends BaseModel {

    /**
     * 获取
     */
    public function getlist($parameter=array(),&$msg=''){
        $where = array(
            '1'=>'1',
        );
        !empty($parameter['name'])?$where['n.name']=$parameter['name']:false;
        !empty($parameter['id'])?$where['n.id']=$parameter['id']:false;
        //按角色搜索
        if($parameter['rid']){
            //获取角色id
            $m = M('role_node');
            $rnList = $m->where('rid='.(int)$parameter['rid'].' and shopId='.$parameter['shopId'])->select();
            if(!$rnList){
                return array();
            }
            $nid_arr = array_get_column($rnList,'nid');
            if(!$nid_arr){
                return array();
            }
            $where['id|in'] = $nid_arr;
        }
        //END
        $where_str = arrChangeSqlStr($where);
        if(isset($parameter['rid']) && $parameter['rid'] > 0){

            $idStr = 0;
            if(count($nid_arr) > 0){
                $idStr = implode(',',$nid_arr);
            }
            $where_str = " n.id in($idStr)";

        }
        if(!empty($parameter['catname'])){
            $where_str .= " and c.catname like '%".$parameter['catname']."%' ";
        }
        $sql = "SELECT n.*,c.catname FROM __PREFIX__node n left join __PREFIX__node_cat  c on c.id=n.catid WHERE {$where_str}";
        $list = $this->pageQuery($sql,$parameter['page'],$parameter['pageSize']);
        return $list;
    }

    /**
     * 获取
     */
    public function getlistNew($parameter=array(),&$msg=''){
        $where = array(
            '1'=>'1',
        );
        !empty($parameter['name'])?$where['n.name']=$parameter['name']:false;
        !empty($parameter['id'])?$where['n.id']=$parameter['id']:false;
        //按角色搜索
        if($parameter['rid']){
            //获取角色id
            $m = M('role_node');
            $rnList = $m->where('rid='.(int)$parameter['rid'].' and shopId='.$parameter['shopId'])->select();
            if(!$rnList){
                return array();
            }
            $nid_arr = array_get_column($rnList,'nid');
            if(!$nid_arr){
                return array();
            }
            $where['id|in'] = $nid_arr;
        }
        //END
        $where_str = arrChangeSqlStr($where);
        if(isset($parameter['rid']) && $parameter['rid'] > 0){

            $idStr = 0;
            if(count($nid_arr) > 0){
                $idStr = implode(',',$nid_arr);
            }
            $where_str = " n.id in($idStr)";

        }
        if(!empty($parameter['catname'])){
            $where_str .= " and c.catname like '%".$parameter['catname']."%' ";
        }
        $sql = "SELECT n.*,c.catname FROM __PREFIX__node n left join __PREFIX__node_cat  c on c.id=n.catid WHERE {$where_str}";
        $list = $this->query($sql);

        //后加分类
        $catList = M('node_cat')->where(['dataFlag'=>1])->select();
        $numstart = count($catList);
        $numend = count($catList);
        foreach ($catList as $key=>$val){
            $numend++;
            $catList[$key]['nodeList'] = [];
            $catList[$key]['catid'] = $catList[$key]['id'];
            $catList[$key]['id'] = $numstart - $numend;
        }
        foreach ($catList as $key=>&$val){
            foreach ($list as $v){
                if($val['catid'] == $v['catid']){
                    $val['nodeList'][] = $v;
                }
            }
        }
        unset($val);
        return $catList;
    }

    /**
     * 获取单个节点信息
     */
    public function getInfo($parameter=array(),&$msg=''){
        if(!$parameter['id']){
            return array();
        }
        $m = M('node');
        $row = $m->where('id='.$parameter['id'])->find();
        return $row;
    }

    /**
     * 添加
     */
    public function addnode($parameter=array(),&$msg=''){
        #检测
        if(!$parameter){
            $msg = '数据为空';
            return false;
        }
        if(isset($parameter['status']) && !in_array($parameter['status'],array(-1,1))){
            $msg = '状态不在可选范围';
            return false;
        }
        #保存
        $data = array(
            'name'=>$parameter['name'],
            'catid'=>$parameter['catid'],
            'mname'=>$parameter['mname'],
            'aname'=>$parameter['aname'],
            'status'=>$parameter['status']?$parameter['status']:1,
        );
        $rs = M('node')->add($data);
        return $rs;
    }

    /**
     * 编辑用户
     */
    public function edit($parameter=array(),&$msg=''){
        //检测
        if(isset($parameter['status']) && !in_array($parameter['status'],array(-1,1))){
            $msg = '状态不在可选范围';
            return false;
        }
        //保存
        $saveData = array();
        isset($parameter['name'])?$saveData['name']=$parameter['name']:false;
        isset($parameter['mname'])?$saveData['mname']=$parameter['mname']:false;
        isset($parameter['aname'])?$saveData['aname']=$parameter['aname']:false;
        isset($parameter['status'])?$saveData['status']=$parameter['status']:false;
        isset($parameter['catid'])?$saveData['catid']=$parameter['catid']:false;
        $rs = M('node')->where(' id='.$parameter['id'])->save($saveData);
        if($rs !== false){
            $rs = true;
        }
        return $rs;
    }

    //
    /**
     * 删除用户
     */
    public function del($parameter=array(),&$msg=''){
//        $m = M('role_node');
//        $resp = $m->where(' nid='.$parameter['id'])->select();//存在
//        if($resp){
//            $msg='已有角色选择该节点，请先更改角色节点再进行删除操作';
//            return false;
//        }
        $rs = M('node')->where(' id='.$parameter['id'])->delete();
        return $rs;
    }


    /**
     * 获取节点分类
     */
    public function getCatIndex($param){
        $where = " where c.dataFlag=1 ";
        if(!empty($param['catname'])){
            $where .= " and c.catname like '%".$param['catname']."%' ";
        }
        $sql = "select c.id,c.catname,c.icon from __PREFIX__node_cat c ".$where." order by c.id desc ";
        $list = $this->pageQuery($sql,$param['page'],$param['pageSize']);
        return $list;
    }

    /**
     * 获取节点分类(无分页)
     */
    public function getCatListAll(){
        $where = " where c.dataFlag=1 ";
        if(!empty($param['catname'])){
            $where .= " and c.catname like '%".$param['catname']."%' ";
        }
        $sql = "select c.id,c.catname from __PREFIX__node_cat c ".$where." order by c.id desc ";
        $list = $this->query($sql);
        return $list;
    }

    /**
     * 获取单个节点分类信息
     */
    public function getCatInfo($param){
        if(!$param['id']){
            return array();
        }
        $m = M('node_cat');
        $row = $m->where('id='.$param['id'])->find();
        return $row;
    }

    /**
     * 添加节点分类
     */
    public function catAdd($param){
        $reponse = [
            "errorCode" => -1,
            "errorMsg" => '添加失败',
        ];
        $tab = M('node_cat');
        if(!empty($param['catname'])){
            $catInfo = $tab->where(["catname"=>$param['catname'],'dataFlag'=>1])->find();
            if($catInfo){
                $reponse['errorMsg'] = $catInfo['catname'].'已经存在,不能重复添加';
                return $reponse;
            }
        }
        $insert['catname'] = $param['catname'];
        $insert['icon'] = $param['icon'];
        $insert['addTime'] = date('Y-m-d H:i:s',time());
        $rs = $tab->add($insert);
        if($rs){
            $reponse = [
                "errorCode" => 1,
                "errorMsg" => '添加成功',
            ];
        }
        return $reponse;
    }

    /**
     * 编辑节点分类
     */
    public function catEdit($param){
        $reponse = [
            "errorCode" => -1,
            "errorMsg" => '编辑失败',
        ];
        $tab = M('node_cat');
        if(!empty($param['catname'])){
            $catInfo = $tab->where(["catname"=>$param['catname'],'dataFlag'=>1])->find();
            if($catInfo && $catInfo['id'] != $param['id']){
                $reponse['errorMsg'] = $catInfo['catname'].'已经存在,不能重复添加';
                return $reponse;
            }
        }
        $where = [];
        $where['id'] = $param['id'];
        $edit['catname'] = $param['catname'];
        $edit['icon'] = $param['icon'];
        $rs = $tab->where($where)->save($edit);
        if($rs !== false){
            $reponse = [
                "errorCode" => 1,
                "errorMsg" => '编辑成功',
            ];
        }
        return $reponse;
    }

    /**
     * 删除节点分类
     */
    public function catDel($param){
        $reponse = [
            "errorCode" => -1,
            "errorMsg" => '删除失败',
        ];
        $tab = M('node_cat');
        if(!empty($param['id'])){
            $nodeCount = M('node')->where(['catid'=>$param['id']])->count();
            if($nodeCount > 0 ){
                $reponse['errorMsg'] = '删除失败,该分类下存在节点数据';
                return $reponse;
            }
        }
        $where = [];
        $where['id'] = $param['id'];
        $edit['dataFlag'] = -1;
        $rs = $tab->where($where)->save($edit);
        if($rs){
            $reponse = [
                "errorCode" => 1,
                "errorMsg" => '删除成功',
            ];
        }
        return $reponse;
    }



}