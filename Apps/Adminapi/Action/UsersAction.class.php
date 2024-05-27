<?php

namespace Adminapi\Action;

use Adminapi\Model\UsersModel;
use App\Enum\ExceptionCodeEnum;
use App\Enum\Sms\SmsEnum;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员控制器
 */
class UsersAction extends BaseAction
{
    /**
     * 查看详情
     */
    public function detail()
    {
        $this->isLogin();
        $this->checkPrivelege('hylb_02');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = new UsersModel();
        $detail = $m->getUserInfo();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
    }

    /**
     * 新增/修改会员操作
     */
    public function edit()
    {
        $userData = $this->isLogin();
        $m = new UsersModel();
        if (I('id', 0) > 0) {
            $this->checkPrivelege('hylb_02');
            $rs = $m->edit($userData);
        } else {
            $this->checkPrivelege('hylb_01');
            $rs = $m->insert($userData);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除会员操作
     */
    public function del()
    {
        $userData = $this->isLogin();
        $this->checkPrivelege('hylb_03');
        $m = new UsersModel();
        $rs = $m->del($userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 查看
     */
    public function toView()
    {
        $this->isLogin();
        $this->checkPrivelege('hylb_00');
        $m = new UsersModel();
        if (I('id') > 0) {
            $object = $m->get();
            $this->assign('object', $object);
        }
        $this->view->display('/users/view');
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('hylb_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new UsersModel();

        $list = $m->queryByPage($page, $pageSize);

        $this->ajaxReturn(returnData($list));
    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = new UsersModel();
        $list = $m->queryByList();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 查询用户账号
     */
    public function checkLoginKey()
    {
        $this->isLogin();
        $m = new UsersModel();
        $key = I('clientid');
        $id = I('id', 0);
        $rs = $m->checkLoginKey(I($key), $id);
        $this->ajaxReturn($rs);
    }

    /**********************************************************************************************
     *                                             账号管理                                                                                                                              *
     **********************************************************************************************/
    /**
     * 获取账号分页列表
     */
    public function queryAccountByPage()
    {
        $this->isLogin();
        $this->checkPrivelege('hyzh_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new UsersModel();
        $list = $m->queryAccountByPage($page, $pageSize);

        $this->ajaxReturn(returnData($list));
    }

    /**
     * 编辑账号状态
     */
    public function editUserStatus()
    {
        $this->isLogin();
        $this->checkPrivelege('hyzh_04');
        $m = new UsersModel();
        $rs = $m->editUserStatus();
        $this->ajaxReturn($rs);
    }

    /**
     * 账号编辑详情
     */
    public function accountEditDetail()
    {
        $this->isLogin();
        $this->checkPrivelege('hyzh_04');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', "参数不全"));
        }
        $m = new UsersModel();
        $detail = $m->getAccountById();

        $this->ajaxReturn(returnData($detail));
    }

    /**
     * 编辑账号信息
     */
    public function editAccount()
    {
        $this->isLogin();
        $this->checkPrivelege('hyzh_04');
        $m = new UsersModel();
        $rs = $m->editAccount();
        $this->ajaxReturn($rs);
    }

    /**
     * 获取小程序二维码
     */
    public function getProgramQrCode()
    {
        // 暂时注释$this->isLogin();
        // 暂时注释$this->checkPrivelege('hyzh_04');
        $jsonVal = I('jsonVal');
        if (!empty($jsonVal)) {
            // $jsonVal =  html_entity_decode($jsonVal); //需要字符解密 非转实体字符 辉修复2019
            $jsonVal = htmlspecialchars_decode($jsonVal);

        } else {
            $rs['status'] = -1;
            $rs['msg'] = "参数不能为空";
            $this->ajaxReturn($rs);
        }
        $access_token = getWxAccessToken();
        if (!$access_token) {
            $rs['status'] = -1;
            $this->ajaxReturn($rs);
        }
        //获取图标
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";

        $res = file_get_contents_post($url, $jsonVal);

        // header('Content-type: image/jpeg');

        //由于使用接口 转码为base64_encode
        $res = base64_encode($res);
        echo $res;
    }

    /**
     * 会员开通记录
     */
    public function recordLog()
    {
        $this->isLogin();
        $this->checkPrivelege('hyktjl_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new UsersModel();
        $list = $m->queryByPageRecord($page, $pageSize);

        $this->ajaxReturn(returnData($list));
    }

    /**
     * 用户收货地址-删除
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/unyo0w
     * */
    public function delUserAddress()
    {
        $this->isLogin();
        $addressId = (int)I('addressId');
        if (empty($addressId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-缺少addressId'));
        }
        $mod = new UsersModel();
        $result = $mod->delUserAddress($addressId);
        $this->ajaxReturn($result);
    }

    /**
     * 用户收货地址-修改
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/iiuody
     * */
    public function updateUserAddress()
    {
        $this->isLogin();
        $reqParams = I();
        if (empty($reqParams['addressId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-缺少addressId'));
        }
        $mod = new UsersModel();
        $result = $mod->updateUserAddress($reqParams);
        $this->ajaxReturn($result);
    }

    /**
     * 用户收货地址-添加
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/to2lcc
     * */
    public function addUserAddress()
    {
        $this->isLogin();
        $reqParams = I();
        if (empty($reqParams['userId']) || empty($reqParams['userPhone']) || empty($reqParams['userName']) || empty($reqParams['lat']) || empty($reqParams['lng'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        if (!preg_match(SmsEnum::MOBILE_FORMAT, $reqParams['userPhone'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '手机号格式不正确'));
        }
        $mod = new UsersModel();
        $result = $mod->addUserAddress($reqParams);
        $this->ajaxReturn($result);
    }
}