<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 支付类
 */
class PaymentsModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function add(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$data = array();
		$data["payCode"] = I("payCode");
		$data["payName"] = I("payName");
		$data["payDesc"] = I("payDesc");
		if($this->checkEmpty($data,true)){
			$data["payOrder"] = (int)I("payOrder",0);
			$data["payConfig"] = I("payConfig");
			$data["enabled"] = (int)I("enabled");
			$data["isOnline"] = (int)I("isOnline");
			$rs = M('payments')->add($data);
			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 }
     /**
	  * 修改
	  */
	 public function edit(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = (int)I("id",0);
		$data["payName"] = I("payName");
		$data["payDesc"] = I("payDesc");
		$data["payOrder"] = (int)I("payOrder");
		$data["payConfig"] = htmlspecialchars_decode(I("payConfig"));
		$pay_config = json_decode($data['payConfig'],true);
		foreach ($pay_config as $key=>&$item){
		    $item = I($key);

        }
		unset($item);
		$data['payConfig'] = htmlspecialchars_decode(json_encode($pay_config,true));
//         json_decode(htmlspecialchars_decode(I("payConfig")),true);
		$data["enabled"] = 1;
		if($this->checkEmpty($data)){
			$rs = M('payments')->where("id=".$id)->save($data);
			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 }
	 /**
	  * 获取指定对象
	  */
     public function get(){
		$payment = M('payments')->where("id=".(int)I('id'))->find();
//		$payConfig = json_decode(htmlspecialchars_decode($payment["payConfig"]),true);
		$payConfig = json_decode($payment["payConfig"],true);

		foreach ($payConfig as $key => $value) {
            $payment[$key] = $value;
		}
		return $payment;
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
	 	$sql = "select * from __PREFIX__payments order by payOrder asc";
		$rs = $this->pageQuery($sql,$page,$pageSize);

		foreach ($rs["root"] as $key => $value) {
			 $rs["root"][$key]["payDesc"] = htmlspecialchars_decode($value["payDesc"]) ;
		}
		return $rs;
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
		 $rs = M('payments')->select();
		 return $rs;
	  }

	 /**
	  * 删除
	  */
	 public function del(){
         $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$data["enabled"] = 0;
		$rs = M('payments')->where("id=".(int)I('id'))->save($data);
		if(false !== $rs){
			$rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		return $rd;
	 }

    /**
     * @param $params
     * @return mixed
     * 变更支付状态
     * https://www.yuque.com/youzhibu/ruah6u/ph7lel
     */
	 public function editPayStatus($params){
         $id = $params['id'];
         $enabled = $params['enabled'];

         if($enabled != 0){
             $payment = M('payments')->where(['id'=>$id])->field('payCode,enabled')->find();

             switch ($payment['payCode']){
                 case 'cod'://货到付款
                     $codWhere['payCode'] = 'balance';
                     $payCheck = M('payments')->where($codWhere)->field('enabled')->find();
                     if ($payCheck['enabled'] == 1) {
                         return returnData(null, -1, 'error', '货到付款余额支付不可同时开启');
                     }
                     break;
                 case 'balance'://余额支付
                     $balanceWhere['payCode'] = 'cod';
                     $payCheck = M('payments')->where($balanceWhere)->field('enabled')->find();
                     if ($payCheck['enabled'] == 1){
                         return returnData(null, -1, 'error', '余额支付货到付款不可同时开启');
                     }
                     break;
             }
         }

         $data = M('payments')->where(['id'=>$id])->save(['enabled'=>$enabled]);
         if(empty($data)){
             return returnData(null, -1, 'error', '操作失败');
         }
         return returnData(true);
     }

}