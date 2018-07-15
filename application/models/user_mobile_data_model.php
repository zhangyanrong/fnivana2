<?php
/**
 /-------------------------------------
 / 用户手机数据模型
 /-------------------------------------
 */
class User_mobile_data_model extends MY_Model {

    /**
     * 获取表名
     */
    public function table_name() {
        return 'ttgy_user_mobile_data';
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
        $operating_system = mysql_escape_string(strtolower($data['operating_system'])); //手机操作系统
        $version_number = mysql_escape_string($data['version_number']); //操作系统版本号
        $device_number = mysql_escape_string($data['device_number']); //IMEI,IDFA,MEID
        $device_code = strtolower(str_replace('-', '', $device_number)); //IMEI,IDFA,MEID,替换横杆再转小写
        $android_id = mysql_escape_string($data['android_id']); //安卓系统的Android Id
        $android_id_lower = strtolower($android_id); //安卓系统的Android Id,转为小写
        $channel_code = mysql_escape_string(strtolower($data['channel_code'])); //广告渠道标识,统一转小写入库
        $activation_time = intval($data['activation_time']); //首次打开APP的时间戳
        $device_id = $data['device_id']; //接口约定设备号
        $registration_id = $data['registration_id']; //jpush推送设备id
        $ip_address = $data['ip_address']; //外网IP
        $user_agent = $data['user_agent']; //浏览器User-Agent
        $ip_ua = $data['ip_ua']; //MD5(ip+ua),用于inmobi第三方数据匹配

        $table_name = $this->table_name();
        if ($device_code) {
            $device_code_total = $this->db->query("SELECT COUNT(1) AS total FROM `{$table_name}` WHERE device_code = '{$device_code}' LIMIT 1")->row()->total;
            if ($device_code_total){ 
              if(!empty($device_id) && !empty($registration_id)){
                $this->db->query("update `{$table_name}` set device_id='{$device_id}',registration_id='{$registration_id}' where device_code = '{$device_code}'");
              }
              return false;
            }
        }
        if ($android_id_lower) {
            $android_id_lower_total = $this->db->query("SELECT COUNT(1) AS total FROM `{$table_name}` WHERE android_id_lower = '{$android_id_lower}' LIMIT 1")->row()->total;
            if ($android_id_lower_total) {
              if(!empty($device_id) && !empty($registration_id)){
                $this->db->query("update `{$table_name}` set device_id='{$device_id}',registration_id='{$registration_id}' where android_id_lower = '{$android_id_lower}'");
              }
              return false;
            }
        }
        
        return $this->db->query("INSERT INTO `{$table_name}` (
          `operating_system`,
          `version_number`,
          `device_number`,
          `device_code`,
          `android_id`,
          `android_id_lower`,
          `channel_code`,
          `activation_time`,
          `device_id`,
          `registration_id`,
          `ip_address`,
          `user_agent`,
          `ip_ua`
        ) 
        VALUES
          ('{$operating_system}', '{$version_number}', '{$device_number}', '{$device_code}', '{$android_id}', '{$android_id_lower}', '{$channel_code}', {$activation_time},'{$device_id}','{$registration_id}', '{$ip_address}','{$user_agent}','{$ip_ua}')");
    }
    
}