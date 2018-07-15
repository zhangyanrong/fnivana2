<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/16
 * Time: 下午5:44
 */

class Apk_manager_model extends MY_Model {
    public function table_name() {
        return 'apk_manager';
    }

    /**
     * 获取api信息
     */
    public function get_info($package_name, $channel_id = null) {
        $this->db->select('*');
        $this->db->from($this->table_name());
        $where = array('package_name' => $package_name , 'channel_id' => $channel_id);
        $this->db->where($where);
        $result = $this->db->get()->row_array();
        return $result;
    }

}