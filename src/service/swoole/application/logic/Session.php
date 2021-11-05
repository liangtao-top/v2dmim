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
// | Version: 2.0 2020/9/14 10:20
// +----------------------------------------------------------------------

namespace app\logic;

use com\console\Color;
use app\model_bak\ImGroupChatMessageState;
use app\model_bak\ImSession;
use app\model_bak\ImFriends;
use app\model_bak\ImGroupChat;
use app\model_bak\ImGroupChatMember;
use app\model_bak\ImSingleChatMessageState;
use app\model_bak\ImSystemChatMessageState;

/**
 * Class Session
 * @package app\logic
 */
class Session extends Logic
{

    public static function createID(int $cate_id, string $uuid, string|int $to_id): string
    {
        $md5  =  md5($cate_id . (bindec(str2bin($uuid, '')) + bindec(str2bin($to_id, ''))));
        Color::log("createID {$md5}");
        return $md5;
    }

    /**
     * 会话是否存在
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @return bool
     * @throws \think\Exception
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/12 14:09
     */
    public static function exist(int $cate_id, string $uuid, string $to_id): bool
    {
        return 0 < (new ImSession)->where(['uuid' => $uuid, 'cate_id' => $cate_id, 'to_id' => $to_id])->count();
    }

    /**
     * create
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @param bool   $is_active
     * @param array  $extend
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/18 14:39
     */
    public function create(int $cate_id, string $uuid, string $to_id, bool $is_active = false, array $extend = []): bool
    {
        $unread             = $extend['unread'] ?? 0;
        $last_message       = $extend['last_message'] ?? 0;
        $is_mute            = $extend['is_mute'] ?? 0;
        $is_mute_all = $extend['is_mute_all'] ?? 0;

        $model   = new ImSession;
        $where   = ['uuid' => $uuid, 'to_id' => $to_id, 'cate_id' => $cate_id];
        $session = $model->where($where)->find();
        if (!empty($session)) {
            $this->error = '会话[' . $session->id . ']已存在';
            return false;
        }
        $insert = [
            'uuid'               => $uuid,
            'to_id'              => $to_id,
            'cate_id'            => $cate_id,
            'unread'             => $unread,
            //'is_active'          => $is_active ? 1 : 0,
            //'is_mute'            => $is_mute,
            //'is_mute_all' => $is_mute_all
        ];
        switch ($last_message) {
            case -1:
                $insert['last_message'] = 0;
                break;
            case 0:
                $insert['last_message'] = self::getLastMessageId($cate_id, $uuid, $to_id);
                break;
            default:
                $insert['last_message'] = $last_message;
        }
        // 如果是群聊,查询是否开启消息免打扰
        if ($cate_id === 2) {
            $group_member = ImGroupChatMember::where(['group_id' => $to_id, 'uuid' => $uuid])->field('is_disturb,mute_time,mute_until')->find();
            if (empty($group_member)) {
                $this->error = '你不是群[' . $to_id . ']成员,无法创建会话';
                return false;
            }
            //$insert['is_mute']            = (int)$group_member->mute; // 单人禁言
            //$insert['is_disturb']         = (int)$group_member->disturb;  // 消息免打扰
            //$insert['is_mute_all'] = (int)ImGroupChat::where(['id' => $to_id])->value('is_mute_all'); // 全员禁言
        } elseif ($cate_id === 1) {
            // 查询是否开启消息免打扰
            //$insert['is_disturb'] = (int)ImFriends::where('uuid', $uuid)->where('to_id', $to_id)->value('disturb');
        }
        $model->allowField(true)->isUpdate(false)->data($insert)->save();
        $this->result = $model->where('id', $model->id)->find()->toArray();
        return true;
    }

