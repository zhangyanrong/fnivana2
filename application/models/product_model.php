<?php

class Product_model extends CI_model {

    function Product_model() {
        parent::__construct();
        $this->load->helper('public');
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }


    /*
    *获取商品信息
    */
    function selectProducts($field, $where = '', $where_in = '', $order = '', $limits = '', $like = '', $or_like = '', $join = '', $or_like2 = '') {
        $this->db->select($field);
        $this->db->distinct();
        $this->db->from('product');
        if (!empty($where)) {
            $this->db->where($where);
        }
        if (!empty($where_in)) {
            foreach ($where_in as $val) {
                $this->db->where_in($val['key'], $val['value']);
            }
        }
        if (!empty($like)) {
            $this->db->like($like);
        }
        if (!empty($or_like)) {
            $this->db->or_like($or_like);
        }
        if (!empty($or_like2)) {
            $this->db->or_like($or_like2);
        }
        if (!empty($limits)) {
            $this->db->limit($limits['page_size'], ($limits['curr_page'] * $limits['page_size']));
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($join)) {
            foreach ($join as $val) {
                $this->db->join($val['table'], $val['field'], $val['type']);
            }
        }

        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
     * 获取商品规格信息
     */
    function selectProductPrice($field, $where = '', $where_in = '', $order = '', $group_by = '', $ware_id = 0) {
        $this->db->select($field);
        $this->db->from('product_price');
        if (!empty($where)) {
            $this->db->where($where);
        }
        if (!empty($where_in)) {
            $this->db->where_in($where_in['key'], $where_in['value']);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group_by)) {
            $this->db->group_by($group_by);
        }
        $result = $this->db->get()->result_array();
        foreach ($result as $key => $value) {
            if (isset($value['id']) || isset($value['price_id'])) {
                $sku_id = isset($value['id']) ? $value['id'] : $value['price_id'];
                $product_no = isset($value['product_no']) ? $value['product_no'] : '';
                $sku_info = $this->getRedisProductStock($sku_id, $ware_id, 0, $product_no);
                $result[$key]['stock'] = $sku_info['stock'];
            }
        }
        return $result;
    }

