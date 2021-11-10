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
// | Version: 2.0 2020/10/31 21:05
// +----------------------------------------------------------------------

namespace app\task;

use app\command\R;
use app\logic\Offline;
use app\logic\Session;
use app\logic\Online;
use app\model_bak\ImSession;
use app\model_bak\ImSingleChatMessageState;
use app\logic\Timeline;
use com\console\Color;
use com\db\PDOPool;
use com\event\Event;
use Swoole\WebSocket\Server as Ws;
use think\Db;

class Single
{

    /**
     * 发送消息 - 消息存储及推送
     * @param \Swoole\WebSocket\Server $ws
     * @param array                    $param
     * @param array                    $message
     * @return bool
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/14 9:21
     */
    public function sendBefore(Ws $ws, array $param, array $message): bool
    {
        $data = $param['data'] ?? [];
//        $use                             = microtime(true);
        $model_single_chat_message_state = new ImSingleChatMessageState;
//        Color::go('new ImSingleChatMessageState ' . ((microtime(true) - $use) * 1000) . 'ms');
//        $use       = microtime(true);
        $insertAll = [
            ['uuid' => $param['uuid'], 'to_id' => $data['to_id'], 'msg_id' => $message['id'], 'msg_uuid' => $param['uuid'], 'read' => 0],
            ['uuid' => $data['to_id'], 'to_id' => $param['uuid'], 'msg_id' => $message['id'], 'msg_uuid' => $param['uuid'], 'read' => 0]
        ];
        $model_single_chat_message_state->allowField(false)->saveAll($insertAll);
//        Color::go('ImSingleChatMessageState saveAll ' . ((microtime(true) - $use) * 1000) . 'ms');
//        $use           = microtime(true);
        $model_session = new ImSession;
//        Color::go('new ImSession ' . ((microtime(true) - $use) * 1000) . 'ms');
//        $use   = microtime(true);
        $logic = new Session;
//        Color::go('new Session ' . ((microtime(true) - $use) * 1000) . 'ms');
//        $use                = microtime(true);
        $message_state_list = $model_single_chat_message_state->with(['message'])->where('msg_id', $message['id'])->select()->toArray();
//        Color::go('message_state_list ' . ((microtime(true) - $use) * 1000) . 'ms');
//        $use2 = microtime(true);
        foreach ($message_state_list as &$value) {
            $timeline = [];
            if (Session::exist(1, $value['uuid'], $value['to_id'])) {     // 更新会话
//                $use = microtime(true);
                $model_session->where(['cate_id' => 1, 'uuid' => $value['uuid'], 'to_id' => $value['to_id']])->update(['update_time' => time(), 'last_message' => $value['id']]);
//                Color::go('ImSingleChatMessageState update ' . ((microtime(true) - $use) * 1000) . 'ms');
//                $use     = microtime(true);
                $session = $model_session->where(['cate_id' => 1, 'uuid' => $value['uuid'], 'to_id' => $value['to_id']])->find()->toArray();
//                Color::go('ImSingleChatMessageState select ' . ((microtime(true) - $use) * 1000) . 'ms');
//                $use        = microtime(true);
                $timeline[] = Timeline::save($value['uuid'], new Event(Event::CONVERSATION_UPDATED), $session);
//                Color::go('CONVERSATION_UPDATED Timeline::save ' . ((microtime(true) - $use) * 1000) . 'ms');
            } else { // 创建会话
                $logic->create(1, $value['uuid'], $value['to_id'], false, ['unread' => 1, 'last_message' => $value['id']]);
                $timeline[] = Timeline::save($value['uuid'], new Event(Event::CONVERSATION_CREATE), $logic->getResult());
            }
            // 新增消息
//            $use        = microtime(true);
            $timeline[] = Timeline::save($value['uuid'], new Event(Event::MESSAGE_RECEIVED), $value);
//            Color::go('MESSAGE Timeline::save ' . ((microtime(true) - $use) * 1000) . 'ms');
            print_r($value['uuid']);
            $fds = Online::getFds($ws, $value['uuid']);
            print_r($fds);
//            $use = microtime(true);
            if (empty($fds)) {
                // 如果消息接收人不在线,则放入离线推送队列
                if ($value['uuid'] !== $param['uuid']) {
                    Offline::save($value['message']['uuid'], $value['message']['id'], 1, $value['message']['cate_id'], $value['uuid']);
                }
//                Color::go('Offline::save ' . ((microtime(true) - $use) * 1000) . 'ms');
            } else {
                // 如果消息接收人在线,则进行会话推送
                foreach ($fds as $fd) {
                    foreach ($timeline as $data) {
                        $ws->push($fd, R::e($data->getEvent(), $data->getData()));
                    }
                }
//                Color::go('ws->push ' . ((microtime(true) - $use) * 1000) . 'ms');
            }
        }
//        Color::go('foreach ' . ((microtime(true) - $use2) * 1000) . 'ms');
        unset($value);
        return true;
    }


    /**
     * sendAfter
     * @param Ws    $ws
     * @param array $param
     * @param array $message
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/11 10:20
     */
    public function sendAfter(Ws $ws, array $param, array $message, $data = null)
    {

    }

    /**
     * readBefore
     * @param Ws    $ws
     * @param       $session
     * @param array $ids
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/13 17:44
     */
    public function readBefore(Ws $ws, $session, array $ids): bool
    {
        $where = ['cate_id' => 1, 'uuid' => $session['to_id'], 'to_id' => $session['uuid']];
        if (ImSession::where($where)->count()) {
            $session = ImSession::where($where)->find();
            // 如果消息接收人不在线
            $fds = Online::getFds($ws, $session['uuid']);
            if (empty($fds)) {
                return false;
            }
            // 通知发送方消息被已读
            if ($session['is_active']) {
                $data = R::y('Message.c2cReadReceipt', ['ids' => $ids, 'session_id' => $session['id']]);
                foreach ($fds as $fd) {
                    $ws->push($fd, $data);
                }
            }
        }
        return true;
    }

    /**
     * readAfter
     * @param Ws    $ws
     * @param       $session
     * @param array $ids
     * @param null  $data
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/13 17:44
     */
    public function readAfter(Ws $ws, $session, array $ids, $data = null)
    {
    }

    /**
     * revokedBefore
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
        $list = ImSingleChatMessageState::where('msg_id', $msg_id)->where('uuid', 'neq', $param['uuid'])->field('id,uuid,to_id')->select();
        foreach ($list as $value) {
            // 如果消息接收人不在线
            $fds = Online::getFds($ws, $value['uuid']);
            if (empty($fds)) {
                return false;
            }
            $session = Session::find(1, $value['uuid'], $value['to_id']);
            // 通知接收方消息被撤回
            if ($session['is_active']) {
                $data = R::y('Message.c2cRevokedReceipt', ['session_id' => $session['id'], 'msg_state_id' => $value['id']]);
                foreach ($fds as $fd) {
                    $ws->push($fd, $data);
                }
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

}
