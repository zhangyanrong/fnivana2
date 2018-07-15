<?php
namespace bll;
include_once("snscomm.php");
/**
 * 果食用户中心
 */
class Snscenter extends Snscomm
{
	//官方名称
	private $sys_username = "水果君";
	//官方图片
	private $sys_userface = "up_images/avatar/sys_userpic.jpg";

	public function __construct($params = array()){
		$this->ci = &get_instance();

		$this->ci->load->helper('public');
		$session_id = isset($params['connect_id'])?$params['connect_id']:'';
		if($session_id){
			$this->ci->load->library('session',array('session_id'=>$session_id));
		}
	    	$this->stime = time();
	}

	//notify
	//更新消息通知状态
	public function upNotify($params){
		$this->ci->load->model('fruit_notify_model');

		$res = $this->_ckuser();
		if($res['code']!='200'){
			return $res;
		}

		$uid = $res['msg'];
		$where = array('uid'=>$uid,'state'=>0);
		if(!empty($params['id'])){
			$where['id'] = $params['id'];
		}
		$res = $this->ci->fruit_notify_model->upNotify($where);
		if(!empty($res)){
			return array('code'=>'200','msg'=>'更新成功');
		}else{
			return array('code'=>'300','msg'=>'更新失败');
		}
	}
	//消息通知列表
	public function listNotify($params){
		$this->ci->load->model('fruit_notify_model');

		$res = $this->_ckuser();
		if($res['code']!='200'){
			return array('notify_num'=>0,'notify_data'=>array());
		}

		$uid = $res['msg'];
		$field = "id,aid,type,state,ctime,remark";
		$where = array('uid'=>$uid);
		if(isset($params['state'])){
			$where['state'] = $params['state'];
		}
		$page = isset($params['page'])?$params['page']:1;
		$limit = isset($params['limit'])?$params['limit']:10;
		$limits = array('curr_page'=>$page,'page_size'=>$limit);
		$result = $this->ci->fruit_notify_model->getNotifyList($field, $where, $limits);
		foreach($result as &$val){
			$remark = unserialize($val['remark']);
			$val['notify_info'] = array(
				'uid'=>empty($remark['uid'])?0:$remark['uid'],
				'username'=>empty($remark['uid']) ? $this->sys_username:$remark['username'],
				'content'=>empty($remark['content'])?'':urldecode($remark['content']),
			);
			unset($val['remark']);
		}
		$data['notify_num'] = $this->ci->fruit_notify_model->getNotifyCount($uid);
		$data['notify_data'] = $result;
        		return $data;
	}

	//userinfo
	//获取果食用户信息
	public function getUserInfo($params){
		$this->ci->load->model('user_model');
		$this->ci->load->model('fruit_comments_model');
		$this->ci->load->model('bake_comments_model');
		$this->ci->load->model('bake_collection_model');
		$this->ci->load->model('fruit_articles_model');

        if(!empty($params['uid']) && $params['uid'] == -1) //匿名
        {
            $result['username'] = '果园用户';
            $result['userface'] = PIC_URL.$this->sys_userface;
            $result['userrank'] = 0;
        }
        else
        {
            $res = $this->_ckuser();
            if($res['code']!='200'){
                return $res;
            }
            $uid = $res['msg'];
            if(!empty($params['uid'])){
                $userinfo = $this->ci->user_model->getUser($params['uid'], '', 'id,username,user_head,mobile,is_pic_tmp');

                $result['ufruitnums'] = $this->ci->fruit_articles_model->getUserArticlesNum($params['uid'],array('state'=>1));
                //隐藏用户名
                $userinfo['username']  = encrypt_num($userinfo['username']);

            }else{
                $userinfo = $this->ci->user_model->getUser($uid, '', 'id,username,user_head,mobile,is_pic_tmp');

                $fruit_comment_nums = $this->ci->fruit_comments_model->getUserCommentsAnum($uid);
                $bake_comment_nums = $this->ci->bake_comments_model->getUserCommentsAnum($uid);
                $comment_nums = $fruit_comment_nums + $bake_comment_nums;
                $result['cartnums'] = $comment_nums;
                $result['ufruitnums'] = $this->ci->fruit_articles_model->getUserArticlesNum($uid);
                $result['cbakenums'] = $this->ci->bake_collection_model->getUserCollectAnum($uid);
            }
            $result['username'] = $userinfo['username'];
            $result['userface'] = $userinfo['userface'];
            $result['userrank'] = $userinfo['user_rank'];
        }

		return $result;
	}

	//获取用户果食文章列表
	public function getUserArticleList($params){
		$this->ci->load->model('fruit_articles_model');

		$res = $this->_ckuser();
		if($res['code']!='200'){
			return $res;
		}
		$uid = $res['msg'];
		$curr_uid = (int)$params['uid'];
		$page = isset($params['page'])?$params['page']:1;
		$limit = isset($params['limit'])?$params['limit']:10;
		$limits = array('curr_page'=>$page,'page_size'=>$limit);
		if(empty($curr_uid)){
			$field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp,state,title,is_can_comment";
			$where = array('uid'=>$uid);
			$res = $this->ci->fruit_articles_model->getArticleList($field, $where, '', '', $limits, 1);
		}else{
			$field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp,title,is_can_comment";
			$where = array('uid'=>$curr_uid);
			$res = $this->ci->fruit_articles_model->getArticleList($field, $where, '', '', $limits);
		}
		$art_result = $this->_initFruitArticles($res, $uid);

        		return $art_result;
	}

