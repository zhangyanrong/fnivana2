<?php
/**
 * 订单生成推送信息
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   application/libraries/Push_msg/drivers
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Push_msg_order_create.php 1 2014-11-17 10:19:49Z pax $
 * @link      http://www.fruitday.com
 **/
class Push_msg_order_create extends CI_Driver
{	
	/**
	 * 订单信息
	 *
	 * @var string
	 **/
	private $_order = array();


	/**
	 * 发短信
	 *
	 * @return void
	 * @author 
	 **/
	public function send_sms($mobile)
	{
		if (!$mobile) return ;

		// 判断是否允许发送
		$parent = $this->parent;

		if (($this->msgsetting & $parent::ORDER_CREATE_SEND_SMS) == 0) return ;

		// 获取模板
		$defContent = "感谢果友的订购，您的单号：{$this->_order['order_name']}，使用{$this->_order['pay_name']}，订单金额：{$this->_order['money']}元。" . ($this->_order['pay_parent_id'] < 4 ? '请在半小时内支付，我们将尽快为您配送。' : '');

        get_instance()->load->model('sms_template');
        $sms_template = get_instance()->sms_template;

        $smsContent = $sms_template->getSmsTemplate($sms_template::_SMS_ORDER_CREATE,array('order_name'=>$this->_order['order_name'],'pay_name'=>$this->_order['pay_name'],'money'=>$this->_order['money']));

        $smsContent = $smsContent ? $smsContent : $defContent;

		get_instance()->load->model("jobs_model");
		get_instance()->jobs_model->add(array('mobile'=>$mobile,'text'=>$smsContent), "sms");
	}

