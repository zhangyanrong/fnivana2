<?php

namespace bll;

class Ad
{
    public function __construct()
    {
        $this->ci = & get_instance();

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $this->ci->load->model('ad/ad_model');
        $this->ci->load->model('ad/ad_tab_model');
        $this->ci->load->model('ad/ad_group_model');

        $this->ci->load->model('banner_icon_model');
        $this->ci->load->model('setting_model');
        $this->ci->load->model('b2o_store_model');

        $this->ci->load->model('product_model');
        $this->ci->load->model('b2o_product_template_image_model');

        // 过滤商品库存，测试阶段先关闭
        $this->filterInStock = true;
        // 是否启用缓存
        $this->useMemcache = true;
        // 推荐系统数据更新间隔，12h
        $this->userRecdSkusInterval = 43200;
        // 是否显示BI推荐数据
        $this->showBiData = true;

        $this->log = [];
    }

    /**
     * @api {post} / Mobile 首页广告
     * @apiDescription 获取移动端首页广告Tab、分组和图片信息
     * @apiGroup ad
     * @apiName mobileHomepage
     *
     * @apiParam {Number} type 请求的情况类型
     *
     * @apiParam {String} [device_id] 设备号
     * @apiParam {String} [connect_id] 登录标识
     * @apiParam {String} [lonlat] 经纬度，type=0|2 时必须
     * @apiParam {String} [district_code] 区域代码，type=0|2 时必须
     * @apiParam {String} [store_id_list] 门店ID，多个用英文逗号分隔，type=3 时必须
     * @apiParam {String} [tms_region_type] 配送类型
     * @apiParam {Number} [tab_id] TabID
     *
     * @apiParam {String} [channel=portal] 渠道
     *
     * @apiSampleRequest /api/test?service=ad.mobileHomepage&source=app
     */
    public function mobileHomepage($params)
    {
        // 仅用于 app
        if (!in_array($params['source'], ['app', 'wap'])) {
            return ['code' => '400', 'msg' => '获取数据失败'];
        }

        // 检查参数
        $required = [
            // 请求的情况类型 [ 0 定位获取成功 | 1 定位获取失败 | 2 切换地址 | 3 切换门店 ]
            'type' => ['required' => ['code' => '500', 'msg' => 'type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
		}
//        $this->log['params'] = $params;

        // 返回的数据格式
        $data = [
            'type' => $params['type'],

            'storeId' => '',
            'deliverId' => '',
            'deliveryType' => [],

            'banner' => [],
            'currentTabId' => '',
            'tab' => [],

            'currentAddress' => [],
            'otherAddress' => [],

            'needRefresh' => '1',

            'tmsRegionType' => '',
            'tmsRegionTime' => '',
            'tms_region_type' => '',
            'tms_region_time' => '',
            'isDayNight' => '',
        ];

        // 获取相应的数据
        $userInfo = $this->getUserInfo($params['device_id'], $params['connect_id']);
        $defaultChannle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $defaultChannle)) {
            $params['channel'] = 'portal';
        }
        $channel = empty($params['channel']) ? ['portal'] : ['portal', $params['channel']];
        $dateTime = date("Y-m-d H:i:s", $params['timestamp']);

        // 请求的情况类型
        switch ($data['type']) {
            /**
             * 定位获取成功
             *  存在用户信息，根据最近用户地址的 GPS 计算 storeId
             *  或者 根据入参 GPS 计算 storeId
             */
            case '0':
                $required = [
                    'lonlat' => ['required' => ['code' => '500', 'msg' => 'lonlat can not be null']],
//                    'district_code' => ['required' => ['code' => '500', 'msg' => 'district_code can not be null']],
                ];
                $checkResult = check_required($params, $required);
                if ($checkResult) {
                    return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
                }

                $districtCode = $params['district_code'];
                $lonlat = $params['lonlat'];
                if (empty($districtCode)) {
                    $data['banner'] = new \stdClass();
                    return ['code' => '200', 'msg' => '当前区域不支持', 'data' => $data];
                }

                // 存在 connect_id 才返回 currentAddress
                if ($params['connect_id']) {
                    // 根据 GPS 匹配最近的用户地址的 GPS
                    $userAddress = $this->getUserGpsAddress($userInfo['uid'], $lonlat, $data);
                    if ($userAddress && !empty($userAddress['data']['defaultAddress']['lonlat'])) {
                        $lonlat = $userAddress['data']['defaultAddress']['lonlat'];
                        $districtCode = $userAddress['data']['defaultAddress']['area_adcode'];
                    }
                }

                // 根据 GPS 获取 storeId、deliverId
                $result = $this->getTmsStore($lonlat, $districtCode, $data, $params);
                if ($result) {
                    return $result;
                }
                break;

            /**
             * 定位获取失败
             *  存在用户信息，返回用户地址列表
             */
            case '1':
                if ($params['connect_id']) {
                    // 根据 GPS 匹配最近的用户地址的 GPS
                    $userAddress = $this->getUserGpsAddress($userInfo['uid'], '', $data);
                    if ($userAddress) {
                        return ['code' => '200', 'msg' => 'succ', 'data' => $data];
                    }
                    // 新用户
                    else {
                        return ['code' => '300', 'msg' => '数据获取失败', 'data' => $userInfo['uid']];
                    }
                } else {
                    return ['code' => '300', 'msg' => '数据获取失败'];
                }
                break;

            /**
             * 切换地址
             *  根据入参 GPS 计算 storeId
             */
            case '2':
                $required = [
                    'lonlat' => ['required' => ['code' => '500', 'msg' => 'lonlat can not be null']],
//                    'district_code' => ['required' => ['code' => '500', 'msg' => 'district_code can not be null']],
                ];
                $checkResult = check_required($params, $required);
                if ($checkResult) {
                    return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
                }
                if (empty($params['district_code'])) {
                    $data['banner'] = new \stdClass();
                    return ['code' => '200', 'msg' => '当前区域不支持', 'data' => $data];
                }

                // 根据 GPS 获取 storeId、deliverId
                $result = $this->getTmsStore($params['lonlat'], $params['district_code'], $data, $params);
                if ($result) {
                    return $result;
                }
                break;

            /**
             * 切换门店
             *  根据当前 storeId、tabId 来返回数据
             */
            case '3':
                $required = [
                    'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
                    'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
                ];
                $checkResult = check_required($params, $required);
                if ($checkResult) {
                    return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
                }

                $data['storeId'] = $params['store_id_list'];
                $data['tmsRegionType'] = $params['tms_region_type'];
                $data['tms_region_type'] = $params['tms_region_type'];
                break;
        }
//        $this->log['data'] = $data;
//        $this->addLog($this->log);

        // 过滤大门店
        $adStoreId = $this->filterStore($data['storeId']);

        // 根据 adStoreId，获取广告Tab
        $data['tab'] = $this->ci->ad_tab_model->getTabByStore($adStoreId, "at.id, at.title, at.image");
        if (!$data['tab']) {
            return ['code' => '300', 'msg' => '当前地址没有首页广告Tab数据', 'data' => $adStoreId];
        } else {
            foreach ($data['tab'] as &$item) {
                $item['image'] = $item['image'] ? cdnImageUrl($item['id']) . $item['image'] : '';
            }
        }

        // 根据广告TabID，获取广告分组
        $data['currentTabId'] = $tabId = $params['tab_id'] ?: $data['tab'][0]['id'];
        $groups = $this->ci->ad_group_model->getList('id, type_id, tag_id, has_space, bg_color, show_limit', [
            'is_show' => 1,
            'tab_id' => $tabId,
        ], 0, -1, 'sort DESC');
        if (!$groups) {
            return ['code' => '300', 'msg' => '当前地址没有首页广告分组数据', 'data' => $data['tab']];
        }

        // 组建 memcache key 值
        $storeList = explode(',', $adStoreId);
        sort($storeList);
        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . implode('_', $channel) . "_" . implode('_', $userInfo) . "_" . implode('_', $storeList) . "_" . $data['tmsRegionType'] . "_" . $tabId . "_" . $data['type'] . "_" . $data['deliverId'];
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service'] && $this->useMemcache) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$cacheData = $this->ci->memcached->get($memKey);
			if ($cacheData) {
				return ['code' => '200', 'msg' => 'succ', 'data' => $cacheData];
			}
		}

