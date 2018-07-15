<?php
/**
 * 短信模板类
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   e_manage
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: sms_template.php 1 2014-07-07 14:28:06Z pax $
 * @link      http://www.fruitday.com
 **/
class Sms_template extends CI_model
{
    // 订单提交
    const _SMS_ORDER_CREATE = '1';

    // 订单支付成功
    const _SMS_ORDER_PAY_SUCC = '2';

    // 订单超时支付取消
    const _SMS_ORDER_PAY_TIMEOUT = '3';

    // 赠品过期提醒
    const _SMS_GIFT_EXPIRE = '4';

    // 手机验证码发送
    const _SMS_MOBILE_SECURITY_CODE = '5';

    // 推荐有礼下单成功
    const _SMS_RECOMMEND_TO_ORDERCREATE = '6';

    // 会员等级变更
    const _SMS_MEMBER_LEVEL = '7';

    /**
     * 获取启用中的短信模板
     *
     * @param Int $type 模板类型
     * @return String
     * @author 
     **/
    public function getSmsTemplateEnable($type)
    {
        $template = $this->db->select('content')->from('sms_template')->where(array('type'=>$type,'enable' => '1'))->get()->row_array();

        return $template['content'];
    }

    /**
     * 获取短信模板
     *
     * @param String $type 短信类型
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    public function getSmsTemplate($type,$params = array())
    {
        $smsTmpl = $this->getSmsTemplateEnable($type);

        $smsContent = call_user_func_array(array($this,'_format_smscontent'.$type), array($smsTmpl,$params));

        return $smsContent;
    }

    /**
     * 订单提交成功-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent1($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$order_name>','<$pay_name>','<$money>'), array($params['order_name'], $params['pay_name'],$params['money']), $smsTmpl);

        return $smsContent;
    }

    /**
     * 订单支付成功-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent2($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$order_name>'), array($params['order_name']), $smsTmpl);

        return $smsContent;
    }

    /**
     * 订单超时支付取消-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent3($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$order_name>'), array($params['order_name']), $smsTmpl);

        return $smsContent;
    }

    /**
     * 赠品过期提醒-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent4($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$gift_name>'), array($params['gift_name']), $smsTmpl);

        return $smsContent;
    }

    /**
     * 手机验证码发送-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent5($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$security_code>'), array($params['security_code']), $smsTmpl);

        return $smsContent;
    }

    /**
     * 推荐有礼下单成功-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent6($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$member_name>','<$score>'), array($params['member_name'], $params['score']), $smsTmpl);

        return $smsContent;
    }

    /**
     * 会员等级变更-短信模板
     *
     * @param String $smsTmpl 短信模板
     * @param Array $params 参数
     * @return void
     * @author 
     **/
    private function _format_smscontent7($smsTmpl,$params)
    {
        $smsContent = str_replace(array('<$amount>'), array($params['amount']), $smsTmpl);

        return $smsContent;
    }
}