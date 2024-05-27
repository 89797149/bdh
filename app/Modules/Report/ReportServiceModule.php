<?php

namespace App\Modules\Report;

use App\Models\BaseModel;

/**
 * 报表服务类
 * 统一提供给内部其他模块使用的报表服务类
 * Class ReportServiceModule
 * @package App\Modules\Report
 */
class ReportServiceModule extends BaseModel
{

    /**
     * 根据参数获取报表总表统计信息
     * @param array $params <p>
     * int shopId 门店id
     * int reportId 报表id
     * date reportDate 报表日期
     * </p>
     * @param string $field 表字段
     * @return array
     * */
    public function getReportInfoByParams(array $params, string $field)
    {
        $module = new ReportModule();
        $data = $module->getReportInfoByParams($params, $field);
        return $data;
    }

    /**
     * 根据报表id获取报表详情
     * @param int $reportId 报表id
     * @param string $field 表字段
     * @return array
     * */
    public function getReportInfoById(int $reportId, string $field)
    {
        $module = new ReportModule();
        $data = $module->getReportInfoById($reportId, $field);
        return $data;
    }
}