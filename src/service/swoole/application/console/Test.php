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
// | Version: 2.0 2021/4/7 11:07
// +----------------------------------------------------------------------

namespace app\console;

use app\dao\FriendDao;
use app\service\FriendService;
use com\console\Color;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use function Swoole\Coroutine\run;

class Test extends Command
{

    protected function configure()
    {
        $this->setName('test')->setDescription('test.');
    }

    protected function execute(Input $input, Output $output)
    {
        run(function () {
            $use = microtime(true);
            FriendDao::save("c6450d49-2f91-11eb-bd3f-ac4e9144c257", "daa7c8d2-2f91-11eb-bd3f-ac4e9144c257", '002', ['group1', 'group2']);
            FriendDao::save("c6450d49-2f91-11eb-bd3f-ac4e9144c257", "7cf38f44-2fe3-11eb-bd3f-ac4e9144c257", '888', ['group1']);
            FriendDao::save("c6450d49-2f91-11eb-bd3f-ac4e9144c257", "038cda61-3214-11eb-bd3f-ac4e9144c257", "xiaomi", [], ['key1' => 'field1']);
            print_r(FriendDao::all("c6450d49-2f91-11eb-bd3f-ac4e9144c257"));
            (new FriendService)->index("c6450d49-2f91-11eb-bd3f-ac4e9144c257", 0, 10);
            Color::log('test ' . ((microtime(true) - $use) * 1000) . 'ms.');
        });
    }

}



