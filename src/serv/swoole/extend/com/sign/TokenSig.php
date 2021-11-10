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
// | Version: 2.0 2021/4/21 16:35
// +----------------------------------------------------------------------

namespace com\sign;

/**
 * Class TokenSig
 * @package com\sign
 */
class TokenSig
{
    /**
     * verify
     * @param string $sign
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/12 10:29
     */
    public static function verify(string $sign): bool
    {
        $json_str = base64_decode($sign);
        if (!$json_str) {
            return false;
        }
        if (!is_json($json_str)) {
            return false;
        }
        $obj  = json_decode($json_str, true);
        $time = time();
        if ($obj['expire_time'] < $time) {
            return false;
        }
        $old_sign = $obj['sign'];
        unset($obj['sign']);
        if ($old_sign !== think_im_md5(json_encode($obj), $obj['slat'])) {
            return false;
        }
        return true;
    }


    /**
     * getDevice
     * @param string $sign
     * @return string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/12 10:29
     */
    public static function getDevice(string $sign): string
    {
        return json_decode(base64_decode($sign))->device;
    }

}
