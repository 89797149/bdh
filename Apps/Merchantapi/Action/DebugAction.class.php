<?php
/**
 * 调试
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-05-21
 * Time: 14:54
 */

namespace Merchantapi\Action;


use App\Models\GoodsModel;
use App\Models\UserAddressModel;
use App\Modules\Map\MapModule;
use App\Modules\Users\UsersModule;

class DebugAction extends BaseAction
{
//    public function debug()
//    {
//        $goods_model = M('goods');
//        $where = array(
//            'shopId' => 1,
//            'goodsFlag' => 1,
//        );
//        $goods_list = $goods_model->where($where)->field('goodsId,goodsImg')->select();
//        $ga_model = M('goods_gallerys');
//        foreach ($goods_list as $item) {
//            $ga_data = $ga_model->where(array(
//                'goodsId' => $item['goodsId']
//            ))->select();
//            if (empty($ga_data)) {
//                if (!empty($item['goodsImg'])) {
//                    $ga_model->add(
//                        array(
//                            'goodsId' => $item['goodsId'],
//                            'shopId' => 1,
//                            'goodsImg' => $item['goodsImg'],
//                            'goodsThumbs' => $item['goodsImg'],
//                        )
//                    );
//                }
//            }
//        }
//        echo "OK";
//    }


    public function repairUserAddressLatLng()
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 10);
        $isUpdate = (int)I('isUpdate', 0);//是否更新经纬度
        $userAddressMod = new UserAddressModel();
        $userAddressWhere = array(
            'addressFlag' => 1
        );
        $allAddressIdArr = $userAddressMod->where($userAddressWhere)->field('userId,addressId')->limit(($page - 1) * $pageSize, $pageSize)->select();
        $usersModule = new UsersModule();
        $mapModule = new MapModule();
        foreach ($allAddressIdArr as $allAddressIdRow) {
            $userId = $allAddressIdRow['userId'];
            $addressId = $allAddressIdRow['addressId'];
            $addressRow = $usersModule->getUserAddressDetail($userId, $addressId);
            if (empty($addressRow)) {
                continue;
            }
            $addressRow['detailAddress'] = '';
            if (handleCity($addressRow['areaId1Name'])) {
                $addressRow['detailAddress'] .= $addressRow['areaId1Name'];
            }
            $addressRow['detailAddress'] .= $addressRow['areaId2Name'] . $addressRow['areaId3Name'];
            $del_str = $addressRow['detailAddress'];
            if (!empty($addressRow['setaddress'])) {
                $addressRow['detailAddress'] .= $addressRow['setaddress'];
            }
            $detailAddressArr = explode($del_str, $addressRow['detailAddress']);
            if (count($detailAddressArr) > 2) {
                $addressRow['detailAddress'] = $del_str . $detailAddressArr[2];
            }
            $gaodeAddressRow = $mapModule->mapPlaceByKeywords($addressRow['detailAddress'], $addressRow['areaId2Name']);
            if (empty($gaodeAddressRow['pois'])) {
                continue;
            }
            $pois0 = $gaodeAddressRow['pois'][0];//取第一个最近的位置
            if ($isUpdate == 1) {
                $userAddressMod->where(array('addressId' => $addressId))->save(array('lat' => $pois0['lat'], 'lng' => $pois0['lng'])); //更新地址的经纬度
            }
            $debugDescribe = "地址：{$addressRow['detailAddress']}：以前：lat#{$addressRow['lat']},lng#{$addressRow['lng']}|现在：lat#{$pois0['lat']},lng#{$pois0['lng']}";
            dump($debugDescribe);
        }
        dd('done');
    }


    public function repairNotify()
    {
        $notify_log_tab = M('notify_log');
        $notify_log_list = $notify_log_tab->select();
        foreach ($notify_log_list as $notify_log_list_row) {
            if (!empty($notify_log_list_row['userId'])) {
                continue;
            }
            $request_json_decode = json_decode($notify_log_list_row['requestJson'], true);
            $notify_log_tab->where(array('id' => $notify_log_list_row['id']))->save(array('userId' => $request_json_decode['userId']));
        }
        dd("处理结束");

    }
}