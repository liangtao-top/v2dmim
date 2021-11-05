<?php
declare(strict_types=1);
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2021/4/21 13:49
// +----------------------------------------------------------------------

namespace com\sign;

use Exception;

/**
 * Class TLSSig
 * @package com\sign
 */
class UserSig
{

    private string $key;

    private string $sdk_app_id;

    /**
     * TLSSig constructor.
     * @param string $sdk_app_id
     * @param string $key \
     */
    public function __construct(string $sdk_app_id = '', string $key = '')
    {
        $this->sdk_app_id = $sdk_app_id;
        $this->key        = $key;
    }

    /**
     *【功能说明】用于签发 TRTC 和 IM 服务中必须要使用的 UserSig 鉴权票据
     *【参数说明】
     * @param string userid - 用户id，限制长度为32字节，只允许包含大小写英文字母（a-zA-Z）、数字（0-9）及下划线和连词符。
     * @param int expire - UserSig 票据的过期时间，单位是秒，比如 86400 代表生成的 UserSig 票据在一天后就无法再使用了。
     * @return string 签名字符串
     * @throws \Exception
     */
    public function genUserSig(string $userid, int $expire = 86400 * 180): string
    {
        return $this->__genSig($userid, $expire, '', false);
    }

    /**
     *【功能说明】
     * 用于签发 TRTC 进房参数中可选的 PrivateMapKey 权限票据。
     * PrivateMapKey 需要跟 UserSig 一起使用，但 PrivateMapKey 比 UserSig 有更强的权限控制能力：
     *  - UserSig 只能控制某个 UserID 有无使用 TRTC 服务的权限，只要 UserSig 正确，其对应的 UserID 可以进出任意房间。
     *  - PrivateMapKey 则是将 UserID 的权限控制的更加严格，包括能不能进入某个房间，能不能在该房间里上行音视频等等。
     * 如果要开启 PrivateMapKey 严格权限位校验，需要在【实时音视频控制台】=>【应用管理】=>【应用信息】中打开“启动权限密钥”开关。
     *【参数说明】
     * @param string $userid       - 用户id，限制长度为32字节，只允许包含大小写英文字母（a-zA-Z）、数字（0-9）及下划线和连词符。
     * @param int    $expire       - PrivateMapKey 票据的过期时间，单位是秒，比如 86400 生成的 PrivateMapKey 票据在一天后就无法再使用了。
     * @param int    $room_id      - 房间号，用于指定该 userid 可以进入的房间号
     * @param string $privilegeMap - 权限位，使用了一个字节中的 8 个比特位，分别代表八个具体的功能权限开关：
     *                             - 第 1 位：0000 0001 = 1，创建房间的权限
     *                             - 第 2 位：0000 0010 = 2，加入房间的权限
     *                             - 第 3 位：0000 0100 = 4，发送语音的权限
     *                             - 第 4 位：0000 1000 = 8，接收语音的权限
     *                             - 第 5 位：0001 0000 = 16，发送视频的权限
     *                             - 第 6 位：0010 0000 = 32，接收视频的权限
     *                             - 第 7 位：0100 0000 = 64，发送辅路（也就是屏幕分享）视频的权限
     *                             - 第 8 位：1000 0000 = 200，接收辅路（也就是屏幕分享）视频的权限
     *                             - privilegeMap == 1111 1111 == 255 代表该 userid 在该 roomid 房间内的所有功能权限。
     *                             - privilegeMap == 0010 1010 == 42  代表该 userid 拥有加入房间和接收音视频数据的权限，但不具备其他权限。
     * @return string
     * @throws \Exception
     */
    public function genPrivateMapKey(string $userid, int $expire, int $room_id, string $privilegeMap): string
    {
        $user_buf = $this->__genUserBuf($userid, $room_id, $expire, $privilegeMap, 0, '');
        return $this->__genSig($userid, $expire, $user_buf, true);
    }

