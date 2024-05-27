<?php
 namespace Merchantapi\Model;
use App\Enum\ExceptionCodeEnum;
use App\Modules\FlashSale\FlashSaleServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 限时购
 */
class FlashSaleModel extends BaseModel {
    /**
     * 获取限时时间列表
     */
    public function getFlashSaleList($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $field = "id,startTime,endTime,state";
        $flashSaleServiceModule = new FlashSaleServiceModule();
        $data = $flashSaleServiceModule->getFlashSaleListById(['shopId'=>$params['shopId']],$field);
        $rs = arrayPage($data['data'],$page,$pageSize);
        return $rs;
    }

    /**
	  * 新增限时时间段
	  */
	 public function insert($request){
		if(!empty($request)){
			$rs = M('flash_sale')->add($request);
			if(empty($rs)){
			    return returnData(false,-1,'error',"操作失败");
			}
		}
		return returnData(true);
	 } 
     /**
	  * 修改限时时间信息
	  */
	 public function edit($request){
        $flashSaleServiceModule = new FlashSaleServiceModule();
        $rest = $flashSaleServiceModule->editFlashSaleInfo($request);
        //sb
//        $rs = $rest['data'];
//        if(empty($rs)){
//            return returnData(false,-1,'error',"操作失败");
//        }
         if($rest['code'] != ExceptionCodeEnum::SUCCESS){
             return returnData(false,ExceptionCodeEnum::FAIL,'error',"操作失败");
         }
        return returnData(true);
	 }

	 /**
	  * 获取限时时间详情
	  */
     public function getFlashSaleDetail($params)
     {
         $flashSaleServiceModule = new FlashSaleServiceModule();
         $data = $flashSaleServiceModule->getFlashSaleDetailByParam($params);
         return $data;
	 }

    /**
     * 获取指定对象
     */
    public function getList($where){
        return $this->where($where)->select();
    }
	  
	 /**
	  * 删除限时时间信息
	  */
	 public function delFlashSaleInfo($request){
         $flashSaleServiceModule = new FlashSaleServiceModule();
         $data = $flashSaleServiceModule->getFlashSaleDetailByParam($request);
         if(empty($data['data'])){
             return returnData(false,-1,'error',"暂无相关数据");
         }
         $param = [];
         $param['fs.id'] = $request['id'];
         $param['fs.shopId'] = $request['shopId'];
         $goodsList = $flashSaleServiceModule->getFlashSaleGoods($param);
         $goodsCount = count($goodsList['data']);
         if($goodsCount > 0){
             return returnData(false,-1,'error',"当前时间段下存在{$goodsCount}个商品,请取消后再删除");
         }
         $request['isDelete'] = 1;
         $rest = $flashSaleServiceModule->editFlashSaleInfo($request);
         $rs = $rest['data'];
         if(empty($rs)){
             return returnData(false,-1,'error',"操作失败");
         }
         return returnData(true);
	 }
}