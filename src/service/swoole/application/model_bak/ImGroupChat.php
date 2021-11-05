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
 * Class ImGroupChat
 * @property array|bool|float|int|mixed|object|\stdClass|null owner_id
 * @property array|bool|float|int|mixed|object|\stdClass|null id
 * @property array|bool|float|int|mixed|object|\stdClass|null update_time
 * @property array|bool|float|int|mixed|object|\stdClass|null is_mute_all
 * @package app\model
 */
class ImGroupChat extends Model
{

    /**
     * 群组的资料详情
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/11 14:20
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'owner_id');
    }

}

