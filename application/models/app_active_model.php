<?php
/**
 * Created by PhpStorm.
 * User: chenzhicheng
 * Date: 16/7/6
 * Time: 上午10:51
 */

class App_active_model extends MY_Model {
    public function table_name(){
        return 'app_active';
    }

    /**
     * 获取广告图片 －app启动
     */
    public function get_list()
    {
        $this->db->select('id,url');
        $this->db->from('app_active');
        $result = $this->db->get()->result_array();
        return $result;
    }
}