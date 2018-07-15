<?php
class Banner_model extends MY_Model {

	function Banner_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'appbanner';
    }

	/*
	*获取广告详情
	*/
	function selectPage($field,$where='',$where_in='',$order='',$limits='',$like=''){
		$this->db->select($field);
		$this->db->from('apppage');
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			foreach($where_in as $val){
				$this->db->where_in($val['key'],$val['value']);
			}
		}
		if(!empty($like)){
			$this->db->like($like);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],($limits['curr_page']*$limits['page_size']));
		}
		if(!empty($order)){
			$this->db->order_by($order['key'],$order['value']);
		}

		$result = $this->db->get()->result_array();
		return $result;
	}

	/*
	*获取广告列表
	*/
	function selectBanner($field,$where='',$where_in='',$order='',$limits='',$like=''){
		$this->db->select($field);
		$this->db->from('appbanner');
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			foreach($where_in as $val){
				$this->db->where_in($val['key'],$val['value']);
			}
		}
		if(!empty($like)){
			$this->db->like($like);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],($limits['curr_page']*$limits['page_size']));
		}
		if(!empty($order)){
			$this->db->order_by($order['key'],$order['value']);
		}

		$result = $this->db->get()->result_array();
		return $result;
	}

	/*
	*获取广告列表
	*/
	function get_banner_list($params){
		$field = array(
			'photo',//广告图片
            'newphoto',//广告图片 －new
			'price',//广告价格
			'title',//广告标题
			'type',//广告类型
			'target_id',//目标页id
			'description',//广告描述
			'page_url',//链接
			'position',//位置
			'is_top',//是否置顶
			'channel',//渠道
            'start_time', // 活动开始时间
            'end_time', // 活动结束时间
            'everyday_start_hour', // 每天开始时间，单位：时
		);
		$where['is_show'] = 1;
		switch ($params['source']) {
			case 'app':
				$where['app'] = 1;
				break;
			case 'wap':
				$where['wap'] = 1;
				break;
            case 'pc':
                $where['pc'] = 1;
                break;
			default:
				break;
		}
        $dateTime = date("Y-m-d H:i:s", $params['timestamp']);
        $where['((start_time IS NULL AND end_time IS NULL) OR (start_time <= "' . $dateTime . '" AND end_time >= "' . $dateTime . '"))'] = null;
		$channel_where_in = array('portal');
		if(isset($params['channel']) && !empty($params['channel'])){
			$channel_where_in = array('portal',$params['channel']);
		}
		$where_in[] = array('key'=>'channel','value'=>$channel_where_in);
//		$like['send_region'] = '"'.$params['region_id'].'"';
		$order = array('key'=>'sort','value'=>'desc');
		if(!empty($params['ids'])){
			$where_in[] = array('key'=>'id','value'=>$params['ids']);
		}

        $isUseCang = $this->config->item('is_enable_cang');
        if ($isUseCang) { // warehouse
            $where['(cang_id LIKE \'' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . ',%\' OR cang_id LIKE \'%,' . $params['cang_id'] . '\' OR cang_id = \'' . $params['cang_id'] . '\')'] = null;
        } else { // region
            $where['send_region LIKE \'%"' . $params['region_id'] . '"%\''] = null;
        }

		$result = $this->selectBanner($field,$where,$where_in,$order,'',$like);

		foreach($result as $key=>&$val){
			if($val['type']=='5'){
				$this->db->select('photo');
				$this->db->from('apppage');
				$this->db->where('id',$val['target_id']);
				$photo_result = $this->db->get()->row_array();
				$result[$key]['page_photo'] = PIC_URL.$photo_result['photo'];
			}

            if(strcmp($params['version'],'3.1.0') >= 0 && $val['type']=='19')
            {
                $val['photo'] = PIC_URL.$val['newphoto'];
            }
            else{
                $val['photo'] = PIC_URL.$val['photo'];
            }

            unset($val['newphoto']);
		}
		return $result;
	}

    function get_charge_money_upto($type=0,$region_id){
        $now = date("Y-m-d H:i:s");
        $this->db->from("money_upto");
        $this->db->where(array('start <='=>$now,'end >='=>$now,'type'=>$type));
        $area_where .= 'send_region like \'%"'.$region_id.'"%\'';
        $this->db->where($area_where);
        $query = $this->db->get();
        $promotion = $query->result();
        $promotion = unserialize($promotion[0]->content);
        $products = array();
        $products_content = array();
        $products_num = array();
        $score_content = array();
        $bonus_content = array();
        for($i=0;$i<count($promotion);$i++) {
          $upto_money = $promotion[$i]['low'];
          $products_content[$upto_money] = $promotion[$i]['products_content'];
          $products_num[$upto_money] = $promotion[$i]['products_num'];
          if($promotion[$i]['score_content']){
              $score_content[$upto_money] = $promotion[$i]['score_content'];
          }
          if($promotion[$i]['bonus_content']){
              $bonus_content[$upto_money] = $promotion[$i]['bonus_content'];
          }
        }
        if(!empty($products_content)) {
          foreach($products_content as $key=>$val) {
              if(!empty($val)) {
                  $query = $this->db->query("select b.volume,b.unit,b.price,a.* from ttgy_product_price b,(select id,thum_photo,product_name,summary,template_id from ttgy_product where id in ($val)) a where b.product_id=a.id");
                  $products[$key] = $query->result_array();
              }
          }
        }else{
            $products="";
        }
        $charge_rule = array();
        $i = 0;
        if(!empty($products)) {
            foreach ($products as $key => $value) {
                // 获取产品模板图片
                if ($value[0]['template_id']) {
                    $this->load->model('b2o_product_template_image_model');
                    $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value[0]['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $value[0]['thum_photo'] = $templateImages['main']['thumb'];
                    }
                }

                $charge_rule[$i]['id'] = $key * 3;
                $charge_rule[$i]['money_upto'] = $key;
                $charge_rule[$i]['product_name'] = $value[0]['product_name'];
                $charge_rule[$i]['gg_name'] = $value[0]['volume'].'/'.$value[0]['unit'];
                $charge_rule[$i]['price'] = $value[0]['price'];
                $charge_rule[$i]['products_num'] = (string)$products_num[$key];
                $charge_rule[$i]['photo'] = PIC_URL.$value[0]['thum_photo'];
                $charge_rule[$i]['desc'] = strip_tags($value[0]['summary']);
                $charge_rule[$i]['expired_desc'] = '赠品有效期两周';
                $charge_rule[$i]['is_virtual'] = 0;
                $i++;
            }
        }
        if(!empty($bonus_content)){
        	foreach ($bonus_content as $key => $value) {
                $charge_rule[$i]['id'] = $key * 3;
                $charge_rule[$i]['money_upto'] = $key;
                $charge_rule[$i]['product_name'] = "充值金额满".$key."元赠".$value.'元';
                $charge_rule[$i]['gg_name'] = '';
                $charge_rule[$i]['price'] = $value;
                $charge_rule[$i]['products_num'] = 1;
                $charge_rule[$i]['photo'] = PIC_URL.'assets/images/bg/charge200.jpg';
                $charge_rule[$i]['desc'] = "充值金额满".$key."元赠".$value.'元';
                $charge_rule[$i]['expired_desc'] = '';
                $charge_rule[$i]['is_virtual'] = 1;
                $i++;
            }
        }
        return $charge_rule;
    }

    function getIcon(){
    	$this->db->select('type,url');
    	$this->db->from('icon');
    	$this->db->where(array('is_show'=>'1'));
    	return $this->db->get()->result_array();
    }

}
