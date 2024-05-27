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
class UsersModel extends BaseModel
{

    /**
     * 获取用户信息
     */
    public function get($userId = 0)
    {

        $userId = intval($userId ? $userId : I('id', 0));
        $user = $this->where("userId=" . $userId)->find();
        if (!empty($user) && $user['userType'] == 1) {
            //加载商家信息
            $sp = M('shops');
            $shops = $sp->where('userId=' . $user['userId'] . " and shopFlag=1")->find();
            if (!empty($shops)) $user = array_merge($shops, $user);
        }
        return $user;
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($loginName, $loginPwd)
    {
        $loginPwd = md5($loginPwd);
        $rs = $this->where(" loginName ='%s' AND loginPwd ='%s' ", array($loginName, $loginPwd))->find();
        return $rs;
    }

    /**
     * 获取用户信息
     */
    public function getUserById($obj)
    {
        $userId = (int)$obj["userId"];
        $rs = $this->where(" userId ='%s' ", array($userId))->find();
        return $rs;
    }

    /**
     * 查询登录名是否存在
     */
    public function checkLoginKey($loginName, $id = 0, $isCheckKeys = true)
    {
        $loginName = WSTAddslashes(($loginName != '') ? $loginName : I('loginName'));
        $rd = array('status' => -1);
        if ($loginName == '') return $rd;
        if ($isCheckKeys) {
            if (!WSTCheckFilterWords($loginName, $GLOBALS['CONFIG']['limitAccountKeys'])) {
                $rd['status'] = -2;
                return $rd;
            }
        }
        $sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') and userFlag=1 ";

        if ($id > 0) {
            $sql .= " and userId!=" . $id;
        }
        $rs = $this->where($sql, array($loginName, $loginName, $loginName))->count();
        if ($rs == 0) {
            $rd['status'] = 1;
        }
        return $rd;
    }

    /**
     * 查询并加载用户资料
     */
    public function checkAndGetLoginInfo($key)
    {
        if ($key == '') return array();
        $sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') and userFlag=1 and userStatus=1 ";
        $keyArr = array($key, $key, $key);
        $rs = $this->where($sql, $keyArr)->find();
        return $rs;
    }

    /**
     * 用户登录验证
     */
    public function checkLogin()
    {
        $rv = array('status' => -1);
        $loginName = WSTAddslashes(I('loginName'));
        $userPwd = WSTAddslashes(I('loginPwd'));
        $rememberPwd = I('rememberPwd');
        $sql = "SELECT * FROM __PREFIX__users WHERE (loginName='" . $loginName . "' OR userEmail='" . $loginName . "' OR userPhone='" . $loginName . "') AND userFlag=1 and userStatus=1 ";
        $rss = $this->query($sql);
        if (!empty($rss)) {
            $rs = $rss[0];
            if ($rs['loginPwd'] != md5($userPwd . $rs['loginSecret'])) return $rv;
            if ($rs['userFlag'] == 1 && $rs['userStatus'] == 1) {
                $data = array();
                $data['lastTime'] = date('Y-m-d H:i:s');
                $data['lastIP'] = get_client_ip();
                $this->where(" userId=" . $rs['userId'])->data($data)->save();
                //如果是店铺则加载店铺信息
                if ($rs['userType'] >= 1) {
                    $s = M('shops');
                    $shops = $s->where('userId=' . $rs['userId'] . " and shopFlag=1")->find();
                    if (!empty($shops)) $rs = array_merge($shops, $rs);
                }
                //记录登录日志
                $data = array();
                $data["userId"] = $rs['userId'];
                $data["loginTime"] = date('Y-m-d H:i:s');
                $data["loginIp"] = get_client_ip();
                M('log_user_logins')->add($data);

                $rv = $rs;
                //记住密码
                setcookie("loginName", $loginName, time() + 3600 * 24 * 90);
                if ($rememberPwd == "on") {
                    $datakey = md5($rs['loginName']) . "_" . md5($rs['loginPwd']);
                    $key = C('COOKIE_PREFIX') . "_" . $rs['loginSecret'];
                    //加密
                    $base64 = new \Think\Crypt\Driver\Base64();
                    $loginKey = $base64->encrypt($datakey, $key);
                    setcookie("loginPwd", $loginKey, time() + 3600 * 24 * 90);
                } else {
                    setcookie("loginPwd", null);
                }
            }
        }
        return $rv;
    }

    /**
     * 根据cookie自动登录
     */
    public function autoLoginByCookie()
    {
        $loginName = WSTAddslashes($_COOKIE['loginName']);
        $loginKey = $_COOKIE['loginPwd'];
        if ($loginKey != '' && $loginName != '') {
            $sql = "SELECT * FROM __PREFIX__users WHERE (loginName='" . $loginName . "' OR userEmail='" . $loginName . "' OR userPhone='" . $loginName . "') AND userFlag=1 and userStatus=1 ";
            $rs = $this->queryRow($sql);
            if (!empty($rs) && $rs['userFlag'] == 1 && $rs['userStatus'] == 1) {
                //用数据库的记录记性加密核对
                $datakey = md5($rs['loginName']) . "_" . md5($rs['loginPwd']);
                $key = C('COOKIE_PREFIX') . "_" . $rs['loginSecret'];

                $base64 = new \Think\Crypt\Driver\Base64();
                $compareKey = $base64->encrypt($datakey, $key);
                //验证成功的话则补上登录信息
                if ($compareKey == $loginKey) {
                    $data = array();
                    $data['lastTime'] = date('Y-m-d H:i:s');
                    $data['lastIP'] = get_client_ip();
                    $m = M('users');
                    $m->where(" userId=" . $rs['userId'])->data($data)->save();
                    //如果是店铺则加载店铺信息
                    if ($rs['userType'] >= 1) {
                        $s = M('shops');
                        $shops = $s->where('userId=' . $rs['userId'] . " and shopFlag=1")->find();
                        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
                        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
                        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
                        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
                        $rs = array_merge($shops, $rs);
                    }
                    //记录登录日志
                    $data = array();
                    $data["userId"] = $rs['userId'];
                    $data["loginTime"] = date('Y-m-d H:i:s');
                    $data["loginIp"] = get_client_ip();
                    M('log_user_logins')->add($data);
                    session('WST_USER', $rs);
                }
            }
        }
    }

    /**
     * 会员注册
     */
    public function regist()
    {
        $rd = array('status' => -1);

        $data = array();
        $data['loginName'] = I('loginName', '');
        $data['loginPwd'] = I("loginPwd");
        $data['reUserPwd'] = I("reUserPwd");
        $data['protocol'] = (int)I("protocol");
        $loginName = $data['loginName'];
        //检测账号是否存在
        $crs = $this->checkLoginKey($loginName);
        if ($crs['status'] != 1) {
            $rd['status'] = -2;
            $rd['msg'] = ($crs['status'] == -2) ? "不能使用该账号" : "该账号已存在";
            return $rd;
        }
        if ($data['loginPwd'] != $data['reUserPwd']) {
            $rd['status'] = -3;
            $rd['msg'] = '两次输入密码不一致!';
            return $rd;
        }
        if ($data['protocol'] != 1) {
            $rd['status'] = -6;
            $rd['msg'] = '必须同意使用协议才允许注册!';
            return $rd;
        }
        foreach ($data as $v) {
            if ($v == '') {
                $rd['status'] = -7;
                $rd['msg'] = '注册信息不完整!';
                return $rd;
            }
        }
        $nameType = (int)I("nameType");
        $mobileCode = I("mobileCode");
        if ($nameType == 3 && $GLOBALS['CONFIG']['phoneVerfy'] == 1) {//手机号码
            $verify = session('VerifyCode_userPhone');
            $startTime = (int)session('VerifyCode_userPhone_Time');
            if ((time() - $startTime) > 120) {
                $rd['status'] = -5;
                $rd['msg'] = '验证码已超过有效期!';
                return $rd;
            }
            if ($mobileCode == "" || $verify != $mobileCode) {
                $rd['status'] = -4;
                $rd['msg'] = '验证码错误!';
                return $rd;
            }
            $loginName = $this->randomLoginName($loginName);
        } else if ($nameType == 1) {//邮箱注册
            $unames = explode("@", $loginName);
            $loginName = $this->randomLoginName($unames[0]);
        }
        if ($loginName == '') return $rd;//分派不了登录名
        $data['loginName'] = $loginName;
        unset($data['reUserPwd']);
        unset($data['protocol']);
        //检测账号，邮箱，手机是否存在
        $data["loginSecret"] = rand(1000, 9999);
        $data['loginPwd'] = md5($data['loginPwd'] . $data['loginSecret']);
        $data['userType'] = 0;
        $data['userName'] = I('userName');
        $data['userQQ'] = I('userQQ');
        $data['userPhone'] = I('userPhone');
        $data['userScore'] = I('userScore');
        $data['userEmail'] = I("userEmail");
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['userFlag'] = 1;


        $rs = $this->add($data);
        if (false !== $rs) {
            $rd['status'] = 1;
            $rd['userId'] = $rs;
        }

        if ($rd['status'] > 0) {
            $data = array();
            $data['lastTime'] = date('Y-m-d H:i:s');
            $data['lastIP'] = get_client_ip();
            $this->where("userId=" . $rd['userId'])->data($data)->save();
            //记录登录日志
            $data = array();
            $data["userId"] = $rd['userId'];
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = get_client_ip();
            $m = M('log_user_logins');
            $m->add($data);

        }
        return $rd;
    }

    /**
     * 随机生成一个账号
     */
    public function randomLoginName($loginName)
    {
        $chars = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
        //简单的派字母
        foreach ($chars as $key => $c) {
            $crs = $this->checkLoginKey($loginName . "_" . $c);
            if ($crs['status'] == 1) return $loginName . "_" . $c;
        }
        //随机派三位数值
        for ($i = 0; $i < 1000; $i++) {
            $crs = $this->checkLoginKey($loginName . "_" . $i);
            if ($crs['status'] == 1) return $loginName . "_" . $i;
        }
        return '';
    }

    /**
     * 查询用户手机是否存在
     */
    public function checkUserPhone($userPhone, $userId = 0)
    {
        $userId = $userId > 0 ? $userId : (int)I("userId");
        $rd = array('status' => -3);
        $sql = " userFlag=1 and userPhone='" . $userPhone . "'";
        if ($userId > 0) {
            $sql .= " AND userId <> $userId";
        }
        $rs = $this->where($sql)->count();

        if ($rs == 0) $rd['status'] = 1;
        return $rd;
    }

    /**
     * 修改用户密码
     */
    /*public function editPass($id){
        $rd = array('status'=>-1);
        $data = array();
        $data["loginPwd"] = I("newPass");
        if($this->checkEmpty($data,true)){
            $rs = $this->where('userId='.$id)->find();
            //核对密码
            if($rs['loginPwd']==md5(I("oldPass").$rs['loginSecret'])){
                $data["loginPwd"] = md5(I("newPass").$rs['loginSecret']);
                $rs = $this->where("userId=".$id)->save($data);
                if(false !== $rs){
                    $rd['status']= 1;
                }
            }else{
                $rd['status']= -2;
            }
        }
        return $rd;
    }*/
    public function editPass($loginUserInfo, $password)
    {
        $m = D('Home/User');
        $rd = [];
        if (!empty($loginUserInfo['id'])) {
            //职员
            $id = $loginUserInfo['id'];
            $userTab = M('user');
            $where = [];
            $where['id'] = $id;
            $userInfo = $userTab->where($where)->find();
            if (empty($userInfo)) {
//                $rd['status'] = -2;
//                $rd['msg'] = '修改密码失败，用户信息有误';
//                return $rd;
                return returnData(false, -1, 'error', '修改密码失败，用户信息有误');
            }
            $data = [];
            $addTime = strtotime($userInfo['addtime']);
            $data["pass"] = md5($password . $addTime);
            $res = $userTab->where($where)->save($data);
            if (false !== $res) {
                $m->loginOut($loginUserInfo['token']);
//                $rd['status'] = 1;
//                $rd['msg'] = '修改成功';
//                return $rd;
                return returnData(true);
            } else {
//                $rd['status'] = -2;
//                $rd['msg'] = '修改失败';
//                return $rd;
                return returnData(false, -1, 'error', '修改失败');
            }
        } else {
            //管理员
            $usersTab = M('users');
            $userId = $loginUserInfo['userId'];
            $where = [];
            $where['userFlag'] = 1;
            $where['userId'] = $userId;
            $userInfo = $usersTab->where($where)->find();
            if (empty($userInfo)) {
                return returnData(false, -1, 'error', '修改密码失败，用户信息有误');
//                $rd['status'] = -2;
//                $rd['msg'] = '修改密码失败，用户信息有误';
//                return $rd;
            }
            $data = [];
            $data["loginPwd"] = md5($password . $userInfo['loginSecret']);
            $res = $usersTab->where($where)->save($data);
            if (false !== $res) {
                $m->loginOut($loginUserInfo['token']);
                return returnData(true);
            } else {
                return returnData(false, -1, 'error', '修改失败');
            }
        }
    }

    /**
     * 修改用户资料
     */
    public function editUser($obj)
    {
        $rd = array('status' => -1);
        $userPhone = I("userPhone");
        $userEmail = I("userEmail");
        $userId = (int)$obj["userId"];
        //检测账号是否存在
        $crs = $this->checkLoginKey($userPhone, $userId, false);
        if ($crs['status'] != 1) {
            $rd['status'] = -2;
            return $rd;
        }
        //检测邮箱是否存在
        $crs = $this->checkLoginKey($userEmail, $userId, false);
        if ($crs['status'] != 1) {
            $rd['status'] = -3;
            return $rd;
        }
        $data = array();
        $data["userName"] = I("userName");
        $data["userQQ"] = I("userQQ");
        $data["userPhone"] = $userPhone;
        $data["userSex"] = (int)I("userSex", 0);
        $data["userEmail"] = $userEmail;
        $data["userPhoto"] = I("userPhoto");
        $rs = $this->where(" userId=" . $userId)->data($data)->save();
        if (false !== $rs) {
            $rd['status'] = 1;
            $WST_USER = session('WST_USER');
            $WST_USER['userName'] = $data["userName"];
            $WST_USER['userQQ'] = $data["userQQ"];
            $WST_USER['userSex'] = $data["userSex"];
            $WST_USER['userPhone'] = $data["userPhone"];
            $WST_USER['userEmail'] = $data["userEmail"];
            $WST_USER['userPhoto'] = $data["userPhoto"];
            session('WST_USER', $WST_USER);
        }
        return $rd;
    }

    /**
     * 重置用户密码
     */
    public function resetPass()
    {
        $rs = array('status' => -1);
        //$reset_userId = (int)session('REST_userId');
        $reset_userId = (int)session('findPass.userId');
        if ($reset_userId == 0) {
            $rs['msg'] = '无效的用户！';
            return $rs;
        }
        $user = $this->where("userId=" . $reset_userId . " and userFlag=1 and userStatus=1")->find();
        if (empty($user)) {
            $rs['msg'] = '无效的用户！';
            return $rs;
        }
        $loginPwd = I('loginPwd');
        if (trim($loginPwd) == '') {
            $rs['msg'] = '无效的密码！';
            return $rs;
        }
        $data['loginPwd'] = md5($loginPwd . $user["loginSecret"]);
        $rc = $this->where("userId=" . $reset_userId)->save($data);
        if (false !== $rc) {
            $rs['status'] = 1;
        }
        session('REST_userId', null);
        session('REST_Time', null);
        session('REST_success', null);
        session('findPass', null);
        return $rs;
    }

    /**
     * 检测第三方帐号是否已注册
     */
    public function checkThirdIsReg($userFrom, $openId)
    {
        $openId = WSTAddslashes($openId);
        $sql = "select userId, userName from __PREFIX__users where userFrom=$userFrom and openId='$openId'";
        $row = $this->queryRow($sql);
        if ($row["userId"] > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 第三方注册
     */
    public function thirdRegist($obj)
    {
        $rd = array('status' => -1);

        $data = array();
        $data['loginName'] = $this->randomLoginName(time());
        $data["loginSecret"] = rand(1000, 9999);
        $data['loginPwd'] = "";
        $data['userType'] = 0;
        $data['userName'] = WSTAddslashes($obj["userName"]);
        $data['userQQ'] = "";
        $data['userPhoto'] = $obj["userPhoto"];
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['userFlag'] = 1;
        $data['userFrom'] = $obj["userFrom"];
        $data['openId'] = WSTAddslashes($obj["openId"]);

        $rs = $this->add($data);
        if (false !== $rs) {
            $rd['status'] = 1;
            $rd['userId'] = $rs;
        }

        if ($rd['status'] > 0) {
            $data = array();
            $data['lastTime'] = date('Y-m-d H:i:s');
            $data['lastIP'] = get_client_ip();
            $this->where(" userId=" . $rd['userId'])->data($data)->save();
            //记录登录日志
            $data = array();
            $data["userId"] = $rd['userId'];
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = get_client_ip();
            $m = M('log_user_logins');
            $m->add($data);

            $user = self::get($rd['userId']);
            if (!empty($user)) session('WST_USER', $user);
        }
        return $rd;
    }

    /**
     * 第三方登录
     */
    public function thirdLogin($obj)
    {
        $rd = array('status' => -1);
        $openId = WSTAddslashes($obj['openId']);
        $sql = "select * from __PREFIX__users where userStatus=1 and userFlag=1 and userFrom=" . $obj['userFrom'] . " and openId='" . $openId . "'";
        $row = $this->queryRow($sql);
        if ($row["userId"] > 0) {
            session('WST_USER', $row);
            $rd["status"] = 1;
        }
        return $rd;
    }

    /**
     * 获取用户信息
     */
    public function getUserInfoRow($where)
    {
        $res = M('users')->where($where)->find();
        if (!empty($res['userId'])) {
            $expireTimeState = -1;//会员过期状态【-1：失效|1：有效】
            if (!empty($res['expireTime']) && $res['expireTime'] > date('Y-m-d H:i:s', time())) {
                $expireTimeState = 1;
            }
            $res['expireTimeState'] = $expireTimeState;
        }
        return $res;
    }

    /**
     * 更改用户信息
     * @param $where
     * @param $data
     * @return mixed
     */
    public function updateUser($where, $data)
    {
        return M('users')->where($where)->save($data);
    }

    /**
     * 新会员注册
     */
    public function userRegister()
    {
        $rd = array('status' => -1);

        $data = array();
        $data['loginName'] = I('loginName', '', 'trim');
        $data['loginPwd'] = I("loginPwd", '', 'trim');//密码
        $confirmPwd = I("confirmPwd", '', 'trim');//确认密码
        $loginName = $data['loginName'];
        $data['userPhone'] = I('userPhone', '', 'trim');
        //检测账号是否存在
        $crs = $this->checkLoginKey($loginName);
        if ($crs['status'] != 1) {
            $rd['status'] = -2;
            $rd['msg'] = ($crs['status'] == -2) ? "不能使用该账号" : "该账号已存在";
            return $rd;
        }
        if ($data['loginPwd'] != $confirmPwd) {
            $rd['status'] = -3;
            $rd['msg'] = '两次输入密码不一致!';
            return $rd;
        }
        foreach ($data as $v) {
            if ($v == '') {
                $rd['status'] = -7;
                $rd['msg'] = '注册信息不完整!';
                return $rd;
            }
        }
//        if($loginName=='')return $rd;//分派不了登录名
//        $data['loginName'] = $loginName;
        //检测账号，邮箱，手机是否存在
        $data["loginSecret"] = rand(1000, 9999);
        $data['loginPwd'] = md5($data['loginPwd'] . $data['loginSecret']);
        $data['userType'] = 0;
        $data['userName'] = I('userName');
        $data['userQQ'] = I('userQQ');
        $data['userPhone'] = I('userPhone');
        $data['userScore'] = I('userScore');
        $data['userEmail'] = I("userEmail");
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['userFlag'] = 1;
        $data['cardState'] = 1;
        $data['cardNum'] = I('cardNum', '', 'trim');

        //判断卡是否已被使用
        if (!empty($data['cardNum'])) {
            $user_data = $this->getUserInfoRow(array('cardNum' => $data['cardNum']));
            if (!empty($user_data)) {
                $rd['status'] = -4;
                $rd['msg'] = '卡已被使用!';
                return $rd;
            }
        }


        $rs = $this->add($data);
        if (false !== $rs) {
            $rd['status'] = 1;
            $rd['userId'] = $rs;
        }

        if ($rd['status'] > 0) {
            $data = array();
            $data['lastTime'] = date('Y-m-d H:i:s');
            $data['lastIP'] = get_client_ip();
            $this->where("userId=" . $rd['userId'])->data($data)->save();
            //记录登录日志
            $data = array();
            $data["userId"] = $rd['userId'];
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = get_client_ip();
            $m = M('log_user_logins');
            $m->add($data);

        }
        return $rd;
    }

    /**
     * 查看会员余额记录
     * @param $userId
     */
    public function getUserBalanceList($userId, $page)
    {
        $sql = "select * from __PREFIX__user_balance where 1 = 1 and userId = " . $userId . " order by createTime desc";
        $rs = $this->pageQuery($sql, $page['page'], $page['pageSize']);
        return $rs;
    }

    /**
     * 查看消费记录
     * @param $userId
     */
    public function getPosConsumeRecord($memberId, $page)
    {
        $sql = "select id,orderNO,addtime,realpayment,setintegral,isCombinePay from __PREFIX__pos_orders where memberId = " . $memberId . " order by addtime desc";
        $rs = $this->pageQuery($sql, $page['page'], $page['pageSize']);
        return $rs;
    }

    /**
     * 检测账号/手机号/用户名/邮箱 是否存在
     * @param array <p>
     * string loginName 账号
     * string userPhone 手机号
     * string userEmail 邮箱
     * string userName 用户名
     * </p>
     * @param string $type 类型【loginName,userPhone,userEmail,userName】
     * @param $userId 用户id 用于编辑
     * @return array $data
     */
    public function verificationLoginKey($params, $type = 'loginName', $userId = 0)
    {
        $where = [];
        $where['loginName'] = null;
        $where['userPhone'] = null;
        $where['userEmail'] = null;
        $where['userName'] = null;
        parm_filter($where, $params);
        if (empty($where)) {
            return returnData(true);
        }
        $where['userFlag'] = 1;
        $data = M('users')->where($where)->find();
        if ($data) {
            if ($userId != $data['userId']) {
                return returnData(false, -1, 'error', $this->getTypeName($type) . '已存在，请更换');
            }
        } else {
            if ($type == 'loginName' && !WSTCheckFilterWords($params['loginName'], $GLOBALS['CONFIG']['limitAccountKeys'])) {
                return returnData(false, -1, 'error', $this->getTypeName($type) . '包含非法字符，请更换');
            }
        }
        return returnData(true);
    }

    public function getTypeName($type = 'loginName')
    {
        switch ($type) {
            case 'loginName':
                $typeName = '账号';
                break;
            case 'userPhone':
                $typeName = '手机号';
                break;
            case 'userEmail':
                $typeName = '邮箱';
                break;
            case 'userName':
                $typeName = '用户名';
                break;
        }
        return $typeName;
    }

    /**
     * 门店找回密码
     * @param array $params <p>
     * int step 步骤(1:步骤1-填写账户名 2:步骤2-验证身份 3:步骤3-设置新密码)
     * string loginName 登陆名
     * string code 图形验证码
     * string mobileCode 手机验证码
     * string loginPwd 设置新密码
     * string repassword 确认新密码
     * </p>
     * @return array
     * */
    public function findPass(array $params)
    {
        $step = (int)$params['step'];
        $loginName = WSTAddslashes($params['loginName']);
        $code = (string)$params['code'];
        $mobileCode = (string)$params['mobileCode'];
        $loginPwd = (string)trim($params['loginPwd']);
        $repassword = (string)trim($params['repassword']);
        $where = array(
            'userFlag' => 1,
            'loginName' => $loginName,
        );
        $user_info = $this->getUserInfoRow($where);
        switch ($step) {
            case 1:#第二步，验证身份
                if (!$this->checkCodeVerify($code)) {
                    //这里把returnData中的status的值改为0和-1是为了兼容之前的返回值
                    return returnData(false, -1, -1, '验证码不正确');
                }
                $where = array(
                    'shopFlag' => 1,
                    'userId' => $user_info['userId'],
                );
                $shopInfo = (array)M('shops')->where($where)->find();
                if (empty($shopInfo)) {
                    return returnData(false, -1, -1, '门店账号不正确');
                }
                return returnData($user_info, 0, 0);
            case 2:#第三步,设置新密码
                $where = array(
                    'smsCode' => $mobileCode,
                    'dataFlag' => 1,
                );
                $smsInfo = D('Home/LogSms')->smsInfo($where);
                if (!$smsInfo || ((time() - strtotime($smsInfo['createTime'])) > 1800)) {
                    return returnData(false, -1, -1, '手机验证码不正确或已失效');
                }
                return returnData(true, 0, 0);
            case 3:#设置成功
                $where = array(
                    'smsCode' => $mobileCode,
                    'dataFlag' => 1,
                );
                $smsInfo = D('Home/LogSms')->smsInfo($where);
                if (!$smsInfo || ((time() - strtotime($smsInfo['createTime'])) > 1800)) {
                    return returnData(false, -1, -1, '手机验证码不正确或已失效');
                }
                if (empty($loginPwd)) {
                    return returnData(false, -1, -1, '新密码必填');
                }
                if ($loginPwd != $repassword) {
                    return returnData(false, -1, -1, '两次密码输入不一致');
                }
                $save = array(
                    'loginPwd' => md5($loginPwd . $user_info["loginSecret"])
                );
                $res = $this->updateUser($where, $save);
                if ($res === false) {
                    return returnData(false, -1, -1, '找回密码失败');
                }
                D('Home/LogSms')->destructionSmsCode($mobileCode);
                return returnData(true, 0, 0, '找回密码成功');
        }
    }

    /**
     * 校验图形验证码
     * @param string $code 验证码
     * @return array
     */
    public function checkCodeVerify($code)
    {
        $verify = new \Think\Verify(array('reset' => false));
        $rs = $verify->check($code);
        if ($rs == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $params
     * @return mixed
     * 门店通过账号获取手机号
     */
    public function getShopUserInfo($params)
    {
        $loginName = WSTAddslashes($params['loginName']);
        $where = [];
        $where['userFlag'] = 1;
        $where['loginName'] = $loginName;
        $userInfo = $this->getUserInfoRow($where);
        if(!empty($userInfo)){
            $where = [];
            $where['shopFlag'] = 1;
            $where['userId'] = $userInfo['userId'];
            $shopInfo = (array)M('shops')->where($where)->find();
            if (!empty($shopInfo)) {
                $param = [];
                $param['userId'] = $userInfo['userId'];
                $param['userPhone'] = substr_replace("{$userInfo['userPhone']}",'*****',3,5);
                return returnData($param, 0, 0);
            }
        }
        return returnData(false, -1, -1, '门店账号不正确');
    }

    /**
     * @param $params
     * @return mixed
     * 门店重置密码
     */
    public function editShopPass($params){
        $userId = (int)$params['userId'];
        $mobileCode = (string)$params['smsCode'];
        $loginPwd = (string)trim($params['loginPwd']);

        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $userId;
        $userInfo = $this->getUserInfoRow($where);
        if(empty($userInfo)){
            return returnData(false, -1, -1, '门店账号不正确');
        }

        $where = [];
        $where['dataFlag'] = 1;
        $where['smsCode'] = $mobileCode;
        $smsInfo = D('Home/LogSms')->smsInfo($where);
        if (!$smsInfo || ((time() - strtotime($smsInfo['createTime'])) > 1800)) {
            return returnData(false, -1, -1, '手机验证码不正确或已失效');
        }

        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $userId;
        $save = [];
        $save['loginPwd'] = md5($loginPwd . $userInfo["loginSecret"]);
        $res = $this->updateUser($where, $save);
        if ($res === false) {
            return returnData(false, -1, -1, '重置密码失败');
        }
        D('Home/LogSms')->destructionSmsCode($mobileCode);
        return returnData(true, 0, 0, '重置密码成功');
    }
}