<?php
class Cardchange_model extends MY_Model {

    function Cardchange_model() {
        parent::__construct();
        $this->load->helper('public');
    }

    function get_pro_card_info($card_number){
        $this->db->select('card_money,card_passwd,to_date,is_used,is_sent,is_delete,product_id,start_time,is_expire,hide_price,is_auto,is_freeze,card_type, error_num, pay_method, pay_money');
        $this->db->from('pro_card');
        $this->db->where('card_number',$card_number);
        $query = $this->db->get();
        $card_info = $query->row_array();
        return $card_info;
    }

    function addUserAddress($params,$uid){
        $address_data = array(
            'name'=>$params['name'],
            'uid'=>$uid,
            'province'=>$params['province'],
            'city'=>$params['city'],
            'area'=>$params['area'],
            'address'=>$params['address'],
            'mobile'=>$params['mobile'],
            'telephone'=>isset($params['telephone'])?$params['telephone']:''
        );
        $this->db->insert('user_address',$address_data);
        $address_id = $this->db->insert_id();
        return $address_id;
    }

    function get_order_region($address_id, $province = '106092'){
        if ($address_id) {
            $sql = "select province from ttgy_user_address where id = {$address_id}";
            $query = $this->db->query($sql);
            $result = $query->row_array();
            $province = $result['province'];
        }
        $area_refelect = $this->config->item("area_refelect");
        $order_region = 1;
        foreach ($area_refelect as $key => $value) {
            if(in_array($province, $value)){
                $order_region = $key;
                break;
            }
        }
        return $order_region;
     }

     function getPrice($price_id){
        $this->db->select("price");
        $this->db->from("product_price");
        $this->db->where("id",$price_id);
        $price_query = $this->db->get();
        $price_result = $price_query->row_array();
        return $price_result['price'];
     }

     function generate_order($table,$fields){
        $order_name = date("ymdi").$this->randcode();
        $fields['order_name'] = $order_name;
        $insert_query = $this->db->insert_string($table,$fields);
        $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
        $query = $this->db->query($insert_query);
        $order_id = $this->db->insert_id();
        if($order_id=="0"){
            $order_result=$this->generate_order($table, $fields);
            $order_name = $order_result['order_name'];
            $order_id = $order_result['order_id'];
        }
        return array('order_name'=>$order_name,'order_id'=>$order_id);
    }

    private function randcode()
     {
         $randcode="";
         for($i = 0; $i<4 ;$i++){
             $randcode .= rand(0,9);
         }
         return $randcode;
     }

     function orderAddPro($order_id,$price_id,$card_money=0){
        $this->db->select("product.product_name,product_price.price,product_price.product_no,product_price.volume,product_price.unit,product_price.product_id,product.group_pro");
        $this->db->from("product_price");
        $this->db->join("product","product.id=product_price.product_id");
        $this->db->where("product_price.id",$price_id);
        $query = $this->db->get();
        $result = $query->row_array();

        $order_pro_type = 1;
        if(!empty($result['group_pro'])){
          $group_pro_id_arr = explode(',', $result['group_pro']);
          $this->db->select('product.id,product.product_name,product_price.product_no,product_price.volume,product_price.unit,product_price.price');
          $this->db->from('product');
          $this->db->join('product_price','product_price.product_id=product.id');
          $this->db->where_in('product.id',$group_pro_id_arr);
          $group_pro_result = $this->db->get()->result_array();
          $count_money = 0;
          $all_money = 0;
          $c = count($group_pro_result);
          $i = 1;
          foreach ($group_pro_result as $key => $value) {
              $all_money = bcadd($all_money, $value['price'],2);
          }
          foreach ($group_pro_result as $gpr_value) {
              $product_name = addslashes($gpr_value['product_name']);
              if($card_money > 0){
                  if($i == $c){
                      $gpr_value['price'] = bcsub($card_money, $count_money ,2);
                  }else{
                      $gpr_value['price'] = bcmul($card_money,$gpr_value['price']/$all_money,2);
                      $count_money = bcadd($count_money, $gpr_value['price'],2);
                  }
              }
              $total_money = $gpr_value['price'];
              $gg_name = $gpr_value['volume'].'/'.$gpr_value['unit'];
              $this->db->query("INSERT INTO ttgy_order_product (`order_id`,`product_name`,`product_id`,`product_no`,`gg_name`,`price`,`qty`,`score`,`type`,`total_money`,`group_pro_id`) VALUES ('{$order_id}','{$product_name}','{$gpr_value['id']}','{$gpr_value['product_no']}','{$gg_name}','{$gpr_value['price']}','1','0','{$order_pro_type}','{$total_money}','{$result['product_id']}')");
              $i++;

              $this->orderAddProGift($order_id, $gpr_value['id']);
          }
        }else{
          if($card_money > 0) $result['price'] = $card_money;
          $product_name = addslashes($result['product_name']);
          $gg_name = $result['volume'].'/'.$result['unit'];
          $total_money = $result['price'];
          $this->db->query("INSERT INTO ttgy_order_product (`order_id`,`product_name`,`product_id`,`product_no`,`gg_name`,`price`,`qty`,`score`,`type`,`total_money`) VALUES ('{$order_id}','{$product_name}','{$result['product_id']}','{$result['product_no']}','{$gg_name}','{$result['price']}','1','0','{$order_pro_type}','{$total_money}')");

          $this->orderAddProGift($order_id, $result['product_id']);
        }


        //销量
        // $this->db->query("UPDATE ttgy_product set sales = sales + 1 where id = {$result['product_id']}");
        return array('order_pro_info'=>$result);
    }

