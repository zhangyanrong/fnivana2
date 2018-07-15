<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Oauthproduct extends CI_Model{
	private $str_area_refelect;
	function oauthproduct() {
		parent::__construct();
		$this->load->model("product_model");
		$this->str_area_refelect = $this->config->item('str_area_refelect');
	}

	public function oauth_pro_list($params){
        $oauth_from = $params['oauth_from'];
        if(!$oauth_from){
            echo json_encode(array('code'=>"500",'msg'=>"$oauth_from can not be empty"));
            exit;
        }
        $page_size = $params['page_size']?$params['page_size']:4;
        $page = $params['page']?$params['page']:1;
        $this->db->select("(select price from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc  limit 1 )as price");
        $this->db->select("(select mobile_price from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc  limit 1 )as mobile_price");
        $this->db->select("(select old_price from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc  limit 1 )as old_price");
        $this->db->select("(select volume from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc limit 1 )as volume");
        $this->db->select('product_name,summary,thum_photo,photo,id,template_id');
        $this->db->from('product AS p');
        $o_f_arr = $this->checkOauthFrom($oauth_from);

        if($o_f_arr[1]=='wap')
            $this->db->where(array('p.mobile_online' => 1));
        else
            $this->db->where(array('p.online' => 1));
        $this->db->where('p.oauth_channel',$o_f_arr[0]);

        $total_number = $this->db->count_all_results();
        $this->db->select("(select price from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc  limit 1 )as price");
        $this->db->select("(select mobile_price from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc  limit 1 )as mobile_price");
        $this->db->select("(select old_price from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc  limit 1 )as old_price");
        $this->db->select("(select volume from ttgy_product_price where ttgy_product_price.product_id=p.id order by ttgy_product_price.order_id asc,ttgy_product_price.id desc limit 1 )as volume");
        $this->db->select('product_name,summary,thum_photo,photo,id,template_id');
        $this->db->from('product AS p');
        $o_f_arr = $this->checkOauthFrom($oauth_from);

        if($o_f_arr[1]=='wap')
            $this->db->where(array('p.mobile_online' => 1));
        else
            $this->db->where(array('p.online' => 1));
        $this->db->where('p.oauth_channel',$o_f_arr[0]);
        if($page_size){
            $this->db->limit($page_size,(($page-1)*$page_size));
        }
        $result = $this->db->get();
        $product_result = $result->result_array();
        $flag = "?flag=".$o_f_arr[0];
        foreach ($product_result as $key => $value) {
            // 获取产品模板图片
            if ($value['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $value['photo'] = $templateImages['main']['image'];
                    $value['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

            $product_result[$key]['photo'] = PIC_URL.$value['thum_photo'];
            unset($product_result[$key]['thum_photo']);
            if(isset($product_id_tmp[$value['id']])){
                $product_result[$key]['mark_time'] = $product_id_tmp[$value['id']];
            }
            if($o_f_arr[1]=='wap'){
                $product_result[$key]['pro_link'] = "http://m.fruitday.com/detail/index/".$value['id'].$flag;
            }else{
                $product_result[$key]['pro_link'] = "http://www.fruitday.com/web/pro/".$value['id'];
            }
        }
        $data = array();
        $data['total_number'] = $total_number;
        $data['product_list'] = $product_result;
        return $data;
    }

    private function get_oauth_from($params){
        if(!isset($params['connect_id']) || empty($params['connect_id'])){
            return array('code'=>'500','msg'=>'connect id can not be null');
        }
        $session_id = $params['connect_id'];
        $this->load->model("session_model");
        $session =   $this->session_model->get_session($session_id);
        if(empty($session)){
            return array('code'=>'400','msg'=>'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        $oauth_from = $userdata['oauth_from'];
        return $oauth_from;
    }

    private function checkOauthFrom($from){
        $f_arr = explode('_', $from);
        if($f_arr[1] == 'm')
            return array($f_arr[0],'wap');
        else
            return array($f_arr[0],'pc');
    }
}

