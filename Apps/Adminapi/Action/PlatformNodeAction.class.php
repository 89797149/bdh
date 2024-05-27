<?php

namespace Adminapi\Action;
use Adminapi\Model\PlatformNodeModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 平台节点控制器
 */
class PlatformNodeAction extends BaseAction
{

    public function __construct()
    {
        parent::__construct();
        $this->isLogin();
//        $this->checkPrivelege('dttj_00');
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('ddlb_00');
        $parameter = I();
        $parameter['page'] = I('page', 1, 'intval');
        $parameter['pageSize'] = I('pageSize', 15, 'intval');
        $m = D('Adminapi/PlatformNode');
        $msg = '';
        $list = $m->getlist($parameter, $msg);

        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取节点分类(无分页)
     */
    public function getCatListAll()
    {
        $this->isLogin();
        $m = D('Adminapi/PlatformNode');
        $list = $m->getCatListAll();

        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取节点列表(无分页)
     */
    public function getPlatformNodeList()
    {
        $this->isLogin();
        $m = D('Adminapi/PlatformNode');

        $list = $m->getPlatformNodeList();

        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取权限列表【总后台】
     */
    public function getTableAuthRuleList()
    {
        $this->isLogin();
        $m = new PlatformNodeModel();
        $param = [];
        $param['roleId'] = I('roleId', 0);
        $list = $m->getTableAuthRuleList($param);

        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 根据token获取权限列表
     */
    public function getUserAuth()
    {
        $res = $this->isLogin();
        $param = [];
        $param['roleId'] = $res['staffRoleId'];//获取角色id
        $param['module_type'] = 1;//所属模块【1运营后台、2商家后台】
        $param['loginName'] = $res['loginName'];//用于判断是否是总管理员账号
        $data = getUserPrivilege($param);

        $this->ajaxReturn(returnData((array)$data));
    }

    /**
     * 获取
     * 无用
     */
    public function getlist()
    {

        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $msg = '';
        $res = $m->getlist($parameter, $msg);

        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $res);
        $this->ajaxReturn($rs);
    }

    /**
     * 添加节点
     */
    public function add()
    {
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $msg = '';
        $res = $m->addnode($parameter, $msg);
        if (!$res) {
            $this->returnResponse(-1, $msg ? $msg : '操作失败');
        }
        $this->returnResponse(0, '操作成功');
    }

    /**
     * 查看详情
     */
    public function detail()
    {
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $msg = '';
        $detail = $m->getInfo($parameter, $msg);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑账号
     */
    public function edit()
    {
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $msg = '';
        $res = $m->edit($parameter, $msg);
        if (!$res) {
            $this->returnResponse(-1, $msg ? $msg : '操作失败');
        }
        $this->returnResponse(0, '操作成功');
    }

    /**
     * 删除账号
     */
    public function del()
    {
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $msg = '';
        $res = $m->del($parameter, $msg);
        if (!$res) {
            $this->returnResponse(-1, $msg ? $msg : '删除失败');
        }
        $this->returnResponse(0, '删除成功');
    }

    //分类 后加

    /**
     * 节点分类
     * @param string catname PS:分类名称
     */
    public function catIndex()
    {
        $this->isLogin();
//        $this->checkPrivelege('jdfllb_00');
        $param = I();
        $param['page'] = I('page', 1, 'intval');
        $param['pageSize'] = I('pageSize', 15, 'intval');
        $m = D('Adminapi/PlatformNode');
        $list = $m->getCatIndex($param);

        $this->returnResponse(0, '操作成功', $list);
    }

    /**
     * 节点分类详情
     */
    public function catDetail()
    {
        $this->isLogin();
//        $this->checkPrivelege('jdfllb_02');
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $catDetail = $m->getCatInfo($parameter);

        $this->returnResponse(0, '操作成功', $catDetail);
    }

    /**
     * 编辑或添加节点分类
     */
    public function catEdit()
    {
        $this->isLogin();
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        if ($parameter['id'] > 0) {
//            $this->checkPrivelege('jdfllb_02');
            $res = $m->catEdit($parameter);
        } else {
//            $this->checkPrivelege('jdfllb_01');
            $res = $m->catAdd($parameter);
        }
        if ($res['errorCode'] == -1) {
            $this->returnResponse(-1, $res['errorMsg']);
        }
        $this->returnResponse(0, $res['errorMsg']);
    }

    /**
     * 删除节点分类
     * @param string id PS:分类id
     */
    public function catDel()
    {
        $parameter = I();
        $m = D('Adminapi/PlatformNode');
        $res = $m->catDel($parameter);
        if ($res['errorCode'] == -1) {
            $this->returnResponse(-1, $res['errorMsg']);
        }
        $this->returnResponse(0, $res['errorMsg']);
    }

    /**
     * 新增/编辑菜单节点
     * https://www.yuque.com/youzhibu/ruah6u/reugk8
     */
    public function addPrivilege()
    {
        $requestParams = I();
        $params = [];
        $params['id'] = 0;
        $params['pid'] = 0;
        $params['name'] = null;
        $params['title'] = null;
        $params['menu_type'] = 0;
        $params['weigh'] = 0;
        $params['page_hidden'] = -1;
        $params['path'] = null;
        $params['component'] = null;
        $params['is_frame'] = 0;
        $params['module_type'] = 0;
        $params['redirect'] = null;
        $params['icon'] = null;
        parm_filter($params, $requestParams);
        if (empty($params['title'])) {
            $data = returnData(null, -1, 'error', '请填写菜单名称', '参数有误');
            $this->ajaxReturn($data);
        }
        if (empty($params['path'])) {
            $data = returnData(null, -1, 'error', '请填写路由地址', '参数有误');
            $this->ajaxReturn($data);
        }
        $params['name'] = $params['path'].rand(0,10).rand(0,10);//防止重复
        if (empty($params['module_type'])) {
            $data = returnData(null, -1, 'error', '请选择所属模块', '参数有误');
            $this->ajaxReturn($data);
        }
        $m = new PlatformNodeModel();
        if (empty($params['id'])) {
            unset($params['id']);
            $params['createTime'] = date("Y-m-d H:i:s");
            $info = $m->addPrivilege($params);
        } else {
            $params['updateTime'] = date("Y-m-d H:i:s");
            $info = $m->updatePrivilege($params);
        }
        if (!empty($info['code'])) {
            $this->ajaxReturn($info);
        }
        if ($info <= 0) {
            $data = returnData(null, -1, 'error', '请检查权限规则(api路由)是否重复', '参数有误');
        } else {
            $data = returnData($info);
        }
        $this->ajaxReturn($data);
    }

    /**
     * 编辑节点排序
     */
    public function editPrivilegeWeigh()
    {
        $Id = (int)I('id', 0);
        if (empty($Id)) {
            $data = returnData(null, -1, 'error', '请选择编辑的信息', '参数有误');
            $this->ajaxReturn($data);
        }
        $weigh = (int)I('weigh',0);
        $params = [];
        $params['id'] = $Id;
        $params['weigh'] = $weigh;
        $m = new PlatformNodeModel();
        $info = $m->editPrivilegeWeigh($params);
        $this->ajaxReturn($info);
    }

    /**
     * 获取菜单节点详情
     * https://www.yuque.com/youzhibu/ruah6u/fmzmdp
     */
    public function getPrivilegeInfo()
    {
        $Id = (int)I('id', 0);
        if (empty($Id)) {
            $data = returnData(null, -1, 'error', '请选择查看的信息', '参数有误');
            $this->ajaxReturn($data);
        }
        $m = new PlatformNodeModel();
        $info = $m->getPrivilegeInfo($Id);
        $this->ajaxReturn($info);
    }

    /**
     * 删除菜单节点
     * https://www.yuque.com/youzhibu/ruah6u/me30td
     */
    public function delPrivilege()
    {
        $Id = (int)I('id', 0);
        if (empty($Id)) {
            $data = returnData(null, -1, 'error', '请选择删除的信息', '参数有误');
            $this->ajaxReturn($data);
        }
        $m = new PlatformNodeModel();
        $info = $m->delPrivilege($Id);
        if (!empty($info['code'])) {
            $this->ajaxReturn($info);
        }
        if ($info <= 0) {
            $data = returnData(null, -1, 'error', '操作失败', '参数有误');
        } else {
            $data = returnData($info);
        }
        $this->ajaxReturn($data);
    }

    /**
     * 获取菜单列表(树形)
     * https://www.yuque.com/youzhibu/ruah6u/smmgvk
     */
    public function getPrivilegeList()
    {
        $moduleType = (int)I('module_type', 0);
        if (empty($moduleType)) {
            $data = returnData(null, -1, 'error', '请选择所属模块', '参数有误');
            $this->ajaxReturn($data);
        }
        $m = new PlatformNodeModel();
        $info = $m->getPrivilegeList($moduleType);
//        $info = $m->getPrivilegeTreeList($moduleType);//【旧】
        $data = returnData($info);
        $this->ajaxReturn($data);
    }

    /**
     * 单独获取菜单列表包括数量
     * https://www.yuque.com/youzhibu/ruah6u/zleqxg
     */
    public function getPrivilegeListCount()
    {
        $moduleType = (int)I('module_type', 0);
        if (empty($moduleType)) {
            $data = returnData(null, -1, 'error', '请选择所属模块', '参数有误');
            $this->ajaxReturn($data);
        }
        $m = new PlatformNodeModel();
        $info = $m->getPrivilegeListCount($moduleType);
        $data = returnData($info);
        $this->ajaxReturn($data);
    }
}