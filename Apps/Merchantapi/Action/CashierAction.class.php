<?php
namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use function App\Util\responseError;
use function App\Util\responseSuccess;
use Merchantapi\Model\CashierModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 收银端
 */
class CashierAction extends BaseAction
{
    /**
     *会员-新用户注册
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nrbgww
     * */
    public function userRegister()
    {
        $requestParams = I();
        if (empty($requestParams['userPhone']) || empty($requestParams['userName'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $model = new CashierModel();
        $data = $model->userRegister($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-会员短信验证码登录，并返回会员信息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tyova2
     */
    public function userLoginByCode()
    {
        $phone = I('phone', '', 'trim');
        $code = I("code");
        if (empty($phone) || empty($code)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $model = new CashierModel();
        $data = $model->userLoginByCode($phone, $code);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-更改会员密码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qzbhm1
     */
    public function updateUserPassword()
    {
        $userId = (int)I('userId');
        $password = I('password', '', 'trim');
        $confirmPassword = I('confirmPassword', '', 'trim');
        if (empty($password) || empty($confirmPassword) || empty($userId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        if ($password != $confirmPassword) {
            $this->ajaxReturn(returnData(false, -1, 'error', '两次输入的密码不一致'));
        }
        $model = new CashierModel();
        $data = $model->updateUserPassword($userId, $password);
        $this->ajaxReturn($data);
    }

    /**
     * 根据token获取职员信息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/pchg80
     */
    public function getUserInfoByToken()
    {
        $token = I('token', '');
        if (empty($token)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $tokenInfo = userTokenFind($token, 86400 * 30);//查询token
        if (empty($tokenInfo)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '登陆令牌失效'));
        } else {
            $data = $tokenInfo;
        }
        $this->ajaxReturn(returnData($data));
    }


    /**
     * 会员-忘记密码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/orbtff
     */
    public function forgetPassword()
    {
        $requestParams = I();
        if (empty($requestParams['phone']) || empty($requestParams['code']) || empty($requestParams['password']) || empty($requestParams['confirmPassword'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $model = new CashierModel();
        $userInfo = $model->userLoginByCode($requestParams['phone'], $requestParams['code']);
        if ($userInfo['code'] != 0) {
            $this->ajaxReturn($userInfo);
        }
        $userId = $userInfo['userId'];
        $password = I('password', '', 'trim');
        $confirmPassword = I('confirmPassword', '', 'trim');
        if ($password != $confirmPassword) {
            $this->ajaxReturn(returnData(false, -1, 'error', '两次密码输入不一致'));
        }
        $data = $model->updateUserPassword($userId, $password);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-编辑会员信息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tf6fe0
     */
    public function updateUsers()
    {
        $requestParams = I();
        $usersWhere = [];
        $usersWhere['userId'] = (int)$requestParams['userId'];
        $usersWhere['userFlag'] = 1;
        $userInfo = D('Home/Users')->getUserInfoRow($usersWhere);
        if (empty($userInfo['userId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '会员信息有误'));
        }
        if (!empty($requestParams['loginPwd']) || !empty($requestParams['confirmPassword'])) {
            if ($requestParams['loginPwd'] != $requestParams['confirmPassword']) {
                $this->ajaxReturn(returnData(false, -1, 'error', '两次密码输入的不一致'));
            }
            $requestParams['password'] = $requestParams['loginPwd'];
        }
        $requestParams['userId'] = $userInfo['userId'];
        $model = new CashierModel();
        $data = $model->updateUsers($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-查看会员积分记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/axsqpe
     */
    public function getScoreListForPos()
    {
        $userId = (int)I('userId');
        $usersWhere = [];
        $usersWhere['userId'] = $userId;
        $usersWhere['userFlag'] = 1;
        $userInfo = D('Home/Users')->getUserInfoRow($usersWhere);
        if (empty($userInfo['userId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '会员信息有误'));
        }
        $userId = $userInfo['userId'];
        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 15, 'intval');
        $model = D('Home/UserScore');
        $data = $model->getScoreListForPos($userId, $page);
        $this->ajaxReturn(returnData((array)$data));
    }

    /**
     * 会员-查看会员余额记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ngwcdl
     */
    public function getUserBalanceList()
    {
        $userId = (int)I('userId');
        $usersWhere = [];
        $usersWhere['userId'] = $userId;
        $usersWhere['userFlag'] = 1;
        $userInfo = D('Home/Users')->getUserInfoRow($usersWhere);
        if (empty($userInfo['userId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '会员信息有误'));
        }
        $userId = $userInfo['userId'];
        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 15, 'intval');
        $model = D('Home/Users');
        $data = $model->getUserBalanceList($userId, $page);
        $this->ajaxReturn(returnData((array)$data));
    }

    /**
     * 会员-POS消费记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xf15e0
     */
    public function getPosConsumeRecord()
    {
        $userId = (int)I('userId');
        $usersWhere = [];
        $usersWhere['userId'] = $userId;
        $usersWhere['userFlag'] = 1;
        $userInfo = D('Home/Users')->getUserInfoRow($usersWhere);
        if (empty($userInfo['userId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '会员登录令牌失效'));
        }
        $userId = $userInfo['userId'];
        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 15, 'intval');
        $model = D('Home/Users');
        $data = $model->getPosConsumeRecord($userId, $page);
        $this->ajaxReturn(returnData((array)$data));
    }

    /**
     * 根据门店id来获取门店信息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vuwwql
     */
    public function getShopInfoByToken()
    {
        $userInfo = $this->MemberVeri();
        if (empty($userInfo['shopId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '会员登录令牌失效'));
        }
        $shopId = $userInfo['shopId'];
        $model = D('Home/Shops');
        $data = $model->get($shopId);
        $this->ajaxReturn(returnData((array)$data));
    }

    /**
     * 卡-挂失或解除挂失
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gyu8g4
     */
    public function changeCardState()
    {
        if (empty(I('userId'))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $userInfo = D('Home/Users')->getUserInfoRow(['userId' => I('userId')]);
        if (empty($userInfo)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '用户信息有误'));
        }
        if (empty($userInfo['cardNum'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '该用户暂未绑卡'));
        }
        $userId = $userInfo['userId'];
        $cardState = I('cardState', 0, 'intval');//状态 -1：挂失 1：正常
        if (empty($cardState) || !in_array($cardState, [-1, 1])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->changeCardState($userId, $cardState);
        $this->ajaxReturn($data);
    }

    /**
     * 卡-会员绑卡或换卡
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gn2frs
     */
    public function bindOrReplaceCard()
    {
        if (empty(I('userId'))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $userInfo = D('Home/Users')->getUserInfoRow(['userId' => I('userId')]);
        if (empty($userInfo)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '用户信息有误'));
        }
        $card = I('card', '', 'trim');
        $card_arr = explode('K', $card);
        $cardNum = trim($card_arr[0]);
        $userId = intval($card_arr[1]);
        if (empty($card) || empty($cardNum)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数错误'));
        }
        if ($userInfo['userId'] != $userId) {
            $this->ajaxReturn(returnData(false, -1, 'error', '卡和当前会员不匹配'));
        }
        $model = new CashierModel();
        $data = $model->bindOrReplaceCard($userId, $cardNum);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-使用账号和密码登录
     * 使用账号和密码登录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ayciz9
     */
    public function userLogin()
    {
        $loginName = I("loginName", '');
        $loginPwd = I("loginPwd", '');
        if (empty($loginName) || empty($loginPwd)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->userLogin($loginName, $loginPwd);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-获取充值列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gbruhs
     * */
    public function getRechargesetList()
    {
        $this->MemberVeri();
        $model = new CashierModel();
        $data = $model->getRechargesetList();
        $this->ajaxReturn($data);
    }

    /**
     * 会员-获取充值返现规则列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hqxpha
     * */
    public function getRechargeConfigList()
    {
        $this->MemberVeri();
        $model = new CashierModel();
        $data = $model->getRechargeConfigList();
        $this->ajaxReturn($data);
    }

    /**
     * 会员-会员充值
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uqidog
     */
    public function userRecharge()
    {
        $loginUserInfo = $this->MemberVeri();
        $model = new CashierModel();
        $userId = (int)I('userId');
        $type = (int)I('type');//类型（1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付）
        $auth_code = I('auth_code', '', 'trim');
        $money = I('money', 0);
        if (empty($userId) || empty($type) || empty($money)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        if ($money <= 0) {
            $this->ajaxReturn(returnData(false, -1, 'error', '金额必须大于0'));
        }
        $orderNo = $model->createOrderNo();
        $data = $model->userRecharge($userId, $type, $auth_code, $money, $orderNo, $loginUserInfo);
        $this->ajaxReturn($data);
    }

    /**
     * 总管理员-申请提现
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gc0frh
     */
    public function applyWithdraw()
    {
        $shops = $this->MemberVeri();
        if ($shops['id']) {
            $this->ajaxReturn(returnData(false, -1, 'error', '只有总管理员才可以申请提现'));
        }
        $shopId = $shops['shopId'];
        $money = I('money', 0);
        if (empty($shopId) || empty($money)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->applyWithdraw($shopId, $money);
        $this->ajaxReturn($data);
    }

    /**
     * 会员-购买会员套餐
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/rr0cm7
     */
    public function buySetmeal()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $userId = I('userId');
        $smId = I('smId', 0, 'intval');
        $type = I('type', 1, 'intval');//类型，（1:现金 4：微信 5：支付宝）
        if (empty($userId) || empty($smId) || empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $usersWhere = [];
        $usersWhere['userId'] = $userId;
        $usersWhere['userFlag'] = 1;
        $userInfo = D('Home/Users')->getUserInfoRow($usersWhere);
        if (empty($userInfo['userId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '会员信息有误'));
        }
        $userId = $userInfo['userId'];
        $model = new CashierModel();
        $data = $model->buySetmeal($shopId, $userId, $smId, $type);
        $this->ajaxReturn($data);
    }

    /**
     * 会员套餐列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lw8si9
     */
    public function getSetmealList()
    {
        $model = new CashierModel();
        $data = $model->getSetmealList();
        $this->ajaxReturn($data);
    }

    /**
     * 商户预存款流水
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tyfqc8
     */
    public function getRechargeRecord()
    {
        $shopId = $this->MemberVeri()['shopId'];
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', 'token令牌失效'));
        }
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $model = new CashierModel();
        $data = $model->getRechargeRecord($shopId, $page, $pageSize);
        $this->ajaxReturn($data);
    }

    /**
     * 商家预存款（充值）
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yu95g9
     */
    public function recharge()
    {
        $model = new CashierModel();
        $shopId = $this->MemberVeri()['shopId'];
        $type = I('type', 0, 'intval');//类型，4：微信 5：支付宝（1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付）
        $auth_code = I('auth_code', '', 'trim');
        $money = I('money', 0);
        if (empty($type) || empty($auth_code) || empty($money)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $orderNo = $model->createOrderNo();
        $data = $model->recharge($shopId, $type, $auth_code, $money, $orderNo);
        $this->ajaxReturn($data);
    }

    /**
     * Pos订单结算申请
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/elkcun
     */
    public function posOrderSettlement()
    {
        $shopInfo = $this->MemberVeri();
        $param['shopId'] = $shopInfo['shopId'];
        $param['ids'] = trim(I('ids'), ',');
        if (empty($param['ids'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->posOrderSettlement($param);
        $this->ajaxReturn($data);
    }

    /**
     * 获取Pos结算订单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/py5g3k
     */
    public function getPosOrderSettlementList()
    {
        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
        $requestParams = I();
        $requestParams['shopId'] = $shopId;
        $requestParams['page'] = I('page', 1);
        $requestParams['pageSize'] = I('pageSize', 15);
        $model = new CashierModel();
        $res = $model->getPosOrderSettlementList($requestParams);
        $this->ajaxReturn($res);
    }

    /*
     * 获取Pos订单详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nowpuv
     * */
    public function getPosOrderDetail()
    {
        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
        $posId = (int)I('posId');
        if (empty($posId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getPosOrderDetail($shopId, $posId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取Pos订单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/em5ggg
     */
    public function getPosOrderList()
    {
        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
        $params = I();
        $params['shopId'] = $shopId;
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $params['pageSupport'] = I('pageSupport', 1);
        $params['state'] = 3;//收银订单只展示已结算的
        $model = new CashierModel();
        $res = $model->getPosOrderList($params);
        $this->ajaxReturn($res);
    }

    /**
     * 改版弃用
     * 退货退款 - 动作
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bqycp4
     */
    public function returnGoods()
    {
        $shopInfo = $this->MemberVeri();
        $pack = I('pack');//json数据
        if (empty($pack)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $pack = htmlspecialchars_decode($pack);
        $pack = rtrim($pack, '"');
        $pack = ltrim($pack, '"');
        $model = new CashierModel();
        $data = $model->returnGoods($shopInfo, json_decode($pack, true));
        $this->ajaxReturn($data);
    }

    /**
     * 退货/换货-订单商品搜索(条码|编码)
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/evngsi
     * */
    public function searchOrderGoodsInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $model = new CashierModel();
        $barcode = I('barcode', '');
        $orderId = (int)I('orderId');
        if (empty($barcode)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $data = $model->searchOrderGoodsInfo($shopId, $orderId, $barcode);
        $this->ajaxReturn($data);
    }

    /**
     * 改版已废弃
     * 退货单列表
     * 文档链接地址：https://www.yuque.com/anthony-6br1r/oq7p0p/yagbkv
     * */
    public function getGivebackOrderList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $model = new CashierModel();
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $data = $model->getGivebackOrderList($shopId, $params);
        $this->ajaxReturn($data);
    }

    /**
     * 改版已废弃
     * 退货单详情
     * 文档链接地址：https://www.yuque.com/anthony-6br1r/oq7p0p/ehpvys
     * */
    public function getGivebackOrderDetail()
    {
        $this->MemberVeri();
        $backId = (int)I('backId');
        if (empty($backId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getGivebackOrderDetail($backId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 根据 订单编号 获取订单详情和订单商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mci5fl
     */
    public function getOrderDetailAndOrderGoodsByOrderNo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $orderNO = I('orderNO', 0, 'trim');
        if (empty($orderNO)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $result = $model->getOrderDetailAndOrderGoodsByOrderNo($shopId, $orderNO);
        $this->ajaxReturn($result);
    }

    /**
     * 微信支付 - 动作
     */
    public function doWxPay($userId, $auth_code, $money, $orderNo, $out_trade_no)
    {
        $model = new CashierModel();
        $result = $model->doWxPay($userId, $auth_code, $money, $orderNo, $out_trade_no);
        if ($result['apiCode'] == 0) {
            //回调方法
            $this->scanPayCallback($orderNo);
            //订单校验
            $this->checkOrder($orderNo);
        }
        return $result;
        /*
                $pom = M('pos_orders');
                $pogm = M('pos_orders_goods');
                $order_info = $pom->where(array('orderNO'=>$orderNo))->find();
                $order_goods = $pogm->where(array('orderid'=>$order_info['id']))->select();

                Vendor('WxPay.lib.WxPayApi');
                Vendor('WxPay.MicroPay');
                Vendor('WxPay.log');
                if((isset($auth_code) && !preg_match("/^[0-9]{6,64}$/i", $auth_code, $matches)))
                {
                    header('HTTP/1.1 404 Not Found');
                    exit();
                }
                //初始化日志
                $logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
                $log = \Log::Init($logHandler, 15);
                if (isset($auth_code) && $auth_code != '') {
                    try {
                        $wx_payments = M('payments')->where(array('payCode'=>'weixin','enabled'=>1))->find();
                        if (empty($wx_payments['payConfig'])) {
                            $apiRet['apiCode'] = -1;
                            $apiRet['apiInfo'] = '参数不全';
                            $apiRet['apiState'] = 'error';
                            return $apiRet;
                        }
                        $wx_config = json_decode($wx_payments['payConfig'],true);

        //                    $auth_code = $_REQUEST["auth_code"];
                        $input = new \WxPayMicroPay();
                        $input->SetAuth_code($auth_code);
                        $input->SetBody("POS-支付");
                        $input->SetTotal_fee($money*100);
                        $input->SetOut_trade_no($out_trade_no);

                        $microPay = new \MicroPay();
                        $result = printf_info($microPay->pay($wx_config,$input));
        //                    echo "<pre>";var_dump($result);exit();
                        //支付成功后,执行下面的方法
                        if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS'){
                            Vendor('WxPay.notify');
                            //查询订单
                            \Log::DEBUG("begin notify");
                            $PayNotifyCallBack = new \PayNotifyCallBack();
                            $res = $PayNotifyCallBack->Queryorder($wx_config,$result['transaction_id']);
                            if ($res) {//支付成功
                                //回调方法
                                $this->scanPayCallback($orderNo);
                                //订单校验
                                $this->checkOrder($orderNo);

                                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 微信付款码： 收款成功 \r\n");
                                fclose($myfile);

                                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                                fclose($myfile);

                                $apiRet['apiCode'] = 0;
                                $apiRet['apiInfo'] = '收款成功';
                                $apiRet['apiState'] = 'success';
                                return $apiRet;
                            } else {

                                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 微信付款码： 收款失败 \r\n");
                                fclose($myfile);

                                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                                fclose($myfile);

                                $apiRet['apiInfo'] = '收款失败';
                                return $apiRet;
                            }
                        } else {

                            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 微信付款码： 收款失败 \r\n");
                            fclose($myfile);

                            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                            fclose($myfile);

                            $apiRet['apiInfo'] = '收款失败';
                            return $apiRet;
                        }
                    } catch(Exception $e) {
                        Log::ERROR(json_encode($e));
                    }
                }*/
    }

    /**
     * 支付宝支付 - 动作
     */
    public function doAliPay($userId, $auth_code, $money, $orderNo, $out_trade_no)
    {

        $model = new CashierModel();
        $result = $model->doAliPay($userId, $auth_code, $money, $orderNo, $out_trade_no);
        if ($result['apiCode'] == 0) {
            //回调方法
            $this->scanPayCallback($orderNo);
            //订单校验
            $this->checkOrder($orderNo);
        }
        return $result;

        /*$apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $order_info = $pom->where(array('orderNO'=>$orderNo))->find();
        $order_goods = $pogm->where(array('orderid'=>$order_info['id']))->select();

        header("Content-type: text/html; charset=utf-8");
        Vendor('Alipay.dangmianfu.f2fpay.model.builder.AlipayTradePayContentBuilder');
        Vendor('Alipay.dangmianfu.f2fpay.service.AlipayTradeService');
//        $config = C('alipayConfig');

        $wx_payments = M('payments')->where(array('payCode'=>'alipay','enabled'=>1))->find();
        if (empty($wx_payments['payConfig'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数不全';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $config = json_decode($wx_payments['payConfig'],true);

        // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
        // 需保证商户系统端不能重复，建议通过数据库sequence生成，
        //$outTradeNo = "barpay" . date('Ymdhis') . mt_rand(100, 1000);
//            $outTradeNo = $_POST['out_trade_no'];
        $outTradeNo = $out_trade_no;

        // (必填) 订单标题，粗略描述用户的支付目的。如“XX品牌XXX门店消费”
//            $subject = $_POST['subject'];
        $subject = "收银员收银";

        // (必填) 订单总金额，单位为元，不能超过1亿元
        // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
        $totalAmount = $money;

        // (必填) 付款条码，用户支付宝钱包手机app点击“付款”产生的付款条码
        $authCode = $auth_code; //28开头18位数字

        // (可选,根据需要使用) 订单可打折金额，可以配合商家平台配置折扣活动，如果订单部分商品参与打折，可以将部分商品总价填写至此字段，默认全部商品可打折
        // 如果该值未传入,但传入了【订单总金额】,【不可打折金额】 则该值默认为【订单总金额】- 【不可打折金额】
        //String discountableAmount = "1.00"; //

        // (可选) 订单不可打折金额，可以配合商家平台配置折扣活动，如果酒水不参与打折，则将对应金额填写至此字段
        // 如果该值未传入,但传入了【订单总金额】,【打折金额】,则该值默认为【订单总金额】-【打折金额】
//            $undiscountableAmount = "0.01";

        // 卖家支付宝账号ID，用于支持一个签约账号下支持打款到不同的收款账号，(打款到sellerId对应的支付宝账号)
        // 如果该字段为空，则默认为与支付宝签约的商户的PID，也就是appid对应的PID
        $sellerId = "";

        // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
//            $body = "购买商品2件共15.00元";
        $body = "";
        $goods_num = 0;
        if (!empty($order_goods)) {
            foreach ($order_goods as $v) {
                $goods_num += $v['number'] + $v['weight'];
            }
        }
        if ($goods_num > 0) {
            $body = "购买商品 " . $goods_num . " 件共 " . $order_info['realpayment'] . " 元";
        }

        //商户操作员编号，添加此参数可以为商户操作员做销售统计
//            $operatorId = "test_operator_id";
        $operatorId = $userId;

        // (可选) 商户门店编号，通过门店号和商家后台可以配置精准到门店的折扣信息，详询支付宝技术支持
//            $storeId = "test_store_id";
        $storeId = $order_info['shopId'];

        // 支付宝的店铺编号
//            $alipayStoreId = "test_alipay_store_id";

        // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
//            $providerId = ""; //系统商pid,作为系统商返佣数据提取的依据
//            $extendParams = new \ExtendParams();
//            $extendParams->setSysServiceProviderId($providerId);
//            $extendParamsArr = $extendParams->getExtendParams();

        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";

        // 商品明细列表，需填写购买商品详细信息，
        $goodsDetailList = array();

        // 创建一个商品信息，参数含义分别为商品id（使用国标）、名称、单价（单位为分）、数量，如果需要添加商品类别，详见GoodsDetail
        if (!empty($order_goods)) {
            $goods = new \GoodsDetail();
            foreach ($order_goods as $k=>$v) {
                $goods->setGoodsId($v['goodsId']);
                $goods->setGoodsName($v['goodsName']);
                $goods->setPrice($v['presentPrice']);
                $num = ($v['number'] > 0) ? $v['number'] : $v['weight'];
                $goods->setQuantity($num);
                $goodsDetailList[] = $goods->getGoodsDetail();
            }
        }

        //第三方应用授权令牌,商户授权系统商开发模式下使用
        $appAuthToken = "";//根据真实值填写

        // 创建请求builder，设置请求参数
        $barPayRequestBuilder = new \AlipayTradePayContentBuilder();
        $barPayRequestBuilder->setOutTradeNo($outTradeNo);
        $barPayRequestBuilder->setTotalAmount($totalAmount);
        $barPayRequestBuilder->setAuthCode($authCode);
        $barPayRequestBuilder->setTimeExpress($timeExpress);
        $barPayRequestBuilder->setSubject($subject);
        $barPayRequestBuilder->setBody($body);
//            $barPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
//            $barPayRequestBuilder->setExtendParams($extendParamsArr);
        $barPayRequestBuilder->setGoodsDetailList($goodsDetailList);
        $barPayRequestBuilder->setStoreId($storeId);
        $barPayRequestBuilder->setOperatorId($operatorId);
//            $barPayRequestBuilder->setAlipayStoreId($alipayStoreId);

        $barPayRequestBuilder->setAppAuthToken($appAuthToken);

        // 调用barPay方法获取当面付应答
        $barPay = new \AlipayTradeService($config);
        $barPayResult = $barPay->barPay($barPayRequestBuilder);

        $result = $barPayResult->getTradeStatus();
        if ($result == "SUCCESS") {//支付宝支付成功
//                print_r($barPayResult->getResponse());
//                $alipayResult = $barPayResult->getResponse();
//                echo "<pre>";var_dump($alipayResult);
//                exit();
            ////获取商户订单号
//                $out_trade_no = trim($_POST['out_trade_no']);
            $out_trade_no = trim($out_trade_no);

            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $appAuthToken = "";//根据真实值填写

            //构造查询业务请求参数对象
            $queryContentBuilder = new \AlipayTradeQueryContentBuilder();
            $queryContentBuilder->setOutTradeNo($out_trade_no);

            $queryContentBuilder->setAppAuthToken($appAuthToken);


            //初始化类对象，调用queryTradeResult方法获取查询应答
            $queryResponse = new \AlipayTradeService($config);
            $queryResult = $queryResponse->queryTradeResult($queryContentBuilder);

            //根据查询返回结果状态进行业务处理
            $resultState = $queryResult->getTradeStatus();
            if ($resultState == "SUCCESS") {//支付宝查询交易成功
                //处理业务逻辑
                //回调方法
                $this->scanPayCallback($orderNo);
                //订单校验
                $this->checkOrder($orderNo);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款成功 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '收款成功';
                $apiRet['apiState'] = 'success';
                return $apiRet;
            } else if ($resultState == "FAILED") {//支付宝查询交易失败或者交易已关闭
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款失败 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款失败原因： 支付宝查询交易失败或者交易已关闭 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '支付宝查询交易失败或者交易已关闭';
                return $apiRet;
            } else if ($resultState == "UNKNOWN") {//系统异常，订单状态未知
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款失败 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款失败原因： 系统异常，订单状态未知 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '系统异常，订单状态未知';
                return $apiRet;
            } else {//不支持的查询状态，交易返回异常
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款失败 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款失败原因： 不支持的查询状态，交易返回异常 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '不支持的查询状态，交易返回异常';
                return $apiRet;
            }

        } else if ($result == "FAILED") {//支付宝支付失败
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款失败原因： 支付宝支付失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '支付宝支付失败';
            return $apiRet;
        } else if ($result == "UNKNOWN") {//系统异常，订单状态未知
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款失败原因： 系统异常，订单状态未知 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '系统异常，订单状态未知';
            return $apiRet;
        } else {//不支持的交易状态，交易返回异常
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 收款失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款失败原因： 不支持的交易状态，交易返回异常 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 结束 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '不支持的交易状态，交易返回异常';
            return $apiRet;
        }*/
    }

    /**
     * 校验订单
     */
    public function checkOrder($orderNo)
    {
        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $orderInfo = $pom->where(array('orderNO' => $orderNo))->find();
        if (!empty($orderInfo)) {
            $total_money = 0;
            $order_goods_list = $pogm->where(array('orderid' => $orderInfo['id']))->select();
            if (!empty($order_goods_list)) {
                foreach ($order_goods_list as $v) {
                    $total_money += $v['presentPrice'] * ($v['number'] + $v['weight']);
                }
            }
            //如果校验金额和订单金额不一致，则写入日志
            $total_money_new = $total_money * ($orderInfo['discount'] / 100);
            if ($total_money_new != $orderInfo['realpayment']) {

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(" . date('Y-m-d H:i:s') . ")-异常订单：开始 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(" . date('Y-m-d H:i:s') . ")-异常订单-订单号：" . $orderNo . " \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(" . date('Y-m-d H:i:s') . ")-异常订单-校验前实付金额：" . $orderInfo['realpayment'] . " \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(" . date('Y-m-d H:i:s') . ")-异常订单-校验后实付金额：" . $total_money_new . " \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(" . date('Y-m-d H:i:s') . ")-异常订单-校验后结论：校验前 和 校验后 实付金额不一致 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(" . date('Y-m-d H:i:s') . ")-异常订单：结束 \r\n");
                fclose($myfile);
            }
        }

    }

    /**
     * 微信/支付宝 扫码支付成功后的回调
     */
    public function scanPayCallback($orderNo)
    {

        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 进入回调函数 \r\n");
        fclose($myfile);

        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ") - 回调函数： 开始 \r\n");
        fclose($myfile);

        $where = array('orderNO' => $orderNo);
        $pom = M('pos_orders');
        $pos_orders_info = $pom->where($where)->find();
        /*
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').") - 回调函数(sql)： ".M()->getLastSql()." \r\n");
                fclose($myfile);
        */
        if (!empty($pos_orders_info)) {
            $result = $pom->where($where)->save(array('state' => 3));
            if ($result) {
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ") - 回调函数： 回调成功 \r\n");
                fclose($myfile);

//            exit("回调成功");
            } else {
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ") - 回调函数： 回调失败 \r\n");
                fclose($myfile);

//            exit("回调失败");
            }
        }
        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ") - 回调函数： 结束 \r\n");
        fclose($myfile);
    }

    /**
     * 扫码支付
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xwkid0
     */
    public function scanPay()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $type = I('type', 0, 'intval');//类型，4：微信 5：支付宝
        $auth_code = I('auth_code', '', 'trim');
        $money = I('money', 0);
        $orderNo = I('orderNo', 0, 'trim');
        if (empty($type) || empty($auth_code) || empty($money) || empty($orderNo)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->scanPay($shopId, $type, $auth_code, $money, $orderNo);
        $this->ajaxReturn($data);

    }

    /**
     * 查看POS订单状态
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/rs0s3l
     */
    public function posOrderInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $orderNo = I('orderNo', '', 'trim');
        if (empty($orderNo)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $params = [
            'shopId' => $shopId,
            'orderNO' => $orderNo
        ];
        $data = $model->getPosOrderInfo($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取店铺商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hgwh9y
     */
    public function getShopGoodsList()
    {
        $shopInfo = $this->MemberVeri();
        $params = I();
        $params['shopId'] = $shopInfo['shopId'];
        $model = new CashierModel();
        $data = $model->getShopGoodsList($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取店铺商品详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yytwku
     * */
    public function getShopGoodsInfo()
    {
        $shopInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $barcode = (string)I('barcode');
        if (empty($goodsId) && empty($barcode)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $shopId = $shopInfo['shopId'];
        $model = new CashierModel();
        $data = $model->getShopGoodsInfo($shopId, $goodsId, $barcode);
        $this->ajaxReturn($data);
    }

    /**
     * 店铺分类列表(分页格式)
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gxo6z3
     */
    public function getShopCatsList()
    {
        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
        $parentId = (int)I('parentId');
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $model = new CashierModel();
        $data = $model->getShopCatsList($shopId, $parentId, $page, $pageSize);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取店铺分类列表(树状图格式)
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/eruwzc
     */
    public function getShopCatsListTree()
    {
        $shopInfo = $this->MemberVeri();
        $model = D('Home/ShopsCats');
        $data = $model->getCatAndChild($shopInfo['shopId'], (int)I('parentId', 0));
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 生成条码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lhgnkh
     * */
    public function getPosBarcode()
    {
        $loginUser = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $weight = I('weight');
        if (empty($goodsId) || empty($weight)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getPosBarcode($loginUser, $goodsId, $weight);
        $this->ajaxReturn($data);
    }

    /**
     *会员-会员码登录/实体卡登录
     *文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fhazsu
     */
    public function getUserInfoByUserCode()
    {
        $userCode = I('userCode');//会员code
        if (empty($userCode)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getUserInfoByUserCode($userCode);
        $this->ajaxReturn($data);
    }

    /**
     *会员-搜索会员列表
     *文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bvm5rc
     */
    public function searchUser()
    {
        $this->MemberVeri();
        $keywords = I('keywords', '');
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        if (empty($keywords)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入关键字'));
        }
        $model = new CashierModel();
        $data = $model->searchUser($keywords, $page, $pageSize);
        $this->ajaxReturn($data);
    }

    /**
     *会员-会员详情
     *文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wuxd07
     */
    public function getUserDetail()
    {
        $this->MemberVeri();
        $userId = (int)I('userId');
        if (empty($userId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getUserDetail($userId);
        $this->ajaxReturn($data);
    }

    /**
     *pos支付提交订单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dzbn9r
     * */
    public function submit()
    {
        $shopInfo = $this->MemberVeri();
        $pack = I('pack');
        if (empty($pack)) {
//            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全'));
        }
        $pack = htmlspecialchars_decode($pack);
        $pack = rtrim($pack, '"');
        $pack = json_decode(ltrim($pack, '"'), true);
        $model = new CashierModel();
        if (empty($pack['pay']) || !in_array($pack['pay'], [1, 2, 3, 4, 5, 6])) {
            //$this->ajaxReturn(returnData(false, -1, 'error', '请选择支付方式'));
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择支付方式'));
        }
        if ($pack['pay'] == 6) {
            $pack['isCombinePay'] = 1;
        } else {
            $pack['isCombinePay'] = 0;
        }
        //$data = $model->submit($shopId, json_decode($pack, true));
        $result = $model->posSubmit($shopInfo, $pack);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        //$this->ajaxReturn($data);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     *搜索商品(商品名/商品编码&条码)
     *文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ppco0u
     * */
    public function search()
    {
        $shopInfo = $this->MemberVeri();
        $goodsName = I('goodsName', '');
        $barcode = I('barcode', '');
        if (empty($goodsName) && empty($barcode)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $model = new CashierModel();
        $data = $model->search($shopInfo, $goodsName, $barcode);
        $this->ajaxReturn($data);
    }

    /**
     * POS开单(其实就是获取一个订单号)
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bk5hyo
     */
    public function orderCreate()
    {
        $shopInfo = $this->MemberVeri();
        $model = new CashierModel();
        $data = $model->orderCreate($shopInfo);
        $this->ajaxReturn($data);
    }

    /**
     * POS职员登陆
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bmngb9
     */
    public function login()
    {
        $type = I('type', 1);
        if ($type != 2) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请使用收银员账号登录'));
        }
        $loginName = I('loginName');
        $loginPwd = I('loginPwd');
        if (empty($loginName) || empty($loginPwd)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '账号或密码不能为空'));
        }
        $model = new CashierModel();
        $data = $model->login($loginName, $loginPwd, $type);
        $this->ajaxReturn($data);
    }

    /**
     * 更改职员/管理员密码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/eban08
     * */
    public function editUserPassword()
    {
        $loginUserInfo = $this->MemberVeri();
        $password = I('password');
        $confirmPassword = I('confirmPassword');
        if (empty($password) || empty($confirmPassword)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        if ($password != $confirmPassword) {
            $this->ajaxReturn(returnData(false, -1, 'error', '两次密码输入的不一致'));
        }
        $model = D('Home/User');
        $data = $model->editUserPassword($loginUserInfo, $password);
        $this->ajaxReturn($data);
    }

    /**
     * 改版废弃
     * 换货-动作
     * 文档链接地址:PS:https://www.yuque.com/anthony-6br1r/oq7p0p/vpd42w
     * */
    public function exchangeGoods()
    {
//        $testArr = [
//            'orderId'=>108,
//            'auth_code'=>782797z,//支付授权码
//            'userId'=>351,//该字段的作用是判断会员是否使用了会员积分,余额等
//            'pay'=>1,
//            'discount'=>'',
//            'discountPrice'=>'',
//            'cash'=>'15.8',
//            'balance'=>'',
//            'unionpay'=>'',
//            'wechat'=>'',
//            'alipay'=>'',
//            'change'=>'',
//            'realpayment'=>'',//0=>等价换货|正数=>用户补差价|负数=>商家补差价
//            'setintegral'=>'',
//            'allowanceAmount'=>'',
//            'goods'=>[
//                [
//                    'orderGoodsRelationId'=>69,
//                    'orderGoodsExchangeNumber'=>1,
//                    'orderGoodsExchangeWeight'=>150,
//                    'exchangeGoodsId'=>8321,
//                    'exchangeGoodsSkuId'=>0,
//                    'exchangeGoodsWeight'=>0,
//                    'exchangeGoodsName'=>'芒果',
//                    'exchangeGoodsNumber'=>1,
//                    'exchangeGoodsOriginalPrice'=>15.8,
//                    'exchangeGoodsFavorablePrice'=>0,
//                    'exchangeGoodsPresentPrice'=>15.8,
//                    'exchangeGoodsDiscount'=>0,
//                    'exchangeGoodsSubtotal'=>15.8,
//                    'exchangeGoodsIntegral'=>0,
//                ]
//            ]
//        ];
//        dd(json_encode($testArr));
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        if (empty($requestParams['exchangeData'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $requestParams['exchangeData'] = json_decode(htmlspecialchars_decode($requestParams['exchangeData']), true);
        $model = new CashierModel();
        $data = $model->exchangeGoods($shopInfo, $requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 改版已废弃
     * 换货单列表
     * 文档链接地址：https://www.yuque.com/anthony-6br1r/oq7p0p/hsckl5
     * */
    public function getExchangeOrderList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $model = new CashierModel();
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $data = $model->getExchangeOrderList($shopId, $params);
        $this->ajaxReturn($data);
    }

    /**
     * 改版已废弃
     * 换货单详情
     * 文档链接地址：https://www.yuque.com/anthony-6br1r/oq7p0p/yer6yt
     * */
    public function getExchangeOrderDetail()
    {
        $this->MemberVeri();
        $exchangeId = (int)I('exchangeId');
        if (empty($exchangeId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getExchangeOrderDetail($exchangeId);
        $this->ajaxReturn(returnData($data));
    }


    /**
     * 日结单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vsde4x
     * */
    public function dailyStatement()
    {
        $loginUser = $this->MemberVeri();
        $datetime = I('datetime', date('Y-m-d'));
        $model = new CashierModel();
        $data = $model->dailyStatement($loginUser, $datetime);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取收营员销售明细
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yzqt49
     * @param string token
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @return json
     * */
    public function getCashierSalesDetails()
    {
        $loginUser = $this->MemberVeri();
        $startDate = (string)I('startDate');
        $endDate = (string)I('endDate');
        $model = new CashierModel();
        $data = $model->getCashierSalesDetails($loginUser, $startDate, $endDate);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 收银订单数据统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ggghk7
     * @param string token
     * @return json
     * */
    public function countPosOrders()
    {
        //出现类似于这种统计接口的原因:前端以前无法实现筛选条件搜索,如果后期改动打的话直接废弃这种接口,写到列表接口中
        $loginUser = $this->MemberVeri();
        $shopId = (int)$loginUser['shopId'];
        $params = I();
        $params['state'] = I('state', 3);
        $params['shopId'] = $shopId;
        $model = new CashierModel();
        $data = $model->countPosOrders($params);
        $this->ajaxReturn(returnData($data));
    }


    /**
     * 获取收银订单日志
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tbtnsn
     * @param string token
     * @param int pos_order_id 收银订单id
     * @return json
     * */
    public function getPosOrdersLog()
    {
        $this->MemberVeri();
        $pos_order_id = (int)I('pos_order_id');
        if (empty($pos_order_id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getPosOrdersLog($pos_order_id);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ug01wo
     * 获取退换商品明细-用于退货/换货
     * @param string token
     * @param pos_order_id 收银订单id
     * @return json
     * */
    public function getReturnAndExchangeGoods()
    {
        $this->MemberVeri();
        $pos_order_id = (int)I('pos_order_id');
        if (empty($pos_order_id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getReturnAndExchangeGoods($pos_order_id);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 提交退货
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fnn0fi
     * @param string token
     * @param json return_params
     * @return json
     * */
    public function returnGoodsAction()
    {
        $login_user_info = $this->MemberVeri();
        $return_params = json_decode(htmlspecialchars_decode(I('return_params')), true);
        if (empty($return_params)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        foreach ($return_params as &$item) {
            if (empty($item['id'])) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
            }
            if ($item['current_return_num'] <= 0) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '退货数量必须大于0', false));
            }
            $item['id'] = (int)$item['id'];
            $item['current_return_num'] = (int)$item['current_return_num'];
        }
        unset($item);
        $model = new CashierModel();
        $result = $model->returnGoodsAction($login_user_info, $return_params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess(true));
    }

    /**
     * 退货记录统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ycpuk5
     * */
    public function countReturnGoodsLog()
    {
        //出现类似于这种统计接口的原因:前端以前无法实现筛选条件搜索,如果后期改动打的话直接废弃这种接口,写到列表接口中
        $login_user_info = $this->MemberVeri();
        $shop_id = $login_user_info['shopId'];
        $params = I();
        $params['shop_id'] = $shop_id;
        $model = new CashierModel();
        $result = $model->countReturnGoodsLog($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 退货记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lg6tgh
     * @param string token
     * @param string bill_no 单号
     * @param date startDate 时间区间-开始时间
     * @param date endDate 时间区间-结束时间
     * @param int page
     * @param int pageSize
     * @return json
     * */
    public function getReturnGoodsLogList()
    {
        $login_user_info = $this->MemberVeri();
        $shop_id = $login_user_info['shopId'];
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $params['shop_id'] = $shop_id;
        $model = new CashierModel();
        $result = $model->getReturnGoodsLogList($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 换货记录统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sge6fp
     * */
    public function countExchangeGoodsLog()
    {
        //出现类似于这种统计接口的原因:前端以前无法实现筛选条件搜索,如果后期改动打的话直接废弃这种接口,写到列表接口中
        $login_user_info = $this->MemberVeri();
        $shop_id = $login_user_info['shopId'];
        $params = I();
        $params['shop_id'] = $shop_id;
        $model = new CashierModel();
        $result = $model->countExchangeGoodsLog($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 换货记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ih4oxe
     * @param string token
     * @param string bill_no 单号
     * @param date startDate 时间区间-开始时间
     * @param date endDate 时间区间-结束时间
     * @param string action_user_name 操作人
     * @param int page
     * @param int pageSize
     * @return json
     * */
    public function getExchangeGoodsLogList()
    {
        $login_user_info = $this->MemberVeri();
        $shop_id = $login_user_info['shopId'];
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $params['shop_id'] = $shop_id;
        $model = new CashierModel();
        $result = $model->getExchangeGoodsLogList($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 提交换货操作
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/milt3o
     * @param string token
     * @param json exchange_params
     * @return json
     * */
    public function exchangeGoodsAction()
    {
        $login_user_info = $this->MemberVeri();
        $exchange_params = json_decode(htmlspecialchars_decode(I('exchange_params')), true);
        if (empty($exchange_params['primary_params']) || empty($exchange_params['present_params'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        foreach ($exchange_params['primary_params'] as $item) {
            if (empty($item['id'])) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '退还商品参数有误', false));
            }
            if ($item['primary_num'] <= 0) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '退还商品数量必须大于0', false));
            }
        }
        foreach ($exchange_params['present_params'] as $item) {
            if (empty($item['goodsId'])) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '换货商品参数有误', false));
            }
            if ($item['present_num'] <= 0) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '换货商品数量必须大于0', false));
            }
        }
        $model = new CashierModel();
        $result = $model->exchangeGoodsAction($login_user_info, $exchange_params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess(true));
    }


    /**
     * 收银预提交接口 目前主用于处理促销活动计算
     */
    public function PreSubmission()
    {


        //接收参数
        $userId = I('userId', 0);//用户id

        $goodsArr = htmlspecialchars_decode(I('goodsArr'));
        $goodsArr = json_decode($goodsArr, true);//商品数组
        // var_dump($goodsArr);
        $data = (new \App\Modules\Pos\PosServiceModule)->PreSubmission($userId, $goodsArr);

        $this->ajaxReturn(returnData((array)$data));
    }

    #############################收银报表-start###################################

    /**
     * 营业数据统计报表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yq8lzw
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function businessStatisticsReport()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $model = new CashierModel();
        $result = $model->businessStatisticsReport($shop_id, $start_date, $end_date, $page, $page_size, $export);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 商品销量统计报表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/co6dg3
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int data_type 统计类型(1:按商品统计 2:按分类统计)
     * @param int model_type 模式(1:列表模式 2:图表模式)
     * @param int goodsCatId1 商品商城一级分类id
     * @param int goodsCatId2 商品商城二级分类id
     * @param int goodsCatId3 商品商城三级分类id
     * @param string goods_keywords 商品名称或商品编码 PS:仅仅按商品统计的场景需要
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function goodsSaleReport()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $goods_keywords = (string)I('goods_keywords');
        $data_type = (int)I('data_type', 1);
        $model_type = (int)I('model_type', 1);
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        if (!in_array($data_type, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的统计类型'));
        }
        if (!in_array($model_type, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的统计模式'));
        }
        $model = new CashierModel();
        $params = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'page' => $page,
            'page_size' => $page_size,
            'data_type' => $data_type,
            'model_type' => $model_type,
            'goods_cat_id1' => (int)I('goodsCatId1'),
            'goods_cat_id2' => (int)I('goodsCatId2'),
            'goods_cat_id3' => (int)I('goodsCatId3'),
            'goods_keywords' => $goods_keywords,
            'export' => $export
        );
        $result = $model->goodsSaleReport($params);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 商品销量统计报表-客户详情
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int data_type 统计类型(1:按商品统计 2:按分类统计)
     * @param int goodsId 商品id
     * @param int catId 分类id
     * @param int page 页码
     * @param int pageSize 分页条数
     * @return json
     * */
    public function goodsSaleReportCustomerDetail()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $data_type = (int)I('data_type', 1);
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $goods_id = (int)I('goodsId');
        $cat_id = (int)I('catId');
        if ($data_type == 1 && empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品id不能为空'));
        }
        if ($data_type == 2 && empty($cat_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '分类id不能为空'));
        }
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        if (!in_array($data_type, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的统计类型'));
        }
        $params = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data_type' => $data_type,
            'page' => $page,
            'page_size' => $page_size,
            'goods_id' => $goods_id,
            'cat_id' => $cat_id,
        );
        $m = new CashierModel();
        $data = $m->goodsSaleReportCustomerDetail($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 客户统计报表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fb0483
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param string userName 客户名称
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function customerReport()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $user_name = (string)I('userName');
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $m = new CashierModel();
        $data = $m->customerReport($shop_id, $user_name, $start_date, $end_date, $page, $page_size, $export);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 订单统计报表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/iirqhh
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function ordersReport()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $m = new CashierModel();
        $data = $m->ordersReport($shop_id, $start_date, $end_date, $page, $page_size, $export);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 销售毛利统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/asskbv
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int date_type 统计类型(1:按商品统计 2:按分类统计 3:按客户统计)
     * @param int model_type 统计模式(1:列表模式 2:图表模式)
     * @param int goodsCatId1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * @param int goodsCatId2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * @param int goodsCatId3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * @param string goods_keywords 商品名称或商品编码 PS:仅按商品统计时需要
     * @param string userName 客户名称 PS:仅按客户统计时需要
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function saleGrossProfit()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $data_type = I('data_type', 1);
        $model_type = I('model_type', 1);
        $goods_cat_id1 = (int)I('goods_cat_id1');
        $goods_cat_id2 = (int)I('goods_cat_id2');
        $goods_cat_id3 = (int)I('goods_cat_id3');
        $goods_keywords = (string)I('goods_keywords');
        $user_name = (string)I('userName');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $m = new CashierModel();
        $params = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data_type' => $data_type,
            'model_type' => $model_type,
            'goods_cat_id1' => $goods_cat_id1,
            'goods_cat_id2' => $goods_cat_id2,
            'goods_cat_id3' => $goods_cat_id3,
            'goods_keywords' => $goods_keywords,
            'user_name' => $user_name,
            'page' => $page,
            'page_size' => $page_size,
            'export' => $export
        );
        $data = $m->saleGrossProfit($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 销售毛利-客户详情 PS:仅销售毛利-按商品统计时才有该操作
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gdhh13
     * @param date startDate 开始日期
     * @param date endData 结束日期
     * @param int goodsId 商品id
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function saleGrossProfitCustomerDetail()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $goods_id = (int)I('goodsId');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的商品'));
        }
        $params = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'goods_id' => $goods_id,
            'page' => $page,
            'page_size' => $page_size,
            'export' => $export
        );
        $m = new CashierModel();
        $data = $m->saleGrossProfitCustomerDetail($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 客户毛利
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zknwm0
     * @param date startDate 开始日期
     * @param date endData 结束日期
     * @param string userName 客户名称
     * @param string bill_no 单号
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function customerGrossProfit()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $user_name = I('userName');
        $bill_no = I('bill_no');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $params = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user_name' => $user_name,
            'bill_no' => $bill_no,
            'page' => $page,
            'page_size' => $page_size,
            'export' => $export,
        );
        $m = new CashierModel();
        $data = $m->customerGrossProfit($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 客户毛利-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/henbmn
     * @param string token
     * @param int order_id 订单id
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function customerGrossProfitToDetail()
    {
        $this->MemberVeri();
        $order_id = I('order_id');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($order_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $m = new CashierModel();
        $data = $m->customerGrossProfitToDetail($order_id, $page, $page_size, $export);
        $this->ajaxReturn(returnData($data));
    }

    #############################收银报表-end###################################

    /**
     * 获取Plu商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/onb2bv
     * @param string token
     * @param string keywords 关键字(商品名称/编码/PLU编码)
     * @param int SuppPriceDiff 销售方式(-1:计件商品 1:称重商品)
     * @param int shopCatId1 门店一级分类id
     * @param int shopCatId2 门店二级分类id
     * @return json
     */
    public function getPluGoodsList()
    {
        $shop_info = $this->MemberVeri();
        $request_params = I();
        $request_params['shop_id'] = $shop_info['shopId'];
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $model = new CashierModel();
        $data = $model->getPluGoodsList($request_params, $page, $page_size);
        $this->ajaxReturn(returnData($data));
    }
}

?>