	//获取用户评论列表
	public function getMaxCommentArticleList($params){
		$this->ci->load->model('fruit_articles_model');
		$this->ci->load->model('bake_articles_model');
		$this->ci->load->model('snscenter_model');
		$this->ci->load->model('user_model');
		$this->ci->load->model('bake_comments_model');
		$this->ci->load->model('fruit_comments_model');

		$res = $this->_ckuser();
		if($res['code']!='200'){
			return $res;
		}

		//获取评论过的文章id
		$uid = $res['msg'];
		$page = isset($params['page'])?$params['page']:1;
		$limit = isset($params['limit'])?$params['limit']:10;
		$limits = array('curr_page'=>$page,'page_size'=>$limit);
		$max_com = $this->ci->snscenter_model->getMaxComments($uid, $limits);

		$return = array();
		if(!empty($max_com)){
			foreach($max_com as &$val){
				if($val['type'] == 1){//fruit
					$fruit_aids[] = $val['aid'];
				}else{//bake
					$bake_aids[] = $val['aid'];
				}
			}
			if(!empty($fruit_aids)){
				$field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp";
				$where_in = array('key'=>'id','value'=>$fruit_aids);
				$res = $this->ci->fruit_articles_model->getArticleList($field, '', $where_in, '', '');
				$fruit_result = $this->_initFruitArticles($res, $uid);
				$fruit_result = array_column($fruit_result, null, 'id');
			}
			if(!empty($bake_aids)){
				$field = "id,title,summary,content,type,photo,thumbs,sec_id,is_recommend";
				$where_in_bake[] = array('key'=>'id','value'=>$bake_aids);
				$res = $this->ci->bake_articles_model->getArticleList($field, '', $where_in_bake, 'id', '');
				$bake_result = $this->_initBakeArticles($res, $uid);
				$bake_result = array_column($bake_result, null, 'id');
			}
			$return = $this->_initComment($max_com);
			foreach($return as &$val){
				if($val['type'] == 1){//fruit
					$val['article'] = $fruit_result[$val['aid']];
				}else{//bake
					$val['article'] = $bake_result[$val['aid']];
				}
			}
		}
		return $return;
	}
	//获取用户收藏列表
	public function getCollectArticleList($params){
		$this->ci->load->model('bake_articles_model');
		$this->ci->load->model('bake_collection_model');

		$res = $this->_ckuser();
		if($res['code']!='200'){
			return $res;
		}
		$uid = $res['msg'];

		$aids = $this->ci->bake_collection_model->getUserCollectAids($uid);
		$art_result = array();
		if(!empty($aids)){
			$page = isset($params['page'])?$params['page']:1;
			$limit = isset($params['limit'])?$params['limit']:10;
			$limits = array('curr_page'=>$page,'page_size'=>$limit);

			$field = "id,title,summary,content,type,photo,thumbs,sec_id,is_recommend,ctime";
			$where_in[] = array('key'=>'id','value'=>$aids);
			$res = $this->ci->bake_articles_model->getArticleList($field, '', $where_in, 'id', $limits);
			$art_result = $this->_initBakeArticles($res, $uid);
		}

        		return $art_result;
	}

