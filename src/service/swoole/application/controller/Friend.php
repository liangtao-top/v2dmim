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
// | Version: 2.0 2020/9/16 16:55
// +----------------------------------------------------------------------

namespace app\controller;

use app\common\Base;
use app\model_bak\ImFriends;
use app\model_bak\ImFriendsApply;
use app\model_bak\ImSession;
use app\model_bak\ImSystemChatMessage;
use app\model_bak\ImSystemChatMessageState;
use app\service\FriendService;
use app\validate\Friend as Validate;
use com\console\Color;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use think\Db;

/**
 * Class Friend
 * @package app\controller
 */
class Friend extends Base
{
    /**
     * 我的好友列表
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/12 12:10
     * @noinspection DuplicatedCode
     */
    public function index(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->setError($validate->getError());
            return false;
        }
        $offset  = $data['offset'] ?? 0;
        $length  = $data['length'] ?? 50;
        $service = new FriendService;
        $result  = $service->index($uuid, $offset, $length);
        if (!$result) {
            $this->setError($service->getError());
            return false;
        }
        $this->setResult($service->getResult());
        return true;
    }

    /**
     * 查询好友信息
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 14:31
     */
    public function info(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid = $param['uuid'];
        $data = $param['data'] ?? [];
        if (!isset($data['uuid'])) {
            $this->error = '好友的uuid必填';
            return false;
        }
        $model = new ImFriends;
        $where = ['uuid' => $uuid, 'to_id' => $data['uuid']];
        $count = $model->where($where)->count();
        if (!$count) {
            $this->error = '好友不存在';
            return false;
        }
        $this->result = $model->with(['profile'])->where($where)->find()->append(['online'])->toArray();
        return true;
    }

    /**
     * 申请添加好友
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/18 11:44
     * @noinspection DuplicatedCode
     */
    public function add(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $to_id = $data['to_id'];
        if ($uuid === $to_id) {
            $this->error = '自己不能添加自己为好友';
            return false;
        }
        // 我的好友列表中已有对方
        $my_count = ImFriends::where('uuid', $uuid)->where('to_id', $to_id)->count();
        // 对方的好友列表中已有我
        $to_count = ImFriends::where('uuid', $to_id)->where('to_id', $uuid)->count();
        if ($my_count > 0 && $to_count > 0) {
            $this->error = '你们双方已是好友，请勿重复添加';
            return false;
        }
        $where = ['to_id' => $uuid, 'uuid' => $to_id];
        $count = ImFriendsApply::where($where)->where('status', 0)->count();
        if ($count > 0) {
            $this->error = '你的好友申请审核中，请耐心等待';
            return false;
        }
        $model = ImFriendsApply::create(array_merge($where, [
            'rfa'    => $data['rfa'] ?? '',
            'rfr'    => $data['rfr'] ?? '',
            'status' => 0
        ]));
        if (!$model->id) {
            $this->error = '添加好友失败，请稍候再试';
            return false;
        }
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Friend::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $model->id],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $model->id;
        return true;
    }

    /**
     * 删除好友
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/18 11:44
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $to_id = $data['to_id'];
        $uuid  = $param['uuid'];
        $where = ['to_id' => $to_id, 'uuid' => $uuid];
        $count = ImFriends::where($where)->count();
        if (!$count) {
            $this->error = 'ToId:' . $to_id . ' 不是你的好友';
            return false;
        }
        ImFriends::where($where)->delete();
        // ImSession::where('uuid', $uuid)->where('to_id', $to_id)->where('cate_id', 1)->delete();
        return true;
    }

    /**
     * 好友申请列表
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/16 17:08
     * @noinspection DuplicatedCode
     */
    public function applyList(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $offset       = $data['offset'] ?? 0;
        $length       = $data['length'] ?? 50;
        $model        = new ImFriendsApply;
        $count        = $model->where('uuid', $uuid)->count();
        $list         = $model->with(['profile', 'message'])->where('uuid', $uuid)->order('id DESC')->limit($offset, $length)->select()->toArray();
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list,
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

    /**
     * 处理好友申请
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/18 16:16
     */
    public function applyHandle(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $status   = $data['status'];
        $apply_id = $data['apply_id'];
        $apply    = ImFriendsApply::get($apply_id);
        switch ($apply->status) {
            case 1:
                $this->error = '已通过,请勿重复重操作';
                return false;
            case 2:
                $this->error = '已拒绝,请勿重复重操作';
                return false;
        }
        $to_id         = $apply->to_id;
        $system_msg_id = $apply->system_msg_id;
        if ($uuid !== $apply->uuid) {
            $this->error = '这条申请记录你无权审批';
            return false;
        }
        $model = new ImFriends;
        switch ((int)$status) {
            case 1: // 同意
                $apply->status = 1;
                $apply->save();
                // 我添加对方为我的好友
                $map    = ['uuid' => $uuid, 'to_id' => $to_id];
                $friend = $model->where($map)->find();
                if (empty($friend)) {
                    $model->allowField(true)->isUpdate(false)->data($map)->save();
                } else {
                    $friend->update_time = time();
                    $friend->save();
                }
                // 对方添加我为他的好友
                $map    = ['to_id' => $uuid, 'uuid' => $to_id];
                $friend = $model->where($map)->find();
                if (empty($friend)) {
                    $model->allowField(true)->isUpdate(false)->data($map)->save();
                } else {
                    $friend->update_time = time();
                    $friend->save();
                }
                // 投递异步任务
                $task_id = $ws->task([
                                         'class'  => \app\task\Friend::class,
                                         'method' => __FUNCTION__,
                                         'arg'    => [$apply],
                                     ]);
                Color::task("Dispatch AsyncTask: id=$task_id");
                break;
            case 2: // 拒绝
                $apply->status = 2;
                $apply->rfr    = $data['rfr'];
                $apply->save();
                break;
            case 3: // 忽略
                $apply->status = 3;
                $apply->save();
                break;
        }
        // 更新为系统消息已读
        ImSystemChatMessage::where('id', $system_msg_id)->update([
                                                                     'read'   => Db::raw('`read`+1'), // 已读人数+1
                                                                     'unread' => Db::raw('`unread`-1') // 未读人数-1
                                                                 ]);
        ImSystemChatMessageState::where('msg_id', $system_msg_id)->where('uuid', $uuid)->update(['read' => 1]);
        // 会话未读消息数-1
        $model = ImSession::get(['cate_id' => 3, 'uuid' => $uuid, 'to_id' => 1]);
        if (!empty($model)) {
            $model->unread      = Db::raw('`unread`-1');
            $model->update_time = time();
            $model->save();
            $this->result = $model->id;
        }
        return true;
    }

    /**
     * 修改昵称
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/27 10:08
     */
    public function nickname(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $where = ['uuid' => $uuid, 'to_id' => $data['member_id']];
        $model = ImFriends::get($where);
        if (empty($model)) {
            $this->error = '对方不是你的好友';
            return false;
        }
        $model->remark = $data['nickname'];
        $model->save();
        $this->result = $model->toArray();
        return true;
    }

    /**
     * star 星标好友
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 16:38
     * @noinspection DuplicatedCode
     */
    public function star(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $toId = $data['to_id'];
        $info = ImFriends::where('uuid', $uuid)->where('to_id', '=', $toId)
                         ->field('is_star')->find();
        if (empty($info)) {
            $this->error = '您还不是他的好友，无法设置为星标';
            return false;
        }
        if (array_key_exists('force', $data) && isset($data['force'])) {
            $info->is_star = (int)(bool)$data['force'];
        } else {
            $info->is_star = !$info['is_star'];
        }
        $info->save();
        $this->result = $info->is_star;
        return true;
    }

    /**
     * 设置消息免打扰
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/10 11:19
     * @noinspection DuplicatedCode
     */
    public function disturb(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $toId = $data['to_id'];
        $info = ImFriends::where('uuid', $uuid)->where('to_id', '=', $toId)->field('disturb')->find();
        if (empty($info)) {
            $this->error = '你还不是他的好友，无法设置免打扰';
            return false;
        }
        if (array_key_exists('force', $data) && isset($data['force'])) {
            $info->disturb = (int)(bool)$data['force'];
        } else {
            $info->disturb = !$info['disturb'];
        }
        $info->save();
        // 单向免打扰
        $model             = ImSession::get(['cate_id' => 1, 'uuid' => $uuid, 'to_id' => $toId]);
        $model->is_disturb = $info->disturb;
        $model->save();
        $this->result = ['session_id' => $model->id, 'disturb' => $info->disturb];
        return true;
    }
}
