<?php
namespace App\ViewModel;
/**
 * 促销单、DM档期计划、DM商品特价 视图模型
 */
class PromotionViewModel extends BaseModel
{
    public $viewFields = array(     
        'promotion'=>array('promotion_id','schedule_id'),//促销单表 
        'promotion_schedule'=>array('_on'=>'promotion.schedule_id=promotion_schedule.schedule_id'),//档期计划
        'promotion_special_dm'=>array('promotion_id'=>'promotion_special_dm.promotion_id','_on'=>'promotion.promotion_id=promotion_special_dm.promotion_id'),//DM商品特价表
       );
}