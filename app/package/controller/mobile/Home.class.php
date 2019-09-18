<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 下午2:48
 */

namespace package\controller\mobile;

use cn\eunionz\core\Controller;
use package\application\RpcClient;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Home
 * @package package\controller\mobile
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
    public $_cache_view_lifetime = 30;

    public $_is_ob_start = false;

    public function _index()
    {
//        ctx()->setTheme('default');

        $this->viewData['test_var'] = "加";
        $arr = [
            [
                'a'=>1,
                'b'=>'aaa',
            ],
            [
                'a'=>2,
                'b'=>'aaa',
            ],
            [
                'a'=>3,
                'b'=>'aaa',
            ],
        ];
        $this->viewData['arr']  = $arr;

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
        $this->viewData['arr1']  = $arr1;

        $this->session('login_user', array('admin', 'ddd'));
        $langs =  $this->getLang('error_server_config');
//        $this->ajaxReturn(
//            array('name' => 'zs', 'sex' => "男", 'a' => $this->get('a'),
//                'time' => date('Y-m-d H:i:s'),
//                'langs' => $langs,
//                'language' => $this->getLanguage() ,
//                'session' => $this->session(),
//                )
//        );
        $this->display();


    }

//    public function _index_2_1()
//    {
//
//        $this->viewData['test_var'] = "加 2.1";
//
//        $this->session('login_user', array('admin', 'ddd'));
//        $langs = $this->getLang('error_server_config');
////        $this->ajaxReturn(
////            array('name' => 'zs', 'sex' => "男", 'a' => $this->get('a'),
////                'time' => date('Y-m-d H:i:s'),
////                'langs' => $langs,
////                'language' => $this->getLanguage() ,
////                'session' => $this->session(),
////                )
////        );
//        $this->display();
//
//
//    }

    public function _index1()
    {
        $this->viewData['test_var'] = "加" . ($this->session('login_user') ? print_r($this->session('login_user'), true) : '');


//        $this->ajaxReturn(array('name'=>'zs' , 'sex' =>"男"));
        $this->display();


    }
