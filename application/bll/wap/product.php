<?php
namespace bll\wap;
include_once("wap.php");
/**
* 商品相关接口
*/
class Product extends wap{

	function __construct(){
		$this->ci = &get_instance();
        $this->ci->load->model('product_model');
        $this->ci->load->helper('public');
    }
	
	/*
	 * 获取分类
	 */

	function getCatList($params) {
		$params['version'] = '2.2.0';
		$result = parent::call_bll($params);
		return $result;
	}

    /*
	 * 商品列表获取
	 */

    function productList($params) {
        $required_fields = array(
            'ids' => array('required' => array('code' => '300', 'msg' => '请输入商品id')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $product_id_arr = explode(',', $params['ids']);
        $sort = $params['sort'] ? $params['sort'] : 0;
        $region_id = $this->get_region_id($params['region_id']);
        $page_size = $params['page_size'] ? $params['page_size'] : 10;
        $curr_page = $params['curr_page'] ? $params['curr_page'] : 0;
        $show_tuan = $params['show_tuan'] ? true : false;

        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        $channel = $params['channel'] ? $params['channel'] : 'portal';
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['ids'] . "_" . $sort . "_" . $region_id . "_" . $page_size . "_" . $curr_page . "_" . $channel;
            $product_arr = $this->ci->memcached->get($mem_key);
            if ($product_arr) {
                return $product_arr;
            }
        }

        //仓储设置
        $product_arr = $this->ci->product_model->get_products(array("product_id_arr" => $product_id_arr, "sort" => $sort, "region" => $region_id, "page_size" => $page_size, "curr_page" => $curr_page, "source" => $params['source'], "channel" => $channel,"cang_id"=>$params['cang_id'], "show_tuan" => $show_tuan));

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['ids'] . "_" . $sort . "_" . $region_id . "_" . $page_size . "_" . $curr_page . "_" . $channel;
            $this->ci->memcached->set($mem_key, $product_arr, 1800);
        }
        return $product_arr;
    }


    /*
	 * 获取地区标识
	 */
    private function get_region_id($region_id = 106092) {
        $region_id = empty($region_id) ? "" : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if (isset($site_list[$region_id])) {
            $region_result = $site_list[$region_id];
        } else {
            $region_result = '';
        }
        return $region_result;
    }
}