<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品属性值添加
 */
class GoodsAttributesAction extends BaseAction{
    /**
     *商品属性值列表
     */
    public function getGoodsAttributesList(){
        $m = D('Home/GoodsAttributes');
        $parameter = I();
        $data = [];
        !empty($parameter['goodsId'])?$data['goodsId']=$parameter['goodsId']:0;
        $res = $m->getGoodsAttributesList($data);
        $this->ajaxReturn($res);
    }

    /**
     * 添加或编辑商品屬性
     */
    public function goodsAttributesSave(){
        $parameter = I();
        $m = D('Home/GoodsAttributes');
        $data = array();
        !empty($parameter['id'])?$data['id']=$parameter['id']:false;
        !empty($parameter['attrId'])?$data['attrId']=$parameter['attrId']:false;
        !empty($parameter['goodsId'])?$data['goodsId']=$parameter['goodsId']:false;
        !empty($parameter['attrVal'])?$data['attrVal']=$parameter['attrVal']:false;
        !empty($parameter['attrPrice'])?$data['attrPrice']=$parameter['attrPrice']:0;
        !empty($parameter['attrStock'])?$data['attrStock']=$parameter['attrStock']:$data['attrStock']=0;
        !empty($parameter['isRecomm'])?$data['isRecomm']=$parameter['isRecomm']:0;
        if(isset($data['id']) && !empty($data['id'])){
            //编辑
            $res = $m->goodsAttributesEdit($data);
        }else{
            //添加
            $res = $m->goodsAttributesAdd($data);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 删除商品屬性值
     */
    public function goodsAttributesDel(){
        $parameter = I();
        $data = array();
        !empty($parameter['id'])?$data['id']=$parameter['id']:false;
        $m = D('Home/GoodsAttributes');
        $res = $m->goodsAttributesDel($data);
        $this->ajaxReturn($res);
    }

};
?>