    /**
     *【功能说明】
     * 用于签发 TRTC 进房参数中可选的 PrivateMapKey 权限票据。
     * PrivateMapKey 需要跟 UserSig 一起使用，但 PrivateMapKey 比 UserSig 有更强的权限控制能力：
     *  - UserSig 只能控制某个 UserID 有无使用 TRTC 服务的权限，只要 UserSig 正确，其对应的 UserID 可以进出任意房间。
     *  - PrivateMapKey 则是将 UserID 的权限控制的更加严格，包括能不能进入某个房间，能不能在该房间里上行音视频等等。
     * 如果要开启 PrivateMapKey 严格权限位校验，需要在【实时音视频控制台】=>【应用管理】=>【应用信息】中打开“启动权限密钥”开关。
     *【参数说明】
     * @param string $userid       - 用户id，限制长度为32字节，只允许包含大小写英文字母（a-zA-Z）、数字（0-9）及下划线和连词符。
     * @param int    $expire       - PrivateMapKey 票据的过期时间，单位是秒，比如 86400 生成的 PrivateMapKey 票据在一天后就无法再使用了。
     * @param string $room_str     - 房间号，用于指定该 userid 可以进入的房间号
     * @param string $privilegeMap - 权限位，使用了一个字节中的 8 个比特位，分别代表八个具体的功能权限开关：
     *                             - 第 1 位：0000 0001 = 1，创建房间的权限
     *                             - 第 2 位：0000 0010 = 2，加入房间的权限
     *                             - 第 3 位：0000 0100 = 4，发送语音的权限
     *                             - 第 4 位：0000 1000 = 8，接收语音的权限
     *                             - 第 5 位：0001 0000 = 16，发送视频的权限
     *                             - 第 6 位：0010 0000 = 32，接收视频的权限
     *                             - 第 7 位：0100 0000 = 64，发送辅路（也就是屏幕分享）视频的权限
     *                             - 第 8 位：1000 0000 = 200，接收辅路（也就是屏幕分享）视频的权限
     *                             - privilegeMap == 1111 1111 == 255 代表该 userid 在该 roomid 房间内的所有功能权限。
     *                             - privilegeMap == 0010 1010 == 42  代表该 userid 拥有加入房间和接收音视频数据的权限，但不具备其他权限。
     * @return string
     * @throws \Exception
     */
    public function genPrivateMapKeyWithStringRoomID(string $userid, int $expire, string $room_str, string $privilegeMap): string
    {
        $user_buf = $this->__genUserBuf($userid, 0, $expire, $privilegeMap, 0, $room_str);
        return $this->__genSig($userid, $expire, $user_buf, true);
    }

    /**
     * 验证签名
     * @param string $sig         签名内容
     * @param string $identifier  需要验证用户名，utf-8 编码
     * @param int    $init_time   返回的生成时间，unix 时间戳
     * @param int    $expire_time 返回的有效期，单位秒
     * @param string $error_msg   失败时的错误信息
     * @return boolean 验证是否成功
     */
    public function verifySig(string $sig, string $identifier, int &$init_time, int &$expire_time, string &$error_msg): bool
    {
        $user_buf = '';
        return $this->__verifySig($sig, $identifier, $init_time, $expire_time, $user_buf, $error_msg);
    }

    /**
     * 带 user_buf 验证签名。
     * @param string $sig         签名内容
     * @param string $identifier  需要验证用户名，utf-8 编码
     * @param int    $init_time   返回的生成时间，unix 时间戳
     * @param int    $expire_time 返回的有效期，单位秒
     * @param string $user_buf     返回的用户数据
     * @param string $error_msg   失败时的错误信息
     * @return boolean 验证是否成功
     */
    public function verifySigWithUserBuf(string $sig, string $identifier, int &$init_time,int &$expire_time, string &$user_buf, string &$error_msg): bool
    {
        return $this->__verifySig($sig, $identifier, $init_time, $expire_time, $user_buf, $error_msg);
    }

