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

use app\struct\ConvType;
use app\struct\Message;
use app\struct\MsgType;
use com\redis\OfflinePool;

/**
 * 离线队列
 * Class Offline
 * @package app\logic
 */
class Offline extends Logic
{
    // 指定队列键名
    const QUEUE_KEY = 'queue';


    /**
     * save
     * @param string      $uuid
     * @param int         $msg_id
     * @param int         $conv_typ
     * @param int         $msg_type
     * @param string|null $receive_id
     * @param int|null    $group_id
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/8 9:58
     */
    public static function save(string $uuid, int $msg_id, int $conv_typ, int $msg_type, ?string $receive_id = null, ?int $group_id = 0): void
    {
        $redis   = OfflinePool::instance()->get();
        $message = new Message();
        $message->setMsgID($msg_id);
        $message->setConvType(new ConvType($conv_typ));
        $message->setMsgType(new MsgType($msg_type));
        $message->setUserID($uuid);
        $message->setReceiveID($receive_id);
        $message->setGroupID($group_id);
        $redis->rPush(self::QUEUE_KEY, serialize($message));
        OfflinePool::instance()->put($redis);
    }



}
