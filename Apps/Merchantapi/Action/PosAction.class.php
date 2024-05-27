<?php
 namespace Merchantapi\Action;;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 收银端控制器
 * TODO:此文件作废 因为是老版本的
 */
class PosAction extends BaseAction{
	/**
	 * 创建订单 pos开单
	 */

    public function orderCreate(){
    	$shopInfo = $this->MemberVeri();
		  $mod = D('Merchantapi/Pos');

      $data = $mod->orderCreate($shopInfo);
	//$this->ajaxReturn($shopInfo);
      if($data){
        $rs = array();
        $rs['status'] = 1;
        $rs['msg'] = '开单成功';
        $rs['data'] = $data;
        $this->ajaxReturn($rs);
      }else {
        $rs = array();
        $rs['status'] = -1;
        $rs['msg'] = '开单失败';
        $rs['data'] = null;
        $this->ajaxReturn($rs);
      }

	}
/*
*搜索商品
*模糊查询商品名
*模糊查找商品编码
*/
  public function search()
  {
      $rs = array();
      $shopInfo = $this->MemberVeri();
      $mod = D('Merchantapi/Pos');

      //接收参数
      $str = I('str');
      if(empty($str)){
        $rs['status'] = -1;
        $rs['msg'] = '参数不能为空';
        $rs['data'] = null;
        $this->ajaxReturn($rs);
      }


      $data = $mod->search($shopInfo,$str);
    //$this->ajaxReturn($shopInfo);
      if($data[0]){
//      if($data){
        $rs['status'] = 1;
        $rs['msg'] = '获取成功';
        $rs['data'] = $data;
        $this->ajaxReturn($rs);
      }else {
        $rs['status'] = -1;
        $rs['msg'] = '获取失败';
        $rs['data'] = null;
        $this->ajaxReturn($rs);
      }

  }

  /*
  *pos支付返回后 提交订单 无论是否成功都需要提交订单
  */
  public function submit()
  {
      $rs = array();
      $shopInfo = $this->MemberVeri();
      $mod = D('Merchantapi/Pos');

      // exit(json_encode($shopInfo)) ;

      //接收参数
      $pack = I('pack');//json数据

      //$pack = "{goods:[{goodsId:1,goodsName:'芒果'},{goodsId:2,goodsName:'西瓜'}],orderid:'4as65df46as4f6'}";

      if(empty($pack)){
        $rs['status'] = -1;
        $rs['msg'] = '参数不能为空!';
        $rs['data'] = null;
        $this->ajaxReturn($rs);
      }
       $pack = htmlspecialchars_decode($pack);//解决js JSON.stringify的参数被vue编码问题
       $pack = rtrim($pack, '"');//去除开头
       $pack = ltrim($pack, '"');//去除结尾
      $data = $mod->submit($shopInfo,json_decode($pack,true));

      // if($data){
      //   $rs['status'] = 1;
      //   $rs['msg'] = '提交成功';
      //   $rs['data'] = null;
      //   $this->ajaxReturn($rs);
      // }else {
      //   $rs['status'] = -1;
      //   $rs['msg'] = '提交失败';
      //   $rs['data'] = null;
      //   $this->ajaxReturn($rs);
      // }


      $this->ajaxReturn($data);

  }

  /*
  *获取pos订单列表-分页  可携带参数搜索
  */
  public function orders()
  {
    $rs = array();
   $shopInfo = $this->MemberVeri();
    //   $shopInfo = array('shopId'=>I('shopId',0,'intval'));
    $mod = D('Merchantapi/Pos');

    //接收参数
    $orderNO = I('orderNO');//订单号
    $count = (int)I('count',20);//条数 默认20
    $pageCount = (int)I('pageCount',1);//指定页码

    $startTime = I('startTime');//开始时间
    $endTime = I('endTime');//结束时间

      $userId = I('userId',0,'intval');//收银员ID

      $state = I('state',3,'intval');//1:待结算 2：已取消 3：已结算 4:退款


    $data = $mod->orders($shopInfo,$orderNO,$count,$pageCount,$startTime,$endTime,$userId,$state);

    $this->ajaxReturn($data);
    // if($data){
    //   $rs['status'] = 1;
    //   $rs['msg'] = '获取成功';
    //   $rs['data'] = $data;
    //
    // }else {
    //   $rs['status'] = -1;
    //   $rs['msg'] = '获取失败';
    //   $rs['data'] = $data;
    //   $this->ajaxReturn($rs);
    // }


  }

