<?php
/*
 * 中文转拼音
 * @param string $str
 * */
function MadeGetFirstCharter($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
    $s1 = iconv('UTF-8', 'gb2312', $str);
    $s2 = iconv('gb2312', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284) return 'A';
    if ($asc >= -20283 && $asc <= -19776) return 'B';
    if ($asc >= -19775 && $asc <= -19219) return 'C';
    if ($asc >= -19218 && $asc <= -18711) return 'D';
    if ($asc >= -18710 && $asc <= -18527) return 'E';
    if ($asc >= -18526 && $asc <= -18240) return 'F';
    if ($asc >= -18239 && $asc <= -17923) return 'G';
    if ($asc >= -17922 && $asc <= -17418) return 'H';
    if ($asc >= -17417 && $asc <= -16475) return 'J';
    if ($asc >= -16474 && $asc <= -16213) return 'K';
    if ($asc >= -16212 && $asc <= -15641) return 'L';
    if ($asc >= -15640 && $asc <= -15166) return 'M';
    if ($asc >= -15165 && $asc <= -14923) return 'N';
    if ($asc >= -14922 && $asc <= -14915) return 'O';
    if ($asc >= -14914 && $asc <= -14631) return 'P';
    if ($asc >= -14630 && $asc <= -14150) return 'Q';
    if ($asc >= -14149 && $asc <= -14091) return 'R';
    if ($asc >= -14090 && $asc <= -13319) return 'S';
    if ($asc >= -13318 && $asc <= -12839) return 'T';
    if ($asc >= -12838 && $asc <= -12557) return 'W';
    if ($asc >= -12556 && $asc <= -11848) return 'X';
    if ($asc >= -11847 && $asc <= -11056) return 'Y';
    if ($asc >= -11055 && $asc <= -10247) return 'Z';
    return null;
}

/*
 * 获取Python返回的数据 PS:针对删除|编辑|添加,防止后期返回结果变动
 * 注意:返回null也是成功,也存在其他不知道的值
 * */
function handlePythonRes($response){
    $result = true;
    if($response == true){
        return $result;
    }
    if(!is_null($response)){
        $result = false;
    }
    return $result;
}

/*
 *PDO连接sqlserver数据库
 * @param string $dbname PS:数据库名,如果不填的话,默认取配置中参数
 * */
function sqlServerDB($dbname){
    $sqlserverInfo = getDatabaseConfig();
    if(empty($dbname)){
        $dbname = $sqlserverInfo['dbname'];
    }
    try {
        //服务器
        $host = $sqlserverInfo['host'];
        $port = $sqlserverInfo['port'];
        $dbname = $dbname;
        $username = $sqlserverInfo['username'];
        $pw = $sqlserverInfo['pw'];
        $dbh = null;
        if(PATH_SEPARATOR==':'){
            $dbh = new PDO ("dblib:host=$host:$port;dbname=$dbname","$username","$pw");
        }else{
            $dbh = new PDO("sqlsrv:Server=$host,$port;Database=$dbname",$username,$pw);
        }
        return $dbh;
    } catch (PDOException $e) {
        echo "Failed to get DB handle: " . $e->getMessage() . "\n";
        exit;
    }
}

/*
 * 处理数据
 * @param array $data
 * @param int $type PS:返回数据类型['all':全部 | 'row':单条 ]
 * */
function hanldeSqlServerData($conn,$type='all'){
    $result = [];
    while ($row = $conn->fetch(\PDO::FETCH_ASSOC)) {
        $result[] = $row;
    }
    unset($dbh); unset($conn);
    $data = [];
    if($type == 'all'){
        $data = $result;
    }elseif($type == 'row'){
        $data = $result[0];
    }
    return $data;
}
/*
 * 连接数据库
 * @param string $tableName PS:表名
 * @param string $prefix PS:前缀
 * @param string $db PS:数据库
 * */
function madeDB($tableName,$prefix='',$db=''){
    $response['code'] = -1;
    $response['msg'] = '错误信息';
    if(empty($tableName)){
        $response['msg'] = '表名不能为空';
        return $response;
    }
    if(empty($db)){
        $db = C('made_db');
    }
    $mod = M($tableName,$prefix,$db);
    return $mod;
}

/*
 * 执行sql
 *@param string $sql
 * */
function sqlExcute($sql){
    if(empty($sql)){
        return false;
    }
    /*$db = sqlServerDB();
    $conn = $db->prepare($sql);
    return $conn->execute();*/
    $db = connectSqlServer();
    $res = handleReturnData($db->sqlExcute(getDatabaseConfig(),$sql),'write');
    return $res;
}