    /**
     * 用于 url 的 base64 encode
     * '+' => '*', '/' => '-', '=' => '_'
     * @param string $string 需要编码的数据
     * @return string 编码后的base64串，失败返回false
     * @throws \Exception
     */
    private function base64_url_encode(string $string): string
    {
        static $replace = array('+' => '*', '/' => '-', '=' => '_');
        $base64 = base64_encode($string);
        return str_replace(array_keys($replace), array_values($replace), $base64);
    }

    /**
     * 用于 url 的 base64 decode
     * '+' => '*', '/' => '-', '=' => '_'
     * @param string $base64 需要解码的base64串
     * @return string 解码后的数据，失败返回false
     * @throws \Exception
     */
    private function base64_url_decode(string $base64): string
    {
        static $replace = array('+' => '*', '/' => '-', '=' => '_');
        $string = str_replace(array_values($replace), array_keys($replace), $base64);
        $result = base64_decode($string);
        if ($result == false) {
            throw new Exception('base64_url_decode error');
        }
        return $result;
    }

    /**
     * 使用 hmac sha256 生成 sig 字段内容，经过 base64 编码
     * @param string $identifier       用户名，utf-8 编码
     * @param int    $curr_time        当前生成 sig 的 unix 时间戳
     * @param int    $expire           有效期，单位秒
     * @param string $base64_user_buf  base64 编码后的 userbuf
     * @param bool   $user_buf_enabled 是否开启 userbuf
     * @return string base64 后的 sig
     */
    private function hmacSha256(string $identifier, int $curr_time, int $expire, string $base64_user_buf, bool $user_buf_enabled): string
    {
        $content_to_be_signed = 'TLS.identifier:' . $identifier . "\n"
                                . 'TLS.sdk_app_id:' . $this->sdk_app_id . "\n"
                                . 'TLS.time:' . $curr_time . "\n"
                                . 'TLS.expire:' . $expire . "\n";
        if (true == $user_buf_enabled) {
            $content_to_be_signed .= 'TLS.userbuf:' . $base64_user_buf . "\n";
        }
        return base64_encode(hash_hmac('sha256', $content_to_be_signed, $this->key, true));
    }

    /**
     * TRTC业务进房权限加密串使用用户定义的userbuf
     * @brief 生成 userbuf
     * @param string $account        用户名
     * @param int    $dwAuthID       数字房间号
     * @param int    $dwExpTime      过期时间：该权限加密串的过期时间. 过期时间 = now+dwExpTime
     * @param string $dwPrivilegeMap 用户权限，255表示所有权限
     * @param int    $dwAccountType  用户类型, 默认为0
     * @param string $roomStr        字符串房间号
     * @return string  返回的userbuf
     */
    private function __genUserBuf(string $account, int $dwAuthID, int $dwExpTime, string $dwPrivilegeMap, int $dwAccountType, string $roomStr): string
    {
        //cVer  unsigned char/1 版本号，填0
        if ($roomStr == '')
            $userbuf = pack('C1', '0');
        else
            $userbuf = pack('C1', '1');

        $userbuf .= pack('n', strlen($account));
        //wAccountLen   unsigned short /2   第三方自己的帐号长度
        $userbuf .= pack('a' . strlen($account), $account);
        //buffAccount   wAccountLen 第三方自己的帐号字符
        $userbuf .= pack('N', $this->sdk_app_id);
        //dwSdkAppid    unsigned int/4  sdk_app_id
        $userbuf .= pack('N', $dwAuthID);
        //dwAuthId  unsigned int/4  群组号码/音视频房间号
        $expire  = $dwExpTime + time();
        $userbuf .= pack('N', $expire);
        //dwExpTime unsigned int/4  过期时间 （当前时间 + 有效期（单位：秒，建议300秒））
        $userbuf .= pack('N', $dwPrivilegeMap);
        //dwPrivilegeMap unsigned int/4  权限位
        $userbuf .= pack('N', $dwAccountType);
        //dwAccountType  unsigned int/4
        if ($roomStr != '') {
            $userbuf .= pack('n', strlen($roomStr));
            //roomStrLen   unsigned short /2   字符串房间号长度
            $userbuf .= pack('a' . strlen($roomStr), $roomStr);
            //roomStr   roomStrLen 字符串房间号
        }
        return $userbuf;
    }

