<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 公告相关
 */
class AnnouncementAction extends BaseAction {
    /**
     * 添加店铺公告
     * @param string title 公告标题
     * @param string content 公告内容
     */
    public function addAnnouncement(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Announcement');
        $title = I('title','');
        $content = I('content','');
        if(empty($title) || empty($content)){
            $this->ajaxReturn(returnData(false,-1,'error','参数不全'));
        }
        $shopId = $shopInfo['shopId'];
        $data = $m->addAnnouncement($shopId,$title,$content);
        $this->ajaxReturn($data);
    }

    /**
     * 编辑商城公告
     * @param int id 公告id
     * @param string title 公告标题
     * @param string content 公告内容
     */
    public function updateAnnouncement()
    {
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        if(empty($requestParams['id']) || empty($requestParams['title'])){
            $this->ajaxReturn(returnData(false,-1,'error','参数不全'));
        }
        $params = [];
        $params['id'] = 0;
        $params['title'] = null;
        $params['content'] = null;
        parm_filter($params,$requestParams);
        $params['shopId'] = $shopInfo['shopId'];
        $m = D('Merchantapi/Announcement');
        $data = $m->updateAnnouncement($params);
        $this->ajaxReturn($data);
    }

    /**
     * 获取商城公告列表
     * @param string title 公告标题
     * @param datetime startDate 添加时间区间-开始时间
     * @param datetime endDate 添加时间区间-结束时间
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function getAnnouncementList()
    {
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $params = [];
        $params['title'] = '';
        $params['startDate'] = '';
        $params['endDate'] = '';
        $params['page'] = 1;
        $params['pageSize'] = 15;
        parm_filter($params,$requestParams);
        $params['shopId'] = $shopInfo['shopId'];
        $m = D('Merchantapi/Announcement');
        $data = $m->getAnnouncementList($params);
        $this->ajaxReturn($data);
    }

    /**
     * 获取商城公告列表
     * @param int id 公告id
     */
    public function getAnnouncementDetail()
    {
        $shopInfo = $this->MemberVeri();
        $id = I('id',0);
        if(empty($id)){
            $this->returnResponse(-1,'参数不全',false);
        }
        $m = D('Merchantapi/Announcement');
        $params = [];
        $params['id'] = $id;
        $params['shopId'] = $shopInfo['shopId'];
        $data = $m->getAnnouncementDetail($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 删除公告
     * @param id 公告id,删除多个用英文逗号拼接id
     */
    public function delAnnouncement()
    {
        $shopInfo = $this->MemberVeri();
        $ids = I('id',0);
        if(empty($ids)){
            $this->returnResponse(-1,'参数不全',false);
        }
        $m = D('Merchantapi/Announcement');
        $ids = rtrim($ids,',');
        $shopId = $shopInfo['shopId'];
        $data = $m->delAnnouncement($shopId,$ids);
        $this->ajaxReturn($data);
    }
}