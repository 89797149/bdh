<?php
 namespace Merchantapi\Action;
use App\Enum\ExceptionCodeEnum;
use App\Modules\FlashSale\FlashSaleServiceModule;
use Merchantapi\Model\FlashSaleModel;

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
     * 获取限时时间列表
     * https://www.yuque.com/anthony-6br1r/oq7p0p/whnis1
     */
    public function getFlashSaleList(){
        $shopId = $this->MemberVeri()['shopId'];
        $m = new  FlashSaleModel();

        $params = [];
        $params['page'] = I('page',1,'intval');
        $params['pageSize'] = I('pageSize',15,'intval');
        $params['shopId'] = (int)$shopId;

        $list = $m->getFlashSaleList($params);
        $this->ajaxReturn(returnData($list));
    }

	/**
	 * 获取限时时间详情
     * https://www.yuque.com/anthony-6br1r/oq7p0p/gngc51
	 */
	public function getFlashSaleDetail(){
        $shopId = $this->MemberVeri()['shopId'];
	    $id = I('id',0);
        if ($id <= 0) {
            $this->ajaxReturn(returnData(false,-1,'error',"请选择查看限时时间段"));
        }
        $params = [];
        $params['id'] = $id;
        $params['shopId'] = $shopId;
        $m = new  FlashSaleModel();
        $detail = $m->getFlashSaleDetail($params);
        $this->ajaxReturn($detail);
	}
	/**
	 * 新增|编辑限时时间
     * https://www.yuque.com/anthony-6br1r/oq7p0p/pv55b4
	 */
	public function editFlashSaleInfo(){
        $shopId = $this->MemberVeri()['shopId'];
		$m = new  FlashSaleModel();
        $id = (int)I('id',0);
        //判断是否有重复时间段
        $where = [];
        $where['startTime'] = I('startTime');
        $where['endTime'] = I('endTime');
        $where['isDelete'] = 0;
        $where['shopId'] = $shopId;
        $flashSaleServiceModule = new FlashSaleServiceModule();
        //sb
//        $data = $flashSaleServiceModule->getFlashSaleListById($where,'id');
//        $result = count($data['data']);
//        if ($result>0){
//            $rs = array('code'=>-1,'msg'=>'限时时间重复','data'=>array());
//            $this->ajaxReturn($rs);
//        }
        $flash_params = array(
            'shopId' => $shopId,
            'startTime' => I('startTime'),
            'endTime' => I('endTime'),
        );
        $flash_sale_result = $flashSaleServiceModule->getFlashSaleDetailByParam($flash_params);
        if(!empty($flash_sale_detail['data'])){
            $flash_sale_detail = $flash_sale_result['data'];
            if($flash_sale_detail['id'] != $id){
                $this->ajaxReturn(returnData(false,ExceptionCodeEnum::FAIL,'error','限时时间重复'));
            }
        }
		$param = [];
        $param['startTime'] = I('startTime');
        $param['endTime'] = I('endTime');
        $param['state'] = I('state');
        $param['shopId'] = $shopId;
    	if($id > 0){
            $param['id'] = $id;
    		$rs = $m->edit($param);
    	}else{
            $param['addTime'] = date('Y-m-d H:i:s',time());
    		$rs = $m->insert($param);
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除限时时间
     * https://www.yuque.com/anthony-6br1r/oq7p0p/mi90tz
	 */
	public function delFlashSaleInfo(){
        $shopId = $this->MemberVeri()['shopId'];
        $m = new  FlashSaleModel();
		$id = (int)I('id',0);
        if ($id <= 0) {
            $this->ajaxReturn(returnData(false,-1,'error',"请选择查看限时时间段"));
        }
		$param = [];
		$param['id'] = $id;
		$param['shopId'] = (int)$shopId;
    	$rs = $m->delFlashSaleInfo($param);
    	$this->ajaxReturn($rs);
	}
}
