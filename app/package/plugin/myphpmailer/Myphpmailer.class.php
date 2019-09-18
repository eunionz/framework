<?php
/**
 * EUnionZ PHP Framework Myphpmailer Plugin class
 * phpmailer 邮件管理类
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 16-9-30
 * Time: 上午10:16
 */

namespace package\plugin\myphpmailer;



defined('APP_IN') or exit('Access Denied');

/**
 * 邮件管理类
 * phpmailer 邮件管理类
 * @package plugin\phpmailer
 */
 require_once('idna_convert.class.php');
class Myphpmailer extends \com\eunionz\core\Plugin
{

    // 实例句柄
    static protected $_instance;

    // Mailer 实例
    static private $_mailer;

    private $mail_cfg=array(
        'CharSet'=>'utf-8',
        'Host'=>'',
        'Port'=>25,
        'SMTPAuth'=>true,
        'Username'=>'',
        'Password'=>'',
        'From'=>'',
        'ReplyTo'=>'',
    );

    /**
     * 构造函数
     * Myphpmailer constructor.
     */
    public function __construct()
    {
        require_once 'PHPMailerAutoload.php';
    }

    public function init($mail_cfg=false){
        if($mail_cfg) $this->mail_cfg = $mail_cfg;
        return $this;
    }

    public function sender($address, $name, $subject, $body){
		
		$IDN = new \idna_convert();
	    $address = isset($address) ? stripslashes($address) : '';
	    $address = $IDN->encode($address);
		
        //Create a new PHPMailer instance
        $_mailer = new \PHPMailer;
        //Tell PHPMailer to use SMTP
        $_mailer->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $_mailer->SMTPDebug = 0;

        $_mailer->CharSet = $this->mail_cfg['CharSet'];

        //Ask for HTML-friendly debug output
        $_mailer->Debugoutput = 'html';
        //Set the hostname of the mail server
        $_mailer->Host = $this->mail_cfg['Host'];
        //Set the SMTP port number - likely to be 25, 465 or 587
        $_mailer->Port = $this->mail_cfg['Port'];
        //Whether to use SMTP authentication
        $_mailer->SMTPAuth = $this->mail_cfg['SMTPAuth'];
        //Username to use for SMTP authentication
        $_mailer->Username = $this->mail_cfg['Username'];
        //Password to use for SMTP authentication
        $_mailer->Password = $this->mail_cfg['Password'];
        //Set who the message is to be sent from
        $_mailer->setFrom($this->mail_cfg['From'],$this->mail_cfg['From']);
        //Set an alternative reply-to address
        $_mailer->addReplyTo($this->mail_cfg['ReplyTo'],$this->mail_cfg['ReplyTo']);
        //Set who the message is to be sent to

        $_mailer->addAddress($address, $name);
        //Set the subject line
        $_mailer->Subject = $subject;
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $_mailer->msgHTML($body);
        //Replace the plain text body with one created manually
        $_mailer->AltBody = strip_tags($body);
        //Attach an image file
        //self::$_mailer->addAttachment('images/phpmailer_mini.png');
        if (!$_mailer->send()) {
            $this->loadCore("log")->write(APP_DEBUG,"Mailer Error: " . $_mailer->ErrorInfo,'sendemailerr');
            return false;
        }
        return true;
    }


}
