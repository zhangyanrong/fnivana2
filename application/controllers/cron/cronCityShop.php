<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CronCityShop extends CI_Controller {
    function __construct(){
        parent::__construct ();
        $this->load->helper('public');
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    public function getPushCityShopProducts(){
        if (php_sapi_name() !== 'cli') return ;
        $this->load->model('cityshop_product_model');
        $products = $this->cityshop_product_model->getCityShopSaleTTGYProducts();
        if(empty($products)) return;
        $add_data = array();
        foreach ($products as $key => $value) {
            if(empty($value['inner_code']) || empty($value['store_code'])) continue;
            $data_one = array();
            $data_one['store_code'] = $value['store_code'];
            $data_one['sap_code'] = $value['inner_code'];
            $add_data[] = $data_one;
        }
        if(empty($add_data)) return;
        $this->cityshop_product_model->addCityShopSaleTTGYProducts($add_data);
    }
}
