<?php

namespace bll\weixin;

class Material
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('weixin_model');
        $this->ci->load->library("Weixin", ['sModuleName' => 'Material']);
    }

    public function searchNews($aParams)
    {
        $keyword = $aParams['keyword'];
        $offset = $aParams['offset'];
        $amount = $aParams['amount'] ? : 10;
        $aNewsList = $this->ci->weixin_model->searchNews($keyword, $offset, $amount);

        $aResult = [];

        foreach ($aNewsList as $value) {
            $tmp = json_decode($value['content'], true);

            foreach ($tmp as $k => $v) {
                $aImageInfo = $this->ci->weixin_model->getImageInfo($v['thumb_media_id']);
                $tmp[$k]['thumb_real_url'] = $aImageInfo['url'];
            }

            $value['content'] = $tmp;
            $value['update_time_show'] = date('Y-m-d H:i:s', $value['update_time']);
            $aResult[] = $value;
        }

        return ['code' => 200, 'data' => $aResult];
    }

    /**
     * 清空图文素材。
     */
    public function clearNews($aParams)
    {
        $result = $this->ci->weixin_model->clearNews();

        if ($result) {
            return ['code' => 200, 'msg' => 'Success!'];
        } else {
            return ['code' => 500, 'msg' => 'Clear Failed!'];
        }
    }

    /**
     * 同步图文消息素材。
     */
    public function syncNews($aParams)
    {
        $local_count = $this->ci->weixin_model->getNewsCount();
        $offset = isset($aParams['offset']) ? $aParams['offset'] : 0;

        $param = [
            'type' => 'news',
            'offset' => $offset,
            'count' => 20
        ];

        $data = $this->ci->weixin->Material->batchGet($param);

        foreach ($data['item'] as $value) {
            $news_item = $value['content']['news_item'];
            $keywords = '';

            foreach ($news_item as $kk => $vv) {
                unset($news_item[$kk]['content']);
                $keywords .= $vv['title'] . "|" . $vv['author'] . "|" . $vv['digest'] . "|";
            }

            $value['keywords'] = $keywords;
            $value['content'] = json_encode($news_item);
            $return = $this->ci->weixin_model->addNews($value);

            if (!$return['status']) {
                return ['code' => 301, 'msg' => $return['message']];
            }
        }

        return ['code' => 200, 'msg' => 'Success!'];
    }

    /**
     * 同步图片素材。
     * @param array $aParam 可能参数：offset, count
     * @return array
     */
    public function syncImage($aParams)
    {
        $local_count = $this->ci->weixin_model->getImageCount();

        $offset = isset($aParams['offset']) ? $aParams['offset'] : 0;

        if ($offset < 0) {
            $offset = 0;
        }

        $i = 0;

        while ($i < 10) {
            $param = [
                'type' => 'image',
                'offset' => $offset,
                'count' => 20
            ];

            $data = $this->ci->weixin->Material->batchGet($param);
            $offset += 20;
            $i++;

            foreach ($data['item'] as $value) {
                $return = $this->ci->weixin_model->addImage($value);

                if (!$return['status']) {
                    return ['code' => 301, 'msg' => $return['message']];                    
                }
            }
        }

        return ['code' => 200, 'msg' => 'Success!'];
    }

    public function getCount($aParams)
    {
        $type = $aParams['type'];
        $method = 'get' . ucfirst($type) . 'Count';
        return $this->$method();
    }

    private function getImageCount()
    {
        $local_count = $this->ci->weixin_model->getImageCount();
        $weixin_result = $this->ci->weixin->Material->batchCount();
        $remote_count = $weixin_result['image_count'];

        return ['local_count' => $local_count, 'remote_count' => $remote_count];
    }

    private function getNewsCount()
    {
        $local_count = $this->ci->weixin_model->getNewsCount();
        $weixin_result = $this->ci->weixin->Material->batchCount();
        $remote_count = $weixin_result['news_count'];

        return ['local_count' => $local_count, 'remote_count' => $remote_count];
    }
}

# end of this file.