	//格式显示的文章信息
	private function _initBakeArticles($res,$uid=0){
		$this->ci->load->model('bake_worths_model');
		$this->ci->load->model('bake_comments_model');
		$this->ci->load->model('bake_collection_model');
		$this->ci->load->model('bake_section_model');

		if(!empty($res)){
			$aids = array_column($res, 'id');
			$comment_nums = $this->ci->bake_comments_model->getCommentsNum($aids);
			$worth_nums = $this->ci->bake_worths_model->getWorthsNum($aids);
			$collection_nums = $this->ci->bake_collection_model->getCollectionNum($aids);
			$user_worths = $this->ci->bake_worths_model->getUserWorth($uid);
			$user_collection = $this->ci->bake_collection_model->getUserCollection($uid);
			//$sec_res = $this->ci->bake_section_model->getList(array('state' => 1));
			//$sec_list = array_column($sec_res, null, 'id');

			//set res
			foreach($res as &$val){
				//set other
				$val['share_url'] = "http://m.fruitday.com/sns/bake/".$val['id'];
				$val['stime'] = $this->stime;
				$val['comment_num'] = !isset($comment_nums[$val['id']])?0:$comment_nums[$val['id']];
				$val['worth_num'] = !isset($worth_nums[$val['id']])?0:$worth_nums[$val['id']];
				$val['collection_num'] = !isset($collection_nums[$val['id']])?0:$collection_nums[$val['id']];
				$val['is_worth'] = isset($user_worths[$val['id']]) ? 1 : 0;
				$val['is_collect'] = isset($user_collection[$val['id']]) ? 1 : 0;
                $articleSectioins = $this->ci->bake_articles_model->getArticleSections($val['id']);
                if ($articleSectioins) {
                    foreach ($articleSectioins as $section) {
                        $temp = array(
                            'sec_id' => $section['id'],
                            'sec_name' => $section['name'],
                            'sec_photo' => (empty($section['photo']) ? "" : PIC_URL . $section['photo']),
                        );
                        array_push($val['section'], $temp);
                    }
                }
//				if($val['sec_id']){
//					$val['sec_name'] = $sec_list[$val['sec_id']]['name'];
//					$val['sec_photo'] = $sec_list[$val['sec_id']]['photo'];
//				}
				$val['ptype'] = 3;
			}
		}
		return $res;
	}
	//格式显示的文章信息
	private function _initFruitArticles($res,$uid=0){
		$this->ci->load->model('fruit_worths_model');
		$this->ci->load->model('fruit_comments_model');
		$this->ci->load->model('fruit_topic_model');
		$this->ci->load->model('user_model');

		if(!empty($res)){
			//获取查询条件
			$uids = array_column($res, 'uid');
			$tids = array_column($res, 'tid');
			//批量获取主题
			$topic_field = 'id, title, start_time, end_time';
			$topic_where_in = array('key'=>'id','value'=>$tids);
			$topic_res = $this->ci->fruit_topic_model->getTopicList($topic_field, '', '', '', $topic_where_in);
			$topic_result = array_column($topic_res, null, 'id');
			//批量获取用户信息
			$user_res = $this->ci->user_model->getUsers($uids);
			$user_result = array_column($user_res, null, 'id');

			$comment_nums = $this->ci->fruit_comments_model->getCommentsNum($aids);
			$worth_nums = $this->ci->fruit_worths_model->getWorthsNum($aids);
			$user_worths = $this->ci->fruit_worths_model->getUserWorth($uid);

			//set res
			foreach($res as &$val){
				//set user
				$val['username'] = $user_result[$val['uid']]['username'];
                if($val['uid'] == -1) //匿名
                {
                    $val['username'] = '果园用户';
                    $val['userface'] = PIC_URL.$this->sys_userface;
                    $val['userrank'] = 0;
                }
                else{
                    $val['username'] = empty($val['uid']) ? $this->sys_username:encrypt_num($val['username']);
                    $val['userrank'] = empty($val['uid']) ? 6 : $user_result[$val['uid']]['userrank'];
                    $val['userface'] = empty($val['uid']) ? PIC_URL.$this->sys_userface :$user_result[$val['uid']]['userface'];
                }

				//set topic
				$val['topic_id'] = empty($val['tid']) ? '' : $val['tid'];
				$val['topic_title'] = empty($topic_result[$val['tid']]['title']) ? '' : $topic_result[$val['tid']]['title'];
				//set other
				$val['share_url'] = "http://m.fruitday.com/sns/fruit/".$val['id'];
				$val['stime'] = $this->stime;
				$val['comment_num'] = !isset($comment_nums[$val['id']])?0:$comment_nums[$val['id']];
				$val['worth_num'] = !isset($worth_nums[$val['id']])?0:$worth_nums[$val['id']];
				$val['is_liked'] = isset($user_worths[$val['id']]) ? 1 : 0;
				if(!empty($uid)&&!empty($val['uid'])){
					$val['can_delete'] = ($uid==$val['uid']) ? true : false;
				}else{
					$val['can_delete'] = false;
				}

				$val['latest_comments'] = $this->ci->fruit_comments_model->getCommentListByGuan($val['id'], 1, 1);
				$val['latest_comments'] = $this->_initComment($val['latest_comments']);

                //is can comment
                $val['is_can_comment'] = ($val['is_can_comment'] == 0) ? 1 : 0;

				unset($val['tid'], $val['is_pic_tmp']);
			}
		}
		return $res;
	}

	/**
	 * [formatComment 格式化评论]
	 * @param  [array] $val [description]
	 * @param  [array] $userinfo [description]
	 * @return [array]      [description]
	 */
	private function _initComment($res){
		if(!empty($res)){
			//set res
			foreach($res as &$val){
				//set other
				$val['content'] = empty($val['content'])?'':urldecode($val['content']);
				$val['stime'] = $this->stime;
				if(empty($val['uid'])){
					$val['userface'] = PIC_URL.$this->sys_userface ;
					$val['userrank'] = 6;
					$val['username'] = $this->sys_username;
				}
				if(!empty($val['pid'])){
					$val['is_replay'] = 1;
					$val['replay'] = unserialize($val['remark']);
					$val['replay']['username'] = empty($val['replay']['uid'])?$this->sys_username:encrypt_num($val['replay']['username']);
					$val['replay']['content'] = empty($val['replay']['content'])?"":urldecode($val['replay']['content']);
				}else{
					$val['is_replay'] = 0;
				}

				unset($val['pid'],$val['remark']);
			}
		}
		return $res;
	}
}
