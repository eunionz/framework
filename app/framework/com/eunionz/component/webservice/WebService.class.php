<?php
/**
 * Eunionz PHP Framework WebService class
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */


namespace com\eunionz\component\webservice;

defined('APP_IN') or exit('Access Denied');
class WebService extends \com\eunionz\core\Component {

    // 实例句柄
    static protected $_instance;

    // 命名空间
    private $namespace;

    // 操作函数数组
    private $operation;

    // 服务地址
    private $serviceURI;

    // 字符集
    private $charset;

    // 目标空间
    private $targetNamespace;

    private function createTarGetNameSpace(&$namespace)
    {
        return APP_URL . '/' . $namespace;
    }

    private function createDefinitions()
    {
        $xml = '
	xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
	xmlns:tns="'.$this->targetNamespace.'"
	xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
	xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
	xmlns="http://schemas.xmlsoap.org/wsdl/"
	targetNamespace="'.$this->targetNamespace.'">
	<types>
		<xsd:schema targetNamespace="'.$this->targetNamespace.'">
			<xsd:import namespace="http://schemas.xmlsoap.org/soap/encoding/" />
			<xsd:import namespace="http://schemas.xmlsoap.org/wsdl/" />
		</xsd:schema>
	</types>';

        return $xml;
    }

    private function createMessage()
    {
        $xml = null;

        // 消息类型
        $msgType = array(
            'int', 'float', 'double', 'boolean', 'time', 'date',
            'dateTime', 'hexBinary', 'string'
        );

        foreach ($this->operation as $k => $v)
        {
            if (!is_null($xml))
            {
                $xml .= '
	';
            }
            $xml .= '<message name="'.$k.'Request">';

            if (isset($v['input']))
            {
                foreach ($v['input'] as $k_ => $v_)
                {
                    $xml .= '
		<part name="' . $k_ . '" type="xsd:' . $v_ . '" />';
                }
            }

            $xml .= '
	</message>
	';

            $xml .= '<message name="'.$k.'Response">
		<part name="return" type="xsd:'.$v['return'].'" />
	</message>';
        }

        return $xml;
    }

    private function createPortType()
    {
        $xml = '<portType name="'.$this->namespace.'PortType">';

        foreach ($this->operation as $k => $v)
        {
            $xml .= '
		<operation name="'.$k.'">
			<documentation>'.trim(@$v['doc']).'</documentation>
			<input message="tns:'.$k.'Request"/>
			<output message="tns:'.$k.'Response"/>
		</operation>';
        }

        $xml .= '
	</portType>';

        return $xml;
    }

    private function createBinding()
    {
        $xml = '<binding name="'.$this->namespace.'Binding" type="tns:'.$this->namespace.'PortType">
		<soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>';

        foreach ($this->operation as $k => $v)
        {
            $xml .= '
		<operation name="' . $k . '">
			<soap:operation soapAction="urn:'.$this->namespace.'#'.$k.'" style="rpc"/>
			<input><soap:body use="encoded" namespace="urn:'.$this->namespace.'" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></input>
			<output><soap:body use="encoded" namespace="urn:'.$this->namespace.'" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></output>
		</operation>';
        }

        $xml .= '
	</binding>';

        return $xml;
    }

    private function createService()
    {
        return '<service name="'.$this->namespace.'">
 		<port name="'.$this->namespace.'Port" binding="tns:'.$this->namespace.'Binding">
			<soap:address location="'.$this->serviceURI.'"/>
		</port>
	</service>';
    }

    private function createWSDL()
    {
        return '<?xml version =\'1.0\' encoding =\''.$this->charset.'\' ?>
<definitions'.$this->createdefinitions().'
	'.$this->createMessage().'
	'.$this->createPortType().'
	'.$this->createBinding().'
	'.$this->createService().'
</definitions>';
    }

    private function parseClass($operation)
    {
        $functions = array();

        $class = new \ReflectionClass($operation);

        foreach ($class->getMethods() as $method)
        {
            if (($method->class != $operation) || ('_' == substr($method->name, 0, 1)))
                continue;

            // 读取函数说明与输入输出类型
            $doc = $method->getDocComment();
            $doc = str_replace(array('/**', '*/', '* ', '	 '), '', $doc);
            $doc = explode("\n", $doc);
            $doc = array_unique($doc); // 删除空行
            $doc = array_splice($doc, 1); // 重新排序

            // 检查是否有远程定义符号
            if (!preg_match('/\@access remote/i', implode('_', $doc)))
                continue;

            // 写入说明
            $functions[$method->name]['doc'] = array_shift($doc);

            // 删除远程定义符号
            array_shift($doc);

            // 读取参数
            foreach ($doc as $d)
            {
                if (false !== stripos($d, '@param'))
                {
                    $p = explode(' ', trim($d));
                    $p = array_values(array_diff($p, array(null, '', ' ', "\t")));

                    list($tag, $type, $name) = $p;

                    // 变量名为输入名
                    $name = substr($name, 1);
                    $functions[$method->name]['input'][$name] = $type;
                }

                if (false !== stripos($d, '@return'))
                {
                    $p = explode(' ', trim($d));
                    $p = array_values(array_diff($p, array(null, '', ' ', "\t")));

                    list($tag, $type) = $p;

                    $functions[$method->name]['return'] = $type;
                }
            }
        }

        if (empty($functions))
        {
            throw new \com\eunionz\exception\WebServiceException($this->getLang('error_webservice_title'),$this->getLang('error_webservice_operation',array($operation)));
        }

        return $functions;
    }

    /**
     * 输出WSDL文件
     *
     * 直接输出显示WSDL文件
     *
     * @param string $namespace 命名空间
     * @param string $serviceURI 服务地址
     * @param array $operation 函数数组
     * @param string $charset XML字符集
     */
    public function renderWSDL($namespace, $serviceURI, $operation, $charset = 'UTF-8')
    {
        $xml = $this->readWSDL($namespace, $serviceURI, $operation, $charset);
        header("Content-Type: text/xml\r\n");
        exit($xml);
    }

    /**
     * 读取WSDL
     *
     * 读取生成的WSDL内容
     *
     * @param string $namespace 命名空间
     * @param string $serviceURI 服务地址
     * @param array $operation 函数数组
     * @param string $charset XML字符集
     */
    public function readWSDL($namespace, $serviceURI, $operation, $charset = 'UTF-8')
    {
        // 根据类来创建
        if (is_string($operation) && class_exists($operation))
            $operation = $this->parseClass($operation);

        $this->namespace = $namespace;
        $this->serviceURI = $serviceURI;
        $this->operation = $operation;
        $this->charset = $charset;
        $this->targetNamespace = $this->createTarGetNameSpace($namespace);

        return $this->createWSDL();
    }

    /**
     * 创建WebService服务
     *
     * @param string $wsdlURI wsdl文件地址
     * @param string $className 服务类名
     */
    public function create($wsdlURI, $className)
    {
        if (!class_exists('SoapServer'))
            throw new \com\eunionz\exception\WebServiceException($this->getLang('error_webservice_title'),$this->getLang('error_webservice_soap'));

        $server = new \SoapServer($wsdlURI);
        $server->setClass($className);
        $server->handle();
    }

}
