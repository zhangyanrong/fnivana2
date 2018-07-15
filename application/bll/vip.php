<?php

namespace bll;

/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 15/12/17
 * Time: 上午9:31
 */
class vip {

    function __construct($params = array()) {
        $this->ci = &get_instance();
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        if ($session_id || (isset($params['service']) && in_array($params['service'], $this->sess_allow))) {
            $this->ci->load->library('session', array('session_id' => $session_id));
        }
        $this->ci->load->model('vip_model');
        $this->ci->load->helper('public');
        $this->head_photopath = $this->head_photopath . date("Y-m-d");
    }

    function recommend($params) {
//        $level = $params['level'];
//        $productIds = $this->ci->vip_model->getProductsByLevel($level);
//
//        $this->ci->load->model('product_model');
//        $result = $this->ci->product_model->getProductsById($productIds);
//        return $result;

    }

    /**
     * 获取会员中心配置的特惠商品列表
     */
    function getSaleProduct($params) {
        $require_fields = array(
            'region_id'=>array('required'=>array('code'=>'500','msg'=>'region id can not be null'))
        );
        if($alert_msg = check_required($params, $require_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }

        $this->ci->load->model('product_model');

        $region = $this->get_region_id($params['region_id']);
        $saleProductGroup = $this->ci->vip_model->getSaleProducts();
        if(empty($saleProductGroup)){
            return array('code'=>200, 'msg'=>array());
        }
        $productGroup = array();
        foreach ($saleProductGroup as $saleProduct) {
            $saleProductGroup = explode(',', $saleProduct['sale_product']);
            if (in_array($region, explode(',', $saleProduct['region']))) {
                $productGroup = array_merge($productGroup, $saleProductGroup);
            }
        }
        $productInfoGroup = $this->ci->product_model->getProductsById($productGroup);
        $sortProductGroup = array();
        foreach($productGroup as $k=>$pid){
            foreach($productInfoGroup as $product){
                if($product['id'] == $pid){
                    $sortProductGroup[$k] = $product;
                }
            }
        }
        return array('code'=>200, 'msg'=>$sortProductGroup);
    }

    /*
    *获取地区标识
    */
    private function get_region_id($region_id=106092){
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if(isset($site_list[$region_id])){
            $region_result = $site_list[$region_id];
        }else{
            $region_result = 106092;
        }
        return $region_result;
    }

}