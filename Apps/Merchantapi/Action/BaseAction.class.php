<?php

namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 基础控制器
 */

use Think\Controller;

class BaseAction extends Controller
{
    public function __construct()
    {
        parent::__construct();
        //跨域支持
        // 指定允许其他域名访问嗯
        header('Access-Control-Allow-Origin: ' . @$_SERVER['HTTP_ORIGIN']);
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        //允许cookie 跨域（跨域资源共享）

        header('Access-Control-Allow-Credentials: true');

        //初始化系统信息
        $m = D('Home/System');
        $GLOBALS['CONFIG'] = $m->loadConfigs();
        // WSTAutoByCookie();

        #验证接口参数
        $check_rule = self::get_rule($_SERVER['PATH_INFO']);
//        if (empty($check_rule)) {
//            $this->returnResponse(-3,'非法调用');
//        }
        //$check_rule = 1;
        if ($check_rule) {
            $validate = new \Think\Validate($check_rule['rule'], $check_rule['msg'], $check_rule['field']);
            $result = $validate->check(I());
            if (!$result) {
                $this->returnResponse(-1, $validate->getError());
            }
        }
        #END

        //判断是否存在定制
        //self::isrules_made();
    }

    /**
     * 空操作处理
     */
    public function _empty($name)
    {
        $this->assign('msg', "你的思想太飘忽，系统完全跟不上....");
        $this->display('default/sys_msg');
    }

    /**
     * ajax程序验证,只要不是会员都返回-999
     */
    public function isUserLogin()
    {
        $USER = session('WST_USER');
        if (empty($USER) || ($USER['userId'] == '')) {
            if (IS_AJAX) {
                $this->ajaxReturn(array('status' => -999, 'url' => 'Users/login'));
            } else {
                $this->redirect("Users/login");
            }
        }
    }

    /**
     * 商家ajax登录验证
     */
    public function isShopLogin()
    {
        $USER = session('WST_USER');
        if (empty($USER) || $USER['userType'] == 0) {
            if (IS_AJAX) {
                $this->ajaxReturn(array('status' => -999, 'url' => 'Shops/login'));
            } else {
                $this->redirect("Shops/login");
            }
        }
    }

    /**
     * 用户登录验证-主要用来判断会员和商家共同功能的部分
     */
    public function isLogin($userType = 'Users')
    {
        $USER = session('WST_USER');
        if (empty($USER)) {
            if (IS_AJAX) {
                $this->ajaxReturn(array('status' => -999, 'url' => $userType . '/login'));
            } else {
                $this->redirect($userType . "/login");
            }
        }
    }

    /**
     * 检查登录状态
     */
    public function checkLoginStatus()
    {
        $USER = session('WST_USER');
        if (empty($USER)) {
            die("{status:-999}");
        } else {
            die("{status:1}");
        }
    }

    /**
     * 验证模块的码校验
     */
    public function checkVerify($type)
    {
        if (stripos($GLOBALS['CONFIG']['captcha_model'], $type) !== false) {
            $verify = new \Think\Verify();
            return $verify->check(I('verify'));
        } else {
            return true;
        }
        return false;
    }

    /**
     * 核对单独的验证码
     * $re = false 的时候不是ajax返回
     * @param boolean $re [description]
     * @return [type]      [description]
     */
    public function checkCodeVerify($re = true)
    {
        $code = I('code');
        $verify = new \Think\Verify(array('reset' => false));
        $rs = $verify->check($code);
        if ($re == false) return $rs;
        else $this->ajaxReturn(array('status' => (int)$rs));
    }

