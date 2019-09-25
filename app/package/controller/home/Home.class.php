<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 下午2:48
 */

namespace package\controller\home;

use cn\eunionz\component\amqpConsumer\AmqpConsumer;
use cn\eunionz\component\consul\Consul;
use cn\eunionz\component\elasticsearch\Elasticsearch;
use cn\eunionz\component\grpc\Parser;
use cn\eunionz\core\Controller;
use Elasticsearch\ClientBuilder;
use Grpc\HiReply;
use Grpc\HiUser;
use Helloworld\GreeterClient;
use Helloworld\HelloRequest;
use package\application\GrpcClient;
use package\application\HttpClient;
use package\application\RpcClient;
use package\application\TcpClient;
use package\application\UdpClient;
use package\grpc\HiClient;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Home
 * @package package\controller\home
 * @RPC_CLASS
 */
class Home extends \cn\eunionz\core\Controller
{

    /**
     * is cache view result
     * @var bool
     */
    public $is_cache_view = false;

    /**
     * view cache lifetime
     * @var bool
     */
    public $_cache_view_lifetime = 10;

    //public $_disabled_cache_view_actions = ['_index'];

    public $_enabled_cache_view_actions = ['_index' => 5];

    public function _index($a)
    {
        $this->viewData['test_var'] = "加  333 444 555" . $a;
        $arr = [
            [
                'a' => 1,
                'b' => 'aaa',
            ],
            [
                'a' => 2,
                'b' => 'aaa',
            ],
            [
                'a' => 3,
                'b' => 'aaa',
            ],
        ];
        $this->viewData['arr'] = $arr;

        $arr1 = [
            [
                1,
                'aaa',
            ],
            [
                2,
                'aaa',
            ],
            [
                3,
                'aaa',
            ],
        ];
        $this->viewData['arr1'] = $arr1;

        $this->session('login_user', array('admin', 'ddd'));
        $langs = $this->getLang('error_server_config');
//        $this->ajaxReturn(
//            array('name' => 'zs', 'sex' => "男", 'a' => $this->get('a'),
//                'time' => date('Y-m-d H:i:s'),
//                'langs' => $langs,
//                'language' => $this->getLanguage() ,
//                'session' => $this->session(),
//                )
//        );
        $this->write(ctx()->getShopId() . '<hr/>');
        $this->write(print_r($this->session(), true));
        $this->display();
    }

    public function _ajax()
    {
        $this->session('login_user', array('admin', 'ddd'));
        $langs = $this->getLang('error_server_config');
        $content = $this->ajaxReturn(
            array('name' => 'zs', 'sex' => "男", 'a' => $this->get('a'),
                'time' => date('Y-m-d H:i:s'),
                'langs' => $langs,
                'language' => $this->getLanguage(),
                'session' => $this->session(),
                'userseconds' => '__PAGE_EXECUTE_SECONDS__',
                'db_query_times' => '__PAGE_EXECUTE_QUERYS__',
            ), true
        );
        $this->write($content);
    }


    public function _main()
    {

//        $html= $this->loadActionByUrl('/home/home/sub.shtml');
        $html = $this->loadAction(Home::class, 'sub', [$this->request('a')]);
        $this->viewData['html'] = $html;
        $this->display();
    }


    public function _sub($a)
    {
        $this->viewData['time'] = date('Y-m-d H:i:s') . ' ' . $a;

        $html = $this->loadAction(Home::class, 'sub_sub', [$a]);
        $this->viewData['html'] = $html;

        $this->display();
    }

    public function _sub_sub($a)
    {
        $this->viewData['time'] = $a . ' ' . date('Y-m-d H:i:s');
        $this->display();
    }


