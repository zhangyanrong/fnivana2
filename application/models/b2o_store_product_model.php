<?php

class B2o_store_product_model extends CI_model {

    function B2o_store_product_model() {
        parent::__construct();
        $this->load->helper('public');
        // $this->load->library('phpredis');
        // $this->redis = $this->phpredis->getConn();
        $this->load->library('productredis');
        $this->redis = $this->productredis;
    }

    public function getProductList($fields = "*", $filter = array(), $join = array(), $like = array(), $order_by = '', $limit = '', $offset = 0) {
        $checkFront = false;
        if (isset($filter['store_id']) || isset($filter['b2o_store_product.store_id'])) {
            $store_id = $filter['store_id'];
            $store_id or $store_id = $filter['b2o_store_product.store_id'];
            $checkFront = $this->checkFrontBin($store_id);
        }
        //存在前置仓城超属性门店
        if ($checkFront === true) {
            $filter['b2o_store_product.is_store_sell'] = 1;
        }

        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        //$this->db->select($fields);
        $this->db->select("SUBSTRING_INDEX(GROUP_CONCAT(ttgy_b2o_store_product.id ORDER BY ttgy_b2o_store_type.sort desc SEPARATOR '_'),'_',1) as id", false);
        $this->db->from('b2o_store_product');
        $join_b2o_store = true;
        $join_b2o_store_type = true;
        if ($join) {
            foreach ($join as $v) {
                $table_name = $v['name'];
                $cond = $v['cond'];
                $type = $v['type'] ? $v['type'] : '';
                $this->db->join($table_name, $cond, $type);
                if (str_replace($this->db->dbprefix, '', $table_name) == 'b2o_store') {
                    $join_b2o_store = false;
                }
                if (str_replace($this->db->dbprefix, '', $table_name) == 'b2o_store_type') {
                    $join_b2o_store_type = false;
                }
            }
        }
        if ($join_b2o_store) {
            $this->db->join('b2o_store', 'b2o_store.id=b2o_store_product.store_id');
        }
        if ($join_b2o_store_type) {
            $this->db->join('b2o_store_type', 'b2o_store.type=b2o_store_type.id');
        }
        if (!isset($filter['b2o_store_product.is_delete'])) {
            $filter['b2o_store_product.is_delete'] = 0;
        }
        if ($filter) {
            $this->_filter($filter);
        }
        if ($like) {
            foreach ($like as $value) {
                if ($value['type'] == 'or') {
                    $this->db->or_like($value['field'], $value['val']);
                } else {
                    $this->db->like($value['field'], $value['val']);
                }
            }
            $this->db->like_close();
        }
        $this->db->group_by('b2o_store_product.product_id');
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        if ($limit) {
            $this->db->limit($limit, $offset);
        }
        $result = $this->db->get()->result_array();
        $store_product_ids = array();
        foreach ($result as $key => $value) {
            $store_product_ids[] = $value['id'];
        }
        if (empty($store_product_ids)) return array();
        if ($fields == '*' || strstr($fields, 'stock') || strstr($fields, 'b2o_store_product.*')) {
            $fields .= ',product_price.product_no';
            $fields .= ',b2o_store_product.is_real_stock';
            $fields .= ',b2o_store_product.store_id';
            $fields .= ',b2o_store.warehouse';
            $fields .= ',b2o_store.type as store_type';
            $joinon_b2o_store = true;
            $joinon_product_price = true;
        }
        $this->db->select($fields);
        $this->db->from('b2o_store_product');
        if ($join) {
            foreach ($join as $v) {
                $table_name = $v['name'];
                $cond = $v['cond'];
                $type = $v['type'] ? $v['type'] : '';
                $this->db->join($table_name, $cond, $type);
                if (str_replace($this->db->dbprefix, '', $table_name) == 'b2o_store') {
                    $joinon_b2o_store = false;
                }
                if (str_replace($this->db->dbprefix, '', $table_name) == 'product_price') {
                    $joinon_product_price = false;
                }
            }
        }
        if ($joinon_b2o_store) {
            $this->db->join('b2o_store', 'b2o_store.id=b2o_store_product.store_id');
        }
        if ($joinon_product_price) {
            $this->db->join('product_price', 'product_price.product_id=b2o_store_product.product_id');
        }
        $new_filter = array();
        $new_filter['b2o_store_product.id'] = $store_product_ids;

        $this->_filter($new_filter);
        $this->_filter($filter);
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        $result = $this->db->get()->result_array();
        $result = $this->getRedisStock($result);
        return $result;
    }