//
//
//    public function _index2($a)
//    {
//
//        $this->delSession();
//
//        $this->ajaxReturn(array('name' => 'zs', 'sex' => "男", 'a' => $a, 'b' => $this->get('b')));
//
//    }
//
//    public function _client()
//    {
//        $this->session("login_user", "admin");
//        $this->addHeader("Content-Type", "text/html;charset=utf-8");
//        $rs = $this->http_get_call('http_service_default', '/home/add1/53/46.shtml');
//        $this->write($rs);
//        $this->write(print_r($this->session(), true));
//
//    }
//
//
//    public function _client1()
//    {
//        $this->session("login_user", "admin");
//        $this->addHeader("Content-Type", "text/html;charset=utf-8");
//        $rs = $this->http_get_call('http_service_default', '/home/add1/533/46.shtml', [], false, array('clientversion' => 2.3));
//        $this->write($rs);
//        $this->write(print_r($this->session(), true));
//    }
//
//
//    public function _client2()
//    {
//        $this->session("login_user", "admin");
//        $this->addHeader("Content-Type", "text/html;charset=utf-8");
//        $rs = $this->http_post_call('http_service_default', '/home/add2.shtml', ['name' => 'admin', 'paasword' => 'admin1234'], false, [], array(array('name' => 'file', 'path' => '/home/wwwroot/swoole/swoole/1.png'), array('name' => 'file1', 'path' => '/home/wwwroot/swoole/swoole/2.png')));
//        $this->write($rs);
//        $this->write(print_r($this->session(), true));
//    }
//
//
//    public function _http_client()
//    {
//        $this->session("login_user", "admin");
//        $this->addHeader("Content-Type", "text/html;charset=utf-8");
//        $rpc_class = "\\package\\controller\\home\\Home";
//        $rpc_class_method = "_add";
//        $rs = $this->rpc_call('rpc_service_test', $rpc_class, $rpc_class_method, [23, 45]);
//        if ($rs) {
//            $this->write(print_r($rs, true));
//        } else {
//            $this->write($this->getLang('error_rpc_service_no_response', array($rpc_class, $rpc_class_method)));
//        }
//        $this->write(print_r($this->session(), true));
//
//    }
//
//    /**
//     * Rpc服务类中add服务方法
//     * @param $a
//     * @param $b
//     * @RPC_METHOD
//     */
//    public function _add($a, $b)
//    {
//        $this->session("login_user", "admin2345");
//        return $a + $b;
//    }
//
//
//    /**
//     * Rpc服务类中add服务方法
//     * @param $a
//     * @param $b
//     */
//    public function _add1($a, $b)
//    {
//        $this->closeTraceOutput();
//        $this->session("login_user", "admin2345");
//        $this->write($a + $b . ' ');
//        $this->write($this->getLang('app_404_error_content'));
//    }
//
//    /**
//     * Rpc服务类中add服务方法
//     * @param $a
//     * @param $b
//     */
//    public function _add1_1_02($a, $b)
//    {
//        $this->closeTraceOutput();
//        $this->session("login_user", "admin2345");
//        $this->write($a + $b . ' === ');
//        $this->write($this->getLang('app_404_error_content'));
//    }
//
//
//    public function _add2()
//    {
//        $this->closeTraceOutput();
//        $this->session("login_user", "admin2345222");
//        $this->write(print_r($this->post(), true));
//        if ($this->files()) {
//            $this->write(print_r($this->files(), true));
//            foreach ($this->files() as $form_name => $file) {
//                move_uploaded_file($file['tmp_name'], APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . $file['name']);
//            }
//        }
//    }
//
//
//    public function _upfile()
//    {
//        $this->closeTraceOutput();
//        $html = "<!DOCTYPE html>
//                <html>
//                <head>
//                    <meta charset=\"utf-8\">
//                    <title>文件上传21333333333===</title>
//                </head>
//                <body>
//                <hr/>
//                <form enctype='multipart/form-data' method='post' action='/home/add2.shtml'>
//                    <input type='file' id='file' name='file'>
//                    <input type='submit' value='上传333332===6444433frg75456'>
//                </form>
//                </body>
//                </html>";
//        $this->write($html);
//    }
//
//
//    public function _db()
//    {
////        $sql = "SELECT * FROM `shop_base`";
////        $rs = $this->C('db')->query($sql);
//        $this->write(" <meta charset=\"utf-8\">");
//        $this->db()->start_trans();
////        $this->write(print_r($this->C('db')->getConnect(),true));
//        try {
//
//            $sql = "INSERT INTO `short_url` VALUES(0,'AAAAAA1===','bbbbbb===','" . time() . "','" . time() . "')";
//            $rs = $this->db()->exec($sql);
////            $this->write(print_r($this->C('db')->getConnect(),true));
//
//            $sql = "INSERT INTO `short_url` VALUES(131,'AAAAA2A===','bbbbbb===','" . time() . "','" . time() . "')";
//            $rs = $this->db()->exec($sql);
////            $this->write(print_r($this->C('db')->getConnect(),true));
//
//            $this->db()->commit();
////            $this->write(print_r($this->C('db')->getConnect(),true));
//            $this->write('success');
//        } catch (\Exception $err) {
//            $this->write($err->getMessage());
////            $this->write(print_r($this->C('db')->getConnect(),true));
//            $this->db()->rollback();
//            $this->write('fail');
//        }
//
//    }
//
//
//    public function _db1()
//    {
////        $this->write(print_r($this->loadModel('short_url'),true));
//        $opts = [];
//        $rs = $this->loadModel('short_url')->find($opts);
//        $this->write(print_r($rs, true));
//
//    }
//
//    public function _db2()
//    {
//        $this->write(" <meta charset=\"utf-8\">");
//        $this->db()->start_trans();
//        try {
//            $id = $this->loadModel('short_url')->insert(['short_url' => 'LLLLLLLL123', 'long_url' => 'LLLLLLLL123===']);
//
//
////            $id2 = $this->loadModel('short_url')->insert(['id' => 131, 'short_url' => 'LLLLLLLL123----', 'long_url' => 'LLLLLLLL123---']);
//
//            $this->write($this->db()->get_sql());
//            $this->write('<hr/>ddddd23');
//
////            $opts['where']['id']=3056;
////            $id = $this->loadModel('short_url')->update(['short_url' => 'aaaa', 'long_url' => 'aaaaaa===']);
//            $this->db()->commit();
//            $this->write('success ' . $id);// . ' ' . $id2);
//        } catch (\Exception $err) {
//            $this->write($err->getMessage());
//            $this->db()->rollback();
//            $this->write('fail');
//        }
//
//    }
//
//    public function _db3()
//    {
//        $this->write(" <meta charset=\"utf-8\">");
//        $opts['field'] = "*";
//        $opts['where'][0]['id'] = 1;
//        $opts['where'][1]['id'] = 1;
//        $opts['where'][1]['short_url']['LIKE'] = '%Yh%';
//        $opts['where'][1]['_logic'] = 'OR';
//        $opts['where'][1][1]['id'] = 1;
//        $opts['where'][1][1]['__sql__'] = "EXISTS(SELECT * FROM `shop_base`)";
//        $opts['where'][1][1]['_logic'] = 'OR';
//        $opts['where'][2]['id']['IN'] = '(F{__field__}SELECT id FROM `short_url` WHERE id<1000)';
//        $opts['where'][2]['id']['>'] = 'F{id} + 10';
//        $rs = $this->cache($this->getShopID() . '_short_url', array($opts));
//        if (!$rs) {
//            $this->db()->start_trans();
//            try {
//
////            $opts['where']['id'] = 3072;
//                //$id = $this->loadModel('short_url')->update(['short_url' => 'aaaa12', 'long_url' => 'aaaaaa==3='], $opts);
////            $id = $this->loadModel('short_url')->delete($opts);
//
////            $opts['field'] = ['id1'=>'(SELECT COUNT(* F{__field__}) FROM `short_url`)','id2'=>'(SELECT COUNT(* F{__field__}) FROM `short_url`)','n'=>'LEFT(F{short_url},10)','m'=>'NOW(F{__field__})'];
//                $rs = $this->loadModel('short_url')->find($opts);
//                $this->cache($this->getShopID() . '_short_url', array($opts), $rs);
//                $this->write($this->db()->get_sql());
//                $this->db()->commit();
//                $this->write(print_r($rs, true));// . ' ' . $id2);
//                $this->write('success ' . count($rs));// . ' ' . $id2);
//            } catch (\Exception $err) {
//                $this->write($err->getMessage());
//                $this->db()->rollback();
//                $this->write('fail');
//            }
//        } else {
//            $this->write(print_r($rs, true));// . ' ' . $id2);
//            $this->write('success ' . count($rs));// . ' ' . $id2);
//        }
//    }
//
//    public function _db4()
//    {
//        $this->write(" <meta charset=\"utf-8\">");
//        $this->log(APP_INFO,'ddd' , 'sql');
//        //////////////////////////////////////////////////////////////////////////////////
//        ////////////              分布式事务使用事例开始                      ////////////
//        $pdo = $this->db();
//        $pdo1 = $this->db("test");
//
//        //数据库1的事务
//        $pdo->xa_start();
//        $xa_rs = false;
//        try {
//            $sql = "INSERT INTO `sessions` VALUES(?,?,?,?)";
//            $num = $pdo->exec($sql, array('1111111111123452122==', 15, 1, '4334443433443'));
//            if (!$num) {
//                throw  new \Exception("ddd");
//            }
//            $sql = "SELECT * FROM `sessions`";
//            $rs = $pdo->query($sql);
//            $xa_rs = true;
//        } catch (\Exception $err) {
//
//        }
//        $pdo->xa_end();
//        if ($xa_rs) {
//            $pdo->xa_prepare();
//        }
//
//        //数据库2的事务
//        $pdo1->xa_start();
//        $xa1_rs = false;
//        try {
//            $sql = "INSERT INTO `sessions` VALUES(?,?,?)";
//            $num = $pdo1->exec($sql, array('1111111111123452122==', 15, '4334443433443'));
//            if (!$num) {
//                throw  new \Exception("ddd");
//            }
//            $sql = "SELECT * FROM `sessions`";
//            $rs = $pdo1->query($sql);
//
//            $sql = "INSERT INTO `sessions` VALUES(?,?,?)";
//            $num = $pdo1->exec($sql, array('111111111112345211', 15, '4334443433443'));
//            if (!$num) {
//                throw  new \Exception("ddd");
//            }
//
//            $xa1_rs = true;
//        } catch (\Exception $err) {
//
//        }
//        $pdo1->xa_end();
//        if ($xa1_rs) {
//            $pdo1->xa_prepare();
//        }
//
//        if ($xa_rs && $xa1_rs) {
//            $pdo->xa_commit();
//            $pdo1->xa_commit();
//            $this->write('<hr/>success');
//        } else {
//            $pdo->xa_rollback();
//            $pdo1->xa_rollback();
//            $this->write('<hr/>fail');
//        }
//
//        ////////////              分布式事务使用事例结束                      ////////////
//        //////////////////////////////////////////////////////////////////////////////////
//    }
//
//    public function _aaa(){
//        $yaml =new Yaml();
//        $this->write(print_r($yaml,true));
//    }
}
