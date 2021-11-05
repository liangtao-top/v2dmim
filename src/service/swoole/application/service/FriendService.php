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
// | Version: 2.0 2021/5/27 13:31
// +----------------------------------------------------------------------

namespace app\service;

use app\common\Service;
use app\dao\FriendDao;
use app\struct\Friend;
use app\struct\Profile;

class FriendService extends Service
{

    public function index(string $uuid, int $offset, int $length): bool
    {
        $list  = [];
        $count = FriendDao::count($uuid);
        if ($count > 0) {
            $all = FriendDao::all($uuid, $offset, $length);
            foreach ($all as $value) {
                $struct = new Friend($value->toArray());
                $struct->setUserProfile(new Profile())
                $list[] =  $struct;
            }
            print_r($list);
        }
        $nextSeq = $offset + count($list);
        $this->setResult([
                             'list'       => $list,
                             'nextSeq'    => $nextSeq,
                             'isFinished' => $nextSeq >= $count,
                         ]);
        return true;
    }

}
