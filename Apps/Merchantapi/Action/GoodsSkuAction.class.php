<?php

namespace Merchantapi\Action;

use Home\Model\GoodsSkuModel;

;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品SKU
 */
class GoodsSkuAction extends BaseAction
{
    /*
     * 添加规格
     * @param string token
     * @param string specName PS:规格名称
     * @param int sort PS:排序
     * */
    public function goodsSpecInsert()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['specName'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['specName'] = $request['specName'];
        $param['sort'] = (int)$request['sort'];
        $param['shopId'] = $shopInfo['shopId'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecInsert($param);
        $this->ajaxReturn($res);
    }

    /*
     * 编辑规格
     * @param string token
     * @param string specName PS:规格名称
     * @param int sort PS:排序
     * @param int specId PS:规格id
     * */
    public function goodsSpecEdit()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['specName']) || empty($request['specId'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['specName'] = $request['specName'];
        $param['sort'] = (int)$request['sort'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['specId'] = $request['specId'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecEdit($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取规格列表
     * @param string token
     * @param string specName PS:规格名称(用于搜索)
     * @param string sortField PS:排序字段
     * @param string sortWord PS:排序方法(ASC:正序 | DESC:倒叙)
     * @param int p PS:页码
     * */
    public function goodsSpecList()
    {
        $shopInfo = $this->MemberVeri();
        $request = I();
        $param['sortField'] = $request['sortField'];
        $param['sortWord'] = $request['sortWord'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['specName'] = $request['specName'];
        $param['page'] = I('page', 1);
        $param['pageSize'] = I('pageSize', 15);
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecList($param);
        $this->ajaxReturn($res);
    }

    /*
     * 删除规格
     * @param string token
     * @param string specId PS:规格id,多个用英文逗号分隔
     * */
    public function goodsSpecDel()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['specId'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['shopId'] = $shopInfo['shopId'];
        $param['specId'] = $request['specId'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecDel($param);
        $this->ajaxReturn($res);
    }

    /*
     * 添加属性
     * @param string token
     * @param string specId PS:规格id
     * @param string attrName PS:属性名称
     * @param int sort PS:排序
     * */
    public function goodsSpecAttrInsert()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['attrName']) || empty($request['specId'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['attrName'] = $request['attrName'];
        $param['specId'] = $request['specId'];
        $param['sort'] = (int)$request['sort'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecAttrInsert($param);
        $this->ajaxReturn($res);
    }

    /*
     * 编辑属性
     * @param string token
     * @param string attrId PS:属性id
     * @param string specId PS:规格id
     * @param string attrName PS:属性名称
     * @param int sort PS:排序
     * */
    public function goodsSpecAttrEdit()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['attrName']) || empty($request['attrId'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['attrId'] = $request['attrId'];
        $param['attrName'] = $request['attrName'];
        $param['specId'] = $request['specId'];
        $param['sort'] = (int)$request['sort'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecAttrEdit($param);
        $this->ajaxReturn($res);
    }

    /*
     * 删除属性
     * @param string token
     * @param int attrId PS:属性id,多个用英文逗号分隔
     * */
    public function goodsSpecAttrDel()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['attrId'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['attrId'] = $request['attrId'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecAttrDel($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取属性列表
     * @param string token
     * @param int specId PS:规格id
     * @param string sortField PS:排序字段
     * @param string sortWord PS:排序方法(ASC:正序 | DESC:倒叙)
     * @param int page PS:页码
     * @param int pageSize PS:分页条数
     * */
    public function goodsSpecAttrList()
    {
        $shopInfo = $this->MemberVeri();
        $request = I();
        $param['sortField'] = $request['sortField'];
        $param['sortWord'] = $request['sortWord'];
        $param['specId'] = $request['specId'];
        $param['page'] = I('page', 1);
        $param['pageSize'] = I('pageSize', 15);
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecAttrList($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取规格属性列表(无分页)
     * @param string token
     * @param string specSortField PS:规格排序字段
     * @param string specSortWord PS:规格排序方法(ASC:正序 | DESC:倒叙)
     * @param string attrSortField PS:属性排序字段
     * @param string attrSortWord PS:属性排序方法(ASC:正序 | DESC:倒叙)
     * */
    public function goodsSpecAttrMergeList()
    {
        $shopInfo = $this->MemberVeri();
        $param['specSortField'] = I("specSortField", "sort");
        $param['specSortWord'] = I("specSortWord", "desc");
        $param['attrSortField'] = I("attrSortField", "sort");
        $param['attrSortWord'] = I("attrSortWord", "desc");
        $param['shopId'] = $shopInfo['shopId'];
        $m = D("Home/GoodsSku");
        $res = $m->goodsSpecAttrMergeList($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商品添加sku属性
     * @param string token
     * @param int goodsId PS:商品id
     * @param string specAttrString PS:规格属性组合,例子:
sku = systemSepc + selfSpec
{
    "specAttrString": [
        {
            "systemSpec": {
                "skuShopPrice": "10.9",
                "skuMemberPrice": "11",
                "skuGoodsStock": "100",
                "skuGoodsImg": "pic",
                "skuBarcode": "B-sku21"
            },
            "selfSpec": [
                {
                    //"specId": 1,
                    "attrId": 2
                },
                {
                    //"specId": 1,
                    "attrId": 3
                }
            ]
        },
    ]
}
     * */
    public function insertGoodsSku()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['goodsId']) || empty($_POST['specAttrString'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['goodsId'] = $request['goodsId'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['specAttrString'] = json_decode(htmlspecialchars_decode(I('specAttrString')), true);
//        $m = D("Home/GoodsSku");
        $m = new GoodsSkuModel();
        $res = $m->insertGoodsSku($param);
        $this->ajaxReturn($res);
    }


    /*
     * 编辑商品sku属性
     * @param string token
     * @param int goodsId PS:商品id
     * @param string specAttrString PS:规格属性组合,例子:
sku = systemSepc + selfSpec
{
    "specAttrString": [
        {
            "skuId":1,
            "systemSpec": {
                "skuShopPrice": "10.9",
                "skuMemberPrice": "11",
                "skuGoodsStock": "100",
                "skuGoodsImg": "pic",
                "skuBarcode": "B-sku21"
            },
            "selfSpec": [
                {
                    //"specId": 1,
                    "attrId": 2
                },
                {
                    //"specId": 1,
                    "attrId": 3
                }
            ]
        },
    ]
}
     * */
    public function editGoodsSku()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['goodsId']) || empty($_POST['specAttrString'])) {
            $this->ajaxReturn($apiRet);
        }
        $param['goodsId'] = $request['goodsId'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['specAttrString'] = json_decode(htmlspecialchars_decode(I('specAttrString')), true);
//        $m = D("Home/GoodsSku");
        $m = new GoodsSkuModel();
        $res = $m->editGoodsSku($param);
        $this->ajaxReturn($res);
    }


    /*
     *删除商品sku
     * @param string token
     * @param string skuId PS:skuId,多个用英文逗号拼接
     * */
    public function deleteGoodsSku()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if (empty($request['skuId'])) {
            $this->ajaxReturn($apiRet);
        }
        $m = D("Home/GoodsSku");
        $res = $m->deleteGoodsSku($request);
        $this->ajaxReturn($res);
    }

    /*
     *获取商品的sku
     * @param string token
     * @param int goodsId
     * */
    public function getGoodsSku()
    {
        $shopInfo = $this->MemberVeri();
        $request = I();
        if (empty($request['goodsId'])) {
            $this->ajaxReturn(returnData(false, 0, 'success', '参数有误'));
        }
        $m = D("Home/GoodsSku");
        $param['shopId'] = $shopInfo['shopId'];
        $param['goodsId'] = $request['goodsId'];
        $res = $m->getGoodsSku($param);
        $this->ajaxReturn($res);
    }


}

?>