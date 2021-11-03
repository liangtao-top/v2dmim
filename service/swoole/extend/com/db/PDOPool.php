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
// | Date: 2021/5/8 15:04
// +----------------------------------------------------------------------

namespace com\db;

use Swoole\Database\PDOConfig;
use think\Env;

/**
 * PDO连接池
 * @package com\db
 */
class PDOPool
{

    // 支持多个数据库的连接池同时存在
    static private array $links = [];

    //创建静态私有的变量保存该类对象
    static private ?\Swoole\Database\PDOPool $instance = null;

    // 防止使用new直接创建对象
    private function __construct()
    {
    }

    // 防止使用clone克隆对象
    private function __clone()
    {
    }

//    static public function instance(array $config = [], array $params = []): \Swoole\Database\PDOPool
//    {
//        $name = md5(serialize($config) . serialize($params));
//        var_dump(__CLASS__.'::Links['.$name.']');
//        var_dump(isset(self::$links[$name]));
//        if (!isset(self::$links[$name])) {
//            self::$links[$name] = new \Swoole\Database\PDOPool((new PDOConfig)
//                                                  ->withDriver($config['type'] ?? 'mysql')
//                                                  ->withHost($config['hostname'] ?? Env::get('database.hostname'))
//                                                  ->withPort((int)($config['hostport'] ?? Env::get('database.hostport', 3306)))
//                                                  // ->withUnixSocket('/tmp/mysql.sock')
//                                                  ->withDbName($config['database'] ?? Env::get('database.database'))
//                                                  ->withCharset($config['charset'] ?? Env::get('database.charset'))
//                                                  ->withUsername($config['username'] ?? Env::get('database.username'))
//                                                  ->withPassword($config['password'] ?? Env::get('database.password'))
//                                                  ->withOptions($params));
//        }
//        return self::$links[$name];
//    }

    static public function instance(): \Swoole\Database\PDOPool
    {
        //判断$instance是否是Singleton的对象，不是则创建
        if (is_null(self::$instance)) {
            self::$instance = new \Swoole\Database\PDOPool((new PDOConfig)
                                                               ->withHost(Env::get('database.hostname'))
                                                               ->withPort((int)(Env::get('database.hostport', 3306)))
                                                               // ->withUnixSocket('/tmp/mysql.sock')
                                                               ->withDbName(Env::get('database.database'))
                                                               ->withCharset(Env::get('database.charset'))
                                                               ->withUsername(Env::get('database.username'))
                                                               ->withPassword(Env::get('database.password'))
//                                                               ->withOptions([8 => 0, 3 => 2, 11 => 0, 17 => false, 20 => false])
            );
        }
        return self::$instance;
    }
}
