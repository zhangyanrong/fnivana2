<?php
 namespace bll;

class Foretaste
{
    //文章上传图片
    private $photolimit = 3;
    //文章图片存储路径
    private $photopath = "images/";
    //文章图片大小
    private $thumb_size = "320";

    public function __construct($params = array()){
        $this->ci = &get_instance();
        $this->ci->load->helper('public');

        $this->photopath = $this->photopath.date("Y-m-d");
        $session_id = isset($params['connect_id'])?$params['connect_id']:'';
        if($session_id){
            $this->ci->load->library('session',array('session_id'=>$session_id));
        }

        $this->ci->load->model('foretaste_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('user_address_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('region_model');
        $this->ci->load->model('user_model');
    }

    /**
     * 获取当前正在进去中的试吃
     *
     * @return void
     * @author
     **/
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


    /**
     * 获取试吃明细
     *
     * @return void
     * @author
     **/
    public function getDetail($params)
    {
        $this->ci->load->model('order_model');

        // $id = intval($params['id']);
        $filter = array('enabled' => '1');
        $filter['start_time <='] = time();
        $filter['end_time >'] = time();

        $rows = $this->ci->foretaste_model->selectForetaste('', $filter);
        $row = $rows[0];

        $data = array();

        // 试吃规则
        $setting = $this->ci->foretaste_model->selectForetasteSetting();
        $data['setting'] = $setting ? unserialize($setting['setting']) : array();

        if ($row) {
            // 获取商品信息
            $product_ids = array($row['product_id']);
            //获取商品
            $product_where_in[] = array('key'=>'id','value'=>$product_ids);
            $product_res = $this->ci->product_model->selectProducts('*', '', $product_where_in);
            $product = $product_res[0];
            //获取价格
            $pprice_where_in = array('key'=>'product_id','value'=>$product_ids);
            $pprice_res = $this->ci->product_model->selectProductPrice('' ,'' ,$pprice_where_in);
            $pprice = $pprice_res[0];
            //获取图片
            $photo_where_in = array('key'=>'product_id','value'=>$product_ids);
            $photo_res = $this->ci->product_model->selectProductPhoto('' ,'' ,$photo_where_in);
            $photo = $photo_res;

            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id']);
                if (isset($templateImages['main'])) {
                    $product['photo'] = $templateImages['main']['image'];
                    $product['thum_photo'] = $templateImages['main']['thumb'];
                }
            }
            $data['product']['photo'][] = array(
                'photo' => PIC_URL.$product['photo'],
                'thum_photo' => PIC_URL.$product['thum_photo'],
            );

            // 获取产品模板图片
            if ($product['template_id']) {
                if (isset($templateImages['detail'])) {
                    foreach ($templateImages['detail'] as $key => $value) {
                        $data['product']['photo'][] = array(
                            'photo' => PIC_URL.$value['image'],
                            'thum_photo' => PIC_URL.$value['thumb'],
                        );
                    }
                }
            } else {
                if ($photo)
                    foreach ($photo as $key => $value) {
                        $data['product']['photo'][] = array(
                            'photo' => PIC_URL.$value['photo'],
                            'thum_photo' => PIC_URL.$value['thum_photo'],
                        );
                    }
            }

            $data['id']                     = $row['id'];
            $data['name']                   = $row['name'];
            $data['end_time']               = date('Y-m-d H:i:s',$row['end_time']);
            $data['start_time']             = date('Y-m-d H:i:s',$row['start_time']);
            $data['quantity']               = $row['quantity'];
            $data['applycount']             = $row['applycount'];
            $data['share_url']              = 'http://www.fruitday.com/foretaste/share/'.$row['id'];
            $data['curr_time']              = date('Y-m-d H:i:s');
            // 期数
            // $periods = $this->db->select('*')
            //                 ->from('foretaste_goods')
            //                 ->where('enabled','1')
            //                 ->where('end_time <',time())
            //                 ->count_all_results();
            $data['periods'] = $row['periods'];


            $data['product']['productId']   = $row['product_id'];
            $data['product']['name']        = $product['product_name'];
            // $data['product']['photo']       = PIC_URL.$product['photo'];
            $data['product']['price']       = (string) $pprice['price'];
            $data['product']['product_no']  = (string) $pprice['product_no'];
            $data['product']['unit']        = (string) $pprice['unit'];
            $data['product']['volume']      = (string) $pprice['volume'];
            $data['product']['summary']     = $product['summary'];
            $data['product']['discription'] = str_replace(" src=\"", " src=\"".trim(PIC_URL,'/'), $product['discription']);
            $data['product']['op_weight']   = $product['op_weight'];

            $data['addr_list'] = null;
            $res = $this->_ckuser();
            if($res['code']=='200'){
                $uid = $res['msg'];
                $foretaste_last = $this->ci->foretaste_model->selectForetasteApply('address_id', array('uid'=>$uid), array('page_size'=>1, 'offset'=>0), array('key'=>'id', 'value'=>'desc'));
                $last_addr_id = $foretaste_last[0]['address_id'];
                if(empty($last_addr_id)){
                    $last_addr = $this->ci->order_model->get_user_address(array('uid'=>$uid, 'is_default'=>1));
                    $last_addr_id = $last_addr['id'];
                }
                if(!empty($last_addr_id)){
                    $addr_list = $this->ci->user_model->geta_user_address($uid,$last_addr_id,'',$params['source']);
                    $data['addr_list'] = $addr_list[0];
                }
            }


            return array('status' => 'succ','code' => 200,'data' => $data,);
        } else {
            return array('status' => 'succ', 'code' => 300 ,'msg' => '当期试吃已结束 吃货别急 下期很快就有了');
        }
    }

