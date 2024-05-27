<?php
/**
 * 桌面端-入库
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-08
 * Time: 10:04
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\EnterpriseWarehouseModel;

class EnterpriseWarehouseAction extends BaseAction
{
    /**
     * 商品商城分类列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cfw3zg
     * */
    public function getGoodsCatList()
    {
        $this->verficationToken();
        $model = new EnterpriseWarehouseModel();
        $res = $model->getGoodsCatList();
        $this->ajaxReturn(returnData($res));
    }

    /**
     * 获取门店商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/oxnv8x
     * */
    public function getShopGoodsList()
    {
        $token_data = $this->verficationToken();
        $shop_id = $token_data['shopid'];
        $request_params = I();
        $params = array(
            'shop_id' => $shop_id,
            'goods_code' => '',//商品码
            'cat_id' => '',//分类id
            'page' => 1,//页码
            'page_size' => 15,//分页条数
        );
        parm_filter($params, $request_params);
        $model = new EnterpriseWarehouseModel();
        $res = $model->getShopGoodsList($params);
        $this->ajaxReturn(returnData($res));
    }

    /**
     * 创建入库单
     * 文档链接地址:yuque.com/anthony-6br1r/oq7p0p/sagk2u
     * */
    public function createWarehousingBill()
    {
        $token_data = $this->verficationToken();
        $id = $token_data['id'];
        $model = new EnterpriseWarehouseModel();
        $bill_params = json_decode(htmlspecialchars_decode(I('bill_params')), true);
        if (empty($bill_params)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-bill_params'));
        }
        if (empty($bill_params['goods_data'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品'));
        }
        $res = $model->createWarehousingBill($id, $bill_params);
        $this->ajaxReturn($res);
    }
}