<?php
/**
 * 银行信息
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-04
 * Time: 10:01
 */

namespace App\Modules\Banks;


use App\Models\BanksModel;

class BanksModule
{
    /**
     * 银行卡-详情-id查找
     * @param int $bankId 银行卡id
     * @return array
     * */
    public function getBankDetialById(int $bankId)
    {
        $model = new BanksModel();
        $where = array(
            'bankId' => $bankId,
            'bankFlag' => 1,
        );
        $result = $model->where($where)->field('bankId,bankName')->find();
        return (array)$result;
    }
}