<?php
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
use app\logic\GroupChat;
use com\console\Color;
use app\model_bak\ImGroupChatAdminAuth;
use app\model_bak\ImGroupChatMessage;
use app\model_bak\ImGroupChatMessageState;
use app\model_bak\ImSession;
use app\model_bak\ImGroupChat;
use app\model_bak\ImGroupChatApply;
use app\model_bak\ImGroupChatMember;
use app\model_bak\ImMember;
use app\model_bak\ImSystemChatMessage;
use app\model_bak\ImSystemChatMessageState;
use app\validate\Group as Validate;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use think\Db;
use think\Env;

class Group extends Base
{

    /**
     * 我的群组列表
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/12 17:35
     * @noinspection DuplicatedCode
     */
    public function index(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $offset = $data['offset'] ?? 0;
        $length = $data['length'] ?? 50;
        $list   = [];
        $count  = (new ImGroupChatMember)->where('uuid', $uuid)->count();
        if ($count > 0) {
            $group_data = (new ImGroupChatMember)->where('uuid', $uuid)->select();
            $temp_array = [];
            foreach ($group_data as $vo) {
                $temp_array[$vo['group_id']] = $vo;
            }
            $group_id = array_unique(array_column($group_data->toArray(), 'group_id'));
            $list     = (new ImGroupChat)->where('id', 'in', $group_id)->order('id ASC')->limit($offset, $length)->select();
            foreach ($list as &$item) {
                $item['self_info'] = (bool)$temp_array[$item['id']];
            }
        }
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list->toArray(),
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

    /**
     * 群成员列表
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 14:58
     */
    public function member(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $uuid     = $param['uuid'];
        $offset   = $data['offset'] ?? 0;
        $length   = $data['length'] ?? 50;
        $group_id = $data['group_id'];
        $list     = [];
        $model    = new ImGroupChatMember;
        $count    = $model->where('group_id', $group_id)->count();
        if ($count > 0) {
            $owner_id = ImGroupChat::where('id', $group_id)->value('owner_id');
            $append   = [];
            if ($uuid === $owner_id) {
                $append[] = 'permission';
            }
            if ($offset === 0) {
                $temp_lord = $model->with(['profile'])->where('group_id', $group_id)->where('uuid', $owner_id)->find();
                if ($temp_lord) {
                    $lord = $temp_lord->append($append)->toArray();
                }
            }
            $list = $model->with(['profile'])->where('group_id', $group_id)->where('uuid', 'neq', $owner_id)->order('is_admin DESC,id ASC')->limit($offset, ($offset === 0 ? $length - 1 : $length))->select()->append($append)->toArray();
            isset($lord) && array_unshift($list, $lord);
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
     * 读取群内单个成员资料
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 12:04
     */
    public function singleMember(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $to_id    = $data['to_id'] ?? 50;
        $group_id = $data['group_id'];
        $model    = new ImGroupChatMember;
        $where    = ['group_id' => $group_id, 'uuid' => $to_id];
        $count    = $model->where($where)->count();
        if (!$count) {
            $this->error = '他不是群成员';
            return false;
        }
        $owner_id = ImGroupChat::where('id', $group_id)->value('owner_id');
        $append   = [];
        $uuid     = $param['uuid'];
        if ($uuid === $owner_id) {
            $append[] = 'permission';
        }
        $this->result = $model->with(['profile'])->where($where)->find()->append($append)->toArray();;
        return true;
    }

    /**
     * 读取非群成员的群资料
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 17:38
     */
    public function info(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $group_id = $data['group_id'];
        $group    = ImGroupChat::get($group_id, ['profile'])->append(['count', 'online_count'])->toArray();
        // 是否消息免打扰\⭐标
        $_info = ImGroupChatMember::where('group_id', $group_id)->where('uuid', $uuid)->field('is_disturb, is_star, is_admin')->find();
        if (!empty($_info)) {
            $group['is_admin']   = (bool)$_info['is_admin'];    // 是否为管理员
            $group['is_star']    = (bool)$_info['is_star'];
            $group['is_disturb'] = (bool)$_info['is_disturb'];
        } else {
            $group['is_admin']   = false;    // 是否为管理员
            $group['is_star']    = false;
            $group['is_disturb'] = false;
        }
        $group['is_lord'] = $group['owner_id'] === $uuid; // 是否为群主
        // 如果我是管理员
        if ($group['is_admin']) {
            // 读取我的权限
            $group['my_permission'] = ImGroupChatAdminAuth::where('group_id', $data['group_id'])->where('uuid', $uuid)->find();
        } else {
            $group['my_permission'] = null;
        }
        $this->result = $group;
        return true;
    }

    /**
     * 消息接收人列表
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/21 9:38
     */
    public function recipient(array $param, Ws &$ws, Frame &$frame): bool
    {
//        $use    = microtime(true);
        $data     = $param['data'] ?? [];
        $msg_id   = $data['msg_id'];
        $offset   = $data['offset'] ?? 0;
        $length   = $data['length'] ?? 50;
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
//        Color::log("1: " . (microtime(true) - $use) * 1000);
//        $use    = microtime(true);
        $message = ImGroupChatMessage::get($msg_id);
        if (empty($message)) {
            $this->error = 'msg_id参数错误';
            return false;
        }
//        Color::log("2: " . (microtime(true) - $use) * 1000);
//        $use    = microtime(true);
        $list  = [];
        $model = new ImGroupChatMessageState;
        $count = $model->where('msg_id', $msg_id)->count();
        if ($count) {
            $list = $model->with(['recipient'])->where('msg_id', $msg_id)->field('uuid,read')->order('read DESC')->limit($offset, $length)->select()->toArray();
        }
//        Color::log("3: " . (microtime(true) - $use) * 1000);
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list,
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

    /**
     * 详情
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/17 14:51
     * @noinspection DuplicatedCode
     */
    public function read(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $group_id = $data['group_id'];
        $group    = ImGroupChat::get($group_id, ['profile'])->append(['count', 'online_count'])->toArray();
        // 是否消息免打扰\⭐标
        $_info = ImGroupChatMember::where('group_id', $group_id)->where('uuid', $uuid)->field('is_disturb, is_star, is_admin')->find();
        if (empty($_info)) {
            $this->error = '你还不是群内成员';
            return false;
        }
        $group['is_admin']   = (bool)$_info['is_admin'];    // 是否为管理员
        $group['is_star']    = (bool)$_info['is_star'];
        $group['is_disturb'] = (bool)$_info['is_disturb'];
        $group['is_lord']    = $group['owner_id'] === $uuid; // 是否为群主
        // 如果我是管理员
        if ($group['is_admin']) {
            // 读取我的权限
            $group['my_permission'] = ImGroupChatAdminAuth::where('group_id', $data['group_id'])->where('uuid', $uuid)->find();
        } else {
            $group['my_permission'] = null;
        }
        $this->result = $group;
        return true;
    }

    /**
     * 搜索
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/21 10:17
     */
    public function search(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $this->result = [];
        $count        = ImGroupChat::where('name', 'like', '%' . $data['name'] . '%')->count();
        if ($count > 0) {
            $this->result = ImGroupChat::where('name', 'like', '%' . $data['name'] . '%')->limit(0, 100)->select()->append(['count'])->toArray();
        }
        return true;
    }

    /**
     * 修改群昵称
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/9/17 17:04
     */
    public function nickname(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $this->result = ImGroupChatMember::where('id', $data['member_id'])->update(['nickname' => $data['nickname'], 'update_time' => time()]);

        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $data['member_id']],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 设置消息免打扰
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/10 11:19
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
        if (!ImGroupChatMember::where('uuid', $uuid)->where('group_id', $data['group_id'])->count()) {
            $this->error = '你不是群成员';
            return false;
        }
        $disturb = $data['disturb'];
        ImGroupChatMember::where('uuid', $uuid)->where('group_id', $data['group_id'])->update(['is_disturb' => $disturb, 'update_time' => time()]);
        //ImSession::where('uuid', $uuid)->where('to_id', $data['group_id'])->where('cate_id', 2)->update(['is_disturb' => $disturb, 'update_time' => time()]);

        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $data['group_id']],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 群主、管理员屏蔽他人消息
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/10 11:19
     */
    public function shield(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $message = ImGroupChatMessage::get($data['msg_id']);
        if (empty($message)) {
            $this->error = 'msg_id错误';
            return false;
        }
        $owner_id = ImGroupChat::where('id', $message->group_id)->value('owner_id');
        if ($owner_id !== $uuid) {
            if (!ImGroupChatMember::where('group_id', $message->group_id)->where('uuid', $uuid)->where('is_admin', 1)->count()) {
                $this->error = '权限不足';
                return false;
            }
        }
        $message->shield      = $data['shield'];
        $message->update_time = time();
        $message->save();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $message],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 群主设置全员禁言
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 18:45
     */
    public function allForbiddenWords(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $model = ImGroupChat::get($data['group_id']);
        if (empty($model)) {
            $this->error = "群ID错误";
            return false;
        }
        if ($uuid !== $model->owner_id) {
            $this->error = '你不是群主,无权操作';
            return false;
        }
        $model->update_time = time();
        $model->is_mute_all = $data['is_mute_all'];
        $model->save();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $model],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $model->is_mute_all;
        return true;
    }

    /**
     * 修改群名称
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/10 16:42
     */
    public function setGroupName(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $group_id = $data['group_id'];
        if ($uuid !== ImGroupChat::where('id', $group_id)->value('owner_id')) {
            if (!ImGroupChatMember::where('group_id', $group_id)->where('uuid', $uuid)->where('is_admin', 1)->count()) {
                $this->error = '只有群主、管理员才能修改群名称';
                return false;
            }
        }
        $update       = ['name' => $data['group_name'], 'update_time' => time()];
        $this->result = ImGroupChat::where('id', $group_id)->update($update);
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $group_id, $uuid],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 设置管理员
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/22 14:36
     */
    public function admin(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $groupChatMember = ImGroupChatMember::where('id', $data['member_id'])->find();
        if (!$groupChatMember) {
            $this->error = "群成员不存在，请检查后重试";
            return false;
        }

        $status = (int)$data['status'];
        if ($status === 1) {
            $max_group_admin = Env::get('swoole.max_group_admin', 3);
            $group_id        = ImGroupChatMember::where('id', $data['member_id'])->value('group_id');
            $admin_count     = ImGroupChatMember::where('group_id', $group_id)->where('is_admin', 1)->count();
            if ($admin_count >= $max_group_admin) {
                $this->error = '群最大允许管理员数 ' . $max_group_admin . ' 人';
                return false;
            }
            if ($groupChatMember->is_admin == 1) {
                $this->result = $status;
                return true;
            }
        } else {
            if ($groupChatMember->is_admin == 0) {
                $this->result = $status;
                return true;
            }
        }
        $groupChatMember->is_admin    = $status;
        $groupChatMember->update_time = time();
        if (!$groupChatMember->save()) {
            $this->error = '更新信息失败';
            return false;
        }
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$param, $groupChatMember, $status],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $status;
        return true;
    }

    /**
     * 单人禁言
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 14:03
     */
    public function mute(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $member_id = $data['member_id'];
        if ($member_id === $uuid) {
            $this->error = '不能禁言自己!';
            return false;
        }
        $model    = ImGroupChatMember::get($member_id);
        $group_id = $model->group_id;
        $is_lord  = ImGroupChat::where('id', $group_id)->value('owner_id') === $uuid;
        if (!$is_lord) {
            $where = ['uuid' => $uuid, 'group_id' => $group_id];
            if (!$model->where($where)->value('is_admin')) {
                $this->error = '你不是管理员,无权操作';
                return false;
            }
            if (!ImGroupChatAdminAuth::where($where)->value('is_mute')) {
                $this->error = '权限不足!';
                return false;
            }
        }
        $model->is_mute     = $data['status'];
        $model->update_time = time();
        $model->save();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [
                                     [
                                         'uuid'    => $model->uuid,
                                         'to_id'   => $model->group_id,
                                         'cate_id' => 2,
                                     ], $model->is_mute, $uuid, $is_lord
                                 ]
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $data['status'];
        return true;
    }

    /**
     * 从本群删除
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/22 15:25
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $model = ImGroupChatMember::get($data['member_id']);
        if ($model->uuid === $uuid) {
            $this->error = '不能删除自己!';
            return false;
        }
        $is_lord = ImGroupChat::where('id', $model->group_id)->value('owner_id') === $uuid;
        if (!$is_lord) {
            $is = ImGroupChatAdminAuth::where('uuid', $uuid)->where('group_id', $model->group_id)->value('is_remove');
            if (!$is) {
                $this->error = '权限不足!';
                return false;
            }
        }
        $oldGroupMember = $model->toArray();
        $model->delete();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [
                                     [
                                         'uuid'    => $model->uuid,
                                         'to_id'   => $model->group_id,
                                         'cate_id' => 2,
                                     ], $uuid, $is_lord, $oldGroupMember
                                 ]
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 退出本群
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException|\think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/17 17:54
     * @noinspection DuplicatedCode
     */
    public function out(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $group_id = $data['group_id'];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $model = ImGroupChatMember::where(['group_id' => $group_id, 'uuid' => $uuid])->find();
        if (!$model) {
            $this->error = '用户不存在!';
            return false;
        }
        $is_lord = ImGroupChat::where('id', $model->group_id)->value('owner_id') === $uuid;
        $count   = ImGroupChatMember::where('group_id', $model->group_id)->count();
        if ($is_lord == $uuid) {
            if ($count > 1) {
                $this->error = '群主不能直接退群';
                return false;
            }
        }
        $deleteMember = clone $model;
        $model->delete();
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [
                                     [
                                         'uuid'    => $model->uuid,
                                         'to_id'   => $model->group_id,
                                         'cate_id' => 2,
                                     ], $uuid, $deleteMember->toArray()
                                 ]
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        return true;
    }

    /**
     * 创建群
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/26 16:54
     */
    public function create(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $model   = new ImMember;
        $ids     = array_unique($data['member_ids']);
        $members = $model->with('profile')->where('uuid', 'in', $ids)->field('uuid')->select();
        if (empty($members)) {
            $this->error = "请选择有效的联系人";
            return false;
        }
        $user          = $model->with('profile')->where('uuid', $uuid)->field('uuid')->find();
        $my_nickname   = $user->profile->nickname ?? '';
        $group_name    = [$my_nickname];
        foreach ($members as $user) {
            $group_name[] = $user->profile->nickname ?? '';
        }
        $group_name = mb_substr(implode('、', $group_name), 0, 25, 'utf-8');
        // 创建群
        $model = new ImGroupChat;
        $model->allowField(true)->isUpdate(false)->data([
                                                            'name'     => $group_name,
                                                            'owner_id' => $uuid,
                                                        ])->save();
        $group_id = $model->id;
        // 批量插入群成员
        $list = [['uuid' => $uuid, 'group_id' => $group_id]];
        foreach ($members as $user) {
            $list[] = [
                'uuid'     => $user->uuid,
                'group_id' => $group_id
            ];
        }
        $message_state = (new ImGroupChatMember)->saveAll($list);
        //更新群头像
        $groupChat = new GroupChat();
        $result    = $groupChat->updateAvatar($group_id);
        if (!$result) {
            $this->error = $groupChat->getError();
            return false;
        }
        // 准备返回数据
        $item               = $model->where('id', $group_id)->find()->toArray();
        $item['count']      = count($members) + 1;
        $item['is_admin']   = false;
        $item['is_mute']    = false;
        $item['is_star']    = false;
        $item['is_disturb'] = false;
        $item['is_lord']    = true; // 是否为群主
        // 创建会话
        $logic  = new \app\logic\Session();
        $result = $logic->create(2, $uuid, $group_id, true);
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$item, $message_state, $my_nickname, $uuid]
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = ['group' => $item, 'session' => $logic->getResult()];
        return true;
    }

