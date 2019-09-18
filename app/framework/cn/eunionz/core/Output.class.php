<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Output class
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */


namespace cn\eunionz\core;

use cn\eunionz\exception\ViewException;

defined('APP_IN') or exit('Access Denied');
class Output extends Kernel {
    // template extension
    private $outputs = array();

    // output header
    private $headers = array();

    // output compression option
    private $output_compress = false;

    // output compression
    private $_zlib_oc = false;

    public function __construct()
    {
        $this->_zlib_oc = @ini_get('zlib.output_compression');
        $this->output_compress = getConfig('app', 'APP_OUTPUT_COMPRESS');
    }

    /**
     * set output
     *
     * push an string to output buffer array
     *
     * @param $str
     */
    public function setOutput($str)
    {
        array_push($this->outputs, $str);
    }

    /**
     * get output
     *
     * get output buffer array
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->outputs;
    }

    /**
     * render output
     *
     * render and display output content
     *
     * @param string $str
     * @param bool $compress
     */
    public function render($str = '', $compress = true)
    {
        // set header
        if (count($this->headers) > 0)
            foreach ($this->headers as $header)
                header($header[0], $header[1]);

        echo empty($str) ? implode('', $this->outputs) : $str;
    }

    /**
     * set output header
     *
     * set output header info
     *
     * $replace is true, Replace existing same type header.
     *
     * @param string $header
     * @param bool $replace
     */
    public function setHeader($header, $replace = true)
    {
        $this->headers[] = array($header, $replace);
    }

    /**
     * set header status
     *
     * set response status qrcode
     *
     * @param integer $code
     * @param string  $text
     */
    public function setHeaderStatus($code = 200, $text = '')
    {
        $stati = array(
            200	=> 'OK',
            201	=> 'Created',
            202	=> 'Accepted',
            203	=> 'Non-Authoritative Information',
            204	=> 'No Content',
            205	=> 'Reset Content',
            206	=> 'Partial Content',

            300	=> 'Multiple Choices',
            301	=> 'Moved Permanently',
            302	=> 'Found',
            304	=> 'Not Modified',
            305	=> 'Use Proxy',
            307	=> 'Temporary Redirect',

            400	=> 'Bad Request',
            401	=> 'Unauthorized',
            403	=> 'Forbidden',
            404	=> 'Not Found',
            405	=> 'Method Not Allowed',
            406	=> 'Not Acceptable',
            407	=> 'Proxy Authentication Required',
            408	=> 'Request Timeout',
            409	=> 'Conflict',
            410	=> 'Gone',
            411	=> 'Length Required',
            412	=> 'Precondition Failed',
            413	=> 'Request Entity Too Large',
            414	=> 'Request-URI Too Long',
            415	=> 'Unsupported Media Type',
            416	=> 'Requested Range Not Satisfiable',
            417	=> 'Expectation Failed',

            500	=> 'Internal Server Error',
            501	=> 'Not Implemented',
            502	=> 'Bad Gateway',
            503	=> 'Service Unavailable',
            504	=> 'Gateway Timeout',
            505	=> 'HTTP Version Not Supported'
        );

        if ($code == '' || ! is_numeric($code))
            throw new ViewException(ctx()->getI18n()->getLang('error_output_title'),ctx()->getI18n()->getLang('error_output_status'));

        if (isset($stati[$code]) && $text == '')
            $text = $stati[$code];

        if ($text == '')
            throw new ViewException(ctx()->getI18n()->getLang('error_output_title'),ctx()->getI18n()->getLang('error_output_status_text'));

        $server_protocol = ($this->server('SERVER_PROTOCOL')) ? $this->server('SERVER_PROTOCOL') : false;

        if (substr(php_sapi_name(), 0, 3) == 'cgi')
            $this->setHeader("Status: {$code} {$text}", true);
        elseif ($server_protocol == 'HTTP/1.1' || $server_protocol == 'HTTP/1.0')
            $this->setHeader($server_protocol." {$code} {$text}", true);
        else
            $this->setHeader("HTTP/1.1 {$code} {$text}", true);
    }

    /**
     * set header charset
     *
     * set response header charset
     *
     * @param $charset string
     */
    public function setHeaderCharset($charset)
    {
        $this->setHeader("Content-type:text/html; charset={$charset}", true);
    }

