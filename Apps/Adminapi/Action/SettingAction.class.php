<?php

namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 官网设置
 */
class SettingAction extends BaseAction
{
    /**
     * 关于我们-详情
     */
    public function about()
    {
        $this->isLogin();
        $detail = M('setting')->where(['name' => 'about'])->find();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑关于我们
     */
    public function aboutEdit()
    {
        $this->isLogin();
        $value = htmlspecialchars_decode(I('value'), true);
        $object = M('setting')->where(['name' => 'about'])->save(['value' => $value]);
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if ($object !== false) {
            $rd = ['code' => 0, 'msg' => '操作成功'];
        }
        $this->ajaxReturn($rd);
    }

    /**
     * 快乐工作-详情
     */
    public function work()
    {
        $this->isLogin();
        $detail = M('setting')->where(['name' => 'work'])->find();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑快乐工作
     */
    public function workEdit()
    {
        $this->isLogin();
        $value = htmlspecialchars_decode(I('value'), true);
        $object = M('setting')->where(['name' => 'work'])->save(['value' => $value]);
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if ($object !== false) {
            $rd = ['code' => 0, 'msg' => '操作成功'];
        }
        $this->ajaxReturn($rd);
    }

    /**
     * 加入我们-详情
     */
    public function join()
    {
        $this->isLogin();
        $detail = M('setting')->where(['name' => 'join'])->find();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑加入我们
     */
    public function joinEdit()
    {
        $this->isLogin();
        $value = htmlspecialchars_decode(I('value'), true);
        $object = M('setting')->where(['name' => 'join'])->save(['value' => $value]);
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if ($object !== false) {
            $rd = ['code' => 0, 'msg' => '操作成功'];
        }
        $this->ajaxReturn($rd);
    }

    /**
     * 下载-详情
     */
    public function down()
    {
        $this->isLogin();
        $detail = M('setting')->where(['name' => 'down'])->find();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑下载内容
     */
    public function downEdit()
    {
        $this->isLogin();
        $value = htmlspecialchars_decode(I('value'), true);
        $object = M('setting')->where(['name' => 'down'])->save(['value' => $value]);
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if ($object !== false) {
            $rd = ['code' => 0, 'msg' => '操作成功'];
        }
        $this->ajaxReturn($rd);
    }

    /**
     *联系我们-详情
     */
    public function contact()
    {
        $this->isLogin();
        $detail = M('setting')->where(['name' => 'contact'])->find();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑联系我们
     */
    public function contactEdit()
    {
        $this->isLogin();
        $value = htmlspecialchars_decode(I('value'), true);
        $object = M('setting')->where(['name' => 'contact'])->save(['value' => $value]);
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if ($object !== false) {
            $rd = ['code' => 0, 'msg' => '操作成功'];
        }
        $this->ajaxReturn($rd);
    }

    /**
     *媒体资料-详情
     */
    public function data()
    {
        $this->isLogin();
        $detail = M('setting')->where(['name' => 'data'])->find();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 编辑媒体资料
     */
    public function dataEdit()
    {
        $this->isLogin();
        $value = htmlspecialchars_decode(I('value'), true);
        $object = M('setting')->where(['name' => 'data'])->save(['value' => $value]);
        $rd = ['code' => -1, 'msg' => '操作失败', 'data' => []];
        if ($object !== false) {
            $rd = ['code' => 0, 'msg' => '操作成功'];
        }
        $this->ajaxReturn($rd);
    }

    /*
 * 首页banner
 * */
    public function indexBanner()
    {
        $this->isLogin();
//        $this->checkPrivelege('mldindexbanner_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/Setting');
        $list = $m->getIndexList($page, $pageSize);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /*
     * 首页banner添加
     * */
    public function indexBannerDetail()
    {
        $this->isLogin();
//        $this->checkPrivelege('mldindexbanner_01');
        $info = [];
        $id = (int)I('id');
        if (empty($id)) $this->returnResponse(-1, '参数不全');
        $info = M('setting_index_banner')->where(['id' => $id])->find();
        $this->returnResponse(0, '操作成功', $info);
    }

    /*
     * 首页banner添加操作
     * */
    public function indexBannerAddDo()
    {
        $this->isLogin();
        $param = I();
        isset($param['id']) ? $response['id'] = $param['id'] : false;
        isset($param['sort']) ? $response['sort'] = $param['sort'] : false;
        isset($param['pic']) ? $response['pic'] = $param['pic'] : false;
        $m = D('Adminapi/Setting');
        if ($response['id'] > 0) {
//            $this->checkPrivelege('mldindexbanner_02');
            $rs = $m->indexBannerEditDo($response);
        } else {
//            $this->checkPrivelege('mldindexbanner_01');
            $response['addTime'] = date('Y-m-d H:i:s', time());
            $rs = $m->indexBannerAddDo($response);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除banner
     */
    public function indexBannerDel()
    {
        $this->isLogin();
        $this->checkPrivelege('mldindexbanner_03');
        $m = D('Adminapi/Setting');
        $param = I();
        isset($param['id']) ? $response['id'] = $param['id'] : false;
        $rs = $m->indexBannerDel($response);
        $this->ajaxReturn($rs);
    }

    /*
     * 其他图片设置详情
     * */
    public function serverBanner()
    {
        $this->isLogin();
        $object['indexBottomBanner'] = M('setting')->where(['name' => 'indexBottomBanner'])->getField('value');
        $this->returnResponse(0, '操作成功', $object);
    }

    /*
     * 其他图片设置操作
     * */
    public function serverBannerEdit()
    {
        $this->isLogin();
//        $this->checkPrivelege('mldfwbanner_00');
        $param = I();
        isset($param['indexBottomBanner']) ? $response['indexBottomBanner'] = $param['indexBottomBanner'] : false;
        $m = D('Adminapi/Setting');
        $rs = $m->serverBannerEdit($response);
        $this->ajaxReturn($rs);
    }
}