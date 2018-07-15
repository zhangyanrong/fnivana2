<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/16
 * Time: 下午5:40
 */

class Apk_channel_model extends MY_Model {
    public function table_name() {
        return 'apk_channel';
    }

    /**
     * 获取apk渠道详情
     */
    public function get_info($id) {
        $this->db->select('*');
        $this->db->from($this->table_name());
        $this->db->where(array('id' => $id));
        $result = $this->db->get()->row_array();
        return $result;
    }

    public function get_info_by_name($channel) {
        $this->db->select('*');
        $this->db->from($this->table_name());
        $this->db->where(array('channel' => $channel));
        $result = $this->db->get()->row_array();
        return $result;
    }

}