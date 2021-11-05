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
// | Version: 2.0 2019-11-27 22:18
// +----------------------------------------------------------------------
namespace app\common;

/**
 * Class R
 * @package app\logic
 */
class R
{
    /**
     * 服务主动推送事件
     * @param string $event
     * @param mixed  $data
     * @return bool|string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/19 10:43
     */
    public static function e(string $event, mixed $data = ''): bool|string
    {
        return json_encode(['data' => $data, 'event' => $event], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回成功信息
     * @param string $route
     * @param mixed  $data
     * @param string $msg
     * @param array  $append
     * @return false|string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2019-11-28 11:46
     */
    public static function y(string $route, mixed $data, $msg = 'success', $append = []): bool|string
    {
        $result = [
            'route' => $route,
            'code'  => 1,
            'msg'   => $msg,
            'data'  => $data,
            'time'  => time(),
        ];
        return json_encode(array_merge($result, $append), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回失败信息
     * @param string $route
     * @param string $msg
     * @param string $data
     * @param array  $append
     * @return false|string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2019-11-28 11:46
     */
    public static function n(string $route, $msg = 'fail', $data = '', $append = []): bool|string
    {
        $result = [
            'route' => $route,
            'code'  => 0,
            'msg'   => $msg,
            'data'  => $data,
            'time'  => time(),
        ];
        return json_encode(array_merge($result, $append), JSON_UNESCAPED_UNICODE);
    }

}
