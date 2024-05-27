<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品分类控制器
 */
class GoodsCatsTestAction extends BaseAction{

    /**
     * 分类详情
     */
    public function detail(){
        $this->isLogin();
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if ($id <= 0) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/GoodsCats');
        $this->checkPrivelege('spfl_02');
        $detail = $m->get($id);

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
		$m = D('Adminapi/GoodsCats');
    	$rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
    	if(I('id',0)>0){
    		$this->checkPrivelege('spfl_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('spfl_01');
    		$rs = $m->insert();
    	}
		
    	$this->ajaxReturn($rs);
	}
	/**
	 * 修改名称
	 */
	public function editName(){
		$this->isLogin();
		$m = D('Adminapi/GoodsCats');
    	$rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
    	if(I('id',0)>0){
    		$this->checkPrivelege('spfl_02');
    		$rs = $m->editName();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('spfl_03');
		$m = D('Adminapi/GoodsCats');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('spfl_00');
		$m = D('Adminapi/GoodsCats');
    	$list = $m->getCatAndChild();
        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
	}

    /**
     * 获取树形分类
     */
    public function getCatslist(){
        $this->isLogin();
        $this->checkPrivelege('spfl_00');
        $m = D('Adminapi/GoodsCats');
        $list = $m->getCatAndChild();
        $rs = array();
        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $list;
        $this->ajaxReturn($rs);

    }

	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/GoodsCats');
		$list = $m->queryByList(I('id'));
		$rs = array();
		$rs['code'] = 0;
        $rs['msg'] = '操作成功';
		$rs['data'] = $list;
		$this->ajaxReturn($rs);
	}
    /**
	 * 修改商品分类为显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('spfl_02');
	 	$m = D('Adminapi/GoodsCats');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }
};
?>