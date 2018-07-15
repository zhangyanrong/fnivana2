<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/16
 * Time: 下午5:35
 */

namespace bll;

/**
 * 文章相关接口
 */
class Apk {
    public function __construct() {
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

        if (!empty($params['channel_name'])) {
            $channelInfo = $this->ci->apk_channel_model->get_info_by_name($params['channel_name']);
            $channelId = $channelInfo['id'];
        }

        $apkInfo = $this->ci->apk_manager_model->get_info($params['package_name'], $channelId);

        $return = array('version_code' => $apkInfo['version_code'],
            'version' => $apkInfo['version'],
            'change_log' => $apkInfo['change_log'],
            'apk_url' => ''
        );

        return array('code' => 200, 'msg' => '', 'data' => $return);
    }
}