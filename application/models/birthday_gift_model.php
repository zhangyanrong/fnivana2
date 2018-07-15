<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/2
 * Time: 上午11:22
 */

class Birthday_gift_model extends MY_Model {
    public function table_name() {
        return 'birthday_gift';
    }

    /**
     * 获取积分兑换列表
     */
    public function get_gift($month) {
        $this->db->select('*');
        $this->db->from('birthday_gift');
        $this->db->where(array('is_del' => 0,'month'=>$month));
        $result = $this->db->get()->result_array();
        return $result;
    }

    /**
     * 获取积分兑换详情
     */
    public function get_info($tag) {
        $this->db->select('*');
        $this->db->from($this->table_name());
        $this->db->where(array('is_del' => 0, 'tag' => $tag));
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