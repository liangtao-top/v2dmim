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
 * Class ImGroupChatMessageIdentifier
 * @package app\model
 */
class ImGroupChatMessageIdentifier extends Model
{
    /**
     * profile
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/20 14:45
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'msg_uuid');
    }

    /**
     * message
     * @return \think\model\relation\BelongsTo
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/20 14:46
     */
    public function message(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo('ImGroupChatMessage', 'id', 'msg_id');
    }

}