/*
 * 查询sql
 *@param string $sql
 *@param string $type PS:['all':全部|'row':单条]
 * */
function sqlQuery($sql,$type='all'){
    if(empty($sql)){
        return false;
    }
    /*$db = sqlServerDB();
    $conn = $db->prepare($sql);
    $conn->execute();
    $result = hanldeSqlServerData($conn,$type);*/
    $db = connectSqlServer();
    $result = handleReturnData($db->sqlQuery(getDatabaseConfig(),$sql,$type));
    /*$config['config'] = C('sqlserver_db');
    $config['sql'] = $sql;
    $config['dataType'] = $type;
    $config['type'] = 'sqlQuery';//sqlExcute
    $p['body'] = serialize($config);
    $data = request_post("http://sql.srmyzx.com/sql.php",$p);
    $result = unserialize($data);*/
    return $result;
}

/*
 * 获取数库配置
 * */
function getDatabaseConfig(){
    $databaseConfig = C('sqlserver_db');
    $databaseConfigSet = M('sys_configs')->where(['fieldCode'=>'database'])->getField('fieldValue');
    $config = [];
    $config['host'] = $databaseConfig['host'];
    $config['port'] = $databaseConfig['port'];
    $config['username'] = $databaseConfig['username'];
    $config['pw'] = $databaseConfig['pw'];
    $config['dbname'] = $databaseConfig['dbname'.$databaseConfigSet];
    return $config;
}

/*
 * 获取insertId
 * @param string tableName
 * */
function sqlInsertId($tableName){
    if(empty($tableName)){
        return false;
    }
    /*$db = sqlServerDB();
    $sql = "SELECT IDENT_CURRENT('{$tableName}')";
    $conn = $db->prepare($sql);
    $conn->execute();
    $insertRow = hanldeSqlServerData($conn,'row');
    unset($conn);*/
    $db = connectSqlServer();
    $sql = "SELECT IDENT_CURRENT('{$tableName}')";
    $insertRow = handleReturnData($db->sqlQuery(getDatabaseConfig(),$sql,'row'));
    $insertId = $insertRow[''];
    return $insertId;
}

/*
 * 获取构建ptype数据基本字段信息
 * @param array $fieldArr
 * */
function getBuildField($fieldArr){
    $returnData = [
        'field' => '',
        'value' => '',
    ];
    //构建基本信息,PS:字段不要乱删,不知道删了哪个字段就不能用了
    $field = [];
    $field['parid'] = '';
    $field['leveal'] = 4;
    $field['sonnum'] = 0;
    $field['soncount'] = 0;
    $field['soncount'] = 0;
    $field['FullName'] = '';
    $field['PyCode'] = '';
    $field['UserCode'] = '';
    $field['name'] = '';
    $field['Standard'] = '';
    $field['type'] = '';
    $field['CreateDate'] = '';
    $field['UnitsType'] = 1;
    $field['BuyUnitId'] = 1;
    $field['baseUnitId'] = 1;
    $field['SaleUnitId'] = 1;
    $field['Unit1'] = 0;
    $field['UnitRate2'] = 0;
    $field['EntryCode'] = 0;
    $field['StopBuy'] = 0;
    $field['AssistantUnitId'] = 0;
    $field['OmPrice'] = 0;
    $field['weight'] = 0;
    $field['volume'] = 0;
    if(is_array($fieldArr) && !empty($fieldArr)){
        $fieldStr = '';
        $valueStr = '';
        foreach ($fieldArr as $key=>$value){
            $fieldStr .= $key.',';
            if(isset($field[$key])){
                $field[$key] = $value;
            }

            $valueStr .= "'".$value."',";
        }
        $fieldStr = trim($fieldStr,',');
        $valueStr = trim($valueStr,',');
        /*$value = "'{$field['parid']}','{$field['level']}','{$field['sonnum']}','{$field['soncount']}','{$field['FullName']}','{$field['PyCode']}','{$field['UserCode']}','{$field['name']}','{$field['Standard']}','{$field['type']}','{$field['CreateDate']}','{$field['UnitsType']}','{$field['BuyUnitId']}','{$field['baseUnitId']}','{$field['Unit1']}','{$field['UnitRate2']}','{$field['EntryCode']}','{$field['StopBuy']}','{$field['AssistantUnitId']}','{$field['OmPrice']}','{$field['weight']}','{$field['volume']}'";*/

        $returnData['field'] = $fieldStr;
        $returnData['value'] = $valueStr;
    }
    return $returnData;
}

