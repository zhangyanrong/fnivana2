<?php
class O2o_cart_model extends CI_model {

    function O2o_cart_model() {
        parent::__construct();
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    /**
     * 购物车mem KEY
     *
     * @return void
     * @author 
     **/
    private function _get_rediskey($uid)
    {
        return 'api:o2o_cart:'.$uid;
    }

    /**
     * 保存购物车
     *
     * @return void
     * @author 
     **/
    public function save($uid,$cart,$persist = false)
    {
        $cart = is_array($cart) ? serialize($cart) : $cart;
        $o2o_cart_key = $this->_get_rediskey($uid);
        $this->redis->set($o2o_cart_key,$cart);
    }

	/**
	 * 购物车判断商品是否可以团购购买
	 * @param type $items	商品
	 * @param type $uid		用户id
	 */
	public function check_cart_tuan_pro($items, $uid) {
		$tuanPro = array(
			7411 => array('taglm' => "1" . date('Ymd'), 'text' => '啊哟~10元1斤的云南褚橙为团购商品哦，仅成团用户可购买！'),
			8990 => array('taglm' => "2" . date('Ymd'), 'text' => '啊哟~元宵节龙眼200克3.9元，仅成团用户可购买！'),
			8989 => array('taglm' => "3" . date('Ymd'), 'text' => '啊哟~元宵节龙眼200克3.9元，仅成团用户可购买！'),
			8988 => array('taglm' => "4" . date('Ymd'), 'text' => '啊哟~元宵节龙眼200克3.9元，仅成团用户可购买！'),
			8987 => array('taglm' => "5" . date('Ymd'), 'text' => '啊哟~元宵节龙眼200克3.9元，仅成团用户可购买！'),
			9131 => array('taglm' => "6" . date('Ymd'), 'text' => '啊哟~椰青团购1个仅5元，仅成团用户可购买！'),
			9132 => array('taglm' => "7" . date('Ymd'), 'text' => '啊哟~椰青团购1个仅5元，仅成团用户可购买！'),
			9133 => array('taglm' => "8" . date('Ymd'), 'text' => '啊哟~椰青团购1个仅5元，仅成团用户可购买！'),
			9134 => array('taglm' => "9" . date('Ymd'), 'text' => '啊哟~椰青团购1个仅5元，仅成团用户可购买！'),
			9616 => array('taglm' => "10" . date('Ymd'), 'text' => '啊哟~果汁团购1个仅5元，仅成团用户可购买！'),
			9615 => array('taglm' => "11" . date('Ymd'), 'text' => '啊哟~果汁团购1个仅5元，仅成团用户可购买！'),
            10019 => array('taglm' => "12" . date('Ymd'), 'text' => '啊哟~果汁团购1个仅5元，仅成团用户可购买！'),
            10024 => array('taglm' => "13" . date('Ymd'), 'text' => '啊哟~果汁团购1个仅5元，仅成团用户可购买！'),
			10966 => array('taglm' => "15" . date('Ymd'), 'text' => '啊哟~荔枝团购1斤仅19.9元，仅成团用户可购买！'),
			10967 => array('taglm' => "16" . date('Ymd'), 'text' => '啊哟~荔枝团购1斤仅19.9元，仅成团用户可购买！'),
		);
		if (!empty($items)) {
//			$sku_id = $items['sku_id'];
//			$qty = $items['qty'];
			$product_ids = $items['product_id'];
			if (array_key_exists($product_ids, $tuanPro)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
							'member_uid' => $uid,
							'tag' => $tuanPro[$product_ids]['taglm'],
						))->get()->row_array();
				if (empty($tuan_member)) {
					return $tuanPro[$product_ids]['text'];
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
								'id' => $tuan_id,
								'product_id' => $tuanPro[$product_ids]['taglm'],
							))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return $tuanPro[$product_ids]['text'];
					}
				}
			}

			$proidList = $this->getTuanList();
			foreach ($proidList as $val) {
				$pro_id = $val['product_id'];
				$taglm = $val['id'] . date('md');
				if ($pro_id == $items['product_id']) {
					$tuan_member = $this->db->from('tuan_member')->where(array(
								'member_uid' => $uid,
								'tag' => $taglm
							))->get()->row_array();
					$return = "啊哟~" . $val['name'] . "为团购商品哦，仅成团用户可购买，请删除后重新提交订单！";
					if (empty($tuan_member)) {
						return $return;
					} else {
						$tuan_id = $tuan_member['tuan_id'];
						$tuan_status = $this->db->from("tuan")->where(array(
									'id' => $tuan_id,
									'product_id' => $taglm
								))->get()->row_array();
						if ($tuan_status['is_tuan'] != 1) {
							return $return;
						}
						$tuanCount = $this->db->from('tuan_member')->where('tuan_id', $tuan_id)->count_all_results();
						if ($tuanCount < $val['user_num']) {
							return $return;
						}
					}
				}
			}
		}
		return FALSE;
	}

	/**
     * 获取购物车
     *      
     * @return void
     * @author 
     **/
    public function get($uid)
    {
        $cart = array();
        $o2o_cart_key = $this->_get_rediskey($uid);
        $cart = $this->redis->get($o2o_cart_key);

        if ($cart) {
            $cart = @unserialize($cart);
        }

        return $cart;
    }

    function getProStock($sku_id){
        $this->db->select('stock');
        $this->db->from('product_price');
        $this->db->where('id',$sku_id);
        return $this->db->get()->row_array();
    }

    function get_active_limit($uid,$tag){
        $this->db->from('active_limit');
        $this->db->where(array('uid'=>$uid,'active_tag'=>$tag));
        $query = $this->db->get();
        $rows = $query->num_rows();
        return $rows;
    }

	private function getTuanList() {
		$today = date('Y-m-d H:i:s');
		$list = $this->db->query("select b.name,r.activeId as id,r.user_num,r.product_id from ttgy_active_base as b join ttgy_active_tuan_rule as r on b.id=r.activeId where r.rule_type>0 and b.startTime<'{$today}' and b.endTime>'{$today}' and b.type=4")->result_array();
		return $list;
	}

}