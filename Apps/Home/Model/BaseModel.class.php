<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 基础服务类
 */
use Think\Model;
class BaseModel extends Model {
    /**
     * 用来处理内容中为空的判断
     */
	public function checkEmpty($data,$isDie = false){
	    foreach ($data as $key=>$v){
			if(trim($v)==''){
				if($isDie)die("{status:-1,'key'=>'$key'}");
				return false;
			}
		}
		return true;
	}


  //会员身份验证 成功即返回用户信息
    public function MemberVeri(){
        $memberToken = I("token");


        if(empty($memberToken)){
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken,86400*30);//查询token

        if(empty($sessionData)){
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }

        //普通管理员权限检测
        if($sessionData['login_type'] == 2){
            if(!$this->checkUserJurisdiction($sessionData)){
                $this->returnResponse(-3,'无权访问');
            }
        }
        //END

        return $sessionData;
    }
	

	/**
	 * 输入sql调试信息
	 */
	public function logSql($m){
		echo $m->getLastSql();
	}


	/**
	 * 获取一行记录
	 */
	public function queryRow($sql){
		$plist = $this->query($sql);
		return $plist[0];
	}


	/**
	 * 格式化查询语句中传入的in 参与，防止sql注入
	 * @param unknown $split
	 * @param unknown $str
	 */
	public function formatIn($split,$str){
		if(is_array($str)){
			$strdatas = $str;
		}else{
			$strdatas = explode($split,$str);
		}

		$data = array();
		for($i=0;$i<count($strdatas);$i++){
			$data[] = (int)$strdatas[$i];
		}
		$data = array_unique($data);
		return implode($split,$data);
	}

	//访问权限检测
	public function checkUserJurisdiction ($parameter=array(),&$msg=''){


		$urlPath_arr = explode('/',$_SERVER['PATH_INFO']);

		//未加入到权限数据库里的路由不做任何处理 辉辉 2019.6.22
		$nm = M('node');
		$IsnodeList = $nm->where("status=1 and mname = '{$urlPath_arr[0]}' and aname = '{$urlPath_arr[1]}'  ")->count();
		if($IsnodeList <= 0){//如果不存在路由
			return true;
		}

		if(!$parameter['id'] || !$parameter['shopId'] || !$urlPath_arr[0] || !$urlPath_arr[1]){
			return false;
		}

		$nodeList = $this->getUserNodes($parameter,$msg);
		if(!$nodeList){
			return false;
		}
		foreach ($nodeList as $key => $value) {
			if($value['mname'] == $urlPath_arr[0] && $value['aname'] == $urlPath_arr[1]){
				return true;
			}
		}

		return false;
	}

	public function getUserNodes ($parameter=array(),&$msg=''){
		if(!$parameter['id'] || !$parameter['shopId']){
			return false;
		}
		//缓存
		$cache_arr =S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}");
		if($cache_arr && is_array($cache_arr)){
			return $cache_arr;
		}

		//数据库
		$m = M('user_role');
		//获取角色id
		$ruList = $m->where('uid='.(int)$parameter['id'].' and shopId='.$parameter['shopId'])->select();
		if(!$ruList){
			return false;
		}
		$rid_arr = array_get_column($ruList,'rid');
		$rids = implode(',',array_unique($rid_arr));
		if(!$rids){
			return false;
		}
		//角色检测
		$rm =  M('role');
		$roleList = $rm->where('status=1 and id in('.$rids.') and shopId='.$parameter['shopId'])->select();
		$check_ridArr = array_get_column($roleList,'id');
		$check_rids = implode(',',array_unique($check_ridArr));
		if(!$check_rids){
			return false;
		}
		//END

		//获取节点
		$rnm = M('role_node');
		$nrList = $rnm->where('rid in('.$check_rids.') and shopId='.$parameter['shopId'])->select();
		$nid_arr = array_get_column($nrList,'nid');
		$nids = implode(',',array_unique($nid_arr));//用户所有权限id
		if(!$nids){
			return false;
		}

		$nm = M('node');
		$nodeList = $nm->where("status=1 and id in({$nids})")->select();
		//存缓存
		if($nodeList && is_array($nodeList)){
			S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}",$nodeList,300);
		}
		return $nodeList;
	}



};
?>
