<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 直播/短视频
 */
class LivePlayModel extends BaseModel
{
    /**
     * 添加直播
     * 文档链接地址:
     * @param array $requestParams
     * */
    public function addLiveplay(array $requestParams)
    {
        $date = date('Y-m-d H:i:s', time());
        $params = [];
        $params['name'] = null;
        $params['type'] = null;
        $params['shopId'] = 0;
        $params['goodsCatId3'] = null;
        $params['liveImgUrl'] = null;//封面图调用七牛云上传接口
        $params['videoUrl'] = null;
        $params['startDate'] = null;
        $params['endDate'] = null;
        $params['anchorName'] = null;
        $params['anchorWechat'] = null;
        $params['closeComment'] = 1;
        $params['liveSale'] = 1;
        $params['live_status'] = 0;
        $params['shopSort'] = 0;
        $params['storeSort'] = 0;
        $params['createTime'] = $date;
        $params['updateTime'] = $date;
        parm_filter($params, $requestParams);
        $params['coverImgUrl'] = '';
        $params['shareImgUrl'] = '';
        $params['feedsImgUrl'] = '';
        if (!empty($requestParams['coverImgMediaId'])) {
            $params['coverImgUrl'] = $this->getMediaUrl($requestParams['coverImgMediaId']);
        }
        if (!empty($requestParams['shareImgMediaId'])) {
            $params['shareImgUrl'] = $this->getMediaUrl($requestParams['shareImgMediaId']);
        }
        if (!empty($requestParams['feedsImgMediaId'])) {
            $params['feedsImgUrl'] = $this->getMediaUrl($requestParams['feedsImgMediaId']);
        }
        if (empty($params['type']) || !in_array($params['type'], [1, 2, 3, 4])) {
            return returnData(false, -1, 'error', '请选择直播类型');
        }
        if (empty($params['name'])) {
            return returnData(false, -1, 'error', '直播间名称/短视频标题不能为空');
        }
        if (mb_strlen($params['name'], 'gb2312') < 6 || mb_strlen($params['name'], 'gb2312') > 34) {
            return returnData(false, -1, 'error', '直播间名字，最短3个汉字，最长17个汉字');
        }
        if (empty($params['goodsCatId3'])) {
            return returnData(false, -1, 'error', '分类不能为空');
        }
        if (empty($params['liveImgUrl'])) {
            return returnData(false, -1, 'error', '封面图不能为空');
        }
        if (in_array($params['type'], [1, 2, 3])) {
            //直播
            if (empty($params['coverImgUrl'])) {
                return returnData(false, -1, 'error', '直播间背景图不能为空');
            }
            if (empty($params['shareImgUrl'])) {
                return returnData(false, -1, 'error', '直播间分享图不能为空');
            }
            if (empty($params['feedsImgUrl'])) {
                return returnData(false, -1, 'error', '购物直播频道封面图不能为空');
            }
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return returnData(false, -1, 'error', '直播开始时间和结束时间不能为空');
            }
            $startDateTime = strtotime($params['startDate']);
            $endDateTime = strtotime($params['endDate']);
            if ($startDateTime < (time() + 600) || $startDateTime > (time() + (3600 * 24 * 30 * 6))) {
                return returnData(false, -1, 'error', '直播开始时间必须在当前时间的10分钟后且不能在6个月后');
            }
            if ($endDateTime > ($endDateTime + (3600 * 24))) {
                return returnData(false, -1, 'error', '直播结束时间距开始时间不得超过24小时');
            }
            if (($endDateTime - $startDateTime) < 1800) {
                return returnData(false, -1, 'error', '开始时间和结束时间不得短于30分钟');
            }
            if (empty($params['anchorName']) || empty($params['anchorWechat'])) {
                return returnData(false, -1, 'error', '主播昵称或主播微信号不能为空');
            }
            if (mb_strlen($params['anchorName'], 'gb2312') < 4 || mb_strlen($params['anchorName']) > 30) {
                return returnData(false, -1, 'error', '主播昵称，最短2个汉字，最长15个汉字');
            }
            if (!preg_match('/^[a-zA-Z]{1}[-_a-zA-Z0-9]{5,19}$/', $params['anchorWechat'])) {
                return returnData(false, -1, 'error', '请输入正确格式的微信号');
            }
        } elseif (in_array($params['type'], [4])) {
            //短视频
            if (empty($params['videoUrl'])) {
                return returnData(false, -1, 'error', '视频链接不能为空');
            }
        }
        $checkGoodsCatRes = $this->getGoodsCat3Info($params['goodsCatId3']);
        if ($checkGoodsCatRes['code'] != 0) {
            return $checkGoodsCatRes;
        }
        $params['goodsCatId1'] = $checkGoodsCatRes['data']['goodsCatId1'];
        $params['goodsCatId2'] = $checkGoodsCatRes['data']['goodsCatId2'];
        $params['goodsCatId3'] = $checkGoodsCatRes['data']['goodsCatId3'];
        M()->startTrans();
        $liveplayTab = M('liveplay');
        $liveplayId = $liveplayTab->add($params);
        if (empty($liveplayId)) {
            M()->rollback();
            return returnData(false, -1, 'error', '添加失败');
        }
        if ($params['type'] == 1) {
            //小程序直播
            $liveplayModel = D('Home/LivePlay');
            $liveplayParams = [];
            $liveplayParams['name'] = $params['name'];
            $liveplayParams['coverImg'] = $requestParams['coverImgMediaId'];
//            $liveplayParams['anchorImg'] = $requestParams['shareImgMediaId'];
            $liveplayParams['shareImg'] = $requestParams['shareImgMediaId'];
            $liveplayParams['feedsImg'] = $requestParams['feedsImgMediaId'];
            $liveplayParams['startTime'] = strtotime($params['startDate']);
            $liveplayParams['endTime'] = strtotime($params['endDate']);
            $liveplayParams['anchorName'] = $params['anchorName'];
            $liveplayParams['anchorWechat'] = $params['anchorWechat'];
            $liveplayParams['type'] = 0;
            $liveplayParams['screenType'] = 0;
            $liveplayParams['closeLike'] = 0;
            $liveplayParams['closeGoods'] = 0;
            $liveplayParams['closeComment'] = $params['closeComment'] == 1 ? 0 : 1;
            $addWxLieveplayRes = $liveplayModel->addWxLiveplay($liveplayParams);
            if ($addWxLieveplayRes['errcode'] != 0) {
                M()->rollback();
                return returnData(false, -1, 'error', $addWxLieveplayRes['errmsg']);
            }
            $saveData = [];
            $saveData['roomId'] = $addWxLieveplayRes['roomId'];
            $saveLiveRes = $liveplayTab->where(['liveplayId' => $liveplayId])->save($saveData);
            if (!$saveLiveRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '添加失败，直播房间id保存失败');
            }
        }
        $requestParams['liveplayGoodsId'] = json_decode(htmlspecialchars_decode($requestParams['liveplayGoodsId']), true);
        $liveplayGoodsId = array_column($requestParams['liveplayGoodsId'], 'goodsId');
        if (!empty($liveplayGoodsId)) {
            //检测商品库商品的有效性
            $liveplayGoodsId = array_unique($liveplayGoodsId);
            $checkLiveplayGoodsRes = $this->checkLiveplayGoods($liveplayGoodsId);
            if ($checkLiveplayGoodsRes['code'] != 0) {
                M()->rollback();
                return $checkLiveplayGoodsRes;
            }
            $relationTab = M('liveplay_goods_relation');
            $where = [];
            $where['liveplayId'] = $liveplayId;
            $saveData = [];
            $saveData['dataFlag'] = -1;
            $relationTab->where($where)->save($saveData);
            $insertRelationData = [];
            $liveplayGoodsParams = arrayUnset($requestParams['liveplayGoodsId'], 'goodsId');
            $wxGoodsIdArr = [];
            foreach ($liveplayGoodsParams as $item) {
                $liveplayGoodsDetail = $this->getLiveplayGoodsDetail($item['goodsId'])['data'];
                if ($params['type'] == 1 && $liveplayGoodsDetail['examinePlatform'] != 1) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "商品【{$liveplayGoodsDetail['goodsName']}】不属于小程序直播商品");
                }
                $wxGoodsIdArr[] = $liveplayGoodsDetail['wxGoodsId'];
                $insertRelation = [];
                $insertRelation['liveplayId'] = $liveplayId;
                $insertRelation['goodsId'] = $item['goodsId'];
                $insertRelation['goodsSort'] = $item['goodsSort'];
                $insertRelation['createTime'] = date('Y-m-d H:i:s', time());
                $insertRelationData[] = $insertRelation;
            }
            $relationRes = $relationTab->addAll($insertRelationData);
            if (!$relationRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '添加直播商品失败');
            }
            $liveplayInfo = $this->getLiveplayDetail($liveplayId);
            if ($liveplayInfo['data']['type'] == 1) {
                $liveplayModel = D('Home/LivePlay');
                $liveplayGoodsParams = [];
                $liveplayGoodsParams['ids'] = $wxGoodsIdArr;
                $liveplayGoodsParams['roomId'] = $liveplayInfo['data']['roomId'];
                $addWxLieveplayGoodsRes = $liveplayModel->addWxLiveplayGoods($liveplayGoodsParams);
                if ($addWxLieveplayGoodsRes['errcode'] != 0) {
                    M()->rollback();
                    return returnData(false, -1, 'error', $addWxLieveplayGoodsRes['errmsg']);
                }
            }
        }
        M()->commit();
        return returnData(true);
    }


    /*
     * 更新直播信息
     * @param array $requestParams<p>
     * int shopId 门店id
     * int liveplayId 直播id
     * string liveImgUrl 封面图
     * int shopSort 门店直播排序
     * int liveSale 上架状态【0：下架|1：上架】
     * jsonString liveplayGoodsId 商品库商品信息 [{"goodsId":"222","goodsSort":"1"}]
     * </p>
     * */
    public function updateLiveplay(array $requestParams)
    {
        M()->startTrans();
        $liveplayId = $requestParams['liveplayId'];
        $params = [];
        $params['shopSort'] = null;
        $params['storeSort'] = null;
        $params['name'] = null;
        $params['liveImgUrl'] = null;
        $params['liveSale'] = null;
        $params['live_status'] = null;
        parm_filter($params, $requestParams);
        $params['updateTime'] = date('Y-m-d H:i:s', time());
        $liveplayInfo = $this->getLiveplayDetail($liveplayId)['data'];
        if ($liveplayInfo['type'] == 1) {
            //小程序直播暂不支持修改
            unset($params['name']);
        } elseif ($liveplayInfo['type'] == 4) {
            if ($params['live_status'] == 1) {
                $params['live_status'] = 2;
            }
        }
        $where = [];
        if (!empty($requestParams['shopId'])) {
            $where['shopId'] = $requestParams['shopId'];
        }
        $where['liveplayId'] = $liveplayId;
        $data = M('liveplay')->where($where)->save($params);
        if (!$data) {
            M()->rollback();
            return returnData(false, -1, 'error', '修改失败');
        }
        $liveplayGoodsId = array_column($requestParams['liveplayGoodsId'], 'goodsId');
        $relationTab = M('liveplay_goods_relation');
        $where = [];
        $where['liveplayId'] = $liveplayId;
        $saveData = [];
        $saveData['dataFlag'] = -1;
        $relationTab->where($where)->save($saveData);
        if (!empty($liveplayGoodsId)) {
            //检测商品库商品的有效性
            $liveplayGoodsId = array_unique($liveplayGoodsId);
            $checkLiveplayGoodsRes = $this->checkLiveplayGoods($liveplayGoodsId);
            if ($checkLiveplayGoodsRes['code'] != 0) {
                M()->rollback();
                return $checkLiveplayGoodsRes;
            }
            $insertRelationData = [];
            $liveplayGoodsParams = arrayUnset($requestParams['liveplayGoodsId'], 'goodsId');
            $wxGoodsIdArr = [];
            foreach ($liveplayGoodsParams as $item) {
                $liveplayGoodsDetail = $this->getLiveplayGoodsDetail($item['goodsId'])['data'];
                if ($liveplayInfo['type'] == 1 && $liveplayGoodsDetail['examinePlatform'] != 1) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "商品【{$liveplayGoodsDetail['goodsName']}】不属于小程序直播商品");
                }
                $wxGoodsIdArr[] = $liveplayGoodsDetail['wxGoodsId'];
                $insertRelation = [];
                $insertRelation['liveplayId'] = $liveplayId;
                $insertRelation['goodsId'] = (int)$item['goodsId'];
                $insertRelation['goodsSort'] = (int)$item['goodsSort'];
                $insertRelation['createTime'] = date('Y-m-d H:i:s', time());
                $insertRelationData[] = $insertRelation;
            }
            $relationRes = $relationTab->addAll($insertRelationData);
            if (!$relationRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '修改直播商品失败');
            }
            if ($liveplayInfo['type'] == 1) {
                $liveplayModel = D('Home/LivePlay');
                $liveplayGoodsParams = [];
                $liveplayGoodsParams['ids'] = $wxGoodsIdArr;
                $liveplayGoodsParams['roomId'] = $liveplayInfo['roomId'];
                $addWxLieveplayGoodsRes = $liveplayModel->addWxLiveplayGoods($liveplayGoodsParams);
                if ($addWxLieveplayGoodsRes['errcode'] != 0) {
                    M()->rollback();
                    return returnData(false, -1, 'error', $addWxLieveplayGoodsRes['errmsg']);
                }
            }
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 获取微信媒体素材信息
     * @param string $mediaID
     * @return string $url
     * */
    public function getMediaUrl($mediaID)
    {
        $url = '';
        $mediaTab = M('liveplay_media');
        $where = [];
        $where['mediaID'] = $mediaID;
        $mediaInfo = $mediaTab->where($where)->find();
        if (!empty($mediaInfo)) {
            $url = $mediaInfo['qiniuImageUrl'];
        }
        return $url;
    }

    /**
     * 获取门店详情
     * @param int $shopId 门店id
     * */
    public function getShopInfo(int $shopId)
    {
        $shopTab = M('shops');
        $where = [];
        $where['shopFlag'] = 1;
        $where['shopId'] = $shopId;
        $shopInfo = $shopTab->where($where)->find();
        return (array)$shopInfo;
    }

    /**
     * 获取商品三级分类详情
     * @param int $goodsCatId3
     * @return array $data
     * */
    public function getGoodsCat3Info($goodsCatId3)
    {
        $goodsCatTab = M('goods_cats');
        $where = [];
        $where['catId'] = (int)$goodsCatId3;
        //$where['catFlag'] = 1;
        $goodsCat3Info = $goodsCatTab->where($where)->find();//第三级分类
        if (empty($goodsCat3Info)) {
            return returnData(false, -1, 'error', '传入的第三级分类有误');
        }
        $where = [];
        $where['catId'] = $goodsCat3Info['parentId'];
        $where['catFlag'] = 1;
        $goodsCat2Info = $goodsCatTab->where($where)->find();//第二级分类
        if (empty($goodsCat2Info)) {
            return returnData(false, -1, 'error', '传入的第三级分类有误');
        }
        $where = [];
        $where['catId'] = $goodsCat2Info['parentId'];
        $where['catFlag'] = 1;
        $goodsCat1Info = $goodsCatTab->where($where)->find();//第一级分类
        if (empty($goodsCat1Info)) {
            return returnData(false, -1, 'error', '传入的第三级分类有误');
        }
        $goodsCat3Info['goodsCatId1'] = $goodsCat1Info['catId'];
        $goodsCat3Info['goodsCatId1Name'] = $goodsCat1Info['catName'];
        $goodsCat3Info['goodsCatId2'] = $goodsCat2Info['catId'];
        $goodsCat3Info['goodsCatId2Name'] = $goodsCat2Info['catName'];
        $goodsCat3Info['goodsCatId3'] = $goodsCat3Info['catId'];
        $goodsCat3Info['goodsCatId3Name'] = $goodsCat3Info['catName'];
        return returnData($goodsCat3Info);
    }

    /**
     * 获取店铺商品二级分类详情
     * @param int $shopCatId2 二级店铺分类
     * @return array $data
     * */
    public function getShopGoodsCat2Info($goodsCatId2)
    {
        $shopCatsTab = M('shops_cats');
        $where = [];
        $where['catId'] = (int)$goodsCatId2;
        //$where['catFlag'] = 1;
        $goodsCat2Info = $shopCatsTab->where($where)->find();//第二分类
        $goodsCat1Info = [];
        if (!empty($goodsCat2Info)) {
            $where = [];
            $where['catId'] = $goodsCat2Info['parentId'];
            $where['catFlag'] = 1;
            $goodsCat1Info = $shopCatsTab->where($where)->find();//第一级分类
        }
        $goodsCat2Info['shopCatId1'] = $goodsCat1Info['catId'];
        $goodsCat2Info['shopCatId1Name'] = $goodsCat1Info['catName'];
        $goodsCat2Info['shopCatId2'] = $goodsCat2Info['catId'];
        $goodsCat2Info['shopCatId2Name'] = $goodsCat2Info['catName'];
        return returnData($goodsCat2Info);
    }

    /**
     * 校验商品库商品是否正确
     * @param array $liveplayGoodsId 商品库商品id
     * @return array $data
     * */
    public function checkLiveplayGoods(array $liveplayGoodsId)
    {
        $where = [];
        $where['lp_goods.liveplayGoodsId'] = ['IN', $liveplayGoodsId];
        $where['lp_goods.dataFlag'] = 1;
        $field = 'lp_goods.status';
        $field .= ',goods.goodsId,goods.goodsName,goods.goodsFlag,goods.isSale';
        $goodsList = M('liveplay_goods lp_goods')
            ->join('left join wst_goods goods on goods.goodsId=lp_goods.goodsId')
            ->where($where)
            ->field($field)
            ->select();
        foreach ($goodsList as $value) {
            if ($value['goodsFlag'] != 1 || $value['isSale'] != 1 || $value['status'] != 2) {
                return returnData(false, -1, 'error', "商品【{$value['goodsName']}】已被删除下架或审核不通过");
            }
        }
        return returnData(true);
    }

    /**
     * 商品库-商品详情
     * @param int $goodsId 门店商品id
     * @param int $liveplayGoodsId 商品库自增id
     * @return array $goodsInfo
     * */
    public function getLiveplayGoodsDetail(int $goodsId, $liveplayGoodsId = 0)
    {
        $where = [];
        if (!empty($goodsId)) {
            $where['lp_goods.goodsId'] = $goodsId;
        }
        if (!empty($liveplayGoodsId)) {
            $where['lp_goods.liveplayGoodsId'] = $liveplayGoodsId;
        }
        $where['lp_goods.dataFlag'] = 1;
        $field = 'lp_goods.goodsId,lp_goods.wxGoodsId,lp_goods.goodsImgUrl,lp_goods.examinePlatform,lp_goods.status,lp_goods.goodsDetailUrl,lp_goods.createTime';
        $data = M('liveplay_goods lp_goods')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($data)) {
            return returnData([]);
        }
        $where = [];
        $where['goodsId'] = $data['goodsId'];
        $goodsInfo = M('goods')->where($where)->find();
        if (empty($goodsInfo)) {
            return returnData([]);
        }
        $goodsCatInfo = $this->getGoodsCat3Info($goodsInfo['goodsCatId3'])['data'];
        $goodsInfo['goodsCatId1Name'] = $goodsCatInfo['goodsCatId1Name'];
        $goodsInfo['goodsCatId2Name'] = $goodsCatInfo['goodsCatId2Name'];
        $goodsInfo['goodsCatId3Name'] = $goodsCatInfo['goodsCatId3Name'];
        $goodsShopCatInfo = $this->getShopGoodsCat2Info($goodsInfo['shopCatId2'])['data'];
        $goodsInfo['shopCatId1Name'] = $goodsShopCatInfo['shopCatId1Name'];
        $goodsInfo['shopCatId2Name'] = $goodsShopCatInfo['shopCatId2Name'];
        $goodsInfo['goodsImgUrl'] = $data['goodsImgUrl'];
        $goodsInfo['status'] = $data['status'];
        $goodsInfo['goodsDetailUrl'] = $data['goodsDetailUrl'];
        $goodsInfo['createTime'] = $data['createTime'];
        $goodsInfo['wxGoodsId'] = $data['wxGoodsId'];
        $goodsInfo['examinePlatform'] = $data['examinePlatform'];
        return returnData($goodsInfo);
    }

    /**
     * 直播/短视频详情
     * @param int $liveplayId
     * @return array $data
     * */
    public function getLiveplayDetail(int $liveplayId)
    {
        $where = " liveplayId={$liveplayId} ";
        $field = 'liveplayId,shopId,roomId,name,type,goodsCatId1,goodsCatId2,goodsCatId3,liveImgUrl,coverImgUrl,shareImgUrl,videoUrl,feedsImgUrl,pageView,likenumInt,commentNumber,startDate,endDate,anchorName,anchorWechat,closeComment,live_status,liveSale,shopSort,storeSort,createTime';
        $data = M('liveplay')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($data)) {
            return returnData([]);
        }
        $data['shopName'] = $this->getShopInfo($data['shopId'])['shopName'];
        $goodsCatInfo = $this->getGoodsCat3Info($data['goodsCatId3']);
        $data['goodsCatId1Name'] = (string)$goodsCatInfo['data']['goodsCatId1Name'];
        $data['goodsCatId2Name'] = (string)$goodsCatInfo['data']['goodsCatId2Name'];
        $data['goodsCatId3Name'] = (string)$goodsCatInfo['data']['goodsCatId3Name'];
        $where = " lp_relation.liveplayId={$liveplayId} and lp_relation.dataFlag=1 and lp_goods.dataFlag=1";
        $field = " lp_goods.status,lp_goods.createTime";
        $field .= ",goods.goodsId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.marketPrice,goods.shopPrice";
        $field .= ',lp_relation.goodsSort';
        $goods = M('liveplay_goods_relation lp_relation')
            ->join("left join wst_liveplay_goods lp_goods on lp_goods.goodsId=lp_relation.goodsId")
            ->join("left join wst_goods goods on goods.goodsId=lp_goods.goodsId")
            ->where($where)
            ->field($field)
            ->group("goods.goodsId")
            ->order('lp_relation.goodsSort desc')
            ->select();
        $data['goods'] = (array)$goods;
        return returnData($data);
    }

    /**
     * 删除直播/短视频
     * @param array $liveplayId 直播id
     * @return array $data
     * */
    public function delLiveplay($liveplayId)
    {
        $where = [];
        $where['liveplayId'] = ['IN', $liveplayId];
        $where['dataFlag'] = 1;
        $saveData = [];
        $saveData['dataFlag'] = -1;
        $saveData['updateTime'] = date('Y-m-d H:i:s', time());
        $data = M('liveplay')->where($where)->save($saveData);
        if (!$data) {
            return returnData(false, -1, 'error', '删除失败');
        }
        return returnData(true);
    }

    /**
     * 直播/短视频-批量上下架/批量禁用
     * @param array $liveplayId 直播id
     * @param array $liveSale 上架状态【-1:禁用|0：下架|1：上架】
     * @return array $data
     * */
    public function bathActionSale($liveplayId, $liveSale)
    {
        $where = [];
        $where['liveplayId'] = ['IN', $liveplayId];
        $where['dataFlag'] = 1;
        $saveData = [];
        if (in_array($liveSale, [0, 1])) {
            $saveData['liveSale'] = $liveSale;
        } elseif (in_array($liveSale, [-1])) {
            $saveData['live_status'] = -1;
        }
        $saveData['updateTime'] = date('Y-m-d H:i:s', time());
        $data = M('liveplay')->where($where)->save($saveData);
        if (!$data) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData($data);
    }

    /**
     * 获取直播列表
     * @param array $requestParams <p>
     *int shopId 门店id
     *string name 直播名称/短视频标题
     *int type 直播类型【1:小程序直播|2:系统原生直播|3:第三方推流直播|4:短视频】
     *int goodsCatId1 一级商品分类id
     *int goodsCatId2 二级分类id
     *int goodsCatId3 三级分类id
     *string anchorName 主播昵称
     *string anchorWechat 主播微信号
     *int live_status 直播状态【-1:禁播|0:待审核|1:即将开始|2:正在直播|3:已结束】
     *int liveSale 上架状态【0：下架|1：上架】
     *datetime startDate 添加时间-开始时间
     *datetime endDate 添加时间-结束时间
     * </p>
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     * @return array $data
     * */
    public function getLiveplayList(array $params, $page = 1, $pageSize = 15)
    {
        $where = " lp.dataFlag=1 ";
        $whereFind = [];
        $whereFind['shop.shopName'] = function () use ($params) {
            if (empty($params['shopName'])) {
                return null;
            }
            return ['like', "%{$params['shopName']}%", 'and'];
        };
        $whereFind['lp.name'] = function () use ($params) {
            if (empty($params['name'])) {
                return null;
            }
            return ['like', "%{$params['name']}%", 'and'];
        };
        $whereFind['lp.type'] = function () use ($params) {
            if (!is_numeric($params['type'])) {
                return null;
            }
            return ['=', "{$params['type']}", 'and'];
        };
        $whereFind['lp.goodsCatId1'] = function () use ($params) {
            if (!is_numeric($params['goodsCatId1'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId1']}", 'and'];
        };
        $whereFind['lp.goodsCatId2'] = function () use ($params) {
            if (!is_numeric($params['goodsCatId2'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId2']}", 'and'];
        };
        $whereFind['lp.goodsCatId3'] = function () use ($params) {
            if (!is_numeric($params['goodsCatId3'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId3']}", 'and'];
        };
        $whereFind['lp.anchorName'] = function () use ($params) {
            if (empty($params['anchorName'])) {
                return null;
            }
            return ['like', "%{$params['anchorName']}%", 'and'];
        };
        $whereFind['lp.anchorWechat'] = function () use ($params) {
            if (empty($params['anchorWechat'])) {
                return null;
            }
            return ['like', "%{$params['anchorWechat']}%", 'and'];
        };
        $whereFind['lp.live_status'] = function () use ($params) {
            if (!is_numeric($params['live_status'])) {
                return null;
            }
            return ['=', "{$params['live_status']}", 'and'];
        };
        $whereFind['lp.liveSale'] = function () use ($params) {
            if (!is_numeric($params['liveSale'])) {
                return null;
            }
            return ['=', "{$params['liveSale']}", 'and'];
        };
        $whereFind['lp.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = 'lp.*';
        $sql = "select {$field} from __PREFIX__liveplay lp left join __PREFIX__shops shop on shop.shopId=lp.shopId where {$whereInfo} order by liveplayId desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as &$value) {
                $goodsCatInfo = $this->getGoodsCat3Info($value['goodsCatId3']);
                $value['shopName'] = $this->getShopInfo($value['shopId'])['shopName'];
                $value['goodsCatId1Name'] = (string)$goodsCatInfo['data']['goodsCatId1Name'];
                $value['goodsCatId2Name'] = (string)$goodsCatInfo['data']['goodsCatId2Name'];
                $value['goodsCatId3Name'] = (string)$goodsCatInfo['data']['goodsCatId3Name'];
            }
            unset($value);
            $data['root'] = $root;
        }
        return returnData((array)$data);
    }

    /**
     *获取门店商品库-商品列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/ipbx5k
     * */
    public function getLiveplayGoodsList(array $params, $page = 1, $pageSize = 15)
    {
        $where = " lp_goods.dataFlag=1 and goods.goodsFlag=1  ";
        $whereFind = [];
        $whereFind['goods.shopId'] = function () use ($params) {
            if (empty($params['shopId'])) {
                return null;
            }
            return ['=', "{$params['shopId']}", 'and'];
        };
        $whereFind['goods.goodsName'] = function () use ($params) {
            if (empty($params['goodsName'])) {
                return null;
            }
            return ['like', "%{$params['goodsName']}%", 'and'];
        };
        $whereFind['shop.shopName'] = function () use ($params) {
            if (empty($params['shopName'])) {
                return null;
            }
            return ['like', "%{$params['shopName']}%", 'and'];
        };
        $whereFind['lp_goods.status'] = function () use ($params) {
            if (!is_numeric($params['status'])) {
                return null;
            }
            return ['=', "{$params['status']}", 'and'];
        };
        $whereFind['lp_goods.examinePlatform'] = function () use ($params) {
            if (!is_numeric($params['examinePlatform'])) {
                return null;
            }
            return ['=', "{$params['examinePlatform']}", 'and'];
        };
        $whereFind['goods.goodsCatId1'] = function () use ($params) {
            if (!is_numeric($params['goodsCatId1'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId1']}", 'and'];
        };
        $whereFind['goods.goodsCatId2'] = function () use ($params) {
            if (!is_numeric($params['goodsCatId2'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId2']}", 'and'];
        };
        $whereFind['goods.goodsCatId3'] = function () use ($params) {
            if (!is_numeric($params['goodsCatId3'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId3']}", 'and'];
        };
        $whereFind['goods.shopCatId1'] = function () use ($params) {
            if (!is_numeric($params['shopCatId1'])) {
                return null;
            }
            return ['=', "{$params['shopCatId1']}", 'and'];
        };
        $whereFind['goods.shopCatId2'] = function () use ($params) {
            if (!is_numeric($params['shopCatId2'])) {
                return null;
            }
            return ['=', "{$params['shopCatId2']}", 'and'];
        };
        $whereFind['lp_goods.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = 'goods.goodsId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.brandId,goods.shopId,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.isBook,goods.bookQuantity,goods.warnStock,goods.goodsUnit,goods.goodsSpec,goods.isSale,goods.isBest,goods.isHot,goods.isRecomm,goods.isNew,goods.isAdminBest,goods.isAdminRecom,goods.recommDesc,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3,goods.shopCatId1,goods.shopCatId2,goods.goodsDesc,goods.isShopRecomm,goods.isIndexRecomm,goods.isActivityRecomm,goods.isInnerRecomm,goods.goodsStatus,goods.saleTime,goods.attrCatId,goods.goodsKeywords,goods.goodsFlag,goods.statusRemarks,goods.isShopSecKill,goods.ShopGoodSecKillStartTime,goods.ShopGoodSecKillEndTime,goods.isAdminShopSecKill,goods.AdminShopGoodSecKillStartTime,goods.AdminShopGoodSecKillEndTime,goods.AdminSecKillSort,goods.shopSecKillSort,goods.isShopPreSale';
        $field .= ',lp_goods.liveplayGoodsId,lp_goods.goodsImgUrl,lp_goods.goodsDetailUrl,lp_goods.status,lp_goods.createTime,lp_goods.examinePlatform';
        $field .= ',shop.shopName';
        $sql = "select {$field} from __PREFIX__liveplay_goods lp_goods left join __PREFIX__goods goods on goods.goodsId=lp_goods.goodsId left join __PREFIX__shops shop on shop.shopId=goods.shopId where {$whereInfo} order by lp_goods.liveplayGoodsId desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data)) {
            $root = $data['root'];
            foreach ($root as &$value) {
                $goodsCatInfo = $this->getGoodsCat3Info($value['goodsCatId3']);
                $value['goodsCatId1Name'] = (string)$goodsCatInfo['data']['goodsCatId1Name'];
                $value['goodsCatId2Name'] = (string)$goodsCatInfo['data']['goodsCatId2Name'];
                $value['goodsCatId3Name'] = (string)$goodsCatInfo['data']['goodsCatId3Name'];
                $goodsShopCatInfo = $this->getShopGoodsCat2Info($value['shopCatId2']);
                $value['shopCatId1Name'] = (string)$goodsShopCatInfo['data']['shopCatId1Name'];
                $value['shopCatId2Name'] = (string)$goodsShopCatInfo['data']['shopCatId2Name'];
            }
            unset($value);
            $data['root'] = $root;
        }
        return returnData($data);
    }

    /**
     * 更新商品库商品状态 PS:小程序直播不可用
     * @param int liveplayGoodsId 商品库自增id
     * @param int status 审核状态【0：未审核|1：审核中|2:审核通过|3审核失败】
     * */
    public function updateLiveplayGoodsStatus(int $liveplayGoodsId, int $status)
    {
        $liveplayGoodsInfo = $this->getLiveplayGoodsDetail('', $liveplayGoodsId)['data'];
        if (empty($liveplayGoodsInfo)) {
            return returnData(false, -1, 'error', "请传入正确的商品库id");
        }
        if ($liveplayGoodsInfo['examinePlatform'] == 1) {
            return returnData(false, -1, 'error', "操作失败，该数据必须由微信平台审核");
        }
        $where = [];
        $where['liveplayGoodsId'] = $liveplayGoodsId;
        $where['dataFlag'] = 1;
        $saveData = [];
        $saveData['status'] = $status;
        $saveData['updateTime'] = date('Y-m-d H:i:s', time());
        $data = M('liveplay_goods')->where($where)->save($saveData);
        if (!$data) {
            return returnData(false, -1, 'error', "操作失败");
        }
        return returnData(true);
    }

    /**
     * 删除直播/短视频商品
     * @param int $liveplayId
     * @param array $goodsId
     * */
    public function delLiveplayGoodsRelation($liveplayId, $goodsId)
    {
        $where = [];
        $where['liveplayId'] = $liveplayId;
        $where['goodsId'] = ['IN', $goodsId];
        $where['dataFlag'] = 1;
        $saveData = [];
        $saveData['dataFlag'] = -1;
        $relationTab = M('liveplay_goods_relation');
        $res = $relationTab->where($where)->save($saveData);
        if (!$res) {
            return returnData(false, -1, 'error', "操作失败");
        }
        return returnData(true);
    }
}