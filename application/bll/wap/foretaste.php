<?php
namespace bll\wap;
include_once("wap.php");
/**
* 商品相关接口
*/
class Foretaste extends wap{

	function __construct(){
        $this->ci = &get_instance();
        $this->ci->load->helper('public');

        $this->photopath = $this->photopath.date("Y-m-d");
        $session_id = isset($params['connect_id'])?$params['connect_id']:'';
        if($session_id){
            $this->ci->load->library('session',array('session_id'=>$session_id));
        }
//
        $this->ci->load->model('foretaste_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('user_address_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('region_model');
        $this->ci->load->model('user_model');
	}


    public function getCurList($params)
    {
        $page_no = (int) $params['page_no'] > 0 ? (int) $params['page_no'] : 1 ;
        $page_size =  (int) $params['page_size'] > 0 ? min((int) $params['page_size'],100) : 20 ;
        $type = $params['type'];
        $offset = ($page_no - 1) * $page_size;
        $filter = array('enabled' => '1');
        if ($type == '1') {
            $filter['start_time <'] = time();
            $filter['end_time >'] = time();
        }
        if ($type == '2') {
            $filter['end_time <'] = time();
        }

        $limits = array('page_size'=>$page_size,'offset'=>$offset);
        $order = array('key'=>'end_time','value'=>'DESC');
        $rows = $this->ci->foretaste_model->selectForetaste('', $filter, $limits, $order);
        $data = array();
        if ($rows){
            $product_ids = array_column($rows, 'product_id');
            //获取商品
            $product_where_in[] = array('key'=>'id','value'=>$product_ids);
            $product_res = $this->ci->product_model->selectProducts('*', '', $product_where_in);
            $products = array_column($product_res, null, 'id');
            //获取价格
            $pprice_where_in = array('key'=>'product_id','value'=>$product_ids);
            $pprice_res = $this->ci->product_model->selectProductPrice('' ,'' ,$pprice_where_in);
            $pprices = array_column($pprice_res, null, 'product_id');

            foreach ($rows as $row) {
                $f = array();

                // 获取商品信息
                $product = $products[$row['product_id']];
                $pprice = $pprices[$row['product_id']];

                // 获取产品模板图片
                if ($product['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $product['photo'] = $templateImages['main']['image'];
                    }
                }

                $f['periods']               = $row['periods'];
                $f['id']                    = $row['id'];
                $f['name']                  = $row['name'];
                $f['end_time']              = date('Y-m-d H:i:s',$row['end_time']);
                $f['start_time']            = date('Y-m-d H:i:s',$row['start_time']);
                $f['quantity']              = $row['quantity'];
                $f['picture']               = constant(CDN_URL.rand(1, 9)) . $row['picture'];
                $f['applycount']            = $row['applycount'];
                $f['answer_url']            = $row['answer_url'];
                $f['pro_url']               = $row['pro_url'];
                $f['product']['summary']    = strip_tags($product['summary']);
                $f['product']['name']       = $product['product_name'];
                $f['product']['photo']      = PIC_URL.$product['photo'];
                $f['product']['desc']       = str_replace('src="/', 'src="'.PIC_URL, $product['discription']);
                $f['product']['price']      = $pprice['price'] ? $pprice['price'] : '0.0';
                $f['product']['product_no'] = $row['product_no'];
                $f['product']['id']         = $row['product_id'];
                $f['product']['detail_place']   = $product['op_detail_place'];
                $f['product']['share_url']  = 'http://www.fruitday.com/foretaste/share/'.$row['id'];

                $data[] = $f;
            }
        }

        $allrows  = $this->ci->foretaste_model->selectForetaste('id', $filter);
        $totalResult = count($allrows);
        return array('status' => 'succ','code' => 200,'data' => $data, 'totalResult' => $totalResult);
    }

}