        // 广告公用过滤条件
        $where = [
            'a.channel' => $channel,
            'a.user_type LIKE "%' . $userInfo['type'] . '%"' => null,
            'a.is_show' => '1',
            '((a.start_time = 0 AND a.end_time = 0) OR (a.start_time <= "' . $dateTime . '" AND a.end_time >= "' . $dateTime . '"))' => null,
            'a.apply_status >=' => 0,
            'a.send_region_type >=' => $data['tmsRegionType'],
        ];
        switch ($params['source']) {
            case 'app':
				$where['a.app'] = 1;
				break;
			case 'wap':
				$where['a.wap'] = 1;
				break;
            case 'pc':
                $where['a.pc'] = 1;
                break;
			default:
				break;
        }

        // icon背景图
        $iconGroupKey = 0;
        $additional = [
            'a.position' => 4,
        ];
        $iconBackground = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', array_merge($where, $additional));

        // 根据广告分组，获取广告
        foreach ($groups as $group) {
            switch ($group['type_id']) {
                case 1: // 大图轮播
                    $type = 'rotationBanner'; break;
                case 2: // 天天快报
                    $type = 'newsBanner'; break;
                case 3: // icon列表
                    $type = 'iconBanner'; break;
                case 4: // 文字标题
                    $type = 'titleBanner'; break;
                case 5: // 普通大图广告位
                    $type = 'bigImageBanner'; break;
                case 6: // 图片标题
                    $type = 'imageTitleBanner'; break;
                case 7: // 普通大图广告位（矮）
                    $type = 'bigImageBannerLow'; break;
                case 8: // 小图轮播
                    $type = 'rotationSmallBanner'; break;
                case 9: // 铺货（3个/行）
                    $type = '3perLineBanner'; break;
                case 10: // 铺货（2个/行）
                    $type = '2perLineBanner'; break;
                case 11: // 复合广告（1+2）
                    $type = '1plus2Banner'; break;
                case 12: // 商品轮播
                    $type = 'rotationProBanner'; break;
                case 13: // 横排多个商品-大图滑动
                    $type = 'proBannerBigImage_v51'; break;
                case 14: // 横排2个广告位
                    $type = '2nBanner_v51'; break;
                case 15: // 单品广告位
                    $type = 'normalBanner_v51'; break;
                case 16: // 横排3个广告位（可滑动）
                    $type = 'rotationSmallBanner_v51'; break;
                case 17: // 大促banner
                    $type = 'promotionBanner'; break;
                case 18: // 品牌广告
                    $type = 'brandBanner'; break;
                case 19: // 猜你喜欢 - 单品广告位展示类型
                    $type = '2perLineBanner'; break;
                case 20: // 标签在图下
                    $type = 'proBannerTagUnderImage'; break;
                default:
                    $type = '';
            }

            $content = [];
            // 广告来源BI
            if (in_array($group['type_id'], [19])) {
                if ($userInfo['uid']) {
                    $recdPros = $this->getUserRecdSkus($userInfo['uid'], $adStoreId, $data['tmsRegionType'], $params['source']);
                    $pros = array_slice($recdPros, 0, $group['show_limit']);
                    $content = $this->getProductBanner($pros, $params);
                } else { continue; }
            }
            // 广告来源数据库
            else {
                // 附加条件
                $additional = [
                    'a.position' => 1,
                    'a.group_id' => $group['id'],
                ];

                $banners = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', array_merge($where, $additional));
                if (0 == count($banners)) { continue; }

                // 格式化广告图
                foreach ($banners as $banner) {
                    $formatRst = $this->formatBanner([
                        'banner_id' => $banner['id'],
                        'store_id' => $data['storeId'],
                        'source' => $params['source'],
                        'platform' => $params['platform'],
                        'region_type' => $data['tmsRegionType'],
                    ]);

                    $tempString = '';
                    // 特殊处理，首页一行两个铺货，主副标题互换
                    if ($formatRst && '2perLineBanner' == $type) {
                        $tempString = $formatRst['title'];
                        $formatRst['title'] = $formatRst['subtitle'];
                        $formatRst['subtitle'] = $tempString;
                    }

                    if ($formatRst) {
                        $content[] = $formatRst;
                    }
                }
                $content = $this->adGroupLimit($content, $group['show_limit']);
            }

            // 精准化推荐广告位
            if ($group['tag_id'] && $params['connect_id'] && $userInfo['uid'] && !in_array($group['type_id'], [19])) {
                $beforeProIds = array_column($content, 'target_id');
                $recdPros = $this->getUserRecdSkus($userInfo['uid'], $adStoreId, $data['tmsRegionType'], $params['source']);
                $inStock = 1;
                foreach ($recdPros as $pro) {
                    if (!in_array($pro['product_id'], $beforeProIds) && strstr(',' . $group['tag_id'] . ',', $pro['scene_tag'])) {
                        $biProBanner = $this->getProductBanner([$pro], $params)[0];

                        // 特殊处理，首页一行两个铺货，主副标题互换
                        if ('2perLineBanner' == $type) {
                            $biProBanner['title'] = $pro['product_desc'];
                            $biProBanner['subtitle'] = $pro['product_name'];
                        }

                        if (count($content) == 0) {
                            $content[] = $biProBanner;
                        }
                        // 最后一个是查看更多
                        elseif ($content[count($content)-1]['is_more']) {
                            $content[count($content)-2] = $biProBanner;
                        }
                        else {
                            $content[count($content)-1] = $biProBanner;
                        }
                        break;
                    }
                }
            }

            if (count($content) > 0) {
                // 必须是4个
                if ($type == 'iconBanner') {
                    if (count($content) < 4) {
                        continue;
                    } else {
                        $content = array_slice($content, 0, 4);
                    }
                }
                // 必须是1个
                if (in_array($type, ['titleBanner', 'imageTitleBanner', 'bigImageBanner', 'bigImageBannerLow', 'promotionBanner', 'brandBanner'])) {
                    $content = array_slice($content, 0, 1);
                }
                // 必须是3个
                if ($type == '1plus2Banner') {
                    if (count($content) < 3) {
                        continue;
                    } else {
                        $content = array_slice($content, 0, 3);
                    }
                }
                // 必须是3的倍数
                if ($type == '3perLineBanner') {
                    if (count($content) < 3) {
                        continue;
                    } else {
                        $content = array_slice($content, 0, floor(count($content) / 3) * 3);
                    }
                }
                // 必须是2的倍数
                if (in_array($type, ['2perLineBanner', '2nBanner_v51'])) {
                    if (count($content) < 2) {
                        continue;
                    } else {
                        $content = array_slice($content, 0, floor(count($content) / 2) * 2);
                    }
                }
                // 必须大于3个
                if (in_array($type, ['rotationSmallBanner_v51', 'rotationSmallBanner']) && count($content) < 3) {
                    continue;
                }

                $groupInfo = [
                    'group_type' => $type,
                    'group_id' => $group['id'],
                    'has_space' => $group['has_space'],
                    'bg_color' => $group['bg_color'],
                    'content' => $content,
                ];

                // icon 背景图
                if ($type == 'iconBanner' && isset($iconBackground[$iconGroupKey])) {
                    $groupInfo['background'] = cdnImageUrl($group['id']) . $iconBackground[$iconGroupKey]['image'];
                    $iconGroupKey++;
                }

                // BI 推荐数据自动加头部标题分组
                if ($group['type_id'] == 19) {
                    $titleGroup = [
                        'group_type' => "titleBanner",
                        'group_id' => '',
                        'has_space' => "1",
                        'bg_color' => "",
                        'content' => [
                            [
                                'banner_ad_id' => '',
                                'title' => '猜你喜欢',
                                'subtitle' => '',
                                'image' => '',

                                // target
                                'target_type' => '999',
                                'target_id' => '',
                                'target_url' => '',
                                'store_id' => '',

                                // other
                                'is_more' => '0', // 是否查看更多
                                'need_navigation_bar' => '0', // 目标页面是否需要导航条

                                // product
                                'banner_tag' => '',
                                'promotion_tag' => '',
                                'volume' => '',
                                'guide_price' => '',
                                'price' => '',
                                'in_stock' => '0',

                                // share
                                'share' => [
                                    'is_share' => '',
                                    'share_icon' => '',
                                    'share_text' => '',
                                    'share_url' => '',
                                    'share_type' => '',
                                ],
                            ],
                        ],
                    ];
                    $data['banner']['mainBanners'][] = $titleGroup;
                }

                $data['banner']['mainBanners'][] = $groupInfo;
            }
        }