    /**
     * 添加成员
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/18 9:30
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
        $group   = ImGroupChat::get($data['group_id']);
        $is_lord = $group->owner_id === $uuid;
        $model   = new ImGroupChatMember;
        if (!$is_lord) {
            $is_admin = $model->where('group_id', $group->id)->where('uuid', $uuid)->value('is_admin');
            if (!$is_admin) {
                $this->error = '你不是管理员,无权操作';
                return false;
            }
            $is = ImGroupChatAdminAuth::where('uuid', $uuid)->where('group_id', $group->id)->value('is_invite');
            if (!$is) {
                $this->error = '权限不足!';
                return false;
            }
        }
        $max_group_number = Env::get('swoole.max_group_number', 20);
        if ($model->where(['group_id' => $group->id])->count() >= $max_group_number) {
            $this->error = '群最大允许成员数 <= ' . $max_group_number . ' ，已达上限';
            return false;
        }
        $ids       = array_unique($data['member_ids']);
        $insertAll = [];
        foreach ($ids as $id) {
            if (strlen($id) !== 36) {
                $this->error = 'member_ids参数错误';
                return false;
            }
            if ($id === $uuid) {
                $this->error = '自己不能邀请请自己';
                return false;
            }
            if (!ImMember::where('uuid', $id)->count()) {
                $this->error = 'UUID:' . $id . ',用户不存在';
                return false;
            }
            // 判断是否已经为群成员
            if ($model->where('group_id', $group->id)->where('uuid', $id)->count() === 0) {
                $insertAll[] = ['uuid' => $id, 'group_id' => $group->id];
            }
        }
        $model->saveAll($insertAll);
        $uuids = array_column($insertAll, 'uuid');
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$uuids, $group, $uuid]
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $append = [];
        if ($is_lord) {
            $append[] = 'permission';
        }
        //$this->result = $model->with(['profile'])->where('group_id', $group->id)->where('uuid', 'in', $uuids)->order('is_admin DESC,id ASC')->select()->append($append)->toArray();
        $this->result = $model->with(['profile'])->where('group_id', $group->id)->where('uuid', 'in', $uuids)->order('id ASC')->select()->append($append)->toArray();
        return true;
    }

    /**
     * 入群申请列表
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 14:42
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
        $offset    = $data['offset'] ?? 0;
        $length    = $data['length'] ?? 50;
        $owner_ids = ImGroupChat::where('owner_id', $uuid)->column('id');
        $admin_ids = ImGroupChatMember::where('uuid', $uuid)->where('is_admin', 1)->column('group_id');
        $group_ids = array_unique(array_merge($owner_ids, $admin_ids));
        $list      = [];
        $model     = new ImGroupChatApply;
        $count     = $model->where('group_id', 'in', $group_ids)->count();
        if ($count > 0) {
            $list = $model->with(['profile', 'message'])->where('group_id', 'in', $group_ids)->order('id DESC')->limit($offset, $length)->select()->toArray();
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
     * 申请加入群
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 19:01
     * @noinspection DuplicatedCode
     */
    public function apply(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $group_id = $data['group_id'];
        $group    = ImGroupChat::get($group_id);
        if (ImGroupChatMember::where('group_id', $group_id)->where('uuid', $uuid)->count() > 0) {
            $this->error = '你已是群成员，请勿重复申请加入';
            return false;
        }
        $max_group_number = Env::get('swoole.max_group_number', 20);
        if (ImGroupChatMember::where(['group_id' => $group_id])->count() >= $max_group_number) {
            $this->error = '群最大允许成员数 <= 500 ，已达上限';
            return false;
        }
        $where = ['group_id' => $group_id, 'uuid' => $uuid];
        $count = ImGroupChatApply::where($where)->where('status', 0)->count();
        if ($count > 0) {
            $this->error = '你的入群申请还在审核中，请耐心等待';
            return false;
        }
        $model = ImGroupChatApply::create(array_merge($where, [
            'rfa'    => $data['rfa'] ?? '',
            'rfr'    => $data['rfr'] ?? '',
            'status' => 0
        ]));
        if (!$model->id) {
            $this->error = '入群申请失败，请稍候再试';
            return false;
        }
        $apply = $model::get($model->id);
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => __FUNCTION__,
                                 'arg'    => [$apply, $group]
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        $this->result = $apply->toArray();
        return true;
    }

