<?php
declare(strict_types=1);
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2021/5/20 9:24
// +----------------------------------------------------------------------

namespace app\model_bak;

use app\logic\Online;

/**
 * Class ImMemberProfile
 * @package app\model
 */
class ImMemberProfile extends Model
{
    // 模型名称
    protected string $name = 'im_member_profile';
    // 模型主键
    protected string $pk = 'uuid';

    /**
     * 在线状态
     * @param $value
     * @param $data
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/13 16:43
     */
    public function getOnlineAttr($value, $data): bool
    {
        unset($value);
        return Online::exist($data['uuid']);
    }

    /**
     * 用户昵称
     * @param $value
     * @param $data
     * @return string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/1/13 16:44
     */
    public function getNicknameAttr($value, $data): string
    {
        unset($value);
        if (isset($data['nickname']) && !empty($data['nickname'])) {
            return $data['nickname'];
        }
        return $data['surname'] . $data['last_name'];
    }
}
