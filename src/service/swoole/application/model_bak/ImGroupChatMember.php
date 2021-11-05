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
 * Class ImGroupChatMember
 * @property array|bool|float|int|mixed|object|\stdClass|null group_id
 * @property array|bool|float|int|mixed|object|\stdClass|null uuid
 * @package app\model
 */
class ImGroupChatMember extends Model
{
    /**
     * 成员资料详情
     * @return \think\model\relation\HasOne
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/9/21 15:57
     */
    public function profile(): \think\model\relation\HasOne
    {
        return $this->hasOne('ImMemberProfile', 'uuid', 'uuid');
    }


    /**
     * 成员权限
     * @param $value
     * @param $data
     * @return array|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/21 16:01
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function getPermissionAttr($value, $data): ?array
    {
        unset($value);
        $where = ['group_id' => $data['group_id'], 'uuid' => $data['uuid']];
        if (ImGroupChatAdminAuth::where($where)->count()) {
            return ImGroupChatAdminAuth::where($where)->find()->toArray();
        }
        return null;
    }


}