    /**
     * 处理入群申请
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 10:12
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
        $status        = (int)$data['status'];
        $apply_id      = (int)$data['apply_id'];
        $apply         = ImGroupChatApply::get($apply_id);
        $group_id      = $apply->group_id;
        $to_id         = $apply->uuid;
        $system_msg_id = $apply->system_msg_id;
        // 防止重复审核
        if ($apply->status == 1 || $apply->status == 2) {
            $this->error = '已' . ($apply->status == 1 ? '通过' : '拒绝') . '，请勿重复操作';
            return false;
        }
        $group = ImGroupChat::get($group_id);
        $model = new ImGroupChatMember;
        $uuids = $model->where('group_id', $group_id)->where('is_admin', 1)->column('uuid');
        $uuids = array_merge(array_unique($uuids), [$group->owner_id]);
        // 权限过滤
        if (!in_array($uuid, $uuids)) {
            $this->error = '权限不足';
            return false;
        }
        switch ($status) {
            case 1: // 同意
                $max_group_number = Env::get('swoole.max_group_number', 20);
                if ($model->where(['group_id' => $group_id])->count() >= $max_group_number) {
                    $this->error = "群最大允许成员数 <= {$max_group_number} ，已达上限";
                    return false;
                }
                $apply->status = 1;
                $apply->save();
                // 添加申请人入群
                $map = ['uuid' => $to_id, 'group_id' => $group_id];
                if ($model->where($map)->count()) {
                    $model->update(['update_time' => time()], $map);
                } else {
                    $model->allowField(true)->isUpdate(false)->data($map)->save();
                }
                // 投递异步任务
                $task_id = $ws->task([
                                         'class'  => \app\task\Group::class,
                                         'method' => __FUNCTION__,
                                         'arg'    => [$uuid, $apply, $group],
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
        $model = new ImSystemChatMessageState;
        if ($status === 1 || $status === 2) {
            $admin_list = $model->where('msg_id', $system_msg_id)->column('uuid');
            ImSystemChatMessage::where('id', $system_msg_id)->update(['read' => count($admin_list), 'unread' => 0]);
            // 投递异步任务，通知其余管理员申请已处理
            $task_id = $ws->task([
                                     'class'  => \app\task\Group::class,
                                     'method' => 'notifyOtherAdmin',
                                     'arg'    => [array_diff($admin_list, [$uuid]), $system_msg_id],
                                 ]);
            Color::task("Dispatch AsyncTask: id=$task_id");
        } else {
            // 更新系统消息已读/未读数
            ImSystemChatMessage::where('id', $system_msg_id)->update([
                                                                         'read'   => Db::raw('`read`+1'), // 已读人数+1
                                                                         'unread' => Db::raw('`unread`-1') // 未读人数-1
                                                                     ]);
        }
        // 更新为系统消息已读
        $model->where('uuid', $uuid)->where('msg_id', $system_msg_id)->update(['read' => 1, 'update_time' => time()]);
        // 会话未读消息数-1
        $model = ImSession::get(['cate_id' => 3, 'uuid' => $uuid, 'to_id' => 2]);
        if (!empty($model)) {
            $model->unread      = Db::raw('`unread`-1');
            $model->update_time = time();
            $model->save();
            $this->result = $model->id;
        }
        return true;
    }

    /**
     * 把某条入群申请标记为已读
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/9/22 16:15
     */
    public function applyMarkAsRead(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $uuid          = $param['uuid'];
        $apply_id      = $data['apply_id'];
        $system_msg_id = ImGroupChatApply::where('id', $apply_id)->value('system_msg_id');
        // 更新为系统消息已读
        $read = ImSystemChatMessageState::where('uuid', $uuid)->where('msg_id', $system_msg_id)->value('read');
        if ((int)$read === 1) {
            $this->error = '已经是已读状态了';
            return false;
        }
        ImSystemChatMessageState::where('uuid', $uuid)->where('msg_id', $system_msg_id)->update(['read' => 1, 'update_time' => time()]);
        ImSystemChatMessage::where('id', $system_msg_id)->update([
                                                                     'read'   => Db::raw('`read`+1'), // 已读人数+1
                                                                     'unread' => Db::raw('`unread`-1') // 未读人数-1
                                                                 ]);
        return true;
    }

