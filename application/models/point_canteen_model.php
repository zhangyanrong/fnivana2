<?php

/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/7/27
 * Time: 下午5:15
 */
class Point_canteen_model extends MY_Model {
    public function table_name() {
        return 'point_canteen';
    }

    /**
     * 获取积分兑换列表
     */
    public function get_list($user_rank) {
        $now = date('Y-m-d H:i:s', time());

        $this->db->select('*');
        $this->db->from($this->table_name());
        $this->db->where(array('is_del' => 0, 'user_rank' => $user_rank, 'begin_time <=' => $now, 'end_time >= ' => $now));
        $this->db->order_by('order');
        $result = $this->db->get()->result_array();
        return $result;
    }

    /**
     * 获取积分兑换详情
     */
    public function get_info($id) {
        $this->db->select('*');
        $this->db->from($this->table_name());
        $this->db->where(array('is_del' => 0, 'id' => $id));
        $result = $this->db->get()->row_array();
        return $result;
    }

    /**
     * 获取优惠券
     */
    public function checkCardNum($card_number) {
        $this->db->from('card');
        $this->db->where('card_number', $card_number);
        $query = $this->db->get();
        $num = $query->num_rows();
        if ($num > 0) {
            return true;
        } else {
            return false;
        }
    }
}