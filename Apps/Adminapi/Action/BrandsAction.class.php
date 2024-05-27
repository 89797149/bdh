<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 品牌控制器
 */
class BrandsAction extends BaseAction{

    /**
     * 品牌详情
     */
    public function detail(){
        $this->isLogin();
//        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if ($id <= 0) {
//            $rs['msg'] = '参数不全';
//            $this->ajaxReturn($rs);
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $m = D('Adminapi/Brands');
        $this->checkPrivelege('ppgl_02');
        $detail = $m->get();

//        $rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//        $rs['data'] = $detail;
//
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($detail));
    }

	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Brands');
//    	$rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
    	if(I('id',0)>0){
    		$this->checkPrivelege('ppgl_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('ppgl_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('ppgl_03');
		$m = D('Adminapi/Brands');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	/*public function index(){
		$this->isLogin();
		$this->checkPrivelege('ppgl_00');
		
		$m = D('Adminapi/GoodsCats');
		$cats = $m->queryByList(0);
		$this->assign('cats',$cats);
		self::WSTAssigns();
		$m = D('Adminapi/Brands');
    	$page = $m->queryByPage();
    	foreach ($page['root'] as &$value) {
    		$value['brandDesc'] = html_entity_decode(stripslashes($value['brandDesc']));
    	}
    	$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
        $this->view->display("/brands/list");
	}*/

    /**
     * 分页查询
     */
    public function index(){
        $this->isLogin();
        $this->checkPrivelege('ppgl_00');

        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');

        $m = D('Adminapi/Brands');
        $list = $m->queryByPage($page,$pageSize);
        foreach ($list['root'] as &$value) {
            $value['brandDesc'] = html_entity_decode(stripslashes($value['brandDesc']));
        }

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
    }

	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/Brands');
		$list = $m->queryByList();
//		$rs = array();
//		$rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//		$rs['data'] = $list;
        $rs = returnData($list);
		$this->ajaxReturn($rs);
	}
};
?>