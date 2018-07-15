<?php

namespace bll;

/**
 * 文章相关接口
 */
class Article
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('article_model');
        $this->ci->load->model('msg_promotion_model');
        $this->ci->load->helper('public');
    }

    public function getList($params)
    {
        // 检查参数
        $required = array(
			'class_id' => array('required' => array('code' => '500', 'msg' => 'class id can not be null')),
		);
        $checkResult = check_required($params, $required);
		if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
		}

        $pageSize = $params['page_size'] ? $params['page_size'] : 10;
		$currPage = $params['curr_page'] ? $params['curr_page'] : 0;

        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['class_id']  . "_" . $pageSize . "_" . $currPage;
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$result = $this->ci->memcached->get($memKey);
			if ($result) {
				return $result;
			}
		}

        $fields = 'id, title, subtitle content, discription, online_time time, is_new';
        $filter = [
            'class_id' => $params['class_id'],
            'is_show' => 1,
            'online_time <=' => date('Y-m-d H:i:s'),
            '(offline_time = 0 OR offline_time > "' . date('Y-m-d H:i:s', strtotime('-3 days')) . '")' => null,
        ];
        $offset = ($currPage - 1) * $pageSize;
        $result['articles'] = $this->ci->article_model->getList($fields, $filter, $offset, $pageSize, 'online_time DESC, order_id DESC');
        $result['count'] = $this->ci->article_model->count($filter);

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$this->ci->memcached->set($memKey, $result, 600);
		}
		return $result;
    }

    public function getInfo($params)
    {
        // 检查参数
        $required = array(
			'id' => array('required' => array('code' => '500', 'msg' => 'article id can not be null')),
		);
        $checkResult = check_required($params, $required);
		if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
		}

        $fields = 'id, title, discription, online_time, is_new';

        //是否显示
        $is_show = 1;
        if(isset($params['is_show']))
        {
            $is_show = $params['is_show'];
        }

        $filter = array(
            'is_show' => $is_show,
            'online_time <=' => date('Y-m-d H:i:s'),
            'id' => (int) $params['id']
        );
        $article = $this->ci->article_model->dump($filter, $fields);

        return $article?: array('code' => '300', 'msg' => '文章不存在');
    }

    /**
     * 消息中心 - 优惠促销
     */
    public function getPromotionMsg($params)
    {
        $pageSize = $params['page_size'] ? $params['page_size'] : 10;
		$currPage = $params['curr_page'] ? $params['curr_page'] : 0;

        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $pageSize . "_" . $currPage;
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$result = $this->ci->memcached->get($memKey);
			if ($result) {
				return $result;
			}
		}

        $fields = 'id, title, image, subtitle as `desc`, online_time, offline_time, target_type as `type`, target_id, target_price_id, target_url as page_url';
        $filter = [
//            '(offline_time = 0 OR offline_time > "' . date('Y-m-d H:i:s', strtotime('-3 days')) . '")' => null,
        ];
        $offset = ($currPage - 1) * $pageSize;
        $result['articles'] = $this->ci->msg_promotion_model->getList($fields, $filter, $offset, $pageSize, 'online_time DESC, id DESC');
        foreach ($result['articles'] as &$article) {
            $article['photo'] = PIC_URL . $article['image'];
            $article['is_over'] = date("Y-m-d H:i:s") >= $article['offline_time'] ? 1 : 0;
            unset($article['offline_time'], $article['image']);
        }
        $result['count'] = $this->ci->msg_promotion_model->count($filter);

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$this->ci->memcached->set($memKey, $result, 600);
		}
		return $result;
    }
}
