<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/2
 * Time: 上午11:36
 */

/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/8/1
 * Time: 下午5:11
 */
class For_you_save_model extends MY_Model {
    public function table_name() {
        return 'for_you_save';
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
     * 获取优惠券信息
     */
    public function get_card_info($tag) {
        $this->db->select('id,card_money,remarks,time,to_date,card_to_date,order_money_limit,restr_good,card_desc,can_sales_more_times,channel,direction,product_id');
        $this->db->from('mobile_card');
        $this->db->where(array('card_tag' => $tag));
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