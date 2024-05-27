<?php
 namespace Home\Action;;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 支付控制器
 */
class PaymentsAction extends BaseAction{

    public function _initialize(){
        header("Content-type: text/html; charset=utf-8");
//        Vendor('Alipay.dangmianfu.f2fpay.model.builder.AlipayTradePayContentBuilder');
        Vendor('Alipay.dangmianfu.f2fpay.service.AlipayTradeService');

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：进入方法 \r\n");
        fclose($myfile);
    }
	
	/**
	 * 获取支付宝URL
	 */
    public function getAlipayURL(){
    	$this->isUserLogin();
    	$morders = D('Home/Orders');
		$USER = session('WST_USER');
		$obj["userId"] = (int)$USER['userId'];

		$data = $morders->checkOrderPay($obj);
    	if($data["status"]==1){
    		$m = D('Home/Payments');
    		$url =  $m->getAlipayUrl();
    		$data["url"] = $url;
    	}
		$this->ajaxReturn($data);
	}

	public function getWeixinURL(){
		$this->isUserLogin();
		$morders = D('Home/Orders');
		$USER = session('WST_USER');
		$obj["userId"] = (int)$USER['userId'];
		
		$data = $morders->checkOrderPay($obj);
		if($data["status"]==1){
			$m = D('Home/Payments');
			$orderId = (int)I("orderId");
			if($orderId>0){
				$pkey = $obj["userId"]."@".$orderId."@1";
			}else{
				$pkey = $obj["userId"]."@".session("WST_ORDER_UNIQUE")."@2";
			}
			$data["url"] = U('Home/WxPay/createQrcode',array("pkey"=>base64_encode($pkey)));
		}
		$this->ajaxReturn($data);
	}
	
	/**
	 * 支付
	 */
	public function toPay(){
		$this->isUserLogin();
		$USER = session('WST_USER');
		$morders = D('Home/Orders');
		//支付方式
		$pm = D('Home/Payments');
		$payments = $pm->getList();
		$this->assign("payments",$payments["onlines"]);

		$obj["orderId"] = (int)I("orderId");
		$data = $morders->getPayOrders($obj);
		$orders = $data["orders"];
		$needPay = $data["needPay"];
		$this->assign("orderId",$obj["orderId"]);
		$this->assign("orders",$orders);
		$this->assign("needPay",$needPay);
		$this->assign("orderCnt",count($orders));
		$this->display('default/payment/order_pay');
	}
	
	/**
	 * 支付结果同步回调
	 */
    public function response(){
		$request = $_GET;
		unset($request['_URL_']);
		$pay_res = D('Payments')->notify($request);
		if($pay_res['status']){
			header('Location:../../index.php?m=Home&c=Orders&a=queryByPage',false);
			//支付成功业务逻辑
		}else{
			$this->error('支付失败');
		}
	}
	
	/**
	 * 支付结果异步回调
	 */
    /*public function notify(){
		$pm = D('Home/Payments');
		$request = $_POST;
		$pay_res = $pm->notify($request);
		if($pay_res['status']){
			//商户订单号
			$obj = array();
			$obj["trade_no"] = $_POST['trade_no'];
			$obj["out_trade_no"] = $_POST['out_trade_no'];
			$obj["total_fee"] = $_POST['total_fee'];
			$extras = explode("@",$_POST['extra_common_param']);
			$obj["userId"] = $extras[0];
			$obj["order_type"] = $extras[1];
			$obj["payFrom"] = 1;
			//支付成功业务逻辑
			$payments = $pm->complatePay($obj);
			echo 'success';
		}else{
			echo 'fail';
		}
	}*/

    /**
     * 支付结果异步回调
     */
    public function notify(){
        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：进入回调 \r\n");
        fclose($myfile);
        $pm = D('Home/Payments');

        $config = $pm->getPayment ( "alipay" );
        $arr = $_POST;

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：开始 \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($arr);
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s')."),返回的全部参数：$txt \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：结束 \r\n");
        fclose($myfile);

        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        if($result){

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
//            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
//            $trade_no = $_POST['trade_no'];

            //交易状态
//            $trade_status = $_POST['trade_status'];
            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            //商户订单号
            $obj = array();
            $obj["trade_no"] = $_POST['trade_no'];
            $obj["out_trade_no"] = $_POST['out_trade_no'];
            $obj["total_fee"] = $_POST['total_amount'];
            $extras = explode("@",$_POST['extra_common_param']);
            $obj["userId"] = $extras[0];
            $obj["order_type"] = $extras[1];
            $obj["payFrom"] = 1;
            //支付成功业务逻辑
            $payments = $pm->complatePay($obj);
            echo 'success';
        }else{
            echo 'fail';
        }
    }

