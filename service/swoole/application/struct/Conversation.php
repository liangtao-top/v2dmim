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

use app\common\Struct;

/**
 * 会话
 * @package app\struct
 */
class Conversation extends Struct
{
    // 会话 ID
    private string $conversationID;
    // 会话类型
    private ConvType $type;
    // 如果会话类型为 C2C 单聊，userID 会存储对方的用户ID，否则为 null
    private string $userID;
    // 如果会话类型为群聊，groupID 会存储当前群的群 ID，否则为 null
    private string $groupID;
    // 如果会话类型为系统，systemID 会存储当前系统的系统 ID，否则为 null
    private string $systemID;
    // 会话展示名称，其展示优先级如下：
    // 系统：系统名称
    // 群组：群名称
    // C2C：对方好友备注->对方昵称->对方的 userID
    private string $showName;
    // 会话展示头像
    // 系统：系统头像
    // 群组：群头像
    // C2C：对方头像
    private string $faceUrl;
    // 群消息接收选项（群会话有效）
    private MsgRevOpt $groupRecvOpt;
    // C2C消息接收选项（接收 | 接收但不提醒 | 不接收）
    private MsgRevOpt $userRecvOpt;
    // 系统消息接收选项（系统会话有效）
    private MsgRevOpt $systemRecvOpt;
    // 取群类型（群会话有效）
    private GroupType $groupType;
    // 未读计数。TYPES.GRP_MEETING / TYPES.GRP_AVCHATROOM 类型的群组会话不记录未读计数，该字段值为0
    private int $unreadCount;
    // 获取会话最新一条消息，可以通过 lastMessage -> timestamp 对会话做排序，timestamp 越大，会话越靠前
    private Message $lastMessage;
    // 群会话的 at 信息列表，接入侧可根据此信息在会话列表展示【有人@我】【@所有人】等效果。GroupAtInfo - 群 at 信息结构。
    private array $groupAtInfoList;
    // 是否置顶
    private bool $isPinned;

    /**
     * @return string
     */
    public function getConversationID(): string
    {
        return $this->conversationID;
    }

    /**
     * @param string $conversationID
     */
    public function setConversationID(string $conversationID): void
    {
        $this->conversationID = $conversationID;
    }

    /**
     * @return \app\struct\ConvType
     */
    public function getType(): ConvType
    {
        return $this->type;
    }

    /**
     * @param \app\struct\ConvType $type
     */
    public function setType(ConvType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getUserID(): string
    {
        return $this->userID;
    }

    /**
     * @param string $userID
     */
    public function setUserID(string $userID): void
    {
        $this->userID = $userID;
    }

    /**
     * @return string
     */
    public function getGroupID(): string
    {
        return $this->groupID;
    }

    /**
     * @param string $groupID
     */
    public function setGroupID(string $groupID): void
    {
        $this->groupID = $groupID;
    }

    /**
     * @return string
     */
    public function getSystemID(): string
    {
        return $this->systemID;
    }

    /**
     * @param string $systemID
     */
    public function setSystemID(string $systemID): void
    {
        $this->systemID = $systemID;
    }

    /**
     * @return string
     */
    public function getShowName(): string
    {
        return $this->showName;
    }

    /**
     * @param string $showName
     */
    public function setShowName(string $showName): void
    {
        $this->showName = $showName;
    }

    /**
     * @return string
     */
    public function getFaceUrl(): string
    {
        return $this->faceUrl;
    }

    /**
     * @param string $faceUrl
     */
    public function setFaceUrl(string $faceUrl): void
    {
        $this->faceUrl = $faceUrl;
    }

    /**
     * @return \app\struct\MsgRevOpt
     */
    public function getGroupRecvOpt(): MsgRevOpt
    {
        return $this->groupRecvOpt;
    }

    /**
     * @param \app\struct\MsgRevOpt $groupRecvOpt
     */
    public function setGroupRecvOpt(MsgRevOpt $groupRecvOpt): void
    {
        $this->groupRecvOpt = $groupRecvOpt;
    }

    /**
     * @return \app\struct\MsgRevOpt
     */
    public function getUserRecvOpt(): MsgRevOpt
    {
        return $this->userRecvOpt;
    }

    /**
     * @param \app\struct\MsgRevOpt $userRecvOpt
     */
    public function setUserRecvOpt(MsgRevOpt $userRecvOpt): void
    {
        $this->userRecvOpt = $userRecvOpt;
    }

    /**
     * @return \app\struct\MsgRevOpt
     */
    public function getSystemRecvOpt(): MsgRevOpt
    {
        return $this->systemRecvOpt;
    }

    /**
     * @param \app\struct\MsgRevOpt $systemRecvOpt
     */
    public function setSystemRecvOpt(MsgRevOpt $systemRecvOpt): void
    {
        $this->systemRecvOpt = $systemRecvOpt;
    }

    /**
     * @return \app\struct\GroupType
     */
    public function getGroupType(): GroupType
    {
        return $this->groupType;
    }

    /**
     * @param \app\struct\GroupType $groupType
     */
    public function setGroupType(GroupType $groupType): void
    {
        $this->groupType = $groupType;
    }

    /**
     * @return int
     */
    public function getUnreadCount(): int
    {
        return $this->unreadCount;
    }

    /**
     * @param int $unreadCount
     */
    public function setUnreadCount(int $unreadCount): void
    {
        $this->unreadCount = $unreadCount;
    }

    /**
     * @return \app\struct\Message
     */
    public function getLastMessage(): Message
    {
        return $this->lastMessage;
    }

    /**
     * @param \app\struct\Message $lastMessage
     */
    public function setLastMessage(Message $lastMessage): void
    {
        $this->lastMessage = $lastMessage;
    }

    /**
     * @return array
     */
    public function getGroupAtInfoList(): array
    {
        return $this->groupAtInfoList;
    }

    /**
     * @param array $groupAtInfoList
     */
    public function setGroupAtInfoList(array $groupAtInfoList): void
    {
        $this->groupAtInfoList = $groupAtInfoList;
    }

    /**
     * @return bool
     */
    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    /**
     * @param bool $isPinned
     */
    public function setIsPinned(bool $isPinned): void
    {
        $this->isPinned = $isPinned;
    }

}
