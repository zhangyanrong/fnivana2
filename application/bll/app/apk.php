<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/30
 * Time: 下午2:38
 */

namespace bll\app;
/**
 * app接口基类
 */
class Apk {
    public static $bll_obj;

    function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->model('apk_channel_model');
        $this->ci->load->model('apk_manager_model');
        $this->ci->load->helper('public');
    }


    public function getApkInfo($params) {
        // 检查参数

        $required = array(
            'package_name' => array('required' => array('code' => '500', 'msg' => 'package name can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        if (empty($params['channel_name'])) {
            $params['channel_name'] = 'portal';
        }

        $channelInfo = $this->ci->apk_channel_model->get_info_by_name($params['channel_name']);
        $portalInfo = $this->ci->apk_channel_model->get_info_by_name('portal');

        $channelId = $channelInfo['id'];
        $portalChannelId = $portalInfo['id'];
        $apkInfo = $this->ci->apk_manager_model->get_info($params['package_name'], $channelId);
        if (empty($apkInfo)) {
            $apkInfo = $this->ci->apk_manager_model->get_info($params['package_name'], $portalChannelId);
        }

        $return = array('version_code' => $apkInfo['version_code'],
            'version' => $apkInfo['version'],
            'change_log' => $apkInfo['change_log'],
            'is_youmeng' => $apkInfo['is_youmeng'],
            'apk_url' => 'http://cdnws.fruitday.com/apk/' . $apkInfo['download_key'],
            'apk_md5' => $apkInfo['apk_md5']
        );

        return array('code' => 200, 'msg' => '', 'data' => $return);
    }

}