        // 浮动广告位，最新的一个
        $additional = [
            'a.position' => 2,
        ];
        $floatBanner = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', array_merge($where, $additional), 0, 1);
        if ($floatBanner) {
            $temp = $this->formatBanner([
                'banner_id' => $floatBanner[0]['id'],
                'store_id' => $data['storeId'],
                'source' => $params['source'],
                'platform' => $params['platform'],
                'region_type' => $data['tmsRegionType'],
            ]);
            if ($temp) {
                $data['floatBanner'] = $temp;
            }
        }

        // h5动画，最新的一个
        $additional = [
            'a.position' => 3,
        ];
        $h5FlashBanner = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', array_merge($where, $additional), 0, 1);
        if ($h5FlashBanner && $h5FlashBanner[0]['target_url']) {
            $data['h5Flash'] = $h5FlashBanner[0]['target_url'];
        }

        if ($data['currentTabId'] == $data['tab'][0]['id']) {
            // 下拉h5动画，最新的一个
            $additional = [
                'a.position' => 5,
            ];
            $dropDownH5FlashBanner = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', array_merge($where, $additional), 0, 1);
            if ($dropDownH5FlashBanner && $dropDownH5FlashBanner[0]['target_url']) {
                $data['banner']['dropDownH5'] = $dropDownH5FlashBanner[0]['target_url'];
            }
        }

