<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 登陆日志服务类
 */
class LogLoginsModel extends BaseModel {
     /**
	  * 新增
	  */
	 public function insert(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = (int)I("id",0);
		$data = array();
		$data["loginId"] = (int)I("loginId");
		$data["staffId"] = (int)I("staffId");
		$data["loginTime"] = date('Y-m-d H:i:s');
		$data["loginIp"] = get_client_ip();;
		foreach ($data as $key=>$v){
			if(trim($v)==''){
				$rd['code'] = -2;
                $rd['msg'] = '参数不全';
				return $rd;
			}
		}
		$m = M('log_staff_logins');
		$rs = $m->add($data);
		if($rs){
			$rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		return $rd;
	 } 
	 /**
	  * 获取指定对象
	  */
     public function get(){
	 	$m = M('log_staff_logins');
		return $m->where("loginId=".(int)I('id'))->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
        $m = M('log_logins');
        $key = WSTAddslashes(I('key'));
	 	$sql = "select loginName,staffName,loginTime,loginIp from __PREFIX__log_staff_logins l,__PREFIX__staffs s where l.staffId=s.staffId 
	 	        and loginTime between'".I('startDate',date('Y-m-d',strtotime('-30 days')))." 00:00:00' and '".I('endDate',date('Y-m-d'))." 23:59:59'";
	 	if($key!='')$sql.=" (loginName like '%".$key."%' or staffName like '".$key."')";
	 	$sql.=" order by loginId desc";
		return $m->pageQuery($sql,$page,$pageSize);
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
	    $m = M('log_logins');
	     $sql = "select * from __PREFIX__log_logins order by loginId desc";
		 return $m->find($sql);
	  }
};
?>