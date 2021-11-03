<?php /** @noinspection PhpUndefinedFieldInspection PhpDynamicAsStaticMethodCallInspection */
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2020/10/31 21:05
// +----------------------------------------------------------------------

namespace app\task;

use app\command\R;
use app\logic\Conversation;
use app\logic\Offline;
use app\logic\Push;
use app\logic\Session;
use app\logic\Online;
use app\logic\Timeline;
use app\model_bak\ImFriends;
use app\model_bak\ImFriendsApply;
use app\model_bak\ImMember;
use app\model_bak\ImSession;
use app\model_bak\ImSingleChatMessage;
use app\model_bak\ImSingleChatMessageState;
use app\model_bak\ImSystemChatMessage;
use app\model_bak\ImSystemChatMessageState;
use app\struct\ConvType;
use app\struct\MsgType;
use com\console\Color;
use com\event\Event;
use Swoole\Coroutine;
use Swoole\WebSocket\Server as Ws;
use think\Db;

class Friend
{

    /**
     * 申请添加好友
     * @param Ws    $ws
     * @param array $param
     * @param int   $apply_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/15 20:19
     */
    public function addBefore(Ws $ws, array $param, int $apply_id): bool
    {
        $uuid  = $param['uuid'];
        $data  = $param['data'] ?? [];
        $to_id = $data['to_id'];
        $user  = ImMember::where('uuid', $uuid)->field('uuid,surname,name,nickname,sex,avatar')->find();
        // 消息内容
        $content      = [
            'cate_id' => 1, // 单聊消息
            'user'    => $user->toArray(),
            'text'    => $user->nickname . '申请添加您为好友'
        ];
        $model        = ImSystemChatMessage::create([
                                                        'uuid'      => $uuid,
                                                        'system_id' => 1,// 验证消息
                                                        'cate_id'   => 1,// 文本类型
                                                        'content'   => json_encode($content, JSON_UNESCAPED_UNICODE),// 消息内容
                                                        'random'    => string_make_guid(),
                                                        'read'      => 0,// 已读人数
                                                        'unread'    => 1,// 未读人数
                                                    ]);
        $msg_id       = $model->id;
        $message      = $model->toArray();
        $message_state        = ImSystemChatMessageState::create([
                                                             'uuid'      => $to_id, //消息接收方
                                                             'system_id' => 1,// 验证消息
                                                             'msg_id'    => $msg_id,// 消息ID
                                                             'msg_uuid'  => $uuid,// 消息发送者ID
                                                             'read'      => 0,// 未读
                                                         ]);
        $msg_state_id = $message_state->id;
        // 在好友申请中记录下系统消息ID,后面用来实现已读功能
        $apply                = ImFriendsApply::get($apply_id);
        $apply->system_msg_id = $msg_id;
        $apply->save();

        //记录申请消息
        $timeline = Timeline::save($uuid, new Event(Event::MESSAGE_RECEIVED), $message);
        $tmp_data1 = R::e($timeline->getEvent(), $timeline->getData());

        // 查询会话信息
//        $convType = new ConvType(ConvType::CONV_SYSTEM);
//        if (Conversation::exist($convType, $uuid, 1)) {
//            Conversation::update($convType, $to_id, 1, ['unread' => 1, 'last_message' => $msg_state_id]);
//        } else {
//            Conversation::create($convType, $to_id, 1, ['unread' => 1, 'last_message' => $msg_state_id]);
//        }

        $session_logic = new Session;
        $model         = new ImSession;
        $where         = ['cate_id' => 3, 'uuid' => $to_id, 'to_id' => 1];
        $session       = $model->where($where)->find();
        if (empty($session)) {  // 创建会话
            $session_logic->create(3, $to_id, 1, false, ['unread' => 1, 'last_message' => $msg_state_id]);
            $session  = $session_logic->getResult();
            //$tmp_data = R::y('Session.new', $session);
            $timeline = Timeline::save($uuid, new Event(Event::CONVERSATION_CREATE), $session);
        } else {    // 更新会话
            /** @noinspection PhpUndefinedMethodInspection */
            $model->where($where)->update(['last_message' => $msg_state_id, 'unread' => Db::raw('unread+1'), 'update_time' => time()]);
            $session  = $model->where('id', $session->id)->find()->toArray();
            //$tmp_data = R::y('Session.changed', $session);
            $timeline = Timeline::save($uuid, new Event(Event::CONVERSATION_UPDATED), $session);
        }
        $tmp_data2 = R::e($timeline->getEvent(), $timeline->getData());
        // 如果消息接收人不在线,则离线推送
        if (empty($fds)) {
            Color::go('Task umeng push coroutine[' . Coroutine::getCid() . ']');
            $use = microtime(true);
            Offline::save($uuid, $msg_id, ConvType::CONV_SYSTEM, MsgType::MSG_SYS_FRIEND, $to_id);
            Color::log("离线消息入队列");
            $use = (microtime(true) - $use) * 1000;
            Color::go("Join push queue of umeng use {$use}ms");
            return false;
        }
        // 如果消息接收人在线,则进行会话推送
        $fds = Online::getFds($ws, $to_id);
        foreach ($fds as $fd) {
            $ws->push($fd, $tmp_data1);
            $ws->push($fd, $tmp_data2);
        }
        return true;
    }