    public function getAllList($fields = "*", $filter = array(), $join = array(), $order_by = '', $limit = '', $offset = 0) {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        if ($fields == '*' || strstr($fields, 'stock') || strstr($fields, 'b2o_store_product.*')) {
            $fields .= ',product_price.product_no';
            $fields .= ',b2o_store_product.is_real_stock';
            $fields .= ',b2o_store_product.store_id';
            $fields .= ',b2o_store.warehouse';
            $fields .= ',b2o_store.type as store_type';
            $joinon_b2o_store = true;
            $joinon_product_price = true;
        }
        $this->db->select($fields);
        $this->db->from('b2o_store_product');
        if ($join) {
            foreach ($join as $v) {
                $table_name = $v['name'];
                $cond = $v['cond'];
                $type = $v['type'] ? $v['type'] : '';
                $this->db->join($table_name, $cond, $type);
                if (str_replace($this->db->dbprefix, '', $table_name) == 'b2o_store') {
                    $joinon_b2o_store = false;
                }
                if (str_replace($this->db->dbprefix, '', $table_name) == 'product_price') {
                    $joinon_product_price = false;
                }
            }
        }
        if ($joinon_b2o_store) {
            $this->db->join('b2o_store', 'b2o_store.id=b2o_store_product.store_id');
        }
        if ($joinon_product_price) {
            $this->db->join('product_price', 'product_price.product_id=b2o_store_product.product_id');
        }
        if (!isset($filter['b2o_store_product.is_delete'])) {
            $filter['b2o_store_product.is_delete'] = 0;
        }
        if ($filter) {
            $this->_filter($filter);
        }
        if ($order_by) {
            if (is_array($order_by)) {
                $this->db->orderby_filed($order_by[0], $order_by[1]);
            } else {
                $this->db->order_by($order_by);
            }
        }
        if ($limit) {
            $this->db->limit($limit, $offset);
        }
        $result = $this->db->get()->result_array();
        $result = $this->getRedisStock($result);
        return $result;
    }

