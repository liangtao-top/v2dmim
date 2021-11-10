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
// | Version: 2.0 2021/5/26 14:12
// +----------------------------------------------------------------------

namespace app\model;

use app\common\Struct;

class FriendModel extends Struct
{

    // 用户 userID
    private string $userID;
    // 好友 userID
    private string $friendUserID;
    // 好友备注
    private string $friendRemark;
    // 好友分组列表
    private array $friendGroups;
    // 好友自定义字段
    private array $friendCustomInfo;
    // 成为好友的时间
    private int $joinTime;
    // 好友资料最后一次更新时间
    private int $lastTime;

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
     * @return string
     */
    public function getFriendUserID(): string
    {
        return $this->friendUserID;
    }

    /**
     * @param string $friendUserID
     */
    public function setFriendUserID(string $friendUserID): void
    {
        $this->friendUserID = $friendUserID;
    }

    /**
     * @return string
     */
    public function getFriendRemark(): string
    {
        return $this->friendRemark;
    }

    /**
     * @param string $friendRemark
     */
    public function setFriendRemark(string $friendRemark): void
    {
        $this->friendRemark = $friendRemark;
    }

    /**
     * @return array
     */
    public function getFriendGroups(): array
    {
        return $this->friendGroups;
    }

    /**
     * @param array $friendGroups
     */
    public function setFriendGroups(array $friendGroups): void
    {
        $this->friendGroups = $friendGroups;
    }

    /**
     * @return array
     */
    public function getFriendCustomInfo(): array
    {
        return $this->friendCustomInfo;
    }

    /**
     * @param array $friendCustomInfo
     */
    public function setFriendCustomInfo(array $friendCustomInfo): void
    {
        $this->friendCustomInfo = $friendCustomInfo;
    }

    /**
     * @return int
     */
    public function getJoinTime(): int
    {
        return $this->joinTime;
    }

    /**
     * @param int $joinTime
     */
    public function setJoinTime(int $joinTime): void
    {
        $this->joinTime = $joinTime;
    }

    /**
     * @return int
     */
    public function getLastTime(): int
    {
        return $this->lastTime;
    }

    /**
     * @param int $lastTime
     */
    public function setLastTime(int $lastTime): void
    {
        $this->lastTime = $lastTime;
    }

}
