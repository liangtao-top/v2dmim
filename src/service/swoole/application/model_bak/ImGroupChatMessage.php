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
 * Class ImGroupChatMessage
 * @package app\model
 */
class ImGroupChatMessage extends Model
{

    /**
     * profile
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/20 14:45
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'uuid');
    }

    /**
     * identifier
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/20 14:45
     */
    public function identifier(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImGroupChatMessageIdentifier', 'msg_id', 'id');
    }
}

