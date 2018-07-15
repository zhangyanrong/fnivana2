<?php
namespace bll\app;
include_once("app.php");

/**
 * 商品相关接口
 */
class Product extends app {

    function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }


    /**
     * @api              {post} / 评论列表
     * @apiDescription   评论列表
     * @apiGroup         product
     * @apiName          comments
     *
     * @apiParam {Number} id 商品id
     * @apiParam {Number} page_size 产品id
     * @apiParam {Number} curr_page 指定门店id(非必填)
     * @apiParam {String} type 评论类型
     * @apiParam {Number} comment_type 评论类型
     * @apiParam {Number} show 是否只显示有图评论
     *
     * @apiSampleRequest /api/test?service=product.comments&source=app
     */
    function comments($params) {
        $page_size = $params['page_size'] ? $params['page_size'] : 10;
        $curr_page = $params['curr_page'] ? $params['curr_page'] : 0;
        $type = empty($params['type']) ? 0 : $params['type'];
        $comment_type = empty($params['comment_type']) ? 0 : $params['comment_type'];

        $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_app" . "_" . $page_size . "_" . $type . "_" . $comment_type . "_" . $curr_page . "_" . $params['show'];
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }

            $return = $this->ci->memcached->get($mem_key);
            if ($return) {
                return array('code' => 200, 'msg' => '', 'data' => $return);
            }
        }
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            $return = $result['list'];
            if (!empty($return)) {
                //获取评论回复
                $this->ci->load->model('comment_reply_model');
                $comment_ids = array_column($return, 'id');
                $comment_replys = $this->ci->comment_reply_model->commentReply($comment_ids);

                foreach ($return as &$val) {
                    //图片为空删除该字段
                    if (!empty($val['images'])) {
                        $val['images'] = explode(',', $val['images']);
                    } else {
                        unset($val['images']);
                    }
                    if (!empty($val['thumbs'])) {
                        $val['thumbs'] = explode(',', $val['thumbs']);
                    } else {
                        unset($val['thumbs']);
                    }
                    //获取回复
                    $returnReply = $params['version'] < '3.5.0' ? '' : new \stdClass();
                    $val['reply'] = empty($comment_replys[$val['id']]) ? $returnReply : $comment_replys[$val['id']];
                }
            }
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $this->ci->memcached->set($mem_key, $return, 600);
        }

        return array('code' => 200, 'msg' => '', 'data' => $return);
    }

    function category($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            $return = $result['list'];
        }
        return $return;
    }

    // function search($params) {
    //     $result = parent::call_bll($params);
    //     if (isset($result['code']) && $result['code'] != '200') {
    //         return $result;
    //     } else {
    //         $return = $result['list'];
    //     }
    //     return $return;
    // }

    function searchKey($params) {
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['region_id'] . "_app";
            $return = $this->ci->memcached->get($mem_key);
            if ($return) {
                return $return;
            }
        }
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            $region_id = $this->get_region_id($params['region_id']);
            $params['region_id'] = $region_id;
            $this->ci->load->model('banner_model');
            $banner_list = $this->ci->banner_model->get_banner_list($params);
            $search_product_list = array();
            foreach ($banner_list as $key => $value) {
                if ($value['position'] == 17) {
                    $search_product_list[] = $value;
                }
            }
            if (strcmp($params['version'], '3.5.0') >= 0) {
                $return['search_hint'] = array_pop($result);
            }
            $return['searchKey'] = $result;
            $return['search_banner'] = $search_product_list;
        }
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['region_id'] . "_app";
            $this->ci->memcached->set($mem_key, $return, 1800);
        }
        return $return;
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


    /*
     * 商品详情页 － New
     */
    function productInfoBak($params) {
        $res = parent::call_bll($params);

        //限时惠
        foreach ($res['items'] as $key => $val) {
            $e_time = $val['over_time'];
            $time = strtotime($e_time) - time();

            if ($e_time >= date('Y-m-d H:i:s') && $time > 0) {
                //unset($res['items'][$key]['start_time']);
                //unset($res['items'][$key]['over_time']);

                if ($params['version'] >= '4.2.0') {
                    $res['items'][$key]['show_time'] = $time;
                } else {
                    $res['items'][$key]['start_time'] = '0000-00-00 00:00:00';
                    $res['items'][$key]['over_time'] = '0000-00-00 00:00:00';
                    $res['items'][$key]['show_time'] = 0;
                }
            } else {
                //unset($res['items'][$key]['start_time']);
                //unset($res['items'][$key]['over_time']);

                $res['items'][$key]['show_time'] = 0;
            }

            $res['items'][$key]['start_time'] = '0000-00-00 00:00:00';
            $res['items'][$key]['over_time'] = '0000-00-00 00:00:00';
            $res['items'][$key]['show_time'] = 0;

        }

        //促销
        $this->ci->load->model('area_model');
        $province = $this->ci->area_model->getProvinceByArea($params['region_id']);

        $this->ci->load->model('product_promotion_model');
        $ppro = $this->ci->product_promotion_model->getList('title, type, target_url, target_product_id', array('product_id' => $res['product']['id']));

        if (!empty($params['connect_id'])) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
            $uid = $this->ci->login->get_uid();

            if (empty($uid)) {
                $user_rank = 1;
            } else {
                $this->ci->load->model('user_model');
                $user = $this->ci->user_model->getUser($uid);
                $user_rank = $user['user_rank'];
            }
        } else {
            $user_rank = 1;
        }

        $this->ci->load->model('promotion_v1_model');
        $promotion = $this->ci->promotion_v1_model->loadStrategies('app', 'b2c', $province['id'], $user_rank);
        $promotion = json_decode(json_encode($promotion), true);   //obj->array

        $pro = array();
        foreach ($promotion['strategies'] as $key => $val) {
            if ($val['type'] == 'amount' && $val['product']['all'] == 'false' && count($val['product']['white']) > 1 && in_array($res['product']['id'], $val['product']['white'])) {
                $ds = array(
                    'title' => $val['name'],
                    'type' => 'cart',
                    'target_url' => '',
                    'target_product_id' => '',
                    'pmt_id' => $val['id']
                );

                if ($val['solution']['type'] == 'gift') {
                    $ds['pmt_type'] = '满赠';
                } else if ($val['solution']['type'] == 'discount') {
                    $ds['pmt_type'] = '满减';
                } else {
                    $ds['pmt_type'] = '';
                }

                array_push($pro, $ds);
            }
        }
        $active = array_merge($ppro, $pro);
        $res['active'] = $active;

        //高清
        if (count($res['photo']) > 0) {
            foreach ($res['photo'] as $key => $val) {
                $res['photo'][$key]['photo'] = $val['big_photo'];
            }
        }

        return $res;
    }

    /*
     * 赠品详情页 － New
     */
    function giftInfo($params) {
        $res = parent::call_bll($params);

        //限时惠
        foreach ($res['items'] as $key => $val) {
//            $e_time = $val['over_time'];
//            $time = $this->timediff(time(),strtotime($e_time));
//
//            if($e_time >=  date('Y-m-d H:i:s') && !empty($time))
//            {
//                //unset($res['items'][$key]['start_time']);
//                //unset($res['items'][$key]['over_time']);
//
//                $res['items'][$key]['show_time'] = $time;
//            }
//            else
//            {
//                //unset($res['items'][$key]['start_time']);
//                //unset($res['items'][$key]['over_time']);
//
//                $res['items'][$key]['show_time'] = 0;
//            }
            $res['items'][$key]['start_time'] = '0000-00-00 00:00:00';
            $res['items'][$key]['over_time'] = '0000-00-00 00:00:00';
            $res['items'][$key]['show_time'] = 0;
        }

        //促销
        $this->ci->load->model('area_model');
        $province = $this->ci->area_model->getProvinceByArea($params['region_id']);

        $this->ci->load->model('product_promotion_model');
        $ppro = $this->ci->product_promotion_model->getList('title, type, target_url, target_product_id', array('product_id' => $res['product']['id']));

        if (!empty($params['connect_id'])) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
            $uid = $this->ci->login->get_uid();

            if (empty($uid)) {
                $user_rank = 1;
            } else {
                $this->ci->load->model('user_model');
                $user = $this->ci->user_model->getUser($uid);
                $user_rank = $user['user_rank'];
            }
        } else {
            $user_rank = 1;
        }

        $this->ci->load->model('promotion_v1_model');
        $promotion = $this->ci->promotion_v1_model->loadStrategies('app', 'b2c', $province['id'], $user_rank);
        $promotion = json_decode(json_encode($promotion), true);   //obj->array

        $pro = array();
        foreach ($promotion['strategies'] as $key => $val) {
            if ($val['type'] == 'amount' && $val['product']['all'] == 'false' && count($val['product']['white']) > 1 && in_array($res['product']['id'], $val['product']['white'])) {
                $ds = array(
                    'title' => $val['name'],
                    'type' => 'cart',
                    'target_url' => '',
                    'target_product_id' => '',
                    'pmt_id' => $val['id']
                );

                if ($val['solution']['type'] == 'gift') {
                    $ds['pmt_type'] = '满赠';
                } else if ($val['solution']['type'] == 'discount') {
                    $ds['pmt_type'] = '满减';
                } else {
                    $ds['pmt_type'] = '';
                }

                array_push($pro, $ds);
            }
        }
        $active = array_merge($ppro, $pro);
        $res['active'] = $active;

        return $res;
    }

    private function timediff($begin_time, $end_time) {
        $str = '';

        if ($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        $timediff = $endtime - $starttime;
        $days = intval($timediff / 86400);
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;

        if ($days == 0) {
            $str = $hours . ':' . $mins . ':' . $secs;
        }
        return $str;
    }

    /*
     * 促销商品列表
     */
    function promotionListBak($params) {
        //必要参数验证
        $required_fields = array(
            'pmt_id' => array('required' => array('code' => '500', 'msg' => 'pmt_id can not be null'))
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model('promotion_v1_model');
        $promotion = $this->ci->promotion_v1_model->getOneStrategy($params['pmt_id']);
        $promotion = json_decode(json_encode($promotion), true);   //obj->array

        $this->ci->load->model('product_model');
        $pro_ids['product_id_arr'] = $promotion['product']['white'];
        $pro_ids['source'] = $params['source'];
        $pro_ids['region'] = $params['region_id'];
        $pro_list = $this->ci->product_model->get_products($pro_ids);

        return $pro_list;
    }

    /**
     * 获取商品分类
     */
    public function getClass($params) {
        // 检查参数
        $required = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        $regionId = $this->get_region_id($params['region_id']);
        $parentId = empty($params['parent_id']) ? 0 : $params['parent_id'];

        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version']
            . "_" . $regionId . "_" . $params['cang_id'] . "_" . $parentId;
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }

            $data = $this->ci->memcached->get($memKey);
            if ($data) {
                return array('code' => '200', 'msg' => 'succ', 'data' => $data);
            }
        }

        $this->ci->load->model('cat_model');
        $where = ['c.parent_id' => $parentId];
        if ($params['version'] >= '4.2.0') {
            // mobile 分类：场景类别、商品类别
            $data = ['scene' => [], 'product' => []];
        } else {
            $data = [];
            $where['c.is_scene'] = 0;
        }
        $classes = $this->ci->cat_model->selectClass($regionId, $params['source'], $where, $params['cang_id']);

        if ($classes) {
            foreach ($classes as $class) {
                $sendRegion = empty($class['send_region']) ? array() : unserialize($class['send_region']);
                if (!in_array($regionId, $sendRegion)) {
                    continue;
                }

                $pic_url = cdnImageUrl($class['id']);
                $temp = array(
                    'id' => $class['id'],
                    'name' => $class['name'],
                    'photo' => empty($class['class_photo']) ? '' : $pic_url . $class['class_photo'],
                );
                if ($params['version'] >= '4.2.0') {
                    if ($class['is_scene']) {
                        array_push($data['scene'], $temp);
                    } else {
                        array_push($data['product'], $temp);
                    }
                } else {
                    array_push($data, $temp);
                }
            }
        } else {
            return array('code' => '300', 'msg' => 'failed');
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }

            $this->ci->memcached->set($memKey, $data, 600);
        }
        return array('code' => '200', 'msg' => 'succ', 'data' => $data);
    }

    /**
     * 获取分类商品
     */
    public function getClassProductBak($params) {
        // 检查参数
        $required = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
            'class_id' => array('required' => array('code' => '500', 'msg' => 'class id can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        $regionId = $this->get_region_id($params['region_id']);

        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version']
            . "_" . $regionId . "_" . $params['cang_id'] . "_" . $params['class_id'];
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }

            $data = $this->ci->memcached->get($memKey);
            if ($data) {
                return array('code' => '200', 'msg' => 'succ', 'data' => $data);
            }
        }

        $this->ci->load->model('product_model');
        $field = 'class.`name` as class_name, product.id as product_id, product.photo, product.cart_tag, product.product_name, product.product_desc, product.use_store, product.tag_begin_time, product.tag_end_time ,product.cang_id, product.template_id';
        $where = array(
            'class.parent_id' => $params['class_id'],
            'product.lack' => 0,
            'product.is_tuan' => 0,
        );
        switch ($params['source']) {
            case 'pc':
                $where['product.online'] = 1;
                break;
            case 'app':
                $where['product.app_online'] = 1;
                break;
            case 'wap':
                $where['product.mobile_online'] = 1;
                break;
        }
        $orderBy = "class.order_id ASC, product.order_id DESC, product.id DESC";

        //仓储设置
        $is_enable_cang = $this->ci->config->item('is_enable_cang');
        if ($is_enable_cang == 1 && !empty($params['cang_id'])) {
            $where['(cang_id LIKE \'' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . '\' OR cang_id = \'' . $params['cang_id'] . '\')'] = null;
            $like = '';
        } else {
            $like = array(
                'product.send_region' => '"' . $regionId . '"',
            );
        }

        $join = array(
            array(
                'table' => 'pro_class',
                'field' => 'product.id = pro_class.product_id',
                'type' => 'left',
            ),
            array(
                'table' => 'class',
                'field' => 'pro_class.class_id = class.id',
                'type' => 'left',
            ),
        );
        $products = $this->ci->product_model->selectProducts($field, $where, '', $orderBy, '', $like, '', $join);
        if (!$products) {
            return ['code' => '300', 'msg' => '该分类下暂无上架商品'];
        }

        $productIds = array_column($products, 'product_id');

        $listTemp = ['推荐' => []];
        $this->ci->load->model('product_label_model');
        $labelTemp = ['recommend' => [], 'labels' => []];
        $labelTemp['labels'] = array_column($this->ci->product_label_model->getTopLabel(), null, 'id');
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $cang_id = $region_to_warehouse[$params['region_id']];
        // 获取商品规格
        $productPriceTemp = $this->ci->product_model->selectProductPrice('id as price_id, product_no, price, volume, stock, product_id, sku_online', '', array(
            'key' => 'product_id',
            'value' => $productIds,
        ), 'order_id', '', $cang_id);
        $productPrices = [];
        foreach ($productPriceTemp as $item) {
            if ($item['sku_online'] == '1') {
                $productPrices[$item['product_id']][] = $item;
            }
        }

        // 获取商品标签
        $productLabelTemp = $this->ci->product_label_model->getProductLabel($productIds);
        $productLabels = [];
        foreach ($productLabelTemp as $item) {
            $productLabels[$item['product_id']][] = $item;
        }

        if ($products) {
            // 预售
            $preSell = $this->ci->product_model->get_presell_list();
            $preSellIds = array_column($preSell, 'product_id');
            $iNowUnixTime = $_SERVER['REQUEST_TIME'];

            foreach ($products as $p) {
                if (!empty($p['cart_tag']) and $p['tag_begin_time'] > 0 and $p['tag_end_time'] > 0) {
                    if ($iNowUnixTime < $p['tag_begin_time'] or $iNowUnixTime > $p['tag_end_time']) {
                        $p['cart_tag'] = '';
                    }
                }

                unset($p['tag_begin_time'], $p['tag_end_time']);

                if (in_array($p['product_id'], $preSellIds)) {
                    $p['is_hidecart'] = "1";
                } else {
                    $p['is_hidecart'] = "0";
                }

                // 获取产品模板图片
                if ($p['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($p['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $p['photo'] = $templateImages['main']['image'];
                    }
                }
                $p['photo'] = empty($p['photo']) ? '' : PIC_URL . $p['photo'];
                $p['items'] = isset($productPrices[$p['product_id']]) ? $productPrices[$p['product_id']] : [];
                $listTemp[$p['class_name']][] = $p;

                // 推荐商品
                if (isset($productLabels[$p['product_id']])) {
                    foreach ($productLabels[$p['product_id']] as $l) {
                        if (isset($labelTemp['labels'][$l['parent_id']])) {
                            $labelTemp['labels'][$l['parent_id']]['items'][$l['id']] = [
                                'id' => $l['id'],
                                'name' => $l['name'],
                                'sort' => $l['sort'],
                            ];
                        }

                        if ($l['is_recommend']) {
                            $labelTemp['recommend'][$l['id']] = [
                                'id' => $l['id'],
                                'name' => $l['name'],
                                'sort' => $l['sort'],
                            ];
                        }

                        unset($l['parent_id'], $l['is_recommend']);
                        $p['labels'][] = $l;
                    }

                    $listTemp['推荐'][] = $p;
                }
            }

            // 格式化商品列表
            foreach ($listTemp as $k => $d) {
                $data['list'][] = array(
                    'title' => $k,
                    'products' => $d,
                );
            }

            // 格式化商品标签
            if ($labelTemp['recommend']) {
                array_multisort(array_column($labelTemp['recommend'], 'sort'), SORT_ASC, SORT_NUMERIC, $labelTemp['recommend']);
            }
            $data['tag']['recommend'] = $labelTemp['recommend'];

            foreach ($labelTemp['labels'] as $labelGroup) {
                if (!isset($labelGroup['items'])) {
                    continue;
                }
                array_multisort(array_column($labelGroup['items'], 'sort'), SORT_ASC, SORT_NUMERIC, $labelGroup['items']);

                $data['tag']['labels'][] = [
                    'title' => $labelGroup['name'],
                    'items' => $labelGroup['items'],
                ];
            }
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }

            $this->ci->memcached->set($memKey, $data, 600);
        }
        return array('code' => '200', 'msg' => 'succ', 'data' => $data);
    }

    /**
     * 获取商品标签
     */
