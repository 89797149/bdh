<?php

namespace V3\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 基础控制器
 *
 */

/**
 * 接口规范 使用范围务尽量在模型中
 * code (0：没问题,-1：错误,401:身份异常特别状态)
 * msg  (用户级别友好提示)
 * info (开发人员排错信息)
 * status (error,success)
 * time  (接口返回时间戳)
 * data  (无数据返回null)
 */


use Think\Controller;

class BaseAction extends Controller
{

    public function __construct()
    {
        parent::__construct();

        //统一shopId接收风格
        $_POST['shopid'] = $_POST['shopId'];
        $_GET['shopid'] = $_POST['shopId'];


        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        $_POST['memberToken'] = $headers['Membertoken'];
        $_GET['memberToken'] = $headers['Membertoken'];


        //跨域支持
        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin: ' . @$_SERVER['HTTP_ORIGIN']);
        // 响应类型
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE');
        header('Access-Control-Allow-Credentials: true');
        // 响应头设置

        header('Access-Control-Allow-Headers:x-requested-with,content-type,membertoken,memberToken');
        // header('Access-Control-Allow-Headers:'.$_SERVER['Access-Control-Allow-Headers']);

        /* $token = I("token");
        if(Niao_token($token) !== true){
            if(I("apiAll") == 1){return "error";}else{$this->ajaxReturn("error");}//返回方式处理
        } */
        //初始化系统信息
        $m = D('V3/System');
        $GLOBALS['CONFIG'] = $m->loadConfigs();

        //判断是否存在定制
        self::isrules_made();
    }


    //---------------比如通过参数控制  先测试node是否好使 可以的话就用这个方式 这个方式真正做到了聚合接口
    public function allurl()
    {
        header('Content-Type:application/json; charset=utf-8');

        $_POST['apiAll'] = 1;//表示本次调用为聚合
        $_GET['apiAll'] = 1;

        //统一shopId接收风格 防止合并接口时改变变量失败
        $_POST['shopid'] = $_POST['shopId'];
        $_GET['shopid'] = $_POST['shopId'];


        //覆盖系统returnajax方法

        // $this->ajaxReturn = function($e){
        // 	echo '23';
        // };

        $ret['code'] = -1;
        $ret['msg'] = '失败';
        $ret['data'] = [];

        $pack = I('pack');
        if (empty($pack)) {
            // $ret['msg'] = 'pack参数不能为空';

            exit(json_encode(returnData(null, -1, 'error', 'pack参数不能为空')));
        }
        $pack = htmlspecialchars_decode($pack);
        $pack = json_decode($pack, true);

        foreach ($pack['urls'] as $k => $v) {
            $data = explode('/', $v['url']);
            if (count($data) < 3) {
                // $ret['msg'] = '地址不对';

                exit(json_encode(returnData(null, -1, 'error', '地址不对')));
            }
            $action = A(ucfirst($data[0]) . '/' . ucfirst($data[1]));

            //解析并 更改相关参数
            //装载公用参数
            foreach ($pack['commomParam'] as $k1 => $v1) {
                if ($k1 == 'shopId') {
                    $_POST['shopid'] = $v1;
                    $_GET['shopid'] = $v1;
                }

                $_POST[$k1] = $v1;
                $_GET[$k1] = $v1;

            }
            //装载私有参数
            foreach ($pack['privateParam'][$v['name']] as $k2 => $v2) {

                if ($k2 == 'shopId') {
                    $_POST['shopid'] = $v2;
                    $_GET['shopid'] = $v2;
                }


                $_POST[$k2] = $v2;
                $_GET[$k2] = $v2;
            }

            $t1 = microtime(true); //获取程序1，开始的时间
            $ret['data'][$v['name']]['body'] = call_user_func_array(array($action, $data[2]), array());//赋值给自定义的name
            $t2 = microtime(true); //获取程序1，结束的时间

            //记录每个接口调用时间
            $time1 = $t2 - $t1;
            $ret['data'][$v['name']]['taketime'] = $time1;

        }


        $ret['code'] = 0;
        $ret['msg'] = '成功';
        exit(json_encode(returnData($ret['data'])));


        // echo call_user_func(array($action,"getOrderID"));
        // echo call_user_func_array('test1',[]);
        // echo call_user_func_array(array($this, "test1"));

        // $this->getOrderID();
    }

    /**
     * 空操作处理
     */
    public function _empty($name)
    {
        $status = returnData(null, -1, 'error', '请求去了火星');
        if (I("apiAll") == 1) {
            return $status;
        } else {
            $this->ajaxReturn($status);
        }//返回方式处理
    }

    //会员身份验证 成功即返回用户信息
    public function MemberVeri()
    {
        $memberToken = I("memberToken");


        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        if (empty($memberToken)) {
            $memberToken = $headers['Membertoken'];
        }


        if (empty($memberToken)) {
            // $status['apiCode'] = -1;
            // $status['apiInfo'] = "token字段有误";
            // $status['apiState'] = "error";

            $status = returnData(null, 401, 'error', '认证失效，请重新登陆', 'token字段有误');

            if (I("apiAll") == 1) {
                return $status;
            } else {
                $this->ajaxReturn($status);
            }//返回方式处理
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken, 86400 * 30);//查询token

        if (empty($sessionData['userId'])) {
            // $status['apiCode'] = "000005";
            // $status['apiInfo'] = "认证失效，请重新登陆";
            // $status['apiState'] = "error";

            $status = returnData(null, 401, 'error', '认证失效，请重新登陆');
            if (I("apiAll") == 1) {
                return $status;
            } else {
                $this->ajaxReturn($status);
            }//返回方式处理
        }


        return $sessionData;
    }


    //仅仅会员身份获取 成功即返回用户信息
    public function getMemberInfo()
    {
        $memberToken = I("memberToken");


        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        if (empty($memberToken)) {
            $memberToken = $headers['Membertoken'];
        }


        $sessionData = userTokenFind($memberToken, 86400 * 30);//查询token

        if (empty($sessionData)) {
            return false;
        }
        return $sessionData;
    }

    /**
     * ajax程序验证,只要不是会员都返回-999
     */
    public function isUserLogin()
    {
        $USER = session('WST_USER');
        if (empty($USER) || ($USER['userId'] == '')) {
            if (IS_AJAX) {
                if (I("apiAll") == 1) {
                    return array('status' => -999, 'url' => 'Users/login');
                } else {
                    $this->ajaxReturn(array('status' => -999, 'url' => 'Users/login'));
                }//返回方式处理
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
                if (I("apiAll") == 1) {
                    return array('status' => -999, 'url' => 'Shops/login');
                } else {
                    $this->ajaxReturn(array('status' => -999, 'url' => 'Shops/login'));
                }//返回方式处理
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
                if (I("apiAll") == 1) {
                    return array('status' => -999, 'url' => $userType . '/login');
                } else {
                    $this->ajaxReturn(array('status' => -999, 'url' => $userType . '/login'));
                }//返回方式处理
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
        else if (I("apiAll") == 1) {
            return array('status' => (int)$rs);
        } else {
            $this->ajaxReturn(array('status' => (int)$rs));
        }//返回方式处理
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