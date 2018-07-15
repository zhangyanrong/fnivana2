<?php
/**
 /-------------------------------------
 / 市场投放渠道追踪码收集数据模型
 /-------------------------------------
 */
class User_channel_tracking_model extends MY_Model {

    /**
     * 获取表名
     */
    public function table_name() {
        return 'ttgy_user_channel_tracking';
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

        $code = strval($data['code']); //渠道追踪码
        $device_id = $data['device_id']; //接口约定设备号
        $visit_time = intval($data['visit_time']); //唤起APP的时间戳

        $table_name = $this->table_name();

        return $this->db->query("INSERT INTO `{$table_name}` (
          `code`,
          `device_id`,
          `visit_time`
        ) 
        VALUES
          ('{$code}', '{$device_id}', {$visit_time})");
    }

}