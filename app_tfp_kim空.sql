/*
Navicat MySQL Data Transfer

Source Server         : coin2
Source Server Version : 50719
Source Host           : 47.89.23.187:3306
Source Database       : app_tfp_kim

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2018-05-31 17:10:21
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `pwd` char(32) DEFAULT NULL,
  `last_log_ip` varchar(15) DEFAULT NULL,
  `last_log_time` char(10) DEFAULT NULL,
  `descript` varchar(50) DEFAULT NULL COMMENT '管理员描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=gbk;

-- ----------------------------
-- Table structure for admin_auth_group
-- ----------------------------
DROP TABLE IF EXISTS `admin_auth_group`;
CREATE TABLE `admin_auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `rules` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=gbk;

-- ----------------------------
-- Table structure for admin_auth_group_access
-- ----------------------------
DROP TABLE IF EXISTS `admin_auth_group_access`;
CREATE TABLE `admin_auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=gbk;

-- ----------------------------
-- Table structure for admin_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `admin_auth_rule`;
CREATE TABLE `admin_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(6) DEFAULT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `controller` varchar(100) DEFAULT NULL COMMENT '控制器',
  `action` varchar(100) DEFAULT NULL COMMENT '方法',
  `cengji` char(1) DEFAULT '1' COMMENT '菜单层级',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for banner
-- ----------------------------
DROP TABLE IF EXISTS `banner`;
CREATE TABLE `banner` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `image` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for config
-- ----------------------------
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `descript` varchar(255) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `copyright` varchar(100) DEFAULT NULL,
  `tel` varchar(18) DEFAULT NULL,
  `fax` varchar(18) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `logo` varchar(30) DEFAULT NULL,
  `banner` varchar(30) DEFAULT NULL,
  `price` decimal(16,4) DEFAULT '1.0000',
  `total` int(11) DEFAULT '0' COMMENT '预设数量',
  `total1` int(11) DEFAULT '0' COMMENT '人数/2 * 6.25 每小时',
  `days` int(8) DEFAULT '0' COMMENT '预设天数',
  `users` int(8) DEFAULT '0' COMMENT '人数',
  `xishu` decimal(7,5) DEFAULT '1.00000' COMMENT '难度系数',
  `invite` int(5) DEFAULT '0' COMMENT '给自己返多少',
  `invite_dongjie` int(5) DEFAULT '0' COMMENT '注册送币冻结状态',
  `invite1` int(5) DEFAULT '0' COMMENT '给上级返多少',
  `invite2` int(5) DEFAULT '0' COMMENT '给上上级返多少',
  `register_suanli` int(10) DEFAULT '0' COMMENT '注册送算力',
  `invite1_suanli` int(5) DEFAULT '0' COMMENT '直推的人获得多少算力，即上级',
  `invite2_suanli` int(5) DEFAULT '0' COMMENT '简介推荐人获得算力',
  `login_suanli` int(10) DEFAULT '0' COMMENT '登录加算力',
  `block_renshu` int(4) DEFAULT '2' COMMENT '公式用 每多少人产生个区块',
  `block_num` varchar(10) DEFAULT '6.25' COMMENT '每个区块产生多少个数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for device
-- ----------------------------
DROP TABLE IF EXISTS `device`;
CREATE TABLE `device` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `max` int(2) DEFAULT '0' COMMENT '最多可绑定多少个',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态 1可用 0不可用',
  `yuanlibi` varchar(10) DEFAULT '0' COMMENT '原力币 绑定后给自己返多少个原力币',
  `yuanlibi_2` varchar(10) DEFAULT '0' COMMENT '不用',
  `suanli` varchar(10) DEFAULT '0' COMMENT '绑定设备给自己返多少个算力',
  `charge` varchar(10) DEFAULT '0' COMMENT '手续费多少才算激活 （pos）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for device_sn
-- ----------------------------
DROP TABLE IF EXISTS `device_sn`;
CREATE TABLE `device_sn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(2) DEFAULT NULL COMMENT '设备id',
  `sn` varchar(50) DEFAULT NULL COMMENT 'sn码',
  `mima` varchar(50) DEFAULT NULL COMMENT '密码',
  `status` tinyint(4) DEFAULT '1' COMMENT '1未用 0已用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sn` (`sn`),
  KEY `comp` (`device_id`,`sn`,`mima`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for device_xiaofei_log
-- ----------------------------
DROP TABLE IF EXISTS `device_xiaofei_log`;
CREATE TABLE `device_xiaofei_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_sn` varchar(50) DEFAULT NULL COMMENT '设备sn码',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `day` varchar(10) DEFAULT NULL,
  `time` varchar(10) DEFAULT NULL,
  `money` decimal(12,4) DEFAULT NULL,
  `fee` decimal(10,4) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0' COMMENT '0是未匹配 1是已匹配，2是无效',
  `jgmc` varchar(50) DEFAULT NULL COMMENT '机构名称',
  `zdpc` varchar(50) DEFAULT NULL COMMENT '终端批次',
  `jylx` varchar(20) DEFAULT NULL COMMENT '交易类型',
  `yhkh` varchar(50) DEFAULT NULL COMMENT '银行卡号',
  `sjhm` varchar(15) DEFAULT NULL COMMENT '手机号码',
  `khxm` varchar(30) DEFAULT NULL COMMENT '客户姓名',
  `sfzh` varchar(18) DEFAULT NULL COMMENT '身份证号',
  `sxfl` varchar(10) DEFAULT NULL COMMENT '手续费率',
  `gdsxf` varchar(10) DEFAULT NULL COMMENT '固定手续费',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_sn` (`order_sn`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for mycz
-- ----------------------------
DROP TABLE IF EXISTS `mycz`;
CREATE TABLE `mycz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `num` decimal(16,4) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for myinvite
-- ----------------------------
DROP TABLE IF EXISTS `myinvite`;
CREATE TABLE `myinvite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL COMMENT '来源自谁返的 ',
  `device_id` int(11) DEFAULT NULL COMMENT '这个对应我的设备id user_device',
  `type` tinyint(4) DEFAULT NULL COMMENT '1是原力币 2是算力',
  `num` decimal(10,4) DEFAULT '0.0000' COMMENT '0是未激活 1是已激活',
  `status` tinyint(4) DEFAULT NULL COMMENT '0未成功返 1成功返',
  `createdate` int(11) DEFAULT NULL,
  `channel` tinyint(4) DEFAULT '0' COMMENT '来源 1注册 2邀请好友 3消费4,登录5绑定设备',
  PRIMARY KEY (`id`),
  KEY `rex` (`userid`,`createdate`,`channel`),
  KEY `rex1` (`from_id`,`status`),
  KEY `device_id` (`device_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1488 DEFAULT CHARSET=utf8 COMMENT='邀请返利记录';

-- ----------------------------
-- Table structure for mytransfer
-- ----------------------------
DROP TABLE IF EXISTS `mytransfer`;
CREATE TABLE `mytransfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `peerid` int(11) DEFAULT NULL COMMENT '对方userid',
  `num` decimal(10,4) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `realname` varchar(30) DEFAULT NULL,
  `phone` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for myzc
-- ----------------------------
DROP TABLE IF EXISTS `myzc`;
CREATE TABLE `myzc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(6) NOT NULL,
  `address` varchar(100) DEFAULT NULL COMMENT '转出地址',
  `txid` varchar(200) DEFAULT NULL,
  `num` decimal(16,4) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0' COMMENT '0未到账 1到账 2是拒绝',
  `remark` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `address` (`address`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for myzr
-- ----------------------------
DROP TABLE IF EXISTS `myzr`;
CREATE TABLE `myzr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(6) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `txid` varchar(200) DEFAULT NULL,
  `num` decimal(16,4) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `zuhe1` (`txid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for sys_cookie
-- ----------------------------
DROP TABLE IF EXISTS `sys_cookie`;
CREATE TABLE `sys_cookie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `identifier` varchar(100) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `timeout` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`) USING BTREE,
  KEY `identifier` (`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=194 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for sys_fl_log
-- ----------------------------
DROP TABLE IF EXISTS `sys_fl_log`;
CREATE TABLE `sys_fl_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `nandu` varchar(20) DEFAULT NULL,
  `suanli` varchar(15) DEFAULT NULL,
  `num` decimal(15,8) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(2) DEFAULT '2' COMMENT '1是已收取 0是作废 2是待收取',
  `updatedate` int(11) DEFAULT '0' COMMENT '收取时间',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`,`status`,`createdate`)
) ENGINE=InnoDB AUTO_INCREMENT=2850 DEFAULT CHARSET=utf8 COMMENT='每天返利记录';

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `pid` int(6) DEFAULT '0',
  `username` varchar(30) NOT NULL,
  `phone` varchar(15) NOT NULL COMMENT '电话',
  `password` char(32) DEFAULT NULL,
  `paypassword` char(32) DEFAULT NULL,
  `realname` varchar(30) DEFAULT NULL COMMENT '姓名',
  `idcard` varchar(18) DEFAULT NULL COMMENT '身份证号',
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(2) DEFAULT '1',
  `country` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `is_cert` tinyint(4) DEFAULT '0' COMMENT '是否实名认证 1已实名认证',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  UNIQUE KEY `phone` (`phone`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user_certification
-- ----------------------------
DROP TABLE IF EXISTS `user_certification`;
CREATE TABLE `user_certification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `zheng` varchar(50) DEFAULT NULL,
  `fan` varchar(50) DEFAULT NULL,
  `shou` varchar(50) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0' COMMENT '0审核中 1已审核',
  `realname` varchar(30) DEFAULT NULL,
  `idcard` char(18) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user_coin
-- ----------------------------
DROP TABLE IF EXISTS `user_coin`;
CREATE TABLE `user_coin` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `userid` int(6) DEFAULT NULL,
  `lth` decimal(20,8) unsigned DEFAULT '0.00000000',
  `lthd` decimal(20,8) unsigned DEFAULT '0.00000000',
  `lthb` varchar(100) DEFAULT '',
  `lthz` decimal(20,8) unsigned DEFAULT '0.00000000' COMMENT '我的算力',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`) USING BTREE,
  KEY `lthb` (`lthb`)
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user_device
-- ----------------------------
DROP TABLE IF EXISTS `user_device`;
CREATE TABLE `user_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `device_id` tinyint(4) DEFAULT NULL COMMENT '设备类型id ',
  `sn` varchar(50) DEFAULT NULL,
  `mima` varchar(50) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0' COMMENT '0是未激活 1是已激活',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user_qianbao
-- ----------------------------
DROP TABLE IF EXISTS `user_qianbao`;
CREATE TABLE `user_qianbao` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `userid` int(6) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `createdate` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user_shenqing
-- ----------------------------
DROP TABLE IF EXISTS `user_shenqing`;
CREATE TABLE `user_shenqing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `shr` varchar(50) DEFAULT NULL COMMENT '收货人',
  `lxfs` varchar(20) DEFAULT NULL COMMENT '联系方式',
  `area` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `default` tinyint(2) DEFAULT '0' COMMENT '默认 1',
  `createdate` int(11) DEFAULT NULL,
  `type` tinyint(2) DEFAULT NULL COMMENT '类型 1是pos机 2是路由器',
  `status` tinyint(2) DEFAULT '2' COMMENT '2处理中 1已处理  0已作废',
  `kuaidi` varchar(50) DEFAULT '' COMMENT '快递公司',
  `danhao` varchar(50) DEFAULT '' COMMENT '快递单号',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`,`status`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COMMENT='pos路由器申请记录';
