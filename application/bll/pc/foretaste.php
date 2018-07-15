<?php

namespace bll\pc;

include_once("pc.php");

class Foretaste extends Pc
{
    public function __construct() {
        parent::__construct();
        $this->ci->load->helper('public');
        $this->ci->load->model('foretaste_model');
        $this->ci->load->model('product_model');
    }

    /**
	 * 试吃报告
	 */
    public function reportInfo($params)
    {
        // 检查参数
        $required = array(
			'id' => array('required' => array('code' => '500', 'msg' => 'foretaste id can not be null')),
		);
        $checkResult = check_required($params, $required);
		if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
		}

		$id = $params['id'];
        $data = array();
        $rows = $this->ci->foretaste_model->selectForetaste('name, product_id, product_no, quantity, pro_url', array('id' => $id));

        if ($rows) {
            // 获取商品
            $productInfo = $this->ci->product_model->selectProducts('photo, template_id', array('id' => $rows[0]['product_id']))[0];
            // 获取价格
            $productPrice = $this->ci->product_model->selectProductPrice('price', array('product_id' => $rows[0]['product_id'], 'product_no' => $rows[0]['product_no']))[0];
            // 获取用户的申请、评论
            $applyCount = $this->ci->foretaste_model->getForetasteApplyCount(array(
                'foretaste_goods_id' => $id,
                'status' => 1,
            ));
            $commentCount = $this->ci->foretaste_model->getForetasteCommentCount(array('foretaste_goods_id' => $id));
            $commentRate = $this->ci->foretaste_model->getStarAvg($id);

            // 获取产品模板图片
            if ($productInfo['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($productInfo['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $productInfo['photo'] = $templateImages['main']['image'];
                }
            }

            $productInfo['photo'] = PIC_URL . $productInfo['photo'];
            $data = array_merge($rows[0], $productInfo, $productPrice, $commentRate);
            $data['apply_count'] = $applyCount;
            $data['comment_count'] = $commentCount;
        }

        return $data;
    }

    /**
     * 获取商品的试吃情况
     */
    public function getInfoByPro($params)
    {
        // 检查参数
        $required = array(
			'product_id' => array('required' => array('code' => '500', 'msg' => 'product id can not be null')),
		);
        $checkResult = check_required($params, $required);
		if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
		}

        $data = array('foretaste_goods_id' => false, 'count' => 0);
        $foretaste = $this->ci->foretaste_model->selectForetaste('id', array('product_id' => $params['product_id'], 'enabled' => 1));
        if ($foretaste) {
            $data['foretaste_goods_id'] = $foretaste[0]['id'];
            $data['count'] = $this->ci->foretaste_model->getForetasteCommentCount(array('product_id' => $params['product_id'], 'is_show' => 1));
        }

        return $data;
    }
}
