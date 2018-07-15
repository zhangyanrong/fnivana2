<?php
namespace bll;
include_once("snscomm.php");

class Fruit extends Snscomm
{
    //文章上传图片
    private $photolimit = 9;
    //文章图片存储路径
    private $photopath = "images/";
    //用户头像
    private $head_photopath = "up_images/";
    //官方名称
    private $sys_username = "水果君";
    //官方图片
    private $sys_userface = "up_images/avatar/sys_userpic.jpg";
    //当前机器时间
    private $stime;
    //文章图片大小
    private $thumb_size = "320";
    //用户头像大小
    private $userface_size = array('big'=>200,'middle'=>130,'small'=>112);
    //doWorth,fundoWorth => setWorth
    //getArticleList=>getMaxArticleList
    //memcache(getDetailArticle,getDetailTopic)

    public function __construct($params = array()){
        $this->ci = &get_instance();

        $this->ci->load->helper('public');
        $session_id = isset($params['connect_id'])?$params['connect_id']:'';
        if($session_id){
            $this->ci->load->library('session',array('session_id'=>$session_id));
        }
            $this->photopath = $this->photopath.date("Y-m-d");
            $this->head_photopath = $this->head_photopath.date("Y-m-d");
            $this->stime = time();
    }

    /**
     * [checkInvate 检测黑名单]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function checkInvate($params){
        return $this->_ckinvate($params);
    }

    //发表果食文章
    public function doArticle($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_topic_model');

        //权限检查
        $ck_res = $this->_ckinvate($params);
        if($ck_res['code']!='200'){
            return $ck_res;
        }
        $uid = $ck_res['msg'];

        // $img_arr = $this->savePhoto();

        // 评论图片上传到七牛
        // 蔡昀辰 2015
        if ($_FILES && count($_FILES) <= $this->photolimit) {
            $img_arr = [
                "images"=>[],
                "thumbs"=>[]
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path   = $photo['tmp_name'];
                $name   = $photo['name'];
                $date   = date("ymd", time());
                $prefix = 'img/guoshi';
                $hash   = str_replace('/tmp/php', '', $path);
                $key    = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);

                if($ret) {
                    $img_arr["images"][] = str_replace('img/', '', $key);
                    $img_arr["thumbs"][] = str_replace('img/', '', $key).'-thumb';
                }
            }
        }

        if(!empty($params['topicTitle']))
        {

            $tid = $this->ci->fruit_topic_model->getTopicTitle($params['topicTitle']);
            if(!empty($tid))
            {
                $data['tid'] = $tid['id'];
            }
            else
            {
                $rs = array();
                $rs['state'] = 1;
                $rs['title'] = trim($params['topicTitle']);
                $rs['description'] = trim($params['topicTitle']);
                $rs['remark'] = trim($params['topicTitle']);
                $rs['summary'] = trim($params['topicTitle']);
                $rs['ctime'] = time();
                $rs['start_time'] = time();
                $rs['end_time'] = time()+86400*365;
                $rs['type'] = 0;
                $rs['is_top'] = 0;
                $rs['is_type'] = 2;

                //默认图片
                $rs['photo'] = 'app_topic_user_bg.png';
                $rs['thumbs'] = 'app_topic_user_bg.png';

                $this->ci->fruit_topic_model->insTopic($rs);

                $tid = $this->ci->fruit_topic_model->getTopicTitle($params['topicTitle']);
                $data['tid'] = $tid['id'];
            }
        }
        else
        {
            $data['tid'] = empty($params['tid']) ? 0 : $params['tid'];
        }

        $data['state'] = 1;
        $data['type'] = 0;
        $data['description'] = empty($params['description']) ? "" : strip_tags($params['description']);
        $data['title'] = empty($params['title']) ? "" : strip_tags($params['title']);
        $data['ctime'] = $this->stime;
        $data['utime'] = $this->stime;
        $data['uid'] = $uid;
        $data['photo'] = empty($img_arr['images']) ? "" : implode(",",$img_arr["images"]);
        // images/2014-09-09/1410232479_app227943.jpg
        $data['thumbs'] = empty($img_arr['images']) ? "" : implode(",",$img_arr["thumbs"]);
        // images/2014-09-08/1410170246_app259814_200.jpg

        $data['is_pic_tmp'] = 1;

        $res = $this->ci->fruit_articles_model->insArticle($data);
        if($res){
            return array('code'=>'200','msg'=>'发布成功');
        }else{
            return array('code'=>'300','msg'=>'发布失败');
        }
    }

    public function doUserface($params){
        $this->ci->load->model('user_model');

        //_ckuser
        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        if(empty($_FILES['photo']['size'])){
            return array('code'=>'300','msg'=>'请上传图片');
        }

        // 头像图片上传到七牛
        // 蔡昀辰 2016
        if ($_FILES) {
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
                $prefix = 'img/user';
                $hash   = str_replace('/tmp/php', '', $path);
                $key    = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);
                if ($ret) {
                    $user_head['big']    = str_replace('img/', '', $key) . '-userbig';
                    $user_head['middle'] = str_replace('img/', '', $key) . '-usermiddle';
                    $user_head['small']  = str_replace('img/', '', $key) . '-usersmall';
                }
            }
        }

        // $head_photopath = $this->head_photopath;
        // $config['upload_path'] = $this->ci->config->item('photo_base_path').$head_photopath;
        // $config['allowed_types'] = 'gif|jpg|png';
        // $config['encrypt_name'] = true;
        // $this->ci->load->library('upload', $config);
        // if ( ! $this->ci->upload->do_upload('photo')){
        //  return array('code'=>'300','msg'=>'上传失败');
        // }
        // $image_data = $this->ci->upload->data();
        // $curr_image_info = pathinfo($image_data['file_name']);
        // $this->ci->load->library('image_lib');
        // foreach($this->userface_size as $k=>$v){
        //  $thumb_config = array();
        //  $thumb_image_info = $curr_image_info['filename']."_".$v;
        //  $thumb_photo =  $thumb_image_info.".".$curr_image_info['extension'];
        //  $thumb_config['image_library'] = 'gd2';
        //  $thumb_config['source_image'] = $config['upload_path']."/".$image_data['file_name'];
        //  $thumb_config['thumb_marker'] = "_".$v;
        //  $thumb_config['create_thumb'] = TRUE;
        //  $thumb_config['maintain_ratio'] = TRUE;
        //  $thumb_config['width'] = $v;
        //  $thumb_config['height'] = $v;
        //  $this->ci->image_lib->initialize($thumb_config);
        //  if ( ! $this->ci->image_lib->resize())
        //  {
        //      return array('code'=>'300','msg'=>'上传失败');
        //  }
        //  $user_head[$k] = $head_photopath."/".$thumb_photo;
        // }

        $res = $this->ci->user_model->updateUser(array('id'=>$uid), array('user_head'=>serialize($user_head),'is_pic_tmp'=>1));
        if(empty($res)){
            return array('code'=>'300','msg'=>'上传失败');
        }else{
            return array('code'=>'200','msg'=>'上传成功');
        }
    }

    //发表评论
    public function doComment($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_comments_model');
        $this->ci->load->model('fruit_notify_model');
        $this->ci->load->model('user_model');

        //匿名用户
        if($params['uid'] == -1)
        {
            $data['state'] = 1;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $params['uid'];
            $data['content'] = urlencode(strip_tags($params['content']));

            $id = $this->ci->fruit_comments_model->insComment($data);
        }
        else{
            //权限检查
            $ck_res = $this->_ckinvate($params);
            if($ck_res['code']!='200'){
                return $ck_res;
            }
            $uid = $ck_res['msg'];


            //回复评论
            if($params['replay_id']){
                $data['pid'] = $params['replay_id'];
                $remark['uid'] = $params['replay_uid'];
                $remark['username'] = $params['replay_username'];
                $remark['content'] = urlencode($params['replay_content']);
                $data['remark'] = serialize($remark);
            }

            $data['state'] = 1;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $uid;
            $data['content'] = urlencode(strip_tags($params['content']));

            $id = $this->ci->fruit_comments_model->insComment($data);

            if($params['replay_id'] && $params['replay_uid'] > 0){
                //调用通知start
                $this->ci->load->library("notify");
                $type = ['app'];
                $target =  ["uid"=>$params['replay_uid']];
                $message = ["title"=>'果食回复通知',"body"=>'您发的果食文章被回复啦，赶紧去查看一下吧'];
                $extras = ["tabType"=>'GuoShi',"type" =>'1','pid'=>$params['aid']];
                $params = [ "source" => "api", "mode" => "single", "type" => json_encode($type), "target" => json_encode($target), "message" => json_encode($message), "extras" => json_encode($extras)];
                $no  = $this->ci->notify->send($params);
                $no['source'] = $params;
                $this->ci->load->library('fdaylog');
                $db_log = $this->ci->load->database('db_log', TRUE);
                $this->ci->fdaylog->add($db_log,'GuoShi-fruit',json_encode($no));
                //调用通知end
            }

            //新增消息通知
            if($id){
                if($params['replay_id']){
                    //获取通知用户信息
                    $notice_uid = $remark['uid'];
                    $notice_type=3;
                }else{
                    //获取通知用户信息
                    $notice_uid = $this->ci->fruit_articles_model->getArticlesUid($data['aid']);
                    $notice_type=2;
                }
                $notify_userinfo = $this->ci->user_model->getUser($data['uid']);
                $notify_data = array('ctime'=>$data['ctime'],'type'=>$notice_type,'aid'=>$data['aid'],'uid'=>$notice_uid,'pid'=>$id,
                    'notify_uid'=>$data['uid'],'notify_username'=>$notify_userinfo['username'],'notify_content'=>$data['content'],
                );
                $this->ci->fruit_notify_model->addNotify($notify_data);
            }
        }

        $result = $this->ci->fruit_comments_model->getComment($id);

        //更新文章时间
        $this->ci->fruit_articles_model->upArticleTime($data['aid']);

        if(!empty($result)){
            $return = $this->_initComment(array($result));
            return $return[0];
        }else{
            return array();
        }
    }

    //喜欢果食文章
    public function setWorth($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_worths_model');
        $this->ci->load->model('fruit_notify_model');
        $this->ci->load->model('user_model');

        //匿名用户
        if($params['uid'] == -1)
        {
            $worth_type = 0;
            $data['type'] = $worth_type;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $params['uid'];
            $ck_res = $this->ci->fruit_worths_model->insFruitWorth($data);
        }
        else{
            $res = $this->_ckuser();
            if($res['code']!='200'){
                return $res;
            }
            $uid = $res['msg'];
            if(empty($params['aid'])){
                return array('code'=>'300','msg'=>'请选择喜欢文章');
            }

            $currUserWorth = $this->ci->fruit_worths_model->getCurrUserWorth($uid, $params['aid']);
            if(!empty($currUserWorth)){
                $worth_type = $currUserWorth['type'] == 1 ? 0 : 1;
                $ck_res = $this->ci->fruit_worths_model->upFruitWorth($params['aid'], $uid, $worth_type);
            }else{
                $worth_type = 0;
                $data['type'] = $worth_type;
                $data['aid'] = $params['aid'];
                $data['ctime'] = $this->stime;
                $data['uid'] = $uid;
                $ck_res = $this->ci->fruit_worths_model->insFruitWorth($data);
                $id = $ck_res;
                if($id){
                    //获取通知用户信息
                    $notice_id = $this->ci->fruit_articles_model->getArticlesUid($data['aid']);
                    $notify_userinfo = $this->ci->user_model->getUser($data['uid']);
                    $notify_data = array('ctime'=>$data['ctime'],'type'=>1,'aid'=>$data['aid'],'uid'=>$notice_id,'pid'=>$id,
                        'notify_uid'=>$data['uid'],
                        'notify_username'=>$notify_userinfo['username'],
                        'notify_content'=>'',
                    );
                    $this->ci->fruit_notify_model->addNotify($notify_data);
                }
            }
        }

        if($ck_res){
            // set user behavior
            $sql = sprintf("INSERT INTO `%s`(`article_id`, `type`, `date`, `like`) VALUES ('%s', 'fruit', '%s', 1) ON DUPLICATE KEY UPDATE `like` = `like` + 1", $this->ci->db->dbprefix('article_user_behavior'), $params['aid'], date('Y-m-d', time()));
            $this->ci->db->query($sql);

            return array('type'=>$worth_type);
        }else{
            return array('code'=>'300','msg'=>'点赞失败');
        }
    }

    //喜欢果食文章
    public function doWorth($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_worths_model');
        $this->ci->load->model('fruit_notify_model');
        $this->ci->load->model('user_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        if(empty($params['aid'])){
            return array('code'=>'300','msg'=>'请选择喜欢文章');
        }
        if(!$this->ci->fruit_worths_model->checkUserWorth($uid,$params['aid'])){
            return array('code'=>'300','msg'=>'请勿重复点赞');
        }

        if(!$this->ci->fruit_worths_model->checkUserWorth($uid,$params['aid'],1)){
            $ck_res = $this->ci->fruit_worths_model->upFruitWorth($params['aid'], $uid, 0);
        }else{
            $data['type'] = 0;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $uid;
            $ck_res = $this->ci->fruit_worths_model->insFruitWorth($data);
            $id = $ck_res;
            if($id){
                //获取通知用户信息
                $notice_id = $this->ci->fruit_articles_model->getArticlesUid($data['aid']);
                $notify_userinfo = $this->ci->user_model->getUser($data['uid']);
                $notify_data = array('ctime'=>$data['ctime'],'type'=>1,'aid'=>$data['aid'],'uid'=>$notice_id,'pid'=>$id,
                                'notify_uid'=>$data['uid'],
                                'notify_username'=>$notify_userinfo['username'],
                                'notify_content'=>'',
                        );
                $this->ci->fruit_notify_model->addNotify($notify_data);
            }
        }
        if($ck_res){
            return array('code'=>'200','msg'=>'点赞成功');
        }else{
            return array('code'=>'300','msg'=>'点赞失败');
        }
    }

    //取消喜欢果食文章
    public function undoWorth($params){
        $this->ci->load->model('fruit_worths_model');

        $session_id = $params['connect_id'];
        $res = $this->_ckuser($session_id);
        if($res['code']!='200'){
            return $res;
        }

        $uid = $res['msg'];
        if(empty($params['aid'])){
            return array('code'=>'300','msg'=>'请选择取消喜欢的文章');
        }
        if(!$this->ci->fruit_worths_model->checkUserWorth($uid,$params['aid'],1)){
            return array('code'=>'300','msg'=>'您太纠结了，请再三思');
        }

        $res = $this->ci->fruit_worths_model->upFruitWorth($params['aid'], $uid, 1);
        if($res){
            return array('code'=>'200','msg'=>'取消成功');
        }else{
            return array('code'=>'300','msg'=>'取消失败');
        }
    }

    //获取果食文章
    public function getArticleList($params){
        $this->ci->load->model('fruit_articles_model');

        $res = $this->_ckuser();
        $uid = 0;
        if($res['code']=='200'){
            $uid = $res['msg'];
        }

        $tid = $params['tid'];
        $type = isset($params['type'])?$params['type']:-1;
        $page = isset($params['page'])?$params['page']:1;
        $limit = isset($params['limit'])?$params['limit']:10;
        $limits = array('curr_page'=>$page,'page_size'=>$limit);
        $field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp,title,is_can_comment";
        if($type!=-1){
            $where['type'] = $type;
        }
        if(!empty($tid)){
            $where['tid'] = $tid;
        }
        $res = $this->ci->fruit_articles_model->getArticleList($field, $where, '', '', $limits);
        $result = $this->_initArticles($res, $uid);

        if(!empty($result)){
            foreach($result as &$val){
                $comment_params = array();
                $comment_params['aid'] = $val['id'];
                $comment_params['page'] = 1;
                $comment_params['limit'] = 3;
                $val['latest_comments'] = $this->getArtCommentList($comment_params);
            }
        }
                return $result;
    }

    public function getMaxArticleList($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_topic_model');

        $res = $this->_ckuser();
        $uid = 0;
        if($res['code']=='200'){
            $uid = $res['msg'];
        }
        $art_field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp,title,utime,sort,is_can_comment";
        $topic_field = 'id, title, start_time, end_time, photo, thumbs, description, type, ctime, summary,is_type';

        //top
        if($params['page']<=1 || !isset($params['page'])){
            $top_where = array('is_top'=>'1');
            $top_art_res = $this->ci->fruit_articles_model->getArticleList($art_field, $top_where, '');
            $top_topic_res = $this->ci->fruit_topic_model->getTopicList($topic_field, $top_where, '');
            $top_art_result = $this->_initArticles($top_art_res, $uid);
            $top_topic_result = $this->_initTopic($top_topic_res);
            $top_data = $this->_initMax($top_art_result, $top_topic_result);
        }else{
            $top_data = array();
        }

        //get main art
        $main_page = isset($params['page'])?$params['page']:1;
        $main_limit = isset($params['limit'])?$params['limit']:10;
        $main_limits = array('curr_page'=>$main_page,'page_size'=>$main_limit);
        $main_where = array('is_top'=>'0');
        $main_art_res = $this->ci->fruit_articles_model->getArticleList($art_field, $main_where, '', '', $main_limits);
        $main_art_result = $this->_initArticles($main_art_res, $uid);
        //get main topic
        $main_times = array_column($main_art_res, "ctime");
        $main_topic_where_one = $main_where;
        $main_topic_where_one['start_time >='] = min($main_times);
        $main_topic_where_one['start_time <='] = max($main_times);
        $main_topic_res_one = $this->ci->fruit_topic_model->getTopicList($topic_field, $main_topic_where_one, '', '', '');
        $main_topic_where_two = $main_where;
        $main_topic_where_two['start_time'] = 0;
        $main_topic_where_two['ctime >='] = min($main_times);
        $main_topic_where_two['ctime <='] = max($main_times);
        $main_topic_res_two = $this->ci->fruit_topic_model->getTopicList($topic_field, $main_topic_where_two, '', '', '');
        $main_topic_res = array_merge($main_topic_res_one, $main_topic_res_two);
        $main_topic_res = $this->_initTopic($main_topic_res);
        $main_data = $this->_initMax($main_art_result, $main_topic_res);

        //用户话题 － 过滤
        $rs =array();
        foreach($main_data as $key=>$val)
        {
            if($val['ptype'] != 2)
            {
                array_push($rs,$main_data[$key]);
            }
        }

        $result = array(
            'top'=>$top_data,
            'main'=>$rs,
        );
                return $result;
    }

    //获取果食文章列表
    public function getDetailArticle($params){
        $res = $this->_ckuser();
        $uid = 0;
        if($res['code']=='200'){
            $uid = $res['msg'];
        }

        $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['id'];
        $res = $this->_before_data($mem_key);
        if(empty($res)){
            $this->ci->load->model('fruit_articles_model');
            $id = $params['id'];
            $field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp,title,is_can_comment";
            $where = array('id'=>$id);
            $res = $this->ci->fruit_articles_model->getArticleList($field, $where, '', '', '');
            $this->_after_data($mem_key, $res);
        }
        $detail_res = array();
        if(!empty($res)){
            $result = $this->_initArticles($res, $uid);
            $detail_res = $result[0];

            // set user behavior
            $sql = sprintf("INSERT INTO `%s`(`article_id`, `type`, `date`, `view`) VALUES ('%s', 'fruit', '%s', 1) ON DUPLICATE KEY UPDATE `view` = `view` + 1", $this->ci->db->dbprefix('article_user_behavior'), $detail_res['id'], date('Y-m-d', time()));
            $this->ci->db->query($sql);
        }
                return $detail_res;
    }

    //获取文章评论列表
    public function getArtCommentList($params){
        $this->ci->load->model('fruit_comments_model');

        $aid = (int)$params['aid'];
        $page = isset($params['page'])?$params['page']:1;
        $limit = isset($params['limit'])?$params['limit']:10;
        $result = $this->ci->fruit_comments_model->getCommentList($aid, 0, $limit, $page);
        //$result = $this->ci->fruit_comments_model->getCommentList($aid,'',$limit, $page);
        $result = $this->_initComment($result);
                return $result;
    }

    //获取果食用户信息 --something to do
    public function getFruitUserInfo($params){
        $this->ci->load->model('user_model');
        $this->ci->load->model('fruit_comments_model');
        $this->ci->load->model('fruit_worths_model');
        $this->ci->load->model('fruit_articles_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }

        $uid = $res['msg'];
        $userinfo = $this->ci->user_model->getUser($uid, '', 'id,username,user_head,mobile,is_pic_tmp');
        $result = array(
                'username'=>$userinfo['username'],
                'userface'=>$userinfo['userface'],
                'cartnums'=>$this->ci->fruit_comments_model->getUserCommentsAnum($uid),
                'wartnums'=>$this->ci->fruit_worths_model->getUserWorthsAnum($uid),
                'uartnums'=>$this->ci->fruit_articles_model->getUserArticlesNum($uid),
            );
        return $result;
    }

    //获取用户果食文章列表 --something to do
    public function getUserArticleList($params){
        $this->ci->load->model('fruit_articles_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        $page = isset($params['page'])?$params['page']:1;
        $limit = isset($params['limit'])?$params['limit']:10;
        $limits = array('curr_page'=>$page,'page_size'=>$limit);
        $field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp,state";
        $where = array('uid'=>$uid);
        $res = $this->ci->fruit_articles_model->getArticleList($field, $where, '', '', $limits,1);
        $art_result = $this->_initArticles($res, $uid);

                return $art_result;
    }

    //获取用户评论列表 --something to do
    public function getCommentArticleList($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_comments_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }

        //获取评论过的文章id
        $uid = $res['msg'];
        $aids = $this->ci->fruit_comments_model->getUserCommentsAids($uid);
        $art_result = array();
        if(!empty($aids)){
            $page = isset($params['page'])?$params['page']:1;
            $limit = isset($params['limit'])?$params['limit']:10;
            $limits = array('curr_page'=>$page,'page_size'=>$limit);

            $field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp";
            $where_in = array('key'=>'id','value'=>$aids);
            $res = $this->ci->fruit_articles_model->getArticleList($field, '', $where_in, '', $limits);

            $art_result = $this->_initArticles($res, $uid);
        }

                return $art_result;
    }

    //获取用户点赞列表
    public function getWorthArticleList($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_worths_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        $aids = $this->ci->fruit_worths_model->getUserWorthsAids($uid);
        $art_result = array();
        if(!empty($aids)){
            $page = isset($params['page'])?$params['page']:1;
            $limit = isset($params['limit'])?$params['limit']:10;
            $limits = array('curr_page'=>$page,'page_size'=>$limit);

            $field = "id,ctime,description,photo,type,ptype,content,thumbs,uid,tid,is_pic_tmp";
            $where_in = array('key'=>'id','value'=>$aids);
            $res = $this->ci->fruit_articles_model->getArticleList($field, '', $where_in, '', $limits);

            $art_result = $this->_initArticles($res, $uid);
        }

                return $art_result;
    }

    //更新消息通知状态
    public function upNotify($params){
        $this->ci->load->model('fruit_notify_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }

        $uid = $res['msg'];
        $where = array('id'=>$params['id'],'uid'=>$uid,'state'=>0);
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

    //删除文章相关信息
    public function delArticle($params){
        $this->ci->load->model('fruit_articles_model');
        $this->ci->load->model('fruit_comments_model');
        $this->ci->load->model('fruit_notify_model');
        $this->ci->load->model('fruit_worths_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        $where = array('id'=>$params['id']);
        $res = $this->ci->fruit_articles_model->delArticle($where);
        if($res){
            $del_where = array('aid' => $params['id']);
            $this->ci->fruit_comments_model->delComments($del_where);
            $this->ci->fruit_worths_model->delWorths($del_where);
            $this->ci->fruit_notify_model->delNotifys($del_where);
            return array('code'=>'200','msg'=>'删除成功');
        }else{
            return array('code'=>'300','msg'=>'删除失败');
        }
    }

    //删除文章相关信息
    public function delComment($params){
        $this->ci->load->model('fruit_comments_model');
        $this->ci->load->model('fruit_notify_model');

        $res = $this->_ckuser();
        if($res['code']!='200'){
            return $res;
        }
        $uid = $res['msg'];

        $where = array('id'=>$params['id'],'uid'=>$uid);
        $res = $this->ci->fruit_comments_model->delComment($where);
        if($res){
            $notify_where = array('pid'=>$params['id']);
            $this->ci->fruit_notify_model->delNotify($notify_where);
            return array('code'=>'200','msg'=>'删除成功');
        }else{
            return array('code'=>'300','msg'=>'删除失败');
        }
    }

    /**
     * [getTopicList 获取主题列表]
     * @param  [array] $params [description]
     * @return [array]         [description]
     */
    public function getTopicList($params){
        $this->ci->load->model('fruit_topic_model');
        $this->ci->load->model('fruit_articles_model');

        //初始化数据
        $return = array();
        $res = array(1=>array(),2=>array(),3=>array());//话题类型1-话题周期内  2-话题周期未到 3-话题周期过期
        $curr_time = $this->stime;

        //获取主题数据
        $like = array();
        $limits = array();
        $field = '';
        $keyword = trim($params['keyword']);//关键字搜索
        if(!empty($keyword)){
            $like = array('key'=>'title','value'=>$keyword);
        }
        if(!empty($params['type'])){//0-获取基础主题信息1-详细主题信息
            $field =  'id, title, start_time, end_time, photo, thumbs, description';
            $limits['curr_page'] = isset($params['page'])?$params['page']:1;
            $limits['page_size'] = isset($params['limit'])?$params['limit']:10;
        }else{
            $field = 'id, title, start_time, end_time';
        }
        $result = $this->ci->fruit_topic_model->getTopicList($field, '',$like,$limits);

        if(!empty($result)){
            if(empty($params['type'])){
                foreach($result as $key=>$val){
                    $topic_state = $this->_initTopicState($curr_time,$val['start_time'],$val['end_time']);
                    $res[$topic_state][] = array(
                            'id'=>$val['id'],
                            'title'=>$val['title'],
                            'topic_state'=>$topic_state,
                        );
                }
            }else{
                $tids = array();
                foreach($result as $key=>$val){
                    $topic_state = $this->_initTopicState($curr_time,$val['start_time'],$val['end_time']);
                    $res[$topic_state][$val['id']] = array(
                            'id'=>$val['id'],
                            'title'=>$val['title'],
                            'topic_state'=>$topic_state,
                            'photo'=>$val['photo'],
                            'thumbs'=>$val['thumbs'],
                            'description'=>$val['description'],
                            'num'=>0,
                        );
                    if($topic_state==1 || $topic_state==3){
                        $tids[] = $val['id'];
                    }
                }
                $art_topic_num = array();
                if(!empty($tids)){
                    //get num && sort array
                    $art_field = "tid,count(id) c";
                    $art_where_in = array('key'=>'tid','value'=>$tids);
                    $art_group = "tid";
                    $art_res = $this->ci->fruit_articles_model->getArticleList($art_field, '', $art_where_in, $art_group);
                    if(!empty($art_res)){
                        $art_topic_num = array_column($art_res, null, "tid");
                        foreach($art_topic_num as $key=>$val){
                            if(isset($res[1][$key])){
                                $res[1][$key]['num'] = $val['c'];
                            }
                            if(isset($res[3][$key])){
                                $res[3][$key]['num'] = $val['c'];
                            }
                        }
                    }
                }
            }
            // 排序
            if(!empty($res[1])){
                $res_num = array_column($res[1], 'num');
                array_multisort($res_num, SORT_DESC, SORT_NUMERIC,$res[1] );
            }
            $return = array_merge($res[1], $res[2],$res[3]);
        }
        return $return;
    }

