<?php

namespace App\Modules\AuthRule;


use App\Enum\ExceptionCodeEnum;
use App\Models\AuthRuleModel;
use App\Models\BaseModel;
use App\Models\RolesModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class AuthRuleModule
 * @package App\Modules\AuthRule
 * 该类只为AuthRuleServiceModule类服务
 */
class AuthRuleModule extends BaseModel
{
    /**
     * @param $moduleType
     * @return array
     * 根据所属模块获取菜单节点列表
     */
    public function getAuthRuleList($moduleType)
    {
        $response = LogicResponse::getInstance();
        $authRuleModel = new AuthRuleModel();
        $where = [];
        $where['module_type'] = $moduleType;
        $rest = $authRuleModel->where($where)->order('weigh asc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$rest)->toArray();
    }

    /**
     * @param $id
     * @return array
     * 获取菜单节点详情
     */
    public function getAuthRuleInfo($id)
    {
        $response = LogicResponse::getInstance();
        $authRuleModel = new AuthRuleModel();
        $where = [];
        $where['id'] = $id;
        $rest = $authRuleModel->where($where)->find();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据菜单节点id更新
     */
    public function editAuthRuleInfo($params)
    {
        $response = LogicResponse::getInstance();
        $authRuleModel = new AuthRuleModel();

        $where = [];
        $where['id'] = $params['id'];

        $param = [];
        $param['pid'] = null;//父ID
        $param['name'] = null;//规则名称（api路由）
        $param['title'] = null;//规则名称（权限文字表述）
        $param['redirect'] = null;//重定向路由【前端需要】
        $param['icon'] = null;//图标
        $param['condition'] = null;//条件（暂不使用）
        $param['remark'] = null;//备注（暂不使用）
        $param['menu_type'] = null;//类型 0目录 1菜单 2按钮
        $param['updateTime'] = null;//更新时间
        $param['weigh'] = null;//权重
        $param['page_hidden'] = null;//隐藏状态 【1是[隐藏] |-1根据权限设置判断[展示] 】
        $param['path'] = null;//路由地址
        $param['component'] = null;//组件路径
        $param['is_frame'] = null;//是否外链 1是 0否
        $param['module_type'] = null;//所属模块【1运营后台、2商家后台】
        $param['model_id'] = null;//模型ID（暂不使用）
        parm_filter($param, $params);

        $rest = $authRuleModel->where($where)->save($param);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
    }
}