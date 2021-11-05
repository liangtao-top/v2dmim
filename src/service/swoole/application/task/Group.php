<?php
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2020/10/28 11:41
// +----------------------------------------------------------------------

namespace app\task;

use app\logic\GroupChat;
use app\logic\Offline;
use app\logic\Timeline;
use app\model_bak\ImMemberProfile;
use app\struct\ConvType;
use app\struct\MsgType;
use com\console\Color;
use app\command\R;
use app\logic\Push;
use app\logic\Online;
use app\logic\Session;
use app\model_bak\ImGroupChat;
use app\model_bak\ImGroupChatApply;
use app\model_bak\ImSystemChatMessage;
use app\model_bak\ImSystemChatMessageState;
use app\model_bak\ImGroupChatMember;
use app\model_bak\ImGroupChatMessage;
use app\model_bak\ImGroupChatMessageIdentifier;
use app\model_bak\ImGroupChatMessageState;
use app\model_bak\ImMember;
use app\model_bak\ImSession;
use com\enum\Enum;
use com\event\Event;
use Swoole\Coroutine;
use Swoole\WebSocket\Server as Ws;
use think\Db;
use think\Model;

class Group
{
    /**
     * 通知接收方消息被撤回
     * @param Ws    $ws
     * @param array $param
     * @param int   $msg_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 9:40
     */
    public function revokedBefore(Ws $ws, array $param, int $msg_id): bool
    {
        //$list = ImGroupChatMessageState::where('msg_id', $msg_id)->where('uuid', 'neq', $param['uuid'])->field('id,uuid,group_id')->select()->toArray();
        $list = ImGroupChatMessageState::where('msg_id', $msg_id)->field('id,uuid,group_id')->select()->toArray();
        foreach ($list as $value) {
            $session = Session::find(2, $value['uuid'], $value['group_id']);
            $timeline = Timeline::save($value['uuid'], new Event(Event::MESSAGE_REVOKED), $session->toArray());
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            // 如果消息接收人不在线
            $fds = Online::getFds($ws, $value['uuid']);
            if (empty($fds)) {
                continue;
            }

            // 通知接收方消息被撤回
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
        }
        return true;
    }

    /**
     * revokedAfter
     * @param Ws    $ws
     * @param array $param
     * @param int   $msg_id
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 9:40
     */
    public function revokedAfter(Ws $ws, array $param, int $msg_id, $data = null)
    {
    }