    public function _index_2_1()
    {
        $this->viewData['test_var'] = "加 2.1  time: " . date("Y-m-d H:i:s");

        $arr = [
            [
                'a' => 1,
                'b' => 'aaa',
            ],
            [
                'a' => 2,
                'b' => 'aaa',
            ],
            [
                'a' => 3,
                'b' => 'aaa',
            ],
        ];
        $this->viewData['arr'] = $arr;

        $arr1 = [
            [
                1,
                'aaa',
            ],
            [
                2,
                'aaa',
            ],
            [
                3,
                'aaa',
            ],
        ];
        $this->viewData['arr1'] = $arr1;

        $this->session('login_user', array('admin', 'ddd'));
        $langs = $this->getLang('error_server_config');
//        $this->ajaxReturn(
//            array('name' => 'zs', 'sex' => "男", 'a' => $this->get('a'),
//                'time' => date('Y-m-d H:i:s'),
//                'langs' => $langs,
//                'language' => $this->getLanguage() ,
//                'session' => $this->session(),
//                )
//        );
        $html = $this->display(null, [], true);
        $this->write($html);
        return $html;

    }


    public function _index1()
    {
        $this->viewData['test_var'] = "加" . ($this->session('login_user') ? print_r($this->session('login_user'), true) : '');


//        $this->ajaxReturn(array('name'=>'zs' , 'sex' =>"男"));
        $this->display();


    }


    public function _index2($a)
    {

        ctx()->getSession()->delSession();

        $this->ajaxReturn(array('name' => 'zs', 'sex' => "男", 'a' => $a, 'b' => $this->get('b')));

    }

    public function _client()
    {
        $this->session("login_user", "admin");
        $this->addHeader("Content-Type", "text/html;charset=utf-8");

        $this->write(date("Y-m-d H:i:s") . "<hr/>");
        $rs = $this->httpGetCall("ms_usercenter", '/home/add1/53/46.shtml');
        $this->write($rs);
        $this->write("<hr/>");
        $this->write(print_r($this->session(), true));
        $this->write("<hr/>");

    }


    public function _client1()
    {
        $this->session("login_user", "admin");
        $this->addHeader("Content-Type", "text/html;charset=utf-8");
        $this->write(date("Y-m-d H:i:s") . "<hr/>");
        $rs = $this->httpGetCall("ms_usercenter", '/home/http/533/46.shtml', [], false, array('clientversion' => 2.0));
        $this->write($rs);
        $this->write(print_r($this->session(), true));
    }


    /**
     * Rpc服务类中add服务方法
     * @param $a
     * @param $b
     * @RPC_METHOD
     */
    public function _http($a, $b)
    {
        ctx()->closeTraceOutput();
        $this->session("login_user", "admin23455555555=");
        $this->write($a * 10 + $b);
//        $this->write(print_r($this->session(), true));
    }


    /**
     * Rpc服务类中add服务方法
     * @param $a
     * @param $b
     * @RPC_METHOD
     */
    public function _http_2_1($a, $b)
    {
        ctx()->closeTraceOutput();
        $this->session("login_user", "admin23455555555=");
        $this->write($a * 100 + $b);
//        $this->write(print_r($this->session(), true));
    }


    public function _client2()
    {
        $this->session("login_user", "admin");
        $this->addHeader("Content-Type", "text/html;charset=utf-8");
        $this->write(date("Y-m-d H:i:s") . "<hr/>");
//        $rs = $this->httpPostCall("ms_usercenter", '/home/add2.shtml', ['name' => 'admin', 'paasword' => 'admin1234'], false, [], array(array('name' => 'file', 'path' => APP_REAL_PATH . '1.png'), array('name' => 'file1', 'path' => APP_REAL_PATH . '2.png')));

        $rs = $this->http_post_call('http_service_default', '/home/add2.shtml', ['name' => 'admin', 'paasword' => 'admin1234'], false, [], array(array('name' => 'file', 'path' => APP_REAL_PATH . '1.png'), array('name' => 'file1', 'path' => APP_REAL_PATH . '2.png')));
        $this->write($rs);
        $this->write(print_r($this->session(), true));
    }


    public function _http_client()
    {
        $this->session("login_user", "admin");
        $this->addHeader("Content-Type", "text/html;charset=utf-8");
        $rpc_class = "\\package\\controller\\home\\Home";
//        $rpc_client = $this->rpc_client('rpc_service_test', $rpc_class);
        $rpc_client = $this->rpcClient("ms_usercenter", $rpc_class);
        if ($rpc_client) {
            $rs = $rpc_client->_add(23, 45);
            if ($rs) {
                $this->write(print_r($rs, true));
            } else {
                $this->write($this->getLang('error_rpc_service_no_response', array($rpc_class, '_add')));
            }
        }
        $this->write(print_r($this->session(), true));
    }

