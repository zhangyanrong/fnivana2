<?php
/**
 * 评论
 *
 **/
class Comment_model extends MY_Model {
	/**
	* 评论表
	*
	* @var string
	**/
	const _TABLE_NAME = 'comment_new';

	public function table_name()
	{
		return self::_TABLE_NAME;
	}

	public function __construct(){
		parent::__construct();
		$this->load->helper("public");
	}

	/**
     * 获取商品评论
     */
    public function selectComments($field, $where = '', $where_in = '', $order = '', $limit = '',$force='')
    {
        $this->db->select($field);
        $this->db->from('comment_new'.$force);
        if (!empty($where)) {
            $this->db->where($where);
        }
        if (!empty($where_in)) {
            foreach ($where_in as $val) {
                $this->db->where_in($val['key'], $val['value']);
            }
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($limit)) {
            $this->db->limit($limit['page_size'], ($limit['curr_page'] * $limit['page_size']));
        }
        $result = $this->db->get()->result_array();
        return $result;
    }

    /**
     * 获取商品评论数量、比例
     *
     * @param string $pid
     * @return array
     */
    public function commentsRate($pid)
    {
        $field_time = date("Y-m-d", strtotime("-10 months",time()));
        $where = array(
            'is_review' => 1,
            'show' => 1,
            'product_id' => $pid,
            'time >'=>$field_time
        );

        $count = $this->count($where);
        $goodCount = $this->count(array_merge($where, array('star >' => 3)));
        $normalCount = $this->count(array_merge($where, array('star >' => 1, 'star <' => 4)));
        $badCount = $this->count(array_merge($where, array('star <' => 2)));
        $hasImageCount = $this->count(array_merge($where, array('images !=' => '')));

        $sum = $this->dump($where,'SUM(`star_eat`) as star_eat , SUM(`star_show`) as star_show');

        // 进度条
        $praise['good'] = ($count > 0) ? round($goodCount / $count, 2) * 100 : 0;
        $praise['normal'] = ($count > 0) ? round($normalCount / $count, 2) * 100 : 0;
        $praise['bad'] = ($count > 0) ? round($badCount / $count, 2) * 100 : 0;

        $praise['eat'] = ($count > 0) ? (string)round($sum['star_eat'] / $count, 1) : 0;
        $praise['show'] = ($count > 0) ? (string)round($sum['star_show'] / $count, 1) : 0;

        $praise['num'] = array(
            'total' => $count,
            'good' => $goodCount,
            'normal' => $normalCount,
            'bad' => $badCount,
            'has_image' => $hasImageCount,
        );

        $data = $this->commentsNew($pid,1,0)['list'];

        if(count($data) == 0)
        {
            $data = $this->comments($pid,1,0)['list'];
        }

        $images =  $data[0]['images'];
        $thumbs =  $data[0]['thumbs'];
        if(!empty($images) && !empty($thumbs))
        {
            $data[0]['images'] = explode(',',$images);
            $data[0]['thumbs'] = explode(',',$thumbs);
        }
        else
        {
            //ios
            $data[0]['images'] = array();
            $data[0]['thumbs'] = array();
        }
        //ios
        unset($data[0]['customer_repaly']);

        //特殊处理
        if(!isset($data[0]['uid']))
        {
            $data = array();
        }

        //特殊处理 - null
        if(empty($data))
        {
            $praise['data'] = array();
        }
        else
        {
            $rs = array(0=>$data[0]);
            $praise['data'] = $rs;
        }

        return $praise;
    }

