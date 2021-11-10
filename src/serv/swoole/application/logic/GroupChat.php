<?php /** @noinspection DuplicatedCode PhpUndefinedFieldInspection PhpDynamicAsStaticMethodCallInspection */
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

use app\model_bak\ImGroupChatMessageIdentifier;
use app\model_bak\ImSession;
use com\console\Color;
use app\logic\Session as Logic;
use app\model_bak\ImGroupChat;
use app\model_bak\ImGroupChatMember;
use app\model_bak\ImGroupChatMessage;
use app\model_bak\ImGroupChatMessageState;
use com\http\Http;
use GuzzleHttp\Exception\RequestException;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use GuzzleHttp\Client;

/**
 * Class GroupChat
 * @package app\logic
 */
class GroupChat extends Logic
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
     * @noinspection PhpFullyQualifiedNameUsageInspection PhpUnusedParameterInspection PhpMissingReturnTypeInspection
     */
    public function send(array $param, Ws &$ws, Frame &$frame)
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $group_id = $data['to_id'];
        if (!$this->checkGroupId($group_id)) {
            return false;
        }
        if (!$this->checkGroupUuid($group_id, $param['uuid'])) {
            return false;
        }
        $group_info = ImGroupChat::get($group_id);
        if ($group_info['is_mute_all'] && $uuid !== $group_info['owner_id']) {
            $this->error = '全员禁言,只有群主能发言';
            return false;
        }
        // 创建消息
        $model = new ImGroupChatMessage();
        $model->allowField(true)->data([
                                           'group_id' => $group_id,
                                           'uuid'     => $param['uuid'],
                                           'cate_id'  => $data['type_id'],
                                           'random'   => $data['random'],
                                           'content'  => $data['content'],
                                           'read'     => 1, // 已读人数
                                           'unread'   => ImGroupChatMember::where('group_id', $group_id)->count() - 1, // 未读人数
                                           'type'     => 0, // 类型 默认0:普通 1:Tips
                                           'retract'  => 0, // 是否撤回 0:否 1:是
                                           'shield'   => 0, // 屏蔽
                                       ])->save();
        $msg_id  = $model->id;
        $message = $model->toArray();
        // 插入到我的消息列表
        $model = new ImGroupChatMessageState();
        $model->allowField(true)->data([
                                           'group_id' => $group_id,
                                           'uuid'     => $param['uuid'],
                                           'msg_id'   => $msg_id,
                                           'msg_uuid' => $param['uuid'],
                                           'read'     => 1, // 自己发的消息,直接已读
                                       ])->save();
        $this->result = $model->toArray();
        // 如果设置了@对象,并且@了所有人
        if (isset($data['identifier']) && !empty($data['identifier']) && is_array($data['identifier'])) {
            $is_all = false;
            foreach ($data['identifier'] as $value) {
                // 如果包含0,则@所有人
                if ($value === 0) {
                    $is_all = true;
                    break;
                }
            }
            if ($is_all) {
                // @信息批量插入
                $model = new ImGroupChatMessageIdentifier();
                $model->allowField(true)->data([
                                                   'group_id' => $group_id,
                                                   'uuid'     => $uuid,
                                                   'msg_id'   => $msg_id,
                                                   'read'     => 1, // 自己直接已读
                                                   'is_all'   => 1
                                               ])->save();
                $identifier = $model->toArray();
            }
        }
        // 更新会话
        $update = ['last_message' => $this->result['id'], 'update_time' => time()];
        ImSession::where(['cate_id' => 2, 'uuid' => $uuid, 'to_id' => $group_id])->update($update);
        // 投递异步任务
        $task_id = $ws->task([
                                 'class'  => \app\task\Group::class,
                                 'method' => 'send',
                                 'arg'    => [$param, $message],
                             ]);
        Color::task("Dispatch AsyncTask: id=$task_id");
        // 激活会话
        $session_id = Session::active(2, $uuid, $group_id);
        // 组装返回数据
        $this->result['message']    = $message;
        $this->result['identifier'] = $identifier ?? null;
        $this->result['session_id'] = $session_id;
        return true;
    }

    /**
     * 检测用户是否存在,防止产生垃圾数据
     * @param $group_id
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 12:05
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function checkGroupId($group_id): bool
    {
        if (ImGroupChat::where('id', $group_id)->count() > 0) {
            return true;
        }
        $this->error = 'to_id错误，群不存在';
        return false;
    }

    /**
     * checkGroupUuid
     * @param int    $group_id
     * @param string $uuid
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/16 15:11
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function checkGroupUuid(int $group_id, string $uuid): bool
    {
        $group = ImGroupChatMember::where('group_id', $group_id)->where('uuid', $uuid)->field('uuid,group_id,is_mute')->find();
        if ($group) {
            if ($group['is_mute']) {
                $this->error = '你已被禁言，请联系群管理员';
                return false;
            }
            return true;
        }
        $this->error = '你不是群内成员，请加入群后再试';
        return false;
    }

    /**
     * 更新群头像
     * @user zmq <zmq3821@163.com>
     * @param int $group_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateAvatar(int $group_id = 0): bool
    {
        if (!$group_id) {
            $this->error = '参数错误';
            return false;
        }
        // 取前4位群成员生成头像
        $memberList = ImGroupChatMember::with(['profile'])
            ->where('group_id',$group_id)
            ->field('id,uuid')
            ->order('id asc')
            ->limit(4)
            ->select();
        if (!$memberList) {
            $this->error = '群组信息异常';
            return false;
        }
        $list = [];
        foreach ($memberList as $member) {
            $avatar = $member->profile->avatar ?? 0;
            if (!$avatar) {
                $avatar = $member->profile->nickname ?? '';
            }
            $list[] = $avatar;
        }
        //群头像id
        $group_avatar = ImGroupChat::where('id', $group_id)->value('avatar');
        //请求接口
        $params = [
            'pic_list' => $list,
            'file_id'  => $group_avatar,
        ];
        $http = new Http();
        if (!$http->post("/api/groupAvatar", $params)) {
            $this->error = $http->getError();
            return false;
        }
        $response = $http->getResult();
        if (!$response['code']) {
            $this->error = $response['msg'];
            return false;
        }
        // 更新头像
        $avatar = $response['data']['id'] ?? 0;
        if ($avatar) {
            ImGroupChat::update(['avatar'=>$avatar], ['id'=>$group_id]);
        }
        return true;
    }
}