    public function _http_client1()
    {
        $this->addHeader("Content-Type", "text/html;charset=utf-8");
        $rpc_class = "\\package\\controller\\home\\Home";
//        $rpc_client = $this->rpc_client('rpc_service_test', $rpc_class);

//        $rpc_client = $this->rpcClient("ms_usercenter", $rpc_class);
        $rpc_client = new RpcClient("192.168.1.135",8899);
        if ($rpc_client) {
            $rs = $rpc_client->_add1(23, 4445);
            if ($rs) {
                $this->write(print_r($rs, true));
            } else {
                $this->write($this->getLang('error_rpc_service_no_response', array($rpc_class, '_add1')));
            }
        }
        $this->write(print_r($this->session(), true));
    }


    /**
     * Rpc服务类中add服务方法
     * @param $a
     * @param $b
     * @RPC_METHOD
     */
    public function _add($a, $b)
    {
        $this->session("login_user", "admin23455555555=");
        $obj = new \stdClass();
        $obj->return = $a + $b;
        return $obj;
    }

//

    /**
     * Rpc服务类中add服务方法
     * @param $a
     * @param $b
     * @RPC_METHOD
     */
    public function _add1($a, $b)
    {
        $this->session("login_user", "admin2345" . $this->getLang('app_404_error_content'));
        return $a + $b;
//        $this->write($a + $b);
    }

    /**
     * Rpc服务类中add服务方法
     * @param $a
     * @param $b
     */
    public function _add1_1_02($a, $b)
    {
        ctx()->closeTraceOutput();
        $this->session("login_user", "admin2345");
        $this->write($a + $b . ' === ');
        $this->write($this->getLang('app_404_error_content'));
    }


    public function _add2()
    {
        ctx()->closeTraceOutput();
        $this->session("login_user", "admin2345222");
        $this->write(print_r($this->post(), true));
        if ($this->files()) {
            $this->write(print_r($this->files(), true));
            foreach ($this->files() as $form_name => $file) {
                copy($file['tmp_name'], ctx()->getAppRuntimeRealPath() . 'uploads' . APP_DS . $file['name']);
            }
        }
    }

    public function _tcp_client()
    {
        $this->addHeader("Content-Type", "text/html;charset=utf-8");
        $tcp_client = new TcpClient();
        $data = $tcp_client->send("tcp hello");
        $this->write($data);
    }


    public function _udp_client()
    {
        $this->addHeader("Content-Type", "text/html;charset=utf-8");
        $udp_client = new UdpClient();
        $data = $udp_client->send("udp hello");
        $this->write($data);
    }

    public function _upfile()
    {
        ctx()->closeTraceOutput();
        $html = "<!DOCTYPE html>
                <html>
                <head>
                    <meta charset=\"utf-8\">
                    <title>文件上传21333333333===</title>
                </head>
                <body>
                <hr/>
                <form enctype='multipart/form-data' method='post' action='/home/add2.shtml'>
                    <input type='file' id='file' name='file'>
                    <input type='submit' value='上传333332===6444433frg75456'>
                </form>
                </body>
                </html>";
        $this->write($html);
    }


    public function _db()
    {
//        $sql = "SELECT * FROM `shop_base`";
//        $rs = $this->C('db')->query($sql);
        $this->write(" <meta charset=\"utf-8\">");
        $this->db()->start_trans();
//        $this->write(print_r($this->C('db')->getConnect(),true));
        try {

            $sql = "INSERT INTO `short_url` VALUES(0,'AAAAAA1" . time() . "' ,'bbbbbb===','" . time() . "','" . time() . "')";
            $rs = $this->db()->exec($sql);
//            $this->write(print_r($this->C('db')->getConnect(),true));

//            $sql = "INSERT INTO `short_url` VALUES(131,'AAAAA2A===','bbbbbb===','" . time() . "','" . time() . "')";
//            $rs = $this->cdb()->exec($sql);
//            $this->write(print_r($this->C('db')->getConnect(),true));

            $this->db()->commit();
//            $this->write(print_r($this->C('db')->getConnect(),true));
            $this->write('success');
        } catch (\Exception $err) {
            $this->write($err->getMessage());
//            $this->write(print_r($this->C('db')->getConnect(),true));
            $this->db()->rollback();
            $this->write('fail');
        }

    }

