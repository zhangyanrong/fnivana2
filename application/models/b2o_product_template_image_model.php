<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 17/1/5
 * Time: 下午3:48
 */

class B2o_product_template_image_model extends MY_Model {
    public function table_name() {
        return 'b2o_product_template_image';
    }

    /**
     * 获取产品模板图片
     *
     * @param int $templateId
     * @param string $imageType
     * @return array
     */
    public function getTemplateImage($templateId, $imageType = '')
    {
        $result = [];
        $filter = [
            'template_id' => $templateId,
        ];
        if ($imageType && in_array($imageType, ['main', 'whitebg', 'detail'])) {
            $filter['image_type'] = $imageType;
        }
        $images = $this->getList('*', $filter, 0, -1, 'sort');

        if ($images) {
            foreach ($images as $image) {
                switch ($image['image_type']) {
                    case 'main':
                        $result['main'] = $image;
                        break;
                    case 'whitebg':
                        $result['whitebg'] = $image;
                        break;
                    case 'detail':
                        $result['detail'][] = $image;
                        break;
                }
            }

            return $result;
        } else {
            return [];
        }
    }
}
