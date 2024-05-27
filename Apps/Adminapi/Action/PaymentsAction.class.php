<?php
 namespace Adminapi\Action;
 use Adminapi\Model\PaymentsModel;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 银行控制器
 */
class PaymentsAction extends BaseAction{
	/**
	 * 查看详情
	 */
	public function detail(){
		$this->isLogin();
		$id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
        $m = new PaymentsModel();
    	$detail = $m->get();

        $this->returnResponse(0,'操作成功',$detail);
	}

	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Payments');
    	$rs = array();
    	if(I('id',0)>0){
    	    $this->check(I('id',0));
    	}else{
    		$rs = $m->add();
    	}
    	$this->ajaxReturn($rs);
	}

    /**
     * 检测余额和货到付款是否冲突
     */
    public function check($id){
        $payment = M('payments')->where("id=".$id)->field('payCode,enabled')->find();
        switch ($payment['payCode']){
            case 'cod'://货到付款
                $codWhere['payCode'] = 'balance';
                $payCheck = M('payments')->where($codWhere)->field('enabled')->find();
                    if ($payCheck['enabled'] == 1) {
                        $this->returnResponse(-1, '货到付款余额支付不可同时开启');
                    }
                break;
            case 'balance'://余额支付
                $balanceWhere['payCode'] = 'cod';
                $payCheck = M('payments')->where($balanceWhere)->field('enabled')->find();
                if ($payCheck['enabled'] == 1){
                    $this->returnResponse(-1,'余额支付货到付款不可同时开启');
                }
                break;
        }
        $m = new PaymentsModel();
        $rs = $m->edit();
        $this->ajaxReturn($rs);
    }

	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$m = D('Adminapi/Payments');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
   
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/Payments');
    	$list = $m->queryByPage($page,$pageSize);
        $this->returnResponse(0,'操作成功',$list);
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/Payments');
		$list = $m->queryByList();
        $this->returnResponse(0,'操作成功',$list);
	}

    /**
     * 变更支付状态
     */
	public function editPayStatus(){
        $this->isLogin();
        $id = (int)I('id',0);
        $enabled = (int)I('enabled');//是否启用[0:不启用 1:启用]
        if(empty($id)){
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择开启的支付方式'));
        }
        if(!in_array($enabled,[0,1])){
            $this->ajaxReturn(returnData(null, -1, 'error', '参数有误'));
        }
        $param = [];
        $param['id'] = $id;
        $param['enabled'] = $enabled;
        $m = new PaymentsModel();
        $data = $m->editPayStatus($param);
        $this->ajaxReturn($data);
    }
}