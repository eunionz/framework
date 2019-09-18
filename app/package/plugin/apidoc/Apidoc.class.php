<?php
namespace package\plugin\apidoc;
defined('APP_IN') or exit('Access Denied');
include_once("DocParser.php");
include_once("helper.php");
class Apidoc extends \com\eunionz\core\Plugin
{
    protected  $config = [
        'title'=>'APi接口文档',
        'version'=>'1.0.0',
        'copyright'=>'Powered By wangtao',
    	'controller_namespace'=>'',
        'controller' => [],
    	'controller_path' => "",
    	'filter_controller' => [],
        'filter_method'=>['_empty']
    ];

    /**
     * 架构方法 设置参数
     * @access public
     * @param  array $config 配置参数
     */
    public function __construct()
    {
    	$config=$this->getConfig('apidoc');
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 使用 $this->name 获取配置
     * @access public
     * @param  string $name 配置名称
     * @return mixed    配置值
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * 设置验证码配置
     * @access public
     * @param  string $name  配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 检查配置
     * @access public
     * @param  string $name 配置名称
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 获取接口列表
     * @return array
     */
    public function getList()
    {
    	$controller_namespace = $this->config['controller_namespace'];
        $controller_path = $this->config['controller_path'];
        $controller_list = $this->config['controller'];
        $filter_controller = $this->config['filter_controller'];
        //var_dump($controller_path);
        //var_dump(opendir($controller_path));die;
        //根据目录获取文件列表
        if (!empty($controller_path) && ($dh = opendir($controller_path))){
        	while (($file = readdir($dh))!= false){
        		if(!in_array($file,array(".",".."))){
	        		//文件名的全路径 包含文件名
	        		$controller_list[] = str_replace(".class.php","",$file);
        		}
        	}
        	closedir($dh);
        }
        $controller_list = array_unique($controller_list);
        $controller_list = array_diff($controller_list,$filter_controller);
        $list = [];
        foreach ($controller_list as $class){
            if(class_exists($controller_namespace.$class)){
                $moudel= [];
                $reflection = new \ReflectionClass($controller_namespace.$class);
                $doc_str = $reflection->getDocComment();
                require_once(__DIR__ . "/DocParser.php");
                $doc = new \DocParser();
                $class_doc = $doc->parse($doc_str);
                $moudel =  $class_doc;
                $moudel['class'] = $class;
                $moudel['title'] = $class."【".$moudel['title']."】";
                $method = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $filter_method = array_merge(['__construct'], $this->config['filter_method']);
                $moudel['actions'] = [];
                foreach ($method as $action){
                    if(!in_array($action->name, $filter_method))
                    {
                        $doc = new \DocParser();
                        $doc_str = $action->getDocComment();
                        if($doc_str)
                        {
                            $action_doc = $doc->parse($doc_str);
                            $action_doc['name'] = $class."::".$action->name;
                            //去除方法名前缀_
                            $action_doc['title'] = ltrim($action->name,"_")." : ".$action_doc['title'];
                            array_push($moudel['actions'], $action_doc);
                        }
                    }
                }
                
                array_push($list, $moudel);
            }
        }
        return $list;
    }

    /**
     * 获取类中指导方法注释详情
     * @param $class
     * @param $action
     * @return array
     */
    public function getInfo($class, $action)
    {
    	$controller_namespace = $this->config['controller_namespace'];
        $action_doc = [];
        if($class && class_exists($controller_namespace.$class)){
            $reflection = new \ReflectionClass($controller_namespace.$class);
            if($reflection->hasMethod($action)) {
                $method = $reflection->getMethod($action);
                $doc = new \DocParser();
                $action_doc = $doc->parse($method->getDocComment());
            }
        }
        return $action_doc;
    }

    /**
     * 格式化数组为json字符串-用于格式显示
     * @param array $doc
     * @return string
     */
    public function formatReturn($doc = [])
    {
        $json = '{<br>';
        $returns = isset($doc['return']) ? $doc['return'] : [];
        foreach ($returns as $val){
            list($name, $value) =  explode(":", trim($val));
            if(strpos($value, '@') != false){
                $json .= $this->string2jsonArray($doc, $val, '&nbsp;&nbsp;&nbsp;&nbsp;');
            }else{
                $json .= '&nbsp;&nbsp;&nbsp;&nbsp;' . $this->string2json(trim($name), $value);
            }
        }
        $json .= '}';
        return $json;
    }

    /**
     * 格式化json字符串-用于展示
     * @param $name
     * @param $val
     * @return string
     */
    private function string2json($name, $val){
        if(strpos($val,'#') != false){
            return '"'.$name.'": ["'.str_replace('#','',$val).'"],<br/>';
        }else {
            return '"'.$name.'":"'.$val.'",<br/>';
        }
    }

    /**
     * 递归转换数组为json字符格式-用于展示
     * @param $doc
     * @param $val
     * @param $space
     * @return string
     */
    private function string2jsonArray($doc, $val, $space){
        list($name, $value) =  explode(":", trim($val));
        $json = "";
        if(strpos($value, "@!") != false){
            $json .= $space.'"'.$name.'":{//'.str_replace('@!','',$value).'<br/>';
        }else{
            $json .= $space.'"'.$name.'":[{//'.str_replace('@','',$value).'<br/>';
        }
        $return = isset($doc[$name]) ? $doc[$name] : [];
        if(preg_match_all('/(\w+):(.*?)[\s\n]/s', $return." ", $meatchs)){
            foreach ($meatchs[0] as $key=>$v){
                if(strpos($meatchs[2][$key],'@') != false){
                    $json .= $this->string2jsonArray($doc,$v,$space.'&nbsp;&nbsp;');
                } else{
                    $json .= $space.'&nbsp;&nbsp;'. $this->string2json(trim($meatchs[1][$key]), $meatchs[2][$key]);
                }
            }
        }
        if(strpos($value, "@!") != false){
            $json .= $space."},<br/>";
        }else{
            $json .= $space."}],<br/>";
        }
        return $json;
    }
}