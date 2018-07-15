<?php
/**
 * 购物车营销：满额换购。
 */

namespace bll\pmt;

class Limit2buy
{

    public function __construct($params = [])
    {
        $this->ci = & get_instance();
        $this->ci->load->model('promotion_model');
        $this->ci->load->library('terminal');
    }

	public function cal(&$cart_info, $use_exch_as_limit2buy = false)
	{
		// 获得进行中的满额换购活动。
        $promotion_list = $this->ci->promotion_model->get_limit2buy(date('Y-m-d H:i:s'));
        $pmt_type = $this->get_pmt_type($use_exch_as_limit2buy);

        if (empty($promotion_list)) {
        	return true;
        }

        // 获得购物车所有商品规格ID。
        $cart_sku_ids = array_values(array_map(function($v){
        	return $v['sku_id'];
        }, $cart_info['items']));

        // 判断每一个满额换购活动。
        foreach ($promotion_list as $promotion) {
        	$content = json_decode($promotion['content'], true);

        	$pmt_sku_ids =  array_values(array_map(function($v){
        		return $v['product_sku_id'];
        	}, $content));

        	if (array_intersect($cart_sku_ids, $pmt_sku_ids)) {
        		continue;
        	}

        	$only_one = count($content) === 1 ? true : false;

        	if ($only_one) {
		        $this->ci->load->model('product_model');
		        $product_info = $this->ci->product_model->selectProducts('product_name', ['id' => $content[0]['product_id']])[0];
		        $product_name = $product_info['product_name'];
        	}

        	if ($cart_info['total_amount'] >= $promotion['total_limit']) {
        		if ($only_one) {
        			$title = "已满{$promotion['total_limit']}元，加{$content[0]['product_price']}元可换购{$product_name}，去换购。";
        		} else {
        			$title = "已满{$promotion['total_limit']}元，加{$content[0]['product_price']}元可参与换购，去换购。";
        		}

        		// 换购列表
	            $pmt_alert = [
	            	'pmt_type' => $pmt_type,
	            	'solution'=> [
	            		'title' => $title,
	            		'tag' => '促',
	            		'url' => true,
	            		'pmt_type' => $pmt_type,
	            		'pmt_id' => $promotion['id']
	            	]
	            ];

	            $cart_info['pmt_alert'][] = $pmt_alert;
        	} else {
        		// 凑单列表
        		$outofmoney = $promotion['total_limit'] - $cart_info['total_amount'];

        		if ($only_one) {
        			$title = "满{$promotion['total_limit']}元可换购{$product_name}，去凑单。";
        		} else {
        			$title = "满{$promotion['total_limit']}元可参与换购，去凑单。";
        		}

	            $pmt_alert = [
	            	'pmt_type' => 'amount',
	            	'solution'=> [
	            		'title' => $title,
	            		'tag' => '促',
	            		'url' => $this->ci->terminal->is_app(),
	            		'pmt_type' => 'amount',
	            		'pmt_id' => $promotion['id'],
	            		'outofmoney' => $outofmoney
	            	]
	            ];

	            $cart_info['pmt_alert'][] = $pmt_alert;
        	}
        }
	}

    private function get_pmt_type($use_exch_as_limit2buy)
    {
        $pmt_type = $use_exch_as_limit2buy ? 'singleskuexch' : 'limit2buy';
        return $pmt_type;
    }
}

# end of this file
