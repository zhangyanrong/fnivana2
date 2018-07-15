<?php
/**
 * 优惠方案
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: promotion.php 1 2014-08-14 08:40:08Z pax $
 * @link      http://www.fruitday.com
 **/
namespace bll\pool;

class Promotion 
{
    
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    /**
     * 获取优惠方案
     *
     * @return void
     * @author 
     **/
    public function getlist($filter = array())
    {
        $data = array();

        $data = array_merge($this->get_money_upto(),
                            $this->product_promotion(),
                            $this->pkg_promotion(),
                            $this->salerule_promotion()
                );

        return $data;
    }

    /**
     * 满百活动
     *
     * @return void
     * @author 
     **/
    public function get_money_upto()
    {
        $now = date("Y-m-d H:i:s");
        $rows = $this->ci->db->select('*')->from('money_upto')->where(array('start <='=>$now,'end >='=>$now,'type'=>'0'))->get()->result_array();

        if (!$rows) return array();

        $money_upto = array();
        foreach ($rows as $key => $row) {
            $money_upto[$key]['disType'] = '满百活动';
            $money_upto[$key]['disCode'] = $row['id'];
            $money_upto[$key]['info']    = $row['remarks'];
            $money_upto[$key]['area']    = '';
            $money_upto[$key]['begindate'] = $row['start'];
            $money_upto[$key]['enddate'] = $row['end'];

            if ($region_id = @unserialize($row['send_region'])) {
                $area = $this->ci->db->select('name')->from('area')->where_in('id',$region_id)->get()->result_array();
                $money_upto[$key]['area'] = implode('、',array_map('current', $area));
            }
        }

        $this->rpc_log = array('rpc_desc' => '优惠方案获取','obj_type'=>'promotion');

        return $money_upto;
    }

    /**
     * 双11活动
     *
     * @return void
     * @author 
     **/
    public function double11()
    {
    }

    /**
     * 购物卡赠品
     *
     * @return void
     * @author 
     **/
    public function gift_card_giveaway()
    {

    }

    /**
     * 单品促销
     *
     * @return void
     * @author 
     **/
    public function product_promotion()
    {
        $now = date("Y-m-d H:i:s");
        $rows = $this->ci->db->select('*')->from('pro_sales')->where(array('start <='=>$now,'end >='=>$now))->get()->result_array();

        if (!$rows) return array();

        $product_promotion = array();
        foreach ($rows as $key => $row) {
            $product_promotion[$key]['disType'] = '单品促销';
            $product_promotion[$key]['disCode'] = $row['id'];
            $product_promotion[$key]['info']    = $row['remarks'];
            $product_promotion[$key]['area']    = '';
            $product_promotion[$key]['begindate']    = $row['start'];
            $product_promotion[$key]['enddate']    = $row['end'];
        }

        return $product_promotion;
    }

    /**
     * 捆绑促销
     *
     * @return void
     * @author 
     **/
    public function pkg_promotion()
    {
        $now = date("Y-m-d H:i:s");
        $rows = $this->ci->db->select('*')->from('bind_sales')->where(array('start <='=>$now,'end >='=>$now))->get()->result_array();

        if (!$rows) return array();

        $pkg_promotion = array();
        foreach ($rows as $key => $row) {
            $pkg_promotion[$key]['disType'] = '捆绑促销';
            $pkg_promotion[$key]['disCode'] = $row['id'];
            $pkg_promotion[$key]['info']    = $row['remarks'];
            $pkg_promotion[$key]['area']    = '';
            $pkg_promotion[$key]['begindate']    = $row['start'];
            $pkg_promotion[$key]['enddate']    = $row['end'];
        }

        return $pkg_promotion;
    }

    /**
     * 满额拆
     *
     * @return void
     * @author 
     **/
    public function salerule_promotion()
    {
        $now = time();
        $rows = $this->ci->db->select('*')->from('sale_rules')->where(array('start_time <='=>$now,'end_time >='=>$now))->get()->result_array();

        if (!$rows) return array();

        $salerule_promotion = array();
        foreach ($rows as $key => $row) {
            $disType = '';
            if ($row['type'] == '1') $disType = '搭配购';
            if ($row['type'] == '2') $disType = '满额拆';

            $salerule_promotion[$key]['disType'] = $disType;
            $salerule_promotion[$key]['disCode'] = $row['id'];
            $salerule_promotion[$key]['info']    = $row['desc'];
            $salerule_promotion[$key]['area']    = '';
            $salerule_promotion[$key]['begindate']    = date('Y-m-d H:i:s',$row['start_time']);
            $salerule_promotion[$key]['enddate']    = date('Y-m-d H:i:s',$row['end_time']); 
        }

        return $salerule_promotion;
    }
}