    /**
     * 订单添加商品赠品
     */
    public function orderAddProGift($orderId, $proId)
    {
        $this->db->select('p.id, p.product_name, pp.product_no')
                 ->from('pro_gift AS pg')
                 ->join('product AS p', 'pg.gift_product_id = p.id', 'left')
                 ->join('product_price AS pp', 'p.id = pp.product_id', 'left')
                 ->where("pg.product_ids like '%," . $proId . ",%'");
       $result = $this->db->get()->row_array();
       if ($result) {
           $this->db->query("INSERT INTO ttgy_order_product (`order_id`,`product_name`,`product_id`,`product_no`,`gg_name`,`price`,`qty`,`type`) VALUES ('{$orderId}','{$result['product_name']}','{$result['id']}','{$result['product_no']}','件','0','1','2')");
       }
    }

    function orderAddAddr($order_id,$address_id, $result = []){
        if ($address_id) {
            $this->db->select('name,mobile,address,area,telephone,province,city');
            $this->db->from('user_address');
            $this->db->where('id',$address_id);
            $query = $this->db->get();
            $result = $query->row_array();
        }
        $region = $this->get_region($result['area']);
        $address = $region.$result['address'];

        $province = 0; $city = 0;
        if (is_numeric($result['area'])) {
            $rowarea = $this->db->select('pid')->from('area')->where('id',$result['area'])->get()->row_array();
            if ($rowarea['pid']) $city = $rowarea['pid'];
        }

        if (is_numeric($city)) {
            $rowcity = $this->db->select('pid')->from('area')->where('id',$city)->get()->row_array();
            if ($rowcity['pid']) $province = $rowcity['pid'];
        }

        $order_address = array(
          'order_id'  => $order_id,
          'position'  => $region,
          'address'   => $address,
          'name'      => $result['name'],
          'email'     => $result['email'] ?: '',
          'telephone' => $result['telephone'] ?: '',
          'mobile'    => $result['mobile'],
          'province'  => $province,
          'city'      => $city,
          'area'      => $result['area'],
        );

        $this->db->insert('order_address',$order_address);
        $orderAddressId = $this->db->insert_id();
        $this->db->update('order', ['address_id' => $orderAddressId], ['id' => $order_id]);

        // $this->db->query("INSERT INTO `ttgy_order_address` (`order_id`,`position`,`address`,`name`,`email`,`telephone`,`mobile`) VALUES ('{$order_id}','{$region}','{$result['address']}','{$result['name']}','', '{$result['telephone']}','{$result['mobile']}')");

        return array('order_addr_info'=>$order_address,'region'=>$region);
    }

    private function get_region($area_id){
        if($area_id==0){
            return '';
        }
        $this->db->select("name,pid");
        $this->db->from("area");
        $this->db->where(array("id"=>$area_id));
        $query=$this->db->get();
        $result=$query->row_array();
        if($result['pid']!='0'){
            $this->region = $result['name'].$this->region;
            $this->get_region($result['pid']);

        }else{
            $this->region = $result['name'].$this->region;
        }
        return $this->region;
    }

    function orderAddGift($order_id,$price_id){
        $this->db->select('product_id');
        $this->db->from('product_price');
        $this->db->where('id',$price_id);
        $query = $this->db->get();
        $result = $query->row_array();

        $this->db->from("product_gifts");
        $this->db->where(array("pid"=>$result['product_id']));

        $query=$this->db->get();
        $result=$query->row_array();
        if(!empty($result)){
        $gift_order_pro_id = $result['product_id']?$result['product_id']:0;
        $gift_name = addslashes($result['gname']);
        $product_no = trim($result['gno']);
        $this->db->query("INSERT INTO ttgy_order_product (`order_id`,`product_name`,`product_id`,`product_no`,`gg_name`,`price`,`qty`,`type`) VALUES ('{$order_id}','{$gift_name}','{$gift_order_pro_id}','{$product_no}','件','0','1','2')");
        }

    }

    function exchange($card_number,$order_name){
        $used_time = date("Y-m-d H:i:s");
        $data = array(
           'is_used' => '1',
           'used_time' => $used_time,
           'order_name' => $order_name,
           'card_opt' => '1'
        );

        $this->db->update('pro_card', $data, array('card_number'=>$card_number,'is_used'=>0));

        $num = $this->db->affected_rows();
        if ($num==1) {
          return array('result'=>'succ','response_error'=>'兑换成功');
        } else {
          return array('result'=>'fail','response_error'=>'兑换失败');
        }
    }
}