    /**
     * 单个上传图片
     */
    public function uploadPic()
    {
        $config = array(
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('jpg', 'png', 'gif', 'jpeg'), //允许上传的文件后缀
            'rootPath' => './Upload/', //保存根路径
            'driver' => 'LOCAL', // 文件上传驱动
            'subName' => array('date', 'Y-m'),
            'savePath' => I('dir', 'uploads') . "/"
        );
        $dirs = explode(",", C("WST_UPLOAD_DIR"));
        if (!in_array(I('dir', 'uploads'), $dirs)) {
            $this->returnResponse(-1, '非法文件目录', array());
        }

        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        $Filedata = key($_FILES);
        if (!$rs) {
            $this->returnResponse(-1, $upload->getError(), array());
        } else {
            $images = new \Think\Image();
            $images->open('./Upload/' . $rs[$Filedata]['savepath'] . $rs[$Filedata]['savename']);
            $newsavename = str_replace('.', '_thumb.', $rs[$Filedata]['savename']);
            $vv = $images->thumb(I('width', 300), I('height', 300))->save('./Upload/' . $rs[$Filedata]['savepath'] . $newsavename);
            if (C('WST_M_IMG_SUFFIX') != '') {
                $msuffix = C('WST_M_IMG_SUFFIX');
                $mnewsavename = str_replace('.', $msuffix . '.', $rs[$Filedata]['savename']);
                $mnewsavename_thmb = str_replace('.', "_thumb" . $msuffix . '.', $rs[$Filedata]['savename']);
                $images->open('./Upload/' . $rs[$Filedata]['savepath'] . $rs[$Filedata]['savename']);
                $images->thumb(I('width', 700), I('height', 700))->save('./Upload/' . $rs[$Filedata]['savepath'] . $mnewsavename);
                $images->thumb(I('width', 250), I('height', 250))->save('./Upload/' . $rs[$Filedata]['savepath'] . $mnewsavename_thmb);
            }
            $rs[$Filedata]['savepath'] = "Upload/" . $rs[$Filedata]['savepath'];
            $rs[$Filedata]['savethumbname'] = $newsavename;
            $this->returnResponse(1, '操作成功', $rs);
//            echo json_encode($rs);

        }
    }

    /**
     * 产生验证码图片
     *
     */
    public function getVerify()
    {
        ob_end_clean();
        // 导入Image类库
        $Verify = new \Think\Verify();
        $Verify->length = 4;
        $Verify->entry();
    }

    /**
     * 页尾参数初始化
     */
    public function footer()
    {
        $m = D('Home/Friendlinks');
        $friendLikds = $m->getFriendLinks();
        $this->assign('friendLikds', $friendLikds);
        $m = D('Home/Articles');
        $helps = $m->getHelps();
        $this->view->assign("helps", $helps);
    }

    /**
     * 设置所在城市
     */
    public function setDefaultCity($cityId)
    {
        setcookie("areaId2", $cityId, time() + 3600 * 24 * 90);
    }

    /**
     * 定位所在城市
     */
    public function getDefaultCity()
    {
        $areas = D('Home/Areas');
        return $areas->getDefaultCity();
    }


    /**
     * 返回所有参数
     */
    function WSTAssigns()
    {
        $params = I();
        $this->assign("params", $params);
    }