    /**
     * readBefore
     * @param Ws    $ws
     * @param int   $group_id
     * @param array $notify_list
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 18:01
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function readBefore(Ws $ws, int $group_id, array $notify_list): bool
    {
        $where = ['cate_id' => 2, 'to_id' => $group_id];
        foreach ($notify_list as $uuid => &$msd_ids) {
            $where['uuid'] = $uuid;
            $session       = ImSession::where($where)->find();
            if (!empty($session)) {
                // 如果消息接收人不在线
                $fds = Online::getFds($ws, $uuid);
                if (empty($fds)) {
                    continue;
                }
                /*// 如果发送者会话处于激活状态,则通知消息被已读
                if ($session['is_active']) {
                    $ids  = ImGroupChatMessageState::where('uuid', $uuid)->where('group_id', $group_id)->where('msg_id', 'in', $msd_ids)->column('id');
                    $data = R::y('Message.c2cReadReceipt', ['ids' => $ids, 'session_id' => $session['id']]);
                    foreach ($fds as $fd) {
                        $ws->push($fd, $data);
                    }
                }*/
                // 通知发送方消息被已读
                $timeline = Timeline::save($session['uuid'], new Event(Event::MESSAGE_READ_BY_PEER), $session->toArray());
                $fds = Online::getFds($ws, $session['uuid']);
                if (!empty($fds)) {
                    $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
                    foreach ($fds as $fd) {
                        $ws->push($fd, $tmp_data);
                    }
                }
            }
        }
        return true;
    }

    /**
     * readAfter
     * @param Ws    $ws
     * @param int   $group_id
     * @param array $notify_list
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 18:01
     */
    public function readAfter(Ws $ws, int $group_id, array $notify_list, $data = null)
    {
    }

    /**
     * 单人禁言
     * @param Ws     $ws
     * @param array  $where
     * @param int    $status
     * @param string $p_uuid
     * @param bool   $is_lord
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 12:25
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function muteBefore(Ws $ws, array $where, int $status, string $p_uuid, bool $is_lord): bool
    {
        ['uuid' => $uuid, 'to_id' => $group_id, 'cate_id' => $cate_id] = $where;
        $mute          = $status ? '禁言' : '解除禁言';
        $model         = ImGroupChatMessage::create([
                                                        'cate_id'  => $cate_id,
                                                        'uuid'     => $p_uuid,
                                                        'group_id' => $group_id,
                                                        'content'  => json_encode(['text' => '你被' . ($is_lord ? '群主' : '管理员') . $mute . '了'], JSON_UNESCAPED_UNICODE),
                                                        'random'   => string_make_guid(),
                                                        'read'     => 0,
                                                        'unread'   => 1,
                                                        'shield'   => 0,
                                                        'retract'  => 0,
                                                        'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                                    ]);
        $message       = $model->toArray();
        $message_state = ImGroupChatMessageState::create([
                                                             'uuid'     => $uuid,
                                                             'group_id' => $group_id,
                                                             'msg_id'   => $message['id'],
                                                             'msg_uuid' => $p_uuid,
                                                             'read'     => 0,
                                                             'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                                         ]);
        $is_disturb    = ImGroupChatMember::where('uuid', $uuid)->where('group_id', $group_id)->value('is_disturb');
        // 准备消息的推送数据
        $notify_list = [
            [
                'uuid'          => $uuid,
                'unread'        => 1,
                'is_disturb'    => $is_disturb,
                'message_state' => $message_state,
                'is_mute'       => $status,
                'msgType'       => MsgType::MSG_GRP_TIP,
                'convType'      => ConvType::CONV_GROUP,
            ]
        ];
        Push::messageNew($ws, $notify_list, $message, 2, false);
        return true;
    }

    /**
     * muteAfter
     * @param Ws     $ws
     * @param array  $where
     * @param int    $status
     * @param string $p_uuid
     * @param bool   $is_lord
     * @param null   $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/18 13:37
     */
    public function muteAfter(Ws $ws, array $where, int $status, string $p_uuid, bool $is_lord, $data = null)
    {
    }

    /**
     * deleteBefore
     * @param Ws     $ws
     * @param array  $where
     * @param string $p_uuid
     * @param bool   $is_lord
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 14:46
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function deleteBeforeBak(Ws $ws, array $where, string $p_uuid, bool $is_lord): bool
    {
        ['uuid' => $uuid, 'to_id' => $group_id] = $where;
        $profile = ImMember::where('uuid', $p_uuid)->field('uuid,surname,name,nickname,sex,avatar')->find();
        // 消息内容
        $content       = [
            'cate_id' => 3, // 退群通知
            'text'    => '你被' . ($is_lord ? '群主' : '管理员') . '移除了' . ImGroupChat::where('id', $group_id)->value('name'),
            'group'   => ImGroupChat::get($group_id)->toArray(),
            'profile' => $profile->toArray(),
        ];
        $model         = ImSystemChatMessage::create([
                                                         'uuid'      => $p_uuid,
                                                         'system_id' => 2,// 群系统消息
                                                         'cate_id'   => 1,// 文本类型
                                                         'content'   => json_encode($content, JSON_UNESCAPED_UNICODE),// 消息内容
                                                         'random'    => string_make_guid(),
                                                         'read'      => 0,// 已读人数
                                                         'unread'    => 1,// 未读人数
                                                     ]);
        $message       = $model->toArray();
        $message_state = ImSystemChatMessageState::create([
                                                              'uuid'      => $uuid, //消息接收方
                                                              'system_id' => 2,// 群系统消息
                                                              'msg_id'    => $message['id'],// 消息ID
                                                              'msg_uuid'  => $p_uuid,// 消息发送者ID
                                                              'read'      => 0,// 未读
                                                          ]);
        $timeline = Timeline::save($message['uuid'], new Event(Event::MESSAGE_RECEIVED), $message);
        $yield = [];
        // 获取在线连接
        $fds = Online::getFds($ws, $uuid);
        if (empty($fds)) {
            $yield[] = [
                'msgID'     => $message['id'],
                'msgType'   => MsgType::MSG_GRP_TIP,
                'convType'  => ConvType::CONV_SYSTEM,
                'userID'    => $p_uuid,
                'groupID'   => $group_id,
                'receiveID' => $uuid,
            ];
        } else {
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
        }

        // 离线推送
        if (!empty($yield)) {
            Color::go('Task coroutine[' . Coroutine::getCid() . '] push umeng');
            $use = microtime(true);
            foreach ($yield as $yi) {
                Offline::save($yi['userID'], $yi['msgID'], $yi['convType'], $yi['msgType'], $yi['receiveID'], $yi['groupID']);
            }
            $use = (microtime(true) - $use) * 1000;
            Color::go("Finish all umeng push use {$use}ms");
        }
        return true;
    }

    /**
     * 群组踢人
     * @param Ws $ws
     * @param array $where
     * @param string $p_uuid
     * @param bool $is_lord
     * @param array $oldGroupMember
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 11:37
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function deleteBefore(Ws $ws, array $where, string $p_uuid, bool $is_lord, array $oldGroupMember): bool
    {
        ['uuid' => $uuid, 'to_id' => $group_id] = $where;
        // 消息内容
        $text  = '你被' . ($is_lord ? '群主' : '管理员') . '移除了群聊';
        $model = ImGroupChatMessage::create([
                                                'uuid'     => $p_uuid,
                                                'group_id' => $group_id,// 群系统消息
                                                'cate_id'  => 1,// 文本类型
                                                'content'  => json_encode(['text' => $text], JSON_UNESCAPED_UNICODE),// 消息内容
                                                'random'   => string_make_guid(),
                                                'read'     => 0,// 已读人数
                                                'unread'   => 1,// 未读人数
                                                'type'     => 1
                                            ]);

        $message = $model->toArray();
        $message_state = ImGroupChatMessageState::create([
                                                             'uuid'     => $uuid, //消息接收方
                                                             'group_id' => $group_id,// 群系统消息
                                                             'msg_id'   => $message['id'],// 消息ID
                                                             'msg_uuid' => $p_uuid,// 消息发送者ID
                                                             'read'     => 0,// 未读
                                                             'type'     => 1
                                                         ]);

        //更新群头像
        $is_update_avatar = 0;
        $groupChat = new GroupChat();
        $imGroupChatMember = new ImGroupChatMember();
        // 群人数低于5时更新头像
        if ($imGroupChatMember->where('group_id', $group_id)->count() < 5) {
            if (!$groupChat->updateAvatar($group_id)) {
                Color::error("群组[{$group_id}]更新群头像失败:\n". $groupChat->getError());
                return false;
            }
            $is_update_avatar = 1;
        } else {
            // 被删的成员位于群内前4名内时更新头像
            $topFour = $imGroupChatMember->where('group_id', $group_id)->order('id asc')->limit(4)->column('uuid');
            if (in_array($uuid, $topFour)) {
                if (!$groupChat->updateAvatar($group_id)) {
                    Color::error("群组[{$group_id}]更新群头像失败:\n". $groupChat->getError());
                    return false;
                }
                $is_update_avatar = 1;
            }
        }

        //向群成员推送群资料变更
        if ($is_update_avatar) {
            $group = ImGroupChat::find($group_id);
            $all_uuids = ImGroupChatMember::where('group_id', $group_id)->column('uuid');
            $notify_list = [];
            foreach ($all_uuids as $_uuid) {
                $notify_list[] = [
                    'uuid'  => $_uuid,
                    'event' => new Event(Event::GROUP_UPDATED),
                    'data'  => $group->toArray(),
                ];
            }
            Push::group($ws, $notify_list);
        }

        // 推送群成员变化
        $notify_list = [
            [
                'uuid'  => $uuid,
                'event' => new Event(Event::FRIEND_DELETE),
                'data'  => $oldGroupMember,
            ]
        ];
        Push::groupMember($ws, $notify_list);

        // 推送群消息变化
        $notify_list2 = [
            [
                'uuid'          => $uuid,
                'unread'        => 1,
                'is_disturb'    => 0,
                'message_state' => &$message_state,
                'msgType'       => MsgType::MSG_GRP_TIP,
                'convType'      => ConvType::CONV_GROUP,
            ]
        ];
        Push::messageNew($ws, $notify_list2, $message, 2);
        return true;
    }

    /**
     * deleteAfter
     * @param Ws     $ws
     * @param array  $where
     * @param string $p_uuid
     * @param bool   $is_lord
     * @param null   $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/19 10:10
     */
    public function deleteAfter(Ws $ws, array $where, string $p_uuid, bool $is_lord, $data = null)
    {

    }

    /**
     * 群主设置全员禁言
     * @param Ws    $ws
     * @param array $param
     * @param       $group
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 18:40
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function allForbiddenWordsBefore(Ws $ws, array $param, $group): bool
    {
        $uuid     = $param['uuid'];
        $group_id = $group->id;
        $model    = new  ImGroupChatMember;
        //更新群成员信息
        $model->where('group_id', $group_id)->whereNotIn('uuid', $uuid)->update(['is_mute'=>1]);

        // 生成系统消息
        $mute      = $group->is_mute_all ? '开启' : '关闭';
        $msg       = ImGroupChatMessage::create([
                                                    'cate_id'  => 2,
                                                    'uuid'     => $param['uuid'],
                                                    'group_id' => $group_id,
                                                    'content'  => json_encode(['text' => '群主' . $mute . '了全员禁言'], JSON_UNESCAPED_UNICODE),
                                                    'random'   => string_make_guid(),
                                                    'read'     => 1, // 群主默认已读
                                                    'unread'   => max(0, $model->where('group_id', $group_id)->count() - 1),
                                                    'shield'   => 0,
                                                    'retract'  => 0,
                                                    'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                                ]);
        $msg_id    = $msg->id; // 获取自增ID
        $message   = $msg->toArray();

        $insertAll = [];
        $tmp_array = [];
        $member    = $model->where('group_id', $group_id)->field('uuid,is_disturb')->select()->toArray();
        foreach ($member as &$value) {
            $insertAll[] = [
                'uuid'     => $value['uuid'],
                'group_id' => $group_id,
                'msg_id'   => $msg_id,
                'msg_uuid' => $value['uuid'],
                'read'     => $value['uuid'] === $param['uuid'] ? 1 : 0,
                'type'     => 1 // 类型 默认0:普通对话 1:系统提示
            ];
            $tmp_array[$value['uuid']] = $value['is_disturb'];
        }
        unset($member);
        $model          = new ImGroupChatMessageState;
        $message_states = $model->saveAll($insertAll);
        unset($insertAll);

        // 准备消息的推送数据
        $notify_list = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'               => $message_state->uuid,
                'unread'             => $message_state->uuid === $param['uuid'] ? 0 : 1, // 群主自己的会话直接已读
                'is_disturb'         => &$tmp_array[$message_state->uuid],
                'message_state'      => &$message_state,
                'is_mute_all' => $group->is_mute_all,
                'msgType'            => MsgType::MSG_GRP_TIP,
                'convType'           => ConvType::CONV_GROUP
            ];
        }
        //推送群会话变更
        Push::messageNew($ws, $notify_list, $message, 2, false);
        return true;
    }

    /**
     * allForbiddenWordsAfter
     * @param Ws    $ws
     * @param array $param
     * @param       $group
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/16 18:46
     */
    public function allForbiddenWordsAfter(Ws $ws, array $param, $group, $data = null)
    {
    }

    /**
     * 邀请加入
     * @param Ws     $ws
     * @param array  $uuids
     * @param        $group
     * @param string $uuid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 16:57
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function addBefore(Ws $ws, array $uuids, $group, string $uuid): bool
    {
        $group_id = $group->id;

        //更新群头像
        $is_update_avatar = 0;
        $groupChat = new GroupChat();
        $imGroupChatMember = new ImGroupChatMember();
        // 群人数低于5时更新头像
        if ($imGroupChatMember->where('group_id', $group_id)->count() < 5) {
            Color::info("群人数低于5,更新群头像");
            if (!$groupChat->updateAvatar($group_id)) {
                Color::error("群组[{$group_id}]更新群头像失败:\n". $groupChat->getError());
                return false;
            }
            $is_update_avatar = 1;
        }

        //向群成员推送群资料变更
        if ($is_update_avatar) {
            $old_uuids = ImGroupChatMember::where('group_id', $group_id)->whereNotIn('uuid', $uuids)->column('uuid');
            $notify_list = [];
            foreach ($old_uuids as $old_uuid) {
                $notify_list[] = [
                    'uuid'  => $old_uuid,
                    'event' => new Event(Event::GROUP_UPDATED),
                    'data'  => $group->toArray(),
                ];
            }
            Push::group($ws, $notify_list);
        }

        //-----------------------新群推送----------------------------------
        foreach ($uuids as $_uuid) {
            $timeline = Timeline::save($_uuid, new Event(Event::GROUP_CREATE), $group);
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            $fds = Online::getFds($ws, $_uuid);
            if (!empty($fds)) {
                // 在线则进行新群推送
                foreach ($fds as $fd) {
                    $ws->push($fd, $tmp_data);
                }
            }
        }
        //-----------------------消息推送----------------------------------
        $memberProfile = ImMemberProfile::where('uuid')->field('uuid,surname,last_name,nickname')->find();
        $nickname      = $memberProfile->nickname ?? '';
        $msg           = ImGroupChatMessage::create([
                                                    'cate_id'  => 2,
                                                    'uuid'     => $uuid,
                                                    'group_id' => $group->id,
                                                    'content'  => json_encode(['text' => $nickname . '邀请你加入群聊'], JSON_UNESCAPED_UNICODE),
                                                    'random'   => string_make_guid(),
                                                    'read'     => 0,
                                                    'unread'   => count($uuids),
                                                    'shield'   => 0,
                                                    'retract'  => 0,
                                                    'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                                ]);
        $msg_id    = $msg->id; // 获取自增ID
        $message   = $msg->toArray();
        $insertAll = [];
        foreach ($uuids as &$value) {
            $insertAll[] = [
                'uuid'     => $value,
                'group_id' => $group->id,
                'msg_id'   => $msg_id,
                'msg_uuid' => $uuid,
                'read'     => 0,
                'type'     => 1 // 类型 默认0:普通对话 1:系统提示
            ];
        }
        $model          = new ImGroupChatMessageState;
        $message_states = $model->saveAll($insertAll);
        unset($insertAll);
        // 准备消息的推送数据
        $notify_list = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'               => $message_state->uuid,
                'unread'             => 1,
                'is_disturb'         => 0,
                'message_state'      => &$message_state,
                'is_mute_all'        => $group->is_all_muted,
                'msgType'            => MsgType::MSG_GRP_TIP,
                'convType'           => ConvType::CONV_GROUP
            ];
        }
        Push::messageNew($ws, $notify_list, $message, 2);
        return true;
    }

    /**
     * 创建群
     * @param Ws $ws
     * @param array $group
     * @param        $message_state
     * @param string $my_nickname
     * @param string $uuid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/26 17:21
     */
    public function createBefore(Ws $ws, array $group, $message_state, string $my_nickname, string $uuid): bool
    {
        //群成员推送
        $notify_list1 = $notify_list2 = [];
        $GroupMemberList = ImGroupChatMember::where('group_id', $group['id'])->whereNotIn('uuid',$group['owner_id'])->select()->toArray();
        foreach ($GroupMemberList as $value) {
            $notify_list1[] = [
                'uuid' => $value['uuid'],
                'event' => new Event(Event::GROUP_CREATE),
                'data' => $group
            ];
            $notify_list2[] = [
                'uuid' => $value['uuid'],
                'event' => new Event(Event::GROUP_MEMBER_CREATE),
                'data' => $value
            ];
        }
        //推送群资料变更
        Push::group($ws, $notify_list1);
        //推送群成员变更
        Push::groupMember($ws, $notify_list2);
        //消息推送
        $msg       = ImGroupChatMessage::create([
                                                    'cate_id'  => 2,
                                                    'uuid'     => $group['owner_id'],
                                                    'group_id' => $group['id'],
                                                    'content'  => json_encode(['text' => $my_nickname . '发起了群聊'], JSON_UNESCAPED_UNICODE),
                                                    'random'   => string_make_guid(),
                                                    'read'     => 0,
                                                    'unread'   => $group['count'],
                                                    'shield'   => 0,
                                                    'retract'  => 0,
                                                    'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                                ]);
        $msg_id    = $msg->id; // 获取自增ID
        $message   = $msg->toArray();
        $insertAll = [];
        foreach ($message_state as &$value) {
            $insertAll[] = [
                'uuid'     => $value->uuid,
                'group_id' => $value->group_id,
                'msg_id'   => $msg_id,
                'msg_uuid' => $group['owner_id'],
                'read'     => 0,
                'type'     => 1 // 类型 默认0:普通对话 1:系统提示
            ];
        }
        $model          = new ImGroupChatMessageState;
        $message_states = $model->saveAll($insertAll);
        unset($insertAll);
        // 准备消息的推送数据
        $notify_list = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'          => $message_state->uuid,
                'unread'        => 1,
                'is_disturb'    => 0,
                'message_state' => &$message_state,
                'msgType'       => MsgType::MSG_GRP_TIP,
                'convType'      => ConvType::CONV_GROUP
            ];
        }
        Push::messageNew($ws, $notify_list, $message, 2);
        return true;
    }

    /**
     * createAfter
     * @param Ws     $ws
     * @param array  $group
     * @param        $message_state
     * @param string $my_nickname
     * @param null   $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/26 17:25
     */
    public function createAfter(Ws $ws, array $group, $message_state, string $my_nickname, $data = null)
    {

    }

    /**
     * addAfter
     * @param Ws     $ws
     * @param array  $uuids
     * @param        $group
     * @param string $uuid
     * @param null   $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/19 10:11
     */
    public function addAfter(Ws $ws, array $uuids, $group, string $uuid, $data = null)
    {

    }

    /**
     * 申请入群
     * @param Ws $ws
     * @param    $apply
     * @param    $group
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 18:48
     */
    public function applyBefore(Ws $ws, $apply, $group): bool
    {
        $uuid     = $apply->uuid;
        $group_id = $apply->group_id;
        $profile  = ImMember::get($uuid);
        // 查找群管理员
        $uuids = ImGroupChatMember::where('group_id', $group_id)->where('is_admin', 1)->column('uuid');
        // 查找群主
        $uuids[] = $group->owner_id;
        $uuids   = array_unique($uuids);
        $model   = ImSystemChatMessage::create([
                                                   'uuid'      => $uuid,
                                                   'system_id' => 1,// 验证消息
                                                   'cate_id'   => 1,// 文本类型
                                                   'content'   => json_encode([
                                                                                  'cate_id' => 2, // 群消息
                                                                                  'text'    => "{$profile->nickname}申请加入{$group->name}",
                                                                                  'group'   => $group->toArray(),
                                                                                  'profile' => $profile->toArray(),
                                                                              ], JSON_UNESCAPED_UNICODE),// 消息内容
                                                   'random'    => string_make_guid(),
                                                   'read'      => 0,// 已读人数
                                                   'unread'    => count($uuids),// 未读人数
                                               ]);
        $message = $model->toArray();
        $list    = [];
        foreach ($uuids as $id) {
            $list[] = [
                'uuid'      => $id, //消息接收方
                'system_id' => 2,// 群系统消息
                'msg_id'    => $message['id'],// 消息ID
                'msg_uuid'  => $uuid,// 消息发送者ID
                'read'      => 0,// 未读
            ];
        }
        $model          = new ImSystemChatMessageState;
        $message_states = $model->saveAll($list);
        // 在申请中记录下系统消息的相关ID,后来用来实现已读功能
        ImGroupChatApply::where('id', $apply->id)->update(['system_msg_id' => $message['id']]);
        // 准备消息的推送数据
        $notify_list = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'          => $message_state->uuid,
                'unread'        => 1,
                'is_disturb'    => false,
                'message_state' => &$message_state,
                'msgType'       => MsgType::MSG_GRP_SYS_NOTICE,
                'convType'      => ConvType::CONV_SYSTEM
            ];
        }
        Push::messageNew($ws, $notify_list, $message, 3);
        return true;
    }

    /**
     * applyAfter
     * @param Ws   $ws
     * @param      $apply
     * @param      $group
     * @param null $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/19 10:11
     */
    public function applyAfter(Ws $ws, $apply, $group, $data = null)
    {
    }

    /**
     * applyHandleBefore
     * @param Ws     $ws
     * @param string $uuid
     * @param        $apply
     * @param        $group
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 19:08
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function applyHandleBefore(Ws $ws, string $uuid, $apply, $group): bool
    {
        $group_id = $group['id'];
        //更新群头像
        $is_update_avatar = 0;
        $groupChat = new GroupChat();
        $imGroupChatMember = new ImGroupChatMember();
        // 群人数低于4时更新头像
        if ($imGroupChatMember->where('group_id', $group_id)->count() <= 4) {
            Color::info("群人数低于4,更新群头像");
            if (!$groupChat->updateAvatar($group_id)) {
                Color::error("群组[{$group_id}]更新群头像失败:\n". $groupChat->getError());
                return false;
            }
            $is_update_avatar = 1;
        }
        //向群成员推送群资料变更
        if ($is_update_avatar) {
            $all_uuids = ImGroupChatMember::where('group_id', $group_id)->column('uuid');
            $notify_list = [];
            foreach ($all_uuids as $_uuid) {
                $notify_list[] = [
                    'uuid'  => $_uuid,
                    'event' => new Event(Event::GROUP_UPDATED),
                    'data'  => $group->toArray(),
                ];
            }
            Push::group($ws, $notify_list);
        }

        //推送群成员变化
        $memberList = ImGroupChatMember::where('group_id', $group['id'])->field('id,uuid')->select()->toArray();
        foreach ($memberList as $item) {
            $timeline = Timeline::save($uuid, new Event(Event::GROUP_MEMBER_CREATE), $item);
            $fds = Online::getFds($ws, $item['uuid']);
            // 在线则进行会话推送
            if (!empty($fds)) {
                $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
                foreach ($fds as $fd) {
                    $ws->push($fd, $tmp_data);
                }
            }
        }

        //-----------------------消息推送----------------------------------
        $profile        = ImMember::get($apply->uuid);
        $model          = ImGroupChatMessage::create([
                                                         'uuid'     => $uuid, // 消息发送者UUID
                                                         'group_id' => $apply->group_id,
                                                         'cate_id'  => 1,// 文本类型
                                                         'content'  => json_encode(['text' => "{$profile->nickname}加入了群聊"], JSON_UNESCAPED_UNICODE),// 消息内容
                                                         'random'   => string_make_guid(),
                                                         'read'     => 0,
                                                         'unread'   => 2,
                                                         'type'     => 1
                                                     ]);
        $message        = $model->toArray();
        $insertAll      = [
            // 插入申请人消息的记录
            [
                'group_id' => $apply->group_id,  // 会话对方的UUID
                'uuid'     => $uuid,             // 消息接收方
                'msg_id'   => $message['id'],    // 消息ID
                'msg_uuid' => $uuid,             // 消息发送者ID
                'read'     => 0,                 // 默认未读
                'type'     => 1                  // 系统消息
            ],
            // 插入审核人的消息记录
            [
                'group_id' => $apply->group_id,  // 会话对方的UUID
                'uuid'     => $apply->uuid,      // 消息接收方
                'msg_id'   => $message['id'],    // 消息ID
                'msg_uuid' => $uuid,             // 消息发送者ID
                'read'     => 0,                 // 默认未读
                'type'     => 1                  // 系统消息
            ],
        ];
        $model          = new ImGroupChatMessageState;
        $message_states = $model->saveAll($insertAll);
        $disturb_uuids  = ImGroupChatMember::where('group_id', $apply->group_id)->where('is_disturb', 1)->column('uuid');
        // 准备消息的推送数据
        $notify_list = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'          => $message_state->uuid,
                'unread'        => 1,
                'is_disturb'    => in_array($message_state->uuid, $disturb_uuids),
                'message_state' => &$message_state,
                'msgType'       => MsgType::MSG_GRP_TIP,
                'convType'      => ConvType::CONV_GROUP
            ];
        }
        Push::messageNew($ws, $notify_list, $message, 2);
        return true;
    }

    /**
     * applyHandleAfter
     * @param Ws     $ws
     * @param string $uuid
     * @param        $apply
     * @param        $group
     * @param null   $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/29 10:12
     */
    public function applyHandleAfter(Ws $ws, string $uuid, $apply, $group, $data = null)
    {

    }

    /**
     * 入群申请审核后，通知其他管理员
     * @param Ws    $ws
     * @param array $uuids
     * @param int   $msg_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/29 10:49
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function notifyOtherAdminBefore(Ws $ws, array $uuids, int $msg_id): bool
    {
        // 更新为系统消息已读
        ImSystemChatMessageState::where('uuid', 'in', $uuids)->where('msg_id', $msg_id)->update(['read' => 1, 'update_time' => time()]);
        $model = new ImSession;
        $where = ['cate_id' => 3, 'to_id' => 2];
        //$model->where('uuid', 'in', $uuids)->where($where)->update(['unread' => Db::raw('`unread`-1'), 'update_time' => time()]);
        foreach ($uuids as $uuid) {
            $fds = Online::getFds($ws, $uuid);
            if (!empty($fds)) {
                $where['uuid'] = $uuid;
                $find          = $model->where($where)->find();
                if (!empty($find)) {
                    $data = R::y('Session.changed', $find->toArray());
                    // 在线则进行会话推送
                    foreach ($fds as $fd) {
                        $ws->push($fd, $data);
                    }
                }
            }
        }
        return true;
    }

    /**
     * notifyOtherAdminAfter
     * @param Ws    $ws
     * @param array $uuids
     * @param int   $msg_id
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/29 10:50
     */
    public function notifyOtherAdminAfter(Ws $ws, array $uuids, int $msg_id, $data = null)
    {

    }

    /**
     * sendBefore
     * @param Ws    $ws
     * @param array $param
     * @param array $message
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 15:30
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function sendBefore(Ws $ws, array $param, array $message): bool
    {
        $data     = $param['data'] ?? [];
        $group_id = $data['to_id'];
        // 群内其他成员人员名单
        $member = ImGroupChatMember::where('group_id', $group_id)->where('uuid', 'neq', $param['uuid'])->field('uuid,group_id,disturb')->select();
        // 需要通知的人员
        $uuids = array_unique(array_column($member->toArray(), 'uuid'));
        if (empty($uuids)) {
            Color::error("群组[{$group_id}]内需要通知人员为空");
            return false;
        }
        // 处理@信息
        if (isset($data['identifier']) && !empty($data['identifier']) && is_array($data['identifier'])) {
            $insertAll = [];
            $is_all    = false;
            foreach ($data['identifier'] as $value) {
                // 如果包含0,则@所有人
                if ($value === 0) {
                    $is_all = true;
                    break;
                }
            }
            if ($is_all) {
                foreach ($uuids as $uuid) {
                    $insertAll[] = [
                        'group_id' => $group_id,
                        'uuid'     => $uuid,
                        'msg_id'   => $message['id'],
                        'read'     => 0,
                        'is_all'   => 1
                    ];
                }
            } else {
                foreach (array_unique($data['identifier']) as $identifier) {
                    // !=0且是群内成员
                    if ($identifier !== 0 && in_array($identifier, $uuids)) {
                        $insertAll[] = [
                            'group_id' => $group_id,
                            'uuid'     => $identifier,
                            'msg_id'   => $message['id'],
                            'read'     => 0,
                            'is_all'   => 0
                        ];
                    }
                }
            }
            if (count($insertAll)) {
                // @信息批量插入
                $model = new ImGroupChatMessageIdentifier;
                $model->allowField(true)->saveAll($insertAll);
                // 需要离线推送@信息人员名单
                $identifier_uuids = array_column($insertAll, 'uuid');
            }
        }
        // 循环处理推送
        $yield         = [];
        $session_logic = new Session;
        $session_model = new ImSession;
        $message_state = new ImGroupChatMessageState;
        foreach ($member as $value) {
            $message_state->data([
                                     'group_id' => $group_id,
                                     'uuid'     => $value['uuid'],
                                     'msg_id'   => $message['id'],
                                     'msg_uuid' => $param['uuid'],
                                     'read'     => 0
                                 ])->allowField(true)->isUpdate(false)->save();
            // 查询会话信息
            $where   = ['cate_id' => 2, 'uuid' => $value['uuid'], 'to_id' => $group_id];
            $session = $session_model->where($where)->find();
            if (empty($session)) {  // 创建会话
                $session_logic->create(2, $value['uuid'], $group_id, false, ['unread' => 1, 'last_message' => $message_state->id]);
                $session  = $session_logic->getResult();
                $tmp_data = R::y('Session.new', $session);
            } else {    // 更新会话
                /** @noinspection PhpUndefinedMethodInspection */
                $update = ['last_message' => $message_state->id, 'unread' => Db::raw('unread+1'), 'update_time' => time()];
                $session_model->where($where)->update($update);
                $session  = $session_model->where('id', $session->id)->find()->toArray();
                $tmp_data = R::y('Session.changed', $session);
            }
            $fds = Online::getFds($ws, $value['uuid']);
            // 如果消息接收人不在线,则离线推送
            if (empty($fds)) {
                // 没有设置免打扰,则进入离线推送列表
                if (!$value['disturb']) {
                    $yield[] = $value['uuid'];
                } else {
                    // 虽然设置了免打扰,但是被@了,依赖进入离线推送列表
                    if (isset($identifier_uuids) && is_array($identifier_uuids) && in_array($value['uuid'], $identifier_uuids)) {
                        $yield[] = $value['uuid'];
                    }
                }
                continue;
            }
            // 在线则进行会话推送
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
            $is_active = Session::isActive(2, $value['uuid'], $group_id);
            // 如果会话处于激活状态,则进行消息推送
            if ($is_active) {
                $state               = $message_state->append(['identifier', 'profile'])->toArray();
                $state['message']    = $message;
                $state['session_id'] = $session['id'];
                // 推送消息
                $tmp_data = R::y('Message.new', $state);
                foreach ($fds as $fd) {
                    $ws->push($fd, $tmp_data);
                }
            }
        }
        // 离线推送
        if (!empty($yield)) {
            Color::go('Task coroutine[' . Coroutine::getCid() . '] push umeng');
            $use = microtime(true);
            $res = app_push($yield, '你有一条新消息');
            if ($res !== true) Color::error($res);
            $use = (microtime(true) - $use) * 1000;
            Color::go("Finish all umeng push use {$use}ms");
        }
        return true;
    }

    /**
     * sendAfter
     * @param Ws    $ws
     * @param array $param
     * @param array $message
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 13:40
     */
    public function sendAfter(Ws $ws, array $param, array $message, $data = null)
    {
    }

    /**
     * 屏蔽通知
     * @param Ws    $ws
     * @param array $param
     * @param       $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException|\think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 14:14
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function shieldBefore(Ws $ws, array $param, $message): bool
    {
        $list = ImGroupChatMessageState::where('msg_id', $message['id'])
                                       ->where('uuid', 'neq', $param['uuid'])
                                       ->field('id,uuid')->select()->toArray();
        foreach ($list as $value) {
            // 如果消息接收人不在线
            $fds = Online::getFds($ws, $value['uuid']);
            if (empty($fds)) continue;
            $session = Session::find(2, $value['uuid'], $message['group_id']);
            $timeline = Timeline::save($value['uuid'], new Event(Event::CONVERSATION_UPDATED), $session->toArray());
            $data = R::e($timeline->getEvent(), $timeline->getData());
            foreach ($fds as $fd) {
                $ws->push($fd, $data);
            }
        }
        return true;
    }

    /**
     * 修改群昵称
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/10 10:06
     * @param \Swoole\WebSocket\Server $ws
     * @param int $member_id
     * @param string $uuid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function nicknameBefore(Ws $ws, array $param, int $member_id): bool
    {
        $uuid = $param['uuid'];
        $info = ImGroupChatMember::where('id', $member_id)->find();
        if ($info) {
            $timeline = Timeline::save($uuid, new Event(Event::GROUP_MEMBER_UPDATED), $info->toArray());
            // 获取在线连接
            $fds = Online::getFds($ws, $uuid);
            foreach ($fds as $fd) {
                $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
                $ws->push($fd, $tmp_data);
            }
        }
        return true;
    }

    /**
     * 设置消息免打扰
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/10 10:06
     * @param \Swoole\WebSocket\Server $ws
     * @param int $group_id
     * @param string $uuid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function disturbBefore(Ws $ws, array $param, int $group_id): bool
    {
        $uuid = $param['uuid'];
        // 获取在线连接
        $fds = Online::getFds($ws, $uuid);
        // 群组消息变更
        $info = ImGroupChatMember::where('uuid', $uuid)->where('group_id', $group_id)->find();
        if ($info) {
            $timeline = Timeline::save($uuid, new Event(Event::GROUP_MEMBER_UPDATED), $info->toArray());
            foreach ($fds as $fd) {
                $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
                $ws->push($fd, $tmp_data);
            }
        }
        // 会话消息变更
//        $info = ImSession::where(['uuid'=>$uuid,'to_id'=>$group_id,'cate_id'=>2])->find();
//        if ($info) {
//            $timeline = Timeline::save($uuid, new Event(Event::CONVERSATION_UPDATED), $info->toArray());
//            foreach ($fds as $fd) {
//                $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
//                $ws->push($fd, $tmp_data);
//            }
//        }
        return true;
    }

    /**
     * 修改群名称
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/10 10:06
     * @param \Swoole\WebSocket\Server $ws
     * @param array $param
     * @param int $group_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setGroupNameBefore(Ws $ws, array $param, int $group_id): bool
    {
        $uuid = $param['uuid'];
        // 群组消息变更
        $info = ImGroupChat::where('id', $group_id)->find();
        if ($info) {
            $timeline = Timeline::save($uuid, new Event(Event::GROUP_UPDATED), $info->toArray());
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            $memberList = ImGroupChatMember::where('group_id',$group_id)->field('id,uuid')->select()->toArray();
            //向群成员告知群资料变更
            foreach ($memberList as $item) {
                // 获取在线连接
                $fds = Online::getFds($ws, $item['uuid']);
                if (empty($fds)) continue;
                foreach ($fds as $fd) {
                    $ws->push($fd, $tmp_data);
                }
            }
        }
        return true;
    }

    /**
     * 设置管理员
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/10 14:53
     * @param \Swoole\WebSocket\Server         $ws
     * @param array                            $param
     * @param \app\model_bak\ImGroupChatMember $groupChatMember
     * @param int                              $status
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function adminBefore(Ws $ws, array $param, ImGroupChatMember $groupChatMember, int $status): bool
    {
        $uuid     = $param['uuid'];
        $group_id = $groupChatMember->group_id;
        $nickname = $groupChatMember->nickname ?: $groupChatMember->profile->nickname;
        $text     = $status === 1 ? "{$nickname}被设为群管理员" : "{$nickname}已取消群管理员";
        $msg      = ImGroupChatMessage::create([
                                                 'cate_id'  => 2,
                                                 'uuid'     => $param['uuid'],
                                                 'group_id' => $group_id,
                                                 'content'  => json_encode(['text' => $text], JSON_UNESCAPED_UNICODE),
                                                 'random'   => string_make_guid(),
                                                 'read'     => 1, // 群主默认已读
                                                 'unread'   => max(0, ImGroupChatMember::where('group_id', $group_id)->count() - 1),
                                                 'shield'   => 0,
                                                 'retract'  => 0,
                                                 'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                             ]);
        $message = $msg->toArray();
        // 群内其他成员人员名单
        $memberList = ImGroupChatMember::where('group_id', $group_id)->where('uuid', 'neq', $param['uuid'])->field('uuid,group_id,disturb')->select();
        $message_state = new ImGroupChatMessageState;
        $insertAll     = [];
        $tmp_array     = [];
        foreach ($memberList as &$value) {
            $insertAll[] = [
                'uuid'     => $value['uuid'],
                'group_id' => $group_id,
                'msg_id'   => $msg->id,
                'msg_uuid' => $value['uuid'],
                'read'     => $value['uuid'] === $param['uuid'] ? 1 : 0,
                'type'     => 1 // 类型 默认0:普通对话 1:系统提示
            ];
            $tmp_array[$value['uuid']] = $value['disturb'];
        }
        $message_states = $message_state->saveAll($insertAll);
        //unset($memberList);
        unset($insertAll);

        // 准备消息的推送数据
        $group       = ImGroupChat::where('id', $group_id)->find();
        $notify_list = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'               => $message_state->uuid,
                'unread'             => $message_state->uuid === $param['uuid'] ? 0 : 1, // 群主自己的会话直接已读
                'is_disturb'         => &$tmp_array[$message_state->uuid],
                'message_state'      => &$message_state,
                'msgType'            => MsgType::MSG_GRP_TIP,
                'convType'           => ConvType::CONV_GROUP
            ];
        }
        //推送群会话变更
        Push::messageNew($ws, $notify_list, $message, 2, false);
        return true;
    }

    /**
     * 退出本群
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/11 16:45
     * @param \Swoole\WebSocket\Server $ws
     * @param array $where
     * @param string $p_uuid
     * @param array $deleteMember
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function outBefore(Ws $ws, array $where, string $p_uuid, array $deleteMember): bool
    {
        ['uuid' => $uuid, 'to_id' => $group_id] = $where;
        $group    = ImGroupChat::get($group_id);

        //更新群头像
        $is_update_avatar = 0;
        $groupChat = new GroupChat();
        $imGroupChatMember = new ImGroupChatMember();
        // 群人数低于4时更新头像
        if ($imGroupChatMember->where('group_id', $group_id)->count() < 4) {
            Color::info("群人数低于4,更新群头像");
            if (!$groupChat->updateAvatar($group_id)) {
                Color::error("群组[{$group_id}]更新群头像失败:\n". $groupChat->getError());
                return false;
            }
            $is_update_avatar = 1;
        } else {
            // 退出的成员位于群内前4名内时更新头像
            $topFour = $imGroupChatMember->where('group_id', $group_id)->order('id asc')->limit(4)->column('uuid');
            if (in_array($p_uuid, $topFour)) {
                if (!$groupChat->updateAvatar($group_id)) {
                    Color::error("群组[{$group_id}]更新群头像失败:\n". $groupChat->getError());
                    return false;
                }
                $is_update_avatar = 1;
            }
        }
        //向群成员推送群资料变更
        if ($is_update_avatar) {
            $all_uuids = ImGroupChatMember::where('group_id', $group_id)->column('uuid');
            $notify_list = [];
            foreach ($all_uuids as $_uuid) {
                $notify_list[] = [
                    'uuid'  => $_uuid,
                    'event' => new Event(Event::GROUP_UPDATED),
                    'data'  => $group->toArray(),
                ];
            }
            Push::group($ws, $notify_list);
        }

        //向退群者推送群组变更
        $notify_list = [
            [
                'uuid'  => $uuid,
                'event' => new Event(Event::GROUP_DELETE),
                'data'  => $group->toArray(),
            ]
        ];
        Push::group($ws, $notify_list);

        $profile = ImMemberProfile::where('uuid', $p_uuid)->field('uuid,surname,last_name,nickname')->find();
        $text    = $profile->nickname ?? '' . "退出了群聊";
        $msg     = ImGroupChatMessage::create([
                                                  'cate_id'  => 2,
                                                  'uuid'     => $uuid,
                                                  'group_id' => $group_id,
                                                  'content'  => json_encode(['text' => $text], JSON_UNESCAPED_UNICODE),
                                                  'random'   => string_make_guid(),
                                                  'read'     => 1, // 群主默认已读
                                                  'unread'   => max(0, ImGroupChatMember::where('group_id', $group_id)->count() - 1),
                                                  'shield'   => 0,
                                                  'retract'  => 0,
                                                  'type'     => 1 // 类型 默认0:普通对话 1:系统提示
                                              ]);
        $message = $msg->toArray();
        // 群内其他成员人员名单
        $memberList = ImGroupChatMember::where('group_id', $group_id)->field('uuid,group_id,is_disturb')->select()->toArray();
        $message_state = new ImGroupChatMessageState;
        $insertAll     = [];
        foreach ($memberList as &$value) {
            $insertAll[] = [
                'uuid'     => $value['uuid'],
                'group_id' => $group_id,
                'msg_id'   => $msg->id,
                'msg_uuid' => $value['uuid'],
                'read'     => $value['uuid'] === $uuid ? 1 : 0,
                'type'     => 1 // 类型 默认0:普通对话 1:系统提示
            ];
        }
        $message_state->saveAll($insertAll);
        unset($insertAll);

        //告知群成员群组减员
        foreach ($memberList as $member) {
            $timeline = Timeline::save($member['uuid'], new Event(Event::GROUP_MEMBER_DELETE), $deleteMember);
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            $fds = Online::getFds($ws, $member['uuid']);
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
        }

        /*
         * 退群不需要离线推送和回话推送
         * // 推送群成员变化
        $notify_list = [
            [
                'uuid'  => $uuid,
                'event' => new Event(Event::FRIEND_DELETE),
                'data'  => $groupChatMember,
            ]
        ];
        Push::groupMember($ws, $notify_list);*/
        return true;
    }



}
