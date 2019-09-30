<?php
/**
 * Eunionz PHP Framework Mail component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\mail;

use cn\eunionz\exception\BaseException;

defined('APP_IN') or exit('Access Denied');
/**
 * @file mail.component.php
 *
 * @brief 邮件组件
 *
 * @author liulin, ziee@sohu.com
 * @date 2012/09/13
 */


/**
 * 邮件处理
 *
 * 实现邮件发送处理功能。
 * 依赖 socket 扩展
 *
 */
class Mail extends \cn\eunionz\core\Component
{
    // 实例句柄
    static protected $_instance;

    // Mailer 实例
    static private $_mailer;

    /**
     * 初始化邮件组件
     */
    public function __construct()
    {

    }

    public function init($cfg = null)
    {
        self::$_mailer = new PHPMailer();
        if($cfg){
            self::$_mailer->CharSet = $cfg['CharSet'];
            self::$_mailer->IsSMTP();
            self::$_mailer->SMTPAuth = $cfg['SMTPAuth'];
            self::$_mailer->Host = $cfg['Host'];
            self::$_mailer->Port = $cfg['Port'];
            self::$_mailer->Username = $cfg['Username'];
            self::$_mailer->Password = $cfg['Password'];
            self::$_mailer->From = $cfg['From'];
            self::$_mailer->FromName = $cfg['FromName'];

            self::$_mailer->SMTPSecure = $cfg['SMTPSecure'];

        }else{
            self::$_mailer->CharSet = 'UTF-8';
            self::$_mailer->IsSMTP();
            self::$_mailer->SMTPAuth = true;
            $plad_shop_id = $this->session('PLATFORM_SHOP_ID') ? $this->session('PLATFORM_SHOP_ID') : ctx()->getShopId();
            self::$_mailer->Host = $this->loadService('shop_params')->get_value_by_key('SITE_MAIL_HOST', $plad_shop_id);
            self::$_mailer->Port = $this->loadService('shop_params')->get_value_by_key('SITE_MAIL_PORT', $plad_shop_id);
            self::$_mailer->Username = $this->loadService('shop_params')->get_value_by_key('SITE_MAIL_USERNAME', $plad_shop_id);
            self::$_mailer->Password = $this->loadService('shop_params')->get_value_by_key('SITE_MAIL_PASSWORD', $plad_shop_id);
            self::$_mailer->From = $this->loadService('shop_params')->get_value_by_key('SITE_MAIL_FROM', $plad_shop_id);
            self::$_mailer->FromName = $this->loadService('shop_params')->get_value_by_key('SITE_MAIL_FROMNAME', $plad_shop_id);

            if ($this->loadService('shop_params')->get_value_by_key('SITE_MAIL_IS_SSL', $plad_shop_id))
                self::$_mailer->SMTPSecure = 'ssl';

        }

        return $this;

    }

    /**
     * 发送邮件
     *
     * 发送邮件内容到指定地址
     *
     * @code
     * $this->sender('ziee@sohu.com', 'Godlker', 'Subject', 'This is body...');
     * @endcode
     *
     * @param string $address 邮件地址
     * @param string $name 接收人名称
     * @param string $subject 主题
     * @param string $body 正文
     *
     * @return bool
     */
    public function sender($address, $name, $subject, $body)
    {
        try{
            self::$_mailer->AddAddress($address, $name);

            self::$_mailer->Subject = $subject;

            self::$_mailer->MsgHTML($body);

            if (!self::$_mailer->Send()) {
                // throw new BaseException(self::$_mailer->ErrorInfo);
                $this->loadCore("log")->write(APP_DEBUG, self::$_mailer->ErrorInfo, 'sendemailerr');
                return false;
            }

        }catch (\Exception $err){
            return false;
        }
        return true;
    }
}

