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
// | Version: 2.0 2020/9/14 14:49
// +----------------------------------------------------------------------

namespace app\logic;

use app\model_bak\ImSession;
use app\model_bak\ImSingleChatMessage;
use app\model_bak\ImSingleChatMessageState;
use app\task\Single;
use com\console\Color;
use com\enum\Enum;
use com\event\Event;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

class SingleMessage extends Logic
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
     * @date         2021/1/12 19:39
     * @noinspection DuplicatedCode
     */
    public function sync(array $param, $session): bool
    {
        $uuid   = $param['uuid'];
        $data   = $param['data'] ?? [];
        $offset = $data['offset'];
        $length = $data['length'];
        $to_id  = $session['to_id'];
        $list   = [];
        $model  = new ImSingleChatMessageState();
        $where  = ['uuid' => $uuid, 'to_id' => $to_id];
        $count  = $model->where($where)->count();
        if ($count > 0) {
            $offset = $offset === 0 ? ($count > 15 ? $count - 15 : $count) : $offset;
            $list   = $model->with(['message'])->where($where)->order('id DESC')->limit($offset, $length)->select()->toArray();
        }
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list,
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

    /**
     * 聊天记录
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/15 11:18
     * @noinspection PhpUnusedParameterInspection
     */
    public function list(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid      = $param['uuid'];
        $data      = $param['data'];
        $to_id     = $data['to_id'];
        $cate_id   = $data['cate_id'];
        $page      = $data['page'] ?? 1;
        $list_rows = $data['list_rows'] ?? 15;
        $where     = ['uuid' => $uuid, 'to_id' => $to_id, 'cate_id' => $cate_id];
        $count     = ImSession::where($where)->count();
        if (!$count) {
            Color::warning('会话不存在 ' . json_encode(['uuid' => $uuid, 'to_id' => $to_id, 'cate_id' => $cate_id]));
            $this->error = '会话不存在';
            return false;
        }
        $session_id = ImSession::where($where)->value('id');
        $model      = new ImSingleChatMessageState();
        $list       = $model->with(['message'])->where('uuid', $uuid)->where('to_id', $to_id)->order('msg_id DESC')->limit(($page - 1) * $list_rows, $list_rows)->select()->toArray();

        // 激活会话
        Session::active(1, $param['uuid'], $data['to_id']);
        $this->result = [
            'message'    => $list,
            'session_id' => $session_id,
        ];
        return true;
    }

    /**
     * 更改消息状态为已读
     * @param string $uuid
     * @param        $session
     * @param Ws     $ws
     * @param Frame  $frame
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/13 17:35
     * @noinspection PhpUnusedParameterInspection
     */
    public function read(string $uuid, $session, Ws &$ws, Frame &$frame): bool
    {
        $to_id    = $session['to_id'];
        $my_where = ['msg_uuid' => $to_id, 'uuid' => $uuid, 'read' => 0];
        $to_where = ['msg_uuid' => $to_id, 'uuid' => $to_id, 'read' => 0];
        $my_ids   = ImSingleChatMessageState::where($my_where)->column('id');
        $to_ids   = ImSingleChatMessageState::where($to_where)->column('id');
        if (count($my_ids) === 0 && count($to_ids) === 0) {
            Color::warning("会话[{$session['id']}]暂无未读消息");
            return true;
        }
        // 更新双方消息列表
        ImSingleChatMessageState::where(function ($query) use ($my_where) {
            $query->where($my_where);
        })->whereOr(function ($query) use ($to_where) {
            $query->where($to_where);
        })->update(['read' => 1]);
        // 会话未读消息数
        $session->unread = 0;
        $session->save();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => Single::class,
                                 'method' => 'read',
                                 'arg'    => [$session, $to_ids],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = [
            'ids'        => $my_ids,
            'session_id' => $session['id'],
        ];
        return true;
    }

    /**
     * 标记为撤回
     * @user zmq <zmq3821@163.com>
     * @date 2021/4/28 10:55
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
        $model  = ImSingleChatMessage::get($msg_id);
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
        ImSingleChatMessage::where('id', $msg_id)->update(['retract' => 1]);
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => Single::class,
                                 'method' => 'revoked',
                                 'arg'    => [$param, $msg_id],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $msg_id;
        return true;
    }

    /**
     * 标记为删除
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/13 21:20
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid         = $param['uuid'];
        $data         = $param['data'];
        $msg_state_id = $data['msg_state_id'];
        $state        = ImSingleChatMessageState::get($msg_state_id);
        if (empty($state)) {
            $this->error = 'msg_state_id参数错误';
            return false;
        }

        if ($state['uuid'] !== $uuid) {
            $this->error = '删除的消息必须是自己发布的';
            return false;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $state->delete_time = time();
        $state->save();
        $session = Session::find(1, $state['uuid'], $state['to_id']);
        //记录消息删除
        Timeline::save($state['uuid'], new Event(Event::CONVERSATION_UPDATED), $session->toArray());
        return true;
    }
}