    /**
     * getLastMessageId
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @return int
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/15 10:10
     */
    public static function getLastMessageId(int $cate_id, string $uuid, string $to_id): int
    {
        switch ($cate_id) {
            case 1:
                return (int)(new ImSingleChatMessageState)->where('uuid', $uuid)->where('to_id', $to_id)->order('id DESC')->value('id');
            case 2:
                return (int)(new ImGroupChatMessageState)->where('uuid', $uuid)->where('group_id', $to_id)->order('id DESC')->value('id');
            case 3:
                return (int)(new ImSystemChatMessageState)->where('uuid', $uuid)->where('system_id', $to_id)->order('id DESC')->value('id');
        }
        return 0;
    }

    /**
     * 激活会话
     * active
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @return array|bool|float|int|mixed|object|\stdClass|null
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/11 14:08
     */
    public static function active(int $cate_id, string $uuid, string $to_id): int
    {
        $model   = new ImSession;
        $where   = ['cate_id' => $cate_id, 'uuid' => $uuid, 'to_id' => $to_id];
        $session = $model->where($where)->find();
        if (empty($session)) {
            $model->where('uuid', $uuid)->update(['is_active' => 0]);
            $model->allowField(true)->isUpdate(false)->data(array_merge($where, ['is_active' => 1]))->save();
            return $model->id;
        } else {
            if ($session->is_active !== 1) {
                $session->is_active = 1;
                $session->save();
                $model->where('uuid', $uuid)->where('id', 'neq', $session->id)->update(['is_active' => 0]);
            }
            return $session->id;
        }
    }

    /**
     * isActive
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/7 17:49
     */
    public static function isActive(int $cate_id, string $uuid, string $to_id): bool
    {
        return (bool)ImSession::where(['cate_id' => $cate_id, 'uuid' => $uuid, 'to_id' => $to_id])->value('is_active');
    }

    /**
     * find
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/13 19:40
     */
    public static function find(int $cate_id, string $uuid, string $to_id)
    {
        return ImSession::where(['uuid' => $uuid, 'cate_id' => $cate_id, 'to_id' => $to_id])->find();
    }

    /**
     * 获取会话ID
     * @param int    $cate_id
     * @param string $uuid
     * @param string $to_id
     * @return mixed
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/12 11:51
     */
    public static function getSessionId(int $cate_id, string $uuid, string $to_id): mixed
    {
        return ImSession::where(['uuid' => $uuid, 'cate_id' => $cate_id, 'to_id' => $to_id])->value('id');
    }

    /**
     * 校正后的未读消息数
     * @param string $uuid
     * @return int
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/29 11:25
     */
    public static function correctUnread(string $uuid): int
    {
        $model = new ImSession;
        $list  = $model->where('uuid', $uuid)->select()->toArray();
        if (empty($list)) {
            return 0;
        }
        $model_1 = new ImSingleChatMessageState;
        $model_2 = new ImGroupChatMessageState;
        $model_3 = new ImSystemChatMessageState;
        foreach ($list as $value) {
            ['uuid' => $uuid, 'cate_id' => $cate_id, 'to_id' => $to_id] = $value;
            $where = [
                'uuid'        => $uuid,
                'read'        => 0,
                'delete_time' => null
            ];
            switch ($cate_id) {
                case 1: // 单聊
                    $count = $model_1->where($where)->where('to_id', $to_id)->where('msg_uuid', 'neq', $uuid)->count();
                    $model->where('id', $value['id'])->update(['unread' => $count]);
                    Color::log("session_id[{$value['id']}]:" . $count);
                    break;
                case 2: // 群聊
                    $count = $model_2->where($where)->where('group_id', $to_id)->count();
                    $model->where('id', $value['id'])->update(['unread' => $count]);
                    Color::log("session_id[{$value['id']}]:" . $count);
                    break;
                case 3: // 系统
                    $count = $model_3->where($where)->where('system_id', $to_id)->count();
                    Color::log("session_id[{$value['id']}]:" . $count);
                    $model->where('id', $value['id'])->update(['unread' => $count]);
                    break;
            }
        }
        return $model->where('uuid', $uuid)->sum('unread');
    }
}
