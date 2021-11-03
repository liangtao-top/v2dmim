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
// | Version: 2.0 2021/4/23 10:44
// +----------------------------------------------------------------------

namespace app\struct;

use com\enum\Enum;

/**
 * 消息状态
 * @package app\struct
 */
class MsgStatus extends Enum
{
    // 消息发送中
    const SENDING = 1;
    // 消息发送成功
    const SEND_SUCC = 2;
    // 消息发送失败
    const SEND_FAIL = 3;
    // 消息被删除
    const HAS_DELETED = 4;
    // 导入到本地的消息
    const LOCAL_IMPORTED = 5;
    // 被撤销的消息
    const LOCAL_REVOKED = 6;
}
