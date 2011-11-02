CREATE TABLE IF NOT EXISTS `#__complaints` (
  `id` int(11) NOT NULL auto_increment,
  `message_id` varchar(20) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `phone` varchar(100) NOT NULL default '',
  `ip_address` varchar(15) NOT NULL default '',
  `editor_id` int(11) NOT NULL default 0,
  `raw_message` text NOT NULL default '',
  `processed_message` text,
  `complaint_area_id` int(11) NOT NULL default 0,
  `date_received` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_processed` datetime default null,
  `date_resolved` datetime default null,
  `resolver_id` int(11) NOT NULL default 0,
  `resolution` text,
  `message_source` ENUM('SMS', 'Email', 'Website'),
  `message_priority` ENUM('Low', 'Medium', 'High'),
  `confirmed_closed` ENUM('Y', 'N') not null default 'N',
  `comments` text,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `#__complaints` MODIFY `message_source` ENUM('SMS', 'Email', 'Website', 'Telephone Call', 'Personal Visit', 'Field Visit by Project Staff', 'Other');
ALTER TABLE `#__complaints` ADD COLUMN `address` varchar(250) NOT NULL default '' AFTER `phone`;
ALTER TABLE `#__complaints` ADD COLUMN `location` varchar(250) NOT NULL default '' AFTER `processed_message`;
ALTER TABLE `#__complaints` ADD COLUMN `contract_id` int(11) NOT NULL default 0 AFTER `processed_message`;
ALTER TABLE `#__complaints` ADD COLUMN `support_group_id` int(11) NOT NULL default 0 AFTER `contract_id`;
ALTER TABLE `#__complaints` ADD COLUMN `preferred_contact` ENUM('', 'Email', 'SMS', 'Telephone Call') AFTER `ip_address`;

CREATE TABLE IF NOT EXISTS `#__complaint_message_ids` (
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__complaint_notifications` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default 0,
  `action` varchar(100) NOT NULL default '',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__complaint_pictures` (
  `id` int(11) NOT NULL auto_increment,
  `complaint_id` int(11) NOT NULL default 0,
  `path` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__complaint_contracts` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `section_id` int(11) NOT NULL default 0,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `#__complaint_contracts` ADD COLUMN `contractors` text AFTER `name`;
ALTER TABLE `#__complaint_contracts` ADD COLUMN `end_date` datetime NOT NULL default '0000-00-00 00:00:00' AFTER `name`;
ALTER TABLE `#__complaint_contracts` ADD COLUMN `start_date` datetime NOT NULL default '0000-00-00 00:00:00' AFTER `name`;
ALTER TABLE `#__complaint_contracts` ADD COLUMN `contract_id` varchar(25) NOT NULL default '' AFTER `name`;

CREATE TABLE IF NOT EXISTS `#__complaint_sections` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `polygon` text,
  `polyline` text,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__complaint_support_groups` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__complaint_support_groups_users_map` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default 0,
  `user_id` int(11) NOT NULL default 0,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `#__complaint_notifications` VALUES (null, 0, 'CLS installed', now(), 'System installed or upgraded');

CREATE TABLE IF NOT EXISTS `#__complaint_message_queue` (
  `id` int(11) NOT NULL auto_increment,
  `complaint_id` int(11) NOT NULL default 0,
  `msg_from` varchar(20) NOT NULL default '',
  `msg_to` varchar(20) NOT NULL default '',
  `msg` text,
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `msg_type` ENUM('Processed', 'Resolved') default 'Processed',
  `status` ENUM('Sent', 'Pending', 'Outgoing', 'Failed') default 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `#__complaint_message_queue` MODIFY `msg_type` ENUM('Processed', 'Resolved', 'Notification', 'Acknowledgement') default 'Processed';

CREATE TABLE IF NOT EXISTS `#__complaint_areas` (
  `id` int(11) NOT NULL auto_increment,
  `area` varchar(40),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

REPLACE INTO `#__complaint_areas` VALUES (1, 'Environment'), (2, 'Land Acquisition and Resettlement'), (3, 'Compensation'), (4, 'Engineering'), (5, 'Traffic'), (6, 'Management'), (7, 'Safety'), (8, 'HIV/AIDS'), (9, 'Gender'), (10, 'Employment Law'), (11, 'Other');