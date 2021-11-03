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
// | Version: 2.0 2021/4/17 17:56
// +----------------------------------------------------------------------

namespace app\struct;

use com\enum\Enum;

class Event extends Enum
{
    // 连接成功
    const CONNECT_OPEN = 'CONNECT_OPEN';
    // 数据格式异常，必须是JSON字符串
    const EXCEPTION_WRONG_FORMAT = 'EXCEPTION_WRONG_FORMAT';

    // SDK 会话信息更新时触发
    const CONVERSATION_CREATE = 'CONVERSATION_CREATE';
    const CONVERSATION_UPDATED = 'CONVERSATION_UPDATED';
    const CONVERSATION_DELETE = 'CONVERSATION_DELETE';

    // SDK 收到推送的单聊、群聊、群提示、群系统通知的新消息，可通过遍历 event.data 获取消息列表数据并渲染到页面
    const MESSAGE_RECEIVED = 'MESSAGE_RECEIVED';
    // SDK 收到消息被撤回的通知，可通过遍历 event.data 获取被撤回的消息列表数据并渲染到页面，如单聊会话内可展示为 "对方撤回了一条消息"；群聊会话内可展示为 "XXX撤回了一条消息"。
    const MESSAGE_REVOKED = 'MESSAGE_REVOKED';
    // SDK 收到对端已读消息的通知，即已读回执。可通过遍历 event.data 获取对端已读的消息列表数据并渲染到页面，如单聊会话内可将己方发送的消息由“未读”状态改为“已读”。
    const MESSAGE_READ_BY_PEER = 'MESSAGE_READ_BY_PEER';

    // SDK 群组资料更新时触发
    const GROUP_CREATE = 'GROUP_CREATE';
    const GROUP_UPDATED = 'GROUP_UPDATED';
    const GROUP_DELETE = 'GROUP_DELETE';

    // SDK 群组成员资料更新时触发
    const GROUP_MEMBER_CREATE = 'GROUP_MEMBER_CREATE';
    const GROUP_MEMBER_UPDATED = 'GROUP_MEMBER_UPDATED';
    const GROUP_MEMBER_DELETE = 'GROUP_MEMBER_DELETE';

    // 自己的资料发生变更时触发，event.data 是 Profile 对象
    const PROFILE_UPDATED = 'PROFILE_UPDATED';

    // 好友的资料发生变更时触发，event.data 是 Profile 对象
    const FRIEND_CREATE  = 'FRIEND_CREATE';
    const FRIEND_UPDATED = 'FRIEND_UPDATED';
    const FRIEND_DELETE = 'FRIEND_DELETE';

    // SDK 黑名单列表更新时触发
    const BLACKLIST_UPDATED = 'BLACKLIST_UPDATED';

    // 用户被踢下线时触发
    // V2DMIM.TYPES.KICKED_OUT_MULT_ACCOUNT(Web端，同一账号，多页面登录被踢)
    // V2DMIM.TYPES.KICKED_OUT_MULT_DEVICE(同一账号，多端登录被踢)
    // V2DMIM.TYPES.KICKED_OUT_USERSIG_EXPIRED(签名过期。使用前需要将SDK版本升级至v2.4.0或以上)
    const KICKED_OUT = 'KICKED_OUT';

}
