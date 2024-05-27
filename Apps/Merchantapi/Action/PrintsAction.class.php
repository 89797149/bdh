<?php
 namespace Merchantapi\Action;
 use Merchantapi\Model\PrintsModel;

 /**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 小票机控制器
 */
class PrintsAction extends BaseAction{

    /**
     * 新增小票机
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dr63ga
     */
    public function addPrints(){
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        $requestParams['shopId'] = $shopId;
        $m = new PrintsModel();
        $data = $m->addPrints($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 修改小票机
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ar2evt
     */
    public function updatePrints(){
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        if(empty($requestParams['printId'])){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $requestParams['shopId'] = $shopId;
        $m = new PrintsModel();
        $data = $m->updatePrints($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 获取小票机详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lv0idz
     */
    public function getPrintsInfo(){
        $printId = (int)I('printId');
        if(empty($printId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $params = [];
        $params['printId'] = $printId;
        $m = D('Merchantapi/Prints');
        $data = $m->getPrintsInfo($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取小票机列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tau9bk
     */
    public function getPrintsList(){
        $shopId = $this->MemberVeri()['shopId'];
        $equipmentNumber = I('equipmentNumber','');
        $page = I('page',1);
        $pageSize = I('pageSize',15);
        $m = D('Merchantapi/Prints');
        $data = $m->getPrintsList($shopId,$equipmentNumber,$page,$pageSize);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 删除小票机
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wofs6x
     */
    public function delPrints(){
        $shopId = $this->MemberVeri()['shopId'];
        $printId = I('printId');
        if(empty($printId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $printId = explode(',',$printId);
        $m = D('Merchantapi/Prints');
        $data = $m->delPrints($shopId,$printId);
        $this->ajaxReturn($data);
    }

    /**
     * 获取默认小票机
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yifvoy
     */
    public function getPrintsDefault(){
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/Prints');
        $data = $m->getPrintsDefault($shopId);
        $this->ajaxReturn(returnData($data));
    }

}
?>