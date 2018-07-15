<?php

class Bake_section_model extends MY_Model
{
	public function table_name()
    {
		return 'bake_section';
	}

	public function getSectionList($where = array(), $all = 0)
    {
        // 显示
		$where['state'] = 1;
		$field = "id, name, photo";
		$orderby = "sort desc";
		$result = $this->getList($field, $where, 0, -1, $orderby);

		foreach ($result as &$val) {
            // 重置图片链接
            $val['photo'] = empty($val['photo']) ? "" : PIC_URL . $val['photo'];

            // 获取文章数目
            $val['num'] = $this->getSectionArticleCount($val['id']);

            $hasSon = $this->count(array('pid' => $val['id']));
            if ($all && $hasSon) {
                $where['pid'] = $val['id'];
                $val['son'] = $this->getSectionList($where, $all);
            } else {
                $val['son'] = array();
            }
        }

        return $result;
	}

    public function getChildIds($pid, $self = 0)
    {
        $where = "state = 1";
        if ($pid != 0) {
            if ($self) {
                $where .= " AND (path LIKE '%,{$pid},%' OR id = {$pid})";
            } else {
                $where .= " AND path LIKE '%,{$pid},%'";
            }
        }

        $result =  $this->db->select('id, name')
                            ->from($this->table_name())
                            ->where($where)
                            ->get()
                            ->result_array();
        return $result;
    }

    public function getSectionArticleCount($sectionId)
    {
        // 推荐
        if ($sectionId == 1) {
            return $this->getRecommendArticleCount();
        }

        $sectionIds = array_column($this->getChildIds($sectionId, 1), 'id');
        $count = $this->db->select('*')
                          ->from('bake_article_section')
                          ->join('bake_articles', 'bake_article_section.article_id = bake_articles.id', 'left')
                          ->where('bake_articles.state', '1')
                          ->where_in('bake_article_section.section_id', $sectionIds)
                          ->group_by('bake_articles.id')
                          ->get()
                          ->result_array();
        return count($count);
    }

    public function getRecommendArticleCount()
    {
        $count = $this->db->select("*")
                          ->from('bake_articles')
                          ->where(array('state' => 1, 'is_recommend' => 1))
                          ->count_all_results();
        return $count;
    }
}
