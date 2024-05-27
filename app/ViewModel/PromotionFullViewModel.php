<?php
namespace App\ViewModel;
/**
 * 促销单、买满数量后特价 视图模型
 */
class PromotionFullViewModel extends BaseModel
{
    public $viewFields = array(     
        'promotion'=>array('promotion_id','schedule_id'),//促销单表 
        'promotion_full'=>array('_on'=>'promotion.promotion_id=promotion_full.promotion_id'),//买满数量后特价
           );
}