    public function getDetailTopic($params){
        $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['id'];
        $topic_res = $this->_before_data($mem_key);
        if(empty($topic_res)){
            $this->ci->load->model("fruit_topic_model");
            $id = (int)$params['id'];
            $topic_where = array('id'=>$id);
            $topic_field = 'id, title, start_time, end_time, photo, thumbs, description, description_rich, type, ctime, summary,is_type';
            $topic_res = $this->ci->fruit_topic_model->getTopicInfo($topic_field, $topic_where, '');
            $this->_after_data($mem_key, $res);
        }
        $res = $this->_initTopic($topic_res);
        $return = array_values($res);
        if (empty($return[0])) {
            return array();
        } else {
            // set user behavior
            $sql = sprintf("INSERT INTO `%s`(`article_id`, `type`, `date`, `view`) VALUES ('%s', 'fruit_topic', '%s', 1) ON DUPLICATE KEY UPDATE `view` = `view` + 1", $this->ci->db->dbprefix('article_user_behavior'), $params['id'], date('Y-m-d', time()));
            $this->ci->db->query($sql);

            return $return[0];
        }
    }

    /**
     * [formatComment 格式化主题]
     * @param  [array] $result [description]
     * @return [array]      [description]
     */
    private function _initTopic($result){
        $this->ci->load->model("fruit_topic_model");
        $this->ci->load->model("fruit_articles_model");

        $curr_time = $this->stime;
        $res = array();
        $tids = array();

        foreach($result as $key=>$val){
            $topic_state = $this->_initTopicState($curr_time,$val['start_time'],$val['end_time']);
            $res[$val['id']] = array(
                'id'=>$val['id'],
                'title'=>$val['title'],
                'topic_state'=>$topic_state,
                'photo'=>empty($val['photo']) ? array("") : array($val['photo']),
                'thumbs'=>empty($val['thumbs']) ? array("") : array($val['thumbs']),
                'description'=>$val['description'],
                'description_rich' => $val['description_rich'],
                'real_description_rich' => $val['real_description_rich'],
                'summary'=>$val['summary'],
                'type'=>$val['type'],
                'ptype'=>2,
                'ctime'=>empty($val['start_time']) ? $val['ctime'] : $val['start_time'],
                'num'=>0,
                'topicType'=>$val['is_type'],
            );

            if($topic_state==1 || $topic_state==3){
                $tids[] = $val['id'];
            }
        }
        $art_topic_num = array();
        if(!empty($tids)){
            //get num && sort array
            $art_field = "tid,count(id) c";
            $art_where_in = array('key'=>'tid','value'=>$tids);
            $art_group = "tid";
            $art_res = $this->ci->fruit_articles_model->getArticleList($art_field, '', $art_where_in, $art_group);
            if(!empty($art_res)){
                $art_topic_num = array_column($art_res, null, "tid");
                foreach($art_topic_num as $key=>$val){
                    if(isset($res[$key])){
                        $res[$key]['num'] = $val['c'];
                    }
                }
            }
        }
        return $res;
    }