    /**
     * 试吃申请提交
     *
     * @return void
     * @author
     **/
    public function doApply($params)
    {
        $foretaste_goods_id = intval($params['foretaste_id']);

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        if (!$foretaste_goods_id) {
            return array('code'=>'300','msg'=>'foretaste not exist');
        }

        // 获取试吃活动
        $filter = array('id'=>$foretaste_goods_id);
        $foretaste_goods_res = $this->ci->foretaste_model->selectForetaste('', $filter);
        $foretaste_goods = $foretaste_goods_res[0];

        if (!$foretaste_goods) {
            return array('code' => '300', 'msg' => '对不起！您申请的试吃水果活动不存在！');
        }

        if ($foretaste_goods['enabled']=='0' || $foretaste_goods['start_time']>time() || $foretaste_goods['end_time']<time()) {
            return array('code' => '300', 'msg' => '对不起！您申请的试吃水果活动未开始或已结束！');
        }

        $apply_filter = array('uid'=>$uid, 'foretaste_goods_id'=>$foretaste_goods['id']);
        $foretaste_apply = $this->ci->foretaste_model->selectForetasteApply('id', $apply_filter);
        if ($foretaste_apply) {
            return array('code' => '300', 'msg' => '对不起！您已申请过此水果的试吃，不能再次申请！');
        }

        // $foretaste_blacklist = $this->db->select('id')
        //                                 ->from('foretaste_blacklist')
        //                                 ->where('uid',$uid)
        //                                 ->get()
        //                                 ->row_array();
        // if ($foretaste_blacklist) {
        //     return array('code' => '300', 'msg' => '对不起！您已被列入黑名单，不允许参加试吃，请联系管理员！');
        // }
        $addr_id = $params['addr_id'];

        if ($addr_id) {
            $addrs = $this->ci->user_address_model->getList('*',array('id' => $addr_id),0,-1);
            $addr = $addrs[0];

            if (!$addr) array('code' => '300', 'msg' => '对不起！您申请的地址不存在！');
        } else {
            $addr = array(
                'uid'       => $uid,
                'name'      => $params['name'],
                'province'  => $params['province'],
                'city'      => $params['city'],
                'area'      => $params['area'],
                'address'   => $params['address'],
                'telephone' => $params['telephone'] ? $params['telephone'] : '',
                'mobile'    => $params['mobile'],
                // 'flag'      => isset($params['flag']) ? $params['flag'] : ''
            );

            $check_addr = $this->ci->order_model->check_addr($params);
            if($check_addr!==true){
                return $check_addr;
            }

            $addr['id'] = $this->ci->order_model->add_user_address($addr);
            if (!$addr['id']) array('code' => '300', 'msg' => '申请地址有误');
        }
        $address = $this->ci->region_model->get_region($addr['area']);
        $apply_data = array(
                    "uid"                => $uid,
                    "foretaste_goods_id" => $foretaste_goods['id'],
                    "mobile"             => $addr['mobile'],
                    'telephone'          => $addr['telephone'],
                    'name'               => $addr['name'],
                    'address'            => $address.$addr['address'],
                    'createtime'         => time(),
                    'address_id'         => $addr['id'],
                    'province'           => $addr['province'],
                    'city'               => $addr['city'],
                    'area'               => $addr['area'],
                );
        $result = $this->ci->foretaste_model->insForetasteApply($apply_data);
        if (!$result) array('code' => '300', 'msg' => '申请失败');

        $rs = $this->ci->foretaste_model->upForetasteApplyCount($foretaste_goods['id']);

        return array('code' => '200', 'msg' => '申请成功，等待审核');

    }

