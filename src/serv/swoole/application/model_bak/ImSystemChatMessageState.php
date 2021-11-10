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
// | Date: 2019-11-28 11:22
// +----------------------------------------------------------------------

namespace app\model_bak;

/**
 * Class ImSystemChatMessageState
 * @package app\model
 */
class ImSystemChatMessageState extends Model
{

    /**
     * message
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 17:09
     */
    public function message(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImSystemChatMessage', 'id', 'msg_id');
    }

    /**
     * 消息发送人用户资料
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 10:24
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'msg_uuid');
    }


    /**
     * getApplyAttr
     * @param $value
     * @param $data
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/19 11:26
     */
    public function getApplyAttr($value,$data)
    {
        $system_id=  (int)$data['system_id'];
        return match ($system_id) {
            1 => ImFriendsApply::where('system_msg_id', $data['msg_id'])->find(),
            2 => ImGroupChatApply::where('system_msg_id', $data['msg_id'])->find(),
            default => null,
        };
    }

}

