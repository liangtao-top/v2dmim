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
// | Version: 2.0 2021/5/27 15:35
// +----------------------------------------------------------------------

namespace app\dao;

use app\common\DaoAbstract;
use app\model\ProfileModel;
use com\redis\FriendPool;
use com\redis\MemberPool;

class ProfileDao extends DaoAbstract
{

    public static function save(ProfileModel $model): ProfileModel
    {
        $pool  = MemberPool::instance();
        $redis = $pool->get();
        $redis->lRem(self::$indexKey, $model->getUserID(), 0);
        $redis->rPush(self::$indexKey, $model->getUserID());
        $redis->set($model->getUserID(), serialize($model));
        $pool->put($redis);
        return $model;
    }

    public static function del(ProfileModel $model): void
    {
        $pool  = MemberPool::instance();
        $redis = $pool->get();
        $redis->lRem(self::$indexKey, $model->getUserID(), 0);
        $redis->del($model->getUserID());
        $pool->put($redis);
    }

    public static function get(string $userID): ProfileModel
    {
        $pool  = MemberPool::instance();
        $redis = $pool->get();
        $value = $redis->get($userID);
        $pool->put($redis);
        return unserialize($value);
    }

    public static function all(int $start = 0, int $stop = null)
    {

    }

    public static function count();
}
