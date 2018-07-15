<?php

/**
 * 功能：用户APP激活渠道收集
 *
 * @author luke_lu
 */
class User_activation_model extends MY_Model {

    /**
     * 获取表名
     */
    public function table_name() {
        return 'ttgy_user_activation';
    }

    /**
     * 单条数据添加
     */
    function add($data) {
        /* 空数组，则不处理 */
        $temp = array_filter($data);
        if (!$temp) {
            return false;
        }
        $operating_system = strtolower($data['operating_system']); //手机操作系统
        $device_number = $data['device_number']; //IMEI,IDFA,MEID
        $android_id = $data['android_id']; //安卓系统的Android Id
        $device_id = $data['device_id']; //接口约定设备号
        $channel_code = strtolower($data['channel_code']); //广告渠道标识,统一转小写入库
        $activation_time = intval($data['activation_time']); //首次打开APP的时间戳

        $table_name = $this->table_name();
        
        return $this->db->query("INSERT IGNORE INTO `{$table_name}` (
          `operating_system`,
          `device_number`,
          `android_id`,
          `device_id`,
          `channel_code`,
          `activation_time`
        ) 
        VALUES
          ('{$operating_system}', '{$device_number}', '{$android_id}', '{$device_id}', '{$channel_code}', {$activation_time})");
    }
    
}