/**
 * 分页函数
 * @param int $page 页码
 * @param int $pageSize 每页条数
 * @param string $tableName 表名
 * @param string $prekey 主键
 * @param string $where 条件
 * @param string $field 字段
 * @param string $orderBy 排序
 * @param int $debug 用于调试
 * @return array('total','pgeSize','start','root','totalPage','currPage');
 */
function sqlServerPageQuery($page = 0,$pageSize = 0,$tableName,$prekey,$where='',$field='*',$orderBy='',$debug=1){
    //$db = sqlServerDB();
    $db = connectSqlServer();
    $pageSize = $pageSize?$pageSize:I('pageSize',0);//二开
    $pageSize = (intval($pageSize)==0)?C('PAGE_SIZE'):$pageSize;
    if($pageSize==0)return array();
    $page = (intval($page)<=0)?I(C('VAR_PAGE'),1):intval($page);
    $page = ($page<=0)?1:$page;
    $start = ($page-1)*$pageSize;
    $end = $pageSize + $start;
    $pager = array();
    if(!empty($where)){
        $where = " AND ".$where;
    }
    //查询总数
    $totalSql = "SELECT COUNT($prekey) FROM $tableName WHERE 1=1 $where";
    /*$conn = $db->prepare($totalSql);
    $conn->execute();
    $total = hanldeSqlServerData($conn,'row');*/
    $total = handleReturnData($db->sqlQuery(getDatabaseConfig(),$totalSql,'row'));
    //查询数据
    $sql = "SELECT TOP $pageSize $field from $tableName where $prekey not in(select top $start $prekey from $tableName where 1=1 $where $orderBy) $where $orderBy ";
    $result = sqlQuery($sql);
    foreach ($result as $key=>$val)
    {
        if(isset($result[$key]['PosDataVersion'])){
            $result[$key]['PosDataVersion']    = iconv('gb2312','utf-8',$val['PosDataVersion']);
        }
    }
    //计算页码信息
    $pager['total'] = $total[''];
    $pager['pageSize'] = $pageSize;
    $pager['start'] = $start;
    $pager['root'] = $result;
    $pager['totalPage'] = ($pager['total']%$pageSize==0)?($pager['total']/$pageSize):(intval($pager['total']/$pageSize)+1);
    $pager['currPage'] = $page;
    return $pager;
}

/*
 *获取表字段
 * @param string $tableName
 * */
function getTableField($tableName){
    $returnData = [];
    $sql = "SELECT DATA_TYPE,COLUMN_NAME FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME='".$tableName."'";
    $data = sqlQuery($sql);
    if(!empty($data)){
        $returnData = $data;
    }
    return $returnData;
}

/*
 * 获取PyCode
 * @param string $name
 * */
function getPyCode($name){
    $str = '';
    if(empty($name)){
        return $str;
    }
    $str = \Made\Model\PinyinModel::getShortPinyin($name);
    return strtoupper($str);
}

/*
 * 新
 * 连接sqlServer
 * */
function connectSqlServer(){
    //$dbConfig = C('sqlserver_db');
    include_once dirname(dirname(dirname(__FILE__))).'/Made/Model/RpcClient.php';
    $address_array = array(
        'tcp://47.113.85.143:2015',
    );
    RpcClient::config($address_array);
    $rpcModel = RpcClient::instance('Sqlserver');
    return $rpcModel;
}

/*
 * 新
 * 处理sqlServer返回的数据
 * @param $data 返回的数据
 * @param string $type 操作类型(read:'读数据'|write:'写数据')
 * */
function handleReturnData($data,$type='read'){
    $res = $data['data'];
    if($type == 'write'){
        return $data['data'];
    }
    if(!$res){
        $res = [];
    }
    return (array)$res;
}

/**
 * ģ��post����url����
 * @param string $url
 * @param array $param
 */
function request_post($url = '', $param = []) {
    if (empty($url) || empty($param)) {
        return false;
    }
    $curlPost = $param;
    $ch = curl_init();//��ʼ��curl
    curl_setopt($ch, CURLOPT_URL,$url);//ָ����ַ
    curl_setopt($ch, CURLOPT_HEADER, 0);//����header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//Ҫ����Ϊ�ַ������������Ļ��
    curl_setopt($ch, CURLOPT_POST, 1);//post�ύ��ʽ
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//����curl
    curl_close($ch);
    return $data;
}