    //get topic state 1-话题周期内  2-话题周期未到 3-话题周期过期
    private function _initTopicState($curr_time,$start_time,$end_time){
        if(empty($start_time) && empty($end_time)){
            return 1;
        }
        if(!empty($start_time) && $curr_time < $start_time){
            return 2;
        }
        if(!empty($end_time) && $curr_time > $end_time){
            return 3;
        }
        return 1;
    }

    /**
     * [formatComment 根据时间合并主题和文章]
     * @param  [array] $art_data [description]
     * @param  [array] $topic_data [description]
     * @return [array]      [description]
     */
    private function _initMax($art_data, $topic_data){
        $data = array_merge($art_data, $topic_data);
        usort($data, function($a, $b) {
            if($a['ctime'] <= $b['ctime']){
                return 1;
            }
        });
        $new = array();
        $newtop = array();
        $ds =array();
        if(!empty($data)){
            foreach($data as $val){
                if($val['sort'] == 1)
                {
                    unset($val['utime']);
                    unset($val['sort']);
                    $newtop[] = array('ptype'=>$val['ptype'],'data'=>$val);
                }
                else{
                    unset($val['utime']);
                    unset($val['sort']);
                    $new[] = array('ptype'=>$val['ptype'],'data'=>$val);
                }
            }
        }
        $ds = array_merge($newtop, $new);
        return $ds;
    }

