<?php

namespace Adminapi\Model;
use App\Modules\AuthRule\AuthRuleServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 平台节点控制器
 */
class PlatformNodeModel extends BaseModel
{

    /**
     * 获取
     */
    public function getlist($parameter = array(), &$msg = '')
    {
        $where = array(
            '1' => '1',
        );
        //!empty($parameter['name'])?$where['n.name']=$parameter['name']:false;
        !empty($parameter['id']) ? $where['n.id'] = $parameter['id'] : false;
        //按角色搜索
        if ($parameter['rid']) {
            //获取角色id
            $m = M('role_platform_node');
            $rnList = $m->where('rid=' . (int)$parameter['rid'] . ' and shopId=' . $parameter['shopId'])->select();
            if (!$rnList) {
                return array();
            }
            $nid_arr = array_get_column($rnList, 'nid');
            if (!$nid_arr) {
                return array();
            }
            $where['id|in'] = $nid_arr;
        }
        //END
        $where_str = arrChangeSqlStr($where);
        if (isset($parameter['rid']) && $parameter['rid'] > 0) {

            $idStr = 0;
            if (count($nid_arr) > 0) {
                $idStr = implode(',', $nid_arr);
            }
            $where_str = " n.id in($idStr)";

        }
        if (!empty($parameter['catname'])) {
            $where_str .= " and c.catname like '%" . $parameter['catname'] . "%' ";
        }
        if (!empty($parameter['name'])) {
            $where_str .= " and n.name like '%" . $parameter['name'] . "%' ";
        }
        $sql = "SELECT n.*,c.catname FROM __PREFIX__platform_node n left join __PREFIX__platform_node_cat  c on c.id=n.catid WHERE {$where_str}";
        $list = $this->pageQuery($sql, $parameter['page'], $parameter['pageSize']);
        return $list;
    }

    /**
     * 获取
     */
    public function getPlatformNodeList($parameter = array(), &$msg = '')
    {
        $where = array(
            '1' => '1',
        );
        !empty($parameter['name']) ? $where['n.name'] = $parameter['name'] : false;
        !empty($parameter['id']) ? $where['n.id'] = $parameter['id'] : false;
        //按角色搜索
        if ($parameter['rid']) {
            //获取角色id
            $m = M('role_platform_node');
            $rnList = $m->where('rid=' . (int)$parameter['rid'] . ' and shopId=' . $parameter['shopId'])->select();
            if (!$rnList) {
                return array();
            }
            $nid_arr = array_get_column($rnList, 'nid');
            if (!$nid_arr) {
                return array();
            }
            $where['id|in'] = $nid_arr;
        }
        //END
        $where_str = arrChangeSqlStr($where);
        if (isset($parameter['rid']) && $parameter['rid'] > 0) {

            $idStr = 0;
            if (count($nid_arr) > 0) {
                $idStr = implode(',', $nid_arr);
            }
            $where_str = " n.id in($idStr)";

        }
        if (!empty($parameter['catname'])) {
            $where_str .= " and c.catname like '%" . $parameter['catname'] . "%' ";
        }
        $sql = "SELECT n.*,c.catname FROM __PREFIX__platform_node n left join __PREFIX__platform_node_cat  c on c.id=n.catid WHERE {$where_str}";
        $list = $this->query($sql);

        //后加分类
        $catList = M('platform_node_cat')->where(['dataFlag' => 1])->select();
        $numstart = count($catList);
        $numend = count($catList);
        foreach ($catList as $key => $val) {
            $numend++;
            $catList[$key]['nodeList'] = [];
            $catList[$key]['catid'] = $catList[$key]['id'];
            $catList[$key]['id'] = $numstart - $numend;
        }
        foreach ($catList as $key => &$val) {
            foreach ($list as $v) {
                if ($val['catid'] == $v['catid']) {
                    $val['nodeList'][] = $v;
                }
            }
        }
        unset($val);
        return $catList;
    }

    /**
     * @param $param
     * @return array
     * 获取权限列表【总后台】
     */
    public function getTableAuthRuleList($param){
        $params = [];
        $params['staffNid'] = [];
        if(!empty($param['roleId'])){
            $res = M('roles')->where(['roleId'=>$param['roleId'],'roleFlag'=>1])->find();
            if(empty($res)){
                return [];
            }
            $staffNid = array_unique(explode(",",$res['grant']));
            $params['staffNid'] = $staffNid;
        }
        $params['module_type'] = 1;
        $data = getTablePrivilege($params);
        return (array)$data;
    }