    //会员身份验证 成功即返回用户信息
    public function MemberVeri()
    {
        $memberToken = I("token");

        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        if (empty($memberToken)) {
            $memberToken = $headers['Token'];
        }
        if (empty($memberToken)) {
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token不能为空";
            $this->ajaxReturn($status);
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken, 86400 * 30);//查询token

        if (empty($sessionData)) {
            $status['code'] = 401;
            $status['status'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        //普通管理员权限检测
        if ($sessionData['login_type'] == 2) {
//            if(!$this->checkUserJurisdiction($sessionData)){
//                $this->returnResponse(404,'无权访问');
//            }
//            所属模块【1运营后台、2商家后台】-----新加【不要删除，如果需要可打开】----start------2020-9-27-----
            $sessionData['module_type'] = 2;
            $checkStaff = $this->checkStaff($sessionData);
            if (empty($checkStaff)) {
                $this->returnResponse(404, '无权访问');
            }
            //--------------end-------------------------------------------------
        }
        //END
        if (empty($sessionData['id'])) {
            //管理员
            $sessionData['user_type'] = 1;
            $sessionData['user_id'] = $sessionData['userId'];
            $sessionData['user_username'] = $sessionData['userName'];
            $sessionData['user_phone'] = $sessionData['userPhone'];
            $user_info = M('users')->where(array('userId' => $sessionData['userId']))->find();
            if (!empty($user_info)) {
                $sessionData['user_username'] = $user_info['userName'];
                $sessionData['user_phone'] = $user_info['userPhone'];
            }
        } else {
            //职员
            $sessionData['user_type'] = 2;
            $sessionData['user_id'] = $sessionData['id'];
            $sessionData['user_username'] = $sessionData['username'];
            $sessionData['user_phone'] = $sessionData['phone'];
            $user_info = M('user')->where(array('id' => $sessionData['id']))->find();
            if (!empty($user_info)) {
                if ($user_info['status'] != 0) {
                    $this->returnResponse(404, '该账号已被禁用');
                }
                $sessionData['user_username'] = $user_info['username'];
                $sessionData['user_phone'] = $user_info['phone'];
            }
        }
        $sessionData['token'] = $memberToken;
        return $sessionData;
    }

    /**
     * @param $params
     * @return bool
     * 访问权限检测
     */
    public function checkStaff($params)
    {
        $server = explode('/', $_SERVER['REQUEST_URI']);
        $url = "{$server[1]}/{$server[2]}/{$server[3]}";
        $roleNodeModel = M('role_node');
        $userRoleModel = M('user_role');
        $authRuleModel = M('auth_rule');
        $routeWhiteModel = M('route_white');
        //查看是否存在白名单
        $routeWhiteInfo = $routeWhiteModel->where(['route_url' => $url])->find();
        if (!empty($routeWhiteInfo)) {
            return true;
        } else {
            $name = "{$server[1]}/{$server[2]}";
        }
        //获取当前权限是否存在
        $ruleList = $authRuleModel->where(['name' => $name, 'module_type' => $params['module_type']])->find();
        //没有记录在数据库的类直接放权限
        if (empty($ruleList)) {
            return true;
        }
        //获取角色ID
        $ridList = $userRoleModel->where(['uid' => $params['id'], 'shopId' => $params['shopId']])->select();
        $rid_arr = array_get_column($ridList, 'rid');
        $rids = implode(',', array_unique($rid_arr));
        //获取当前职员所有权限ID
        $roleList = $roleNodeModel->where(' rid in(' . $rids . ') and shopId=' . $params['shopId'])->select();
        $nid_arr = array_unique(array_get_column($roleList, 'nid'));
        if ($ruleList['pid'] != 0) {
            //获取具体的权限
            $getRuleList = $authRuleModel->where(['id' => $ruleList['pid'], 'module_type' => $params['module_type']])->find();
            //如果不是一级分类，那么就进行id重置
            if ($getRuleList['pid'] != 0) {
                $ruleList['id'] = $getRuleList['id'];
            }
        }
        if (!in_array($ruleList['id'], $nid_arr)) {
            return false;
        }
        return true;
    }

    //访问权限检测
    public function checkUserJurisdiction($parameter = array(), &$msg = '')
    {


        $urlPath_arr = explode('/', $_SERVER['PATH_INFO']);

        //未加入到权限数据库里的路由不做任何处理 辉辉 2019.6.22
        $nm = M('node');
        $IsnodeList = $nm->where("status=1 and mname = '{$urlPath_arr[0]}' and aname = '{$urlPath_arr[1]}'  ")->count();
        if ($IsnodeList <= 0) {//如果不存在路由
            return true;
        }

        if (!$parameter['id'] || !$parameter['shopId'] || !$urlPath_arr[0] || !$urlPath_arr[1]) {
            return false;
        }

        $nodeList = $this->getUserNodes($parameter, $msg);
        if (!$nodeList) {
            return false;
        }
        foreach ($nodeList as $key => $value) {
            if ($value['mname'] == $urlPath_arr[0] && $value['aname'] == $urlPath_arr[1]) {
                return true;
            }
        }

        return false;
    }

    public function getUserNodes($parameter = array(), &$msg = '')
    {
        if (!$parameter['id'] || !$parameter['shopId']) {
            return false;
        }
        //缓存
        $cache_arr = S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}");
        if ($cache_arr && is_array($cache_arr)) {
            return $cache_arr;
        }

        //数据库
        $m = M('user_role');
        //获取角色id
        $ruList = $m->where('uid=' . (int)$parameter['id'] . ' and shopId=' . $parameter['shopId'])->select();
        if (!$ruList) {
            return false;
        }
        $rid_arr = array_get_column($ruList, 'rid');
        $rids = implode(',', array_unique($rid_arr));
        if (!$rids) {
            return false;
        }
        //角色检测
        $rm = M('role');
        $roleList = $rm->where('status=1 and id in(' . $rids . ') and shopId=' . $parameter['shopId'])->select();
        $check_ridArr = array_get_column($roleList, 'id');
        $check_rids = implode(',', array_unique($check_ridArr));
        if (!$check_rids) {
            return false;
        }
        //END

        //获取节点
        $rnm = M('role_node');
        $nrList = $rnm->where('rid in(' . $check_rids . ') and shopId=' . $parameter['shopId'])->select();
        $nid_arr = array_get_column($nrList, 'nid');
        $nids = implode(',', array_unique($nid_arr));//用户所有权限id
        if (!$nids) {
            return false;
        }

        $nm = M('node');
        $nodeList = $nm->where("status=1 and id in({$nids})")->select();
        //存缓存
        if ($nodeList && is_array($nodeList)) {
            S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}", $nodeList, 300);
        }
        return $nodeList;
    }

//二开-公用方法
    public function returnResponse($code, $msg = '', $data = array())
    {
        $returnData = array(
            'code' => $code,
            'status' => $code,
            'msg' => $msg,
            'data' => $data,
        );
        if (isset($data['list'])) {
            $returnData['list'] = $data['list'];
            unset($returnData['data']);
        }
        $this->ajaxReturn($returnData);
    }

