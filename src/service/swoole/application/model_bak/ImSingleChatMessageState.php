<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */
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
 * Class ImSingleChatMessageState
 * @package app\model
 */
class ImSingleChatMessageState extends Model
{

    /**
     * message
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/14 17:07
     */
    public function message(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImSingleChatMessage', 'id', 'msg_id');
    }

}

