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
// | Version: 2.0 2021/5/26 14:01
// +----------------------------------------------------------------------

namespace app\dao;

use app\common\DaoAbstract;
use com\redis\FriendPool;
use app\model\FriendModel;

class FriendDao extends DaoAbstract
{

    public static function save(string $userID, string $friendUserID, string $friendRemark = "", array $friendGroups = [], array $friendCustomInfo = []): FriendModel
    {
        $pool  = FriendPool::instance();
        $redis = $pool->get();
        $model = new FriendModel();
        $model->setUserID($userID);
        $model->setFriendUserID($friendUserID);
        $model->setFriendRemark($friendRemark);
        $model->setFriendGroups($friendGroups);
        $model->setFriendCustomInfo($friendCustomInfo);
        $time = time();
        $model->setJoinTime($time);
        $model->setLastTime($time);
        $key = [$userID . ':list', $userID . ':' . $friendUserID];
        $redis->lRem($key[0], $key[1], 0);
        $redis->rPush($key[0], $key[1]);
        $redis->set($key[1], serialize($model));
        $pool->put($redis);
        return $model;
    }

    public static function del(string $userID, string $friendUserID): void
    {
        $pool  = FriendPool::instance();
        $redis = $pool->get();
        $key   = [$userID . ':list', $userID . ':' . $friendUserID];
        $redis->lRem($key[0], $key[1], 0);
        $redis->del($key[1]);
        $pool->put($redis);
    }

    public static function get(string $userID, string $friendUserID): FriendModel
    {
        $pool  = FriendPool::instance();
        $redis = $pool->get();
        $value = $redis->get($userID . ':' . $friendUserID);
        $pool->put($redis);
        return unserialize($value);
    }

    public static function all(string $userID, int $start = 0, int $stop = null): array
    {
        $pool  = FriendPool::instance();
        $redis = $pool->get();
        $key   = $userID . ':list';
        if (is_null($stop)) {
            $stop = $redis->lLen($key);
        }
        $index = $redis->lRange($key, $start, $stop);
        $list  = [];
        foreach ($index as $k) {
            $value  = $redis->get($k);
            $list[] = unserialize($value);
        }
        $pool->put($redis);
        return $list;
    }

    public static function count(string $userID): int
    {
        $pool  = FriendPool::instance();
        $redis = $pool->get();
        $key   = $userID . ':list';
        $len   = $redis->lLen($key);
        $pool->put($redis);
        return $len;
    }

}
