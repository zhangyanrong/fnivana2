<?php
namespace bll\wap;

/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/2/24
 * Time: 下午3:00
 */
class vip {

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
        if (empty($products)) {
            return array();
        }
        foreach ($products as $product) {
            $effectiveRegion = explode(',', $product['region']);
            if (in_array($region_id, $effectiveRegion)) {
                $productLv = explode(',', $product['user_lv_group']);
                $productGroup[array_shift($productLv)][] = $product['product_id'];
                $productId2Tag[$product['product_id']] = $product['tag'];
                $productId2Pic[$product['product_id']] = constant(CDN_URL.rand(1, 9)).$product['picture'];
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
            $product['pic'] = $productId2Pic[$product['id']];
            foreach ($productIdsSortByLv as $k => $productId) {
                if ($product['id'] == $productId) {
                    $sortResult[$k] = $product;
                }
            }
        }
        ksort($sortResult);
        return $sortResult;
    }

    /**
     * 获取会员中心配置的特惠商品列表
     */
    function getSaleProduct($params) {
        $require_fields = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null'))
        );
        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model('product_model');

        $region = $this->get_region_id($params['region_id']);
        $saleProductGroup = $this->ci->vip_model->getSaleProducts();
        if (empty($saleProductGroup)) {
            return array('code' => 200, 'msg' => array());
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
        foreach ($productGroup as $k => $pid) {
            foreach ($productInfoGroup as $product) {
                if ($product['id'] == $pid) {
                    $sortProductGroup[$k] = $product;
                }
            }
        }
        return array('code' => 200, 'msg' => $sortProductGroup);
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