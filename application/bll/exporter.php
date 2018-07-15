<?php
/**
 * 导出卡券详细信息相关的接口。
 */

namespace bll;

class Exporter
{
    const SMS_CACHE_TIME = 1800;
    const SMS_TIME_LIMIT = 3;
    const SMS_PREFIX = 'exporter_sms_';

    const REDIS_KEY_SMS = 'api:exporter:sms';
    const SMS_COUNTER_PREFIX = 'exporter_sms_';
    const SESSION_EXPIRE = 1800;
    const SESSION_PREFFIX = 'exporter_session_';

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * 发送短信。
     */
    public function sendSMS($aParams)
    {
        $mobile = $aParams['mobile'];
        $code = $aParams['code'];
        $msg = '您获取卡券信息所需的短信验证码是：' . $code;

        $redis = $this->getRedis();
        $record = (int)$redis->get(self::SMS_COUNTER_PREFIX . $mobile);

        if ($record >= self::SMS_TIME_LIMIT) {
            return ['code' => 301, 'msg' => 'Too frequent.'];
        }

        $return = $this->sendMessage($msg, $mobile);

        // 设置计数器
        $redis->incr(self::SMS_COUNTER_PREFIX . $mobile);
        $redis->setTimeout(self::SMS_COUNTER_PREFIX . $mobile, self::SMS_CACHE_TIME);

        // 设置验证码与手机号的关系。
        $redis->set(self::SMS_PREFIX . $code, $mobile);
        $redis->setTimeout(self::SMS_PREFIX . $code, self::SMS_CACHE_TIME);

        return json_decode($return, true);
    }

    /**
     * 检查是否处于登录状态。
     */
    public function checkLogin($aParams)
    {
        $code = $aParams['code'];

        $redis = $this->getRedis();
        $result = $redis->get(self::SESSION_PREFFIX . $code);

        if (empty($result)) {
            return ['code' => 301, 'msg' => 'failed'];
        } else {
            return ['code' => 200, 'msg' => $result];
        }
    }

    /**
     * 登录操作。
     */
    public function login($aParams)
    {
        $identcode = $aParams['identcode'];
        $mobile = $aParams['mobile'];
        $ticket = $aParams['ticket'];

        if (empty($mobile)) {
            return [
                'code' => 301,
                'msg' => '手机号不能为空'
            ];
        }

        $redis = $this->getRedis();
        $value = $redis->get(self::SMS_PREFIX . $identcode);

        if ($value != $mobile) {
            return [
                'code' => 302,
                'msg' => '短信验证码错误'
            ];
        }

        $redis->set(self::SESSION_PREFFIX . $identcode, $ticket);
        $redis->setTimeout($code, self::SESSION_EXPIRE);

        return ['code' => 200, 'msg' => 'success', 'expire' => self::SESSION_EXPIRE];
    }

    /**
     * 调用短信服务。
     */
    private function sendMessage($smsText, $mobile)
    {
        $this->ci->load->library("notifyv1");

        $params = [
            "mobile" => $mobile,
            "message" => $smsText
        ];

        $result = $this->ci->notifyv1->send('sms', 'send', $params);
        return $result;
    }

    /**
     * 获得redis实例。
     */
    private function getRedis()
    {
        $this->ci->load->library('phpredis');
        return $this->ci->phpredis->getConn();
    }
}

# end of this file.