    public function _cdb()
    {
//        $sql = "SELECT * FROM `shop_base`";
//        $rs = $this->C('db')->query($sql);
        $this->write(" <meta charset=\"utf-8\">");
        $this->cdb()->start_trans();
//        $this->write(print_r($this->C('db')->getConnect(),true));
        try {

            $sql = "INSERT INTO `short_url` VALUES(0,'AAAAAA1" . time() . "' ,'bbbbbb===','" . time() . "','" . time() . "')";
            $rs = $this->cdb()->exec($sql);
//            $this->write(print_r($this->C('db')->getConnect(),true));

            $sql = "INSERT INTO `short_url` VALUES(131,'AAAAAA1" . time() . "' ,'bbbbbb===','" . time() . "','" . time() . "')";
            $rs = $this->cdb()->exec($sql);
            $this->write(print_r($this->cdb()->getConnect(), true));

            $this->cdb()->commit();
//            $this->write(print_r($this->C('db')->getConnect(),true));
            $this->write('success');
        } catch (\Exception $err) {
            $this->write($err->getMessage());
//            $this->write(print_r($this->C('db')->getConnect(),true));
            $this->cdb()->rollback();
            $this->write('fail');
        }

    }


    public function _db1()
    {
//        $this->write(print_r($this->loadModel('short_url'),true));
        $opts = [];
        $opts['limit'] = 10;
        $rs = $this->loadModel('short_url')->find($opts);
        $this->write(print_r($this->loadModel('short_url')->current_db()->getConnect(), true));
        $this->write(print_r($rs, true));

        //self::consoleln($this->db()->get_sql());

    }

    public function _db2()
    {
        $this->write(" <meta charset=\"utf-8\">");
        $this->cdb()->start_trans();
        try {
            $id = $this->loadModel('short_url')->insert(['short_url' => 'LLLLLLLL123', 'long_url' => 'LLLLLLLL123===']);


            $id2 = $this->loadModel('short_url')->insert(['id' => 131, 'short_url' => 'LLLLLLLL123----', 'long_url' => 'LLLLLLLL123---']);

            $this->write($this->cdb()->get_sql());
            $this->write('<hr/>ddddd23');

//            $opts['where']['id']=3056;
//            $id = $this->loadModel('short_url')->update(['short_url' => 'aaaa', 'long_url' => 'aaaaaa===']);
            $this->cdb()->commit();
            $this->write('success ' . $id);// . ' ' . $id2);
        } catch (\Exception $err) {
            $this->write($err->getMessage());
            $this->cdb()->rollback();
            $this->write('fail');
        }

    }

    public function _db3()
    {
        $this->write(" <meta charset=\"utf-8\">");
        $opts['field'] = "*";
//        $opts['where'][0]['id'] = 1;
//        $opts['where'][1]['id'] = 1;
//        $opts['where'][1]['short_url']['LIKE'] = '%Yh%';
//        $opts['where'][1]['_logic'] = 'OR';
//        $opts['where'][1][1]['id'] = 1;
//        $opts['where'][1][1]['__sql__'] = "EXISTS(SELECT * FROM `shop_base`)";
//        $opts['where'][1][1]['_logic'] = 'OR';
//        $opts['where'][2]['id']['IN'] = '(F{__field__}SELECT id FROM `short_url` WHERE id<1000)';
//        $opts['where'][2]['id']['>'] = '(F{id} + 10)';
        $opts['limit'] = 10;
        $rs = $this->cache(ctx()->getShopID() . '_short_url', array($opts));
        if (!$rs) {
            $this->cdb()->start_trans();
            try {

//            $opts['where']['id'] = 3072;
                //$id = $this->loadModel('short_url')->update(['short_url' => 'aaaa12', 'long_url' => 'aaaaaa==3='], $opts);
//            $id = $this->loadModel('short_url')->delete($opts);

//            $opts['field'] = ['id1'=>'(SELECT COUNT(* F{__field__}) FROM `short_url`)','id2'=>'(SELECT COUNT(* F{__field__}) FROM `short_url`)','n'=>'LEFT(F{short_url},10)','m'=>'NOW(F{__field__})'];
                $rs = $this->loadModel('short_url')->find($opts);
                $this->cache(ctx()->getShopID() . '_short_url', array($opts), $rs);
                $this->write($this->cdb()->get_sql());
                $this->cdb()->commit();
                $this->write(print_r($rs, true));// . ' ' . $id2);
                $this->write('success ' . count($rs));// . ' ' . $id2);
            } catch (\Exception $err) {
                $this->write($err->getMessage());
                $this->cdb()->rollback();
                $this->write('fail');
            }
        } else {
            $this->write(print_r($rs, true));// . ' ' . $id2);
            $this->write('success ' . count($rs));// . ' ' . $id2);
        }
    }