    /**
     * 验证是否已申请过
     *
     * @return void
     * @author
     **/
    public function checkApply($params)
    {
        $foretaste_goods_id = intval($params['foretaste_id']);

        // 检查是否是有登录状态
        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        if (!$foretaste_goods_id) {
            return array('code'=>'500','msg'=>'foretaste not exist');
        }

        $apply_filter = array('uid'=>$uid, 'foretaste_goods_id'=>$foretaste_goods_id);
        $row = $this->ci->foretaste_model->selectForetasteApply('id', $apply_filter);

        return array('status' => 'succ','code' => 200,'data' => array('has_apply'=>$row ? 'true' : 'false'));
    }

    /**
     * 个人试吃申请集合
     *
     * @return void
     * @author
     **/
    public function ownerApply($params)
    {
        $page_no = (int) $params['page_no'] > 0 ? (int) $params['page_no'] : 1;
        $page_size = (int) $params['page_size'] > 0 ? min((int) $params['page_size'],100) : 20;

        // 检查是否是有登录状态
        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        $offset = ($page_no-1) * $page_size;
        $limits = array('page_size'=>$page_size,'offset'=>$offset);
        $order = array('key'=>'createtime','value'=>'DESC');
        $filter = array('uid'=>$uid);
		if ( $params['type'] == '1' ) {
			$filter['has_comment'] = '0';
			$filter['status'] = '1';
		}
        $apply_rows = $this->ci->foretaste_model->selectForetasteApply('', $filter, $limits, $order);

        $all_apply_rows = $this->ci->foretaste_model->selectForetasteApply('id',$filter);
        $totalResult = count($all_apply_rows);

        $data = array();

        if ($apply_rows) {
            $foretaste_id_arr = array();
            foreach ($apply_rows as $key => $value) {
				if($value['order_id']){
				    $order_info = $this->ci->order_model->getInfoById($value['order_id']);
				}
                $foretaste_id_arr[] = $value['foretaste_goods_id'];

                $data[] = array(
                    'name' => &$foretaste[$value['foretaste_goods_id']]['name'],
                    'periods' => &$foretaste[$value['foretaste_goods_id']]['periods'],
                    'type' => 'free',
                    'apply_time' => date('Y-m-d H:i:s',$value['createtime']),
                    'apply_status' => $value['status'],
                    'has_comment' => $value['has_comment'],
                    'foretaste_goods_id' => $value['foretaste_goods_id'],
                    'id' => $value['id'],
                    'product' => &$product[$value['foretaste_goods_id']],
					'order_id' => $order_info['order_name'],
					'operation_id' => $order_info['operation_id'],
                );
				unset($order_info);
            }

            $foretaste_rows = $this->ci->foretaste_model->selectForetaste('id,name,product_id,periods', '', '', '', array('key'=>'id', 'value'=>$foretaste_id_arr));
            $product_ids = array_column($foretaste_rows, 'product_id');
            //获取商品
            $product_where_in[] = array('key'=>'id','value'=>$product_ids);
            $product_res = $this->ci->product_model->selectProducts('*', '', $product_where_in);
            $products = array_column($product_res, null, 'id');

            foreach ($foretaste_rows as $key => $value) {
                $foretaste[$value['id']]['name'] = $value['name'];
                $foretaste[$value['id']]['periods'] = $value['periods'];

                $productinfo = $products[$value['product_id']];

                // 获取产品模板图片
                if ($productinfo['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($productinfo['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $productinfo['photo'] = $templateImages['main']['image'];
                    }
                }

                $product[$value['id']]['id'] = $value['product_id'];
                $product[$value['id']]['name'] = $productinfo['product_name'];
                $product[$value['id']]['photo'] = PIC_URL.$productinfo['photo'];
            }
            unset($foretaste_rows);
        }

        return array('status' => 'succ','code' => '200', 'data' => $data,'totalResult'=>$totalResult);
    }