    /**
     * 收银员列表
     */
    public function cashierList(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId',0,'intval');
        if (empty($shopId)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $mod = D('Merchantapi/Pos');
        $list = $mod->getCashierList($shopId);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

  /*
  *通过会员动态码 获取用户信息
   * 支持实体卡登录
  */
  public function userInfo()
  {
      $apiRet['apiCode'] = -1;
      $apiRet['apiInfo'] = '会员不存在';
      $apiRet['apiState'] = 'error';
      $apiRet['apiData'] = '';

//    $shopInfo = $this->MemberVeri();
    $mod = D('Merchantapi/Pos');

    //接收参数
    $userCode = I('userCode');//会员code

    if(empty($userCode)){
        $apiRet['apiInfo'] = '参数不能为空';
      $this->ajaxReturn($apiRet);
    }

      $userCode_arr = explode('K',$userCode);
      $userId = intval($userCode_arr[1]);
      if (!empty($userId)) {//实体卡
          $data = D('Home/Users')->getUserInfoRow(array('userId'=>$userId,'cardNum'=>$userCode_arr[0]));
      } else {//会员个人中心二维码
//          $data = $mod->userInfo($shopInfo, $userCode);
          $data = $mod->userInfo($userCode);
          if ($data['apiCode'] == -1) $this->ajaxReturn($data);
          $data = $data['apiData'];
      }

      if (!empty($data)) {
          $data['memberToken'] = getUserTokenByUserId($data['userId']);
          $data['historyConsumeIntegral'] = historyConsumeIntegral($data['userId']);
          $apiRet['apiCode'] = 0;
          $apiRet['apiInfo'] = '登录成功';
          $apiRet['apiState'] = 'success';
          $apiRet['apiData'] = $data;
      }
    $this->ajaxReturn($apiRet);

  }

  /*
  *获取积分和现金兑换比例
  */
  public function integral()
  {
    $rs = array();
    $shopInfo = $this->MemberVeri();
    $mod = D('Merchantapi/Pos');

    $data = $GLOBALS['CONFIG']['scoreCashRatio'];

    if($data){
      $rs['status'] = 1;
      $rs['msg'] = '获取成功';
      $rs['data'] = $data;
      $this->ajaxReturn($rs);
    }else {
      $rs['status'] = -1;
      $rs['msg'] = '获取失败';
      $rs['data'] = $data;
      $this->ajaxReturn($rs);
    }

  }

  //余额充值 如果有第三方店铺 就不可靠了  但是  功能可封包 配置组件包 满足不同模式用户
  //余额消费 积分消费 余额消费后需要单独与商户结算  积分消费也可以和商户结算 当然为了吸引客户 可以不用结算
/*
  *生成条码
  */
  public function getPosBarcode()
  {
      $shopInfo = $this->MemberVeri();
      $parameter = I();//---此种传参方式不可取 不能直观看到接收参数和数据库模型操作对数据的需求
      $parameter['shopId'] = $shopInfo['shopId'];
      $mod = D('Merchantapi/Pos');
      $data = $mod->getPosBarcode($parameter);
      if($data){
          $this->returnResponse(1,'获取成功',$data);
      }else{
          $this->returnResponse(-1,'获取失败',$data);

      }
  }

    /**
     * 店铺列表列表查询
     */
    public function getShopCatsList(){
        $shopInfo = $this->MemberVeri();
        $mod = D('Merchantapi/Pos');
        $paramete = I();
        $paramete['shopId'] = $shopInfo['shopId'];
        $list = $mod->queryByList($paramete);
        $this->returnResponse(1,'获取成功',$list);
    }

    /**
     * 获取店铺商品1
     */
    public function getShopGoodsList(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $paramete = I();
        $paramete['shopId'] = $shopInfo['shopId'];
        $page = $m->queryGetGoodsList($paramete);
        $this->returnResponse(1,'获取成功',$page);
    }

    /**
     * 查看POS订单状态
     */
    public function posOrderInfo(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');
        $orderNo = I('orderNo','','trim');

        if (empty($shopId) || empty($orderNo)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Pos');
        $orderInfo = $m->getPosOrderInfo(array('shopId'=>$shopId,'orderNO'=>$orderNo));

        if ($orderInfo) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $orderInfo;
        } else {
            $apiRet['apiInfo'] = '订单不存在';
            $this->ajaxReturn($apiRet);
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 扫码支付
     */
    public function scanPay(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

//        $userId = $this->MemberVeri()['userId'];
        $userId = I('userId',0,'intval');
        $type = I('type',0,'intval');//类型，4：微信 5：支付宝
        $auth_code = I('auth_code','','trim');
        $money = I('money',0);
        $orderNo = I('orderNo',0,'trim');

        $pom = M('pos_orders');
        $order_info = $pom->where(array('orderNO'=>$orderNo))->find();

//        $out_trade_no = uniqid().date("YmdHis").mt_rand(100, 1000);
        $out_trade_no = date("YmdHis").mt_rand(100, 1000);
//        $out_trade_no = joinString($order_info['id'],0,18);

        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")：开始 \r\n");
        fclose($myfile);

        if (empty($type) || empty($auth_code) || empty($money) || empty($orderNo)) {
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")：参数不全 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        if ($money <= 0) {
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")：请输入大于0的金额 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '请输入大于0的金额';
            $this->ajaxReturn($apiRet);
        }

        if ($type == 4) {//微信
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 微信付款码： 开始 \r\n");
            fclose($myfile);

            $result = $this->doWxPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
        } else if ($type == 5) {//支付宝
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 开始 \r\n");
            fclose($myfile);

            $result = $this->doAliPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
        }

        //收款成功
        if ($result['apiCode'] == 0) {
//            if (!empty($result['apiData'])) $out_trade_no = $result['apiData']['transaction_id'];
            $pom->where(array('orderNO'=>$orderNo))->save(array('outTradeNo'=>$out_trade_no));
        }

        $this->ajaxReturn($result);

    }

    /**
     * 微信支付 - 动作
     */
    public function doWxPay($userId,$auth_code,$money,$orderNo,$out_trade_no){

        $m = D('Merchantapi/Pos');
        $result = $m->doWxPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
        if ($result['apiCode'] == 0){
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
    public function doAliPay($userId,$auth_code,$money,$orderNo,$out_trade_no){

        $m = D('Merchantapi/Pos');
        $result = $m->doAliPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
        if ($result['apiCode'] == 0){
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
     * 微信/支付宝 扫码支付成功后的回调
     */
    public function scanPayCallback($orderNo){

        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")： 进入回调函数 \r\n");
        fclose($myfile);

        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').") - 回调函数： 开始 \r\n");
        fclose($myfile);

        $where = array('orderNO'=>$orderNo);
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
     * 校验订单
     */
    public function checkOrder($orderNo){
        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $orderInfo = $pom->where(array('orderNO'=>$orderNo))->find();
        if (!empty($orderInfo)) {
            $total_money = 0;
            $order_goods_list = $pogm->where(array('orderid'=>$orderInfo['id']))->select();
            if (!empty($order_goods_list)) {
                foreach ($order_goods_list as $v) {
                    $total_money += $v['presentPrice']*($v['number']+$v['weight']);
                }
            }
            //如果校验金额和订单金额不一致，则写入日志
            $total_money_new = $total_money*($orderInfo['discount']/100);
            if ($total_money_new != $orderInfo['realpayment']) {

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(".date('Y-m-d H:i:s').")-异常订单：开始 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(".date('Y-m-d H:i:s').")-异常订单-订单号：".$orderNo." \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(".date('Y-m-d H:i:s').")-异常订单-校验前实付金额：".$orderInfo['realpayment']." \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(".date('Y-m-d H:i:s').")-异常订单-校验后实付金额：".$total_money_new." \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(".date('Y-m-d H:i:s').")-异常订单-校验后结论：校验前 和 校验后 实付金额不一致 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "POS订单核验(".date('Y-m-d H:i:s').")-异常订单：结束 \r\n");
                fclose($myfile);
            }
        }

    }

    /**
     * 根据 订单编号 获取订单详情和订单商品
     */
    public function getOrderDetailAndOrderGoodsByOrderNo(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId',0,'intval');
        $orderNO = I('orderNO',0,'trim');

        if (empty($shopId) || empty($orderNO)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $mod = D('Merchantapi/Pos');
        $result = $mod->getOrderDetailAndOrderGoodsByOrderNo($shopId,$orderNO);

        $this->ajaxReturn($result);
    }

    /**
     * 退货退款 - 动作
     */
/*    public function returnGoods(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
//        $shopId = I('shopId',0,'intval');
        $orderid = I('orderId',0,'trim');
        $orderMoney = I('orderMoney',0);//退款总金额
        $goods_info = I('goods','');
        $goods_info = json_decode(htmlspecialchars_decode($goods_info),true);
//        $goods_info = array(
//            array(
//                'goodsId'=>'',//商品ID
//                'goodsName'=>'',//商品名称
//                'number'=>'',//商品数量
//                'weight'=>'',//商品重量
//            ),
//            array(
//                'goodsId'=>'',//商品ID
//                'goodsName'=>'',//商品名称
//                'number'=>'',//商品数量
//                'weight'=>'',//商品重量
//            ),
//        );

        if (empty($shopId) || empty($orderid) || empty($goods_info)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $userId = I('userId',0,'intval');

        $mod = D('Merchantapi/Pos');
        $result = $mod->returnGoods($shopId,$orderid,$orderMoney,$goods_info,$userId);

        $this->ajaxReturn($result);
    }*/

    /**
     * 退货退款 - 动作
     */
    public function returnGoods(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $shopInfo = $this->MemberVeri();
        $mod = D('Merchantapi/Pos');

        //接收参数
        $pack = I('pack');//json数据

        //$pack = "{goods:[{goodsId:1,goodsName:'芒果'},{goodsId:2,goodsName:'西瓜'}],orderid:'4as65df46as4f6'}";

        if(empty($pack)){
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }
        $pack = htmlspecialchars_decode($pack);//解决js JSON.stringify的参数被vue编码问题
        $pack = rtrim($pack, '"');//去除开头
        $pack = ltrim($pack, '"');//去除结尾
        $data = $mod->returnGoods($shopInfo,json_decode($pack,true));

        $this->ajaxReturn($data);
    }

    /**
     * 获取Pos订单列表
     * @param string token
     * @param string orderNo PS:订单号
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string maxMoney PS:最大金额(金额区间)
     * @param string minMoney PS:最小金额(金额区间)
     * @param int state PS:状态 (1:待结算 | 2：已取消 | 3：已结算 | 20:全部)
     * @param int pay PS:支付方式 (1:现金支付 | 2：余额支付 | 3：银联支付 | 4：微信支付 | 5：支付宝支付 | 6：组合支付 | 20:全部)
     * @param string name PS:收银员账号
     * @param string username PS:收银员姓名
     * @param string phone PS:收银员手机号
     * @param string identity PS:身份 1:会员 2：游客
     * @param string membername PS:会员名
     * @param int p PS:页码
     */
    public function getPosOrderList(){
        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
        $m = D('Merchantapi/Pos');
        $param = I();
        $res = $m->getPosOrderList($shopId,$param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取Pos订单详情
     * @param string token
     * @param int posId //Pos订单id
     * */
    public function getPosOrderDetail(){
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '参数不全';
        $apiRet['apiState'] = 'error';
        $shopId = $shopInfo['shopId'];
        $m = D('Merchantapi/Pos');
        $param = I();
        if(empty($param['posId'])){
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getPosOrderDetail($shopId,$param);
        $this->ajaxReturn($res);
    }

    /**
     * 获取Pos结算订单列表
     * @param string token
     * @param string settlementNo PS:结算单号
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string maxMoney PS:最大结算金额(金额区间)
     * @param string minMoney PS:最小结算金额(金额区间)
     * @param int isFinish PS:状态 (1:未结算 | 2：已结算 | 20:全部)
     * @param int p PS:页码
     */
    public function getPosOrderSettlementList(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo['shopId'] = 1;
        $shopId = $shopInfo['shopId'];
        $m = D('Merchantapi/Pos');
        $param = I();
        $param['shopId'] = $shopInfo['shopId'];
        $res = $m->getPosOrderSettlementList($shopId,$param);
        $this->ajaxReturn($res);
    }


    /**
     * Pos订单结算申请
     * @param string token
     * @param string ids PS:pos订单id,多个用英文逗号隔开,例子:1,2,3
     */
    public function posOrderSettlement(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '参数不全';
        $apiRet['apiState'] = 'error';
        $param['shopId'] = $shopInfo['shopId'];
        $param['ids'] = trim(I('ids'),',');
        if(empty($param['ids'])){
            return $apiRet;
        }
        $res = $m = D('Merchantapi/Pos')->posOrderSettlement($param);
        $this->ajaxReturn($res);
    }

    /**
     * 商家预存款（充值）
     */
    public function recharge(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        //是否开启商户预存款 0:否  1：是
        if (empty($GLOBALS["CONFIG"]["openPresaleCash"])) {//没有开启
            $apiRet['apiInfo'] = '没有开启商户预存款';
            $this->ajaxReturn($apiRet);
        } else {//开启预存款
//            $userId = $this->MemberVeri()['userId'];
            $userId = I('userId',-10,'intval');
            $type = I('type',0,'intval');//类型，4：微信 5：支付宝（1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付）
            $auth_code = I('auth_code','','trim');
            $money = I('money',0);
            $orderNo = I('orderNo',-10,'trim');
            $shopId = I('shopId',0,'intval');

            $pom = M('pos_orders');
            $order_info = $pom->where(array('orderNO'=>$orderNo))->find();

//        $out_trade_no = uniqid().date("YmdHis").mt_rand(100, 1000);
            $out_trade_no = date("YmdHis").mt_rand(100, 1000);
//        $out_trade_no = joinString($order_info['id'],0,18);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")：开始 \r\n");
            fclose($myfile);

            if (empty($userId) || empty($type) || empty($auth_code) || empty($money) || empty($orderNo) || empty($shopId)) {
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")：参数不全 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '参数不全';
                $this->ajaxReturn($apiRet);
            }

            if ($money <= 0) {
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")：请输入大于0的金额 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '请输入大于0的金额';
                $this->ajaxReturn($apiRet);
            }

            $m = D('Merchantapi/Pos');
            if ($type == 4) {//微信
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 微信付款码： 开始 \r\n");
                fclose($myfile);

                $result = $m->doWxPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
            } else if ($type == 5) {//支付宝
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(".date('Y-m-d H:i:s').")方式 - 支付宝付款码： 开始 \r\n");
                fclose($myfile);

                $result = $m->doAliPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
            }

            //写入充值记录
            $recharge_data = array(
                'targetType'    =>  1,
                'targetId'      =>  $shopId,
                'dataSrc'   =>  3,
                'dataId'    =>  0,
                'moneyRemark'   =>  '充值',
                'moneyType' =>  1,
                'money' =>  $money,
                'createTime'    =>  date('Y-m-d H:i:s'),
                'dataFlag'  =>  1,
                'state' =>  empty($result['apiCode'])?1:0,
                'payType'   =>  $type
            );
            $m->addRechargeLog($recharge_data);

            //充值成功，将金额充到商户账户
            if (empty($result['apiCode'])) {
                M('shops')->where(array('shopId'=>$shopId))->setInc('predeposit',$money);
            }

            $this->ajaxReturn($result);
        }
    }

    /**
     * 商户预存款流水
     */
    public function rechargeRecord(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId',0,'intval');
        if (empty($shopId)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',10,'intval');

        $m = D('Merchantapi/Pos');
        $list = $m->getRechargeRecord($shopId, $page, $pageSize);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 会员套餐列表
     */
    public function setmealList(){
        $m = D('V3/Api');
        $result = $m->getSetmealList();

        $this->ajaxReturn($result);
    }

    /**
     * 购买会员套餐
     */
    public function buySetmeal(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '参数不全';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = null;

        $shopId = $this->MemberVeri()['shopId'];

        $memberToken = I('memberToken','','trim');
        $smId = I('smId', 0, 'intval');
        $type = I('type',1,'intval');//类型，（1:现金 4：微信 5：支付宝）
        if (empty($memberToken) || empty($smId) || empty($shopId)){
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $user = userTokenFind($memberToken,86400*30);
        if (!$user) {
            $apiRet['apiInfo'] = 'memberToken 不存在';
            $this->ajaxReturn($apiRet);
        }
        $money = M('set_meal')->where(array('smId'=>$smId))->getField('money');
        //如果是现金支付，则需要验证商户预存款是否足够
        if ($type == 1) {
            $predeposit = M('shops')->where(array('shopId'=>$shopId))->getField('predeposit');
            if ($predeposit < $money) {
                $apiRet['apiInfo'] = '商户预存款不足';
                $this->ajaxReturn($apiRet);
            }
        }

        $m = D('V3/Api');
        $result = $m->buySetmeal($user, $smId);

        //支付成功
        if ($result['apiCode'] == 0) {
            //如果是现金支付，则需要从商户预存款中扣除相应金额
            if ($type == 1) {//现金
                M('shops')->where(array('shopId'=>$shopId))->setDec('predeposit',$money);
            }
        }

        $this->ajaxReturn($result);
    }

    /**
     * 申请提现
     */
    public function applyWithdraw(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $shops = $this->MemberVeri();
        if ($shops['id']) {
            $apiRet['apiInfo'] = '只有总管理员才可以申请提现';
            $this->ajaxReturn($apiRet);
        }
        $shopId = $shops['shopId'];
//        $shopId = I('shopId',0,'intval');
        $money = I('money',0);
        if (empty($shopId) || empty($money)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        $shop_info = M('shops')->where(array('shopId'=>$shopId))->find();
        if (empty($shop_info)) {
            $apiRet['apiInfo'] = '商户不存在';
            $this->ajaxReturn($apiRet);
        }
        if ($money > $shop_info['predeposit']){
            $apiRet['apiInfo'] = '提现金额不能大于预存款';
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Pos');

        $data = array(
            'shopId'    =>  $shopId,
            'money'     =>  $money,
            'state'     =>  0,
            'addTime'   =>  date('Y-m-d H:i:s'),
            'updateTime'    =>  ''
        );
        $insert_id = $m->addWithdraw($data);
        if ($insert_id > 0) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = array('id'=>$insert_id);
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 会员充值
     */
    public function userRecharge(){
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
        $type = I('type',0,'intval');//类型，4：微信 5：支付宝（1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付）
        $auth_code = I('auth_code','','trim');
        $money = I('money',0);
        $orderNo = I('orderNo','','trim');
        $shopId = I('shopId',0,'intval');

        $pom = M('pos_orders');
        $order_info = $pom->where(array('orderNO'=>$orderNo))->find();

//        $out_trade_no = uniqid().date("YmdHis").mt_rand(100, 1000);
        $out_trade_no = date("YmdHis").mt_rand(100, 1000);
//        $out_trade_no = joinString($order_info['id'],0,18);

        if (empty($userId) || empty($type) || empty($money) || empty($shopId)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }

        if ($money <= 0) {
            $apiRet['apiInfo'] = '请输入大于0的金额';
            $this->ajaxReturn($apiRet);
        }

        //如果是现金付款，则需要判断一下商户预存款是否足够
        if ($type == 1) {
            //获取商户预存款
            $predeposit = M('shops')->where(array('shopId'=>$shopId))->getField('predeposit');
            if ($predeposit < $money) {
                $apiRet['apiInfo'] = '商户预存款不足';
                $this->ajaxReturn($apiRet);
            }
        }

        $m = D('Merchantapi/Pos');
        if ($type == 4) {//微信
            $result = $m->doWxPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
        } else if ($type == 5) {//支付宝
            $result = $m->doAliPay($userId,$auth_code,$money,$orderNo,$out_trade_no);
        }

        //写入充值记录
        $recharge_data = array(
            'targetType'    =>  0,
            'targetId'      =>  $userId,
            'dataSrc'   =>  3,
            'dataId'    =>  empty($order_info)?'':$order_info['id'],
            'moneyRemark'   =>  '充值',
            'moneyType' =>  1,
            'money' =>  $money,
            'createTime'    =>  date('Y-m-d H:i:s'),
            'dataFlag'  =>  1,
            'state' =>  empty($result['apiCode'])?1:0,
            'payType'   =>  $type
        );
        $m->addRechargeLog($recharge_data);

        //充值成功，将金额充到会员余额（包括微信、支付宝、现金）
        if (empty($result['apiCode']) || $type == 1) {
            $res = M('users')->where(array('userId'=>$userId))->setInc('balance',$money);
            //充值成功，写入余额流水
            if ($res) {
                $user_balance_data = array(
                    'userId'    =>  $userId,
                    'balance'   =>  $money,
                    'dataSrc'   =>  2,
                    'orderNo'   =>  $orderNo,
                    'dataRemarks'   =>  '充值',
                    'balanceType'   =>  1,
                    'createTime'    =>  date('Y-m-d H:i:s'),
                    'shopId'    =>  $shopId
                );
                M('user_balance')->add($user_balance_data);

                if ($type == 1) {//现金
                    //如果是现金，则需要从预存款中扣除对应金额
                    M('shops')->where(array('shopId'=>$shopId))->setDec('predeposit',$money);
                }
            }
        }

        $user_balance = M('users')->where(array('userId'=>$userId))->field('balance')->find();
        if (in_array($type,array(4,5))) {//微信、支付宝
            $result['apiData'] = $user_balance;
            $this->ajaxReturn($result);
        } else {//现金
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '收款成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $user_balance;
            $this->ajaxReturn($apiRet);
        }
    }

    /**
     * 测试方法，一会删除
     */
    public function testApi(){
//        $dir = $_SERVER['DOCUMENT_ROOT']."/phptmp";
//        deleteDir($dir);exit();
//        echo "<pre>";var_dump($_SERVER);exit();
        $id = I('id',0,'intval');
//        $alipay = 0.01;
//        $result = alipayRefund($id,$alipay,'',1);
        $wechat = 0.01;
        $result = wxRefundForDangMianFu($id,$wechat);
        echo json_encode($result);
        exit();
    }
}
?>
