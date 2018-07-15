<?php
class B2o_parent_order_model extends MY_Model {

	function B2o_parent_order_model() {
		parent::__construct();
        $this->load->helper('public');
        $this->db_master = $this->load->database('default_master', TRUE);
	}

    public function table_name()
    {
        return 'b2o_parent_order';
    }

    /*
    * 更新订单
    */
    function update_order($data,$where){
        $this->_filter($where);
        $this->db->update('b2o_parent_order', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    /*
   *获取订单地址
   */
    function get_order_address($address_id=''){
        $this->db->select('id,name,province,city,area,address,telephone,mobile,flag,is_default as isDefault,lonlat,province_adcode,city_adcode,area_adcode,address_name');
        $this->db->from('user_address');
        $this->db->where('id',$address_id);
        $query = $this->db->get();
        $result = $query->result_array();

        if(!empty($address_id))
        {
            //pro
            $province_id = $result[0]['province'];
            $this->db->select('id,name');
            $this->db->from('area');
            $this->db->where('id',$province_id);
            $pro_info = $this->db->get()->row_array();
            $result[0]['province'] = array(
                'id'=>isset($pro_info['id']) ? $pro_info['id'] : '',
                'name'=>isset($pro_info['name']) ? $pro_info['name'] : '',
            );

            //city
            $city_id = $result[0]['city'];
            $this->db->select('id,name');
            $this->db->from('area');
            $this->db->where('id',$city_id);
            $city_info = $this->db->get()->row_array();
            $result[0]['city'] = array(
                'id'=>isset($city_info['id']) ? $city_info['id'] : '',
                'name'=>isset($city_info['name']) ? $city_info['name'] : '',
            );

            //area
            $area_id = $result[0]['area'];
            $this->db->select('id,name');
            $this->db->from('area');
            $this->db->where('id',$area_id);
            $area_info = $this->db->get()->row_array();
            $result[0]['area'] = array(
                'id'=>isset($area_info['id']) ? $area_info['id'] : '',
                'name'=>isset($area_info['name']) ? $area_info['name'] : '',
            );
        }
        else
        {
            foreach ($result as $key => $value) {
                $area_arr = array($value['province'],$value['city'],$value['area']);
                $this->db->select('id,name');
                $this->db->from('area');
                $this->db->where_in('id',$area_arr);
                $area_query = $this->db->get();
                $area_result = $area_query->result_array();
                $result[$key]['province'] = array(
                    'id'=>isset($area_result[0]['id']) ? $area_result[0]['id'] : '',
                    'name'=>isset($area_result[0]['name']) ? $area_result[0]['name'] : '',
                );
                $result[$key]['city'] = array(
                    'id'=>isset($area_result[1]['id']) ? $area_result[1]['id'] : '',
                    'name'=>isset($area_result[1]['name']) ? $area_result[1]['name'] : '',
                );
                $result[$key]['area'] = array(
                    'id'=>isset($area_result[2]['id']) ? $area_result[2]['id'] : '',
                    'name'=>isset($area_result[2]['name']) ? $area_result[2]['name'] : '',
                );
                if(empty($value['flag'])){
                    $result[$key]['flag'] = '';
                }
            }
        }
        return $result[0];
    }

    /*
    *提交订单短信验证
    */
    public function checkSendCode($items, $uid,$pay_parent_id=0,$order_address=array(), $order_id = 0) {
        $return_result = false;
        /*支付方式需要验证码&历史地址判断start*/
        if($pay_parent_id == 5){
            $return_result = true;
            if(empty($order_id)){
                if(!empty($order_address)){
                    $order_address['address'] = addslashes($order_address['address']);
                    $sql = "select count(o.id) as num from ttgy_b2o_parent_order as o join ttgy_b2o_parent_order_address as a on a.order_id=o.id where o.uid=".$uid." and a.address='".$order_address['address']."' and a.mobile='".$order_address['mobile']."' and o.pay_status=1";
                    $order_address_num = $this->db->query($sql)->row_array();
                    if(!empty($order_address_num) && $order_address_num['num']>=1){
                        $return_result = false;
                    }
                }
            }else{
                $addr = $this->db->query('select address,mobile from ttgy_b2o_parent_order_address where order_id=?', array($order_id))->row_array();
                $sql = "select count(o.id) as num from ttgy_b2o_parent_order as o join ttgy_b2o_parent_order_address as a on a.order_id=o.id where o.uid=".$uid." and a.address=? and a.mobile=? and o.pay_status=1";
                $order_address_num = $this->db->query($sql, array($addr['address'], $addr['mobile']))->row_array();
                if(!empty($order_address_num) && $order_address_num['num']>=1){
                    $return_result = false;
                }
            }
        }
        /*支付方式需要验证码&历史地址判断end*/

        //组合支付－增加短信验证
        if(!empty($order_id))
        {
            $orderInfo = $this->getInfoById($order_id);
            if($orderInfo['use_money_deduction'] >0)
            {
                $return_result = true;
                $addr = $this->db->query('select address,mobile from ttgy_b2o_parent_order_address where order_id=?', array($order_id))->row_array();
                $sql = "select count(o.id) as num from ttgy_b2o_parent_order as o join ttgy_b2o_parent_order_address as a on a.order_id=o.id where o.uid=".$uid." and a.address=? and a.mobile=? and o.pay_status=1";
                $order_address_num = $this->db->query($sql, array($addr['address'], $addr['mobile']))->row_array();
                if(!empty($order_address_num) && $order_address_num['num']>=1){
                    $return_result = false;
                }
            }
        }

        /*强制发送验证码商品判断start*/
        $need_send_code_pro  =  $this->config->item("need_send_code_pro");
        if (!empty($items) && !empty($need_send_code_pro)) {
            foreach ($items as $key => $value) {
                if( in_array($value['product_id'], $need_send_code_pro) ){
                    $return_result = true;
                    break;
                }
            }
        }
        /*强制发送验证码商品判断end*/

        /*白名单start*/
        if($uid==5185553 || $uid==504884 || $uid==4643775 || $uid == 1727612 || $uid == 803007 ){
            $return_result = false;
        }
        /*白名单end*/
        return $return_result;
    }

    /*
    *查看订单信息根据ID
    */
    function getInfoById($id){
        $this->db->select('*');
        $this->db->from('b2o_parent_order');
        $this->db->where('id',$id);
        $order_info = $this->db->get()->row_array();
        return $order_info;
    }

    /*
    *  更新发票状态
    */
    function update_order_invoice($data,$where){
        $this->db->where($where);
        $this->db->update('b2o_parent_order_invoice', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    //check券卡类型商品
    function checkCardtypeProd($order_id){
        $sql = "select p.id from ttgy_b2o_parent_order o join ttgy_b2o_parent_order_product op on o.id=op.order_id join ttgy_product p on op.product_id=p.id where o.id=".$order_id." and p.iscard=1";
        $res = $this->db->query($sql)->row_array();
        if($res) return true;
        else return false;
    }

    /*
     * 变更支付方式
     */
    function set_ordre_payment($pay_name,$pay_parent_id,$pay_id,$order_id){
        $sql = "update ttgy_b2o_parent_order set pay_name='".$pay_name."',pay_parent_id=".$pay_parent_id.",pay_id='".$pay_id."',version=version+1 where id=".$order_id;
        $this->db->query($sql);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    /*
   * 查询发票地址记录
   */
    function get_order_invoice($order_id,$state){
        $this->db_master->select("id");
        $this->db_master->from("b2o_parent_order_invoice");
        $this->db_master->where(array("order_id"=>$order_id,"is_valid"=>$state));
        $this->db_master->limit(1);
        $query=$this->db_master->get();
        $result=$query->row_array();
        if(isset($result['id'])) {
            $invoice_id = intval($result['id']);
            return $invoice_id;
        }else{
            return false;
        }
    }

    /*
    *获取商品信息
    */
    function selectOrderProducts($field,$where='',$where_in='',$order='',$join=''){
        $this->db->select($field);
        $this->db->from('b2o_parent_order_product');
        if(!empty($where)){
            $this->db->where($where);
        }
        if(!empty($where_in)){
            foreach($where_in as $val){
                $this->db->where_in($val['key'],$val['value']);
            }
        }
        if(!empty($order)){
            $this->db->order_by($order);
        }
        if(!empty($join)){
            foreach($join as $val){
                $this->db->join($val['table'],$val['field'],$val['type']);
            }
        }
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *获取订单信息
    */
    function selectOrder($field,$where,$join=''){
        $where['other_msg !='] = 'thj';
        $this->db_master->select($field);
        $this->db_master->from('b2o_parent_order');
        $this->db_master->where($where);
        if(!empty($join)){
            foreach($join as $val){
                $this->db_master->join($val['table'],$val['field'],$val['type']);
            }
        }
        $result = $this->db_master->get()->row_array();
        return $result;
    }

    /*
    *获取历史操作订单信息
    */
    function get_order_id($uid){
        $this->db_master->select("id");
        $this->db_master->from("b2o_parent_order");
        $this->db_master->where(array("order_status" => "0","uid"=>$uid));
        $this->db_master->order_by("time","desc");
        $this->db_master->limit(1);
        $query=$this->db_master->get();
        $result=$query->row_array();
        if(isset($result['id'])) {
            $order_id = intval($result['id']);
            return $order_id;
        }else{
            return false;
        }
    }

    /*
    *获取上一张订单信息
    */
    function preOrderInfo($uid){
        $this->db->select("order_name,pay_parent_id,pay_id,address_id");
        $this->db->from("b2o_parent_order");
        $this->db->where(array("uid"=> $uid, "order_status"=>1,"order_type !="=>'3',"order_type !="=>'4',"pay_parent_id !="=>'6','is_enterprise'=>''));
        $this->db->order_by("time","desc");
        $this->db->limit(1);
        $query=$this->db->get();
        $result=$query->row_array();
        if(isset($result['order_name']))
        {
            return array(
                'pay_parent_id'=>$result['pay_parent_id'],
                'pay_id'=>$result['pay_id'],
                'address_id'=>$result['address_id']
            );
        }
        else
        {
            return;
        }
    }


    /*
     *插入预生成订单
     */
    function generate_order($table, $fields, $prefix="")
    {
        if(isset($table) && isset($fields)){
            $order_name = date("ymdis").rand_code(4);
            if($table == "trade"){
                $order_name = "T".$order_name;
                $fields[array_search("",$fields)]=$order_name;

                $fields['post_at']=date("Y-m-d H:i:s");
                if($fields['money']>0)
                {
                    $fields['type']="income";
                    $fields['status']=isset($fields['status'])?$fields['status']:"等待款项到帐";
                }
                else
                {
                    $fields['type']="outlay";
                }
            } else {
                $order_name = $prefix.$order_name;
                $fields[array_search("",$fields)]=$order_name;
            }

            $insert_query = $this->db->insert_string($table,$fields);
            // $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
            $query = $this->db->query($insert_query);
            $order_id = $this->db->insert_id();
            if($order_id=="0"){
                $order_name=$this->generate_order($table, $fields);
            }
            $this->generate_order_name = $order_name;
            return  $order_id;
        }else{
            return false;
        }
    }

    /*
    *   订单可操作状态验证
    */
    public function check_cart_pro_status($cart_info){
        /*判断类型：
            或：有一件商品满足即可;
            和：需要所有商品满足
        */
        $result['card_limit'] = '0';//不能使用优惠券，或；
        $result['jf_limit'] = '0';//不能使用积分，或；
        $result['pay_limit'] = '0';//不能使用线下支付，或；
        $result['active_limit'] = '0';//不参加营销活动，或；
        $result['delivery_limit'] = '0';//只能2-3天送达，或；
        $result['group_limit'] = '0';//不能单独购买，和；
        $result['pay_discount_limit'] = '0';//不能参加支付折扣活动，和；
        $result['free'] = '0';//是企业专享订单，或；
        $result['offline'] = '0';//是线下订单,或；
        $result['type'] = '1';//包含生鲜商品，或；
        $result['free_post'] = '0';//官网包邮，或；
        $result['free_post'] = '0';//手机包邮，或；
        $result['ignore_order_money'] = '0';//无视起送规则，收取运费，或；
        $result['iscard'] = '0';//是否包含券卡，或；
        $result['expect'] = '0';//单独购买；

        $cart_count = 0;
        $group_limit_count = 0;
        $pay_discount_limit_count = 0;
        $card_limit_count = 0;
        $cmoney = 0;
        foreach ($cart_info['products'] as $key => $value) {
            $cart_count++;
            if($value['card_limit']=='1'){
                $card_limit_count++;
                $result['card_limit_pro'] = $value['name'];
            }
            if($value['jf_limit']=='1'){
                $result['jf_limit'] = '1';
                $result['jf_limit_pro'] = $value['name'];
            }
            if($value['pay_limit']=='1'){
                $result['pay_limit'] = '1';
                $result['pay_limit_pro'] = $value['name'];
            }
            if($value['active_limit']=='1'){
                $result['active_limit'] = '1';
                $result['active_limit_pro'] = $value['name'];
            }
            if($value['delivery_limit']=='1'){
                $result['delivery_limit'] = '1';
                $result['delivery_limit_pro'] = $value['name'];
            }
            if($value['group_limit']=='1'){
                $group_limit_count++;
            }
            if($value['pay_discount_limit']=='1'){
                $pay_discount_limit_count++;
            }
            if($value['free']=='1'){
                $result['free'] = '1';
                $result['free_pro'] = $value['name'];
            }
            if($value['offline']=='1'){
                $result['offline'] = '1';
                $result['offline_pro'] = $value['name'];
            }
            if($value['type']=='4'){
                $result['type'] = '2';
            }
            if($value['free_post']=='1'){
                $result['free_post'] = '1';
                $result['free_post_pro'] = $value['name'];
            }
            if($value['free_post']=='1'){
                $result['free_post'] = '1';
                $result['free_post_pro'] = $value['name'];
            }
            if($value['ignore_order_money']=='1'){
                $result['ignore_order_money'] = '1';
                $result['ignore_order_money_pro'] = $value['name'];
            }
            if($value['iscard']=='1'){
                $result['iscard'] = '1';
                $result['iscard_pro'] = $value['name'];
                $cmoney += $value['amount'];
            }
            if($value['expect']=='1'){
                $result['expect'] = '1';
            }
        }

        //当预售商品为购物车单独商品时，可以下单
        if($cart_count==1){
            $result['expect'] = 0;
        }

        if($cart_count==$group_limit_count){
            $result['group_limit'] = '1';
        }
        if($cart_count==$pay_discount_limit_count){
            $result['pay_discount_limit'] = '1';
        }
        // if($cart_count==$card_limit_count){
        if($card_limit_count>0){
            $result['card_limit'] = '1';
        }
        $result['cmoney'] = $cmoney;
        return $result;
    }

    /*
    *重置优惠券
    */
    function init_order_card($order_id){
        $data = array(
            'use_card'=>'',
            'card_money'=>0,
        );
        $this->db->where('id',$order_id);
        $this->db->update('b2o_parent_order', $data);
        return true;
    }

    /*
    *重置积分
    */
    function init_order_jf($order_id){
        $data = array(
            'use_jf'=>0,
            'jf_money'=>0,
        );
        $this->db->where('id',$order_id);
        $this->db->update('b2o_parent_order', $data);
        return true;
    }

    /*
    *重置余额抵扣
    */
    function deduction_init($order_id){
        $this->db->where("id",$order_id);
        $this->db->update("b2o_parent_order",array("use_money_deduction" =>0));
        return true;
    }

    //重置支付折扣,支付方式
    function init_pay_discount($order_id){
        $this->db->where("id",$order_id);
        $this->db->update("b2o_parent_order",array("new_pay_discount" =>0,"pay_parent_id"=>'',"pay_id"=>''));
        return true;
    }

    /*
    *获取优惠券积分使用情况
    */
    function get_card_jf($id){
        $this->db->select("use_jf,use_card,pay_discount,new_pay_discount,jf_money,card_money");
        $this->db->from("b2o_parent_order");
        $this->db->where(array("id"=>$id));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result;
    }

    /*
    *初始化订单
    */
    function init_order($order_id,$cart_info=array(),$o_address_id = ''){
        $this->db_master->select("order_name,uid,pay_parent_id,pay_id,pay_name,use_card,use_jf,address_id,shtime,stime,pay_discount,new_pay_discount,fp,fp_dz,fp_id_no");
        $this->db_master->from("b2o_parent_order");
        $this->db_master->where(array("id"=>$order_id));
        $query=$this->db_master->get();
        $result=$query->row_array();

        if($o_address_id){
            $order_address = $this->get_order_address($o_address_id);
            $data['order_address'] = $order_address;
        }
        $data['order_name'] = $result['order_name'];
        $data['address_id'] = $o_address_id;

        //重置支付方式
        if($result['pay_parent_id'] == 0 || $result['pay_parent_id']==10 )
        {
            $this->db->where(array("id"=>$order_id));
            $this->db->update("order",array('pay_parent_id'=>'7','pay_id'=>'0','pay_name'=>'微信支付'));
            $result['pay_parent_id'] = 7;
        }

        $data['order_id'] = $order_id;
        $data['uid'] = $result['uid'];
        $data['pay_parent_id'] = $result['pay_parent_id'];
        $data['pay_id'] = $result['pay_id'];
        $data['pay_name'] = $result['pay_name'];

        //发票
        $has_invoice = 0;
        $data['has_invoice'] = $has_invoice;
        $no_invoice_message = '';
        if(!$has_invoice){
            $no_invoice_message = '您选择的支付方式不支持开发票';
        }
        $data['no_invoice_message'] = $no_invoice_message;

        //电子
        $this->load->model('region_model');
        $user_address_info = $this->region_model->get_province_id($result['address_id'],$result['uid']);
        if (in_array($user_address_info['province'], array(1, 54351, 106340, 106092))) {
            $data['support_einvoice'] = 1;
        } else {
            $data['support_einvoice'] = 0;
        }

        $data['use_card']=$result['use_card'];
        $data['use_jf']=$result['use_jf'];
        $data['pay_discount'] = $result['pay_discount'];
        $data['new_pay_discount'] = $result['new_pay_discount'];

        /*发票信息start*/
        $data['fp'] = $result['fp'];
        if(!empty($result['fp_dz'])){
            $this->db->from('b2o_parent_order_invoice');
            $this->db->where('order_id',$order_id);
            $invoice_result = $this->db->get()->row_array();
            if(!empty($invoice_result)){
                $this->db->select('id,name');
                $this->db->from('area');
                $this->db->where_in('id',array($invoice_result['province_id'],$invoice_result['city_id'],$invoice_result['area_id']));
                $area_info = $this->db->get()->result_array();
                if(!empty($area_info) && count($area_info)==3){

                }else{
                    $data = array(
                        'fp'=>'',
                        'fp_dz'=>'',
                        'invoice_money'=>0,
                    );
                    $where = array(
                        'id'=>$order_id
                    );
                    $this->update_order($data,$where);
                    $this->delete_order_invoice($order_id);
                    $data['fp'] = '';
                }
            }
        }
        /*发票信息end*/

        return $data;
    }

    /*
    *删除订单发票信息
    */
    function delete_order_invoice($order_id){
        $this->db->delete('b2o_parent_order_invoice',array('order_id'=>$order_id));
    }


    /*
   *发票信息
   */
    function has_invoice($pay_parent=0, $pay_son=0) {
        $current_payment = $pay_parent;
        if ($pay_son) {
            $current_payment = $current_payment . '-' . $pay_son;
        }
        $payments = $this->config->item('no_invoice');
        return array_key_exists($current_payment, $payments) ? 0 : 1;
    }


    /*
   * 是否互斥
   * */
    function fan($cart_arr,$uid){
        $time = date("Y-m-d H:i:s");
        $sql = "select m_id,m_productId,m_type,m_desc from ttgy_mutex where m_btime<='".$time."' and m_etime>='".$time."'";
        $mutex = $this->db->query($sql)->result_array();
        if(!empty($mutex)){
            foreach($mutex as $kk=>$vv){
                $proArr = explode(',',$vv['m_productId']);
                $count_aa = 0;
                if(!empty($cart_arr)){
                    foreach( $cart_arr as $key=>$val)
                    {
                        if( in_array($val['product_id'], $proArr) )
                        {
                            $count_aa++;
                        }
                    }
                    if($count_aa>1){
                        if(!empty($vv['m_desc'])){
                            return array('code' => '300', 'msg' => $vv['m_desc']);
                        }else{
                            if($vv['m_id']>12&&$vv['m_id']<25){
                                return array('code' => '300', 'msg' => '您的购物车中包含5月3日发货的美国加州樱桃，届时此商品可能会缺货，建议您单独下单哦~');
                            }else{
                                return array('code' => '300', 'msg' => '单笔订单只能领取一份免费水果哟，么么哒');

                            }
                        }
                    }
                }
            }
        }
    }

    //重置支付方式
    function init_pay($order_id){
        $this->db->where("id",$order_id);
        $this->db->update("b2o_parent_order",array('pay_parent_id'=>'7','pay_id'=>'0','pay_name'=>'微信支付'));
        return true;
    }

    /*
    * 获取订单地址
    */
    function get_user_addr($uid,$address_id){
        $this->db->select('id,name,province,city,area,address,telephone,mobile,flag,is_default as isDefault,lonlat,province_adcode,city_adcode,area_adcode,address_name');
        $this->db->from('user_address');
        $this->db->where(array("uid"=>$uid,"id"=>$address_id));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result;
    }

    /*
    *新增订单地址
    */
    function addOrderAddr($insert_data) {
        $this->db->insert("b2o_parent_order_address",$insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    //购物车营销记录
    public function add_order_cart($order_name,$cart_info){
        $insert_data  = array(
            'order_name'=>$order_name,
            'items'=>json_encode($cart_info['items']),
            'products'=>json_encode($cart_info['products']),
            'promotions'=>json_encode($cart_info['promotions']),
            'total'=>json_encode($cart_info['total']),
        );
        $this->db->insert('b2o_order_cart', $insert_data);
    }

    /*
    *用户赠品处理
    */
    function receive_user_gift($uid,$order_id,$user_gift_id){
        $this->db->select('active_type,active_id');
        $this->db->from('user_gifts');
        $this->db->where(array('id'=>$user_gift_id,'uid'=>$uid,'has_rec'=>0));
        $result = $this->db->get()->row_array();
        $user_gift_data = array(
            'has_rec'=>'1',
            'bonus_b2o_order'=>$order_id,
        );
        $this->db->where(array('id'=>$user_gift_id,'uid'=>$uid,'has_rec'=>0));
        $this->db->update('user_gifts', $user_gift_data);
        if(!$this->db->affected_rows()){
            return array("code"=>"300","msg"=>"赠品领取错误，请重新领取");
        }
        if($result['active_type']=='1'){
            $trade_gift_data = array(
                'has_rec'=>'1',
                'bonus_order'=>$order_id,
            );
            $this->db->where(array('id'=>$result['active_id'],'uid'=>$uid));
            $this->db->update('trade', $trade_gift_data);
            if(!$this->db->affected_rows()){
                return array("code"=>"300","msg"=>"赠品领取错误，请重新领取");
            }
        }
    }

    /*
    *新增订单商品
    */
    function addOrderProduct($insert_data) {
        $this->db->insert("b2o_parent_order_product",$insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /**
     * 订单明细积分
     *
     * @return void
     * @author
     **/
    public function get_order_product_score($uid,$cart_item){
        $score = 0;
        $this->load->model('user_model');
        $user = $this->user_model->selectUser('user_rank',array('id'=>$uid));
        $user_rank = $user['user_rank'] ? $user['user_rank'] : 1;
        //fix 积分比例
        $cart_price = $cart_item['price'] * $cart_item['qty'];
        $score = $this->user_model->cal_rank_score($cart_price,$user_rank,$msg,$cart_item['jf_percent']);
        //$score = $this->user_model->cal_rank_score($cart_item['price'],$user_rank,$msg);
        return $score;
    }

    /**
     * 订单包裹
     *
     * @return void
     * @author
     **/
    public function add_order_package($order_name,$package){
        $insert_data  = array(
            'order_name'=>$order_name,
            'content'=>json_encode($package),
        );
        $this->db->insert('b2o_order_package', $insert_data);
    }

    /*
    * 获取订单包裹
    */
    function get_order_package($order_name){
        $this->db->select("id,order_name,content");
        $this->db->from("b2o_order_package");
        $this->db->where(array("order_name"=>$order_name));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result;
    }

    /*
     *获取发票抬头列表
     */
    function get_invoice_title_list($uid){
        $this->db->distinct();
        $this->db->select('fp');
        $this->db->from('order');
        $this->db->where(array('uid'=>$uid,'order_status'=>'1','fp !='=>'','fp !='=>'个人'));
        $order = $this->db->get()->result_array();

        $res = $order;
        return $res;
    }

    /*
   *获取电子发票
   */
    function getDzFp($order_names){
        $this->db->select('order_name,mobile');
        $this->db->from('order_einvoices');
        $this->db->where_in('order_name',$order_names);
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *   删除电子发票
    */
    function delete_DzFp($order_id){
        $this->db->delete('order_einvoices',array('order_name'=>$order_id));
    }

    /*
   *插入订单发票信息
   */
    function add_order_invoice($order_id,$fp_info){
        $this->db->select('name');
        $this->db->from('area');
        $this->db->where_in('id',array($fp_info['fp_province'],$fp_info['fp_city'],$fp_info['fp_area']));
        $area_info = $this->db->get()->result_array();
        if(!empty($area_info) && count($area_info)==3){
            $this->db->from('b2o_parent_order_invoice');
            $this->db->where('order_id',$order_id);
            $query = $this->db->get();
            if($query->num_rows() > 0){
                $data = array(
                    "name"=>$fp_info['fp'],
                    "address"=>$fp_info['fp_dz'],
                    "mobile"=>$fp_info['fp_mobile'],
                    "username"=>$fp_info['fp_name'],
                    "province"=>$area_info[0]['name'],
                    "city"=>$area_info[1]['name'],
                    "area"=>$area_info[2]['name'],
                    "province_id"=>$fp_info['fp_province'],
                    "city_id"=>$fp_info['fp_city'],
                    "area_id"=>$fp_info['fp_area'],
                    "kp_type"=>$fp_info['fp_kp'],
                );
                $this->db->where('order_id',$order_id);
                $this->db->update('b2o_parent_order_invoice', $data);
            }else{
                $this->db->insert(
                    "b2o_parent_order_invoice",
                    array(
                        "order_id" => $order_id,
                        "name"=>$fp_info['fp'],
                        "address"=>$fp_info['fp_dz'],
                        "mobile"=>$fp_info['fp_mobile'],
                        "username"=>$fp_info['fp_name'],
                        "province"=>$area_info[0]['name'],
                        "city"=>$area_info[1]['name'],
                        "area"=>$area_info[2]['name'],
                        "province_id"=>$fp_info['fp_province'],
                        "city_id"=>$fp_info['fp_city'],
                        "area_id"=>$fp_info['fp_area'],
                        "kp_type"=>$fp_info['fp_kp'],
                    )
                );
            }
        }
    }

    /*
     *获取订单发票信息
     */
    function get_invoice_info($order_id){
        $this->db->select('fp,fp_dz,fp_id_no,kp_type');
        $this->db->from('b2o_parent_order');
        $this->db->where('id',$order_id);
        $result = $this->db->get()->row_array();

        $type = array(
            ['id' => 3, 'name' => '明细', 'desc' => ''],
            ['id' => 4, 'name' => '商品大类', 'desc' => '根据购买商品,开具其所属大类']
        );
        $type2Name = array('1'=>'水果','2'=>'食品','3'=>'明细','4'=>'商品大类');

        $invoice_info = array(
            'invoice_type'=>1,
            'invoice_username'=>'',
            'invoice_address_type'=>1,
            'invoice_address'=>'使用收货地址',
            'invoice_mobile'=>'',
            'invoice_name'=>'',
            'invoice_province_key'=>'',
            'invoice_province'=>'',
            'invoice_city_key'=>'',
            'invoice_city'=>'',
            'invoice_area_key'=>'',
            'invoice_area'=>'',
            'kp_type'=>3,
            'fp_id_no'=>'',
            'type'=>$type,
            'kpTypeName'=>'',
        );

        if(empty($result)){
            return $invoice_info;
        }

        if(!empty($result['fp_dz'])){
            $this->db->from('b2o_parent_order_invoice');
            $this->db->where('order_id',$order_id);
            $invoice_result = $this->db->get()->row_array();
            if(!empty($invoice_result)){
                $this->db->select('id,name');
                $this->db->from('area');
                $this->db->where_in('id',array($invoice_result['province_id'],$invoice_result['city_id'],$invoice_result['area_id']));
                $area_info = $this->db->get()->result_array();
                if(!empty($area_info) && count($area_info)==3){
                    $invoice_info['invoice_address_type'] = 2;
                    $invoice_info['invoice_address'] = $invoice_result['address'];
                    $invoice_info['invoice_mobile'] = $invoice_result['mobile'];
                    $invoice_info['invoice_name'] = $invoice_result['name'];
                    $invoice_info['invoice_province_key'] = $invoice_result['province_id'];
                    $invoice_info['invoice_province'] = $invoice_result['province'];
                    $invoice_info['invoice_city_key'] = $invoice_result['city_id'];
                    $invoice_info['invoice_city'] = $invoice_result['city'];
                    $invoice_info['invoice_area_key'] = $invoice_result['area_id'];
                    $invoice_info['invoice_area'] = $invoice_result['area'];
                    $invoice_info['kp_type'] = $invoice_result['kp_type'];
                    $invoice_info['invoice_username'] = $invoice_result['username'];
                    $invoice_info['invoice_type'] = 0;
                    $invoice_info['fp_id_no'] = $invoice_result['fp_id_no'];

                    $invoice_info['type'] = $type;
                    $invoice_info['kpTypeName'] = $type2Name[$invoice_result['kp_type']];

                }else{
                    $data = array(
                        'fp_dz'=>'',
                        'invoice_money'=>0,
                    );
                    $where = array(
                        'id'=>$order_id
                    );
                    $this->update_order($data,$where);
                    $this->delete_order_invoice($order_id);
                }
            }
        }

        if(!empty($result['fp']) && $result['fp']!='个人'){
            $invoice_info['invoice_type'] = 0;
            $invoice_info['invoice_name'] = $result['fp'];
            $invoice_info['fp_id_no'] = $result['fp_id_no'];
            $invoice_info['type'] = $type;
            $invoice_info['kpTypeName'] = $type2Name[$result['kp_type']];
            $invoice_info['kp_type'] = $result['kp_type'];
        }

        return $invoice_info;
    }

    //获取预生成订单地区
    function getIorderArea($order_id){
        $this->db->select('address_id');
        $this->db->from('b2o_parent_order');
        $this->db->where("id",$order_id);
        $query = $this->db->get();
        $result = $query->row_array();
        $address_id = $result['address_id'];
        if($address_id){
            $this->db->select('uid,name,province,city,area,address,telephone,mobile');
            $this->db->from('user_address');
            $this->db->where("id",$address_id);
            $query = $this->db->get();
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

    /*
   *设备号验证
   */
    function check_device($cart_info,$device_code=''){
        if(!empty($cart_info) && $device_code!=''){
            $product_ids = array();
            foreach ($cart_info['products'] as $key => $value) {
                $product_ids[] = $value['product_id'];
            }
            $this->db->select('id,product_name,is_xsh_time_limit');
            $this->db->from('product');
            $this->db->where('device_limit','1');
            $this->db->where_in('id',$product_ids);
            $result = $this->db->get()->result_array();
            if(!empty($result)){
                $device_product_id = array();
                foreach ($result as $key => $value) {
                    $special_pids = array(9715,9720,7878,9612,9886,9950,9951,10124,10154,10254,10253,10378,10440,10439,10518,10519,10520);//每人每天限购
                    if(in_array($value['id'],$special_pids)||$value['is_xsh_time_limit']==0){
                        $this->db->select('b2o_parent_order.operation_id');
                        $this->db->from('device_limit');
                        $this->db->join('b2o_parent_order','b2o_parent_order.id=device_limit.order_id');
                        $this->db->where(array('device_limit.product_id'=>$value['id'],'device_limit.device_code'=>$device_code,'b2o_parent_order.time >='=>date('Y-m-d 00:00:00'),'b2o_parent_order.time <='=>date('Y-m-d 59:59:59')));
                        $device_limit_check_result = $this->db->get()->row_array();
                        if(!empty($device_limit_check_result) && $device_limit_check_result['operation_id']!='5'){
                            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                                $this->load->library("memcached");
                                $yt_devlice_limit_cf_num = $this->memcached->get('yt_devlice_limit_cf_num')?$this->memcached->get('yt_devlice_limit_cf_num'):0;
                                $yt_devlice_limit_cf_num += 1;
                                $this->memcached->set('yt_devlice_limit_cf_num', $yt_devlice_limit_cf_num, '60*60*24*30');
                            }
                            return array("code"=>"300","msg"=>"您购买的".$value['product_name']."为活动商品，一个手机(设备)每天只能购买一次，请删除后重新提交订单");
                        }else{
                            $device_product_id[] = $value['id'];
                        }
                    }else{
                        $this->db->select('b2o_parent_order.operation_id');
                        $this->db->from('device_limit');
                        $this->db->join('b2o_parent_order','b2o_parent_order.id=device_limit.order_id');
                        $this->db->where(array('device_limit.product_id'=>$value['id'],'device_limit.device_code'=>$device_code));
                        $device_limit_check_result = $this->db->get()->row_array();
                        if(!empty($device_limit_check_result) && $device_limit_check_result['operation_id']!='5'){
                            return array("code"=>"300","msg"=>"您购买的".$value['product_name']."为活动商品，一个手机(设备)只能购买一次，请删除后重新提交订单");
                        }else{
                            $device_product_id[] = $value['id'];
                        }
                    }
                }
                if(!empty($device_product_id)){
                    return array("code"=>"200","msg"=>serialize($device_product_id));
                }
            }
        }
    }


    /*
    *重置优惠券
    */
    function init_order_fresh($order_id){
        $data = array(
            'fresh_discount'=>'0.00',
            'fresh_no'=>'',
        );
        $this->db->where('id',$order_id);
        $this->db->update('b2o_parent_order', $data);
        return true;
    }

    /*
    *重置订单包裹
    */
    function init_order_package($order_name,$package){
        $data = array(
            'content'=>json_encode($package),
        );
        $this->db->where('order_name',$order_name);
        $this->db->update('b2o_order_package', $data);
        return true;
    }


    /*
    *重置积点
    */
    function init_order_jd($order_id){
        $data = array(
            'jd_discount'=>'0.00',
        );
        $this->db->where('id',$order_id);
        $this->db->update('b2o_parent_order', $data);
        return true;
    }

}
