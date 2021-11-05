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
// | Version: 2.0 2021/5/7 16:45
// +----------------------------------------------------------------------

namespace app\logic;

use think\Exception;
use app\struct\ConvType;
use app\struct\Conversation as Struct;
use com\redis\ConversationPool as Pool;

class Conversation
{

    /**
     * 哈希存储KEY
     */
    const HASH_KEY = 'conversation';

    /**
     * 创建会话ID
     * 规则：C2C 单聊组成方式为: String.format("c2c_%s", "userID")；群聊组成方式为: String.format("group_%s", "groupID")
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 18:42
     * @param \app\struct\ConvType $convType
     * @param string               $to
     * @param string|int           $to_id
     * @return string
     */
    public static function createID(ConvType $convType, string $to, string|int $to_id): string
    {
        return match ($convType) {
            ConvType::CONV_C2C => sprintf("c2c_%s", $to),
            ConvType::CONV_GROUP => sprintf("group_%s", $to),
            ConvType::CONV_SYSTEM => sprintf("system_%s", $to),
            default => new Exception('ConvType Exception')
        };
    }

    /**
     * 保存
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 18:48
     * @param \app\struct\ConvType $convType
     * @param string               $userId
     * @param int|string           $toUserId
     * @param array                $extend
     * @return \app\struct\Session
     */
    public static function create(ConvType $convType, string $userId, int|string $toUserId, array $extend = []): Struct
    {
        $extend['create_time'] = $extend['update_time'] = time();
        $session_id            = Struct::createID($convType, $userId, $toUserId);
        $struct                = new Struct($session_id, $convType, $userId, $toUserId);
        $struct->setAttributes($extend);
        $redis = SessionPool::instance()->get();
        $redis->hSet(self::HASH_KEY, $session_id, serialize($struct));
        SessionPool::instance()->put($redis);
        return $struct;
    }

    public static function update(ConvType $convType, string $userId, int|string $toUserId, array $extend = []): Struct
    {
        $extend['update_time'] = time();
        $session_id            = Struct::createID($convType, $userId, $toUserId);
        $struct                = new Struct($session_id, $convType, $userId, $toUserId);
        $struct->setAttributes($extend);
        $redis = SessionPool::instance()->get();
        $redis->hSet(self::HASH_KEY, $session_id, serialize($struct));
        SessionPool::instance()->put($redis);
        return $struct;
    }

    /**
     * 移除
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 18:48
     * @param \app\struct\ConvType $convType
     * @param string               $userId
     * @param int|string           $toUserId
     * @return bool
     */
    public static function remove(ConvType $convType, string $userId, int|string $toUserId = 0): bool
    {
        $redis      = SessionPool::instance()->get();
        $session_id = Struct::createID($convType, $userId, $toUserId);
        $res        = $redis->hDel(self::HASH_KEY, $session_id);
        SessionPool::instance()->put($redis);
        return $res !== false;
    }

    /**
     * 读取
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 18:53
     * @param \app\struct\ConvType $convType
     * @param string               $userId
     * @param int|string           $toUserId
     * @return array
     */
    public static function find(ConvType $convType, string $userId, int|string $toUserId = 0): array
    {
        $redis      = SessionPool::instance()->get();
        $session_id = Struct::createID($convType, $userId, $toUserId);
        $res        = $redis->hGet(self::HASH_KEY, $session_id);
        SessionPool::instance()->put($redis);
        if (!$res) return [];
        try {
            $session = unserialize($res);
        } catch (Exception) {
            $session = [];
        }
        return (array)$session;

    }

    /**
     * 会话是否存在
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 18:55
     * @param \app\struct\ConvType $convType
     * @param string               $userId
     * @param int|string           $toUserId
     * @return bool
     */
    public static function exist(ConvType $convType, string $userId, int|string $toUserId = 0): bool
    {
        $redis      = SessionPool::instance()->get();
        $session_id = Struct::createID($convType, $userId, $toUserId);
        return $redis->hExists(self::HASH_KEY, $session_id);
    }

    /**
     * getLastMessageId
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 18:58
     * @param \app\struct\ConvType $convType
     * @param string               $userId
     * @param int|string           $toUserId
     * @return int
     */
    public static function getLastMessageId(ConvType $convType, string $userId, int|string $toUserId = 0): int
    {
        $session = self::find($convType, $userId, $toUserId);
        return $session['lastMessageId'] ?? 0;
    }

    /**
     * 获取会话ID
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/18 19:02
     * @param \app\struct\ConvType $convType
     * @param string               $userId
     * @param int|string           $toUserId
     * @return string|null
     */
    public static function getSessionId(ConvType $convType, string $userId, int|string $toUserId = 0): ?string
    {
        $session = self::find($convType, $userId, $toUserId);
        return $session['sessionId'] ?? '';
    }


}
