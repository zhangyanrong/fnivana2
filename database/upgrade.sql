CREATE TABLE `ttgy_b2o_package` (
  `p_order_id` int(11) NOT NULL COMMENT '包裹所属订单ID',
  `tag` varchar(100) NOT NULL COMMENT '包裹TAG',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '包裹类型',
  `method_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '包裹运费',
  `shtime` varchar(20) NOT NULL DEFAULT '' COMMENT '发货日期',
  `stime` varchar(20) NOT NULL DEFAULT '' COMMENT '发货时间',
  PRIMARY KEY (`p_order_id`,`tag`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='包裹表';

ALTER TABLE `ttgy_user_address` ADD `lonlat` VARCHAR(50) NULL DEFAULT NULL COMMENT '坐标' , ADD `province_adcode` VARCHAR(10) NULL DEFAULT NULL COMMENT '省行政编号' , ADD `city_adcode` VARCHAR(10) NULL DEFAULT NULL COMMENT '市行政编号' , ADD `area_adcode` VARCHAR(10) NULL DEFAULT NULL COMMENT '区行政编号' , ADD INDEX (`province_adcode`) , ADD INDEX (`city_adcode`), ADD INDEX (`area_adcode`);

ALTER TABLE `ttgy_user_address` ADD `address_name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址名称' AFTER `flag`;

ALTER TABLE `ttgy_user_address` ADD `province_name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '省',ADD `city_name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '市',ADD `area_name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '区';

ALTER TABLE `ttgy_order` MODIFY COLUMN `stime` VARCHAR(20);
ALTER TABLE `ttgy_order` ADD `p_order_id` int(11) DEFAULT '0',
CREATE INDEX p_order_id ON ttgy_order (p_order_id);

ALTER TABLE `ttgy_user_gifts` ADD `bonus_b2o_order` int(11) DEFAULT NULL
ALTER TABLE `ttgy_user_gifts` ADD `cancel_b2o_order` int(11) DEFAULT NULL

ALTER TABLE `ttgy_trade_invoice` ADD `kp_type` int(11) DEFAULT '1' COMMENT '1-水果 2-食品'

ALTER TABLE `ttgy_order_invoice` ADD `kp_type` int(11) DEFAULT '1' COMMENT '1-水果 2-食品'

ALTER TABLE `ttgy_order_product` ADD `discount` decimal(10,2) DEFAULT '0.00' COMMENT '购物车折扣'

ALTER TABLE `ttgy_order` ADD `store_id` int(11) DEFAULT '0' COMMENT '门店id'

ALTER TABLE `ttgy_order` MODIFY COLUMN `method_money` decimal(10,2) NOT NULL DEFAULT '0.00';

--------------------
-- product
--------------------
DROP TABLE IF EXISTS `ttgy_b2o_product_template`;
CREATE TABLE `ttgy_b2o_product_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '产品模板ID',
  `template_title` varchar(100) NOT NULL DEFAULT '' COMMENT '模板标题',
  `template_subtitle` varchar(100) NOT NULL DEFAULT '' COMMENT '模板副标题',
  `class_id` int(10) unsigned NOT NULL COMMENT '后端类目ID',
  `desc_pc` text NOT NULL,
  `desc_mobile` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品模板表';

DROP TABLE IF EXISTS `ttgy_b2o_product_template_image`;
CREATE TABLE `ttgy_b2o_product_template_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL COMMENT '产品模板ID',
  `image_type` varchar(20) NOT NULL DEFAULT '' COMMENT '图片类型',
  `image` varchar(120) NOT NULL DEFAULT '',
  `thumb` varchar(120) NOT NULL DEFAULT '',
  `big_image` varchar(120) NOT NULL DEFAULT '',
  `middle_image` varchar(120) NOT NULL DEFAULT '',
  `small_thumb` varchar(120) NOT NULL DEFAULT '',
  `sort` int(11) unsigned NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `template_image_type` (`template_id`,`image_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品模板图片表';

DROP TABLE IF EXISTS `ttgy_b2o_product_template_attr`;
CREATE TABLE `ttgy_b2o_product_template_attr` (
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '产品模板ID',
  `attr_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '属性ID',
  `attr_value` varchar(50) NOT NULL DEFAULT '' COMMENT '属性值'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品模板动态属性关联表';

DROP TABLE IF EXISTS `ttgy_b2o_product_warehouse`;
CREATE TABLE `ttgy_b2o_product_warehouse` (
  `product_id` int(10) unsigned NOT NULL COMMENT '产品ID',
  `ph_warehouse_id` int(10) unsigned NOT NULL COMMENT '物理大仓ID',
  PRIMARY KEY (`product_id`,`ph_warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='物理大仓产品关系表';

ALTER TABLE `ttgy_product`
ADD COLUMN `template_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '产品的模板ID' AFTER `id`,
ADD COLUMN `middle_type` varchar(50) NOT NULL DEFAULT '' COMMENT '中台类型' AFTER `is_present`;
ALTER TABLE `ttgy_product`
ADD INDEX `template_id` (`template_id`);

ALTER TABLE `ttgy_product`
ADD COLUMN `scene_tag`  varchar(255) NOT NULL DEFAULT '' COMMENT '场景化标签' AFTER `use_warehouse_store`;

--------------------
-- ad
--------------------
DROP TABLE IF EXISTS `ttgy_b2o_ad_tab`;
CREATE TABLE `ttgy_b2o_ad_tab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'TabID',
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT 'Tab标题',
  `image` varchar(100) NOT NULL DEFAULT '' COMMENT '默认图片',
  `image_selected` varchar(100) NOT NULL DEFAULT '' COMMENT '选中图片',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告Tab表';

DROP TABLE IF EXISTS `ttgy_b2o_ad_tab_store`;
CREATE TABLE `ttgy_b2o_ad_tab_store` (
  `tab_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'TabID',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '门店ID',
  PRIMARY KEY (`tab_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告Tab门店关联表';

DROP TABLE IF EXISTS `ttgy_b2o_ad_group`;
CREATE TABLE `ttgy_b2o_ad_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '分组标题',
  `tab_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'TabID',
  `type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '显示类型ID',
  `tag_id` int(10) unsigned NOT NULL COMMENT '场景标签ID',
  `has_space` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '分组顶部是否有间隔行',
  `bg_color` varchar(10) NOT NULL DEFAULT '' COMMENT '背景色',
  `is_show` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告分组表';

DROP TABLE IF EXISTS `ttgy_b2o_ad`;
CREATE TABLE `ttgy_b2o_ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '广告ID',
  `position` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '广告位置',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `channel` varchar(50) NOT NULL DEFAULT '' COMMENT '展示渠道',
  `app` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'app使用',
  `wap` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'm站使用',
  `pc` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'pc使用',
  `user_type` varchar(100) NOT NULL DEFAULT '' COMMENT '用户类型',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '广告标题',
  `subtitle` varchar(100) NOT NULL DEFAULT '' COMMENT '广告副标题',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '广告备注',
  `image` varchar(100) NOT NULL DEFAULT '' COMMENT '广告图片',
  `target_type` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '目标类型',
  `target_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '目标ID',
  `target_url` varchar(255) NOT NULL DEFAULT '' COMMENT '目标链接',
  `is_more` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否查看更多',
  `need_navigation_bar` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '目标页面是否需要导航条',
  `is_show` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示',
  `start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '显示开始时间',
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '显示结束时间',
  `time_display_type` varchar(20) NOT NULL DEFAULT '' COMMENT '一天内分时展示类型',
  `time_display_start` time NOT NULL DEFAULT '00:00:00' COMMENT '一天内分时展示开始时间',
  `time_display_end` time NOT NULL DEFAULT '00:00:00' COMMENT '一天内分时展示结束时间',
  `apply_status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '审核状态',
  `apply_feedback` varchar(255) NOT NULL DEFAULT '' COMMENT '审核反馈',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `admin_name` varchar(20) NOT NULL DEFAULT '' COMMENT '管理员名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告表';

DROP TABLE IF EXISTS `ttgy_b2o_ad_store`;
CREATE TABLE `ttgy_b2o_ad_store` (
  `ad_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广告ID',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '门店ID',
  PRIMARY KEY (`ad_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告门店关联表';


CREATE TABLE `ttgy_solr_product_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL COMMENT '关键字',
  `type` tinyint(4) NOT NULL COMMENT '类型',
  `last_modify_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`,`type`),
  KEY `last_modify_time` (`last_modify_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--------------------
-- order
--------------------

DROP TABLE IF EXISTS `ttgy_b2o_order_cart`;
CREATE TABLE `ttgy_b2o_order_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_name` varchar(200) NOT NULL DEFAULT '0',
  `items` longtext,
  `products` longtext,
  `promotions` longtext,
  `total` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_name` (`order_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单购物车表';

DROP TABLE IF EXISTS `ttgy_b2o_order_package`;
CREATE TABLE `ttgy_b2o_order_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_name` varchar(200) NOT NULL DEFAULT '0',
  `content` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_name` (`order_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单包裹表';

DROP TABLE IF EXISTS `ttgy_b2o_delivery_tpl`;
CREATE TABLE `ttgy_b2o_delivery_tpl` (
  `tpl_id` int(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '模板名称',
  `type` int(11) NOT NULL COMMENT '模板类型。1=当日达，2=次日达，3=预售',
  `rule` text NOT NULL COMMENT '规则详情。用json格式保存。',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注。',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间。',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最近更新时间。',
  PRIMARY KEY (`tpl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='配送模板数据。'

DROP TABLE IF EXISTS `ttgy_b2o_parent_order`;
CREATE TABLE `ttgy_b2o_parent_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `order_name` varchar(50) NOT NULL DEFAULT '0',
  `trade_no` varchar(50) DEFAULT NULL,
  `billno` varchar(50) DEFAULT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pay_time` datetime DEFAULT NULL,
  `update_pay_time` datetime DEFAULT NULL,
  `pay_name` varchar(70) DEFAULT '',
  `pay_parent_id` int(11) DEFAULT '0',
  `pay_id` varchar(11) DEFAULT '',
  `shtime` varchar(20) NOT NULL DEFAULT '',
  `stime` varchar(10) DEFAULT NULL,
  `send_date` date DEFAULT NULL,
  `zipcode` int(10) DEFAULT NULL,
  `hk` varchar(220) NOT NULL DEFAULT '',
  `msg` varchar(220) NOT NULL DEFAULT '',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `goods_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `jf_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `method_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `today_method_money` int(11) NOT NULL DEFAULT '0',
  `card_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `manbai_money` decimal(10,2) DEFAULT NULL,
  `member_card_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '????',
  `cmoney` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bank_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_msg` varchar(120) NOT NULL DEFAULT '',
  `pay_status` int(11) NOT NULL DEFAULT '0',
  `postcode` varchar(50) DEFAULT NULL,
  `fp` varchar(50) DEFAULT NULL,
  `fp_dz` varchar(50) DEFAULT NULL,
  `operation_id` int(2) NOT NULL DEFAULT '0',
  `lyg` tinyint(1) NOT NULL DEFAULT '0',
  `score` decimal(10,2) DEFAULT NULL,
  `address_id` varchar(20) DEFAULT NULL,
  `use_card` varchar(50) DEFAULT NULL,
  `use_vip` varchar(20) DEFAULT NULL,
  `use_jf` int(10) DEFAULT '0',
  `order_status` smallint(2) NOT NULL DEFAULT '0',
  `referer_url` mediumtext,
  `sync_erp` int(11) NOT NULL DEFAULT '0',
  `cart_num` int(5) NOT NULL DEFAULT '0',
  `get_card_money_upto` varchar(200) DEFAULT NULL,
  `money_upto_text` varchar(500) DEFAULT NULL,
  `active_gifts` varchar(500) DEFAULT NULL,
  `order_region` tinyint(2) NOT NULL DEFAULT '1',
  `has_bask` tinyint(2) NOT NULL DEFAULT '0',
  `had_comment` tinyint(4) NOT NULL DEFAULT '0',
  `invoice_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `channel` tinyint(4) NOT NULL DEFAULT '1',
  `statistics_tag` varchar(100) DEFAULT NULL,
  `order_ip` varchar(50) DEFAULT NULL,
  `use_money_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `erp_active_tag` varchar(200) NOT NULL,
  `order_type` int(3) unsigned NOT NULL DEFAULT '1',
  `last_modify` int(10) DEFAULT '0',
  `is_enterprise` varchar(20) DEFAULT NULL,
  `sales_channel` tinyint(1) NOT NULL DEFAULT '1' COMMENT '????1-????2-??',
  `pay_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `new_pay_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `oauth_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `oauth_from` varchar(50) DEFAULT NULL COMMENT 'APPæ¥æºæ ‡ç¤º',
  `version` int(11) NOT NULL DEFAULT '1',
  `sync_status` tinyint(2) NOT NULL DEFAULT '0',
  `last_modify_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `has_tj` tinyint(2) NOT NULL DEFAULT '0',
  `cang_id` tinyint(2) DEFAULT '1',
  `deliver_type` tinyint(1) DEFAULT '1',
  `sheet_show_price` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'é¢å•æ˜¯å¦æ˜¾ç¤ºä»·æ ¼',
  `sendCompleteTime` datetime DEFAULT NULL,
  `show_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'è®¢å•çŠ¶æ€0-éšè— 1-æ˜¾ç¤º',
  `department` varchar(100) DEFAULT NULL,
  `p_order_id` int(11) DEFAULT '0',
  `store_id` int(11) DEFAULT '0' COMMENT '门店id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_name` (`order_name`),
  KEY `uid` (`uid`),
  KEY `region_type_index` (`order_region`),
  KEY `use_card_index` (`use_card`),
  KEY `last_modify_time` (`last_modify_time`),
  KEY `IDX_PA_OP_PA_UI` (`pay_parent_id`,`operation_id`,`pay_status`,`uid`),
  KEY `time_index` (`time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单表';

DROP TABLE IF EXISTS `ttgy_b2o_parent_order_invoice`;
CREATE TABLE `ttgy_b2o_parent_order_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `username` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `address` varchar(200) NOT NULL,
  `province` varchar(200) NOT NULL,
  `city` varchar(200) NOT NULL,
  `area` varchar(200) NOT NULL,
  `province_id` int(11) NOT NULL DEFAULT '0',
  `city_id` int(11) NOT NULL DEFAULT '0',
  `area_id` int(11) NOT NULL DEFAULT '0',
  `is_valid` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否有效发票',
  `kp_type` int(11) DEFAULT '1' COMMENT '1-水果 2-食品',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_order_id` (`order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8


DROP TABLE IF EXISTS `ttgy_b2o_parent_order_product`;
CREATE TABLE `ttgy_b2o_parent_order_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `product_name` varchar(120) NOT NULL DEFAULT '',
  `product_id` int(11) DEFAULT NULL,
  `product_no` varchar(20) DEFAULT NULL,
  `gg_name` varchar(120) NOT NULL DEFAULT '',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `qty` int(11) NOT NULL DEFAULT '0',
  `score` int(10) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `type` tinyint(2) NOT NULL DEFAULT '1',
  `sale_rule_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1:搭配购，2：满额折',
  `total_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `group_pro_id` int(10) NOT NULL DEFAULT '0',
  `sid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `index_group_pro_id` (`group_pro_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单商品表';

CREATE TABLE `ttgy_solr_store_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `is_hide` tinyint(1) NOT NULL DEFAULT '0',
  `product_name` varchar(200) NOT NULL DEFAULT '',
  `promotion_tag` varchar(200) NOT NULL DEFAULT '',
  `tag` varchar(1000) NOT NULL DEFAULT '',
  `is_pc_online` tinyint(1) NOT NULL DEFAULT '0',
  `is_wap_online` tinyint(1) NOT NULL DEFAULT '0',
  `is_app_online` tinyint(1) NOT NULL DEFAULT '0',
  `channel` varchar(50) NOT NULL DEFAULT '',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `sort` int(10) NOT NULL DEFAULT '1',
  `last_modifty_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `ttgy_order_pay_discount` ADD `p_order_id` INT(20) NOT NULL DEFAULT '0' COMMENT '父单ID' AFTER `order_id`, ADD INDEX (`p_order_id`) ;

ALTER TABLE `ttgy_order_op` ADD `p_order_id` INT(20) NOT NULL DEFAULT '0' COMMENT '父单ID' AFTER `order_id`, ADD INDEX (`p_order_id`) ;

ALTER TABLE `ttgy_order_cancel_detail` ADD `p_order_id` INT(20) NOT NULL DEFAULT '0' COMMENT '父单ID' AFTER `order_id`, ADD INDEX (`p_order_id`) ;

ALTER TABLE `ttgy_order_cancel_detail` MODIFY COLUMN `order_id` int(20) NOT NULL DEFAULT '0' COMMENT '子单ID';

CREATE TABLE `ttgy_b2o_product_class` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(120) NOT NULL DEFAULT '',
	`parent_id` int(11) NOT NULL DEFAULT '1',
	`order_id` decimal(10,2) NOT NULL DEFAULT '0.00',
	`is_show` tinyint(3) NOT NULL DEFAULT '1',
	`step` smallint(11) NOT NULL DEFAULT '1',
	`cat_path` varchar(20) DEFAULT NULL,
	`ename` varchar(100) DEFAULT NULL,
	`photo` varchar(120) DEFAULT NULL,
	`product_id` varchar(100) DEFAULT NULL,
	`class_photo` varchar(120) DEFAULT NULL,
	`send_region` text DEFAULT NULL,
	`link` varchar(255) DEFAULT NULL,
	`is_split` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0-不分割，1-分割',
	`is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-ä¸€\n\nèˆ¬1-çƒ­é—¨',
	`is_scene` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否是场景类别',
	`last_modify_time` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=`InnoDB` AUTO_INCREMENT=1 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ROW_FORMAT=COMPACT CHECKSUM=0 DELAY_KEY_WRITE=0;

CREATE TABLE `ttgy_b2o_front_product_class` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(120) NOT NULL DEFAULT '',
	`parent_id` int(11) NOT NULL DEFAULT '1',
	`order_id` decimal(10,2) NOT NULL DEFAULT '0.00',
	`is_show` tinyint(3) NOT NULL DEFAULT '1',
	`step` smallint(11) NOT NULL DEFAULT '1',
	`cat_path` varchar(20) DEFAULT NULL,
	`ename` varchar(100) DEFAULT NULL,
	`photo` varchar(120) DEFAULT NULL,
	`product_id` varchar(100) DEFAULT NULL,
	`class_photo` varchar(120) DEFAULT NULL,
	`send_region` text DEFAULT NULL,
	`link` varchar(255) DEFAULT NULL,
	`is_split` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0-不分割，1-分割',
	`is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-ä¸€\n\nèˆ¬1-çƒ­é—¨',
	`is_scene` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否是场景类别',
	`banner_id` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `cat_path` USING BTREE (cat_path)
) ENGINE=`InnoDB` AUTO_INCREMENT=1 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ROW_FORMAT=COMPACT CHECKSUM=0 DELAY_KEY_WRITE=0;

CREATE TABLE `ttgy_b2o_template_front_class` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`template_id` int(11) NOT NULL,
	`front_class_id` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=`InnoDB` AUTO_INCREMENT=1 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ROW_FORMAT=COMPACT CHECKSUM=0 DELAY_KEY_WRITE=0;

CREATE TABLE `ttgy_b2o_product_search_tag` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`class_id` int(11) NOT NULL,
	`tags` varchar(255) DEFAULT '',
	`is_list_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否列表页显示０-不显示１-显示',
	`last_modify_time` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `last_modify_time` USING BTREE (last_modify_time)
) ENGINE=`InnoDB` AUTO_INCREMENT=1 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ROW_FORMAT=COMPACT CHECKSUM=0 DELAY_KEY_WRITE=0;



CREATE TABLE `ttgy_b2o_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT '' COMMENT '门店名称',
  `code` varchar(20) NOT NULL DEFAULT '' COMMENT '门店编码(sap)',
  `type` tinyint(2) DEFAULT '0' COMMENT '业务类型',
  `group` tinyint(2) DEFAULT '0' COMMENT '业务组',
  `seller_id` int(11) DEFAULT '0' COMMENT '商户ID',
  `manager` varchar(20) DEFAULT '' COMMENT '负责人',
  `mobile` varchar(20) DEFAULT '' COMMENT '手机号',
  `address` varchar(255) DEFAULT '' COMMENT '地址',
  `warehouse` tinyint(2) DEFAULT '0' COMMENT '补给物理大仓',
  `is_open` tinyint(1) DEFAULT '0' COMMENT '营业状态',
  `open_time` varchar(10) DEFAULT '' COMMENT '营业时间',
  `close_time` varchar(10) DEFAULT '' COMMENT '结束时间',
  `am_cutoff_time` varchar(10) DEFAULT '' COMMENT '上午截单时间',
  `pm_cutoff_time` varchar(10) DEFAULT '' COMMENT '下午截单时间',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `province` int(11) DEFAULT '0' COMMENT '省',
  `city` int(11) DEFAULT '0' COMMENT '市',
  `area` int(11) DEFAULT '0' COMMENT '区',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店';

INSERT INTO `ttgy_b2o_store` (`id`, `name`, `code`, `type`, `group`, `seller_id`, `manager`, `mobile`, `address`, `warehouse`, `is_open`, `open_time`, `close_time`, `am_cutoff_time`, `pm_cutoff_time`, `remark`, `province`, `city`, `area`)
VALUES
	(1, '上海大门店', '5-SELFDELIVERY', 1, 4, 2, '', '', '', 4, 1, '', '21:00', '11:00', '15:00', '', 0, 0, 0),
	(2, '江浙皖崇大门店', '5-THIRDDELIVERY', 1, 4, 2, '', '', '', 4, 1, '', '21:00', '11:00', '15:00', '', 0, 0, 0),
	(3, '北京大门店', '10-THIRDDELIVERY', 1, 4, 2, '', '', '', 9, 1, '', '21:00', '11:00', '15:00', '', 0, 0, 0),
	(4, '广州大门店（自建物流）', '12-SELFDELIVERY', 1, 4, 2, '', '', '', 11, 1, '', '21:00', '11:00', '15:00', '', 0, 0, 0),
	(5, '广州大门店（第三方快递）', '12-THIRDDELIVERY', 1, 4, 2, '', '', '', 11, 1, '', '21:00', '11:00', '15:00', '', 0, 0, 0);

CREATE TABLE `ttgy_b2o_store_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `product_desc` varchar(50) DEFAULT '' COMMENT '副标题',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `is_pc_online` tinyint(1) DEFAULT '0' COMMENT 'pc官网: 1:上架,0下架',
  `is_wap_online` tinyint(1) DEFAULT '0' COMMENT 'wap: 1:上架,0下架',
  `is_app_online` tinyint(1) DEFAULT '0' COMMENT 'app: 1:上架,0下架',
  `is_hide` tinyint(1) DEFAULT '0' COMMENT '是否隐藏',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `promotion_tag` varchar(50) DEFAULT '' COMMENT '促销标签',
  `promotion_tag_start` datetime DEFAULT NULL COMMENT '标签开始时间',
  `promotion_tag_end` datetime DEFAULT NULL COMMENT '标签结束时间',
  `special_tag` varchar(50) DEFAULT '' COMMENT '首页大促标签',
  `card_limit` tinyint(1) DEFAULT '0' COMMENT '不能使用优惠券',
  `jf_limit` tinyint(1) DEFAULT '0' COMMENT '不能使用积分',
  `first_limit` tinyint(1) DEFAULT '0' COMMENT '仅限首次购买',
  `active_limit` tinyint(1) DEFAULT '0' COMMENT '不参与任何促销活动',
  `is_free_post` tinyint(1) DEFAULT '0' COMMENT '是否包邮',
  `is_active` tinyint(1) DEFAULT '0' COMMENT '是否为活动商品',
  `is_limit_time` tinyint(1) DEFAULT '0' COMMENT '是否为限时惠',
  `limit_time_start` datetime DEFAULT NULL COMMENT '限时惠开始时间',
  `limit_time_end` datetime DEFAULT NULL COMMENT '限时惠结束时间',
  `limit_time_count` int(11) DEFAULT '0' COMMENT '限时惠份数',
  `device_limit` tinyint(1) DEFAULT '0' COMMENT '限时惠设备限制: 1 每日,2 永久',
  `delivery_template_id` int(11) DEFAULT '0' COMMENT '配送模版ID',
  `stock` int(11) DEFAULT '0' COMMENT '可售库存',
  `is_real_stock` tinyint(1) DEFAULT '0' COMMENT '是否启用实时库存',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '是否删除',
  `last_modify_time` datetime DEFAULT NULL COMMENT '最后修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_product` (`store_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店商品';


CREATE TABLE `ttgy_b2o_store_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ttgy_b2o_store_type` (`id`, `name`, `sort`)
VALUES
	(1, '大门店', 0),
	(2, '前置仓店', 5),
	(3, '城超门店', 4),
	(4, '果汁鲜榨', 3),
	(5, '牛奶饮品', 2);

CREATE TABLE `ttgy_b2o_cate_attr` (
  `attr_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '属性ID。',
  `name` char(10) NOT NULL DEFAULT '' COMMENT '属性名称。',
  `type` tinyint(1) NOT NULL COMMENT '属性类型。1=文本，2=单选。',
  `value` varchar(200) NOT NULL DEFAULT '' COMMENT '属性值。',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品属性库。';

CREATE TABLE `ttgy_b2o_delivery_tpl` (
  `tpl_id` int(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '模板名称',
  `type` int(11) NOT NULL COMMENT '模板类型。1=当日达，2=次日达，3=预售',
  `rule` text NOT NULL COMMENT '规则详情。用json格式保存。',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注。',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间。',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最近更新时间。',
  PRIMARY KEY (`tpl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配送模板数据。';

CREATE TABLE `ttgy_b2o_product_class_leaf` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `leaf_id` int(11) NOT NULL COMMENT '三级分类的ID。',
  `attr_id` int(11) NOT NULL COMMENT '属性项ID。',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品三级分类对应的属性项列表。';

CREATE TABLE `ttgy_b2o_parent_order_address` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `position` varchar(120) NOT NULL DEFAULT '',
  `address` varchar(220) NOT NULL DEFAULT '',
  `name` varchar(120) NOT NULL DEFAULT '',
  `email` varchar(120) NOT NULL DEFAULT '',
  `telephone` varchar(120) NOT NULL DEFAULT '',
  `mobile` varchar(120) NOT NULL DEFAULT '',
  `province` int(11) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `order_id` (`order_id`),
  KEY `index_mobile` (`mobile`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--------------------
-- promotion
--------------------
CREATE TABLE `ttgy_promotion_v2` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '优惠活动名称(对外展示)' ,
`remarks`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '优惠活动名称(对内展示)' ,
`owner`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '创建人' ,
`reviser`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后修改人' ,
`active`  tinyint(1) NULL DEFAULT 1 COMMENT '有效' ,
`start`  int(10) NOT NULL COMMENT '开始时间' ,
`end`  int(10) NOT NULL COMMENT '结束时间' ,
`created`  timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' ,
`updated`  timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间' ,
`range_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '范围类型single/group/all' ,
`range_sources`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '客户端[pc, wap, app]' ,
`range_members`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户等级[1,2,3,4,5,6]' ,
`range_stores`  text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '门店白名单[1,2,3]' ,
`range_products`  text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品白名单' ,
`condition_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '满额amount/满件quantity' ,
`condition_min`  float NOT NULL COMMENT '最小值' ,
`condition_max`  float NOT NULL COMMENT '最大值' ,
`condition_combo`  tinyint(1) NULL DEFAULT 0 COMMENT '是否全部满足(搭配购)' ,
`condition_repeat`  tinyint(1) NULL DEFAULT 1 COMMENT '是否可重复享受此优惠(同一单)' ,
`solution_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '换购exchange/赠品gift/减免discount' ,
`solution_reduce_money`  float NULL DEFAULT NULL COMMENT '优惠金额' ,
`solution_products`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '商品列表' ,
`solution_add_money`  float NULL DEFAULT NULL COMMENT '加价金额' ,
`solution_alert`  tinyint(1) NULL DEFAULT 1 COMMENT '是否要提醒' ,
PRIMARY KEY (`id`),
INDEX `购物车加载优惠` (`active`, `start`, `end`) USING BTREE COMMENT '购物车加载优惠'
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
AUTO_INCREMENT=86
ROW_FORMAT=COMPACT
;

--购物车增加秒杀字段
ALTER TABLE `gold`.`ttgy_promotion_v2` ADD COLUMN `condition_qty_limit` int DEFAULT 0 COMMENT '限购数量' AFTER `condition_repeat`, CHANGE COLUMN `solution_type` `solution_type` varchar(255) NOT NULL COMMENT '换购exchange/赠品gift/减免discount' AFTER `condition_qty_limit`, CHANGE COLUMN `solution_reduce_money` `solution_reduce_money` float DEFAULT NULL COMMENT '优惠金额' AFTER `solution_type`, CHANGE COLUMN `solution_products` `solution_products` text DEFAULT NULL COMMENT '商品列表' AFTER `solution_reduce_money`, CHANGE COLUMN `solution_add_money` `solution_add_money` float DEFAULT NULL COMMENT '加价金额' AFTER `solution_products`, CHANGE COLUMN `solution_alert` `solution_alert` tinyint(1) DEFAULT 1 COMMENT '是否要提醒' AFTER `solution_add_money`;

---------------------------------------------------------------
--商品模版图片表新增has_webp，用来标记是否生成过webp格式的图片。
---------------------------------------------------------------
ALTER TABLE `ttgy_b2o_product_template_image` ADD `has_webp` TINYINT( 1 ) NOT NULL DEFAULT '0' COMMENT '是否转换过webp’;

---------------------------------------------------------------
--年轮
---------------------------------------------------------------
ALTER TABLE `ttgy_b2o_store_product` ADD `send_region_type` INT(1) NOT NULL DEFAULT '3' COMMENT '支持配送区域类型' ;
ALTER TABLE `ttgy_b2o_ad` ADD `send_region_type` INT(1) NOT NULL DEFAULT '3' COMMENT '支持配送区域类型';

---------------------------------------------------------------
--TMS - 订单坐标地址
---------------------------------------------------------------
ALTER TABLE `ttgy_order_address` ADD `lonlat` varchar(50) DEFAULT '' COMMENT '订单坐标地址';
