<?php

namespace Adminapi\Action;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Rank\RankServiceModule;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 身份控制器
 */
class RankAction extends BaseAction
{
    /**
     * 身份-获取身份列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/pzfycu
     */
    public function getRankList()
    {
        $this->isLogin();
        $rankName = I('rankName');
        $m = new RankServiceModule($rankName);
        $res = $m->getRankList();
        $this->ajaxReturn(returnData($res));
    }


    /**
     * 身份-修改身份
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/pf2ly5
     */
    public function updateRank()
    {
        $this->isLogin();
        $rankId = I('rankId', 0);
        $rankName = I('rankName', '');
        $rankCost = I('rankCost');
        if (empty($rankId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-rankId'));
        }
        if (empty($rankName)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请填写身份名称'));
        }
        $m = new RankServiceModule();
        $res = $m->updateRank($rankId, $rankName,$rankCost);
        $this->ajaxReturn($res);
    }

    /**
     * 身份-添加身份
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/nggi00
     */
    public function addRank()
    {
        $this->isLogin();
        $rankName = I('rankName');
        $rankCost = I('rankCost');
        if (empty($rankName)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请填写身份名称'));
        }
        $m = new RankServiceModule();
        $res = $m->addRank($rankName,$rankCost);
        $this->ajaxReturn($res);
    }

    /**
     * 身份-身份详情
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/sdpsf8
     * */
    public function getRankById()
    {
        $this->isLogin();
        $rankId = I('rankId', 0);
        if (empty($rankId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $m = new RankServiceModule();
        $res = $m->getRankById($rankId);
        $this->ajaxReturn(returnData($res));
    }

    /**
     * 身份-删除身份
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/sz0fzr
     * */
    public function deleteRank()
    {
        $this->isLogin();
        $rankId = I('rankId', 0);
        if (empty($rankId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $m = new RankServiceModule();
        $res = $m->deleteRank($rankId);
        $this->ajaxReturn($res);
    }

    /**
     * 用户身份-为用户绑定身份
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/gep2op
     */
    public function bindUserToRank()
    {
        $this->isLogin();
        $userId = I('userId', 0);
        $rankId = I('rankId', 0);
        if (empty($userId) || empty($rankId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $m = new RankServiceModule();
        $res = $m->bindUserToRank($userId, $rankId);
        $this->ajaxReturn($res);
    }


    /**
     * 用户身份-解绑用户身份
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/tyq8xt
     */
    public function unbindUserToRank()
    {
        $this->isLogin();
        $userId = I('userId', 0);
        if (empty($userId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要解绑身份的用户'));
        }
        $m = new RankServiceModule();
        $res = $m->unbindUserToRank($userId);
        $this->ajaxReturn($res);
    }

}