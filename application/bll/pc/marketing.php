<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class Marketing extends pc{

	function __construct($params=array()){
		$this->ci = &get_instance();
	}

	function indexProduct($params){
		$this->ci->load->model('product_model');
		$product_list = parent::call_bll($params);

		//get product detail info
		$product_ids = array();
		$adv_product_ids = array();
		foreach ($product_list as $key => &$value) {
			$curr_product_ids = explode(',', $value['product_id']);
			$product_ids = array_merge($product_ids, $curr_product_ids);
			if($value['is_adv']==1 && !empty($value['adv_product_id'])){
				$adv_product_ids[] = $value['adv_product_id'];
			}
		}
		$product_ids = array_merge($product_ids, $adv_product_ids);
        $region_id = $this->get_region_id($params['region_id']);

        //分仓设置
        if(!empty($params['cang_id']) && isset($params['cang_id']))
        {
            $cang_str = '(cang_id LIKE \'' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . '\' OR cang_id = \'' . $params['cang_id'] . '\')';
            $product_res = $this->ci->product_model->getProductsById($product_ids, "{$cang_str} AND online = 1 AND lack = 0 AND (use_store = 0 OR (use_store = 1 AND stock > 0))");
        }
        else
        {
            $product_res = $this->ci->product_model->getProductsById($product_ids, "send_region LIKE '%{$region_id}%' AND online = 1 AND lack = 0 AND (use_store = 0 OR (use_store = 1 AND stock > 0))");
        }
        $product_info_list = array_column($product_res, null, 'id');

		//get module product
		foreach ($product_list as $key => &$value) {
            $curr_product_ids = explode(',', $value['product_id']);
            $items = array();

            // sort by product_id
            foreach ($curr_product_ids as $pid) {
                if (isset($product_info_list[$pid])) {
                    $items[] = $product_info_list[$pid];
                }
            }

            if ($value['is_adv'] == 1 && isset($product_info_list[$value['adv_product_id']])) {
                $show_num = $value['show_num'] - 2;
                $value['adv_item'] = $product_info_list[$value['adv_product_id']];
                $value['items'] = array_chunk(array_slice($items, 0, $show_num), 4);
            } else {
                $show_num = $value['show_num'];
                $value['items'] = array_chunk(array_slice($items, 0, $show_num), 5);
            }

            //楼层关键字
            $value['keyword'] = empty($value['keyword']) ? array() : explode(',', $value['keyword']);

            //set module
            switch ($value['position']) {
                case '6':
                    $new_product_list = $value;
                    break;
                case '7':
                    $all_product_list = $value; //todo by lusc
                    break;
                case '8':
                    $shengxian_product_list = $value; //todo by lusc
                    break;
                case '9':
                    $gift_product_list = $value; //todo by lusc
                    break;
                default:
                    # code...
                    break;
            }
        }

		$data = array(
            'new' => $new_product_list,
            'all' => $all_product_list,
            'shengxian' => $shengxian_product_list,
            'gift' => $gift_product_list,
        );
        return $data;
	}

	function banner($params){
	        $this->ci->load->model('user_action_model');
            if( $params['connect_id'] ){
                 $this->_sessid = $params['connect_id'];
                 $params['uid'] = $this->get_userid();
                 $this->ci->user_action_model->visitor($params);
            }
                if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
		 	if(!$this->ci->memcached){
		 		$this->ci->load->library('memcached');
		 	}
		 	$mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id']."_".$params['cang_id']."_".$params['channel'];
		 	$data = $this->ci->memcached->get($mem_key);
		 	if($data){
		 		return $data;
		 	}
		}
                $banner_list = parent::call_bll($params);
		$rotation_banner_list = array();
		$cross_mix_banner_list = array();

        //会员中心app
        $app_foretaste_banner_list = array();
        $app_rotation_banner_list = array();
        $app_general_banner_list = array();

        //首页
        $home_globefruit_banner_list = array();
        $home_recommend_banner_list = array();
        $home_shengxian_banner_list = array();
        $home_gift_banner_list = array();

		foreach ($banner_list as $key => &$value) {
			$is_top = $value['is_top'];
			unset($value['is_top']);
			switch ($value['position']) {
				case '0':
					$rotation_banner_list[] = $value;
					break;
				case '1':
					$cross_mix_banner_list[] = $value;//todo by lusc
					break;
                case '30':
                    $home_recommend_banner_list[] = $value;//todo by lusc
                    break;
                case '31':
                    $home_globefruit_banner_list[] = $value;//todo by lusc
                    break;
                case '32':
                    $home_shengxian_banner_list[] = $value;//todo by lusc
                    break;
                case '33':
                    $home_gift_banner_list[] = $value;//todo by lusc
                    break;
                case '54':
                    $qiangxian_product_list[] = $value;
                    break;
                case '55':
                    $kjt_product_list[] = $value;
                    break;
                case '56':
                    $member_banner_list[] = $value;
                    break;
                case '57':
                    $top_max_banner_list[] = $value;
                    break;
                case '58':
                    $top_min_banner_list[] = $value;
                    break;
                case '23':
                    $app_foretaste_banner_list[] = $value;  //todo by jackchen
                    break;
                case '80':
                    $app_rotation_banner_list[] = $value;  //todo by jackchen
                    break;
                case '81':
                    $app_general_banner_list[] = $value;  //todo by jackchen
                    break;
				default:
					# code...
					break;
			}
		}
		// $day_product_list = $this->xsh($region_id);//todo
		$data = array(
			'rotation'=>$rotation_banner_list,
			'cross_mix_banner'=>array_slice($cross_mix_banner_list,0, 4),
            'qiangxian_product_list'=>$qiangxian_product_list,
            'kjt_product_list'=>array_slice($kjt_product_list,0, 3),
            'member_banner_list'=>array_slice($member_banner_list,0, 3),
            'top_max_banner_list'=>$top_max_banner_list,
            'top_min_banner_list'=>$top_min_banner_list,
            'app_foretaste_banner_list'=>$app_foretaste_banner_list,
            'app_rotation_banner_list'=>$app_rotation_banner_list,
            'app_general_banner_list'=>$app_general_banner_list,
            'home_globalfruit_banner_list'=>$home_globefruit_banner_list,
            'home_recommend_banner_list'=>$home_recommend_banner_list,
            'home_shengxian_banner_list'=>$home_shengxian_banner_list,
            'home_gift_banner_list'=>$home_gift_banner_list,
		);
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if(!$this->ci->memcached){
				$this->ci->load->library('memcached');
			}
			$mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id']."_".$params['cang_id']."_".$params['channel'];
			$this->ci->memcached->set($mem_key,$data,600);
		}
		return $data;
	}
        /**
         * 获取会员
         *
         * @return void
         * @author
         **/
        public function get_userid()
        {
            $this->ci->load->library('login');
            $this->ci->login->init($this->_sessid);

            return $this->ci->login->get_uid();
        }

    public function baseSetting($params){
        $this->ci->load->model('setting_model');
        $res = $this->ci->setting_model->dump(array('id'=>1), 'setting');

        $url = $params['url'] ? : '';

        if (!empty($url)) {
            $this->ci->load->model('seo_setting_model');
            $seo_setting = $this->ci->seo_setting_model->dump(['url' => $url]);

            if (!empty($seo_setting)) {
                $field_list = [
                    'url' => 'web_url',
                    'title' => 'web_name',
                    'keyword' => 'web_keyword',
                    'description' => 'web_discription'
                ];

                $standard = unserialize($res['setting']);

                foreach ($field_list as $new_field => $old_field) {
                    if (!empty($seo_setting[$new_field])) {
                        $standard[$old_field] = $seo_setting[$new_field];
                    }
                }

                $res = ['setting' => serialize($standard)];
            }
        }

        return $res;
    }

    private function get_region_id($region_id=106092){
		$region_id = empty($region_id) ? 106092 : $region_id;
		$site_list = $this->ci->config->item('site_list');
		if(isset($site_list[$region_id])){
			$region_result = $site_list[$region_id];
		}else{
			$region_result = 106092;
		}
		return $region_result;
	}

}
