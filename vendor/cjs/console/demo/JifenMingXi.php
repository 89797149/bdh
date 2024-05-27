<?php
/**
 * 积分明细
 */
class JifenMingXi {


    /**
     * 消耗积分来源明细
     * @param array $data
     * @return array
     */
    public static function XiaoHao($data = [])
    {
        $retDataField = [
                'iM1', //消耗1月分可过期积分
                'iM2', //消耗2月分可过期积分
                'iM3', //消耗3月分可过期积分
                'iM4', //消耗4月分可过期积分
                'iM5', //消耗5月分可过期积分
                'iM6', //消耗6月分可过期积分
                'iM7', //消耗7月分可过期积分
                'iM8', //消耗8月分可过期积分
                'iM9', //消耗9月分可过期积分
                'iM10', //消耗10月分可过期积分
                'iM11', //消耗11月分可过期积分
                'iM12', //消耗12月分可过期积分
                'iTotalM1_12', //消耗不可提现积分数(具有有效期的积分)，是本次消耗1~12月份的总和
                'iForEver', //消耗不可提现积分数(永久积分)， 来源如充值积分，充值赠送的，非充值的永久积分
                'iUsableCash', //消耗可提现积分数(永久积分)， 来源如：非充值得来的积分
                'sCode', //使用的积分策略sCode即行为代号
        ];
        if(!is_array($data)) {
            $data = [$data];
        }
        $retData = [];
        foreach ($data as $k=>$v) {
            if($k && in_array($k, $retDataField)) {
                $retData[$k] = $v;
            }
        }
        $retData['iPlusMinus'] = 2; //1增，2减
        return $retData;
    }

    /**
     * 增加积分来源明细
     * @param array $data
     * @return array
     */
    public static function Add($data = [])
    {
        $retDataField = [
            'iM1', //增加1月分可过期积分,不可提现
            'iM2', //增加2月分可过期积分,不可提现
            'iM3', //增加3月分可过期积分,不可提现
            'iM4', //增加4月分可过期积分,不可提现
            'iM5', //增加5月分可过期积分,不可提现
            'iM6', //增加6月分可过期积分,不可提现
            'iM7', //增加7月分可过期积分,不可提现
            'iM8', //增加8月分可过期积分,不可提现
            'iM9', //增加9月分可过期积分,不可提现
            'iM10', //增加10月分可过期积分,不可提现
            'iM11', //增加11月分可过期积分,不可提现
            'iM12', //增加12月分可过期积分,不可提现
            'iForEver', //增加不可提现积分数（永久积分）， 来源如充值积分，充值赠送的，非充值的永久积分
            'iUsableCash', //增加可提现积分积分数（永久积分）， 来源如：非充值得来的积分
            'sCode', //使用的积分策略sCode即行为代号
        ];
        if(!is_array($data)) {
            $data = [$data];
        }
        $retData = [];
        foreach ($data as $k=>$v) {
            if($k && in_array($k, $retDataField)) {
                $retData[$k] = $v;
            }
        }
        $retData['iPlusMinus'] = 1; //1增，2减
        return $retData;
    }


}

var_export(JifenMingXi::XiaoHao(['iM10'=>50, 'sCode'=>'A_TIXIAN']));
echo PHP_EOL;

var_export(JifenMingXi::Add(['iM12'=>100, 'sCode'=>'A_PAY']));
echo PHP_EOL;

