<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 活动控制器
 */
class ActivityAction extends BaseAction{
	/**
	 * 获取活动列表
	 */
    public function queryByList(){
		$shopInfo = $this->MemberVeri();
		$m = D('Merchantapi/Activity');
		$list = $m->getList($shopInfo);
		$data = returnData($list);
		$this->ajaxReturn($data);
	}

	//新增活动
	public function addActivity(){
		$shopInfo = $this->MemberVeri();
		$img = I('img');
		$activityId = I('activityId');
		$title = I('title');
		if(empty($img) && empty($title) && empty($activityId)){
			$retdata = returnData($data,-1,'error','有字段不允许为空','数据错误');
			$this->ajaxReturn($retdata);
		}
		
		$post['img'] = $img;
		$post['activityId'] =  $activityId;
		$post['title'] =  $title;
		$post['shopId'] =  $shopInfo['shopId'];

		$m = D('Merchantapi/Activity');
		$data = $m->addData($post);
		
		if($data){
			$retdata = returnData(null);
		}else {
			$retdata = returnData($data,-1,'error','失败','数据错误');
		}
		
		$this->ajaxReturn($retdata);

	}

	//修改活动
	public function editActivity(){
		$shopInfo = $this->MemberVeri();
		
		$id = I('id');
		if(empty($id)){
			$retdata = returnData(null,-1,'error','失败','数据错误');
			$this->ajaxReturn($retdata);
		}
		$post['id'] = $id;
        $post['img'] = I('img');
		$post['activityId'] =  I('activityId');
		$post['title'] =  I('title');
		$post['shopId'] =  $shopInfo['shopId'];
		$m = D('Merchantapi/Activity');
		$data = $m->edit($post);

		
		if($data){
			$retdata = returnData(null);
		}else {
			$retdata = returnData(null,-1,'error','修改失败','数据错误');
		}
		
		$this->ajaxReturn($retdata);


	}

	//删除活动
	public function deleteActivity(){
		$shopInfo = $this->MemberVeri();
		$id = I('id');
	
		if(empty($id)){
			$retdata = returnData(null,-1,'error','修改失败','数据错误');
			$this->ajaxReturn($retdata);
		}
		
		$post['id'] = $id;
		$post['shopId'] =  $shopInfo['shopId'];

		$m = D('Merchantapi/Activity');
		$data = $m->delData($post);
		// var_dump($data);
		$rs['status'] = -1;
		if($data){
			$retdata = returnData(null);
		}else {
			$retdata = returnData(null,-1,'error','修改失败','');
		}
		
		$this->ajaxReturn($retdata);

	}

	//获取活动详情
	public function getActivityDetail(){
		$shopInfo = $this->MemberVeri();
		$id = I('id');
	
		if(empty($id)){
			$rs['status'] = -1;
			$this->ajaxReturn($rs);
		}
		
		$post['id'] = $id;
		$post['shopId'] =  $shopInfo['shopId'];

		$m = D('Merchantapi/Activity');
		$data = $m->getActivityDetail($post);
		if($data){
			$retdata = returnData($data);
		}else {
			$retdata = returnData($data,-1,'error','修改失败','数据错误');
		}
		$this->ajaxReturn($retdata);

	}

	//活动页内容-修改
	public function editActivityPageType(){
		$shopInfo = $this->MemberVeri();
		$post['goods'] = I('goods');
		$post['img'] = I('img');
		$post['sort'] = I('sort');
		$post['direction'] = I('direction');

		$post['id'] = I('id');

		$m = D('Merchantapi/Activity');
		$data = $m->editActivityPageType($post);
		if($data){
			$retdata = returnData(null);
		}else {
			$retdata = returnData($data,-1,'error','修改失败','数据错误');
		}
		
		$this->ajaxReturn($retdata);
	}

	//活动页内容-删除
	public function deleteActivityPageType(){
		$shopInfo = $this->MemberVeri();
		$post['id'] = I('id');
		$m = D('Merchantapi/Activity');
		$data = $m->deleteActivityPageType($post);
		if($data){
			$retdata = returnData(null);
		}else {
			$retdata = returnData($data,-1,'error','删除','数据错误');
		}
		
		$this->ajaxReturn($retdata);
	}

	//活动页内容-列表
	public function getActivityPageType(){
		$shopInfo = $this->MemberVeri();
		$post['activityPageId'] = I('activityPageId');
		$m = D('Merchantapi/Activity');
		$data = $m->getActivityPageType($post);
//		if($data){
//			$retdata = returnData($data);
//		}else {
//			$retdata = returnData($data,-1,'error','获取失败','数据错误');
//		}
		
		
		$this->ajaxReturn(returnData($data));
	}

	//活动页内容-详情 包含商品
	public function getActivityPageTypeDetail(){
		$shopInfo = $this->MemberVeri();

		$post['id'] = I('id');
		$m = D('Merchantapi/Activity');
		$data = $m->getActivityPageTypeDetail($post);
		if($data){
			$retdata = returnData($data);
		}else {
			$retdata = returnData($data,-1,'error','获取失败','数据错误');
		}
		
		$this->ajaxReturn($retdata);
	}


	//活动页内容-新增
	public function addActivityPage(){
			$shopInfo = $this->MemberVeri();
			$post['activityPageId'] = I('activityPageId');
			$post['img'] = I('img');
			$post['goods'] = I('goods');
			$post['sort'] = I('sort');
			$post['direction'] = I('direction');
			$m = D('Merchantapi/Activity');
			$data = $m->addActivityPage($post);
			if($data){
				$retdata = returnData($data);
			}else {
				$retdata = returnData($data,-1,'error','新增失败','数据错误');
			}
			
			$this->ajaxReturn($retdata);
		}

	
  
};
?>