<?php /** @noinspection PhpFullyQualifiedNameUsageInspection PhpDynamicAsStaticMethodCallInspection */
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
 * Class ImGroupChatMessageState
 * @package app\model
 */
class ImGroupChatMessageState extends Model
{

    /**
     * message
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 10:26
     */
    public function message(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImGroupChatMessage', 'id', 'msg_id')->setEagerlyType(0);
    }

    /**
     * 消息发送人用户资料
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 10:24
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'msg_uuid')->setEagerlyType(0);
    }

    /**
     * identifier
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 10:27
     */
    public function identifier(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImGroupChatMessageIdentifier', 'msg_id', 'msg_id')->setEagerlyType(0);
    }

    /**
     * 消息接收人用户资料
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/9/21 9:52
     */
    public function recipient(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'uuid')->setEagerlyType(0);
    }

}

