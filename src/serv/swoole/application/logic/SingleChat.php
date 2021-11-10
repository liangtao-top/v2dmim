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
// | Version: 2.0 2020/9/11 17:16
// +----------------------------------------------------------------------

namespace app\logic;

use app\task\Single;
use com\console\Color;
use app\logic\Session as Logic;
use app\model_bak\ImFriends;
use app\model_bak\ImMember;
use app\model_bak\ImSingleChatMessage;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

/**
 * Class SingleChat
 * @package app\logic
 */
class SingleChat extends Logic
{

    /**
     * send
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/11 18:01
     * @noinspection DuplicatedCode
     */
    public function send(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid  = $param['uuid'];
        $data  = $param['data'] ?? [];
        $to_id = $data['to_id'];
        if (!$this->checkUuid($to_id)) {
            return false;
        }
        $where = ['to_id' => $uuid, 'uuid' => $to_id];
        if (!(new ImFriends)->where($where)->count()) {
            $this->error = '你不是对方的好友，请添加为好友后再试';
            return false;
        }
        $insert = [
            'uuid'    => $uuid,
            'cate_id' => $data['type_id'],
            'content' => $data['content'],
            'retract' => 0 // 是否撤回 0:否 1:是
        ];
        if (isset($data['random']) && !empty($data['random'])) {
            $insert['random'] = $data['random'];
        }
        $model = new ImSingleChatMessage();
        $model->allowField(true)->data($insert)->save();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => Single::class,
                                 'method' => 'send',
                                 'arg'    => [$param, $model->toArray()],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 检测用户是否存在,防止产生垃圾数据
     * @param $uuid
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 12:05
     */
    private function checkUuid($uuid): bool
    {
        $model = new ImMember;
        $count = $model->where('uuid', $uuid)->count();
        if ($count > 0) {
            return true;
        }
        $this->error = 'to_id错误,用户不存在';
        return false;
    }
}
