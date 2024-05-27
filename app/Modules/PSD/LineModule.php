<?php
/**
 * 配送端-线路
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-06-19
 * Time: 13:37
 */

namespace App\Modules\PSD;


use App\Models\BaseModel;
use App\Models\LineModel;

class LineModule extends BaseModel
{
    /**
     * 线路-线路列表
     * @param array $params
     * -int shop_id 门店id
     * -string line_name 线路名称
     * -int use_page 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int page_size 分页条数
     * @return array $result
     * */
    public function getLineList(array $params)
    {
        $handle_params = array(
            'shop_id' => 0,
            'line_name' => '',
            'use_page' => 0,
            'page' => 1,
            'page_size' => 15,
        );
        parm_filter($handle_params, $params);
        $model = new LineModel();
        $shop_id = $params['shop_id'];
        $where = array(
            'shopId' => $shop_id,
            'dataFlag' => 1,
        );
        if (!empty($params['line_name'])) {
            $where['lineName'] = array('like', "%{$params['line_name']}%");
        }
        $field = 'lineId,lineName,createTime';
        if ($params['use_page'] == 1) {//有分页
            $page = $params['page'];
            $page_size = $params['page_size'];
            $sql = $model
                ->where($where)
                ->field($field)
                ->buildSql();
            $result = $this->pageQuery($sql, $page, $page_size);
        } else {//无分页
            $result = $model
                ->where($where)
                ->field($field)
                ->select();
        }
        return $result;
    }

    /**
     * 线路-线路列表
     * @param int $line_id 线路id
     * @return array
     * */
    public function getLineDetailById(int $line_id)
    {
        $model = new LineModel();
        $where = array(
            'lineId' => $line_id,
            'dataFlag' => 1,
        );
        $field = 'lineId,lineName,createTime';
        $result = $model->where($where)->field($field)->find();
        return $result;
    }
}