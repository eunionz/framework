<?php

namespace com\eunionz\core;
defined('APP_IN') or exit('Access Denied');

/**
 * 响应对像
 * Class Response
 * @package com\eunionz\core
 */
class Response extends Component
{
    /**
     * 默认是否开启ob_start
     * @var bool
     */
    public $_is_ob_start = true;

    /**
     * 每一个输出缓冲区大小，单位字节
     * @var int
     */
    private $output_buffer_size = 8092;

    /**
     * 输出缓冲区数组，采用堆栈方式实现即先进后出
     * @var array(index=>'')
     */
    private $output_buffer = [];

    /**
     * 输出缓冲区层级 0--最底层级
     * @var int
     */
    private $ob_level = 0;

    private $response;

    public function __construct(\Swoole\Http\Response $response)
    {
        $this->response = $response;
    }


    /**
     * 基于当前请求ID设置$_COOKIE变量
     * @param $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return string
     */
    public final function setcookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = true)
    {
        if ($this->response) {
            ctx()->getRequest()->cookie($key, $value);
            $this->response->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);
        }
        return $value;
    }

    /**
     * 分段输出内容
     * @param $content 内容不能为空，内容不超过2M
     */
    public function write($content)
    {
        if ($this->response && $content) {
            @$this->response->write($content);
        }
    }

    /**
     * 发送Http响应体，并结束请求处理
     * @param null $content
     */
    public function end($content = null)
    {
        if ($this->response) {
            if ($content) {
                @$this->response->end($content);
            } else {
                @$this->response->end();
            }
        }
    }


    /**
     * 发送HTTP状态码
     * @param $code 200 302
     */
    public function status($code)
    {
        if ($this->response) {
            $this->response->status(intval($code));
        }
    }

    /**
     * 发送文件
     * @param $filename    文件名
     * @param int $offset 偏移 发送文件的偏移量，可以指定从文件的中间部分开始传输数据。此特性可用于支持断点续传。
     * @param int $length 长度 发送数据的尺寸，默认为整个文件的尺寸
     */
    public function sendfile($filename, $offset = 0, $length = 0)
    {
        if ($this->response) {
            $this->response->sendfile($filename, $offset, $length);
        }
    }

    /**
     * 基于当前请求ID在请求中增加头部信息
     * @param $name  头部信息名称
     * @param $value  头部信息值
     */
    public function addHeader($name, $value)
    {
        if ($this->response) {
            return $this->response->header($name, $value);
        }
        return false;
    }

    /**
     * 开启输出缓冲区
     */
    public function ob_start()
    {
        if (!$this->_is_ob_start) return false;
        if (empty($this->output_buffer)) {
            //如果输出缓冲区数组为空，表示第1次开启输出缓冲区
            $this->output_buffer[$this->ob_level] = '';
        } else {
            //如果输出缓冲区数组不为空，表示第n+1次开启输出缓冲区
            $this->ob_level++;
            $this->output_buffer[$this->ob_level] = '';
        }
        return true;
    }


    /**
     * 刷新输出缓冲区到下一层输出缓冲区，如果为最底层缓冲区直接输出到浏览器
     * 并不结束当前缓冲区
     */
    public function ob_flush()
    {
        $buffer = '';
        if (!$this->_is_ob_start) return $buffer;
        if ($this->ob_level > 0) {
            //非最底层缓冲区，向下级缓冲区置入内容并销毁当前缓冲区
            if (isset($this->output_buffer[$this->ob_level])) {
                $buffer = $this->output_buffer[$this->ob_level];
                $this->output_buffer[$this->ob_level] = '';
            }
            if ($buffer) $this->output_buffer[$this->ob_level - 1] .= $buffer;
        } else {
            //为最底层缓冲区，输出缓冲
            $this->ob_level = 0;
            if (isset($this->output_buffer[$this->ob_level])) {
                $buffer = $this->output_buffer[$this->ob_level];
                $this->write($this->output_buffer[$this->ob_level]);
            }
            $this->output_buffer[$this->ob_level] = '';
        }
        return $buffer;
    }

    /**
     * 刷新输出缓冲区到下一层输出缓冲区，如果为最底层缓冲区直接输出到浏览器
     * 同时结束当前层缓冲区
     */
    public function ob_end_flush()
    {
        $buffer = '';
        if (!$this->_is_ob_start) return $buffer;
        if ($this->ob_level > 0) {
            //非最底层缓冲区，向下级缓冲区置入内容并销毁当前缓冲区

            if (isset($this->output_buffer[$this->ob_level])) {
                $buffer = $this->output_buffer[$this->ob_level];
                unset($this->output_buffer[$this->ob_level]);
            }
            $this->ob_level--;
            if ($buffer) $this->output_buffer[$this->ob_level] .= $buffer;
        } else {
            $this->ob_level = 0;
            //为最底层缓冲区，输出缓冲

            if (isset($this->output_buffer[$this->ob_level])) {
                $buffer = $this->output_buffer[$this->ob_level];
                $this->write($buffer);
            }
            unset($this->output_buffer[$this->ob_level]);
        }
        return $buffer;
    }

    /**
     * 向当前输出缓冲区写入内容
     */
    public function ob_write($string)
    {
        if (empty($string)) return false;
        if (!$this->_is_ob_start) {
            $this->write($string);
        }else{
            if (!isset($this->output_buffer[$this->ob_level])) {
                $this->output_buffer[$this->ob_level] = '';
            }
            $this->output_buffer[$this->ob_level] .= $string;
        }
        return true;
    }

    /**
     * 清除输出缓冲区并不输出到浏览器
     */
    public function ob_end_clean()
    {
        $buffer = '';
        if (!$this->_is_ob_start) return $buffer;
        if ($this->ob_level > 0) {
            //非最底层缓冲区，向下级缓冲区置入内容并销毁当前缓冲区
            if (isset($this->output_buffer[$this->ob_level])) {
                $buffer = $this->output_buffer[$this->ob_level];
                unset($this->output_buffer[$this->ob_level]);
            }
            $this->ob_level--;
        } else {
            $this->ob_level = 0;
            $buffer = $this->output_buffer[$this->ob_level];
            unset($this->output_buffer[$this->ob_level]);
        }
        return $buffer;
    }

    /**
     * 获取输出缓冲区内容
     */
    public function ob_get_contents()
    {
        if (!$this->_is_ob_start) return '';
        if (isset($this->output_buffer[$this->ob_level])) {
            return $this->output_buffer[$this->ob_level];
        }
        return '';
    }


    public function trailer($trailer_name, $trailer_value){
        if ($this->response) {
            return $this->response->trailer($trailer_name, $trailer_value);
        }
        return null;
    }

    public function get_HttpResponse(){
        if ($this->response) {
            return $this->response;
        }
        return null;
    }



}