    /*
     * new - 评价筛选
     */
    function commentsNew($pid,$page_size,$curr_page,$type=0,$comment_type=0)
    {
        $field = "id,content,time,star,is_pic_tmp,images,uid,thumbs,star_eat,star_show";
        $field_time = date("Y-m-d", strtotime("-10 months",time()));
        $where = array(
            'is_review' => 1,
            'show' => 1,
            'star >='=>'4',
            'LENGTH(content) >'=>'15',
            'time >'=>$field_time
        );

        $order = ' star desc,time desc';
        $limit = array('curr_page'=>'0','page_size'=>'100');
        $where_in[] = array('key' => 'product_id', 'value' => $pid);

        if(!empty($type)){
            $star = $this->parse_type($type);
            if(!empty($star)){
                $where_in[] = array('key'=>'star','value'=>$star);
            }
        }
        if(!empty($comment_type)){
            $where['type'] = 1;
        }
        $result_array = $this->selectComments($field,$where,$where_in,$order,$limit);

        //筛选
        if(count($result_array)>0)
        {
            $arr_text = array(
                '太贵', '不好吃', '不新鲜', '坏掉', '烂掉', '压坏', '不好',
                '坏', '烂', '差', '失望', '晚了', '一般', '不够', '不甜',
                '不满意', '不是', '上当', '后悔', '老', '臭', '腥', '不灵',
                '柴', '油', '肥', '失落', '不值','此人没有写文字评论哦~'
            );

            foreach($result_array as $key=>$val)
            {
                foreach($arr_text as $row)
                {
                    if(strpos($val['content'],$row)!== false)
                    {
                        unset($result_array[$key]);
                    }
                }
            }
            $ds = array();
            $result_array= array_merge($ds,$result_array);
        }

        if(!empty($result_array)){
            $this->load->model("user_model");
            $uids = array_column($result_array,'uid');
            $where_in = array('key'=>'id','value'=>$uids);
            $field = 'username,mobile,id,user_head,is_pic_tmp,user_rank';
            $user_res = $this->user_model->selectUsers($field,'',$where_in);
            $user_data = array_column($user_res,null,'id');

            //customer repaly
            $this->load->model("comment_reply_model");

            foreach($result_array as &$val){
                $val['user_name'] = empty($user_data[$val['uid']]['username']) ? encrypt_num($user_data[$val['uid']]['mobile']) : encrypt_num($user_data[$val['uid']]['username']);

                // set userface
                $user_head = unserialize($user_data[$val['uid']]['user_head']);
                $userface = $user_head['middle'];
                if ($user_data[$val['uid']]['is_pic_tmp'] == 1) {
                    if (strstr($userface, "http")) {
                        $val['userface'] = $userface;
                    } else {
                        $val['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL_TMP . $userface;
                    }
                } else {
                    if (strstr($userface, "http")) {
                        $val['userface'] = $userface;
                    } else {
                        $val['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL . $userface;
                    }
                }
                unset($user_data[$val['uid']]['user_head'], $user_data[$val['uid']]['is_pic_tmp']);
                $val['user_rank'] = $user_data[$val['uid']]['user_rank'];

                if(!empty($val['images'])){
                    $images = explode(',', $val['images']);
                    foreach($images as &$v){
                        if($val['is_pic_tmp']==1){
                            $v = PIC_URL_TMP.$v;
                        }else{
                            $v = PIC_URL.$v;
                        }
                    }
                    $val['images'] = join(',',$images);
                }
                if(!empty($val['thumbs'])){
                    $thumbs = explode(',', $val['thumbs']);
                    foreach($thumbs as &$v){
                        if($val['is_pic_tmp']==1){
                            $v = PIC_URL_TMP.$v;
                        }else{
                            $v = PIC_URL.$v;
                        }
                    }
                    $val['thumbs'] = join(',',$thumbs);
                }
                unset($val['is_pic_tmp']);

                //customer repaly
                $fieldReplay = 'id,comment_id,content,time';
                $where = 'comment_id = '.$val['id'];
                $customer_repaly = $this->comment_reply_model->selectCommentReply($fieldReplay,$where);
                if(!empty($customer_repaly))
                {
                    $customer_repaly[0]['time'] = date('Y-m-d H:i:s',$customer_repaly[0]['time']);
                }
                $val['customer_repaly'] = $customer_repaly;
            }
        }
        return array('list'=>$result_array);
    }

    function comments($pid,$page_size,$curr_page,$type=0,$comment_type=0,$show=1){
		$field = "id,content,time,star,is_pic_tmp,images,uid,thumbs,star_eat,star_show";

        //只看有内容的评论
        $field_time = date("Y-m-d", strtotime("-10 months",time()));
        if($show == 1)
        {
            $where = array(
                'is_review' => 1,
                'show' => 1,
                'content <>'=>'此人没有写文字评论哦~',
                'time >'=>$field_time
            );
        }
        else
        {
            $where = array(
                'is_review' => 1,
                'show' => 1,
                'time >'=>$field_time
            );
        }
        
		$order = 'id desc';
		$limit = array('curr_page'=>$curr_page,'page_size'=>$page_size);
		$where_in[] = array('key' => 'product_id', 'value' => $pid);

		if(!empty($type)){
			$star = $this->parse_type($type);
			if(!empty($star)){
				$where_in[] = array('key'=>'star','value'=>$star);
			}
		}
		if(!empty($comment_type)){
			$where['type'] = 1;
		}
		$result_array = $this->selectComments($field,$where,$where_in,$order,$limit,' force index(index_product_id)');

		if(!empty($result_array)){
			$this->load->model("user_model");
			$uids = array_column($result_array,'uid');
			$where_in = array('key'=>'id','value'=>$uids);
			$field = 'username,mobile,id,user_head,is_pic_tmp,user_rank';
			$user_res = $this->user_model->selectUsers($field,'',$where_in);
			$user_data = array_column($user_res,null,'id');

            //customer repaly
            $this->load->model("comment_reply_model");

			foreach($result_array as &$val){
				$val['user_name'] = empty($user_data[$val['uid']]['username']) ? encrypt_num($user_data[$val['uid']]['mobile']) : encrypt_num($user_data[$val['uid']]['username']);

                // set userface
                $user_head = unserialize($user_data[$val['uid']]['user_head']);
                $userface = $user_head['middle'];
                if ($user_data[$val['uid']]['is_pic_tmp'] == 1) {
                    if (strstr($userface, "http")) {
                        $val['userface'] = $userface;
                    } else {
                        $val['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL_TMP . $userface;
                    }
                } else {
                    if (strstr($userface, "http")) {
                        $val['userface'] = $userface;
                    } else {
                        $val['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL . $userface;
                    }
                }
                unset($user_data[$val['uid']]['user_head'], $user_data[$val['uid']]['is_pic_tmp']);
                $val['user_rank'] = $user_data[$val['uid']]['user_rank'];

				if(!empty($val['images'])){
					$images = explode(',', $val['images']);
					foreach($images as &$v){
						if($val['is_pic_tmp']==1){
							$v = PIC_URL_TMP.$v;
						}else{
							$v = PIC_URL.$v;
						}
					}
					$val['images'] = join(',',$images);
				}
				if(!empty($val['thumbs'])){
					$thumbs = explode(',', $val['thumbs']);
					foreach($thumbs as &$v){
						if($val['is_pic_tmp']==1){
							$v = PIC_URL_TMP.$v;
						}else{
							$v = PIC_URL.$v;
						}
					}
					$val['thumbs'] = join(',',$thumbs);
				}
				unset($val['is_pic_tmp']);

                //customer repaly
                $fieldReplay = 'id,comment_id,content,time';
                $where = 'comment_id = '.$val['id'];
                $customer_repaly = $this->comment_reply_model->selectCommentReply($fieldReplay,$where);
                if(!empty($customer_repaly))
                {
                    $customer_repaly[0]['time'] = date('Y-m-d H:i:s',$customer_repaly[0]['time']);
                }
                $val['customer_repaly'] = $customer_repaly;
			}
		}
		return array('list'=>$result_array);
	}

	function insComment($data){
		if(empty($data)){
			return false;
		}
		$this->db->insert('comment_new', $data);
        		$insert_id = $this->db->insert_id();
        		return $insert_id;
	}

	private function parse_type($type) {
		switch($type) {
			case "good" :
				return array(4,5);
				break;
			case "normal" :
				return array(2,3);
				break;
			case "bad" :
				return array(0,1);
				break;
			default :
				return array();
				break;
		}
	}

    public function get_push_comments(){
        $sql = "select c.id,o.order_name,p.product_name,c.content,c.star,c.star_eat,c.star_show,c.time from ttgy_comment_new c join ttgy_order o on o.id=c.order_id join ttgy_product p on c.product_id=p.id where c.time>='2016-11-20' and c.sync_status=0 limit 200";
        $res = $this->db->query($sql)->result_array();
        return $res;
    }

    public function set_sync($ids,$sync_status=1){
        $this->update(array('sync_status'=>$sync_status),array('id'=>$ids));
    }
}