    public function notifyUser() {
        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：进入回调 \r\n");
        fclose($myfile);
        $pm = D('Home/Payments');

        $config = $pm->getPayment ( "alipay" );
        $arr = $_POST;

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：开始 \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($arr);
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s')."),返回的全部参数：$txt \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：结束 \r\n");
        fclose($myfile);

        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        if($result){

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
//            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
//            $trade_no = $_POST['trade_no'];

            //交易状态
//            $trade_status = $_POST['trade_status'];
            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            // 此处应该更新一下订单状态，商户自行增删操作
            $trade_no = $arr["trade_no"];
            $total_fee = $arr["total_amount"];
            $out_trade_no = $arr['out_trade_no'];
//            $pm = D ( 'Home/Payments' );

            $orderToken = M('order_merge')->where(['orderToken'=>$arr['out_trade_no']])->find();
            $openid = $orderToken['value'];
            // 用户充值数组组织
            $obj = array ();
            $obj ["trade_no"] = $trade_no;
            $obj ["userId"] = $out_trade_no;
            $obj ["balance"] = $total_fee;
            $obj ["openid"] = $openid;
            $obj ["out_trade_no"] = $out_trade_no;


//                $obj ["userId"] = $userId;
//                $obj ["order_type"] = $orderType;
//                $obj["payFrom"] = 2;
            // 支付成功业务逻辑
            $res = $pm->wxNotifyUser ( $obj );
            S ("$out_trade_no",$total_fee);
        }


        if($res){
            // echo "SUCCESS";exit;
            header("Content-type:text/xml");
            $resxml['return_code'] = 'SUCCESS';
            exit(arrayToXml($resxml));
        }
    }

    /**
     * 购买会员套餐
     */
    public function notifyBuySetmeal() {
        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：进入回调 \r\n");
        fclose($myfile);
        $pm = D('Home/Payments');

        $config = $pm->getPayment ( "alipay" );
        $arr = $_POST;

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：开始 \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($arr);
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s')."),返回的全部参数：$txt \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：结束 \r\n");
        fclose($myfile);

        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        if($result){

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
//            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
//            $trade_no = $_POST['trade_no'];

            //交易状态
//            $trade_status = $_POST['trade_status'];
            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

                // 此处应该更新一下订单状态，商户自行增删操作

                $m = M('transaction');
                $transactionInfo = $m->where(array('transaction_id'=>$arr['trade_no']))->find();
                if (!empty($transactionInfo)) {
                    return "success";
                } else {
                    $m->add(array(
                        'transaction_id'=>$arr['trade_no'],
                        'createTime'=>date('Y-m-d H:i:s')
                    ));
                }

                //$out_trade_no = $order['out_trade_no'];
                $orderToken = M('order_merge')->where(['orderToken'=>$arr['out_trade_no']])->find();
                $out_trade_no = $orderToken['value'];
                $out_trade_no_arr = explode('-',$out_trade_no);
                $userId = $out_trade_no_arr[1];
                $smId = $out_trade_no_arr[2];
                $m = D ('Mendianapi/Api');
                $res = $m->notifyBuySetmeal($userId,$smId);
        }

        if($res){
            // echo "SUCCESS";exit;
            header("Content-type:text/xml");
            $resxml['return_code'] = 'SUCCESS';
            exit(arrayToXml($resxml));
        }
    }

    /**
     * 购买抢购加量包
     */
    public function notifyBuyCouponset() {
        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：进入回调 \r\n");
        fclose($myfile);
        $pm = D('Home/Payments');

        $config = $pm->getPayment ( "alipay" );
        $arr = $_POST;

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：开始 \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($arr);
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s')."),返回的全部参数：$txt \r\n");
        fclose($myfile);

        $myfile = fopen("alipay_pay.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "支付宝notify回调(".date('Y-m-d H:i:s').")：结束 \r\n");
        fclose($myfile);

        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        if($result){

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
//            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
//            $trade_no = $_POST['trade_no'];

            //交易状态
//            $trade_status = $_POST['trade_status'];
            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            // 此处应该更新一下订单状态，商户自行增删操作

            $m = M('transaction');
            $transactionInfo = $m->where(array('transaction_id'=>$arr['trade_no']))->find();
            if (!empty($transactionInfo)) {
                return "success";
            } else {
                $m->add(array(
                    'transaction_id'=>$arr['trade_no'],
                    'createTime'=>date('Y-m-d H:i:s')
                ));
            }

            //$out_trade_no = $order['out_trade_no'];
            $orderToken = M('order_merge')->where(['orderToken'=>$arr['out_trade_no']])->find();
            $out_trade_no = $orderToken['value'];
            $out_trade_no_arr = explode('-',$out_trade_no);
            $userId = $out_trade_no_arr[1];
            $csId = $out_trade_no_arr[2];
            $m = D ('Mendianapi/Api');
            $res = $m->notifyBuyCouponset($userId,$csId);
        }

        if($res){
            // echo "SUCCESS";exit;
            header("Content-type:text/xml");
            $resxml['return_code'] = 'SUCCESS';
            exit(arrayToXml($resxml));
        }
    }
};
?>