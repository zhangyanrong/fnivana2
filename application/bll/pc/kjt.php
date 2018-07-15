<?php

namespace bll\pc;

include_once 'pc.php';

class Kjt extends Pc
{
    function __construct()
    {
        parent::__construct();
        $this->ci->load->helper('public');
        $this->ci->load->model('kjt_model');
        $this->ci->load->model('product_model');

        $this->_filtercol = array(
            'device_limit',
            'card_limit',
            'jf_limit',
            'group_limit',
            'pay_limit',
            'first_limit',
            'active_limit',
            'delivery_limit',
            'pay_discount_limit',
            'free',
            'offline',
            'type',
            'free_post',
            'free_post',
            'is_tuan',
            'use_store',
            'xsh',
            'xsh_limit',
            'ignore_order_money',
            'group_pro',
            'iscard',
            'pmt_pass',
        );
    }

    /**
     * 获取跨境通商品信息
     */
    public function getKjtProducts($params)
    {
        $required = array(
			'active_id' => array('required' => array('code' => '300', 'msg' => 'active id can not be null')),
		);
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['active_id'];
			$result = $this->ci->memcached->get($memKey);
			if ($result) {
				return $result;
			}
		}

        // 获取跨境通商品ID
        $kjtResult = $this->ci->kjt_model->dump(array('id' => $params['active_id']), 'product_id');
        $kjtProductIds = explode(',', $kjtResult['product_id']);

