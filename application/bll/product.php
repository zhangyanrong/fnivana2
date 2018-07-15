<?php

namespace bll;

/**
 * 商品相关接口
 */
class Product {

    function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->model('product_model');
        $this->ci->load->model('banner_model');
        $this->ci->load->model('pro_commision_model');
        $this->ci->load->model('b2o_store_product_model');
        $this->ci->load->helper('public');
    }

    /**
     * @api              {post} / 搜索商品
     * @apiDescription   商品搜索
     * @apiGroup         product
     * @apiName          search
     *
     * @apiParam {String} [keyword] 搜索关键字
     * @apiParam {String} [store_id_list] 门店ID列表
     * @apiParam {int} [tms_region_type] 区域类型
     * @apiParam {String} [page_size] 分页行数
     * @apiParam {String} [curr_page] 分页页数
     * @apiParam {String} [channel] 渠道
     *
     * @apiSampleRequest /api/test?service=product.search&source=app
     */
    public function search($params) {
        $required_fields = array(
            'keyword' => array('required' => array('code' => '300', 'msg' => '请输入搜索内容')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $keyword = $this->ci->security->xss_clean($params['keyword']);
        $send_region_type = intval($params['tms_region_type']);
        $productList = array();
        $store_id_list = $params['store_id_list'] ? explode(',', $params['store_id_list']) : array(1);//todo
        $limit = $params['page_size'] ? $params['page_size'] : 10;
        $page = $params['curr_page'] ? $params['curr_page'] : 1;
        $offset = $limit * ($page - 1);
        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        $channel = $params['channel'] ? $params['channel'] : 'portal';
        if ($channel != 'portal') {
            $channel = array($channel, 'portal');
        }
        switch ($params['source']) {
            case 'app':
                $online_key = 'is_app_online';
                break;
            case 'pc':
                $online_key = 'is_pc_online';
                break;
            case 'wap':
                $online_key = 'is_wap_online';
                break;
            default:
                $online_key = 'is_app_online';
                break;
        }
        $return = array();
        if (defined('OPEN_SOLR') && OPEN_SOLR === TRUE && defined('OPEN_SOLR_PRODUCT') && OPEN_SOLR_PRODUCT === TRUE) {
            $check_front = $this->ci->b2o_store_product_model->checkFrontBin($store_id_list);
            $solr_params['path'] = 'solr/solr_product_new';
            $solr_params['disMax'] = true;
            $solr_params['EdisMax'] = false;
            $this->ci->load->library('solr', $solr_params);
            $this->ci->solr->setTerms(true);
            $this->ci->solr->addField('*,score');
            // if(mb_strlen($keyword,'utf8') == 1){
            //     $keyword = "*".$keyword."*";
            // }
            $this->ci->solr->setQuery($keyword);
            $this->ci->solr->addQueryField('product_name', 1000);
            $this->ci->solr->addQueryField('tag', 100);
            $this->ci->solr->addQueryField('promotion_tag', 10);
            $this->ci->solr->addFilterQuery($online_key, 1);
            $this->ci->solr->addFilterQuery('is_hide', 0);
            $this->ci->solr->addFilterQuery('store_id', $store_id_list, true);
            $this->ci->solr->addFilterQuery('is_delete', 0);
            $this->ci->solr->addFilterQuery('send_region_type','['.$send_region_type.' TO *]');
            $this->ci->solr->addFilterQuery('channel', $channel, true);
            if($check_front === true){
                $this->ci->solr->addFilterQuery('is_store_sell', 1);
            }
            $this->ci->solr->setGroup(true);
            $this->ci->solr->addGroupField('product_id');
            $this->ci->solr->addGroupSortField('sort', 1);
            $this->ci->solr->setGroupMain();
            $this->ci->solr->limit($limit, $offset);
            $response = $this->ci->solr->query();
            $this->addLog((string)$this->ci->solr->solr_query);
            // error_log($this->ci->solr->solr_query,3,'E:\www\1.log');
            // error_log(print_r($response,true),3,'E:\www\1.log');
            $ids = array();
            if ($response && $response['docs']) {
                foreach ($response['docs'] as $key => $value) {
                    $ids[] = $value->id;
                }
                $productList = $this->ci->b2o_store_product_model->getProductListByIDs($ids);
            }
        } else {
            $productList = $this->ci->b2o_store_product_model->getSearchList($keyword, $store_id_list, $online_key, $send_region_type, $limit, $offset);
        }
        foreach ($productList as $key => &$value) {
            if($value['type'] == 1){
                $value['isTodayDeliver'] = 1;
            }else{
                $value['isTodayDeliver'] = 0;
            }
            $deliverType2word = array(1=>'当日达',2=>'次日达','3'=>'预售');
            $value['deliverType'] = $deliverType2word[$value['type']];
            unset($productList[$key]['type']);
        }
        $return['list'] = $productList;
        $return['classname'] = $keyword;
        return array('code'=>200,'data'=>$return);
    }

    /**
     * @api              {post} / 搜索关键字
     * @apiDescription   关键字补全
     * @apiGroup         product
     * @apiName          getKeyword
     *
     * @apiParam {String} [keyword] 搜索关键字
     *
     * @apiSampleRequest /api/test?service=product.getKeyword&source=app
     */
    public function getKeyword($params) {
        $required_fields = array(
            'keyword' => array('required' => array('code' => '300', 'msg' => '请输入搜索内容')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $keyword = $this->ci->security->xss_clean($params['keyword']);
        $return = array();
        if (defined('OPEN_SOLR') && OPEN_SOLR === TRUE && defined('OPEN_SOLR_PRODUCT') && OPEN_SOLR_PRODUCT === TRUE) {
            $solr_params['path'] = 'solr/solr_keywords';
            $solr_params['disMax'] = true;
            $solr_params['EdisMax'] = true;
            $this->ci->load->library('solr', $solr_params);
            $this->ci->solr->setTerms(true);
            $this->ci->solr->addField('id,keyword,type,score');
            $this->ci->solr->addBoostQuery('type', 1, 1);
            $this->ci->solr->addBoostQuery('type', 2, 3);
            $this->ci->solr->addBoostQuery('type', 3, 5);
            $this->ci->solr->setQuery('keyword', $keyword);
            $this->ci->solr->setGroup(true);
            $this->ci->solr->setGroupMain();
            $this->ci->solr->addGroupField('tt');
            $this->ci->solr->limit(10);
            $response = $this->ci->solr->query();
            // error_log($this->ci->solr->solr_query."\n",3,'E:\www\1.log');
            // error_log(print_r($response,true)."\n",3,'E:\www\1.log');
            if (!empty($response) && !empty($response['docs'])){
                foreach ($response['docs'] as $key => $value) {
                    $return[] = $value->keyword;
                }
            }
        }
        return array('code'=>200,'data'=>$return);
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
        $show_tuan = $params['show_tuan'];

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
        $product_arr = $this->ci->product_model->get_products(array("product_id_arr" => $product_id_arr, "sort" => $sort, "region" => $region_id, "page_size" => $page_size, "curr_page" => $curr_page, "source" => $params['source'], "channel" => $channel, "cang_id" => $params['cang_id'], "show_tuan" => $show_tuan));
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
     * 热门搜索关键字
     */

    function searchKey($params) {
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['region_id'];
            $searchKey = $this->ci->memcached->get($mem_key);
            if ($searchKey) {
                return $searchKey;
            }
        }
        $searchKey = $this->ci->product_model->get_search_key();
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['region_id'];
            $this->ci->memcached->set($mem_key, $searchKey, 1800);
        }
        return $searchKey;
    }

    /*
     * 商品详情
     */

    function productInfo($params) {
        $required_fields = array(
            'id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $id = $params['id'];
        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        $channel = (isset($params['channel']) && !empty($params['channel'])) ? $params['channel'] : 'portal';

        //转换对应关系
        $region_id = $this->get_region_id($params['region_id']);
        $params['region_id'] = $region_id;

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $params['region_id'] . "_" . $channel;
            $result = $this->ci->memcached->get($mem_key);
            if ($result) {
                return $result;
            }
        }
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $cang_id = $params['cang_id'] ? $params['cang_id'] : $region_to_warehouse[$params['region_id']];
        $result = $this->ci->product_model->get_product($id, $channel, $params['source'], $cang_id);

        // get product commision
        $result['commision'] = $this->ci->pro_commision_model->dump(['product_id' => $result['product']['id']])['commision'];

        //banner  start

        $this->ci->load->model('banner_model');
        $banner_list = $this->ci->banner_model->get_banner_list($params);
        $top_banner_list = array();
        $normal_banner_list = array();
        $total_banner = array();


        foreach ($banner_list as $key => &$value) {
            //判断版本
            if (strcmp($params['version'], '2.3.0') < 0 && $value['type'] == 12) {
                continue;
            }

            $is_top = $value['is_top'];
            unset($value['is_top']);

            if ($value['position'] == 1) {
                if ($is_top == 1) {
                    $top_banner_list[] = $value;
                } else {
                    $normal_banner_list[] = $value;
                }
            }
        }
        $total_banner = array_merge($top_banner_list, $normal_banner_list);
        $num = rand(0, (count($total_banner) - 1));
        $result['banner'] = $total_banner[$num];

        //特殊商品广告
//        if($params['id'] == '10616') {
//            $params['ids'] = 4273;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '10615') {
//            $params['ids'] = 4274;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '10617') {
//            $params['ids'] = 4275;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '10618') {
//            $params['ids'] = 4277;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '2424') {
//            $params['ids'] = 4276;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } else {
//            $result['banner'] = $total_banner[$num];
//        }

        //banner end

        //presell start
        $this->ci->load->model('presell_model');
        $presell = $this->ci->presell_model->get_list($id);
        $pre_info = array();
        if (count($presell) > 0) {
            foreach ($presell as $val) {
                if ($id == $val['product_id']) {
                    $pre_info = array(
                        'send_date' => $val['send_date'],
                        'count' => $val['ordercount'],
                    );
                }
            }

            if (!empty($pre_info)) {
                $result['is_presell'] = 1;
                $result['presell'] = $pre_info;
            }
        } else {
            $result['is_presell'] = 0;
        }
        //presell end

        // product promotion
        $this->ci->load->model('product_promotion_model');
        $result['promotion'] = $this->ci->product_promotion_model->getList('title, type, target_url, target_product_id', array('product_id' => $id));

        //仓储设置
        $is_enable_cang = $this->ci->config->item('is_enable_cang');
        if ($is_enable_cang == 1 && !empty($params['cang_id'])) {
            $cang_id = $params['cang_id'];
            $cang_arr = explode(',', $result['product']['cang_id']);

            if (!in_array($cang_id, $cang_arr)) {
                $result = array('code' => '300', 'msg' => '该地区不支持购买此商品');
            }
        }

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $params['region_id'] . "_" . $channel;
            $this->ci->memcached->set($mem_key, $result, 600);
        }
        return $result;
    }


    /*
	 * 商品详情 -- 赠品
	 */
    function giftInfo($params) {
        $required_fields = array(
            'id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $id = $params['id'];
        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        $channel = (isset($params['channel']) && !empty($params['channel'])) ? $params['channel'] : 'portal';

        //转换对应关系
        $region_id = $this->get_region_id($params['region_id']);
        $params['region_id'] = $region_id;

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $params['region_id'] . "_" . $channel;
            $result = $this->ci->memcached->get($mem_key);
            if ($result) {
                return $result;
            }
        }
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $cang_id = $params['cang_id'] ? $params['cang_id'] : $region_to_warehouse[$params['region_id']];
        $result = $this->ci->product_model->get_point_product($id, $channel, $params['source'], $cang_id);

        // get product commision
        $result['commision'] = $this->ci->pro_commision_model->dump(['product_id' => $result['product']['id']])['commision'];

        //banner  start

        $this->ci->load->model('banner_model');
        $banner_list = $this->ci->banner_model->get_banner_list($params);
        $top_banner_list = array();
        $normal_banner_list = array();
        $total_banner = array();


        foreach ($banner_list as $key => &$value) {
            //判断版本
            if (strcmp($params['version'], '2.3.0') < 0 && $value['type'] == 12) {
                continue;
            }

            $is_top = $value['is_top'];
            unset($value['is_top']);

            if ($value['position'] == 1) {
                if ($is_top == 1) {
                    $top_banner_list[] = $value;
                } else {
                    $normal_banner_list[] = $value;
                }
            }
        }
        $total_banner = array_merge($top_banner_list, $normal_banner_list);
        $num = rand(0, (count($total_banner) - 1));
        $result['banner'] = $total_banner[$num];

        //特殊商品广告
//        if($params['id'] == '10616') {
//            $params['ids'] = 4273;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '10615') {
//            $params['ids'] = 4274;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '10617') {
//            $params['ids'] = 4275;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '10618') {
//            $params['ids'] = 4277;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } elseif($params['id'] == '2424') {
//            $params['ids'] = 4276;
//            $bn = $this->ci->banner_model->get_banner_list($params);
//            $result['banner'] = $bn[0];
//        } else {
//            $result['banner'] = $total_banner[$num];
//        }

        //banner end

        //presell start
        $this->ci->load->model('presell_model');
        $presell = $this->ci->presell_model->get_list($id);
        $pre_info = array();
        if (count($presell) > 0) {
            foreach ($presell as $val) {
                if ($id == $val['product_id']) {
                    $pre_info = array(
                        'send_date' => $val['send_date'],
                        'count' => $val['ordercount'],
                    );
                }
            }

            if (!empty($pre_info)) {
                $result['is_presell'] = 1;
                $result['presell'] = $pre_info;
            }
        } else {
            $result['is_presell'] = 0;
        }
        //presell end

        // product promotion
        $this->ci->load->model('product_promotion_model');
        $result['promotion'] = $this->ci->product_promotion_model->getList('title, type, target_url, target_product_id', array('product_id' => $id));

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $params['region_id'] . "_" . $channel;
            $this->ci->memcached->set($mem_key, $result, 600);
        }
        return $result;
    }

    /**
     * 无论上下架
     */
    public function getPriceInfo($params) {
        $id = $params['id'];
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $cang_id = $params['cang_id'] ? $params['cang_id'] : $region_to_warehouse[$params['region_id']];
        $result = $this->ci->product_model->get_price_info($id, $cang_id);
        return $result;
    }

    /* 专题页面商品 */

    function pageListProducts($params) {
        $required_fields = array(
            'page_type' => array('required' => array('code' => '500', 'msg' => 'page type can not be null')),
            'target_id' => array('required' => array('code' => '500', 'msg' => 'target id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $page_type = $params['page_type'];
        $target_id = $params['target_id'];
        $product_id = $params['product_id'];
        $sort = $params['sort'] ? $params['sort'] : 0;
        $region_id = $this->get_region_id($params['region_id']);
        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        $channel = $params['channel'] ? $params['channel'] : 'portal';
        /* if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
          if(!$this->ci->memcached){
          $this->ci->load->library('memcached');
          }
          $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['page_type']."_".$target_id."_".$product_id."_".$sort."_".$region_id."_".$channel;
          $result = $this->ci->memcached->get($mem_key);
          if($result){
          return $result;
          }
          } */
        if ($page_type == '2' && $target_id == '64') {
            $page_type = 4;
        }
        $result = $this->ci->product_model->get_by_page($page_type, $target_id, $sort, $region_id, $params['source'], $channel, $product_id);
        /*
          if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
          if(!$this->ci->memcached){
          $this->ci->load->library('memcached');
          }
          $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['page_type']."_".$target_id."_".$product_id."_".$sort."_".$region_id."_".$channel;
          $this->ci->memcached->set($mem_key,$result,1800);
          } */
        return $result;
    }

    /*
     * 获取地区标识
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

    /**
     * @api              {post} / 评论比例
     * @apiDescription   评论比例
     * @apiGroup         product
     * @apiName          commentsRate
     *
     * @apiParam {Number} id 商品id
     *
     * @apiSampleRequest /api/test?service=product.commentsRate&source=app
     */
    function commentsRate($params) {
        $required_fields = array(
            'id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'];
            $result = $this->ci->memcached->get($mem_key);
            if ($result) {
                return array('code' => 200, 'msg' => '', 'data' => $result);
            }
        }
        $this->ci->load->model('comment_model');
        $pid = $params['id'];

        //根据template_id获取所有相关规格商品
        $templateId = $this->ci->product_model->getTemplateId($pid);
        if ($templateId == 0) {
            $pidGroup = array($pid);
        } else {
            $productGroup = $this->ci->product_model->getList('id', array('template_id' => $templateId));
            $pidGroup = array_column($productGroup, 'id');
        }

        $result = $this->ci->comment_model->commentsRate($pidGroup);
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'];
            $this->ci->memcached->set($mem_key, $result, 600);
        }
        return array('code' => 200, 'msg' => '', 'data' => $result);
    }

    /**
     * 商品评论
     */
    function comments($params) {
        $required_fields = array(
            'id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model('comment_model');
        $pid = $params['id'];
        $page_size = $params['page_size'] ? $params['page_size'] : 10;
        $curr_page = $params['curr_page'] ? $params['curr_page'] : 0;
        $type = empty($params['type']) ? 0 : $params['type'];
        $comment_type = empty($params['comment_type']) ? 0 : $params['comment_type'];

        $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $page_size . "_" . $type . "_" . $comment_type . "_" . $curr_page . '_' . $params['show'];

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $result = $this->ci->memcached->get($mem_key);
            if ($result) {
                return $result;
            }
        }

        //根据template_id获取所有相关规格商品
        $templateId = $this->ci->product_model->getTemplateId($pid);
        if ($templateId == 0) {
            $pidGroup = array($pid);
        } else {
            $productGroup = $this->ci->product_model->getList('id', array('template_id' => $templateId));
            $pidGroup = array_column($productGroup, 'id');
        }

        $result = $this->ci->comment_model->comments($pidGroup, $page_size, $curr_page, $type, $comment_type, $params['show']);
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $this->ci->memcached->set($mem_key, $result, 600);
        }
        return $result;
    }

    /*
     * 获取分类
     */

    function getCatList($params) {
        $this->ci->load->model('cat_model');
        $region_id = $this->get_region_id($params['region_id']);
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $region_id;
            $return = $this->ci->memcached->get($mem_key);
            if ($return) {
                return $return;
            }
        }
        $classes = $this->ci->cat_model->app_menu_array($region_id, $params['source'], $params['cang_id']);
        if (!empty($classes)) {
            if ($params['source'] != 'pc') {
                unset($classes['277']); // 不显示全部生鲜
            }

            if ($params['version'] <= '2.1.0') {
                foreach ($classes as $val) {
                    if ($val['parent_id'] == 0) {
                        if ($val['is_hot'] == 1) {
                            if (!empty($val['class_photo'])) {
                                $return['hot'][] = $val;
                            }
                        } else {
                            $return['common'][] = $val;
                        }
                    }
                }
            } else {
                $return = $this->getTree($return, $classes);
            }
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $region_id;
            $this->ci->memcached->set($mem_key, $return, 7200);
        }
        return $return;
    }

    private function getTree($return, $classes) {
        $data = array();
        $return = array('hot' => array(), 'common' => array());
        if (!$classes) {
            return NULL;
        }
        foreach ($classes as $k => $v) {
            $data[$v['step']][] = $v;
        }
        for ($i = count($data); $i > 0; $i--) {
            $step = $i;
            if (isset($data[$step])) {
                $sArr = $data[$step];
                foreach ($sArr as $val) {
                    if ($val['step'] == $step) {
                        if ($step > 1) {
                            foreach ($data[$step - 1] as $x => $y) {
                                if ($y['id'] == $val['parent_id']) {
                                    $data[$step - 1][$x]['sub_level'][] = $val;
                                }
                            }
                        }
                    }
                }
            }
        }
        $data = $data[1];
        foreach ($data as $value) {
            unset($value['step']);
            unset($value['parent_id']);
            if (isset($value['sub_level'])) {
                $newArr = array(
                    'id' => $value['id'],
                    'class_photo' => 'http://cdn.fruitday.com/sale/images/gengd.png',
                    'ename' => '',
                    'name' => '全部',
                    'is_hot' => '1',
                    'photo' => ''
                );
                $value['sub_level'][] = $newArr;
            } else {
                $value['sub_level'] = array();
            }
            if ($value['is_hot'] == 1) {
                if (!empty($value['class_photo'])) {
                    $return['hot'][] = $value;
                }
            } else {
                $return['common'][] = $value;
            }
        }
        return $return;
    }

    /*
     * 分类商品
     */

    function category($params) {
        $this->ci->load->model('cat_model');
        $required_fields = array(
            'class_id' => array('required' => array('code' => '500', 'msg' => 'category id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $class_id = $params['class_id'];
        $sort = $params['sort'] ? $params['sort'] : 0;
        $region_id = $this->get_region_id($params['region_id']);
        $page_size = $params['page_size'] ? $params['page_size'] : 10;
        $curr_page = $params['curr_page'] ? $params['curr_page'] : 0;
        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        $channel = $params['channel'] ? $params['channel'] : 'portal';
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $class_id . "_" . $sort . "_" . $region_id . "_" . $params['cang_id'] . "_" . $page_size . "_" . $curr_page . "_" . $channel;
            $return = $this->ci->memcached->get($mem_key);
            if ($return) {
                return $return;
            }
        }
        $return['classname'] = '水果';
        $return['list'] = $this->ci->product_model->get_by_category($class_id, $sort, $region_id, $page_size, $curr_page, $params['source'], $channel, $params['cang_id']);
        if ($curr_page == 0) {
            $return['classname'] = $this->ci->cat_model->selectClassName($class_id);
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $class_id . "_" . $sort . "_" . $region_id . "_" . $params['cang_id'] . "_" . $page_size . "_" . $curr_page . "_" . $channel;
            $this->ci->memcached->set($mem_key, $return, 1800);
        }
        return $return;
    }

    /*
     * 企业用户专享商品
     */

    function enterpriseProducts($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $this->ci->load->library('session', array('session_id' => $params['connect_id']));
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $sort = $params['sort'] ? $params['sort'] : 0;
        $region_id = $this->get_region_id($params['region_id']);

        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
        //     if(!$this->ci->memcached){
        //         $this->ci->load->library('memcached');
        //     }
        //     $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$uid."_".$sort."_".$region_id;
        //     $return = $this->ci->memcached->get($mem_key);
        //     if($return){
        //         return $return;
        //     }
        // }
        $return = $this->ci->product_model->get_enterprise_products($uid, $sort, $region_id, $params['source']);
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $cang_id = $region_to_warehouse[$params['region_id']];
        foreach ($return['products'] as $key => $val) {
            $return['products'][$key]['items'] = $this->ci->product_model->selectProductPrice('id as price_id, product_no, price, volume, stock', array(
                'product_id' => $val['product_id'],
                'sku_online' => 1,
            ), '', 'order_id', '', $cang_id);
        }

        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
        //     if(!$this->ci->memcached){
        //         $this->ci->load->library('memcached');
        //     }
        //     $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$uid."_".$sort."_".$region_id;
        //     $this->ci->memcached->set($mem_key,$return,1800);
        // }
        return $return;
    }

    /*
     * 商品关注接口
     */

    function mark($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'product_id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $this->ci->load->library('session', array('session_id' => $params['connect_id']));
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $product_id = $params['product_id'];

        $result = $this->ci->product_model->select_user_mark('id', array('uid' => $uid, 'product_id' => $product_id));
        if (!empty($result)) {
            return array('code' => '300', 'msg' => '您已经关注该商品');
        }

        $insert_data = array(
            'uid' => $uid,
            'product_id' => $product_id,
            'mark_time' => date("Y-m-d H:i:s"),
        );
        $insert_id = $this->ci->product_model->add_user_mark($insert_data);
        if ($insert_id) {
            return array('code' => '200', 'msg' => 'succ');
        } else {
            return array('code' => '500', 'msg' => '添加关注失败');
        }
    }

    /*
     * 取消关注
     */

    function cancelMark($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'product_id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $this->ci->load->library('session', array('session_id' => $params['connect_id']));
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $product_id = $params['product_id'];

        $result = $this->ci->product_model->select_user_mark('id', array('uid' => $uid, 'product_id' => $product_id));
        if (empty($result)) {
            return array('code' => '300', 'msg' => '您还没有关注该商品');
        }

        $this->ci->product_model->delete_user_mark(array('uid' => $uid, 'product_id' => $product_id));
        return array('code' => '200', 'msg' => 'succ');
    }

    /*
     * 商品关注状态
     */

    function markStatus($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'product_id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $this->ci->load->library('session', array('session_id' => $params['connect_id']));
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $product_id = $params['product_id'];

        $result = $this->ci->product_model->select_user_mark('id', array('uid' => $uid, 'product_id' => $product_id));
        if (empty($result)) {
            return array('code' => '200', 'msg' => 'false');
        } else {
            return array('code' => '200', 'msg' => 'true');
        }
    }

    /*
     * 关注商品列表
     */

    function markList($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $this->ci->load->library('session', array('session_id' => $params['connect_id']));
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end


        $result = $this->ci->product_model->select_user_mark('product_id,mark_time', array('uid' => $uid));
        if (empty($result)) {
            return array();
        }
        $product_id_arr = array();
        $product_id_tmp = array();
        foreach ($result as $key => $value) {
            $product_id_arr[] = $value['product_id'];
            $product_id_tmp[$value['product_id']] = $value['mark_time'];
        }

        $where_in[] = array(
            'key' => 'id',
            'value' => $product_id_arr
        );

        $product_result = $this->ci->product_model->selectProducts('id,product_name,thum_min_photo,tag_id,template_id', '', $where_in);
        foreach ($product_result as $key => $value) {
            // 获取产品模板图片
            if ($value['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $value['thum_min_photo'] = $templateImages['main']['small_thumb'];
                }
            }

            $product_result[$key]['photo'] = PIC_URL . $value['thum_min_photo'];
            unset($product_result[$key]['thum_min_photo']);
            if (isset($product_id_tmp[$value['id']])) {
                $product_result[$key]['mark_time'] = $product_id_tmp[$value['id']];
            }
        }

        return $product_result;
    }

    /*
     * 关注商品的相关商品
     */

    function markedProductsBak($params) {
        $required_fields = array(
            'tag_id' => array('required' => array('code' => '500', 'msg' => 'tag id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $sort = $params['sort'] ? $params['sort'] : 0;
        $region_id = $this->get_region_id($params['region_id']);
        $page_size = $params['page_size'] ? $params['page_size'] : 10;
        $curr_page = $params['curr_page'] ? $params['curr_page'] : 0;

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['tag_id'] . "_" . $sort . "_" . $region_id . "_" . $page_size . "_" . $curr_page;
            $return = $this->ci->memcached->get($mem_key);
            if ($return) {
                return $return;
            }
        }
        $return = $this->ci->product_model->get_marked_products($params['tag_id'], $sort, $region_id, $page_size, $curr_page);
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['tag_id'] . "_" . $sort . "_" . $region_id . "_" . $page_size . "_" . $curr_page;
            $this->ci->memcached->set($mem_key, $return, 1800);
        }
        return $return;
    }

    /*
     * 关联商品推荐
     */

    function recommend($params) {
        $return_count = 20;
        $required_fields = array(
            'id' => array('required' => array('code' => '500', 'msg' => '商品id不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $product_id = $params['id'];
        $region_id = $this->get_region_id($params['region_id']);
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $product_id . "_" . $region_id;
            $result = $this->ci->memcached->get($mem_key);
            if ($result) {
                return $result;
            }
        }

        $pro_tag_result = $this->ci->product_model->get_pro_active();
        $pro_tag_tmp = array();
        foreach ($pro_tag_result as $key => $value) {
            if (!empty($value['product_id'])) {
                $pro_tag_tmp[$value['type']] = explode(',', $value['product_id']);
            }
        }//后台设置的推荐商品

        $pro_rec_result = $this->ci->product_model->product_recommend($product_id);
        $pro_rec_arr = unserialize($pro_rec_result['product_tags']);
        //自动推荐的商品

        $recommend_result = array();
        // $recommend_result = $this->check_pro_recommend($pro_tag_tmp,'top_recommend');
        // $return_count = 9;
        $recommend_count = $return_count - count($recommend_result);
        $i = 1;
        if (!empty($pro_rec_arr)) {
            foreach ($pro_rec_arr as $value) {
                if ($i > $recommend_count) {
                    break;
                }
                $pro_id = $this->ci->product_model->check_recommend($value);
                if ($pro_id) {
                    $recommend_result[] = $pro_id;
                    $i++;
                }
            }//top+自动推荐商品
        }

        if (count($recommend_result) < $return_count) {
            $recommend_tmp = $this->ci->product_model->check_pro_recommend($pro_tag_tmp, 'recommend');
            $add_count = $return_count - count($recommend_result);
            $n = 0;
            foreach ($recommend_tmp as $value) {
                if ($n >= $add_count) {
                    break;
                } else {
                    $recommend_result[] = $value;
                    $n++;
                }
            }
        }//top+自动推荐商品+设置推荐商品

        $result = array();
        if (!empty($recommend_result)) {
            $result = $this->ci->product_model->get_products(array('product_id_arr' => $recommend_result, 'sort' => 0, 'region' => $region_id, 'page_size' => 3, 'curr_page' => 0, 'source' => $params['source'], 'channel' => 'portal'));
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $product_id . "_" . $region_id;
            $this->ci->memcached->set($mem_key, $result, 1800);
        }
        return $result;
    }

    function get_area_info($area_id) {
        $this->ci->load->model("region_model");
        $province_info = $this->ci->region_model->get_province_info($area_id);
        $city_info = $this->ci->region_model->get_parent_region_id($area_id);
        $area_info = $this->ci->region_model->get_area_info($area_id);
        $area_all_info['province']['id'] = $province_info['id'];
        $area_all_info['province']['name'] = $province_info['name'];
        $area_all_info['city']['id'] = $city_info['id'];
        $area_all_info['city']['name'] = $city_info['name'];
        $area_all_info['area']['id'] = $area_info['id'];
        $area_all_info['area']['name'] = $area_info['name'];
        return $area_all_info;
    }

    function sendInfo($params) {
        $required_fields = array(
            'product_id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model("region_model");

        if (isset($params['area_id']) && !empty($params['area_id'])) {//前端选择
            $area_id = $params['area_id'];
        } elseif (isset($params['connect_id']) && !empty($params['connect_id'])) {//用户默认
            $this->ci->load->library('session', array('session_id' => $params['connect_id']));
            $session = $this->ci->session->userdata;
            if (empty($session)) {
                return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
            }

            $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
            if (!isset($userdata['id']) || $userdata['id'] == "") {
                return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
            }
            $uid = $userdata['id'];

            $this->ci->load->model("order_model");
            $this->ci->load->model("user_model");
            $order_info = $this->ci->order_model->preOrderInfo($uid);

            $address_id = $this->ci->user_model->get_user_default_address($uid, $order_info['address_id']);//存在默认地址
            if (!empty($address_id)) {
                $fields = 'area';
                $where = array('id' => $address_id);
                $area_info = $this->ci->user_model->selectAddressInfo($fields, $where);
                $area_id = $area_info['area'];
            } else {
                $area_id = $this->ci->region_model->get_area_id($params['region_id']);
            }
        } else {//分站选择
            $area_id = $this->ci->region_model->get_area_id($params['region_id']);
        }


        /*获取一级地区start*/
        $refresh_province_mem = false;
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = "getAreaInfo_" . $area_id;
            $area_all_info = $this->ci->memcached->get($mem_key);
            if (!$area_all_info) {
                $area_all_info = $this->get_area_info($area_id);
                $refresh_province_mem = true;
            }
        } else {
            $area_all_info = $this->get_area_info($area_id);
            $refresh_province_mem = true;
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && $refresh_province_mem) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = "getAreaInfo_" . $area_id;
            $this->ci->memcached->set($mem_key, $area_all_info, 86400);
        }
        $province = $area_all_info['province']['id'];
        /*获取一级地区end*/

        $return_result = array();
        //判断是否有货
        $product_result = $this->ci->product_model->selectProducts('send_region,lack,delivery_limit', array('id' => $params['product_id']));
        if (strpos($product_result[0]['send_region'], '"' . $province . '"') !== false && $product_result[0]['lack'] == 0) {
            $return_result['can_buy'] = 1;
            $this->ci->load->bll('region');
            /*模拟购物车start*/
            $cart_info['items'][0]['product_id'] = $params['product_id'];
            $cart_info['items'][0]['delivery_limit'] = $product_result[0]['delivery_limit'];
            /*模拟购物车end*/
            $send_result = $this->ci->bll_region->send_time_process($area_id, $uid, $cart_info);
            if ($send_result['code'] == '300') {
                $return_result['can_buy'] = 0;
                $return_result['send_desc'] = '商品暂时无法配送';
            } elseif ($send_result[0]['time'][0]['time_key'] != 'weekday') {

                //DATE
                $week_day = array(
                    0 => '周日',
                    1 => '周一',
                    2 => '周二',
                    3 => '周三',
                    4 => '周四',
                    5 => '周五',
                    6 => '周六'
                );
                $xq = date("w", strtotime($send_result[0]['date_key']));

                if ($send_result[0]['time'][0]['disable'] == 'true') {
                    $send_desc = '最早' . date("m-d", strtotime($send_result[0]['date_key'])) . ' ' . $week_day[$xq] . ' ' . $send_result[0]['time'][0]['time_value'] . '送达';
                } else {
                    $send_desc = '最早' . date("m-d", strtotime($send_result[0]['date_key'])) . ' ' . $week_day[$xq] . ' ' . $send_result[0]['time'][1]['time_value'] . '送达';
                }
                $return_result['send_desc'] = $send_desc;
            } else {
                $return_result['send_desc'] = $send_result[0]['date_value'];
            }
        } else {
            $return_result['can_buy'] = 0;
            $return_result['send_desc'] = '';
        }
        $return_result['area_info'] = $area_all_info;
        return array('code' => '200', 'msg' => $return_result);

    }

    function getStockFromRedis($params) {
        $required_fields = array(
            'sku_id' => array('required' => array('code' => '500', 'msg' => 'sku_id id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        // $this->ci->load->library('phpredis');
        // $this->redis = $this->ci->phpredis->getConn();
        // $rs = $this->redis->hGetAll("product:" . $params['sku_id']);
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $cang_id = $region_to_warehouse[$params['region_id']];
        $rs = $this->ci->product_model->getRedisProductStock($params['sku_id'], $cang_id);
        return array('code' => 200, 'msg' => $rs);
    }


    function getClassList($params) {
    }


    function getClassProduct($params) {
    }

    /**
     * @api              {post} / 查询商品库存
     * @apiDescription   查询商品库存
     * @apiGroup         product
     * @apiName          getB2oStock
     *
     * @apiParam {String} [product_ids] 商品ID列表,逗号分隔
     * @apiParam {String} [store_id] 门店ID
     *
     * @apiSampleRequest /api/test?service=product.getB2oStock&source=app
     */
    public function getB2oStock($params){
        $required_fields = array(
            'product_ids' => array('required' => array('code' => '500', 'msg' => 'product_id id can not be null')),
            'store_id' => array('required' => array('code' => '500', 'msg' => 'store_id id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $store_id = $params['store_id'];
        $pids = explode(',', $params['product_ids']);
        $stock_data = array();
        foreach ($pids as $product_id) {
            $stock = $this->ci->b2o_store_product_model->get_product_stock($product_id, $store_id);
            if($stock<0) $stock = 0;
            $stock_data[$product_id] = $stock?intval($stock):0;
        }
        return array('code' => 200, 'stock' => $stock_data);
    }

    /**
     * @api              {post} / 扣除商品库存
     * @apiDescription   查询商品库存
     * @apiGroup         product
     * @apiName          reduceB2oStock
     *
     * @apiParam {Object[]} product_info 商品信息
     * @apiParam {Number} product_info.product_id 商品ID
     * @apiParam {Number} product_info.store_id 商品门店ID
     * @apiParam {Number} product_info.qty 商品数量
     *
     * @apiSampleRequest /api/test?service=product.reduceB2oStock&source=app
     */
    public function reduceB2oStock($params){
        $required_fields = array(
            'product_info' => array('required' => array('code' => '500', 'msg' => 'product_info can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $product_info = $params['product_info'];
        if(empty($product_info)){
            return array('code' => 200, 'msg' => 'product_info empty');
        }
        foreach ($product_info as $p_info) {
            $store_id = $p_info['store_id'];
            $product_id = $p_info['product_id'];
            $qty = $p_info['qty'];
            if(empty($store_id) || empty($product_id) || empty($qty)){
                continue;
            }
            $stock = $this->ci->b2o_store_product_model->reduce_product_stock($product_id,$store_id,$qty);
        }
        return array('code' => 200, 'msg' => 'succ');
    }

    private function addLog($data,$tag='search') {
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, $tag, json_encode($data));
    }
}
