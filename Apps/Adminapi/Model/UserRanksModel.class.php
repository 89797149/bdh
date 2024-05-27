<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 会员服务类
 */
class UserRanksModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = I("id",0);
		$data = array();
		$data["rankName"] = I("rankName");
		/*$data["startScore"] = I("startScore");
		$data["endScore"] = I("endScore");
		$data["rebate"] = I("rebate");*/
		$data["createTime"] = date('Y-m-d H:i:s');
		if($this->checkEmpty($data)){
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
		$data['rankName'] = I("rankName");
		/*$data['startScore'] = I("startScore");
		$data['endScore'] = I("endScore");
		$data['rebate'] = I("rebate");*/
		if($this->checkEmpty($data)){
			$rs = $this->where("rankId=".(int)I('id'))->save($data);
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
		return $this->where("rankId=".(int)I('id'))->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
	 	$sql = "select * from __PREFIX__user_ranks order by rankId desc";
		$rs = $this->pageQuery($sql,$page,$pageSize);
		return $rs;
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
//	     $sql = "select * from __PREFIX__user_ranks order by rankId desc";
//		 $rs = $this->find($sql);
          return M('user_ranks')->order('rankId desc')->select();
	  }
	  
	 /**
	  * 删除
	  */
	 public function del(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	    $rs = $this->delete((int)I('id'));
		if($rs){
			$rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		return $rd;
	 }
};
?>