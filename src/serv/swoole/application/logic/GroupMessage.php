<?php /** @noinspection PhpUnusedParameterInspection PhpParameterByRefIsNotUsedAsReferenceInspection PhpFullyQualifiedNameUsageInspection PhpUndefinedMethodInspection DuplicatedCode PhpDynamicAsStaticMethodCallInspection */
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2020/9/14 14:49
// +----------------------------------------------------------------------

namespace app\logic;

use app\model_bak\ImGroupChatMessage;
use app\model_bak\ImGroupChatMessageIdentifier;
use app\model_bak\ImGroupChatMessageState;
use com\console\Color;
use com\event\Event;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use think\Db;

class GroupMessage extends Logic
{

    /**
     * sync
     * @param array $param
     * @param       $session
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 10:17
     */
    public function sync(array $param, $session): bool
    {
        $uuid   = $param['uuid'];
        $data   = $param['data'] ?? [];
        $offset = $data['offset'];
        $length = $data['length'];
        $to_id  = $session['to_id'];
        $list   = [];
        $model  = new ImGroupChatMessageState();
        $where  = ['group_id' => $to_id, 'uuid' => $uuid];
        $count  = $model->where($where)->count();
//        if ($count > 0) {
//            $offset = $offset === 0 ? ($count > 15 ? $count - 15 : $count) : $offset;
//            print_r($model->where($where)->order('id DESC')->fetchSql()->limit($offset, $length)->select());
//            $list   = $model->with(['message', 'profile', 'identifier'])->where($where)->order('id DESC')->limit($offset, $length)->select()->toArray();
//
//        }
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list,
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

    /**
     * 已读
     * @param string $uuid
     * @param        $session
     * @param Ws     $ws
     * @param Frame  $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 19:58
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function read(string $uuid, &$session, Ws &$ws, Frame &$frame): bool
    {
        $model    = new ImGroupChatMessageState;
        $group_id = $session['to_id'];
        $where    = ['uuid' => $uuid, 'group_id' => $group_id, 'read' => 0];
        $count    = $model->where($where)->count();
        if (!$count) {
            $this->error = "会话[{$session['id']}]暂无未读消息";
            return false;
        }
        $message_state = $model->where($where)->field('id,msg_id,msg_uuid')->select()->toArray();
        $msg_ids       = array_column($message_state, 'msg_id');
        // 更新消息的已读/未读数
        ImGroupChatMessage::where('id', 'in', $msg_ids)->update([
                                                                    'read'   => Db::raw('`read`+1'), // 已读人数+1
                                                                    'unread' => Db::raw('`unread`-1') // 未读人数-1
                                                                ]);
        // 更新个人消息为已读
        $model->where($where)->update(['read' => 1]);
        // 如果当前消息有@我,则更新@已读
        ImGroupChatMessageIdentifier::where($where)->where('msg_id', 'in', $msg_ids)->update(['read' => 1]);
        // 会话未读消息数归零
        $session->unread = 0;
        $session->save();
        // 通知列表
        $notify_list = [];
        foreach ($message_state as $value) {
            $notify_list[$value['msg_uuid']][] = $value['msg_id'];
        }
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => 'read',
                                 'arg'    => [$group_id, $notify_list],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = [
            'ids'        => array_column($message_state, 'id'),
            'session_id' => $session['id'],
        ];
        return true;
    }

    /**
     * 标记消息为撤回
     * @user zmq <zmq3821@163.com>
     * @date 2021/4/28 10:40
     * @param array                    $param
     * @param \Swoole\WebSocket\Server $ws
     * @param \Swoole\WebSocket\Frame  $frame
     * @return bool
     * @throws \think\exception\DbException
     */
    public function withdraw(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid   = $param['uuid'];
        $data   = $param['data'] ?? [];
        $msg_id = $data['msg_id'];
        $model  = ImGroupChatMessage::get($msg_id);
        if (!$model) {
            $this->error = '消息不存在';
            return false;
        }
        if ($model['retract']) {
            $this->result = $msg_id;
            return true;
        }
        if ($model['uuid'] !== $uuid) {
            $this->error = '撤回的消息必须是自己发布的';
            return false;
        }

        // 标记消息为撤回
        ImGroupChatMessage::where('id', $msg_id)->update(['retract' => 1]);
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => 'revoked',
                                 'arg'    => [$param, $msg_id],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $msg_id;
        return true;
    }

    /**
     * delete
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 20:38
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid         = $param['uuid'];
        $data         = $param['data'];
        $msg_state_id = $data['msg_state_id'];
        $msg_state    = ImGroupChatMessageState::get($msg_state_id);
        if (empty($msg_state)) {
            $this->error = 'msg_state_id参数错误';
            return false;
        }
        if ($msg_state['uuid'] !== $uuid) {
            $this->error = '删除的消息必须是自己发布的';
            return false;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $msg_state->delete_time = time();
        $msg_state->save();
        $session = Session::find(2, $msg_state['uuid'], $msg_state['to_id']);
        //记录消息删除
        Timeline::save($msg_state['uuid'], new Event(Event::CONVERSATION_UPDATED), $session->toArray());
        return true;
    }

}
