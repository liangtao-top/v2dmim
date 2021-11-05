<?php /** @noinspection PhpDynamicAsStaticMethodCallInspection PhpFullyQualifiedNameUsageInspection */
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
 * Class ImFriendsApply
 * @package app\model
 */
class ImFriendsApply extends Model
{

    /**
     * 好友资料详情
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/9/18 10:43
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'to_id');
    }

    /**
     * 系统消息
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/9/25 15:52
     */
    public function message(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImSystemChatMessage', 'id', 'system_msg_id');
    }

}

