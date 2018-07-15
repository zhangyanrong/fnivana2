<?php

/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 17/1/3
 * Time: 下午4:48
 */
class B2o_front_product_class_model extends MY_Model {
    public function table_name() {
        return 'b2o_front_product_class';
    }

    public function getClassOne($storeIdGroup, $tmsRegionType) {
        $exclusiveCondition = $this->_getStoreProductConditionByStoreIdGroup($storeIdGroup);
        $this->db->select('a.*');
        $this->db->from('b2o_front_product_class a');
        $this->db->join('b2o_front_product_class b', 'a.id = b.parent_id', 'LEFT');
        $this->db->join('b2o_front_product_class c', 'b.id = c.parent_id', 'LEFT');
        $this->db->join('b2o_template_front_class d', 'c.id = d.front_class_id', 'LEFT');
        $this->db->join('product e', 'd.template_id = e.template_id', 'LEFT');
        $this->db->join('b2o_store_product f', 'e.id = f.product_id', 'LEFT');
        $this->db->where(array('a.step' => 1, 'a.is_show' => 1, 'f.is_delete' => 0, 'f.is_app_online' => 1, 'f.is_hide' => 0, 'f.send_region_type >=' => $tmsRegionType));
        if (is_array($exclusiveCondition['value'])) {
            $this->db->where_in($exclusiveCondition['key'], $exclusiveCondition['value']);
        } else {
            $this->db->where(array($exclusiveCondition['key'] => null));
        }
        $this->db->order_by('a.order_id desc');
        $this->db->group_by('a.id');
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getByClassOne($classOne, $storeIdGroup, $tmsRegionType) {
        $exclusiveCondition = $this->_getStoreProductConditionByStoreIdGroup($storeIdGroup);
        $this->db->select('a.name as class2Name,a.id as class2Id , b.*');
        $this->db->from('b2o_front_product_class a');
        $this->db->join('b2o_front_product_class b', 'a.id = b.parent_id', 'LEFT');
        $this->db->join('b2o_template_front_class d', 'b.id = d.front_class_id', 'LEFT');
        $this->db->join('product e', 'd.template_id = e.template_id', 'LEFT');
        $this->db->join('b2o_store_product f', 'e.id = f.product_id', 'LEFT');
        $this->db->where(array('a.step' => 2, 'b.is_show' => 1, 'f.is_delete' => 0, 'f.is_app_online' => 1, 'f.is_hide' => 0, 'f.send_region_type >=' => $tmsRegionType));
        if (is_array($exclusiveCondition['value'])) {
            $this->db->where_in($exclusiveCondition['key'], $exclusiveCondition['value']);
        } else {
            $this->db->where(array($exclusiveCondition['key'] => $exclusiveCondition['value']));
        }
        $this->db->where(array('a.parent_id' => $classOne));
        $this->db->order_by('a.order_id desc');
        $this->db->order_by('b.order_id desc');
        $this->db->group_by('b.id');
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getBrotherClass($parentId, $storeIdGroup, $mainId, $tmsRegionType) {
        $exclusiveCondition = $this->_getStoreProductConditionByStoreIdGroup($storeIdGroup);
        $this->db->select('a.id,a.name');
        $this->db->from('b2o_front_product_class a');
        $this->db->join('b2o_template_front_class d', 'a.id = d.front_class_id', 'LEFT');
        $this->db->join('product e', 'd.template_id = e.template_id', 'LEFT');
        $this->db->join('b2o_store_product f', 'e.id = f.product_id', 'LEFT');
        $this->db->where(array("( (a.id = {$mainId}) OR (a.parent_id = {$parentId} AND f.is_delete = 0 AND f.is_app_online = 1 AND f.is_hide = 0 AND a.is_show = 1 AND f.send_region_type >= {$tmsRegionType}) )" => null));
        if (is_array($exclusiveCondition['value'])) {
            $this->db->where_in($exclusiveCondition['key'], $exclusiveCondition['value']);
        } else {
            $this->db->where(array($exclusiveCondition['key'] => $exclusiveCondition['value']));
        }
        $this->db->order_by('a.order_id desc');
        $this->db->group_by('a.id');
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getProductByClass($classId, $storeIdGroup, $tmsRegionType) {
        $this->load->model('b2o_store_product_model');

        $fields = 'b2o_store_product.store_id,b2o_store_product.stock,product.product_name,product.id,product.cart_tag,product_price.unit,product_price.volume,b2o_store_product.price,b2o_store_product.product_desc,b2o_store_product.promotion_tag,b2o_store_product.promotion_tag_start,b2o_store_product.promotion_tag_end,product.promotion_photo,product.middle_promotion_photo,product.thum_promotion_photo,product.bpromotion_photo,product.thum_min_promotion_photo,product.photo,product.thum_photo,product.middle_photo,product.bphoto,product.thum_min_photo,product.has_webp,b2o_store_product.delivery_template_id,b2o_store_product.sort,product.template_id';
        $join = array();
        $join[] = array(
            'name' => 'product',
            'cond' => 'product.id=b2o_store_product.product_id',
        );
        $join[] = array(
            'name' => 'product_price',
            'cond' => 'product_price.product_id=product.id',
        );
        $join[] = array(
            'name' => 'b2o_template_front_class',
            'cond' => 'b2o_template_front_class.template_id=product.template_id',
        );
        $filter = array();
        $filter['product.template_id <>'] = 0;
        $filter['b2o_template_front_class.front_class_id'] = (array)$classId;
        $filter['b2o_store_product.store_id'] = $storeIdGroup;
        $filter['b2o_store_product.is_hide'] = 0;
        $filter['b2o_store_product.is_app_online'] = 1;
        $filter['b2o_store_product.is_delete'] = 0;
        $filter['b2o_store_product.send_region_type >='] = $tmsRegionType;

        $result = $this->b2o_store_product_model->getProductList($fields, $filter, $join, array());
        return $result;
    }

    private function _getStoreProductConditionByStoreIdGroup($storeIdGroup) {
        //判断是否前置仓

        $hasCityShop = false;
        $storeId2Type = array();
        foreach ($storeIdGroup as $storeId) {
            $this->db->select('type');
            $this->db->from('b2o_store');
            $this->db->where(array('id' => $storeId));
            $storeInfo = $this->db->get()->row_array();
            if ($storeInfo['type'] == 2 || $storeInfo['type'] == 3) {
                $hasCityShop = true;
            }
            $storeId2Type[$storeId] = $storeInfo['type'];
        }

        if (!$hasCityShop) {
            return array('key' => 'f.store_id', 'value' => $storeIdGroup);
        } else {
            $exclusiveCondition = array();
            foreach ($storeId2Type as $storeId => $type) {
                switch ($type) {
                    case 1:
                        $exclusiveCondition[] = "(f.store_id = {$storeId} AND f.is_store_sell = 1)";
                        break;
                    default:
                        $exclusiveCondition[] = "(f.store_id = {$storeId})";
                        break;
                }
            }
            $condition = '( ' . implode(' OR ', $exclusiveCondition) . ' )';
            return array('key' => $condition, 'value' => null);
        }

    }
}