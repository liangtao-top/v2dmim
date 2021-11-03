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
// | Version: 2.0 2021/5/26 10:00
// +----------------------------------------------------------------------

namespace app\struct;

use app\common\Struct;

class Message extends Struct
{
    // 消息 ID
    private string $msgID;
    // 消息时间戳。单位：秒
    private int $timestamp;
    // 发送方的 userID，在消息发送时，会默认设置为当前登录的用户
    private string $sender;
    // 消息发送者昵称
    private string $nickName;
    // 好友备注。如果没有拉取过好友信息或者不是好友，返回 null
    private string $friendRemark;
    // 发送者头像 url
    //
    // 注意
    // 在 C2C 场景下，陌生人的头像不会更新，建议在 UI 上点击陌生人信息的时候主动调用 V2TIMManager -> getUsersInfo 去拉取资料， 拉取成功后，SDK 会更新本地头像，下次 getFaceUrl 会拿到更新后的头像，注意请不要在收到每条消息都去 getUsersInfo，会严重影响程序性能。
    private string $faceUrl;
    // 如果是群组消息，nameCard 为发送者的群名片
    private string $nameCard;
    // 如果是群组消息，groupID 为接收消息的群组 ID，否则为 null
    private string $groupID;
    // 如果是单聊消息，userID 为会话用户 ID，否则为 null。 假设自己和 userA 聊天，无论是自己发给 userA 的消息还是 userA 发给自己的消息，这里的 userID 均为 userA
    private string $userID;
    // 消息状态
    private MsgStatus $status;
    // 消息类型
    private MsgType $elemType;
    // 消息的内容
    private Elem $elem;
    // 消息自定义数据（本地保存，不会发送到对端，程序卸载重装后失效）
    private string $localCustomData;
    // 消息自定义数据（云端保存，会发送到对端，程序卸载重装后还能拉取到）
    private string $cloudCustomData;
    // 消息发送者是否是自己
    private bool $isSelf;
    // 消息自己是否已读
    private bool $isRead;
    // C2C 消息对端是否已读，true 标识对端已读
    private bool $isPeerRead;
    // 消息优先级
    private MsgPriority $priority;
    // 消息的离线推送信息
    private OfflinePushInfo $offlinePushInfo;
    // 群聊时此字段存储被 at 的群成员的 userID
    private array $groupAtUserList;
    // 消息的序列号
    //
    // 群聊中的消息序列号云端生成，在群里是严格递增且唯一的。 单聊中的序列号是本地生成，不能保证严格递增且唯一。
    private int $seq;
    // 消息随机码
    private string $random;
    // 获取消息是否计入未读数
    //
    // true - 不计入未读数，false - 计入未读数
    private bool $isExcludedFromUnreadCount;
    // 设置发送的消息是否计入未读数
    //
    // excludedFromUnreadCount	true - 不计入未读数，false - 计入未读数
    private bool $excludedFromUnreadCount;

    // 消息的流向
    private MsgFlow $flow;
}
