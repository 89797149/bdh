<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 用户申请入驻
 */
class DeliveryTimeAction extends BaseAction{

    /**
     * 获取时间分类列表
     */
    public function getTypeList(){
        $this->isLogin();
		$data = (array)M("delivery_time_type")->order("sort asc")->select();
		foreach ($data as $k=>$v){
		    $data[$k]['number'] = (int)$v['number'];
        }
        $rs = returnData($data);
        $this->ajaxReturn($rs);
	}

    /**
     * 添加时间分类
     */
	public function addType(){
        $userData = $this->isLogin();

		$typeName = I("typeName");
		if(empty($typeName)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请添加分类名'));
        }
		$sort = (int)I("sort",0);
		$number = (int)I("number",0);

		$addData['typeName'] = $typeName;
		$addData['sort'] = $sort;
		$addData['number'] = $number;
		$addData['addTime'] = date("Y-m-d H:i:s");

		$data = M("delivery_time_type")->add($addData);
        if($data){
            $rs = returnData(true,0,'success','操作成功');
            $describe = "[{$userData['loginName']}]新增了配送时间分类:[{$typeName}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,1);
		}else{
            $rs = returnData(false, -1, 'error', '操作失败');
		}
		$this->ajaxReturn($rs);
	}

	//删除时间分类
	public function deleteType(){
        $userData = $this->isLogin();
        $id = (int)I("id",0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间分类'));
        }
		$where['id'] = $id;
        $deliveryInfo = M("delivery_time_type")->where($where)->find();
        if(empty($deliveryInfo)){
            $this->ajaxReturn(returnData(false, -1, 'error', '暂无相关数据'));
        }
		$data = M("delivery_time_type")->where($where)->delete();

        if($data){
            $rs = returnData(true,0,'success','操作成功');
            $describe = "[{$userData['loginName']}]删除了配送时间分类:[{$deliveryInfo['typeName']}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,2);
		}else{
            $rs = returnData(false, -1, 'error', '操作失败');
		}
		$this->ajaxReturn($rs);
	}

    /**
     * 更新时间分类
     */
	public function updateType(){
        $userData = $this->isLogin();
        $id = (int)I("id",0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间分类'));
        }
		$where['id'] = $id;
        $deliveryInfo = M("delivery_time_type")->where($where)->find();
        if(empty($deliveryInfo)){
            $this->ajaxReturn(returnData(false, -1, 'error', '暂无相关数据'));
        }
		$saveData['sort'] = I('sort',null);
		$saveData['number'] = I('number');
		$saveData['typeName'] = I('typeName',null);

		array_filter($saveData, function (&$v, $k) {
			if ($v === null or $v === '') {
				return false;
			}
			return true;

		}, ARRAY_FILTER_USE_BOTH);

		$data = M("delivery_time_type")->where($where)->save($saveData);
        if($data){
            $rs = returnData(true,0,'success','操作成功');
            $describe = "[{$userData['loginName']}]编辑了配送时间分类:[{$deliveryInfo['typeName']}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,3);
		}else{
            $rs = returnData(false, -1, 'error', '操作失败，请确认是否有变动');
		}
		$this->ajaxReturn($rs);
    }

    /**
     * 获取时间点列表
     */
	public function getDeliveryTimeList(){
        $this->isLogin();
		$where['deliveryTimeTypeId'] = I("id");
		$data = (array)M("delivery_time")->order("sort asc")->where($where)->select();
        $rs = returnData($data);
        $this->ajaxReturn($rs);
	}

    /**
     * 添加时间点
     */
	public function addDeliveryTime(){
        $userData = $this->isLogin();

		$timeStart = I("timeStart");
		$timeEnd = I("timeEnd");
        if(empty($timeStart)||empty($timeEnd)){
            $rs = returnData(false, -1, 'error', '请添加开始时间点或结束时间点');
            $this->ajaxReturn($rs);
        }
		$sort = I("sort");
		$deliveryTimeTypeId = I("deliveryTimeTypeId");

		$addData["timeStart"] = $timeStart;
		$addData["timeEnd"] = $timeEnd;
		$addData["sort"] = $sort;
		$addData["deliveryTimeTypeId"] = $deliveryTimeTypeId;
		$addData["addTime"] = date("Y-m-d H:i:s");


		$data = M("delivery_time")->add($addData);
        if($data){
            $rs = returnData(true,0,'success','操作成功');
            $describe = "[{$userData['loginName']}]新增了配送时间:[开始{$addData["timeStart"]},结束{$addData["timeEnd"]}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,1);
		}else{
            $rs = returnData(false, -1, 'error', '操作失败');
		}
		$this->ajaxReturn($rs);
	}

    /**
     * 删除时间点
     */
	public function delDeliveryTime(){
        $userData = $this->isLogin();
        $id = (int)I("id",0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间'));
        }
		$where['id'] = I("id");

        $deliveryInfo = M("delivery_time")->where($where)->find();
        if(empty($deliveryInfo)){
            $this->ajaxReturn(returnData(false, -1, 'error', '暂无相关数据'));
        }

		$data = M("delivery_time")->where($where)->delete();
        if($data){
            $rs = returnData(true,0,'success','操作成功');
            $describe = "[{$userData['loginName']}]删除了配送时间:[开始{$deliveryInfo["timeStart"]},结束{$deliveryInfo["timeEnd"]}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,2);
		}else{
            $rs = returnData(false, -1, 'error', '操作失败');
		}
		$this->ajaxReturn($rs);
	}

    /**
     * 更新时间点
     */
	public function updateDeliveryTime(){
        $userData = $this->isLogin();
        $id = (int)I("id",0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间'));
        }

		$where['id'] = I("id");
        $deliveryInfo = M("delivery_time")->where($where)->find();
        if(empty($deliveryInfo)){
            $this->ajaxReturn(returnData(false, -1, 'error', '暂无相关数据'));
        }

		$saveData["timeStart"] = I("timeStart",null);
		$saveData["timeEnd"] = I("timeEnd",null);
		$saveData["sort"] = I("sort",null);
		$saveData["deliveryTimeTypeId"] = I("deliveryTimeTypeId",null);
		array_filter($saveData, function (&$v, $k) {
			if ($v === null or $v === '') {
				return false;
			}
			return true;

		}, ARRAY_FILTER_USE_BOTH);

		$data = M("delivery_time")->where($where)->save($saveData);
        if($data){
            $rs = returnData(true,0,'success','操作成功');
            $describe = "[{$userData['loginName']}]编辑了配送时间:[开始{$deliveryInfo["timeStart"]},结束{$deliveryInfo["timeEnd"]}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,3);
		}else{
            $rs = returnData(false, -1, 'error', '操作失败，请确认是否有变动');
		}
		$this->ajaxReturn($rs);
	}
}