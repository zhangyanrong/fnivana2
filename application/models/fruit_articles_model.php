<?php
class Fruit_articles_model extends MY_Model {

	public function table_name(){
		return 'fruit_articles';
	}

	//根据文章id获取用户id
	public function getArticlesUid($id){
		$where = array('id'=>$id);
		$this->db->select('uid');
		$this->db->from('fruit_articles');
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		return $result['uid'];
	}

	/**
	 * [getDetailArticle 详情文章]
	 * @param  [int] $id [description]
	 * @return [array]     [description]
	 */
	public function getDetailArticle($id){
		$where = array('id'=>$id,'state'=>1);
		$this->db->select('id,ctime,description,photo,type,ptype,content,thumbs,tid,is_pic_tmp,uid');
		$this->db->from('fruit_articles');
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		return $result;
	}

	public function insArticle($data){
		if(empty($data)){
			return false;
		}
		$res = $this->db->insert('fruit_articles',$data);
		return $res;
	}

	/**
	 * [getArticleList 获取文章列表]
	 * @param  [string] $field    [字段:is_pic_tmp必选]
	 * @param  [array] $where    [description]
	 * @param  [array] $where_in [description]
	 * @param  [string] $group    [description]
	 * @return [array]           [description]
	 */
	public function getArticleList($field, $where, $where_in, $group='',$limits='',$is_show_all=0){
		if($is_show_all==0){
			$where['state'] = 1;//显示
		}
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($field)){
			$this->db->select($field);
		}
		if(!empty($where_in)){
			$this->db->where_in($where_in['key'], $where_in['value']);
		}
		if(!empty($group)){
			$this->db->group_by($group);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
		}
		//$this->db->order_by('sort','desc');
		$this->db->order_by('utime','desc');
		$this->db->from('fruit_articles');
		$query = $this->db->get();
		$result = $query->result_array();
		foreach($result as &$val){
			if(isset($val['photo'])){
	                		if(!empty($val['photo'])){
	                			$val['photo'] = array_values(array_unique(explode(",",$val['photo'])));
					foreach($val['photo'] as $pkey=>$pval){
						$val['photo'][$pkey] = ($val['is_pic_tmp']==1) ? PIC_URL_TMP.$pval:PIC_URL.$pval;
					}
				}else{
					$val['photo'] = array("");
				}
				$val['photo_arr'] = $val['photo'];
			}
			if(isset($val['thumbs'])){
				if(!empty($val['thumbs'])){
					$val['thumbs'] = array_values(array_unique(explode(",",$val['thumbs'])));
					foreach($val['thumbs'] as $pkey=>$pval){
						$val['images_thumbs'][$pkey] = ($val['is_pic_tmp']==1) ? PIC_URL_TMP.$pval:PIC_URL.$pval;
					}
				}else{
					$val['images_thumbs'] = array("");
				}
				$val['images_thumbs_arr'] = $val['images_thumbs'];
				unset($val['thumbs']);
			}
			if(isset($val['content'])){
				$val['real_content'] = "";
				if(!empty($val['content'])){
					if($val['is_pic_tmp']==1){
						$val['content'] = str_replace('src="/', 'src="'.PIC_URL_TMP, $val['content']);
					}else{
						$val['content'] = str_replace('src="/', 'src="'.PIC_URL, $val['content']);
					}

                    //果食
                    $val['content'] = str_replace('http://fruitday://Guoshi_Baike?', 'fruitday://Guoshi_Baike?', $val['content']);
                    $val['content'] = str_replace('http://fruitday://Guoshi_Shequ?', 'fruitday://Guoshi_Shequ?', $val['content']);
                    $val['content'] = str_replace('http://fruitday://Guoshi_Activity?', 'fruitday://Guoshi_Activity?', $val['content']);

					$val['real_content'] = $val['content'] ;
		                                	//$val['content'] = '<html><head><meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" name="viewport" /></head><body>'.$val['content'].'</body></html>';//让图片适配手机显示
		                                	$val['content'] = $this->_initContent($val['content']);
	                                	}
                                	}
		}
		return $result;
	}

	//获取用户发表的文章数
	public function getUserArticlesNum($uid,$where=array()){
		if(empty($uid)){
			return 0;
		}
		$where['uid'] = $uid;
		$this->db->select("count(id) c");
		$this->db->where($where);
		$this->db->from("fruit_articles");
		$result = $this->db->get()->row_array();
		return $result['c'];
	}

	public function delArticle($where){
		if(empty($where['id'])){
			return false;
		}
		$this->db->where($where);
		$res = $this->db->delete('fruit_articles');
		return $res; 
	}

	private function _initContent($content){
		$html = <<<EOT
<!DOCTYPE html>
<html lang="zh-cn">
  <head>
    <meta charset="utf-8">
    <title>天天果园</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="X-UA-Compatible" content="IE-9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes"> 
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta content='black' name='apple-mobile-web-app-status-bar-style' />

    <link rel="shortcut icon" href="favicon.ico" >  
    <link rel="apple-touch-icon" href="touch-icon-iphone.png">
    <link rel="apple-touch-icon" sizes="76x76" href="touch-icon-ipad.png">
    <link rel="apple-touch-icon" sizes="120x120" href="touch-icon-iphone-retina.png">
    <link rel="apple-touch-icon" sizes="152x152" href="touch-icon-ipad-retina.png">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="http://m.fruitday.com/css/bootstrap.css">
    <link rel="stylesheet" href="http://m.fruitday.com/css/app.css">   
    <!--[if lt IE 9]>
    <script src="http://cdn.bootcss.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
</head>
<body>
    <section class="m-app-baike" style="padding-bottom:0">
        <div class="page-content">
            <p class="p-baike-img">
            $content
             </p>
        </div>
    </section>
    <script src="http://m.fruitday.com/js/require.js" data-main="http://m.fruitday.com/js/main"></script>
</body>
</html>
EOT;
	return $html;
	}

	public function getLastArticleTime(){
        $this->db->select("max(ctime) as maxtime");
        $this->db->from("fruit_articles");
        $this->db->where(array('state'=>1));
        $result = $this->db->get()->row_array();
        return $result['maxtime'];
	}

    /**
     * [upArticleTime 发表评论－更新文章更新时间]
     * @param  [int] $aid
     */
    public function upArticleTime($aid)
    {
        $filter = array(
            'id' => $aid,
        );
        $set = array(
            'utime' => time()
        );
        $this->update($set,$filter);
    }
}