    public function _db4()
    {
        $this->write(" <meta charset=\"utf-8\">");
        $this->log(APP_INFO, 'ddd', 'sql');
        //////////////////////////////////////////////////////////////////////////////////
        ////////////              分布式事务使用事例开始                      ////////////
        $pdo = $this->db();
        $pdo1 = $this->db("test");

        //数据库1的事务
        $pdo->xa_start();
        $xa_rs = false;
        try {
            $sql = "INSERT INTO `sessions` VALUES(?,?,?,?)";
            $num = $pdo->exec($sql, array('1111111111123452122==1', 15, 1, '4334443433443'));
            if (!$num) {
                throw  new \Exception("ddd");
            }
            $sql = "SELECT * FROM `sessions`";
            $rs = $pdo->query($sql);
            $xa_rs = true;
        } catch (\Exception $err) {

        }
        $pdo->xa_end();
        if ($xa_rs) {
            $pdo->xa_prepare();
        }

        //数据库2的事务
        $pdo1->xa_start();
        $xa1_rs = false;
        try {
            $sql = "INSERT INTO `sessions` VALUES(?,?,?)";
            $num = $pdo1->exec($sql, array('1111111111123452122==2', 15, '4334443433443'));
            if (!$num) {
                throw  new \Exception("ddd");
            }
            $sql = "SELECT * FROM `sessions`";
            $rs = $pdo1->query($sql);

            $sql = "INSERT INTO `sessions` VALUES(?,?,?)";
            $num = $pdo1->exec($sql, array('1111111111123452113', 15, '4334443433443'));
            if (!$num) {
                throw  new \Exception("ddd");
            }

            $xa1_rs = true;
        } catch (\Exception $err) {

        }
        $pdo1->xa_end();
        if ($xa1_rs) {
            $pdo1->xa_prepare();
        }

        if ($xa_rs && $xa1_rs) {
            $pdo->xa_commit();
            $pdo1->xa_commit();
            $this->write('<hr/>success');
        } else {
            $pdo->xa_rollback();
            $pdo1->xa_rollback();
            $this->write('<hr/>fail');
        }

        ////////////              分布式事务使用事例结束                      ////////////
        //////////////////////////////////////////////////////////////////////////////////
    }
//
//    public function _aaa(){
//        $yaml =new Yaml();
//        $this->write(print_r($yaml,true));
//    }


    public function _test()
    {
        self::consoleln(print_r($this->loadComponent('cdb'), true));

    }


    public function _amqp()
    {
        $amqpPublisher = $this->loadComponent('amqpPublisher', false);
        $amqpPublisher->send(['a' => '233232', 'b' => $this->get('b')]);

    }

    public function _amqp_c()
    {
        $amqpConsumer = $this->loadComponent('amqpConsumer', false);

//        $amqpConsumer->run(function ($msg) {
//            self::consoleln($msg->body);
//        });
//        $amqpConsumer->run(array($this,'handle'));

        $amqpConsumer->run(array(self::class, 'handle1'));
    }

    public function handle($msg)
    {
        self::consoleln($msg->body, APP_ERROR);
    }

