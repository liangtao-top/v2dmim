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
// | Version: 2.0 2021/4/8 13:55
// +----------------------------------------------------------------------

namespace com\redis;

use Swoole\Database\RedisPool;
use Swoole\Database\RedisConfig;

class ConversationPool
{
    //创建静态私有的变量保存该类对象
    static private ?RedisPool $instance = null;

    //防止使用new直接创建对象
    private function __construct()
    {
    }

    //防止使用clone克隆对象
    private function __clone()
    {
    }

    static public function instance(): RedisPool
    {
        //判断$instance是否是Singleton的对象，不是则创建
        if (is_null(self::$instance)) {
            self::$instance = new RedisPool((new RedisConfig)->withHost('container_redis_conversation')
                                                             ->withPort(6379)
                                                             ->withAuth('8bt8vmQz2KWhrGh7')
                                                             ->withDbIndex(0)
                                                             ->withTimeout(1)
            );
        }
        return self::$instance;
    }
}
