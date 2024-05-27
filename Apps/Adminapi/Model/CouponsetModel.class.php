<?php
 namespace Adminapi\Model;
use App\Modules\Coupons\CouponsModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券服务类
 */
class CouponsetModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
         $rd = returnData(false, -1, 'error', '操作失败');

	 	//创建数据
		$data = array();
//        $data["title"] = I("title");
		if($this->checkEmpty($data,true)){
			$data["name"] = I("name");
			$data["couponId"] = I("couponId",0);
			$data["num"] = I("num");
            $data["oprice"] = I("oprice");
            $data["nprice"] = I("nprice");
		    $data["csFlag"] = 1;
			$rs = M('coupon_set')->add($data);

			if(false !== $rs){
                $rd = returnData(true,0,'success','操作成功');
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit(){
         $rd = returnData(false, -1, 'error', '操作失败');
	 	$id = (int)I('id',0);
	 	//修改数据
		$data = array();
//         $data["title"] = I("title");
		if($this->checkEmpty($data,true)){
			$data["name"] = I("name");
		    $data["couponId"] = I("couponId");
		    $data["num"] = I("num");
            $data["oprice"] = I("oprice");
            $data["nprice"] = I("nprice");
			$rs = M('coupon_set')->where("csId=".$id)->save($data);

			if(false !== $rs){
                return returnData(true,0,'success','操作成功');
			}
		}
		return $rd;
	 }

    /**
     * 获取配置信息
     */
    public function getCouponset(){
        return (array)M('coupon_set')->where("csId=".(int)I('id'))->find();
    }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
         $sql = "SELECT cs.*,wc.couponName FROM __PREFIX__coupon_set cs LEFT JOIN __PREFIX__coupons wc ON wc.couponId = cs.couponId WHERE cs.csFlag=1 ";
         //if(I('name')!='')$sql.=" and cs.name LIKE '%".WSTAddslashes(I('name'))."%'";
         if(I('title')!='')$sql.=" and cs.name LIKE '%".WSTAddslashes(I('title'))."%'";
         $sql.="  ORDER BY csId desc";
         $rs = $this->pageQuery($sql,$page,$pageSize);
         //sb
         $couponMoudle = new CouponsModule();
         foreach ($rs['root'] as &$val){
             $val['couponName'] = '';
             $couponRow = $couponMoudle->getCouponDetailById($val['couponId']);
             if(!empty($couponRow)){
                 $val['couponName'] = $couponRow['couponName'];
             }
         }
         unset($val);
         return $rs;
	 }
	  
	 /**
	  * 删除
	  */
	 public function del(){
//	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
	 	$id = (int)I('id');
	 	$m = M('coupon_set');
		$rs = $m->where("csId=".$id)->save(array('csFlag'=>-1));
		if(false !== $rs){
//		   $rd['code']= 0;
//            $rd['msg'] = '操作成功';
            $rd = returnData(true,0,'success','操作成功');
		}
		
		return $rd;
	 }

    /**
     * @return mixed
     * 获取会员专享优惠券列表[无分页]
     */
    public function getCouponsList(){
        $data = M('coupons')->where(array('shopId'=>0,'couponType'=>5,'validStartTime'=>array('ELT',date('Y-m-d')),'validEndTime'=>array('EGT',date('Y-m-d')),'sendStartTime'=>array('ELT',date('Y-m-d')),'sendEndTime'=>array('EGT',date('Y-m-d')),'dataFlag'=>1,'type'=>1))->select();
        if(empty($data)){
            return [];
        }
        return $data;
    }
};
?>