    /**
     * 获取试吃报告
     *
     * @return void
     * @author
     **/
    public function getCommentList($params)
    {
        $foretaste_goods_id = $params['foretaste_goods_id'];
        $page_no = (int) $params['page_no'] > 0 ? (int) $params['page_no'] : 1;
        $page_size = (int) $params['page_size'] > 0 ? min((int) $params['page_size'],100) : 20;

        if (!$foretaste_goods_id) {
            return array('status' => 'fail', 'code'=>'500','msg'=>'无该试吃');
        }

        $filter = array('foretaste_goods_id'=>$foretaste_goods_id,'is_show'=>'1');
        $offset = ($page_no-1) * $page_size;
        $limit = array('page_size'=>$page_size,'offset'=>$offset);
        $order = array('key'=>'createtime','value'=>'DESC');
        $comment_rows = $this->ci->foretaste_model->selectForetasteComment('', $filter, $limit, $order);
        $all_comment_rows = $this->ci->foretaste_model->selectForetasteComment('id', $filter);
        $totalResult = count($all_comment_rows);

        $data = array();
        if ($comment_rows) {
            $foretaste_id_arr = array();
            $user_id_arr = array();
            foreach ($comment_rows as $key => $value) {
                $domain = $value['source']==2 ? PIC_URL_TMP : PIC_URL;

                $foretaste_id_arr[] = $value['foretaste_goods_id'];
                $user_id_arr[] = $value['uid'];

                $pic_urls = unserialize($value['pic_urls']);
                if ($pic_urls) {
                    foreach ($pic_urls as $k=>$l) {
                        $pic_urls[$k] = $domain.$l;
                    }
                }

                $data[] = array(
                    'foretaste' => &$foretaste[$value['foretaste_goods_id']],
                    'userinfo' => &$users[$value['uid']],
                    'meminfo' => $value['meminfo'],
                    'content' => $value['content'],
                    'title' => $value['title'],
                    'apply_id' => $value['apply_id'],
                    // 'is_show' => $value['is_show'],
                    'rank' => $value['rank'],
                    'createtime' => date('Y-m-d H:i:s',$value['createtime']),
                    'pic_urls' => $pic_urls,
                    'id' => $value['id'],
                );
            }

            $foretaste_rows = $this->ci->foretaste_model->selectForetaste('id,name', '', '', '', array('key'=>'id', 'value'=>$foretaste_id_arr));
            foreach ($foretaste_rows as $key => $value) {
                $foretaste[$value['id']]['name'] = $value['name'];
                $foretaste[$value['id']]['id'] = $value['id'];
            }
            unset($foretaste_rows);

            $user_id_arr = array_unique($user_id_arr);
            $user_rows = $this->ci->user_model->selectUsers('id,username,mobile,user_head,is_pic_tmp','',array('key'=>'id','value'=>$user_id_arr));

            foreach ($user_rows as $key => $value) {
                $users[$value['id']]['username'] = ellipsize($value['username'],6,0.5,'***');
                $users[$value['id']]['mobile'] = ellipsize($value['mobile'],7,0.5,'***');
                $users[$value['id']]['id'] = $value['id'];

                // set userface
                $userHead = unserialize($value['user_head']);
                $userface = $userHead['middle'];
                if ($value['is_pic_tmp'] == 1) {
                    if (strstr($userface, "http")) {
                        $users[$value['id']]['userface'] = $userface;
                    } else {
                        $users[$value['id']]['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL_TMP . $userface;
                    }
                } else {
                    if (strstr($userface, "http")) {
                        $users[$value['id']]['userface'] = $userface;
                    } else {
                        $users[$value['id']]['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL . $userface;
                    }
                }
            }
        }

        return array('status' => 'succ','code' => '200', 'data' => $data, 'totalResult'=>$totalResult);
    }

    /**
     * 填写试吃报告
     *
     * @return void
     * @author
     **/
    public function doComment($params)
    {
        //用户验证
        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        // 参数验证
        $apply_id   = $params['apply_id'];
        $title      = $params['title'];
        $content    = $params['content'];
        $meminfo    = $params['meminfo'];
        $rank       = $params['rank'] ? $params['rank'] : '5';

        if (!$apply_id) {
            return array('status' => 'fail', 'code' => '300', 'msg' => '请选择试吃申请');
        }
        $apply_filter = array('id'=>$apply_id);
        $apply_res = $this->ci->foretaste_model->selectForetasteApply('', $apply_filter);
        $apply = $apply_res[0];
        if (!$apply) {
            return array('status' => 'fail', 'code' => '300', 'msg' => '试吃申请不存在');
        }
        if ($apply['status'] != '1') {
            return array('status' => 'fail', 'code' => '300', 'msg' => '试吃申请审核未通过，不允许发表试吃体验');
        }
        if ($apply['has_comment'] == '1') {
            return array('status' => 'fail', 'code' => '300', 'msg' => '你已发表过试吃体验，不允许再发表');
        }
        if (!$title) {
            return array('status' => 'fail', 'code' => '300', 'msg' => '标题不能为空');
        }
        if (!$content) {
            return array('status' => 'fail', 'code' => '300', 'msg' => '未填写试吃体验');
        }
        if (!$meminfo) {
            return array('status' => 'fail', 'code' => '300', 'msg' => '未填写个人信息');
        }

        // $img_arr = $this->savePhoto();

        // 试吃图片上传到七牛
        // 蔡昀辰 2016
        if ($_FILES && count($_FILES) <= $this->photolimit) {
            $img_arr = [
                "images" => [],
                "thumbs" => []
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path   = $photo['tmp_name'];
                $name   = $photo['name'];
                $date   = date("ymd", time());
                $prefix = 'img/foretaste';
                $hash   = str_replace('/tmp/php', '', $path);
                $key    = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);
                if ($ret) {
                    $img_arr["images"][] = str_replace('img/', '', $key);
                    $img_arr["thumbs"][] = str_replace('img/', '', $key) . '-thumb';
                }
            }
        }


        if(isset($img_arr['code']) && $img_arr['code']==300){
            return array('code'=>'300','msg'=>'上传图片失败，请重新上传');
        }

        $fgoods_filter = array('id'=>$apply['foretaste_goods_id']);
        $fgoods_res = $this->ci->foretaste_model->selectForetaste('', $fgoods_filter);
        $fgoods = $fgoods_res[0];

        $comment_data = array(
            'uid'                => $uid,
            'foretaste_goods_id' => $apply['foretaste_goods_id'],
            'content'            => $content,
            'createtime'         => time(),
            'meminfo'            => $meminfo,
            'product_id'         => $fgoods['product_id'],
            'product_no'         => $fgoods['product_no'],
            'title'              => $title,
            'pic_urls'           => serialize($img_arr['images']),
            'apply_id'           => $apply_id,
            'rank'               => $rank,
            'source'             => 2,
        );
        $rs = $this->ci->foretaste_model->insForetasteComments($comment_data);
        if ($rs == false) {
            return array('status' => 'fail', 'code' => '300', 'msg' => '发表失败');
        }

        $this->ci->foretaste_model->upForetasteApply(array('id'=>$apply_id),array('has_comment'=>'1'));
        return array('status' => 'succ', 'code'=>'200');
    }

    //保存图片
    private function savePhoto(){
        $img_name_arr = array();
        $photo_arr = array();
        $thumbs_arr = array();
        if(!empty($_FILES)){
                $config['upload_path'] = $this->ci->config->item('photo_base_path').$this->photopath;
                $config['allowed_types'] = 'gif|jpg|png';
                $config['encrypt_name'] = true;
                $this->ci->load->library('upload', $config);
                for($i=0;$i<$this->photolimit;$i++){
                    $key = "photo".$i;
                    if(empty($_FILES[$key]['size'])){
                        continue;
                    }
                    if ( ! $this->ci->upload->do_upload($key)){
                        return array('code'=>'300','msg'=>'上传失败');
                    }
                    $image_data[] = $this->ci->upload->data();
                }
                if(!empty($image_data)){
                    $this->ci->load->library('image_lib');
                    foreach($image_data as $val){
                        $curr_image_info = pathinfo($val['file_name']);
                        $thumb_image_info = $curr_image_info['filename']."_thumb";
                        $thumb_photo =  $thumb_image_info.".".$curr_image_info['extension'];
                        $thumb_config['image_library'] = 'gd2';
                        $thumb_config['source_image'] = $config['upload_path']."/".$val['file_name'];
                        $thumb_config['create_thumb'] = TRUE;
                        $thumb_config['maintain_ratio'] = TRUE;
                        $thumb_config['width'] = $this->thumb_size;
                        $thumb_config['height'] = $this->thumb_size;
                        $this->ci->image_lib->initialize($thumb_config);
                        if ( ! $this->ci->image_lib->resize())
                        {
                            return array('code'=>'300','msg'=>'上传失败');
                        }
                        $photo_arr[] = $this->photopath."/".$val['file_name'];
                        $thumbs_arr[] = $this->photopath."/".$thumb_photo;
                    }
                }
                if(empty($photo_arr)) return array('code'=>'300','msg'=>'上传失败');
        }
        $img_name_arr["images"] = $photo_arr;
        $img_name_arr["thumbs"] = $thumbs_arr;
        return $img_name_arr;
    }

    /**
     * [_ckuser 获取用户id]
     * @param  [string] $session_id [用户标识]
     * @return [array]             [description]
     */
    private function _ckuser(){
        $session =   $this->ci->session->userdata;
        if(empty($session)){
            return array('code'=>'400','msg'=>'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if( !isset($userdata['id']) || $userdata['id'] == "" ){
            return array('code'=>'400','msg'=>'not this user,may be wrong connect id');
        }
        return array('code'=>'200','msg'=>$userdata['id']);
    }
}
