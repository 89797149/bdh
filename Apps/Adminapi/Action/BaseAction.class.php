<?php

namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
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
        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin: ' . @$_SERVER['HTTP_ORIGIN']);
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Credentials: true');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        //初始化系统信息
        $m = D('Home/System');
        $GLOBALS['CONFIG'] = $m->loadConfigs();
        $this->assign('CONF', $GLOBALS['CONFIG']);
        // $s = session('WST_STAFF');
        //判断是否存在定制
        //self::isrules_made();
        // $this->assign('WST_STAFF',$s);

    }

    /**
     * 单个上传图片
     */
    public function uploadPic()
    {
        $this->isLogin();
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
            echo '非法文件目录！';
            return false;
        }

        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        $Filedata = key($_FILES);
        if (!$rs) {
            $this->error($upload->getError());
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
            $rs['status'] = 1;

            echo json_encode($rs);
        }
    }

    /**
     * 上传文件
     * Enter description here ...
     */
    public function uploadFile()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('pem');// 设置附件上传类型
        $upload->savePath = './Uploads/wxpay_cert/'; // 设置附件上传目录
        // 上传文件
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功
//            $this->success('上传成功！');
            echo json_encode($info);
            exit();
        }
    }
    /**
     * 登录操作验证
     */
    /*public function isLogin(){
    	$s = session('WST_STAFF');
        if(IS_AJAX){
    	    if(empty($s))die("{status:-999,url:'toLogin'}");
    	}else{
//    		if(empty($s))$this->redirect("Index/toLogin");
            if(empty($s)){
                echo json_encode(array('code'=>-1,'msg'=>'请重新登录'));
                exit();
            }
    	}
    }*/

    /**
     * 登录操作验证
     */
    public function isLogin()
    {
        return $this->MemberVeri();
    }

    /**
     * 跳转权限操作
     */
    public function checkPrivelege($grant)
    {
        return '';
        /*$s = session('WST_STAFF.grant');
        if(IS_AJAX){
            if(empty($s) || !in_array($grant,$s))die("{status:-998}");
        }else{
            if(empty($s) || !in_array($grant,$s)){
                $this->display("/noprivelege");exit();
            }
        }*/
    }

    /**
     * 返回所有参数
     */
    function WSTAssigns()
    {
        $params = I();
        $this->assign("params", $params);
    }

    /**
     * 产生验证码图片
     *
     */
    public function getVerify()
    {
        // 导入Image类库
        $Verify = new \Think\Verify();
        $Verify->length = 4;
        $Verify->entry();
    }

    public function checkVerify()
    {
        $verify = new \Think\Verify();
        return $verify->check(I('verify'));
    }

    public function returnResponse($code, $msg = '', $data = array())
    {
        $returnData = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        );
        if ($data['list']) {
            $returnData['list'] = $data['list'];
            unset($returnData['data']);
        }
        $this->ajaxReturn($returnData);
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
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);
        $configLoginExpirationTime = $GLOBALS['CONFIG']['loginExpirationTime'];
        $loginExpirationTime = 30 * 60;
        if (is_numeric($configLoginExpirationTime) && $configLoginExpirationTime > 30) {
            $loginExpirationTime = $configLoginExpirationTime * 60;
        }
        //系统平台,登陆半小时后过期
        $sessionData = userTokenFind($memberToken, $loginExpirationTime);//查询token

        if (empty($sessionData)) {
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
//权限检测---------------------------------------------------
//        if($sessionData['loginName'] != "admin666"){
//            //普通管理员权限检测
////            if(!$this->checkUserJurisdiction($sessionData)){
////                //$this->returnResponse(-3,'无权访问');
////                $status['status'] = -3;
////                $status['msg'] = "无权访问";
////                $this->ajaxReturn($status);
////            }
//            $checkStaff = $this->checkStaff($sessionData);
//            if(empty($checkStaff)){
//                $status['status'] = -3;
//                $status['msg'] = "无权访问";
//                $this->ajaxReturn($status);
//            }
//        }
        //--------------------------------------------------------
        $sessionData['user_id'] = $sessionData['staffId'];
        $sessionData['user_username'] = $sessionData['staffName'];
        //END
        return $sessionData;
    }

    //访问权限检测
    public function checkUserJurisdiction($parameter = array(), &$msg = '')
    {


        $urlPath_arr = explode('/', $_SERVER['PATH_INFO']);

        //未加入到权限数据库里的路由不做任何处理 辉辉 2019.6.22
        $nm = M('platform_node');
        $IsnodeList = $nm->where("status=1 and mname = '{$urlPath_arr[0]}' and aname = '{$urlPath_arr[1]}'  ")->count();
        if ($IsnodeList <= 0) {//如果不存在路由
            return true;
        }

        if (!$parameter['staffId'] || !$urlPath_arr[0] || !$urlPath_arr[1]) {
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

    /**
     * @param $params
     * @return bool
     * 访问权限检测
     */
    public function checkStaff($params)
    {
        $server = explode('/', $_SERVER['REQUEST_URI']);
        $url = "{$server[1]}/{$server[2]}/{$server[3]}";
        $routeWhiteModel = M('route_white');
        $authRuleModel = M('auth_rule');
        $rulesModel = M('roles');
        $params['module_type'] = 1;
        //查看是否存在白名单
        $routeWhiteInfo = $routeWhiteModel->where(['route_url' => $url])->find();
        if (!empty($routeWhiteInfo)) {
            return true;
        } else {
            $name = "{$server[1]}/{$server[2]}";
        }
        //获取当前权限是否存在
        $ruleList = $authRuleModel->where(['name' => $name, 'module_type' => $params['module_type']])->find();
        if (empty($ruleList)) {
            return false;
        }
        //获取当前权限是否存在
//        $rulesInfo= $rulesModel->where(['roleId'=>$params['staffRoleId'],'roleFlag'=>1])->find();
//        $nid_arr = array_unique(explode(',',$rulesInfo['grant']));

        $rulesInfo = $rulesModel->where('roleFlag = 1 and roleId in(' . $params['staffRoleId'] . ')')->select();
        //判断是否存在权限
        if (empty($rulesInfo)) {
            return false;
        }

        //取值---合并---分割---去重
        $grantId = array_get_column($rulesInfo, 'grant');
        $grantInfo = implode(',', $grantId);
        $nid_arr = array_unique(explode(',', $grantInfo));

        if ($ruleList['pid'] != 0) {
            //获取具体的权限
            $getRuleList = $authRuleModel->where(['id' => $ruleList['pid'], 'module_type' => $params['module_type']])->find();
            //如果不是一级分类，那么就进行id重置
            if ($getRuleList['pid'] != 0) {
                $ruleList['id'] = $getRuleList['id'];
            }
        }
        //判断当前权限是否存在当前职员中
        if (!in_array($ruleList['id'], $nid_arr)) {
            return false;
        }
        return true;
    }

    public function getUserNodes($parameter = array(), &$msg = '')
    {
        if (!$parameter['staffId']) {
            return false;
        }
        //缓存
        /*$cache_arr =S("adminapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}");
        if($cache_arr && is_array($cache_arr)){
            return $cache_arr;
        }*/

        //数据库
        $m = M('roles');
        //获取角色id
        $roleInfo = $m->where(['roleId' => $parameter['staffRoleId']])->find();
        $roleInfo['grant'] = trim($roleInfo['grant'], ',');
        if (empty($roleInfo['grant'])) {
            return false;
        }
        //END
        //获取节点
        /*$rnm = M('role_platform_node');
        $nrList = $rnm->where('nid in('.$roleInfo['grant'].') and rid='.$parameter['staffRoleId'])->select();
        $nid_arr = array_get_column($nrList,'nid');
        $nids = implode(',',array_unique($nid_arr));//用户所有权限id
        if(!$nids){
            return false;
        }*/
        $nids = $roleInfo['grant'];
        $roleNodeWhere = [];
        $roleNodeWhere['id'] = ['IN', $nids];
        $roleNodeWhere['status'] = 1;
        $roleNodes = M('platform_node')->where($roleNodeWhere)->select();

        /*$nm = M('platform_node');
        $nodeList = $nm->where("status=1 and id in({$nids})")->select();
        //存缓存
        if($nodeList && is_array($nodeList)){
            S("adminapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}",$nodeList,300);
        }*/
        return $roleNodes;
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


}