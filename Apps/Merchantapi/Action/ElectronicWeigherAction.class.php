<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 电子秤控制器
 */
class ElectronicWeigherAction extends BaseAction{

    /**
     * 新增电子秤配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tc6ank
     */
    public function addElectronicWeigher(){
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        $requestParams['shopId'] = $shopId;
        $m = D('Merchantapi/ElectronicWeigher');
        $data = $m->addElectronicWeigher($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 修改电子秤配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/phrgop
     */
    public function updateElectronicWeigher(){
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        if(empty($requestParams['id'])){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $requestParams['shopId'] = $shopId;
        $m = D('Merchantapi/ElectronicWeigher');
        $data = $m->updateElectronicWeigher($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 获取电子秤详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ya1bsz
     */
    public function getElectronicWeigherInfo(){
        $id = (int)I('id');
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $params = [];
        $params['id'] = $id;
        $m = D('Merchantapi/ElectronicWeigher');
        $data = $m->getElectronicWeigherInfo($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取电子秤列表
     * 文档链接地址:
     */
    public function getElectronicWeigherList(){
        $shopId = $this->MemberVeri()['shopId'];
        $ip = I('ip','');
        $port = I('port','');
        $page = (int)I('page',1);
        $pageSize = (int)I('pageSize',15);
        $m = D('Merchantapi/ElectronicWeigher');
        $data = $m->getElectronicWeigherList($shopId,$ip,$port,$page,$pageSize);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 删除电子秤配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ntto4m
     */
    public function delElectronicWeigher(){
        $shopId = $this->MemberVeri()['shopId'];
        $id = I('id','');
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $idArr = explode(',',$id);//前端要求逗号分隔
        $m = D('Merchantapi/ElectronicWeigher');
        $data = $m->delElectronicWeigher($shopId,$idArr);
        $this->ajaxReturn($data);
    }

    /**
     * 获取默认电子称配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/msibmf
     */
    public function getElectronicWeigherDefault(){
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/ElectronicWeigher');
        $data = $m->getElectronicWeigherDefault($shopId);
        $this->ajaxReturn(returnData($data));
    }

}
?>