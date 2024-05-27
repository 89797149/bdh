<?php
namespace App\ViewModel;
/**
 * 促销单、类别折扣 视图模型
 */
class PromotionClassViewModel extends BaseModel
{
    public $viewFields = array(    
        'promotion'=>array('promotion_id','schedule_id'),//促销单表
        'promotion_class'=>array('_on'=>'promotion.promotion_id=promotion_class.promotion_id'),//类别折扣表
           );
}