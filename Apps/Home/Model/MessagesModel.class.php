<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商城信息服务类
 */
class MessagesModel extends BaseModel {
	 /**
	  * 删除
	  */
	 public function del(){
	 	$rd = array('status'=>-1);
	    $map = array('id'=>(int)I('id'),'receiveUserId'=>(int)session('WST_USER.userId'));
	    $rs = $this->where($map)->delete();
		if(false !== $rs){
		   $rd['status']= 1;
		}
		return $rd;
	 }
	/**
	 * 获取分页列表
	 */
	 public function queryByPage($parameter=array()){
	 	$userId=(int)session('WST_USER.userId');
         $userId = $userId?$userId:$parameter['userId'];
		$sql = "select * from __PREFIX__messages m where receiveUserId=".$userId;
		$sql.=" order by msgStatus asc,createTime desc ";
		return $this->pageQuery($sql,$parameter['page'],$parameter['pageSize']);
	 }
	
	/**
	 * 获取消息
	 */
	public function get($parameter=array()){
	    $userId = (int)session('WST_USER.userId');
        $userId = $userId?$userId:$parameter['userId'];
		$id = (int)I('id');
        $map = array('id'=>$id,'receiveUserId'=>$userId);
        $info = $this->where($map)->find();
        if (!empty($info)) {
            if ($info['msgStatus'] == 0) {
                $this->where("id=".$id." and receiveUserId=".$userId)->save(array('msgStatus'=>1));
            }
        }
        return $info;
	}

	public function batchDel($parameter=array()){
        $userId = (int)session('WST_USER.userId');
        $userId = $userId?$userId:$parameter['userId'];
		$ids = self::formatIn(",", I('id'));
		$re = array();
        $map = array('id'=>array('in',$ids),'receiveUserId'=>$userId);
        $re['status'] = $this->where($map)->delete() === false ? -1 : 1 ;
        return $re;
	}

    /**
     * 商城信息数量统计
     */
    public function getlistCount($shopInfo,$param){
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $userId=(int)session('WST_USER.userId');
        $userId = $userId?$userId:$shopInfo['userId'];
        $where = " and msgFlag=1 ";
        if($param['status'] != 20){
            $where .= " and msgStatus='".$param['status']."'";
        }
        $sql = "select count(id) from __PREFIX__messages where receiveUserId='".$userId."' ".$where;
        $data = $this->queryRow($sql)['count(id)'];
        $apiRet['apiData'] = $data;
        return $apiRet;
    }
};
?>