<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 限时购
 */
class FlashSaleAction extends BaseAction{
    /**
     * 分页查询
     */
    public function index(){
        $this->isLogin();
        $this->checkPrivelege('xsg_00');
        $m = D('Adminapi/FlashSale');

        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');

        $list = $m->queryByPage($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
    }

	/**
	 * 限时购详情
	 */
	public function detail(){
		$this->isLogin();
        $this->checkPrivelege('xsg_02');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());

	    $id = I('id',0);
        if ($id <= 0) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }

        $m = D('Adminapi/FlashSale');
        $detail = $m->getInfo($id);

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;

        $this->ajaxReturn($rs);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/FlashSale');
		$param = I();
		isset($param['id'])?$request['id']=$param['id']:false;
		isset($param['startTime'])?$request['startTime']=$param['startTime']:false;
		isset($param['endTime'])?$request['endTime']=$param['endTime']:false;
		isset($param['state'])?$request['state']=$param['state']:false;
    	if($request['id'] > 0){
    		$this->checkPrivelege('xsg_02');
    		$rs = $m->edit($request);
    	}else{
    		$this->checkPrivelege('xsg_01');
    		//判断是否有重复时间段
    		$where['startTime'] = $request['startTime'];
    		$where['endTime'] = $request['endTime'];
    		$where['isDelete'] = 0;
    		$result = M('flash_sale')->where($where)->count();
    		if ($result>0){
                $rs = array('code'=>-1,'msg'=>'限时时间重复','data'=>array());
                $this->ajaxReturn($rs);
            }
            $request['addTime'] = date('Y-m-d H:i:s',time());
    		$rs = $m->insert($request);
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('xsg_03');
		$m = D('Adminapi/flashSale');
		$param = I();
        isset($param['id'])?$request['id']=$param['id']:false;
    	$rs = $m->del($request);
    	$this->ajaxReturn($rs);
	}

}
?>