    /**
     * 获取单个节点信息
     */
    public function getInfo($parameter = array(), &$msg = '')
    {
        if (!$parameter['id']) {
            return array();
        }
        $m = M('platform_node');
        $row = $m->where('id=' . $parameter['id'])->find();
        return $row;
    }

    /**
     * 添加
     */
    public function addnode($parameter = array(), &$msg = '')
    {
        #检测
        if (!$parameter) {
            $msg = '数据为空';
            return false;
        }
        if (isset($parameter['status']) && !in_array($parameter['status'], array(-1, 1))) {
            $msg = '状态不在可选范围';
            return false;
        }
        #保存
        $data = array(
            'name' => $parameter['name'],
            'catid' => $parameter['catid'],
            'mname' => $parameter['mname'],
            'aname' => $parameter['aname'],
            'status' => $parameter['status'] ? $parameter['status'] : 1,
        );
        $rs = M('platform_node')->add($data);
        return $rs;
    }

    /**
     * 编辑用户
     */
    public function edit($parameter = array(), &$msg = '')
    {
        //检测
        if (isset($parameter['status']) && !in_array($parameter['status'], array(-1, 1))) {
            $msg = '状态不在可选范围';
            return false;
        }
        //保存
        $saveData = array();
        isset($parameter['name']) ? $saveData['name'] = $parameter['name'] : false;
        isset($parameter['mname']) ? $saveData['mname'] = $parameter['mname'] : false;
        isset($parameter['aname']) ? $saveData['aname'] = $parameter['aname'] : false;
        isset($parameter['status']) ? $saveData['status'] = $parameter['status'] : false;
        isset($parameter['catid']) ? $saveData['catid'] = $parameter['catid'] : false;
        $rs = M('platform_node')->where(' id=' . $parameter['id'])->save($saveData);
        if ($rs !== false) {
            $rs = true;
        }
        return $rs;
    }

    //

    /**
     * 删除用户
     */
    public function del($parameter = array(), &$msg = '')
    {
//        $m = M('role_platform_node');
//        $resp = $m->where(' nid='.$parameter['id'])->select();//存在
//        if($resp){
//            $msg='已有角色选择该节点，请先更改角色节点再进行删除操作';
//            return false;
//        }
        $rs = M('platform_node')->where(' id=' . $parameter['id'])->delete();
        return $rs;
    }


    /**
     * 获取节点分类
     */
    public function getCatIndex($param)
    {
        $where = " where c.dataFlag=1 ";
        if (!empty($param['catname'])) {
            $where .= " and c.catname like '%" . $param['catname'] . "%' ";
        }
        $sql = "select c.id,c.catname from __PREFIX__platform_node_cat c " . $where . " order by c.id desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);
        return $list;
    }

    /**
     * 获取节点分类(无分页)
     */
    public function getCatListAll()
    {
        $where = " where c.dataFlag=1 ";
        if (!empty($param['catname'])) {
            $where .= " and c.catname like '%" . $param['catname'] . "%' ";
        }
        $sql = "select c.id,c.catname from __PREFIX__platform_node_cat c " . $where . " order by c.id desc ";
        $list = $this->query($sql);
        return $list;
    }

    /**
     * 获取单个节点分类信息
     */
    public function getCatInfo($param)
    {
        if (!$param['id']) {
            return array();
        }
        $m = M('platform_node_cat');
        $row = $m->where('id=' . $param['id'])->find();
        return $row;
    }

