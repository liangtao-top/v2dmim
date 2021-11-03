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
// | Version: 2.0 2021/5/7 16:45
// +----------------------------------------------------------------------

namespace app\logic;

use com\event\Event;
use com\redis\TimelinePool;
use app\struct\Timeline as Struct;
use Exception;

class Timeline
{
    /**
     * sequence键名
     */
    const SEQ_KEY = 'sequence';

    /**
     * save
     * @param string            $uuid
     * @param \app\struct\Event $event
     * @param mixed             $data
     * @return \app\struct\Timeline
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/14 11:04
     */
    public static function save(string $uuid, Event $event, mixed $data): Struct
    {
        $redis = TimelinePool::instance()->get();
        $redis->hSetNx(self::SEQ_KEY, $uuid, 0);
        $redis->hIncrBy(self::SEQ_KEY, $uuid, 1);
        $sequence = $redis->hGet(self::SEQ_KEY, $uuid);
        $struct   = new Struct($uuid, $event, $data, (int)$sequence);
        $redis->zAdd($uuid, $sequence, serialize($struct));
        TimelinePool::instance()->put($redis);
        return $struct;
    }

    /**
     * last
     * @param string $uuid
     * @return int
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/14 11:06
     */
    public static function lastSeq(string $uuid): int
    {
        $redis  = TimelinePool::instance()->get();
        $maxSeq = $redis->hGet(self::SEQ_KEY, $uuid) ?: 0;
        TimelinePool::instance()->put($redis);
        return (int)$maxSeq;
    }

    /**
     * all
     * @param string $uuid
     * @param int    $start
     * @param int    $end
     * @return array
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/12 12:17
     */
    public static function all(string $uuid, int $start, int $end): array
    {
        if ($start > 0) {
            $start++;
        }
        $list    = [];
        $nextSeq = 0;
        $redis   = TimelinePool::instance()->get();
        $result  = $redis->zRangeByScore($uuid, (string)$start, (string)$end);
        $maxSeq  = $redis->hGet(self::SEQ_KEY, $uuid) ?: 0;
        TimelinePool::instance()->put($redis);
        if ($result && is_array($result)) {
            foreach ($result as $item) {
                try {
                    $timeline = unserialize($item);
                } catch (Exception) {
                    continue;
                }
                $nextSeq = $timeline->getSequence();
                $list[]  = $timeline;
            }
        }
        return [
            'list'       => $list,
            'nextSeq'    => $nextSeq === 0 ? $maxSeq : $nextSeq,
            'isFinished' => !$nextSeq || $maxSeq == $nextSeq
        ];
    }

}
