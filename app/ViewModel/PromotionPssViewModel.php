<?php
namespace App\ViewModel;
/**
 * 促销单、单品特价 视图模型
 */
class PromotionPssViewModel extends BaseModel
{
    public $viewFields = array(     
        'promotion'=>array('promotion_id','schedule_id'),//促销单表 
        'promotion_special_single'=>array('_on'=>'promotion.promotion_id=promotion_special_single.promotion_id'),//单商品特价
       );
}

