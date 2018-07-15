<?php

namespace bll\pc;

include_once("pc.php");

/**
 * 商品相关接口
 */
class Product extends Pc
{

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
        $this->ci->load->model('product_model');
    }

    public function category($params)
    {
        // 检查参数
        $required = array(
			'class_id' => array('required' => array('code' => '500', 'msg' => 'category id can not be null')),
		);
        $checkResult = check_required($params, $required);
        if ($checkResult) {
			return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
		}

        $classId = $params['class_id'];
        $regionId = $this->get_region_id($params['region_id']);
        $defaultChannle = $this->ci->config->item('default_channle');
        $channel = empty($params['channel']) ? 'portal' : $params['channel'];
		if (in_array($channel, $defaultChannle)) {
			$channel = 'portal';
		}
        $filter = unserialize($params['filter']);
        $filterArr = array(
            'class_id' => $classId,
            'region' => $regionId,
            'channel' => $channel,
            'tag_id' => isset($filter['tag_id']) ? $filter['tag_id'] : null,
            'op_detail_place' => isset($filter['op_detail_place']) ? $filter['op_detail_place'] : null,
            'op_occasion' => isset($filter['op_occasion']) ? $filter['op_occasion'] : null,
            'op_size' => isset($filter['op_size']) ? $filter['op_size'] : null,
            'price' => isset($filter['price']) ? $filter['price'] : null,
            'sort' => isset($filter['sort']) ? $filter['sort'] : null,
            'multi_item' => 1,
        );
        $tagStr = ($filterArr['tag_id'] && is_array($filterArr['tag_id'])) ? implode('_', $filterArr['tag_id']) : $filterArr['tag_id'];

        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $classId . "_" . $regionId . "_" . $channel . "_" . $tagStr . "_" . $filterArr['op_detail_place'] . "_" . $filterArr['op_occasion'] . "_" . $filterArr['op_size'] . "_" . $filterArr['price'] . "_" . $filterArr['sort'];
			$return = $this->ci->memcached->get($memKey);
			if ($return) {
				return $return;
			}
		}

        //仓储设置
        $filterArr['cang_id'] = $params['cang_id'];

        // 获取数据
        $this->ci->load->model('cat_model');
        $return['classname'] = $this->ci->cat_model->selectClassName($classId);
        $return['list'] = $this->ci->product_model->get_products($filterArr);
        $return['search_items'] = $this->getSearchItems($return['list']);

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $classId . "_" . $regionId . "_" . $channel . "_" . $tagStr . "_" . $filterArr['op_detail_place'] . "_" . $filterArr['op_occasion'] . "_" . $filterArr['op_size'] . "_" . $filterArr['price'] . "_" . $filterArr['sort'];
			$this->ci->memcached->set($memKey, $return, 600);
		}

        return $return;
    }

    public function search($params)
    {
        $return = parent::call_bll($params);
        $return['search_items'] = $this->getSearchItems($return['list']);

        return $return;
    }

    public function search_v2($params){
        $return = parent::call_bll($params);
        $return['search_items'] = $this->getSearchItems($return['list']);

        return $return;
    }

    public function pageListProducts($params)
    {
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['page_type'] . "_" . $params['target_id'] . "_" . $params['region_id'];
			$return = $this->ci->memcached->get($memKey);
			if ($return) {
				return $return;
			}
		}

        $return = parent::call_bll($params);

        if (!empty($return['products'])) {
            $this->ci->load->model('order_product_model');
            foreach ($return['products'] as &$pro) {
                // need to update
                $pro['sale_count'] = $this->ci->order_product_model->count(array('product_id' => $pro['id']));
            }
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['page_type'] . "_" . $params['target_id'] . "_" . $params['region_id'];
			$this->ci->memcached->set($memKey, $return, 600);
		}

        return $return;
    }

    public function productInfo($params)
    {
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $params['region_id'] . "_portal";
			$return = $this->ci->memcached->get($memKey);
			if ($return) {
				return $return;
			}
		}

        $return = parent::call_bll($params);

        $this->ci->load->model('cat_model');
        $this->ci->load->model('product_tag_model');
        $siteList = $this->ci->config->item('site_list');
        $return['product']['top_class'] = $this->ci->cat_model->selectClassName($return['product']['parent_id']);
        $return['product']['tag_name'] = $this->ci->product_tag_model->selectTagName($return['product']['tag_id']);
        if (!empty($return['product']['send_region'])) {
            $regions = unserialize($return['product']['send_region']);
            $temp = array();
            foreach ($regions as $region) {
                $temp = array_merge($temp, array_keys($siteList, $region));
            }
            $return['product']['send_region'] = serialize($temp);
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $params['region_id'] . "_portal";
			$this->ci->memcached->set($memKey, $return, 600);
		}

        return $return;
    }

    /**
     * 无论上下架
     */
    public function getPriceInfo($params){
        $id = $params['id'];
        $region_to_warehouse = $this->ci->config->item('region_to_cang'); 
        $cang_id = $params['cang_id']?$params['cang_id']:$region_to_warehouse[$params['region_id']];
        $result = $this->ci->product_model->get_price_info($id,$cang_id);
        return $result;
    }

    private function get_region_id($region_id = 106092)
    {
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if (isset($site_list[$region_id])) {
            $region_result = $site_list[$region_id];
        } else {
            $region_result = 106092;
        }
        return $region_result;
    }

    private function getSearchItems($products)
    {
        $temp = array(
            'class_fruit' => array(
                'name' => '品类',
                'val' => array('0' => '全部'),
            ),
            'class_fresh' => array(
                'name' => '分类',
                'val' => array('0' => '全部'),
            ),
            'detail_place' => array(
                'name' => '产地',
                'val' => array('0' => '全部'),
            ),
            'occasion' => array(
                'name' => '场合',
                'val' => array('0' => '全部'),
            ),
            'size' => array(
                'name' => '规格',
                'val' => array('0' => '全部'),
            ),
        );
        if(empty($products)){
            return $temp;
        }
        // format search items
        $productIds = array_column($products, 'id');
        $this->ci->load->model('cat_model');
        $temp['class_fruit']['val'] += array_column($this->ci->cat_model->getClassPairsByProIds($productIds, 40), 'name', 'id');
        $temp['class_fresh']['val'] += array_column($this->ci->cat_model->getClassPairsByProIds($productIds, 277), 'name', 'id');

        $productOptions = $this->ci->config->item('product_option');
        $placeIds = array_column($products, 'id', 'op_detail_place');
        $temp['detail_place']['val'] += array_intersect_key($productOptions['detail_place']['val'], $placeIds);
        $occasionIds = array_column($products, 'id', 'op_occasion');
        $temp['occasion']['val'] += array_intersect_key($productOptions['occasion']['val'], $occasionIds);
        $sizeIds = array_column($products, 'id', 'op_size');
        $temp['size']['val'] += array_intersect_key($productOptions['size']['val'], $sizeIds);

        return $temp;
    }
}
