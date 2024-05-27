<?php
namespace Home\Action;
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
		$this->isLogin();
		$this->assign("umark","toEditPass");
		$this->display("default/users/edit_pass");
	}
	
	/**
	 * 修改用户密码
	 */
	public function editPass(){
		$this->isLogin();
		$USER = session('WST_USER');
		$m = D('Home/Users');
   		$rs = $m->editPass($USER['userId']);
    	$this->ajaxReturn($rs);
	}
	/**
	 * 跳去修改买家资料
	 */
	public function toEdit(){
		$this->isLogin();
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
		$this->isLogin();
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
    public function findPass(){
    	//禁止缓存
    	header('Cache-Control:no-cache,must-revalidate');  
		header('Pragma:no-cache');
    	$step = (int)I('step');
    	$response = [
    	    'status' => -1,
    	    'msg' => '请求失败',
        ];
    	switch ($step) {
    		case 1:#第二步，验证身份
    			if (!$this->checkCodeVerify(false)) {
    			    $response['msg'] = '验证码错误';
    			    $this->ajaxReturn($response);
    			}
    			$loginName = WSTAddslashes(I('loginName'));
    			$m = D('Home/Users');
    			$info = $m->checkAndGetLoginInfo($loginName);
    			if ($info != false) {
    				session('findPass',array('userId'=>$info['userId'],'loginName'=>$loginName,'userPhone'=>$info['userPhone'],'userEmail'=>$info['userEmail'],'loginSecret'=>$info['loginSecret']) );
    				if($info['userPhone']!='')$info['userPhone'] = WSTStrReplace($info['userPhone'],'*',3);
    				if($info['userEmail']!='')$info['userEmail'] = WSTStrReplace($info['userEmail'],'*',2,'@');
    				$response['status'] = 0;
    				$response['msg'] = '用户信息获取成功';
    				$response['data'] = $info;
    			}else{
                    $response['msg'] = '该用户不存在！';
                }
                $this->ajaxReturn($response);
    			break;
    		case 2:#第三步,设置新密码
    			if (session('findPass.loginName') != null ){
    			    if(I('type') == 'email'){
                        if (session('findPass.userEmail')==null) {
                            $response['msg'] = '你没有预留邮箱，请通过手机号码找回密码！';
                            $this->ajaxReturn($response);
                        }
                        $this->getEmailVerify();
                    }else{
                        if ( session('findPass.userPhone') == null) {
                            $response['msg'] = '你没有预留手机号码，请通过邮箱方式找回密码！';
                            $this->ajaxReturn($response);
                        }
                        $codeData['smsCode'] = I('mobileCode');
                        $codeData['smsReturnCode'] = 1;
                        $codeInfo = D('Home/LogSms')->smsInfo($codeData);
                        if($codeInfo){
                            $response['status'] = 0;
                            $response['msg'] = '校检码正确';
                        }else{
                            $response['msg'] = '校检码不正确';
                        }
                        $this->ajaxReturn($response);
                    }

    			}else{
                    $response['msg'] = '页面过期！';
                }
                $this->ajaxReturn($response);
    			break;
    		case 3:#设置成功
    			/*$resetPass = session('REST_success');
    			if($resetPass!='1'){
                    $response['msg'] = '非法的操作!';
                    $this->ajaxReturn($response);
                }*/
                $loginPwd = I('loginPwd');
                $repassword = I('repassword');
                if ($loginPwd == $repassword) {
	                $rs = D('Home/Users')->resetPass();
			    	if($rs['status']==1){
                        $response['status'] = 0;
                        $response['msg'] = '密码重置成功';
			    	}else{
                        $response['msg'] = '密码重置失败';
			    	}
                    $this->ajaxReturn($response);
                }else{
                    $response['msg'] = '两次密码不同!';
                    $this->ajaxReturn($response);
                };
    			break;
    		default:
                $response['msg'] = '页面过期!';
                $this->ajaxReturn($response);
    			break;
    	}  	
    }


	/**
	 * 手机验证码获取
	 */
	public function getPhoneVerify(){
		$rs = array('status'=>-1);
		if(session('findPass.userPhone')==''){
			$this->ajaxReturn($rs);
		}
		$phoneVerify = mt_rand(100000,999999);
		$USER = session('findPass');
		$USER['phoneVerify'] = $phoneVerify;
        session('findPass',$USER);
		$msg = "您正在重置登录密码，验证码为:".$phoneVerify."，请在30分钟内输入。";
		$rv = D('Home/LogSms')->sendSMS(0,session('findPass.userPhone'),$msg,'getPhoneVerify',$phoneVerify);
		$rv['time']=30*60;
		$this->ajaxReturn($rv);
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
     * 会员短信验证码登录，并返回会员信息
     */
    public function userLoginByCode($flag = 0){
        $phone = I('mobile','','trim');
//        $sessionPhone = session('VerifyCode_userPhone');
//        $verify = session('VerifyCode_userPhone_verify');
//        $startTime = (int)session('VerifyCode_userPhone_Time');
        $sessionPhone = S('VerifyCode_userPhone_'.$phone);
        $verify = S('VerifyCode_userPhone_verify_'.$phone);
        $startTime = (int)S('VerifyCode_userPhone_Time_'.$phone);
        $mobileCode = I("mobileCode");

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '登录失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        if (empty($phone) || empty($mobileCode)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        //有效时间为30分钟
        if((time()-$startTime) > 1800) {
            $apiRet['apiInfo'] = '短信验证码已失效';
            $this->ajaxReturn($apiRet);
        }

        if($verify != $mobileCode || $sessionPhone != $phone) {
            $apiRet['apiInfo'] = '手机号或短信验证码错误';
            $this->ajaxReturn($apiRet);
        }

        $data = D('Home/Users')->getUserInfoRow(array('userPhone'=>$phone));

        if (!empty($data)) {
            $data['memberToken'] = getUserTokenByUserId($data['userId']);
            $data['historyConsumeIntegral'] = historyConsumeIntegral($data['userId']);

            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '登录成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $data;
        }
        if ($flag > 0) {
            return $apiRet;
        }else {
            $this->ajaxReturn($apiRet);
        }
    }

    /**
     * 更改密码
     */
    public function updateUserPassword($memberToken = ''){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '更改密码失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $memberToken = !empty($memberToken) ? $memberToken : I('memberToken','','trim');
        $password = I('password','','trim');
        $confirmPassword = I('confirmPassword','','trim');

        if (empty($memberToken) || empty($password) || empty($confirmPassword)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $userId = $user_info['userId'];

        if ($password != $confirmPassword) {
            $apiRet['apiInfo'] = '两次输入的密码不一致';
            $this->ajaxReturn($apiRet);
        }

        $um = M('users');
        $where = array('userId'=>$userId);
        $user_info = $um->where($where)->find();
        if (empty($user_info)) {
            $apiRet['apiInfo'] = '会员不存在';
            $this->ajaxReturn($apiRet);
        }

        $password_new = md5($password.$user_info['loginSecret']);
        if ($user_info['loginPwd'] == $password_new) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '更改密码成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $user_info;
            $this->ajaxReturn($apiRet);
        }

        $save_data = array('loginPwd'=>$password_new);
        $result = D('Home/Users')->updateUser($where,$save_data);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '更改密码成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $um->where($where)->find();
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 新用户注册
     */
    public function userRegister(){
        $result = D('Home/Users')->userRegister();
        $this->ajaxReturn($result);
    }

    /**
     * 忘记密码
     */
    public function forgetPassword(){
        $user_data = $this->userLoginByCode(1);
        if ($user_data['apiCode'] == 0) {
            $memberToken = $user_data['apiData']['memberToken'];
            $this->updateUserPassword($memberToken);
        }
    }

    /**
     * 编辑会员信息
     */
    public function updateUsers(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $memberToken = I('memberToken','','trim');
        $data = I('param.');
        if (empty($data['memberToken']) || empty($data['loginPwd']) || empty($data['confirmPassword']) || empty($data['userName'])){
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $userId = $user_info['userId'];

        if ($data['loginPwd'] != $data['confirmPassword']) {
            $apiRet['apiInfo'] = '两次输入的密码不一致';
            $this->ajaxReturn($apiRet);
        }

        $um = M('users');
        $where = array('userId'=>$userId);
        $user_info = $um->where($where)->find();
        if (empty($user_info)) {
            $apiRet['apiInfo'] = '会员不存在';
            $this->ajaxReturn($apiRet);
        }

        $password_new = md5($data['loginPwd'].$user_info['loginSecret']);
        $data['loginPwd'] = $password_new;
        unset($data['userId']);
        unset($data['confirmPassword']);
        unset($data['memberToken']);

        $result = D('Home/Users')->updateUser($where,$data);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $um->where($where)->find();
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 查看会员余额记录
     */
    public function getUserBalanceList(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $memberToken = I('memberToken','','trim');
        if (empty($memberToken)){
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $userId = $user_info['userId'];

		$page['page']=I('page',1,'intval');
        $page['pageSize']=I('pageSize',10,'intval');

        $m = D('Home/Users');
        $rs = $m->getUserBalanceList($userId,$page);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $rs;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 查看会员积分记录
     */
    public function getScoreListForPos(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $memberToken = I('memberToken','','trim');
        if (empty($memberToken)){
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $userId = $user_info['userId'];

        $page['page']=I('page',1,'intval');
        $page['pageSize']=I('pageSize',10,'intval');

        $m = D('Home/UserScore');
        $rs = $m->getScoreListForPos($userId,$page);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $rs;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 查看POS消费记录
     */
    public function getPosConsumeRecord(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $memberToken = I('memberToken','','trim');
        if (empty($memberToken)){
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $user_info = userTokenFind($memberToken,86400*30);
        if (!$user_info) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $userId = $user_info['userId'];

        $page['page']=I('page',1,'intval');
        $page['pageSize']=I('pageSize',10,'intval');

        $m = D('Home/Users');
        $rs = $m->getPosConsumeRecord($userId,$page);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $rs;

        $this->ajaxReturn($apiRet);
    }
}