    /**
     * 添加节点分类
     */
    public function catAdd($param)
    {
        $reponse = [
            "errorCode" => -1,
            "errorMsg" => '添加失败',
        ];
        $tab = M('platform_node_cat');
        if (!empty($param['catname'])) {
            $catInfo = $tab->where(["catname" => $param['catname'], 'dataFlag' => 1])->find();
            if ($catInfo) {
                $reponse['errorMsg'] = $catInfo['catname'] . '已经存在,不能重复添加';
                return $reponse;
            }
        }

        $insert['catname'] = $param['catname'];
        $insert['addTime'] = date('Y-m-d H:i:s', time());
        $rs = $tab->add($insert);
        if ($rs) {
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
    public function catEdit($param)
    {
        $reponse = [
            "errorCode" => -1,
            "errorMsg" => '编辑失败',
        ];
        $tab = M('platform_node_cat');
        if (!empty($param['catname'])) {
            $catInfo = $tab->where(["catname" => $param['catname'], 'dataFlag' => 1])->find();
            if ($catInfo && $catInfo['id'] != $param['id']) {
                $reponse['errorMsg'] = $catInfo['catname'] . '已经存在,不能重复添加';
                return $reponse;
            }
        }
        $where = [];
        $where['id'] = $param['id'];
        $edit['catname'] = $param['catname'];
        $rs = $tab->where($where)->save($edit);
        if ($rs !== false) {
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
    public function catDel($param)
    {
        $reponse = [
            "errorCode" => -1,
            "errorMsg" => '删除失败',
        ];
        $tab = M('platform_node_cat');
        if (!empty($param['id'])) {
            $nodeCount = M('platform_node')->where(['catid' => $param['id']])->count();
            if ($nodeCount > 0) {
                $reponse['errorMsg'] = '删除失败,该分类下存在节点数据';
                return $reponse;
            }
        }
        $where = [];
        $where['id'] = $param['id'];
        $edit['dataFlag'] = -1;
        $rs = $tab->where($where)->save($edit);
        if ($rs) {
            $reponse = [
                "errorCode" => 1,
                "errorMsg" => '删除成功',
            ];
        }
        return $reponse;
    }

    /**
     * @param $params
     * @return mixed
     * 新增菜单节点
     */
    public function addPrivilege($params)
    {
        $res = M('auth_rule')->add($params);
        return $res;
    }

    /**
     * @param $params
     * @return mixed
     * 编辑菜单节点
     */
    public function updatePrivilege($params)
    {
        $authRuleServiceModule = new AuthRuleServiceModule();
        $getAuthRuleInfo = $authRuleServiceModule->getAuthRuleInfo($params['id']);
        if(empty($getAuthRuleInfo['data'])){
            return returnData(null, -1, 'error', '暂无相关数据');
        }
        $editAuthRuleInfo = $authRuleServiceModule->editAuthRuleInfo($params);
        if(empty($editAuthRuleInfo['data'])){
            return returnData(null, -1, 'error', '暂无数据变更');
        }
        return returnData(true);
    }

    /**
     * @param $Id
     * @return mixed
     * 验证菜单第三级是否存在
     */
    public function verifyPrivilege($Id)
    {
        $authRuleModel = M('auth_rule');
        $res = $authRuleModel->where(['id' => $Id])->find();
        if (!empty($res)) {
            $oneInfo = $authRuleModel->where(['pid' => $res['id']])->select();
            if (!empty($oneInfo)) {
                foreach ($oneInfo as $v) {
                    $twoInfo = $authRuleModel->where(['pid' => $v['id']])->select();
                    if (!empty($twoInfo)) {
                        $data = returnData(null, -1, 'error', '第三级存在不可更改', '参数有误');
                        return $data;
                    }
                }
            }
            $data = returnData([]);
        } else {
            $data = returnData(null, -1, 'error', '操作失败', '参数有误');
        }
        return $data;
    }

    /**
     * @param $params
     * @return mixed
     * 编辑节点排序
     */
    public function editPrivilegeWeigh($params)
    {
        $authRuleServiceModule = new AuthRuleServiceModule();
        $getAuthRuleInfo = $authRuleServiceModule->getAuthRuleInfo($params['id']);
        if(empty($getAuthRuleInfo['data'])){
            return returnData(null, -1, 'error', '暂无相关数据');
        }
        $authRuleServiceModule->editAuthRuleInfo($params);

        return returnData(true);
    }

    /**
     * @param $Id
     * @return mixed
     * 获取菜单节点详情
     */
    public function getPrivilegeInfo($Id){
        $authRuleServiceModule = new AuthRuleServiceModule();
        $getAuthRuleInfo = $authRuleServiceModule->getAuthRuleInfo($Id);
        $res = $getAuthRuleInfo['data'];
        if(empty($res)){
            return returnData(null, -1, 'error', '暂无相关数据', '参数有误');
        }
        if($res['pid'] == 0){
            $res['lastTitle'] = "主类目";
        }else{
            $res['lastTitle'] = $authRuleServiceModule->getAuthRuleInfo($res['pid'])['data']['title'];
        }
        return returnData($res);
    }

    /**
     * @param $Id
     * @return mixed
     * 删除菜单节点
     */
    public function delPrivilege($Id)
    {
        $authRuleServiceModule = new AuthRuleServiceModule();
        $getAuthRuleInfo = $authRuleServiceModule->getAuthRuleInfo($Id);
        if(empty($getAuthRuleInfo['data'])){
            return returnData(null, -1, 'error', '暂无相关数据', '参数有误');
        }
        $authRuleModel = M('auth_rule');
        $res = $authRuleModel->where(['pid' => $Id])->count();
        if ($res > 0) {
            $data = returnData(null, -1, 'error', '请查看是否存在下级', '参数有误');
            return $data;
        }
        $rest = $authRuleModel->where(['id' => $Id])->delete();
        return $rest;
    }

    /**
     * @param $moduleType
     * @return mixed
     * 单独获取菜单列表包括数量
     */
    public function getPrivilegeListCount($moduleType)
    {
        //获取第一级分类
        $sql = "select id,pid,title from __PREFIX__auth_rule where module_type = {$moduleType} and pid =0 order by weigh asc";
        $rs1 = $this->query($sql);
        if(count($rs1)>0){
            $ids = array();
            foreach ($rs1 as $key =>$v){
                $ids[] = $v['id'];
            }

            //获取第二级分类
            $sql = "select id,pid,title from __PREFIX__auth_rule where module_type = {$moduleType} and pid in (".implode(',',$ids).")  order by weigh asc";
            $rs2 = $this->query($sql);
            if(count($rs2)>0){
                $ids = array();
                foreach ($rs2 as $key =>$v){
                    $ids[] = $v['id'];
                }
                $tmpArr = array();
                foreach ($rs2 as $key =>$v){
                    $tmpArr[$v['pid']][] = $v;
                }
            }

            //把二季归类到第一级下
            foreach ($rs1 as $key =>$v){
                $rs1[$key]['children'] = $tmpArr[$v['id']];
                $rs1[$key]['childNum'] = count($tmpArr[$v['id']]);
            }
        }
        $res = [];
        $res['list'] = $rs1;
        $res['total'] = (int)count($rs1);
        return $res;
    }

    /**
     * @param $moduleType
     * @return mixed
     * 获取菜单列表(树形)
     */
    public function getPrivilegeList($moduleType)
    {
        //获取第一级分类
        $sql = "select * from __PREFIX__auth_rule where module_type = {$moduleType} and pid =0 order by weigh asc";
        $rs1 = $this->query($sql);
        if(count($rs1)>0){
            $ids = array();
            foreach ($rs1 as $key =>$v){
                $ids[] = $v['id'];
            }

            //获取第二级分类
            $sql = "select * from __PREFIX__auth_rule where module_type = {$moduleType} and pid in (".implode(',',$ids).")  order by weigh asc";
            $rs2 = $this->query($sql);
            if(count($rs2)>0){
                $ids = array();
                foreach ($rs2 as $key =>$v){
                    $ids[] = $v['id'];
                }
                //获取第三级分类
                $sql = "select * from __PREFIX__auth_rule where module_type = {$moduleType} and pid in (".implode(',',$ids).")  order by weigh asc";
                $rs3 = $this->query($sql);
                $tmpArr = array();
                if(count($rs3)>0){
                    foreach ($rs3 as $key =>$v){
                        $meta = [];
                        $meta['title'] = $v['title'];
                        $meta['requireAuth'] = true;
                        $v['meta'] = $meta;
                        unset($v['title'],$v['icon']);
                        $tmpArr[$v['pid']][] = $v;
                    }
                }
                //把第三级归类到第二级下
                foreach ($rs2 as $key =>$v){
                    $meta = [];
                    $meta['title'] = $v['title'];
                    $meta['requireAuth'] = true;
                    $rs2[$key]['meta'] = $meta;
                    $rs2[$key]['children'] = $tmpArr[$v['id']];
                    //如果第三级不存在时，将children移除
                    if(empty($rs2[$key]['children'])){
                        unset($rs2[$key]['children']);
                    }
                    unset($rs2[$key]['title'],$rs2[$key]['icon']);
                }
                $tmpArr = array();
                foreach ($rs2 as $key =>$v){
                    $tmpArr[$v['pid']][] = $v;
                }
            }

            //把二季归类到第一级下
            foreach ($rs1 as $key =>$v){
                $meta = [];
                $meta['title'] = $v['title'];
                $meta['icon'] = $v['icon'];
                $rs1[$key]['meta'] = $meta;
                $rs1[$key]['children'] = $tmpArr[$v['id']];
                unset($rs1[$key]['title'],$rs1[$key]['icon']);
            }
        }
        return $rs1;
    }

    public function getPrivilegeTreeList($moduleType)
    {
        $authRuleServiceModule = new AuthRuleServiceModule();
        $getAuthRuleList = $authRuleServiceModule->getAuthRuleList($moduleType);
        $data = getChild($getAuthRuleList['data']);
        return $data;
    }
}