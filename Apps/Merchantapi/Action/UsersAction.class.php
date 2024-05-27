<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员控制器
 */
class UsersAction extends BaseAction {
    /**
     * 跳去登录界面
     */
	public function login(){
		//如果已经登录了则直接跳去后台
		$USER = session('WST_USER');
		if(!empty($USER) && $USER['userId']!=''){
			$this->redirect("Users/index");
		}
		if(isset($_COOKIE["loginName"])){
			$this->assign('loginName',$_COOKIE["loginName"]);
		}else{
			$this->assign('loginName','');
		}
		$this->assign('qqBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/qqlogin.php"));
		$this->assign('wxBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/wxlogin.php"));
		$this->display('default/login');
	}


	/**
	 * 用户退出
	 */
	public function logout(){
		session('WST_USER',null);
		setcookie("loginPwd", null);
		echo "1";
	}

	/**
     * 注册界面
     *
     */
	public function regist(){
		if(isset($_COOKIE["loginName"])){
			$this->assign('loginName',$_COOKIE["loginName"]);
		}else{
			$this->assign('loginName','');
		}
		$this->display('default/regist');
	}

	/**
	 * 验证登陆
	 *
	 */
	public function checkLogin(){
	    $rs = array();
	    $rs["status"]= 1;
		if(!$this->checkVerify("4") && ($GLOBALS['CONFIG']["captcha_model"]["valueRange"]!="" && strpos($GLOBALS['CONFIG']["captcha_model"]["valueRange"],"3")>=0)){
			$rs["status"]= -1;//验证码错误
		}else{
			$m = D('Home/Users');
			$res = $m->checkLogin();
			if (!empty($res)){
				if($res['userFlag'] == 1){
					session('WST_USER',$res);
					unset($_SESSION['toref']);
					if(strripos($_SESSION['refer'],"regist")>0 || strripos($_SESSION['refer'],"logout")>0 || strripos($_SESSION['refer'],"login")>0){
						$rs["refer"]= __ROOT__;
					}
				}else if($res['status'] == -1){
					$rs["status"]= -2;//登陆失败，账号或密码错误
				}
			} else {
				$rs["status"]= -2;//登陆失败，账号或密码错误
			}

			$rs["refer"]= $rs['refer']?$rs['refer']:__ROOT__;
		}
		echo json_encode($rs);
	}

	/**
	 * 新用户注册
	 */
	public function toRegist(){

		$m = D('Home/Users');
		$res = array();
		$nameType = (int)I("nameType");
		if($nameType!=3 && !$this->checkVerify("3")){
			$res['status'] = -4;
			$res['msg'] = '验证码错误!';
		}else{
			$res = $m->regist();
			if($res['userId']>0){//注册成功
				//加载用户信息
				$user = $m->get($res['userId']);
				if(!empty($user))session('WST_USER',$user);

			}
		}
		echo json_encode($res);

	}

 	/**
	 * 获取验证码
	 */
	public function getPhoneVerifyCode(){
		$userPhone = WSTAddslashes(I("userPhone"));
		$rs = array();
		if(!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#",$userPhone)){
			$rs["msg"] = '手机号格式不正确!';
			echo json_encode($rs);
			exit();
		}
		$m = D('Home/Users');
		$rs = $m->checkUserPhone($userPhone,(int)session('WST_USER.userId'));
		if($rs["status"]!=1){
			$rs["msg"] = '手机号已存在!';
			echo json_encode($rs);
			exit();
		}
		$phoneVerify = rand(100000,999999);
		$msg = "欢迎您注册成为".$GLOBALS['CONFIG']['mallName']."会员，您的注册验证码为:".$phoneVerify."，请在30分钟内输入。";
		$rv = D('Home/LogSms')->sendSMS(0,$userPhone,$msg,'getPhoneVerifyByRegister',$phoneVerify);
		if($rv['status']==1){
			session('VerifyCode_userPhone',$phoneVerify);
			session('VerifyCode_userPhone_Time',time());
			//$rs["phoneVerifyCode"] = $phoneVerify;
		}
		echo json_encode($rv);
	}
   /**
    * 会员中心页面
    */
	public function index(){
		$this->isUserLogin();
		$this->redirect("Orders/queryByPage");
	}

   /**
    * 跳到修改用户密码
    */
	public function toEditPass(){
		$shopInfo = $this->MemberVeri();
		$this->assign("umark","toEditPass");
		$this->display("default/users/edit_pass");
	}

	/**
	 * 修改用户密码
	 */
	public function editPass(){
		$loginUserInfo = $this->MemberVeri();
		$password = I('password');
		$confirmPassword = I('confirmPassword');
		if(empty($password) || empty($confirmPassword)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请补全页面中的信息'));
        }
		if($password != $confirmPassword){
            $this->ajaxReturn(returnData(false, -1, 'error', '两次输入的不一致'));
        }
		$m = D('Home/Users');
   		$rs = $m->editPass($loginUserInfo,$password);
    	$this->ajaxReturn($rs);
	}
	/**
	 * 跳去修改买家资料
	 */
	public function toEdit(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/Users');
		$obj["userId"] = session('WST_USER.userId');
		$user = $m->getUserById($obj);

		//判断会员等级
		$USER = session('WST_USER');
		$rm = D('Home/UserRanks');
		$USER["userRank"] = $rm->getUserRank();
		session('WST_USER',$USER);

		$this->assign("user",$user);
		$this->assign("umark","toEditUser");
		$this->display("default/users/edit_user");
	}

	/**
	 * 跳去修改买家资料
	 */
	public function editUser(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/Users');
		$obj["userId"] = session('WST_USER.userId');
		$data = $m->editUser($obj);

		$this->ajaxReturn($data);
	}

	/**
	 * 判断手机或邮箱是否存在
	 */
	public function checkLoginKey(){
		$m = D('Home/Users');

		$key = I('clientid');
        $key = $key?$key:'userPhone';
		$userId = (int)session('WST_USER.userId');
		$rs = $m->checkLoginKey(I($key),$userId);
		if($rs['status']==1){
			$rs['msg'] = "该账号可用";
		}else if($rs['status']==-2){
			$rs['msg'] = "不能使用该账号";
		}else{
			$rs['msg'] = "该账号已存在";
		}
		$this->ajaxReturn($rs);
	}
	/**
	 * 忘记密码
	 */
    public function forgetPass(){
    	session('step',1);
    	$this->display('default/forget_pass');
    }

    /**
     * 找回密码
     */
//    public function findPass(){
//        //禁止缓存
//        header('Cache-Control:no-cache,must-revalidate');
//        header('Pragma:no-cache');
//        $step = (int)I('step');
//        $response = [
//            'status' => -1,
//            'msg' => '请求失败',
//        ];
//        switch ($step) {
//            case 1:#第二步，验证身份
//                if (!$this->checkCodeVerify(false)) {
//                    $response['msg'] = '验证码错误';
//                    $this->ajaxReturn($response);
//                }
//                $loginName = WSTAddslashes(I('loginName'));
//                $m = D('Home/Users');
//                $info = $m->checkAndGetLoginInfo($loginName);
//                if ($info != false) {
//                    session('findPass',array('userId'=>$info['userId'],'loginName'=>$loginName,'userPhone'=>$info['userPhone'],'userEmail'=>$info['userEmail'],'loginSecret'=>$info['loginSecret']) );
//                    if($info['userPhone']!='')$info['userPhone'] = WSTStrReplace($info['userPhone'],'*',3);
//                    if($info['userEmail']!='')$info['userEmail'] = WSTStrReplace($info['userEmail'],'*',2,'@');
//                    $response['status'] = 0;
//                    $response['msg'] = '用户信息获取成功';
//                    $response['data'] = $info;
//                }else{
//                    $response['msg'] = '该用户不存在！';
//                }
//                $this->ajaxReturn($response);
//                break;
//            case 2:#第三步,设置新密码
//                if (session('findPass.loginName') != null ){
//                    if(I('type') == 'email'){
//                        if (session('findPass.userEmail')==null) {
//                            $response['msg'] = '你没有预留邮箱，请通过手机号码找回密码！';
//                            $this->ajaxReturn($response);
//                        }
//                        $this->getEmailVerify();
//                    }else{
//                        if ( session('findPass.userPhone') == null) {
//                            $response['msg'] = '你没有预留手机号码，请通过邮箱方式找回密码！';
//                            $this->ajaxReturn($response);
//                        }
//                        $codeData['smsCode'] = I('mobileCode');
//                        $codeData['smsReturnCode'] = 1;
//                        $codeInfo = D('Home/LogSms')->smsInfo($codeData);
//                        if($codeInfo){
//                            $response['status'] = 0;
//                            $response['msg'] = '校检码正确';
//                        }else{
//                            $response['msg'] = '校检码不正确';
//                        }
//                        $this->ajaxReturn($response);
//                    }
//
//                }else{
//                    $response['msg'] = '页面过期！';
//                }
//                $this->ajaxReturn($response);
//                break;
//            case 3:#设置成功
//                /*$resetPass = session('REST_success');
//                if($resetPass!='1'){
//                    $response['msg'] = '非法的操作!';
//                    $this->ajaxReturn($response);
//                }*/
//                $loginPwd = I('loginPwd');
//                $repassword = I('repassword');
//                if ($loginPwd == $repassword) {
//                    $rs = D('Home/Users')->resetPass();
//                    if($rs['status']==1){
//                        $response['status'] = 0;
//                        $response['msg'] = '密码重置成功';
//                    }else{
//                        $response['msg'] = '密码重置失败';
//                    }
//                    $this->ajaxReturn($response);
//                }else{
//                    $response['msg'] = '两次密码不同!';
//                    $this->ajaxReturn($response);
//                };
//                break;
//            default:
//                $response['msg'] = '页面过期!';
//                $this->ajaxReturn($response);
//                break;
//        }
//    }

    /**
     * 门店找回密码---弃用
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/rmztni
     */
    public function findPass(){
        //禁止缓存
        header('Cache-Control:no-cache,must-revalidate');
        header('Pragma:no-cache');
        $step = (int)I('step');//步骤(1:步骤1-填写账户名 2:步骤2-验证身份 3:步骤3-设置新密码)
        $params = I();
        if(!in_array($step,array(1,2,3))){
//            $response = [
//                'status' => -1,
//                'msg' => '请求失败',
//            ];
            //这里把returnData中的status的值改为0和-1是为了兼容之前的返回值
            $this->ajaxReturn(returnData(false, -1, -1, '参数有误'));
        }
        $model = D('Home/Users');
        $data = $model->findPass($params);
        $this->ajaxReturn($data);
    }

    /**
     * 门店通过账号获取手机号
     * https://www.yuque.com/anthony-6br1r/oq7p0p/cgn7gc
     */
    public function getShopUserInfo(){
        $loginName = I('loginName');
        if(empty($loginName)){
            $this->ajaxReturn(returnData(false, -1, -1, '请输入用户名'));
        }
        $params = [];
        $params['loginName'] = $loginName;
        $model = D('Home/Users');
        $data = $model->getShopUserInfo($params);
        $this->ajaxReturn($data);
    }

    /**
     * 门店通过用户ID获取手机验证码
     * https://www.yuque.com/anthony-6br1r/oq7p0p/ayz2y1
     */
    public function getShopPhoneVerify(){
        $userId = (int)I('userId');
        if(empty($userId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请确认门店账号是否存在'));
        }
        $model = D('Home/Users');
        $params = [];
        $params['userFlag'] = 1;
        $params['userId'] = $userId;
        $userInfo = $model->getUserInfoRow($params);
        if(empty($userInfo)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请确认门店账号是否存在'));
        }
        $userPhone = $userInfo['userPhone'];
        $phoneVerify = mt_rand(100000,999999);
        $msg = "您正在重置登录密码，验证码为:".$phoneVerify."，请在30分钟内输入。";
        $rv = D('Home/LogSms')->sendSMS(0,$userPhone,$msg,'getPhoneVerify',$phoneVerify);
        $rv['time'] = 30 * 60;
        if ($rv['status'] != 1) {
            $msg = !empty($rv['msg']) ? $rv['msg'] : '短信发送失败';
            $this->ajaxReturn(returnData(false, -1, 'error', $msg));
        }
        $this->ajaxReturn(returnData(true));
    }

    /**
     * 门店重置密码
     * 通过userId+验证码+密码 完成重置
     * https://www.yuque.com/anthony-6br1r/oq7p0p/iwmt20
     */
    public function editShopPass(){
        $userId = (int)I('userId');
        $smsCode = (string)I('smsCode');
        $loginPwd = (string)I('loginPwd','','trim');
        if(empty($userId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请确认门店账号是否存在'));
        }
        if(empty($smsCode)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入验证码'));
        }
        if(empty($loginPwd)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入重置密码'));
        }
        $model = D('Home/Users');
        $params = [];
        $params['userId'] = $userId;
        $params['smsCode'] = $smsCode;
        $params['loginPwd'] = $loginPwd;
        $data = $model->editShopPass($params);
        $this->ajaxReturn($data);
    }

	/**
	 * 手机验证码获取
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sqxxfk
	 */
	public function getPhoneVerify(){
	    $userPhone = (string)I('userPhone');
		if(empty($userPhone)){
            $this->ajaxReturn(returnData(false, -1, 'error', '手机号必填'));
        }
		$phoneVerify = mt_rand(100000,999999);
		$msg = "您正在重置登录密码，验证码为:".$phoneVerify."，请在30分钟内输入。";
		$rv = D('Home/LogSms')->sendSMS(0,$userPhone,$msg,'getPhoneVerify',$phoneVerify);
        $rv['time'] = 30 * 60;
        if ($rv['status'] != 1) {
            $msg = !empty($rv['msg']) ? $rv['msg'] : '短信发送失败';
            $this->ajaxReturn(returnData(false, -1, 'error', $msg));
        }
		$this->ajaxReturn(returnData(true));
	}

	/**
	 * 手机验证码检测
	 * -1 错误，1正确
	 */
	public function checkPhoneVerify(){
		$phoneVerify = I('phoneVerify');
		$rs = array('status'=>-1);
		if (session('findPass.phoneVerify') == $phoneVerify ) {
			//获取用户信息
			$user = D('Home/Users')->checkAndGetLoginInfo(session('findPass.userPhone'));
			$rs['u'] = $user;
			if(!empty($user)){
				$rs['status'] = 1;
				$keyFactory = new \Think\Crypt();
			    $key = $keyFactory->encrypt("0_".$user['userId']."_".time(),C('SESSION_PREFIX'),30*60);
				$rs['url'] = "http://".$_SERVER['HTTP_HOST'].U('Home/Users/toResetPass',array('key'=>$key));
			}
		}
		$this->ajaxReturn($rs);
	}

	/**
	 * 发送验证邮件
	 */
	public function getEmailVerify(){
		$rs = array('status'=>-1);
		$keyFactory = new \Think\Crypt();
		$key = $keyFactory->encrypt("0_".session('findPass.userId')."_".time(),C('SESSION_PREFIX'),30*60);
		$url = "http://".$_SERVER['HTTP_HOST'].U('Home/Users/toResetPass',array('key'=>$key));
		$html="您好，会员 ".session('findPass.loginName')."：<br>
		您在".date('Y-m-d H:i:s')."发出了重置密码的请求,请点击以下链接进行密码重置:<br>
		<a href='".$url."'>".$url."</a><br>
		<br>如果您的邮箱不支持链接点击，请将以上链接地址拷贝到你的浏览器地址栏中。<br>
		该验证邮件有效期为30分钟，超时请重新发送邮件。<br>
		<br><br>*此邮件为系统自动发出的，请勿直接回复。";
		$sendRs = WSTSendMail(session('findPass.userEmail'),'密码重置',$html);
		if($sendRs['status']==1){
			$rs['status'] = 1;
		}else{
			$rs['msg'] = $sendRs['msg'];
		}
		$this->ajaxReturn($rs);
	}

    /**
     * 跳到重置密码
     */
    public function toResetPass(){
    	$key = I('key');
	    $keyFactory = new \Think\Crypt();
		$key = $keyFactory->decrypt($key,C('SESSION_PREFIX'));
		$key = explode('_',$key);
		if(time()>floatval($key[2])+30*60)$this->error('连接已失效！');
		if(intval($key[1])==0)$this->error('无效的用户！');
		session('REST_userId',$key[1]);
		session('REST_Time',$key[2]);
		session('REST_success','1');
		$this->display('default/forget_pass3');
    }

    /**
     * 跳去用户登录的页面
     */
    public function toLoginBox(){
        if(isset($_COOKIE["loginName"])){
			$this->assign('loginName',$_COOKIE["loginName"]);
		}else{
			$this->assign('loginName','');
		}
		$this->assign('qqBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/qqlogin.php"));
		$this->assign('wxBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/wxlogin.php"));
    	$this->display('default/login_box');
    }

    /**
     * 查看积分记录
     */
    public function toScoreList(){
    	$this->isUserLogin();
    	$um = D('Home/Users');
    	$user = $um->getUserById(array("userId"=>session('WST_USER.userId')));
    	$this->assign("userScore",$user['userScore']);
    	$this->assign("umark","toScoreList");
    	$this->display("default/users/score_list");
    }

    /**
     * 查看积分记录
     */
    public function getScoreList(){
    	$this->isUserLogin();
    	$m = D('Home/UserScore');
    	$rs = $m->getScoreList();
    	$this->ajaxReturn($rs);
    }

    /**
     * QQ登录回调方法
     */
	public function qqLoginCallback(){
    	header ( "Content-type: text/html; charset=utf-8" );
    	vendor ( 'ThirdLogin.QqLogin' );

    	$appId = $GLOBALS['CONFIG']["qqAppId"];
    	$appKey = $GLOBALS['CONFIG']["qqAppKey"];
    	//回调接口，接受QQ服务器返回的信息的脚本
    	$callbackUrl = WSTDomain()."/Wstapi/thridLogin/qqlogin.php";
    	//实例化qq登陆类，传入上面三个参数
    	$qq = new \QqLogin($appId,$appKey,$callbackUrl);
    	//得到access_token验证值
    	$accessToken = $qq->getToken();
    	if(!$accessToken){
    		$this->redirect("Home/Users/login");
    	}
    	//得到用户的openid(登陆用户的识别码)和Client_id
    	$arr = $qq->getClientId($accessToken);
    	if(isset($arr['client_id'])){
    		$clientId = $arr['client_id'];
    		$openId = $arr['openid'];
    		$um = D('Home/Users');
    		//已注册，则直接登录
    		if($um->checkThirdIsReg(1,$openId)){
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 1;
    			$rd = $um->thirdLogin($obj);
    			if($rd["status"]==1){
    				$this->redirect("Home/Index/index");
    			}else{
    				$this->redirect("Home/Users/login");
    			}
    		}else{
    			//未注册，则先注册
    			$arr = $qq->getUserInfo($clientId,$openId,$accessToken);
    			$obj["userName"] = $arr["nickname"];
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 1;
    			$obj["userPhoto"] = $arr["figureurl_2"];
    			$um->thirdRegist($obj);
    			$this->redirect("Home/Index/index");
    		}
    	}else{
    		$this->redirect("Home/Users/login");
    	}
    }

    /**
     * 微信登录回调方法
     */
	public function wxLoginCallback(){
    	header ( "Content-type: text/html; charset=utf-8" );
    	vendor ( 'ThirdLogin.WxLogin' );

    	$appId = $GLOBALS['CONFIG']["wxAppId"];
    	$appKey = $GLOBALS['CONFIG']["wxAppKey"];

    	$wx = new \WxLogin($appId,$appKey);
    	//得到access_token验证值
    	$accessToken = $wx->getToken();

    	if(!$accessToken){
    		$this->redirect("Home/Users/login");
    	}
    	//得到用户的openid(登陆用户的识别码)和Client_id
    	$openId = $wx->getOpenId();
    	if($openId!=""){
    		$um = D('Home/Users');
    		//已注册，则直接登录
    		if($um->checkThirdIsReg(2,$openId)){
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 2;
    			$rd = $um->thirdLogin($obj);
    			if($rd["status"]==1){
    				$this->redirect("Home/Index/index");
    			}else{
    				$this->redirect("Home/Users/login");
    			}
    		}else{
    			//未注册，则先注册
    			$arr = $wx->getUserInfo($openId,$accessToken);
    			$obj["userName"] = $arr["nickname"];
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 2;
    			$obj["userPhoto"] = $arr["headimgurl"];
    			$um->thirdRegist($obj);
    			$this->redirect("Home/Index/index");
    		}
    	}else{
    		$this->redirect("Home/Users/login");
    	}
    }

    /**
     * 用户(会员)登陆,并获取会员信息
     * 使用账号和密码登录
     */
    public function userLogin(){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '登陆失败';
        $apiRes['apiState'] = 'error';
        $apiRes['apiData'] = null;

        $mobileNumber = I("loginName");
        $loginPwd = I("loginPwd");

        if (empty($mobileNumber) || empty($loginPwd)) {
            $apiRes['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRes);
        }

        $users = M("users");
        $loginSecret = $users->where("loginName = '{$mobileNumber}'")->field(array("loginSecret"))->find();//获取安全码
        $where['loginName'] = $mobileNumber;
        $where['userFlag'] = 1;
        $where['loginPwd'] = md5($loginPwd . $loginSecret['loginSecret']);
        $mod = $users->where($where)->find();
        if (empty($mod)) {
            $apiRes["apiInfo"] = "账号或者密码错误哦";
            $this->ajaxReturn($apiRes);
        }

        $mod['memberToken'] = getUserTokenByUserId($mod['userId']);
        $mod['historyConsumeIntegral'] = historyConsumeIntegral($mod['userId']);

        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '登陆成功';
        $apiRes['apiState'] = 'success';
        $apiRes['apiData'] = $mod;
        $this->ajaxReturn($apiRes);
    }

    /**
     * 绑卡 或 换卡
     */
    public function bindOrReplaceCard(){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '登陆失败';
        $apiRes['apiState'] = 'error';
        $apiRes['apiData'] = null;

        $memberToken = I('memberToken','','trim');
        $card = I('card','','trim');
        $card_arr = explode('K',$card);
        $cardNum = trim($card_arr[0]);
        $userId = intval($card_arr[1]);

        if (empty($card) || empty($cardNum) || empty($userId) || empty($memberToken)) {
            $apiRes['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRes);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRes['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRes);
        }

        if ($user_info['userId'] != $userId) {
            $apiRes['apiInfo'] = '卡异常';
            $this->ajaxReturn($apiRes);
        }

        $users = M("users");
        $user_data = $users->where(array('cardNum'=>$cardNum,'userFlag'=>1))->find();
        if (!empty($user_data)) {
            $apiRes['apiInfo'] = '卡已被使用,请换一张';
            $this->ajaxReturn($apiRes);
        }
        $user_info = $users->where(array('userId'=>$userId))->find();
        $users->where(array('userId'=>$userId))->save(array('cardNum'=>$cardNum));

        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '绑卡成功';
        $apiRes['apiState'] = 'success';
        $apiRes['apiData'] = $users->where(array('userId'=>$userId))->find();
        if (!empty($user_info['cardNum'])) {
            $apiRes["apiInfo"] = "换卡成功";
        }

        $this->ajaxReturn($apiRes);
    }

    /**
     * 挂失 或 解除挂失
     */
    public function changeCardState(){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '操作失败';
        $apiRes['apiState'] = 'error';
        $apiRes['apiData'] = null;

        $memberToken = I('memberToken','','trim');
        $cardState = I('cardState',0,'intval');//状态 -1：挂失 1：正常

        if (empty($memberToken) || empty($cardState)) {
            $apiRes['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRes);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $userId = $user_info['userId'];

        $users = M("users");
        $users->where(array('userId'=>$userId))->save(array('cardState'=>$cardState));

        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '解除挂失成功';
        $apiRes['apiState'] = 'success';
        $apiRes['apiData'] = $users->where(array('userId'=>$userId))->find();
        if ($cardState == -1) {
            $apiRes["apiInfo"] = "挂失成功";
        }

        $this->ajaxReturn($apiRes);
    }
}