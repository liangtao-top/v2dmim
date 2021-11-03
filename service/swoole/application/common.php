<?php
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Date: 2020/9/23 13:47
// +----------------------------------------------------------------------

/**
 * 将字符串转换成二进制
 * @param string $str
 * @param string $separator
 * @return string
 * @author TaoGe <liangtao.gz@foxmail.com>
 * @date   2021/4/14 10:27
 */
function str2bin(string $str, string $separator = ' '): string
{
    //1.列出每个字符
    $arr = preg_split('/(?<!^)(?!$)/u', $str);
    //2.unpack字符
    foreach ($arr as &$v) {
        $temp = unpack('H*', $v);
        $v    = base_convert($temp[1], 16, 2);
        unset($temp);
    }
    return join($separator, $arr);
}

/**
 * 将二进制转换成字符串
 * @param string $str
 * @param string $separator
 * @return string
 * @author TaoGe <liangtao.gz@foxmail.com>
 * @date   2021/4/14 10:27
 */
function bin2str(string $str, string $separator = ' '): string
{
    $arr = explode($separator, $str);
    foreach ($arr as &$v) {
        $v = pack("H" . strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
    }
    return join('', $arr);
}

/**
 * 系统非常规MD5加密方法
 * @param string $str 要加密的字符串
 * @param string $key
 * @return string
 */
function think_im_md5(string $str, string $key = 'ThinkUCenter'): string
{
    return '' === $str ? '' : md5(sha1($str) . $key);
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name    字符串
 * @param int    $type    转换类型
 * @param bool   $ucfirst 首字母是否大写（驼峰规则）
 * @return string
 */
function parse_name(string $name, int $type = 0, bool $ucfirst = true): string
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);

        return $ucfirst ? ucfirst($name) : lcfirst($name);
    }

    return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
}

/**
 * get_client_ip
 * @param int   $type
 * @param false $adv
 * @return mixed
 * @author TaoGe <liangtao.gz@foxmail.com>
 * @date   2021/4/13 14:00
 */
function get_client_ip($type = 0, $adv = false): mixed
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) {
        return $ip[$type];
    }
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 是否为json字符串
 * @param string $string
 * @return bool
 * @author TaoGe <liangtao.gz@foxmail.com>
 * @date   2019-12-03 11:49
 */
function is_json(string $string): bool
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Http服务成功返回
 * @param string $data
 * @param string $msg
 * @param array  $header
 * @return string
 * @author       TaoGe <liangtao.gz@foxmail.com>
 * @date         2020/9/23 13:47
 * @noinspection PhpUnusedParameterInspection
 */
function y($data = '', $msg = '', array $header = []): string
{
    $result = [
        'code' => 1,
        'msg'  => $msg ?: 'success',
        'data' => $data ?: '',
    ];
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * Http服务错误返回
 * @param string $msg
 * @param string $data
 * @param array  $header
 * @return string
 * @author       TaoGe <liangtao.gz@foxmail.com>
 * @date         2020/9/23 13:47
 * @noinspection PhpUnusedParameterInspection
 */
function n($msg = '', $data = '', array $header = []): string
{
    $result = [
        'code' => 0,
        'msg'  => $msg ?: 'error',
        'data' => $data ?: '',
    ];
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * 生成GUID
 */
function string_make_guid(): string
{
    // 1、去掉中间的“-”，长度有36变为32
    // 2、字母由“大写”改为“小写”
    if (function_exists('com_create_guid') === true) {
        return strtolower(str_replace('-', '', trim(com_create_guid(), '{}')));
    }

    return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

/**
 * 调试打印
 * @param mixed $value
 * @author TaoGe <liangtao.gz@foxmail.com>
 * @date   2019-12-20 15:01
 */
function lt(mixed $value)
{
    switch (gettype($value)) {
        case 'resource':
        case 'boolean':
            var_dump($value);
            break;
        case 'integer':
        case 'double':
        case 'string':
            echo($value);
            break;
        case 'object':
//                try {
//                    echo new Reflectionclass($value);
//                } catch (ReflectionException $e) {
//                    echo $e->getMessage();
//                }
//                break;
        case 'array':
            print_r($value);
            break;
        case 'NULL':
            echo 'NULL';
            break;
        default:
            echo 'unknown type';
    }
    echo PHP_EOL;
}