        // 底部tab可换图
        $bottomTabs = $this->ci->banner_icon_model->getList('id, type, img, img_pitch', ['state' => 1]);
        if ($bottomTabs) {
            foreach ($bottomTabs as &$tab) {
                $tab['img'] = cdnImageUrl($tab['id']) . $tab['img'];
                $tab['img_pitch'] = cdnImageUrl($tab['id']) . $tab['img_pitch'];
            }
            $data['bottomTabs'] = $bottomTabs;
        }

        // 公共配置项
        $commonConfig = $this->ci->setting_model->getList('type, setting', [
            'type' => ['pro_promotion_tag_color', 'promotion_top_color'],
        ]);
        $commonConfig = array_column($commonConfig, 'setting', 'type');
        $data['banner']['promotion_tag_color'] = $commonConfig['pro_promotion_tag_color'];
        $data['promotion_top_color'] = $commonConfig['promotion_top_color'];

        // 没有数据
        if (!$data['banner']) {
            return ['code' => '300', 'msg' => '没有广告数据'];
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && $this->useMemcache) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$this->ci->memcached->set($memKey, $data, 60);
		}
        return ['code' => '200', 'msg' => 'succ', 'data' => $data];
    }

    /**
     * @api {post} / 用户推荐产品
     * @apiDescription 从 BI 获取用户的推荐产品
     * @apiGroup ad
     * @apiName setUserRecdSkus
     *
     * @apiParam {String} [device_id] 设备号
     * @apiParam {String} [connect_id] 登录标识
     *
     * @apiSampleRequest /api/test?service=ad.setUserRecdSkus&source=app
     */
    public function setUserRecdSkus($params)
    {
        $userInfo = $this->getUserInfo($params['device_id'], $params['connect_id']);
        if ($userInfo['uid'] && $this->showBiData) {
            $cacheKey = $userInfo['uid'] . '_recd_skus';
            $updateTime = $this->redis->hget($cacheKey, 'updateTime');

            // 第一次没有设置 或者 时间差大于更新间隔
            if (!$updateTime || $_SERVER['REQUEST_TIME'] - $updateTime / 1000 > $this->userRecdSkusInterval) {
                $this->ci->load->library('curl', null, 'http_curl');
                $omsParams = [
                    'uid' => intval($userInfo['uid']),
                    'page' => 1,
                    'length' => 200,
                ];
                $options['timeout'] = 6;
                $recSkuResponseTemp = $this->ci->http_curl->request(OMS_RECD_SKU_URL, $omsParams, 'JSON', $options);
                $recSkuResponse = json_decode($recSkuResponseTemp['response'], true);

                // 推送数据请求成功
                if ('0' == $recSkuResponse['code']) {
                    $this->redis->hMset($cacheKey, [
                        'updateTime' => $recSkuResponse['createTime'],
                        'skus' => json_encode($recSkuResponse['skus']),
                    ]);
                    $this->redis->expire($cacheKey, 3600*24*7);
                }
            }
        }

        // 防止APP报错
        return ['code' => '200'];
    }

    /**
     * 获取某个位置的广告
     *
     * @param $params ['device_id', 'connect_id', 'channel', 'timestamp', 'source', 'platform', 'store_id_list', 'tms_region_type', 'position']
     */
    public function getPositionBanner($params)
    {
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
            'position' => ['required' => ['code' => '500', 'msg' => 'position can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        // 获取相应的数据
        $userInfo = $this->getUserInfo($params['device_id'], $params['connect_id']);
        $defaultChannle = $this->ci->config->item('default_channle');
        if (in_array($params['channel'], $defaultChannle)) {
            $params['channel'] = 'portal';
        }
        $channel = empty($params['channel']) ? ['portal'] : ['portal', $params['channel']];
        $dateTime = date("Y-m-d H:i:s", $params['timestamp']);
        $data['storeId'] = $params['store_id_list'];
        $data['tmsRegionType'] = $params['tms_region_type'];

        // 过滤大门店
        $adStoreId = $this->filterStore($data['storeId']);

        // 广告公用过滤条件
        $where = [
            'a.channel' => $channel,
            'a.user_type LIKE "%' . $userInfo['type'] . '%"' => null,
            'a.is_show' => '1',
            '((a.start_time = 0 AND a.end_time = 0) OR (a.start_time <= "' . $dateTime . '" AND a.end_time >= "' . $dateTime . '"))' => null,
            'a.apply_status >=' => 0,
            'a.send_region_type >=' => $data['tmsRegionType'],
        ];
        switch ($params['source']) {
            case 'app':
                $where['a.app'] = 1;
                break;
            case 'wap':
                $where['a.wap'] = 1;
                break;
            case 'pc':
                $where['a.pc'] = 1;
                break;
            default:
                break;
        }

        $additional = [
            'a.position' => $params['position'],
        ];

        $banners = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', array_merge($where, $additional));
        if (0 == count($banners)) {
            return ['code' => '300', 'msg' => '没有广告数据'];
        }

        // 格式化广告图
        $content = [];
        foreach ($banners as $banner) {
            $formatRst = $this->formatBanner([
                'banner_id' => $banner['id'],
                'store_id' => $data['storeId'],
                'source' => $params['source'],
                'platform' => $params['platform'],
                'region_type' => $data['tmsRegionType'],
            ]);

            if ($formatRst) {
                $content[] = $formatRst;
            }
        }

        if (0 == count($content)) {
            return ['code' => '300', 'msg' => '没有广告数据'];
        } else {
            return ['code' => '200', 'msg' => 'succ', 'data' => $content];
        }
    }

    /**
     * 格式化广告图（对内）
     *
     * @param array $params [ banner_id, store_id, source ]
     * @return array
     */
    public function formatBanner($params)
    {
        // 获取广告数据
        $adStoreId = $this->filterStore($params['store_id']);
        $ads = $this->ci->ad_model->getAdByStore($adStoreId, 'a.*,ast.store_id', [
            'id' => $params['banner_id'],
        ], 0, 1);
        if (!$ads) {
            return [];
        } else {
            $adInfo = $ads[0];
        }

        // webp 图片
        if ($params['source'] == 'app' && $adInfo['has_webp'] && $params['platform'] != 'IOS') {
            $adInfo['image'] = str_replace(['.jpg', '.jpeg'], '.webp', $adInfo['image']);
        }

        // 可能被商品数据覆盖的数据
        $title = $adInfo['title'];
        $subtitle = $adInfo['subtitle'];
        $image = $adInfo['image'];

        // target_type = 1 商品详情，target_id 代表 product id
        $productInfo = [];
        $isOnline = $inStock = 1; // 在线，有库存
        if ($adInfo['target_type'] == 1) {
            // 获取 product 信息
            $products = $this->ci->product_model->getProductByStore($params['store_id'], $params['region_type'], $params['source'], ['product.id' => $adInfo['target_id'] ]);
            if ($products) {
                $productInfo = $products[0];

                // 获取产品模板图片
                if ($productInfo['template_id']) {
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($productInfo['template_id'], 'whitebg');
                    if (isset($templateImages['whitebg'])) {
                        $productInfo['promotion_photo'] = $templateImages['whitebg']['image'];
                        $productInfo['has_webp'] = $templateImages['whitebg']['has_webp'];
                    }
                }

                // webp 图片
                if ($params['source'] == 'app' && $productInfo['has_webp'] && $params['platform'] != 'IOS') {
                    $productInfo['promotion_photo'] = str_replace(['.jpg', '.jpeg'], '.webp', $productInfo['promotion_photo']);
                }

                // 覆盖原则，已广告配置为主
                $title = $title ?: $productInfo['product_name'];
                $subtitle = $subtitle ?: $productInfo['product_desc'];
                $image = $image ?: $productInfo['promotion_photo'];

                // 标签时间过滤
                if (!empty($productInfo['promotion_tag']) && $productInfo['promotion_tag_start'] && $productInfo['promotion_tag_end']) {
                    $iNowUnixTime = $_SERVER['REQUEST_TIME'];

                    if ($iNowUnixTime < strtotime($productInfo['promotion_tag_start']) || $iNowUnixTime > strtotime($productInfo['promotion_tag_end'])) {
                        $productInfo['promotion_tag'] = '';
                    }
                }

                // 是否有货
                if ($productInfo['stock'] <= 0) {
                    $this->filterInStock && $inStock = 0;
                }
            } else {
                $isOnline = 0;
            }
        }

        // 分时显示
        if ($adInfo['time_display_type']) {
            $time = date("H:i:s", $_SERVER['REQUEST_TIME']);
            $day = date("N", $_SERVER['REQUEST_TIME']);

            // everyday
            if ($time < $adInfo['time_display_start'] || $adInfo['time_display_end'] < $time) {
                return [];
            }
            // workday
            if ('2' == $adInfo['time_display_type'] && 6 > $day) {
                return [];
            }
            // weekend
            if ('3' == $adInfo['time_display_type'] && 5 < $day) {
                return [];
            }
        }
        // 不在线 | 没库存
        if ($isOnline == 0 || $inStock == 0) {
            return [];
        }
        return [
            'banner_ad_id' => $params['banner_id'],
            'title' => $title,
            'subtitle' => $subtitle,
            'image' => empty($image) ? '' : cdnImageUrl($adInfo['id']) . $image,

            // target
            'target_type' => $adInfo['target_type'],
            'target_id' => $adInfo['target_id'],
            'target_url' => $adInfo['target_url'],
            'store_id' => $adInfo['store_id'],

            // other
            'is_more' => $adInfo['is_more'], // 是否查看更多
            'need_navigation_bar' => $adInfo['need_navigation_bar'], // 目标页面是否需要导航条

            // product
            'banner_tag' => isset($productInfo['promotion_tag']) ? $productInfo['promotion_tag'] : '',
            'promotion_tag' => isset($productInfo['special_tag']) ? $productInfo['special_tag'] : '',
            'volume' => isset($productInfo['volume']) ? $productInfo['volume'] : '',
            'guide_price' => isset($productInfo['old_price']) ? strval((float) $productInfo['old_price']) : '',
            'price' => isset($productInfo['price']) ? strval((float) $productInfo['price']) : '',
            'in_stock' => strval($inStock),
//            'is_bi_recd' => '0',

            // share
            'share' => [
                'is_share' => $adInfo['is_share'],
                'share_icon' => empty($adInfo['share_icon']) ? '' : cdnImageUrl($adInfo['id']) . $adInfo['share_icon'],
                'share_text' => $adInfo['share_text'],
                'share_url' => $adInfo['share_url'],
                'share_type' => $adInfo['share_type'],
            ],
        ];
    }

    /**
     * 根据设备号、登陆标识来获取用户信息
     *
     * @param string $deviceId 设备号
     * @param string $connectId 登录标识
     * @return string [ type => unregistered | v0 | register_no_buy | v1 | v2 | v3 | v4 | v5 | ...,
     *                  uid => 0 ]
     */
    protected function getUserInfo($deviceId, $connectId)
    {
        $userType = '';
        $uid = 0;

        // 设备是否绑定过会员号
        $isBindUser = 0;
        if ($deviceId) {
            $this->ci->load->model('user_mobile_data_model');
            $isBindUser = $this->ci->user_mobile_data_model->count([
                'device_id' => $deviceId,
                'uid !=' => '0',
            ]);
        }

        // 未登录
        if (empty($connectId)) {
            if (!$isBindUser) {
                // 未注册用户
                $userType = 'unregistered';
            } else {
                // 获取设备最后绑定的用户ID
                $uids = $this->ci->user_mobile_data_model->getList('uid', ['device_id' => $deviceId], 0, 1, 'id DESC');
                $uid = $uids[0]['uid'];
            }
        }
        // 已经登陆
        else {
            // 根据 connect id 获取用户ID
            $this->ci->load->library('session', ['session_id' => $connectId]);
            $session = $this->ci->session->userdata;
            $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
            $uid = $userdata['id'];
        }

        // 根据用户ID获取用户类型
        $this->ci->load->model('user_model');
        if ($uid) {
            $userInfo = $this->ci->user_model->dump(['id' => $uid], 'user_rank');
            // 已注册用户
            $userType = 'v' . ($userInfo['user_rank'] - 1);
        }

        // 判断 v0 用户订单情况
        if ($userType == 'v0') {
            $this->ci->load->model('order_model');
            $orderCount = $this->ci->order_model->count([
                'order_status' => '1',
                'pay_status' => '1',
                'operation_id !=' => '5',
                'uid' => $uid,
            ]);
            if ($orderCount == 0) {
                // 注册未购买用户
                $userType = 'register_no_buy';
            }
        }

        return [
            'type' => $userType,
            'uid' => $uid,
        ];
    }

    /**
     * 获取用户GPS地址
     *
     * @param int $uid 用户ID
     * @param string $lonlat 经纬度
     * @param array $data
     * @return array
     */
    protected function getUserGpsAddress($uid, $lonlat, &$data)
    {
        $this->ci->load->bll('deliver');
        $result = $this->ci->bll_deliver->getGpsAddress([
            'uid' => $uid,
            'lonlat' => $lonlat,
        ]);
//        $this->log['result'] = $result;

        if ($result['code'] == '200') {
            if ($result['data']['defaultAddress']) {
                $data['currentAddress'][] = $result['data']['defaultAddress'];
            }
//            $data['otherAddress'] = $result['data']['addressList'];
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * 根据 GPS 获取 storeId、deliverId
     *
     * @param string $lonlat 经纬度
     * @param string $districtCode 区域代码
     * @param array $data
     * @param array $params
     * @return array
     */
    protected function getTmsStore($lonlat, $districtCode, &$data, &$params)
    {
        $this->ci->load->bll('deliver');
        $result = $this->ci->bll_deliver->getTmsStore([
            'lonlat' => $lonlat,
            'districtCode' => $districtCode,
        ]);

        if ($result['code'] == '200') {
            if (empty($result['data']['store_id_list'])) {
                $params['res'] = $result;
                $this->addLog($params, 'tms_gps_error');
            }
            if (empty($result['data']['delivery_code'])) {
                $params['res'] = $result;
                $this->addLog($params, 'rbac_gps_error');
            }

            $data['storeId'] = $result['data']['store_id_list'];
            $data['deliverId'] = $result['data']['delivery_code'];
            $data['deliveryType'] = $result['data']['delivery_type'];
            $data['tmsRegionType'] = $result['data']['tms_region_type'];
            $data['tmsRegionTime'] = $result['data']['tms_region_time'];
            $data['tms_region_type'] = $result['data']['tms_region_type'];
            $data['tms_region_time'] = $result['data']['tms_region_time'];
            $data['isDayNight'] = $result['data']['is_day_night'];

            return '';
        } else {
            return $result;
        }
    }

    /**
     * 获取用户推荐商品
     *
     * @param int $uid
     * @param array|string $adStoreId
     * @param int $regionType
     * @param string $source
     */
    protected function getUserRecdSkus($uid, $adStoreId, $regionType, $source = 'app')
    {
        if (!$this->showBiData) {
            return [];
        }

        $cacheKey = $uid . '_recd_skus';
        $skus = $this->redis->hGet($cacheKey, 'skus');

        if ($skus) {
            $skuWeightArr = array_column(json_decode($skus, true), 'weight', 'sku');
            $skuArr = array_column(json_decode($skus, true), 'sku');
            $products = $this->ci->product_model->getProductByStore($adStoreId, $regionType, $source, ['product_price.inner_code' => $skuArr]);
            foreach ($products as $key => &$product) {
                if ($this->filterInStock && $product['stock'] <= 0) {
                    unset($products[$key]);
                }
                $product['sku_weight'] = $skuWeightArr[$product['product_no']];
            }
            array_multisort(array_column($products, 'sku_weight'), SORT_DESC, SORT_NUMERIC, $products);
            return $products;
        } else {
            return [];
        }
    }

    /**
     * 过滤大门店
     *
     * @param string $storeIdList
     * @return string
     */
    protected function filterStore($storeIdList)
    {
        $storeIdList = changeStoreId($storeIdList);

        // 过滤大门店
        $adStoreIdTemp = array_column($this->ci->b2o_store_model->getList('id', ['type' => 1]), 'id');
        if (implode(',', array_diff(explode(',', $storeIdList), $adStoreIdTemp))) {
            $adStoreId = implode(',', array_diff(explode(',', $storeIdList), $adStoreIdTemp));
        } else {
            $adStoreId = $storeIdList;
        }

        return $adStoreId;
    }

    /**
     * 首页广告分组数量限制
     *
     * @param array $ads
     * @param int $limit
     * @return array
     */
    protected function adGroupLimit($ads, $limit)
    {
        $count = count($ads);
        if ($count < $limit) {
            return $ads;
        } else {
            $result = [];
            $isMore = [];
            foreach ($ads as $ad) {
                if ($ad['is_more'] == 1) {
                    $isMore = $ad;
                    continue;
                } else {
                    $result[] = $ad;
                }
            }

            if ($isMore) {
                $temp = array_slice($result, 0, $limit - 1);
                $temp[] = $isMore;
                return $temp;
            } else {
                return array_slice($result, 0, $limit);
            }
        }
    }

    /**
     * 获取商品广告
     *
     * @param array $products
     * @param array $params
     */
    protected function getProductBanner(array $products, array $params)
    {
        if (!$products) return [];

        $banners = [];
        foreach ($products as $pro) {
            $inStock = 1;

            // 获取产品模板图片
            if ($pro['template_id']) {
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($pro['template_id'], 'whitebg');
                if (isset($templateImages['whitebg'])) {
                    $pro['promotion_photo'] = $templateImages['whitebg']['image'];
                    $pro['has_webp'] = $templateImages['whitebg']['has_webp'];
                }
            }

            // webp 图片
            if ($params['source'] == 'app' && $pro['has_webp'] && $params['platform'] != 'IOS') {
                $pro['promotion_photo'] = str_replace(['.jpg', '.jpeg'], '.webp', $pro['promotion_photo']);
            }

            // 标签时间过滤
            if (!empty($pro['promotion_tag']) && $pro['promotion_tag_start'] && $pro['promotion_tag_end']) {
                $iNowUnixTime = $_SERVER['REQUEST_TIME'];

                if ($iNowUnixTime < strtotime($pro['promotion_tag_start']) || $iNowUnixTime > strtotime($pro['promotion_tag_end'])) {
                    $pro['promotion_tag'] = '';
                }
            }

            // 是否有货
            if ($pro['stock'] <= 0) {
                $this->filterInStock && $inStock = 0;
            }

            // 设置商品广告
            $banners[] = [
                'banner_ad_id' => '',
                'title' => $pro['product_name'],
                'subtitle' => $pro['product_desc'],
                'image' => empty($pro['promotion_photo']) ? '' : cdnImageUrl($pro['product_id']) . $pro['promotion_photo'],

                // target
                'target_type' => '1',
                'target_id' => $pro['product_id'],
                'target_url' => '',
                'store_id' => $pro['store_id'],

                // other
                'is_more' => '0', // 是否查看更多
                'need_navigation_bar' => '0', // 目标页面是否需要导航条

                // product
                'banner_tag' => isset($pro['promotion_tag']) ? $pro['promotion_tag'] : '',
                'promotion_tag' => isset($pro['special_tag']) ? $pro['special_tag'] : '',
                'volume' => isset($pro['volume']) ? $pro['volume'] : '',
                'guide_price' => isset($pro['old_price']) ? strval((float) $pro['old_price']) : '',
                'price' => isset($pro['price']) ? strval((float) $pro['price']) : '',
                'in_stock' => strval($inStock),
//                'is_bi_recd' => '1',

                // share
                'share' => [
                    'is_share' => '',
                    'share_icon' => '',
                    'share_text' => '',
                    'share_url' => '',
                    'share_type' => '',
                ],
            ];
        }

        return $banners;
    }

    /**
     * @api {post} / 热销商品
     * @apiDescription 获取当前所在位置门店的热销商品
     * @apiGroup ad
     * @apiName getTopSeller
     *
     * @apiParam {String} [store_id_list] 门店ID，多个用英文逗号分隔，type=3 时必须
     * @apiParam {String} [tms_region_type] 配送类型
     *
     * @apiSampleRequest /api/test?service=ad.getTopSeller&source=app
     */
    public function getTopSeller($params)
    {
        $required = [
            'store_id_list' => ['required' => ['code' => '500', 'msg' => 'store_id_list can not be null']],
            'tms_region_type' => ['required' => ['code' => '500', 'msg' => 'tms_region_type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        // 组建 memcache key 值
        $storeList = explode(',', $params['store_id_list']);
        sort($storeList);
        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . implode('_', $storeList) . "_" . $params['tms_region_type'];
        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service'] && $this->useMemcache) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }

            $cacheData = $this->ci->memcached->get($memKey);
            if ($cacheData) {
                return ['code' => '200', 'msg' => 'succ', 'data' => $cacheData];
            }
        }

        $sql = "SELECT op.product_id, op.product_name, SUM(op.qty) quantity, SUBSTRING_INDEX(pc.cat_path,',',1) top_class
                FROM ttgy_order o
                LEFT JOIN ttgy_order_product op ON op.order_id = o.id
                LEFT JOIN ttgy_product p ON op.product_id = p.id
                LEFT JOIN ttgy_b2o_product_template pt ON p.template_id = pt.id
                LEFT JOIN ttgy_b2o_product_class pc ON pt.class_id = pc.id
                WHERE o.time > NOW() - INTERVAL 2 HOUR
                AND op.type != 3
                GROUP BY op.product_id
                HAVING top_class IN(10, 1, 4)
                ORDER BY quantity DESC
                limit 100";
        $topSellers = $this->ci->db->query($sql)->result_array();

        if ($topSellers) {
            $pidQtyArr = array_column($topSellers, 'quantity', 'product_id');
            $pidTopClassArr = array_column($topSellers, 'top_class', 'product_id');
            $pidArr = array_column($topSellers, 'product_id');
            $products = $this->ci->product_model->getProductByStore($params['store_id_list'], $params['tms_region_type'], $params['source'], ['b2o_store_product.product_id' => $pidArr]);

            // 一级类目数量限制 10:6 | 1+4:4
            $topClassLimit = ['10' => 6, '1_4' => 4];
            foreach ($products as $key => &$product) {
                if ($this->filterInStock && $product['stock'] <= 0) {
                    unset($products[$key]);
                    continue;
                }

                // 水果 10
                if ($pidTopClassArr[$product['product_id']] == 10) {
                    if ($topClassLimit['10'] > 0) {
                        $topClassLimit['10'] = $topClassLimit['10'] - 1;
                    } else {
                        unset($products[$key]);
                        continue;
                    }
                }
                // 水产、肉禽 1+4
                else {
                    if ($topClassLimit['1_4'] > 0) {
                        $topClassLimit['1_4'] = $topClassLimit['1_4'] - 1;
                    } else {
                        unset($products[$key]);
                        continue;
                    }
                }

                $product['sell_quantity'] = $pidQtyArr[$product['product_id']];
            }
            array_multisort(array_column($products, 'sell_quantity'), SORT_DESC, SORT_NUMERIC, $products);

            $proBanners = $this->getProductBanner($products, $params);

            if ($proBanners) {
                // 尝试保存到缓存
                if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && $this->useMemcache) {
                    if (!$this->ci->memcached) {
                        $this->ci->load->library('memcached');
                    }

                    $this->ci->memcached->set($memKey, $proBanners, 7200);
                }

                return ['code' => '200', 'msg' => 'succ', 'data' => $proBanners];
            } else {
                return ['code' => '300', 'msg' => '没有热销数据'];
            }
        } else {
            return ['code' => '300', 'msg' => '没有热销数据'];
        }
    }

    private function addLog($data, $key = 'ad_error')
    {
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, $key, json_encode($data));
    }
}
