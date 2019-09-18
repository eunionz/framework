<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/29
 * Time: 11:37
 */

namespace cn\eunionz\core;


use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements \Psr\Container\ContainerInterface
{

    /**
     * 当容器中对像被创建之后将针对对像执行的初始化方法，前提是该对像存在 INITIALIZE_METHOD 方法
     * 该方法必须为public且非静态，没有参数
     * 如果为单例对像则仅在第一次创建单例对像时执行一次初始化方法，如果为原型对像则在每一次创建原型对像时均执行初始化方法
     */
    public const INITIALIZE_METHOD = 'initialize';

    /**
     * 当容器中对像被销毁之前将执行对像的销毁方法，前提是该对像存在 DESTROY_METHOD 方法
     * 该方法必须为public且非静态，没有参数
     * 该方法将在容器结束之前自动调用
     */
    public const DESTROY_METHOD = 'destroy';


    /**
     * 容器将以单例模式存在，在整个容器生命周期内，容器仅存在一份
     * @var
     */
    public static $instance;


    /**
     * 全局单例作用域对像池
     * @var array
     * @example
     * [
     *     'beanName' => object,
     *     'fullClassName' => object,
     * ]
     */
    private $singletonPools = array();


    /**
     * 原型作用域对像池
     * @var array
     * @example
     * [
     *     'beanName' => object,
     *     'fullClassName' => object,
     * ]
     */
    private $prototypePools = array();


    /**
     * 请求范围作用域对像池
     * @var array
     * @example
     * [
     *     'beanName' => object,
     *     'fullClassName' => object,
     * ]
     */
    private $requestPools = array();


    /**
     * 会话范围作用域对像池
     * @var array
     * @example
     * [
     *     'beanName' => object,
     *     'fullClassName' => object,
     * ]
     */
    private $sessionPools = array();



    /**
     * Container constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function get($id)
    {
        //优先从全局作用域中获取对像
        if (isset($this->singletonPools[$id])) {
            return $this->singletonPools[$id];
        }

        //其次从原型作用域中获取对像
        if (isset($this->prototypePools[$id])) {
            return clone $this->prototypePools[$id];
        }

        //否则，创建对像
        


        // TODO: Implement get() method.
    }

    public function has($id)
    {
        // TODO: Implement has() method.
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }


}