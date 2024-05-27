<?php

namespace Adminapi\Action;
use Adminapi\Model\ActivityModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 活动控制器
 */
class ActivityAction extends BaseAction
{
    /**
     * 获取活动列表1
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = new ActivityModel();
        $list = $m->getList();
        if (!$list) {
            $list = [];
        }
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    //新增活动
    public function addActivity()
    {
        $this->isLogin();
        $img = I('img');
        $activityId = I('activityId');
        $title = I('title');
        if (empty($img) && empty($title) && empty($activityId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }

        $post['img'] = $img;
        $post['activityId'] = $activityId;
        $post['title'] = $title;


        $m = new ActivityModel();
        $data = $m->addData($post);

        if ($data) {
            $retdata = returnData(true);
        } else {
            $retdata = returnData($data, -1, 'error', '失败', '数据错误');
        }

        $this->ajaxReturn($retdata);

    }

    //修改活动
    public function editActivity()
    {
        $this->isLogin();

        $id = I('id');
        if (empty($id)) {
            $retdata = returnData(null, -1, 'error', '失败', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $post['id'] = $id;
        $post['img'] = I('img');
        $post['activityId'] = I('activityId');
        $post['title'] = I('title');

        $m = new ActivityModel();
        $data = $m->edit($post);


        if ($data) {
            $retdata = returnData(null);
        } else {
            $retdata = returnData(null, -1, 'error', '修改失败', '数据错误');
        }

        $this->ajaxReturn($retdata);


    }

    //删除活动
    public function deleteActivity()
    {
        $this->isLogin();
        $id = I('id');

        if (empty($id)) {
            $retdata = returnData(null, -1, 'error', '删除失败', '数据错误');
            $this->ajaxReturn($retdata);
        }

        $post['id'] = $id;

        $m = new ActivityModel();
        $data = $m->delData($post);
        // var_dump($data);
        $rs['status'] = -1;
        if ($data) {
            $retdata = returnData(true);
        } else {
            $retdata = returnData(null, -1, 'error', '删除数据失败', '');
        }

        $this->ajaxReturn($retdata);

    }

    //获取活动详情
    public function getActivityDetail()
    {
        $this->isLogin();
        $id = I('id');

        if (empty($id)) {
            $rs['status'] = -1;
            $this->ajaxReturn($rs);
        }

        $post['id'] = $id;


        $m = new ActivityModel();
        $data = $m->getActivityDetail($post);
        $this->ajaxReturn(returnData($data));

    }

    //活动页内容-修改
    public function editActivityPageType()
    {
        $this->isLogin();
        $post['goods'] = I('goods');
        $post['img'] = I('img');
        $post['sort'] = I('sort');
        $post['direction'] = I('direction');

        $post['id'] = I('id');

        $m = new ActivityModel();
        $data = $m->editActivityPageType($post);
        if ($data) {
            $retdata = returnData(true);
        } else {
            $retdata = returnData($data, -1, 'error', '修改失败', '数据错误');
        }

        $this->ajaxReturn($retdata);
    }

    //活动页内容-删除
    public function deleteActivityPageType()
    {
        $this->isLogin();
        $post['id'] = I('id');
        $m = new ActivityModel();
        $data = $m->deleteActivityPageType($post);
        if ($data) {
            $retdata = returnData(true);
        } else {
            $retdata = returnData($data, -1, 'error', '删除', '数据错误');
        }

        $this->ajaxReturn($retdata);
    }

    //活动页内容-列表
    public function getActivityPageType()
    {
        $this->isLogin();
        $post['activityPageId'] = I('activityPageId');
        $m = new ActivityModel();
        $data = $m->getActivityPageType($post);
        if (!$data) {
            $data = [];
        }
        if ($data) {
            $retdata = returnData($data);
        } else {
            $retdata = returnData($data, -1, 'error', '获取失败', '数据错误');
        }


        $this->ajaxReturn($retdata);
    }

    //活动页内容-详情 包含商品
    public function getActivityPageTypeDetail()
    {
        $this->isLogin();

        $post['id'] = I('id');
        $m = new ActivityModel();
        $data = $m->getActivityPageTypeDetail($post);
        if ($data) {
            $retdata = returnData($data);
        } else {
            $retdata = returnData($data, -1, 'error', '获取失败', '数据错误');
        }

        $this->ajaxReturn($retdata);
    }


    //活动页内容-新增
    public function addActivityPage()
    {
        $this->isLogin();
        $post['activityPageId'] = I('activityPageId');
        $post['img'] = I('img');
        $post['goods'] = I('goods');
        $post['sort'] = I('sort');
        $post['direction'] = I('direction');
        $m = new ActivityModel();
        $data = $m->addActivityPage($post);
        if ($data) {
            $retdata = returnData($data);
        } else {
            $retdata = returnData($data, -1, 'error', '新增失败', '数据错误');
        }

        $this->ajaxReturn($retdata);
    }
}