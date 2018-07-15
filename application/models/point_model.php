<?php
class Point_model extends MY_Model {
	public function table_name(){
		return 'bonus_point';
	}

    /**
     * 获取积分兑换列表
     */
    public function get_list()
    {
        $this->db->select('id,type,tag,bonus_point,region,picture,order');
        $this->db->from('bonus_point');
        $this->db->where(array('is_del'=>0));
        $result = $this->db->get()->result_array();
        return $result;
    }


    /**
     * 获取优惠券信息
     */
    public function get_card_info($tag)
    {
        $this->db->select('id,card_money,remarks,time,to_date,card_to_date,order_money_limit,restr_good,card_desc,can_sales_more_times,channel,direction,product_id');
        $this->db->from('mobile_card');
        $this->db->where(array('card_tag' => $tag));
        $result = $this->db->get()->row_array();

        return $result;
    }


    /**
     * 获取积分兑换详情
     */
    public function get_info($tag)
    {
        $this->db->select('id,type,tag,bonus_point,region,picture,limit');
        $this->db->from('bonus_point');
        $this->db->where(array('is_del'=>0,'tag'=>$tag));
        $this->db->order_by('order');
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