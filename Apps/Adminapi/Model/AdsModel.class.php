<?php

namespace Adminapi\Model;
use App\Modules\Goods\GoodsServiceModule;
use App\Modules\Shops\ShopsServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 广告服务类
 */
class AdsModel extends BaseModel
{
    /**
     * @param $userData
     * @return mixed
     * 新增广告
     */
    public function insert($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $data = array();

        $data["adStartDate"] = I("adStartDate");
        $data["adEndDate"] = I("adEndDate");
        $data["adSort"] = I("adSort", 0);
        if ($this->checkEmpty($data, true)) {

            $data["adPositionId"] = (int)I("adPositionId");
            $data["adFile"] = I("adFile");

            $data["adName"] = I("adName");
            $data["adURL"] = I("adURL");
            $data["areaId1"] = (int)I("areaId1");
            $data["areaId2"] = (int)I("areaId2");
            $data["appBannerFile"] = I('appBannerFile');//app轮播地址
            $data["appBannerFileShop"] = I('appBannerFileShop');//店铺id
            $data["appBannerFileGood"] = I('appBannerFileGood');//商品id
            $data["content"] = htmlspecialchars_decode(I("content"),true);
            if(!empty($data["appBannerFileShop"]) && !empty($data["appBannerFileGood"])){
                $rd = returnData(false, -1, 'error', '店铺或商品两者不可同时存在');
                return $rd;
            }
            $rs = $this->add($data);
            if (false !== $rs) {
                $describe = "[{$userData['loginName']}]新增了广告:[{$data["adName"]}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
                $rd = returnData(true, 0, 'success', '操作成功');
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return mixed
     * 修改广告
     */
    public function edit($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I("id", 0);

        $data["adStartDate"] = I("adStartDate");
        $data["adEndDate"] = I("adEndDate");
        $data["adSort"] = (int)I("adSort", 0);

        if ($this->checkEmpty($data, true)) {

            $data["adPositionId"] = (int)I("adPositionId");
            $data["adFile"] = I("adFile");

            $data["adName"] = I("adName");
            $data["adURL"] = I("adURL");
            $data["areaId1"] = (int)I("areaId1");
            $data["areaId2"] = (int)I("areaId2");
            $data["appBannerFile"] = I('appBannerFile');//app轮播地址
            $data["appBannerFileShop"] = I('appBannerFileShop');//店铺id
            $data["appBannerFileGood"] = I('appBannerFileGood');//商品id
            $data["content"] = htmlspecialchars_decode(I("content"),true);
            if(!empty($data["appBannerFileShop"]) && !empty($data["appBannerFileGood"])){
                $rd = returnData(false, -1, 'error', '店铺或商品两者不可同时存在');
                return $rd;
            }
            $rs = $this->where("adId=" . $id)->save($data);
            if (false !== $rs) {
                $describe = "[{$userData['loginName']}]编辑了广告:[{$data["adName"]}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
                $rd = returnData(true, 0, 'success', '操作成功');
            }
        }
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function get()
    {
        $res = $this->where("adId=" . (int)I('id'))->find();
        if(!empty($res)){
            $shopsServiceModule = new ShopsServiceModule();
            $goodsServiceModule = new GoodsServiceModule();
            $res['goodsShopName'] = "";
            //店铺
            if(!empty($res['appBannerFileShop'])){
                $shopInfo = $shopsServiceModule->getShopsInfoById($res['appBannerFileShop']);
                if($shopInfo['code'] != -1){
                    $res['goodsShopName'] = $shopInfo['data']['shopName'];
                }
            }
            //商品
            if(!empty($res['appBannerFileGood'])){
                $goodsInfo = $goodsServiceModule->getGoodsInfoById($res['appBannerFileGood']);
                if($goodsInfo['code'] != -1){
                    $res['goodsShopName'] = $goodsInfo['data']['goodsName'];
                }
            }
        }
        return $res;
    }

    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $adPositionId = (int)I('adPositionId');
        $adDateRange = I('adDateRange');
        $adName = WSTAddslashes(I('adName'));
        $sql = "select a.*,a1.areaName areaName1,a2.areaName areaName2
	 	        from __PREFIX__ads a 
	 	        left join __PREFIX__areas a1 on a.areaId1=a1.areaId 
	 	        left join __PREFIX__areas a2 on a.areaId2 = a2.areaId 
	 	        where 1=1 ";
        if ($adPositionId != "") $sql .= "  and adPositionId=" . $adPositionId;
        if ($adName != "") {
            $sql .= "  and a.adName like '%$adName%'";
        }
        $sql .= ' order by adSort desc';
        $res = $this->pageQuery($sql, $page, $pageSize);
        if(!empty($res['root'])){
            $shopsServiceModule = new ShopsServiceModule();
            $goodsServiceModule = new GoodsServiceModule();
            foreach ($res['root'] as $k=>$v){
                $res['root'][$k]['goodsShopName'] = "";
                //店铺
                if(!empty($v['appBannerFileShop'])){
                    $shopInfo = $shopsServiceModule->getShopsInfoById($v['appBannerFileShop']);
                    if($shopInfo['code'] != -1){
                        $res['root'][$k]['goodsShopName'] = $shopInfo['data']['shopName'];
                    }
                }
                //商品
                if(!empty($v['appBannerFileGood'])){
                    $goodsInfo = $goodsServiceModule->getGoodsInfoById($v['appBannerFileGood']);
                    if($goodsInfo['code'] != -1){
                        $res['root'][$k]['goodsShopName'] = $goodsInfo['data']['goodsName'];
                    }
                }
            }
        }
        return $res;
    }

    /**
     * 获取列表
     */
    public function queryByList()
    {
        $sql = "select * from __PREFIX__ads order by adId desc";
        return $this->find($sql);
    }

    /**
     * @param $userData
     * @return mixed
     * 删除广告
     */
    public function del($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I('id');
        $res = M('ads')->where(['adId' => $id])->find();
        if (empty($res)) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $rs = $this->delete((int)I('id'));
        if (false !== $rs) {
            $describe = "[{$userData['loginName']}]删除了广告:[{$res['adName']}]";
            addOperationLog($userData['loginName'], $userData['staffId'], $describe, 2);
            $rd = returnData(true, 0, 'success', '操作成功');
        }
        return $rd;
    }
}