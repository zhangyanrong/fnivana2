<?php

namespace bll\weixin;

class Menu
{
    public function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->model('weixin_model');
    }

    public function delConditional($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
        return $this->ci->weixin->Menu->delConditional($aParams['menuid']);
    }

    public function addConditional($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
        return $this->ci->weixin->Menu->addConditional(json_decode($aParams['menu'], true));
    }

    /**
     * 获得自定义菜单配置。
     * @return array
     */
    public function getMenu($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
        $aConfig = $this->ci->weixin->Menu->get();

        if (isset($aConfig['menu'])) {
            $menuid = 0;

            if (isset($aParams['menuid']) && !empty($aParams['menuid'])) {
                $menuid = (int)$aParams['menuid'];
            }

            $menu = $aConfig['menu']['button'];
            $matchrule = [];
            $conditionalmenu = [];

            foreach ($aConfig['conditionalmenu'] as $aConditionalMenu) {
                $conditionalmenu[] = $aConditionalMenu['menuid'];

                if ($aConditionalMenu['menuid'] === $menuid) {
                    $menu = $aConditionalMenu['button'];
                    $matchrule = $aConditionalMenu['matchrule'];
                }
            }

            $this->ci->load->model('weixin_model');
            $menu_handled = $this->ci->weixin_model->menuConvert($menu);
            $aResult = [
                'code' => 200,
                'menu' => $menu_handled,
                'matchrule' => $matchrule,
                'menuid' => $menuid,
                'conditionalmenu' => $conditionalmenu
            ];
        } else {
            $aConfig['APP_ID'] = WX_APP_ID;
            $aConfig['SECRET'] = WX_SECRET;
            $aConfig['IP'] = $_SERVER['SERVER_ADDR'];

            $aResult = ['code' => $aConfig['errcode'], 'msg' => $aConfig['errmsg']];
        }

        return $aResult;
    }

    /**
     * 设置自定义菜单。
     * @param array $aMenuConfig
     * @return array
     */
    public function setMenu($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
        $this->ci->load->model('weixin_model');
        $aMenuConfig = json_decode($aParams['menu'], true);
        $menu = $this->ci->weixin_model->prePostMenu($aMenuConfig);

        // 重置数组的key，否则报40016
        $tmp = [];

        foreach ($menu['button'] as $value) {
            $tmp[] = $value;
        }

        $menu['button'] = $tmp;

        if (!isset($aMenuConfig['matchrule'])) {
            // 默认菜单
            $aReturn = $this->ci->weixin->Menu->create($menu);
        } else {
            // 个性化菜单
            $menu['matchrule'] = $aMenuConfig['matchrule'];
            $aReturn = $this->ci->weixin->Menu->addConditional($menu);

            if ((int)$aReturn['errcode'] > 0) {
                return $aResult = ['code' => $aReturn['errcode'], 'msg' => $aReturn['errcode'] . ':' . $aReturn['errmsg']];;
            }

            if (isset($menu['menuid'])) {
                $aReturn = $this->ci->weixin->Menu->delConditional($menu['menuid']);
            }
        }

        if ($aReturn['errcode'] == 0) {
            $aResult = ['code' => 200, 'msg' => '创建成功'];
        } else {
            $aResult = ['code' => $aReturn['errcode'], 'msg' => $aReturn['errcode'] . ':' . $aReturn['errmsg']];
        }

        return $aResult;
    }

}

# end of this file.