    /**
     * 设置管理员权限
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/21 15:55
     */
    public function auth(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        if (!ImGroupChat::where('id', $data['group_id'])->where('owner_id', $uuid)->count()) {
            $this->error = '权限不足，因为您不是群主';
            return false;
        }
        $where  = ['group_id' => $data['group_id'], 'uuid' => $data['to_id']];
        $update = [
            'is_invite' => $data['is_invite'],
            'is_mute'   => $data['is_mute'],
            'is_remove' => $data['is_remove']
        ];
        $model  = ImGroupChatAdminAuth::get($where);
        if (empty($model)) {
            $model = ImGroupChatAdminAuth::create(array_merge($where, $update));
        } else {
            $model->is_mute   = $data['is_mute'];
            $model->is_remove = $data['is_remove'];
            $model->is_invite = $data['is_invite'];
            $model->save();
        }
        $this->result = $model->where('id', $model->id)->find()->toArray();
        return true;
    }

    /**
     * 修改群是否可被搜索
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @author: zmq <zmq3821@163.com>
     * @Date  : 2020/9/28 15:27
     */
    public function isSearch(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        ImGroupChat::where('id', $data['group_id'])->update(['is_search' => $data['is_search'], 'update_time' => time()]);
        $this->result = $data['is_search'];
        return true;
    }

    /**
     * 给群组加⭐
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 14:56
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
        $uuid    = $param['uuid'];
        $groupId = $data['group_id'];
        $info    = ImGroupChatMember::where('uuid', '=', $uuid)->where('group_id', '=', $groupId)->field('is_star')->find();
        if (empty($info)) {
            $this->error = '您未加入该群或被群管理员踢出，无法设置为星标';
            return false;
        }
        if (array_key_exists('force', $data) && isset($data['force'])) {
            $info->is_star = (int)(bool)$data['force'];
        } else {
            $info->is_star = !$info['is_star'];
        }
        $this->result = (bool)$info->is_star;
        return $info->save();
    }
}
