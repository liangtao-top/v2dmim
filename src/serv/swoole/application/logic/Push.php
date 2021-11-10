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
// | Version: 2.0 2020/9/24 9:16
// +----------------------------------------------------------------------

namespace app\logic;

use app\common\R;
use app\model_bak\ImSession;
use com\console\Color;
use app\logic\Session as Logic;
use app\struct\Event;
use Swoole\Coroutine;
use Swoole\WebSocket\Server as Ws;
use think\Db;

/**
 * 服务器主动推送
 * @package app\logic
 */
class Push extends Logic
{

    /**
     * friend
     * @param Ws    $ws
     * @param array $friend_list
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/16 11:56
     */
    public static function friend(Ws &$ws, array &$friend_list): void
    {
        foreach ($friend_list as &$friend) {
            $fds = Online::getFds($ws, $friend['uuid']);
            if (!empty($fds)) {
                //$push_data = R::y('Friend.new', $friend);
                $push_data = R::e(Event::MESSAGE_RECEIVED, $friend);
                // 在线则进行会话推送
                foreach ($fds as $fd) {
                    $ws->push($fd, $push_data);
                }
            }
        }
    }

    /**
     * message
     * @param Ws    $ws
     * @param array $notify_list
     * @param array $message
     * @param int   $cate_id
     * @param bool  $offline
     * @param array $identifier_uuids
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 11:21
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function message(Ws &$ws, array &$notify_list, array &$message, int $cate_id, bool $offline = true, array $identifier_uuids = []): void
    {
        $yield         = [];
        $session_logic = new Session;
        $session_model = new ImSession;
        foreach ($notify_list as &$notify) {
            [
                'uuid'          => &$uuid,
                'unread'        => &$unread,
                'message_state' => &$message_state,
                'is_disturb'    => &$is_disturb
            ] = $notify;
            switch ($cate_id) {
                case 1:
                    $to_id = $message_state->to_id;
                    $state = $message_state->toArray();
                    break;
                case 2:
                    $to_id = $message_state->group_id;
                    $state = $message_state->append(['identifier', 'profile'])->toArray();
                    break;
                case 3:
                    $to_id = $message_state->system_id;
                    $state = $message_state->toArray();
                    break;
                default:
                    throw new \think\Exception("会话类型异常");
            }
            // 查询会话信息
            $where   = ['cate_id' => $cate_id, 'uuid' => $uuid, 'to_id' => $to_id];
            $session = $session_model->where($where)->find();
            if (empty($session)) {  // 创建会话
                $extend                 = [];
                $extend['unread']       = $unread;
                $extend['last_message'] = $message_state['id'];
                if (isset($notify['is_mute'])) { // 单人禁言
                    $update['is_mute'] = $notify['is_mute'];
                }
                if (isset($notify['is_mute_all'])) { // 全员禁言
                    $extend['is_mute_all'] = $notify['is_mute_all'];
                }
                $session_logic->create($cate_id, $uuid, $to_id, false, $extend);
                $session  = $session_logic->getResult();
                $tmp_data = R::y('Session.new', $session);
            } else {    // 更新会话
                $update = [
                    'update_time'  => time(),
                    'last_message' => $message_state['id'],
                ];
                if ($unread > 0) { // 未读数
                    /** @noinspection PhpUndefinedMethodInspection */
                    $update['unread'] = Db::raw('unread+' . $unread);
                }
                if (isset($notify['is_mute'])) { // 单人禁言
                    $update['is_mute'] = $notify['is_mute'];
                }
                if (isset($notify['is_mute_all'])) { // 全员禁言
                    $update['is_mute_all'] = $notify['is_mute_all'];
                }
                $session_model->where($where)->update($update);
                $session  = $session_model->where('id', $session['id'])->find()->toArray();
                $tmp_data = R::y('Session.changed', $session);
            }
            $fds = Online::getFds($ws, $uuid);
            // 如果消息接收人不在线,则离线推送
            if (empty($fds)) {
                if ($offline) {
                    // 没有设置免打扰,则进入离线推送列表
                    if (!$is_disturb) {
                        $yield[] = $uuid;
                    } else {
                        // 虽然设置了免打扰,但是被@了,依赖进入离线推送列表
                        if (isset($identifier_uuids) && is_array($identifier_uuids) && in_array($uuid, $identifier_uuids)) {
                            $yield[] = $uuid;
                        }
                    }
                }
                continue;
            }
            // 在线则进行会话推送
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
            $is_active = Session::isActive($cate_id, $uuid, $to_id);
            // 如果会话处于激活状态,则进行消息推送
            if ($is_active) {
                $state['message']    = &$message;
                $state['session_id'] = $session['id'];
                // 推送消息
                $tmp_data = R::y('Message.new', $state);
                foreach ($fds as $fd) {
                    $ws->push($fd, $tmp_data);
                }
            }
        }
        // 离线推送
        if (!empty($yield) && $offline) {
            Color::go('Task coroutine[' . Coroutine::getCid() . '] push umeng');
            $use = microtime(true);
            $res = app_push($yield, '你有一条新消息');
            if ($res !== true) Color::error($res);
            $use = (microtime(true) - $use) * 1000;
            Color::go("Finish all umeng push use {$use}ms");
        }
    }

    /**
     * 推送会话
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/11 13:55
     * @param \Swoole\WebSocket\Server $ws
     * @param array $notify_list
     * @param array $message
     * @param int $cate_id
     * @param bool $offline
     * @param array $identifier_uuids
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function messageNew(Ws &$ws, array &$notify_list, array &$message, int $cate_id, bool $offline = true, array $identifier_uuids = []): void
    {
        $yield         = [];
        $session_logic = new Session;
        $session_model = new ImSession;
        foreach ($notify_list as &$notify) {
            [
                'uuid'          => &$uuid,
                'unread'        => &$unread,
                'message_state' => &$message_state,
                'is_disturb'    => &$is_disturb,
                'msgType'       => $msgType,
                'convType'      => $convType
            ] = $notify;
            switch ($cate_id) {
                case 1:
                    $to_id = $message_state->to_id;
                    $state = $message_state->toArray();
                    break;
                case 2:
                    $to_id = $message_state->group_id;
                    $state = $message_state->append(['identifier', 'profile'])->toArray();
                    break;
                case 3:
                    $to_id = $message_state->system_id;
                    $state = $message_state->toArray();
                    break;
                default:
                    throw new \think\Exception("会话类型异常");
            }
            // 查询会话信息
            $where   = ['cate_id' => $cate_id, 'uuid' => $uuid, 'to_id' => $to_id];
            $session = $session_model->where($where)->find();
            if (empty($session)) {  // 创建会话
                $extend                 = [];
                $extend['unread']       = $unread;
                $extend['last_message'] = $message_state['id'];
                if (isset($notify['is_mute'])) { // 单人禁言
                    $update['is_mute'] = $notify['is_mute'];
                }
                if (isset($notify['is_mute_all'])) { // 全员禁言
                    $extend['is_mute_all'] = $notify['is_mute_all'];
                }
                $session_logic->create($cate_id, $uuid, $to_id, false, $extend);
                $session  = $session_logic->getResult();
                //$tmp_data = R::y('Session.new', $session);
                $timeline = Timeline::save($uuid, new Event(Event::CONVERSATION_CREATE), $session);
            } else {    // 更新会话
                $update = [
                    'update_time'  => time(),
                    'last_message' => $message_state['id'],
                ];
                if ($unread > 0) { // 未读数
                    /** @noinspection PhpUndefinedMethodInspection */
                    $update['unread'] = Db::raw('unread+' . $unread);
                }
                if (isset($notify['is_mute'])) { // 单人禁言
                    $update['is_mute'] = $notify['is_mute'];
                }
                if (isset($notify['is_mute_all'])) { // 全员禁言
                    //$update['is_mute_all'] = $notify['is_mute_all'];
                }
                $session_model->where($where)->update($update);
                $session  = $session_model->where('id', $session['id'])->find()->toArray();
                //$tmp_data = R::y('Session.changed', $session);
                $timeline = Timeline::save($uuid, new Event(Event::CONVERSATION_UPDATED), $session);
            }
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            $fds = Online::getFds($ws, $uuid);
            // 如果消息接收人不在线,则离线推送
            if (empty($fds) && $offline) {
                // 没有设置免打扰,则进入离线推送列表
                if (!$is_disturb) {
                    //$yield[] = $uuid;
                    $yield[] = [
                        'msgID'     => $state['msg_id'],
                        'msgType'   => $msgType,
                        'convType'  => $convType,
                        'userID'    => $to_id,
                        'groupID'   => $cate_id == 3 ? $to_id : 0,
                        'receiveID' => $uuid,
                    ];
                } else {
                    // 虽然设置了免打扰,但是被@了,依赖进入离线推送列表
                    if (isset($identifier_uuids) && is_array($identifier_uuids) && in_array($uuid, $identifier_uuids)) {
                        //$yield[] = $uuid;
                        $yield[] = [
                            'msgID'     => $state['msg_id'],
                            'msgType'   => $msgType,
                            'convType'  => $convType,
                            'userID'    => $to_id,
                            'groupID'   => $cate_id == 3 ? $to_id : 0,
                            'receiveID' => $uuid,
                        ];
                    }
                }
                continue;
            }
            // 在线则进行会话推送
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
        }
        // 离线推送
        if (!empty($yield) && $offline) {
            Color::log("推送消息");
            Color::go('Task coroutine[' . Coroutine::getCid() . '] push umeng');
            $use = microtime(true);
            //$res = app_push($yield, '你有一条新消息');
            //if ($res !== true) Color::error($res);
            foreach ($yield as $yi) {
                Color::log(json_encode($yi,JSON_UNESCAPED_UNICODE));
                Offline::save($yi['userID'], $yi['msgID'], $yi['convType'], $yi['msgType'], $yi['receiveID'], $yi['groupID']);
                Color::log("离线消息入队列");
            }
            $use = (microtime(true) - $use) * 1000;
            Color::go("Finish all umeng push use {$use}ms");
        }
    }


    /**
     * 推送群资料变更
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/11 13:55
     * @param \Swoole\WebSocket\Server $ws
     * @param array $notify_list
     */
    public static function group(Ws &$ws, array $notify_list): void
    {
        //推送群资料变更
        foreach ($notify_list as $notify) {
            $timeline = Timeline::save($notify['uuid'], $notify['event'], $notify['data']);
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            // 在线则进行会话推送
            $fds = Online::getFds($ws, $notify['uuid']);
            if (empty($fds)) continue;
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
        }
    }

    /**
     * 推送群成员资料变更
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/11 13:55
     * @param \Swoole\WebSocket\Server $ws
     * @param array $notify_list
     */
    public static function groupMember(Ws &$ws, array $notify_list): void
    {
        //推送群成员资料变更
        foreach ($notify_list as $notify) {
            $timeline = Timeline::save($notify['uuid'], $notify['event'], $notify['data']);
            $tmp_data = R::e($timeline->getEvent(), $timeline->getData());
            // 在线则进行会话推送
            $fds = Online::getFds($ws, $notify['uuid']);
            if (empty($fds)) continue;
            foreach ($fds as $fd) {
                $ws->push($fd, $tmp_data);
            }
        }

    }

}