    /**
     * [formatComment 格式化评论]
     * @param  [array] $val [description]
     * @param  [array] $userinfo [description]
     * @return [array]      [description]
     */
    private function _initComment($res){
        $this->ci->load->model('user_model');

        if(!empty($res)){
            //获取查询条件
            $uids = array_column($res, 'uid');
            //批量获取用户信息
            $user_res = $this->ci->user_model->getUsers($uids);
            $user_result = array_column($user_res, null, 'id');

            //set res
            foreach($res as &$val){
                //set user
                $val['username'] = $user_result[$val['uid']]['username'];

                if($val['uid'] == -1) //匿名
                {
                    $val['username'] = '果园用户';
                    $val['userrank'] = 0;
                    $val['userface'] = PIC_URL.$this->sys_userface;
                }
                else{
                    $val['username'] = empty($val['uid']) ? $this->sys_username:encrypt_num($val['username']);
                    $val['userface'] = empty($val['uid']) ? PIC_URL.$this->sys_userface :$user_result[$val['uid']]['userface'];
                    $val['userrank'] = empty($val['uid']) ? 6 : $user_result[$val['uid']]['userrank'];
                }

                //set other
                $val['content'] = empty($val['content'])?'':urldecode($val['content']);
                $val['stime'] = $this->stime;
                if(!empty($val['pid'])){
                    $val['is_replay'] = 1;
                    $val['replay'] = unserialize($val['remark']);
                    $val['replay']['username'] = empty($val['replay']['uid'])?$this->sys_username:encrypt_num($val['replay']['username']);
                    $val['replay']['content'] = empty($val['replay']['content'])?"":urldecode($val['replay']['content']);

                    $val['replay_uid'] = $val['replay']['uid'];
                    $val['replay_username'] = encrypt_num($val['replay']['username']);
                    $val['replay_content'] = $val['replay']['content'];
                }else{
                    $val['is_replay'] = 0;
                    $val['replay'] = array();
                    $val['replay_uid'] = '';
                    $val['replay_username'] = '';
                    $val['replay_content'] = '';
                }

                unset($val['pid'],$val['remark']);
            }
        }
        return $res;
    }

    //格式显示的文章信息
    private function _initArticles($res,$uid=0){
        $this->ci->load->model('fruit_worths_model');
        $this->ci->load->model('fruit_comments_model');
        $this->ci->load->model('fruit_topic_model');
        $this->ci->load->model('user_model');

        if(!empty($res)){
            //获取查询条件
            $uids = array_column($res, 'uid');
            $tids = array_column($res, 'tid');
            $aids = array_column($res, 'id');
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
                    $val['userrank'] = 0;
                    $val['userface'] = PIC_URL.$this->sys_userface;
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
        }
        if(empty($photo_arr)) return array('code'=>'300','msg'=>'上传失败');
        $img_name_arr["images"] = $photo_arr;
        $img_name_arr["thumbs"] = $thumbs_arr;
        return $img_name_arr;
    }
}
