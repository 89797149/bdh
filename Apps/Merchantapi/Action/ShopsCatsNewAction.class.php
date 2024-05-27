<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 新店铺分类功能类(主要用于洗衣服功能)
 */
class ShopsCatsNewAction extends BaseAction {

    /**
     * (洗衣服)分类列表
     */
    public function catList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval'),
            'name'      =>  I('name', '', 'trim')
        );

        $m = D('Merchantapi/ShopsCatsNew');
        $list = $m->getCatList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }

    /**
     * 新增/编辑 分类
     */
    public function editCat(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $id = I('id', 0, 'intval');

        $name = I('name','','trim');
        if (empty($name)) $this->ajaxReturn(array('code'=>2, 'msg'=>'名称不能为空'));

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $shopId;

        $m = D('Merchantapi/ShopsCatsNew');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editCat(array('id'=>$id,'shopId'=>$shopId),$data);
        } else {//新增
            $data['scnFlag'] = 1;
            $result = $m->addCat($data);
        }

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }

    /**
     * 删除分类
     */
    public function deleteCat(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        if (empty($shopId) || empty($id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/ShopsCatsNew');
        $result = $m->deleteCat(array('id'=>$id,'shopId'=>$shopId),array('scnFlag'=>-1));

        $this->ajaxReturn(array('code'=>$result?0:-1));
    }

    /**
     * 获取店铺一级分类
     */
    public function oneCatList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId'    =>  $shopId,
            'parentId'  => 0,
            'catFlag'   => 1
        );

        $m = D('Merchantapi/ShopsCatsNew');
        $list = $m->getOneCatList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }
}