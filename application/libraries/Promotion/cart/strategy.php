<?php
// 营销策略基类
// 蔡昀辰 2015
class strategy {

	var $id;
	var $type;
	var $product;
	var $condition;
	var $solution;

	function __construct($id, $product, $condition, $solution) {
		$this->id        = $id;
		$this->type      = get_class($this);
		$this->product   = $product;
		$this->condition = $condition;
		$this->solution  = $solution;
	}

	function __toString() {
		return json_encode($this);
	}

	function meet($id, $lists) {

		if ($lists->all == 'true') {

			return true;
		}

		if ( is_array($lists->white) && in_array($id, $lists->white) ) {
			return true;
		}

		if ( is_array($lists->black) && !in_array($id, $lists->black) ) {
			return true;
		}

		return false;

	}

	function getProductName($id) {
		$ci = get_instance();
		$r = $ci->db->select('product_name')->from('product')->where('id', $this->solution->product_id)->limit(1)->get()->row();
		return $r->product_name;
	}

	function getGift($id, $repeat = 1) {
		$ci = get_instance();

		// product table
		$product = $ci->db->select('id, product_name, bphoto, photo, middle_photo, thum_photo, thum_min_photo, template_id')
			->from('product')->where('id', $this->solution->product_id)->limit(1)->get()->row();

        // 获取产品模板图片
        if ($product->template_id) {
            $ci->load->model('b2o_product_template_image_model');
            $templateImages = $ci->b2o_product_template_image_model->getTemplateImage($product->template_id, 'main');
            if (isset($templateImages['main'])) {
                $product->bphoto = $templateImages['main']['big_image'];
                $product->photo = $templateImages['main']['image'];
                $product->middle_photo = $templateImages['main']['middle_image'];
                $product->thum_photo = $templateImages['main']['thumb'];
                $product->thum_min_photo = $templateImages['main']['small_thumb'];
            }
        }

		// sku table
		$sku = $ci->db->select()->from('product_price')->where('product_id', $this->solution->product_id)->limit(1)->get()->row();

		$r = [
			'gift_'.$sku->id => [
				'name'       => $product->product_name,
				'sku_id'     => $sku->id,
				'product_id' => $product->id,
				'product_no' => $sku->product_no,
				'item_type'  => 'gift',
				'status'     => 'active',
				'qty'        => $this->solution->product_num ? $this->solution->product_num * $repeat: $repeat,
				'unit'       => $sku->unit,
				'spec'       => $sku->volume,
				'price'      => '0.00',
				'amount'     => '0',
				// 'tags'       => ['满额赠品'], // 为什么要这个字段？
                'photo' => [
					'huge'   => $product->bphoto ? PIC_URL.$product->bphoto : '',
					'big'    => $product->photo ? PIC_URL.$product->photo : '',
					'middle' => $product->middle_photo ? PIC_URL.$product->middle_photo : '',
					'small'  => $product->thum_photo ? PIC_URL.$product->thum_photo : '',
					'thum'   => $product->thum_min_photo ? PIC_URL.$product->thum_min_photo : '',
                ],
                // 'product_photo' => PIC_URL.($product->thum_min_photo ? $product->thum_min_photo : $product->thum_photo),	// 为什么要这个字段？
                // 'pmt_details'   => [0=>['tag'=>'满百赠品','pmt_id'=>$this->id,'pmt_type'=>'amount','pmt_price'=>0]]3, //  为什么要这个字段？
			]
		];

		return $r;
	}

}


interface strategyInterface {
    public function implement(&$cart);
}