    /**
     * set header type
     *
     * set response header type
     *
     * @param string $mime_type
     */
    public function setHeaderType($mime_type)
    {
        $mimes = array(
            'hqx'	=>	'application/mac-binhex40',
            'cpt'	=>	'application/mac-compactpro',
            'csv'	=>	array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
            'bin'	=>	'application/macbinary',
            'dms'	=>	'application/octet-stream',
            'lha'	=>	'application/octet-stream',
            'lzh'	=>	'application/octet-stream',
            'exe'	=>	array('application/octet-stream', 'application/x-msdownload'),
            'class'	=>	'application/octet-stream',
            'psd'	=>	'application/x-photoshop',
            'so'	=>	'application/octet-stream',
            'sea'	=>	'application/octet-stream',
            'dll'	=>	'application/octet-stream',
            'oda'	=>	'application/oda',
            'pdf'	=>	array('application/pdf', 'application/x-download'),
            'ai'	=>	'application/postscript',
            'eps'	=>	'application/postscript',
            'ps'	=>	'application/postscript',
            'smi'	=>	'application/smil',
            'smil'	=>	'application/smil',
            'mif'	=>	'application/vnd.mif',
            'xls'	=>	array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
            'ppt'	=>	array('application/powerpoint', 'application/vnd.ms-powerpoint'),
            'wbxml'	=>	'application/wbxml',
            'wmlc'	=>	'application/wmlc',
            'dcr'	=>	'application/x-director',
            'dir'	=>	'application/x-director',
            'dxr'	=>	'application/x-director',
            'dvi'	=>	'application/x-dvi',
            'gtar'	=>	'application/x-gtar',
            'gz'	=>	'application/x-gzip',
            'php'	=>	'application/x-httpd-php',
            'php4'	=>	'application/x-httpd-php',
            'php3'	=>	'application/x-httpd-php',
            'phtml'	=>	'application/x-httpd-php',
            'phps'	=>	'application/x-httpd-php-source',
            'js'	=>	'application/x-javascript',
            'swf'	=>	'application/x-shockwave-flash',
            'sit'	=>	'application/x-stuffit',
            'tar'	=>	'application/x-tar',
            'tgz'	=>	array('application/x-tar', 'application/x-gzip-compressed'),
            'xhtml'	=>	'application/xhtml+xml',
            'xht'	=>	'application/xhtml+xml',
            'zip'	=>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
            'mid'	=>	'audio/midi',
            'midi'	=>	'audio/midi',
            'mpga'	=>	'audio/mpeg',
            'mp2'	=>	'audio/mpeg',
            'mp3'	=>	array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
            'aif'	=>	'audio/x-aiff',
            'aiff'	=>	'audio/x-aiff',
            'aifc'	=>	'audio/x-aiff',
            'ram'	=>	'audio/x-pn-realaudio',
            'rm'	=>	'audio/x-pn-realaudio',
            'rpm'	=>	'audio/x-pn-realaudio-plugin',
            'ra'	=>	'audio/x-realaudio',
            'rv'	=>	'video/vnd.rn-realvideo',
            'wav'	=>	array('audio/x-wav', 'audio/wave', 'audio/wav'),
            'bmp'	=>	array('image/bmp', 'image/x-windows-bmp'),
            'gif'	=>	'image/gif',
            'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
            'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
            'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
            'png'	=>	array('image/png',  'image/x-png'),
            'tiff'	=>	'image/tiff',
            'tif'	=>	'image/tiff',
            'css'	=>	'text/css',
            'html'	=>	'text/html',
            'htm'	=>	'text/html',
            'shtml'	=>	'text/html',
            'txt'	=>	'text/plain',
            'text'	=>	'text/plain',
            'log'	=>	array('text/plain', 'text/x-log'),
            'rtx'	=>	'text/richtext',
            'rtf'	=>	'text/rtf',
            'xml'	=>	'text/xml',
            'xsl'	=>	'text/xml',
            'mpeg'	=>	'video/mpeg',
            'mpg'	=>	'video/mpeg',
            'mpe'	=>	'video/mpeg',
            'qt'	=>	'video/quicktime',
            'mov'	=>	'video/quicktime',
            'avi'	=>	'video/x-msvideo',
            'movie'	=>	'video/x-sgi-movie',
            'doc'	=>	'application/msword',
            'docx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
            'xlsx'	=>	array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
            'word'	=>	array('application/msword', 'application/octet-stream'),
            'xl'	=>	'application/excel',
            'eml'	=>	'message/rfc822',
            'json' => array('application/json', 'text/json')
        );

        if (strpos($mime_type, '/') === false)
        {
            $extension = ltrim($mime_type, '.');

            // Is this extension supported?
            if (isset($mimes[$extension]))
            {
                $mime_type =& $mimes[$extension];

                if (is_array($mime_type))
                    $mime_type = current($mime_type);
            }
        }

        $header = 'Content-Type: '.$mime_type;

        $this->headers[] = array($header, true);
    }
}
