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

use app\command\Server;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Swoole extends Command
{

    protected function configure()
    {
        $this->setName('swoole')->setDescription('swoole service.');
    }

    protected function execute(Input $input, Output $output)
    {
        (new Server)->run();
    }

}



