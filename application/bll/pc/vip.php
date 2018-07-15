<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 15/12/18
 * Time: 上午9:28
 */
namespace bll\pc;
include_once("pc.php");

class vip extends pc {

    function __construct($params = array()) {
        $this->ci = &get_instance();
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        if ($session_id || (isset($params['service']) && in_array($params['service'], $this->sess_allow))) {
            $this->ci->load->library('session', array('session_id' => $session_id));
        }

        $this->userLv = $params['level'];
        $this->regionId = $params['region_id'];
        $this->ci->load->model('vip_model');
        $this->ci->load->helper('public');
        $this->head_photopath = $this->head_photopath . date("Y-m-d");
    }

    function recommend($params) {
        $region_id = $this->get_region_id($this->regionId);
        $products = $this->ci->vip_model->getProducts();
        if(empty($products)){
            return array();
        }
        foreach ($products as $product) {
            $effectiveRegion = explode(',',$product['region']);
            if (in_array($region_id, $effectiveRegion)) {
                $productLv = explode(',', $product['user_lv_group']);
                $productGroup[array_shift($productLv)][] = $product['product_id'];
                $productId2Tag[$product['product_id']] = $product['tag'];
            }
        }
        ksort($productGroup);
        $productIdsSortByLv = array();
        foreach ($productGroup as $productIdGroup) {
            $productIdsSortByLv = array_merge($productIdsSortByLv, $productIdGroup);
        }

        $this->ci->load->model('product_model');
        $result = $this->ci->product_model->getProductsById($productIdsSortByLv);
        //根据lv配置排序
        $sortResult = array();
        foreach ($result as &$product) {
            $product['tag'] = $productId2Tag[$product['id']];
            foreach ($productIdsSortByLv as $k => $productId) {
                if ($product['id'] == $productId) {
                    $sortResult[$k] = $product;
                }
            }
        }
        ksort($sortResult);
        return $sortResult;

    }

    /*
	*获取地区标识
	*/
    private function get_region_id($region_id = 106092) {
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if (isset($site_list[$region_id])) {
            $region_result = $site_list[$region_id];
        } else {
            $region_result = 106092;
        }
        return $region_result;
    }
}