    public function arrChangeSqlStr($arr = array())
    {
        if (!$arr) {
            return false;
        }

        $str = '';
        $num = 0;
        foreach ($arr as $key => $value) {
            if ($num) {
                $str .= " and {$key}='$value' ";
            } else {
                $str .= " {$key}='$value' ";
            }
            $num++;
        }

        return $str;
    }

    public function array_get_column($parameter = array(), $key = '')
    {
        if (!$parameter) {
            return array();
        }
        $new_arr = array();
        foreach ($parameter as $value) {
            if ($value[$key]) {
                $new_arr[] = $value[$key];
            }
        }
        return $new_arr;
    }

    // 获取规则
    public static function get_rule($api)
    {
        $urlPath_arr = explode('/', $api);
        $key = $urlPath_arr[0] . '.' . $urlPath_arr[1];
        $rules = include APP_PATH . '/Merchantapi/rule_api.php';
        return isset($rules[$key]) ? $rules[$key] : [];
    }

    //对于定制功能进行跳转
    protected function isrules_made()
    {
        //助手函数、请求信息
        //获取当前请求的模块名
        $module = MODULE_NAME;//当前模块名称是
        $controller = CONTROLLER_NAME;//当前控制器名称是
        $action = ACTION_NAME;//当前操作名称是

        $action = $module . '_' . $controller . '_' . $action;

        //实例化控制器
        $CT = A("Made/$module");

        // echo $CT->test();
        //获取控制器下的所有方法
        $methods = get_class_methods($CT);
        if (in_array($action, $methods)) {//如果存在此方法 以定制模块的方法为主
            $data = call_user_func_array(array($CT, $action), array());//赋值给自定义的name
            echo $data;
            exit();
        } else {
            return;
        }
    }

    /**
     * 桌面端分拣-校验token
     * */
    public function verficationToken()
    {
        $token = I("token");
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        if (empty($token)) {
            $token = $headers['Token'];
        }
        if (empty($token)) {
            $this->ajaxReturn(returnData(false, 401, 'error', 'token失效，请重新登陆', '缺少必填参数-token'));
        }
        $token_data = userTokenFind($token, 86400 * 30);
        if (empty($token_data)) {
            $this->ajaxReturn(returnData(false, 401, 'error', 'token失效，请重新登陆'));
        }
        return $token_data;
    }

}