    /**
     * 处理好友申请
     * @param Ws    $ws
     * @param       $apply
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 11:02
     */
    public function applyHandleBefore(Ws $ws, $apply): bool
    {
        //-----------------------好友推送----------------------------------
        $friend_list = ImFriends::with('profile')->where(function ($query) use (&$apply) {
            $query->where(['uuid' => $apply->uuid, 'to_id' => $apply->to_id]);
        })->whereOr(function ($query) use (&$apply) {
            $query->where(['uuid' => $apply->to_id, 'to_id' => $apply->uuid]);
        })->select()->append(['online'])->toArray();
        Push::friend($ws, $friend_list);
        //-----------------------消息推送----------------------------------
        $model          = ImSingleChatMessage::create([
                                                          'uuid'    => $apply->uuid, // 消息发送者UUID
                                                          'cate_id' => 1,// 文本类型
                                                          'content' => json_encode(['text' => "我通过了你的朋友验证请求，现在我们可以开始聊天了"], JSON_UNESCAPED_UNICODE),// 消息内容
                                                          'random'  => string_make_guid()
                                                      ]);
        $message        = $model->toArray();
        $insertAll      = [
            // 插入申请人消息的记录
            [
                'to_id'    => $apply->uuid,  // 会话对方的UUID
                'uuid'     => $apply->to_id, // 消息接收方
                'msg_id'   => $model->id,    // 消息ID
                'msg_uuid' => $apply->uuid,  // 消息发送者ID
                'read'     => 0              // 默认未读
            ],
            // 插入自己的消息记录
            [
                'to_id'    => $apply->to_id, // 会话对方的UUID
                'uuid'     => $apply->uuid,  // 消息接收方
                'msg_id'   => $model->id,    // 消息ID
                'msg_uuid' => $apply->uuid,  // 消息发送者ID
                'read'     => 0              // 显示对方已读
            ],
        ];
        $model          = new ImSingleChatMessageState;
        $message_states = $model->saveAll($insertAll);

        $notify_list = $notify_list2 = [];
        foreach ($message_states as &$message_state) {
            $notify_list[] = [
                'uuid'          => $message_state->uuid,
                'unread'        => $message_state->uuid === $apply->uuid ? 0 : 1,
                'is_disturb'    => false,
                'message_state' => &$message_state,
                'msgType'       => MsgType::MSG_SYS_FRIEND,
                'convType'      => ConvType::CONV_C2C
            ];
            $notify_list2[] = [
                'uuid'          => $message_state->uuid,
                'data'          => ImMember::where('uuid',$message_state->uuid)->select()->toArray()
            ];
        }
        //通知双方好友增加
        foreach ($notify_list2 as $item) {
            $_timeline = Timeline::save($item['uuid'], new Event(Event::FRIEND_CREATE), $item['data']);
            $_tmp_data = R::e($_timeline->getEvent(), $_timeline->getData());
            $fds = Online::getFds($ws, $item['uuid']);
            foreach ($fds as $fd) {
                $ws->push($fd, $_tmp_data);
            }
        }
        //通知双方会话更新
        Push::messageNew($ws, $notify_list, $message, 1);
        return true;
    }
}
