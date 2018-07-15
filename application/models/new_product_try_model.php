<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/1
 * Time: 下午5:11
 */
class New_product_try_model extends MY_Model {
    public function table_name() {
        return 'new_product_try';
    }

    /**
     * 获取积分兑换列表
     */
    public function get_list($userRank) {
        $now = date('Y-m-d H:i:s', time());
        $this->db->select('*');
        $this->db->from($this->table_name());
        $this->db->where(array('is_del' => 0,'user_rank'=>$userRank, 'begin_time <=' => $now, 'end_time >= ' => $now));
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