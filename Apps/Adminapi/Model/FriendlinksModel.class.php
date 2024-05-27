<?php
namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 社区服务类
 */
class FriendlinksModel extends BaseModel {
    /**
     * 获取分页记录
     */
	public function queryPage($page=1,$pageSize=15){
		$sql = "select * from __PREFIX__friendlinks order by friendlinkSort asc,friendlinkId asc";
		$rs = $this->pageQuery($sql,$page,$pageSize);
		return $rs;
	}
	/**
	 * 新增
	 */
	public function insert(){
		$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$data = array();
		$data['friendlinkIco'] = I('friendlinkIco');
		$data['friendlinkName'] = I('friendlinkName');
		$data['friendlinkUrl'] = I('friendlinkUrl');
		$data['friendlinkSort'] = I('friendlinkSort',0);
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
	 * 编辑
	 */
    public function edit(){
    	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$data = array();
		$data["friendlinkIco"] = I('friendlinkIco');
		$data["friendlinkName"] = I('friendlinkName');
		$data["friendlinkUrl"] = I('friendlinkUrl');
		$data["friendlinkSort"] = (int)I('friendlinkSort',0);
		if($this->checkEmpty($data)){
			$rs = $this->where("friendlinkId=".(int)I('id'))->save($data);
			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	}
	/**
	 * 获取
	 */
	public function get(){
		return $this->where("friendlinkId=".(int)I('id'))->find();
	}
	/**
	 * 删除
	 */
	public function del(){
		$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$rs = $this->delete((int)I('id'));
		if(false !== $rs){
			$rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		return $rd;
	}
}