<?php
class Solr_bake_articles_model extends MY_Model {

	/*
	* 获取商品规格信息
	*/
	function selectBakeArticles($field,$where='',$where_in='',$order='',$group_by=''){
		$where['state'] = 1;
		$this->db->select($field);
		$this->db->from($this->table_name());
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			$this->db->where_in($where_in['key'],$where_in['value']);
		}
		if(!empty($order)){
			$this->db->order_by($order);
		}
		if(!empty($group_by)){
			$this->db->group_by($group_by);
		}
		$result = $this->db->get()->result_array();
		return $result;
	}

	/**
	 * [getArticleList 获取文章列表]
	 * @param  [string] $field    [字段:is_pic_tmp必选]
	 * @param  [array] $where    [description]
	 * @param  [array] $where_in [description]
	 * @param  [string] $group    [description]
	 * @return [array]           [description]
	 */
	public function getArticleList($field, $where, $where_in, $group='',$limits=''){
		$where['state'] = 1;//显示
		$this->db->where($where);

		if(!empty($field)){
			$this->db->select($field);
		}
		if(!empty($where_in)){
            foreach ($where_in as $key=>$val) {
                $this->db->where_in($val['key'], $val['value']);
            }
		}
		if(!empty($group)){
			$this->db->group_by($group);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
		}
		$this->db->order_by('utime desc, id desc');
		$this->db->from('bake_articles');
        $this->db->join('bake_article_section', 'bake_articles.id=bake_article_section.article_id');
		$query = $this->db->get();
		$result = $query->result_array();

		foreach($result as &$val){
			if(isset($val['photo'])){
	                		if(!empty($val['photo'])){
	                			$val['photo'] = PIC_URL.$val['photo'];
	                			$val['photo_arr'] = array($val['photo']);
				}else{
					$val['photo'] = '';
					$val['photo_arr'] = array();
				}
			}
			if(isset($val['thumbs'])){
				if(!empty($val['thumbs'])){
					$val['images_thumbs'] = PIC_URL.$val['thumbs'];
					$val['images_thumbs_arr'] = array($val['images_thumbs_arr']);
				}else{
					$val['images_thumbs'] = '';
					$val['images_thumbs_arr'] = array();
				}
				unset($val['thumbs']);
			}
			if(isset($val['content'])){
				$val['real_content'] = "";
				if(!empty($val['content'])){
					$val['content'] = str_replace('src="/', 'src="'.PIC_URL, $val['content']);
					$val['real_content'] = $val['content'];

					$val['content'] = str_replace('http://fruitday://Product?', 'fruitday://Product?', $val['content']);

                    //果食
                    $val['content'] = str_replace('http://fruitday://Guoshi_Baike?', 'fruitday://Guoshi_Baike?', $val['content']);
                    $val['content'] = str_replace('http://fruitday://Guoshi_Shequ?', 'fruitday://Guoshi_Shequ?', $val['content']);
                    $val['content'] = str_replace('http://fruitday://Guoshi_Activity?', 'fruitday://Guoshi_Activity?', $val['content']);
		            //$val['content'] = '<html><head><meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" name="viewport" /></head><body>'.$val['content'].'</body></html>';//让图片适配手机显示

                    //$patterns = array('/class=".*?"/','/width=".*?"/','/height=".*?"/','/style=".*?"/');
					//$replacements = array('','','','');
					//$val['content'] = preg_replace($patterns, $replacements, $val['content']);
                    $val['content'] = $this->_initContent($val['content']);
                }
            }
		}
		return $result;
	}

    public function getArticleSections($aid)
    {
        $result = $this->db->select('*')
                 ->from('bake_article_section')
                 ->join('bake_section', 'bake_section.id=bake_article_section.section_id')
                 ->where('article_id', $aid)
                 ->get()
                 ->result_array();
        return $result;
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
</body>
</html>
EOT;
		return $html;
	}
}