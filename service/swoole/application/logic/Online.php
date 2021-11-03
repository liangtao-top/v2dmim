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
// | Version: 2.0 2020/9/14 16:25
// +----------------------------------------------------------------------

namespace app\logic;

use com\redis\OnlinePool;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

class Online extends Logic
{

    /**
     * 用户连接加入
     * @param Ws     $ws
     * @param Frame  $frame
     * @param string $uuid
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/4/9 11:00
     * @noinspection PhpUnusedParameterInspection
     */
    public static function push(Ws &$ws, Frame &$frame, string $uuid): void
    {
        $redis = OnlinePool::instance()->get();
        if (!$redis->exists($frame->fd) || !$redis->exists($uuid)) {
            $device = $redis->get('device' . $frame->fd);
            $redis->hMSet($frame->fd, ['uuid' => $uuid, 'device' => $device, 'ip' => get_client_ip(), 'time' => time()]);
            $redis->sAdd($uuid, $frame->fd);
        }
        OnlinePool::instance()->put($redis);
    }

    /**
     * 用户连接移除
     * @param int $fd
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/8 17:35
     */
    public static function remove(int $fd): void
    {
        $redis = OnlinePool::instance()->get();
        $uuid  = $redis->get($fd);
        $redis->del($fd);
        $redis->sRem($uuid, $fd);
        OnlinePool::instance()->put($redis);
    }

    /**
     * 是否在线
     * @param string  $uuid
     * @param Ws|null $ws
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/12 11:31
     */
    public static function exist(string &$uuid, ?Ws &$ws = null): bool
    {
        $redis = OnlinePool::instance()->get();
        if (!$redis->exists($uuid)) {
            OnlinePool::instance()->put($redis);
            return false;
        }
        $fds = $redis->sMembers($uuid);
        if (!$fds) {
            $redis->del($uuid);
            OnlinePool::instance()->put($redis);
            return false;
        }
        if (is_null($ws)) {
            OnlinePool::instance()->put($redis);
            return true;
        } else {
            $list = [];
            foreach ($fds as $fd) {
                if ($ws->exist($fd) && $ws->isEstablished($fd)) {
                    $list[] = $fd;
                } else {
                    $redis->del($fd);
                    $redis->sRem($uuid, $fd);
                }
            }
            OnlinePool::instance()->put($redis);
            return count($list) > 0;
        }
    }

    /**
     * get
     * @param int $fd
     * @return array|null
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/12 10:50
     */
    public static function get(int $fd): ?array
    {
        $redis = OnlinePool::instance()->get();
        if (!$redis->exists($fd)) {
            OnlinePool::instance()->put($redis);
            return null;
        }
        $result = $redis->hMGet($fd, ['uuid', 'device', 'ip', 'time']);
        OnlinePool::instance()->put($redis);
        return $result ?: null;
    }

    /**
     * getFds
     * @param Ws     $ws
     * @param string $uuid
     * @return array
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 16:01
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public static function getFds(Ws &$ws, string $uuid): array
    {
        $redis = OnlinePool::instance()->get();
        if (!$redis->exists($uuid)) {
            OnlinePool::instance()->put($redis);
            return [];
        }
        $fds = $redis->sMembers($uuid);;
        if (!$fds) {
            $redis->del($uuid);
            OnlinePool::instance()->put($redis);
            return [];
        }
        $list = [];
        foreach ($fds as $fd) {
            if ($ws->exist($fd) && $ws->isEstablished($fd)) {
                $list[] = $fd;
            } else {
                $redis->del($fd);
                $redis->sRem($uuid, $fd);
            }
        }
        OnlinePool::instance()->put($redis);
        return $list;
    }

}
