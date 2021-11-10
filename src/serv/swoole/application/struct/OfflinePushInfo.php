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
// | Version: 2.0 2021/5/26 10:11
// +----------------------------------------------------------------------

namespace app\struct;

use app\common\Struct;

/**
 * 离线推送配置
 * @package app\struct
 */
class OfflinePushInfo extends Struct
{
    // 离线推送通知标题
    private string $title;
    // 离线推送通知内容
    private string $desc;
    // 离线推送透传的扩展字段
    private string $ext;
    // 是否关闭推送（默认开启推送）。
    //
    // 参数
    // disable	true：关闭；false：打开
    private bool $isDisablePush;
    // 离线推送声音设置（仅对 iOS 生效）。 当 sound = IOS_OFFLINE_PUSH_NO_SOUND，表示接收时不会播放声音。 当 sound = IOS_OFFLINE_PUSH_DEFAULT_SOUND，表示接收时播放系统声音。 如果要自定义 iOSSound，需要先把语音文件链接进 Xcode 工程，然后把语音文件名（带后缀）设置给 iOSSound。
    //
    // 参数
    // sound	iOS 声音路径
    private IosOfflinePush $IOSSound;
    // 离线推送忽略 badge 计数（仅对 iOS 生效）， 如果设置为 true，在 iOS 接收端，这条消息不会使 APP 的应用图标未读计数增加。
    //
    // 参数
    // ignoreIOSBadge	iOS 应用图标未读计数状态。true：忽略；false：开启
    private bool $ignoreIOSBadge;
    // 离线推送设置 OPPO 手机 8.0 系统及以上的渠道 ID。
    //
    // 参数
    // channelID	OPPO 手机的渠道 ID
    private string $androidOPPOChannelID;

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDesc(): string
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     */
    public function setDesc(string $desc): void
    {
        $this->desc = $desc;
    }

    /**
     * @return string
     */
    public function getExt(): string
    {
        return $this->ext;
    }

    /**
     * @param string $ext
     */
    public function setExt(string $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return bool
     */
    public function isDisablePush(): bool
    {
        return $this->isDisablePush;
    }

    /**
     * @param bool $isDisablePush
     */
    public function setIsDisablePush(bool $isDisablePush): void
    {
        $this->isDisablePush = $isDisablePush;
    }

    /**
     * @return \app\struct\IosOfflinePush
     */
    public function getIOSSound(): IosOfflinePush
    {
        return $this->IOSSound;
    }

    /**
     * @param \app\struct\IosOfflinePush $IOSSound
     */
    public function setIOSSound(IosOfflinePush $IOSSound): void
    {
        $this->IOSSound = $IOSSound;
    }

    /**
     * @return bool
     */
    public function isIgnoreIOSBadge(): bool
    {
        return $this->ignoreIOSBadge;
    }

    /**
     * @param bool $ignoreIOSBadge
     */
    public function setIgnoreIOSBadge(bool $ignoreIOSBadge): void
    {
        $this->ignoreIOSBadge = $ignoreIOSBadge;
    }

    /**
     * @return string
     */
    public function getAndroidOPPOChannelID(): string
    {
        return $this->androidOPPOChannelID;
    }

    /**
     * @param string $androidOPPOChannelID
     */
    public function setAndroidOPPOChannelID(string $androidOPPOChannelID): void
    {
        $this->androidOPPOChannelID = $androidOPPOChannelID;
    }

}
