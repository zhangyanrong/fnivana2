<?php
namespace bll;
include_once("snscomm.php");

class bake extends Snscomm {
    //官方名称
    private $sys_username = "水果君";
    //官方图片
    private $sys_userface = "up_images/avatar/sys_userpic.jpg";

    //memcache(getSectionList,getDetailArticle,getSearchTagList)

    public function __construct($params = array()) {
        $this->ci = &get_instance();

        $this->ci->load->helper('public');
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        if ($session_id) {
            $this->ci->load->library('session', array('session_id' => $session_id));
        }
        $this->stime = time();
    }

    /**
     * 获取百科版块列表
     */
    public function getSectionList($params) {
        !isset($params['pid']) && $params['pid'] = 0;
        !isset($params['all']) && $params['all'] = 0;

        $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['pid'] . "_" . $params['all'];
        $sec_res = $this->_before_data($mem_key);
        $sec_res = null;
        if (empty($sec_res)) {
            // 获取版块数据
            $this->ci->load->model('bake_section_model');
            $where['pid'] = $params['pid'];

            $sec_res = $this->ci->bake_section_model->getSectionList($where, $params['all']);
            //小于4.2.0 做特殊处理
            if (version_compare($params['version'], '4.2.0') < 0) {
                $sec_res = array_merge(array($sec_res[0]), $sec_res);
            }

            $this->_after_data($mem_key, $sec_res);
        }

        return $sec_res;
    }

