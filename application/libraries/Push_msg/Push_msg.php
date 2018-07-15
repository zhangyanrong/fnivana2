<?php
/**
 * 向会员推送信息
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   application/libraries/Push_msg
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Push_msg.php 1 2014-11-17 10:16:44Z pax $
 * @link      http://www.fruitday.com
 **/
class Push_msg extends CI_Driver_Library 
{
	public $valid_drivers = array(
		'Push_msg_order_create',
        'Push_msg_order_delivery',
        'Push_msg_order_pay_timeout',
        'Push_msg_gift_expire',
	);

	/**
	 * 会员推送设置
	 *
	 * @var string
	 **/
	public $msgsetting = 252;

	/**
	 * 订单生成发短信
	 *
	 * @var string
	 **/
	const ORDER_CREATE_SEND_SMS = 0b0000000000000001;

	/**
	 * 订单生成发邮件
	 *
	 * @var string
	 **/
	const ORDER_CREATE_SEND_EMAIL = 0b0000000000000010;

	/**
	 * 订单发货发短信
	 *
	 * @var string
	 **/
	const ORDER_DELIVERY_SEND_SMS = 0b0000000000000100;

	/**
	 * 订单发货发邮件
	 *
	 * @var string
	 **/
	const ORDER_DELIVERY_SEND_EMAIL = 0b0000000000001000;

	/**
	 * 订单支付超时发短信
	 *
	 * @var string
	 **/
	const ORDER_PAY_TIMEOUT_SEND_SMS = 0b0000000000010000;

	/**
	 * 订单支付超时发邮件
	 *
	 * @var string
	 **/
	const ORDER_PAY_TIMEOUT_SEND_EMAIL = 0b0000000000100000;

	/**
	 * 赠品过期发短信
	 *
	 * @var string
	 **/
	const GIFT_EXPIRE_SEND_SMS = 0b0000000001000000;

	/**
	 * 赠品过期发邮件
	 *
	 * @var string
	 **/
	const GIFT_EXPIRE_SEND_EMAIL = 0b0000000010000000;

	/**
     * 获取推送设置结构
     *
     * @return void
     * @author 
     **/
    public function getwaysetting()
    {
      $setting = array(
        'order_create'=>array(
          'name' => '下单成功推送',
          'ways' => array(
              'email' => array('value'=>self::ORDER_CREATE_SEND_EMAIL),
              'sms'  => array('value' => self::ORDER_CREATE_SEND_SMS),
          ),
        ),
        'order_delivery' => array(
          'name' => '订单发货推送',
          'ways' => array(
              'email' => array('value' => self::ORDER_DELIVERY_SEND_EMAIL),
              'sms'  => array('value' => self::ORDER_DELIVERY_SEND_SMS),
          ),
        ),
        'order_pay_timeout'=>array(
          'name' => '支付超时推送',
          'ways' => array(
              'email' => array('value' => self::ORDER_PAY_TIMEOUT_SEND_EMAIL),
              'sms'  => array('value' => self::ORDER_PAY_TIMEOUT_SEND_SMS),
          ),
        ),
        'gift_expire'=>array(
          'name' => '赠品过期推送',
          'ways' => array(
              'email' => array('value' => self::GIFT_EXPIRE_SEND_EMAIL),
              'sms'  => array('value' => self::GIFT_EXPIRE_SEND_SMS),
          ),
        ),
      );

      return $setting;
    }

    /**
     * 赋值
     *
     * @return void
     * @author 
     **/
    public function set_msgsetting($msgsetting)
    {
    	$this->msgsetting = $msgsetting;

    	return $this;
    }

}