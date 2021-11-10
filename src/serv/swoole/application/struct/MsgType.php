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
 * 消息类型
 * @package app\struct
 */
class MsgType extends Enum
{
    // 消息类型：文本消息
    const MSG_TEXT = 1;
    // 消息类型：图片消息
    const MSG_IMAGE = 2;
    // 消息类型：音频消息
    const MSG_AUDIO = 3;
    // 消息类型：文件消息
    const MSG_FILE = 4;
    // 消息类型：视频消息
    const MSG_VIDEO = 5;
    // 消息类型：表情消息
    const MSG_FACE = 11;
    // 消息类型：转发消息
    const MSG_FORWARD = 12;

    // 消息类型：位置消息
    const MSG_GEO = 6;
    // 消息类型：自定义消息
    const MSG_CUSTOM = 9;
    // 消息类型：合并消息
    const MSG_MERGER = 10;

    // 消息类型：群提示消息
    const MSG_GRP_TIP = 7;
    // 消息类型：群系统通知消息
    const MSG_GRP_SYS_NOTICE = 8;

}