//    public function getLabel($params)
//    {
//        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'];
//        // 尝试获取缓存数据
//        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
//			if (!$this->ci->memcached) {
//				$this->ci->load->library('memcached');
//			}
//
//			$data = $this->ci->memcached->get($memKey);
//			if ($data) {
//				return array('code' => '200', 'msg' => 'succ', 'data' => $data);
//			}
//		}
//
//        $this->ci->load->model('product_label_model');
//        $labels = $this->ci->product_label_model->getList('id, name, is_recommend, parent_id', array(), 0, -1, 'parent_id, sort');
//
//        $data = array('recommend' => array(), 'labels' => array());
//        if ($labels) {
//            // 格式化数据
//            foreach ($labels as $label) {
//                if ($label['parent_id'] == 0) {
//                    $data['labels'][$label['id']]['title'] = $label['name'];
//                } else {
//                    $data['labels'][$label['parent_id']]['items'][] = array(
//                        'id' => $label['id'],
//                        'name' => $label['name'],
//                    );
//
//                    if ($label['is_recommend']) {
//                        $data['recommend'][] = array(
//                            'id' => $label['id'],
//                            'name' => $label['name'],
//                        );
//                    }
//                }
//            }
//            $data['labels'] = array_values($data['labels']);
//        } else {
//            return array('code' => '300', 'msg' => 'failed');
//        }
//
//        // 尝试保存到缓存
//        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
//			if (!$this->ci->memcached) {
//				$this->ci->load->library('memcached');
//			}
//
//			$this->ci->memcached->set($memKey, $data, 600);
//		}
//        return array('code' => '200', 'msg' => 'succ', 'data' => $data);
//    }

    /**
     * @api              {post} / 分类列表
     * @apiDescription   获取分类列表
     * @apiGroup         product
     * @apiName          getClassList
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} class_id 选定的一级分类id, 传空则取第一个
     * @apiParam {Number} tms_region_type 年轮
     *
     * @apiSampleRequest /api/test?service=product.getClassList&source=app
     */
    function getClassList($params) {
        // 检查参数
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $this->ci->load->model("b2o_front_product_class_model");
        $this->ci->load->library('phpredis');
        $this->ci->load->bll('ad');
        $this->redis = $this->ci->phpredis->getConn();

        $storeIdList = $params['store_id_list'];
        $classId = $params['class_id'];
        $tmsRegionType = $params['tms_region_type'];

        $storeArr = explode(',', $storeIdList);
        sort($storeArr);
        $cacheKey = 'class' . implode('.', $storeArr);
        //classOneGroup
        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $classOneGroupCache = $this->redis->hGet($cacheKey, 'classOneGroup');
        }
        if (empty($classOneGroupCache)) {
            $classOneGroup = $this->ci->b2o_front_product_class_model->getClassOne($storeArr, $tmsRegionType);
            foreach ($classOneGroup as &$classOne) {
                $pic_url = cdnImageUrl($classOne['id']);
                $classOne['photo'] = $pic_url . $classOne['photo'];
                $classOne['class_photo'] = $pic_url . $classOne['class_photo'];
            }
            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($cacheKey, 'classOneGroup', json_encode($classOneGroup));
                $this->redis->expire($cacheKey, 300);
            }
        } else {
            $classOneGroup = json_decode($classOneGroupCache, true);
        }

        if (empty($classId)) {
            $classId = $classOneGroup[0]['id'];
        }

        //childrenList
        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $childrenListCache = $this->redis->hGet($cacheKey, 'childrenList_' . $classId);
        }
        if (empty($childrenListCache)) {
            $childrenList = $this->ci->b2o_front_product_class_model->getByClassOne($classId, $storeArr, $tmsRegionType);
            foreach ($childrenList as &$v) {
                if (!empty($v['id'])) {
                    $class2 = $v['class2Name'];
                    unset($v['class2Name']);
                    $pic_url = cdnImageUrl($v['id']);
                    $v['photo'] = $pic_url . $v['photo'];
                    $v['class_photo'] = $pic_url . $v['class_photo'];
                    $childrenGroupByClass2[$class2][] = $v;
                }
            }

            $childrenList = array();
            foreach ($childrenGroupByClass2 as $class2Name => $childrenInfo) {
                $childrenList[] = array('class2Name' => $class2Name, 'class3Group' => $childrenInfo);
            }

            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($cacheKey, 'childrenList_' . $classId, json_encode($childrenList));
            }
        } else {
            $childrenList = json_decode($childrenListCache, true);
        }

        foreach ($classOneGroup as $classOneInfo) {
            if ($classOneInfo['id'] == $classId) {
                $bannerId = $classOneInfo['banner_id'];
                break;
            }
        }
        //banner
        $banner = $this->ci->bll_ad->formatBanner(array('banner_id' => $bannerId, 'store_id' => implode(',', $storeArr), 'region_type' => $params['tms_region_type'], 'source' => $params['source'], 'platform' => $params['platform']));
        return array('code' => 200, 'msg' => '', 'data' => array('classId' => $classId, 'classOneGroup' => $classOneGroup, 'childrenList' => $childrenList, 'banner' => (object)$banner));
    }


    /**
     * @api              {post} / 分类列表(新)
     * @apiDescription   获取分类列表(新)
     * @apiGroup         product
     * @apiName          getClassListNew
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} class_id 选定的一级分类id, 传空则取第一个
     * @apiParam {Number} tms_region_type 年轮
     *
     * @apiSampleRequest /api/test?service=product.getClassListNew&source=app
     */
    function getClassListNew($params) {
        // 检查参数
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $this->ci->load->model("b2o_front_product_class_model");
        $this->ci->load->library('phpredis');
        $this->ci->load->bll('ad');
        $this->redis = $this->ci->phpredis->getConn();

        $storeIdList = $params['store_id_list'];
        $classId = $params['class_id'];
        $tmsRegionType = $params['tms_region_type'];

        $storeArr = explode(',', $storeIdList);
        sort($storeArr);
        $cacheKey = 'class' . implode('.', $storeArr) . 'new';
        //classOneGroup
        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $classOneGroupCache = $this->redis->hGet($cacheKey, 'classOneGroup');
        }
        if (empty($classOneGroupCache)) {
            $classOneGroup = $this->ci->b2o_front_product_class_model->getClassOne($storeArr, $tmsRegionType);
            foreach ($classOneGroup as &$classOne) {
                $pic_url = cdnImageUrl($classOne['id']);
                $classOne['photo'] = $pic_url . $classOne['photo'];
                $classOne['class_photo'] = $pic_url . $classOne['class_photo'];
            }
            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($cacheKey, 'classOneGroup', json_encode($classOneGroup));
                $this->redis->expire($cacheKey, 300);
            }
        } else {
            $classOneGroup = json_decode($classOneGroupCache, true);
        }

        if (empty($classId)) {
            $classId = $classOneGroup[0]['id'];
        }

        //childrenList
        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $childrenListCache = $this->redis->hGet($cacheKey, 'childrenList_' . $classId);
        }
        if (empty($childrenListCache)) {
            $childrenList = $this->ci->b2o_front_product_class_model->getByClassOne($classId, $storeArr, $tmsRegionType);
            foreach ($childrenList as &$v) {
                if (!empty($v['id'])) {
                    $class2Name = $v['class2Name'];
                    $class2Id = $v['class2Id'];
                    unset($v['class2Name']);
                    $pic_url = cdnImageUrl($v['id']);
                    $v['photo'] = $pic_url . $v['photo'];
                    $v['class_photo'] = $pic_url . $v['class_photo'];
                    $childrenGroupByClass2[$class2Id . '_' . $class2Name][] = $v;
                }
            }

            $childrenList = array();
            foreach ($childrenGroupByClass2 as $class2Info => $childrenInfo) {
                $class2Id = strstr($class2Info, '_', true);
                $class2Name = str_replace($class2Id . '_', '', $class2Info);
                $childrenList[] = array('class2Name' => array('id' => $class2Id, 'name' => $class2Name), 'class3Group' => $childrenInfo);
            }

            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($cacheKey, 'childrenList_' . $classId, json_encode($childrenList));
            }
        } else {
            $childrenList = json_decode($childrenListCache, true);
        }

        foreach ($classOneGroup as $classOneInfo) {
            if ($classOneInfo['id'] == $classId) {
                $bannerId = $classOneInfo['banner_id'];
                break;
            }
        }
        //banner
        $banner = $this->ci->bll_ad->formatBanner(array('banner_id' => $bannerId, 'store_id' => implode(',', $storeArr), 'region_type' => $params['tms_region_type'], 'source' => $params['source'], 'platform' => $params['platform']));
        return array('code' => 200, 'msg' => '', 'data' => array('classId' => $classId, 'classOneGroup' => $classOneGroup, 'childrenList' => $childrenList, 'banner' => (object)$banner));
    }


    /**
     * @api              {post} / 分类商品列表
     * @apiDescription   获取分类下的商品
     * @apiGroup         product
     * @apiName          getClassProduct
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} class_id 三级分类id
     * @apiParam {Number} sort_type 1:综合 2:销量 3:价格正序 4:价格逆序
     * @apiParam {Number} tms_region_type 年轮
     *
     * @apiSampleRequest /api/test?service=product.getClassProduct&source=app
     */
    function getClassProduct($params) {
        // 检查参数
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'class_id' => ['required' => ['code' => '500', 'msg' => 'class_id can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],

        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $this->ci->load->model("b2o_front_product_class_model");
        $this->ci->load->model("b2o_delivery_tpl_model");
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $classId = $params['class_id'];
        $storeIdList = $params['store_id_list'];
        $sort_type = $params['sort_type'];
        $storeIdGroup = explode(',', $storeIdList);
        $tmsRegionType = $params['tms_region_type'];

        sort($storeIdGroup);
        $cacheKey = 'class:product:' . implode('.', $storeIdGroup);

        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $productGroup = $this->redis->hGet($cacheKey, 'class_' . $classId);
        }
        $productGroup = json_decode($productGroup, true);
        if (empty($productGroup)) {
            $productGroup = $this->ci->b2o_front_product_class_model->getProductByClass($classId, $storeIdGroup, $tmsRegionType);
            foreach ($productGroup as &$product) {
                // 获取产品模板图片
                if ($product['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id']);
                    if (isset($templateImages['whitebg'])) {
                        $product['promotion_photo'] = $templateImages['whitebg']['image'];
                        $product['middle_promotion_photo'] = $templateImages['whitebg']['middle_image'];
                        $product['thum_promotion_photo'] = $templateImages['whitebg']['thumb'];
                        $product['thum_min_promotion_photo'] = $templateImages['whitebg']['small_thumb'];
                    }
                    if (isset($templateImages['main'])) {
                        $product['photo'] = $templateImages['main']['image'];
                        $product['thum_photo'] = $templateImages['main']['thumb'];
                        $product['middle_photo'] = $templateImages['main']['middle_image'];
                        $product['bphoto'] = $templateImages['main']['big_image'];
                        $product['thum_min_photo'] = $templateImages['main']['small_thumb'];
                    }
                }
                $pic_url = cdnImageUrl($product['id']);
                $product['product_name'] = $product['product_name'] . ' ' . $product['volume'];
                $product['promotion_photo'] = $pic_url . $product['promotion_photo'];
                $product['middle_promotion_photo'] = $pic_url . $product['middle_promotion_photo'];
                $product['thum_promotion_photo'] = $pic_url . $product['thum_promotion_photo'];
                $product['thum_min_promotion_photo'] = $pic_url . $product['thum_min_promotion_photo'];
                $product['photo'] = $pic_url . $product['photo'];
                $product['thum_photo'] = $pic_url . $product['thum_photo'];
                $product['middle_photo'] = $pic_url . $product['middle_photo'];
                $product['bphoto'] = $pic_url . $product['bphoto'];
                $product['thum_min_photo'] = $pic_url . $product['thum_min_photo'];
                $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $product['delivery_template_id']), 'type');
                $product['isTodayDeliver'] = $deliveryInfo['type'] == 1 ? 1 : 0;
                if (!empty($product['promotion_tag_start']) && !empty($product['promotion_tag_end'])) {
                    $now = time();
                    if ($now < strtotime($product['promotion_tag_start']) || $now > strtotime($product['promotion_tag_end'])) {
                        $product['promotion_tag'] = '';
                    }
                }
            }
            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($cacheKey, 'class_' . $classId, json_encode($productGroup));
                $this->redis->expire($cacheKey, 300);
            }
        }

        $sortFactorArr = array();
        switch ($sort_type) {
            case 1:
                $sortFactor = 'order_id';
                $sortOrder = SORT_DESC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);
                $nonStockArr = array();
                foreach ($productGroup as $k => $v) {
                    if ($v['stock'] == 0) {
                        $nonStockArr[] = $v;
                        unset($productGroup[$k]);
                    }
                }

                $productGroup = array_merge($productGroup, $nonStockArr);

                break;
            case 2:
                $sortFactor = 'id';
                $sortOrder = SORT_DESC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);

                break;
            case 3:
                $sortFactor = 'price';
                $sortOrder = SORT_ASC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);

                break;
            case 4:
                $sortFactor = 'price';
                $sortOrder = SORT_DESC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);
                break;
        }


        $classInfo = $this->ci->b2o_front_product_class_model->dump(array('id' => $classId));
        $fatherClass = $this->ci->b2o_front_product_class_model->dump(array('id' => $classInfo['parent_id']));
        $brotherClass = $this->ci->b2o_front_product_class_model->getBrotherClass($fatherClass['id'], $storeIdGroup, $classId, $tmsRegionType);

        $this->_transformPhoto2webP($productGroup, $params['platform']);
        return array('code' => 200, 'msg' => '', 'data' => array('productGroup' => $productGroup, 'fatherClassName' => $fatherClass['name'], 'brotherClass' => $brotherClass));
    }


    /**
     * @api              {post} / 分类商品列表(新)
     * @apiDescription   获取分类下的商品(新)
     * @apiGroup         product
     * @apiName          getClassProductNew
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} class2_id 二级分类id
     * @apiParam {Number} class3_id 三级分类id
     * @apiParam {Number} sort_type 1:综合 2:销量 3:价格正序 4:价格逆序
     * @apiParam {Number} tms_region_type 年轮
     *
     * @apiSampleRequest /api/test?service=product.getClassProductNew&source=app
     */
    function getClassProductNew($params) {
        // 检查参数
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'class3_id' => ['required' => ['code' => '500', 'msg' => 'class3_id can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],

        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $this->ci->load->model("b2o_front_product_class_model");
        $this->ci->load->model("b2o_delivery_tpl_model");
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $class2Id = $params['class2_id'];
        $class3Id = $params['class3_id'];
        $storeIdList = $params['store_id_list'];
        $sort_type = $params['sort_type'];
        $storeIdGroup = explode(',', $storeIdList);
        $tmsRegionType = $params['tms_region_type'];

        sort($storeIdGroup);
        $cacheKey = 'class:product:' . implode('.', $storeIdGroup) . 'new';

        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $productGroup = $this->redis->hGet($cacheKey, 'class2_' . $class2Id . ':class3_' . $class3Id);
        }
        $productGroup = json_decode($productGroup, true);
        if (empty($productGroup)) {
            if ($class3Id == 0) {
                //表示获取全部
                $class3IdGroup = $this->ci->b2o_front_product_class_model->getList('id', array('parent_id' => $class2Id));
                $class3Ids = array();
                foreach ($class3IdGroup as $class3) {
                    $class3Ids[] = $class3['id'];
                }
                $productGroup = $this->ci->b2o_front_product_class_model->getProductByClass($class3Ids, $storeIdGroup, $tmsRegionType);
            } else {
                $productGroup = $this->ci->b2o_front_product_class_model->getProductByClass($class3Id, $storeIdGroup, $tmsRegionType);
            }
            foreach ($productGroup as &$product) {
                // 获取产品模板图片
                if ($product['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id']);
                    if (isset($templateImages['whitebg'])) {
                        $product['promotion_photo'] = $templateImages['whitebg']['image'];
                        $product['middle_promotion_photo'] = $templateImages['whitebg']['middle_image'];
                        $product['thum_promotion_photo'] = $templateImages['whitebg']['thumb'];
                        $product['thum_min_promotion_photo'] = $templateImages['whitebg']['small_thumb'];
                    }
                    if (isset($templateImages['main'])) {
                        $product['photo'] = $templateImages['main']['image'];
                        $product['thum_photo'] = $templateImages['main']['thumb'];
                        $product['middle_photo'] = $templateImages['main']['middle_image'];
                        $product['bphoto'] = $templateImages['main']['big_image'];
                        $product['thum_min_photo'] = $templateImages['main']['small_thumb'];
                    }
                }
                $pic_url = cdnImageUrl($product['id']);
                $product['product_name'] = $product['product_name'] . ' ' . $product['volume'];
                $product['promotion_photo'] = $pic_url . $product['promotion_photo'];
                $product['middle_promotion_photo'] = $pic_url . $product['middle_promotion_photo'];
                $product['thum_promotion_photo'] = $pic_url . $product['thum_promotion_photo'];
                $product['thum_min_promotion_photo'] = $pic_url . $product['thum_min_promotion_photo'];
                $product['photo'] = $pic_url . $product['photo'];
                $product['thum_photo'] = $pic_url . $product['thum_photo'];
                $product['middle_photo'] = $pic_url . $product['middle_photo'];
                $product['bphoto'] = $pic_url . $product['bphoto'];
                $product['thum_min_photo'] = $pic_url . $product['thum_min_photo'];
                $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $product['delivery_template_id']), 'type');
                $deliverType2word = array(1=>'当日达',2=>'次日达','3'=>'预售');
                $product['deliverType'] = $deliverType2word[$deliveryInfo['type']];
                $product['isTodayDeliver'] = $deliveryInfo['type'] == 1 ? 1 : 0;
                if (!empty($product['promotion_tag_start']) && !empty($product['promotion_tag_end'])) {
                    $now = time();
                    if ($now < strtotime($product['promotion_tag_start']) || $now > strtotime($product['promotion_tag_end'])) {
                        $product['promotion_tag'] = '';
                    }
                }
            }
            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($cacheKey, 'class2_' . $class2Id . ':class3_' . $class3Id, json_encode($productGroup));
                $this->redis->expire($cacheKey, 300);
            }
        }
        $sortFactorArr = array();
        switch ($sort_type) {
            case 1:
                $sortFactor = 'sort';
                $sortOrder = SORT_DESC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);
                $nonStockArr = array();
                foreach ($productGroup as $k => $v) {
                    if ($v['stock'] == 0) {
                        $nonStockArr[] = $v;
                        unset($productGroup[$k]);
                    }
                }

                $productGroup = array_merge($productGroup, $nonStockArr);

                break;
            case 2:
                $sortFactor = 'id';
                $sortOrder = SORT_DESC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);

                break;
            case 3:
                $sortFactor = 'price';
                $sortOrder = SORT_ASC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);

                break;
            case 4:
                $sortFactor = 'price';
                $sortOrder = SORT_DESC;

                foreach ($productGroup as $k => $productInfo) {
                    $sortFactorArr[$k] = $productInfo[$sortFactor];
                }
                array_multisort($sortFactorArr, $sortOrder, $productGroup);
                break;
        }

        if ($class3Id == 0) {
            $fatherClass = $this->ci->b2o_front_product_class_model->dump(array('id' => $class2Id));
            $brotherClass = $this->ci->b2o_front_product_class_model->getBrotherClass($fatherClass['id'], $storeIdGroup, $class3Id, $tmsRegionType);
        } else {
            $classInfo = $this->ci->b2o_front_product_class_model->dump(array('id' => $class3Id));
            $fatherClass = $this->ci->b2o_front_product_class_model->dump(array('id' => $classInfo['parent_id']));
            $brotherClass = $this->ci->b2o_front_product_class_model->getBrotherClass($fatherClass['id'], $storeIdGroup, $class3Id, $tmsRegionType);
        }

        if (!empty($brotherClass)) {
            array_unshift($brotherClass, array('id' => 0, 'name' => '全部'));
        }
        $this->_transformPhoto2webP($productGroup, $params['platform']);
        return array('code' => 200, 'msg' => '', 'data' => array('productGroup' => $productGroup, 'fatherClass' => array('name' => $fatherClass['name'], 'id' => $fatherClass['id']), 'brotherClass' => $brotherClass));
    }

    /**
     * @api              {post} / 商品详情
     * @apiDescription   商品详情
     * @apiGroup         product
     * @apiName          getProductInfo
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} product_id 产品id
     * @apiParam {Number} store_id 指定门店id(非必填)
     * @apiParam {Number} tms_region_type 年轮
     *
     * @apiSampleRequest /api/test?service=product.getProductInfo&source=app
     */
    function getProductInfo($params) {
        // 检查参数
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'product_id' => ['required' => ['code' => '500', 'msg' => 'product_id can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $this->ci->load->model("product_model");
        $this->ci->load->model("b2o_product_template_model");
        $this->ci->load->model("b2o_product_template_image_model");
        $this->ci->load->model("product_promotion_model");
        $this->ci->load->model("b2o_store_product_model");
        $this->ci->load->model("b2o_delivery_tpl_model");
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $storeIdList = $params['store_id_list'];
        $storeIdGroup = explode(',', $storeIdList);
        $product_id = $params['product_id'];
//        $storeId = in_array($params['store_id'], $storeIdGroup) ? $params['store_id'] : ''; //TODO 暂时处理,新版本此逻辑由app判断
        $storeId = 0;//不读取storeId,(首页问题)
        $tmsRegionType = $params['tms_region_type'];

        $channel = $params['channel'];
        $default_channle = $this->ci->config->item('default_channle');
        if (in_array($channel, $default_channle) || empty($channel)) {
            $channel = array('portal');
        } else {
            $channel = array('portal', $channel);
        }
        //判断目标product_id是否存在
        if (!empty($storeId)) {
            $productExist = $this->ci->product_model->getProductInfoGroupByProductAndStore($product_id, array($storeId), $tmsRegionType);
        } else {
            $productExist = $this->ci->product_model->getProductInfoGroupByProductAndStore($product_id, $storeIdGroup, $tmsRegionType);
        }
        if (empty($productExist)) {
            return array('code' => '300', 'msg' => '此商品在当前所在地区无货', 'data' => array());
        }
        $templateId = $this->ci->product_model->getTemplateId($product_id);
        if ($productExist[0]['is_hide'] == 1) {
            $productGroup = $productExist;
        } else {
            if (!empty($storeId)) {
                $productInfoByStoreId = $this->ci->product_model->getProductInfoGroupByProductAndStore($product_id, array($storeId), $tmsRegionType);
                $productGroupByTemplate = $this->ci->product_model->getProductInfoGroupByTemplate($templateId, $storeIdGroup, $tmsRegionType, '', array('product.id <>' => $product_id, 'product.channel' => $channel), 0, '', 'product.id');
                $productGroup = array_merge((array)$productInfoByStoreId, (array)$productGroupByTemplate);
            } else {
                $productGroup = $this->ci->product_model->getProductInfoGroupByTemplate($templateId, $storeIdGroup, $tmsRegionType, '', array('product.channel' => $channel), 0, '', 'product.id');
            }
        }

        $productItem = array();
        $limitPromotion = array();
        foreach ($productGroup as $key => &$product) {
            if ($product['id'] == $product_id) {
                if (!empty($product['promotion_tag_start']) && !empty($product['promotion_tag_end'])) {
                    $now = time();
                    if ($now < strtotime($product['promotion_tag_start']) || $now > strtotime($product['promotion_tag_end'])) {
                        $product['promotion_tag'] = '';
                    }
                }
                $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $product['delivery_template_id']), 'type');
                $deliverType2word = array(1=>'当日达',2=>'次日达','3'=>'预售');
                $productInfo = array('product_name' => $product['product_name'],
                    'product_desc' => $product['product_desc'],
                    'op_weight' => $product['op_weight'],
                    'price' => $product['price'],
//                    'old_price' => $product['old_price'],
                    'old_price' => $product['price'],//TODO for 315
                    'cart_tag' => $product['cart_tag'],
                    'card_limit' => $product['card_limit'],
                    'jf_limit' => $product['jf_limit'],
                    'stock' => $product['stock'],
                    'store_id' => $product['store_id'],
                    'delivery_template_id' => $product['delivery_template_id'],
                    'has_webp' => $product['has_webp'],
                    'promotion_tag' => $product['promotion_tag'],
                    'deliverType' => $deliverType2word[$deliveryInfo['type']],
                    'isTodayDeliver' => $deliveryInfo['type'] == 1 ? 1 : 0
                );

                //限时惠
                if ($product['device_limit'] != 0) {
                    $e_time = $product['limit_time_end'];
                    $time = strtotime($e_time) - time();

                    if ($e_time >= date('Y-m-d H:i:s') && $time > 0) {
                        if ($params['version'] >= '4.2.0') {
                            $limitPromotion['show_time'] = $time;
                        } else {
                            $limitPromotion['start_time'] = '0000-00-00 00:00:00';
                            $limitPromotion['over_time'] = '0000-00-00 00:00:00';
                            $limitPromotion['show_time'] = 0;
                        }
                    } else {
                        $limitPromotion['show_time'] = 0;
                    }
                    if ($product['device_limit'] == 1) {
                        $limitPromotion['title'] = '每日限' . $product['limit_time_count'] . '份数';
                    }
                    if ($product['device_limit'] == 2) {
                        $limitPromotion['title'] = '限' . $product['limit_time_count'] . '份数';
                    }
                }

            }

            $productItem[] = array('store_id' => $product['store_id'], 'id' => $product['id'], 'volume' => $product['volume']);
        }

        //商品促销
        $promotionList = array();
        $promotionGroup = $this->ci->product_promotion_model->getList('*', array('product_id' => $product_id));
        foreach ($promotionGroup as $k => $promotion) {
            switch ($promotion['type']) {
                case 'h5':
                    $promotionList[] = $promotion;
                    break;
                case 'detail':
                    $existInStoreList = $this->ci->b2o_store_product_model->existProductByStoreId($promotion['target_product_id'], $storeIdGroup, $tmsRegionType);
                    if ($existInStoreList) {
                        $promotionList[] = $promotion;
                    }
                    break;
            }
        }
        //购物车促销
        if (!empty($params['connect_id'])) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
            $uid = $this->ci->login->get_uid();

            if (empty($uid)) {
                $user_rank = 1;
            } else {
                $this->ci->load->model('user_model');
                $user = $this->ci->user_model->getUser($uid);
                $user_rank = $user['user_rank'];
            }
        } else {
            $user_rank = 1;
        }

        $this->ci->load->model('promotion_v2_model');
        $promotion = $this->ci->promotion_v2_model->loadStrategies($storeIdGroup, $user_rank, $params['source'], $params['version']);
        $promotion = json_decode(json_encode($promotion), true);   //obj->array
        $pro = array();
        foreach ($promotion['strategies'] as $key => $val) {
            if (!in_array($productInfo['store_id'], $val['range_stores'])) {
                continue;
            }

            if ($val['range_type'] != 'all' && $val['range_type'] != 'single' && count($val['range_products']) >= 1 && in_array($product_id, $val['range_products'])) {
                $ds = array(
                    'title' => $val['name'],
                    'type' => 'cart',
                    'target_url' => '',
                    'target_product_id' => '',
                    'pmt_id' => $val['id']
                );

                if ($val['solution_type'] == 'gift') {
                    $ds['pmt_type'] = '满赠';
                } else if ($val['solution_type'] == 'discount') {
                    $ds['pmt_type'] = '满减';
                } else {
                    $ds['pmt_type'] = '';
                }

                array_push($pro, $ds);
            }
        }

        $sale = array();
        $singlePromotion = $this->ci->promotion_v2_model->getSingleStrategy($product_id, $productInfo['store_id']);
        $singlePromotion = json_decode(json_encode($singlePromotion), true);   //obj->array
        if (!empty($singlePromotion)) {
            if($singlePromotion['end'] > $now){
                $sale['start'] = $singlePromotion['start'];
                $sale['end'] = $singlePromotion['end'];
                $sale['qty_limit'] = $singlePromotion['condition_qty_limit'];
                $sale['secKillPrice'] = $productInfo['price'] - $singlePromotion['solution_reduce_money'];
                $sale['time'] = time();
            }
        }

        $templateCacheKey = 'template_' . $templateId;
        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $templateInfoCache = $this->redis->hGet($templateCacheKey, 'templateInfoFormat');
            $templatePhotoCache = $this->redis->hGet($templateCacheKey, 'templatePhoto');
        }
        if (!empty($templateInfoCache) && !empty($templatePhotoCache)) {
            $templateInfoFormat = json_decode($templateInfoCache, true);
            $templatePhoto = json_decode($templatePhotoCache, true);
        } else {
            $templateInfo = $this->ci->b2o_product_template_model->dump(array('id' => $templateId), 'desc_pc,desc_mobile');
            $templatePhoto = $this->ci->b2o_product_template_image_model->getList('*', array('template_id' => $templateId, 'image_type' => array('main', 'detail')), 0, -1, 'sort');
            foreach ($templatePhoto as &$photo) {
                $pic_url = cdnImageUrl($photo['id']);
                $photo['image'] = $pic_url . $photo['image'];
                $photo['thumb'] = $pic_url . $photo['thumb'];
                $photo['big_image'] = $pic_url . $photo['big_image'];
                $photo['middle_image'] = $pic_url . $photo['middle_image'];
                $photo['small_thumb'] = $pic_url . $photo['small_thumb'];
            }
            $allowTags = '<img><style><div><dl><dt><dd>';
            $templateInfo['desc_pc'] = trim(str_replace('&nbsp;', '', strip_tags($templateInfo['desc_pc'], $allowTags)));

            $pc_url = cdnImageUrl($templateInfo['id']);
            $templateInfo['desc_pc'] = str_replace('src="/', 'src="' . $pc_url, $templateInfo['desc_pc']);
            $discription = $templateInfo['desc_pc'];
            $discription = preg_replace(array('/width=".*?"/', '/height=".*?"/', '/style=".*?"/'), array('', '', ''), $discription);
            $templateInfo['desc_pc'] = <<<EOT
<html>
        <head>
                <meta content="width=device-width, initial-scale=1.0, user-scalable=1;" name="viewport" />
                <style>*{margin:0; padding:0;}
                        .app-detail{padding:0px; margin:0}
                        .app-detail>img{width:100%;}
                </style>
        </head>
        <body><div class="app-detail">$discription</div></body>
</html>
EOT;

            $templateInfoFormat['desc_mobile'] = empty($templateInfo['desc_mobile']) ? $templateInfo['desc_pc'] : $templateInfo['desc_mobile'];
            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($templateCacheKey, 'templateInfoFormat', json_encode($templateInfoFormat));
                $this->redis->hSet($templateCacheKey, 'templatePhoto', json_encode($templatePhoto));
                $this->redis->expire($templateCacheKey, 300);
            }

        }

        $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $productInfo['delivery_template_id']), 'type,rule');
        $deliveryMsg = '';
        if ($deliveryInfo['type'] == 1) {
            $deliveryMsg = '最快当日1小时内送达';
        }
        if ($deliveryInfo['type'] == 2) {
            $deliveryMsg = '最快' . date('Y-m-d', strtotime('+1 day')) . '日送到';
        }
        if ($deliveryInfo['type'] == 3) {
            $rule = json_decode($deliveryInfo['rule'], true);
            $deliveryMsg = '最快' . date('Y-m-d', strtotime($rule['delivery_date'])) . '日送到';
        }

        $this->_transformPhoto2webPByTemplatePhoto($templatePhoto, $params['platform']);
        $returnData['productInfo'] = $productInfo;
        $returnData['productItem'] = $productItem;
        $returnData['templateInfo'] = $templateInfoFormat;
        $returnData['templatePhoto'] = $templatePhoto;
        $returnData['promotion'] = array_merge($promotionList, $pro);
        $returnData['shareUrl'] = "http://m.fruitday.com/detail/index/" . $product_id;
        $returnData['deliveryMsg'] = $deliveryMsg;
        $returnData['sale'] = (object)$sale;

        return array('code' => 200, 'msg' => '', 'data' => $returnData);
    }

    /**
     * @api              {post} / 已关注商品的相关商品
     * @apiDescription   已关注商品的相关商品
     * @apiGroup         product
     * @apiName          markedProducts
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} product_id 产品id
     * @apiParam {Number} tms_region_type 年轮
     * @apiParam {Number} [page=1] 第几页
     * @apiParam {Number} [limit=10] 每页显示几条
     *
     * @apiSampleRequest /api/test?service=product.markedProducts&source=app
     */
    function markedProducts($params) {
        $required_fields = array(
            'product_id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],

        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model("product_model");
        $this->ci->load->model("b2o_product_template_model");
        $this->ci->load->model("b2o_delivery_tpl_model");

        $productId = $params['product_id'];
        $storeIdList = $params['store_id_list'];
        $storeIdGroup = explode(',', $storeIdList);
        $limit = $params['limit'] ? (int)$params['limit'] : 0;
        $offset = (int)($params['page'] - 1) * $limit;
        $tmsRegionType = $params['tms_region_type'];


        //活动3级类目id by product
        $templateId = $this->ci->product_model->getTemplateId($productId);
        $templateInfo = $this->ci->b2o_product_template_model->dump(array('id' => $templateId));
        $class3Id = $templateInfo['class_id'];
        //获取3级类目下所有商品
        $templateGroup = $this->ci->b2o_product_template_model->getList('id', array('class_id' => $class3Id), 0, -1);
        $templateIdGroup = array_column($templateGroup, 'id');

        $productList = $this->ci->product_model->getProductInfoGroupByTemplate($templateIdGroup, $storeIdGroup, $tmsRegionType, 'product.photo', array(), $offset, $limit, $sort = 'product.order_id');
        $deliverType2word = array(1=>'当日达',2=>'次日达','3'=>'预售');
        foreach ($productList as &$product) {
            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['photo'] = $templateImages['main']['image'];
                }
            }

            $pic_url = cdnImageUrl($product['id']);
            $product['photo'] = $pic_url . $product['photo'];
            $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $product['delivery_template_id']), 'type');
            $product['deliverType'] = $deliverType2word[$deliveryInfo['type']];
            $product['isTodayDeliver'] = $deliveryInfo['type'] == 1 ? 1 : 0;

            if (!empty($product['promotion_tag_start']) && !empty($product['promotion_tag_end'])) {
                $now = time();
                if ($now < strtotime($product['promotion_tag_start']) || $now > strtotime($product['promotion_tag_end'])) {
                    $product['promotion_tag'] = '';
                }
            }
        }
        $this->_transformPhoto2webP($productList, $params['platform']);
        return array('code' => 200, 'msg' => '', 'data' => $productList);
    }


    /**
     * @api              {post} / 促销商品列表
     * @apiDescription   促销商品列表
     * @apiGroup         product
     * @apiName          promotionList
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} pmt_id 促销id
     * @apiParam {Number} tms_region_type 年轮
     *
     * @apiSampleRequest /api/test?service=product.promotionList&source=app
     */
    function promotionList($params) {
        //必要参数验证
        $required_fields = array(
            'pmt_id' => array('required' => array('code' => '500', 'msg' => 'pmt_id can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model('promotion_v2_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('b2o_delivery_tpl_model');

        $storeIdList = $params['store_id_list'];
        $storeIdGroup = explode(',', $storeIdList);
        $tmsRegionType = $params['tms_region_type'];

        $promotion = $this->ci->promotion_v2_model->getOneStrategy($params['pmt_id']);
        $promotion = json_decode(json_encode($promotion), true);   //obj->array
        $pro_ids['product_id_arr'] = $promotion['range_products'];

        if (empty($pro_ids['product_id_arr'])) {
            return array('code' => 200, 'msg' => '', 'data' => array());
        }
        $productList = $this->ci->product_model->getProductInfoGroupByProductId($pro_ids['product_id_arr'], $storeIdGroup, $tmsRegionType, 'product.photo');
        foreach ($productList as &$product) {
            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['photo'] = $templateImages['main']['image'];
                }
            }

            $pic_url = cdnImageUrl($product['id']);
            $product['photo'] = $pic_url . $product['photo'];
            $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $product['delivery_template_id']), 'type');
            $product['isTodayDeliver'] = $deliveryInfo['type'] == 1 ? 1 : 0;
            if (!empty($product['promotion_tag_start']) && !empty($product['promotion_tag_end'])) {
                $now = time();
                if ($now < strtotime($product['promotion_tag_start']) || $now > strtotime($product['promotion_tag_end'])) {
                    $product['promotion_tag'] = '';
                }
            }
        }
        $this->_transformPhoto2webP($productList, $params['platform']);
        return array('code' => 200, 'msg' => '', 'data' => $productList);
    }


    /**
     * @api              {post} / 根据商品id获取列表
     * @apiDescription   根据商品id获取列表
     * @apiGroup         product
     * @apiName          productList
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {String} product_id_list 产品id,逗号分隔
     * @apiParam {Number} tms_region_type 年轮
     * @apiParam {Number} [page=1] 第几页
     * @apiParam {Number} [limit=10] 每页显示几条
     * @apiParam {Number} [show_tuan] 过滤参数
     * @apiParam {String} [channel] 过滤参数
     *
     *
     * @apiSampleRequest /api/test?service=product.productList&source=app
     */
    function productList($params) {
        $required_fields = array(
            'product_id_list' => array('required' => array('code' => '300', 'msg' => '请输入商品id')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model('product_model');
        $this->ci->load->model('b2o_delivery_tpl_model');

        $product_id_arr = explode(',', $params['product_id_list']);
        $storeIdList = $params['store_id_list'];
        $storeIdGroup = explode(',', $storeIdList);
        $limit = $params['limit'] ? (int)$params['limit'] : 0;
        $offset = (int)($params['page'] - 1) * $limit;
        $is_tuan = (int)$params['show_tuan'];
        $tmsRegionType = $params['tms_region_type'];

        $defaultChannle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $defaultChannle)) {
            $params['channel'] = 'portal';
        }
        $channel = empty($params['channel']) ? ['portal'] : ['portal', $params['channel']];

        $productList = $this->ci->product_model->getProductInfoGroupByProductId($product_id_arr, $storeIdGroup, $tmsRegionType, 'product.photo', $offset, $limit, 'product.order_id', array('product.channel' => $channel, 'product.is_tuan' => $is_tuan));
        foreach ($productList as &$product) {
            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['photo'] = $templateImages['main']['image'];
                }
            }

            $pic_url = cdnImageUrl($product['id']);
            $product['photo'] = $pic_url . $product['photo'];
            $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $product['delivery_template_id']), 'type');
            $product['isTodayDeliver'] = $deliveryInfo['type'] == 1 ? 1 : 0;
            if (!empty($product['promotion_tag_start']) && !empty($product['promotion_tag_end'])) {
                $now = time();
                if ($now < strtotime($product['promotion_tag_start']) || $now > strtotime($product['promotion_tag_end'])) {
                    $product['promotion_tag'] = '';
                }
            }
        }
        $this->_transformPhoto2webP($productList, $params['platform']);
        return array('code' => 200, 'msg' => '', 'data' => $productList);
    }


    /**
     * @api              {post} / 赠品详情
     * @apiDescription   赠品详情
     * @apiGroup         product
     * @apiName          getGiftInfo
     *
     * @apiParam {String} store_id_list 门店id,逗号分隔
     * @apiParam {Number} tms_region_type 年轮
     * @apiParam {Number} product_id 产品id
     * @apiParam {Number} store_id 指定门店id(非必填)
     *
     * @apiSampleRequest /api/test?service=product.getGiftInfo&source=app
     */
    function getGiftInfo($params) {
        // 检查参数
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'product_id' => ['required' => ['code' => '500', 'msg' => 'product_id can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $this->ci->load->model("product_model");
        $this->ci->load->model("b2o_product_template_model");
        $this->ci->load->model("b2o_product_template_image_model");
        $this->ci->load->model("product_promotion_model");
        $this->ci->load->model("b2o_store_product_model");
        $this->ci->load->model("b2o_delivery_tpl_model");
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $storeIdList = $params['store_id_list'];
        $storeIdGroup = explode(',', $storeIdList);
        $product_id = $params['product_id'];
        $tmsRegionType = $params['tms_region_type'];

        //判断目标product_id是否存在
        $productExist = $this->ci->product_model->getGiftInfoGroupByProductAndStore($product_id, $storeIdGroup, $tmsRegionType);
        if (empty($productExist)) {
            return array('code' => '300', 'msg' => '此商品在当前所在地区无货', 'data' => array());
        }

        $templateId = $this->ci->product_model->getTemplateId($product_id);
        $productGroup = $this->ci->product_model->getGiftByStore($product_id, $storeIdList, $tmsRegionType);

        $productItem = array();
        $limitPromotion = array();
        foreach ($productGroup as $key => &$product) {

            if ($product['id'] == $product_id) {
                $productInfo = array('product_name' => $product['product_name'],
                    'product_desc' => $product['product_desc'],
                    'op_weight' => $product['op_weight'],
                    'price' => $product['price'],
                    'old_price' => $product['old_price'],
                    'cart_tag' => $product['cart_tag'],
                    'card_limit' => $product['card_limit'],
                    'jf_limit' => $product['jf_limit'],
                    'stock' => $product['stock'],
                    'has_webp' => $product['has_webp']
                );

                //限时惠
                if ($product['device_limit'] != 0) {
                    $e_time = $product['limit_time_end'];
                    $time = strtotime($e_time) - time();

                    if ($e_time >= date('Y-m-d H:i:s') && $time > 0) {
                        if ($params['version'] >= '4.2.0') {
                            $limitPromotion['show_time'] = $time;
                        } else {
                            $limitPromotion['start_time'] = '0000-00-00 00:00:00';
                            $limitPromotion['over_time'] = '0000-00-00 00:00:00';
                            $limitPromotion['show_time'] = 0;
                        }
                    } else {
                        $limitPromotion['show_time'] = 0;
                    }
                    if ($product['device_limit'] == 1) {
                        $limitPromotion['title'] = '每日限' . $product['limit_time_count'] . '份数';
                    }
                    if ($product['device_limit'] == 2) {
                        $limitPromotion['title'] = '限' . $product['limit_time_count'] . '份数';
                    }
                }
            }

            $productItem[] = array('store_id' => $product['store_id'], 'id' => $product['id'], 'volume' => $product['volume']);
        }

        //商品促销
        $promotionList = array();
        $promotionGroup = $this->ci->product_promotion_model->getList('*', array('product_id' => $product_id));
        foreach ($promotionGroup as $k => $promotion) {
            switch ($promotion['type']) {
                case 'h5':
                    $promotionList[] = $promotion;
                    break;
                case 'detail':
                    $existInStoreList = $this->ci->b2o_store_product_model->existProductByStoreId($promotion['target_product_id'], $storeIdGroup, $tmsRegionType);
                    if ($existInStoreList) {
                        $promotionList[] = $promotion;
                    }
                    break;
            }
        }
        //购物车促销
        if (!empty($params['connect_id'])) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
            $uid = $this->ci->login->get_uid();

            if (empty($uid)) {
                $user_rank = 1;
            } else {
                $this->ci->load->model('user_model');
                $user = $this->ci->user_model->getUser($uid);
                $user_rank = $user['user_rank'];
            }
        } else {
            $user_rank = 1;
        }

        $this->ci->load->model('promotion_v2_model');
        $promotion = $this->ci->promotion_v2_model->loadStrategies(array($product_id), $storeIdGroup, $user_rank, $params['source']);
        $promotion = json_decode(json_encode($promotion), true);   //obj->array
        $pro = array();
        foreach ($promotion['strategies'] as $key => $val) {
            if ($val['type'] == 'amount' && $val['product']['all'] == 'false' && count($val['product']['white']) > 1 && in_array($product_id, $val['product']['white'])) {
                $ds = array(
                    'title' => $val['name'],
                    'type' => 'cart',
                    'target_url' => '',
                    'target_product_id' => '',
                    'pmt_id' => $val['id']
                );

                if ($val['solution']['type'] == 'gift') {
                    $ds['pmt_type'] = '满赠';
                } else if ($val['solution']['type'] == 'discount') {
                    $ds['pmt_type'] = '满减';
                } else {
                    $ds['pmt_type'] = '';
                }

                array_push($pro, $ds);
            }
        }

        $templateCacheKey = 'template_' . $templateId;

        if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
            $templateInfoCache = $this->redis->hGet($templateCacheKey, 'templateInfoFormat');
            $templatePhotoCache = $this->redis->hGet($templateCacheKey, 'templatePhoto');
        }
        if (!empty($templateInfoCache) && !empty($templatePhotoCache)) {
            $templateInfoFormat = json_decode($templateInfoCache, true);
            $templatePhoto = json_decode($templatePhotoCache, true);
        } else {
            $templateInfo = $this->ci->b2o_product_template_model->dump(array('id' => $templateId), 'desc_mobile');
            $templatePhoto = $this->ci->b2o_product_template_image_model->getList('*', array('template_id' => $templateId, 'image_type' => 'main'), 0, -1, 'sort');
            foreach ($templatePhoto as &$photo) {
                $pic_url = cdnImageUrl($photo['id']);
                $photo['image'] = $pic_url . $photo['image'];
                $photo['thumb'] = $pic_url . $photo['thumb'];
                $photo['big_image'] = $pic_url . $photo['big_image'];
                $photo['middle_image'] = $pic_url . $photo['middle_image'];
                $photo['small_thumb'] = $pic_url . $photo['small_thumb'];
            }
            $allowTags = '<img><style><div><dl><dt><dd>';
            $templateInfo['desc_pc'] = trim(str_replace('&nbsp;', '', strip_tags($templateInfo['desc_pc'], $allowTags)));

            $pc_url = constant(CDN_URL . rand(1, 9));
            $templateInfo['desc_pc'] = str_replace('src="/', 'src="' . $pc_url, $templateInfo['desc_pc']);
            $discription = $templateInfo['desc_pc'];
            $discription = preg_replace(array('/width=".*?"/', '/height=".*?"/', '/style=".*?"/'), array('', '', ''), $discription);
            $templateInfo['desc_pc'] = <<<EOT
<html>
        <head>
                <meta content="width=device-width, initial-scale=1.0, user-scalable=1;" name="viewport" />
                <style>*{margin:0; padding:0;}
                        .app-detail{padding:0px; margin:0}
                        .app-detail>img{width:100%;}
                </style>
        </head>
        <body><div class="app-detail">$discription</div></body>
</html>
EOT;
            $templateInfoFormat['desc_mobile'] = empty($templateInfo['desc_mobile']) ? $templateInfo['desc_pc'] : $templateInfo['desc_mobile'];
            if (defined('OPEN_REDIS') && OPEN_REDIS == true) {
                $this->redis->hSet($templateCacheKey, 'templateInfoFormat', json_encode($templateInfoFormat));
                $this->redis->hSet($templateCacheKey, 'templatePhoto', json_encode($templatePhoto));
                $this->redis->expire($templateCacheKey, 300);
            }

        }

        $deliveryInfo = $this->ci->b2o_delivery_tpl_model->dump(array('tpl_id' => $templateId), 'type,rule');
        $deliveryMsg = '';
        if ($deliveryInfo['type'] == 1) {
            $deliveryMsg = '最快当日1小时内送达';
        }
        if ($deliveryInfo['type'] == 2) {
            $deliveryMsg = '最快' . date('Y-m-d', strtotime('+1 day')) . '日送到';
        }
        if ($deliveryInfo['type'] == 3) {
            $rule = json_decode($deliveryInfo['rule'], true);
            $deliveryMsg = '最快' . date('Y-m-d', strtotime($rule['delivery_date'])) . '日送到';
        }

        $this->_transformPhoto2webPByTemplatePhoto($templatePhoto, $params['platform']);
        $returnData['productInfo'] = $productInfo;
        $returnData['productItem'] = $productItem;
        $returnData['templateInfo'] = $templateInfoFormat;
        $returnData['templatePhoto'] = $templatePhoto;
        $returnData['promotion'] = $promotionList;
        $returnData['active'] = $pro;
        $returnData['shareUrl'] = "http://m.fruitday.com/detail/index/" . $product_id;
        $returnData['deliveryMsg'] = $deliveryMsg;

        return array('code' => 200, 'msg' => '', 'data' => $returnData);
    }

    /**
     * @param $productGroup
     */
    private function _transformPhoto2webP(&$productGroup, $plantForm) {
        if ($plantForm != 'IOS') {
            $photoKeys = array('photo', 'thum_photo', 'middle_photo', 'bphoto', 'thum_min_photo', 'promotion_photo', 'middle_promotion_photo', 'thum_promotion_photo', 'bpromotion_photo', 'thum_min_promotion_photo');
            $find = array('.jpg', '.jpeg');
            foreach ($productGroup as &$product) {
                if ($product['has_webp'] == 1) {
                    foreach ($product as $k => $v) {
                        if (in_array($k, $photoKeys)) {
                            $product[$k] = str_replace($find, '.webp', $v);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $templatePhoto
     */
    private function _transformPhoto2webPByTemplatePhoto(&$templatePhoto, $plantForm) {
        if ($plantForm != 'IOS') {
            $photoKeys = array('photo', 'thum_photo', 'middle_photo', 'bphoto', 'thum_min_photo', 'promotion_photo', 'middle_promotion_photo', 'thum_promotion_photo', 'bpromotion_photo', 'thum_min_promotion_photo');
            $find = array('.jpg', '.jpeg');
            foreach ($templatePhoto as $k => $v) {
                if (in_array($k, $photoKeys) && $v['has_webp']) {
                    $templatePhoto[$k] = str_replace($find, '.webp', $v);
                }
            }
        }
    }
}