    /**
     * 生成签名。
     * @param string $identifier       用户账号
     * @param int    $expire           过期时间，单位秒，默认 180 天
     * @param string $user_buf         base64 编码后的 userbuf
     * @param bool   $user_buf_enabled 是否开启 userbuf
     * @return string 签名字符串
     * @throws \Exception
     */
    private function __genSig(string $identifier, int $expire, string $user_buf, bool $user_buf_enabled): string
    {
        $curr_time      = time();
        $sig_array      = array(
            'TLS.ver'        => '2.0',
            'TLS.identifier' => $identifier,
            'TLS.sdk_app_id' => $this->sdk_app_id,
            'TLS.expire'     => $expire,
            'TLS.time'       => $curr_time,
        );
        $base64_userbuf = '';
        if (true == $user_buf_enabled) {
            $base64_userbuf           = base64_encode($user_buf);
            $sig_array['TLS.userbuf'] = $base64_userbuf;
        }
        $sig_array['TLS.sig'] = $this->hmacSha256($identifier, $curr_time, $expire, $base64_userbuf, $user_buf_enabled);
        $json_str_sig         = json_encode($sig_array);
        if ($json_str_sig === false) {
            throw new Exception('json_encode error');
        }
        $compressed = gzcompress($json_str_sig);
        if ($compressed === false) {
            throw new Exception('gzcompress error');
        }
        return $this->base64_url_encode($compressed);
    }

    /**
     * 验证签名。
     * @param string $sig         签名内容
     * @param string $identifier  需要验证用户名，utf-8 编码
     * @param int    $init_time   返回的生成时间，unix 时间戳
     * @param int    $expire_time 返回的有效期，单位秒
     * @param string $user_buf     返回的用户数据
     * @param string $error_msg   失败时的错误信息
     * @return boolean 验证是否成功
     */
    private function __verifySig(string $sig, string $identifier, int &$init_time, int &$expire_time, string &$user_buf, string &$error_msg): bool
    {
        try {
            $error_msg        = '';
            $compressed_sig   = $this->base64_url_decode($sig);
            $pre_level        = error_reporting(E_ERROR);
            $uncompressed_sig = gzuncompress($compressed_sig);
            error_reporting($pre_level);
            if ($uncompressed_sig === false) {
                throw new Exception('gzuncompress error');
            }
            $sig_doc = json_decode($uncompressed_sig);
            if ($sig_doc == false) {
                throw new Exception('json_decode error');
            }
            $sig_doc = ( array )$sig_doc;
            if ($sig_doc['TLS.identifier'] !== $identifier) {
                throw new Exception("identifier dosen't match");
            }
            if ($sig_doc['TLS.sdk_app_id'] != $this->sdk_app_id) {
                throw new Exception("sdk_app_id dosen't match");
            }
            $sig = $sig_doc['TLS.sig'];
            if ($sig == false) {
                throw new Exception('sig field is missing');
            }

            $init_time   = $sig_doc['TLS.time'];
            $expire_time = $sig_doc['TLS.expire'];

            $curr_time = time();
            if ($curr_time > $init_time + $expire_time) {
                throw new Exception('sig expired');
            }

            $userbuf_enabled = false;
            $base64_userbuf  = '';
            if (isset($sig_doc['TLS.userbuf'])) {
                $base64_userbuf  = $sig_doc['TLS.userbuf'];
                $user_buf         = base64_decode($base64_userbuf);
                $userbuf_enabled = true;
            }
            $sigCalculated = $this->hmacSha256($identifier, $init_time, $expire_time, $base64_userbuf, $userbuf_enabled);

            if ($sig != $sigCalculated) {
                throw new Exception('verify failed');
            }

            return true;
        } catch (Exception $ex) {
            $error_msg = $ex->getMessage();
            return false;
        }
    }

}
