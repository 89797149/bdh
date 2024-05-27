<?php

use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsServiceModule;
use Think\Model;

/**
 * 接口规范约定 尽量在模型中使用
 * data 默认为null
 * code (成功0 失败-1)默认0 401:身份异常特别状态
 * status （error,success）默认success
 * msg 默认成功 面向用户友好提示
 * info 默认 ''  面向开发人员提示
 * returnData($data,-1,'error','失败','数据错误')
 */


function returnData($data = array(), $code = 0, $status = 'success', $msg = '成功', $info = '')
{
    $retrunData['data'] = $data;
    $retrunData['code'] = $code;
    $retrunData['status'] = $status;
    $retrunData['msg'] = $msg;
    $retrunData['info'] = $info;
    $retrunData['server_time'] = time();
    return $retrunData;
}

// 对某个数组或者对象添加kv或者修改值 支持匿名函数获取kv进行处理 对某个数组或者对象字段获取值 并通过匿名函数处理 改变值或重新计算
//还可以改造成为可以 可选项通过字符串data->list->...等方式来指定走到哪一层再去处理？未继续改造目前能用
function setObj(&$data, $key, $value = null, $func = null)
{
    if (gettype($data) != "array") {
        throw new Exception("Value must be array");
    }

    if (count($data) == count($data, 1)) {

        if (isset($data[$key])) {
            if (gettype($func) == "object") {

                $data[$key] = $func($key, $data[$key]);
            } else {
                $data[$key] = $value;
            }
        } else {
            $data[$key] = $value;
        }


    } else {
        foreach ($data as &$v) {

            if (isset($v[$key])) {
                if (gettype($func) == "object") {

                    $v[$key] = $func($key, $v[$key]);
                } else {
                    $v[$key] = $value;
                }
            } else {
                $v[$key] = $value;
            }
        }

    }


}

/**
 * 对查询条件进行拼装 自动去除无用字符串
 *千万注意如果你拼接的like %%中间没数据 就直接return null 即可
 * @param array $where 条件支持匿名函数 支持复杂条件
 * @return void
 */
function where(array &$where)
{
    $tmp = ' ';
    $where = array_filter($where, function ($v, $k) {
        if ($v === null or $v === '') {
            return false;
        }
        return true;

    }, ARRAY_FILTER_USE_BOTH);
    $count = count($where);
    $i = 0;
    foreach ($where as $k => $v) {
        $i += 1;
        if (gettype($v) == 'object') {
            $tmparr = $v();
            if (empty($tmparr)) {
                continue;
            }

            if ($i == $count) {
                array_pop($tmparr);
            }


            if (gettype($tmparr[1]) == 'string') {
                $tmparr[1] = "'{$tmparr[1]}'";
            }

            if (empty($tmparr[1])) {
                continue;
            }

            $tmp .= $k . ' ' . implode(' ', $tmparr) . ' ';

        } else {

            $v = trim($v);
            if ($v === null or $v === '') {
                continue;
            }

            if (gettype($v) == 'string') {
                $v = "'{$v}'";
            }
            if ($i == $count) {
                $tmp .= " {$k}={$v}";
            } else {
                $tmp .= "{$k}={$v} and ";

            }

        }
    }
    $where = $tmp;
}


/**
 * 判断是否手机访问
 */
function WSTIsMobile()
{
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        $mobile_browser++;
    if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;
    if (isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
    $mobile_agents = array(
        'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
        'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
        'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
        'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
        'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
        'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
        'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
        'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
        'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
    );
    if (in_array($mobile_ua, $mobile_agents)) $mobile_browser++;
    if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false) $mobile_browser++;
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false) $mobile_browser = 0;
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false) $mobile_browser++;
    if ($mobile_browser > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 邮件发送函数
 * @param string to      要发送的邮箱地址
 * @param string subject 邮件标题
 * @param string content 邮件内容
 * @return array
 */
function WSTSendMail($to, $subject, $content)
{
    require_cache(VENDOR_PATH . "PHPMailer/class.smtp.php");
    require_cache(VENDOR_PATH . "PHPMailer/class.phpmailer.php");
    $mail = new PHPMailer();
    // 装配邮件服务器
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = $GLOBALS['CONFIG']['mailSmtp'];
    $mail->SMTPAuth = $GLOBALS['CONFIG']['mailAuth'];
    $mail->Username = $GLOBALS['CONFIG']['mailUserName'];
    $mail->Password = $GLOBALS['CONFIG']['mailPassword'];
    $mail->CharSet = 'utf-8';
    // 装配邮件头信息
    $mail->From = $GLOBALS['CONFIG']['mailAddress'];
    $mail->AddAddress($to);
    $mail->FromName = $GLOBALS['CONFIG']['mailSendTitle'];
    $mail->IsHTML(true);

    // 装配邮件正文信息
    $mail->Subject = $subject;
    $mail->Body = $content;
    // 发送邮件
    $rs = array();
    if (!$mail->Send()) {
        $rs['status'] = 0;
        $rs['msg'] = $mail->ErrorInfo;
        return $rs;
    } else {
        $rs['status'] = 1;
        return $rs;
    }
}

/**
 * 发送短信 （大汉三通）
 * @param $account  用户账号
 * @param $password  密码，需采用MD5加密(32位小写) ，如调用大汉三通提供jar包的话使用明文
 * @param $sign  短信签名，该签名需要提前报备，生效后方可使用，不可修改，必填，示例如：【大汉三通】
 * @param $phoneNumer  手机号码
 * @param $content  短信内容
 * @return bool|mixed
 */
function WSTSendSMS3($phoneNumer, $content)
{
    $param = array(
        'account' => $GLOBALS['CONFIG']['dhAccount'],//用户账号
        'password' => md5($GLOBALS['CONFIG']['dhPassword']),//密码，需采用MD5加密(32位小写) ，如调用大汉三通提供jar包的话使用明文
        'msgid' => '',//该批短信编号(32位UUID)，需保证唯一，选填，不填的话响应里会给一个系统生成的
        'phones' => $phoneNumer,//接收手机号码，多个手机号码用英文逗号分隔，最多1000个，必填，国际号码格式为+国别号手机号，示例：+85255441234
        'content' => $content,//短信内容，最多1000个汉字，必填,内容中不要出现【】[]这两种方括号，该字符为签名专用
        'sign' => $GLOBALS['CONFIG']['dhSign'],//短信签名，该签名需要提前报备，生效后方可使用，不可修改，必填，示例如：【大汉三通】
        'subcode' => '',//短信签名对应子码(大汉三通提供)+自定义扩展子码(选填)，必须是数字，选填，未填使用签名对应子码，通常建议不填
        'sendtime' => ''//定时发送时间，格式yyyyMMddHHmm，为空或早于当前时间则立即发送
    );

    $url = $GLOBALS['CONFIG']['dhSendUrl'] . "/json/sms/Submit";//短信下发（相同内容多个号码）
    $result = curlRequest($url, $param, 1);
    return $result;
}

/**
 * 发送短信
 * 此接口要根据不同的短信服务商去写，这里只是一个参考
 * @param string $phoneNumer 手机号码
 * @param string $content 短信内容
 */
function WSTSendSMS2($phoneNumer, $content)
{
    $url = 'http://223.4.21.214:8180/service.asmx/SendMessage?Id=' . $GLOBALS['CONFIG']['smsOrg'] . "&Name=" . $GLOBALS['CONFIG']['smsKey'] . "&Psw=" . $GLOBALS['CONFIG']['smsPass'] . "&Timestamp=0&Message=" . $content . "&Phone=" . $phoneNumer;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置否输出到页面
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); //设置连接等待时间
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    $data = curl_exec($ch);
    curl_close($ch);
    return "$data";
}

/**
 * @param unknown_type $phoneNumer
 * @param unknown_type $content
 */
function WSTSendSMS($phoneNumer, $content)
{
    $url = 'http://utf8.sms.webchinese.cn/?Uid=' . $GLOBALS['CONFIG']['smsKey'] . '&Key=' . $GLOBALS['CONFIG']['smsPass'] . '&smsMob=' . $phoneNumer . '&smsText=' . $content;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置否输出到页面
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); //设置连接等待时间
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * 字符串替换
 * @param string $str 要替换的字符串
 * @param string $repStr 即将被替换的字符串
 * @param int $start 要替换的起始位置,从0开始
 * @param string $splilt 遇到这个指定的字符串就停止替换
 */
function WSTStrReplace($str, $repStr, $start, $splilt = '')
{
    $newStr = substr($str, 0, $start);
    $breakNum = -1;
    for ($i = $start; $i < strlen($str); $i++) {
        $char = substr($str, $i, 1);
        if ($char == $splilt) {
            $breakNum = $i;
            break;
        }
        $newStr .= $repStr;
    }
    if ($splilt != '' && $breakNum > -1) {
        for ($i = $breakNum; $i < strlen($str); $i++) {
            $char = substr($str, $i, 1);
            $newStr .= $char;
        }
    }
    return $newStr;
}

/**
 * 循环删除指定目录下的文件及文件夹
 * @param string $dirpath 文件夹路径
 */
function WSTDelDir($dirpath)
{
    $dh = opendir($dirpath);
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
            $fullpath = $dirpath . "/" . $file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                WSTDelDir($fullpath);
                rmdir($fullpath);
            }
        }
    }
    closedir($dh);
    $isEmpty = 1;
    $dh = opendir($dirpath);
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
            $isEmpty = 0;
            break;
        }
    }
    return $isEmpty;
}

/**
 * 获取网站域名
 */
function WSTDomain()
{
    $server = $_SERVER['HTTP_HOST'];
    $http = is_ssl() ? 'https://' : 'http://';
    return $http . $server . __ROOT__;
}

/**
 * 获取系统根目录
 */
function WSTRootPath()
{
    return dirname(dirname(dirname(dirname(__File__))));
}

/**
 * 获取网站根域名
 */
function WSTRootDomain()
{
    $server = $_SERVER['HTTP_HOST'];
    $http = is_ssl() ? 'https://' : 'http://';
    return $http . $server;
}

function NiaoRootDomain()
{
    $server = $_SERVER['HTTP_HOST'];
    return $server;
}

/**
 * 设置当前页面对象
 * @param int 0-用户  1-商家
 */
function WSTLoginTarget($target = 0)
{
    $WST_USER = session('WST_USER');
    $WST_USER['loginTarget'] = $target;
    session('WST_USER', $WST_USER);
}

/**
 * 生成缓存文件
 */
function WSTDataFile($name, $path = '', $data = array())
{
    $key = C('DATA_CACHE_KEY');
    $name = md5($key . $name);
    if (is_array($data) && !empty($data)) {
        if ($data['mallLicense'] == '') {
            if (stripos($data['mallTitle'], 'Powered By WSTMall') === false) $data['mallTitle'] = $data['mallTitle'] . " - Powered By WSTMall";
        }
        $data = serialize($data);
        if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //数据压缩
            $data = gzcompress($data, 3);
        }
        if (C('DATA_CACHE_CHECK')) {//开启数据校验
            $check = md5($data);
        } else {
            $check = '';
        }
        $data = "<?php\n//" . sprintf('%012d', $expire) . $check . $data . "\n?>";
        $result = file_put_contents(DATA_PATH . $path . $name . ".php", $data);
        clearstatcache();
    } else if (is_null($data)) {
        unlink(DATA_PATH . $path . $name . ".php");
    } else {
        if (file_exists(DATA_PATH . $path . $name . '.php')) {
            $content = file_get_contents(DATA_PATH . $path . $name . '.php');
            if (false !== $content) {
                $expire = (int)substr($content, 8, 12);
                if (C('DATA_CACHE_CHECK')) {//开启数据校验
                    $check = substr($content, 20, 32);
                    $content = substr($content, 52, -3);
                    if ($check != md5($content)) {//校验错误
                        return null;
                    }
                } else {
                    $content = substr($content, 20, -3);
                }
                if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                    //启用数据压缩
                    $content = gzuncompress($content);
                }
                $content = unserialize($content);
                return $content;
            }
        }
        return null;
    }
}


/**
 * 建立文件夹
 * @param string $aimUrl
 * @return viod
 */
function WSTCreateDir($aimUrl)
{
    $aimUrl = str_replace('', '/', $aimUrl);
    $aimDir = '';
    $arr = explode('/', $aimUrl);
    $result = true;
    foreach ($arr as $str) {
        $aimDir .= $str . '/';
        if (!file_exists_case($aimDir)) {
            $result = mkdir($aimDir, 0777);
        }
    }
    return $result;
}

/**
 * 建立文件
 * @param string $aimUrl
 * @param boolean $overWrite 该参数控制是否覆盖原文件
 * @return boolean
 */
function WSTCreateFile($aimUrl, $overWrite = false)
{
    if (file_exists_case($aimUrl) && $overWrite == false) {
        return false;
    } elseif (file_exists_case($aimUrl) && $overWrite == true) {
        WSTUnlinkFile($aimUrl);
    }
    $aimDir = dirname($aimUrl);
    WSTCreateDir($aimDir);
    touch($aimUrl);
    return true;
}

/**
 * 删除文件
 * @param string $aimUrl
 * @return boolean
 */
function WSTUnlinkFile($aimUrl)
{
    if (file_exists_case($aimUrl)) {
        unlink($aimUrl);
        return true;
    } else {
        return false;
    }
}

function WSTLog($filepath, $word)
{
    if (!file_exists_case($filepath)) {
        WSTCreateFile($filepath);
    }
    $fp = fopen($filepath, "a");
    flock($fp, LOCK_EX);
    fwrite($fp, $word);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function WSTReadExcel($file)
{
    Vendor("PHPExcel.PHPExcel");
    Vendor("PHPExcel.PHPExcel.IOFactory");
    return PHPExcel_IOFactory::load(WSTRootPath() . "/Upload/" . $file);
}

/**
 * 处理转义字符
 * @param $str 需要处理的字符串
 */
function WSTAddslashes($str)
{
    if (!get_magic_quotes_gpc()) {
        if (!is_array($str)) {
            $str = addslashes($str);
        } else {
            foreach ($str as $key => $val) {
                $str[$key] = WSTAddslashes($val);
            }
        }
    }
    return $str;
}

/**
 * 检测字符串不否包含
 * @param $srcword 被检测的字符串
 * @param $filterWords 禁用使用的字符串列表
 * @return boolean true-检测到,false-未检测到
 */
function WSTCheckFilterWords($srcword, $filterWords)
{
    $flag = true;
    $filterWords = str_replace("，", ",", $filterWords);
    $words = explode(",", $filterWords);
    for ($i = 0; $i < count($words); $i++) {
        if (strpos($srcword, $words[$i]) !== false) {
            $flag = false;
            break;
        }
    }
    return $flag;
}

/**
 * 比较两个日期相差的天数
 * @param $date1 开始日期  Y-m-d
 * @param $date2 结束日期  Y-m-d
 */
function WSTCompareDate($date1, $date2)
{
    $time1 = strtotime($date1);
    $time2 = strtotime($date2);
    return ceil(($time1 - $time2) / 86400);
}

/**
 * 截取字符串
 */
function WSTMSubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    $newStr = '';
    if (function_exists("mb_substr")) {
        if ($suffix)
            $newStr = mb_substr($str, $start, $length, $charset);
        else
            $newStr = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        if ($suffix)
            $newStr = iconv_substr($str, $start, $length, $charset);
        else
            $newStr = iconv_substr($str, $start, $length, $charset);
    }
    if ($newStr == '') {
        $re ['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re ['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re ['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re ['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re [$charset], $str, $match);
        $slice = join("", array_slice($match [0], $start, $length));
        if ($suffix)
            $newStr = $slice;
    }
    return (strlen($str) > strlen($newStr)) ? $newStr . "..." : $newStr;
}

/**
 * 获取当前毫秒数
 */
function WSTGetMillisecond()
{
    $time = explode(" ", microtime());
    $time = $time [1] . ($time [0] * 1000);
    $time2 = explode(".", $time);
    $time = $time2 [0];
    return $time;
}

/**
 * 格式化查询语句中传入的in 参与，防止sql注入
 * @param unknown $split
 * @param unknown $str
 */
function WSTFormatIn($split, $str)
{
    $strdatas = explode($split, $str);
    $data = array();
    for ($i = 0; $i < count($strdatas); $i++) {
        $data[] = (int)$strdatas[$i];
    }
    $data = array_unique($data);
    return implode($split, $data);
}

/**
 * 获取上一个月或者下一个月份 1:下一个月,其他值为上一个月
 * @param int $sign default 1
 */
function WSTMonth($sign = 1, $month = '')
{
    $tmp_year = date('Y');
    $tmp_mon = date('m');
    $tmp_nextmonth = mktime(0, 0, 0, $tmp_mon + 1, 1, $tmp_year);
    $tmp_forwardmonth = mktime(0, 0, 0, $tmp_mon - 1, 1, $tmp_year);
    if ($sign == 1) {
        //得到当前月的下一个月
        return $fm_next_month = date("Y-m", $tmp_nextmonth);
    } else {
        //得到当前月的上一个月
        return $fm_forward_month = date("Y-m", $tmp_forwardmonth);
    }
}


/**
 * 高精度数字相加
 * @param $num
 * @param number $i 保留小数位
 * 注意：APP用有引用
 */
function WSTBCMoney($num1, $num2, $i = 2)
{
    $num = bcadd($num1, $num2, $i);
    return (float)$num;
}


//php获取中文字符拼音首字母
function WSTGetFirstCharter($str)
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

/**
 * 下载网络文件到本地服务器
 * @param unknown $url
 * @param unknown $folde
 */
function WSTDownFile($url, $folde = './Upload/image/')
{
    set_time_limit(24 * 60 * 60);
    $newfname = $folde . basename($url);
    $file = fopen($url, "rb");
    if ($file) {
        $newf = fopen($newfname, "wb");
        if ($newf) {
            while (!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
}

/**
 * 自动登录
 */
function WSTAutoByCookie()
{
    $USER = session('WST_USER');
    if (empty($USER)) D('Home/Users')->autoLoginByCookie();
}

/**
 * 根据IP获取城市
 */
function WSTIPAddress()
{
    $url = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . get_client_ip();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'utf8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $location = curl_exec($ch);
    curl_close($ch);
    if ($location) {
        $location = json_decode($location);
        return array('province' => $location->province, 'city' => $location->city, 'district' => $location->district);
    }
    return array();
}


function niaocms_file_get_contents_post($url, $post)
{//file_get_content POST请求数据
    $options = array(
        'http' => array(
            'method' => 'POST',
            // 'content' => 'name=caiknife&email=caiknife@gmail.com',
            'content' => http_build_query($post),
        ),
    );

    @$result = file_get_contents($url, false, stream_context_create($options));

    return $result;
}


/**
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @return int
 * $lat1 = '31.253411';
 * $lon1 = '121.518998';
 * $lat2 = '31.277117';
 * $lon2 = '120.744587';
 * echo getDistance($lat1, $lon1, $lat2, $lon2);  // 73.734589823361
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{

    // 将角度转为狐度
    $radLat1 = deg2rad($lat1);// deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137;

    return $s;

}

/**
 * @param $lat1
 * @param $lon1
 * @param $lat2
 * @param $lon2
 * @param float $radius 星球半径 KM
 * @return float
 * $lat1 = '31.253411';
 * $lon1 = '121.518998';
 * $lat2 = '31.277117';
 * $lon2 = '120.744587';
 * echo distance($lat1, $lon1, $lat2, $lon2);   // 73.734589823354
 */
function distance($lat1, $lon1, $lat2, $lon2, $radius = 6378.137)
{
    $rad = floatval(M_PI / 180.0);

    $lat1 = floatval($lat1) * $rad;
    $lon1 = floatval($lon1) * $rad;
    $lat2 = floatval($lat2) * $rad;
    $lon2 = floatval($lon2) * $rad;

    $theta = $lon2 - $lon1;

    $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($theta));

    if ($dist < 0) {
        $dist += M_PI;
    }
    return $dist = $dist * $radius;
}


//BD-09(百度)坐标转换成GCJ-02(火星，高德)坐标
//@param bd_lon 百度经度
//@param bd_lat 百度纬度
function bd_decrypt($bd_lon, $bd_lat)
{
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $bd_lon - 0.0065;
    $y = $bd_lat - 0.006;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    // $data['gg_lon'] = $z * cos($theta);
    // $data['gg_lat'] = $z * sin($theta);
    $gg_lon = $z * cos($theta);
    $gg_lat = $z * sin($theta);
    // 保留小数点后六位
    $data['gg_lon'] = round($gg_lon, 10);
    $data['gg_lat'] = round($gg_lat, 10);
    return $data;
}

//GCJ-02(火星，高德)坐标转换成BD-09(百度)坐标
//@param bd_lon 百度经度
//@param bd_lat 百度纬度
function bd_encrypt($gg_lon, $gg_lat)
{
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $gg_lon;
    $y = $gg_lat;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    $bd_lon = $z * cos($theta) + 0.0065;
    $bd_lat = $z * sin($theta) + 0.006;
    // 保留小数点后六位
    $data['bd_lon'] = round($bd_lon, 10);
    $data['bd_lat'] = round($bd_lat, 10);
    return $data;
}

/* 计算两个经纬度的距离
相比上面的距离计算较精准
*/
function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2)
{
    $theta = $longitude1 - $longitude2;
    $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
    $miles = acos($miles);
    $miles = rad2deg($miles);
    $miles = $miles * 60 * 1.1515;
    $feet = $miles * 5280;
    $yards = $feet / 3;
    $kilometers = $miles * 1.609344;
    $meters = $kilometers * 1000;
    return compact('miles', 'feet', 'yards', 'kilometers', 'meters');
}

/**
 * @param $url 请求网址
 * @param bool $params 请求参数
 * @param int $ispost 请求方式
 * @param int $https https协议
 * @param int $json json格式[0:否|1:是]
 * @return bool|mixed
 */

function curlRequest($url, $params = false, $ispost = 0, $https = 0, $json = 0)

{

    $httpInfo = array();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($https) {

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在

    }

    if ($ispost) {

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        curl_setopt($ch, CURLOPT_URL, $url);

        $xml_parser = xml_parser_create();
        if (xml_parse($xml_parser, $params, true)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:text/xml; charset=utf-8"));
        }
        if ($json == 1) {
            $headers = array("Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    } else {

        if ($params) {

            if (is_array($params)) {

                $params = http_build_query($params);

            }

            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);

        } else {

            curl_setopt($ch, CURLOPT_URL, $url);

        }

    }
    $response = curl_exec($ch);
    if ($response === FALSE) {

//echo "cURL Error: " . curl_error($ch);

        return false;

    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));

    curl_close($ch);

    return $response;

}

//token存储模块
/****
 * token string
 * $userData array
 ***/
function userTokenAdd($token, $userData)
{
    $User = M("user_token");
    $data['staffId'] = $userData['staffId'];
    $data['token'] = $token;
    $data['userData'] = json_encode($userData);
    $data['createTime'] = time();
    if ($User->add($data)) {
        return true;
    } else {
        return false;
    }

}

/**
 * @param $shopId
 * @return mixed
 * 验证商家是否有问题
 */

function verifyShopInfo($shopId)
{
    $shopsModel = M('shops');
    $where = " shopId = {$shopId} and shopFlag = 1 and (shopStatus = 0 or shopStatus = 1)";
    $shopInfo = $shopsModel->where($where)->find();
    return $shopInfo;
}

/**
 * @param $params
 * @return array|string
 * 获取权限信息----登录时使用
 */
function getUserPrivilege($params)
{
    //--start----商家后台-----管理员|职员登录获取权限信息
    if ($params['module_type'] == 2) {//所属模块【1运营后台、2商家后台】
        $param = [];
        $param['shopId'] = $params['shopId'];
        if ($params['type'] == 2) {
            $param['staffNid'] = $params['staffNid'];
        }
        $param['module_type'] = 2;
        $param['type'] = $params['type'];//2职员|1管理员
        $res = getStaffPrivilege($param);
        return $res;
    }
    //-------end-===============---------------------------------
    //start----------=========总后台=========================================
    $authRuleModel = M('auth_rule');
//    $ruleModel = M('roles');

//    $ruleInfo = $ruleModel->where('roleFlag = 1 and roleId in(' . $params['roleId'] . ')')->select();
    $rolesServiceModule = new \App\Modules\Roles\RolesServiceModule();
    $ruleData = $rolesServiceModule->getRolesListByRoleIds($params['roleId']);
    $ruleInfo = $ruleData['data'];
    if (empty($ruleInfo) && $params['loginName'] != "admin") {
        return [];
    }

    //取值---合并---分割---去重
    $grantId = array_get_column($ruleInfo, 'grant');
    $grantInfo = implode(',', $grantId);
    $ruleList = array_unique(explode(',', $grantInfo));

    $where = [];
    $where['module_type'] = $params['module_type'];
    $authRuleList = $authRuleModel->where($where)->order('weigh asc')->select();
    if (empty($authRuleList)) {
        return [];
    }

    //系统模式路由关联表【需要过滤的路由id】
    $authRuleServiceModule = new \App\Modules\AuthRule\AuthRuleServiceModule();
    $getAuthRuleSystemInfo = $authRuleServiceModule->getAuthRuleSystemInfo($GLOBALS["CONFIG"]["systemModeId"]);
    $authRuleId = implode(',', array_get_column($getAuthRuleSystemInfo['data'], 'authRuleIds'));
    $authRuleIds = array_unique(explode(',', $authRuleId));

    foreach ($authRuleList as $k => $v) {
        $authRuleList[$k]['id'] = (int)$v['id'];
        $authRuleList[$k]['pid'] = (int)$v['pid'];
        $authRuleList[$k]['menu_type'] = (int)$v['menu_type'];
        $authRuleList[$k]['weigh'] = (int)$v['weigh'];
        $authRuleList[$k]['is_frame'] = (int)$v['is_frame'];
        $authRuleList[$k]['module_type'] = (int)$v['module_type'];
        $authRuleList[$k]['page_hidden'] = (bool)$v['page_hidden'];

        if ($params['loginName'] == "admin") {
            //管理员不做处理
            if ($v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = false;
            }
        } else {
            //隐藏状态 1是[隐藏] 0否[展示]  -1根据权限设置判断
            if (in_array($v['id'], $ruleList) && $v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = false;
            } elseif (!in_array($v['id'], $ruleList) && $v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = true;
            }
        }
        if ($v['pid'] == 0) {
            $meta = [];
            $meta['title'] = $v['title'];
            $meta['icon'] = $v['icon'];
        } else {
            $meta = [];
            $meta['title'] = $v['title'];
            $meta['requireAuth'] = true;
        }
        $authRuleList[$k]['meta'] = $meta;
        $authRuleList[$k]['name'] = $v['path'];
        unset($authRuleList[$k]['title'], $authRuleList[$k]['icon']);

        //过滤系统模式路由关联的数据
        if (in_array($v['id'], $authRuleIds)) {
            unset($authRuleList[$k]);
        }
    }
    $res = getChild($authRuleList);
    return (array)$res;
}

/**
 * @param $params
 * @return array
 * 总后台-----获取权限【树形】-----权限是否选中
 */

function getTablePrivilege($params)
{
    $authRuleModel = M('auth_rule');

    //获取权限节点信息
    $where = [];
    $where['module_type'] = $params['module_type'];
    $authRuleList = $authRuleModel->where($where)->order('weigh asc')->select();
    if (empty($authRuleList)) {
        return [];
    }

    //系统模式路由关联表【需要过滤的路由id】
    $authRuleServiceModule = new \App\Modules\AuthRule\AuthRuleServiceModule();
    $getAuthRuleSystemInfo = $authRuleServiceModule->getAuthRuleSystemInfo($GLOBALS["CONFIG"]["systemModeId"]);
    $authRuleId = implode(',', array_get_column($getAuthRuleSystemInfo['data'], 'authRuleIds'));
    $authRuleIds = array_unique(explode(',', $authRuleId));

    //将权限进行处理
    foreach ($authRuleList as $k => $v) {
        $authRuleList[$k]['id'] = (int)$v['id'];
        $authRuleList[$k]['pid'] = (int)$v['pid'];
        $authRuleList[$k]['menu_type'] = (int)$v['menu_type'];
        $authRuleList[$k]['weigh'] = (int)$v['weigh'];
        $authRuleList[$k]['is_frame'] = (int)$v['is_frame'];
        $authRuleList[$k]['module_type'] = (int)$v['module_type'];
        $authRuleList[$k]['page_hidden'] = (bool)$v['page_hidden'];

        //当职员没有分配权限时进行隐藏状态分配

        if (empty($params['staffNid'])) {
            $authRuleList[$k]['page_hidden'] = false;
        } else {
            //隐藏状态 1是[隐藏] 0否[展示]  -1根据权限设置判断
            if (in_array($v['id'], $params['staffNid']) && $v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = false;
            } elseif (!in_array($v['id'], $params['staffNid']) && $v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = true;
            }
        }
        //是否勾选 1:是 true|0:否false  默认没有勾选
        $authRuleList[$k]['is_checked'] = false;

        if (in_array($v['id'], $params['staffNid'])) {
            //是否勾选
            $authRuleList[$k]['is_checked'] = true;
        }
        if ($v['pid'] == 0) {
            $meta = [];
            $meta['title'] = $v['title'];
            $meta['icon'] = $v['icon'];
        } else {
            $meta = [];
            $meta['title'] = $v['title'];
            $meta['requireAuth'] = true;
        }
        $authRuleList[$k]['meta'] = $meta;
        $authRuleList[$k]['name'] = $v['path'];
        unset($authRuleList[$k]['title'], $authRuleList[$k]['icon']);

        if ($v['page_hidden'] == 1) {//选择权限时过滤隐藏路由，目前没有测试出bug
            unset($authRuleList[$k]);
        }

        //过滤系统模式路由关联的数据
        if (in_array($v['id'], $authRuleIds)) {
            unset($authRuleList[$k]);
        }
    }
    $res = getChild($authRuleList);
    return (array)$res;
}

/**
 * @param $params
 * @return array
 * 商家后台-----获取权限【树形】---权限是否选中
 */
function getStaffPrivilege($params)
{
    $authRuleModel = M('auth_rule');

    //获取权限节点信息
    $where = [];
    $where['module_type'] = $params['module_type'];
    $authRuleList = $authRuleModel->where($where)->order('weigh asc')->select();
    if (empty($authRuleList)) {
        return [];
    }

    //系统模式路由关联表【需要过滤的路由id】
    $authRuleServiceModule = new \App\Modules\AuthRule\AuthRuleServiceModule();
    $getAuthRuleSystemInfo = $authRuleServiceModule->getAuthRuleSystemInfo($GLOBALS["CONFIG"]["systemModeId"]);
    $authRuleId = implode(',', array_get_column($getAuthRuleSystemInfo['data'], 'authRuleIds'));
    $authRuleIds = array_unique(explode(',', $authRuleId));

    //将权限进行处理
    foreach ($authRuleList as $k => $v) {
        $authRuleList[$k]['id'] = (int)$v['id'];
        $authRuleList[$k]['pid'] = (int)$v['pid'];
        $authRuleList[$k]['menu_type'] = (int)$v['menu_type'];
        $authRuleList[$k]['weigh'] = (int)$v['weigh'];
        $authRuleList[$k]['is_frame'] = (int)$v['is_frame'];
        $authRuleList[$k]['module_type'] = (int)$v['module_type'];
        $authRuleList[$k]['page_hidden'] = (bool)$v['page_hidden'];
        /*
         * 隐藏状态 1是[隐藏] 0否[展示]  -1根据权限设置判断
         * */
        if ($params['type'] == 1) {
            //总管理员是不会受到权限的影响
            if ($v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = false;
            }
        } else {
            if (in_array($v['id'], $params['staffNid']) && $v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = false;
            } elseif (!in_array($v['id'], $params['staffNid']) && $v['page_hidden'] == -1) {
                $authRuleList[$k]['page_hidden'] = true;
            }
            //当职员没有分配权限时进行隐藏状态分配
            if (empty($params['staffNid'])) {
                $authRuleList[$k]['page_hidden'] = true;
            }
        }

        //用于管理员操作
        if (!empty($params['checked'])) {
            //是否勾选 1:是 true|0:否false
            $authRuleList[$k]['is_checked'] = false;

            if (in_array($v['id'], $params['staffNid'])) {
                //是否勾选
                $authRuleList[$k]['is_checked'] = true;
            }
        }
        if ($v['pid'] == 0) {
            $meta = [];
            $meta['title'] = $v['title'];
            $meta['icon'] = $v['icon'];
        } else {
            $meta = [];
            $meta['title'] = $v['title'];
            $meta['requireAuth'] = true;
        }
        $authRuleList[$k]['meta'] = $meta;
        $authRuleList[$k]['name'] = $v['path'];
        unset($authRuleList[$k]['title'], $authRuleList[$k]['icon']);
        if (!empty($params['checked'])) {//选择权限时过滤隐藏路由，目前没有测试出bug
            if ($v['page_hidden'] == 1) {
                unset($authRuleList[$k]);
            }
        }
        //过滤系统模式路由关联的数据
        if (in_array($v['id'], $authRuleIds)) {
            unset($authRuleList[$k]);
        }
    }
    $res = getChild($authRuleList);
    return (array)$res;
}

/**
 * @param $data
 * @return array
 * 进行菜单列表数据分配
 */
function getChild($data)
{
    $tree = [];
    $newData = [];
    //循环重新排列
    foreach ($data as $datum) {
        $newData[$datum['id']] = $datum;
    }

    foreach ($newData as $key => $datum) {
        if ($datum['pid'] > 0) {
            //不是根节点的将自己的地址放到父级的child节点
            $newData[$datum['pid']]['children'][] = &$newData[$key];
        } else {
            //根节点直接把地址放到新数组中
            $tree[] = &$newData[$datum['id']];
        }
    }
    return $tree;
}

/**
 * 通过 用户ID 来获取 token
 * @param $userId
 * @return string
 */
function getUserTokenByUserId($userId)
{
    $um = M('users');
    $user_info = $um->where(array('userId' => $userId))->find();
    $token = md5(uniqid('', true) . $user_info['userPhone'] . $user_info['loginPwd'] . $user_info['loginSecret'] . (string)microtime());
    $result = userTokenAdd($token, $user_info);
    if ($result) return $token;
    else return '';
}

/******
 * token查询模块 成功返回数组 错误返回false
 * token string
 * stopTime 存活秒数
 ******/
function userTokenFind($token, $stopTime)
{
    $User = M("user_token");
    $where['token'] = $token;

    $UserData = $User->where($where)->find();
    if (empty($UserData)) {
        return false;
    }

    if (time() - $UserData['createTime'] > $stopTime) {
        $User->where("id='{$UserData['id']}'")->delete();
        return false;
    }

    return json_decode($UserData['userData'], true);

}


//菜谱存储模块
/****
 * token string
 * $userData array
 ***/
function menuTokenAdd($token, $userData)
{
    $User = M("menu");
    $data['token'] = $token;
    $data['userData'] = $userData;
    $data['createTime'] = time();
    if ($User->add($data)) {
        return true;
    } else {
        return false;
    }

}


/******
 * 菜谱查询模块 成功返回数组 错误返回false
 * token string
 * stopTime 存活秒数
 ******/
function menuTokenFind($token)
{
    $User = M("menu");
    $where['token'] = $token;

    $UserData = $User->where($where)->find();
    if (empty($UserData)) {
        return false;
    }

    return $UserData['userData'];

}


/*****
 *
 * $data['a'] = 1;
 * $data['b'] = 2;
 * echo arrayToXml($data);
 * 数组转xml
 ******/
function arrayToXml($arr, $dom = 0, $item = 0, $root = 'xml')
{
    if (!$dom) {
        $dom = new DOMDocument("1.0");
    }
    if (!$item) {
        $item = $dom->createElement($root);
        $dom->appendChild($item);
    }
    foreach ($arr as $key => $val) {
        $itemx = $dom->createElement(is_string($key) ? $key : "item");
        $item->appendChild($itemx);
        if (!is_array($val)) {
            $text = $dom->createTextNode($val);
            $itemx->appendChild($text);

        } else {
            arrayToXml($val, $dom, $itemx);
        }
    }
    return $dom->saveXML();
}

/*****
 *
 * 网页返回码
 * 例如：httpStatus(400);
 ******/
function httpStatus($num)
{
    static $http = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );
    header($http[$num]);
    exit();
}

/*****
 *
 * 判断商品是否在配送范围内
 *
 * $goodsId  Array 一维数组
 * $areaId3 string 第三级城市id
 *
 * 成功返回 null
 * 失败返回 array
 ******/
function isDistriScope($goodsId, $areaId3)
{
    //todo::暂时不启用,后期在考虑
//    $goods = M('goods');
//
//
//    for ($i = 0; $i < count($goodsId); $i++) {
//        //配送区域条件
//        $where['wst_goods.goodsId'] = $goodsId[$i];//商品id
//        $where['wst_shops_communitys.areaId3'] = $areaId3;//收货地址
//        $where['wst_shops.shopId'] = $goods->where("goodsId = '{$goodsId[$i]}'")->field('shopId')->find()['shopId'];//店铺ID
//
//        //$where['wst_shops_communitys.communityId'] = $user_address['communityId'];//精确到社区 暂时不用 等待客户要求
//
//
//        $mod = $goods
//            ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
//            ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
//            ->where($where)
//            ->group('wst_goods.goodsId')
//            ->field("goodsId")
//            ->find();
//
//
//        if (empty($mod)) {
//            unset($where);
//            $where['goodsId'] = $goodsId[$i];
//            $mod_goods_data_name = $goods->where($where)->field('goodsName,goodsId')->find();
//
//
//            unset($apiRet);
//            $apiRet['apiCode'] = '000073';
//            $apiRet['apiInfo'] = '商品不在配送区域';
//            $apiRet['goodsId'] = $mod_goods_data_name['goodsId'];
//            $apiRet['goodsName'] = $mod_goods_data_name['goodsName'];
//            $apiRet['apiState'] = 'error';
//            return $apiRet;
//        }
//    }

    //成功返回空
    return null;


}


//微信退款
/*******
 * transaction_id 微信订单号
 * total_fee 订单总金额(分)
 * refund_fee 退款金额(分)
 * orderId    订单id
 * goodsId        商品Id
 * logUserId    操作者Id
 * userId        用户Id
 *
 * return
 * -1;//数据验证不通过
 * -4;//有参数为空
 * -3;//退款失败
 * -2;//退款异常
 * true 成功
 * false 失败
 *
 * wxRefund('4200000199201811257658840011','1099','99','36','2')
 ********/
//function wxRefund($transaction_id, $total_fee, $refund_fee, $orderId, $goodsId, $logUserId, $userId, $skuId = 0)
//{
//
//    /*if((isset($transaction_id) && $transaction_id != ""
//            && !preg_match("/^[0-9a-zA-Z]{10,64}$/i", $transaction_id, $matches))
//        || (isset($total_fee) && $total_fee != ""
//            && !preg_match("/^[0-9]{0,10}$/i", $total_fee, $matches))
//        || (isset($refund_fee) && $refund_fee != ""
//            && !preg_match("/^[0-9]{0,10}$/i", $refund_fee, $matches)))
//    {
//        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
//        $txt = $transaction_id.'+'.$total_fee."+".$refund_fee;
//        fwrite($myfile, "退退退退退退退退退退退退：我来了：$txt \n");
//        fclose($myfile);
//        return false;//数据验证不通过
//    }*/
//    if (empty($orderId) or empty($goodsId) or empty($logUserId)) {
//        return false;//有参数为空
//    }
//
//    //判断此商品是否已经退款 在退款记录表里不能有次记录
//    $order_complainsrecord_mod = M('order_complainsrecord');
//    $where['orderId'] = $orderId;
//    $where['goodsId'] = $goodsId;
//    $where['userId'] = $userId;
//    $where['skuId'] = $skuId;
//    if ($order_complainsrecord_mod->where($where)->count() > 0) {
//        return false;//退款失败
//    }
//
//
//    vendor('WxPay.lib.WxPayApi');
//    vendor('WxPay.lib.WxPayConfig');
//    vendor('WxPay.lib.log');
//
//    try {
//        $input = new WxPayRefund();
//        $input->SetTransaction_id($transaction_id);
//        $input->SetTotal_fee($total_fee);
//        $input->SetRefund_fee($refund_fee);
//        /*
//        $config = new WxPayConfig();
//        $input->SetOut_refund_no("sdkphp".date("YmdHis"));
//        $input->SetOp_user_id($config->GetMerchantId());
//        $resdata = WxPayApi::refund($config, $input);
//        */
//
//        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
//        if (empty($wx_payments['payConfig'])) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            return $apiRet;
//        }
//        $wx_config = json_decode($wx_payments['payConfig'], true);
//        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
//        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);
//
//        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
//        $input->SetOp_user_id($wx_config['mchId']);
//        $resdata = WxPayApi::refund($wx_config, $input);
//        unlink($wx_config['sslCertPath']);
//        unlink($wx_config['sslKeyPath']);
//
//        if ($resdata['result_code'] == 'SUCCESS') {
//            //写入订单日志
//            $log_orders = M("log_orders");
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "发起微信退款：" . $refund_fee / 100 . '元';
//            $data["logUserId"] = $logUserId;
//            $data["logType"] = "0";
//            $data["logTime"] = date("Y-m-d H:i:s");
//
//            $log_orders->add($data);
//
//
//            //添加退款记录 退款记录应由微信服务器通知修改 目前使用本地方式---------------------
//
//            $add_data['orderId'] = $orderId;//订单id
//            $add_data['tradeNo'] = $transaction_id;//流水号
//            $add_data['goodsId'] = $goodsId;//商品id
//            $add_data['money'] = $refund_fee / 100;//单位转为元
//            $add_data['addTime'] = date('Y-m-d H:i:s');
//            $add_data['payType'] = 1;
//            $add_data['userId'] = $userId;
//            $add_data['skuId'] = $skuId;
//
//            if (M('order_complainsrecord')->add($add_data)) {
//                return true;//退款申请成功
//            } else {
//                return false;
//            }
//        } else {
//            return false;//退款失败
//        }
//
//    } catch (Exception $e) {
//        return false;//退款失败
//        //Log::ERROR(json_encode($e));
//    }
//
//}

/**
 * 微信退款-用于商户端退款
 * @param string $pay_transaction_id 微信交易号
 * @param float $pay_total_fee 订单实付金额
 * @param float $pay_refund_fee 退款金额
 * @param int $orderId 订单id
 * @param int $goodsId 商品id
 * @param int $skuId skuId
 * */
function wxRefund($pay_transaction_id, $pay_total_fee, $pay_refund_fee, $orderId, $goodsId, $skuId)
{
    if (empty($orderId) or empty($goodsId)) {
        return returnData(false, -1, 'error', '参数不全');
    }
    $order_tab = M('orders');
    $where = [];
    $where['orderId'] = $orderId;
    $order_info = $order_tab->where($where)->find();
    if (empty($order_info)) {
        return returnData(false, -1, 'error', '订单信息有误');
    }
    $userId = $order_info['userId'];
    //判断此商品是否已经退款 在退款记录表里不能有次记录
    $order_complainsrecord_tab = M('order_complainsrecord');
    $where['orderId'] = $orderId;
    $where['goodsId'] = $goodsId;
    $where['userId'] = $userId;
    $where['skuId'] = $skuId;
    $count = $order_complainsrecord_tab->where($where)->count();
    if ($count > 0) {
        return returnData(false, -1, 'error', '已处理过，不能重复处理');
    }
    vendor('WxPay.lib.WxPayApi');
    vendor('WxPay.lib.WxPayConfig');
    vendor('WxPay.lib.log');
    try {
        $input = new WxPayRefund();
        $input->SetTransaction_id($pay_transaction_id);
        $input->SetTotal_fee($pay_total_fee);
        $input->SetRefund_fee($pay_refund_fee);
        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
        if (empty($wx_payments['payConfig'])) {
            return returnData(false, -1, 'error', '微信配置有误');
        }
        $wx_config = json_decode($wx_payments['payConfig'], true);
        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);
        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
        $input->SetOp_user_id($wx_config['mchId']);
        $resdata = WxPayApi::refund($wx_config, $input);
        unlink($wx_config['sslCertPath']);
        unlink($wx_config['sslKeyPath']);
        if ($resdata['err_code'] == "NOTENOUGH" || $resdata['err_code'] == "INVALID_REQUEST") {//临时针对性修复
            return returnData(false, -1, 'error', $resdata["err_code_des"]);
        }
        if ($resdata['result_code'] == 'SUCCESS') {
            return returnData(true);
        } else {
//            return returnData(false, -1, 'error', '退款失败');
            $err_msg = '退款失败';
            if (!empty($resdata['return_msg'])) {
                $err_msg = $resdata['return_msg'];
            }
            return returnData(false, -1, 'error', $err_msg);
        }

    } catch (Exception $e) {
        return returnData(false, -1, 'error', '退款失败');
        //Log::ERROR(json_encode($e));
    }
}

/*指定微信平台流水号进行退款
*tradeNo 流水号
*needPay 金额 单位元
*$orderId 订单id
 *$orderStatus 取消订单前的状态
 * @param int $logUserId 操作用户id
*/
//function order_WxPayRefund($tradeNo, $needPay, $orderId, $orderStatus = 0,$logUserId=0)
//{
//    //判断此订单是否已经退款
//    vendor('WxPay.lib.WxPayApi');
//    vendor('WxPay.lib.WxPayConfig');
//    vendor('WxPay.lib.log');
//    $config = $GLOBALS['CONFIG'];
//    try {
//        $orders = M('orders');
//        $orderInfo = $orders->where("orderId='" . $orderId . "'")->find();
//        $totalFee = $orderInfo['realTotalMoney'];
//        $returnFee = $orderInfo['realTotalMoney'];
//        if ($config['setDeliveryMoney'] == 2) {
//            //兼容多商户和统一运费
//            $orderTokenInfo = M('order_merge')->where(['orderToken' => $orderInfo['orderToken']])->find();
//            $orderNoArr = explode('A', $orderTokenInfo['value']);
//            $totalFee = 0;
//            foreach ($orderNoArr as $key => $value) {
//                $orderSingle = $orders->where(['orderNo' => $value, 'isPay' => 1])->find();
//                if ($orderSingle) {
//                    //$realTotalMoney = $orderSingle['realTotalMoney'] + $orderSingle['deliverMoney'];
//                    $realTotalMoney = $orderSingle['realTotalMoney'];
//                    $totalFee += $realTotalMoney;
//                }
//            }
//        }
//        if (in_array($orderStatus, [0, 13, 14]) && $config['setDeliveryMoney'] == 2) {//非统一运费,实付金额已经包含了运费
//            //$returnFee += $orderInfo['deliverMoney'];
//        }
//        //在这里处理下传过来的参数
//        $input = new WxPayRefund();
//        $input = new WxPayRefund();
//        $input->SetTransaction_id($tradeNo);
//        $input->SetTotal_fee($totalFee * 100);
//        $input->SetRefund_fee($returnFee * 100);
//
////        $config = new WxPayConfig();
//
//        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
//        if (empty($wx_payments['payConfig'])) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            return $apiRet;
//        }
//        $wx_config = json_decode($wx_payments['payConfig'], true);
//        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
//        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);
//
//        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
//        $input->SetOp_user_id($wx_config['mchId']);
//        $resdata = WxPayApi::refund($wx_config, $input);
//
//        unlink($wx_config['sslCertPath']);
//        unlink($wx_config['sslKeyPath']);
//
//        if ($resdata['result_code'] == 'FAIL' || $resdata['return_code'] == 'FAIL') {
//            return -3;//退款失败
//        }
//
//        //更改订单为已退款  //------可增加事物
//        $save_orders['isRefund'] = 1;
//        $orders->where("orderId = " . $orderId)->save($save_orders);
//
//
//        //写入订单日志
//        $log_orders = M("log_orders");
//        $data["orderId"] = $orderId;
//        $logContent = $logUserId>0?"平台审核拒绝，发起微信退款：" . $returnFee . '元':"用户取消订单，发起微信退款：" . $returnFee . '元';
//        $data["logContent"] = $logContent;
//        $data["logUserId"] = $logUserId>0?$logUserId:$orderInfo['userId'];
//        $data["logType"] = $logUserId>0?"1":"0";
//        $data["logTime"] = date("Y-m-d H:i:s");
//        $log_orders->add($data);
//
//    } catch (Exception $e) {
//        return -2;//退款失败
//        //Log::ERROR(json_encode($e));
//    }
//
//
//}


/**
 *指定微信平台流水号进行退款
 * @param string $tradeNo 交易号
 * @param int $orderId 订单id
 * @param int $orderStatus 取消订单前的状态
 * @param int $loginType 类型【0：用户|1：商户|2:系统】
 * @param array $loginUserInfo 当前操作者信息<p>
 * int user_id 操作者id
 * string user_username 操作者名称
 * <p>
 * */
function order_WxPayRefund($tradeNo, $orderId, $orderStatus = 0, $loginType = 0, $loginUserInfo = [])
{
    //判断此订单是否已经退款
    vendor('WxPay.lib.WxPayApi');
    vendor('WxPay.lib.WxPayConfig');
    vendor('WxPay.lib.log');
    $config = $GLOBALS['CONFIG'];
    try {
        $orders = M('orders');
        $orderInfo = $orders->where("orderId='" . $orderId . "'")->find();
        $totalFee = $orderInfo['realTotalMoney'];
        $returnFee = $orderInfo['realTotalMoney'];
        if ($config['setDeliveryMoney'] == 2) {//废弃
            //兼容多商户和统一运费
            $orderTokenInfo = M('order_merge')->where(['orderToken' => $orderInfo['orderToken']])->find();
            $orderNoArr = explode('A', $orderTokenInfo['value']);
            $totalFee = 0;
            foreach ($orderNoArr as $key => $value) {
                $orderSingle = $orders->where(['orderNo' => $value, 'isPay' => 1])->find();
                if ($orderSingle) {
                    //$realTotalMoney = $orderSingle['realTotalMoney'] + $orderSingle['deliverMoney'];
                    $realTotalMoney = $orderSingle['realTotalMoney'];
                    $totalFee += $realTotalMoney;
                }
            }
        } else {//处理常规非常规订单拆单后运费问题
            $orderTokenInfo = M('order_merge')->where(['orderToken' => $orderInfo['orderToken']])->find();
            $orderNoArr = explode('A', $orderTokenInfo['value']);
            if (count($orderNoArr) > 1) {
                $totalFee = 0;
                foreach ($orderNoArr as $key => $value) {
                    $orderSingle = $orders->where(['orderNo' => $value, 'isPay' => 1])->find();
                    if ($orderSingle) {
                        $realTotalMoney = $orderSingle['realTotalMoney'];
                        $totalFee += $realTotalMoney;
                    }
                }
            }
        }
        if (in_array($orderStatus, [0, 13, 14]) && $config['setDeliveryMoney'] == 2) {//非统一运费,实付金额已经包含了运费
            //$returnFee += $orderInfo['deliverMoney'];
        }
        //在这里处理下传过来的参数
        $input = new WxPayRefund();
        $input->SetTransaction_id($tradeNo);
        $input->SetTotal_fee($totalFee * 100);
        $input->SetRefund_fee($returnFee * 100);

//        $config = new WxPayConfig();

        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
        if (empty($wx_payments['payConfig'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数不全';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $wx_config = json_decode($wx_payments['payConfig'], true);
        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);

        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
        $input->SetOp_user_id($wx_config['mchId']);
        $resdata = WxPayApi::refund($wx_config, $input);

        unlink($wx_config['sslCertPath']);
        unlink($wx_config['sslKeyPath']);

        if ($resdata['result_code'] == 'FAIL' || $resdata['return_code'] == 'FAIL') {
            return -3;//退款失败
        }

        //更改订单为已退款  //------可增加事物
        $save_orders['isRefund'] = 1;
        $orders->where("orderId = " . $orderId)->save($save_orders);

        if ($loginType == 0) {
            //用户
            $logUserId = $loginUserInfo['user_id'];
            $logUserName = '用户';
            $content = "用户取消订单，发起微信退款：{$returnFee}元";
        } elseif ($loginType == 1) {
            //商户
            $logUserId = $loginUserInfo['user_id'];
            $logUserName = $loginUserInfo['user_username'];
            $content = "商户取消订单，发起微信退款：{$returnFee}元";
        } else {
            //系统
            $logUserId = 0;
            $logUserName = '系统';
            $content = "平台审核拒绝，发起微信退款：{$returnFee}元";
        }
        //写入订单日志
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => (int)$logUserId,
            'logUserName' => (string)$logUserName,
            'orderStatus' => $orderInfo['orderStatus'],
            'payStatus' => 2,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        M('log_orders')->add($logParams);

//        $log_orders = M("log_orders");
//        $data["orderId"] = $orderId;
//        $logContent = $logUserId>0?"平台审核拒绝，发起微信退款：" . $returnFee . '元':"用户取消订单，发起微信退款：" . $returnFee . '元';
//        $data["logContent"] = $logContent;
//        $data["logUserId"] = $logUserId>0?$logUserId:$orderInfo['userId'];
//        $data["logType"] = $logUserId>0?"1":"0";
//        $data["logTime"] = date("Y-m-d H:i:s");
//        $log_orders->add($data);

    } catch (Exception $e) {
        return -2;//退款失败
        //Log::ERROR(json_encode($e));
    }


}


//微信退款-----商品差价退款
/*******
 * transaction_id 微信订单号
 * total_fee 订单总金额(分)
 * refund_fee 退款金额(分)
 * orderId    订单id
 * goodsId        商品Id
 * logUserId    操作者Id
 * userId        用户Id
 *
 * return
 * -1;//数据验证不通过
 * -4;//有参数为空
 * -3;//退款失败
 * -2;//退款异常
 * true 成功
 * false 失败
 *
 * wxRefund('4200000199201811257658840011','1099','99','36','2')
 ********/
//function wxRefundGoods($transaction_id, $total_fee, $refund_fee, $orderId, $goodsId, $logUserId, $userId)
//{
//
//    if ((isset($transaction_id) && $transaction_id != ""
//            && !preg_match("/^[0-9a-zA-Z]{10,64}$/i", $transaction_id, $matches))
//        || (isset($total_fee) && $total_fee != ""
//            && !preg_match("/^[0-9]{0,10}$/i", $total_fee, $matches))
//        || (isset($refund_fee) && $refund_fee != ""
//            && !preg_match("/^[0-9]{0,10}$/i", $refund_fee, $matches))) {
//        return -1;//数据验证不通过
//    }
//
//    if (empty($orderId) or empty($goodsId) or empty($logUserId)) {
//        return -4;//有参数为空
//    }
//
//    //判断此商品是否已经退款
//    $goods_pricediffe = M('goods_pricediffe');
//    $where['orderId'] = $orderId;
//    $where['goodsId'] = $goodsId;
//    $where['userId'] = $userId;
//    $where['isPay'] = 1;
//    if ($goods_pricediffe->where($where)->count() > 0) {
//        return -3;//退款失败
//    }
//
//
//    vendor('WxPay.lib.WxPayApi');
//    vendor('WxPay.lib.WxPayConfig');
//    vendor('WxPay.lib.log');
//
//    try {
//        $input = new WxPayRefund();
//        $input->SetTransaction_id($transaction_id);
//        $input->SetTotal_fee($total_fee);
//        $input->SetRefund_fee($refund_fee);
//
////        $config = new WxPayConfig();
//
//        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
//        if (empty($wx_payments['payConfig'])) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            return $apiRet;
//        }
//        $wx_config = json_decode($wx_payments['payConfig'], true);
//        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
//        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);
//
//        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
//        $input->SetOp_user_id($wx_config['mchId']);
//        $resdata = WxPayApi::refund($wx_config, $input);
//
//        unlink($wx_config['sslCertPath']);
//        unlink($wx_config['sslKeyPath']);
//
//        if ($resdata['result_code'] == 'FAIL') {
//
//            return -3;//退款失败
//        }
//
//
//        //写入订单日志
//        $log_orders = M("log_orders");
//        $data["orderId"] = $orderId;
//        $data["logContent"] = "发起微信退款：" . $refund_fee / 100 . '元';
//        $data["logUserId"] = $logUserId;
//        $data["logType"] = "0";
//        $data["logTime"] = date("Y-m-d H:i:s");
//
//        $log_orders->add($data);
//
//
//        //更改退款记录
//
//        $save_data['isPay'] = 1;
//        $save_data['payTime'] = date('Y-m-d H:i:s');
//
//
//        if (M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$goodsId} and userId= {$userId}")->save($save_data)) {
//            return true;//退款申请成功
//        } else {
//            return false;
//        }
//
//    } catch (Exception $e) {
//        return -2;//退款失败
//        //Log::ERROR(json_encode($e));
//    }
//
//}

/**
 * 商品差价退款-微信
 * @param string $transaction_id 交易号
 * @param float $total_fee 订单实付金额
 * @param float $refund_fee 退款金额
 * @param int $orderId 订单id
 * @param int $goodsId 商品id
 * @param int $skuId 商品skuId
 * @param int $loginType 类型【0：用户|1：商户|2：系统】
 * @param array $loginUserInfo
 * */
function wxRefundGoods($transaction_id, $total_fee, $refund_fee, $orderId, $goodsId, $skuId, $loginType, $loginUserInfo)
{
    if ((isset($transaction_id) && $transaction_id != ""
            && !preg_match("/^[0-9a-zA-Z]{10,64}$/i", $transaction_id, $matches))
        || (isset($total_fee) && $total_fee != ""
            && !preg_match("/^[0-9]{0,10}$/i", $total_fee, $matches))
        || (isset($refund_fee) && $refund_fee != ""
            && !preg_match("/^[0-9]{0,10}$/i", $refund_fee, $matches))) {
        return -1;//数据验证不通过
    }
    if (empty($orderId) or empty($goodsId)) {
        return -4;//有参数为空
    }
    $orderInfo = M('orders')->where(['orderId' => $orderId])->find();
    if (empty($orderInfo)) {
        return -3;//退款失败
    }
    $userId = $orderInfo['userId'];
    //判断此商品是否已经退款
    $goods_pricediffe = M('goods_pricediffe');
    $where = [];
    $where['orderId'] = $orderId;
    $where['userId'] = $userId;
    $where['goodsId'] = $goodsId;
    $where['skuId'] = $skuId;
    $where['isPay'] = 1;
    if ($goods_pricediffe->where($where)->count() > 0) {
        return -3;//退款失败
    }
    vendor('WxPay.lib.WxPayApi');
    vendor('WxPay.lib.WxPayConfig');
    vendor('WxPay.lib.log');
    try {
        $input = new WxPayRefund();
        $input->SetTransaction_id($transaction_id);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($refund_fee);
        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
        if (empty($wx_payments['payConfig'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数不全';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $wx_config = json_decode($wx_payments['payConfig'], true);
        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);

        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
        $input->SetOp_user_id($wx_config['mchId']);
        $resdata = WxPayApi::refund($wx_config, $input);

        unlink($wx_config['sslCertPath']);
        unlink($wx_config['sslKeyPath']);

        if ($resdata['result_code'] == 'FAIL') {

            return -3;//退款失败
        }


        //写入订单日志
//        $log_orders = M("log_orders");
//        $data["orderId"] = $orderId;
//        $data["logContent"] = "发起微信退款：" . $refund_fee / 100 . '元';
//        $data["logUserId"] = $logUserId;
//        $data["logType"] = "0";
//        $data["logTime"] = date("Y-m-d H:i:s");
//
//        $log_orders->add($data);
        if ($loginType == 0) {
            $loginUserId = $loginUserInfo['user_id'];
            $loginUserName = '用户';
        } elseif ($loginType == 1) {
            $loginUserId = $loginUserInfo['user_id'];
            $loginUserName = $loginUserInfo['user_username'];
        } else {
            $loginUserId = 0;
            $loginUserName = '系统';
        }
        $content = "发起微信退款：" . $refund_fee / 100 . '元';
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => (int)$loginUserId,
            'logUserName' => (string)$loginUserName,
            'orderStatus' => $orderInfo['orderStatus'],
            'payStatus' => 1,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        M('log_orders')->add($logParams);


        //更改退款记录

        $save_data['isPay'] = 1;
        $save_data['payTime'] = date('Y-m-d H:i:s');


        if (M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$goodsId} and userId= {$userId}")->save($save_data)) {
            return true;//退款申请成功
        } else {
            return false;
        }

    } catch (Exception $e) {
        return -2;//退款失败
        //Log::ERROR(json_encode($e));
    }

}


/**
 * 订单-确认收货 && 拒收 废弃
 */
function ConfirmReceipt($loginName, $orderId, $type, $rejectionRemarks = '', $logParams = array())
{
    $where["loginName"] = $loginName;
    $where["userFlag"] = 1;
    //$user = M("users")->where($where)->field(['userId','firstOrder'])->find();
    $user = M("users")->where($where)->find();

    //用于处理首单状态
    $firstOrder = $user['firstOrder'];
    $firstOrderNew = $firstOrder;
    //$that = new \Think\Model;//可用
    $that = M();

    $userId = (int)$user["userId"];
    $orderId = (int)$orderId;
    $type = (int)$type;
    $rsdata = array();
    $sql = "SELECT orderId,orderNo,orderScore,orderStatus,poundageRate,poundageMoney,shopId,useScore,scoreMoney,payType,payFrom FROM __PREFIX__orders WHERE orderId = $orderId and userId=" . $userId;
    //$rsv = $this->queryRow($sql);//只能在控制器或者类库使用
    $rsv = $that->query($sql)[0];
//    if ($rsv["orderStatus"] != 3) {
//        $statusCode["statusCode"] = "000064";
//        return $statusCode;
//    }
    M()->startTrans();
    //收货则给用户增加积分
    if ($type == 1) {
        $set_param = "orderStatus = 4,receiveTime='" . date("Y-m-d H:i:s") . "'";
        if (empty($rsv['payType'])) $set_param .= ",isPay=1";
        if ($rsv['payFrom'] == 4) {
            $pay_time = date('Y-m-d H:i:s');
            $set_param .= ",pay_time='{$pay_time}' ";
        }
//        $sql = "UPDATE __PREFIX__orders set orderStatus = 4,receiveTime='".date("Y-m-d H:i:s")."'  WHERE orderId = $orderId and userId=".$userId;
        $sql = "UPDATE __PREFIX__orders set " . $set_param . "  WHERE orderId = $orderId and userId=" . $userId;
        $rs = $that->execute($sql);


        //修改商品销量
//        $sql = "UPDATE __PREFIX__goods g, __PREFIX__order_goods og, __PREFIX__orders o SET g.saleCount=g.saleCount+og.goodsNums WHERE g.goodsId= og.goodsId AND og.orderId = o.orderId AND o.orderId=$orderId AND o.userId=" . $userId;
//        $rs = $that->execute($sql);
        $order_service_module = new \App\Modules\Orders\OrdersServiceModule();
        $save_sale_res = $order_service_module->IncOrderGoodsSale($orderId, M());//增加商品销量
        if (!$save_sale_res) {
            M()->rollback();
            $statusCode["statusCode"] = "000063";
            $statusCode["info"] = "操作失败，销量增长失败";
            return $statusCode;
        }

        //修改积分
        if ($GLOBALS['CONFIG']['isOrderScore'] == 1 && $rsv["orderScore"] > 0) {
            $sql = "UPDATE __PREFIX__users set userScore = userScore + " . $rsv["orderScore"] . ",userTotalScore = userTotalScore+" . $rsv["orderScore"] . " WHERE userId =" . $userId;
            $rs = $that->execute($sql);

            $data = array();
            $us = M('user_score');
            $data["userId"] = $userId;
            $data["score"] = $rsv["orderScore"];
            $data["dataSrc"] = 1;
            $data["dataId"] = $orderId;
            $data["dataRemarks"] = "交易获得";
            $data["scoreType"] = 1;
            $data["createTime"] = date('Y-m-d H:i:s');
            $us->add($data);
        }
        //积分支付支出
        if ($rsv["scoreMoney"] > 0) {
            $data = array();
            $m = M('log_sys_moneys');
            $data["targetType"] = 0;
            $data["targetId"] = $userId;
            $data["dataSrc"] = 2;
            $data["dataId"] = $orderId;
            $data["moneyRemark"] = "订单【" . $rsv["orderNo"] . "】支付 " . $rsv["useScore"] . " 个积分，支出 ￥" . $rsv["scoreMoney"];
            $data["moneyType"] = 2;
            $data["money"] = $rsv["scoreMoney"];
            $data["createTime"] = date('Y-m-d H:i:s');
            $data["dataFlag"] = 1;
            $m->add($data);
        }
        //收取订单佣金
        if ($rsv["poundageMoney"] > 0) {
            $data = array();
            $m = M('log_sys_moneys');
            $data["targetType"] = 1;
            $data["targetId"] = $rsv["shopId"];
            $data["dataSrc"] = 1;
            $data["dataId"] = $orderId;
            $data["moneyRemark"] = "收取订单【" . $rsv["orderNo"] . "】" . $rsv["poundageRate"] . "%的佣金 ￥" . $rsv["poundageMoney"];
            $data["moneyType"] = 1;
            $data["money"] = $rsv["poundageMoney"];
            $data["createTime"] = date('Y-m-d H:i:s');
            $data["dataFlag"] = 1;
            $m->add($data);
        }

        //判断商品是否属于分销商品
        checkGoodsDistribution($orderId);
        //发放地推邀请奖励
        grantPullNewAmount($orderId);
    } else {
        if ($rejectionRemarks == '') return $rsdata;//如果是拒收的话需要填写原因
        $sql = "UPDATE __PREFIX__orders set orderStatus = -3 WHERE orderId = $orderId and userId=" . $userId;
        $rs = $that->execute($sql);
    }

    //判断当前订单是否有差价(列表)需要退

    $mod_goods_pricediffe = M('goods_pricediffe');
    $finwhere['orderId'] = $orderId;
    $finwhere['userId'] = $userId;
    $finwhere['isPay'] = 0;
    $data_goods_pricediffe = $mod_goods_pricediffe->where($finwhere)->select();
    $mod_orders = M('orders');
    $data_mod_orders = $mod_orders->where("orderId ={$orderId} and userId={$userId}")->find();
    $order_module = new \App\Modules\Orders\OrdersModule();
    $goods_module = new \App\Modules\Goods\GoodsModule();
    if (count($data_goods_pricediffe) > 0) {//如果有需要退的 进行退款操作
        //退款备参
        $pay_transaction_id = $data_mod_orders['tradeNo'];
        $pay_total_fee = $data_mod_orders['realTotalMoney'] * 100;

        for ($i = 0; $i < count($data_goods_pricediffe); $i++) {
            $goods_id = (int)$data_goods_pricediffe[$i]['goodsId'];
            $sku_id = (int)$data_goods_pricediffe[$i]['skuId'];
            $goods_num = (int)$data_goods_pricediffe[$i]['goosNum'];
            $real_weight = (float)$data_goods_pricediffe[$i]['weightG'];//实际称重
            $goods_field = 'goodsId,SuppPriceDiff,weightG';
            $goods_data = $goods_module->getGoodsInfoById($goods_id, $goods_field, 2);
            $goods_weightG = (float)$goods_data['weightG'];//包装系数
            if ($sku_id > 0) {
                $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                $goods_weightG = (float)$sku_detail['weigetG'];
            }
//            $buy_weight = $goods_num * $goods_weightG;//购买的重量数量
//            //返还商品库存-start
//            $return_stock = $buy_weight - $real_weight;
//            if ($return_stock > 0) {//返库存
//                $order_goods_result = $order_module->getOrderGoodsInfoByParams(array(
//                    'orderId' => $data_goods_pricediffe[$i]['orderId'],
//                    'goodsId' => $data_goods_pricediffe[$i]['goodsId'],
//                    'skuId' => $data_goods_pricediffe[$i]['skuId']
//                ));
//                if ($order_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
//                    if ($goods_data['SuppPriceDiff'] == 1) {
//                        $return_stock = $return_stock / 1000;
//                    }
//                    $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $return_stock, 1, 2, M());
//                }
//            }
//            //返还商品库存-end
            $pay_refund_fee = $data_goods_pricediffe[$i]['money'] * 100;
            if ($data_mod_orders['payFrom'] == 2) {
                //微信出差价退款
                $repay = wxRefundGoods($pay_transaction_id, $pay_total_fee, $pay_refund_fee, $orderId, $data_goods_pricediffe[$i]['goodsId'], $data_goods_pricediffe[$i]['skuId'], 2, []);
            } elseif ($data_mod_orders['payFrom'] == 3) {
                //余额补差价退款
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                $userBalance = M('users')->where(['userId' => $userId])->getField('balance');
                $saveData = [];
                $saveData['balance'] = $userBalance + $refundFee;

                $refundRes = M('users')->where(['userId' => $userId])->save($saveData);
                if ($refundRes === false) {
                    $repay = false;
                }
                //写入订单日志
//                $log_orders = M("log_orders");
//                $data = [];
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "补差价退款：" . $refundFee . '元';
//                $data["logUserId"] = $userId;
//                $data["logType"] = "0";
//                $data["logTime"] = date("Y-m-d H:i:s");
//                $log_orders->add($data);
                $content = "补差价退款：" . $refundFee . '元';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 4,
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);

                //补差价余额日志
                $userBalanceTable = M('user_balance');
                $userBalanceData = [];
                $userBalanceData['userId'] = $userId;
                $userBalanceData['balance'] = $refundFee;
                $userBalanceData['dataSrc'] = 1;
                $userBalanceData['orderNo'] = $rsv['orderNo'];
                $userBalanceData['dataRemarks'] = "补差价退款：" . $refundFee . '元';
                $userBalanceData['balanceType'] = 1;
                $userBalanceData['createTime'] = date("Y-m-d H:i:s");
                $userBalanceData['shopId'] = $rsv['shopId'];
                $userBalanceTable->add($userBalanceData);

                //更改退款记录
                $save_data = [];
                $save_data['isPay'] = 1;
                $save_data['payTime'] = date('Y-m-d H:i:s');
                $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$data_goods_pricediffe[$i]['goodsId']} and userId= {$userId} and skuId={$data_goods_pricediffe[$i]['skuId']}")->save($save_data);
                if ($diffRes) {
                    $repay = true;//退款申请成功
                } else {
                    $repay = false;
                }
            }
            if ($repay !== true) {
                M()->rollback();
                $statusCode["statusCode"] = "000063";
                $statusCode["info"] = "差价退款失败";
                $statusCode["data"] = $repay;
                return $statusCode;
            }
        }
    }
    //判断是否是首次下单
    //是否奖励邀请券 判断是否是第一次下单(第一笔订单 之前一定是0笔) 且是否拥有邀请人 并邀请人有优惠券待恢复使用
    //if(M('orders')->limit(1)->where("orderStatus=4 and userId = '{$userId}'")->count() == 0){
    //改写首单判断逻辑 根据users表-firstOrder字段判断是否为首单 -1非首单 1首单
    $mod_user_Invitation = M('user_invitation');
    if ($user['firstOrder'] == 1) {
//    if (M('orders')->limit(1)->where("orderStatus=4 and userId = '{$userId}'")->count() == 1) {
        //本次订单是否满足十元
        //判断被邀请人ID是否还存在
        $find_user_invitation = M('user_invitation')->where("UserToId = '{$userId}'")->find();
        $userInfo = M('users')->where("userId = '{$find_user_invitation['userId']}' and userFlag = 1")->find();
        if ($userInfo) {
            //订单完成后更新用户的邀新状态
            $saveData['invitationStatus'] = 1;
            $saveData['updateTime'] = date('Y-m-d H:i:s');
            $res = $mod_user_Invitation->where("UserToId = '{$userId}'")->save($saveData);
        }
        if ($data_mod_orders['realTotalMoney'] >= $GLOBALS["CONFIG"]["InvitationOrderMoney"]) {
            //查询是否存在邀请人
            if ($find_user_invitation) {
                //是否存在待恢复使用的优惠券
                $mod_coupons_users = M('coupons_users');
                $coupons_save['dataFlag'] = 1;//这里的删除状态一定要注意 如果删除用户优惠券之后 会导致 下次邀请成功 恢复所有优惠券的问题
                $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->save($coupons_save);


                // $res_mod_coupons_users = $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->select();

                // if($res_mod_coupons_users){
                //   //恢复冻结的优惠券
                //
                //   for($i=0;$i<count($res_mod_coupons_users);$i++){
                //     $coupons_users_save['dataFlag'] = 1;
                //     $mod_coupons_users->where("id ='{$res_mod_coupons_users[$i]['id']}'")->save($coupons_users_save);
                //   }
                //
                //
                // }

            }
            //领过权益以后 首单状态更新
//            $userSave['firstOrder'] = -1;
//            M('users')->where("userId = '{$userId}' and userFlag = 1")->save($userSave);
        }
        $userSave['firstOrder'] = -1;
        M('users')->where("userId = '{$userId}' and userFlag = 1")->save($userSave);
    }
    //用户邀请
    $uim = M('user_invitation');
    $uiData = $uim->where("UserToId={$userId}")->find();
    if (!empty($uiData) && $uiData['inviteRewardNum'] > 0) {
        //用于判断是否减去邀请奖励次数
        $typeStatus = 0;
        //-------------------------------------
        $inviteNumRules = (int)$GLOBALS["CONFIG"]['inviteNumRules'];  //1.优惠券||2.返现||3.积分
        if ($inviteNumRules == 1 && $firstOrderNew == -1) {             //优惠券
            //获取邀请优惠券
            $where = [];
            $where['dataFlag'] = 1;
            $where['couponType'] = 3;
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->select();
            $m = D("V3/Api");
            for ($i = 0; $i < count($data); $i++) {
                $m->okCoupons($uiData['userId'], $data[$i]['couponId'], 3, 1, $userId);
            }
            //用于判断是否走到这里
            $typeStatus = 1;
        } elseif ($inviteNumRules == 2) {          //返现
            $invitationMoney = $GLOBALS["CONFIG"]['InvitationMoney'];        //返现百分比
            $num = $data_mod_orders['realTotalMoney'] * $invitationMoney / 100;
            //余额消费记录
            M('user_balance')->add(array(
                'userId' => $uiData['userId'],
                'balance' => $num,
                'dataSrc' => 1,
                'orderNo' => $orderId,
                'dataRemarks' => "邀请用户订单返现",
                'balanceType' => 1,
                'createTime' => date('Y-m-d H:i:s'),
                'shopId' => 0
            ));
            $balance = M('users')->where(['userId' => $uiData['userId']])->getField('balance');
            $balance += $num;
            M('users')->where(['userId' => $uiData['userId']])->save(['balance' => $balance]);
            //用于判断是否走到这里
            $typeStatus = 1;
        } elseif ($inviteNumRules == 3) {                               //积分
            $mod_users = M('users');
            $num = explode("-", $GLOBALS["CONFIG"]['InvitationRange']);
            $Integral = rand($num[0], $num[1]);
            $mod_users->where("userId = '{$uiData['userId']}'")->setInc('userScore', (int)$Integral);
            //添加积分记录
            $data = array();
            $us = M('user_score');
            $data["userId"] = $uiData['userId'];//这里是邀请者，不是被邀请者
            $data["score"] = $Integral;
            $data["dataSrc"] = 8;//8：小程序邀请好友获得
            $data["dataId"] = $orderId;
            $data["dataRemarks"] = "邀请好友赠送获得";
            $data["scoreType"] = 1;
            $data["createTime"] = date('Y-m-d H:i:s');
            $us->add($data);

            //用于判断是否走到这里
            $typeStatus = 1;
        }
        if ($typeStatus == 1) {
            $mod_user_Invitation->where("userId = '{$uiData['userId']}'")->setDec('inviteRewardNum');
        }
    }

    //增加记录
//    $data = array();
//    $m = M('log_orders');
//    $data["orderId"] = $orderId;
//    $data["logContent"] = ($type == 1) ? "用户已收货" : "用户拒收：" . $rejectionRemarks;
//    $data["logUserId"] = $userId;
//    $data["logType"] = 0;
//    $data["logTime"] = date('Y-m-d H:i:s');
//    $ra = $m->add($data);
    if (empty($logParams)) {
        $content = ($type == 1) ? "用户已收货" : "用户拒收：" . $rejectionRemarks;
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => $userId,
            'logUserName' => '用户',
            'orderStatus' => 4,
            'payStatus' => 1,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
    }
    $ra = M('log_orders')->add($logParams);
    if ($ra) {
        M()->commit();
        //订单送达时通知
        $push = D('Adminapi/Push');
        $push->postMessage(10, $userId, $rsv['orderNo'], $rsv['shopId']);

        $statusCode["statusCode"] = "000062";
        return $statusCode;
    } else {
        M()->rollback();
        $statusCode["statusCode"] = "000063";
        return $statusCode;
    }
}

/**
 * @param $params
 * 用户收货|骑手完成 调用
 * 修改商品销量
 * 修改积分
 * 积分支付支出
 * 收取订单佣金
 */
function editOrderInfo($params)
{
    $that = M();
    $orderId = $params['orderId'];
    $userId = $params['userId'];
    $rsv = $params['rsv'];
    //修改商品销量
    $sql = "UPDATE __PREFIX__goods g, __PREFIX__order_goods og, __PREFIX__orders o SET g.saleCount=g.saleCount+og.goodsNums WHERE g.goodsId= og.goodsId AND og.orderId = o.orderId AND o.orderId=$orderId AND o.userId=" . $userId;
    $rs = $that->execute($sql);

    //修改积分
    if ($GLOBALS['CONFIG']['isOrderScore'] == 1 && $rsv["orderScore"] > 0) {
        $sql = "UPDATE __PREFIX__users set userScore = userScore + " . $rsv["orderScore"] . ",userTotalScore = userTotalScore+" . $rsv["orderScore"] . " WHERE userId =" . $userId;
        $rs = $that->execute($sql);

        $data = array();
        $us = M('user_score');
        $data["userId"] = $userId;
        $data["score"] = $rsv["orderScore"];
        $data["dataSrc"] = 1;
        $data["dataId"] = $orderId;
        $data["dataRemarks"] = "交易获得";
        $data["scoreType"] = 1;
        $data["createTime"] = date('Y-m-d H:i:s');
        $us->add($data);
    }
    //积分支付支出
    if ($rsv["scoreMoney"] > 0) {
        $data = array();
        $m = M('log_sys_moneys');
        $data["targetType"] = 0;
        $data["targetId"] = $userId;
        $data["dataSrc"] = 2;
        $data["dataId"] = $orderId;
        $data["moneyRemark"] = "订单【" . $rsv["orderNo"] . "】支付 " . $rsv["useScore"] . " 个积分，支出 ￥" . $rsv["scoreMoney"];
        $data["moneyType"] = 2;
        $data["money"] = $rsv["scoreMoney"];
        $data["createTime"] = date('Y-m-d H:i:s');
        $data["dataFlag"] = 1;
        $m->add($data);
    }
    //收取订单佣金
    if ($rsv["poundageMoney"] > 0) {
        $data = array();
        $m = M('log_sys_moneys');
        $data["targetType"] = 1;
        $data["targetId"] = $rsv["shopId"];
        $data["dataSrc"] = 1;
        $data["dataId"] = $orderId;
        $data["moneyRemark"] = "收取订单【" . $rsv["orderNo"] . "】" . $rsv["poundageRate"] . "%的佣金 ￥" . $rsv["poundageMoney"];
        $data["moneyType"] = 1;
        $data["money"] = $rsv["poundageMoney"];
        $data["createTime"] = date('Y-m-d H:i:s');
        $data["dataFlag"] = 1;
        $m->add($data);
    }
}

/**
 * @param $params
 * @return mixed
 * 骑手完成 调用
 * 判断当前订单是否有差价(列表)需要退
 */
function editPriceDiffer($params)
{
    $orderId = $params['orderId'];
    $userId = $params['userId'];
    $rsv = $params['rsv'];

    $mod_goods_pricediffe = M('goods_pricediffe');
    $finwhere['orderId'] = $orderId;
    $finwhere['userId'] = $userId;
    $finwhere['isPay'] = 0;
    $data_goods_pricediffe = $mod_goods_pricediffe->where($finwhere)->select();
    $mod_orders = M('orders');
    $data_mod_orders = $mod_orders->where("orderId ={$orderId} and userId={$userId}")->find();
    if (count($data_goods_pricediffe) > 0) {//如果有需要退的 进行退款操作
        $payModule = new \App\Modules\Pay\PayModule();
        //退款备参
        $pay_transaction_id = $data_mod_orders['tradeNo'];
        $pay_total_fee = $data_mod_orders['realTotalMoney'] * 100;

        for ($i = 0; $i < count($data_goods_pricediffe); $i++) {


            $pay_refund_fee = $data_goods_pricediffe[$i]['money'] * 100;
            if ($data_mod_orders['payFrom'] == 2) {
                //微信出差价退款
                $repay = wxRefundGoods($pay_transaction_id, $pay_total_fee, $pay_refund_fee, $orderId, $data_goods_pricediffe[$i]['goodsId'], $data_goods_pricediffe[$i]['skuId'], 2, []);
            } elseif ($data_mod_orders['payFrom'] == 3) {
                //余额补差价退款
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                $userBalance = M('users')->where(['userId' => $userId])->getField('balance');
                $saveData = [];
                $saveData['balance'] = $userBalance + $refundFee;

                $refundRes = M('users')->where(['userId' => $userId])->save($saveData);
                if ($refundRes === false) {
                    $repay = false;
                }

                $content = "补差价退款：" . $refundFee . '元';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 4,
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);

                //补差价余额日志
                $userBalanceTable = M('user_balance');
                $userBalanceData = [];
                $userBalanceData['userId'] = $userId;
                $userBalanceData['balance'] = $refundFee;
                $userBalanceData['dataSrc'] = 1;
                $userBalanceData['orderNo'] = $rsv['orderNo'];
                $userBalanceData['dataRemarks'] = "补差价退款：" . $refundFee . '元';
                $userBalanceData['balanceType'] = 1;
                $userBalanceData['createTime'] = date("Y-m-d H:i:s");
                $userBalanceData['shopId'] = $rsv['shopId'];
                $userBalanceTable->add($userBalanceData);

                //更改退款记录
                $save_data = [];
                $save_data['isPay'] = 1;
                $save_data['payTime'] = date('Y-m-d H:i:s');
                $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$data_goods_pricediffe[$i]['goodsId']} and userId= {$userId} and skuId={$data_goods_pricediffe[$i]['skuId']}")->save($save_data);
                if ($diffRes) {
                    $repay = true;//退款申请成功
                } else {
                    $repay = false;
                }
            } elseif ($data_mod_orders['payFrom'] == 1) {
                //临时修复,原有代码直接复制过来的
                //余额补差价退款
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                $aliPayRefundRes = $payModule->aliPayRefund($data_mod_orders['tradeNo'], $refundFee);
                if ($aliPayRefundRes['code'] != 0) {
                    $repay = false;
                } else {
                    $content = "补差价退款：" . $refundFee . '元';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 4,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);

                    //更改退款记录
                    $save_data = [];
                    $save_data['isPay'] = 1;
                    $save_data['payTime'] = date('Y-m-d H:i:s');
                    $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$data_goods_pricediffe[$i]['goodsId']} and userId= {$userId} and skuId={$data_goods_pricediffe[$i]['skuId']}")->save($save_data);
                    if ($diffRes) {
                        $repay = true;//退款申请成功
                    } else {
                        $repay = false;
                    }
                }
            }
            if ($repay !== true) {
                $statusCode["statusCode"] = "000063";
                $statusCode["info"] = "差价退款失败";
                $statusCode["data"] = $repay;
                return $statusCode;
            }
        }
    }
}

/**
 * @param $params
 * 骑手完成 调用
 * 邀请有礼
 */
function editUserInfo($params)
{
    $userId = $params['userId'];
    $orderId = $params['orderId'];
    $where = [];
    $where["userId"] = $userId;
    $where["userFlag"] = 1;
    $user = M("users")->where($where)->find();

    //用于处理首单状态
    $firstOrder = $user['firstOrder'];
    $firstOrderNew = $firstOrder;

    $mod_orders = M('orders');
    $data_mod_orders = $mod_orders->where("orderId ={$orderId} and userId={$userId}")->find();

    //判断是否是首次下单
    //是否奖励邀请券 判断是否是第一次下单(第一笔订单 之前一定是0笔) 且是否拥有邀请人 并邀请人有优惠券待恢复使用
    //改写首单判断逻辑 根据users表-firstOrder字段判断是否为首单 -1非首单 1首单
    $mod_user_Invitation = M('user_invitation');
    if ($user['firstOrder'] == 1) {
        //本次订单是否满足十元
        //判断被邀请人ID是否还存在
        $find_user_invitation = M('user_invitation')->where("UserToId = '{$userId}'")->find();
        $userInfo = M('users')->where("userId = '{$find_user_invitation['userId']}' and userFlag = 1")->find();
        if ($userInfo) {
            //订单完成后更新用户的邀新状态
            $saveData['invitationStatus'] = 1;
            $saveData['updateTime'] = date('Y-m-d H:i:s');
            $res = $mod_user_Invitation->where("UserToId = '{$userId}'")->save($saveData);
        }
        if ($data_mod_orders['realTotalMoney'] >= $GLOBALS["CONFIG"]["InvitationOrderMoney"]) {
            //查询是否存在邀请人
            if ($find_user_invitation) {
                //是否存在待恢复使用的优惠券
                $mod_coupons_users = M('coupons_users');
                $coupons_save['dataFlag'] = 1;//这里的删除状态一定要注意 如果删除用户优惠券之后 会导致 下次邀请成功 恢复所有优惠券的问题
                $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->save($coupons_save);
            }
        }
        $userSave['firstOrder'] = -1;
        M('users')->where("userId = '{$userId}' and userFlag = 1")->save($userSave);
    }
    //用户邀请
    $uim = M('user_invitation');
    $uiData = $uim->where("UserToId={$userId}")->find();
    if (!empty($uiData) && $uiData['inviteRewardNum'] > 0) {
        //用于判断是否减去邀请奖励次数
        $typeStatus = 0;
        //-------------------------------------
        $inviteNumRules = (int)$GLOBALS["CONFIG"]['inviteNumRules'];  //1.优惠券||2.返现||3.积分
        if ($inviteNumRules == 1 && $firstOrderNew == -1) {             //优惠券
            //获取邀请优惠券
            $where = [];
            $where['dataFlag'] = 1;
            $where['couponType'] = 3;
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->select();
            $m = D("V3/Api");
            for ($i = 0; $i < count($data); $i++) {
                $m->okCoupons($uiData['userId'], $data[$i]['couponId'], 3, 1, $userId);
            }
            //用于判断是否走到这里
            $typeStatus = 1;
        } elseif ($inviteNumRules == 2) {          //返现
            $invitationMoney = $GLOBALS["CONFIG"]['InvitationMoney'];        //返现百分比
            $num = $data_mod_orders['realTotalMoney'] * $invitationMoney / 100;
            //余额消费记录
            M('user_balance')->add(array(
                'userId' => $uiData['userId'],
                'balance' => $num,
                'dataSrc' => 1,
                'orderNo' => $orderId,
                'dataRemarks' => "邀请用户订单返现",
                'balanceType' => 1,
                'createTime' => date('Y-m-d H:i:s'),
                'shopId' => 0
            ));
            $balance = M('users')->where(['userId' => $uiData['userId']])->getField('balance');
            $balance += $num;
            M('users')->where(['userId' => $uiData['userId']])->save(['balance' => $balance]);
            //用于判断是否走到这里
            $typeStatus = 1;
        } elseif ($inviteNumRules == 3) {                               //积分
            $mod_users = M('users');
            $num = explode("-", $GLOBALS["CONFIG"]['InvitationRange']);
            $Integral = rand($num[0], $num[1]);
            $mod_users->where("userId = '{$uiData['userId']}'")->setInc('userScore', (int)$Integral);
            //添加积分记录
            $data = array();
            $us = M('user_score');
            $data["userId"] = $uiData['userId'];//这里是邀请者，不是被邀请者
            $data["score"] = $Integral;
            $data["dataSrc"] = 8;//8：小程序邀请好友获得
            $data["dataId"] = $orderId;
            $data["dataRemarks"] = "邀请好友赠送获得";
            $data["scoreType"] = 1;
            $data["createTime"] = date('Y-m-d H:i:s');
            $us->add($data);

            //用于判断是否走到这里
            $typeStatus = 1;
        }
        if ($typeStatus == 1) {
            $mod_user_Invitation->where("userId = '{$uiData['userId']}'")->setDec('inviteRewardNum');
        }
    }
}

/*********
 *
 * 单商品退款 差价计算
 *
 * orderId 订单Id
 * goodsId 商品Id
 * userId    用户Id
 *
 * goodsPayN()
 * return 金额 单位元
 ***********/
function goodsPayN($orderId, $goodsId, $skuId, $userId)
{
    $mod_order = M('orders');
    $mod_order_goods = M('order_goods');
    $mod_coupons = M('coupons');

    $order_data = $mod_order->where("orderId = {$orderId} and userId = {$userId}")->find();
    $order_goods_data = $mod_order_goods->where("orderId = {$orderId} and goodsId={$goodsId} and skuId={$skuId}")->find();

    $coupons_data = $mod_coupons->where("couponId = {$order_data['couponId']}")->find();


    //差额算法 不退运费的话 删除 $order_data['deliverMoney'] 即可
    //$mo = $order_data['scoreMoney']+$coupons_data['couponMoney']-$order_data['deliverMoney'];//包含运费
    $mo = $order_data['scoreMoney'] + $coupons_data['couponMoney'];//不包含运费
    $mo2 = ($order_data['totalMoney'] - $mo) / $order_data['totalMoney'];

    $mo3 = $mo2 * ($order_goods_data['goodsNums'] * $order_goods_data['goodsPrice']);

    return sprintf("%.2f", substr(sprintf("%.3f", $mo3), 0, -1)); //不进行任何四舍五入

}

//###################  二开  ############################

//根据当前经纬度校验店铺是否允许配送
function checkShopDistribution($shopId, $lng, $lat)
{
    // if(!$shopId || !$lng || !$lat){
    //     return false;
    // }
    //获取店铺配置
    $shopConfm = M('shop_configs');
    $shopfm = M('shops');
    // $shopConfs = $shopConfm->where("shopId={$shopId}")->field('configId,shopId,isDis')->find();
    $shopsInfo = $shopfm->where("shopId=" . $shopId)->field('shopId,deliveryLatLng')->find();

    // if(!$shopConfs){
    //     return false;
    // }
    // if($shopConfs['isDis'] == -1){
    //     return true;
    // }
    if (!$shopsInfo['deliveryLatLng']) {//用户未规定区域
        return false;
    }


    //辉 修复此编码 在复杂情况下这样字符替换会导致严重问题
    //$deliveryLatLng = str_replace("&quot;",'"',$shopsInfo['deliveryLatLng']);

    $deliveryLatLng = htmlspecialchars_decode($shopsInfo['deliveryLatLng']);
    $pts = json_decode($deliveryLatLng, 1);

    foreach ($pts as $data) {
        if (!empty($data['M'])) {
            $lng_M = $data['M'];
        } else {
            $lng_M = $data['lng'];
        }
        if (!empty($data['O'])) {
            $lat_O = $data['O'];
        } else {
            $lat_O = $data['lat'];
        }

        $arrlnglat[] = array('lng' => $lng_M, 'lat' => $lat_O);
        //$arrlnglat[] = array('lng' => $data['M'], 'lat' => $data['O']);

    }


    if (!$pts || !is_array($pts)) {
        return false;
    }


    //辉 修复 百度转高德
//    $arr = bd_decrypt($lng, $lat);
//    $lng = $arr['gg_lon'];
//    $lat = $arr['gg_lat'];

    $point = [
        'lng' => $lng,
        'lat' => $lat,
    ];
    //检测
    return is_point_in_polygon($point, $arrlnglat);
}

/**
 * 判断一个坐标是否在一个多边形内（由多个坐标围成的）
 * 基本思想是利用射线法，计算射线与多边形各边的交点，如果是偶数，则点在多边形外，否则
 * 在多边形内。还会考虑一些特殊情况，如点在多边形顶点上，点在多边形边上等特殊情况。
 * @param $point 指定点坐标
 * @param $pts 多边形坐标 顺时针方向
 */
function is_point_in_polygon($point, $pts)
{
    $N = count($pts);
    $boundOrVertex = true; //如果点位于多边形的顶点或边上，也算做点在多边形内，直接返回true
    $intersectCount = 0;//cross points count of x
    $precision = 2e-10; //浮点类型计算时候与0比较时候的容差
    $p1 = 0;//neighbour bound vertices
    $p2 = 0;
    $p = $point; //测试点

    $p1 = $pts[0];//left vertex
    for ($i = 1; $i <= $N; ++$i) {//check all rays
        // dump($p1);
        if ($p['lng'] == $p1['lng'] && $p['lat'] == $p1['lat']) {
            return $boundOrVertex;//p is an vertex
        }

        $p2 = $pts[$i % $N];//right vertex
        if ($p['lat'] < min($p1['lat'], $p2['lat']) || $p['lat'] > max($p1['lat'], $p2['lat'])) {//ray is outside of our interests
            $p1 = $p2;
            continue;//next ray left point
        }

        if ($p['lat'] > min($p1['lat'], $p2['lat']) && $p['lat'] < max($p1['lat'], $p2['lat'])) {//ray is crossing over by the algorithm (common part of)
            if ($p['lng'] <= max($p1['lng'], $p2['lng'])) {//x is before of ray
                if ($p1['lat'] == $p2['lat'] && $p['lng'] >= min($p1['lng'], $p2['lng'])) {//overlies on a horizontal ray
                    return $boundOrVertex;
                }

                if ($p1['lng'] == $p2['lng']) {//ray is vertical
                    if ($p1['lng'] == $p['lng']) {//overlies on a vertical ray
                        return $boundOrVertex;
                    } else {//before ray
                        ++$intersectCount;
                    }
                } else {//cross point on the left side
                    $xinters = ($p['lat'] - $p1['lat']) * ($p2['lng'] - $p1['lng']) / ($p2['lat'] - $p1['lat']) + $p1['lng'];//cross point of lng
                    if (abs($p['lng'] - $xinters) < $precision) {//overlies on a ray
                        return $boundOrVertex;
                    }

                    if ($p['lng'] < $xinters) {//before ray
                        ++$intersectCount;
                    }
                }
            }
        } else {//special case when ray is crossing through the vertex
            if ($p['lat'] == $p2['lat'] && $p['lng'] <= $p2['lng']) {//p crossing over p2
                $p3 = $pts[($i + 1) % $N]; //next vertex
                if ($p['lat'] >= min($p1['lat'], $p3['lat']) && $p['lat'] <= max($p1['lat'], $p3['lat'])) { //p.lat lies between p1.lat & p3.lat
                    ++$intersectCount;
                } else {
                    $intersectCount += 2;
                }
            }
        }
        $p1 = $p2;//next ray left point
    }

    if ($intersectCount % 2 == 0) {//偶数在多边形外
        return false;
    } else { //奇数在多边形内
        return true;
    }
}

function array_get_column($parameter = array(), $key = '')
{
    if (!$parameter) {
        return array();
    }
    $new_arr = array();
    foreach ($parameter as $value) {
        if ($value[$key]) {
            $new_arr[] = $value[$key];
        }
    }
    return $new_arr;
}

function get_changearr_key($data = array(), $key = '')
{
    if (empty($data) || empty($key)) {
        return array();
    }
    $temp_key = array_get_column($data, $key);  //键值
    $new_data = array_combine($temp_key, $data);
    return $new_data;
}

/**检测商品是否满足优惠券权限
 * @param array $parameter <p>
 * int couponId 优惠券id
 * array goods_id_arr 商品id
 * </p>
 * @param string $msg
 * @return bool
 */
function check_coupons_auth($parameter = array(), &$msg = '')
{

    $temp_msg = [];//错误信息记录
    if (!$parameter['couponId'] || !$parameter['goods_id_arr']) {
        return false;
    }
    if (!is_array($parameter['goods_id_arr'])) {
        $parameter['goods_id_arr'] = explode(',', $parameter['goods_id_arr']);
    }
    $gm = M('goods');
    $where = array(
        'goodsId' => array(
            'in',
            $parameter['goods_id_arr']
        ),
    );
    //获取商品信息
    $goodsList = $gm->where($where)->field('goodsId,shopId,goodsName,goodsCatId1,goodsCatId2,goodsCatId3,shopCatId1,shopCatId2')->select();
    if (!$goodsList) {
        return false;
    }

    //获取优惠券信息
    $cm = M('coupons');
    $acm = M('coupons_auth');
    $couponsInfo = $cm->where("couponId={$parameter['couponId']}")->find();
    if (!$couponsInfo) {
        return false;
    }
    $authList = $acm->where('couponId=' . $parameter['couponId'])->select();
    if (!$authList) {
        return true;
    }

    $newAuthList = array();
    foreach ($authList as $key => $value) {
        if ($value['type'] && $value['toid']) {
            $newAuthList[$value['type']][] = $value['toid'];
        }
    }


    #检测
    foreach ($goodsList as $goods) {


        $isauth = 0;
        //获取对比次数
        if (count($newAuthList['1']) > 0) {
            $isauth++;
        }
        if (count($newAuthList['2']) > 0) {
            $isauth++;
        }
        if (count($newAuthList['3']) > 0) {
            $isauth++;
        }
        $temp_static = 0;//权限结果检测


        //先行判断是否达到满减条件 暂时不加------------


        //店铺优惠券，不匹配则不能用
        if ($couponsInfo['type'] == 2 && $couponsInfo['shopId'] != $goods['shopId']) {
            return false;
        }
        //权限检测
        if (!$newAuthList) {//没有权限
            return true;
        }


        //检测商品权限

        if (count($newAuthList['1']) > 0) {


            if (in_array($goods['goodsId'], $newAuthList['1'])) {
                $temp_static++;
                //  return true;
            } else {
                $msg = "商品【{$goods['goodsName']}】超出权限范围";
                array_push($temp_msg, $msg);

            }


        }

        //检测分类权限

        if (count($newAuthList['2']) > 0) {
            if (in_array($goods['goodsCatId1'], $newAuthList['2']) || in_array($goods['goodsCatId2'], $newAuthList['2']) || in_array($goods['goodsCatId3'], $newAuthList['2'])) {
                $temp_static++;


                //  return true;
            } else {
                $msg = "商品【{$goods['goodsName']}】分类超出权限范围";
                array_push($temp_msg, $msg);

            }
        }

        //检测店铺权限

        if (count($newAuthList['3']) > 0) {


            if (in_array($goods['shopId'], $newAuthList['3'])) {
                $temp_static++;
                //  return true;
            } else {
                $msg = "商品【{$goods['goodsName']}】店铺超出权限范围";
                array_push($temp_msg, $msg);

            }

        }


        //      $myfile = fopen("hhhhhhhhhhhhhhhhhhhhhhhhhh.txt", "a+") or die("Unable to open file!");
        // $txt = json_encode($goodsList);
        // fwrite($myfile, "$txt# $temp_static \n");
        // fclose($myfile);
        //
        //
        //      $myfile = fopen("666666666666666666666666666.txt", "a+") or die("Unable to open file!");
        // $txt = $isauth;
        // fwrite($myfile, "$txt# $temp_static \n");
        // fclose($myfile);

        if ($temp_static != 0) {
            return true;
        }

    }
}


/**获取优惠券列表权限信息
 * @param array $parameter
 * @param string $msg
 * @return bool
 */
function get_couponsList_auth(&$couponList = array(), &$msg = '')
{
    if (!$couponList) {
        return false;
    }
    foreach ($couponList as $key => &$value) {
        $auth_arr = get_coupons_auth($value['couponId']);
        $value['auth_goods'] = $auth_arr['auth_goods'];
        $value['auth_cats'] = $auth_arr['auth_cats'];
        $value['auth_shops'] = $auth_arr['auth_shops'];
    }
    return $couponList;
}

/**获取优惠券权限信息
 * @param array $parameter
 * @param string $msg
 * @return bool
 */
function get_coupons_auth($couponId = '', &$msg = '')
{
    $retrueArr = array(
        'auth_goods' => array(),
        'auth_cats' => array(),
        'auth_shops' => array(),
    );
    if (!$couponId) {
        return $retrueArr;
    }

    //缓存获取
    $cache_arr = S("coupons.auth.couponId_{$couponId}");
    if ($cache_arr && is_array($cache_arr)) {
        return $cache_arr;
    }

    //数据库获取

//获取优惠券权限信息
//    $cm = M('coupons');
    $acm = M('coupons_auth');
    $goodsm = M('goods');
    $goodscatsm = M('goods_cats');
    $shopsm = M('shops');
    $authList = $acm->where('couponId=' . $couponId)->select();
    if (!$authList) {
        return $retrueArr;
    }

    $newAuthList = array();
    foreach ($authList as $key => $value) {
        if ($value['type'] && $value['toid']) {
            $newAuthList[$value['type']][] = $value['toid'];
        }
    }


    foreach ($newAuthList as $key => $id_arr) {
        switch ($key) {
            case "1"://商品
                $where = array(
                    'goodsId' => array(
                        'in',
                        $id_arr
                    ),
                );
                $goodsList = $goodsm->where($where)->field('goodsId,shopId,goodsName')->select();
                $retrueArr['auth_goods'] = array_merge($retrueArr['auth_goods'], $goodsList);
                break;
            case "2"://分类
                $where = array(
                    'catId' => array(
                        'in',
                        $id_arr
                    ),
                );

                $catsList = $goodscatsm->where($where)->field('catId,parentId,catName')->select();
                $retrueArr['auth_cats'] = array_merge($retrueArr['auth_cats'], $catsList);
                break;
            case "3"://店铺
                $where = array(
                    'shopId' => array(
                        'in',
                        $id_arr
                    ),
                );
                $shopsList = $shopsm->where($where)->field('shopId,shopName,shopSn')->select();
                $retrueArr['auth_shops'] = array_merge($retrueArr['auth_shops'], $shopsList);
                break;
            default :
                return false;
                break;
        }
    }

    //存缓存
    S("coupons.auth.couponId_{$couponId}", $retrueArr, 3600 * 12);


    return $retrueArr;


}

//获取会员动态码
function retuenUsersDynamiccode($parameter = array())
{
    if (!$parameter['pastTime']) {
        return false;
    }

    $code = rand(1000000000, 9999999999);
    $m = M('users_dynamiccode');
    $time = time() - (float)$parameter['pastTime'];
    $res = true;
    $num = 0;
    while ($res && $num < 5) {
        $where['state'] = 2;
        $where['code'] = $code;
        $where['addtime'] = array('GT', date('Y-m-d H:i:s', $time));
        $res = $m->where($where)->find();
        if ($res) {
            $code = rand(1000000000, 9999999999);
        }
        $num++;
        if ($num >= 5) {
            return false;
        }
    }

    return $code;
}

//取消订单优惠券 返还用户已使用的优惠券
function cancelUserCoupon($orderId = '', &$msg = '')
{
    //原有的函数,很多地方在使用,就不迁移了
    $order_id = (int)$orderId;
    if (empty($order_id)) {
        return false;
    }
    $orders_module = new \App\Modules\Orders\OrdersModule();
    $field = 'orderId,orderNo,couponId,userId,orderStatus,delivery_coupon_id';
    $order_detail = $orders_module->getOrderInfoById($order_id, $field, 2);
    if (empty($order_detail) || empty($order_detail['userId'])) {
        return false;
    }
    if (empty($order_detail['couponId']) && empty($order_detail['delivery_coupon_id'])) {//未使用优惠券,运费券
        return true;
    }
    $coupon_id_arr = array();
    if (!empty($order_detail['couponId'])) {
        $coupon_id_arr[] = $order_detail['couponId'];
    }
    if (!empty($order_detail['delivery_coupon_id'])) {
        $coupon_id_arr[] = $order_detail['delivery_coupon_id'];
    }
    $coupons_users_model = new \App\Models\CouponsUsersModel();
    $where = array(
        'userId' => $order_detail['userId'],
        'couponId' => array('IN', $coupon_id_arr),
        'orderNo' => $order_detail['orderNo'],
    );
    $save_data = array(
        'couponStatus' => 1,
        'orderNo' => '',
    );
    $save_res = $coupons_users_model->where($where)->save($save_data);
    if ($save_res === false) {
        return false;
    }
    $content = '优惠券已返还，请注意查收！';
    $log_params = array(
        'orderId' => $orderId,
        'logContent' => $content,
        'logUserId' => 0,
        'logUserName' => '系统',
        'orderStatus' => $order_detail['orderStatus'],
        'payStatus' => 1,
        'logType' => 2,
        'logTime' => date('Y-m-d H:i:s'),
    );
    $log_orders_model = new \App\Models\LogOrdersModel();
    $log_orders_model->add($log_params);
    return true;

}


//返还用户已使用的积分
function returnIntegral($orderId, $userId)
{
//    $myfile = fopen("cancelOrderOK.txt", "a+") or die("Unable to open file!");
//    fwrite($myfile, "已付款订单取消(" . date('Y-m-d H:i:s') . ")，（退换已使用的积分）：进入退换已使用的积分函数 \r\n");
//    fclose($myfile);

    if (!(int)$orderId) {

//        $myfile = fopen("cancelOrderOK.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "已付款订单取消(" . date('Y-m-d H:i:s') . ")，（退换已使用的积分）：orderId 不存在 \r\n");
//        fclose($myfile);

        return false;
    }
    $orders = M("orders");

    //查询本单已使用的积分
    $orderInfo = $orders->where('orderId = ' . $orderId)->find();

//    $myfile = fopen("cancelOrderOK.txt", "a+") or die("Unable to open file!");
//    fwrite($myfile, "已付款订单取消(" . date('Y-m-d H:i:s') . ")，（退换已使用的积分），订单信息：" . json_encode($orderInfo) . " \r\n");
//    fclose($myfile);

    //增加用户积分
    if (M('users')->where("userId = {$userId}")->setInc('userScore', (int)$orderInfo['useScore'])) {

//        $myfile = fopen("cancelOrderOK.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "已付款订单取消(" . date('Y-m-d H:i:s') . ")，（退换已使用的积分）：增加用户积分 \r\n");
//        fclose($myfile);

        //记录日志
        //订单日志
//        $log_orders = M("log_orders");
//        $log_orders_data["orderId"] = $orderId;
//        $log_orders_data["logContent"] = "积分已返还，请注意查收！";
//        $log_orders_data["logUserId"] = $userId;
//        $log_orders_data["logType"] = "0";
//        $log_orders_data["logTime"] = date("Y-m-d H:i:s");
//
//        $log_orders->add($log_orders_data);
        $content = '积分已返还，请注意查收！';
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => 0,
            'logUserName' => '系统',
            'orderStatus' => $orderInfo['orderStatus'],
            'payStatus' => 1,
            'logType' => 2,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        M('log_orders')->add($logParams);
    }

//    $myfile = fopen("cancelOrderOK.txt", "a+") or die("Unable to open file!");
//    fwrite($myfile, "已付款订单取消(" . date('Y-m-d H:i:s') . ")，（退换已使用的积分）：离开退换已使用的积分函数 \r\n");
//    fclose($myfile);


}

//返还商品库存
function returnGoodsNum($orderId)
{
    if (!(int)$orderId) {
        return false;
    }
    $mod_order_goods = M('order_goods');
    $mod_goods = M('goods');

    $resdata = $mod_order_goods->where("orderId = " . $orderId)->select();

    for ($i = 0; $i < count($resdata); $i++) {
        $resdata_i_goodsNums = gChangeKg($resdata[$i]['goodsId'], $resdata[$i]['goodsNums'], 1);
        $mod_goods->where('goodsId = ' . $resdata[$i]['goodsId'])->setInc('goodsStock', $resdata_i_goodsNums);

        //更新进销存系统商品的库存
        //updateJXCGoodsStock($resdata[$i]['goodsId'], $resdata[$i]['goodsNums'], 0);
    }

}


//#######   二开   ###########

function arrChangeSqlStr($arr = array())
{
    if (!$arr) {
        return false;
    }

    $str = '';
    $num = 0;
    foreach ($arr as $key => $value) {
        $sql = getSymbolSql($key, $value);
        if (!$sql) {
            continue;
        }
        if ($num) {
            $str .= " and {$sql} ";
        } else {
            $str .= " {$sql} ";
//                $str.=" {$key}".$symbol."'$value' ";
        }
        $num++;
    }

    return $str;
}

//获取符号
function getSymbolSql($key = '', $value = '')
{
    if (!$key) {
        return false;
    }
    $arr = explode('|', $key);
    if (!isset($arr[1])) {
        return "{$key} = '{$value}'";
    }
    switch ($arr[1]) {
        case "neq":
            return "{$arr['0']} != '{$value}'";
            break;
        case "in":
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            return "{$arr['0']} in('{$value}')";
            break;
        default :
            return false;
            break;
    }


}

//获取符号
function getWxAccessToken()
{
    $appid = $GLOBALS["CONFIG"]["xiaoAppid"];
    $secret = $GLOBALS["CONFIG"]["xiaoSecret"];
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
    $res = curlRequest($url, false, 1, 1);
    $resArr = json_decode($res, 1);
    if (!isset($resArr['access_token']) || !$resArr['access_token']) {
        return false;
    }
    return $resArr['access_token'];
}


/*
开启：php_openssl
开启：allow_url_fopen = Off 为 allow_url_fopen = ON
否则file_get_contents和curl不正常
*/
function file_get_contents_post($url, $post)
{
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => $post,
        ),
    );

    @$result = file_get_contents($url, false, stream_context_create($options));

    return $result;
}

/*
 * 对象转数组
 * */
function objectToArray($e)
{
    $e = (array)$e;
    foreach ($e as $k => $v) {
        if (gettype($v) == 'resource') return;
        if (gettype($v) == 'object' || gettype($v) == 'array')
            $e[$k] = (array)objectToArray($v);
    }
    return $e;
}

/**
 * @param $data
 * @return array
 * 过滤回收站的商品
 */
function filterRecycleGoods($data)
{
    if (empty($data)) {
        return $data;
    }
    $rest = [];
    foreach ($data as $v) {
        $getRecycleInfo = M('recycle_bin')->where(['status' => 0, 'tableName' => 'wst_goods', 'shopId' => $v['shopId'], 'dataId' => $v['goodsId']])->find();
        if (empty($getRecycleInfo)) {
            $rest[] = $v;
        }
    }
    return $rest;
}

/*
 * 处理商品结果集 PS:改变店铺价格为等级价格,如果没有等价则价格不变
 * @param $data 结果集 * PS:(买立得用的是商家后台等级,家菜园用的是总后台等级,需要特别注意)
 * */
//function rankGoodsPrice($data)
//{
//    if (empty($data)) {
//        return $data;
//    }
////    $memberToken = I("memberToken");
////    $userId = 0;
////    if (empty($memberToken)) {
////        $userId = 0;
////    }
////    $sessionData = userTokenFind($memberToken, 86400 * 30);//查询token
////    if (!empty($sessionData)) {
////        $userId = $sessionData['userId'];
////    }
//    $users_service_module = new UsersServiceModule();
//    $users_result = $users_service_module->getUsersInfoByMemberToken();
//    $userId = 0;
//    if ($users_result['code'] == ExceptionCodeEnum::SUCCESS) {
//        $userId = $users_result['data']['userId'];
//    }
//    //$userId = 46;//需要删除
//    $m = M('goods_attributes');
//    $goodsRank = M('rank_goods');
//    if ($userId > 0) {//针对于总后台等级
//        $userRank = M('rank_user')->where("userId='" . $userId . "' AND state=1")->find();
//        $rankInfo = M('rank')->where("rankId='" . $userRank['rankId'] . "'")->find(); //获取用户的等级
//        if (count($data) == count($data, 1)) {
//            $shopPrice = number_format($data['shopPrice'], 2, ".", "");
//            $rankGoodsRow = $goodsRank->where("goodsId='" . $data['goodsId'] . "' AND rankId='" . $userRank['rankId'] . "' AND attributesID IS NULL")->find();
//            if ($rankGoodsRow) {
//                if ($rankInfo['shopId'] == $data['shopId']) {
//                    $data['shopPrice'] = number_format($rankGoodsRow['price'], 2, ".", "");
//                }
//            }
//            //判断是否有属性
//            $goodsAttribute = $m->where("goodsId='" . $data['goodsId'] . "'")->select();
//            if (count($goodsAttribute) > 0 && $rankInfo['rankId']) {
//                //有属性要判断是否有对应的等级价格
//                $data['hasAttr'] = 1;
//                $list = $m->where("goodsId='" . $data['goodsId'] . "'")->field('attrId')->select();
//                if ($list) {
//                    $parentId = [];
//                    foreach ($list as $val) {
//                        $parentId[] = $val['attrId'];
//                    }
//                    sort($parentId);
//                    $parentIdStr = 0;
//                    $parentId = array_unique($parentId);
//                    if (count($parentId) > 0) {
//                        $parentIdStr = implode(',', $parentId);
//                    }
//                    $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
//                    foreach ($parentList as $key => &$val) {
//                        $children = $m->where("attrId='" . $val['attrId'] . "' AND goodsId='" . $data['goodsId'] . "'")->select();
//                        foreach ($children as $ck => $cv) {
//                            $attrGoodsRanks = $goodsRank->where("goodsId='" . $data['goodsId'] . "' AND attributesID='" . $cv['id'] . "'")->select();
//                            foreach ($attrGoodsRanks as $ak => $av) {
//                                if ($rankInfo['shopId'] == $data['shopId']) {
//                                    if ($av['rankId'] == $userRank['rankId']) {
//                                        //属性有对应的等级价格就将其赋值给属性的价格 PS:多属性为价格追加
//                                        //$children[$ck]['attrPrice'] = $shopPrice + $av['price'];
//                                        $children[$ck]['attrPrice'] = $av['price'];
//                                    }
//                                }
//                            }
//                            //普通价格属性直接覆盖商品原价(属性价格分为普通价格和叠加价格)
//                            /*if($val['isPriceAttr'] == 1){
//                                $shopPrice = $cv['attrPrice'];
//                            }*/
//                        }
//                        $val['children'] = $children;
//                    }
//                    unset($val);
//                    $data['goodsAttr'] = $parentList;
//                    if (empty($data['spec'])) {
//                        $data['spec'] = [];
//                    } else {
//                        $data['spec'] = json_decode($data['spec'], true);
//                    }
//                    //$data['shopPrice'] = $shopPrice;
//                }
//            } elseif (count($goodsAttribute) > 0 && !$rankInfo['id']) {
//                $data['hasAttr'] = 1;
//                $list = $m->where("goodsId='" . $data['goodsId'] . "'")->field('attrId')->select();
//                if ($list) {
//                    $parentId = [];
//                    foreach ($list as $val) {
//                        $parentId[] = $val['attrId'];
//                    }
//                    sort($parentId);
//                    $parentIdStr = 0;
//                    $parentId = array_unique($parentId);
//                    if (count($parentId) > 0) {
//                        $parentIdStr = implode(',', $parentId);
//                    }
//                    $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
//                    foreach ($parentList as $key => &$val) {
//                        $children = $m->where("attrId='" . $val['attrId'] . "' AND goodsId='" . $data['goodsId'] . "'")->select();
//                        /*foreach ($children as $ck=>$cv){
//                            //普通价格属性直接覆盖商品原价(属性价格分为普通价格和叠加价格)
//                            if($val['isPriceAttr'] == 1){
//                                $shopPrice = $cv['price'];
//                            }
//                        }*/
//                        $val['children'] = $children;
//                    }
//                    unset($val);
//                    $data['goodsAttr'] = $parentList;
//                    //$data['shopPrice'] = $shopPrice;
//                }
//            } else {
//                $data['hasAttr'] = 0;
//                $data['goodsAttr'] = false;
//            }
//
//            //为前端增加shopName,shopId字段 start
//            $shopInfo = M('shops')->where("shopId='" . $data['shopId'] . "'")->field('shopId,shopName')->find();
//            $data['shopName'] = $shopInfo['shopName'];
//            //为前端增加shopName,shopId字段 end
//
//            //处理秒杀过期的问
//            $goodsInfo = M('goods')->where(['goodsId' => $data['goodsId']])->find();
//            //PS: 商城秒杀和店铺秒杀同时存在,以店铺的秒杀权重高
//            $data['shopSecKillOutTime'] = 0;
//            if ($goodsInfo['isAdminShopSecKill'] == 1) {
//                if ($goodsInfo['AdminShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                    $data['shopSecKillOutTime'] = 1; //已过期
//                } else {
//                    $data['shopSecKillOutTime'] = 0; //未过期
//                }
//            }
//            if ($goodsInfo['isShopSecKill'] == 1) {
//                if ($goodsInfo['ShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                    $data['shopSecKillOutTime'] = 1; //已过期
//                } else {
//                    $data['shopSecKillOutTime'] = 0; //未过期
//                }
//            }
//
//            //处理限时,为前端增加字段
//            $data['flashSale'] = [];
//            $data['flashSaleStatus'] = true;//限时商品能否购买(true:是|false:否)
//            $checkGoodsFlashSale = checkGoodsFlashSale($goodsInfo['goodsId']);
//            if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                //$data['flashSaleStatus'] = false;//注释原因:不在时间段允许以商品原价购买
//            }
//            if ($goodsInfo['isFlashSale'] == 1) {
//                $where['isDelete'] = 0;
//                $where['goodsId'] = $goodsInfo['goodsId'];
//                $flashSaleGoods = M('flash_sale_goods')->where($where)->select();
//                if ($flashSaleGoods) {
//                    $flashId = [];
//                    foreach ($flashSaleGoods as $val) {
//                        $flashId[] = $val['flashSaleId'];
//                    }
//                    $fwhere['isDelete'] = 0;
//                    $fwhere['state'] = 1;
//                    $fwhere['id'] = ['IN', $flashId];
//                    $flashList = M('flash_sale')->where($fwhere)->select();
//                    $data['flashSale'] = $flashList;
//                }
//            }
//            if (empty($data['spec'])) {
//                $data['spec'] = [];
//            } else {
//                $data['spec'] = json_decode($data['spec'], true);
//            }
//            $data['virtualSales'] = (int)$goodsInfo['virtualSales'];//虚拟销量
//        } else {
//            $shopId = [];
//            foreach ($data as $key => $val) {
//                $shopPrice = number_format($data['shopPrice'], 2, ".", "");
//                $goodsAttribute = $m->where("goodsId='" . $val['goodsId'] . "'")->select();
//                $rankGoodsRow = M('rank_goods')->where("goodsId='" . $val['goodsId'] . "' AND rankId='" . $userRank['rankId'] . "' AND attributesID IS NULL")->find();
//                if ($rankInfo['shopId'] == $data[$key]['shopId']) {
//                    if ($rankGoodsRow) {
//                        $data[$key]['shopPrice'] = number_format($rankGoodsRow['price'], 2, ".", ""); //没属性,等级价格直接覆盖原价
//                    }
//                }
//                if (count($goodsAttribute) > 0) {
//                    //商品是否有属性 PS:0=>无,1=>有
//                    $data[$key]['hasAttr'] = 1;
//                    $list = $m->where("goodsId='" . $val['goodsId'] . "'")->field('attrId')->select();
//                    if ($list) {
//                        $parentId = [];
//                        foreach ($list as $val) {
//                            $parentId[] = $val['attrId'];
//                        }
//                        sort($parentId);
//                        $parentIdStr = 0;
//                        $parentId = array_unique($parentId);
//                        if (count($parentId) > 0) {
//                            $parentIdStr = implode(',', $parentId);
//                        }
//                        $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
//                        foreach ($parentList as $pkey => &$pval) {
//                            $children = $m->where("attrId='" . $pval['attrId'] . "' AND goodsId='" . $data[$key]['goodsId'] . "'")->select();
//                            foreach ($children as $ck => $cv) {
//                                $attrGoodsRanks = $goodsRank->where("goodsId='" . $data[$key]['goodsId'] . "' AND attributesID='" . $cv['id'] . "'")->select();
//                                foreach ($attrGoodsRanks as $gv) {
//                                    if ($rankInfo['shopId'] == $data[$key]['shopId']) {
//                                        if ($gv['rankId'] == $userRank['rankId']) {
//                                            $children[$ck]['attrPrice'] = $gv['price'];
//                                        }
//                                    }
//                                }
//                                //普通价格属性直接覆盖商品原价(属性价格分为普通价格和叠加价格)
//                                /*if($pval['isPriceAttr'] == 1){
//                                    $shopPrice = $cv['price'];
//                                }*/
//                            }
//                            $pval['children'] = $children;
//                        }
//                        unset($pval);
//                        $data[$key]['goodsAttr'] = $parentList;
//                        //$data[$key]['shopPrce'] = $shopPrice;
//                    }
//                } else {
//                    //没属性要判断是否有对应的等级价格
//                    $data[$key]['hasAttr'] = 0;
//                    $data[$key]['goodsAttr'] = false;
//                }
//                $shopId[] = $data[$key]['shopId'];//为前端增加shopName,shopId字段 start
//            }
//            sort($shopId);
//            $shopId = array_unique($shopId);
//            if (count($shopId) > 0) {
//                $shopIdStr = implode(',', $shopId);
//                $shopList = M('shops')->where("shopId IN($shopIdStr)")->field('shopId,shopName')->select();
//                foreach ($data as $key => $val) {
//                    //处理秒杀过期的问
//                    $goodsInfo = M('goods')->where(['goodsId' => $val['goodsId']])->find();
//                    //PS: 商城秒杀和店铺秒杀同时存在,以店铺的秒杀权重高
//                    $data[$key]['shopSecKillOutTime'] = 0;
//                    if ($goodsInfo['isAdminShopSecKill'] == 1) {
//                        if ($goodsInfo['AdminShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                            $data[$key]['shopSecKillOutTime'] = 1; //已过期
//                        } else {
//                            $data[$key]['shopSecKillOutTime'] = 0; //未过期
//                        }
//                    }
//                    if ($goodsInfo['isShopSecKill'] == 1) {
//                        if ($goodsInfo['ShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                            $data[$key]['shopSecKillOutTime'] = 1; //已过期
//                        } else {
//                            $data[$key]['shopSecKillOutTime'] = 0; //未过期
//                        }
//                    }
//
//                    //处理限时,为前端增加字段
//                    $data[$key]['flashSale'] = [];
//                    $data[$key]['flashSaleStatus'] = true;//限时商品能否购买(true:是|false:否)
//                    $checkGoodsFlashSale = checkGoodsFlashSale($goodsInfo['goodsId']);
//                    if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                        //$data[$key]['flashSaleStatus'] = false;//注释原因:不在时间段允许以商品原价购买
//                    }
//                    if ($goodsInfo['isFlashSale'] == 1) {
//                        $where['isDelete'] = 0;
//                        $where['goodsId'] = $goodsInfo['goodsId'];
//                        $flashSaleGoods = M('flash_sale_goods')->where($where)->select();
//                        if ($flashSaleGoods) {
//                            $flashId = [];
//                            foreach ($flashSaleGoods as $val) {
//                                $flashId[] = $val['flashSaleId'];
//                            }
//                            $fwhere['isDelete'] = 0;
//                            $fwhere['state'] = 1;
//                            $fwhere['id'] = ['IN', $flashId];
//                            $flashList = M('flash_sale')->where($fwhere)->select();
//                            $data[$key]['flashSale'] = $flashList;
//                        }
//                    }
//
//                    foreach ($shopList as $sv) {
//                        if ($sv['shopId'] == $val['shopId']) {
//                            $data[$key]['shopName'] = $sv['shopName'];
//                        }
//                    }
//                    $data[$key]['virtualSales'] = (int)$goodsInfo['virtualSales'];
//                }
//            }
//            //为前端增加shopName,shopId字段 end
//        }
//    } else {
//        //未登录不处理等级价格
//        if (count($data) == count($data, 1)) {
//            $shopPrice = number_format($data['shopPrice'], 2, ".", "");
//            //判断是否有属性
//            $goodsAttribute = $m->where("goodsId='" . $data['goodsId'] . "'")->select();
//            if (count($goodsAttribute) > 0) {
//                $data['hasAttr'] = 1;
//                $list = $m->where("goodsId='" . $data['goodsId'] . "'")->field('attrId')->select();
//                if ($list) {
//                    $parentId = [];
//                    foreach ($list as $val) {
//                        $parentId[] = $val['attrId'];
//                    }
//                    sort($parentId);
//                    $parentIdStr = 0;
//                    $parentId = array_unique($parentId);
//                    if (count($parentId) > 0) {
//                        $parentIdStr = implode(',', $parentId);
//                    }
//                    $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
//                    foreach ($parentList as $key => &$val) {
//                        $children = $m->where("attrId='" . $val['attrId'] . "' AND goodsId='" . $data['goodsId'] . "'")->select();
//                        /*foreach ($children as $ck=>$cv){
//                            //普通价格属性直接覆盖商品原价(属性价格分为普通价格和叠加价格)
//                            if($val['isPriceAttr'] == 1){
//                                $shopPrice = $cv['price'];
//                            }
//                        }*/
//                        $val['children'] = $children;
//                    }
//                    unset($val);
//                    $data['goodsAttr'] = $parentList;
//                    //$data['shopPrice'] = $shopPrice;
//                }
//            } else {
//                $data['hasAttr'] = 0;
//                $data['goodsAttr'] = false;
//            }
//
//            //为前端增加shopName,shopId字段 start
//            $shopInfo = M('shops')->where("shopId='" . $data['shopId'] . "'")->field('shopId,shopName')->find();
//            $data['shopName'] = $shopInfo['shopName'];
//            //为前端增加shopName,shopId字段 end
//
//            //处理秒杀过期的问
//            $goodsInfo = M('goods')->where(['goodsId' => $data['goodsId']])->find();
//            //PS: 商城秒杀和店铺秒杀同时存在,以店铺的秒杀权重高
//            $data['shopSecKillOutTime'] = 0;
//            if ($goodsInfo['isAdminShopSecKill'] == 1) {
//                if ($goodsInfo['AdminShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                    $data['shopSecKillOutTime'] = 1; //已过期
//                } else {
//                    $data['shopSecKillOutTime'] = 0; //未过期
//                }
//            }
//            if ($goodsInfo['isShopSecKill'] == 1) {
//                if ($goodsInfo['ShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                    $data['shopSecKillOutTime'] = 1; //已过期
//                } else {
//                    $data['shopSecKillOutTime'] = 0; //未过期
//                }
//            }
//            //处理限时,为前端增加字段
//            $data['flashSale'] = [];
//            $data['flashSaleStatus'] = true;//限时商品能否购买(true:是|false:否)
//            $checkGoodsFlashSale = checkGoodsFlashSale($goodsInfo['goodsId']);
//            if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                //$data['flashSaleStatus'] = false;//注释原因:不在时间段允许以商品原价购买
//            }
//            if ($goodsInfo['isFlashSale'] == 1) {
//                $where['isDelete'] = 0;
//                $where['goodsId'] = $goodsInfo['goodsId'];
//                $flashSaleGoods = M('flash_sale_goods')->where($where)->select();
//                if ($flashSaleGoods) {
//                    $flashId = [];
//                    foreach ($flashSaleGoods as $val) {
//                        $flashId[] = $val['flashSaleId'];
//                    }
//                    $fwhere['isDelete'] = 0;
//                    $fwhere['state'] = 1;
//                    $fwhere['id'] = ['IN', $flashId];
//                    $flashList = M('flash_sale')->where($fwhere)->select();
//                    $data['flashSale'] = $flashList;
//                }
//            }
//            if (empty($data['spec'])) {
//                $data['spec'] = [];
//            } else {
//                $data['spec'] = json_decode($data['spec'], true);
//            }
//            $data['virtualSales'] = (int)$goodsInfo['virtualSales'];
//        } else {
//            $shopId = [];
//            foreach ($data as $key => $val) {
//                $shopPrice = number_format($data[$key]['shopPrice'], 2, ".", "");
//                $goodsAttribute = $m->where("goodsId='" . $val['goodsId'] . "'")->select();
//                if (count($goodsAttribute) > 0) {
//                    //商品是否有属性 PS:0=>无,1=>有
//                    $data[$key]['hasAttr'] = 1;
//                    $list = $m->where("goodsId='" . $val['goodsId'] . "'")->field('attrId')->select();
//                    if ($list) {
//                        $parentId = [];
//                        foreach ($list as $val) {
//                            $parentId[] = $val['attrId'];
//                        }
//                        sort($parentId);
//                        $parentIdStr = 0;
//                        $parentId = array_unique($parentId);
//                        if (count($parentId) > 0) {
//                            $parentIdStr = implode(',', $parentId);
//                        }
//                        $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
//                        foreach ($parentList as $pkey => &$pval) {
//                            $children = $m->where("attrId='" . $pval['attrId'] . "' AND goodsId='" . $data[$key]['goodsId'] . "'")->select();
//                            /*foreach ($children as $ck=>$cv){
//                                //普通价格属性直接覆盖商品原价(属性价格分为普通价格和叠加价格)
//                                if($pval['isPriceAttr'] == 1){
//                                    $shopPrice = $cv['price'];
//                                }
//                            }*/
//
//                            $pval['children'] = $children;
//                        }
//                        unset($pval);
//                        $data[$key]['goodsAttr'] = $parentList;
//                        //$data[$key]['shopPrice'] = $shopPrice;
//                    }
//                } else {
//                    $data[$key]['hasAttr'] = 0;
//                    $data[$key]['goodsAttr'] = false;
//                }
//
//                $shopId[] = $data[$key]['shopId'];//为前端增加shopName,shopId字段 start
//            }
//            sort($shopId);
//            $shopId = array_unique($shopId);
//            if (count($shopId) > 0) {
//                $shopIdStr = implode(',', $shopId);
//                $shopList = M('shops')->where("shopId IN($shopIdStr)")->field('shopId,shopName')->select();
//                foreach ($data as $key => $val) {
//                    //处理秒杀过期的问
//                    $goodsInfo = M('goods')->where(['goodsId' => $val['goodsId']])->find();
//                    //PS: 商城秒杀和店铺秒杀同时存在,以店铺的秒杀权重高
//                    $data[$key]['shopSecKillOutTime'] = 0;
//                    if ($goodsInfo['isAdminShopSecKill'] == 1) {
//                        if ($goodsInfo['AdminShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                            $data[$key]['shopSecKillOutTime'] = 1; //已过期
//                        } else {
//                            $data[$key]['shopSecKillOutTime'] = 0; //未过期
//                        }
//                    }
//                    if ($goodsInfo['isShopSecKill'] == 1) {
//                        if ($goodsInfo['ShopGoodSecKillEndTime'] <= date('Y-m-d H:i:s', time())) {
//                            $data[$key]['shopSecKillOutTime'] = 1; //已过期
//                        } else {
//                            $data[$key]['shopSecKillOutTime'] = 0; //未过期
//                        }
//                    }
//                    //处理限时,为前端增加字段
//                    $data[$key]['flashSale'] = [];
//                    $data[$key]['flashSaleStatus'] = true;//限时商品能否购买(true:是|false:否)
//                    $checkGoodsFlashSale = checkGoodsFlashSale($goodsInfo['goodsId']);
//
//                    if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                        //$data[$key]['flashSaleStatus'] = false;//注释原因:不在时间段允许以商品原价购买
//                    }
//                    if ($goodsInfo['isFlashSale'] == 1) {
//                        $where['isDelete'] = 0;
//                        $where['goodsId'] = $goodsInfo['goodsId'];
//                        $flashSaleGoods = M('flash_sale_goods')->where($where)->select();
//                        if ($flashSaleGoods) {
//                            $flashId = [];
//                            foreach ($flashSaleGoods as $val) {
//                                $flashId[] = $val['flashSaleId'];
//                            }
//                            $fwhere['isDelete'] = 0;
//                            $fwhere['state'] = 1;
//                            $fwhere['id'] = ['IN', $flashId];
//                            $flashList = M('flash_sale')->where($fwhere)->select();
//                            $data[$key]['flashSale'] = $flashList;
//                        }
//                    }
//                    foreach ($shopList as $sv) {
//                        if ($sv['shopId'] == $val['shopId']) {
//                            $data[$key]['shopName'] = $sv['shopName'];
//                        }
//                    }
//                    $data[$key]['virtualSales'] = (int)$goodsInfo['virtualSales'];
//                }
//            }
//            //为前端增加shopName,shopId字段 end
//        }
//    }
//    //获取商品的sku
//    $data = getGoodsSku($data);
//    return $data;
//}

//上面注释的为原来的版本
function rankGoodsPrice($data)
{
    //历史遗留的函数,牵扯的地方太多了,这里就不去除这个函数,只保留现在有效的内容,继续沿用吧
    if (empty($data)) {
        return $data;
    }
    $shopModule = new \App\Modules\Shops\ShopsModule();
    $goodsModule = new \App\Modules\Goods\GoodsModule();
    $shopField = 'shopId,shopName,shopImg,shopAtive';
    $goodsField = 'goodsId,goodsName,goodsImg,virtualSales,goodsStock,selling_stock';
    $needHandleData = $data;
//    if (count($data) == count($data, 1)) {
//
//    }
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $needHandleData = array($data);
    }
    $shopIdArr = array();
    $goodsIdArr = [];
    foreach ($needHandleData as $key => $val) {
        $shopIdArr[] = $needHandleData[$key]['shopId'];//为前端增加shopName,shopId字段 start
        $goodsIdArr[] = $val['goodsId'];
    }
    sort($shopIdArr);
    $shopIdArr = array_unique($shopIdArr);
    if (count($shopIdArr) > 0) {
        $shopList = $shopModule->getShopListByShopId($shopIdArr, $shopField);
        $goodsListMap = [];
        if (count($goodsIdArr) > 0) {
            $goodsIdArr = array_unique($goodsIdArr);
            $goodsListData = $goodsModule->getGoodsListById($goodsIdArr);
            $goodsList = $goodsListData['data'];
            foreach ($goodsList as $goodsRow) {
                $goodsListMap[$goodsRow['goodsId']] = $goodsRow;
            }
        }
        foreach ($needHandleData as $key => $val) {
            $goodsId = (int)$val['goodsId'];
//            $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, $goodsField, 2);
            $goodsDetail = $goodsListMap[$goodsId];
            //处理限时,为前端增加字段 注:这个限时估计是最初的版本,现在已经使用活动管理中的限时了,所以前端用的字段就暂保留,其它的旧逻辑删除
            $needHandleData[$key]['flashSale'] = array();
            $needHandleData[$key]['flashSaleStatus'] = true;//限时商品能否购买(true:是|false:否)
            foreach ($shopList as $shopDetail) {
                if ($shopDetail['shopId'] == $val['shopId']) {
                    $needHandleData[$key]['shopName'] = $shopDetail['shopName'];//前端需要
                    $needHandleData[$key]['shopAtive'] = $shopDetail['shopAtive'];
                }
            }
            $needHandleData[$key]['virtualSales'] = (float)$goodsDetail['virtualSales'];
            if (isset($val['spec'])) {
                if (empty($val['spec'])) {
                    $needHandleData[$key]['spec'] = array();
                } else {
                    $needHandleData[$key]['spec'] = json_decode($needHandleData[$key]['spec'], true);
                }
            }
        }
    }
    $returnData = $needHandleData;
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $returnData = $returnData[0];
    }
    $returnData = getGoodsSku($returnData);//获取商品的sku
    $goodsModule->filterGoods($returnData);
    return (array)$returnData;
}


/* * 购物车商品结果集处理
 * PS: 针对买立得 2019-6
 * */
//function cartGoodsAttr($data)
//{
//    $usersModule = new \App\Modules\Users\UsersModule();
//    $users_result = $usersModule->getUsersInfoByMemberToken();
//    $userId = 0;
//    if ($users_result['code'] == ExceptionCodeEnum::SUCCESS) {
//        $userId = $users_result['data']['userId'];
//    }
//    //$userId = '46';
//    $m = M('goods_attributes');
//    $goodsRank = M('rank_goods');
//    if ($userId > 0) {//针对商家自己添加等级标签
//        $shopId = [];
//        $userRank = M('rank_user')->where("userId='" . $userId . "' AND state=1")->find();
//        $rankInfo = M('rank')->where("rankId='" . $userRank['rankId'] . "'")->find(); //获取用户的等级
//        foreach ($data as $key => $val) {
//            $shopPrice = $val['shopPrice'];
//            $rankGoodsRow = M('rank_goods')->where("goodsId='" . $val['goodsId'] . "' AND rankId='" . $userRank['rankId'] . "' AND attributesID IS NULL")->find();
//            if ($rankInfo['shopId'] == $data[$key]['shopId']) {
//                if ($rankGoodsRow) {
//                    $data[$key]['shopPrice'] = $rankGoodsRow['price']; //没属性,等级价格直接覆盖原价
//                    $shopPrice = $rankGoodsRow['price'];
//                }
//            }
//            if (!empty($data[$key]['goodsAttrId'])) {
//                $data[$key]['hasAttr'] = 1; //有属性
//                //$goodsAttrIdArr = explode(',',$data[$key]['goodsAttrId']);
//                $goodsAttrId = $data[$key]['goodsAttrId'];
//                $children = $m->where("id IN($goodsAttrId) AND goodsId='" . $data[$key]['goodsId'] . "'")->select();
//                if ($children) {
//                    foreach ($children as $ck => $cv) {
//                        $data[$key]['attrVal'] .= $cv['attrVal'] . " ";
//                        $attrGoodsRanks = $goodsRank->where("goodsId='" . $data[$key]['goodsId'] . "' AND attributesID='" . $cv['id'] . "'")->select();
//                        $parentAttrInfo = M('attributes')->where("attrId='" . $cv['attrId'] . "'")->find();
//                        if (!$attrGoodsRanks) {
//                            if ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 1 && !$attrGoodsRanks) {
//                                $shopPrice = $cv['attrPrice'];
//                            } elseif ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 2 && !$attrGoodsRanks) {
//                                $shopPrice += $cv['attrPrice'];
//                            }
//                        } else {
//                            if ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 1 && $attrGoodsRanks) {
//                                $shopPrice = $cv['attrPrice'];
//                            } elseif ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 2 && !$attrGoodsRanks) {
//                                $shopPrice += $cv['attrPrice'];
//                            }
//                            if ($val['shopId'] == $rankInfo['shopId'] && $attrGoodsRanks) {
//                                foreach ($attrGoodsRanks as $ak => $av) {
//                                    if ($rankInfo['shopId'] == $data[$key]['shopId']) {
//                                        //普通价格属性直接覆盖商品原价(属性价格分为普通价格和叠加价格)
//                                        if ($av['rankId'] == $userRank['rankId'] && $cv['id'] == $av['attributesID'] && $parentAttrInfo['isPriceAttr'] == 1) {
//                                            $shopPrice = $av['price'];
//                                        } elseif ($av['rankId'] == $userRank['rankId'] && $cv['id'] == $av['attributesID'] && $parentAttrInfo['isPriceAttr'] == 2) {
//                                            $shopPrice += $av['price'];
//                                        }
//                                    }
//                                }
//                            }
//                        }
//                    }
//
//                }
//                $data[$key]['shopPrice'] = number_format($shopPrice, 2, ".", "");
//            } else {
//
//                $data[$key]['hasAttr'] = 0; //无属性
//                $data[$key]['attrVal'] = false; //属性值
//                if ($val['shopId'] == $rankInfo['shopId']) {
//                    $attrGoodsRanks = $goodsRank->where("goodsId='" . $data[$key]['goodsId'] . "' AND rankId='" . $userRank['rankId'] . "' AND attributesID IS NULL")->select();
//                    foreach ($attrGoodsRanks as $ak => $av) {
//                        if ($rankInfo['shopId'] == $data[$key]['shopId']) {
//                            if ($av['rankId'] == $userRank['rankId']) {
//                                $data[$key]['shopPrice'] = number_format($av['price'], 2, ".", ""); //PS:没属性时价格为覆盖
//                            }
//                        }
//                    }
//                }
//            }
//            $shopId[] = $data[$key]['shopId'];//为前端增加shopName,shopId字段 start
//            //exit;
//        }
//        sort($shopId);
//        $shopId = array_unique($shopId);
//        if (count($shopId) > 0) {
//            $shopIdStr = implode(',', $shopId);
//            $shopList = M('shops')->where("shopId IN($shopIdStr)")->field('shopId,shopName')->select();
//            foreach ($data as $key => $val) {
//                foreach ($shopList as $sv) {
//                    if ($sv['shopId'] == $val['shopId']) {
//                        $data[$key]['shopName'] = $sv['shopName'];
//                    }
//                }
//            }
//        }
//        //为前端增加shopName,shopId字段 end
//    }
//    //获取商品的sku信息
//    $data = getCartGoodsSku($data);
//    return $data;
//}


/*
 * 返还秒杀库存
 * params int $orderId
 * */
function returnKillStock($orderId)
{
    if (!empty($orderId) && $orderId > 0) {
        $goodsTab = M('goods g');
        $killTab = M('goods_secondskilllimit');
        $goodsList = $goodsTab
            ->join("LEFT JOIN wst_order_goods og ON g.goodsId=og.goodsId")
            ->field('g.*,og.goodsNums')
            ->where("g.goodsId = og.goodsId AND og.orderId='" . $orderId . "'")
            ->select();
        foreach ($goodsList as $val) {
            if ($val['isShopSecKill'] == 1) {
                $goodsTab->where('goodsId = ' . $val['goodsId'])->setInc('shopSecKillNUM', (float)$val['goodsNums']); //更改秒杀库存
                $killData['state'] = -1;
                $killTab->where("orderId = " . $orderId)->save($killData); //更改秒杀记录状态为-1
            }
        }
    }
}

/*
 * 分销商品处理 PS:只处理两级
 * @param int $orderId
 * */
function checkGoodsDistribution($orderId)
{
    $orderTab = M('orders');
    $relationTab = M('distribution_relation');
    $userDistributionTab = M('user_distribution');
    $orderInfo = $orderTab->where("orderId='" . $orderId . "'")->field(['orderId', 'orderStatus', 'userId'])->find();
    if ($orderInfo) {
        $goodsTab = M('goods');
        $orderGoodsTab = M('order_goods');
        $orderGoods = $orderGoodsTab->where("orderId='" . $orderId . "'")->field('goodsId,goodsNums')->select();
        foreach ($orderGoods as $val) {
            $goodsInfo = $goodsTab->where("goodsId='" . $val['goodsId'] . "'")->find();
            if ($goodsInfo['isDistribution'] == 1) {
                $relationList = $relationTab->where("userId='" . $orderInfo['userId'] . "'")->select();
                if (!empty($relationList)) {
                    foreach ($relationList as $iv) {
                        unset($data);
                        $data['goodsId'] = $goodsInfo['goodsId'];
                        $data['userId'] = $iv['pid'];
                        $data['UserToId'] = $iv['userId'];
                        $data['orderId'] = $orderId;
                        $data['distributionLevel'] = $iv['distributionLevel'];
                        $data['buyerId'] = $orderInfo['userId'];
                        if ($data['distributionLevel'] == 1) {
                            $data['distributionMoney'] = $goodsInfo['firstDistribution'];
                        } elseif ($data['distributionLevel'] == 2) {
                            $data['distributionMoney'] = $goodsInfo['SecondaryDistribution'];
                        }
                        $data['distributionMoney'] = (float)$data['distributionMoney'] * (float)$val['goodsNums'];
                        $data['state'] = 0;
                        $data['addtime'] = date('Y-m-d H:i:s', time());
                        $data['updateTime'] = date('Y-m-d H:i:s', time());
                        $userDistributionTab->add($data);
                        M('users')->where("userId='" . $data['userId'] . "'")->setInc('distributionMoney', $data['distributionMoney']);
                    }
                }
            }
        }

    }
}


/*
 * 获取商品属性等级价格 PS:用于支付订单时使用
 * @param int $userId
 * @param string $goodsAttrId
 * @param int $goodsId
 * @param float $shopPrice
 * @param float $goodsCnt
 * @param int $shopId
 * */
function getGoodsAttrPrice($userId, $goodsAttrId, $goodsId, $shopPrice, $goodsCnt, $shopId)
{
    $userRank = M('rank_user')->where("userId='" . $userId . "' AND state=1")->find();
    $rankInfo = M('rank')->where("rankId='" . $userRank['rankId'] . "'")->find(); //获取用户的等级
    $goodsRank = M('rank_goods');
    $shopPrice = (float)$shopPrice;
    $response = [];
    $response['goodsAttrName'] = '';
    $response['goodsPrice'] = $shopPrice;
    $response['totalMoney'] = $shopPrice * (float)$goodsCnt;
    //2019-6-14 start 属性,等级,价格处理
    if (!empty($goodsAttrId)) {
        $goodsAttrId = $goodsAttrId;
        $children = M('goods_attributes')->where("id IN($goodsAttrId) AND goodsId='" . $goodsId . "'")->select();
        if ($children) {
            $attrGoodsRanks = $goodsRank->where("goodsId='" . $goodsId . "' AND rankId='" . $userRank['rankId'] . "' AND attributesID IS NULL")->select();
            foreach ($attrGoodsRanks as $ak => $av) {
                if ($rankInfo['shopId'] == $shopId) {
                    if ($av['rankId'] == $userRank['rankId']) {
                        $shopPrice = $av['price'];
                    }
                }
            }

            foreach ($children as $ck => $cv) {
                $response['goodsAttrName'] .= $cv['attrVal'] . " ";
                $attrGoodsRanks = $goodsRank->where("goodsId='" . $goodsId . "' AND attributesID='" . $cv['id'] . "'")->select();
                $parentAttrInfo = M('attributes')->where("attrId='" . $cv['attrId'] . "'")->find();
                if (!$attrGoodsRanks) {
                    if ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 1 && !$attrGoodsRanks) {
                        $shopPrice = $cv['attrPrice'];
                    } elseif ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 2 && !$attrGoodsRanks) {
                        $shopPrice += $cv['attrPrice'];
                    }
                } else {
                    if ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 1 && $attrGoodsRanks) {
                        $shopPrice = $cv['attrPrice'];
                    } elseif ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 2 && !$attrGoodsRanks) {
                        $shopPrice += $cv['attrPrice'];
                    }
                    if ($shopId == $rankInfo['shopId'] && $attrGoodsRanks) {
                        foreach ($attrGoodsRanks as $ak => $av) {
                            if ($rankInfo['shopId'] == $shopId) {
                                if ($av['rankId'] == $userRank['rankId'] && $cv['id'] == $av['attributesID'] && $parentAttrInfo['isPriceAttr'] == 1) {
                                    //普通价格属性
                                    $shopPrice = $av['price'];
                                } elseif ($av['rankId'] == $userRank['rankId'] && $cv['id'] == $av['attributesID'] && $parentAttrInfo['isPriceAttr'] == 2) {
                                    //叠加价格属性
                                    $shopPrice += $av['price'];
                                }
                            }
                        }
                    }
                }
            }
        }
        $response['goodsPrice'] = $shopPrice;
        $response['totalMoney'] = $shopPrice * (float)$goodsCnt;
        return $response;
    } else {
        if ($shopId == $rankInfo['shopId']) {
            $attrGoodsRanks = $goodsRank->where("goodsId='" . $goodsId . "' AND rankId='" . $userRank['rankId'] . "' AND attributesID IS NULL")->select();
            foreach ($attrGoodsRanks as $ak => $av) {
                if ($rankInfo['shopId'] == $shopId) {
                    if ($av['rankId'] == $userRank['rankId']) {
                        $response['goodsPrice'] = $av['price'];
                        $response['totalMoney'] = $av['price'] * (float)$goodsCnt;
                        return $response; //PS: 有属性时价格为追加,没属性时价格为覆盖
                    }
                }
            }
        }
        return $response;
    }
    //2019-6-14 end
}

/*
 * 返还属性库存
 * params int $orderId
 * */
function returnAttrStock($orderId)
{
//    $orderGoods = M('order_goods')->where("orderId='" . $orderId . "'")->select();
//    $goodsAttr = M('goods_attributes');
//    foreach ($orderGoods as $key => $values) {
//        if (!empty($values['goodsAttrId'])) {
//            $goodsAttrId = $values['goodsAttrId'];
//            $goods_num = gChangeKg($values['goodsId'], $values['goodsNums'], 1);
//            $goodsAttr->where("id IN($goodsAttrId)")->setInc('attrStock', $goods_num);
//        }
//    }
}

/*
 * 多维数组根据某一个字段去重
 * */
function arrayUnset($arr, $key)
{
    //建立一个目标数组
    $res = array();
    foreach ($arr as $value) {
        //查看有没有重复项
        if (isset($res[$value[$key]])) {
            unset($value[$key]);  //有：销毁
        } else {
            $res[$value[$key]] = $value;
        }
    }
    return array_values($res);
}

//获取模板消息内容主体
function getMsg($openid, $template_id, $form_id, $emphasis_keyword = 'keyword1', $data = array())
{

    $data['data'] = $data;//内容主体

    $data['touser'] = $openid;//用户的openid
    $data['template_id'] = $template_id;//从微信后台获取的模板id
    $data['form_id'] = $form_id;//前端提供给后端的form_id
    $data['page'] = 'pages/index/index';//小程序跳转页面
    $data['emphasis_keyword'] = $emphasis_keyword;//选择放大的字体

    return $data;

}

/**
 * 微信小程序 - 消息模板
 * @param $openid  openid
 * @param $form_id 表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id
 * @param string $emphasis_keyword 模板需要放大的关键词，不填则默认无放大
 * @param array $data 模板内容
 * @return bool|mixed
 */
function sendMessage($openid, $form_id, $emphasis_keyword = 'keyword1', $data = array())
{
    /*  模板内容
        $data= [
            'keyword1'=>['value'=>'test1','color'=>''],
            'keyword2'=>['value'=>'test2','color'=>''],
            'keyword3'=>['value'=>'test1','color'=>'']
        ];
    */
    if (empty($openid)) return array('code' => 1, 'msg' => 'openid 不能为空!');

    $access_token = getWxAccessToken();
    $send_url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;

    $uploaddata = getMsg($openid, $GLOBALS["CONFIG"]["xiaoTemplateid"], $form_id, $emphasis_keyword, $data);
    $str = curlRequest($send_url, json_encode($uploaddata), 1);
    $str = json_decode($str, 1);

    return $str;
}


/**
 * 极光推送
 * @param $content  推送内容
 */
function jpush($content)
{

    if (empty($content)) return array('code' => 1, 'msg' => '内容不能为空!');

    Vendor('jpush.autoload');
    $client = new \JPush\Client($GLOBALS["CONFIG"]["jAppkey"], $GLOBALS["CONFIG"]["jMastersecret"]);
    $pusher = $client->push();
    $pusher->setPlatform('all');
    $pusher->addAllAudience();
    $pusher->setNotificationAlert($content);
    try {
        $pusher->send();
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        print $e;
    }
}

/**
 * @param $content 内容
 * @param $registration_id 用户设备标识id
 *别名极光推送
 */
function pushMessageByRegistrationId($title, $content, $alias)
{
    Vendor('jpush.autoload');
    $client = new \JPush\Client($GLOBALS["CONFIG"]["jAppkey"], $GLOBALS["CONFIG"]["jMastersecret"]);

    $pusher = $client->push();
    $pusher->setPlatform("all");
    $pusher->addAlias($alias);
    $pusher->androidNotification($content, ['title' => $title, 'extras' => []]);
    try {
        $date = $pusher->send();
        $res = $date['http_code'];
    } catch (\JPush\Exceptions\JPushException $e) {
        $res = $e;
    }
    return $res;
}

/*
 * 如果是自营店铺的话,店铺价格被批发价覆盖 PS:商品等级属性价格处理变动大,此价格后加
 * @param int goodsId
 * */
function handleTradePrice($goodsId)
{
    $goodsTab = M('goods');
    $shopTab = M('shops');
    $goodsInfo = $goodsTab->where("goodsId='" . $goodsId . "'")->field('goodsId,shopPrice,shopId,tradePrice')->find();
    $shopInfo = $shopTab->where("shopId='" . $goodsInfo['shopId'] . "'")->field('shopId,isSelf')->find();
    if ($shopInfo['isSelf'] == 1) {
        //自营
        return $goodsInfo['tradePrice'];
    } else {
        //非自营
        return $goodsInfo['shopPrice'];
    }
}

/*
 * 更新云仓 库存
 * @param array clouData
 * */
function updateCloudStorage($cloudData)
{
    $shopId = (int)session('WST_USER.shopId');
    //需要删除
    $res = [
        'apiCode' => -1,
        'apiInfo' => '修改云库存失败',
        'apiState' => 'error',
    ];
    if (!empty($cloudData['goodsSn'])) {
        $goodsWhere['goodsFlag'] = 1;
        $goodsWhere['goodsSn'] = $cloudData['goodsSn'];
        $goodsInfo = M('goods')->where($goodsWhere)->find();
        if (empty($shopId)) {
            $shopId = $goodsInfo['shopId'];
        }
        $mc = M('shop_configs');
        $shopConfig = $mc->where('shopId=' . $shopId)->find();
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $request['number'] = $cloudData['goodsSn'];
        $propertys = [];
        $propertys[] = [
            'qty' => $goodsInfo['goodsStock'], //库存
        ];
        $request['propertys'] = json_encode($propertys);
        $openApiUrl = C('OPEN_API') . "/index.php/OpenApi/updateGoodsPropertys";
        $res = curlRequest($openApiUrl, $request, true);
        $res = json_decode($res, true);
    }
    return $res;
}

/*
 *php获取标签的信息
 * */
function extract_attrib($tag)
{
    preg_match_all('/(id|alt|title|src)=("[^"]*")/i', $tag, $matches);
    $ret = array();
    foreach ($matches[1] as $i => $v) {
        $ret[$v] = $matches[2][$i];
    }
    return $ret;
}

/**
 * 判断用户是否是会员，如果是会员，则返回会员奖励积分倍数
 * @param $userId
 * @return int
 */
function WSTRewardScoreMultiple($userId)
{
    $scoreMultiple = 1;
    $userInfo = M('users')->where(array('userId' => $userId, 'userFlag' => 1, 'expireTime' => array('GT', date('Y-m-d H:i:s'))))->find();
    if (!empty($userInfo)) $scoreMultiple = (intval($GLOBALS['CONFIG']['rewardScoreMultiple']) > 0) ? intval($GLOBALS['CONFIG']['rewardScoreMultiple']) : 1;
    return $scoreMultiple;
}

function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $val = json_decode(json_encode($xmlstring), true);
    return $val;
}

/*
 * 验证云仓账号
 * */
function checkCloudAccount($shopId)
{
    $apiRet['apiCode'] = '-1';
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    $mc = M('shop_configs');
    $shopConfig = $mc->where('shopId=' . $shopId)->find();
    if (empty($shopConfig['cloudAccount']) || empty($shopConfig['cloudPwd'])) {
        return false;
    } else {
        return $shopConfig;
    }
}

/**
 * 自动分配筐位
 */
function autoDistributionBasket($shopId)
{
    $ret = array(
        'apiCode' => -1,
        'apiInfo' => '操作失败',
        'apiState' => 'error',
        'apiData' => array()
    );
    $shopInfo = M('shops')->where(array('shopId' => $shopId, 'shopFlag' => 1))->find();
    if (empty($shopInfo)) {
        $ret['apiInfo'] = "店铺不存在";
        return $ret;
    }
    $list = M('basket')->where(array('shopId' => $shopId, 'bFlag' => 1))->field('bid,orderNum')->select();
    if (empty($list)) {
        $ret['apiInfo'] = "没有筐";
        return $ret;
    }
    //获取分拣员
    $mod_sortingpersonnel = M('sortingpersonnel');
    $where = array();
    $where['shopid'] = $shopId;
    $where['state'] = 1;//在线状态(1：在线 -1：不在线)
    $where['isdel'] = 1;//是否删除(1：未删除 -1：已删除)
    $users = $mod_sortingpersonnel->where($where)->select();
    if (count($users) <= 0) {
        $ret['apiInfo'] = "请查看拣货员是否在线";
        return $ret;
    }

    $data = array();
    foreach ($list as $k => $v) {
//        $orderCount = 0;
//        $result = M('barcode as b')->join("left join wst_orders as o on b.orderNo = o.orderNo")->where(array('b.shopId'=>$shopId,'b.bFlag'=>-1,'b.basketId'=>$v['bid'],'o.orderStatus'=>array('in',array(1,2)),'o.orderFlag'=>1))->field("count(DISTINCT o.orderNo) as orderCount")->find();
//        if (!empty($result)) $orderCount = $result['orderCount'];

        $orderCount = M('orders')->where(array('basketId' => $v['bid'], 'orderStatus' => array('in', array(2)), 'orderFlag' => 1))->count();
        $orderCount = empty($orderCount) ? 0 : $orderCount;

        if ($v['orderNum'] == -1 || ($v['orderNum'] > 0 && $orderCount < $v['orderNum'])) {
            $data[] = array('orderCount' => $orderCount, 'basketId' => $v['bid']);
        }
    }
    if (empty($data)) {
        $ret['apiInfo'] = "没有可供分配的筐了";
        return $ret;
    }
    //分配订单最少的筐
    $data = min($data);
    $ret = array(
        'apiCode' => 0,
        'apiInfo' => '操作成功',
        'apiState' => 'success',
        'apiData' => $data
    );
    return $ret;

}

/**
 * 分配分拣员 PS:该函数用于autoDistributionSorting函数中
 * @param int $shopId
 * @param array $orderInfo
 * @param array $orderGoods
 */
function getOrderSorting($shopId, $orderInfo, $orderGoods)
{
    $ret['status'] = -1;
    $ret['msg'] = '操作失败';
    $ret['data'] = null;

    $goodsData = $orderGoods;
    $orderId = $orderInfo['orderId'];
    $mc = M('shop_configs');
    $mod_sorting = M('sorting');
    $modSortingGoods = M('sorting_goods_relation');
    //获取商家店铺设置
    $mod_sortingpersonnel = M('sortingpersonnel');
    $shopcg = $mc->where('shopId=' . $shopId)->find();
    //获取分拣员
    $where = array();
    $where['shopid'] = $shopId;
    $where['state'] = 1;
    $where['isdel'] = 1;
    $users = $mod_sortingpersonnel->where($where)->select();
    if (count($users) <= 0) {
        $ret['msg'] = '没有拣货员 请先添加拣货员';
        return $ret;
    }
    //yhj:这是原有的逻辑,可以当做按订单分拣,分配给未完成分拣单最少的那个人
    $goods_module = new \App\Modules\Goods\GoodsModule();
    $sortingLocationTab = M('sorting_location_relation');
    foreach ($users as $key => $val) {
        //获取分拣员今日的接单任务(未分拣完成的)
        unset($where);
        $where['personId'] = $val['id'];
        $where['addtime'] = array('EGT', date('Y-m-d 00:00:00'));
        $where['status'] = array('IN', [0, 1]);
        $users[$key]['count'] = $mod_sorting->where($where)->count();
        $users[$key]['locations'] = [];
        $sortingLocation = $sortingLocationTab->where(['personId' => $val['id'], 'isDelete' => 0])->select();
        $users[$key]['locations'] = [];
        if (!empty($sortingLocation)) {
            $users[$key]['locations'] = $sortingLocation;
        }
    }
    //单量
    $shopsDataSort = array();
    foreach ($users as $user) {
        $shopsDataSort[] = $user['count'];
    }
    array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $users);//从低到高排序
    //M()->startTrans(); PS:这里关闭事务,不然其他地方调用该方法使用事务会出现嵌套事务,导致事务无效
    if ($shopcg['sortingType'] == 1) {
        //按商品分
        foreach ($users as $key => $val) {
            $users[$key]['okGoods'] = [];
            //获取分拣员今日的接单任务(未分拣完成的)
            unset($where);
            $where['personId'] = $val['id'];
            $where['addtime'] = array('EGT', date('Y-m-d 00:00:00'));
//            $where['status'] = array('IN', [0, 1]);
            $where['isPack'] = -1;//是否打包[-1:未进入|1:已进入]
            $sortingList = $mod_sorting->where($where)->select();
            $users[$key]['count'] = 0;
            //获取该分拣员所有未完成分拣任务的未完成分拣商品的数量
            foreach ($sortingList as $sv) {
                $sWhere['sortingId'] = $sv['id'];
                $sortGoodsCount = $modSortingGoods->where($sWhere)->sum('goodsNum');//总数量
                $sortGoodsNumCount = $modSortingGoods->where($sWhere)->sum('sortingGoodsNum');//已分拣总数
                $noCompleted = $sortGoodsCount - $sortGoodsNumCount; //剩余未分拣完成商品总数
                if ($noCompleted < 0) {
                    $noCompleted = 0;
                }
                $users[$key]['count'] += $noCompleted;
            }
        }
        //商品数量
        $shopsDataSort = array();
        foreach ($users as $user) {
            $shopsDataSort[] = $user['count'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $users);
        //orderGoods
        $type = 0;//用于判断整笔订单是否都不存在货位 0:不存在|1:存在
        foreach ($orderGoods as $key => $value) {
            $orderGoods[$key]['location'] = [];
            $orderGoods[$key]['locationId'] = [];
            $goodsLocation = M('location_goods')->where(['goodsId' => $value['goodsId'], 'lgFlag' => 1])->select();
            if ($goodsLocation) {//商品存在货位
                $orderGoods[$key]['location'] = $goodsLocation;
                foreach ($goodsLocation as $glv) {
                    $orderGoods[$key]['locationId'][] = $glv['lid'];
                }
                $type = 1;
            }

        }
        //进行商品分配
        foreach ($users as $ukey => $uval) {
            $userLocation = $uval['locations'];
            foreach ($userLocation as $uk => $uv) {
                foreach ($orderGoods as $gkey => $gval) {
                    if (in_array($gval['goodsId'], $users[$ukey]['okGoods'])) {
                        continue;
                    }

                    if (in_array($uv['locationId'], $gval['locationId'])) {
//                        $users[$ukey]['okGoods'][] = $gval['goodsId'];
                        $users[$ukey]['okGoods'][] = $gval;
                        unset($orderGoods[$gkey]);
                    }
                    if (!empty($users[$ukey]['okGoods']) && empty($gval['locationId']) && $type == 1) {
                        $users[$ukey]['okGoods'][] = $gval;
                        unset($orderGoods[$gkey]);
                    }
                    //当这笔订单都不存在货位时
                    if ($type == 0) {
                        $users[$ukey]['okGoods'][] = $gval;
                        unset($orderGoods[$gkey]);
                    }
                }
            }
            //如果当前分拣员下没有可以分拣的商品跳出当前循环
            if (count($users[$ukey]['okGoods']) <= 0) {
                continue;
            }
            $settlementSrcNo = M("orderids")->add(array('rnd' => microtime(true)));
            $settlementNo = $settlementSrcNo . "" . (fmod($settlementSrcNo, 7));
            $add_data = array(
                'settlementNo' => $settlementNo,
                'uid' => $uval['id'],
                'personId' => $uval['id'],
                'type' => $shopcg['sortingType'],
                'orderId' => $orderId,
                //'goodsId'  =>  $v, 估计是之前的,先注释掉
                'addtime' => date('Y-m-d H:i:s'),
                'updatetime' => date('Y-m-d H:i:s'),
                'shopid' => $shopId,
                'basketId' => $orderInfo['basketId'],
            );
            $insertSortingId = $mod_sorting->add($add_data);
            //添加任务
            if (!$insertSortingId) {
                //M()->rollback();
                $ret['msg'] = '添加分拣任务失败';
                return $ret;
            }
            //记录分拣任务操作日志 start
            $param = [];
            $param['sortingId'] = $insertSortingId;
            $param['content'] = "系统分配分拣任务[ $settlementNo ]";
            insertSortingActLog($param);
            //记录分拣任务操作日志 end
            $sortingGoodsTab = M('sorting_goods_relation');
//            $addData = array();
            $goodsData = $users[$ukey]['okGoods'];//替换为当前分拣员分拣的商品
            foreach ($goodsData as $ggkey => $ggval) {
                $goods = $goods_module->getGoodsInfoById($ggval['goodsId'], '*', 2);
                $addData = [];
                $addData['sortingId'] = $insertSortingId;
                $addData['goodsId'] = $ggval['goodsId'];
                $addData['goodsNum'] = $ggval['goodsNums'];
                $addData['skuId'] = $ggval['skuId'];//分拣关联商品表,增加skuId
                if ($goods['SuppPriceDiff'] == 1) {//临时修改
//                    if (empty($addData['skuId'])) {
//                        $addData['orderWeight'] = $val['goodsNums'] * (float)$goods['weightG'];
//                    } else {
//                        $sku_detail = $goods_module->getSkuSystemInfoById($addData['skuId'], 2);
//                        $addData['orderWeight'] = $val['goodsNums'] * (float)$sku_detail['weigetG'];
//                    }
                    //废除包装系数
                    $addData['orderWeight'] = $val['goodsNums'];
                }
                $insertRest = $sortingGoodsTab->add($addData);
                if (!$insertRest) {
                    $where = [];
                    $where['id'] = $insertSortingId;
                    $mod_sorting->where($where)->delete();
                    //M()->rollback();
                    $ret['msg'] = '添加分拣商品失败';
                    return $ret;
                }
                unset($goodsData[$ggkey]);
            }

        }
        //M()->commit();
        $ret['status'] = 1;
        $ret['msg'] = '操作成功';
        $ret['data'] = null;
    } else {
        //按订单分
        //生成单号
        $settlementSrcNo = M("orderids")->add(array('rnd' => microtime(true)));
        $settlementNo = $settlementSrcNo . "" . (fmod($settlementSrcNo, 7));
        $add_data = array(
            'settlementNo' => $settlementNo,
            'uid' => $users[0]['id'],
            'personId' => $users[0]['id'], //分拣员id,上面那个字段是以前的
            'type' => $shopcg['sortingType'], //按订单分还是按商品分
            'orderId' => $orderId,
            //'goodsId'  =>  $v, 估计是之前的,先注释掉
            'addtime' => date('Y-m-d H:i:s'),
            'updatetime' => date('Y-m-d H:i:s'),
            'shopid' => $shopId,
            'basketId' => $orderInfo['basketId'],
        );
        $insertSortingId = $mod_sorting->add($add_data); //添加任务
        if (!$insertSortingId) {
            //M()->rollback();
            $ret['msg'] = '添加分拣任务失败';
            return $ret;
        }
        //记录分拣任务操作日志 start
        $param = [];
        $param['sortingId'] = $insertSortingId;
        $param['content'] = "系统分配分拣任务[ $settlementNo ]";
        insertSortingActLog($param);
        //记录分拣任务操作日志 end
        $sortingGoodsTab = M('sorting_goods_relation');
//        $addData = array();
        foreach ($orderGoods as $val) {
//            $goods = M('goods')->where(['goodsId' => $val['goodsId']])->find();
            $goods = $goods_module->getGoodsInfoById($val['goodsId'], '*', 2);
            $addData = array();
            $addData['sortingId'] = $insertSortingId;
            $addData['goodsId'] = $val['goodsId'];
            $addData['goodsNum'] = $val['goodsNums'];
            $addData['skuId'] = $val['skuId'];//分拣关联商品表,增加skuId
            if ($goods['SuppPriceDiff'] == 1) {//临时修改
//                if (empty($addData['skuId'])) {
//                    $addData['orderWeight'] = $val['goodsNums'] * (float)$goods['weightG'];
//                } else {
//                    $sku_detail = $goods_module->getSkuSystemInfoById($addData['skuId'], 2);
//                    $addData['orderWeight'] = $val['goodsNums'] * (float)$sku_detail['weigetG'];
//                }
                //废除包装系数
                $addData['orderWeight'] = $val['goodsNums'];
            }
            $addData['SuppPriceDiff'] = $goods['SuppPriceDiff'];
            $insertRest = $sortingGoodsTab->add($addData);
            if (!$insertRest) {
                //M()->rollback();
                $where = [];
                $where['id'] = $insertSortingId;
                $mod_sorting->where($where)->delete();
                $ret['msg'] = '添加分拣商品失败';
                return $ret;
            }
        }
        $ret['status'] = 1;
        $ret['msg'] = '操作成功';
        $ret['data'] = null;
    }
    //M()->commit();
    return $ret;
}

/**
 * 自动分配分拣员
 * @param $shopId
 * @param $orderId
 * @return array
 */
function autoDistributionSorting($shopId, $orderId)
{
    $ret['status'] = -1;
    $ret['msg'] = '操作失败';
    $ret['data'] = null;
    //加载商店信息
    $mc = M('shop_configs');
    $shopcg = $mc->where('shopId=' . $shopId)->find();
    if ($shopcg['isSorting'] != 1) {
        $ret['status'] = -1;
        $ret['msg'] = '未启动订单分拣功能';
        $ret['data'] = null;
        return $ret;
    }
    $orderGoods = M('order_goods')->where(array('orderId' => $orderId))->select();
    if (empty($orderGoods)) {
        $ret['status'] = -1;
        $ret['msg'] = '订单没有商品';
        $ret['data'] = null;
        return $ret;
    }
    $mod_orders = M('orders');
    $orderInfo = $mod_orders->where(array('orderId' => $orderId, 'shopId' => $shopId))->find();
    if (empty($orderInfo['basketId'])) {
        $ret['status'] = -1;
        $ret['msg'] = '没有筐位，请先对订单进行受理';
        $ret['data'] = null;
        return $ret;
    }
    //分拣方式,按整笔订单分
    $sortingData = getOrderSorting($shopId, $orderInfo, $orderGoods);
    if ($sortingData['status'] !== 1) {
        return $sortingData;
    }
    /*//生成单号
    $settlementSrcNo = M("orderids")->add(array('rnd'=>microtime(true)));
    $settlementNo = $settlementSrcNo."".(fmod($settlementSrcNo,7));
    $add_data = array(
        'settlementNo'  =>  $settlementNo,
        'uid'  =>  $sortingData['data'][0]['id'],
        'personId'  =>  $sortingData['data'][0]['id'], //分拣员id,上面那个字段是以前的
        'type'  =>  $shopcg['sortingType'], //按订单分还是按商品分
        'orderId'  =>  $orderId,
        //'goodsId'  =>  $v, 估计是之前的,先注释掉
        'addtime'  =>  date('Y-m-d H:i:s'),
        'updatetime'  =>  date('Y-m-d H:i:s'),
        'shopid'   =>  $shopId,
        'basketId' =>  $orderInfo['basketId'],
    );
    $insertSortingId = $mod_sorting->add($add_data); //添加任务
    if(!$insertSortingId){
        M()->rollback();
        $ret['status'] = -1;
        $ret['msg'] = '添加分拣任务失败';
        $ret['data'] = null;
        return $ret;
    }
    //记录分拣任务操作日志 start
    $param = [];
    $param['sortingId'] = $insertSortingId;
    $param['content'] = "系统分配分拣任务[ $settlementNo ]";
    insertSortingActLog($param);
    //记录分拣任务操作日志 end
    $sortingGoodsTab = M('sorting_goods_relation');
    $addData = array();
    foreach ($orderGoods as $val){
        $addData['sortingId'] = $insertSortingId;
        $addData['goodsId'] = $val['goodsId'];
        $addData['goodsNum'] = $val['goodsNums'];
        $addData['skuId'] = $val['skuId'];//分拣关联商品表,增加skuId
        $insertRest = $sortingGoodsTab->add($addData);
        if (!$insertRest) {
            M()->rollback();
            return $ret;
        }
    }*/

    $ret['status'] = 1;
    $ret['msg'] = '操作成功';
    $ret['data'] = $sortingData['data'][0];

    return $ret;
}

/*
 * 检测订单数据是否符合店铺设置的订单配送起步价
 * @param array $result PS:订单数据
 * @param array $users PS:用户信息
 * */
function checkdeliveryStartMoney($result, $users)
{
    $checkRes = true;
    $shopsName = [];
    for ($i = 0; $i < count($result); $i++) {
        $shopId = $result[$i][0]['shopId'];
        $shopInfo = M('shops')->where(['shopId' => $shopId])->find();
        for ($i1 = 0; $i1 < count($result[$i]); $i1++) {//商品总金额
            //获取当前订单所有商品总价
            $totalMoney[$i][$i1] = (float)$result[$i][$i1]["shopPrice"] * (float)$result[$i][$i1]["goodsCnt"];
            $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $result[$i][$i1]["goodsAttrId"], $result[$i][$i1]["goodsId"], $result[$i][$i1]["shopPrice"], $result[$i][$i1]["goodsCnt"], $result[$i][$i1]["shopId"])['totalMoney'];
            //原有基础新增
            if ($result[$i][$i1]["skuId"] > 0) {
                $goodsTotalMoney = getGoodsSkuPrice($users['userId'], $result[$i][$i1]["skuId"], $result[$i][$i1]["goodsId"], $result[$i][$i1]["shopPrice"], $result[$i][$i1]["goodsCnt"], $result[$i][$i1]["shopId"])['totalMoney'];
            }
            $totalMoney[$i][$i1] = $goodsTotalMoney;
        }
        $totalMoney[$i] = array_sum($totalMoney[$i]);//计算总金额
        if ($totalMoney[$i] < $shopInfo['deliveryStartMoney']) {
            $checkRes = false;
            $shopsName[] = $shopInfo;
        }
    }
    return ['state' => $checkRes, 'shopInfo' => $shopsName];
}

/**
 * 七牛计算下载凭证
 * @param $str
 * @return mixed
 */
function Qiniu_Encode($str) // URLSafeBase64Encode
{
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($str));
}

/**
 * 七牛计算下载凭证
 * @param $url
 * @return string
 */
function Qiniu_Sign($url)
{//$info里面的url
    $setting = C('UPLOAD_SITEIMG_QINIU');
    $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
    $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
    $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
    $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
    $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
    $duetime = NOW_TIME + 86400;//下载凭证有效时间
    $DownloadUrl = $url . '?e=' . $duetime;
    $Sign = hash_hmac('sha1', $DownloadUrl, $setting ["driverConfig"] ["secrectKey"], true);
    $EncodedSign = Qiniu_Encode($Sign);
    $Token = $setting ["driverConfig"] ["accessKey"] . ':' . $EncodedSign;
    $RealDownloadUrl = $DownloadUrl . '&token=' . $Token;//$RealDownloadUrl为下载对应私有资源的可用URL
    return $RealDownloadUrl;
}

/*********
 * 计算单价
 * orderId 订单Id
 * goodsId 商品Id
 * userId    用户Id
 * return 单价 单位元
 ***********/
function handleGoodsPayN($orderId, $goodsId, $skuId, $userId)
{
    $mod_order = M('orders');
    $mod_order_goods = M('order_goods');
//    $mod_coupons = M('coupons');
    $order_data = $mod_order->where("orderId = {$orderId} and userId = {$userId}")->find();
    $order_goods_data = $mod_order_goods->where("orderId = {$orderId} and goodsId={$goodsId} and skuId={$skuId}")->find();
//    $coupons_data = $mod_coupons->where("couponId = {$order_data['couponId']}")->find();
    //差额算法 不退运费的话 删除 $order_data['deliverMoney'] 即可
    //$mo = $order_data['scoreMoney']+$coupons_data['couponMoney']-$order_data['deliverMoney'];//包含运费
//    $mo = $order_data['scoreMoney'] + $coupons_data['couponMoney'];//不包含运费
    $mo = bc_math($order_data['scoreMoney'], $order_data['coupon_use_money'], 'bcadd');//不包含运费
    //$mo2 = ($order_data['totalMoney'] - $mo) / $order_data['totalMoney'];
    $mo2 = bc_math($order_data['totalMoney'], $mo, 'bcsub');
//    $mo2 = ($order_data['totalMoney'] - $mo) / $order_data['totalMoney'];
    $mo2 = bc_math($mo2, $order_data['totalMoney'], 'bcdiv');
    //$mo3 = $mo2 * ($order_goods_data['goodsNums'] * $order_goods_data['goodsPrice']);
    //$mo3 = $mo2 * ($order_goods_data['goodsPrice']);
    $mo3 = bc_math($mo2, $order_goods_data['goodsPrice'], 'bcmul');
    $mo3 = sprintf("%.2f", substr(sprintf("%.3f", $mo3), 0, -1));
    return $mo3;

}

/**
 * 修改商品库存
 * 通过 redis 来处理
 * @param $goodsId
 * @param float $goodsCnt
 * @return mixed
 */
//function updateGoodsStockByRedis($goodsId, $goodsCnt = 1)
//{
//
//    //--- 原来的，可用的 --- start ---
//    /*$apiRet['apiCode']='-1';
//    $apiRet['apiInfo']='修改商品库存失败';
//    $apiRet['apiState']='error';
//
//    if (empty($goodsId) || empty($goodsCnt)) {
//        $apiRet['apiInfo']='参数不全';
//        return $apiRet;
//    }
//
//    $redis=new \Redis();
//    $result=$redis->connect(C('redis_host1'),C('redis_port1'));
//    $res=$redis->llen('goods_stock_'.$goodsId);
//    if ($res <= 0) {//如果队列为空 就进行添加库存  否则跳过添加
//        $redis->del('goods_stock_'.$goodsId);
//
//        $goodsInfo = M('goods')->where(array('goodsId'=>$goodsId))->find();
//        $goodsStock = $goodsInfo['goodsStock'];
//        if ($goodsStock <= 0){
//            $apiRet['apiInfo'] = "商品库存不足";
//            return $apiRet;
//        }
//
//        $count=$goodsStock;
//        for($i=0;$i<$count;$i++){
//            $redis->lpush('goods_stock_'.$goodsId,1);
//        }
//        $redis->expire('goods_stock_'.$goodsId,60);//设置有效时间为1分钟
//    }
//    $str_len = $redis->llen('goods_stock_'.$goodsId);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet['apiInfo'] = "商品库存不足";
//        return $apiRet;
//    }
//
//    for ($i = 0;$i < $goodsCnt; $i++){
//        $redis->lpop('goods_stock_'.$goodsId);
//    }
//	$goodsCnt = gChangeKg($goodsId,$goodsCnt,1);
//    $returnState = M('goods')->where(array('goodsId'=>$goodsId))->setDec('goodsStock',$goodsCnt);
//    if ($returnState) {
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='修改商品库存成功';
//        $apiRet['apiState']='success';
//        return $apiRet;
//    } else {
//
//        for ($i = 0;$i < $goodsCnt; $i++){
//            $redis->lpush('goods_stock_'.$goodsId,1);
//        }
//        return $apiRet;
//    }*/
//    //--- 原来的，可用的 --- end ---
//
//    /*$apiRet['apiCode']='-1';
//    $apiRet['apiInfo']='修改商品库存失败';
//    $apiRet['apiState']='error';*/
//
//    if (empty($goodsId) || empty($goodsCnt)) {
//        $apiRet = returnData(null, -1, 'error', '参数不全');
//        return $apiRet;
//    }
//    $goodsInfo = M('goods')->where(array('goodsId' => $goodsId))->find();
//    $key = "goods_stock_";
//    $goodsStock = $goodsInfo['goodsStock'];
//    $stock = "goodsStock";
//    if ($goodsInfo['isLimitBuy'] == 1) {
//        $key = "goods_limit_stock_";
//        $goodsStock = $goodsInfo['limitCount'];
//        $stock = "limitCount";
//    }
//    $redis = new \Redis();
//    $result = $redis->connect(C('redis_host1'), C('redis_port1'));
////    $redis->connect('127.0.0.1',6378);
//    $res = $redis->llen($key . $goodsId);
//    //每件商品，redis最多存50个库存
//    $value = 50;
//    if ($res <= $value) {//如果小于50 就进行添加库存  否则跳过添加
////        $redis->del('goods_stock_'.$goodsId);
//
////        $goodsInfo = M('goods')->where(array('goodsId' => $goodsId))->find();
////        $goodsStock = $goodsInfo['goodsStock'];
//        if ($goodsStock <= 0) {
//            $apiRet = returnData(null, -1, 'error', '商品库存不足');
//            return $apiRet;
//        }
//
//        $count = ($goodsStock > $value) ? $value - $res : $goodsStock - $res;
//        if ($count > 0) {
//            for ($i = 0; $i < $count; $i++) {
//                $redis->lpush($key . $goodsId, 1);
//            }
//            $redis->expire($key . $goodsId, 60);//设置有效时间为1分钟
//        }
//    }
//    $str_len = $redis->llen($key . $goodsId);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet = returnData(null, -1, 'error', '商品库存不足');
//        return $apiRet;
//    }
//
//    for ($i = 0; $i < $goodsCnt; $i++) {
//        $redis->lpop($key . $goodsId);
//    }
//    $goodsCnt = gChangeKg($goodsId, $goodsCnt, 1);
//    $returnState = M('goods')->where(array('goodsId' => $goodsId))->setDec("{$stock}", $goodsCnt);
//    if ($returnState) {
//        $apiRet = returnData();
//        return $apiRet;
//    } else {
//        for ($i = 0; $i < $goodsCnt; $i++) {
//            $redis->lpush($key . $goodsId, 1);
//        }
//        $apiRet = returnData(null, -1, 'error', "修改商品库存失败:{$goodsInfo['goodsName']}");
//        return $apiRet;
//    }
//}

/**
 * 修改商品属性库存
 * 通过 redis 来处理
 */
//function updateGoodsAttrStockByRedis($id, $goodsCnt = 1)
//{
//
//    //--- 原来的，可用的 --- start ---
//    /*$apiRet['apiCode']='-1';
//    $apiRet['apiInfo']='修改商品属性库存失败';
//    $apiRet['apiState']='error';
//
//    if (empty($id) || empty($goodsCnt)) {
//        $apiRet['apiInfo']='参数不全';
//        return $apiRet;
//    }
//
//    $redis=new \Redis();
//    $result=$redis->connect(C('redis_host1'),C('redis_port1'));
//    $res=$redis->llen('goods_attr_stock_'.$id);
//    if ($res <= 0) {//如果队列为空 就进行添加库存  否则跳过添加
//        $redis->del('goods_attr_stock_'.$id);
//
//        $goodsAttrInfo = M('goods_attributes')->where(array('id'=>$id))->find();
//        $goodsStock = $goodsAttrInfo['attrStock'];
//        if ($goodsStock <= 0){
//            $apiRet['apiInfo'] = "商品属性库存不足";
//            return $apiRet;
//        }
//
//        $count=$goodsStock;
//        for($i=0;$i<$count;$i++){
//            $redis->lpush('goods_attr_stock_'.$id,1);
//        }
//        $redis->expire('goods_attr_stock_'.$id,60);//设置有效时间为1分钟
//    }
//    $str_len = $redis->llen('goods_attr_stock_'.$id);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet['apiInfo'] = "商品属性库存不足";
//        return $apiRet;
//    }
//
//    for ($i = 0;$i < $goodsCnt; $i++){
//        $redis->lpop('goods_attr_stock_'.$id);
//    }
//	$goodsId = M('goods_attributes')->where(array('id'=>$id))->getField('goodsId');
//	$goods_cnt = gChangeKg($goodsId,$goodsCnt,1);
//    $returnState = M('goods_attributes')->where(array('id'=>$id))->setDec('attrStock',$goods_cnt);
//    if ($returnState) {
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='修改商品属性库存成功';
//        $apiRet['apiState']='success';
//        return $apiRet;
//    } else {
//
//        for ($i = 0;$i < $goodsCnt; $i++){
//            $redis->lpush('goods_attr_stock_'.$id,1);
//        }
//        return $apiRet;
//    }*/
//    //--- 原来的，可用的 --- end ---
//
//
//    /*$apiRet['apiCode']='-1';
//    $apiRet['apiInfo']='修改商品属性库存失败';
//    $apiRet['apiState']='error';*/
//
//    if (empty($id) || empty($goodsCnt)) {
//        $apiRet = returnData(null, -1, 'error', '参数不全');
//        return $apiRet;
//    }
//
//    $redis = new \Redis();
//    $result = $redis->connect(C('redis_host1'), C('redis_port1'));
//    $res = $redis->llen('goods_attr_stock_' . $id);
//    //每件商品，redis最多存50个库存
//    $value = 50;
//    $goodsAttrInfo = M('goods_attributes')->where(array('id' => $id))->find();
//    if ($res <= $value) {//如果小于50 就进行添加库存  否则跳过添加
////        $redis->del('goods_attr_stock_'.$id);
//
//
//        $goodsStock = $goodsAttrInfo['attrStock'];
//        if ($goodsStock <= 0) {
//            $apiRet = returnData(null, -1, 'error', '商品属性库存不足');
//            return $apiRet;
//        }
//
//        $count = ($goodsStock > $value) ? $value - $res : $goodsStock - $res;
//        if ($count > 0) {
//            for ($i = 0; $i < $count; $i++) {
//                $redis->lpush('goods_attr_stock_' . $id, 1);
//            }
//            $redis->expire('goods_attr_stock_' . $id, 60);//设置有效时间为1分钟
//        }
//    }
//    $str_len = $redis->llen('goods_attr_stock_' . $id);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet = returnData(null, -1, 'error', '商品属性库存不足');
//        return $apiRet;
//    }
//
//    for ($i = 0; $i < $goodsCnt; $i++) {
//        $redis->lpop('goods_attr_stock_' . $id);
//    }
//
//    $goods_cnt = gChangeKg($goodsAttrInfo['goodsId'], $goodsCnt, 1);
//    $returnState = M('goods_attributes')->where(array('id' => $id))->setDec('attrStock', $goods_cnt);
//    if ($returnState) {
//        $apiRet = returnData();
//        return $apiRet;
//    } else {
//        for ($i = 0; $i < $goodsCnt; $i++) {
//            $redis->lpush('goods_attr_stock_' . $id, 1);
//        }
//        $apiRet = returnData(null, -1, 'error', '修改商品属性库存失败');
//        return $apiRet;
//    }
//
//}

/**
 * 修改商品秒杀量
 * 通过 redis 来处理
 * @param $goodsId
 * @param float $goodsCnt
 * @return mixed
 */
function updateGoodsShopSecKillNUMByRedis($goodsId, $goodsCnt = 1)
{

//    // --- 原来的，可用的 --- start ---
//    /*$apiRet['apiCode']='-1';
//    $apiRet['apiInfo']='商品秒杀失败';
//    $apiRet['apiState']='error';
//
//    if (empty($goodsId) || empty($goodsCnt)) {
//        $apiRet['apiInfo']='参数不全';
//        return $apiRet;
//    }
//
//    $redis=new \Redis();
//    $result=$redis->connect(C('redis_host1'),C('redis_port1'));
//    $res=$redis->llen('goods_shop_sec_kill_num_'.$goodsId);
//    if ($res <= 0) {//如果队列为空 就进行添加库存  否则跳过添加
//        $redis->del('goods_shop_sec_kill_num_'.$goodsId);
//
//        $goodsInfo = M('goods')->where(array('goodsId'=>$goodsId))->find();
//        $goodsStock = $goodsInfo['shopSecKillNUM'];
//        if ($goodsStock <= 0){
//            $apiRet['apiInfo'] = "秒杀量不足";
//            return $apiRet;
//        }
//
//        $count=$goodsStock;
//        for($i=0;$i<$count;$i++){
//            $redis->lpush('goods_shop_sec_kill_num_'.$goodsId,1);
//        }
//        $redis->expire('goods_shop_sec_kill_num_'.$goodsId,60);//设置有效时间为1分钟
//    }
//    $str_len = $redis->llen('goods_shop_sec_kill_num_'.$goodsId);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet['apiInfo'] = "秒杀量不足";
//        return $apiRet;
//    }
//
//    for ($i = 0;$i < $goodsCnt; $i++){
//        $redis->lpop('goods_shop_sec_kill_num_'.$goodsId);
//    }
//    $returnState = M('goods')->where(array('goodsId'=>$goodsId))->setDec('shopSecKillNUM',$goodsCnt);
//    if ($returnState) {
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商品秒杀成功';
//        $apiRet['apiState']='success';
//        return $apiRet;
//    } else {
//
//        for ($i = 0;$i < $goodsCnt; $i++){
//            $redis->lpush('goods_shop_sec_kill_num_'.$goodsId,1);
//        }
//        return $apiRet;
//    }*/
//    // --- 原来的，可用的 --- end ---
//
//
//    if (empty($goodsId) || empty($goodsCnt)) {
//        $apiRet = returnData(null, -1, 'error', '参数不全');
//        return $apiRet;
//    }
//
//    $redis = new \Redis();
//    $result = $redis->connect(C('redis_host1'), C('redis_port1'));
//    $res = $redis->llen('goods_shop_sec_kill_num_' . $goodsId);
//    //每件商品，redis最多存50个库存
//    $value = 50;
//    if ($res <= $value) {//如果队列小于50 就进行添加库存  否则跳过添加
////        $redis->del('goods_shop_sec_kill_num_'.$goodsId);
//
//        $goodsInfo = M('goods')->where(array('goodsId' => $goodsId))->find();
//        $goodsStock = $goodsInfo['shopSecKillNUM'];
//        if ($goodsStock <= 0) {
//            $apiRet = returnData(null, -1, 'error', '秒杀量不足');
//            return $apiRet;
//        }
//
//        $count = ($goodsStock > $value) ? $value - $res : $goodsStock - $res;
//        if ($count > 0) {
//            for ($i = 0; $i < $count; $i++) {
//                $redis->lpush('goods_shop_sec_kill_num_' . $goodsId, 1);
//            }
//            $redis->expire('goods_shop_sec_kill_num_' . $goodsId, 60);//设置有效时间为1分钟
//        }
//    }
//    $str_len = $redis->llen('goods_shop_sec_kill_num_' . $goodsId);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet = returnData(null, -1, 'error', '秒杀量不足');
//        return $apiRet;
//    }
//
//    for ($i = 0; $i < $goodsCnt; $i++) {
//        $redis->lpop('goods_shop_sec_kill_num_' . $goodsId);
//    }
//    $goodsCnt = gChangeKg($goodsId, $goodsCnt, 1);
//    $returnState = M('goods')->where(array('goodsId' => $goodsId))->setDec('shopSecKillNUM', $goodsCnt);
//    if ($returnState) {
//        return returnData();
//    } else {
//
//        for ($i = 0; $i < $goodsCnt; $i++) {
//            $redis->lpush('goods_shop_sec_kill_num_' . $goodsId, 1);
//        }
//        $apiRet = returnData(null, -1, 'error', '商品秒杀失败');
//        return $apiRet;
//    }

}

//打印输出数组信息
function printf_info($data)
{
    $data_t = array();
    foreach ($data as $key => $value) {
//        echo "<font color='#00ff55;'>$key</font> : ".htmlspecialchars($value, ENT_QUOTES)." <br/>";
        $data_t[$key] = htmlspecialchars($value, ENT_QUOTES);
    }
    return $data_t;
}

/**
 * 拼接字符串
 * @param $str  字符串
 * @param int $join 连接符号
 * @param int $len 长度
 * @return string
 */
function joinString($str, $join = 0, $len = 18)
{
    if (empty($str)) return '';
    $str_len = strlen($str);
    $str_new = "";
    $len_new = $len - $str_len;
    for ($i = 0; $i < $len_new; $i++) {
        $str_new .= $join;
    }
    return $str_new . $str;
}

/**
 * @param $operator 操作人姓名
 * @param $operatorId 操作人ID
 * @param $describe 操作详情描述
 * @param $operationType 操作行为类型【1:增加、2:删除、3:修改】
 * 添加总后台操作日志
 */
function addOperationLog($operator, $operatorId, $describe, $operationType)
{
    $param = [];
    $param['operator'] = (string)$operator;//操作人姓名
    $param['operatorId'] = (int)$operatorId;//操作人ID
    $param['describe'] = (string)$describe;//操作详情描述
    $param['operationType'] = (int)$operationType;//操作行为类型【1:增加、2:删除、3:修改】
    $param['loginIP'] = get_client_ip();;//登陆ip
    $param['createTime'] = date('Y-m-d H:i:s');//操作时间
    $logServiceModule = new \App\Modules\Log\LogServiceModule();
    $logServiceModule->addOperationLog($param);
}

/*
 *复制商家的商品
 * @param['shopSnCopy'] 店铺编号
 * @param['shopId'] 新店铺id
 * */
function copyShopGoods($param)
{
    //$param['shopSnCopy'] = '00181107';//需要删除
    if (!empty($param['shopSnCopy'])) {
        $goodsTab = M('goods');
        $attrTab = M('attributes');
        $goodsAttrTab = M('goods_attributes');
        $galleryTab = M('goods_gallerys');
        $rankGoodsTab = M('rank_goods');
        $shopCatTab = M('shops_cats');
        $skuSpecTab = M('sku_spec');//sku规格名称表
        $skuSpecAttrTab = M('sku_spec_attr');//sku属性值表
        $skuGoodsSystemTab = M('sku_goods_system');//商品SKU表(针对非自定义属性)
        $skuGoodsSelfTab = M('sku_goods_self');//商品SKU表(针对自定义规格属性)
        $shopInfo = M('shops')->where("shopSn='" . $param['shopSnCopy'] . "' AND shopFlag=1")->find();
        $goods = $goodsTab->where("shopId='" . $shopInfo['shopId'] . "' AND isSale=1 AND goodsFlag=1")->select(); //商品有效且上架中
        $attrList = $attrTab->where("shopId='" . $shopInfo['shopId'] . "' AND attrFlag=1")->order("attrId ASC")->select();//店铺有效属性
        //分类
        $shopOneCatList = $shopCatTab->where("shopId='" . $shopInfo['shopId'] . "' AND catFlag=1 AND parentId=0")->order("catId ASC")->select();
        foreach ($shopOneCatList as $key => $val) {
            $shopOneCatList[$key]['son'] = $shopCatTab->where("shopId='" . $shopInfo['shopId'] . "' AND catFlag=1 AND parentId='" . $val['catId'] . "'")->order("catId ASC")->select();
        }
        //需要打开注释
        foreach ($shopOneCatList as $key => $val) {
            unset($firstShopCat);
            $firstShopCat['shopId'] = $param['shopId'];
            $firstShopCat['parentId'] = 0;
            $firstShopCat['isShow'] = $val['isShow'];
            $firstShopCat['catName'] = $val['catName'];
            $firstShopCat['catSort'] = $val['catSort'];
            $firstShopCat['catFlag'] = $val['catFlag'];
            $firstShopCat['icon'] = $val['icon'];
            $firstShopCatId = $shopCatTab->add($firstShopCat);
            if (is_array($val['son'])) {
                foreach ($val['son'] as $v) {
                    unset($secondShopCat);
                    $secondShopCat['shopId'] = $param['shopId'];
                    $secondShopCat['parentId'] = $firstShopCatId;
                    $secondShopCat['isShow'] = $v['isShow'];
                    $secondShopCat['catName'] = $v['catName'];
                    $secondShopCat['catSort'] = $v['catSort'];
                    $secondShopCat['catFlag'] = $v['catFlag'];
                    $secondShopCat['icon'] = $v['icon'];
                    $shopCatTab->add($secondShopCat);
                }
            }
        }
        //复制店铺的sku属性名称和属性值,后加 start
        $where = [];
        $where['shopId'] = $shopInfo['shopId'];
        $where['dataFlag'] = 1;
        $skuSpec = $skuSpecTab->where($where)->order('specId asc')->select();
        if (!empty($skuSpec)) {
            foreach ($skuSpec as $specKey => $specVal) {
                $skuSpecInfo = $skuSpec[$specKey];
                $insertSpecInfo = [];
                $insertSpecInfo['specName'] = $skuSpecInfo['specName'];
                $insertSpecInfo['shopId'] = $param['shopId'];
                $insertSpecInfo['sort'] = $skuSpecInfo['sort'];
                $insertSpecInfo['addTime'] = date('Y-m-d H:i:s');
                $specId = $skuSpecTab->add($insertSpecInfo);
                //$specId = 22;
                $skuSpec[$specKey]['specAttr'] = [];
                $where = [];
                $where['specId'] = $specVal['specId'];
                $where['dataFlag'] = 1;
                $specAttr = $skuSpecAttrTab->where($where)->select();
                $insertSpecAttrData = [];//规格属性值
                if (!empty($specAttr)) {
                    foreach ($specAttr as $attrKey => $attrVal) {
                        $insertSpecAttrInfo = [];
                        $insertSpecAttrInfo['specId'] = $specId;
                        $insertSpecAttrInfo['attrName'] = $attrVal['attrName'];
                        $insertSpecAttrInfo['sort'] = $attrVal['sort'];
                        $insertSpecAttrInfo['addTime'] = date('Y-m-d H:i:s');
                        $insertSpecAttrData[] = $insertSpecAttrInfo;
                    }
                }
                if (!empty($insertSpecAttrData)) {
                    $skuSpecAttrTab->addAll($insertSpecAttrData);
                }
            }
        }
        //复制店铺的sku属性名称和属性值,后加 end
        foreach ($goods as $key => $val) {
            $goodsId = $val['goodsId'];
            $shopId = $val['shopId'];
            unset($val['goodsId']);
            unset($val['shopId']);
            $val['shopId'] = $param['shopId'];
            $oldShopCat1Info = $shopCatTab->where(['catId' => $val['shopCatId1']])->find();
            $newShopCat1Info = $shopCatTab->where(['catName' => $oldShopCat1Info['catName'], 'parentId' => 0, 'shopId' => $param['shopId']])->find();
            $val['shopCatId1'] = (int)$newShopCat1Info['catId'];
            $oldShopCat2Info = $shopCatTab->where(['catId' => $val['shopCatId2']])->find();
            $newShopCat2Info = $shopCatTab->where(['catName' => $oldShopCat2Info['catName'], 'parentId' => $newShopCat1Info['catId'], 'shopId' => $param['shopId']])->find();
            $val['shopCatId2'] = (int)$newShopCat2Info['catId'];
            $newGoodsId = $goodsTab->add($val);
            if ($newGoodsId) {
                foreach ($attrList as $v) {
                    $existAttrId = $v['attrId'];
                    unset($v['attrId']);
                    unset($v['shopId']);
                    $v['shopId'] = $param['shopId'];
                    $v['createTime'] = date('Y-m-d H:i:s', time());
                    $isExist = $attrTab->where("attrName='" . $v['attrName'] . "' AND shopId='" . $param['shopId'] . "' AND attrFlag=1")->find();
                    $newAttrId = $isExist['attrId'];
                    if (!$isExist) {
                        $newAttrId = $attrTab->add($v);
                    }
                    $goodsAttrList = $goodsAttrTab->where("shopId='" . $shopInfo['shopId'] . "' AND attrId='" . $existAttrId . "' AND goodsId='" . $goodsId . "'")->order('id ASC')->select();
                    if ($goodsAttrList) {
                        foreach ($goodsAttrList as $gv) {
                            unset($gv['id']);
                            $gv['shopId'] = $param['shopId'];
                            $gv['goodsId'] = $newGoodsId;
                            $gv['attrId'] = $newAttrId;
                            $newAttrGoodsId = $goodsAttrTab->add($gv);
                            //复制商品的等级
                            $rankGoodsList = M('rank_goods')->where("goodsId='" . $goodsId . "'")->order("id ASC")->select();
                            if ($rankGoodsList) {
                                foreach ($rankGoodsList as $lv) {
                                    unset($lv['id']);
                                    $lv['goodsId'] = $newGoodsId;
                                    if (!empty($lv['attributesID'])) {
                                        $lv['attributesID'] = $newAttrGoodsId;
                                    }
                                    $rankGoodsTab->add($lv);
                                }
                            }
                        }
                    }
                }
            }

            //复制商品的相册
            $galleryList = $galleryTab->where("goodsId='" . $goodsId . "' AND shopId='" . $shopId . "'")->order("id ASC")->select();
            if ($galleryList) {
                foreach ($galleryList as $rv) {
                    unset($rv['id']);
                    $rv['goodsId'] = $newGoodsId;
                    $rv['shopId'] = $param['shopId'];
                    $galleryTab->add($rv);
                }
            }
            //复制商品的sku信息 start
            $where = [];
            $where['goodsId'] = $goodsId;
            $where['dataFlag'] = 1;
            $systemInfoList = $skuGoodsSystemTab->where($where)->order('skuId asc')->select();
            if (!empty($systemInfoList)) {
                foreach ($systemInfoList as $sykey => $syval) {
                    $insertSystemInfo = [];
                    $insertSystemInfo['goodsId'] = $newGoodsId;
                    $insertSystemInfo['skuShopPrice'] = $syval['skuShopPrice'];
                    $insertSystemInfo['skuMemberPrice'] = $syval['skuMemberPrice'];
                    $insertSystemInfo['skuGoodsStock'] = $syval['skuGoodsStock'];
                    $insertSystemInfo['skuMarketPrice'] = $syval['skuMarketPrice'];
                    $insertSystemInfo['skuGoodsImg'] = $syval['skuGoodsImg'];
                    $insertSystemInfo['skuBarcode'] = $syval['skuBarcode'];
                    $insertSystemInfo['addTime'] = date('Y-m-d H:i:s');
                    $insertSkuId = $skuGoodsSystemTab->add($insertSystemInfo);
                    $where = [];
                    $where['self.skuId'] = $syval['skuId'];
                    $where['self.dataFlag'] = 1;
                    $selfList = M('sku_goods_self self')
                        ->join("left join wst_sku_spec spec on spec.specId=self.specId")
                        ->join("left join wst_sku_spec_attr attr on attr.attrId=self.attrId")
                        ->where($where)
                        ->order('self.id asc')
                        ->group('self.id')
                        ->select();
                    if (!empty($selfList)) {
                        $insertSelfData = [];
                        foreach ($selfList as $selfKey => $selfVal) {
                            $where = [];
                            $where['specName'] = $selfVal['specName'];
                            $where['shopId'] = $param['shopId'];
                            $where['dataFlag'] = 1;
                            $newSpecInfo = $skuSpecTab->where($where)->find();
                            if (empty($newSpecInfo)) {
                                continue;
                            }
                            $where = [];
                            $where['specId'] = $newSpecInfo['specId'];
                            $where['attrName'] = $selfVal['attrName'];
                            $where['dataFlag'] = 1;
                            $newAttrInfo = $skuSpecAttrTab->where($where)->find();
                            if (empty($newAttrInfo)) {
                                continue;
                            }
                            $insertSelfInfo = [];
                            $insertSelfInfo['skuId'] = $insertSkuId;
                            $insertSelfInfo['specId'] = $newSpecInfo['specId'];
                            $insertSelfInfo['attrId'] = $newAttrInfo['attrId'];
                            $insertSelfData[] = $insertSelfInfo;
                        }
                        if (!empty($insertSelfData)) {
                            $skuGoodsSelfTab->addAll($insertSelfData);
                        }
                    }
                }
            }
            //复制商品的sku信息 end
        }
    }
}

/*
 *复制商家的商品
 * @param['copyShopId'] 复用店铺id
 * @param['shopId'] 新店铺id
 * */
function copyShopGoodsNew($param)
{
    if (!empty($param['copyShopId'])) {
        $goodsTab = M('goods');
        $attrTab = M('attributes');
        $goodsAttrTab = M('goods_attributes');
        $galleryTab = M('goods_gallerys');
        $rankGoodsTab = M('rank_goods');
        $shopCatTab = M('shops_cats');
        $skuSpecTab = M('sku_spec');//sku规格名称表
        $skuSpecAttrTab = M('sku_spec_attr');//sku属性值表
        $skuGoodsSystemTab = M('sku_goods_system');//商品SKU表(针对非自定义属性)
        $skuGoodsSelfTab = M('sku_goods_self');//商品SKU表(针对自定义规格属性)
        $shopInfo['shopId'] = $param['copyShopId'];
        $goods = $goodsTab->where("shopId='" . $shopInfo['shopId'] . "' AND isSale=1 AND goodsFlag=1")->select(); //商品有效且上架中
        $attrList = $attrTab->where("shopId='" . $shopInfo['shopId'] . "' AND attrFlag=1")->order("attrId ASC")->select();//店铺有效属性
        //分类
        $shopOneCatList = $shopCatTab->where("shopId='" . $shopInfo['shopId'] . "' AND catFlag=1 AND parentId=0")->order("catId ASC")->select();
        foreach ($shopOneCatList as $key => $val) {
            $shopOneCatList[$key]['son'] = $shopCatTab->where("shopId='" . $shopInfo['shopId'] . "' AND catFlag=1 AND parentId='" . $val['catId'] . "'")->order("catId ASC")->select();
        }
        //需要打开注释
        foreach ($shopOneCatList as $key => $val) {
            unset($firstShopCat);
            $firstShopCat['shopId'] = $param['shopId'];
            $firstShopCat['parentId'] = 0;
            $firstShopCat['isShow'] = $val['isShow'];
            $firstShopCat['catName'] = $val['catName'];
            $firstShopCat['catSort'] = $val['catSort'];
            $firstShopCat['catFlag'] = $val['catFlag'];
            $firstShopCat['icon'] = $val['icon'];
            $firstShopCatId = $shopCatTab->add($firstShopCat);
            if (is_array($val['son'])) {
                foreach ($val['son'] as $v) {
                    unset($secondShopCat);
                    $secondShopCat['shopId'] = $param['shopId'];
                    $secondShopCat['parentId'] = $firstShopCatId;
                    $secondShopCat['isShow'] = $v['isShow'];
                    $secondShopCat['catName'] = $v['catName'];
                    $secondShopCat['catSort'] = $v['catSort'];
                    $secondShopCat['catFlag'] = $v['catFlag'];
                    $secondShopCat['icon'] = $v['icon'];
                    $shopCatTab->add($secondShopCat);
                }
            }
        }
        //复制店铺的sku属性名称和属性值,后加 start
        $where = [];
        $where['shopId'] = $shopInfo['shopId'];
        $where['dataFlag'] = 1;
        $skuSpec = $skuSpecTab->where($where)->order('specId asc')->select();
        if (!empty($skuSpec)) {
            foreach ($skuSpec as $specKey => $specVal) {
                $skuSpecInfo = $skuSpec[$specKey];
                $insertSpecInfo = [];
                $insertSpecInfo['specName'] = $skuSpecInfo['specName'];
                $insertSpecInfo['shopId'] = $param['shopId'];
                $insertSpecInfo['sort'] = $skuSpecInfo['sort'];
                $insertSpecInfo['addTime'] = date('Y-m-d H:i:s');
                $specId = $skuSpecTab->add($insertSpecInfo);
                $skuSpec[$specKey]['specAttr'] = [];
                $where = [];
                $where['specId'] = $specVal['specId'];
                $where['dataFlag'] = 1;
                $specAttr = $skuSpecAttrTab->where($where)->select();
                $insertSpecAttrData = [];//规格属性值
                if (!empty($specAttr)) {
                    foreach ($specAttr as $attrKey => $attrVal) {
                        $insertSpecAttrInfo = [];
                        $insertSpecAttrInfo['specId'] = $specId;
                        $insertSpecAttrInfo['attrName'] = $attrVal['attrName'];
                        $insertSpecAttrInfo['sort'] = $attrVal['sort'];
                        $insertSpecAttrInfo['addTime'] = date('Y-m-d H:i:s');
                        $insertSpecAttrData[] = $insertSpecAttrInfo;
                    }
                }
                if (!empty($insertSpecAttrData)) {
                    $skuSpecAttrTab->addAll($insertSpecAttrData);
                }
            }
        }
        //复制店铺的sku属性名称和属性值,后加 end
        foreach ($goods as $key => $val) {
            $goodsId = $val['goodsId'];
            $shopId = $val['shopId'];
            unset($val['goodsId']);
            unset($val['shopId']);
            $val['shopId'] = $param['shopId'];
            $oldShopCat1Info = $shopCatTab->where(['catId' => $val['shopCatId1']])->find();
            $newShopCat1Info = $shopCatTab->where(['catName' => $oldShopCat1Info['catName'], 'parentId' => 0, 'shopId' => $param['shopId']])->find();
            $val['shopCatId1'] = (int)$newShopCat1Info['catId'];
            $oldShopCat2Info = $shopCatTab->where(['catId' => $val['shopCatId2']])->find();
            $newShopCat2Info = $shopCatTab->where(['catName' => $oldShopCat2Info['catName'], 'parentId' => $newShopCat1Info['catId'], 'shopId' => $param['shopId']])->find();
            $val['shopCatId2'] = (int)$newShopCat2Info['catId'];
            $newGoodsId = $goodsTab->add($val);
            if ($newGoodsId) {
                foreach ($attrList as $v) {
                    $existAttrId = $v['attrId'];
                    unset($v['attrId']);
                    unset($v['shopId']);
                    $v['shopId'] = $param['shopId'];
                    $v['createTime'] = date('Y-m-d H:i:s', time());
                    $isExist = $attrTab->where("attrName='" . $v['attrName'] . "' AND shopId='" . $param['shopId'] . "' AND attrFlag=1")->find();
                    $newAttrId = $isExist['attrId'];
                    if (!$isExist) {
                        $newAttrId = $attrTab->add($v);
                    }
                    $goodsAttrList = $goodsAttrTab->where("shopId='" . $shopInfo['shopId'] . "' AND attrId='" . $existAttrId . "' AND goodsId='" . $goodsId . "'")->order('id ASC')->select();
                    if ($goodsAttrList) {
                        foreach ($goodsAttrList as $gv) {
                            unset($gv['id']);
                            $gv['shopId'] = $param['shopId'];
                            $gv['goodsId'] = $newGoodsId;
                            $gv['attrId'] = $newAttrId;
                            $newAttrGoodsId = $goodsAttrTab->add($gv);
                            //复制商品的等级
                            $rankGoodsList = M('rank_goods')->where("goodsId='" . $goodsId . "'")->order("id ASC")->select();
                            if ($rankGoodsList) {
                                foreach ($rankGoodsList as $lv) {
                                    unset($lv['id']);
                                    $lv['goodsId'] = $newGoodsId;
                                    if (!empty($lv['attributesID'])) {
                                        $lv['attributesID'] = $newAttrGoodsId;
                                    }
                                    $rankGoodsTab->add($lv);
                                }
                            }
                        }
                    }
                }
            }

            //复制商品的相册
            $galleryList = $galleryTab->where("goodsId='" . $goodsId . "' AND shopId='" . $shopId . "'")->order("id ASC")->select();
            if ($galleryList) {
                foreach ($galleryList as $rv) {
                    unset($rv['id']);
                    $rv['goodsId'] = $newGoodsId;
                    $rv['shopId'] = $param['shopId'];
                    $galleryTab->add($rv);
                }
            }
            //复制商品的sku信息 start
            $where = [];
            $where['goodsId'] = $goodsId;
            $where['dataFlag'] = 1;
            $systemInfoList = $skuGoodsSystemTab->where($where)->order('skuId asc')->select();
            if (!empty($systemInfoList)) {
                foreach ($systemInfoList as $sykey => $syval) {
                    $insertSystemInfo = [];
                    $insertSystemInfo['goodsId'] = $newGoodsId;
                    $insertSystemInfo['skuShopPrice'] = $syval['skuShopPrice'];
                    $insertSystemInfo['skuMemberPrice'] = $syval['skuMemberPrice'];
                    $insertSystemInfo['skuGoodsStock'] = $syval['skuGoodsStock'];
                    $insertSystemInfo['skuMarketPrice'] = $syval['skuMarketPrice'];
                    $insertSystemInfo['skuGoodsImg'] = $syval['skuGoodsImg'];
                    $insertSystemInfo['skuBarcode'] = $syval['skuBarcode'];
                    $insertSystemInfo['unit'] = $syval['unit'];
                    $insertSystemInfo['weigetG'] = $syval['weigetG'];
                    $insertSystemInfo['purchase_price'] = $syval['purchase_price'];
                    $insertSystemInfo['addTime'] = date('Y-m-d H:i:s');
                    $insertSkuId = $skuGoodsSystemTab->add($insertSystemInfo);
                    $where = [];
                    $where['self.skuId'] = $syval['skuId'];
                    $where['self.dataFlag'] = 1;
                    $selfList = M('sku_goods_self self')
                        ->join("left join wst_sku_spec spec on spec.specId=self.specId")
                        ->join("left join wst_sku_spec_attr attr on attr.attrId=self.attrId")
                        ->where($where)
                        ->order('self.id asc')
                        ->group('self.id')
                        ->select();
                    if (!empty($selfList)) {
                        $insertSelfData = [];
                        foreach ($selfList as $selfKey => $selfVal) {
                            $where = [];
                            $where['specName'] = $selfVal['specName'];
                            $where['shopId'] = $param['shopId'];
                            $where['dataFlag'] = 1;
                            $newSpecInfo = $skuSpecTab->where($where)->find();
                            if (empty($newSpecInfo)) {
                                continue;
                            }
                            $where = [];
                            $where['specId'] = $newSpecInfo['specId'];
                            $where['attrName'] = $selfVal['attrName'];
                            $where['dataFlag'] = 1;
                            $newAttrInfo = $skuSpecAttrTab->where($where)->find();
                            if (empty($newAttrInfo)) {
                                continue;
                            }
                            $insertSelfInfo = [];
                            $insertSelfInfo['skuId'] = $insertSkuId;
                            $insertSelfInfo['specId'] = $newSpecInfo['specId'];
                            $insertSelfInfo['attrId'] = $newAttrInfo['attrId'];
                            $insertSelfData[] = $insertSelfInfo;
                        }
                        if (!empty($insertSelfData)) {
                            $skuGoodsSelfTab->addAll($insertSelfData);
                        }
                    }
                }
            }
            //复制商品的sku信息 end
        }
    }
}

/*
 * 检查分拣任务是否满足完成条件
 * 根据分拣任务商品的分拣数量来完成
 *@param int sortingWorkId
 * */
function checkSortingStatus($sortingWorkId)
{
    if ($sortingWorkId > 0) {
        //获取该任务下所有需要分拣商品的数量
        $totalSortingGoods = M("sorting_goods_relation")->where(['sortingId' => $sortingWorkId])->sum('goodsNum');
        //获取该任务下所有已经分拣商品的数量
        $sortingGoodsNum = M("sorting_goods_relation")->where(['sortingId' => $sortingWorkId])->sum('sortingGoodsNum');

        if ($sortingGoodsNum >= $totalSortingGoods) {
            //已分拣的数量大于或等于待分拣商品的数量,更改任务状态为已完成
            $edit['status'] = 2;
            $edit['endDate'] = date('Y-m-d H:i:s', time());
            $edit['updatetime'] = date('Y-m-d H:i:s', time());
            M('sorting')->where(['id' => $sortingWorkId])->save($edit);
            //记录分拣任务操作日志 start
            $settlementNo = M('sorting')->where(["id" => $sortingWorkId])->getField('settlementNo');
            $param = [];
            $param['sortingId'] = $sortingWorkId;
            $param['content'] = "更改分拣任务[ $settlementNo ]状态为已完成";
            insertSortingActLog($param);
            //记录分拣任务操作日志 end

            //改变订单状态为配送中 PS:后加
            $sortingInfo = M('sorting')->where(['id' => $sortingWorkId])->find();
            $shopConfigInfo = M('shop_configs')->where(['shopId' => $sortingInfo['shopid']])->field('sortingAutoDelivery')->find();
            if ($shopConfigInfo['sortingAutoDelivery'] == 1) {
                $orderModel = D('Home/Orders');
                //商家设置分拣完成自动发货配送
                $orderTab = M('orders');
                $saveData = [];
                $orderInfo = $orderTab->where(['orderId' => $sortingInfo['orderId']])->find();
                if ($orderInfo["deliverType"] == 2 and $orderInfo["isSelf"] == 0) {
                    //预发布 并提交达达订单
//                    $funResData = $orderModel::DaqueryDeliverFee($orderInfo);
                    $funResData = $orderModel::DaqueryDeliverFee(array(), $orderInfo['orderId']);
                    return $funResData;
                }
                //自建司机配送
                if ($orderInfo['deliverType'] == 6 and $orderInfo["isSelf"] == 0) {
                    $funResData = $orderModel::dirverQueryDeliverFee(array(), $orderInfo['orderId']);
                    return $funResData;
                }
                //自建物流配送
                if ($orderInfo["deliverType"] == 4 and $orderInfo["isSelf"] == 0) {
                    if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
                        $rsdata["status"] = -1;
                        $rsdata["info"] = '请联系管理员配置快跑者信息';
                        return $rsdata;
                    };
                    $funResData = $orderModel::KuaiqueryDeliverFee(array(), $orderInfo['orderId']);
                    return $funResData;
                }
                $saveData['deliveryTime'] = date('Y-m-d H:i:s', time());
                $saveData['orderStatus'] = 3;
                $orderTab->where(['orderId' => $sortingInfo['orderId']])->save($saveData);

                //创建出库单
                (new \App\Modules\Sorting\SortingModule())->completeSortingDoWarehouse($sortingWorkId);
            }
        }
    }
}

/**
 * @param $params
 * @return bool
 * 检查分拣任务是否满足完成条件
 * 根据分拣任务商品的分拣状态来完成
 */
function checkSortGoodsStatus($params)
{
    $sortingWorkId = (int)$params['sortingId'];
    $status = (int)$params['status'];
    if ($sortingWorkId > 0) {
        //获取该任务下所有需要分拣商品的数量
        $totalSortingGoods = M("sorting_goods_relation")->where(['sortingId' => $sortingWorkId, 'dataFlag' => 1])->count('status');
        //获取该任务下所有已经分拣商品的数量
        $sortingGoodsNum = M("sorting_goods_relation")->where(['sortingId' => $sortingWorkId, 'dataFlag' => 1])->sum("status >= {$status}");

        if ($sortingGoodsNum >= $totalSortingGoods) {
            //判断当前状态先是否还存在商品,如果存在则按当前状态来,如果不存在则按下一个状态
            $sortStatusNum = M("sorting_goods_relation")->where(['sortingId' => $sortingWorkId, 'dataFlag' => 1])->sum("status = {$status}");
            $edit = [];
            if ($status == 0) {
                $logCount = M('sorting_action_log')->where(['sortingId' => $sortingWorkId])->count();
                if ((int)$sortStatusNum > 0 || $totalSortingGoods == 1) {
                    $edit['status'] = 1;
                    $status = "分拣中";
                    $edit['startDate'] = date('Y-m-d H:i:s', time());
                    if ($logCount >= 2) {
                        return false;
                    }
                } else {
                    return false;
                }
            } elseif ($status == 2) {
                if ((int)$sortStatusNum > 0) {
                    $edit['status'] = 2;
                    $status = "待入框";
                }
            } elseif ($status == 3) {
                if ((int)$sortStatusNum > 0) {
                    $edit['status'] = 3;
                    $status = "已入框";
                    $edit['endDate'] = date('Y-m-d H:i:s', time());
                    //非常规订单受理时不呼叫骑手，在分拣员分拣任务入框完成后呼叫
                    callRiderOrder($params);
                }
            }

            //已分拣的数量大于或等于待分拣商品的数量,更改任务状态为已完成
            $edit['updatetime'] = date('Y-m-d H:i:s', time());
            M('sorting')->where(['id' => $sortingWorkId])->save($edit);
            //记录分拣任务操作日志 start
            $sortInfo = M('sorting ws')
                ->join("left join wst_sortingpersonnel wsu on wsu.id = ws.uid")
                ->where(["ws.id" => $sortingWorkId])
                ->field('ws.settlementNo,wsu.userName')
                ->find();
            $param = [];
            $param['sortingId'] = $sortingWorkId;
            $param['content'] = "[{$sortInfo['userName']}]:更改分拣任务[ {$sortInfo['settlementNo']} ]状态为{$status}";
            insertSortingActLog($param);
            //记录分拣任务操作日志 end
            return true;
        }
    }
}

/**
 * @param $params
 * @return bool
 * 分拣入框后---非常规订单通知骑手
 * TODO：改：支持所有订单通知骑手,在受理时不会通知骑手2020-11-1 16:31:25
 */
function callRiderOrder($params)
{
    $sortingWorkId = (int)$params['sortingId'];
    $orderModel = D('Home/Orders');
    $sortModel = D("Merchantapi/SortingApi");
    $orderInfo = M('sorting ws')
        ->join("left join wst_orders wo on wo.orderId = ws.orderId")
        ->where(["ws.id" => $sortingWorkId])
        ->field('wo.*')
        ->find();
    if ($orderInfo['isSelf'] == 1) {
        //临时修改,之前的老版本自提单未处理
        $saveData = [];
        $saveData['deliveryTime'] = date('Y-m-d H:i:s', time());
        $saveData['orderStatus'] = 3;
        M('orders')->where(['orderId' => $orderInfo['orderId']])->save($saveData);
        return true;
    }
    if (!in_array($orderInfo["deliverType"], [2, 4]) || $orderInfo["isSelf"] != 0) {
        return false;
    }
    //检查当前订单是否是异常订单,异常订单不呼叫骑手
    $orderId = $orderInfo['orderId'];
    $getBasketGoodsNum = $sortModel->getOrderBasketGoods($orderId, 1);
    if ($getBasketGoodsNum <= 0) {
        $content = "订单异常情况不呼叫骑手";
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 2;
        $logParams['orderStatus'] = 2;
        $logParams['payStatus'] = 1;
        D('Home/Orders')->addOrderLog([], $logParams);
        $saveData = [];
        $saveData['deliveryTime'] = date('Y-m-d H:i:s', time());
        $saveData['orderStatus'] = 4;
        M('orders')->where(['orderId' => $orderId])->save($saveData);
        return false;
    }
    if ($orderInfo["deliverType"] == 2 and $orderInfo["isSelf"] == 0) {
        //预发布 并提交达达订单
        $funResData = $orderModel::DaqueryDeliverFee(array(), $orderId);
        if ($funResData['code'] == 0) {
            //写入订单日志
            $content = "商家已通知达达取货";
            $orderStatus = 7;//7:等待骑手接单
        } else {
            $content = "{$funResData['msg']}";
            $orderStatus = 2;
        }
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 2;
        $logParams['orderStatus'] = $orderStatus;
        $logParams['payStatus'] = 1;
        D('Home/Orders')->addOrderLog([], $logParams);
        return true;
    }
    if ($orderInfo["deliverType"] == 4 and $orderInfo["isSelf"] == 0) {
        if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
            return false;
        }
        $funResData = $orderModel::KuaiqueryDeliverFee(array(), $orderId);
        if ($funResData['code'] == 0) {
            $content = "商家已通知骑手取货";
            $orderStatus = 7;//7:等待骑手接单
        } else {
            $content = "{$funResData['msg']}";
            $orderStatus = 2;
        }
        $logParams = [];
        $logParams['orderId'] = $orderId;//订单ID
        $logParams['content'] = $content;
        $logParams['logType'] = 2;//类型【0:用户|1:商家平台|2:系统|3:司机】
        $logParams['orderStatus'] = $orderStatus;//订单状态 PS:和订单表状态一致
        $logParams['payStatus'] = 1;//支付状态【0：未支付|1：已支付|2：已退款】
        D('Home/Orders')->addOrderLog([], $logParams);
        return true;
    }
}

/**
 * @param $orderId
 * @return mixed
 * 打包完成后触发---改变订单状态为配送中
 */
function editOrderStatus($orderId)
{
    //改变订单状态为配送中 PS:后加
    $orderInfo = M('orders')->where(['orderId' => $orderId])->find();
    $shopConfigInfo = M('shop_configs')->where(['shopId' => $orderInfo['shopId']])->field('sortingAutoDelivery')->find();
    if ($shopConfigInfo['sortingAutoDelivery'] == 1) {
//        $orderModel = D('Home/Orders');
        //商家设置分拣完成自动发货配送
        $orderTab = M('orders');
        //start========当前呼叫骑手功能去除，在商家后台接单时就已经呼叫骑手了===================================
        $saveData = [];
//        $orderInfo = $orderTab->where(['orderId' => $sortingInfo['orderId']])->find();
        /*if ($orderInfo["deliverType"] == 2 and $orderInfo["isSelf"] == 0) {
            //预发布 并提交达达订单
            $funResData = $orderModel::DaqueryDeliverFee($orderInfo);
            return $funResData;
        }
        //自建司机配送
        if ($orderInfo['deliverType'] == 6 and $orderInfo["isSelf"] == 0) {
            $funResData = $orderModel::dirverQueryDeliverFee($orderInfo['orderId']);
            return $funResData;
        }
        //自建物流配送
        if ($orderInfo["deliverType"] == 4 and $orderInfo["isSelf"] == 0) {
            if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
                $rsdata["status"] = -1;
                $rsdata["info"] = '请联系管理员配置快跑者信息';
                return $rsdata;
            };
            $funResData = $orderModel::KuaiqueryDeliverFee($orderInfo);
            return $funResData;
        }*/
        //=====================end=================================================================================
        $saveData['deliveryTime'] = date('Y-m-d H:i:s', time());
        $saveData['orderStatus'] = 3;

        $orderTab->where(['orderId' => $orderId])->save($saveData);
    }
}

/**
 * 将证书内容转换成临时文件,并返回临时文件路径
 * 主要用于微信退款
 * @param $content
 * @return mixed
 * 原来的，可用的
 */
/*function getTmpPathByContent($content)
{
    static $tmpFile = null;
    $tmpFile = tmpfile();
    fwrite($tmpFile, $content);
    $tempPemPath = stream_get_meta_data($tmpFile);
    return $tempPemPath['uri'];
}*/

/**
 * 将证书内容转换成临时文件,并返回临时文件路径
 * 主要用于微信退款
 * @param $content
 * @return mixed
 */
/*function getTmpPathByContent($content)
{
    static $tmpFile = null;
//    $tmpFile = tempnam('D:\phptmp','phptmp');
//    $dir = $_SERVER['DOCUMENT_ROOT']."/xiaoniao/Apps/Runtime/phptmp";
    $dir = WSTRootPath()."/Apps/Runtime/phptmp";
    if (!is_dir($dir)) mkdir($dir,0777,true);
    $tmpFile = tempnam($dir,'phptmp');
    $tempPemPath_arr = explode('.',$tmpFile);
    $tempPemPath_new = $tempPemPath_arr[0].'.pem';
    rename($tmpFile,$tempPemPath_new);
    $myfile = fopen($tempPemPath_new, "a+") or die("Unable to open file!");
    fwrite($myfile, $content);
    fclose($myfile);
    return $tempPemPath_new;
}*/

/**
 * 将证书内容转换成临时文件,并返回临时文件路径
 * 主要用于微信退款
 * @param $content
 * @return mixed
 */
function getTmpPathByContent($content)
{
    static $tmpFile = null;
//    $tmpFile = tempnam('D:\phptmp','phptmp');
    // $dir = $_SERVER['DOCUMENT_ROOT']."/xiaoniao/Apps/Runtime/phptmp";
    $dir = WSTRootPath() . "/Apps/Runtime/phptmp";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $tmpFile = tempnam($dir, 'phptmp');
    $tempPemPath_arr = explode('/', $tmpFile);
    $tempPemPath_new = $tempPemPath_arr[count($tempPemPath_arr) - 1] . '.pem';
    $tempPemPath_arr[count($tempPemPath_arr) - 1] = $tempPemPath_new;
    $tempPemPath_new = implode('/', $tempPemPath_arr);
    rename($tmpFile, $tempPemPath_new);
    $myfile = fopen($tempPemPath_new, "a+") or die("Unable to open file!");
    fwrite($myfile, $content);
    fclose($myfile);
    return $tempPemPath_new;
}

/*
 * 获取商品货位
 *@param int goodsId
 * */
function getGoodsLocation($goodsId)
{
    $firstLocation = [];
    if ($goodsId > 0) {
        //货位
        $locationGoodsTab = M('location_goods'); //商品货位表
        $locationTab = M('location'); //货位表
        //location 商品对应的货位信息,目前只做两级
        $goodsLocation = $locationGoodsTab->where(['goodsId' => $goodsId, 'lgFlag' => 1])->order('lgid asc')->select();
        if ($goodsLocation) {
            foreach ($goodsLocation as $gv) {
                $parentId[] = $gv['lparentId'];
            }
            $parentId = array_unique($parentId);
            sort($parentId);
            $locationWhere['lid'] = ['IN', $parentId];
            $locationWhere['lFlag'] = 1;
            $firstLocation = $locationTab->where($locationWhere)->select();
            foreach ($firstLocation as $fk => $fv) {
                $firstLocation[$fk]['secondLocation'] = [];
                //二级货位信息
                foreach ($goodsLocation as $sk => $sv) {
                    if ($fv['lid'] == $sv['lparentId']) {
                        $secondLocationInfno = $locationTab->where(['lid' => $sv['lid']])->find();
                        $firstLocation[$fk]['secondLocation'][] = $secondLocationInfno;
                    }
                }
            }
        }
    }
    return $firstLocation;
}

/**
 * 计算新时间
 */
function calculationTime($date, $days)
{
    return date("Y-m-d H:i:s", strtotime("+" . $days . " days", strtotime($date)));
}

/*
 * 更改云端商品库存变动 PS:niaocms商品库存变动时调用该方法,定义多个参数有用处
 * @param int goodsId PS:商品id
 * @param int goodsNum PS:商品数量
 * @param int type PS:操作类型[0:增加|1:减少|2:更新]
 * */
function updateJXCGoodsStock($goodsId, $goodsNum, $type = 0)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    if (!empty($goodsId) && !empty($goodsNum)) {
        $goodsInfo = M('goods')->where(['goodsId' => $goodsId])->field('goodsSn,shopId')->find();
        $shopId = M('shops')->where(['shopId' => $goodsInfo['shopId']])->getField('shopId');
        $jxc = D('Merchantapi/Jxc');
        $houseInfo = $jxc->checkShopWareHouse($shopId);
        if (empty($houseInfo['id'])) {
            return $houseInfo;
        }
        $warehouseId = $houseInfo['id']; //云仓商品关联必要参数 仓库id
        $goodsSn = $goodsInfo['goodsSn'];//云仓商品关联必要参数 商品编号
        $is_goodInfo = M("goods", "is_")->where(["number" => $goodsSn, "warehouse" => $warehouseId])->find();
        if ($is_goodInfo) {
            $where = " where id='" . $is_goodInfo['id'] . "' and warehouse='" . $warehouseId . "'";
            if ($type == 0) {
                //增加库存
                $sql = "update is_goods set stocktip=stocktip+'" . $goodsNum . "' ";

            } elseif ($type == 1) {
                //减少库存
                $sql = "update is_goods set stocktip=stocktip-'" . $goodsNum . "' ";
            } elseif ($type == 2) {
                //更新库存
                $sql = "update is_goods set stocktip='" . $goodsNum . "' ";
            }
            $sql .= $where;
            $res = M()->execute($sql);
            if ($res !== false) {
                createJXCAllocationclass($goodsId, $goodsNum, '', $type);
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }
        }
    }
    return $apiRet;
}

/*
 * 仓库存储变动
 * @param int goodsId PS:商品id
 * @param int goodsNum PS:商品数量
 * @param int type PS:操作类型[0:增加|1:减少|2:更新]
 * */
function createJXCAllocationclass($goodsId, $goodsNum, $content, $type = 0)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    $goodsInfo = M('goods')->where(['goodsId' => $goodsId])->field('goodsSn,shopId,goodsStock')->find();
    $shopId = M('shops')->where(['shopId' => $goodsInfo['shopId']])->getField('shopId');
    $jxc = D('Merchantapi/Jxc');
    $houseInfo = $jxc->checkShopWareHouse($shopId);
    if (empty($houseInfo['id'])) {
        return $houseInfo;
    }
    $warehouseId = $houseInfo['id'];
    $goodsSn = $goodsInfo['goodsSn'];
    $is_goodInfo = M("goods", "is_")->where(["number" => $goodsSn, "warehouse" => $warehouseId])->find();
    $is_goodsId = $is_goodInfo['id'];
    M()->startTrans();//开启事物
    //is_otpurchaseclass
    $otpurchaseclass["merchant"] = $houseInfo['merchant'];
    $otpurchaseclass["time"] = time();
    $otpurchaseclass["number"] = $jxc->get_number("QTRKD");
    $otpurchaseclass["pagetype"] = 0;
    $otpurchaseclass["user"] = $houseInfo["userId"];
    $otpurchaseclass["file"] = "";
    $otpurchaseclass["data"] = !empty($content) ? $content : "商户端商品添加或编辑,涉及库存变动";
    $otpurchaseclass["type"] = 1;
    $otpurchaseclass["auditinguser"] = $houseInfo["userId"];
    $otpurchaseclass["auditingtime"] = time();
    $otpurchaseclass["more"] = "";
    if ($type == 1) {
        //出库
        $otpurchaseclassId = M("otpurchaseclass", "is_")->add($otpurchaseclass);
    } else {
        //入库
        $otpurchaseclassId = M("otsaleclass", "is_")->add($otpurchaseclass);
    }
    if ($otpurchaseclassId) {
        //is_room
        $roomWhere["warehouse"] = $houseInfo['id'];
        $roomWhere["goods"] = $is_goodsId;
        $roomInfo = M("room", "is_")->where($roomWhere)->find();
        if (!$roomInfo) {
            $insert = [];
            $insert["warehouse"] = $houseInfo['id'];
            $insert["goods"] = $is_goodsId;
            $insert["nums"] = $goodsInfo['goodsStock'];
            $roomId = M("room", "is_")->add($insert);
        } else {
            $roomId = $roomInfo['id'];
        }
        $editRoomStock = M("room", "is_")->where(["id" => $roomId])->save(["nums" => $is_goodInfo["stocktip"]]);
        if ($roomId) {
            //is_otpurchaseinfo
            $otpurchaseinfo = [];
            $otpurchaseinfo["pid"] = $otpurchaseclassId;
            $otpurchaseinfo["goods"] = $is_goodsId;
            $otpurchaseinfo["attr"] = '';
            $otpurchaseinfo["warehouse"] = $houseInfo["id"];
            $otpurchaseinfo["nums"] = $goodsInfo['goodsStock'];
            $otpurchaseinfo["data"] = "商户端商品添加或编辑,涉及库存变动";
            $otpurchaseinfo["room"] = $roomId;
            if ($type == 1) {
                $otpurchaseinfo["data"] = "商户端商品库存减少,涉及库存变动";
                unset($otpurchaseinfo['attr']);
                $infoId = M("otsaleinfo", "is_")->add($otpurchaseinfo);
            } else {
                $infoId = M("otpurchaseinfo", "is_")->add($otpurchaseinfo);
            }
            if (!infoId) {
                M()->rollback();
                return $apiRet;
            }
            //is_roominfo
            $roomInfo = [];
            $roomInfo["pid"] = $roomId;
            $roomInfo["type"] = 7;
            $roomInfo["class"] = 0;
            $roomInfo["info"] = !empty($infoId) ? $infoId : 0;
            $roomInfo["nums"] = $otpurchaseinfo["nums"];
            if ($type == 1) {
                $roomInfo["type"] = 8;
            }
            $roomInfoId = M("roominfo", "is_")->add($roomInfo);
            if (!$roomInfoId) {
                M()->rollback();
                return $apiRet;
            }
            M()->commit();
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
    }
    return $apiRet;
}

/**
 * 支付宝退款
 * $order_id 订单号
 * $refund_amount 退款金额
 * $out_request_no 退款批次号
 * $is_pos 是否 POS 收银 ,0:否 1：是
 */
function alipayRefund($order_id, $refund_amount, $out_request_no = '', $is_pos = 1)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    $apiRet['apiData'] = array();

    header("Content-type: text/html; charset=utf-8");
    Vendor('Alipay.dangmianfu.f2fpay.model.builder.AlipayTradeRefundContentBuilder');
    Vendor('Alipay.dangmianfu.f2fpay.service.AlipayTradeService');

    $wx_payments = M('payments')->where(array('payCode' => 'alipay', 'enabled' => 1))->find();
    if (empty($wx_payments['payConfig'])) {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '参数不全';
        $apiRet['apiState'] = 'error';
        return $apiRet;
    }
    $config = json_decode($wx_payments['payConfig'], true);

    if (!empty($is_pos)) {//POS收银订单退款
        $orders_info = M('pos_orders')->where(array('id' => $order_id))->find();
    } else {//正常订单退款
        $orders_info = M('orders')->where(array('orderId' => $order_id))->find();
    }
    $out_trade_no = empty($is_pos) ? $orders_info['orderunique'] : $orders_info['outTradeNo'];

    if (!empty($out_trade_no) && trim($out_trade_no) != "") {

        if (empty($out_request_no) && trim($out_request_no) == '') $out_request_no = createOutRequestNo();

        //第三方应用授权令牌,商户授权系统商开发模式下使用
        $appAuthToken = "";//根据真实值填写

        //创建退款请求builder,设置参数
        $refundRequestBuilder = new \AlipayTradeRefundContentBuilder();
        $refundRequestBuilder->setOutTradeNo($out_trade_no);
        $refundRequestBuilder->setRefundAmount($refund_amount);
        $refundRequestBuilder->setOutRequestNo($out_request_no);

        $refundRequestBuilder->setAppAuthToken($appAuthToken);

        //初始化类对象,调用refund获取退款应答
        $refundResponse = new \AlipayTradeService($config);
        $refundResult = $refundResponse->refund($refundRequestBuilder);

        //根据交易状态进行处理
        switch ($refundResult->getTradeStatus()) {
            case "SUCCESS":

                //写入订单日志
//                $log_orders = M("log_orders");
//                $data["orderId"] = $order_id;
//                $data["logContent"] = "发起支付宝退款：" . $refund_amount . ' 元';
//                $data["logUserId"] = $orders_info['userId'];
//                $data["logType"] = "0";
//                $data["logTime"] = date("Y-m-d H:i:s");
//                $log_orders->add($data);
                $content = "发起支付宝退款：" . $refund_amount . ' 元';
                $logParams = [
                    'orderId' => $order_id,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => $orders_info['orderStatus'],
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
                /*
                                //添加退款记录 退款记录应由微信服务器通知修改 目前使用本地方式---------------------
                                $add_data['orderId'] = $pos_orders_info['id'];//订单id
                                $add_data['tradeNo'] = $out_trade_no;//流水号
                                $add_data['goodsId'] = '';//商品id
                                $add_data['money'] = $refund_amount;
                                $add_data['addTime'] = date('Y-m-d H:i:s');
                                $add_data['payType'] = 2;
                                $add_data['userId'] = $pos_orders_info['userId'];
                                M('order_complainsrecord')->add($add_data);
                */
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '支付宝退款成功';
                $apiRet['apiState'] = 'success';
                break;
            case "FAILED":
                $apiRet['apiInfo'] = '支付宝退款失败';
                break;
            case "UNKNOWN":
                $apiRet['apiInfo'] = '系统异常，订单状态未知!!!';
                break;
            default:
                $apiRet['apiInfo'] = '不支持的交易状态，交易返回异常!!!';
                break;
        }
    }
    return $apiRet;
}

/**
 * 生成支付宝批次号
 */
function createOutRequestNo()
{
    return time() . mt_rand(100000, 999999);
}

/**
 * 微信退款
 * 主要用于 POS 收银订单
 * @param $order_id 订单id
 * @param $refund_money 退款金额
 * @return bool
 */
function wxRefundForDangMianFu($order_id, $refund_money)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    $apiRet['apiData'] = '';

    if (empty($order_id) || empty($refund_money)) {
        $apiRet['apiInfo'] = '参数不全';
        return $apiRet;
    }

    vendor('WxPay.lib.WxPayApi');
    vendor('WxPay.lib.WxPayConfig');
    vendor('WxPay.lib.log');

    try {

        $pos_order_info = M('pos_orders')->where(array('id' => $order_id))->find();

        $input = new \WxPayRefund();
        // $input->SetTransaction_id($pos_order_info['outTradeNo']);
        $input->SetOut_trade_no($pos_order_info['outTradeNo']);
        $input->SetTotal_fee($pos_order_info['realpayment'] * 100);
        $input->SetRefund_fee($refund_money * 100);

        /*
        $config = new WxPayConfig();
        $input->SetOut_refund_no("sdkphp".date("YmdHis"));
        $input->SetOp_user_id($config->GetMerchantId());
        $resdata = WxPayApi::refund($config, $input);
        */

        $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
        if (empty($wx_payments['payConfig'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数不全';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $wx_config = json_decode($wx_payments['payConfig'], true);
        $wx_config['sslCertPath'] = getTmpPathByContent($wx_config['sslCertContent']);
        $wx_config['sslKeyPath'] = getTmpPathByContent($wx_config['sslKeyContent']);

        $input->SetOut_refund_no("sdkphp" . date("YmdHis"));
        $input->SetOp_user_id($wx_config['mchId']);
        $resdata = \WxPayApi::refund($wx_config, $input);
        unlink($wx_config['sslCertPath']);
        unlink($wx_config['sslKeyPath']);

        if ($resdata['return_code'] == 'SUCCESS' && $resdata['result_code'] == 'SUCCESS') {
            //写入订单日志
//            $log_orders = M("log_orders");
//            $data["orderId"] = $pos_order_info['id'];
//            $data["logContent"] = "发起微信退款：" . $refund_money / 100 . '元';
//            $data["logUserId"] = $pos_order_info['userId'];
//            $data["logType"] = "0";
//            $data["logTime"] = date("Y-m-d H:i:s");
//            $log_orders->add($data);
            $content = "发起微信退款：" . $refund_money / 100 . '元';
            $logParams = [
                'orderId' => $pos_order_info['id'],
                'logContent' => $content,
                'logUserId' => 0,
                'logUserName' => '系统',
                'orderStatus' => 3,
                'payStatus' => 1,
                'logType' => 2,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);

            /*
                        //添加退款记录 退款记录应由微信服务器通知修改 目前使用本地方式---------------------
                        $add_data['orderId'] = $orderId;//订单id
                        $add_data['tradeNo'] = $transaction_id;//流水号
                        $add_data['goodsId'] = $goodsId;//商品id
                        $add_data['money'] = $refund_fee/100;//单位转为元
                        $add_data['addTime'] = date('Y-m-d H:i:s');
                        $add_data['payType'] = 1;
                        $add_data['userId'] = $userId;
                        M('order_complainsrecord')->add($add_data)
            */
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '微信退款成功';
            $apiRet['apiState'] = 'success';
        } else {
            $apiRet['apiInfo'] = '微信退款失败';//退款失败
        }

    } catch (Exception $e) {
        $apiRet['apiInfo'] = '微信退款失败';//退款失败
        //Log::ERROR(json_encode($e));
    }

    return $apiRet;
}

/**
 * 获取七牛云上传图片的命名
 */
function getQiniuImgName($str = '')
{
    $id = M('qiniu_id')->add(array('name' => '123'));
    $microtime = getMicroSecondsTimestamp();
    $time = uniqid() . $id . $microtime;
    if (!empty($str)) {
        $time .= md5($str);
    }
    return $time;
}

/**
 * 获取微秒时间戳
 * @return string
 */
function getMicroSecondsTimestamp()
{
    $time = microtime();
    return substr($time, 11, 10) . str_pad(substr($time, 0, 8) * 1000000, 6, "0", STR_PAD_LEFT);
}

/**
 * 是否可以购买新人专享商品
 */
function isBuyNewPeopleGoods($goodsId, $userId)
{
    $goods_info = M('goods')->where(array('goodsId' => $goodsId))->find();
    if (empty($goods_info)) return false;
    //新人专享商品
    if ($goods_info['isNewPeople'] == 1) {
        $order_info = M('orders')->where(array('userId' => $userId, 'orderFlag' => 1))->find();
        if (!empty($order_info)) return false;
    }
    return true;
}

/**
 * 计算两个时间戳之间相差的日时分秒
 * @param $unixTime_1 开始时间戳
 * @param $unixTime_2 结束时间戳
 * @return array
 */

function timeDiff($unixTime_1, $unixTime_2)
{
    $timediff = abs($unixTime_2 - $unixTime_1);
    //计算天数
    $days = intval($timediff / 86400);
    //计算小时数
    $remain = $timediff % 86400;
    $hours = intval($remain / 3600);
    //计算分钟数
    $remain = $remain % 3600;
    $mins = intval($remain / 60);
    //计算秒数
    $secs = $remain % 60;
    return ['day' => $days, 'hour' => $hours, 'min' => $mins, 'sec' => $secs];
}

/*
 * 记录分拣任务操作日志
 * */
function insertSortingActLog($param)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    if (!empty($param)) {
        $log['sortingId'] = $param['sortingId'];
        $log['content'] = $param['content'];
        $log['addTime'] = date("Y-m-d H:i:s", time());
        $insrtRes = M('sorting_action_log')->add($log);
        if ($insrtRes) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
    }
    return $apiRet;
}

/**
 * 如果用户是第一次购买会员,返给邀请人优惠券
 * @param int $userId
 */
function invitationFriendSetmeal($userId)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    $userInfo = M('users')->where(['userId' => $userId])->field('userId,userPhone')->find();
    if ($userInfo) {
        $setmealInvitation = M('setmeal_invitation')->where(['userPhone' => $userInfo['userPhone']])->order("id desc")->find(); //查找邀请人
        if (!$setmealInvitation) {
            $apiRet['apiInfo'] = '没有相关邀请人';
            return $apiRet;
        }
        $invitationId = $setmealInvitation['userId'];
        $count = M('buy_user_record')->limit(1)->where("burFlag=1 and userId='" . $userId . "'")->count();
        if ($count != 1) {
            $apiRet['apiInfo'] = '该会员非第一次购买套餐';
            return $apiRet;
        }
        //是否存在待恢复使用的优惠券
        $mod_coupons_users = M('coupons_users');
        $coupons_save['dataFlag'] = 1;
        $where = " userId='" . $invitationId . "' and userToId='" . $userId . "' and dataFlag = -1 ";
        $editRes = $mod_coupons_users->where($where)->save($coupons_save);
        if ($editRes) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
    }
    return $apiRet;
}

/*
 * 获取符合条件的店铺信息
 * @param string lat
 * @param string lng
 * @param int societyId
 * */
function getSocietyShop($lat, $lng, $societyId)
{
    $res['shopIdArr'] = [];
    $res['shopList'] = [];

    $where = " WHERE isDelete=0 AND status=1 ";
    if ($societyId > 0) {
        //获取某一个社区相关的店铺
        $where .= " AND id='" . $societyId . "'";
    }
    //获取多个社区相关的店铺
    $sql = "SELECT `id`,`name`,`address`,`groupName`,`mobile`,`wxNumber`,`lat`,`lng`,`communitysId`,`userId`,`money`,`addTime`,`areaId1`,`areaId2`,`areaId3`,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-lat*PI()/180)/2),2)+COS($lat*PI()/180)*COS(lat*PI()/180)*POW(SIN(($lng*PI()/180-lng*PI()/180)/2),2)))*1000) / 1000 AS distance FROM " . __PREFIX__society . $where . " ORDER BY distance ASC ";
    $list = M('')->query($sql);
    if ($list) {
        $communitysId = [];
        foreach ($list as $val) {
            $communitysId[] = $val['communitysId'];
        }
        $communitysId = array_unique($communitysId);
        //店铺条件
        $newTime = date('H') . '.' . date('i');//获取当前时间
        $shopWhere["wst_shops.shopStatus"] = 1;
        $shopWhere["wst_shops.shopFlag"] = 1;
        //$shopWhere["wst_shops.areaId3"] = $areaId3;//不该限制店铺地区
        $shopWhere["wst_shops.shopAtive"] = 1;
        $shopWhere["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        $shopWhere["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

        $shopWhere['wst_shops_communitys.communityId'] = ['IN', $communitysId];
        $Model = M('shops');
        $shopList = $Model
            ->join('LEFT JOIN wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
            ->where($shopWhere)
            ->field('wst_shops.*')
            ->group('wst_shops.shopId')
            ->select();
        $shopIdArr = [];
        foreach ($shopList as $val) {
            $shopIdArr[] = $val['shopId'];
        }
        $res['shopList'] = $shopList;
        $res['shopIdArr'] = $shopIdArr;
    }
    return $res;
}

/*
 * 限制商品下单次数
 * @param int goodsId PS:商品id
 * @param int userId PS:会员id
 * */
function checkGoodsOrderNum($goodsId, $userId)
{
    if ($goodsId > 0 && $userId > 0) {
        $goodsInfo = M('goods')->where(['goodsId' => $goodsId])->field("goodsId,orderNumLimit,goodsName,isShopSecKill")->find();
        if ($goodsInfo['orderNumLimit'] != -1 && $goodsInfo['isShopSecKill'] != 1) {
            $goodsOrderNumLimit = $goodsInfo['orderNumLimit'];
            if ($goodsOrderNumLimit == 0) {
                M()->rollback();
                unset($apiRet);
                $apiRet['apiCode'] = '000821';
                $apiRet['apiInfo'] = '限制下单次数为' . $goodsOrderNumLimit;
                $apiRet['goodsId'] = $goodsInfo['goodsId'];
                $apiRet['goodsName'] = $goodsInfo['goodsName'];
                $apiRet['apiState'] = 'error';
                return $apiRet;
            }
            $goWhere = [];
            $goWhere['userId'] = $userId;
            $goWhere['goodsId'] = $goodsId;
            $logTab = M("goods_ordernum_limit");
            $goodsOrdernumLimitInfo = $logTab->where($goWhere)->find();
            if ($goodsOrdernumLimitInfo) {
                $logTab->where(['id' => $goodsOrdernumLimitInfo['id']])->save(['orderNum' => $goodsInfo['orderNumLimit']]);//如果存在记录,需要更新orderNumLimit,避免后台修改orderNumLimit的值
                $goWhere = [];
                $goWhere['userId'] = $userId;
                $goWhere['goodsId'] = $goodsId;
                $goodsOrdernumLimitInfo = $logTab->where($goWhere)->find();
                if ($goodsOrdernumLimitInfo['buyNum'] >= $goodsOrdernumLimitInfo['orderNum']) {
                    $diffNum = $goodsOrdernumLimitInfo['orderNum'] - $goodsOrdernumLimitInfo['buyNum'];
                    if ($diffNum <= 0) {
                        $diffNum = 0;
                    }
                    M()->rollback();
                    unset($apiRet);
                    $apiRet['apiCode'] = '000821';
                    $apiRet['apiInfo'] = '限制下单次数为' . $goodsOrderNumLimit . ",剩余下单次数为" . $diffNum;
                    $apiRet['goodsId'] = $goodsInfo['goodsId'];
                    $apiRet['goodsName'] = $goodsInfo['goodsName'];
                    $apiRet['apiState'] = 'error';
                    return $apiRet;
                }
            }
        }
    }
}

/*
 * 限制商品下单次数
 * @param int goodsId PS:商品id
 * @param int userId PS:会员id
 * */
function limitGoodsOrderNum($goodsId, $userId)
{
    if ($goodsId > 0 && $userId > 0) {
        $goodsInfo = M("goods")->where(['goodsId' => $goodsId])->find();
        //记录到商品限制下单次数记录表 wst_goods_ordernum_limit
        if ($goodsInfo["orderNumLimit"] > 0 && $goodsInfo['isShopSecKill'] != 1) {
            $logTab = M("goods_ordernum_limit");
            $goWhere = [];
            $goWhere['userId'] = $userId;
            $goWhere['goodsId'] = $goodsId;
            $goodsOrdernumLimitInfo = $logTab->where($goWhere)->find();
            if (!$goodsOrdernumLimitInfo) {
                //insert
                $limitLog = [];
                $limitLog['userId'] = $userId;
                $limitLog['goodsId'] = $goodsId;
                $limitLog['buyNum'] = 1;
                $limitLog['orderNum'] = $goodsInfo["orderNumLimit"];
                $limitLog['state'] = -1;
                $limitLog['logTime'] = date("Y-m-d H:i:s", time());
                $insertId = $logTab->add($limitLog);
            } else {
                //edit
                $logEdit = [];
                $logEdit['buyNum'] = $goodsOrdernumLimitInfo['buyNum'] + 1;
                $logEdit['logTime'] = date("Y-m-d H:i:s", time());
                $logEdit['state'] = -1;
                $logTab->where(['id' => $goodsOrdernumLimitInfo['id']])->save($logEdit);
            }
        }
    }
}

/*
 * 更新商品下单次数记录表
 * @param int orderId PS:订单id
 * */
function updateGoodsOrderNumLimit($orderId)
{
    if ($orderId > 0) {
        $orderInfo = M("orders")->where(['orderId' => $orderId])->field("orderId,userId")->find();
        $userId = $orderInfo['userId'];
        $orderGoods = M("order_goods")->where(["orderId" => $orderId])->select();
        $goodsTab = M('goods');
        $logTab = M("goods_ordernum_limit");
        foreach ($orderGoods as $val) {
            $goodsInfo = $goodsTab->where(['goodsId' => $val['goodsId']])->field("goodsId,orderNumLimit,isShopSecKill")->find();
            //商品为限制下单次数商品
            if ($goodsInfo && ($goodsInfo['orderNumLimit'] > -1) && $goodsInfo['isShopSecKill'] != 1) {
                $logWhere = [];
                $logWhere['userId'] = $userId;
                $logWhere['goodsId'] = $val['goodsId'];
                //$logWhere['state'] = -1;
                $logInfo = $logTab->where($logWhere)->find();
                if ($logInfo) {
                    $logEdit = [];
                    $logEdit['buyNum'] = $logInfo['buyNum'] - 1;
                    if ($logEdit['buyNum'] < 0) {
                        $logEdit['buyNum'] = 0;
                    }
                    $logEdit['orderNum'] = $goodsInfo['orderNumLimit'];
                    $logEdit['logTime'] = date("Y-m-d H:i:s", time());
                    //$logEdit['state'] = 0;
                    $logTab->where(['id' => $logInfo['id']])->save($logEdit);
                }
            }
        }
    }
}

/*
 * 检查商品限时状况,是否能正常购买
 * @param int goodsId PS:商品id
 * */
//function checkGoodsFlashSale($goodsId){
//    if(!empty($goodsId)){
//        $goodsInfo = M("goods")->where(['goodsId'=>$goodsId])->field("goodsId,isFlashSale,goodsName,isShopPreSale")->find();
//        //如果是店铺预售商品,就不验证限时了
//        if($goodsInfo['isShopPreSale'] == 1){
//            $apiRet['apiCode'] = '0';
//            $apiRet['apiInfo'] = '通过验证';
//            $apiRet['goodsId'] = $goodsInfo['goodsId'];
//            $apiRet['goodsName'] = $goodsInfo['goodsName'];
//            $apiRet['apiState'] = 'success';
//            return $apiRet;
//        }
//        if($goodsInfo['isFlashSale'] == 1){
//            $apiRet['apiCode'] = '000822';
//            // $apiRet['apiInfo'] = '未通过验证';
//            $apiRet['apiInfo'] = '不在限购时间段范围';
//            $apiRet['goodsId'] = $goodsInfo['goodsId'];
//            $apiRet['goodsName'] = $goodsInfo['goodsName'];
//            $apiRet['apiState'] = 'error';
//            $flashSaleGoods = M("flash_sale_goods fs")
//                ->join("left join wst_flash_sale f on f.id=fs.flashSaleId")
//                ->where(["fs.goodsId"=>$goodsId,"fs.isDelete"=>0,"f.isDelete"=>0,'f.state'=>1])
//                ->field("f.id,f.startTime,f.endTime")
//                ->select();
//            $toDay = date("Y-m-d ",time());
//            $toDayTime = date("Y-m-d H:i:s",time());
//            $flashSaleStatus = true;//限时商品是否可以购买(true:是|false:否)
//            foreach ($flashSaleGoods as $val){
//                $startTime = $toDay.$val['startTime'];
//                $endTime = $toDay.$val['endTime'];
//                // --- 原来的 --- start ---
//                // if(($startTime > $toDayTime) || ($endTime <= $toDayTime)){
//                // $apiRet['apiInfo'] = '不在限购时间段范围';
//                // return $apiRet;
//                // }
//                // --- 原来的 --- end ---
//
//                // --- 修改后的 --- @author liusijia --- start ---
//                if(($startTime <= $toDayTime) && ($endTime >= $toDayTime)){
//                    $apiRet['apiCode'] = '0';
//                    $apiRet['apiInfo'] = '通过验证';
//                    $apiRet['goodsId'] = $goodsInfo['goodsId'];
//                    $apiRet['goodsName'] = $goodsInfo['goodsName'];
//                    $apiRet['apiState'] = 'success';
//                    return $apiRet;
//                }
//                // --- 修改后的 --- @author liusijia --- end ---
//
//                //如果还未到开始时间
//                if($startTime > $toDayTime){
//                    //今天
//                    $diffDateInfo = timeDiff(strtotime($toDayTime),strtotime($startTime));
//                    $apiRet['apiInfo'] = '距离活动开始，还剩下'.$diffDateInfo['day'].'天'.$diffDateInfo['hour'].'小时'.$diffDateInfo['min'].'分钟'.$diffDateInfo['sec'].'秒';
//                }elseif($endTime < $toDayTime){
//                    //明天
//                    $nextDay = date('Y-m-d',strtotime('1 day')).' '.$val['startTime'];
//                    $diffDateInfo = timeDiff(strtotime($toDayTime),strtotime($nextDay));
//                    $apiRet['apiInfo'] = '距离活动开始，还剩下'.$diffDateInfo['day'].'天'.$diffDateInfo['hour'].'小时'.$diffDateInfo['min'].'分钟'.$diffDateInfo['sec'].'秒';
//                }
//                return $apiRet;
//            }
//        }
//    }
//}


function checkGoodsFlashSale($goodsId)
{
    //为了兼容之前的代码,返回值就继续沿用之前的
    if (!empty($goodsId)) {
        $goodsInfo = M("goods")->where(['goodsId' => $goodsId])->field("goodsId,isFlashSale,goodsName,isShopPreSale")->find();
        //如果是店铺预售商品,就不验证限时了
        if ($goodsInfo['isShopPreSale'] == 1) {
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '通过验证';
            $apiRet['goodsId'] = $goodsInfo['goodsId'];
            $apiRet['goodsName'] = $goodsInfo['goodsName'];
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        if ($goodsInfo['isFlashSale'] == 1) {
            $flashSaleGoods = M("flash_sale_goods fs")
                ->join("left join wst_flash_sale f on f.id=fs.flashSaleId")
                ->where(["fs.goodsId" => $goodsId, "fs.isDelete" => 0, "f.isDelete" => 0, 'f.state' => 1])
                ->field("f.id,f.startTime,f.endTime")
                ->select();
            $toDay = date("Y-m-d ", time());
            $toDayTime = date("Y-m-d H:i:s", time());
            $checkNum = 0;//符合限时的个数
            foreach ($flashSaleGoods as $key => $val) {
                if ($val['endTime'] == '00:00' && $val['startTime'] == '00:00') {
                    $checkNum += 1;
                    continue;
                }
                if ($val['endTime'] == '00:00') {
                    $checkNum += 1;
                    continue;
                }
                $startTime = $toDay . $val['startTime'];
                $endTime = $toDay . $val['endTime'];
                if (($startTime <= $toDayTime) && ($endTime >= $toDayTime)) {
                    $checkNum += 1;
                }
            }
            if ($checkNum > 0) {
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = '通过验证';
                $apiRet['goodsId'] = $goodsInfo['goodsId'];
                $apiRet['goodsName'] = $goodsInfo['goodsName'];
                $apiRet['apiState'] = 'success';
                return $apiRet;
            } else {
                $apiRet['apiCode'] = '000822';
                $apiRet['apiInfo'] = '不在限购时间段范围';
                $apiRet['goodsId'] = $goodsInfo['goodsId'];
                $apiRet['goodsName'] = $goodsInfo['goodsName'];
                $apiRet['apiState'] = 'error';
                foreach ($flashSaleGoods as $val) {
                    $startTime = $toDay . $val['startTime'];
                    $endTime = $toDay . $val['endTime'];
                    //如果还未到开始时间
                    if ($startTime > $toDayTime) {
                        //今天
                        $diffDateInfo = timeDiff(strtotime($toDayTime), strtotime($startTime));
                        $apiRet['apiInfo'] = '距离活动开始，还剩下' . $diffDateInfo['day'] . '天' . $diffDateInfo['hour'] . '小时' . $diffDateInfo['min'] . '分钟' . $diffDateInfo['sec'] . '秒';
                    } elseif ($endTime < $toDayTime) {
                        //明天
                        $nextDay = date('Y-m-d', strtotime('1 day')) . ' ' . $val['startTime'];
                        $diffDateInfo = timeDiff(strtotime($toDayTime), strtotime($nextDay));
                        $apiRet['apiInfo'] = '距离活动开始，还剩下' . $diffDateInfo['day'] . '天' . $diffDateInfo['hour'] . '小时' . $diffDateInfo['min'] . '分钟' . $diffDateInfo['sec'] . '秒';
                    }
                    return $apiRet;
                }
            }
        }
    }
}

/**
 * @param $goodsId
 * @param int $code
 * @return mixed
 * 获取当前时间限时商品信息
 */
function getGoodsFlashSale($goodsId, $code = 0)
{
    $goodsInfo = M('goods')
        ->where(['goodsId' => $goodsId])
        ->field('goodsId,isFlashSale,goodsName')
        ->find();
    if ($goodsInfo['isFlashSale'] != 1) {
        return returnData();
    }
    $where = [];
    //限时商品条件
    $where['gts.goodsId'] = $goodsId;
    $where['gts.dataFlag'] = 1;
    //限时时间段条件
    $where['wfs.state'] = 1;
    $where['wfs.isDelete'] = 0;
    $flashSaleGoods = M("goods_time_snapped gts")
        ->join("left join wst_flash_sale wfs on wfs.id = gts.flashSaleId")
        ->where($where)
        ->field("gts.*,wfs.id,wfs.startTime,wfs.endTime")
        ->group('gts.tsId')
        ->order('wfs.startTime desc')
        ->select();
    $toDay = date("Y-m-d ", time());
    $toDayTime = date("Y-m-d H:i", time());
    $checkNum = 0;//符合限时的个数
    foreach ($flashSaleGoods as $key => $val) {
        $val['minBuyNum'] = (float)$val['minBuyNum'];
        $flashSaleGoods[$key]['minBuyNum'] = $val['minBuyNum'];
        if ($val['endTime'] == '00:00' && $val['startTime'] == '00:00') {
            $val['code'] = 1;
            return $val;
        }
        if ($val['endTime'] == '00:00') {
            $val['code'] = 1;
            return $val;
        }
        $startTime = $toDay . $val['startTime'];
        $endTime = $toDay . $val['endTime'];
        if (($startTime <= $toDayTime) && ($endTime >= $toDayTime)) {
//            if($val['activeInventory'] < 0){
//                return returnData(false, -1, 'error', '当前活动商品库存不足');
//            }
            $val['code'] = 1;
            return $val;
        }
        if ($code == 1) {
            $checkNum += 1;
        }
    }
    if ($code == 1) {
        if ($checkNum > 0) {
            $flashSaleGoods[0]['code'] = -1;
            return $flashSaleGoods[0];
        }
    }
    return returnData(null, -1, 'error', '不在限购时间段范围');
}

/**
 * @param $goodsId
 * @return mixed
 * 判断限时商品详情
 */
function getGoodsFlashSaleDetails($goodsId)
{
    $where = [];
    //限时商品条件
    $where['gts.goodsId'] = $goodsId;
    $where['gts.dataFlag'] = 1;
    //限时时间段条件
    $where['wfs.state'] = 1;
    $where['wfs.isDelete'] = 0;
    $flashSaleGoods = M("goods_time_snapped gts")
        ->join("left join wst_flash_sale wfs on wfs.id = gts.flashSaleId")
        ->where($where)
        ->field("gts.*,wfs.id,wfs.startTime,wfs.endTime")
        ->group('gts.tsId')
        ->order('wfs.startTime desc')
        ->select();
    $toDay = date("Y-m-d ", time());
    $toDayTime = date("Y-m-d H:i", time());
    $checkNum = 0;//符合限时的个数
    $res = [];//商品详情
    $goodsDetail = [];
    foreach ($flashSaleGoods as $key => $val) {
        if ($val['endTime'] == '00:00' && $val['startTime'] == '00:00') {
            $checkNum += 1;
            continue;
        }
        if ($val['endTime'] == '00:00') {
            $checkNum += 1;
            continue;
        }
        $startTime = $toDay . $val['startTime'];
        $endTime = $toDay . $val['endTime'];
        if (($startTime <= $toDayTime) && ($endTime >= $toDayTime)) {
            $goodsDetail = $val;
        }
        if (($startTime <= $toDayTime) && ($endTime >= $toDayTime) || $startTime >= $toDayTime) {
            $checkNum += 1;
        }
    }
    if ($checkNum > 0 || !empty($goodsDetail)) {
        //限时商品----详情需要
        $res['goodsDetail'] = $goodsDetail;
        $res['list'] = $flashSaleGoods;
        return $res;
    }
}

/**
 * @param int $goodsId
 * @param array $goodsTimeSnapped
 * @param float $goodsCnt
 * @return mixed
 * 判断限时购库存----不够时直接还原原始数据
 */
function goodsTimeLimit($goodsId, $goodsTimeSnapped = array(), $goodsCnt, $debug = 2)
{
    $goods_module = new \App\Modules\Goods\GoodsModule();
    $goodsInfo = $goods_module->getGoodsInfoById($goodsId, '*', 2);
    $prefix = '';
    if ($goodsInfo['isFlashSale'] == 1) {//限时商品库存
        $goodsStock = $goodsTimeSnapped['activeInventory'];
        $prefix = '限时';
    }
    if ($goodsInfo['isLimitBuy'] == 1) {//限量商品
        $goodsStock = $goodsInfo['limitCount'];
        //验证每日限购量-start
        $goods_service_module = new GoodsServiceModule();
        $verification_result = $goods_service_module->verificationLimitGoodsBuyLog($goodsId, $goodsCnt, $debug);
        if ($verification_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, -1, 'error', $verification_result['msg']);
        }
        //验证每日限购量-end
        $prefix = '限量';
    }
    if ($goodsTimeSnapped['minBuyNum'] > 0 && $goodsCnt < $goodsTimeSnapped['minBuyNum']) {
        return returnData(false, -1, 'error', $prefix . "商品【{$goodsInfo['goodsName']}】最小起购量为{$goodsTimeSnapped['minBuyNum']}");
    }
    $goodsStock = (float)$goodsStock;
    if ($goodsInfo['SuppPriceDiff'] == 1) {
        //称重商品
        $goodsCntStock = gChangeKg($goodsId, $goodsCnt, 1);
        if ((float)$goodsCntStock > $goodsStock) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $prefix . "商品【{$goodsInfo['goodsName']}】活动库存不足，最多可购买{$goodsStock}{$goodsInfo['unit']}");
        }
    } else {
//        $submit_order_model = new \V3\Model\SubmitOrderModel();
//        $handle_result = $submit_order_model->handleGoodsCntToStock($goodsId, 0, $goodsCnt, $goodsStock);
//        //标品
//        if ($handle_result['goods_cnt'] > $handle_result['can_buy_num']) {
//            return returnData(false, -1, 'error', $prefix . "商品【{$goodsInfo['goodsName']}】活动库存数量不足");
//        }
        if ($goodsCnt > $goodsStock) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $prefix . "商品【{$goodsInfo['goodsName']}】活动库存不足，最多可购买{$goodsStock}{$goodsInfo['unit']}");
        }
    }
    //返回验证通过的结果,以前也没有,为了不影响其他地方,就不加了
}

/**
 * 金额转积分
 * @param $money
 * @return float|int
 */
function moneyToIntegral($money)
{
    $integral = 0;
    //开启积分支付
    if ($GLOBALS['CONFIG']['isOpenScorePay'] == 1) {
        //积分与金钱兑换比例
        if (!empty($GLOBALS['CONFIG']['scoreCashRatio'])) {
            $scoreCashRatio = $GLOBALS['CONFIG']['scoreCashRatio'];
            $scoreCashRatio_arr = explode(':', $scoreCashRatio);
            $integral = ceil($money * $scoreCashRatio_arr[0]);
        }
    }
    return $integral;
}

/**
 * 积分转金额
 * @param $money
 * @return float|int
 */
function integralToMoney($integral)
{
    $money = 0;
    //开启积分支付
    if ($GLOBALS['CONFIG']['isOpenScorePay'] == 1) {
        //积分与金钱兑换比例
        if (!empty($GLOBALS['CONFIG']['scoreCashRatio'])) {
            $scoreCashRatio = $GLOBALS['CONFIG']['scoreCashRatio'];
            $scoreCashRatio_arr = explode(':', $scoreCashRatio);
            $money = $integral / $scoreCashRatio_arr[0];
        }
    }
    $money = number_format($money, 2);
    return $money;
}

/**
 * 积分平摊分配
 * @param $total
 * @param $rate
 * @param $sum
 * @return string
 */
function integralAssignment($total, $rate, $sum)
{
    return number_format($total * ($rate / $sum));
}

/**
 * 得到会员历史总消费积分
 * @param $memberId
 * @return mixed
 */
function historyConsumeIntegral($memberId)
{
    return M('pos_orders')->where(array('memberId' => $memberId))->sum('setintegral');
}

///*
// * 获取商品的sku
// * @param array $data
// * $skuBarcode 具体sku编码
// * */
//function getGoodsSku($data, $skuBarcode = '')
//{
//    if (!empty($data)) {
//        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//        $goodsTab = M('goods');
//        $goodsField = [];
//        if (!empty($replaceSkuField)) {
//            foreach ($replaceSkuField as $fv) {
//                $goodsField[] = $fv;
//            }
//        }
//        if (array_keys($data) !== range(0, count($data) - 1)) {
//            //一维
//            $goodsId = $data['goodsId'];
//            if ($goodsId > 0) {
//                //$goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->field($goodsField)->find();
//                $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->find();
//                $goodsSku = [];
//                $goodsSku['skuSpec'] = [];//PS:多规格,去重后,前端直接展示
//                $goodsSku['skuList'] = [];//PS:商品所有sku,选择规格属性后到该列表中获取对应的sku
//                $systemTab = M('sku_goods_system');
//                //$selfTab = M('sku_goods_self');
//                $sysWhere = [];
//                $sysWhere['dataFlag'] = 1;
//                $sysWhere['goodsId'] = $goodsId;
//                $systemSpec = $systemTab->where($sysWhere)->order('skuId asc')->select();
//                if ($skuBarcode) {
//                    $sysWhere['skuBarcode'] = $skuBarcode;
//                    $systemSpec = $systemTab->where($sysWhere)->limit(1)->order('skuId asc')->select();
//                }
//                $data['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
//                if ($systemSpec) {
//                    $data['hasGoodsSku'] = 1;//是否有商品sku(0=>无 | 1=>有)
//                    //skuList
//                    foreach ($systemSpec as $value) {
//                        $spec = [];
//                        $spec['skuId'] = $value['skuId'];
//                        foreach ($replaceSkuField as $rek => $rev) {
//                            $spec['systemSpec']['selling_stock'] = $value['selling_stock'];
//                            $spec['systemSpec']['weigetG'] = formatAmount($value['weigetG'], 1);
//                            if (isset($value[$rek]) && $rek != 'weigetG') {
//                                $spec['systemSpec'][$rek] = $value[$rek];
//                            }
//                            if (in_array($rek, ['dataFlag', 'addTime'])) {
//                                continue;
//                            }
//                            if ((int)$spec['systemSpec'][$rek] == -1) {
//                                //如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                                $spec['systemSpec'][$rek] = $goodsInfo[$rev];
//                            }
//                            if (is_null($spec['systemSpec'][$rek])) {
//                                $spec['systemSpec'][$rek] = '';
//                            }
//
//                        }
//                        $spec['selfSpec'] = M("sku_goods_self se")
//                            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                            ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
//                            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                            ->order('sp.sort asc')
//                            ->select();
//                        if (empty($spec['selfSpec'])) {
//                            continue;
//                        }
//                        $selfSpecAttr = [];
//                        foreach ($spec['selfSpec'] as $selfVal) {
//                            $selfSpecAttr[] = $selfVal['attrName'];
//                        }
//                        $spec['selfSpecStr'] = implode('，', $selfSpecAttr);
//                        $goodsSku['skuList'][] = $spec;
//                    }
//                    if (empty($goodsSku['skuList'])) {
//                        $data['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
//                    }
//                    //skuSpec
//                    $skuSpec = [];
//                    $skuSpecAttr = [];
//                    //$replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//                    foreach ($goodsSku['skuList'] as $value) {
//                        foreach ($value['selfSpec'] as $va) {
//                            $skuSpecAttr[] = $va;
//                            $skuSpecInfo['specId'] = $va['specId'];
//                            $skuSpecInfo['specName'] = $va['specName'];
//                            $skuSpec[] = $skuSpecInfo;
//                        }
//                    }
//                    $skuSpec = arrayUnset($skuSpec, 'specId');
//                    $skuSpecAttr = arrayUnset($skuSpecAttr, 'attrId');
//                    foreach ($skuSpec as $key => &$val) {
//                        foreach ($skuSpecAttr as $v) {
//                            if ($v['specId'] == $val['specId']) {
//                                $attrInfo['skuId'] = $v['skuId'];
//                                $attrInfo['attrId'] = $v['attrId'];
//                                $attrInfo['attrName'] = $v['attrName'];
//                                $val['attrList'][] = $attrInfo;
//                            }
//                        }
//                    }
//                    unset($val);
//                    $goodsSku['skuSpec'] = $skuSpec;
//                }
//                $data['goodsSku'] = $goodsSku;
//            }
//        } else {
//            //二维
//            foreach ($data as $key => $val) {
//                $goodsId = $data[$key]['goodsId'];
//                if ($goodsId > 0) {
//                    //$goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->field($goodsField)->find();
//                    $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->find();
//                    $goodsSku = [];
//                    $goodsSku['skuSpec'] = [];//PS:多规格,去重后,前端直接展示
//                    $goodsSku['skuList'] = [];//PS:商品所有sku,选择规格属性后到该列表中获取对应的sku
//                    $systemTab = M('sku_goods_system');
//                    //$selfTab = M('sku_goods_self');
//                    $sysWhere = [];
//                    $sysWhere ['dataFlag'] = 1;
//                    $sysWhere ['goodsId'] = $goodsId;
//                    $systemSpec = $systemTab->where($sysWhere)->order('skuId asc')->select();
//                    if ($skuBarcode) {
//                        $sysWhere['skuBarcode'] = $skuBarcode;
//                        $systemSpec = $systemTab->where($sysWhere)->limit(1)->order('skuId asc')->select();
//                    }
//                    $data[$key]['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
//                    if ($systemSpec) {
//                        $data[$key]['hasGoodsSku'] = 1;
//                        //skuList
//                        foreach ($systemSpec as $value) {
//                            $spec = [];
//                            $spec['skuId'] = $value['skuId'];
//                            foreach ($replaceSkuField as $rek => $rev) {
//                                $spec['systemSpec']['selling_stock'] = $value['selling_stock'];
//                                $spec['systemSpec']['weigetG'] = formatAmount($value['weigetG'], 1);
//                                if (isset($value[$rek]) && $rek != 'weigetG') {
//                                    $spec['systemSpec'][$rek] = $value[$rek];
//                                }
//                                if (in_array($rek, ['dataFlag', 'addTime'])) {
//                                    continue;
//                                }
//                                if ((int)$spec['systemSpec'][$rek] == -1) {
//                                    //如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                                    $spec['systemSpec'][$rek] = $goodsInfo[$rev];
//                                }
//                                if (is_null($spec['systemSpec'][$rek])) {
//                                    $spec['systemSpec'][$rek] = '';
//                                }
//                            }
//                            $spec['selfSpec'] = M("sku_goods_self se")
//                                ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                                ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                                ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
//                                ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                                ->order('sp.sort asc')
//                                ->select();
//                            if (empty($spec['selfSpec'])) {
//                                continue;
//                            }
//                            $selfSpecAttr = [];
//                            foreach ($spec['selfSpec'] as $selfVal) {
//                                $selfSpecAttr[] = $selfVal['attrName'];
//                            }
//                            $spec['selfSpecStr'] = implode('，', $selfSpecAttr);
//                            $goodsSku['skuList'][] = $spec;
//                        }
//                        if (empty($goodsSku['skuList'])) {
//                            $data[$key]['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
//                        }
//                        //skuSpec
//                        $skuSpec = [];
//                        $skuSpecAttr = [];
//                        foreach ($goodsSku['skuList'] as $value) {
//                            foreach ($value['selfSpec'] as $va) {
//                                $skuSpecAttr[] = $va;
//                                $skuSpecInfo['specId'] = $va['specId'];
//                                $skuSpecInfo['specName'] = $va['specName'];
//                                $skuSpec[] = $skuSpecInfo;
//                            }
//                        }
//                        $skuSpec = arrayUnset($skuSpec, 'specId');
//                        $skuSpecAttr = arrayUnset($skuSpecAttr, 'attrId');
//                        foreach ($skuSpec as $skey => &$sval) {
//                            foreach ($skuSpecAttr as $v) {
//                                if ($v['specId'] == $sval['specId']) {
//                                    $attrInfo['skuId'] = $v['skuId'];
//                                    $attrInfo['attrId'] = $v['attrId'];
//                                    $attrInfo['attrName'] = $v['attrName'];
//                                    $sval['attrList'][] = $attrInfo;
//                                }
//                            }
//                        }
//                        unset($sval);
//                        $goodsSku['skuSpec'] = $skuSpec;
//                    }
//                    $data[$key]['goodsSku'] = $goodsSku;
//                }
//            }
//        }
//    }
//    return $data;
//}

/*
 * 获取商品的sku
 * @param array $data
 * $skuBarcode 具体sku编码
 * */
function getGoodsSku($data, $skuBarcode = '')
{
    //注:上面注释的方法是原来的
    //太多了,主逻辑就不重写了,只是简化一下
    if (empty($data)) {
        return $data;
    }
    $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
    $goodsField = array();
    if (!empty($replaceSkuField)) {
        foreach ($replaceSkuField as $fv) {
            $goodsField[] = $fv;
        }
    }
    $needHandleData = $data;
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $needHandleData = array($data);
    }
    $goodsModule = new \App\Modules\Goods\GoodsModule();
    $goodsIdArr = [];
    foreach ($needHandleData as $needHandleDataRow) {
        $goodsIdArr[] = $needHandleDataRow['goodsId'];
    }
    $goodsListMap = [];
    $systemSpecMap = [];
    $selfSpecMap = [];
    if (count($goodsIdArr) > 0) {
        $goodsIdArr = array_unique($goodsIdArr);
        $goodsListData = $goodsModule->getGoodsListById($goodsIdArr);
        $goodsList = $goodsListData['data'];
        foreach ($goodsList as $goodsRow) {
            $goodsListMap[$goodsRow['goodsId']] = $goodsRow;
        }
        $systemTab = M('sku_goods_system');
        $sysWhere = [];
        $sysWhere ['dataFlag'] = 1;
        $sysWhere ['goodsId'] = array('IN', $goodsIdArr);
        $systemSpecList = $systemTab->where($sysWhere)->order('skuId asc')->select();
        $skuIdArr = array();
        foreach ($systemSpecList as $systemSpecRow) {
            $systemSpecMap[$systemSpecRow['goodsId']][] = $systemSpecRow;
            $skuIdArr[] = $systemSpecRow['skuId'];
        }
        if (count($skuIdArr) > 0) {
            $skuIdArr = array_unique($skuIdArr);
            $selfSpecList = M("sku_goods_self se")
                ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                ->where(['se.skuId' => array('IN', $skuIdArr), 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                ->order('sp.sort asc')
                ->select();
            foreach ($selfSpecList as $selfSpecListRow) {
                $selfSpecMap[$selfSpecListRow['skuId']][] = $selfSpecListRow;
            }
        }
    }
    foreach ($needHandleData as $key => $val) {
        $goodsId = $needHandleData[$key]['goodsId'];
        if (empty($goodsId)) {
            continue;
        }
        //$goodsInfo = $goodsModule->getGoodsInfoById($goodsId, '*', 2);
        $goodsInfo = $goodsListMap[$goodsId];
        $goodsSku = [];
        $goodsSku['skuSpec'] = [];//PS:多规格,去重后,前端直接展示
        $goodsSku['skuList'] = [];//PS:商品所有sku,选择规格属性后到该列表中获取对应的sku
//        $systemTab = M('sku_goods_system');
//        //$selfTab = M('sku_goods_self');
//        $sysWhere = [];
//        $sysWhere ['dataFlag'] = 1;
//        $sysWhere ['goodsId'] = $goodsId;
//        $systemSpec = $systemTab->where($sysWhere)->order('skuId asc')->select();
        $systemSpec = $systemSpecMap[$goodsId];
        if ($skuBarcode) {
            $sysWhere['skuBarcode'] = $skuBarcode;
            $systemSpec = $systemTab->where($sysWhere)->limit(1)->order('skuId asc')->select();
        }
        $needHandleData[$key]['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
        if ($systemSpec) {
            $needHandleData[$key]['hasGoodsSku'] = 1;
            //skuList
            foreach ($systemSpec as $value) {
                $spec = [];
                $spec['skuId'] = $value['skuId'];
                foreach ($replaceSkuField as $rek => $rev) {
                    $spec['systemSpec']['selling_stock'] = $value['selling_stock'];
                    $spec['systemSpec']['purchase_price'] = $value['purchase_price'];
                    $spec['systemSpec']['weigetG'] = formatAmount($value['weigetG'], 1);
                    if (isset($value[$rek]) && $rek != 'weigetG') {
                        $spec['systemSpec'][$rek] = $value[$rek];
                    }
                    if (in_array($rek, ['dataFlag', 'addTime'])) {
                        continue;
                    }
                    if ((int)$spec['systemSpec'][$rek] == -1) {
                        //如果sku属性值为-1,则调用商品原本的值(详情查看config)
                        $spec['systemSpec'][$rek] = $goodsInfo[$rev];
                    }
                    if (is_null($spec['systemSpec'][$rek])) {
                        $spec['systemSpec'][$rek] = '';
                    }
                }
//                $spec['selfSpec'] = M("sku_goods_self se")
//                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                    ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
//                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                    ->order('sp.sort asc')
//                    ->select();
                $spec['selfSpec'] = $selfSpecMap[$value['skuId']];
                if (empty($spec['selfSpec'])) {
                    continue;
                }
                $selfSpecAttr = [];
                foreach ($spec['selfSpec'] as $selfVal) {
                    $selfSpecAttr[] = $selfVal['attrName'];
                }
                $spec['selfSpecStr'] = implode(',', $selfSpecAttr);
                $goodsSku['skuList'][] = $spec;
            }
            if (empty($goodsSku['skuList'])) {
                $needHandleData[$key]['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
            }
            //skuSpec
            $skuSpec = [];
            $skuSpecAttr = [];
            foreach ($goodsSku['skuList'] as $value) {
                foreach ($value['selfSpec'] as $va) {
                    $skuSpecAttr[] = $va;
                    $skuSpecInfo['specId'] = $va['specId'];
                    $skuSpecInfo['specName'] = $va['specName'];
                    $skuSpec[] = $skuSpecInfo;
                }
            }
            $skuSpec = arrayUnset($skuSpec, 'specId');
            $skuSpecAttr = arrayUnset($skuSpecAttr, 'attrId');
            foreach ($skuSpec as $skey => &$sval) {
                foreach ($skuSpecAttr as $v) {
                    if ($v['specId'] == $sval['specId']) {
                        $attrInfo['skuId'] = $v['skuId'];
                        $attrInfo['attrId'] = $v['attrId'];
                        $attrInfo['attrName'] = $v['attrName'];
                        $sval['attrList'][] = $attrInfo;
                    }
                }
            }
            unset($sval);
            $goodsSku['skuSpec'] = $skuSpec;
        }
        $needHandleData[$key]['goodsSku'] = $goodsSku;
    }
    $returnData = $needHandleData;
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $returnData = $returnData[0];
    }
    return (array)$returnData;
}

///*
// * 获取购物车商品sku信息 PS:使用场景:购物车,订单商品详情等
// * @param array $data
// * */
//function getCartGoodsSku($data)
//{
//    if (!empty($data)) {
//
//        //$data['goodsSku'] = [];//初始化为空数组 避免参与获取sku的数据最终还是没有这个字段
//
//        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//        $goodsField = [];
//        if (!empty($replaceSkuField)) {
//            foreach ($replaceSkuField as $fv) {
//                $goodsField[] = $fv;
//            }
//        }
//        $goodsTab = M('goods');
//        $systemTab = M('sku_goods_system');
//        if (array_keys($data) !== range(0, count($data) - 1)) {
//            //一维
//            $goodsId = $data['goodsId'];
//            //$goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->field($goodsField)->find();
//            $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->find();
//            $skuId = $data['skuId'];
//            $goodsSku = [];
//            $goodsSku['skuList'] = [];//sku详细信息
//            $goodsSku['skuSpecStr'] = '';//sku简略信息
//            $goodsSku['systemSpec'] = '';//sku自定义信息,级别向上升一级,方便前端操作数据
//            $data['hasGoodsSku'] = 0;
//            if ($skuId > 0) {
//                $sysWhere = [];
//                $sysWhere['goodsId'] = $goodsId;
//                $sysWhere['skuId'] = $skuId;
//                $systemSpec = $systemTab->where($sysWhere)->find();
//                //$data['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
//                if (!empty($systemSpec)) {
//                    $data['hasGoodsSku'] = 1;
//                    //skuList
//                    $spec = [];
//                    $spec['skuId'] = $systemSpec['skuId'];
//                    foreach ($replaceSkuField as $rek => $rev) {
//                        if (isset($systemSpec[$rek])) {
//                            $spec['systemSpec'][$rek] = $systemSpec[$rek];
//                        }
//                        if (in_array($rek, ['dataFlag', 'addTime'])) {
//                            continue;
//                        }
//                        if ((int)$spec['systemSpec'][$rek] == -1) {
//                            //如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                            $spec['systemSpec'][$rek] = $goodsInfo[$rev];
//                        }
//                        if (is_null($spec['systemSpec'][$rek])) {
//                            $spec['systemSpec'][$rek] = '';
//                        }
//                    }
//                    $spec['selfSpec'] = M("sku_goods_self se")
//                        ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                        ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                        ->where(['se.skuId' => $systemSpec['skuId'], 'se.dataFlag' => 1])
//                        ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                        ->order('sp.sort asc')
//                        ->select();
//                    $goodsSku['skuList'] = $spec;
//                    foreach ($spec['selfSpec'] as $sv) {
//                        $goodsSku['skuSpecStr'] .= $sv['attrName'] . "，";
//                    }
//                    //$goodsSku['skuSpecStr'] = trim($goodsSku['skuSpecStr'], '，');
////                    $goodsSku['skuSpecStr'] = mb_substr(rtrim($goodsSku['skuSpecStr'],'，'),0,-1,'utf-8');//不要删除
//                    $goodsSku['skuSpecStr'] = rtrim($goodsSku['skuSpecStr'], '，');//暂用这个方式去除
//
//                    if (!empty($replaceSkuField)) {//sku属性值替换
//                        foreach ($replaceSkuField as $rk => $rv) {
//                            if (isset($val[$rv])) {
//                                $data[$rv] = $goodsSku['skuList']['systemSpec'][$rk];
//                            }
//                        }
//                    }
//                    $goodsSku['systemSpec'] = $goodsSku['skuList']['systemSpec'];
//                    if (empty($goodsSku)) {
//                        $data['goodsSku'] = [];
//                    } else {
//                        $data['goodsSku'] = $goodsSku;
//                    }
//
//                }
//            }
//        } else {
//            //二维
//            foreach ($data as $key => $val) {
//                //限量购商品----价格替换
////                if($val['isLimitBuy'] == 1 && $val['limitCount'] >0){
////                    $data[$key]['shopPrice'] = $data[$key]['limitCountActivityPrice'];
////                }
//                $goodsId = $data[$key]['goodsId'];
//                //$goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->field($goodsField)->find();
//                $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->find();
//                $skuId = $data[$key]['skuId'];
//                $goodsSku = [];
//                $goodsSku['skuList'] = [];//sku详细信息
//                $goodsSku['skuSpecStr'] = '';//sku简略信息
//                $data[$key]['hasGoodsSku'] = 0;
//                if ($skuId > 0) {
//                    $sysWhere = [];
//                    $sysWhere['goodsId'] = $goodsId;
//                    $sysWhere['skuId'] = $skuId;
//                    $systemSpec = $systemTab->where($sysWhere)->find();
//                    //$data[$key]['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
//                    if (!empty($systemSpec)) {
//                        $data[$key]['hasGoodsSku'] = 1;
//                        //skuList
//                        $spec = [];
//                        $spec['skuId'] = $systemSpec['skuId'];
//                        foreach ($replaceSkuField as $rek => $rev) {
//                            if (isset($systemSpec[$rek])) {
//                                $spec['systemSpec'][$rek] = $systemSpec[$rek];
//                            }
//                            if (in_array($rek, ['dataFlag', 'addTime'])) {
//                                continue;
//                            }
//                            if ((int)$spec['systemSpec'][$rek] == -1) {
//                                //如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                                $spec['systemSpec'][$rek] = $goodsInfo[$rev];
//                            }
//                            if (is_null($spec['systemSpec'][$rek])) {
//                                $spec['systemSpec'][$rek] = '';
//                            }
//                        }
//                        $spec['selfSpec'] = M("sku_goods_self se")
//                            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                            ->where(['se.skuId' => $systemSpec['skuId'], 'se.dataFlag' => 1])
//                            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                            ->order('sp.sort asc')
//                            ->select();
//                        $goodsSku['skuList'] = $spec;
//                        foreach ($spec['selfSpec'] as $sv) {
//                            $goodsSku['skuSpecStr'] .= $sv['attrName'] . "，";
//                        }
//
//                        //$goodsSku['skuSpecStr'] = trim($goodsSku['skuSpecStr'], '，');
////                        $goodsSku['skuSpecStr'] = mb_substr(rtrim($goodsSku['skuSpecStr'],'，'),0,-1,'utf-8');//不要删除
//                        $goodsSku['skuSpecStr'] = rtrim($goodsSku['skuSpecStr'], '，');//暂用这个方式去除
//
//                        if (!empty($replaceSkuField)) {//sku属性值替换
//                            foreach ($replaceSkuField as $rk => $rv) {
//                                if (isset($val[$rv])) {
//                                    $data[$key][$rv] = $goodsSku['skuList']['systemSpec'][$rk];
//                                }
//                            }
//                        }
//                    }
//                }
//                $data[$key]['goodsSku'] = $goodsSku;
//            }
//        }
//    }
//    return $data;
//}

/*
 * 获取购物车商品sku信息 PS:使用场景:购物车,订单商品详情等
 * @param array $data
 * */
function getCartGoodsSku($data)
{
    //上面注释的是原来的方法,有点乱,下面主逻辑就不修改了,只是简化一下
    if (empty($data)) {
        return $data;
    }
    $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
    $goodsField = [];
    if (!empty($replaceSkuField)) {
        foreach ($replaceSkuField as $fv) {
            $goodsField[] = $fv;
        }
    }
    $goodsTab = M('goods');
    $systemTab = M('sku_goods_system');
    $needHandleData = $data;
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $needHandleData = array($data);
    }
    $goodsListMap = [];
    $goodsIdArr = array_column($needHandleData, 'goodsId');
    $goodsList = $goodsTab->where(['goodsId' => array('in', $goodsIdArr)])->select();
    foreach ($goodsList as $goodsListRow) {
        $goodsListMap[$goodsListRow['goodsId']] = $goodsListRow;
    }
    $systemSpecListMap = [];
    $selfSpecListMap = [];
    $skuIdArr = array_column($needHandleData, 'skuId');
    $skuIdArr = array_unique($skuIdArr);
    if (count($skuIdArr) > 0) {
        $sysWhere = [];
        $sysWhere['skuId'] = array('in', $skuIdArr);
        $systemSpecList = $systemTab->where($sysWhere)->select();
        foreach ($systemSpecList as $systemSpecListRow) {
            if ($systemSpecListRow['skuId'] <= 0) {
                continue;
            }
            $systemSpecListMap[$systemSpecListRow['skuId']] = $systemSpecListRow;
        }
    }
    if (!empty($systemSpecListMap)) {
        $selfSpecList = M("sku_goods_self se")
            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
            ->where(['se.skuId' => array('in', $skuIdArr), 'se.dataFlag' => 1])
            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
            ->order('sp.sort asc')
            ->select();
        foreach ($selfSpecList as $selfSpecListRow) {
            $selfSpecListMap[$selfSpecListRow['skuId']][] = $selfSpecListRow;
        }
    }
    foreach ($needHandleData as $key => $val) {
        $goodsId = $needHandleData[$key]['goodsId'];
        //$goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->field($goodsField)->find();
//        $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->find();
        $goodsInfo = $goodsListMap[$goodsId];
        $skuId = $needHandleData[$key]['skuId'];
        $goodsSku = [];
        $goodsSku['skuList'] = [];//sku详细信息
        $goodsSku['skuSpecStr'] = '';//sku简略信息
        $needHandleData[$key]['hasGoodsSku'] = 0;
        if ($skuId > 0) {
//            $sysWhere = [];
//            $sysWhere['goodsId'] = $goodsId;
//            $sysWhere['skuId'] = $skuId;
//            $systemSpec = $systemTab->where($sysWhere)->find();
            $systemSpec = $systemSpecListMap[$skuId];
            //$data[$key]['hasGoodsSku'] = 0;//是否有商品sku(0=>无 | 1=>有)
            if (!empty($systemSpec)) {
                $needHandleData[$key]['hasGoodsSku'] = 1;
                //skuList
                $spec = [];
                $spec['skuId'] = $systemSpec['skuId'];
                foreach ($replaceSkuField as $rek => $rev) {
                    $spec['systemSpec']['selling_stock'] = $systemSpec['selling_stock'];
                    if (isset($systemSpec[$rek])) {
                        $spec['systemSpec'][$rek] = $systemSpec[$rek];
                    }
                    if (in_array($rek, ['dataFlag', 'addTime'])) {
                        continue;
                    }
                    if ((int)$spec['systemSpec'][$rek] == -1) {
                        //如果sku属性值为-1,则调用商品原本的值(详情查看config)
                        $spec['systemSpec'][$rek] = $goodsInfo[$rev];
                    }
                    if (is_null($spec['systemSpec'][$rek])) {
                        $spec['systemSpec'][$rek] = '';
                    }
                }
//                $spec['selfSpec'] = M("sku_goods_self se")
//                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                    ->where(['se.skuId' => $systemSpec['skuId'], 'se.dataFlag' => 1])
//                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                    ->order('sp.sort asc')
//                    ->select();
                $spec['selfSpec'] = $selfSpecListMap[$skuId];
                $goodsSku['skuList'] = $spec;
                foreach ($spec['selfSpec'] as $sv) {
                    $goodsSku['skuSpecStr'] .= $sv['attrName'] . ",";
                }

                //$goodsSku['skuSpecStr'] = trim($goodsSku['skuSpecStr'], '，');
//                        $goodsSku['skuSpecStr'] = mb_substr(rtrim($goodsSku['skuSpecStr'],'，'),0,-1,'utf-8');//不要删除
                $goodsSku['skuSpecStr'] = rtrim($goodsSku['skuSpecStr'], ',');//暂用这个方式去除

                if (!empty($replaceSkuField)) {//sku属性值替换
                    foreach ($replaceSkuField as $rk => $rv) {
                        if (isset($val[$rv])) {
                            $needHandleData[$key][$rv] = $goodsSku['skuList']['systemSpec'][$rk];
                        }
                    }
                }
            }
        }
        $needHandleData[$key]['goodsSku'] = $goodsSku;
    }
    $returnData = $needHandleData;
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $returnData = $returnData[0];
    }
    return (array)$returnData;
}


/*
 * 获取商品SKU价格 PS:用于支付订单时使用
 * @param int $userId
 * @param int $skuId
 * @param int $goodsId
 * @param float $shopPrice
 * @param int $goodsCnt
 * @param int $shopId
 * */
function getGoodsSkuPrice($userId, $skuId, $goodsId, $shopPrice, $goodsCnt)
{
    $shopPrice = (float)$shopPrice;
    $response = [];
    $response['goodsAttrName'] = '';
    $response['goodsPrice'] = $shopPrice;
    $response['totalMoney'] = $shopPrice * (float)$goodsCnt;
    $systemSkuSpec = M('sku_goods_system')->where(['goodsId' => $goodsId, 'skuId' => $skuId])->find();
    if ($systemSkuSpec) {
        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
        $goodsField = [];
        $goodsTab = M('goods');
        if (!empty($replaceSkuField)) {
            foreach ($replaceSkuField as $fv) {
                $goodsField[] = $fv;
            }
        }
        $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->field($goodsField)->find();
        foreach ($replaceSkuField as $rek => $rev) {
            if (isset($systemSkuSpec[$rek])) {
                $spec['systemSpec'][$rek] = $systemSkuSpec[$rek];
            }
            if (in_array($rek, ['dataFlag', 'addTime'])) {
                continue;
            }
            if ((int)$spec['systemSpec'][$rek] == -1) {
                //如果sku属性值为-1,则调用商品原本的值(详情查看config)
                $systemSkuSpec[$rek] = $goodsInfo[$rev];
            }
        }
        $shopPrice = $systemSkuSpec['skuShopPrice'];
        $where = [];
        $where['se.skuId'] = $skuId;
        $where['se.dataFlag'] = 1;
        $systemSkuSpec['attrList'] = M("sku_goods_self se")
            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
            ->where($where)
            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName')
            ->select();
        if (count($systemSkuSpec['attrList']) > 0) {
            foreach ($systemSkuSpec['attrList'] as $key => &$val) {
                $response['goodsAttrName'] .= $val['attrName'] . "，";
            }
            unset($val);
            $response['goodsAttrName'] = trim($response['goodsAttrName'], '，');
        }
        $response['goodsPrice'] = $shopPrice;
        $response['totalMoney'] = $shopPrice * (float)$goodsCnt;
    }
    return $response;
}

/**
 * 修改商品Sku库存
 * 通过 redis 来处理
 * @param int $skuId
 * @param int $goodsId
 * @param float $goodsCnt
 * @return mixed
 */
function updateGoodsSkuStockByRedis($skuId, $goodsId, $goodsCnt = 1)
{

//    /*$apiRet['apiCode']='-1';
//    $apiRet['apiInfo']='修改商品库存失败';
//    $apiRet['apiState']='error';*/
//
//    if (empty($goodsId) || empty($goodsCnt) || empty($skuId)) {
//        $apiRet = returnData(null, -1, 'error', '参数不全');
//        return $apiRet;
//    }
//
//    $redis = new \Redis();
//    $result = $redis->connect(C('redis_host1'), C('redis_port1'));
//    $res = $redis->llen('goods_sku_stock_' . $skuId);
//    $value = 50;
//    if ($res <= $value) {//如果队列小于50 就进行添加库存  否则跳过添加
//        //$redis->del('goods_sku_stock_'.$skuId);
//
//        $goodsInfo = M('sku_goods_system')->where(array('skuId' => $skuId))->find();
//        $goodsStock = $goodsInfo['skuGoodsStock'];
//        if ($goodsStock <= 0) {
//            $apiRet = returnData(null, -1, 'error', '商品库存不足');
//            return $apiRet;
//        }
//
//        $count = ($goodsStock > $value) ? $value - $res : $goodsStock - $res;
//        for ($i = 0; $i < $count; $i++) {
//            $redis->lpush('goods_sku_stock_' . $skuId, 1);
//        }
//        $redis->expire('goods_sku_stock_' . $skuId, 60);//设置有效时间为1分钟
//    }
//    $str_len = $redis->llen('goods_sku_stock_' . $skuId);
//    if ($str_len < $goodsCnt) {//库存不足
//        $apiRet = returnData(null, -1, 'error', '商品库存不足');
//        return $apiRet;
//    }
//
//    for ($i = 0; $i < $goodsCnt; $i++) {
//        $redis->lpop('goods_sku_stock_' . $skuId);
//    }
//    $goodsCnt = gChangeKg($goodsId, $goodsCnt, 1);
//    $returnState = M('sku_goods_system')->where(array('skuId' => $skuId))->setDec('skuGoodsStock', $goodsCnt);
//    if ($returnState) {
//        return returnData();
//    } else {
//        for ($i = 0; $i < $goodsCnt; $i++) {
//            $redis->lpush('goods_sku_stock_' . $skuId, 1);
//        }
//        // $apiRet = returnData(null,-1,'error','修改商品库存失败');
//        $apiRet = returnData(null, -1, 'error', "修改商品库存失败:{$goodsInfo['goodsName']}");
//        return $apiRet;
//    }
}


/*
 * 返还sku库存
 * @param int orderId
 * */
function returnGoodsSkuStock($orderId)
{
//    if (!empty($orderId)) {
//        $orderGoods = M('order_goods')->where(['orderId' => $orderId])->field('skuId,orderId,goodsNums')->select();
//        if ($orderGoods) {
//            foreach ($orderGoods as $val) {
//                if ($val['skuId'] > 0) {
//                    $goodsNums = gChangeKg($val['goodsId'], $val['goodsNums'], 1);
//                    M('sku_goods_system')->where("skuId='" . $val['skuId'] . "'")->setInc('skuGoodsStock', $val['goodsNums']);
//                }
//            }
//        }
//    }
}

/**
 * 获取要排除的商品分类id
 * @return mixed
 */
function getShopCatNewAllCatId()
{
    $where = array();
    $where['isShow'] = 1;
    $where['scnFlag'] = 1;
    return M('shops_cats_new')->where($where)->getField('catId', true);
}

/*
 * 处理pos端和shu相关的数据
 * */
function handlePosSkuData($data)
{
    if (!empty($data)) {
        if (array_keys($data) !== range(0, count($data) - 1)) {
            //一维
            if (isset($data['skuId']) && $data['skuId'] < 0) {
                $data['skuId'] = '';
            }
        } else {
            foreach ($data as $key => $val) {
                if (isset($val['skuId']) && $val['skuId'] < 0) {
                    $data[$key]['skuId'] = '';
                }
            }
        }
    }
    return $data;
}

/*
    获取符合条件的店铺,用于菜单推荐商品
    @param array shops PS:店铺数据
    @param array keyword PS:分词数据
    @param int type PS:返回数据格式(1:一家店铺信息|2:多家店铺信息)
*/
function handleMenuShop($shops, $keyword, $type = 1)
{
    $shop = [];
    if (!empty($shops) && !empty($keyword)) {
        $goodsTab = M('goods');
        $keywordStr = "";
        foreach ($keyword as $val) {
            $keywordStr .= " (goodsName like '%" . $val . "%') " . " or";
        }
        $keywordStr = rtrim($keywordStr, 'or');
        foreach ($shops as $key => $val) {
            $gWhere = "goodsStatus=1 and goodsFlag=1 and isSale=1 and shopId='" . $val['shopId'] . "' and $keywordStr";
            $goodsCount = $goodsTab->where($gWhere)->count('goodsId');
            if ($goodsCount > 0) {
                if ($type == 1) {
                    $shop['shopId'] = $val['shopId'];
                    break;
                } elseif ($type == 2) {
                    $shop[] = $val['shopId'];
                }
            }
        }
    }
    return $shop;
}

define("LAJP_IP", "127.0.0.1");     //Python端IP
define("LAJP_PORT", 21230);         //Python端侦听端口

define("PARAM_TYPE_ERROR", 101);    //参数类型错误
define("SOCKET_ERROR", 102);        //SOCKET错误
define("LAJP_EXCEPTION", 104);      //Python端反馈异常

function ppython()
{
    //参数数量
    $args_len = func_num_args();
    //参数数组
    $arg_array = func_get_args();

    //参数数量不能小于1
    if ($args_len < 1) {
        throw new Exception("[PPython Error] lapp_call function's arguments length < 1", PARAM_TYPE_ERROR);
    }
    //第一个参数是Python模块函数名称，必须是string类型
    if (!is_string($arg_array[0])) {
        throw new Exception("[PPython Error] lapp_call function's first argument must be string \"module_name::function_name\".", PARAM_TYPE_ERROR);
    }


    if (($socket = socket_create(AF_INET, SOCK_STREAM, 0)) === false) {
        throw new Exception("[PPython Error] socket create error.", SOCKET_ERROR);
    }

    if (socket_connect($socket, LAJP_IP, LAJP_PORT) === false) {
        throw new Exception("[PPython Error] socket connect error.", SOCKET_ERROR);
    }

    //消息体序列化
    $request = serialize($arg_array);
    $req_len = strlen($request);

    $request = $req_len . "," . $request;

    //echo "{$request}<br>";

    $send_len = 0;
    do {
        //发送
        if (($sends = socket_write($socket, $request, strlen($request))) === false) {
            throw new Exception("[PPython Error] socket write error.", SOCKET_ERROR);
        }

        $send_len += $sends;
        $request = substr($request, $sends);

    } while ($send_len < $req_len);

    //接收
    $response = "";
    while (true) {
        $recv = "";
        if (($recv = socket_read($socket, 1400)) === false) {
            throw new Exception("[PPython Error] socket read error.", SOCKET_ERROR);
        }
        if ($recv == "") {
            break;
        }

        $response .= $recv;

        //echo "{$response}<br>";

    }

    //关闭
    socket_close($socket);

    $rsp_stat = substr($response, 0, 1);    //返回类型 "S":成功 "F":异常
    $rsp_msg = substr($response, 1);        //返回信息

    //echo "返回类型:{$rsp_stat},返回信息:{$rsp_msg}<br>";

    if ($rsp_stat == "F") {
        //异常信息不用反序列化
        throw new Exception("[PPython Error] Receive Python exception: " . $rsp_msg, LAJP_EXCEPTION);
    } else {
        if ($rsp_msg != "N") //返回非void
        {
            //反序列化
            return unserialize($rsp_msg);
        }
    }
}

/*
 *转换字符编码 PS:针对python返回的数据格式编码 latin1
 * @param [string | array] $data
 * */
function iconvLatin1($data)
{
    if (is_string($data)) {
        //string
        $handleWord = iconv('UTF-8', 'latin1//IGNORE', $data);
        $handleRes = mb_convert_encoding($handleWord, 'UTF-8', 'CP936');
        if (!empty($handleRes)) {
            $data = $handleRes;
        }
    } elseif (is_array($data)) {
        //array
        if (array_keys($data) !== range(0, count($data) - 1)) {
            //一维
            foreach ($data as $key => $value) {
                $handleWord = iconv('UTF-8', 'latin1//IGNORE', $value);
                $handleRes = mb_convert_encoding($handleWord, 'UTF-8', 'CP936');
                if (!empty($handleRes)) {
                    $data[$key] = $handleRes;
                }
            }
        } else {
            //二维
            foreach ($data as $key => $value) {
                foreach ($value as $k => $v) {
                    $handleWord = iconv('UTF-8', 'latin1//IGNORE', $v);
                    $handleRes = mb_convert_encoding($handleWord, 'UTF-8', 'CP936');
                    if (!empty($handleRes)) {
                        $data[$key][$k] = $handleRes;
                    }
                }
            }
        }
    }
    return $data;
}

/**
 * 去除筐位
 * @param $orderId
 * @return mixed
 * 弃用，目前不需要这个功能
 */
function removeBasket($orderId)
{
//    return M('orders')->where(array('orderId'=>$orderId))->setField('basketId',0);
    return '';
}

/*
 * 随机生成密码
 * @param int $len 6 PS:长度
 * @param string $format PS:数据类型[ALL|CHAR|NUMBER]
 * */
function randPassword($len = 6, $format = 'ALL')
{
    switch ($format) {
        case 'ALL':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
            break;
        case 'CHAR':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~';
            break;
        case 'NUMBER':
            $chars = '0123456789';
            break;
        default :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
            break;
    }
    mt_srand((double)microtime() * 1000000 * getmypid());
    $password = "";
    while (strlen($password) < $len)
        $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
    return $password;
}

/**
 * 将商品重量 由 g 转换成 kg
 * @param int $goodsId 商品id
 * @param int $goodsNum 商品数量
 * @param int $isMove 移动端【0：否|1：是】
 * @param int $skuId 商品skuId
 * @return int|string
 */
//function gChangeKg($goodsId, $goodsNum, $isMove = 1)
//{
//    $goodsWeight = 0;
//    $where = [];
//    $where['goodsId'] = $goodsId;
//    $goodsInfo = M('goods')->where($where)->find();
//    if (empty($goodsInfo)) {
//        return $goodsWeight;
//    }
//    if ($goodsInfo['SuppPriceDiff'] == -1) {//标品
//        $goodsWeight = $goodsNum;
//    } else if ($goodsInfo['SuppPriceDiff'] == 1) {//秤重商品
//        if (empty($isMove)) {//非移动端,直接转换成 kg
//            $goodsWeight = number_format($goodsNum / 1000, 3, ".", "");
//        } else {//移动端，数量乘以单位再除以1000，即为 kg
//            $goodsWeight = number_format(($goodsNum * $goodsInfo['weightG']) / 1000, 3, ".", "");
//        }
//    }
//    return $goodsWeight;
//}

/**
 * 注:重写上面注释的方法
 * 处理最终购买的实际库存数量
 * 将商品重量 由 g 转换成 kg[单位换算已废弃]
 * @param int $goodsId 商品id
 * @param float $goodsNum 商品数量/重量
 * @param int $isMove 移动端【0：否|1：是】
 * @param int $skuId 商品skuId
 * @return float
 */
function gChangeKg($goodsId, $goodsNum, $isMove = 1, $skuId = 0)
{

    $goods_module = new \App\Modules\Goods\GoodsModule();
    $num_or_weight = 0;
    $goods_field = 'goodsId,goodsName,SuppPriceDiff,weightG';
    $goods_detail = $goods_module->getGoodsInfoById($goodsId, $goods_field, 2);
    if (empty($goods_detail)) {
        return $num_or_weight;
    }
    //废除包装系数
//    $weight = (float)$goods_detail['weightG'];//包装系数
//    if (empty($weight)) {
//        $weight = 1;
//    }
//    if ($skuId > 0) {
//        $sku_detail = $goods_module->getSkuSystemInfoById($skuId, 2);
//        $weight = (float)$sku_detail['weigetG'];
//    }
    $weight = 1;
    if ($goods_detail['SuppPriceDiff'] == 1) {//非标品
        if (empty($isMove)) {//非移动端,直接转换成 kg
//            $num_or_weight = sprintfNumber($goodsNum / 1000, 3);
            $num_or_weight = $goodsNum;//不在处理单位相关
        } else {//移动端，用户购买数量乘以包装系数再除以1000，即为 kg
//            $num_or_weight = sprintfNumber(($goodsNum * $weight) / 1000, 3);
            $num_or_weight = sprintfNumber(($goodsNum * $weight), 3);
        }
    } else {//标品
        $num_or_weight = $goodsNum * $weight;
    }
    return (float)$num_or_weight;
}

/**
 * 筛选商品,筛选出可用（在配送范围内）和不可用（不在配送范围内）的商品
 * @param $addressId 地址ID
 * @param $goodsId 商品ID,多个以,连接
 */
function checkShopDistributionGoods($addressId, $goodsId)
{
    $apiRet['apiCode'] = -1;
    $apiRet['apiInfo'] = '操作失败';
    $apiRet['apiState'] = 'error';
    $apiRet['apiData'] = array();

    $uam = M('user_address');
    $gm = M('goods');
    $user_address_info = $uam->where(array('addressId' => $addressId))->find();
    if (empty($user_address_info)) {
        $apiRet['apiInfo'] = '地址不存在';
        return $apiRet;
    }
    if (empty($user_address_info['lat']) || empty($user_address_info['lng'])) {
        $apiRet['apiInfo'] = '地址信息不全（和地址相关的经纬度不完整）';
        return $apiRet;
    }
    $goods_list = $gm->where(array('goodsId' => array('in', $goodsId), 'isSale' => 1, 'goodsStock' => array('GT', 0), 'goodsFlag' => 1, 'goodsStatus' => 1))->field('goodsId,shopId')->select();
    if (empty($goods_list)) {
        $apiRet['apiInfo'] = '商品不存在，或没有上架，或库存不足';
        return $apiRet;
    }
    $data = array('use' => array(), 'no_use' => array());
    foreach ($goods_list as $v) {
        $dcheck = checkShopDistribution($v['shopId'], $user_address_info['lng'], $user_address_info['lat']);
        if ($dcheck) $data['use'][] = $v['goodsId'];
        else $data['no_use'][] = $v['goodsId'];
    }
    return $data;
}

/**
 * 上传图片
 * 支持单个图片上传、多个图片上传
 * 可以单个图片上传成功，目前暂时不能批量上传
 */
function uploadQiniuPic($filePath, $key)
{
    vendor('Qiniu.autoload');
    vendor('Qiniu.src.Qiniu.Auth');
    vendor('Qiniu.src.Qiniu.Storage.BucketManager');
    vendor('Qiniu.src.Qiniu.Storage.UploadManager');

    // $str = I('str');
    $setting = C('UPLOAD_SITEIMG_QINIU');
    $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
//        $setting['saveName'] = getQiniuImgName(I('str'));
    // $setting['saveName'] = array('getQiniuImgName',I('str'));
    $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
    $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
    $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
    $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
    $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
    $setting['driverConfig']['qiniuUploadUrl'] = $GLOBALS['CONFIG']['qiniuUploadUrl'];

    $accessKey = $setting['driverConfig']['accessKey'];
    $secretKey = $setting['driverConfig']['secrectKey'];
    $bucket = $setting['driverConfig']['bucket'];

    // 构建鉴权对象
    $auth = new \Qiniu\Auth($accessKey, $secretKey);

    // 生成上传 Token
    $token = $auth->uploadToken($bucket);

    // 要上传文件的本地路径
//        $filePath = './php-logo.png';
    // $filePath = $_FILES['file']['tmp_name'];
    // $ext = explode('.',$_FILES['file']['name'])[1];  //后缀

    // 上传到七牛后保存的文件名
//        $key = 'my-php-logo.png';
    // $key = getQiniuImgName($str);

    // 初始化 UploadManager 对象并进行文件的上传。
    $uploadMgr = new \Qiniu\Storage\UploadManager();

    // 调用 UploadManager 的 putFile 方法进行文件的上传。
    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
//        echo "\n====> putFile result: \n";
    if ($err !== null) {
//            var_dump($err);
        return array('code' => -1, 'data' => $err);
    } else {

        return array('code' => 0, 'data' => $ret);
    }

}

/**
 * 根据下单送积分比例来获得奖励积分
 * @param $money
 * @return float
 */
function getOrderScoreByOrderScoreRate($money)
{
    $order_score_rate = empty($GLOBALS['CONFIG']['orderScoreRate']) ? 100 : $GLOBALS['CONFIG']['orderScoreRate'];//积分比例是%
    return floor($money * ($order_score_rate / 100));
}

/**
 * @param $goodsInfo 购买商品信息
 * @param $money 纯粹的商品总金额
 * @return float
 * 获取下单奖励积分【商品积分和下单比例积分】
 */
function getRewardsIntegral($goodsInfo, $money)
{
    if ($goodsInfo['integralReward'] > 0) {
        $integral = $goodsInfo['integralReward'] * $goodsInfo['goodsCnt'];
    } else {
        $integral = getOrderScoreByOrderScoreRate($money);
    }
    return floor($integral);
}

/*
 * 获取购物车已选中的商品
 * @param int $userId
 * */
function getCartGoodsChecked($userId)
{
    $returnData = [
        'goodsId' => '',
        'goodsSku' => [],
    ];
    if (!empty($userId)) {
        $cartTab = M('cart');
        $goodsTab = M('goods');
        $where['userId'] = $userId;
        $where['isCheck'] = 1;
        //$where['cartId'] = ['IN',[468]];//需要删除
        $cartList = $cartTab->where($where)->select();
        if ($cartList) {
            $goodsId = '';
            $goodsSku = [];
            foreach ($cartList as $ck => $value) {
                $goodsInfo = $goodsTab->where(['goodsId' => $value['goodsId']])->find();
                if (!$goodsInfo) {
                    continue;
                }
                $goodsId .= $value['goodsId'] . ',';
                $newArr = [];
                /*$newArr['goodsId'] = $value['goodsId'];
                $newArr['skuId'] = $value['skuId'];
                $newArr['cartId'] = $value['skuId'];
                $goodsSku[] = $newArr;*/
                $goodsInfo['cartId'] = $value['cartId'];
                $goodsInfo['userId'] = $value['userId'];
                $goodsInfo['isCheck'] = $value['isCheck'];
                $goodsInfo['goodsAttrId'] = $value['goodsAttrId'];
                $goodsInfo['goodsCnt'] = $value['goodsCnt'];
                $goodsInfo['skuId'] = $value['skuId'];
                $goodsInfo['remarks'] = $value['remarks'];
                $goodsSku[] = $goodsInfo;
            }
            $goodsId = trim($goodsId, ',');
            $returnData['goodsId'] = $goodsId;
            $returnData['goodsSku'] = getCartGoodsSku($goodsSku);
        }
    }
    return $returnData;
}

/*
 * 过滤直辖市
 * @param string cityName
 * */
function handleCity($cityName)
{
    if (!empty($cityName)) {
        if (!in_array($cityName, ['上海市', '北京市', '天津市', '重庆市'])) {
            return true;
        }
    }
    return false;
}

/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function arrayToObject($arr)
{
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }
    return (object)$arr;
}

/*
 * 获取微信支付配置
 * @param int payType PS:支付方式(0:现金|1:支付宝|2:微信|3:余额)
 * @param int dataFrom PS:来源(0:商城 1:微信 2:手机版 3:app 4：小程序 5:公众号(后加))
 * */
function getWxPayConfig($payType, $dataFrom, $config)
{
    /*$root = WSTRootPath().'/ThinkPHP/Library/Vendor/WxPay/sdk3';
    require_once $root."/WxPay.Config.php";
    $config = new WxPayConfig();*/
    //获取商城设置,支付配置相关
    //$configs = $GLOBALS['CONFIG'];
    $where = [];
    $where['fieldCode'] = ['IN', ['xiaoAppid', 'xiaoSecret', 'wxAppId', 'wxAppKey']];
    $configs = M('sys_configs')->where($where)->select();//主要包含wap支付,小程序支付相关配置
    //暂时只有以下三种
    switch ($payType) {
        case 1:
            $payCode = 'alipay';
            break;
        case 2:
            $payCode = 'weixin';
            break;
        default :
            $payCode = 'cod';
    }
    $payConfig = M('payments')->where(['payCode' => $payCode])->find();
    $payConfigDetail = json_decode($payConfig['payConfig'], true);
    if ($payCode == 'alipay') {
        $returnData = $payConfigDetail;
    } elseif ($payCode == 'weixin') {
        $returnData = $payConfigDetail;
        if ($dataFrom == 2 || $dataFrom == 5) {
            //wap
            $returnData['appId'] = getConfigDetail($configs, 'wxAppId');
            if ($dataFrom == 5) {
                //公众号
                $returnData['appsecret'] = getConfigDetail($configs, 'wxAppKey');
            }
        } elseif ($dataFrom == 3) {
            //app
            $returnData['appId'] = $returnData['appId'];
        } else {
            //小程序
            $returnData['appId'] = getConfigDetail($configs, 'xiaoAppid');
            $returnData['appsecret'] = getConfigDetail($configs, 'xiaoSecret');
        }
        //重置关键配置
        $config->SetAppId($returnData['appId']);
        $config->SetAppSecret($returnData['appsecret']);
        $config->SetKey($returnData['apiKey']);
        $config->SetMerchantId($returnData['mchId']);
    } else {
        $returnData = $payConfigDetail;
    }
    return $returnData;
}

/*
 *获取配置中的详细信息
 * @param array data 数据集
 * @param string key
 * */
function getConfigDetail($data, $key)
{
    $info = '';
    foreach ($data as $index => $datum) {
        if ($datum['fieldCode'] == $key) {
            $info = $datum['fieldValue'];
        }
    }
    return $info;
}

/*
 * 调用微信统一下单
 * @param string openId
 * @param string attach 附加参数
 * @param int payType PS:支付方式(1:微信|2:支付宝|3:余额)
 * */
function unifiedOrder($param)
{
    //测试参数
    //$root = WSTRootPath() . '/ThinkPHP/Library/Vendor/WxPay/sdk3';
    $root = $_SERVER['DOCUMENT_ROOT'] . '/ThinkPHP/Library/Vendor/WxPay/sdk3';
    require_once $root . "/lib/WxPay.Api.php";
    require_once $root . "/WxPay.JsApiPay.php";
    require_once $root . "/WxPay.Config.php";
    require_once $root . "/lib/WxPay.Data.php";
    require_once $root . '/log.php';


    //初始化日志
    $logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
    \Log::Init($logHandler, 15);
    if (isset($param['attach']) && !empty($param['attach'])) {
        $attach = $param['attach'];
    }
    $amount = $param['amount'] * 100;
    try {
        $config = new WxPayConfig();
        getWxPayConfig($param['payType'], $param['dataFrom'], $config);//重置支付配置
        //$tools = new \JsApiPay();
        $openId = $param['openId'];
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("订单支付");
        $input->SetAttach($attach);
        $input->SetOut_trade_no($param['orderNo']);
        $input->SetTotal_fee($amount);
        $input->SetNotify_url(WSTDomain() . '/Home/WxPay/notifyUnifiedOrder');
        //2:手机版 3:app 4：小程序
        if ($param['dataFrom'] == 2) {
            $input->SetTrade_type("MWEB");
        } elseif ($param['dataFrom'] == 3) {
            $input->SetTrade_type("APP");
        } else {
            $input->SetTrade_type("JSAPI");
        }
        $input->SetSignType('MD5');
        $input->SetProduct_id(time());
        if ($param['dataFrom'] == 4 || $param['dataFrom'] == 5) {
            $input->SetOpenid($openId);
        }
        $order = WxPayApi::unifiedOrder($config, $input);
        if ($order['result_code'] === 'SUCCESS') {
            $order['apikey'] = $config->GetKey();
        }
        return $order;
        //$jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
        //$editAddress = $tools->GetEditAddressParameters();
    } catch (Exception $e) {
        Log::ERROR(json_encode($e));
    }
}

/*
 * 格式化金额
 * @param float $amount
 * @param int $num PS:保留位数
 * */
function formatAmount($amount, $num = 2)
{
    if ($amount <= 0) {
        $amount = 0;
    }
    return number_format($amount, $num, ".", "");
}

function formatAmountNum($amount, $num = 2)
{
    return number_format($amount, $num, ".", "");
}

/*
 * 处理新人专享商品
 * @param int $data PS:商品数据
 * @param int $userId PS:用户id
 * */
function handleNewPeople($data = [], $userId = 0)
{
    $userId = (int)$userId;
    if (!empty($data)) {
        $userTab = M('users');
        $userInfo = (array)$userTab->where(['userId' => $userId])->find();
        $newPeople = 1;//是否为新人(0:否|1:是)
        if ($userInfo) {
            $orderTab = M('orders');
            $orderCount = (int)$orderTab->where(['userId' => $userInfo['userId']])->count('orderId');
            if ($orderCount > 0) {
                $newPeople = 0;
            }
        }
        if (array_keys($data) !== range(0, count($data) - 1)) {
            //一维,处理详情
        } else {
            //二维,处理列表
            foreach ($data as $index => $datum) {
                if ($datum['isNewPeople'] == 1 && $newPeople != 1) {
                    unset($data[$index]);
                }
            }
        }
        $data = array_values($data);
    }
    return $data;
}

function dd($arr)
{
    echo "<pre>";
    var_dump($arr);
    exit;
}

function pr($arr)
{
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}

function prt($arr)
{
    echo "<pre>";
    print_r($arr);
    exit;
}

/*
 * 比较数字字符串大小
 * 比如：1.0.0.1，1.0.2.0.1.3
 */
function str_compare($str1, $str2)
{
    $arr1 = explode('.', $str1);
    $arr2 = explode('.', $str2);
    $len1 = count($arr1);
    $len2 = count($arr2);
    $len = ($len1 > $len2) ? $len1 : $len2;
    $result = 0;//无更新
    for ($i = 0; $i < $len; $i++) {
        if ($arr1[$i] > $arr2[$i]) {//有更新
            $result = 1;
            break;
        } elseif ($arr1[$i] < $arr2[$i]) {//无更新
            $result = 0;
            break;
        }
    }
    return $result;
}

/**
 * 邮件发送函数
 * @param string to      要发送的邮箱地址
 * @param string subject 邮件标题
 * @param string content 邮件内容
 * @return array
 */
function WSTSendMailForNotice($email_config, $to, $subject, $content)
{
    require_cache(VENDOR_PATH . "PHPMailer/class.smtp.php");
    require_cache(VENDOR_PATH . "PHPMailer/class.phpmailer.php");
    $mail = new PHPMailer();
    // 装配邮件服务器
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = $email_config['mailSmtp'];
    $mail->SMTPAuth = $email_config['mailAuth'];
    $mail->Username = $email_config['mailUserName'];
    $mail->Password = $email_config['mailPassword'];
    $mail->CharSet = 'utf-8';
    // 装配邮件头信息
    $mail->From = $email_config['mailAddress'];
    $mail->AddAddress($to);
    $mail->FromName = $email_config['mailSendTitle'];
    $mail->IsHTML(true);
    // 装配邮件正文信息
    $mail->Subject = $subject;
    $mail->Body = $content;
    // 发送邮件
    $rs = array();
    if (!$mail->Send()) {
        $rs['status'] = 0;
        $rs['msg'] = $mail->ErrorInfo;
        return $rs;
    } else {
        $rs['status'] = 1;
        return $rs;
    }
}

/**
 * 发送短信 （大汉三通）
 * @param $account  用户账号
 * @param $password  密码，需采用MD5加密(32位小写) ，如调用大汉三通提供jar包的话使用明文
 * @param $sign  短信签名，该签名需要提前报备，生效后方可使用，不可修改，必填，示例如：【大汉三通】
 * @param $phoneNumer  手机号码
 * @param $content  短信内容
 * @return bool|mixed
 */
function WSTSendSMS3ForNotice($short_message_config, $phoneNumer, $content)
{
    $param = array(
        'account' => $short_message_config['dhAccount'],//用户账号
        'password' => md5($short_message_config['dhPassword']),//密码，需采用MD5加密(32位小写) ，如调用大汉三通提供jar包的话使用明文
        'msgid' => '',//该批短信编号(32位UUID)，需保证唯一，选填，不填的话响应里会给一个系统生成的
        'phones' => $phoneNumer,//接收手机号码，多个手机号码用英文逗号分隔，最多1000个，必填，国际号码格式为+国别号手机号，示例：+85255441234
        'content' => $content,//短信内容，最多1000个汉字，必填,内容中不要出现【】[]这两种方括号，该字符为签名专用
        'sign' => $short_message_config['dhSign'],//短信签名，该签名需要提前报备，生效后方可使用，不可修改，必填，示例如：【大汉三通】
        'subcode' => '',//短信签名对应子码(大汉三通提供)+自定义扩展子码(选填)，必须是数字，选填，未填使用签名对应子码，通常建议不填
        'sendtime' => ''//定时发送时间，格式yyyyMMddHHmm，为空或早于当前时间则立即发送
    );

    $url = $short_message_config['dhSendUrl'] . "/json/sms/Submit";//短信下发（相同内容多个号码）
    $result = curlRequest($url, $param, 1);
    return $result;
}


/**
 * 极光推送
 * $content 推送内容，
 * $title 推送的标题
 * $type 推送的类型：给所有人推送还是指定id去推送
 * $ids 推送给哪些人，我存的别名是用户id，所以这里只有组合出要推送的人的用户id数组即可
 * $news_info  推送的新闻详情。移动端根据里面新闻id 去打开app中自动打开这个新闻id对应的新闻详情
 *
 */
function appJpush($content, $type, $ids = [], $title = '', $news_info)
{
    Vendor('jpush.autoload');
    $client = new \JPush($GLOBALS["CONFIG"]["jAppkey"], $GLOBALS["CONFIG"]["jMastersecret"]);
    // 给所有人
    if ($type == 1) {
        $response = $client->push()
            ->setPlatform(array('ios', 'android'))// 推送的接收平台
            ->addAllAudience()// 给所有人
//                ->setNotificationAlert('Hello, JPush')
            ->iosNotification(
                array(
                    "title" => $title,
//                        "subtitle" => "JPush Subtitle" ,
                    "body" => $content
                ),
                array(
                    'sound' => 'sound',
                    'badge' => 1,
                    'extras' => [
                        'news_info' => $news_info
                    ]
                )
            )
            ->androidNotification($title, [
                'title' => $content,
                'extras' => [
                    'news_info' => $news_info
                ]
            ])
            ->options(array(
                'apns_production' => false, // 测试环境
            ))
            ->send();
    } else {
        try {
            $response = $client->push()
                ->setPlatform(array('ios', 'android'))// 推送的接收平台
                ->addAlias($ids)// 别名推送
                ->setNotificationAlert($content)
                ->options(array(
                    'apns_production' => false, // 测试环境
                ))
                ->send();
        } catch (\Exception $e) {
            trace($e, 'error');
        }
    }
}

/*
 * 公用导出
 * @param string $body table体
 * @param string $headTitle 表头
 * @param string $filename 文件名
 * @param string $date 日期
 * */
function usePublicExport($body, $headTitle, $filename, $date)
{
    if (!empty($date)) {
        $date = "<div style=\"width: 800px;height:22px;display: block\">日期: " . $date . "</div>";
    } else {
        $date = '';
    }
    $str = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\r\nxmlns=\"http://www.w3.org/TR/REC-html40\">\r\n<head>\r\n<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\r\n</head>\r\n<body>";
    $str .= "<div style=\"width: 800px;height:30px;text-align: center;font-size: 20px;font-weight: bold\">$headTitle</div>
    $date";
    $str .= "<br>";
    $str .= "<table border='1' style='width: 800px' text-align='center'>";
    $str .= $body;
    $str .= "</table>";
    $str .= "<br><br>";
    $str .= "</body></html>";
    header("Cache-Control:public");
    header("Pragma:public");
    header("Content-type: Content-type:application/vnd.ms-excel");
    header("Accept-Ranges: bytes");
    header("Content-Disposition:attachment; filename=" . $filename);
    header("Content-length:" . strlen($str));
    echo $str;
    exit;
}

/**
 * 对更新、新增数据、组合条件查询 的时候过滤参数使用
 * 例如：如果前端不携带某个字段 那么这个字段就不会被更新或新增 但是可以定义预备字段以及默认值  自动剔除无用的查询条件 主要是免除后端冗余代码
 * @param array $arr 定义好的字段数组 指针引用 无需接受return数据
 * @param array $post_arr 请求内的字段数组
 * @param boolean $filter 是否过滤null字段  true过滤（默认） false不过滤 只合并参数
 * 如果是搜索结合where函数的话 需要传false 对于新增 更新这块默认即可
 * @return void
 */
function parm_filter(array &$arr, array $post_arr, $filter = true)
{
    foreach ($arr as $k => $v) {
        if (array_key_exists($k, $post_arr)) {
            $arr[$k] = $post_arr[$k];
        }
    }

    //trace($v, 'error');
    if ($filter == false) {
        return;
    }
    $arr = array_filter($arr, function ($v, $k) {
        if (is_null($v)) {// ||写法避免字段不存在的情况出现导致报错
            return false;
        }
        return true;
    }, ARRAY_FILTER_USE_BOTH);

}


/**
 * 检查用户是否是会员
 * @param int $userId 用户id
 * return bool(true:是会员|false:非会员)
 * */
function checkUserMember($userId = 0)
{
    $userId = (int)$userId;
    if ($userId <= 0) {
        return false;
    }
    $where = [];
    $where['userId'] = $userId;
    $where['userFlag'] = 1;
    $expireTime = M('users')->where($where)->getField('expireTime');
    if (empty($expireTime)) {
        return false;
    }
    $date = date('Y-m-d H:i:s');
    if ($expireTime < $date) {
        return false;
    }
    return true;
}

/**
 * PHP计算区间段是否有交集
 * @param string $beginTime1 开始数据1
 * @param string $endTime1 结束数据1
 * @param string $beginTime2 开始数据2
 * @param string $endTime2 结束数据2
 * @return bool
 */
function checkSection($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '')
{
    $status = $beginTime2 - $beginTime1;
    if ($status > 0) {
        $status2 = $beginTime2 - $endTime1;
        if ($status2 > 0) {
            return false;
        } else {
            return true;
        }
    } else {
        $status2 = $endTime2 - $beginTime1;
        if ($status2 >= 0) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 商品规则,剔除无效商品,先这样写,后期使用辉辉的规则连贯查询
 * 缺陷:未重新计算商品总条数和分页
 * @param array $goods 商品信息
 * @param int $userId 用户id
 * @param array $validate <p>P:传*代表全都检验
 * string newPeople 新人专享
 * string goodsStock 商品库存
 * </p>
 * */
function goodsRules($goods = [], $userId, $validate = [])
{
    if (empty($goods)) {
        return $goods;
    }
    if (empty($validate)) {
        return $goods;
    }
    if (in_array('newPeople', $validate) || $validate == '*') {
        $returnData = handleNewPeople($goods, $userId);//剔除会员专享商品
    }
    return $returnData;
}

/**
 * 获取时间
 * @param string $dateCode 【today：今天|yesterday：昨天|lastSevenDays：最近7天|lastWeek:上周|thisWeek：本周|lastThirtyDays：最近30天|lastNinetyDays：最近90天|thisMonth：本月|thisYear：本年|自定义(例子:2020-05-01 - 2020-05-31)】
 * */
function getDateRules($dateCode)
{
    if (empty($dateCode)) {
        return ['startDate' => '', 'endDate' => ''];
    }
    $startDate = date('Y-m-d') . ' 00:00:00';
    $endDate = date('Y-m-d') . ' 23:59:59';
    $dateRules = ['today', 'yesterday', 'lastSevenDays', 'lastThirtyDays', 'lastNinetyDays', 'lastWeek', 'thisWeek', 'lastMonth', 'thisMonth', 'thisYear', 'thisYear', 'days300', 'days3', 'twoWeek'];
    if (in_array($dateCode, $dateRules)) {
        //300天内
        if ($dateCode == 'days300') {
            $yesterday = date("Y-m-d", strtotime("-300 day"));
            $startDate = $yesterday . ' 00:00:00';
            //$endDate = date('Y-m-d') . ' 23:59:59';
        }
        //今天
        if ($dateCode == 'today') {
            $startDate = date('Y-m-d') . ' 00:00:00';
            $endDate = date('Y-m-d') . ' 23:59:59';
        }
        //昨天
        if ($dateCode == 'yesterday') {
            $yesterday = date("Y-m-d", strtotime("-1 day"));
            $startDate = $yesterday . ' 00:00:00';
            $endDate = $yesterday . ' 23:59:59';
        }
        //近3天
        if ($dateCode == 'days3') {
            $yesterday = date("Y-m-d", strtotime("-3 day"));
            $startDate = $yesterday . ' 00:00:00';
            //$endDate = date('Y-m-d') . ' 23:59:59';
        }
        //最近7天
        if ($dateCode == 'lastSevenDays') {
            $lastday = date("Y-m-d", strtotime("-7 day"));
            $startDate = $lastday . ' 00:00:00';
            //$endDate = $endDate;
        }
        //上周
        if ($dateCode == 'lastWeek') {
            $startDateTime = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
            $startDate = date('Y-m-d', $startDateTime) . ' 00:00:00';
            $endDateTime = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
            $endDate = date('Y-m-d', $endDateTime) . ' 23:59:59';
        }
        //本周
        if ($dateCode == 'thisWeek') {
            $sdefaultDate = date("Y-m-d");
            $first = 1;
            $w = date('w', strtotime($sdefaultDate));
            $startDate = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')) . ' 00:00:00';
            $endDate = date('Y-m-d', strtotime("$startDate +6 days")) . " 23:59:59";
        }
        //近两周
        if ($dateCode == 'twoWeek') {
            $yesterday = date("Y-m-d", strtotime("-14 day"));
            $startDate = $yesterday . ' 00:00:00';
            //$endDate = $yesterday . ' 23:59:59';
        }
        //最近30天
        if ($dateCode == 'lastThirtyDays') {
            $lastday = date("Y-m-d", strtotime("-30 day"));
            $startDate = $lastday . ' 00:00:00';
            //$endDate = $endDate;
        }
        //最近90天
        if ($dateCode == 'lastNinetyDays') {
            $lastday = date("Y-m-d", strtotime("-90 day"));
            $startDate = $lastday . ' 00:00:00';
        }
        //上月
        if ($dateCode == 'lastMonth') {
            $startDate = date('Y-m-01 00:00:00', strtotime('-1 month'));
            $endDate = date("Y-m-d 23:59:59", strtotime(-date('d') . 'day'));
        }
        //本月
        if ($dateCode == 'thisMonth') {
            $startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
            $endDate = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('t'), date('Y')));
        }
        //本年
        if ($dateCode == 'thisYear') {
            $startDate = date('Y-m-d H:i:s', strtotime(date("Y", time()) . "-1" . "-1"));
            $endDate = date('Y-m-d H:i:s', strtotime(date("Y", time()) . "-12" . "-31"));
        }
    } else {
        //自定义时间
        $dataArr = explode(' - ', $dateCode);
        if (!empty($dataArr[0]) && !empty($dataArr[1])) {
            //时间区间
            $startDate = $dataArr[0] . ' 00:00:00';
            $endDate = $dataArr[1] . ' 23:59:59';
        } else {
            //具体日期
            $date = $dataArr[0];
            $date_explode = explode('-', $date);
            $count = count($date_explode);
            if ($count == 2) {
                $startDate = $date_explode[0] . '-' . $date_explode[1] . '-01 00:00:00';
                $endDate = $date_explode[0] . '-' . $date_explode[1] . '-31 23:59:59';
            } elseif ($count == 3) {
                $startDate = $date_explode[0] . '-' . $date_explode[1] . '-' . $date_explode[2] . ' 00:00:00';
                $endDate = $date_explode[0] . '-' . $date_explode[1] . '-' . $date_explode[2] . ' 23:59:59';
            } else {
                $startDate = $date_explode[0] . '-01-01 00:00:00';
                $endDate = $date_explode[0] . '-12-31 23:59:59';
            }
        }
    }
    $date = [];
    $date['startDate'] = $startDate;//开始时间
    $date['endDate'] = $endDate;//结束时间
    return $date;
}

/**
 * 获取指定日期段内每一天的日期
 * @param Date $startdate 开始日期
 * @param Date $enddate 结束日期
 * @return Array
 */
function getDateFromRange($startdate, $enddate)
{
    $stimestamp = strtotime($startdate);
    $etimestamp = strtotime($enddate);
    $days = (int)(($etimestamp - $stimestamp) / 86400 + 1);
    $date = array();
    for ($i = 0; $i < $days; $i++) {
        $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
    }
    return $date;
}

/*
 * 获取日期对应的星期
 * 参数$date为输入的日期数据，格式如：2018-6-22
 */
function getWeek($date)
{
    //强制转换日期格式
    $date_str = date('Y-m-d', strtotime($date));
    //封装成数组
    $arr = explode("-", $date_str);
    //参数赋值
    //年
    $year = $arr[0];
    //月，输出2位整型，不够2位右对齐
    $month = sprintf('%02d', $arr[1]);
    //日，输出2位整型，不够2位右对齐
    $day = sprintf('%02d', $arr[2]);
    //时分秒默认赋值为0；
    $hour = $minute = $second = 0;
    //转换成时间戳
    $strap = mktime($hour, $minute, $second, $month, $day, $year);
    //获取数字型星期几
    $number_wk = date("w", $strap);
    //自定义星期数组
    $weekArr = array("周日", "周一", "周二", "周三", "周四", "周五", "周六");
    //获取数字对应的星期
    return $weekArr[$number_wk];
}

/**
 * 高精度计算
 * @param float $num1
 * @param float $num2
 * @param varchar $type【bcadd：加法 |bcsub:减法|bcmul:乘法|bcdiv:除法|bcpow:乘方|bcmod:取模】
 * @param int $retain 保留位数
 * */
function bc_math($num1, $num2, $type, $retain = 14)
{
    switch ($type) {
        case 'bcadd':
            $res = bcadd($num1, $num2, $retain);
            break;
        case 'bcsub':
            $res = bcsub($num1, $num2, $retain);
            break;
        case 'bcmul':
            $res = bcmul($num1, $num2, $retain);
            break;
        case 'bcdiv':
            $res = bcdiv($num1, $num2, $retain);
            break;
        case 'bcpow ':
            $res = bcpow($num1, $num2, $retain);
            break;
        case 'bcmod  ':
            $res = bcmod($num1, $num2, $retain);
            break;
        default:
            $res = 0;
    }
    return $res;
}

/**
 * 下单扣除商品的库存
 * @param array $order_goods_info 订单商品信息 PS:包含(商品基本信息+购物车信息)
 * @param object $trans
 * @return array
 * */
function reduceGoodsStockByRedis(array $order_goods_info = array(), $trans = null)
{
    if (empty($trans)) {
        $m = new Model();
        $m->startTrans();
    } else {
        $m = $trans;
    }
    $submit_model = new \V3\Model\SubmitOrderModel();
    $goods_id = (int)$order_goods_info['goodsId'];
    $goods_cnt = (float)$order_goods_info['goodsCnt'];//购买数量
    $buy_cnt = (float)$order_goods_info['goodsCnt'];//购买数量
    $sku_id = (int)$order_goods_info['skuId'];//商品skuId
    $goodsModule = new \App\Modules\Goods\GoodsModule();
    $goods_result = (array)$goodsModule->getGoodsInfoById($goods_id, 'goodsName,goodsStock,weightG,SuppPriceDiff,selling_stock', 2);
    if (!isset($order_goods_info['SuppPriceDiff'])) {
        $order_goods_info['SuppPriceDiff'] = $goods_result['SuppPriceDiff'];
    }
    $supp_price_diff = (int)$order_goods_info['SuppPriceDiff'];//-1:标品 1:非标品
    if (empty($goods_id) || empty($goods_cnt)) {
        $m->rollback();
        return returnData(false, -1, 'error', '参数不全');
    }
    vendor('RedisLock.RedisLock');
    $redis = new \Redis;
    $redis->connect(C('redis_host1'), C('redis_port1'));
    //$redis->connect('127.0.0.1',6379);
    $redisLock = \RedisLock::getInstance($redis);
    //判断商品类型---start PS:后续新增的商品类型加在这里
    $goods_type = (int)$order_goods_info['goods_type'];//(1:普通商品 2:限量商品 3:限时商品) PS:通过验证后的类型,不要和商品信息中的类型混为一谈
    $prefix = '';
    if (empty($goods_result)) {
        $m->rollback();
        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品【' . $order_goods_info['goodsName'] . '】信息有误');
    }
    //废除包装系数
//    $weight = (float)$goods_result['weightG'];
    if ($goods_type == 1) {
        //普通商品 即:非活动商品
        if (empty($sku_id)) {
            //无规格
            $key = 'goods:' . $goods_id;
//            $goods_stock = (float)$goods_result['goodsStock'];
            $goods_stock = (float)$goods_result['selling_stock'];
        }
        if (!empty($sku_id)) {
            //有规格
            $key = 'goods_sku:' . $goods_id;
            $sku_system_result = $goodsModule->getSkuSystemInfoById($sku_id, 2);
            if (empty($sku_system_result)) {
                $m->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品【' . $order_goods_info['goodsName'] . '】sku信息有误');
            }
//            $goods_stock = $sku_system_result['skuGoodsStock'];
            $goods_stock = $sku_system_result['selling_stock'];
//            $weight = (float)$sku_system_result['weigetG'];
        }
    }
    //目前限时限量活动商品不支持规格
    //$order_goods_info['goodsStock']在此前已被替换成了活动库存
    if ($goods_type == 2) {
        //限量活动商品 PS:通过活动校验最终确定为活动商品,不要和商品基本信息中的类型混为一谈
        $key = 'goods_limitBuy:' . $goods_id;
        $goods_stock = $order_goods_info['goodsStock'];
        $prefix = '限量活动商品';
    }
    if ($goods_type == 3) {
        //限时活动商品 PS:通过活动校验最终确定为活动商品,不要和商品基本信息中的类型混为一谈
        $key = 'goods_flashSale:' . $goods_id;
        $goods_stock = $order_goods_info['goodsStock'];
        $prefix = '限时活动商品';
    }
    $goods_stock = (float)$goods_stock;
    //判断商品类型---end PS:后续新增的商品类型加在这里
    //设置库存 判断库存锁
    $oj8k = $redisLock->lock($key, 10, 10);
    //number 商品剩余库存
    if ($oj8k) {
        $number = $goods_stock;
    }
    if (empty($number)) {
        $number = -1;
    }
    $goods_cnt_stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
    if ($supp_price_diff == 1) {
        //注:系统不在处理单位换算
        //如果是称重商品,将库存单位kg转为g
//        $number = bc_math($goods_stock, 1000, 'bcmul', 0);
//        $goods_cnt = (int)($goods_cnt_stock * 1000);
        $goods_cnt = $goods_cnt_stock;
    } else {
        //标品 PS:需要处理实际购买库存(包装系数)
//        $goods_cnt = (int)$buy_cnt;
//        $can_buy_num = (int)($goods_stock / $weight);
//        $number = $can_buy_num;
//        if ($buy_cnt > $can_buy_num) {
//            $m->rollback();
//            return returnData(false, -1, 'error', $prefix . '商品【' . $order_goods_info['goodsName'] . '】库存不足');
//        }
        $goods_cnt = (int)$buy_cnt;
        $can_buy_num = $goods_stock;
        $number = $can_buy_num;
        if ($buy_cnt > $can_buy_num) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $prefix . '商品【' . $order_goods_info['goodsName'] . '】库存不足');
        }
    }
    //获取请求id
    $number = (float)$number;
    $reqid = $redisLock->getReqId($key, (float)$goods_cnt * 1000, $number * 1000);//称重商品存的是g,标品存的是份(实际库存/包装系数)
    if (empty($reqid)) {
        $m->rollback();
        return returnData(false, ExceptionCodeEnum::FAIL, 'error', $prefix . '商品【' . $order_goods_info['goodsName'] . '】库存不足');
    }
    //业务逻辑代码
    if ($number < $goods_cnt) {
        $m->rollback();
        return returnData(false, ExceptionCodeEnum::FAIL, 'error', $prefix . '商品【' . $order_goods_info['goodsName'] . '】库存不足');
    }
    $res = $submit_model->reduceGoodsStock($goods_id, $sku_id, $buy_cnt, $order_goods_info, $m);//扣除商品库存
    if ($res['code'] != ExceptionCodeEnum::SUCCESS) {
        $m->rollback();
        return returnData(false, ExceptionCodeEnum::FAIL, 'error', $prefix . '商品【' . $order_goods_info['goodsName'] . '】库存修改失败');
    }
    $redisLock->releaseReqList($key, $reqid);//手动释放请求
    if (empty($trans)) {
        $m->commit();
    }
    return $res;
}

/**
 * 取消订单归还商品库存
 * @param int $id 订单商品表id
 * @param object $trans
 * @return array
 * */
function returnOrderGoodsStock($id, $trans = null)
{
//    $submitModel = D('V3/SubmitOrder');
    $submitModel = new \V3\Model\SubmitOrderModel();
    $res = $submitModel->returnOrderGoodsStock($id, $trans);//归还商品库存
    return $res;
}

/**
 *根据两点经纬度计算距离,X经度，Y纬度
 */
function getDistanceBtwP($lonA, $latA, $lonB, $latB)
{
    $radLng1 = $latA * M_PI / 180.0;
    $radLng2 = $latB * M_PI / 180.0;
    $a = $radLng1 - $radLng2;
    $b = ($lonA - $lonB) * M_PI / 180.0;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLng1) * cos($radLng2) * pow(sin($b / 2), 2))) * 6378.137;//返回单位为公里
    return $s;
}

//点PCx,PCy到线段PAx,PAy,PBx,PBy的距离
function getNearestDistance($PAx, $PAy, $PBx, $PBy, $PCx, $PCy)
{
    $a = getDistanceBtwP($PAy, $PAx, $PBy, $PBx);//经纬坐标系中求两点的距离公式
    $b = getDistanceBtwP($PBy, $PBx, $PCy, $PCx);//经纬坐标系中求两点的距离公式
    $c = getDistanceBtwP($PAy, $PAx, $PCy, $PCx);//经纬坐标系中求两点的距离公式
    if ($b * $b >= $c * $c + $a * $a) {
        return $c;
    }
    if ($c * $c >= $b * $b + $a * $a) {
        return $b;
    }
    $l = ($a + $b + $c) / 2;//周长的一半
    $s = sqrt($l * ($l - $a) * ($l - $b) * ($l - $c));//海伦公式求面积
    return 2 * $s / $a;
}

/**
 * @param $orderId
 * @param $reportType 1:销售|2:退款|3:采购
 * @param $rest 2:退款需要【订单投诉ID|退款金额】3:采购需要
 * 添加报表
 */
//function addReportForms($orderId = 0, $reportType = 0, $rest = array())
//{
//    $dateTime = date("Y-m-d");
//    $createTime = time();
//    $isRefund = -1;//是否退款[1:是|-1:否]
//    //销售
//    if ($reportType == 1) {
//        $goodsWhere = ["og.orderId" => $orderId];
//        $group = "og.goodsId";
//    }
//    //退款
//    if ($reportType == 2) {
//        $goodsWhere = ["og.orderId" => $orderId, 'woc.complainId' => $rest['complainId']];
//        $group = "woc.complainId";
//        $isRefund = 1;//是否退款[1:是|-1:否]
//    }
//    if ($reportType != 3) {
//        $field = "og.goodsId,og.goodsNums,og.goodsPrice,og.couponMoney,og.scoreMoney,(og.goodsNums * og.goodsPrice - og.couponMoney - og.scoreMoney) as goodsPaidPrice,";
//        $field .= "wg.goodsCatId1,wg.goodsCatId2,wg.goodsCatId3,wg.goodsUnit,wg.shopId";
//        $orderInfo = M('orders')->where(["orderId" => $orderId])->find();
//        $goodsInfo = M('order_goods og')
//            ->join('left join wst_goods wg ON wg.goodsId = og.goodsId')//商品基本信息
//            ->join('left join wst_order_complains woc ON woc.orderId = og.orderId')//订单投诉
//            ->where($goodsWhere)
//            ->field($field)
//            ->group($group)
//            ->select();
//    } else {
//        foreach ($rest['goodsInfo'] as $k => $v) {
//            $goodsDetail = M('jxc_goods')->where(['goodsId' => $v['goodsId']])->find();
//            $rest['goodsInfo'][$k]['goodsCatId1'] = $goodsDetail['goodsCat1'];
//            $rest['goodsInfo'][$k]['goodsCatId2'] = $goodsDetail['goodsCat2'];
//            $rest['goodsInfo'][$k]['goodsCatId3'] = $goodsDetail['goodsCat3'];
//            $rest['goodsInfo'][$k]['shopId'] = $goodsDetail['shopId'];
//            $rest['goodsInfo'][$k]['goodsCostPrice'] = $v['unitPrice'];//进货价
//            $rest['goodsInfo'][$k]['goodsPaidPrice'] = $v['totalAmount'];//采购总金额-----商品实付金额【包含商品数量】
//        }
//    }
//    $where = [];
//    $where['reportDate'] = $dateTime;
//    $orderReport = M('order_report')->where($where)->find();
//    if (empty($orderReport)) {
//        //销售
//        if ($reportType == 1) {
//            $dateInfo = [];
//            $dateInfo['salesOrderNum'] = 1;//销售订单数量
//            $dateInfo['salesOrderMoney'] = $orderInfo['realTotalMoney'];//销售订单总金额
//            $dateInfo['practicalPrice'] = $orderInfo['totalMoney'];//实际订单价格---商品总价格--未进行任何折扣的总价格
//        }
//        //退款
//        if ($reportType == 2) {
//            $dateInfo = [];
//            $dateInfo['salesRefundOrderNum'] = 1;//订单退款数量
//            $dateInfo['salesRefundOrderMoney'] = $rest['refundFee'];//订单退款金额
//        }
//        //采购
//        if ($reportType == 3) {
//            $dateInfo = [];
//            $dateInfo['procureOrderNum'] = 1;//订单采购数量
//            $dateInfo['procureOrderMoney'] = $rest['dataAmount'];//订单采购金额
//        }
//        $dateInfo['shopId'] = $orderInfo['shopId'];//门店id
//        $dateInfo['reportDate'] = $orderInfo['createTime'];//订单创建时间
//        $dateInfo['createTime'] = date("Y-m-d H:i:s");//订单创建时间
//        $reportId = M('order_report')->add($dateInfo);
//        $orderReport['reportId'] = $reportId;
//    } else {
//        //销售--更新当天总报表信息
//        if ($reportType == 1) {
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('salesOrderMoney', $orderInfo['realTotalMoney']);
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('practicalPrice', $orderInfo['realTotalMoney']);
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('salesOrderNum');
//        }
//        //退款
//        if ($reportType == 2) {
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('salesRefundOrderMoney', $rest['refundFee']);
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('salesRefundOrderNum');
//        }
//        //采购
//        if ($reportType == 3) {
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('procureOrderMoney', $rest['dataAmount']);
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('procureOrderNum');
//        }
//        M('order_report')->where(['reportId' => $orderReport['reportId']])->save(['updateTime' => date("Y-m-d H:i:s")]);
//    }
//    if (!empty($orderReport['reportId'])) {
//        if ($reportType == 3) {//采购
//            $goodsInfo = $rest['goodsInfo'];
//        }
//        $purchaseCost = 0;//成本总金额
//        $goodsDate = [];
//        foreach ($goodsInfo as $v) {
//            $reportInfo = [];
//            $reportInfo['reportId'] = $orderReport['reportId'];//报表id
//            $reportInfo['reportDate'] = $orderInfo['createTime'];//订单创建时间
//            $reportInfo['userId'] = (int)$orderInfo['userId'];//用户id
//            $reportInfo['orderId'] = (int)$orderInfo['orderId'];//订单id
//            $reportInfo['goodsId'] = $v['goodsId'];//商品id
//            $reportInfo['shopId'] = $v['shopId'];
//            $reportInfo['goodsCatId1'] = $v['goodsCatId1'];
//            $reportInfo['goodsCatId2'] = $v['goodsCatId2'];
//            $reportInfo['goodsCatId3'] = $v['goodsCatId3'];
//            $reportInfo['goodsPrice'] = $v['goodsPrice'];//商品价格
//            $reportInfo['goodsNums'] = $v['goodsNums'];//商品数量
//            $reportInfo['goodsCostPrice'] = $v['goodsUnit'];//进货价
//            $reportInfo['goodsPaidPrice'] = $v['goodsPaidPrice'];//商品实付金额【包含商品数量】
//            $reportInfo['isPos'] = $orderInfo['payType'];//1:线上|2:线下[默认1]
//            $reportInfo['isRefund'] = $isRefund;//是否退款[1:是|-1:否]
//            $reportInfo['createTime'] = $createTime;//时间戳
//            //退款
//            if ($reportType == 2) {
//                $reportInfo['refundFee'] = $rest['refundFee'];//退款金额
//            }
//            //采购
//            if ($reportType == 3) {
//                $reportInfo['reportDate'] = $dateTime;//采购单创建时间
//                $reportInfo['otpId'] = (int)$rest['otpId'];//订单id
//                $reportInfo['shopId'] = (int)$rest['shopId'];
//                $reportInfo['goodsPrice'] = $v['unitPrice'];//商品采购价格
//                $reportInfo['goodsNums'] = $v['totalNum'];//商品数量
//                $reportInfo['goodsCostPrice'] = $v['goodsCostPrice'];//进货价
//                $reportInfo['goodsPaidPrice'] = $v['goodsPaidPrice'];//采购商品实付金额【包含商品数量】
//                $reportInfo['isPos'] = 1;//1:线上|2:线下[默认1]
//                $reportInfo['isPurchase'] = 1;//是否采购[1:是|-1:否]
//            }
//            $purchaseCost += $v['goodsUnit'] * $v['goodsNums'];//成本总金额
//            $goodsDate[] = $reportInfo;
//        }
//        M('sales_order_report')->addAll($goodsDate);
//        if ($reportType == 1) {
//            M('order_report')->where(['reportId' => $orderReport['reportId']])->setInc('purchaseCost', $purchaseCost);
//        }
//    }
//}

/**
 * PS:该方法由以上注释方法所来
 * 只针对线上数据
 * 添加报表 PS:目前该方法的作用只剩下创建报表日期的功能了,因为目前的线上报表是临时统计的
 * @param int $order_id 单据id
 * @param int $report_type 报表类型(1:销售|2:退款(用户申请售后-退款)|3:取消订单|4:采购|5:退货(用户申请售后-退货))
 * @param array $rest <p>
 * int complainId 退货id
 * float refundFee 退款金额
 * </p>
 * @param object $trans 用于统一事务
 * @return array
 */
function addReportForms($order_id = 0, $report_type = 0, $rest = array(), $trans = null)
{
    if (!in_array($report_type, array(1, 2, 3, 4, 5))) {
        return returnData(false, -1, 'error', '报表类型不正确');
    }
    $orders_service_module = new \App\Modules\Orders\OrdersServiceModule();
    $report_service_module = new \App\Modules\Report\ReportServiceModule();
    $orders_model = new \App\Models\OrdersModel();
    $order_goods_model = new \App\Models\OrderGoodsModel();
    $jxc_goods_model = new \App\Models\JxcGoodsModel();
    $order_report_table = M('order_report');//报表统计总表
    $sales_order_report_table = M('sales_order_report');//订单销售统计表
    $coupons_tab = M('coupons');
    $report_date = date("Y-m-d");
    $create_time = date('Y-m-d H:i:s');
    $update_time = date('Y-m-d H:i:s');
    $is_refund = -1;//是否退款[1:是|-1:否]
    //销售
    if ($report_type == 1) {
        $goods_where = array(
            'og.orderId' => $order_id
        );
        $group = 'og.goodsId';
    }
    //退款(包含取消订单和退货)
    if ($report_type == 2 || $report_type == 3 || $report_type == 5) {
        $goods_where = array(
            'og.orderId' => $order_id,
        );
        $group = 'og.goodsId';
        if ($report_type == 2 || $report_type == 5) {
            if ($report_type == 2) {
                $is_refund = 1;//是否退款[1:是|-1:否]
            }
            $goodsDate['woc.complainId'] = $rest['complainId'];
            $group = "woc.complainId";
            $goods_where['woc.complainId'] = $rest['complainId'];
        }
    }
    if ($report_type != 4) {
        $field = "og.goodsId,og.goodsNums,og.goodsPrice,og.couponMoney,og.scoreMoney,(og.goodsNums * og.goodsPrice - og.couponMoney - og.scoreMoney) as goodsPaidPrice,";
        $field .= "wg.goodsCatId1,wg.goodsCatId2,wg.goodsCatId3,wg.goodsUnit,wg.shopId";
        $order_result = $orders_service_module->getOrderInfoById($order_id);
        if ($order_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, -1, 'error', '订单信息有误');
        }
        $order_info = $order_result['data'];
        //未支付订单没有记录
        if ($order_info['isPay'] != 1) {
            return returnData(false, -1, 'error', '该笔订单未支付');
        }
        if ($report_type == 2 || $report_type == 5) {
            $field .= ",woc.complainId";
            $goodsInfo = M('order_complains woc')
                ->join('left join wst_order_goods og ON woc.goodsId = og.goodsId and woc.skuId = og.skuId')//订单投诉
                ->join('left join wst_goods wg ON wg.goodsId = og.goodsId')//商品基本信息
                ->where($goods_where)
                ->field($field)
                ->group($group)
                ->select();
        } else {
            $goodsInfo = M('order_goods og')
                ->join('left join wst_goods wg ON wg.goodsId = og.goodsId')//商品基本信息
                ->where($goods_where)
                ->field($field)
                ->group($group)
                ->select();
        }
    } else {
        foreach ($rest['goodsInfo'] as $k => $v) {
            $goodsDetail = $jxc_goods_model->where(['goodsId' => $v['goodsId']])->find();
            $rest['goodsInfo'][$k]['goodsCatId1'] = $goodsDetail['goodsCat1'];
            $rest['goodsInfo'][$k]['goodsCatId2'] = $goodsDetail['goodsCat2'];
            $rest['goodsInfo'][$k]['goodsCatId3'] = $goodsDetail['goodsCat3'];
            $rest['goodsInfo'][$k]['shopId'] = $goodsDetail['shopId'];
            $rest['goodsInfo'][$k]['goodsCostPrice'] = $v['unitPrice'];//进货价
            $rest['goodsInfo'][$k]['goodsPaidPrice'] = $v['totalAmount'];//采购总金额-----商品实付金额【包含商品数量】
        }
    }
    $shopId = $order_info['shopId'];
    //采购
    if ($report_type == 4) {
        $shopId = $rest['shopId'];
    }
    $order_report_result = $report_service_module->getReportInfoByParams(array(
        'shopId' => $shopId,
        'reportDate' => $report_date
    ));
    if ($order_report_result['code'] != ExceptionCodeEnum::SUCCESS) {
        $order_report_result['data'] = array();
    }
    $order_report_info = $order_report_result['data'];
    if (empty($trans)) {
        $model = new Model();
        $model->startTrans();
    } else {
        $model = $trans;
    }
    $cash_money_total = 0;//余额支付统计
    $wxpay_money_total = 0;//微信支付统计
    $alipay_money_total = 0;//支付宝支付统计
    if ($order_info['payFrom'] == 1) {
        $alipay_money_total += (float)$order_info['realTotalMoney'];
    } elseif ($order_info['payFrom'] == 2) {
        $wxpay_money_total += (float)$order_info['realTotalMoney'];
    } elseif ($order_info['payFrom'] == 3) {
        $cash_money_total += (float)$order_info['realTotalMoney'];
    }
    $need_money_total = (float)$order_info['needPay'];//订单应收金额
    $goods_money_total = (float)$order_info['totalMoney'];//商品总金额
    $coupon_money_total = 0;//优惠券总金额
    if ((int)$order_info['couponId'] > 0) {
//        $coupon_info = $coupons_tab->where(array(
//            'dataFlag' => 1,
//            'couponId' => $order_info['couponId']
//        ))->find();
//        if ($coupon_info) {
//            $coupon_money_total += (float)$coupon_info['couponMoney'];
//        }
        $coupon_money_total = bc_math($coupon_money_total, (float)$order_info['coupon_use_money'], 'bcadd', 2);
    }
    $score_money_total = (float)$order_info['scoreMoney'];//积分抵扣金额
    $delivery_money_total = (float)$order_info['deliverMoney'];//配送费实收金额
    $use_score_total = 0;//积分抵扣统计
    if (empty($order_report_info)) {
        //新增
        //销售
        if ($report_type == 1) {
            $save = array(
                'salesOrderNum' => 1,//销售订单数量
                'salesOrderMoney' => $order_info['realTotalMoney'],//销售订单总金额
                'practicalPrice' => $order_info['realTotalMoney'],//实际订单价格 PS:销售订单总金额-退货金额
                'cash_money_total' => $cash_money_total,
                'wxpay_money_total' => $wxpay_money_total,
                'alipay_money_total' => $alipay_money_total,
                'need_money_total' => $need_money_total,
                'goods_money_total' => $goods_money_total,
                'coupon_money_total' => $coupon_money_total,
                'score_money_total' => $score_money_total,
                'delivery_money_total' => $delivery_money_total,
                'use_score_total' => $use_score_total,
            );
        }
        //申请售后-退款
        if ($report_type == 2) {
            $save = array(
                'salesRefundOrderMoney' => (float)$rest['refundFee'],//退款金额
            );
        }
        //取消订单
        if ($report_type == 3) {
            $save = array(
                'salesRefundOrderNum' => 1,//订单退款数量
                'salesRefundOrderMoney' => $rest['refundFee'],//退款金额
            );
        }
        //采购
        if ($report_type == 4) {
            $save = array(
                'procureOrderNum' => 1,//采购订单数量
                'procureOrderMoney' => $rest['dataAmount'],//采购订单总金额
            );
        }
        //申请售后-退货
        if ($report_type == 5) {
            $save = array(
                'salesRefundOrderNum' => 1,//订单退单数量
            );
        }
        $save['shopId'] = (int)$shopId;//门店id
        $save['reportDate'] = $report_date;
        $save['createTime'] = $create_time;
        $report_id = $order_report_table->add($save);
        if (!$report_id) {
            $model->rollback();
            return returnData(false, -1, 'error', '报表添加失败');
        }
        $order_report_info['reportId'] = $report_id;
    } else {
        //修改
        $report_id = $order_report_info['reportId'];
        //销售--更新当天总报表信息
        if ($report_type == 1) {
            $save = array(
                'salesOrderMoney' => bc_math($order_info['realTotalMoney'], $order_report_info['salesOrderMoney'], 'bcadd', 2),//销售订单总金额
                'practicalPrice' => bc_math($order_info['realTotalMoney'], $order_report_info['practicalPrice'], 'bcadd', 2),//实际订单价格 PS:销售订单总金额-退货金额
                'salesOrderNum' => bc_math(1, $order_report_info['salesOrderNum'], 'bcadd', 0),//销售订单数量
                'cash_money_total' => bc_math($order_report_info['cash_money_total'], $cash_money_total, 'bcadd', 2),//余额支付统计
                'wxpay_money_total' => bc_math($order_report_info['wxpay_money_total'], $wxpay_money_total, 'bcadd', 2),//微信支付统计
                'alipay_money_total' => bc_math($order_report_info['alipay_money_total'], $alipay_money_total, 'bcadd', 2),//支付宝支付统计
                'need_money_total' => bc_math($order_report_info['need_money_total'], $need_money_total, 'bcadd', 2),//订单应收金额统计
                'goods_money_total' => bc_math($order_report_info['goods_money_total'], $goods_money_total, 'bcadd', 2),//商品总金额统计
                'coupon_money_total' => bc_math($order_report_info['coupon_money_total'], $coupon_money_total, 'bcadd', 2),//优惠券总金额统计
                'score_money_total' => bc_math($order_report_info['score_money_total'], $score_money_total, 'bcadd', 2),//积分抵扣总金额统计
                'delivery_money_total' => bc_math($order_report_info['delivery_money_total'], $delivery_money_total, 'bcadd', 2),//配送费实收金额统计
                'use_score_total' => bc_math($order_report_info['use_score_total'], $use_score_total, 'bcadd', 2),//积分抵扣统计
            );
        }
        //申请售后-退款
        if ($report_type == 2) {
            $save = array(
                'salesRefundOrderMoney' => bc_math($order_report_info['salesRefundOrderMoney'], $rest['refundFee'], 'bcadd', 2)
            );
        }
        //取消订单
        if ($report_type == 3) {
            $refund_num = 1;//退单数量
            $save = array(
                'salesRefundOrderNum' => bc_math($order_report_info['salesRefundOrderNum'], $refund_num, 'bcadd', 0),//退单数量
                'salesRefundOrderMoney' => bc_math($order_report_info['salesRefundOrderMoney'], $rest['refundFee'], 'bcadd', 2)//退款金额
            );
        }
        //采购
        if ($report_type == 4) {
            $sales_Info = $sales_order_report_table->where(array(
                'otpId' => $rest['otpId'],//采购单id
                'isPurchase' => 1,//是否采购[1:是|-1:否]
            ))->find();
            $procureOrderNum = 0;
            if (empty($sales_Info)) {
                $procureOrderNum = 1;
            }
            $save = array(
                'procureOrderNum' => bc_math($order_report_info['procureOrderNum'], $procureOrderNum, 'bcadd', 0),//退单数量
                'procureOrderMoney' => bc_math($order_report_info['procureOrderMoney'], $rest['dataAmount'], 'bcadd', 2)
            );

        }
        //申请售后-退货
        if ($report_type == 5) {
            $sales_Info = $sales_order_report_table->where(array(
                'orderId' => $order_id,
                'is_return_goods' => 1,
            ))->find();
            if (empty($sales_Info)) {
                $refund_num = 1;
                $save = array(
                    'salesRefundOrderNum' => bc_math($order_report_info['salesRefundOrderNum'], $refund_num, 'bcadd', 0),//退单数量
                );
            }
        }
        $save['updateTime'] = $update_time;
        $save_res = $order_report_table->where(
            array(
                'reportId' => $report_id
            )
        )->save($save);
        if ($save_res === false) {
            $model->rollback();
            return returnData(false, -1, 'error', '报表更新失败');
        }
    }
    if (!empty($order_report_info['reportId'])) {
        //统计总表参与运费统计,订单销售明细表不参与运费统计
        if ($report_type == 4) {//采购
            $goodsInfo = $rest['procureOrderList'];
            $order_info['createTime'] = $create_time;
        }
        $purchaseCost = 0;//成本总金额
        $goodsDate = [];
        foreach ($goodsInfo as $v) {
            $report_info = [];
            $report_info['reportId'] = $order_report_info['reportId'];//报表id
            $report_info['reportDate'] = $order_info['createTime'];//订单创建时间
            $report_info['userId'] = (int)$order_info['userId'];//用户id
            $report_info['orderId'] = (int)$order_info['orderId'];//订单id
            $report_info['goodsId'] = (int)$v['goodsId'];//商品id
            $report_info['shopId'] = (int)$v['shopId'];
            $report_info['goodsCatId1'] = (int)$v['goodsCatId1'];
            $report_info['goodsCatId2'] = (int)$v['goodsCatId2'];
            $report_info['goodsCatId3'] = (int)$v['goodsCatId3'];
            $report_info['goodsPrice'] = (float)$v['goodsPrice'];//商品价格
            $report_info['goodsNums'] = (int)$v['goodsNums'];//商品数量
            $report_info['goodsCostPrice'] = (float)$v['goodsUnit'];//进货价
            $report_info['goodsPaidPrice'] = (float)$v['goodsPaidPrice'];//商品实付金额【包含商品数量】
            $report_info['isPos'] = (int)$order_info['payType'];//1:线上|2:线下[默认1]
            $report_info['isRefund'] = $is_refund;//是否退款[1:是|-1:否]
            $report_info['createTime'] = $create_time;
            //申请售后-退款
            if ($report_type == 2) {
                $report_info['refundFee'] = $rest['refundFee'];//退款金额
            }
            //取消订单
            if ($report_type == 3) {
                $report_info['is_cancel_order'] = 1;
                $report_info['isRefund'] = 1;
                $report_info['refundFee'] = $report_info['goodsPaidPrice'];//退款金额 PS:用于按分类查看,这里不参与运费计算
            }
            //采购
            if ($report_type == 4) {
                $report_info['reportDate'] = $v['createTime'];//采购单创建时间
                $report_info['otpId'] = (int)$rest['otpId'];//订单id
                $report_info['shopId'] = (int)$rest['shopId'];
                $report_info['goodsPrice'] = $v['sellPrice'];//商品采购价格
                $report_info['goodsNums'] = $v['totalNum'];//商品数量
                $report_info['goodsCostPrice'] = $v['sellPrice'];//进货价
                $report_info['goodsPaidPrice'] = $rest['dataAmount'];//采购商品实付金额【包含商品数量】
                $report_info['isPos'] = 1;//1:线上|2:线下[默认1]
                $report_info['isPurchase'] = 1;//是否采购[1:是|-1:否]
            }
            //申请售后-退货
            if ($report_type == 5) {
                $report_info['is_return_goods'] = 1;//是否退货(-1:否|1:是)
                $report_info['refundFee'] = $rest['returnAmount'];//退货金额
            }
            $purchaseCost += $v['goodsUnit'] * $v['goodsNums'];//成本总金额
            if (in_array($report_type, array(1, 2, 3, 5))) {
                //非采购
                $sales_where = array(
                    'orderId' => $report_info['orderId'],
                    'goodsId' => $report_info['goodsId']
                );
                $sales_order_report_info = $sales_order_report_table->where($sales_where)->find();
                if (empty($sales_order_report_info)) {
                    $goodsDate[] = $report_info;
                } else {
                    if ($report_type == 2) {
                        //申请售后-退款
                        $sales_update_res = $sales_order_report_table->where(array(
                            'id' => $sales_order_report_info['id']
                        ))->save(array(
                            'isRefund' => 1,
                            'refundFee' => $report_info['refundFee']
                        ));
                    } elseif ($report_type == 3) {
                        //取消订单
                        $sales_update_res = $sales_order_report_table->where(array(
                            'id' => $sales_order_report_info['id']
                        ))->save(array(
                            'isRefund' => 1,
                            'is_return_goods' => 1,
                            'is_cancel_order' => 1,
                            'refundFee' => $report_info['goodsPaidPrice']
                        ));
                    } elseif ($report_type == 4) {
                        //采购
                    } elseif ($report_type == 5) {
                        //申请售后-退货
                        $sales_update_res = $sales_order_report_table->where(array(
                            'id' => $sales_order_report_info['id']
                        ))->save(array(
                            'is_return_goods' => 1,
                        ));
                    }
                    if ($sales_update_res === false) {
                        $model->rollback();
                        return returnData(false, -1, 'error', '报表更新失败');
                    }
                }
            } else {
                //采购
                $goodsDate[] = $report_info;
            }
        }
        if (!empty($goodsDate)) {
            $add_res = $sales_order_report_table->addAll($goodsDate);
            if (!$add_res) {
                $model->rollback();
                return returnData(false, -1, 'error', '报表添加失败');
            }
            if ($report_type == 1) {
                $order_report_table->where(array(
                    'reportId' => $report_id
                ))->save(array(
                    'purchaseCost' => bc_math($order_report_info['purchaseCost'], $purchaseCost, 'bcadd', 2)
                ));
            }
        }
        if (empty($trans)) {
            $model->commit();
        }
        return returnData(true);
    }
}

/**
 * @param array $array
 * @param int $page
 * @param int $pageSize
 * @return mixed
 * 分页---数组
 */
function arrayPage($array = array(), $page = 1, $pageSize = 15)
{
    $count = count($array);
    $pageData = array_slice($array, ($page - 1) * $pageSize, $pageSize);
    $pager['total'] = $count;
    $pager['pageSize'] = $pageSize;
    $pager['start'] = ($page - 1) * $pageSize;
    $pager['root'] = (array)$pageData;
    $pager['totalPage'] = ($pager['total'] % $pageSize == 0) ? ($pager['total'] / $pageSize) : (intval($pager['total'] / $pageSize) + 1);
    $pager['currPage'] = $page;
    return $pager;
}

/**
 * @param $title
 * @param $excel_filename
 * @param $sheet_title
 * @param $letter
 * @param $objPHPExcel
 * @param $letterCount
 * @throws PHPExcel_Reader_Exception
 * @throws PHPExcel_Writer_Exception
 * 报表专用---导出Excel
 */
function exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount)
{
    // 设置excel文档的属性
    $objPHPExcel->getProperties()->setCreator("cyf")
        ->setLastModifiedBy("cyf Test")
        ->setTitle("goodsList")
        ->setSubject("Test1")
        ->setDescription("Test2")
        ->setKeywords("Test3")
        ->setCategory("Test result file");
    //设置excel工作表名及文件名
    // 操作第一个工作表
    $objPHPExcel->setActiveSheetIndex(0);
    //第一行设置内容
    $objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);//字体大小
    //合并
    $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
    //设置单元格内容加粗
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    //设置单元格内容水平居中
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    //设置excel的表头
    // 设置第一行和第一行的行高
//          $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
    //首先是赋值表头
    for ($k = 0; $k < $letterCount; $k++) {
        $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
        $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(15)->setBold(true);//字体大小
        //设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置每一列的宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
    }
    $objPHPExcel->getActiveSheet()->setTitle($title);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
    header('Cache-Control: max-age=0');
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
}

/**
 * 取消地推拉新奖励
 * @param int $orderId
 * */
function cancelPullAmount($orderId)
{
    $orderTab = M('orders');
    $orderInfo = $orderTab->where(['orderId' => $orderId])->find();
    $userInfo = M('users')->where(['userId' => $orderInfo['userId']])->find();
    $where = [];
    $where['isPay'] = 1;
    $where['orderToken'] = $orderInfo['orderToken'];
    $payCount = $orderTab->where($where)->count();
    $where = [];
    $where['isRefund'] = 1;
    $where['orderToken'] = $orderInfo['orderToken'];
    $refundCount = $orderTab->where($where)->count();
    if ($refundCount >= $payCount) {
        //如果用户取消订单则取消地推奖励
        $amountLogTab = M('pull_new_amount_log');//地推收益明细表
        $where = [];
        $where['userId'] = $userInfo['userId'];
        $where['orderToken'] = $orderInfo['orderToken'];
        $where['dataType'] = 2;
        $where['status'] = 0;
        $amountLogInfo = $amountLogTab->where($where)->find();
        if ($amountLogInfo) {
            $saveData = [];
            $saveData['status'] = 2;
            $saveData['updateTime'] = date('Y-m-d H:i:s', time());
            $amountLogTab->where(['id' => $amountLogInfo['id']])->save($saveData);
        }
    }
}

/*
 * 发放地推奖励
 * @param int $orderId 订单id
 * */
function grantPullNewAmount($orderId)
{
    $orderTab = M('orders');
    $orderInfo = $orderTab->where(['orderId' => $orderId])->find();
    if (empty($orderInfo)) {
        return false;
    }
    $usersTab = M('users');
    $userInfo = $usersTab->where(['userId' => $orderInfo['userId']])->find();
    $invitationLogTab = M('distribution_invitation invitation');
    $where = [];
    $where['invitation.userPhone'] = $userInfo['userPhone'];
    $field = 'invitation.id,invitation.userId,invitation.userPhone,invitation.dataType';
    $field .= ',users.balance';
    $invitationInfo = $invitationLogTab
        ->join("left join wst_users users on users.userId=invitation.userId")
        ->field($field)
        ->where($where)
        ->find();
    if (empty($invitationInfo)) {
        return false;
    }
    if ($invitationInfo['dataType'] == 2) {
        //邀请人开启了拉新权限
        $amountLogTab = M('pull_new_amount_log');//地推收益明细表
        $where = [];
        $where['userId'] = $userInfo['userId'];
        $where['orderToken'] = $orderInfo['orderToken'];
        $where['dataType'] = 2;
        $where['status'] = 0;
        $amountLogInfo = $amountLogTab->where($where)->find();
        if (empty($amountLogInfo)) {
            return false;
        }
        $saveData = [];
        $saveData['status'] = 1;
        $saveData['updateTime'] = date('Y-m-d H:i:s', time());
        $updateAmountLogRes = $amountLogTab->where(['id' => $amountLogInfo['id']])->save($saveData);
        if (!$updateAmountLogRes) {
            return false;
        }
        //更新用户余额并记录余额变动日志
        $balanceLog = M('user_balance')->add(array(
            'userId' => $invitationInfo['userId'],
            'balance' => $amountLogInfo['amount'],
            'dataSrc' => 1,
            'orderNo' => '',
            'dataRemarks' => "拉新奖励-用户成功下单",
            'balanceType' => 1,
            'createTime' => date('Y-m-d H:i:s', time()),
            'shopId' => 0
        ));
        if (!$balanceLog) {
            return false;
        }
        $userSave = [];
        $userSave['balance'] = $invitationInfo['balance'] + $amountLogInfo['amount'];
        $updateUser = M('users')->where(['userId' => $invitationInfo['userId']])->save($userSave);
        if (!$updateUser) {
            return false;
        }
        return true;
    }
}

/**
 * 返回称重商品价格
 * @param int $goodsId 商品id
 * @param float $weight 称重重量
 * @param float $goodsPrice 自定义商品价格
 * */
function mathWeightPrice($goodsId, $weight, $goodsPrice = 0)
{
    $goodsTab = M('goods');
    $where = [];
    $where['goodsId'] = $goodsId;
    $where['goodsFlag'] = 1;
    $goodsInfo = $goodsTab->where($where)->field('goodsId,goodsName,shopPrice,weightG,SuppPriceDiff')->find();
    $goodsInfo['shopPrice'] = !empty($goodsPrice) ? $goodsPrice : $goodsInfo['shopPrice'];
    if ($goodsInfo['SuppPriceDiff'] != 1) {
        $price = $goodsInfo['shopPrice'];
    } else {
        if ($weight == $goodsInfo['weightG']) {
            $price = $goodsInfo['shopPrice'];
        } else {
            $unitPirce = bc_math($goodsInfo['shopPrice'], $goodsInfo['weightG'], 'bcdiv', 4);//均价保留4位小数,精度尽可能高点
            $price = bc_math($unitPirce, $weight, 'bcmul', 2);
        }
    }
    return $price;
}

/**
 * 验证手机号格式是否正确
 * @param string $mobile 手机号
 * @return bool
 * */
function is_mobile($mobile = '')
{
    if (!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#", $mobile)) {
        return false;
    }
    return true;
}

/**
 * 求两个日期之间相差的天数
 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
 * @param string $day1
 * @param string $day2
 * @return number
 */
function diffBetweenTwoDays($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return ($second1 - $second2) / 86400;
}


/**
 *php对日期数组排序
 * */
function compareByTimeStamp($time1, $time2)
{
    if (strtotime($time1) < strtotime($time2)) {
        return 1;
    } elseif (strtotime($time1) > strtotime($time2)) {
        return -1;
    } else {
        return 0;
    }
}

/**
 * @param $deviceNo
 * @param $key
 * @return bool
 * 获取打印机的状态
 */
function getPrintsStatus($deviceNo, $key)
{
    $url = "http://open.printcenter.cn:8080/queryPrinterStatus";//查询打印机的状态
    $param = [];
    $param['deviceNo'] = $deviceNo;//打印机编号
    $param['key'] = $key;//打印密钥

    $result = niaocms_file_get_contents_post($url, $param);
    $resultInfo = json_decode($result, true);
    if ($resultInfo['responseCode'] == 4) {
        return false;
    }
    return true;
}

/**
 * @param $deviceNo
 * @param $key
 * @param $printContent
 * @param $times
 * @return bool|mixed
 * 打印小票
 */
function getPrintsOrders($deviceNo, $key, $printContent, $times)
{
    $url = "http://open.printcenter.cn:8080/addOrder";//打印内容
    $param = [];
    $param['deviceNo'] = $deviceNo;//打印机编号
    $param['key'] = $key;//打印密钥
    $param['printContent'] = $printContent;//打印内容
    $param['times'] = $times;//打印联数（同一订单，打印的次数，只对S2小票机有效，S1小票机和USB小票机使用打印机命令完成打印联数的设定）

    $result = niaocms_file_get_contents_post($url, $param);
    $resultInfo = json_decode($result, true);
    if ($resultInfo['responseCode'] != 0) {
        return false;
    }
    return $resultInfo;
}

/**
 * @param $deviceNo
 * @param $key
 * @param $orderIndex
 * @return bool
 * 查询订单是否打印成功
 */
function getPrintsOrdersStatus($deviceNo, $key, $orderIndex)
{
    $url = "http://open.printcenter.cn:8080/queryPrinterStatus";//查询订单是否打印成功
    $param = [];
    $param['deviceNo'] = $deviceNo;//打印机编号
    $param['key'] = $key;//打印密钥
    $param['orderindex'] = $orderIndex;//订单索引(orderindex,该值由接口getPrintsOrders)

    $result = niaocms_file_get_contents_post($url, $param);
    $resultInfo = json_decode($result, true);
    if ($resultInfo['responseCode'] != 0) {
        return false;
    }
    return true;
}

function iscOrE($str)
{
    if (preg_match("/[\x7f-\xff]/", $str)) {
        return 'C';
    } else {
        return 'E';
    }
}

function str_split_unicode($str, $l = 0)
{
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function numPriceStr($strArr, $count = 8)
{

    $strArr1 = str_split_unicode($strArr[0]);
    $strArr2 = str_split_unicode($strArr[1]);
    $strcount = (count($strArr1) / 2) + (count($strArr2) / 2);//总占用汉字长度
    // 获取剩余应占用的文字长度
    $tCount = $count - $strcount;
    //TODO:去掉不可占用的长度 并给空格占用

    //获取每个所占用的文字长度  以及每个应占用最多3个文字  那就是3+3+2=8
    $Ccount1 = (count($strArr1) / 2);
    $Ccount2 = (count($strArr2) / 2);

    // 计算字符串计数
    if ((count($strArr2) * 0.5) < 3) {
        $strArr2[0] = ' ' . $strArr2[0];
    }


    // 处理第一个字符占位 与 弥补不可被占用的一个空格替代
    // $strArr1[0] = "　".$strArr1[0];
    $str1 = '';
    for ($i = 0; $i < (3 - $Ccount1); $i++) {
        $str1 .= "　";
    }
    $strArr1[0] = $str1 . $strArr1[0];

    // 处理第二个字符串占位 与 弥补不可占用的空格替代
    // $strArr2[0] = "　".$strArr2[0];
    $str2 = ' ';
    for ($i = 0; $i < (3 - $Ccount2); $i++) {
        $str2 .= "　";
    }
    $strArr2[0] = $str2 . $strArr2[0];

    return [implode('', $strArr1), implode('', $strArr2)];


}

//获取中英文混合字符长度 PS：仅用于打印
function getUseTemplateStrLen($str)
{
    //这里针对性计算,按*号计算,一个中文字符相当于两个*号,一个英文字符算一个*号
    if (empty($str)) {
        return 0;
    }
    $len = bc_math(strlen($str), mb_strlen($str, 'UTF8'), 'bcadd', 2);
    $len = bc_math($len, 2, 'bcdiv', 2);
    return $len;
}

// 根据指定长度 自动分割生成字符串 例如名称一行8个文字16个英文、超过自动换行 不足自动补空格、数量英文或数字为4个 金额4个
function counStr($str, $len)
{
    // 得到字符串数组
    $strArr = str_split_unicode($str);
    $ce = 0;//混合计数 中文+1 英文+0.5

    $newStr = '';//新的字符串
    $newArr = [];//放到数组中
    for ($i = 0; $i < count($strArr); $i++) {
        // echo "ce变化:".$ce."\n";

        if (iscOrE($strArr[$i]) == 'C') {

            //如果+1大于要的 那就补空格与补换行？ 并重置为0 并跳过本次 跳过本次可能导致缺少文字 所以在拼接上
            if (($ce + 1) > $len) {
                // $newStr .= "　<BR>".$strArr[$i];
                $newStr .= "<BR>" . $strArr[$i];
                // array_push($newArr,$newStr);
                $ce = 1;
                continue;
            }

            $ce += 1;
            if ($ce != $len) {
                // echo $ce."\n";
                $newStr .= $strArr[$i];
            }
            if ($ce == $len) {
                $newStr .= $strArr[$i] . "<BR>";
                // array_push($newArr,$newStr);
                $ce = 0;
                // echo "满足中文";
                continue;
            }
        }
        if (iscOrE($strArr[$i]) == 'E') {
            //如果+0.5大于要的 那就补空格或补换行？
            if (($ce + 0.5) > $len) {
                // $newStr .= "　<BR>".$strArr[$i];
                $newStr .= "<BR>" . $strArr[$i];
                // array_push($newArr,$newStr);
                $ce = 0.5;
                continue;
            }

            $ce += 0.5;
            if ($ce != $len) {
                // echo $ce."\n";
                $newStr .= $strArr[$i];
            }
            if ($ce == $len) {
                $newStr .= $strArr[$i] . "<BR>";
                // array_push($newArr,$newStr);
                $ce = 0;
                // echo "满足英文";
                continue;
            }
        }
    }

    $newArr = explode('<BR>', $newStr);

    // return  $newStr;
    return $newArr;
}


/**
 * @param $params
 * @return string
 * 获取打印订单模板并替换数据
 * orderNo 订单号
 * orderTypeName 订单类型名称
 * deliverTypeName 配送方式名称
 * createTime 下单时间
 * requireTime 送达时间
 * orderGoodsList 订单商品列表
 *  goodsName 商品名
 *  skuSpecAttr sku属性名称与值 字符串
 *  remarks 商品智能备注
 *  goodsNums 商品数量
 *  goodsPrice 商品价格 非小计价格 需要模板自己计算
 *  goodsSn 商品条码
 * totalMoney 订单应付金额
 * realTotalMoney 订单实付金额
 * deliverMoney 订单配送费
 * userName 买家用户名
 * userPhone 买家手机号
 * userAddress 用户收货地址
 * orderRemarks 订单备注
 * shopName 店铺名
 * remark 小票描述
 *
 */
function getPrintsOrdersTemplate($params, $remark = "顾客联", $shopName = "小鸟CMS")
{
//    if (I("debug") == 1) {//汉辉更新后需要注释掉,代码保留,避免后期他人覆盖更新导致代码丢失
//        $content = getPrintsOrdersTemplate2($params, $remark, $shopName);
//        return $content;//
//    }

    //其他地方备参复杂杂乱 这里针对缺少的参数主动查询
    // 获取商品条码
//    $orderData = M("orders")->where("orderNo = ".$params['orderNo'])->find();
//    $orderGoods = M("order_goods")->where("orderId = ".$orderData['orderId'])->find();

// ,og.goodsId,og.orderId,og.skuId,og.skuSpecAttr,og.remarks
    foreach ($params['orderGoodsList'] as &$v) {
        if (empty($v['goodsId'])) {
            continue;
        }
        if (!empty($v['skuId'])) {
            $v['goodsSn'] = M("sku_goods_system")->where("skuId = " . $v['skuId'])->find()['skuBarcode'];
            continue;
        }

        //获取普通商品编码
        $v['goodsSn'] = M("goods")->where("goodsId = " . $v['goodsId'])->find()['goodsSn'];

    }


    $titleName = getNameCount('名称', 8, 'b');
    $titleCount = getNameCount('数量', 4, 'b');
    $titlePrice = getNameCount('金额', 2, 'b');
    $title = "{$titleName}{$titleCount}{$titlePrice}";
    $content = "";
    $content .= "<CB>{$shopName}</CB><BR>";
    $content .= "<L>{$remark}</L><BR>";
    $content .= "<L>订单号：{$params['orderNo']}</L><BR>";
    $content .= "订单类型：{$params['orderTypeName']}<BR>";
    $content .= "{$params['deliverTypeName']}<BR>";
    $content .= "下单时间：{$params['createTime']}<BR>";
    $content .= "<L>送达时间：{$params['requireTime']}前</L><BR>";
    $content .= "<C>********************************</C><BR>";

    $content .= "<C>{$title}</C><BR>";
    foreach ($params['orderGoodsList'] as $iiv) {
        // 完善商品标题

        $iiv['goodsName'] = $iiv['goodsName'] . $iiv['skuSpecAttr'] . $iiv['remarks'];

        $goodsName = counStr($iiv['goodsName'], 7);//自动换行处理
        $piaoStrArr = numPriceStr([$iiv['goodsNums'], $iiv['goodsPrice']]);
        $goodsNums = $piaoStrArr[0];
        $goodsPrice = $piaoStrArr[1];
        $content .= "  {$iiv['goodsSn']}<BR>";//条码
        if (count($goodsName) > 1) {
            foreach ($goodsName as $key => $item) {

                //$item = getNameCount($item,3,'b');

                if ($key == 0) {
                    // $item = str_replace('　',$item,'');
                    $goodsTitle = "{$item}<C>{$goodsNums}{$goodsPrice}</C>";
                    $content .= "{$goodsTitle}";
                    continue;
                }
                $goodsTitle = "  {$item}</C>";
                $content .= "{$goodsTitle}";
            }

        } else {
            //$goodsName[0] = getNameCount($goodsName[0],6,'b');
            $goodsTitle = "{$goodsName[0]}<C>{$goodsNums}{$goodsPrice}</C>";
            $content .= "{$goodsTitle}<BR>";
        }


    }
    $content .= "********************************<BR>";
    // $content .= "优惠金额：{$params['couponMoney']}元<BR>";
    $couponMoney = (float)$params['totalMoney'] + (float)$params['deliverMoney'] - (float)$params['realTotalMoney'];
    $content .= "优惠金额：{$couponMoney}元<BR>";
    $content .= "应付金额：{$params['totalMoney']}元<BR>";
    $content .= "实付金额：{$params['realTotalMoney']}元<BR>";
    $content .= "配送费：{$params['deliverMoney']}元<BR>";
    $content .= "--------------------------------<BR>";
    $content .= "买家信息<BR>";
    $content .= "{$params['userName']}<BR>";
    $content .= "{$params['userPhone']}<BR>";
    $content .= "{$params['userAddress']}<BR>";
    $content .= "<L>备注：{$params['orderRemarks']}</L><BR>";
    $content .= "<QR>{$params['orderNo']}</QR>";
    $content .= "<C>谢谢惠顾,欢迎下次光临！</C>";
    // print_r($content);
    // die;
    return $content;
}

function getPrintsOrdersTemplate2($params, $remark = "顾客联", $shopName = "小鸟CMS")
{
    $ordersModule = new \App\Modules\Orders\OrdersModule();
    $orderGoodsField = "og.goodsName,og.goodsId,og.orderId,og.skuId,og.skuSpecAttr,og.remarks,og.goodsNums,og.goodsPrice";
    $getOrderGoodsList = $ordersModule->getOrderGoodsList($params['orderId'], $orderGoodsField, 2);
    $params['orderGoodsList'] = $getOrderGoodsList;
    foreach ($params['orderGoodsList'] as &$v) {
        if (empty($v['goodsId'])) {
            continue;
        }
        if (!empty($v['skuId'])) {
            $v['goodsSn'] = M("sku_goods_system")->where("skuId = " . $v['skuId'])->find()['skuBarcode'];
            continue;
        }

        //获取普通商品编码
        $v['goodsSn'] = M("goods")->where("goodsId = " . $v['goodsId'])->find()['goodsSn'];

    }


    $orderNo = substr($params['orderNo'], -5);
    $content = "";
    $content .= "<CB>{$shopName}</CB><BR>";
    $content .= "{$remark}<BR>";
    $content .= "订单号：<DB>{$orderNo}</DB><BR>";
    $content .= "配送方式：{$params['deliverTypeName']}<BR>";
    $content .= "<L>收货人：{$params['userName']}</L><BR>";
    $content .= "<L>电话：{$params['userPhone']}</L><BR>";
    $content .= "收货地址：{$params['userAddress']}<BR>";


    $titleName = "<L>编码/品名</L>         ";
    $titleCount = "<L>数量x单价</L>(元)         ";
    $titlePrice = "<L>金额</L>(元)";
    $title = "{$titleName}{$titleCount}{$titlePrice}";
    //编码/品名　数量x单价(元)　金额(元)
    $tempTitleName = "编码/品名         ";
    $tempTitleNameLen = getUseTemplateStrLen($tempTitleName);
    $tempTitleCount = "数量x单价(元)         ";
    $tempTitleCountLen = getUseTemplateStrLen($tempTitleCount);
    $tempTitlePrice = "金额(元)";
    $tempTitlePriceLen = getUseTemplateStrLen($tempTitlePrice);
    $tempTitle = "{$tempTitleName}{$tempTitleCount}{$tempTitlePrice}";//最终的字符(排除打印机指令)
    $tempTitleLen = getUseTemplateStrLen($tempTitle); //最终的字符长度，后面商品间隔排版总长度为该值
    $content .= "<C>{$title}</C><BR>";
    $content .= "<C>************************************************</C><BR>";
    //编码/品名*********数量x单价(元)*********金额(元) 按48个*字符算

    $totalGoodsNum = 0;
    foreach ($params['orderGoodsList'] as $iiv) {
        $iiv['goodsNums'] = (float)$iiv['goodsNums'];
        $totalGoodsNum = bc_math($totalGoodsNum, $iiv['goodsNums'], 'bcadd', 3);
        $iiv['goodsPrice'] = formatAmount($iiv['goodsPrice']);
        // 完善商品标题
        $iiv['goodsName'] = $iiv['goodsName'] . $iiv['skuSpecAttr'] . $iiv['remarks'];
        $goodsNameArr = counStr($iiv['goodsName'], 24);//自动换行处理
        $goodsTitle = "";
        foreach ($goodsNameArr as $goodsName) {
            $goodsTitle .= "{$goodsName}<BR>";
        }
        $content .= $goodsTitle;
        //拼接编号,数量x单价,金额小计 注：临时修改，简单处理下即可，按着正常的操作模板正常显示即可，其他异常操作暂不处理（比如商品编码长度不规范等导致的模板显示异常）
        $currGoodsSn = "{$iiv['goodsSn']}";
        $currGoodsSnLen = getUseTemplateStrLen($currGoodsSn);
        if ($currGoodsSnLen < $tempTitleNameLen) {
            $currGoodsSn = $currGoodsSn . str_repeat(' ', ($tempTitleNameLen - $currGoodsSnLen));
            $currGoodsSnLen = getUseTemplateStrLen($currGoodsSn);
        }
        $currGoodsCount = "{$iiv['goodsNums']}x{$iiv['goodsPrice']}";
        $currGoodsCountLen = getUseTemplateStrLen($currGoodsCount);
        if (($currGoodsSnLen + $currGoodsCountLen) < ($tempTitleNameLen + $tempTitleCountLen)) {
            $currGoodsCount = $currGoodsCount . str_repeat(' ', ($tempTitleNameLen + $tempTitleCountLen) - ($currGoodsSnLen + $currGoodsCountLen));
            $currGoodsCountLen = getUseTemplateStrLen($currGoodsCount);
        }
        $currTotalGoodsAmount = bc_math($iiv['goodsNums'], $iiv['goodsPrice'], 'bcmul', 2);
        $currTotalGoodsAmountLen = getUseTemplateStrLen($currTotalGoodsAmount);
        if (($currGoodsSnLen + $currGoodsCountLen + $currTotalGoodsAmountLen) < ($tempTitleNameLen + $tempTitleCountLen + $tempTitlePriceLen)) {
            $currTotalGoodsAmount = str_repeat(' ', ($tempTitleNameLen + $tempTitleCountLen + $tempTitlePriceLen) - ($currGoodsSnLen + $currGoodsCountLen + $currTotalGoodsAmountLen)) . $currTotalGoodsAmount;
            $currTotalGoodsAmountLen = getUseTemplateStrLen($currTotalGoodsAmount);
        }
        if ($currTotalGoodsAmountLen > $tempTitlePriceLen) {
            $currTotalGoodsAmount = formatAmount($currTotalGoodsAmount, 0);
        }
        $content .= "<C><L>{$currGoodsSn}{$currGoodsCount}{$currTotalGoodsAmount}</L></C><BR>";
    }
    $totalGoodsNum = (float)$totalGoodsNum;
    $content .= "<C>************************************************</C><BR>";
    $content .= "销售：共" . count($params['orderGoodsList']) . "项" . "          合计数量：{$totalGoodsNum}<BR>";
    $content .= "应付金额：{$params['totalMoney']}元<BR>";
    $orderRow = M("orders")->where(array("orderId" => $params['orderId']))->find();
    if ((float)$orderRow['coupon_use_money'] > 0) {
        $content .= "优惠券抵扣：{$orderRow['coupon_use_money']}元<BR>";
    }
    if ((float)$orderRow['scoreMoney'] > 0) {
        $content .= "积分抵扣：{$orderRow['scoreMoney']}元<BR>";
    }
    if ((float)$params['deliverMoney'] > 0) {
        $content .= "配送费：{$params['deliverMoney']}元<BR>";
    }
    //支付来源[1:支付宝，2：微信,3:余额,4:货到付款]
    $payFrom = (int)$params['payFrom'];
    if ((float)$params['realTotalMoney'] > 0) {
        if ($payFrom == 1) {
            $content .= "支付宝支付：{$params['realTotalMoney']}元<BR>";
        } elseif ($payFrom == 2) {
            $content .= "微信支付：{$params['realTotalMoney']}元<BR>";
        } elseif ($payFrom == 3) {
            $content .= "余额支付：{$params['realTotalMoney']}元<BR>";
        } elseif ($payFrom == 4) {
            $content .= "货到付款：{$params['realTotalMoney']}元<BR>";
        } elseif ($payFrom == 5) {
            $content .= "云闪付：{$params['realTotalMoney']}元<BR>";
        }
    }
    $content .= "<L>实际金额：{$params['realTotalMoney']}元</L><BR>";
    $content .= "<C>************************************************</C><BR>";
    $shopRow = M("shops")->where(array("shopId" => $params['shopId']))->find();
    $content .= "{$shopRow['shopAddress']}<BR>";
    $content .= "服务热线：{$shopRow['shopTel']}<BR>";
    $content .= "下单时间：{$params['createTime']}<BR>";
    $content .= "<L>送达时间：{$params['requireTime']}前</L><BR>";
    $content .= "<L>备注：{$params['orderRemarks']}</L><BR>";
    $content .= "<BR>";
    $content .= "<C><L>谢谢惠顾　欢迎再次光临</L></C><BR>";
    $content .= "<QR>{$params['orderNo']}</QR><BR>";
    $content .= "<CB><L>汉辉到家30分钟快速到达</L></CB>";
    return $content;
}

/**
 * @param $name
 * @param $num
 * @return false|string
 * 小票打印-商品名称处理
 */
function getAfterFillName($name, $num)
{
    $name = array_iconv($name);//统一编码格式
    $str = '';//返回字符串初始化
    $name = mb_ereg_replace('(^(　| )+|(　| )+$)', '', $name);//过滤
    $oneCount = mb_strlen($name, 'UTF8');

    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $name, $chinese_str);
    $chineseStr = implode('', $chinese_str[0]);

    if (!empty($chineseStr)) {//中文
        $strnumb = mb_strlen($chineseStr, 'UTF8');
        $chineseCount = $strnumb * 3;
        $allCount = $chineseCount + ($oneCount - $strnumb) * 1;//剩余字符
    } else {
        $allCount = $oneCount * 1;
    }
    $a = floor($oneCount / 8);//获取几行 一行24个字符
    if ($a > 0) {
        for ($x = 0; $x < $a; $x++) {
            $goodsName = mb_substr($name, $x * 8, 8, 'utf-8');
            if (($a - $x) == 1) {
                $str .= $goodsName;
            } else {
                $str .= $goodsName . '<BR>';
            }
        }
    } else {
        $str .= $name;
    }
    $a1 = $oneCount % 8;//获取剩余个数

    if ($a1 > 0 && !empty($a)) {
        $b = mb_substr($name, $a * 8, $oneCount - ($a * 8), 'utf-8');
        $surplusCount = mb_strlen($b, 'UTF8');
        preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $b, $surplusStr);
        $surplusChineseStr = implode('', $surplusStr[0]);
        if (!empty($surplusChineseStr)) {//中文
            $strnumb = mb_strlen($surplusChineseStr, 'UTF8');
            $chineseCount = $strnumb * 3;
            $allCount = $chineseCount + ($surplusCount - $strnumb) * 1;//剩余字符
        } else {
            $allCount = $surplusCount * 1;
        }

        $lastCount = ((int)$num * 3 - $allCount) / 3;

        if ($lastCount < 0) {
            if ($lastCount == -8) {
                $lastCount = 1;
            } else {
                $lastCount = abs($lastCount);
            }
        }
        $str = mb_ereg_replace('(^(　| )+|(　| )+$)', '', $str);//过滤
        $str .= '<BR>' . $b . str_repeat('　', $lastCount);
    }

    if ($a1 <= 0 || $a <= 0) {
        $str = mb_ereg_replace('(^(　| )+|(　| )+$)', '', $str);//过滤
        $v = ($allCount - $a * 3) / 3;
        $lastCount = (int)$num - $v;
        if ($lastCount < 0) {
            if ($lastCount == -8) {
                $lastCount = 1;
            } else {
                $lastCount = abs($lastCount);
            }
        }
        $str .= str_repeat('　', $lastCount);
    }
    return $str;
}

/**
 * @param $name
 * @param int $num
 * @return string
 * 获取填充后的文字
 */
function getNameCount($name, $num)
{
    $name = array_iconv($name);
    $name = mb_ereg_replace('(^(　| )+|(　| )+$)', '', $name);
    $strnumb = mb_strlen($name, 'UTF8');
    $nameCount = $name . str_repeat('　', ((int)$num - $strnumb));
    return $nameCount;
}

function getNameCounts($name, $num)
{
    $str = '';
    $name = mb_ereg_replace('(^(　| )+|(　| )+$)', '', $name);
    $strnumb = mb_strlen($name, 'UTF8');
    $a = floor($strnumb / 8);//获取几行
    if ($a > 0) {
        for ($x = 0; $x < $a; $x++) {
            $goodsName = mb_substr($name, $x * 8, 8, 'utf-8');
            if (($a - $x) == 1) {
                $str .= $goodsName;
            } else {
                $str .= $goodsName . '<BR>';
            }
        }
    } else {
        $str .= $name;
    }
    $a1 = $strnumb % 8;//获取剩余个数

    if ($a1 > 0) {
        $b = mb_substr($name, $a * 8, $a1, 'utf-8');
        $str .= '<BR>' . $b . str_repeat('　', ((int)$num - $a1));
    }
    if ($a1 <= 0) {
        $str .= str_repeat('　', ((int)$num - 8));
    }
    return $str;
}


/**
 * @param $data
 * @param string $output 转换后的编码
 * @return array|string 数组
 * 对数据进行编码转换
 */
function array_iconv($data, $output = 'utf-8')
{
    $encode_arr = array('UTF-8', 'ASCII', 'GBK', 'GB2312', 'BIG5', 'JIS', 'eucjp-win', 'sjis-win', 'EUC-JP');
    $encoded = mb_detect_encoding($data, $encode_arr);
    if (!is_array($data)) {
        return mb_convert_encoding($data, $output, $encoded);
    } else {
        foreach ($data as $key => $val) {
            $key = array_iconv($key, $output);
            if (is_array($val)) {
                $data[$key] = array_iconv($val, $output);
            } else {
                $data[$key] = mb_convert_encoding($data, $output, $encoded);
            }
        }
        return $data;
    }
}

/**
 * @param $data
 * @return array
 * 二维数组多字段去重
 */
function deWeight($data)
{
    $res = [];
    foreach ($data as $key => $value) {
        //重新排序value
        ksort($value);
        //获取key ，判断是否存在的依据
        $key = implode("_", $value);
        //md5 为了防止字段内容过长特殊字符等
        $res[md5($key)] = $value;
    }
    //重置索引
    $res = array_values($res);
    return $res;
}

/**
 * 导出excel公用文件
 * @param string $title 标题
 * @param string $excel_filename 文件名
 * @param array $sheet_title 表头标题
 * @param array $letter 值
 * @param object $objPHPExcel
 * */
function exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel)
{
    $letterCount = count($letter);
    // 设置excel文档的属性
    $objPHPExcel->getProperties()->setCreator("cyf")
        ->setLastModifiedBy("cyf Test")
        ->setTitle("goodsList")
        ->setSubject("Test1")
        ->setDescription("Test2")
        ->setKeywords("Test3")
        ->setCategory("Test result file");
    //设置excel工作表名及文件名
    // 操作第一个工作表
    $objPHPExcel->setActiveSheetIndex(0);
    //第一行设置内容
    $objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);//字体大小
    //合并
    $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
    //设置单元格内容加粗
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    //设置单元格内容水平居中
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    //设置excel的表头
    // 设置第一行和第一行的行高
//          $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
    //首先是赋值表头
    for ($k = 0; $k < $letterCount; $k++) {
        $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
        $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(15)->setBold(true);//字体大小
        //设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置每一列的宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
    }
    $objPHPExcel->getActiveSheet()->setTitle($title);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
    header('Cache-Control: max-age=0');
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
}

/**
 * 中文排序
 * @param array $array
 * */
function utf8_array_asort(&$array)
{
    if (!isset($array) || !is_array($array)) {
        return false;
    }
    foreach ($array as $k => $v) {
        $array[$k] = iconv('UTF-8', 'GB2312', $v);
    }
    asort($array);
    foreach ($array as $k => $v) {
        $array[$k] = iconv('GB2312', 'UTF-8', $v);
    }
    return true;
}

/**
 * 格式化数字
 * @param number $num 数字
 * @param int $places 小数位
 * */
function sprintfNumber($num, $places = 2)
{
    if (!is_numeric($num)) {
        return 0;
    }
    $tt = $places + 1;
    $now_num = sprintf("%.{$places}f", substr(sprintf("%.{$tt}f", $num), 0, -1));//不进行四舍五入
    return $now_num;
}


/**
 *数字金额转换成中文大写金额的函数
 *String Int $num 要转换的小写数字或小写字符串
 *return 大写字母
 *小数位为两位
 **/
function num_to_rmb($num)
{
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2);
    //将数字转化为整数
    $num = $num * 100;
    if (strlen($num) > 10) {
        return "金额太大，请检查";
    }
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            //获取最后一位数字
            $n = substr($num, strlen($num) - 1, 1);
        } else {
            $n = $num % 10;
        }
        //每次将最后一位数字转化为中文
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        //去掉数字最后一位了
        $num = $num / 10;
        $num = (int)$num;
        //结束循环
        if ($num == 0) {
            break;
        }
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        //utf8一个汉字相当3个字符
        $m = substr($c, $j, 6);
        //处理数字中很多0的情况,每次循环去掉一个汉字“零”
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j - 3;
            $slen = $slen - 3;
        }
        $j = $j + 3;
    }
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c) - 3, 3) == '零') {
        $c = substr($c, 0, strlen($c) - 3);
    }
    //将处理的汉字加上“整”
    if (empty($c)) {
        return "零元整";
    } else {
        return $c . "整";
    }

}