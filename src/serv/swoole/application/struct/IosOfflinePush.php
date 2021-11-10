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
// | Version: 2.0 2021/5/26 10:50
// +----------------------------------------------------------------------

namespace app\struct;

use com\enum\Enum;

/**
 * 离线推送声音设置（仅对 iOS 生效）
 * @package app\struct
 */
class IosOfflinePush extends Enum
{
    // 表示接收时不会播放声音
    const IOS_OFFLINE_PUSH_NO_SOUND      = "push.no_sound";
    // 表示接收时播放系统声音
    const IOS_OFFLINE_PUSH_DEFAULT_SOUND = "default";
}
