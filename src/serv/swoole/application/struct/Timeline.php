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
// | Version: 2.0 2021/4/23 10:45
// +----------------------------------------------------------------------

namespace app\struct;

use app\common\Struct;

class Timeline extends Struct
{

    // 序列号
    private int $sequence;

    // 用户ID
    private string $uuid;

    // 事件
    private Event $event;

    // 数据
    private mixed $data;

    // 创建时间
    private int $time;

    /**
     * Timeline constructor.
     * @param int|null              $sequence
     * @param string|null           $uuid
     * @param Event|null $event
     * @param mixed                 $data
     */
    public function __construct(?string $uuid, ?Event $event, mixed $data, ?int $sequence)
    {
        if (!is_null($uuid)) {
            $this->uuid = $uuid;
        }
        if (!is_null($event)) {
            $this->event = $event;
        }
        if (!is_null($data)) {
            $this->data = $data;
        }
        if (!is_null($sequence)) {
            $this->sequence = $sequence;
        }
        $this->time = time();
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return int
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * @param int $sequence
     */
    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

}
