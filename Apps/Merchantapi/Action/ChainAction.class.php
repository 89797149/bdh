<?php

namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\ChainModel;
use function App\Util\responseError;
use function App\Util\responseSuccess;

/**
 * 悬挂链控制器
 */
class ChainAction extends BaseAction
{
    /**
     * 钩子-新增钩子
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tdgsk8
     */
    public function addHook()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $params = I();
        $params['shop_id'] = $login_info['shopId'];
        if (empty($params['hook_code'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '钩子编码不能为空', false));
        }
        $result = $model->addHook($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '添加成功'));
    }

    /**
     * 钩子-修改钩子
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/agg3gn
     */
    public function updateHook()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $params = I();
        $params['shop_id'] = $login_info['shopId'];
        if (empty($params['hook_code']) || empty($params['hook_id'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->updateHook($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '修改成功'));
    }

    /**
     * 钩子-获取钩子列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vgguyt
     */
    public function getHookList()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $params = array(
            'shop_id' => $login_info['shopId'],
            'hook_code' => I('hook_code', ''),
            'start_date' => I('start_date', ''),
            'end_date' => I('end_date', ''),
            'page' => (int)I('page', 1),
            'pageSize' => (int)I('pageSize', 15),
        );
        $result = $model->getHookList($params);
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 钩子-获取钩子详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qayzn4
     * @param int hook_id 钩子id
     * @return json
     */
    public function getHookInfo()
    {
        $this->MemberVeri();
        $model = new ChainModel();
        $id = (int)I('hook_id');
        if (empty($id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->getHookInfo($id);
        if ($result['code'] == ExceptionCodeEnum::FAIL) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 钩子-删除钩子
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hy6dzh
     * @param string hook_id 钩子id,多个用英文逗号分隔
     * @return json
     */
    public function delHook()
    {
        $this->MemberVeri();
        $model = new ChainModel();
        $id = rtrim(I('hook_id'), ',');
        if (empty($id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->delHook($id);
        if ($result['code'] == ExceptionCodeEnum::FAIL) {
            $this->ajaxReturn($this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '删除失败', false)));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 下链口-新增下链口
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hhmt2c
     */
    public function addChain()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $params = I();
        $params['shop_id'] = $login_info['shopId'];
        if (empty($params['chain_code'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '编码不能为空', false));
        }
        $result = $model->addChain($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '添加成功'));
    }

    /**
     * 下链口-修改下链口
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/iwp54g
     */
    public function updateChain()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $params = I();
        $params['shop_id'] = $login_info['shopId'];
        if (empty($params['chain_code']) || empty($params['chain_id'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->updateChain($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '修改成功'));
    }

    /**
     * 下链口-修改下链口状态
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ohfb2o
     */
    public function updateChainStatus()
    {
        $this->MemberVeri();
        $model = new ChainModel();
        $chain_id = (int)I('chain_id');
        $status = (int)I('status');
        if (empty($chain_id) || empty($status)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->updateChainStatus($chain_id, $status);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg'], false));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '修改成功'));
    }

    /**
     * 下链口-获取下链口列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/rb19mt
     */
    public function getChainList()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $params = array(
            'shop_id' => $login_info['shopId'],
            'status' => I('status', ''),
            'chain_code' => I('chain_code', ''),
            'start_date' => I('start_date', ''),
            'end_date' => I('end_date', ''),
            'page' => (int)I('page', 1),
            'pageSize' => (int)I('pageSize', 15),
        );
        $result = $model->getChainList($params);
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 下链口-获取下链口详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/pw43tc
     * @param int chain_id 下链口id
     * @return json
     */
    public function getChainInfo()
    {
        $this->MemberVeri();
        $model = new ChainModel();
        $id = (int)I('chain_id');
        if (empty($id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->getChainInfo($id);
        if ($result['code'] == ExceptionCodeEnum::FAIL) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 下链口-删除下链口
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ee5u8v
     * @param string chain_id 下链口id,多个用英文逗号分隔
     * @return json
     */
    public function delChain()
    {
        $this->MemberVeri();
        $model = new ChainModel();
        $id = rtrim(I('chain_id'), ',');
        if (empty($id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $result = $model->delChain($id);
        if ($result['code'] == ExceptionCodeEnum::FAIL) {
            $this->ajaxReturn($this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '删除失败', false)));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 钩子记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qmz94x
     * */
    public function getGoodsBindHoodLogs()
    {
        $login_info = $this->MemberVeri();
        $model = new ChainModel();
        $hook_id = I('hook_id');
        if (empty($hook_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $params = array(
            'hook_id' => $hook_id,
            'shop_id' => (int)$login_info['shopId'],
            'order_no' => I('order_no'),
            'goods_name' => I('goods_name'),
            'goods_sn' => I('goods_sn'),
            'start_date' => I('start_date'),
            'end_date' => I('end_date'),
            'page' => (int)I('page', 1),
            'pageSize' => (int)I('pageSize', 15),
        );
        $result = $model->getGoodsBindHoodLogs($params);
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 下链口-查看订单记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yvgrs9
     */
    public function getChainOrderLog()
    {
        $login_info = $this->MemberVeri();
        $chain_id = (int)I('chain_id');
        if (empty($chain_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全', false));
        }
        $model = new ChainModel();
        $params = I();
        $params["shopId"] = (int)$login_info["shopId"];
        $params["chain_id"] = $chain_id;
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $result = $model->getChainOrderLog($params);
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 悬挂链回调,返回当前在链上订单、载具关系及状态
     * */
    public function pollinfo()
    {
        $shop_id = (int)I('poiId');
        $area_info = json_decode(htmlspecialchars_decode(I('areaInfo')), true);
        if (empty($shop_id)) {
            $response = array(
                'code' => -1,
                'error' => array(
                    'msg' => '参数有误'
                ),
            );
            $this->ajaxReturn($response);
        }
        $model = new ChainModel();
        $result = $model->pollinfo($shop_id, $area_info);
        $this->ajaxReturn($result);
    }
}

?>