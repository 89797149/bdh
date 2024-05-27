<?php
/**
 * 运营后台职员
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-07
 * Time: 14:25
 */

namespace App\Modules\Staffs;


use App\Models\StaffsModel;

class StaffsModule
{
    /**
     * 职员-详情-id查找
     * @param int $staffId 职员id
     * @param string $field 表字段
     * @return array
     * */
    public function getStaffsDetailById(int $staffId, $field = '*')
    {
        $model = new StaffsModel();
        $where = array(
            'staffId' => $staffId,
            'staffFlag' => 1,
        );
        $result = $model->where($where)->field($field)->find();
        return (array)$result;
    }
}