    public static function handle1($msg)
    {
        self::consoleln($msg->body . ' 333333', APP_ERROR);
    }

//    public function _sayHello()
//    {
//        $opts['credentials'] = null;
//        $grpcClient = new GrpcClient('127.0.0.1:8888', $opts);
//        $grpcClient->start();
//        $request = new HiUser();
//        $request->setName("liulin");
//        list($reply, $status) = $grpcClient->SayHello($request);
//        $message = $reply->getMessage();
//        echo "{$message}\n";
//        $grpcClient->close();
//
//    }


    public function _es()
    {
        $this->write(" <meta charset=\"utf-8\">");
        $es = new Elasticsearch();

        //先删除索引
        $params = array();
        $params['index'] = 'goods_base_index';
        $es->deleteIndex($params);
        $params['body'] = [
            'mappings' => [
                'goods_base_type' => [         //某个type
                    'properties' => [     #开始定义这个type的属性值（也可以用fields）
                        'goods_name' => [
                            'type' => 'text',  //字符串: text|keyword 整数 : byte, short, integer, long 浮点数: float, double 布尔型: boolean 日期: date
                            //keyword：存储数据时候，不会分词建立索引,用的时候不用analyzer
                            //text：存储数据时候，会自动分词，并生成索引（这是很智能的，但在有些字段里面是没用的，所以对于有些字段使用text则浪费了空间）。
                            'analyzer' => 'ik_max_word',
                            "search_analyzer" => "ik_max_word",//查询分词
                        ],
                        'goods_keywords' => [
                            'type' => 'text',  //字符串: text|keyword 整数 : byte, short, integer, long 浮点数: float, double 布尔型: boolean 日期: date
                            //keyword：存储数据时候，不会分词建立索引,用的时候不用analyzer
                            //text：存储数据时候，会自动分词，并生成索引（这是很智能的，但在有些字段里面是没用的，所以对于有些字段使用text则浪费了空间）。
                            'analyzer' => 'ik_max_word',
                            "search_analyzer" => "ik_max_word",//查询分词
                        ],
                    ]
                ],
            ]
        ];

//        //再创建
        $es->createIndex($params);

        $opts = [];
//        $opts['limit'] = 100;
        $rs = $this->loadModel('goods_base')->find($opts);
        foreach ($rs as $row) {
            $params = array();
            $params['body'] = array(
                'goods_id' => $row['goods_id'],
                'goods_name' => $row['goods_name'],
                'goods_keywords' => $row['goods_keywords'],
            );
            $params['index'] = 'goods_base_index';
            $params['type'] = 'goods_base_type';
            $es->addOne($params);
        }
        $this->write(' 创建 es [goods_base_index] 索引成功<hr/>');

    }

    public function _es_index()
    {
        $this->write(" <meta charset=\"utf-8\">");
        $es = new Elasticsearch();

        //先删除索引
        $params = array();
        $params['index'] = 'goods_base_index';
        $params['type'] = 'goods_base_type';
        $params['body']['query']['match']['goods_name'] = '商品';
        $params['body']['sort'] = array('goods_id' => array('order' => 'desc'));
        $params['size'] = 100;
        $params['from'] = 0;
        $rs = $es->search($params);
        $this->write(print_r($rs, true));

    }

    public function _health()
    {
        $this->ajaxReturn(['status' => 'ok']);
    }

    public function _del_service()
    {
        $consul = new Consul();
        $consul->service_deregister($this->get('name'));
    }

    public function _reg_service()
    {
        $consul = new Consul();
        $consul->service_register('eunionz2', 'eunionz', '192.168.1.135', 9999, ['http'], ["eunionz_version" => "1.0"], [
            'id' => 'eunionz_rpc_check',
            'name' => '"HTTP API ON PORT 9999',
            'http' => 'http://192.168.1.135:9999/home/health.shtml',
            'Interval' => '10s',
            'timeout' => '1s',
        ], [
            "Passing" => 10,
            "Warning" => 1,
        ]);
    }

    public function _services()
    {
        $consul = new Consul();
        $rs = $consul->service_list('eunionz', 'http');
        $this->write(print_r($rs, true));
    }


