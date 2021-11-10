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
// | Version: 2.0 2021/5/26 14:15
// +----------------------------------------------------------------------

namespace app\model;

use app\common\Struct;
use app\struct\AllowType;
use app\struct\Gender;

/**
 * 用户资料
 * @package app\struct
 */
class ProfileModel extends Struct
{
    // 用户 ID
    private string $userID;

    // 昵称，长度不得超过500个字节
    private string $nickName;

    // 头像URL，长度不得超过500个字节
    private string $faceUrl;

    // 性别
    // GENDER_UNKNOWN（未设置性别）
    // GENDER_FEMALE（女）
    // GENDER_MALE（男）
    private Gender $gender;

    // 生日 uint32 推荐用法：20000101
    private int $birthday;

    // 语言 uint32
    private int $language;

    // 所在地 长度不得超过16个字节，推荐用法如下：App 本地定义一套数字到地名的映射关系 后台实际保存的是4个 uint32_t 类型的数字：
    //
    // 第一个 uint32_t 表示国家
    // 第二个 uint32_t 用于表示省份
    // 第三个 uint32_t 用于表示城市
    // 第四个 uint32_t 用于表示区县
    private string $location;

    // 个性签名 长度不得超过500个字节
    private string $selfSignature;

    // 等级 uint32 建议拆分以保存多种角色的等级信息
    private int $level;

    // 角色 uint32 建议拆分以保存多种角色信息
    private int $role;

    // 加好友验证方式
    //
    // ALLOW_TYPE_ALLOW_ANY（允许任何人添加自己为好友）
    // ALLOW_TYPE_NEED_CONFIRM（需要经过自己确认才能添加自己为好友）
    // ALLOW_TYPE_DENY_ANY（不允许任何人添加自己为好友）
    private AllowType $allowType;

    // 自定义资料键值对集合，可根据业务侧需要使用
    private array $customUserInfo;

    /**
     * @return string
     */
    public function getUserID(): string
    {
        return $this->userID;
    }

    /**
     * @param string $userID
     */
    public function setUserID(string $userID): void
    {
        $this->userID = $userID;
    }

    /**
     * @param string $nickName
     * @return ProfileModel
     */
    public function setNickName(string $nickName): ProfileModel
    {
        $this->nickName = $nickName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFaceUrl(): string
    {
        return $this->faceUrl;
    }

    /**
     * @param string $faceUrl
     */
    public function setFaceUrl(string $faceUrl): void
    {
        $this->faceUrl = $faceUrl;
    }

    /**
     * @return \app\struct\Gender
     */
    public function getGender(): Gender
    {
        return $this->gender;
    }

    /**
     * @param \app\struct\Gender $gender
     */
    public function setGender(Gender $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return int
     */
    public function getBirthday(): int
    {
        return $this->birthday;
    }

    /**
     * @param int $birthday
     */
    public function setBirthday(int $birthday): void
    {
        $this->birthday = $birthday;
    }

    /**
     * @return int
     */
    public function getLanguage(): int
    {
        return $this->language;
    }

    /**
     * @param int $language
     */
    public function setLanguage(int $language): void
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getSelfSignature(): string
    {
        return $this->selfSignature;
    }

    /**
     * @param string $selfSignature
     */
    public function setSelfSignature(string $selfSignature): void
    {
        $this->selfSignature = $selfSignature;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * @param int $role
     */
    public function setRole(int $role): void
    {
        $this->role = $role;
    }

    /**
     * @return \app\struct\AllowType
     */
    public function getAllowType(): AllowType
    {
        return $this->allowType;
    }

    /**
     * @param \app\struct\AllowType $allowType
     */
    public function setAllowType(AllowType $allowType): void
    {
        $this->allowType = $allowType;
    }

    /**
     * @return array
     */
    public function getCustomUserInfo(): array
    {
        return $this->customUserInfo;
    }

    /**
     * @param array $customUserInfo
     */
    public function setCustomUserInfo(array $customUserInfo): void
    {
        $this->customUserInfo = $customUserInfo;
    }

    /**
     * @return string
     */
    public function getNickName(): string
    {
        return $this->nickName;
    }

}
