<?php

namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Pos\PromotionModule;
use http\Params;
use Merchantapi\Model\PromotionModel;
use function App\Util\responseSuccess;
use function App\Util\responseError;

/**
 * 促销
 * */
class PromotionAction extends BaseAction
{
    /**
     * DM档期计划-新增档期计划
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/el4q0m
     * @param string token
     * @param string title DM档期标题
     * @param date sale_start_date 售价开始时间
     * @param date sale_end_date 售价结束时间
     * @param date purchase_start_date 进价开始时间
     * @param date purchase_end_date 进价结束时间
     * @param string remark 备注
     * @return object
     */
    public function addSchedule()
    {
        $login_info = $this->MemberVeri();
        $params = array(
            'title' => trim(I('title')),
            'sale_start_date' => I('sale_start_date'),
            'sale_end_date' => I('sale_end_date'),
            'purchase_start_date' => I('purchase_start_date'),
            'purchase_end_date' => I('purchase_end_date'),
        );
        foreach ($params as $item) {
            if (empty($item)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
            }
        }
        $params['remark'] = I('remark');
        if ((strtotime($params['sale_start_date'])) > strtotime($params['sale_end_date'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '售价开始时间不能大于售价结束时间'));
        }
        if (strtotime($params['purchase_start_date']) > strtotime($params['purchase_end_date'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '进价开始时间不能大于进价结束时间'));
        }
        $module = new PromotionModel();
        $result = $module->addSchedule($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '添加成功'));
    }

    /**
     * DM档期计划-修改档期计划
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dwgetp
     * @param string token
     * @param int schedule_id 档期id
     * @param string title DM档期标题
     * @param date sale_start_date 售价开始时间
     * @param date sale_end_date 售价结束时间
     * @param date purchase_start_date 进价开始时间
     * @param date purchase_end_date 进价结束时间
     * @param string remark 备注
     * @return object
     */
    public function updateSchedule()
    {
        $login_info = $this->MemberVeri();
        $params = I();
        $params['schedule_id'] = I('schedule_id');
        $params['title'] = trim(I('title'));
        $params['sale_start_date'] = I('sale_start_date');
        $params['sale_end_date'] = I('sale_end_date');
        $params['purchase_start_date'] = I('purchase_start_date');
        $params['purchase_end_date'] = I('purchase_end_date');
        $filter_field = array('remark');
        foreach ($params as $key => $item) {
            if (in_array($key, $filter_field)) {
                continue;
            }
            if (empty($item) && !is_numeric($item)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
            }
        }
        if (empty($params['schedule_id'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        if ((strtotime($params['sale_start_date'])) > strtotime($params['sale_end_date'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '售价开始时间不能大于售价结束时间'));
        }
        if (strtotime($params['purchase_start_date']) > strtotime($params['purchase_end_date'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '进价开始时间不能大于进价结束时间'));
        }
        $module = new PromotionModel();
        $result = $module->updateSchedule($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '修改成功'));
    }

    /**
     * DM档期计划-获取档期计划列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bh93g4
     * @param string token
     * @param string title DM档期标题
     * @param date sale_start_date 售价开始时间
     * @param date sale_end_date 售价结束时间
     * @return object
     */
    public function getScheduleList()
    {
        $login_info = $this->MemberVeri();
        $params = I();
        $params['shop_id'] = $login_info['shopId'];
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $module = new PromotionModel();
        $result = $module->getScheduleList($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * DM档期计划-获取档期计划详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fe812e
     * @param string token
     * @param int schedule_id 档期计划id
     * @return object
     */
    public function getScheduleInfo()
    {
        $this->MemberVeri();
        $schedule_id = (int)I('schedule_id');
        if (empty($schedule_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $module = new PromotionModel();
        $result = $module->getScheduleInfo($schedule_id);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * DM档期计划-删除档期计划
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zic3oe
     * @param string token
     * @param string schedule_id 档期计划id,多个用英文逗号分隔
     * @return object
     */
    public function delSchedule()
    {
        $this->MemberVeri();
        $schedule_id = rtrim(I('schedule_id'), ',');
        if (empty($schedule_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $module = new PromotionModel();
        $result = $module->delSchedule($schedule_id);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 新增促销单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/biy7o4
     * @param string token
     * @param data_type 促销方式(1:DM档期计划 2:单品特价 3:买满数量后特价 4:按类别折扣)
     * @param string remark 促销单备注
     * @param json array params 业务参数
     * @return object
     */
    public function addPromotion()
    {
        $login_info = $this->MemberVeri();
        $data_type = (int)I('data_type');
        $goods_params = json_decode(htmlspecialchars_decode(I('goods_params')), true);
        if (empty($goods_params) && $data_type != 4) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择参与促销的商品'));
        }
        if (!in_array($data_type, array(1, 2, 3, 4))) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择正确的促销方式'));
        }
        $remark = (string)I('remark');
        $module = new PromotionModel();
        if ($data_type == 1) {
            //DM档期计划
            $schedule_id = I('schedule_id');
            if (empty($schedule_id)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
            }
            $result = $module->addDMSpecial($goods_params, $schedule_id, $remark, $login_info);
        } elseif ($data_type == 2) {
            //单品特价
            $result = $module->addSpecialSingle($goods_params, $remark, $login_info);
        } elseif ($data_type == 3) {
            //买满数量后特价
            $start_date = I('start_date');
            $end_date = I('end_date');
            if (empty($start_date) || empty($end_date)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请补全促销开始日期和促销结束日期'));
            }
            $result = $module->addSpecialFull($goods_params, $start_date, $end_date, $remark, $login_info);
        } elseif ($data_type == 4) {
            //按类别折扣
            $class_list = json_decode(htmlspecialchars_decode(I('class_list')), true);
            if (empty($class_list)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择分类'));
            }
            $filter_goods = json_decode(htmlspecialchars_decode(I('filter_goods')), true);
            $result = $module->addSpecialClass($class_list, $filter_goods, $remark, $login_info);
        }
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * DM档期计划-获取未过期的档期计划列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yxva7v
     * @param string token
     * @param string title DM档期标题
     * @param date sale_start_date 售价开始时间
     * @param date sale_end_date 售价结束时间
     * @param string sort_field 排序字段(schedule_id:档期id)
     * @param string sort_value 排序值(ASC:正序 DESC:倒序)
     * @return object
     */
    public function getEffectiveScheduleList()
    {
        $login_info = $this->MemberVeri();
        $params = I();
        $params['shop_id'] = $login_info['shopId'];
        $params['sort_field'] = I('sort_field', 'schedule_id');
        $params['sort_value'] = I('sort_value', 'DESC');
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $module = new PromotionModel();
        $result = $module->getEffectiveScheduleList($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 促销单-获取促销单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hg4aw9
     * @param string token
     * @param string bill_date 制单日期:days300:300天内 today:今天 yesterday:昨天 days3:近3天 lastWeek:上周 thisWeek:本周 twoWeek:近两周 自定义年月日期(例子:2020-11) 自定义日期区间:(例子:2020-11-12 - 2020-12-15)
     * @param int examine_status 审核状态(0:未审核 1:已审核)
     * @param int print_status 打印状态(0:未打印 1:已打印)
     * @param int data_type 促销方式(1:DM档期计划 2:单品特价 3:买满数量后特价 4:按类别折扣)
     * @param string bill_no 单据编号
     * @param string creator_name 制单人
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     * @return object
     */
    public function getPromotionList()
    {
        $login_info = $this->MemberVeri();
        $params = I();
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $params['shop_id'] = $login_info['shopId'];
        $module = new PromotionModel();
        $result = $module->getPromotionList($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 促销单-获取促销单详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ipzd4l
     * @param int promotion_id 促销单id
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function getPromotionDetail()
    {
        $login_info = $this->MemberVeri();
        $promotion_id = (int)I('promotion_id');
        $export = (int)I('export', 0);
        if (empty($promotion_id)) {
            $this->ajaxReturn(responseError('参数有误'));
        }
        $module = new PromotionModel();
        $result = $module->getPromotionDetail($promotion_id, $export);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError($result['msg']));
            //$result['data'] = array();
        }
        $result['data']['examine_username'] = '';
        if ($result['data']['examine_status'] == 1) {
            $result['data']['examine_username'] = $login_info['userName'];
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 促销单-修改促销单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sdn7gg
     * @param string token
     * @param int promotion_id 促销单id
     * @param string remark 促销单备注
     * @param json array params 业务参数
     * @return object
     */
    public function updatePromotion()
    {
        $login_info = $this->MemberVeri();
        $goods_params = json_decode(htmlspecialchars_decode(I('goods_params')), true);
        $promotion_id = (int)I('promotion_id');
        if (empty($promotion_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $module = new PromotionModule();
        $promotion_result = $module->getPromotionInfoById($promotion_id);
        if ($promotion_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '传入的促销单id有误'));
        }
        $promotion_info = $promotion_result['data'];
        $data_type = $promotion_info['data_type'];
        if (empty($goods_params) && $data_type != 4) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择参与促销的商品'));
        }
        $remark = (string)I('remark');
        $module = new PromotionModel();
        if ($data_type == 1) {
            //DM档期计划
            $schedule_id = I('schedule_id');
            if (empty($schedule_id)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
            }
            $result = $module->updateDMSpecial($promotion_id, $goods_params, $schedule_id, $remark, $login_info);
        } elseif ($data_type == 2) {
            //单品特价
            $result = $module->updateSpecialSingle($promotion_id, $goods_params, $remark, $login_info);
        } elseif ($data_type == 3) {
            //买满数量后特价
            $start_date = I('start_date');
            $end_date = I('end_date');
            if (empty($start_date) || empty($end_date)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请补全促销开始日期和促销结束日期'));
            }
            $result = $module->updateSpecialFull($promotion_id, $goods_params, $start_date, $end_date, $remark, $login_info);
        } elseif ($data_type == 4) {
            //按类别折扣
            $class_list = json_decode(htmlspecialchars_decode(I('class_list')), true);
            if (empty($class_list)) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择分类'));
            }
            $filter_goods = json_decode(htmlspecialchars_decode(I('filter_goods')), true);
            $result = $module->updateSpecialClass($promotion_id, $class_list, $filter_goods, $remark, $login_info);
        }
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 促销单-修改促销单状态(审核 打印)
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gqtemo
     * @param string promotion_id 促销单,多个用英文逗号分隔
     * @param string field 业务字段名(examine_status:审核状态 print_status:打印状态)
     * @param int value 业务字段值(1:审核通过,打印)
     * @return json
     * */
    public function examinePromotion()
    {
        $login_info = $this->MemberVeri();
        $promotion_id = rtrim(I('promotion_id'), ',');
        $field = I('field', '');
        $value = (int)I('value');
        if (empty($promotion_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全'));
        }
        if (!in_array($field, array('examine_status', 'print_status'))) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请传入正确业务字段名'));
        }
        if (!in_array($value, array(1))) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请传入正确业务字段值'));
        }
        $module = new PromotionModel();
        $result = $module->examinePromotion($promotion_id, $field, $value, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 促销单-修改促销单商品的生效状态
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zl47hb
     * @param string id 促销商品数据id(取值dm_id:DM商品特价id,single_id:单商品特价id,full_id:买满数量后特价id,discount_id:分类折扣id)
     * @param int data_type 促销方式(1:DM档期计划 2:单品特价 3:买满数量后特价 4:按类别折扣)
     * @param int effect_status 状态值(0:生效 1:终止)
     * @return json
     * */
    public function updateGoodsEffectStatus()
    {
        $login_info = $this->MemberVeri();
        $id = rtrim(I('id'), ',');
        $data_type = (int)I('data_type');
        $effect_status = (int)I('effect_status');
        if (empty($id) || empty($data_type)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数不全'));
        }
        if (!in_array($data_type, array(1, 2, 3, 4))) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, "请传入正确的促销方式"));
        }
        if (!in_array($effect_status, array(0, 1))) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, "请传入正确的状态值"));
        }
        $module = new PromotionModel();
        $result = $module->updateGoodsEffectStatus($id, $effect_status, $data_type, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }

    /**
     * 促销单-获取促销单商品Excel表格数据
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gx2xpx
     * @param int data_type 促销方式(1:DM商品特价 2:单商品特价 3:买满数量后特价)
     * @param file $file
     * @return json
     * */
    public function importPromotionGoods()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $data_type = (int)I('data_type');
        if (!in_array($data_type, array(1, 2, 3))) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请传入正确的促销方式'));
        }
        $config = array(
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('xls', 'xlsx', 'xlsm'), //允许上传的文件后缀
            'rootPath' => './Upload/', //保存根路径
            'driver' => 'LOCAL', // 文件上传驱动
            'subName' => array('date', 'Y-m'),
            'savePath' => I('dir', 'uploads') . "/"
        );
        $upload = new \Think\Upload($config);
        $upload_data = $upload->upload($_FILES);
        if (!$upload_data) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $upload->getError()));
        } else {
            $module = new PromotionModel();
            $result = $module->importPromotionGoods($shop_id, $data_type, $upload_data);
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
            }
            $this->ajaxReturn(responseSuccess($result['data'], '成功'));
        }
    }


    /**
     * 促销-扫码搜索商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wne4gy
     * @param string code 商品编码
     * @return array
     * */
    public function searchGoodsInfoByCode()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $code = I('code');
        if (empty($code)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请扫描商品编码'));
        }
        $module = new PromotionModel();
        $result = $module->searchGoodsInfoByCode($shop_id, $code);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data'], '成功'));
    }
}

?>