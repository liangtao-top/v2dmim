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
// | Version: 2.0 2021/4/23 10:45
// +----------------------------------------------------------------------

namespace app\struct;

use com\enum\Enum;

/**
 * 消息接收选项
 * @package app\struct
 */
class MsgRevOpt extends Enum
{
    // 在线正常接收消息，离线时会进行离线推送
    const RECEIVE_MESSAGE = 0;
    // 不会接收到消息
    const NOT_RECEIVE_MESSAGE = 1;
    // 在线正常接收消息，离线不会有推送通知
    const RECEIVE_NOT_NOTIFY_MESSAGE = 2;
}
