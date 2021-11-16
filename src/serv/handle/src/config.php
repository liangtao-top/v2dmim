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
// | Version: 2.0 2021/5/27 17:39
// +----------------------------------------------------------------------

$cpu_num = function_exists("swoole_cpu_num") ? swoole_cpu_num() : 4;

return [
    'debug'  => true,
    'log'    => true,
    'host'   => '0.0.0.0',
    'port'   => 31100,
    'swoole' => [
        'daemonize'               => 0, // 进程守护
        'reactor_num'             => $cpu_num, // Reactor线程数,默认会启用CPU核数相同的数量,建议设置为CPU核数的1-4倍
        'worker_num'              => $cpu_num, // Worker进程数,这里设置为CPU核数的1-4倍最合理
        'task_worker_num'         => $cpu_num, // 设置异步任务的工作进程数量
        'max_connection'          => 20000, // 最大允许的连接数
        'reload_async'            => true, // 设置异步重启开关 设置为 true 时，将启用异步安全重启特性，Worker 进程会等待异步事件完成后再退出
        'enable_coroutine'        => true, // 内置协程
        'task_enable_coroutine'   => true, // 开启 Task 协程支持
        'open_redis_protocol'     => false, // 启用后会解析 Redis 协议，worker 进程 onReceive 每次会返回一个完整的 Redis 数据包。建议直接使用 Redis\Server
        'open_mqtt_protocol'      => false, // 启用后会解析 MQTT 包头，worker 进程 onReceive 每次会返回一个完整的 MQTT 数据包。
        'open_http_protocol'      => true, // 启用 HTTP 协议处理，Swoole\Http\Server 会自动启用此选项。设置为 false 表示关闭 HTTP 协议处理。
        'open_websocket_protocol' => false, // 设置使得这个端口WebSocket协议
        'pid_file'                => LOG_PATH . DS . 'http.pid', // 进程ID
        'log_file'                => LOG_PATH . DS . 'http.log', // 日志路径
        'log_rotation'            => defined('SWOOLE_LOG_ROTATION_HOURLY') ? SWOOLE_LOG_ROTATION_HOURLY : 3, // 日志分割
        'enable_static_handler'   => true, // 是否允许启动静态处理, 如果存在会直接发送文件内容给客户端，不再触发onRequest回调
        'document_root'           => APP_PATH . DS . 'static', // 静态资源根目录
        'open_http2_protocol'     => false,
        //        'ssl_key_file'             => ROOT_PATH . DS . 'config' . DS . 'cert' . DS . 'privkey.key',
        //        'ssl_cert_file'            => ROOT_PATH . DS . 'config' . DS . 'cert' . DS . 'fullchain.pem',
    ],
    'mysql'=>[
        'hostname'=>'127.0.0.1',
        'hostport'=>3306,
        'database'=>'V2dmIM',
        'charset'=>'utf8mb4',
        'username'=>'root',
        'password'=>'XReVA1tSyoTIyWP7',
    ],
];
