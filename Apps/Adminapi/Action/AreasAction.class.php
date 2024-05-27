<?php
 namespace Adminapi\Action;
 use Adminapi\Model\AreasModel;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 地区控制器
 */
class AreasAction extends BaseAction{
/**
	 * 跳到新增/编辑页面
	 */
	public function detail(){
		$this->isLogin();
        $this->checkPrivelege('dqlb_02');
        //$rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
//            $rs['msg'] = '参数不全';
//            $this->ajaxReturn($rs);
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
	    $m = D('Adminapi/Areas');
        $detail = $m->get();

//        $rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//        $rs['data'] = $detail;
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($detail));
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Areas');
		if(is_numeric(I('areaName'))){
            $this->ajaxReturn(returnData(false, -1, 'error', "地区名称不能是数字"));
        }
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('dqlb_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('dqlb_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('dqlb_03');
		$m = D('Adminapi/Areas');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('dqlb_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/Areas');
		$pArea = array('areaId'=>0,'areaName'=>'');
		if(I('parentId',0)>0){
			$pArea = $m->get(I('parentId',0));
		}
    	$list = $m->queryByPage($page,$pageSize);

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list,'pArea'=>$pArea);
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/Areas');
		$list = $m->queryByList(I('parentId'));
//		$rs = array();
//		$rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//		$rs['data'] = $list;
//		$this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
	}
	/**
	 * 列表查询[获取启用的区域信息]
	 */
    public function queryShowByList(){
    	$this->isLogin();
		$m = D('Adminapi/Areas');
		$list = $m->queryShowByList(I('parentId'));
//		$rs = array();
//		$rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//		$rs['data'] = $list;
//		$this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
	}

    /**
	 * 列表查询[带社区]
	 */
    public function queryAreaAndCommunitysByList(){
    	$this->isLogin();
		$m = D('Adminapi/Areas');
		$list = $m->queryAreaAndCommunitysByList(I('areaId'));
        $this->ajaxReturn(returnData($list));
	}
    /**
	 * 设置地区是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('dqlb_02');
	 	$m = D('Adminapi/Areas');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }

    /**
     * 获取区域列表【树形】
     */
    public function getAreasList()
    {
        $this->isLogin();
        $m = new AreasModel();
        $rs = $m->getAreasList();
        $this->ajaxReturn(returnData($rs));
    }
}