    public function getProductInfo($product_id, $store_id) {
        //$fileds = "b2o_store_product.*,product_price.*,product.*";
        $filter = array('product_id' => $product_id, 'store_id' => $store_id);
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.id=product.id',
        );
        $result = $this->getAllList('*', $filter, $join, '', 1);
        return $result[0] ? $result[0] : array();
    }

    public function _filter($filter = array()) {
        foreach ($filter as $key => $value) {
            $keys = explode('.', $key);
            if (count($keys) == 1) {
                $key = 'b2o_store_product.' . $key;
            }
            if (is_array($value)) {
                $this->db->where_in($key, $value);
            } else {
                $this->db->where($key, $value);
            }
        }
    }

    public function getRedisStock($product_list) {
        if (!isset(reset($product_list)['stock'])) return $product_list;
        foreach ($product_list as $key => &$product) {
            $product['stock'] = $this->get_stock($product['product_no'], $product['store_id'], $product['store_type'], $product['warehouse'], $product['is_real_stock']);
        }
        return $product_list;
    }

    private function get_stock($product_no, $store_id = 0, $store_type = 0, $warehouse_id = 0, $is_real_stock = 0) {
        $stock = 0;
        $ware_key = '';
        if ($store_type == 1) {    //大仓实时库存
            $ware_key = 'product_stock:' . $product_no . ':' . $warehouse_id;
        }
        $store_key = 'product_stock_store:' . $product_no . ':' . $store_id;   //门店库存
        if ($is_real_stock == 1) {   //启用实时库存
            if ($ware_key) {  //大门店
                if ($this->redis->exists($ware_key)) {
                    $ware_store_info = $this->redis->hMget($ware_key, array('stock'));
                    $stock = $ware_store_info['stock'] ? $ware_store_info['stock'] : 0;
                }
            } else {
                if ($this->redis->exists($store_key)) {
                    $stock_info = $this->redis->hMget($store_key, array('stock'));
                    $stock = $stock_info['stock'] ? $stock_info['stock'] : 0;
                }
            }
        } else {
            if ($this->redis->exists($store_key)) {
                $stock_info = $this->redis->hMget($store_key, array('sale_stock'));
                $stock = $stock_info['sale_stock'] ? $stock_info['sale_stock'] : 0;
            }
        }
        if ($stock < 0) $stock = 0;
        return $stock;
    }

    //下单冻结库存
    private function reduce_stock($product_no, $store_id = 0, $qty, $store_type = 0, $warehouse_id = 0, $is_real_stock = 0) {
        $store_key = 'product_stock_store:' . $product_no . ':' . $store_id;
        if ($store_type == 1) {       //大仓
            $ware_key = 'product_stock:' . $product_no . ':' . $warehouse_id;
            $ware_stock = 0;
            if ($this->redis->exists($ware_key)) {
                $ware_store_info = $this->redis->hMget($ware_key, array('stock'));
                $new_stock = intval($ware_store_info['stock'] - $qty) > 0 ? intval($ware_store_info['stock'] - $qty) : 0;
                $this->redis->hMset($ware_key, array('stock' => intval($new_stock)));
            }
            if ($is_real_stock == 1) {   //启用实时库存

            } else {
                $sale_stock = 0;
                if ($this->redis->exists($store_key)) {
                    $stock_info = $this->redis->hMget($store_key, array('sale_stock'));
                    $new_stock = intval($stock_info['sale_stock'] - $qty) > 0 ? intval($stock_info['sale_stock'] - $qty) : 0;
                    $this->redis->hMset($store_key, array('sale_stock' => $new_stock));
                }

            }
        } else {
            $stock_info = $this->redis->hMget($store_key, array('stock', 'sale_stock'));
            if ($is_real_stock == 1) {   //启用实时库存
                $new_stock = intval($stock_info['stock'] - $qty) > 0 ? intval($stock_info['stock'] - $qty) : 0;
                $this->redis->hMset($store_key, array('stock' => $new_stock));
            } else {
                $new_stock = intval($stock_info['sale_stock'] - $qty) > 0 ? intval($stock_info['sale_stock'] - $qty) : 0;
                $this->redis->hMset($store_key, array('sale_stock' => $new_stock));
            }
        }
    }

    //取消订单 恢复冻结库存
    public function return_stock($product_no, $store_id = 0, $qty, $store_type = 0, $warehouse_id = 0, $is_real_stock = 0) {
        $store_key = 'product_stock_store:' . $product_no . ':' . $store_id;
        if ($store_type == 1) {       //大仓
            $ware_key = 'product_stock:' . $product_no . ':' . $warehouse_id;
            $ware_stock = 0;
            if ($this->redis->exists($ware_key)) {
                $ware_store_info = $this->redis->hMget($ware_key, array('stock'));
                $new_stock = intval($ware_store_info['stock'] + $qty) > 0 ? intval($ware_store_info['stock'] + $qty) : 0;
                $this->redis->hMset($ware_key, array('stock' => intval($new_stock)));
            }
            if ($is_real_stock == 1) {   //启用实时库存

            } else {
                $sale_stock = 0;
                if ($this->redis->exists($store_key)) {
                    $stock_info = $this->redis->hMget($store_key, array('sale_stock'));
                    $new_stock = intval($stock_info['sale_stock'] + $qty) > 0 ? intval($stock_info['sale_stock'] + $qty) : 0;
                    $this->redis->hMset($store_key, array('sale_stock' => $new_stock));
                }

            }
        } else {
            $stock_info = $this->redis->hMget($store_key, array('stock', 'sale_stock'));
            if ($is_real_stock == 1) {   //启用实时库存
                $new_stock = intval($stock_info['stock'] - $qty) > 0 ? intval($stock_info['stock'] - $qty) : 0;
                $this->redis->hMset($store_key, array('stock' => $new_stock));
            } else {
                $new_stock = intval($stock_info['sale_stock'] + $qty) > 0 ? intval($stock_info['sale_stock'] + $qty) : 0;
                $this->redis->hMset($store_key, array('sale_stock' => $new_stock));
            }
        }
    }

    private function getStockProductInfo($product_id, $store_id) {
        if (!$product_id || !$store_id) return false;
        $key = "stock_baseinfo_".$product_id."-".$store_id;
        if($this->redis->exists($key)){
            $product_info = $this->redis->hMget($key, array('product_no', 'store_id', 'type', 'warehouse', 'is_real_stock'));
            if($product_info && $product_info['product_no'] && $product_info['store_id']) return $product_info;
        }
        $fileds = "product_price.product_no,b2o_store_product.store_id,b2o_store.type,b2o_store.warehouse,b2o_store_product.is_real_stock";
        $filter = array();
        $filter['b2o_store_product.product_id'] = $product_id;
        $filter['b2o_store_product.store_id'] = $store_id;
        $join = array();
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=b2o_store_product.product_id',
        );
        $join[] = array(
            'name' => 'b2o_store',
            'cond' => 'b2o_store.id=b2o_store_product.store_id',
        );
        $product_info = $this->getAllList($fileds, $filter, $join);
        if (empty($product_info)) return false;
        $product_info = $product_info[0];
        $this->redis->hMset($key, $product_info);
        //$this->redis->expire($key, 600);
        return $product_info;
    }

    public function getExpiration($product_id, $store_id){
        $key = "stock_baseinfo_".$product_id."-".$store_id;
        $expiration = '9999-12-31';
        if($this->redis->exists($key)){
            $product_info = $this->redis->hMget($key, array('exp_date'));
            $expiration = $product_info['exp_date']?$product_info['exp_date']:'9999-12-31';
        }
        return $expiration;
    }

    public function get_product_stock($product_id, $store_id) {
        if (!$product_id || !$store_id) return false;
        $product_info = $this->getStockProductInfo($product_id, $store_id);
        if (empty($product_info)) return false;
        $product_no = $product_info['product_no'];
        $store_id = $product_info['store_id'];
        $store_type = $product_info['type'];
        $warehouse_id = $product_info['warehouse'];
        $is_real_stock = $product_info['is_real_stock'];
        return $this->get_stock($product_no, $store_id, $store_type, $warehouse_id, $is_real_stock);
    }

    public function reduce_product_stock($product_id, $store_id, $qty) {
        if (!$product_id || !$store_id) return false;
        $product_info = $this->getStockProductInfo($product_id, $store_id);
        if (empty($product_info)) return false;
        $product_no = $product_info['product_no'];
        $store_id = $product_info['store_id'];
        $store_type = $product_info['type'];
        $warehouse_id = $product_info['warehouse'];
        $is_real_stock = $product_info['is_real_stock'];
        return $this->reduce_stock($product_no, $store_id, $qty, $store_type, $warehouse_id, $is_real_stock);
    }

    public function return_product_stock($product_id, $store_id, $qty) {
        if (!$product_id || !$store_id) return false;
        $product_info = $this->getStockProductInfo($product_id, $store_id);
        if (empty($product_info)) return false;
        $product_no = $product_info['product_no'];
        $store_id = $product_info['store_id'];
        $store_type = $product_info['type'];
        $warehouse_id = $product_info['warehouse'];
        $is_real_stock = $product_info['is_real_stock'];
        return $this->return_stock($product_no, $store_id, $qty, $store_type, $warehouse_id, $is_real_stock);
    }
    //同步后实际减库存
    // public function cut_stock($product_no,$qty,$store_id=0,$store_type=0,$warehouse_id=0,$is_real_stock = 0){
    //     if($store_type == 1){       //大仓实时库存
    //         $redis_key = 'product_stock:'.$product_no.':'.$warehouse_id;
    //     }else{
    //         $redis_key = 'product_stock_store:'.$product_no.':'.$store_id;
    //     }
    //     if($this->redis->exists($redis_key)){
    //         $stock_info = $this->redis->hMget($redis_key,array('stock','sale_stock','pool_stock'));
    //         $pool_stock = intval($stock_info['pool_stock']) - intval($qty);
    //         $stock = intval($stock_info['stock']) - intval($qty);
    //         $sale_stock = intval($stock_info['sale_stock']) - intval($qty);
    //         $this->redis->hMset($redis_key,array('pool_stock'=>$pool_stock));
    //     }
    //     return true;
    // }

    public function existProductByStoreId($productId, $storeGroup, $tmsRegionType) {
        $this->db->select('count(1) as count');
        $this->db->from('b2o_store_product');
        $this->db->where(array('product_id' => $productId, 'send_region_type' => $tmsRegionType));
        $this->db->where_in('store_id', $storeGroup);
        $result = $this->db->get()->row_array();
        if ($result['count'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getProductListByIDs($ids) {
        $fields = 'b2o_store_product.store_id,product.product_name,product.id,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end,product.op_weight,product.card_limit,product.jf_limit,b2o_store_product.price,b2o_store_product.product_desc,product_price.volume,product_price.old_price,product_price.over_time,product_price.start_time,b2o_store_product.stock,product.photo,b2o_delivery_tpl.type,product.template_id';
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
            //'type' => 'left'
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
            //'type' => 'left'
        );
        $join[] = array(
            'name' => 'b2o_delivery_tpl',
            'cond' => 'b2o_delivery_tpl.tpl_id=b2o_store_product.delivery_template_id',
        );
        $filter = array();
        $filter['b2o_store_product.id'] = $ids;
        $str_ids = implode(',', $ids);
        $order_by = array('b2o_store_product.id', $str_ids);
        $result = $this->getAllList($fields, $filter, $join, $order_by);
        $result = $this->addImgUrl($result);
        $result = $this->format_promotion_tag($result);
        return $result;
    }

    public function getSearchList($keyword, $store_id_list, $online_key, $send_region_type=0, $limit, $offset) {
        $fields = 'b2o_store_product.store_id,product.product_name,product.id,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end,product.op_weight,product.card_limit,product.jf_limit,b2o_store_product.price,b2o_store_product.product_desc,product_price.volume,product_price.old_price,product_price.over_time,product_price.start_time,b2o_store_product.stock,product.photo,b2o_delivery_tpl.type,product.template_id';

        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
            //'type' => 'left'
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
            //'type' => 'left'
        );
        $join[] = array(
            'name' => 'b2o_product_template',
            'cond' => 'b2o_product_template.id=product.template_id',
            'type' => 'left',
        );
        $join[] = array(
            'name' => 'b2o_product_class',
            'cond' => 'b2o_product_class.id=b2o_product_template.class_id',
            'type' => 'left',
        );
        $join[] = array(
            'name' => 'b2o_product_search_tag',
            'cond' => 'b2o_product_search_tag.class_id=b2o_product_class.id',
            'type' => 'left',
        );
        $join[] = array(
            'name' => 'b2o_delivery_tpl',
            'cond' => 'b2o_delivery_tpl.tpl_id=b2o_store_product.delivery_template_id',
        );
        $filter = array();
        $filter['b2o_store_product.store_id'] = $store_id_list;
        $filter['b2o_store_product.' . $online_key] = 1;
        $filter['b2o_store_product.is_hide'] = 0;
        $filter['b2o_store_product.is_delete'] = 0;
        $filter['b2o_store_product.send_region_type >='] = intval($send_region_type);
        $like = array();
        $like[] = array('field' => 'product.product_name', 'val' => $keyword);
        $like[] = array('field' => 'b2o_product_class.name', 'val' => $keyword, 'type' => 'or');
        $like[] = array('field' => 'b2o_product_search_tag.tags', 'val' => $keyword, 'type' => 'or');
        // $like[] = array('field'=>'product.promotion_tag','val'=>$keyword,'type'=>'or');
        $orderBy = "b2o_store_product.sort desc";
        $result = $this->getProductList($fields, $filter, $join, $like, $orderBy, $limit, $offset);
        $result = $this->addImgUrl($result);
        $result = $this->format_promotion_tag($result);
        return $result;
    }

    private function addImgUrl($product_list) {
        $this->load->model('b2o_product_template_image_model');
        foreach ($product_list as $key => &$value) {
            // 获取产品模板图片
            if ($value['template_id']) {
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $value['photo'] = $templateImages['main']['image'];
                }
            }
            if (isset($value['photo']) && $value['photo']) {
                if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://') {
                    $pic_url = cdnImageUrl($value['id']);
                    $value['photo'] = $pic_url . $value['photo'];
                }
            }
        }
        return $product_list;
    }

    public function format_promotion_tag($product_list) {
        $now = time();
        foreach ($product_list as $key => &$product) {
            if ($product['promotion_tag']) {
                if ($product['promotion_tag_start'] && $product['promotion_tag_end']) {
                    if ($now >= strtotime($product['promotion_tag_start']) && $now <= strtotime($product['promotion_tag_end'])) {

                    } else {
                        $product['promotion_tag'] = '';
                    }
                } else {

                }
            }
            unset($product['promotion_tag_start'], $product['promotion_tag_end']);
        }
        return $product_list;
    }

    //判断是否存在前置仓，城超门店
    public function checkFrontBin($s_ids) {
        if (!is_array($s_ids)) {
            $s_ids = array($s_ids);
        }
        $this->db->from('b2o_store');
        $this->db->where_in('type', array(2, 3));
        $this->db->where_in('id', $s_ids);
        $res = $this->db->get()->row_array();
        if ($res) return true;
        else return false;
    }
}