<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Hook class (for hook do some thing )
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午11:55
 */

namespace cn\eunionz\core;

use cn\eunionz\exception\MethodNotFoundException;

defined('APP_IN') or exit('Access Denied');

class Hook extends Kernel
{

    /**
     * call hook
     * @param string $event
     * @throws MethodNotFoundException
     * @throws \ReflectionException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function call_hook(string $event)
    {
        $hooks = self::getConfig('app', 'APP_HOOKS');
        if (isset($hooks[$event]) && !empty($hooks[$event])) {
            foreach ($hooks[$event] as $hook) {
                list($class, $method, $params) = $hook;

                $object = new $class;

                if (method_exists($object, 'initialize')) {
                    $object->initialize();
                }

                if (!method_exists($object, $method)) {
                    throw new MethodNotFoundException("error_hook_method_not_found_title", "Hook class {$class} member method {$method} not exist.");
                }
                $method = new \ReflectionMethod($object, $method);
                return $method->invokeArgs(
                    $object,
                    array_pad($params, $method->getNumberOfParameters(), null)
                );
            }
        }
    }
}