    /**
     * 获取百科文章列表
     */
    public function getArticleList($params) {
        $this->ci->load->model('bake_articles_model');
        $this->ci->load->model('bake_section_model');

        $res = $this->_ckuser();
        $uid = 0;
        if ($res['code'] == '200') {
            $uid = $res['msg'];
        }
        if (strcmp($params['version'], '3.0.0') >= 0) {
            $art_field = "id,title,summary,type,photo,thumbs,ctime,is_can_comment,base_pageview,pageview,base_thumb_up_quantity";
        } else {
            $art_field = "id,title,summary,content,type,photo,thumbs,ctime,is_can_comment,base_pageview,pageview,base_thumb_up_quantity";
        }
        $where = array();
        $where_in = array();
        if (!empty($params['sec_id'])) {
            $sec_row = $this->ci->bake_section_model->dump(array('id' => $params['sec_id']), 'name');
            if ($sec_row['name'] == '推荐') {
                $where['is_recommend'] = 1;
            } else {
                $sectionIds = array_column($this->ci->bake_section_model->getChildIds($params['sec_id'], 1), 'id');
                $where_in['section_id'] = array('key' => 'section_id', 'value' => $sectionIds);
            }
        }
        $top_data = array();
        $main_data = array();

        // get main art
        $main_page = isset($params['page']) ? $params['page'] : 1;
        $main_limit = isset($params['limit']) ? $params['limit'] : 10;
        $main_limits = array('curr_page' => $main_page, 'page_size' => $main_limit);
        $main_where = $where;
        if (empty($params['sec_id']) && !isset($params['keyword'])) {

        } else {
            $main_where['is_top'] = 0;
        }
        // 搜索
        if (isset($params['keyword'])) {
            $keyword = trim($params['keyword']);
            if (preg_match("/[\x7f-\xff]/", $keyword)) {
                $keyword = preg_replace('/\w/s', '', $keyword);
            }
            if (empty($keyword)) {
                return array(
                    'top' => array(),
                    'main' => array(),
                );
            } else {
                if (defined('OPEN_SOLR') && OPEN_SOLR === TRUE && defined('OPEN_SOLR_BAKE') && OPEN_SOLR_BAKE === TRUE) {
                    $filter = array();
                    $filter['status'] = 'true';
                    $where['is_recommend'] and $filter['is_recommend'] = 'true';
                    unset($where['is_recommend']);
                    unset($main_where['is_recommend']);
                    unset($main_where['is_top']);
                    $limit = $main_limit;
                    $offset = ($main_page - 1) * $main_limit;
                    $search = $this->_initSolr($keyword, $filter, $limit, $offset);
                    if (empty($search)) {
                        return array(
                            'top' => array(),
                            'main' => array(),
                        );
                    } else {
                        $where_in['solr_search'] = $search;
                    }
                } else {
                    $where['title like'] = '%' . $keyword . '%';
                    $where['summary like'] = '%' . $keyword . '%';
                }
            }
        } else {
            // top
            if (empty($params['sec_id']) && !isset($params['keyword'])) {
                $top_data = array();
            } else {
                if ($params['page'] <= 1 || !isset($params['page'])) {
                    $top_where = $where;
                    $top_where['is_top'] = 1;
                    $top_art_res = $this->ci->bake_articles_model->getArticleList($art_field, $top_where, $where_in, 'id');
                    $top_data = $this->_initArticles($top_art_res, $uid);
                } else {
                    $top_data = array();
                }
            }
        }


        $main_art_res = $this->ci->bake_articles_model->getArticleList($art_field, $main_where, $where_in, 'id', $main_limits);
        $main_data = $this->_initArticles($main_art_res, $uid);

        $result = array(
            'top' => $top_data,
            'main' => $main_data,
        );

        // banner
        $bake_banner_list = array();
        if ($params['page'] <= 1 || !isset($params['page'])) {
            $this->ci->load->model('banner_model');
            $banner_list = $this->ci->banner_model->get_banner_list($params);
            foreach ($banner_list as &$value) {
                switch ($value['position']) {
                    case '71':
                        $bake_banner_list[] = $value;
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
        $result['banner'] = $bake_banner_list;

        return $result;
    }

    //获取文章详情
    public function getDetailArticle($params) {
        $res = $this->_ckuser();
        $uid = 0;
        if ($res['code'] == '200') {
            $uid = $res['msg'];
        }

        $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'];
        $res = $this->_before_data($mem_key);
        $id = $params['id'];
        $this->ci->load->model('bake_articles_model');
        if (empty($res)) {
            $field = "id,title,summary,content,type,photo,thumbs,ctime,is_can_comment,base_pageview,pageview,base_thumb_up_quantity";
            $where = array('id' => $id);
            $res = $this->ci->bake_articles_model->getArticleList($field, $where);
            $this->_after_data($mem_key, $res);
        }

        $detail_res = array();
        if (!empty($res)) {
            $originPageview = (int)($res[0]['pageview']);
            $sql = sprintf("UPDATE `%s` SET `pageview` = `pageview` + 1 WHERE `id` = %d ", $this->ci->db->dbprefix('bake_articles'), $id);
            $this->ci->db->query($sql);
            $result = $this->_initArticles($res, $uid);
            $detail_res = $result[0];

            // set user behavior
            $sql = sprintf("INSERT INTO `%s`(`article_id`, `type`, `date`, `view`) VALUES ('%s', 'bake', '%s', 1) ON DUPLICATE KEY UPDATE `view` = `view` + 1", $this->ci->db->dbprefix('article_user_behavior'), $detail_res['id'], date('Y-m-d', time()));
            $this->ci->db->query($sql);
        }

        return $detail_res;
    }

    public function setWorth($params) {
        $this->ci->load->model('bake_worths_model');

        //匿名用户
        if ($params['uid'] == -1) {
            $worth_type = 0;
            $data['type'] = $worth_type;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $params['uid'];
            $res = $this->ci->bake_worths_model->insWorth($data);
        } else {
            $res = $this->_ckuser();

            if ($res['code'] != '200') {
                return $res;
            }
            $uid = $res['msg'];
            if (empty($params['aid'])) {
                return array('code' => '300', 'msg' => '请选择喜欢文章');
            }

            $currUserWorth = $this->ci->bake_worths_model->getCurrUserWorth($uid, $params['aid']);
            if (!empty($currUserWorth)) {
                $worth_type = $currUserWorth['type'] == 1 ? 0 : 1;
                $res = $this->ci->bake_worths_model->upWorth($params['aid'], $uid, $worth_type);
            } else {
                $worth_type = 0;
                $data['type'] = $worth_type;
                $data['aid'] = $params['aid'];
                $data['ctime'] = $this->stime;
                $data['uid'] = $uid;
                $res = $this->ci->bake_worths_model->insWorth($data);
            }
        }

        if ($res) {
            // set user behavior
            $sql = sprintf("INSERT INTO `%s`(`article_id`, `type`, `date`, `like`) VALUES ('%s', 'bake', '%s', 1) ON DUPLICATE KEY UPDATE `like` = `like` + 1", $this->ci->db->dbprefix('article_user_behavior'), $params['aid'], date('Y-m-d', time()));
            $this->ci->db->query($sql);

            return array('type' => $worth_type);
        } else {
            return array('code' => '300', 'msg' => '点赞失败');
        }
    }

    public function setCollect($params) {
        $this->ci->load->model('bake_collection_model');

        $res = $this->_ckuser();
        if ($res['code'] != '200') {
            return $res;
        }
        $uid = $res['msg'];
        if (empty($params['aid'])) {
            return array('code' => '300', 'msg' => '请选择收藏文章');
        }

        $currUserCollect = $this->ci->bake_collection_model->getCurrUserCollect($uid, $params['aid']);
        if (!empty($currUserCollect)) {
            $collect_type = $currUserCollect['type'] == 1 ? 0 : 1;
            $res = $this->ci->bake_collection_model->upCollect($params['aid'], $uid, $collect_type);
        } else {
            $collect_type = 0;
            $data['type'] = $collect_type;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $uid;
            $res = $this->ci->bake_collection_model->insCollect($data);
        }
        if ($res) {
            return array('type' => $collect_type);
        } else {
            return array('code' => '300', 'msg' => '提交失败');
        }
    }

    public function doComment($params) {
        $this->ci->load->model('bake_articles_model');
        $this->ci->load->model('bake_comments_model');
        $this->ci->load->model('fruit_notify_model');
        $this->ci->load->model('user_model');

        //匿名用户
        if ($params['uid'] == -1) {
            $data['state'] = 1;
            $data['aid'] = $params['aid'];
            $data['ctime'] = $this->stime;
            $data['uid'] = $params['uid'];
            $data['content'] = urlencode(strip_tags($params['content']));

            $id = $this->ci->bake_comments_model->insComment($data);
        } else {

            //权限检查
            $ck_res = $this->_ckinvate($params);
            if ($ck_res['code'] != '200') {
                return $ck_res;
            }
            $uid = $ck_res['msg'];
            if (empty($params['content'])) {
                return array('code' => 300, 'msg' => '请输入评论内容');
            }

            //回复评论
            if ($params['replay_id']) {
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

            $id = $this->ci->bake_comments_model->insComment($data);

            //新增消息通知
            if ($id) {
                if ($params['replay_id']) {
                    //获取通知用户信息
                    $notice_uid = $remark['uid'];
                    $notice_type = 4;
                    $notify_userinfo = $this->ci->user_model->getUser($data['uid']);
                    $notify_data = array('ctime' => $data['ctime'], 'type' => $notice_type, 'aid' => $data['aid'], 'uid' => $notice_uid, 'pid' => $id,
                        'notify_uid' => $data['uid'],
                        'notify_username' => $notify_userinfo['username'],
                        'notify_content' => $data['content'],
                    );
                    $this->ci->fruit_notify_model->addNotify($notify_data);
                }
            }
        }

        $result = $this->ci->bake_comments_model->getComment($id);

        if (!empty($result)) {
            $return = $this->_initComment(array($result));
            return $return[0];
        } else {
            return array();
        }
    }

    //删除文章相关信息
    public function delComment($params) {
        $this->ci->load->model('bake_comments_model');
        $this->ci->load->model('fruit_notify_model');

        $res = $this->_ckuser();
        if ($res['code'] != '200') {
            return $res;
        }
        $uid = $res['msg'];

        $where = array('id' => $params['id'], 'uid' => $uid);
        $res = $this->ci->bake_comments_model->delComment($where);
        if ($res) {
            $notify_where = array('pid' => $params['id'], 'type' => 4);
            $this->ci->fruit_notify_model->delNotify($notify_where);
            return array('code' => '200', 'msg' => '删除成功');
        } else {
            return array('code' => '300', 'msg' => '删除失败');
        }
    }

    //获取评论列表
    public function getArtCommentList($params) {
        $this->ci->load->model('bake_comments_model');

        $aid = (int)$params['aid'];
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $result = $this->ci->bake_comments_model->getCommentList($aid, 0, $limit, $page);
        $result = $this->_initComment($result);
        return $result;
    }

    //获取关键词
    public function getSearchTagList($params) {
        $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'];
        $mem_data = $this->_before_data($mem_key);
        if (!empty($mem_data)) {
            return $mem_data;
        }
        $this->ci->load->model('bake_tag_model');
        $res = $this->ci->bake_tag_model->getTagList();

        $this->_after_data($mem_key, $res);
        return $res;
    }

    private function _initSphinx($keyword) {
        $where_in = array();
        if (empty($keyword)) {
            return $where_in;
        }

        $this->ci->load->library('sphinxclient');
        $this->ci->sphinxclient->SetServer(SPHINX_IP, 9312);
        $this->ci->sphinxclient->SetConnectTimeout(1);
        $this->ci->sphinxclient->SetArrayResult(true);
        $this->ci->sphinxclient->SetMatchMode(SPH_MATCH_ALL);
        //$this->ci->sphinxClient->SetSortMode ( "SPH_SORT_ATTR_DESC", 'id');
        $res = $this->ci->sphinxclient->Query($keyword, "*");
        if (!isset($res['matches']) || empty($res['matches'])) {
            return $where_in;
        }
        $ids = array_column($res['matches'], 'id');
        if ($res['error'] || empty($ids)) {
            return $where_in;
        }
        $where_in = array('key' => 'id', 'value' => $ids);
        return $where_in;
    }

    private function _initSolr($keyword, $filter = array(), $limit = 10, $offset = 0) {
        $where_in = array();
        if (empty($keyword)) {
            return $where_in;
        }
        $params = array();
        $params['path'] = 'solr/solr_bake';
        $params['disMax'] = true;
        $this->ci->load->library('solr', $params);
        $this->ci->solr->setTerms(true);
        $this->ci->solr->addField('article_id');
        $this->ci->solr->setQuery('*', $keyword);
        $this->ci->solr->addQueryField('title', 1000);
        $this->ci->solr->addQueryField('summary', 100);
        $this->ci->solr->addQueryField('content', 1);
        if ($filter) {
            foreach ($filter as $key => $value) {
                $this->ci->solr->addFilterQuery($key, $value);
            }
        }
        $this->ci->solr->limit($limit, $offset);
        $response = $this->ci->solr->query();
        if (empty($response) || empty($response['docs'])) return $where_in;
        $ids = array();
        foreach ($response['docs'] as $key => $value) {
            $ids[] = $value->article_id;
        }
        $where_in = array('key' => 'id', 'value' => $ids);
        return $where_in;
    }

    /**
     * [formatComment 格式化评论]
     *
     * @param  [array] $val [description]
     * @param  [array] $userinfo [description]
     *
     * @return [array]      [description]
     */
    private function _initComment($res) {
        $this->ci->load->model('user_model');

        if (!empty($res)) {
            //获取查询条件
            $uids = array_column($res, 'uid');
            //批量获取用户信息
            $user_res = $this->ci->user_model->getUsers($uids);
            $user_result = array_column($user_res, null, 'id');

            //set res
            foreach ($res as &$val) {
                //set user
                $val['username'] = $user_result[$val['uid']]['username'];

                if ($val['uid'] == -1) //匿名
                {
                    $val['username'] = '果园用户';
                    $val['userface'] = PIC_URL . $this->sys_userface;
                    $val['userrank'] = 0;
                } else {
                    $val['username'] = empty($val['uid']) ? $this->sys_username : encrypt_num($val['username']);
                    $val['userface'] = empty($val['uid']) ? PIC_URL . $this->sys_userface : $user_result[$val['uid']]['userface'];
                    $val['userrank'] = empty($val['uid']) ? 6 : $user_result[$val['uid']]['userrank'];
                }

                //set other
                $val['content'] = empty($val['content']) ? '' : urldecode($val['content']);
                $val['stime'] = $this->stime;
                if (!empty($val['pid'])) {
                    $val['is_replay'] = 1;
                    $val['replay'] = unserialize($val['remark']);
                    $val['replay']['username'] = empty($val['replay']['uid']) ? $this->sys_username : encrypt_num($val['replay']['username']);
                    $val['replay']['content'] = empty($val['replay']['content']) ? "" : urldecode($val['replay']['content']);
                } else {
                    $val['is_replay'] = 0;
                    //$val['replay'] = '';
                }

                unset($val['pid'], $val['remark']);
            }
        }
        return $res;
    }

    //格式显示的文章信息
    private function _initArticles($res, $uid = 0) {
        $this->ci->load->model('bake_worths_model');
        $this->ci->load->model('bake_comments_model');
        $this->ci->load->model('bake_collection_model');

        if (!empty($res)) {
            $aids = array_column($res, 'id');
            $comment_nums = $this->ci->bake_comments_model->getCommentsNum($aids);
            $worth_nums = $this->ci->bake_worths_model->getWorthsNum($aids);
            $collection_nums = $this->ci->bake_collection_model->getCollectionNum($aids);
            $user_worths = $this->ci->bake_worths_model->getUserWorth($uid);
            $user_collection = $this->ci->bake_collection_model->getUserCollection($uid);

            //set res
            foreach ($res as &$val) {
                //set other
                $val['share_url'] = "http://m.fruitday.com/sns/bake/" . $val['id'];
                $val['stime'] = $this->stime;
                $val['comment_num'] = !isset($comment_nums[$val['id']]) ? 0 : $comment_nums[$val['id']];
                $val['worth_num'] = !isset($worth_nums[$val['id']]) ? 0 : $worth_nums[$val['id']] + $val['base_thumb_up_quantity'];
                $val['collection_num'] = !isset($collection_nums[$val['id']]) ? 0 : $collection_nums[$val['id']];
                $val['is_worth'] = isset($user_worths[$val['id']]) ? 1 : 0;
                $val['is_collect'] = isset($user_collection[$val['id']]) ? 1 : 0;
                //is can comment
                $val['is_can_comment'] = ($val['is_can_comment'] == 0) ? 1 : 0;
                $val['pageview'] = (int)$val['pageview'] + (int)$val['base_pageview'];
            }
        }
        return $res;
    }
}
