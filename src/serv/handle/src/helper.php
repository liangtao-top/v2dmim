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
// | Version: 2.0 2021/5/28 9:36
// +----------------------------------------------------------------------
use Swoole\Http\Request;

/**
 * Action的访问控制权限检测
 * @param string $controllerName 控制器类名
 * @param string $actionName     操作方法名
 * @param array  $deny           如果黑名单不为空,则黑名单之外的方法全部允许未登录状态访问;如果黑名单和白名单同时设置,则黑名单优先级大于白名单
 * @param array  $allow          如果白名单不为空,则白名单之外的方法全部禁止未登录状态访问
 * @author TaoGe <liangtao.gz@foxmail.com>
 * @date   2021/11/15 10:31
 */
function access_control(string $controllerName, string $actionName, array $deny, array $allow)
{
    $check = strtolower($controllerName . '/' . $actionName);
    // 阻止黑名单的方法访问
    if (!empty($deny)) {
        foreach ($deny as &$value) $value = $controllerName . '/' . $value;
        if (in_array_case($check, $deny)) {
            throw new RuntimeException('禁止访问黑名单中的' . $actionName . '方法', 403);
        }
    }
    // 阻止白名单以外的方法访问
    if (!empty($allow)) {
        foreach ($allow as &$value) $value = $controllerName . '/' . $value;
        if (!in_array_case($check, $allow)) {
            throw new RuntimeException('禁止访问白名单外的任何方法', 403);
        }
    }
    // 黑白名单皆为空
    if (empty($deny) && empty($allow)) {
        throw new RuntimeException('未登录授权，禁止访问', 403);
    }
}