    public function _off_service()
    {
        $consul = new Consul();
        $rs = $consul->service_offline('eunionz1');
        $this->write(print_r($rs, true));
    }

    public function _on_service()
    {
        $consul = new Consul();
        $rs = $consul->service_online('eunionz1');
        $this->write(print_r($rs, true));
    }

    public function _rnd_service()
    {
        $consul = new Consul();
        $rs = $consul->get_service('ms_usercenter');
        $this->write(print_r($rs, true));
    }

    /**
     * GRPC服务端方法
     * @return bool
     */
    public function _sayHello()
    {
        $req_object = ctx()->getRequestObjectByGrpc();
        if ($req_object) {
            $resp_object = new \Grpc\HiReply();
            $resp_object->setMessage('Hello 777 ' . $req_object->getName() . "," . $req_object->getSex());
            ctx()->responseGrpc($resp_object);
        }
    }

    /**
     * GRPC 客户端方法通过服务中心
     * @return bool
     */
    public function _sayHello_c()
    {
        $grpcClient = $this->grpcClient("ms_usercenter");
        if($grpcClient){
            $grpcClient->start();
            $request = new HiUser();
            $request->setName("aa 3444 dd" . $this->get('aaa'));
            $status = -1;
            try {
                list($reply, $status) = $grpcClient->sayHello("/grpc.hi", $request, HiReply::class);
                $message = $reply->getMessage();
                $this->write("message = {$message} status = " . $status);
            } catch (\Exception $err) {
                $this->write("message = {$err->getMessage()} status = " . $status);
            }
            $grpcClient->close();
        }
    }

    /**
     * GRPC 客户端方法通过服务中心
     * @return bool
     */
    public function _sayHello_cn()
    {
        $grpcClient = $this->grpcClient("ms_usercenter");
        if($grpcClient){
            $grpcClient->start();
            $request = new HiUser();
            $request->setName("aa 3444 dd" . $this->get('aaa'));
            list($reply, $status) = $grpcClient->sayHello("/grpc.hi", $request, HiReply::class);
            $message = $reply->getMessage();
            $this->write("message = {$message} status = " . $status);
            $grpcClient->close();
        }
    }

    /**
     * GRPC 客户端方法不通过服务中心
     * @return bool
     */
    public function _sayHello_cn1()
    {
//        $grpcClient = new GrpcClient();
//        if($grpcClient){
//            $grpcClient->start();
//            $request = new HiUser();
//            $request->setName("aa 3444 dd" . $this->get('aaa'));
//            list($reply, $status) = $grpcClient->sayHello("/grpc.hi", $request, HiReply::class);
//            $message = $reply->getMessage();
//            $this->write("message = {$message} status = " . $status);
//            $grpcClient->close();
//        }

        $request = new HiUser();
        $request->setName("aa 3444 dd" . $this->get('aaa'));

        list($reply, $status) =$this->grpcCall("ms_usercenter", "/grpc.hi", "sayHello", $request, HiReply::class);
        $message = $reply->getMessage();
        $this->write("message = {$message} status = " . $status);

    }

    /**
     * GRPC 客户端方法不通过服务中心
     * @return bool
     */
    public function _sayHello_cn2()
    {
        $request = new HiUser();
        $request->setName("aa 3444 dd" . $this->get('aaa'));
        $grpcClient = new GrpcClient("192.168.1.194", 8888);
        if ($grpcClient) {
            $grpcClient->start();
            list($reply, $status) = $grpcClient->sayHello("/grpc.hi", $request, HiReply::class);
            $grpcClient->close();

            $message = $reply->getMessage();
            $this->write("message = {$message} status = " . $status);
        }else{
            $this->write("message = no Reply status = -1");
        }
    }

    public function _p(){
        $this->write(" <meta charset=\"utf-8\">");
        $this->write($this->loadPlugin('common')->getWeekDay(0));
    }


    public function _s(){
        $this->write(" <meta charset=\"utf-8\">");
        if($this->is_get()){
            $this->display();
        }elseif($this->is_post()){
            $this->write(print_r($this->post() , true));
        }
    }
}
