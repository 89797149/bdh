<?php

namespace Adminapi\Action;

use Adminapi\Model\AreasModel;
use Adminapi\Model\IndexModel;
use Adminapi\Model\StaffsModel;

class IndexAction extends BaseAction
{
    public function index()
    {
        $this->isLogin();
        $this->display("/index");
    }

    public function toMain()
    {
        $data = $this->isLogin();
        $m = D('Index');
        $staffsModel = D('Adminapi/Staffs');
        $res = $staffsModel->getStaffDataFind($data['staffId']);
        if ($res) {
            $data['lastTime'] = $res;
        }
        $weekInfo = $m->getWeekInfo();
        $sumInfo = $m->getSumInfo();
        $result = array('data' => $data, 'weekInfo' => $weekInfo, 'sumInfo' => $sumInfo);
        $this->returnResponse(0, '操作成功', $result);
    }

    public function toMallConfig()
    {
        $this->isLogin();
        $indexModel = new IndexModel();
        $parentId = (int)I('parentId', 0);
        $where = [];
        $where['fieldCode'] = 'authorization_code';
        $saveData = [];
        $domain = WSTDomain();
        $saveData['fieldTips'] = "<span style='color:red'>微信公众号总管理账号<span><a href='{$domain}/Adminapi/LivePlay/auth'>点击授权微信</a>";
        M('sys_configs')->where($where)->save($saveData);
        $list = array('configs' => array_values((array)$indexModel->loadConfigsForParent($parentId)),);
        //dump($list);
        $this->returnResponse(0, '操作成功', $list);
    }

    public function saveMallConfig()
    {
        $this->isLogin();
        $m = new IndexModel();
        $rs = $m->saveConfigsForCode();
        $this->ajaxReturn($rs);
    }

    public function toLogin()
    {
        $this->display("/login");
    }

    public function login()
    {
        $m = new StaffsModel();
        $rs = $m->login();
        if ($rs['code'] == 0) {
            session('WST_STAFF', $rs['data']['staff']);
            unset($rs['data']['staff']);
        }
        $this->ajaxReturn($rs);
    }

    public function logout()
    {
        session('WST_STAFF', null);
        $this->ajaxReturn(array('code' => 0, 'msg' => '操作成功'));
    }

    public function getTask()
    {
        $this->isLogin();
        $m = D('Adminapi/Goods');
        $grs = $m->queryPenddingGoodsNum();
        $m = D('Adminapi/Shops');
        $srs = $m->queryPenddingShopsNum();
        $rd = array('status' => 1);
        $rd['goodsNum'] = $grs['num'];
        $rd['shopsNum'] = $srs['num'];
        $this->ajaxReturn($rd);
    }

    public function getWSTMallVersion()
    {
        $this->isLogin();
        $version = C('WST_VERSION');
        $key = C('WST_MD5');
        $license = $GLOBALS['CONFIG']['mallLicense'];
        $url = C('WST_WEB') . '/Authority/auz/version/' . $version . '/version_md5/' . $key . "/host/" . NiaoRootDomain();
        $content = file_get_contents($url);
        $json = json_decode(trim($content, chr(239) . chr(187) . chr(191)), true);
        if ($json['version'] == $version) {
            $json['version'] = "same";
        }
        $this->ajaxReturn($json);
    }

    public function enterLicense()
    {
        $this->isLogin();
        $this->display("/enter_license");
    }

    public function verifyLicense()
    {
        $this->isLogin();
        $license = I('license');
        $content = file_get_contents(C('WST_WEB') . '/Authority/verifyLicense/host/' . NiaoRootDomain() . '/license/' . $license);
        $json = json_decode(trim($content, chr(239) . chr(187) . chr(191)), true);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => array());
        if ($json['status'] == 1) {
            $rs = D('Adminapi/Index')->saveLicense();
        }
        $rs['data']['license'] = $json;
        $this->ajaxReturn($rs);
    }

    public function getLicenseWeisite()
    {
        $this->isLogin();
        $data = array('url' => WSTRootDomain());
        $this->returnResponse(0, '操作成功', $data);
    }

    public function cleanAllCache()
    {
        $this->isLogin();
        $rv = array('status' => -1);
        $rv['status'] = WSTDelDir(C('WST_RUNTIME_PATH'));
        $this->returnResponse(0, '操作成功');
    }

    public function cleanData()
    {
        $this->isLogin();
        D('Adminapi/Index')->cleanData();
        $this->returnResponse(0, '操作成功');
    }

    public function getStatisticsInfo()
    {
        $this->isLogin();
        $m = new IndexModel();
        $rs = $m->getStatisticsInfo();
        $this->ajaxReturn(returnData($rs));
    }

    public function getGoodsSalesRanking()
    {
        $this->isLogin();
        $typeTime = (int)I('typeTime', 0);
        if (!empty($typeTime) && !in_array($typeTime, [1, 2, 3, 4, 5, 6])) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择可查询的时间区间'));
        }
        $m = new IndexModel();
        $rs = $m->getGoodsSalesRanking($typeTime);
        $this->ajaxReturn(returnData($rs));
    }

    public function getDealUserInfo()
    {
        $this->isLogin();
        $typeTime = (int)I('typeTime', 0);
        if (!empty($typeTime) && !in_array($typeTime, [1, 2, 3, 4, 5, 6])) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择可查询的时间区间'));
        }
        $m = new IndexModel();
        $rs = $m->getDealUserInfo($typeTime);
        $this->ajaxReturn(returnData($rs));
    }

    public function getOperationLogList()
    {
        $this->isLogin();
        $typeTime = (int)I('typeTime', 0);
        $operationType = (int)I('operationType', 0);
        $page = (int)I('page', 1, 'intval');
        $pageSize = (int)I('pageSize', 15, 'intval');
        if (!empty($typeTime) && !in_array($typeTime, [1, 2, 3, 4, 5, 6, 7])) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择可查询的时间区间'));
        }
        if ($typeTime == 7) {
            $startDate = I('startDate');
            $endDate = I('endDate');
            if (empty($startDate) || empty($endDate)) {
                $this->ajaxReturn(returnData(false, -1, 'error', '请选择自定义查询时间', '数据错误'));
            }
            $params['startDate'] = $startDate;
            $params['endDate'] = $endDate;
        }
        if (!empty($operationType) && !in_array($operationType, [1, 2, 3])) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择可查询的操作行为类型'));
        }
        $params = [];
        $params['typeTime'] = $typeTime;
        $params['operationType'] = $operationType;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $m = new IndexModel();
        $rs = $m->getOperationLogList($params);
        $this->ajaxReturn(returnData($rs));
    }
} ?>