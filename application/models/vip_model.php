<?php

/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 15/12/17
 * Time: 上午9:56
 */
class Vip_model extends MY_model {

    function __construct() {
        parent::__construct();
        $this->load->helper('public');
    }

    /**
     * @author liangsijun
     * 根据vip等级获取对应推荐商品列表
     *
     * @return mixed
     */
    public function getProducts() {
        date_default_timezone_set(PRC);
        $date = date('Y-m-d H:i:s', time());
        $this->db->select('product_id,user_lv_group,tag,region,picture');
        $this->db->from('vip_product_rank_limit');
        $this->db->where(array('is_del' => 0, 'begin_date < ' => $date, 'end_date >' => $date));
        $result = $this->db->get()->result_array();
        return $result;
    }

    /**
     * @author liangsijun
     * 获取会员中心配置的特惠商品
     *
     * @return mixed
     */
    public function getSaleProducts() {
        $this->db->select('sale_product,region');
        $this->db->from('ttgy_user_center');
        $this->db->where(array('is_del' => 0, 'is_on' => 1));
        $result = $this->db->get()->result_array();
        return $result;
    }

}