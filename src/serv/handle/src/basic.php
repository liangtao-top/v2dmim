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
// | Version: 2.0 2021/5/27 17:49
// +----------------------------------------------------------------------

use V2dmIM\Core\Config;

// 定义根目录
define('ROOT_PATH', dirname(__DIR__, 2));

// 定义版本
const V2DMIM_VERSION = '1.0.0';

// 定义日志路径
const LOG_PATH = ROOT_PATH . DS . 'logs';

// 定义应用路径
const APP_PATH = ROOT_PATH . DS . 'src';

// 初始化配置
Config::instance(__DIR__ . DS . 'config.php');

// 载入助手函数
require 'helper.php';
