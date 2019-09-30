<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Service class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */


namespace cn\eunionz\core;

defined('APP_IN') or exit('Access Denied');
class Service extends Kernel {

    /**
     * @var string
     */
    private $modelName='';

    /**
     * 是否开启查询缓存
     * @var bool
     */
    public $is_query_cache=true;

    //服务店铺id,取自于shop.config.php中的SHOP_ID
    public $SHOP_ID=0;
    public $shop_version = '';//当前店铺版本
    public $client_version = 1.0;//当前客户端版本号，默认 2.2
    public $version_config = '';//版本配置文档


    /**
     * 是否跳过初始化SHOP_ID
     * @var bool
     */
    public $_is_skip_init_SHOP_ID=false;
    /**
     * 初始化服务
     * @param string $tablename
     */
    public function initialize($serviceName=""){
        if(!$this->_is_skip_init_SHOP_ID){
            $this->SHOP_ID=ctx()->getShopId();
        }
        if(empty($serviceName)){
            $temps=explode("\\",get_called_class());
            $this->modelName=$temps[count($temps)-1];
        }else{
            $this->modelName=$serviceName;
        }
        if($this->session('client_version')){
            $this->client_version=$this->session('client_version');
        }

    }

    /**
     * call service
     *
     * This is load_service alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function S($name, $single = true, $APP_ASSEMBLY_NAME='')
    {
        return $this->loadService($name, $single, $APP_ASSEMBLY_NAME);
    }

    /**
     * call component
     *
     * This is load_component alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function C($name, $single = true)
    {
        return $this->loadComponent($name, $single);
    }

    /**
     * call plugin
     *
     * This is load_plugin alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function P($name, $single = true, $APP_ASSEMBLY_NAME='')
    {
        return $this->loadPlugin($name, $single, $APP_ASSEMBLY_NAME);
    }

    /**
     * call resource
     *
     * This is load_resource alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function M($name, $single = true, $APP_ASSEMBLY_NAME='')
    {
        return $this->loadModel($name, $single, $APP_ASSEMBLY_NAME);
    }


    /**
     * get config item
     *
     * get this global config item
     *
     * $namespace is config file name, But does not contain ".config.php" suffix.
     * $key is null, get all item.
     *
     * @param string $namespace
     * @param string $key
     *
     * @return mixed
     */
    final function F($namespace, $key = '', $APP_ASSEMBLY_NAME='')
    {
        return self::getConfig($namespace, $key, $APP_ASSEMBLY_NAME);
    }


    /**
     * 添加记录
     * @param $model
     */
    public function add($model){
        return $this->loadModel($this->modelName)->insert($model);
    }

    /**
     * 修改记录
     * @param $model 必须包含主键字段的值
     */
    public function update($model){
        $pk = $this->loadModel($this->modelName)->pk();
        $opts['where'][$pk]=$model[$pk];
        return $this->loadModel($this->modelName)->update($model,$opts);
    }

    /**
     * 按条件修改记录
     * @param $model 要修改的数据
     * @param $opts 条件
     */
    public function updateByWhere($model,$opts){
        return $this->loadModel($this->modelName)->update($model,$opts);
    }

    /**
     * 删除记录
     * @param $model 必须包含主键字段的值
     */
    public function delete($model){
        $pk = $this->loadModel($this->modelName)->pk();
        $opts['where'][$pk]=$model[$pk];
        return $this->loadModel($this->modelName)->delete($opts);
    }

    /**
     * 根据主键字段值删除记录
     * @param $id 主键字段值
     */
    public function deleteByID($id){
        $pk = $this->loadModel($this->modelName)->pk();
        $opts['where'][$pk]=$id;
        return $this->loadModel($this->modelName)->delete($opts);
    }

    /**
     * 根据条件删除记录
     * @param $opts 条件
     */
    public function deleteByWhere($opts){
        return $this->loadModel($this->modelName)->delete($opts);
    }

    /**
     * 查询满足条件的记录
     * @param $opts
     */
    public function find($opts){
        return $this->loadModel($this->modelName)->find($opts);
    }


    /**
     * 根据id查询数据
     * @param $opts
     */
    public function findByID($id){
        $pk = $this->loadModel($this->modelName)->pk();
        $opts['where'][$pk]=$id;
        return $this->loadModel($this->modelName)->find_one($opts);
    }

    /**
     * 查询满足条件的第1条记录
     * @param $opts
     */
    public function find_one($opts){
        return $this->loadModel($this->modelName)->find_one($opts);
    }


    /**
     * 查询满足条件的第1条记录指定字段
     * @param $opts
     * @param $field
     */
    public function find_field($opts,$field){
        return $this->loadModel($this->modelName)->find_field($opts, $field);
    }

    /**
     * 获取数据列表
     * @param array $field  查询字段 array()
     * @param array $where  查询条件 array()
     * @param array $order  排序
     * @param $start 开始记录号索引号
     * @param $pagesize 页大小
     * @param $recordCount 返回的记录总数
     * @return array 成功--返回数组  失败--返回 false
     */
    public function get_list($field,$where,$order,&$recordCount,$start=0,$pagesize=10){
        $opts=array();
        if($where) $opts['where']=$where;
        $recordCount=$this->loadModel($this->modelName)->count($opts);
        if($field) $opts['field']=$field;
        if($order) $opts['order']=$order;
        $opts['limit'] = array($start,$pagesize);
        $rs = $this->loadModel($this->modelName)->find($opts);
        return $rs;
    }

    /**
     * 统计记录总数
     * @param $opts
     */
    public function count($opts){
        return $this->loadModel($this->modelName)->count($opts);
    }

    /**
     * 检查传递进来的条件是否进行了条件分组，即有数字索引号下标
     * @param $opts 包括'where'的key的数组或直接为where数组
     * @return true--进行了条件分组    false--未进行条件分组
     */
    public function isWhereGroup($opts){
        if(isset($opts['where'])){
            if($opts['where'] && is_array($opts['where'])){
                foreach ($opts['where'] as $key => $val ){
                    if(is_numeric($key)){
                        return true;
                    }
                }
            }
        }else{
            if($opts && is_array($opts)){
                foreach ($opts as $key => $val ){
                    if(is_numeric($key)){
                        return true;
                    }
                }
            }
        }
        return false;
    }

}