    /*
     * 获取商品图片信息
     */
    function selectProductPhoto($field, $where = '', $where_in = '', $order = '') {
        $this->db->select($field);
        $this->db->from('product_photo');
        if (!empty($where)) {
            $this->db->where($where);
        }
        if (!empty($where_in)) {
            $this->db->where_in($where_in['key'], $where_in['value']);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
     * 获取商品赠品信息
     */
    function selectProductGift($field, $where = '', $where_in = '', $order = '') {
        $this->db->select($field);
        $this->db->from('product_gifts');
        if (!empty($where)) {
            $this->db->where($where);
        }
        if (!empty($where_in)) {
            $this->db->where_in($where_in['key'], $where_in['value']);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *获取线下活动
    */
    function selectOffline() {
        $this->db->from('offline_badge_active');
        $now_data = date("Y-m-d");
        $where['start_time <='] = $now_data;
        $where['end_time >='] = $now_data;
        $where['active_type'] = 2;
        $result = $this->db->get()->result_array();
        return $result;
    }

    function getProductsById($ids, $where = array()) {
        $res = $this->getGroupProducts($ids, $where);

        $iNowUnixTime = $_SERVER['REQUEST_TIME'];

        foreach ($res as $key => $val) {
            // 获取产品模板图片
            if ($val['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($val['template_id']);
                if (isset($templateImages['main'])) {
                    $val['middle_photo'] = $templateImages['main']['middle_image'];
                }
                if (isset($templateImages['whitebg'])) {
                    $val['promotion_photo'] = $templateImages['whitebg']['image'];
                }
            }

            if (!empty($val['cart_tag']) and $val['tag_begin_time'] > 0 and $val['tag_end_time'] > 0) {
                if ($iNowUnixTime < $val['tag_begin_time'] or $iNowUnixTime > $val['tag_end_time']) {
                    $res[$key]['cart_tag'] = '';
                }
            }

            unset($res[$key]['tag_begin_time'], $res[$key]['tag_end_time']);

            if ($val['sku_online'] == 0) {
                unset($res[$key]);
            }
            if (isset($val['middle_photo']) && !empty($val['middle_photo'])) {
                $pc_url = constant(CDN_URL . rand(1, 9));
                $res[$key]['photo'] = $pc_url . $val['middle_photo'];
            }
            if (isset($val['promotion_photo']) && !empty($val['promotion_photo'])) {
                $pc_url = constant(CDN_URL . rand(1, 9));
                $res[$key]['promotion_photo'] = $pc_url . $val['promotion_photo'];
            }
        }
        return $res;
    }

    /*
    *获取组合商品信息
    */
    function getGroupProducts($group_pro_list, $where = array()) {
        $this->db->select('product.id,product.product_name,product.photo,product.middle_photo,product.promotion_photo,product_price.product_no,product_price.volume,product_price.unit,product_price.price, product_price.id price_id, product_price.sku_online,product_price.old_price,product.cart_tag, product.tag_begin_time, product.tag_end_time, product.template_id');
        $this->db->from('product');
        $this->db->join('product_price', 'product_price.product_id=product.id');
        $this->db->where_in('product.id', $group_pro_list);

        if (!empty($where)) {
            if (is_array($where)) {
                foreach ($where as $k => $v) {
                    $this->db->where($k, $v);
                }
            } else {
                $this->db->where($where);
            }
        }

        $group_pro_result = $this->db->get()->result_array();
        return $group_pro_result;
    }

    /*
    *提货券商品信息
    */
    function getProcardProducts($group_pro_list) {
        $this->db->select('product_price.id as price_id,product.product_name,product.send_region,product.summary,product.photo,product_price.price,product_price.volume,product_price.unit,product.product_desc as `desc`, product.template_id');
        $this->db->from('product');
        $this->db->join('product_price', 'product_price.product_id=product.id');
        $this->db->where_in('product.id', $group_pro_list);
        $group_pro_result = $this->db->get()->result_array();
        return $group_pro_result;
    }

    /*
    *商品详情
    */
    function get_product_kjt($id, $channel = 'portal', $source = 'pc', $cang_id = 0) {
        $default_channle = $this->config->item('default_channle');
        if (in_array($channel, $default_channle)) {
            $channel = 'portal';
        }
        //获取商品基础信息
        $product = array();
        $where_in = array();
        $order = ' order_id desc,id desc';
        $field = "id,product_name,discription,photo,thum_photo,app_online as online,offline,send_region,free,consumer_tips,long_photo,op_detail_place,summary,template_id";
        $where = array('id' => $id);
        if ($channel == 'portal') {
            $where['channel'] = 'portal';
        } else {
            $where_in[] = array(
                'key' => 'channel',
                'value' => array('portal', $channel)
            );
        }
        $result_array = $this->selectProducts($field, $where, $where_in, $order);
        $result = $result_array[0];
        if (empty($result)) {
            return array('code' => '300', 'msg' => '该商品已售罄');
        }
        $result['consumer_tips'] = trim(str_replace('&nbsp;', '', strip_tags($result['consumer_tips'], '<img>')));
        $result['discription'] = trim(str_replace('&nbsp;', '', strip_tags($result['discription'], '<img>')));
        if (!empty($result['consumer_tips'])) {
            $result['discription'] = $result['consumer_tips'];
        }
        $result['discription'] = str_replace('src="/', 'src="' . PIC_URL, $result['discription']);
        $discription = $result['discription'];
        $discription = preg_replace(array('/class=".*?"/', '/width=".*?"/', '/height=".*?"/', '/style=".*?"/'), array('', '', '', ''), $discription);
        $result['discription'] = <<<EOT
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
        // if ($is_filter){
        //     $result['discription'] = strip_tags($result['discription'],'<img>');
        //     preg_match_all('/src=\"(.*?)\"/',$result['discription'],$imglist_result);
        //     $result['discription'] = $imglist_result[1];
        // }
        // $result['region'] = '';
        // $region_arr = array_flip($this->config->item('str_area_refelect'));
        // foreach (unserialize($result['send_region']) as $key => $value) {
        //     $result['region'] .= $region_arr[$value].',';
        // }
        // $result['region'] = trim($result['region'],',');
        unset($result['online'], $result['offline']);
        $result['long_photo'] = PIC_URL . $result['long_photo'];
        $product_tmp = $result;

        //获取价格
        $field = "mobile_price,price,volume,id,product_no,product_id,old_price";
        $where = array('product_id' => $id);
        $order = ' order_id asc,id desc';
        $price_result = $this->selectProductPrice($field, $where, '', $order, '', $cang_id);
        if (!empty($price_result)) {
            foreach ($price_result as $key => $value) {
                if ($value['mobile_price'] > 0) {
                    $price_result[$key]['price'] = $value['mobile_price'];
                }
                unset($price_result[$key]['mobile_price']);
            }
        }

        // 获取产品模板图片
        if ($product_tmp['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product_tmp['template_id']);
            if (isset($templateImages['main'])) {
                $product_tmp['photo'] = $templateImages['main']['image'];
                $product_tmp['thum_photo'] = $templateImages['main']['thumb'];
            }
        }
        //获取图片
        $photo_arr_tmp = array();
        $photo_arr_tmp[0]['thum_photo'] = $product_tmp['thum_photo'] = PIC_URL . $product_tmp['thum_photo'];
        $photo_arr_tmp[0]['photo'] = $product_tmp['photo'] = PIC_URL . $product_tmp['photo'];

        if ($product_tmp['template_id']) {
            if (isset($templateImages['detail'])) {
                foreach ($templateImages['detail'] as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thumb'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['image'];
                }
            }
        } else {
            $field = "id,product_id,thum_photo,photo";
            $where = array('product_id' => $id);
            $order = ' order_id asc,id desc';
            $photo_arr = $this->selectProductPhoto($field, $where, '', $order);
            if (!empty($photo_arr)) {
                foreach ($photo_arr as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thum_photo'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['photo'];
                }
            }
        }

        $product['product'] = $product_tmp;
        $product['items'] = $price_result;
        $product['photo'] = $photo_arr_tmp;
        return $product;
    }

    /*
   *团购商品详情
   */
    function get_product_group($id, $channel = 'portal', $source = 'pc', $cang_id = 0) {
        $default_channle = $this->config->item('default_channle');
        if (in_array($channel, $default_channle)) {
            $channel = 'portal';
        }
        //获取商品基础信息
        $product = array();
        $where_in = array();
        $order = ' order_id desc,id desc';
        $field = "id,product_name,discription,photo,thum_photo,app_online as online,offline,send_region,free,consumer_tips,long_photo,op_detail_place,summary,template_id";
        $where = array('id' => $id);
        if ($channel == 'portal') {
            $where['channel'] = 'portal';
        } else {
            $where_in[] = array(
                'key' => 'channel',
                'value' => array('portal', $channel)
            );
        }
        $result_array = $this->selectProducts($field, $where, $where_in, $order);
        $result = $result_array[0];
        if (empty($result)) {
            return array('code' => '300', 'msg' => '该商品已售罄');
        }
        $result['consumer_tips'] = trim(str_replace('&nbsp;', '', strip_tags($result['consumer_tips'], '<img>')));
        $result['discription'] = trim(str_replace('&nbsp;', '', strip_tags($result['discription'], '<img>')));
        if (!empty($result['consumer_tips'])) {
            $result['discription'] = $result['consumer_tips'];
        }
        $result['discription'] = str_replace('src="/', 'src="' . PIC_URL, $result['discription']);
        $discription = $result['discription'];
        $discription = preg_replace(array('/class=".*?"/', '/width=".*?"/', '/height=".*?"/', '/style=".*?"/'), array('', '', '', ''), $discription);
        $result['discription'] = <<<EOT
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
        // if ($is_filter){
        //     $result['discription'] = strip_tags($result['discription'],'<img>');
        //     preg_match_all('/src=\"(.*?)\"/',$result['discription'],$imglist_result);
        //     $result['discription'] = $imglist_result[1];
        // }
        // $result['region'] = '';
        // $region_arr = array_flip($this->config->item('str_area_refelect'));
        // foreach (unserialize($result['send_region']) as $key => $value) {
        //     $result['region'] .= $region_arr[$value].',';
        // }
        // $result['region'] = trim($result['region'],',');
        unset($result['online'], $result['offline']);
        $result['long_photo'] = PIC_URL . $result['long_photo'];
        $product_tmp = $result;

        //获取价格
        $field = "mobile_price,price,volume,id,product_no,product_id,old_price";
        $where = array('product_id' => $id);
        $order = ' order_id asc,id desc';
        $price_result = $this->selectProductPrice($field, $where, '', $order, '', $cang_id);
        if (!empty($price_result)) {
            foreach ($price_result as $key => $value) {
                if ($value['mobile_price'] > 0) {
                    $price_result[$key]['price'] = $value['mobile_price'];
                }
                unset($price_result[$key]['mobile_price']);
            }
        }

        // 获取产品模板图片
        if ($product_tmp['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product_tmp['template_id']);
            if (isset($templateImages['main'])) {
                $product_tmp['photo'] = $templateImages['main']['image'];
                $product_tmp['thum_photo'] = $templateImages['main']['thumb'];
            }
        }
        //获取图片
        $photo_arr_tmp = array();
        $photo_arr_tmp[0]['thum_photo'] = $product_tmp['thum_photo'] = PIC_URL . $product_tmp['thum_photo'];
        $photo_arr_tmp[0]['photo'] = $product_tmp['photo'] = PIC_URL . $product_tmp['photo'];

        if ($product_tmp['template_id']) {
            if (isset($templateImages['detail'])) {
                foreach ($templateImages['detail'] as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thumb'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['image'];
                }
            }
        } else {
            $field = "id,product_id,thum_photo,photo";
            $where = array('product_id' => $id);
            $order = ' order_id asc,id desc';
            $photo_arr = $this->selectProductPhoto($field, $where, '', $order);
            if (!empty($photo_arr)) {
                foreach ($photo_arr as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thum_photo'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['photo'];
                }
            }
        }

        $product['product'] = $product_tmp;
        $product['items'] = $price_result;
        $product['photo'] = $photo_arr_tmp;
        return $product;
    }

    /*
    *商品详情
    */
    function get_product($id, $channel = 'portal', $source = 'pc', $cang_id = 0) {
        $default_channle = $this->config->item('default_channle');
        if (in_array($channel, $default_channle)) {
            $channel = 'portal';
        }
        //获取商品基础信息
        $product = array();
        $where_in = array();
        $order = ' order_id desc,id desc';
        $field = "id,product_name,discription,photo,thum_photo,bphoto,template_id";
        switch ($source) {
            case 'pc':
                $field .= ",online";
                break;
            case 'app':
                $field .= ",app_online as online";
                break;
            case 'wap':
                $field .= ",mobile_online as online";
                break;
            default:
                $field .= ",app_online as online";
                break;
        }
        $field .= ",offline,send_region,free,consumer_tips,op_place,op_detail_place,op_size,tag_id,parent_id,product_desc,lack,use_store,sweet,store,op_weight,cart_tag,jf_limit,card_limit, tag_begin_time, tag_end_time, cang_id";
        $where = array('id' => $id);
        if ($channel == 'portal') {
            $where['channel'] = 'portal';
        } else {
            $where_in[] = array(
                'key' => 'channel',
                'value' => array('portal', $channel)
            );
        }
        $result_array = $this->selectProducts($field, $where, $where_in, $order);

        $result = $result_array[0];

        if (!empty($result['cart_tag']) and $result['tag_begin_time'] > 0 and $result['tag_end_time'] > 0) {
            $iNowUnixTime = $_SERVER['REQUEST_TIME'];

            if ($iNowUnixTime < $result['tag_begin_time'] or $iNowUnixTime > $result['tag_end_time']) {
                $result['cart_tag'] = '';
            }
        }

        unset($result['tag_begin_time'], $result['tag_end_time']);

        if (empty($result)) {
            return array('code' => '300', 'msg' => '该商品已售罄');
        } else if (!$result['online'] && !$result['offline'] && !$result['free']) {
            if ($id == 4070) {
                return array('code' => '300', 'msg' => '亲，加州樱桃5月4日起开售哦，敬请期待～');
            } elseif ($id == 4351) {
                if (date('Y-m-d') < '2015-06-15') {
                    return array('code' => '300', 'msg' => '亲，1元巨无霸金果6月15日起开售哦，敬请期待～');
                } else
                    return array('code' => '300', 'msg' => '亲，请点击app首页新鲜点进行购买～');
            } else {
                return array('code' => '300', 'msg' => '该商品已售罄');
            }
        } else if (!$result['online'] && $source != 'app') {
            return array('code' => '300', 'msg' => '该商品已售罄');
        }
        // fix 商品详情商品属性表格 <style><div><dl><dt><dd>
        $allowTags = '<img><style><div><dl><dt><dd>';
        $result['consumer_tips'] = trim(str_replace('&nbsp;', '', strip_tags($result['consumer_tips'], $allowTags)));
        $result['discription'] = trim(str_replace('&nbsp;', '', strip_tags($result['discription'], $allowTags)));

        // fix
        if (!empty($result['consumer_tips']) && $source != 'pc') {
            $result['discription'] = $result['consumer_tips'];
        }

        //cdn
        $pc_url = constant(CDN_URL . rand(1, 9));

        $result['discription'] = str_replace('src="/', 'src="' . $pc_url, $result['discription']);
        $discription = $result['discription'];
        $discription = preg_replace(array('/width=".*?"/', '/height=".*?"/', '/style=".*?"/'), array('', '', ''), $discription);
        $result['discription'] = <<<EOT
<html>
        <head>
                <meta content="width=device-width, initial-scale=1.0,  user-scalable=1;" name="viewport" />
                <style>*{margin:0; padding:0;}
                        .app-detail{padding:0px; margin:0}
                        .app-detail>img{width:100%;}
                </style>
        </head>
        <body><div class="app-detail">$discription</div></body>
</html>
EOT;
        // if ($is_filter){
        //     $result['discription'] = strip_tags($result['discription'],'<img>');
        //     preg_match_all('/src=\"(.*?)\"/',$result['discription'],$imglist_result);
        //     $result['discription'] = $imglist_result[1];
        // }
        // $result['region'] = '';
        // $region_arr = array_flip($this->config->item('str_area_refelect'));
        // foreach (unserialize($result['send_region']) as $key => $value) {
        //     $result['region'] .= $region_arr[$value].',';
        // }
        // $result['region'] = trim($result['region'],',');
        unset($result['online'], $result['offline']);
        $result['prodcut_desc'] = $result['product_desc'];
        $product_tmp = $result;

        //获取价格
        $field = "id,mobile_price,price,volume,id,product_no,product_id,old_price,unit,stock,over_time,start_time";
        $where = array('product_id' => $id, 'sku_online' => 1);
        $order = ' order_id asc,id desc';
        $price_result = $this->selectProductPrice($field, $where, '', $order, '', $cang_id);
        if (!empty($price_result)) {
            foreach ($price_result as $key => $value) {
                $sku_info = $this->getRedisProductStock($value['id'], $cang_id);
                $price_result[$key]['stock'] = $sku_info['stock'];
                if ($value['mobile_price'] > 0) {
                    $price_result[$key]['price'] = $value['mobile_price'];
                }
                if ($value['old_price'] <= 0) {
                    $price_result[$key]['old_price'] = '0';
                }
                unset($price_result[$key]['mobile_price']);
            }
        }

        // 获取产品模板图片
        if ($product_tmp['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product_tmp['template_id']);
            if (isset($templateImages['main'])) {
                $product_tmp['bphoto'] = $templateImages['main']['big_image'];
                $product_tmp['photo'] = $templateImages['main']['image'];
                $product_tmp['thum_photo'] = $templateImages['main']['thumb'];
            }
        }
        //获取图片
        $photo_arr_tmp = array();
        $photo_arr_tmp[0]['thum_photo'] = $product_tmp['thum_photo'] = PIC_URL . $product_tmp['thum_photo'];
        $photo_arr_tmp[0]['photo'] = $product_tmp['photo'] = PIC_URL . $product_tmp['photo'];
        $photo_arr_tmp[0]['big_photo'] = $product_tmp['bphoto'] = PIC_URL . $product_tmp['bphoto'];

        if ($product_tmp['template_id']) {
            if (isset($templateImages['detail'])) {
                foreach ($templateImages['detail'] as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thumb'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['image'];
                    $photo_arr_tmp[$key_v]['big_photo'] = PIC_URL . $value['big_image'];
                }
            }
        } else {
            $field = "id,product_id,thum_photo,photo,bphoto";
            $where = array('product_id' => $id);
            $order = ' order_id asc,id desc';
            $photo_arr = $this->selectProductPhoto($field, $where, '', $order);
            if (!empty($photo_arr)) {
                foreach ($photo_arr as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thum_photo'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['photo'];
                    $photo_arr_tmp[$key_v]['big_photo'] = PIC_URL . $value['bphoto'];
                }
            }
        }

        $product['product'] = $product_tmp;
        $product['items'] = $price_result;
        $product['photo'] = $photo_arr_tmp;
        $product['share_url'] = "http://m.fruitday.com/detail/index/" . $id;
        return $product;
    }


    function get_price_info($id, $cang_id = 0) {
        //获取价格
        $field = "id,mobile_price,price,volume,id,product_no,product_id,old_price,unit,stock";
        $where = array('product_id' => $id);
        $order = ' order_id asc,id desc';
        $price_result = $this->selectProductPrice($field, $where, '', $order, '', $cang_id);
        if (!empty($price_result)) {
            foreach ($price_result as $key => $value) {
                $sku_info = $this->getRedisProductStock($value['id'], $cang_id);
                $price_result[$key]['stock'] = $sku_info['stock'];
                if ($value['mobile_price'] > 0) {
                    $price_result[$key]['price'] = $value['mobile_price'];
                }
                if ($value['old_price'] <= 0) {
                    $price_result[$key]['old_price'] = '0';
                }
                unset($price_result[$key]['mobile_price']);
            }
        }
        return $price_result;
    }

    /*
    *商品描述
    */
    function get_pro_desc($id, $is_filter = false) {
        $field = "discription";
        $where = array('id' => $id);
        $order = ' order_id asc,id desc';
        $result_array = $this->selectProducts($field, $where, '', $order);

        $result = $result_array[0];
        if (empty($result)) {
            return array('code' => '300', 'msg' => '产品未上架');
        }
        $result['discription'] = str_replace('src="/', 'src="' . PIC_URL, $result['discription']);
        if ($is_filter) {
            $result['discription'] = strip_tags($result['discription'], '<img>');
            preg_match_all('/src=\"(.*?)\"/', $result['discription'], $imglist_result);
            $result['discription'] = $imglist_result[1];
        }
        return $result;
    }

    /*
    *获取分类商品
    */
    function get_by_category($class_id, $sort = 0, $region = "", $page_size = 10, $curr_page = 0, $source = '', $channel = 'portal', $cang_id = '') {
        return $this->get_products(array("class_id" => $class_id, "sort" => $sort, "region" => $region, "page_size" => $page_size, "curr_page" => $curr_page, "source" => $source, "channel" => $channel, "cang_id" => $cang_id));
    }

    /*
    *搜索
    */
    function search($keyword, $sort = 0, $region = "", $page_size = 10, $curr_page = 0, $source = '', $channel = 'portal', $cang_id = '') {
        return $this->get_products(array("keyword" => $keyword, "sort" => $sort, "region" => $region, "page_size" => $page_size, "curr_page" => $curr_page, "source" => $source, "channel" => $channel, "cang_id" => $cang_id));
    }

    /*
    *限时惠商品
    */
    public function xsh_product($product_id, $channel = 'portal', $source, $discare_online = 1, $cang_id = 0) {
        $current_time = Date('Y-m-d H:i:s');
        $this->db->select('id,product_name,summary,thum_photo,photo,long_photo,id,yd,types,lack,maxgifts,parent_id, gift_photo,use_store,discription,product_desc,op_detail_place,xsh_limit,template_id');
        $this->db->from('product as p');
        if ($is_offline) {
            $this->db->where(array('offline' => 1));
        } elseif ($discare_online == 1) {
            switch ($source) {
                case 'pc':
                    $online_key = 'online';
                    break;
                case 'app':
                    $online_key = 'app_online';
                    break;
                case 'wap':
                    $online_key = 'wap_online';
                    break;
                default:
                    $online_key = 'online';
                    break;
            }
            $this->db->where(array($online_key => 1, 'xsh' => 1, 'id' => $product_id));
        } else {
            $this->db->where(array('xsh' => 1, 'id' => $product_id));
        }
        $default_channle = $this->config->item('default_channle');
        if (in_array($channel, $default_channle)) {
            $channel = 'portal';
        }
        if ($channel == 'portal' || empty($channel)) {
            $this->db->where("channel", 'portal');
        } else {
            $this->db->where_in('channel', array('portal', $channel));
        }
        $query = $this->db->get();
        $result = $query->result_array();
        $product_id_tmp = array();
        foreach ($result as $key => $value) {
            $product_id_tmp[] = $value['id'];
        }

        if (empty($product_id_tmp)) {
            return array();
        }

        $this->db->select('product_id,price,stock,old_price,volume,id as price_id,over_time,start_time,product_no');
        $this->db->from('product_price');
        $this->db->where_in('product_id', $product_id_tmp);
        $this->db->order_by('start_time', 'asc');
        $this->db->order_by('order_id', 'desc');
        $this->db->order_by('id', 'desc');
        $this->db->group_by('product_id');
        $price_result = $this->db->get()->result_array();
        $price_result_tmp = array();
        foreach ($price_result as $key => $value) {
            $sku_info = $this->getRedisProductStock($value['price_id'], $cang_id);
            $value['stock'] = $sku_info['stock'];
            $price_result_tmp[$value['product_id']] = $value;
        }

        $result_array = array();
        foreach ($result as $key => $value) {
            if (isset($price_result_tmp[$value['id']])) {
                $result_array[] = array_merge($result[$key], $price_result_tmp[$value['id']]);

            }
        }

        $xsh_result = array();
        $field = array('price', 'mem_lv', 'mem_lv_price', 'can_mem_buy', 'stock', 'old_price', 'volume',
            'price_id', 'product_name', 'summary', 'thum_photo', 'photo', 'id', 'yd', 'types', 'lack',
            'maxgifts', 'parent_id', 'gift_photo', 'use_store', 'start_time', 'over_time', 'discription', 'long_photo', 'product_desc', 'op_detail_place', 'xsh_limit', 'product_no');
        $curr_time = date("Y-m-d H:i:s");
        foreach ($result_array as $key => $value) {
            // 获取产品模板图片
            if ($value['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $value['photo'] = $templateImages['main']['image'];
                    $value['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

            $value = array_intersect_key($value, array_flip($field));
            $value['curr_time'] = $curr_time;
            $value['thum_photo'] = PIC_URL . $value['thum_photo'];
            $value['photo'] = PIC_URL . $value['photo'];
            $value['long_photo'] = PIC_URL . $value['long_photo'];
            $value['description'] = str_replace('src="/', 'src="' . PIC_URL, $value['discription']);
            $value['description'] = strip_tags($value['description'], '<img>');
            $value['prodcut_desc'] = $value['product_desc'];
            unset($value['discription']);
            $xsh_result[] = $value;
        }
        return $xsh_result[0];
    }

    /*
    *推荐广告位
    */
    private function get_recommend_page($id) {
        $this->db->select('photo,price,title,type,target_id,description');
        $this->db->from('appbanner');
        $this->db->where('id', $id);
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *搜索热门关键字
    */
    function get_search_key() {
        $this->db->select('setting');
        $this->db->from('setting');
        $this->db->where('id', '1');
        $query = $this->db->get();
        $result = $query->row_array();
        $setting_arr = unserialize($result['setting']);
        $key_arr = explode(',', $setting_arr['web_hotkeyword']);
        $key_arr[] = $setting_arr['web_searchkeyword'];
        // echo '<pre>';print_r($key_arr);
        return $key_arr;
    }

    function get_productsBak($params = array()) {
        $where = array();
        $where_in = array();
        $order = array();
        $like = array();
        $limit = array();

        if (!isset($params['product_id_arr'])) {
            $params['product_id_arr'] = array();
        }
        $is_offline = false;
        if (isset($params['keyword']) && $params['keyword']) {
            $keyword = urldecode($params['keyword']);
            // $offline_res = $this->selectOffline();
            // $offline_active = empty($offline_res) ? array() : array_column($offline_res,'product_id','key_word');
            // if(isset($offline_active[$keyword])){
            //     $product_ids = explode(',',$offline_active[$keyword]);
            //     $is_offline = true;
            //     $params['product_id_arr'] = array_merge($params['product_id_arr'] ,$product_ids);
            // }else{
            //or会影响and查询,故以下优化
            $p_like = array('product_name' => $keyword);
            $p_or_like = array('tags' => $keyword);
            $p_or_like2 = array('tags_extra' => $keyword);
            $like_product_ids_res = $this->selectProducts('id', '', '', '', '', $p_like, $p_or_like, '', $p_or_like2);
            if (!empty($like_product_ids_res)) {
                $like_product_ids = array_column($like_product_ids_res, 'id');
                $params['product_id_arr'] = array_merge($params['product_id_arr'], $like_product_ids);
            } else {
                return array();
            }
            // }
        }

        $field = "product.id,product_name,thum_photo,photo,middle_photo,promotion_photo,middle_promotion_photo,thum_promotion_photo,types as product_types,product_desc,long_photo,op_detail_place,op_size,op_occasion,summary,lack,use_store,cart_tag, tag_begin_time, tag_end_time , cang_id, template_id";
        if ($is_offline) {
            $where['offline'] = 1;
        } else {
            switch ($params['source']) {
                case 'pc':
                    $online_key = 'online';
                    break;
                case 'app':
                    $online_key = 'app_online';
                    break;
                case 'wap':
                    $online_key = 'mobile_online';
                    break;
                default:
                    $online_key = 'online';
                    break;
            }
            $where[$online_key] = 1;
            if ($params['show_tuan'] == false) {
                $where['is_tuan'] = 0;
            }
//            $where['xsh'] = 0;
        }

        if (isset($params['is_enterprise_pro']) && $params['is_enterprise_pro'] == 1) {
            unset($where['online']);
            unset($where['xsh']);
            $where['free'] = 1;
        }

        if (isset($params['product_id_arr']) && $params['product_id_arr']) {
            $where_in[] = array('key' => 'id', 'value' => $params['product_id_arr']);
        }
        $default_channle = $this->config->item('default_channle');
        if (in_array($params['channel'], $default_channle)) {
            $params['channel'] = 'portal';
        }
        if ($params['channel'] == 'portal' || empty($params['channel'])) {
            $where['channel'] = 'portal';
        } else {
            $where_in[] = array('key' => 'channel', 'value' => array('portal', $channel));
        }
        if ($params['sort'] !== 'none') {
            $order = $this->_parse_sort_method($params['sort']);
            if ($params['sort'] == 2 || $params['sort'] == 3) {
                $join[] = array('table' => 'product_price', 'field' => 'product_price.product_id=product.id', 'type' => 'inner');
            }
        }
        if (isset($params['page_size'])) {
            $limits = array('curr_page' => $params['curr_page'], 'page_size' => $params['page_size']);
        }
        if (isset($params['class_id']) && $params['class_id']) {
            $join[] = array('table' => 'pro_class', 'field' => 'pro_class.product_id=product.id', 'type' => 'inner');
            $where['pro_class.class_id'] = $params['class_id'];
        }
        if (isset($params['tag_id']) && $params['tag_id']) {
//            $where['product.tag_id'] = $params['tag_id'];
            $tagIds = is_array($params['tag_id']) ? $params['tag_id'] : array($params['tag_id']);
            $where_in[] = array('key' => 'product.tag_id', 'value' => $tagIds);
        }

        if (isset($params['op_detail_place']) && $params['op_detail_place']) {
            $where['op_detail_place'] = $params['op_detail_place'];
        }
        if (isset($params['op_occasion']) && $params['op_occasion']) {
            $where['op_occasion'] = $params['op_occasion'];
        }
        if (isset($params['op_size']) && $params['op_size']) {
            $where['op_size'] = $params['op_size'];
        }

        //仓储设置
        $or_like = '';
        $or_like2 = '';

        $is_enable_cang = $this->config->item('is_enable_cang');
        if (isset($params['cang_id']) && $params['cang_id'] && $is_enable_cang == 1) {
            $where['(cang_id LIKE \'' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . '\' OR cang_id = \'' . $params['cang_id'] . '\')'] = null;
        } else {
            if (isset($params['region']) && $params['region']) {
                $like['send_region'] = '"' . $params['region'] . '"';
            }
        }

        $result = $this->selectProducts($field, $where, $where_in, $order, $limits, $like, $or_like, $join, $or_like2);
        if (empty($result)) {
            return array();
        }

        $product_id_tmp = array_column($result, 'id');

        $field = "price,mobile_price,stock,volume,id as price_id,product_no,product_id,old_price,sku_online";
        $where_in['price'] = array('key' => 'product_id', 'value' => $product_id_tmp);
        $order = ' order_id asc,id desc';
        if ($params['sort'] !== 'none' && ($params['sort'] == 2 || $params['sort'] == 3)) {
            $order = 'price desc';
        }
        $filterPrice = array();
        $filterPrice['sku_online ='] = 1;
        if (isset($params['price']) && $params['price']) {
            $temp = explode('T', $params['price']);
            $filterPrice['price >='] = $temp[0] ?: 0;
            $filterPrice['price <'] = $temp[1] ?: 10000;
        }
        $price_result = $this->selectProductPrice($field, $filterPrice, $where_in['price'], $order, '', $params['cang_id']);

        if (isset($params['multi_item']) && $params['multi_item']) {
            foreach ($price_result as $item) {
                $price_result_tmp[$item['product_id']]['items'][] = $item;
            }
        } else {
            $price_result_tmp = array_column(array_reverse($price_result), null, 'product_id');
        }

        $result_array = array();
        foreach ($result as $key => $value) {
            if (isset($price_result_tmp[$value['id']])) {
                $result_array[] = array_merge($result[$key], $price_result_tmp[$value['id']]);
            }
        }

        //预售
        $presell = $this->get_presell_list();
        $pre_ids = array_column($presell, 'product_id');

        foreach ($result_array as $key => $value) {
            if (!empty($value['cart_tag']) and $value['tag_begin_time'] > 0 and $value['tag_end_time'] > 0) {
                $iNowUnixTime = $_SERVER['REQUEST_TIME'];

                if ($iNowUnixTime < $value['tag_begin_time'] or $iNowUnixTime > $value['tag_end_time']) {
                    $result_array[$key]['cart_tag'] = '';
                }
            }

            unset($result_array[$key]['tag_begin_time'], $result_array[$key]['tag_end_time']);


            if ($value['mobile_price'] > 0) {
                $result_array[$key]['price'] = $value['mobile_price'];
            }
            if ($value['old_price'] <= 0) {
                $result_array[$key]['old_price'] = '0';
            }
            unset($price_result[$key]['mobile_price']);

            //cdn
            $pc_url = constant(CDN_URL . rand(1, 9));

            // 获取产品模板图片
            if ($value['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value['template_id']);
                if (isset($templateImages['main'])) {
                    $value['bphoto'] = $templateImages['main']['big_image'];
                    $value['photo'] = $templateImages['main']['image'];
                    $value['middle_photo'] = $templateImages['main']['middle_image'];
                    $value['thum_photo'] = $templateImages['main']['thumb'];
                    $value['thum_min_photo'] = $templateImages['main']['small_thumb'];
                }
                if (isset($templateImages['whitebg'])) {
                    $value['promotion_photo'] = $templateImages['whitebg']['image'];
                    $value['middle_promotion_photo'] = $templateImages['whitebg']['middle_image'];
                    $value['thum_promotion_photo'] = $templateImages['whitebg']['thumb'];
                }
            }
            $result_array[$key]['thum_photo'] = $pc_url . $value['thum_photo'];
            $result_array[$key]['photo'] = $pc_url . $value['middle_photo'];
            $result_array[$key]['middle_photo'] = $pc_url . $value['middle_photo'];
            $result_array[$key]['long_photo'] = $pc_url . $value['long_photo'];
            $result_array[$key]['product_types'] = unserialize($value['product_types']);
            $result_array[$key]['prodcut_desc'] = $value['product_desc'];
            $result_array[$key]['promotion_photo'] = empty($value['promotion_photo']) ? '' : $pc_url . $value['promotion_photo'];
            $result_array[$key]['middle_promotion_photo'] = empty($value['middle_promotion_photo']) ? '' : $pc_url . $value['middle_promotion_photo'];
            $result_array[$key]['thum_promotion_photo'] = empty($value['thum_promotion_photo']) ? '' : $pc_url . $value['thum_promotion_photo'];

            //规格
            if ($params['source'] == 'app' || $params['source'] == 'wap') {
                foreach ($price_result as $item) {
                    if ($item['product_id'] == $value['id'] && $item['sku_online'] == 1) {
                        $result_array[$key]['items'][] = $item;
                    }
                }
            }

            //预售
            if (in_array($result_array[$key]['id'], $pre_ids)) {
                $result_array[$key]['is_hidecart'] = 1;
            } else {
                $result_array[$key]['is_hidecart'] = 0;
            }
        }
        if ($sort === 'none') {
            $result_tmp = array();
            foreach ($product_id_arr as $key => $value) {
                foreach ($result_array as $k => $v) {
                    if ($v['id'] == $value) {
                        $result_tmp[] = $v;
                    }
                }
            }
            $result_array = $result_tmp;
        }
        return $result_array;
    }

    function _parse_sort_method($sort_input = 0) {
        $sort = preg_match('/^[0-4]$/', $sort_input) ? $sort_input : 0;
        switch ($sort) {
            case 1:
                $sortMethod = "sales desc";
                break;
            case 2:
                $sortMethod = "price asc";
                break;
            case 3:
                $sortMethod = "price desc";
                break;
            case 4:
                $sortMethod = "viewed desc";
                break;
            default:
                $sortMethod = "order_id desc, id desc";
                break;
        }
        return $sortMethod;
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderby = '') {
        $this->db->select($cols);
        $this->_filter($filter);
        $this->db->from('product');
        if ($orderby) $this->db->order_by($orderby);
        if ($limit < 0) $limit = '4294967295';
        $this->db->limit($limit, $offset);
        $list = $this->db->get()->result_array();
        return $list ? $list : array();
    }

    public function dump($filter, $cols = '*') {
        $this->db->select($cols);
        $this->_filter($filter);
        $this->db->from('product');
        $this->db->limit(1, 0);
        $row = $this->db->get()->row_array();
        return $row;
    }

    public function _filter($filter = array()) {
        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $this->db->where_in($key, $value);
            } else {
                $this->db->where($key, $value);
            }
        }
    }

    public function get_skus($product_id, $cang_id = 0) {
        $skus = $this->db->select('*')
            ->from('product_price')
            ->where_in('product_id', $product_id)
            ->get()
            ->result_array();
        foreach ($skus as $key => $value) {
            $sku_info = $this->getRedisProductStock($value['id'], $cang_id, 0, $value['product_no']);
            $skus[$key]['stock'] = $sku_info['stock'];
        }
        return $skus;
    }

    /*
    *修改商品库存
    */
    public function reduce_stock($sku_id, $qty, $cang_id = 0, $hy_warehouse_id = 0, $product_no = '') {
        return $this->reduceRedisStock($sku_id, $qty, $cang_id, $hy_warehouse_id, $product_no);

        $this->db->query('update ttgy_product_price set stock = stock - ' . $qty . ' where id=' . $sku_id . ' and stock>0');
        if (!$this->db->affected_rows()) {
            return false;
        }
        return true;
    }

    /*
    *修改商品库存
    */
    public function return_stock($sku_id, $qty, $cang_id = 0, $hy_warehouse_id = 0, $product_no = '') {
        return $this->returnRedisStock($sku_id, $qty, $cang_id, $hy_warehouse_id, $product_no);

        $this->db->query('update ttgy_product_price set stock = stock + ' . $qty . ' where id=' . $sku_id . ' and stock>0');
        if (!$this->db->affected_rows()) {
            return false;
        }
        return true;
    }

    /**
     * 获取商品带SKU
     *
     * @return void
     * @author
     **/
    public function getProductSkus($product_id, $cang_id = 0, $product_type = 0) {
        static $products;

        if ($products[$product_id]) return $products[$product_id];
        if ($product_type) {
            $where = array('id' => $product_id, 'product_type' => $product_type);
        } else {
            $where = array('id' => $product_id);
        }

        $p = $this->db->select('online,mobile_online,app_online,lack,xsh_display,send_region,pay_limit,iscard,product_name,device_limit,card_limit,jf_limit,pay_limit,first_limit,active_limit,delivery_limit,group_limit,pay_discount_limit,free,offline,type,free_post,free_post,is_tuan,use_store,xsh,xsh_limit,ignore_order_money,group_pro,thum_photo,thum_min_photo,photo,middle_photo,bphoto,thum_promotion_photo,middle_promotion_photo,thum_min_promotion_photo,can_buy_one,is_xsh_time_limit,expect,cart_tag,cang_id,product_desc,tag_id, tag_begin_time, tag_end_time, template_id')
            ->from('product')
            ->where($where)
            ->get()
            ->row_array();
        if (!$p) return array();

        if (!empty($p['cart_tag']) and $p['tag_begin_time'] > 0 and $p['tag_end_time'] > 0) {
            $iNowUnixTime = $_SERVER['REQUEST_TIME'];

            if ($iNowUnixTime < $p['tag_begin_time'] or $iNowUnixTime > $p['tag_end_time']) {
                $p['cart_tag'] = '';
            }
        }

        unset($p['tag_begin_time'], $p['tag_end_time']);

        $s = $this->db->select('*')
            ->from('product_price')
            ->where('product_id', $product_id)
            //->where('sku_online',1)
            ->get()
            ->result_array();
        if ($s) {
            foreach ($s as $key => $value) {
                $sku_info = $this->getRedisProductStock($value['id'], $cang_id, 0, $value['product_no']);
                $value['stock'] = $sku_info['stock'];
                $p['skus'][$value['id']] = $value;
            }
        }

        $products[$product_id] = $p;

        return $products[$product_id];
    }

    /*
    *获取企业专享商品
    */
    function get_enterprise_products($uid, $sort = 0, $region = "", $source = "") {
        $this->load->model('banner_model');

        $sql = "select e.product_id,e.photo,e.company_name,e.rotation_id from ttgy_enterprise as e join ttgy_user as u on u.enter_id=e.id where u.id=" . $uid;
        $enter_arr = $this->db->query($sql)->row_array();
        if (empty($enter_arr)) {
            return array('code' => '300', 'msg' => '对不起，您还不是企业认证会员。企业客户请点击“去认证”，非企业客户可点击首页右上方“企业合作”进行申请。', 'action' => 'bind_enterprise');
        }

        $product_id_arr = explode(',', $enter_arr['product_id']);
        if ($sort == 0) {
            $sort = 'none';
        }

        $filed = array(
            "product_id_arr" => $product_id_arr,
            "sort" => $sort,
            "region" => $region,
            "is_enterprise_pro" => '1',
        );
        $product_arr = $this->get_products($filed);

        $return_result['title'] = $enter_arr['company_name'];
        if (isset($enter_arr['photo']) && !empty($enter_arr['photo'])) {
            $return_result['page_photo'] = PIC_URL . $enter_arr['photo'];
        }
        $return_result['products'] = $product_arr;

        //轮播位
        if (!empty($enter_arr['rotation_id'])) {
            $rot_data = array(
                'region_id' => $region,
                'source' => $source,
                'ids' => explode(",", $enter_arr['rotation_id']),
            );
            $return_result['rotation'] = $this->banner_model->get_banner_list($rot_data);
        }
        return $return_result;
    }

    /*
    *查询关注
    */
    function select_user_mark($fields, $where) {
        $this->db->select($fields);
        $this->db->from('user_mark');
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *增加关注
    */
    function add_user_mark($insert_data) {
        $this->db->insert("user_mark", $insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
    *取消关注
    */
    function delete_user_mark($where) {
        $this->db->where($where);
        $this->db->delete('user_mark');
    }

    /*
    *关注商品的相关商品
    */
    function get_marked_products($tag_id, $sort = 0, $region = "", $page_size = 10, $curr_page = 0, $source = 'app') {
        return $this->get_products(array("tag_id" => $tag_id, "sort" => $sort, "region" => $region, "page_size" => $page_size, "curr_page" => $curr_page, "source" => $source, "channel" => $channel));
    }

    /*
    *后台设置的推荐商品
    */
    function get_pro_active() {
        $this->db->select('product_id,type');
        $this->db->from('pro_active');
        $this->db->where_in('type', array('recommend', 'top_recommend'));
        $pro_tag_query = $this->db->get();
        $pro_tag_result = $pro_tag_query->result_array();
        return $pro_tag_result;
    }

    /*
    *自动推荐的商品
    */
    function product_recommend($product_id) {
        $this->db->select('product_tags');
        $this->db->from('product_recommend');
        $this->db->where('product_id', $product_id);
        $pro_rec_query = $this->db->get();
        $pro_rec_result = $pro_rec_query->row_array();
        return $pro_rec_result;
    }

    /*
    *验证推荐商品
    */
    function check_pro_recommend($pro_tag_tmp, $re_key) {
        if (isset($pro_tag_tmp[$re_key]) && !empty($pro_tag_tmp[$re_key])) {
            $recommend_top = $pro_tag_tmp[$re_key];
            $this->db->select('id');
            $this->db->from('product');
            $this->db->where_in('id', $recommend_top);
            $this->db->where(array('online' => 1, 'xsh' => 0));
            $check_pro_query = $this->db->get();
            $check_pro_result = $check_pro_query->result_array();
            $check_pro_tmp = array();
            foreach ($check_pro_result as $key => $value) {
                $check_pro_tmp[] = $value['id'];
            }
            $recommend_result = array_intersect($recommend_top, $check_pro_tmp);
        } else {
            $recommend_result = array();
        }
        return $recommend_result;
    }

    /*
    *验证推荐商品
    */
    function check_recommend($tag_id) {
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->memcached) {
                $this->load->library('memcached');
            }
            $mem_key = 'recommend_pro_' . $tag_id;
            $result = $this->memcached->get($mem_key);
            if ($result) {
                return $result;
            }
        }

        $this->db->select('id');
        $this->db->from('product');
        $this->db->where(array('tag_id' => $tag_id, 'online' => 1, 'xsh' => 0));

        $query = $this->db->get();
        $result = $query->row_array();

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->memcached) {
                $this->load->library('memcached');
            }
            $mem_key = 'recommend_pro_' . $tag_id;
            if (empty($result)) {
                $result = false;
            }
            $this->memcached->set($mem_key, $result['id'], 3600);
        }
        if (empty($result)) {
            return false;
        } else {
            return $result['id'];
        }
    }

    /*
    *专题页
    */
    function get_by_page($page_type, $target_id, $sort = 0, $region = "", $source = "", $channel = 'portal', $product_id = '') {
        $this->load->model('banner_model');

        if (isset($product_id) && $product_id != "" && $product_id != 0 && $page_type == 4) {
            $product_id_arr[0] = $product_id;
        } else {
            $field = 'title,product_id,photo,recommend_id,rotation_id,flash_sale,advance';
            $where = array(
                'id' => $target_id,
                'page_type' => $page_type,
            );
            $result_list = $this->banner_model->selectPage($field, $where);
            $result = $result_list[0];
            $product_id_arr = explode(',', $result['product_id']);
        }

        if ($sort == 0) {
            $sort = 'none';
        }
        $region_to_warehouse = $this->config->item('region_to_cang');
        $cang_id = $region_to_warehouse[$region];
        switch ($page_type) {
            case '1':                   #列表页
                $product_arr = $this->get_products(array("product_id_arr" => $product_id_arr, "sort" => $sort, "region" => $region, "source" => $source, "channel" => $channel));
                $return_result['title'] = $result['title'];
                if (isset($result['photo']) && !empty($result['photo'])) {
                    $return_result['page_photo'] = PIC_URL . $result['photo'];
                }
                $return_result['products'] = sortByArray($product_arr, $product_id_arr, 'id');
                //推荐位
                if (!empty($result['recommend_id'])) {
                    $rec_data = array(
                        'region_id' => $region,
                        'source' => $source,
                        'channel' => $channel,
                        'ids' => array($result['recommend_id']),
                    );
                    $return_result['recommend'] = $this->banner_model->get_banner_list($rec_data);
                }
                //轮播位
                if (!empty($result['rotation_id'])) {
                    $rot_data = array(
                        'region_id' => $region,
                        'source' => $source,
                        'channel' => $channel,
                        'ids' => explode(",", $result['rotation_id']),
                    );
                    $return_result['rotation'] = $this->banner_model->get_banner_list($rot_data);
                }
                break;
            case '4':                   #抢购页
                $return_result = $this->xsh_product($product_id_arr[0], $channel, $source, 0, $cang_id);
                break;
            case '7':   #抢购列表
                $return_result['title'] = $result['title'];
                if (isset($result['photo']) && !empty($result['photo'])) {
                    $return_result['page_photo'] = PIC_URL . $result['photo'];
                }
                if (!empty($product_id_arr)) {
                    foreach ($product_id_arr as $v) {
                        $xsh_pro_info = $this->xsh_product($v, $channel, $source, 0, $cang_id);
                        if (!empty($xsh_pro_info)) {
                            $return_result['products'][] = $xsh_pro_info;
                        }
                    }
                    $return_result['products'] = array_merge($this->qxsort($return_result['products']));
//                    var_dump($return_result['products']); echo "<br>______________-";
//                    var_dump(array_merge($this->qxsort($return_result['products'])));exit;
//                    $return_result['products'] = $this->qxsort($arr_product);
                }
                //轮播位
                if (!empty($result['rotation_id'])) {
                    $rot_data = array(
                        'region_id' => $region,
                        'source' => $source,
                        'channel' => $channel,
                        'ids' => explode(",", $result['rotation_id']),
                    );
                    $return_result['rotations'] = $this->banner_model->get_banner_list($rot_data);
                }
                //秒杀区
                if (!empty($result['flash_sale'])) {
                    $flash_id_arr = explode(',', $result['flash_sale']);
                    foreach ($flash_id_arr as $v) {
                        $pro = $this->xsh_product($v, $channel, $source);
                        $pro['photo'] = $pro['long_photo'];
                        $return_result['flash_sale'][] = $pro;
                    }
                }
                //预告区
                if (!empty($result['advance'])) {
                    $advance_id_arr = explode(',', $result['advance']);
                    foreach ($advance_id_arr as $v) {
                        $return_result['advance'][] = $this->xsh_product($v, $channel, $source, 0, $cang_id);
                    }
                }
                break;

            // 跨境通列表
            case '12':
                $return_result['title'] = $result['title'];
                if (isset($result['photo']) && !empty($result['photo'])) {
                    $return_result['page_photo'] = PIC_URL . $result['photo'];
                }
                if (!empty($product_id_arr)) {
                    foreach ($product_id_arr as $v) {
                        $temp = explode('-', $v);
                        $kjt_pro_info = $this->get_product_kjt($temp[1], $channel, $source, $cang_id);
                        if (!empty($kjt_pro_info)) {
                            $return_result['products'][$temp[0]] = $kjt_pro_info;
                        }
                    }
                }
                break;
            default:
                # code...
                break;
        }
        return $return_result;
    }

    function qxsort($arr) {
        foreach ($arr as $k => $v) {
            if ($v['stock'] == 0 || $v['curr_time'] > $v['over_time']) {
                array_push($arr, $v);
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    function getRedisProductStock($sku_id, $cang_id = 0, $hy_warehouse_id = 0, $product_no = '') {
        if ($this->redis->exists("product:" . $sku_id)) {
            $sku_info = $this->redis->hMget("product:" . $sku_id, array("use_store", "stock", "use_warehouse_store"));
        } else {
            $sku_info = $this->initRedisStock($sku_id);
        }

        if (defined('OPEN_WAREHOUSE_STOCK') && OPEN_WAREHOUSE_STOCK === true) {
            if ($sku_info['use_warehouse_store'] && $sku_info['use_warehouse_store'] == 1) {
                $this->load->model('warehouse_model');
                $this->load->model('product_warehouse_stock_model');
                if (empty($hy_warehouse_id) && $cang_id) {
                    $hy_warehouse_id = $this->warehouse_model->getHypostaticWarehouseID($cang_id); //实体仓ID
                }
                if ($hy_warehouse_id) {
                    $product_no or $product_no = $this->getProductNOBySkuId($sku_id);
                    if ($product_no) {
                        $stock_info = $this->product_warehouse_stock_model->getProductStock($product_no, $hy_warehouse_id);
                        if ($stock_info) {
                            $sku_info['stock'] = $stock_info['stock'];
                        }
                    }
                }
            }
        }
        // fix
        if ($sku_info['use_store'] == 1) {
            $sku_info['stock'] = intval($sku_info['stock']);
        } elseif ($sku_info['stock'] == '') {
            $sku_info['stock'] = null;
        }
        return $sku_info;
    }

    public function reduceRedisStock($sku_id, $qty, $cang_id = 0, $hy_warehouse_id = 0, $product_no = '') {
        if ($this->redis->exists("product:" . $sku_id)) {
            $sku_info = $this->redis->hMget("product:" . $sku_id, array("use_store", "stock"));
        } else {
            $sku_info = $this->initRedisStock($sku_id);
        }
        if ($sku_info['use_store'] == 1) {
            $stock = $sku_info['stock'] - $qty;
            if ($stock < 0) $stock = 0;
            $this->redis->hMset("product:" . $sku_id, array("stock" => $stock));
        }
        $this->load->model('warehouse_model');
        $this->load->model('product_warehouse_stock_model');
        if (empty($hy_warehouse_id) && $cang_id) {
            $hy_warehouse_id = $this->warehouse_model->getHypostaticWarehouseID($cang_id); //实体仓ID
        }
        if ($hy_warehouse_id) {
            $product_no or $product_no = $this->getProductNOBySkuId($sku_id);
            if ($product_no) {
                $this->product_warehouse_stock_model->reduceProductStock($product_no, $qty, $hy_warehouse_id);
            }
        }
        return true;
    }

    public function returnRedisStock($sku_id, $qty, $cang_id = 0, $hy_warehouse_id = 0, $product_no = '') {
        if ($this->redis->exists("product:" . $sku_id)) {
            $sku_info = $this->redis->hMget("product:" . $sku_id, array("use_store", "stock"));
        } else {
            $sku_info = $this->initRedisStock($sku_id);
        }
        if ($sku_info['use_store'] == 1) {
            $stock = $sku_info['stock'] + $qty;
            if ($stock < 0) $stock = 0;
            $this->redis->hMset("product:" . $sku_id, array("stock" => $stock));
        }
        $this->load->model('warehouse_model');
        $this->load->model('product_warehouse_stock_model');
        if (empty($hy_warehouse_id) && $cang_id) {
            $hy_warehouse_id = $this->warehouse_model->getHypostaticWarehouseID($cang_id); //实体仓ID
        }
        if ($hy_warehouse_id) {
            $product_no or $product_no = $this->getProductNOBySkuId($sku_id);
            if ($product_no) {
                $this->product_warehouse_stock_model->returnProductStock($product_no, $qty, $hy_warehouse_id);
            }
        }
        return true;
    }

    private function initRedisStock($sku_id) {
        $this->db->select('product_id,stock');
        $this->db->from('product_price');
        $this->db->where('id', $sku_id);
        $price_result = $this->db->get()->row_array();
        $stock = $price_result['stock'];
        $this->db->select('use_store,use_warehouse_store');
        $this->db->from('product');
        $this->db->where('id', $price_result['product_id']);
        $product_result = $this->db->get()->row_array();
        $use_store = $product_result['use_store'];
        $use_warehouse_store = $product_result['use_warehouse_store'];
        if ($use_store == 0) {
            $stock = '';
        } elseif (!is_numeric($stock)) {
            $stock = 0;
        }
        $sku_info = array("use_store" => $use_store, "stock" => $stock, "use_warehouse_store" => $use_warehouse_store);
        $this->redis->hMset("product:" . $sku_id, $sku_info);
        return $sku_info;
    }

    /*
    *商品详情 - 积分
    */
    function get_point_product($id, $channel = 'portal', $source = 'pc', $cang_id = 0) {
        $default_channle = $this->config->item('default_channle');
        if (in_array($channel, $default_channle)) {
            $channel = 'portal';
        }
        //获取商品基础信息
        $product = array();
        $where_in = array();
        $order = ' order_id desc,id desc';
        $field = "id,product_name,discription,photo,thum_photo,promotion_photo,template_id";
        switch ($source) {
            case 'pc':
                $field .= ",online";
                break;
            case 'app':
                $field .= ",app_online as online";
                break;
            case 'wap':
                $field .= ",mobile_online as online";
                break;
            default:
                $field .= ",app_online as online";
                break;
        }
        $field .= ",offline,send_region,free,consumer_tips,op_place,op_detail_place,op_size,tag_id,parent_id,product_desc,lack,use_store,sweet,store,op_weight";
        $where = array('id' => $id);
        if ($channel == 'portal') {
            $where['channel'] = 'portal';
        } else {
            $where_in[] = array(
                'key' => 'channel',
                'value' => array('portal', $channel)
            );
        }
        $result_array = $this->selectProducts($field, $where, $where_in, $order);

        $result = $result_array[0];
        if (empty($result)) {
            return array('code' => '300', 'msg' => '该商品已售罄');
        }

        // fix 商品详情商品属性表格 <style><div><dl><dt><dd>
        $allowTags = '<img><style><div><dl><dt><dd>';
        $result['consumer_tips'] = trim(str_replace('&nbsp;', '', strip_tags($result['consumer_tips'], $allowTags)));
        $result['discription'] = trim(str_replace('&nbsp;', '', strip_tags($result['discription'], $allowTags)));

        if (!empty($result['consumer_tips'])) {
            $result['discription'] = $result['consumer_tips'];
        }

        //cdn
        $pc_url = constant(CDN_URL . rand(1, 9));

        $result['discription'] = str_replace('src="/', 'src="' . $pc_url, $result['discription']);
        $discription = $result['discription'];
        $discription = preg_replace(array('/width=".*?"/', '/height=".*?"/', '/style=".*?"/'), array('', '', ''), $discription);
        $result['discription'] = <<<EOT
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
        // if ($is_filter){
        //     $result['discription'] = strip_tags($result['discription'],'<img>');
        //     preg_match_all('/src=\"(.*?)\"/',$result['discription'],$imglist_result);
        //     $result['discription'] = $imglist_result[1];
        // }
        // $result['region'] = '';
        // $region_arr = array_flip($this->config->item('str_area_refelect'));
        // foreach (unserialize($result['send_region']) as $key => $value) {
        //     $result['region'] .= $region_arr[$value].',';
        // }
        // $result['region'] = trim($result['region'],',');
        unset($result['online'], $result['offline']);
        $result['prodcut_desc'] = $result['product_desc'];
        $product_tmp = $result;

        //获取价格
        $field = "id,mobile_price,price,volume,id,product_no,product_id,old_price,unit,stock";
        $where = array('product_id' => $id);
        $order = ' order_id asc,id desc';
        $price_result = $this->selectProductPrice($field, $where, '', $order, '', $cang_id);
        if (!empty($price_result)) {
            foreach ($price_result as $key => $value) {
                $sku_info = $this->getRedisProductStock($value['id'], $cang_id);
                $price_result[$key]['stock'] = $sku_info['stock'];
                if ($value['mobile_price'] > 0) {
                    $price_result[$key]['price'] = $value['mobile_price'];
                }
                if ($value['old_price'] <= 0) {
                    $price_result[$key]['old_price'] = '0';
                }
                unset($price_result[$key]['mobile_price']);
            }
        }

        // 获取产品模板图片
        if ($product_tmp['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product_tmp['template_id']);
            if (isset($templateImages['main'])) {
                $product_tmp['photo'] = $templateImages['main']['image'];
                $product_tmp['thum_photo'] = $templateImages['main']['thumb'];
            }
            if (isset($templateImages['whitebg'])) {
                $product_tmp['promotion_photo'] = $templateImages['whitebg']['image'];
            }
        }
        //获取图片
        $photo_arr_tmp = array();
        $photo_arr_tmp[0]['thum_photo'] = $product_tmp['thum_photo'] = PIC_URL . $product_tmp['thum_photo'];
        $photo_arr_tmp[0]['photo'] = $product_tmp['photo'] = PIC_URL . $product_tmp['photo'];
        $photo_arr_tmp[0]['promotion_photo'] = $product_tmp['promotion_photo'] = empty($product_tmp['promotion_photo']) ? '' : PIC_URL . $product_tmp['promotion_photo'];

        if ($product_tmp['template_id']) {
            if (isset($templateImages['detail'])) {
                foreach ($templateImages['detail'] as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thumb'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['image'];
                }
            }
        } else {
            $field = "id,product_id,thum_photo,photo";
            $where = array('product_id' => $id);
            $order = ' order_id asc,id desc';
            $photo_arr = $this->selectProductPhoto($field, $where, '', $order);
            if (!empty($photo_arr)) {
                foreach ($photo_arr as $key => $value) {
                    $key_v = $key + 1;
                    $photo_arr_tmp[$key_v]['thum_photo'] = PIC_URL . $value['thum_photo'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['photo'];
                    $photo_arr_tmp[$key_v]['photo'] = PIC_URL . $value['photo'];
                }
            }
        }

        $product['product'] = $product_tmp;
        $product['items'] = $price_result;
        $product['photo'] = $photo_arr_tmp;
        $product['share_url'] = "http://m.fruitday.com/detail/index/" . $id;
        return $product;
    }

    function get_pro_send_time($pro_ids) {
        if (!is_array($pro_ids)) $pro_ids = array($pro_ids);
        $this->db->select('product_id,delivery_date,validity,delay,default_day');
        $this->db->from('adjust_delivery');
        $this->db->where_in('product_id', $pro_ids);
        return $this->db->get()->result_array();
    }

    /**
     * 获取预售期间的预售商品信息
     */
    public function get_presell_list() {
        $now_data = date("Y-m-d H:i:s");
        $this->db->select('product_id');
        $this->db->from('advance_sales');
        $this->db->where(array('start <=' => $now_data, 'end >=' => $now_data));
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getProductnoByInnerCode($inner_codes) {
        if (empty($inner_codes)) return;
        if (!is_array($inner_codes)) $inner_codes = array($inner_codes);
        $sql = "SELECT code,inner_code FROM ttgy_erp_products where inner_code in('" . implode("','", $inner_codes) . "')";
        $res = $this->db->query($sql)->result_array();
        return $res;
    }

    public function getProductNOBySkuId($sku_id) {
        $product_no = '';
        if ($this->redis->hexists("skuid2productno", $sku_id)) {
            $product_no = $this->redis->hget("skuid2productno", $sku_id);
        }
        $product_no or $product_info = $this->db->select('product_no')->from('product_price')->where(array('id' => $sku_id))->get()->row_array();
        if ($product_info) {
            $product_no = $product_info['product_no'];
            $this->redis->hset("skuid2productno", $sku_id, $product_no);
        }
        return $product_no;
    }


    public function getTemplateId($productId) {
        $this->db->select('template_id');
        $this->db->from('product');
        $this->db->where('id', $productId);
        $result = $this->db->get()->row_array();
        return $result['template_id'];

    }

    public function getProductInfoGroupByTemplate($templateIdGroup, $storeIdGroup, $tmsRegionType, $extraField = '', $where = array(), $offset = 0, $limit = '', $orderBy = '') {
        $this->load->model('b2o_store_product_model');

        $fields = 'b2o_store_product.store_id,product.product_name,product.id,product.cart_tag,product.op_weight,product.card_limit,product.jf_limit,b2o_store_product.price,b2o_store_product.product_desc,product_price.volume,product_price.old_price,b2o_store_product.limit_time_start,b2o_store_product.limit_time_end,b2o_store_product.limit_time_count,b2o_store_product.device_limit,b2o_store_product.stock,product.has_webp,b2o_store_product.delivery_template_id,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end,product.template_id';
        $fields .= ',' . $extraField;
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
            'type' => 'left'
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
            'type' => 'left'
        );

        $filter = array();
        $filter['product.template_id <>'] = 0;
        $filter['b2o_store_product.store_id'] = $storeIdGroup;
        $filter['b2o_store_product.is_hide'] = 0;
        $filter['b2o_store_product.is_app_online'] = 1;
        $filter['b2o_store_product.send_region_type >='] = $tmsRegionType;
        if (!empty($templateIdGroup)) {
            $filter['product.template_id'] = (array)$templateIdGroup;
        }
        $filter = array_merge($filter, $where);
        $result = $this->b2o_store_product_model->getProductList($fields, $filter, $join, array(), $orderBy, $limit, $offset);
        return $result;
    }


    public function getGiftInfoGroupByProductAndStore($productId, $storeIdGroup, $tmsRegionType, $extraField = '', $offset = 0, $limit = '', $orderBy = '') {
        $this->load->model('b2o_store_product_model');

        $fields = 'b2o_store_product.store_id,product.product_name,product.id,product.cart_tag,product.op_weight,product.card_limit,product.jf_limit,b2o_store_product.price,b2o_store_product.product_desc,product_price.volume,product_price.old_price,b2o_store_product.limit_time_start,b2o_store_product.limit_time_end,b2o_store_product.limit_time_count,b2o_store_product.device_limit,b2o_store_product.stock,product.has_webp,b2o_store_product.delivery_template_id,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end';
        $fields .= ',' . $extraField;
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
            'type' => 'left'
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
            'type' => 'left'
        );

        $filter = array();
        $filter['product.template_id <>'] = 0;
        $filter['b2o_store_product.store_id'] = (array)$storeIdGroup;
        $filter['b2o_store_product.send_region_type >='] = $tmsRegionType;
        $filter['product.id'] = $productId;

        $result = $this->b2o_store_product_model->getProductList($fields, $filter, $join, array(), $orderBy, $limit, $offset);
        return $result;
    }

    public function getProductInfoGroupByProductAndStore($productId, $storeIdGroup, $tmsRegionType, $extraField = '', $offset = 0, $limit = '', $orderBy = '') {
        $this->load->model('b2o_store_product_model');

        $fields = 'b2o_store_product.store_id,product.product_name,product.id,product.cart_tag,product.op_weight,product.card_limit,product.jf_limit,b2o_store_product.price,b2o_store_product.product_desc,product_price.volume,product_price.old_price,b2o_store_product.limit_time_start,b2o_store_product.limit_time_end,b2o_store_product.limit_time_count,b2o_store_product.device_limit,b2o_store_product.stock,product.has_webp,b2o_store_product.delivery_template_id,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end,b2o_store_product.is_hide';
        $fields .= ',' . $extraField;
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
            'type' => 'left'
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
            'type' => 'left'
        );

        $filter = array();
        $filter['product.template_id <>'] = 0;
        $filter['b2o_store_product.store_id'] = (array)$storeIdGroup;
        $filter['b2o_store_product.is_app_online'] = 1;
        $filter['b2o_store_product.send_region_type >='] = $tmsRegionType;
        $filter['product.id'] = $productId;

        $result = $this->b2o_store_product_model->getProductList($fields, $filter, $join, array(), $orderBy, $limit, $offset);
        return $result;
    }

    public function getProductInfoGroupByProductId($productIdGroup, $storeIdGroup, $tmsRegionType, $extraField = '', $offset = 0, $limit = '', $orderBy = '', $whereExtra = array()) {
        $this->load->model('b2o_store_product_model');

        $fields = 'b2o_store_product.store_id,product.product_name,product.id,product.cart_tag,product.op_weight,product.card_limit,product.jf_limit,b2o_store_product.price,b2o_store_product.product_desc,product_price.volume,product_price.old_price,product_price.over_time,product_price.start_time,b2o_store_product.stock,b2o_store_product.delivery_template_id,product.has_webp,b2o_store_product.delivery_template_id,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end,product.template_id';
        $fields .= ',' . $extraField;
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
            'type' => 'left'
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
            'type' => 'left'
        );

        $filter = array();
        $filter['product.template_id <>'] = 0;
        $filter['b2o_store_product.store_id'] = $storeIdGroup;
        $filter['b2o_store_product.is_app_online'] = 1;
        $filter['b2o_store_product.send_region_type >='] = $tmsRegionType;
        $filter['product.id'] = (array)$productIdGroup;
        $filter = array_merge($filter, $whereExtra);

        $result = $this->b2o_store_product_model->getProductList($fields, $filter, $join, array(), $orderBy, $limit, $offset);
        return $result;
    }

    function get_products($params = array()) {

    }

    /**
     * 获取多个门店同一个产品的一条产品信息
     *
     * @param string|array $stores
     * @param string       $source
     *
     * @return array
     */
    public function getProductByStore($stores, $regionType, $source = 'app', $filter = [])
    {
        $this->load->model('b2o_store_product_model');

        if (is_string($stores)) {
            $stores = explode(',', $stores);
        }

        switch ($source) {
            case 'app':
                $onlineField = 'b2o_store_product.is_app_online';
                break;
            case 'wap':
                $onlineField = 'b2o_store_product.is_wap_online';
                break;
            case 'pc':
                $onlineField = 'b2o_store_product.is_pc_online';
                break;
        }

        $fields = [
            "b2o_store_product.product_desc", "b2o_store_product.price", "b2o_store_product.store_id",
            "b2o_store_product.promotion_tag", "b2o_store_product.promotion_tag_start", "b2o_store_product.promotion_tag_end", "b2o_store_product.special_tag",
            "b2o_store_product.product_id", "b2o_store_product.stock", "b2o_store_product.delivery_template_id",
            "product.product_name", "product.promotion_photo",
            "product_price.volume", "product_price.old_price", "product.scene_tag", "product.has_webp", "product.template_id", "product_price.inner_code", "product_price.product_no",
        ];

        $filter['b2o_store_product.store_id'] = $stores;
        $filter['b2o_store_product.send_region_type >='] = $regionType;
        $filter['b2o_store_product.is_hide'] = 0;
        $filter[$onlineField] = 1;

        $join = [
            [
                'name' => 'product',
                'cond' => 'b2o_store_product.product_id = product.id',
                'type' => 'left',
            ],
            [
                'name' => 'product_price',
                'cond' => 'product.id = product_price.product_id',
                'type' => 'left',
            ],
        ];

        $results = $this->b2o_store_product_model->getProductList($fields, $filter, $join);
        return $results ?: [];
    }

    public function getGiftByStore($product_id = null, $stores, $tmsRegionType, $source = 'app', $filter = [], $order_by = null, $limit) {
        $this->load->model('b2o_store_product_model');

        if (is_string($stores))
            $stores = explode(',', $stores);


        $fields = [
            "product.id", "b2o_store_product.product_desc", "b2o_store_product.price", "b2o_store_product.store_id",
            "b2o_store_product.promotion_tag", "b2o_store_product.promotion_tag_start", "b2o_store_product.promotion_tag_end", "b2o_store_product.special_tag",
            "b2o_store_product.product_id", "product.product_name", "product.promotion_photo",
            "product_price.volume", "product_price.old_price", "product.has_webp"
        ];

        if ($product_id)
            $filter['product.id'] = $product_id;

        $filter['b2o_store_product.store_id'] = $stores;
        $filter['b2o_store_product.send_region_type >='] = $tmsRegionType;

        $join = [
            [
                'name' => 'product',
                'cond' => 'b2o_store_product.product_id = product.id',
                'type' => 'left',
            ],
            [
                'name' => 'product_price',
                'cond' => 'product.id = product_price.product_id',
                'type' => 'left',
            ],
        ];

        $like = [];

        $results = $this->b2o_store_product_model->getProductList($fields, $filter, $join, $like, $order_by, $limit);

        return $results;
    }

    /**
     * 根据 productId 获取一级后端类目
     *
     * @param int $productId
     *
     * @return string
     */
    public function getProductTopBackendClass($productId) {
        $this->db->select("b2o_product_class.cat_path")
            ->from("product")
            ->join("b2o_product_template", "product.template_id = b2o_product_template.id", "left")
            ->join("b2o_product_class", "b2o_product_template.class_id = b2o_product_class.id", "left")
            ->where([
                "product.id" => $productId,
            ]);
        $result = $this->db->get()->row_array();
        return $result['cat_path'] ? strstr($result['cat_path'], ',', true) : '';
    }
}