        // 获取跨境通商品详情
        $field = 'product.id, product.long_photo, product.product_name, product.op_detail_place, product.summary, pp.price, pp.old_price';
        $whereIn = array(
            array('key' => 'product.id', 'value' => $kjtProductIds),
        );
        $join = array(
            array('table' => 'product_price pp', 'field' => 'pp.product_id=product.id'),
        );
        $result = $this->ci->product_model->selectProducts($field, '', $whereIn, '', '', '', '', $join);
        foreach ($result as &$item) {
            $item['long_photo'] = PIC_URL . $item['long_photo'];
            $item['summary'] = strip_tags($item['summary']);
            $item['old_price'] = $item['old_price']?: $item['price'];
            $item['op_detail_place'] = $item['op_detail_place']?: '';
        }

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['active_id'];
			$this->ci->memcached->set($memKey, $result, 1800);
		}

        return $result;
    }


    /**
     * 订单初始化
     *
     * @return void
     * @author
     **/
    public function orderInit($params)
    {
        $connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        $item       = $params['items'] ? @json_decode($params['items'],true) : '';
        //$item = array_shift($items);

        //不支持积分和优惠券
        $jfmoney     = 0;
        $card_number = '';
        //仅支持支付宝
        $payway['pay_parent_id'] = 1;
        $payway['pay_id'] = 0;

        if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
        if (!$item)        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }

        //一次仅允许一件商品
        $cart_items = array();
        $cart_items[] = array(
            'sku_id'     => $item['ppid'],
            'product_id' => $item['pid'],
            'qty'        => 1,
            'product_no' => $item['pno'],
            'item_type'  => 'kjt',
            'active_id'=>$item['active_id'],
        );

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->set_province($region_id);
        $res = $this->ci->bll_cart->setCart($cart_items);//something to do;
        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }

        $this->ci->load->bll('kjt');
        $rs = $this->ci->bll_kjt->orderInit();

        if (!$rs) {
            $code = $this->ci->bll_kjt->get_code();
            $error = $this->ci->bll_kjt->get_error();

            return array('code'=>$code ? $code : 300,'msg' => $error);
        }

        foreach ((array) $rs['cart_info']['items'] as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($k,$this->_filtercol)) {
                    unset($rs['cart_info']['items'][$key][$k]);
                }
            }
        }
        unset($rs['cart_info']['pmt_alert']);

        //by jackchen
        $rs['cart_info']['total_amount'] = number_format((float)$rs['cart_info']['total_amount'], 2,'.','');
        $rs['cart_info']['goods_amount'] = number_format((float)$rs['cart_info']['goods_amount'], 2,'.','');
        $rs['cart_info']['goods_cost'] = number_format((float)$rs['cart_info']['goods_cost'], 2,'.','');

        self::str($rs);
        return $rs;
    }

    /*
     *  创建订单
     */
    public function createOrder($params){
        $address_id = $params['address_id'] ? $params['address_id'] : 0;
        $connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        $item       = $params['items'] ? @json_decode($params['items'],true) : '';
        //$item = array_shift($items);
        $record_info       = $params['record_info'] ? @json_decode($params['record_info'],true) : '';
        $record_name = $record_info['record_name'] ? $record_info['record_name'] : '';
        $record_iDCardNumber = $record_info['record_iDCardNumber'] ? $record_info['record_iDCardNumber'] : '';
        $record_phoneNumber = $record_info['record_phoneNumber'] ? $record_info['record_phoneNumber'] : 0;
        $record_email = $record_info['record_email'] ? $record_info['record_email'] : '';

        //不支持积分和优惠券
        $jfmoney     = 0;
        $card_number = '';
        //仅支持支付宝
        $payway['pay_parent_id'] = 1;
        $payway['pay_id'] = 0;
        //用户身份信息
        $record_info = array(
            'name'=>$record_name,
            'id_card_type'=>0,
            'id_card_number'=>$record_iDCardNumber,
            'mobile'=>$record_phoneNumber,
            'email'=>$record_email
        );

        if (empty($record_name) || empty($record_iDCardNumber) || empty($record_phoneNumber) || empty($record_email)) {
            return array('code'=>300,'msg'=>'请完整填写您的实名信息');
        }
        if (!$this->validation_filter_id_card($record_iDCardNumber))        return array('code'=>300,'msg'=>'请正确填写实名信息身份证号');
        if (!$this->is_mobile($record_phoneNumber))        return array('code'=>300,'msg'=>'请正确填写实名信息手机号');
        if (!$this->is_eMail($record_email))        return array('code'=>300,'msg'=>'请正确填写邮箱');
        if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
        if (!$address_id)    return array('code'=>300,'msg'=>'请选择收货地址');
        if (!$item || !$item['pid'] || !$item['active_id'])        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }

        //一次仅允许一件商品
        $cart_items = array();
        $cart_items[] = array(
            'sku_id'     => $item['ppid'],
            'product_id' => $item['pid'],
            'qty'        => 1,
            'product_no' => $item['pno'],
            'item_type'  => 'kjt',
            'active_id'=>$item['active_id'],
        );

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->set_province($region_id);
        $this->ci->bll_cart->setCart($cart_items);//something to do
        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }

        $this->ci->load->bll('kjt');
        $rs = $this->ci->bll_kjt->createOrder($address_id, $record_info, $item['pid'], $item['ppid']);

        if (!$rs) {
            $code = $this->ci->bll_kjt->get_code();
            $error = $this->ci->bll_kjt->get_error();
            return array('code'=>$code ? $code : 300,'msg'=>$error);
        }

        self::str($rs);
        return $rs;
    }

    public static function str(&$array)
    {
        if (is_array($array)) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    self::str($value);
                } else {
                    $value = strval($value);
                }
            }
        } else {
            $array = strval($array);
        }
    }

    private function _ck_product_store($product_id){
        $this->ci->load->model('product_model');
        $ck_res = true;
        //check  store
        $p = $this->ci->product_model->getProductSkus($product_id);
        if($p['use_store']==1){
            $ck_res = false;
            foreach($p['skus'] as $val){
                if($val['store']>0){
                    $ck_res = true;
                    break;
                }
            }
        }
        return $ck_res;
    }

    private function is_mobile($mobile) {
        if(preg_match("/^1[0-9]{10}$/",$mobile))
            return TRUE;
        else
            return FALSE;
    }

    private function is_eMail($email) {
        if(preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",$email))
            return TRUE;
        else
            return FALSE;
    }

    private function validation_filter_id_card($id_card)
    {
        if(strlen($id_card) == 18)
        {
            return $this->idcard_checksum18($id_card);
        }
        elseif((strlen($id_card) == 15))
        {
            return false;
            // $id_card = $this->idcard_15to18($id_card);
            // return $this->idcard_checksum18($id_card);
        }
        else
        {
            return false;
        }
    }

    // 计算身份证校验码，根据国家标准GB 11643-1999
    private function idcard_verify_number($idcard_base)
    {
        if(strlen($idcard_base) != 17)
        {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++)
        {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    // 将15位身份证升级到18位
    private function idcard_15to18($idcard){
        if (strlen($idcard) != 15){
            return false;
        }else{
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){
                $idcard = substr($idcard, 0, 6) . '18'. substr($idcard, 6, 9);
            }else{
                $idcard = substr($idcard, 0, 6) . '19'. substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . $this->idcard_verify_number($idcard);
        return $idcard;
    }

    // 18位身份证校验码有效性检查
    private function idcard_checksum18($idcard){
        if (strlen($idcard) != 18){ return false; }
        $idcard_base = intval(substr($idcard, 0, 17));

        if ($this->idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))){
            return false;
        }else{
            return true;
        }
    }

}
