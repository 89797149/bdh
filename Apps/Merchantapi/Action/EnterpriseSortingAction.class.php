<?php
/**
 * 桌面端端分拣
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-06-18
 * Time: 14:30
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Sms\SmsEnum;
use Merchantapi\Model\EnterpriseSortingModel;

class EnterpriseSortingAction extends BaseAction
{
    /**
     * 配置-获取门仓配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bubp1y
     * */
    public function getShopConfig()
    {
        $token_data = $this->verficationToken();
        $shop_id = $token_data['shopid'];
        $model = new EnterpriseSortingModel();
        $result = $model->getShopConfig($shop_id);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 登陆
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xzrztw
     * @param string account 账号
     * @param string password 密码
     * */
    public function login()
    {
        $account = I('account');
        $password = I('password');
        if (empty($account) || empty($password)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入账号或密码'));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->login($account, $password);
        $this->ajaxReturn($result);
    }

    /**
     * 分拣员-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ytypn1
     * @param string token
     * */
    public function getSortingPersonnelDetial()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $model = new EnterpriseSortingModel();
        $result = $model->getSortingPersonnelDetial($id);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 分拣员-修改个人信息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ezo1ll
     * @param string token
     * */
    public function updateSortingPersonnelDetial()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'userName' => null,
            'mobile' => null,
            'state' => null,
            'password' => null,
        );
        $model = new EnterpriseSortingModel();
        parm_filter($params, $request_params);
        if (isset($params['userName'])) {
            if (empty($params['userName'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请填写姓名'));
            }
        }
        if (isset($params['mobile'])) {
            if (!preg_match(SmsEnum::MOBILE_FORMAT, $params['mobile'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '手机号格式不正确'));
            }
        }
        if (isset($params['password'])) {
            if (mb_strlen($params['password']) < 6) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '密码长度不得少于6位'));
            }
        }
        $result = $model->updateSortingPersonnelDetial($params);
        $this->ajaxReturn($result);
    }

    /**
     * 配送端-线路列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zk95lt
     * */
    public function getDeliveryLineList()
    {
        $token_data = $this->verficationToken();
        $shop_id = $token_data['shopid'];
        $model = new EnterpriseSortingModel();
        $result = $model->getDeliveryLineList($shop_id);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 按商品分拣-获取商品分类
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/aeszdu
     * */
    public function getSortingGoodsCatsToGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'delivery_time' => '',//发货日期,不传默认当天
            'sorting_status' => '',//分拣状态(1:未分拣 2:已分拣 不传默认全部)
            'goods_category' => '',//商品品类(1:标品 2:非标品 不传默认全部)
            'line_id' => '',//线路id,不传默认全部
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getSortingGoodsCatsToGoods($params);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 按商品分拣-获取商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/snk2zc
     * */
    public function getSortingGoodsToGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'delivery_time' => '',//发货日期,不传默认当天 注:和获取分类的参数值保持一致
            'sorting_status' => '',//分拣状态(1:未分拣 2:已分拣 不传默认全部) 注:和获取分类的参数值保持一致
            'goods_category' => '',//商品品类(1:标品 2:非标品 不传默认全部) 注:和获取分类的参数值保持一致
            'line_id' => '',//线路id,多个用英文逗号分隔,不传默认全部 注:和获取分类的参数值保持一致
            'cat_id' => 0,//二级分类id
            'goods_name' => '',//搜索-商品名称
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        /*if (empty($params['cat_id'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "缺少必填参数-cat_id"));
        }*/
        $model = new EnterpriseSortingModel();
        $result = $model->getSortingGoodsToGoods($params);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 按商品分拣-获取商品下的客户列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qy469b
     * */
    public function getSortingCustomerToGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'delivery_time' => '',//发货日期,不传默认当天 注:和获取分类的参数值保持一致
            'goods_id' => 0,//商品id
            'sku_id' => 0,//商品skuId
            'payment_username' => '',//搜索-客户名称
            'sort_order' => 1,//排序(1:默认 2:客户名称 3:客户编码 4:线路)
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        if (empty($params['goods_id'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "请先选择商品"));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getSortingCustomerToGoods($params);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 按商品分拣-获取商品下的客户任务详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/avr6t9
     * */
    public function getSortingCustomerDetailToGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $sorting_relationid = I('id');
        if (empty($sorting_relationid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "缺少必填参数-id"));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getSortingCustomerDetailToGoods($id, $sorting_relationid);
//        $this->ajaxReturn(returnData($result));
        $result = json_encode(returnData($result));
        $result = str_replace(array('\n', '\r\n'), '', $result);
        echo $result;
        exit;
    }

    /**
     * 分拣商品-执行分拣
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cy8l0x
     * */
    public function actionSortingGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $sorting_goods_relationid = I('id');//分拣商品唯一标识id
        $num_or_weight = I('num_or_weight');//分拣数量或重量
        if (empty($sorting_goods_relationid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "参数有误-id"));
        }
        if (!is_numeric($num_or_weight)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "请填写分拣数量或重量"));
        }
        if (($num_or_weight + 0) < 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "称重数量或重量不得小于0"));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->actionSortingGoods($id, $sorting_goods_relationid, (float)$num_or_weight);
        $this->ajaxReturn($result);
    }


    /**
     * 分拣商品-标记缺货/部分缺货
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dg1yew
     * */
    public function lackSortingGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $sorting_goods_relationid = I('id');//分拣商品唯一标识id
        $lack_stock_status = (int)I('lack_stock_status');//缺货状态(1:部分缺货 2:全部缺货)
        if (empty($sorting_goods_relationid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "参数有误-id"));
        }
        if (!in_array($lack_stock_status, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "请传入正确的缺货状态"));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->lackSortingGoods($id, $sorting_goods_relationid, $lack_stock_status);
        $this->ajaxReturn($result);
    }

    /**
     * 分拣商品-重置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/blf309
     * */
    public function resetSortingGoods()
    {
        $this->verficationToken();
        $sorting_goods_relationid = I('id');//分拣商品唯一标识id
        if (empty($sorting_goods_relationid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "参数有误-id"));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->resetSortingGoods($sorting_goods_relationid);
        $this->ajaxReturn($result);
    }

    /**
     * 分拣-一键分拣/一键打印-获取分拣商品数量
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/osypi6
     * */
    public function getSortingGoodsCount()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'from_type' => '',//场景类型(1:一键打印 2:一键分拣)
            'delivery_time' => '',//发货日期,不传默认当天 注:和获取分类的参数值保持一致
            'sorting_status' => '',//分拣状态(1:未分拣 2:已分拣 不传默认全部) 注:和获取分类的参数值保持一致,该字段只对一键打印有效
            'goods_category' => '',//商品品类(1:标品 2:非标品 不传默认全部) 注:和获取分类的参数值保持一致
            'line_id' => '',//线路id,多个用英文逗号分隔,不传默认全部 注:和获取分类的参数值保持一致
            'cat_id' => 0,//二级分类id,不传默认全部
            'goods_name' => '',//搜索-商品名称
            'goods_id' => 0,//商品id
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        if (empty($params['from_type'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填字段-from_type'));
        }
        if ($params['from_type'] == 2) {
            $params['sorting_status'] = 1;
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getSortingGoodsCount($params);
        $result = json_encode($result);
        $result = str_replace(array('\n', '\r\n'), '', $result);
        echo $result;
        exit;
//        $this->ajaxReturn($result);
    }

    /**
     * 分拣-一键分拣-确认分拣
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ea1fkd
     * */
    public function oneKeySortingGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'delivery_time' => '',//发货日期,不传默认当天 注:和获取分类的参数值保持一致
            'goods_category' => '',//商品品类(1:标品 2:非标品 不传默认全部) 注:和获取分类的参数值保持一致
            'line_id' => '',//线路id,多个用英文逗号分隔,不传默认全部 注:和获取分类的参数值保持一致
            'cat_id' => 0,//二级分类id,不传默认全部
            'goods_name' => '',//搜索-商品名称
            'goods_id' => 0,//商品id
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        $model = new EnterpriseSortingModel();
        $result = $model->oneKeySortingGoods($params);
        $this->ajaxReturn($result);
    }

    /**
     * 分拣-批量打印-确认打印 废弃
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ebnk9w
     * */
    public function oneKeyPrintSortingGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'delivery_time' => '',//发货日期,不传默认当天 注:和获取分类的参数值保持一致
            'sorting_status' => '',//分拣状态(1:未分拣 2:已分拣 不传默认全部) 注:和获取分类的参数值保持一致
            'goods_category' => '',//商品品类(1:标品 2:非标品 不传默认全部) 注:和获取分类的参数值保持一致
            'line_id' => '',//线路id,多个用英文逗号分隔,不传默认全部 注:和获取分类的参数值保持一致
            'cat_id' => 0,//二级分类id,不传默认全部
            'goods_name' => '',//搜索-商品名称
            'goods_id' => 0,//商品id
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        $model = new EnterpriseSortingModel();
        $result = $model->oneKeyPrintSortingGoods($params);
        $this->ajaxReturn($result);
    }

    /**
     * 分拣-单个商品打印-确认打印 废弃
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/pd8bo8
     * */
    public function oneGoodsPrintSortingGoods()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $sorting_goods_relationid = I('id');//分拣商品唯一标识id
        if (empty($sorting_goods_relationid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', "参数有误 - id"));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->oneGoodsPrintSortingGoods($id, $sorting_goods_relationid);
        $this->ajaxReturn($result);
    }

    /**
     * 按客户分拣-获取客户订单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/utgwwu
     * */
    public function getCustomerToCustomer()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $request_params = I();
        $params = array(
            'id' => $id,
            'delivery_time' => '',//发货日期,不传默认当天
            'sorting_status' => '',//分拣状态(1:未分拣 2:已分拣 不传默认全部)
            'goods_category' => '',//商品品类(1:标品 2:非标品 不传默认全部)
            'line_id' => '',//线路id,多个用英文逗号分隔,不传默认全部
            'payment_username' => '',//搜索-客户名称 注:只支持中文模糊搜索,和蔬东坡一样
        );
        parm_filter($params, $request_params);
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getCustomerToCustomer($params);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 按客户分拣-获取客户订单商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ulnu8d
     * */
    public function getOrderGoodsToCustomer()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $order_id = I('order_id');
        $goods_name = I('goods_name');
        if (empty($order_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-order_id'));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getOrderGoodsToCustomer($id, $order_id, $goods_name);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 按客户分拣-获取客户订单商品详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bc3pzf
     * */
    public function getOrderGoodsDetailToCustomer()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $sorting_goods_relationid = I('id');
        if (empty($sorting_goods_relationid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-id'));
        }
        $model = new EnterpriseSortingModel();
        $result = $model->getOrderGoodsDetailToCustomer($id, $sorting_goods_relationid);
//        $this->ajaxReturn(returnData($result));
        $result = json_encode(returnData($result));
        $result = str_replace(array('\n', '\r\n'), '', $result);
        echo $result;
        exit;
    }


}