	/**
	 * 发邮件
	 *
	 * @return void
	 * @author 
	 **/
	public function send_email($email)
	{
		if (!$email) return ;

		$parent = $this->parent;

		if (($this->msgsetting & $parent::ORDER_CREATE_SEND_EMAIL) == 0) return ;
		$base_url = defined('WWW_PIC_URL') ? WWW_PIC_URL : 'http://www.fruitday.com';

		$order_items = get_instance()->db->select('*')->from('order_product')->where('order_id',$this->_order['id'])->get()->result_array();

		$order_items_body = <<<HTML
		<table width="568" border="0">
		<tr>
		<td colspan="4"><img src="{$base_url}/assets/images/emailimg/img2.jpg" width="574" height="33" /></td>
		</tr>
		<tr>
		<td width="344" height="30" bgcolor="#ececec">商品名称</td>
		<td width="80" height="30" align="center" bgcolor="#ececec"> 规格 </td>
		<td width="67" align="center" bgcolor="#ececec">价格</td>
		<td width="69" align="center" bgcolor="#ececec">订购数量 </td>
		</tr>
HTML;
		foreach ($order_items as $value) {
			$flag = '';
			if ($value['type'] == '2' || $value['type'] == '3') {
				$flag = '[赠]';
			}

			if ($value['type'] == '4') {
				$flag = '[换]';
			}

			$order_items_body .= '<tr><td height="30">' . $flag . $value['product_name'] . '</td><td height="30" align="center">' . $value['gg_name'] . '</td><td align="center">￥' . $value['price'] . '元</td><td align="center">' . $value['qty'] . '</td></tr>';
		}
		$order_items_body .= '<tr><td height="30" colspan="4">-----------------------------------------------------------------------------------------------</td></tr></table>';

		$order_address = get_instance()->db->select('*')->from('order_address')->where('order_id',$this->_order['id'])->get()->row_array();
		if ($this->_order['uid']) {
			$user = get_instance()->db->select('uname,mobile')->from('user')->where('id',$this->_order['uid'])->get()->row_array();
			$uname = $user['uname'] ? $user['uname'] : $user['mobile'];
		}
		

		$shtime = is_numeric($this->_order['shtime']) ? date('Y-m-d',strtotime($this->_order['shtime'])) : '2~3天';

		$message = $this->_order['pay_status']=='0' ? '您已成功下单,请在半小时内支付，我们将尽快为您配送。' : '您已成功下单';
		$emailContent = <<<HTML
		<style type='text/css'>*{margin:0; padding:0;}
a{text-decoration:none; color:#333;}
.conten{width:640px; height:auto; overflow:hidden; background:#ececec; margin:0 auto; padding-top:10px; padding-bottom:30px;}
.hear{padding-left:30px; height:auto; overflow:hidden; margin-bottom:10px;}
.hear h1{float:left;}
.hear p{float:right; padding-right:40px; font-size:12px; padding-top:65px;}
.hear p a{margin:0 6px;}
.conentBox{ border:1px solid #ccc; width:580px; height:auto; padding:10px; clear:both; background:#fff; margin:0 auto;}
table{font-size:14px; font-family:"微软雅黑";}
.tdP{padding-left:10px;}
a,img{border:0;}</style>
<div class="conten">
	<div class="hear">
    	<h1>
	    	<a target='_blank' href="{$base_url}">
	    		<img src="{$base_url}/assets/images/emailimg/logo_green.png" width="90" height="84" />
	    	</a>
    	</h1>
        <p><a target='_blank' href="{$base_url}/home">我的果园</a>|<a target='_blank' href="{$base_url}/web/help">帮助</a></p>
    </div>
    <div class="conentBox">
	<table width="584" border="0">
	<tr>
	<td align="center" style="font-size:24px; color:#666; padding:10px 0;">天天果园订单通知 </td>
	</tr>
	<tr>
	<td style="color:#ccc">------------------------------------------------------------------------------------------------</td>
	</tr>
	<tr>
	<td align="left"><p style="line-height:26px;">尊敬的会员： <font style="color:#669934; font-weight:bold;">{$uname}</font>
	</p>
	<p>{$message} </p></td>
	</tr>
	<tr>
	<td align="left">&nbsp;</td>
	</tr>
	</table>
    <table width="584" border="0">
  <tr>
    <td colspan="2"><img src="{$base_url}/assets/images/emailimg/img1.jpg" width="574" height="33" /></td>
    </tr>
  <tr>
    <td width="71" height="30">订单号:</td>
    <td width="503" height="30">{$this->_order['order_name']}</td>
  </tr>
  <tr>
    <td height="30">订单总价:</td>
    <td height="30">￥{$this->_order['money']}元 </td>
  </tr>
  <tr>
    <td height="30">付款方式:</td>
    <td height="30">{$this->_order['pay_name']} </td>
  </tr>
  <tr>
    <td height="30">送货日期:</td>
    <td height="30">{$shtime}</td>
  </tr>
  <tr>
    <td height="30">贺卡信息: </td>
    <td height="30">{$this->_order['hk']}</td>
  </tr>
  <tr>
    <td height="30">特别备注: </td>
    <td height="30">{$this->_order['msg']}</td>
  </tr>
  <tr>
    <td colspan="2">------------------------------------------------------------------------------------------------</td>
    </tr>
</table>
{$order_items_body}
    <table width="584" border="0">
  <tr>
    <td colspan="2"><img src="{$base_url}/assets/images/emailimg/img3.jpg" width="574" height="33" /></td>
    </tr>
  <tr>
    <td width="90" height="30">送&nbsp;货&nbsp;地&nbsp;&nbsp;址: </td>
    <td width="484" height="30">{$order_address['address']}</td>
  </tr>
  <tr>
    <td height="30">收&nbsp;&nbsp;&nbsp;&nbsp;货&nbsp;&nbsp;&nbsp;人: </td>
    <td height="30">{$order_address['name']} </td>
  </tr>
  <tr>
    <td height="30">收货人电话: </td>
    <td height="30">{$order_address['telephone']}</td>
  </tr>
  <tr>
    <td height="30">收货人手机:</td>
    <td height="30">{$order_address['mobile']} </td>
  </tr>
  <tr>
    <td colspan="2">------------------------------------------------------------------------------------------------</td>
  </tr>
   <tr>
    <td height="27" colspan="2" align="center"><p>版权所有 © 2014天天果园 保留所有权利 | 沪ICP备12042163</p>
      </td>
  </tr>
  <tr>
    <td colspan="2" align="center"><img src="{$base_url}/assets/images/emailimg/img4.jpg" width="162" height="23" />
      </td>
  </tr>
</table>
</div>
</div>
HTML;

        get_instance()->load->model("jobs_model");
        get_instance()->jobs_model->add(array('email'=>$email,'text'=>$emailContent,'title'=>"天天果园订单通知"), "email");
	}

	/**
	 * 订单赋值
	 *
	 * @return void
	 * @author 
	 **/
	public function set_order($order_id)
	{
		$order = get_instance()->db->select('uid,pay_status,id,order_name,pay_name,pay_time,time,update_pay_time,pay_id,pay_parent_id,shtime,stime,money,hk,msg')
									->from('order')
									->where('id',$order_id)
									->get()
									->row_array();

		$this->_order = $order;

		return $this;
	}
}