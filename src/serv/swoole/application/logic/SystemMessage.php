<?php /** @noinspection PhpUndefinedMethodInspection PhpDynamicAsStaticMethodCallInspection */
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

use app\model_bak\ImSystemChatMessage;
use app\model_bak\ImSystemChatMessageState;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use think\Db;

class SystemMessage extends Logic
{

    public function sync(array $param, $session): bool
    {
        /*        $uuid         = $param['uuid'];
                $data         = $param['data'] ?? [];
                $offset       = $data['offset'];
                $length       = $data['length'];
                $to_id        = $session['to_id'];
                $model        = new ImSystemChatMessageState();
                $where        = ['uuid' => $uuid, 'system_id' => $to_id];
                $count        = $model->where($where)->count();
                $list         = $model->with(['message', 'profile'])->where($where)->order('id DESC')->limit($offset, $length)->select()->append(['apply'])->toArray();
                $nextSeq      = $offset + $length;
                $this->result = [
                    'list'       => $list,
                    'sessionId'  => $session['id'],
                    'nextSeq'    => $nextSeq,
                    'isFinished' => $nextSeq >= $count,
                ];
                // 激活会话
                Session::active(3, $uuid, $to_id);*/
        return true;
    }

    /**
     * 消息为已读
     * @param string $uuid
     * @param        $session
     * @param Ws     $ws
     * @param Frame  $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/29 12:11
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function read(string $uuid, $session, Ws &$ws, Frame &$frame): bool
    {
        $system_id = $session->to_id;
        $where     = ['uuid' => $uuid, 'read' => 0, 'system_id' => $system_id];
        $count     = ImSystemChatMessageState::where($where)->count();
        if (!$count) {
            $this->error = '未读消息数 0';
            return false;
        }
        $msg_ids = ImSystemChatMessageState::where($where)->column('msg_id');
        $msg_ids = array_unique($msg_ids);
        ImSystemChatMessage::where('id', 'in', $msg_ids)->update([
                                                                     'read'   => Db::raw('`read`+1'), // 已读人数+1
                                                                     'unread' => Db::raw('`unread`-1') // 未读人数-1
                                                                 ]);
        // TODO::执行顺序不能乱,必须先更新消息汇总已读未读数量,再更新个人已读
        ImSystemChatMessageState::where($where)->update(['read' => 1]);
        $session->unread = 0;
        $session->save();
        $this->result = [
            'ids'        => $msg_ids,
            'session_id' => $session->id,
        ];
        return true;
    }

    /**
     * withdraw
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/24 14:05
     * @noinspection PhpUnusedParameterInspection
     */
    public function withdraw(array $param, Ws &$ws, Frame &$frame): bool
    {

        return true;
    }

    /**
     * delete
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/24 14:05
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {

        return true;
    }
}
