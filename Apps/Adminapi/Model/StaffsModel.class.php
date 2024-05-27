<?php
 namespace Adminapi\Model;
 use App\Modules\Roles\RolesServiceModule;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 职员服务类
 */
class StaffsModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$data = array();
		$data["loginName"] = I("loginName");
		$data["secretKey"] = rand(1000,9999);
		$data["loginPwd"] = md5(I("loginPwd").$data["secretKey"]);
		$data["staffName"] = I("staffName");
		$data["staffRoleId"] = I("staffRoleId");//优化支持多角色
		$data["workStatus"] = (int)I("workStatus");
		$data["staffStatus"] = (int)I("staffStatus");
		$data["staffFlag"] = 1;
		$data["createTime"] = date('Y-m-d H:i:s');
	    if($this->checkEmpty($data,true)){
	    	$data["staffNo"] = I("staffNo");
	    	$data["staffPhoto"] = I("staffPhoto");
			$rs = $this->add($data);
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
		$data = array();
		$data["loginName"] = I("loginName");
		$data["staffName"] = I("staffName");
		$data["staffRoleId"] = I("staffRoleId");//优化支持多角色
		$data["workStatus"] = (int)I("workStatus");
		$data["staffStatus"] = (int)I("staffStatus");
	    if($this->checkEmpty($data)){
	    	$data["staffNo"] = I("staffNo");
	    	$data["staffPhoto"] = I("staffPhoto");
			$rs = $this->where("staffId=".$id)->save($data);
			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
				$staffId = (int)session('WST_STAFF.staffId');
		        if($staffId==$id){
		        	 session('WST_STAFF.loginName',$data["loginName"]);
		        	 session('WST_STAFF.staffName',$data["staffName"]);
		        	 session('WST_STAFF.staffRoleId',$data["staffRoleId"]);
		        	 session('WST_STAFF.workStatus',$data["workStatus"]);
		        	 session('WST_STAFF.staffStatus',$data["staffStatus"]);
		        	 session('WST_STAFF.staffNo',$data["staffNo"]);
		        	 session('WST_STAFF.staffPhoto',$data["staffPhoto"]);
		        }
				
			}
		}
		return $rd;
	 }

	 /**
	  * 获取指定对象
	  */
     public function get(){
		return $this->where("staffId=".(int)I('id'))->find();
	 }

    /**
     * 获取ID获取指定对象
     */
    public function getStaffDataFind($staffId){
        $result = M('user_token')->where(["staffId"=>$staffId])->field('createTime')->order('createTime desc')->limit(1,1)->select();
        $time = $result[0]['createTime'];
        if (!empty($time)){
            return  date('Y-m-d H:i:s',$time);
        }
        return false;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return array
     * 分页列表
     */
     public function queryByPage($page=1,$pageSize=15){
	 	$sql = "select * from __PREFIX__staffs where staffFlag=1 ";
	 	if(I('loginName')!='')$sql.=" and loginName LIKE '%".WSTAddslashes(I('loginName'))."%'";
	 	if(I('staffName')!='')$sql.=" and staffName LIKE '%".WSTAddslashes(I('staffName'))."%'";
	 	$sql .=" order by staffId desc ";
	 	$res = $this->pageQuery($sql,$page,$pageSize);
	 	if(!empty($res['root'])){
	 	    $rolesServiceModule = new RolesServiceModule();
	 	    foreach ($res['root'] as $k=>$v){
                $getRolesList = $rolesServiceModule->getRolesListByRoleIds($v['staffRoleId']);
                $res['root'][$k]['roleName'] = (string)implode(',',array_get_column($getRolesList['data'],'roleName'));
            }
        }
		return $res;
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
	     $sql = "select * from __PREFIX__staffs order by staffId desc";
		 return $this->find($sql);
	  }
	  
	 /**
	  * 删除
	  */
	 public function del(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	if(I('id')==session('WST_STAFF.staffId'))return $rd;
	 	$data = array();
		$data["staffFlag"] = -1;
	 	$rs = $this->where("staffId=".(int)I('id'))->save($data);
	    if(false !== $rs){
			$rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		return $rd;
	 }
	 
     /**
	  * 查询登录关键字
	  */
	 public function checkLoginKey(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = (int)I('id');
	 	$key = I('clientid');
	 	if($key!=''  && I($key)=='')return $rd;
	 	$sql = " loginName ='%s' and staffFlag=1 ";
	 	if($id>0)$sql.=" and staffId!=".$id;
	 	$rs = $this->where($sql,array(I("loginName")))->count();
	    if($rs==0){
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
	    return $rd;
	 }
	 
	 /**
	  * 登录验证
	  */
	 public function login(){
	 	$staff = $this->where('loginName="'.WSTAddslashes(I('loginName')).'" and staffFlag=1 and staffStatus=1')->find();
	 	if($staff['loginPwd']==md5(I('loginPwd').$staff['secretKey'])){
	 		//获取角色权限
	 		$r = M('roles');
	 		$rrs = $r->where('roleFlag =1 and roleId='.$staff['staffRoleId'])->find();
	 		$staff['roleName'] = $rrs['roleName'];
	 		$staff['grant'] = explode(',',$rrs['grant']);
	 		$returnData = [];
            $returnData['staff'] = $staff;
	 		$this->lastTime = date('Y-m-d H:i:s');
	 		$this->lastIP = get_client_ip();
	 		$this->where(' staffId='.$staff['staffId'])->save();
	 		//记录登录日志
		 	$data = array();
			$data["staffId"] = $staff['staffId'];
			$data["loginTime"] = date('Y-m-d H:i:s');
			$data["loginIp"] = get_client_ip();
			$m = M('log_staff_logins');
			$m->add($data);

            $token = md5(uniqid('',true).$staff['loginPwd'].$staff['secretKey'].(string)microtime());
            $staff_data = $this->where('staffId='.$staff['staffId'])->find();
            $result = userTokenAdd($token,$staff_data);
            if ($result){
                $returnData['token'] = $token;
                //获取权限信息
                $params = [];
                $params['roleId'] = $staff_data['staffRoleId'];//角色ID
                $params['module_type'] = 1;//所属模块【1运营后台、2商家后台】
                $params['loginName'] = $staff['loginName'];//用于判断是否是总管理员账号
                $returnData['rolePrivilege'] = getUserPrivilege($params);
            }
            return returnData($returnData);
	 	}
	 	return returnData(false, -1, 'error', '请确定账号密码是否正确');
	 }
	 /**
	  * 显示否显示/隐藏
	  */
	 public function editStatus(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	if(I('id',0)==0)return $rd;
	 	$this->staffStatus = (I('staffStatus')==1)?1:0;
	 	$rs = $this->where("staffId=".(int)I('id',0))->save();
	    if(false !== $rs){
			$rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
	 	return $rd;
	 }
	/**
	 * 修改职员密码
	 */
	public function editPass($id = ''){
		$id = ($id == '') ? I('staffId') : $id;
		$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$data = array();
//		$oldPass = I("oldPass");
		$newPass = I('password');
		$reNewPass = I('confirmPassword');
        $data['loginPwd'] = $newPass;
        if ($this->checkEmpty($data,true)) {
            if($newPass != $reNewPass){
                $rd['msg'] = "两次密码输入的不一致";
                return $rd;
            }
            $where = [];
            $where['staffFlag'] = 1;
            $where['staffId'] = $id;
            $rs = $this->where($where)->find();
            if(!$rs){
                $rd['msg'] = "该用户已被删除";
                return $rd;
            }
//            if(md5($oldPass.$rs['secretKey']) != $rs['loginPwd']){
//                $rd['msg'] = "原密码不正确";
//                return $rd;
//            }
            if(md5($newPass.$rs['secretKey']) == $rs['loginPwd']){
                $rd['msg'] = "原密码和新密码不能一样";
                return $rd;
            }
            $data["loginPwd"] = md5($newPass.$rs['secretKey']);
            $rs = $this->where(['staffId'=>$id])->save($data);

            //$token = I('token');
            //删除登录token
            //$res = M('user_token')->where(['token'=>$token])->delete();
            if ($rs) {
                $rd['code']= 0;
                $rd['msg'] = '操作成功';
            }
        }
		return $rd;
	}
};
?>