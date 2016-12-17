CREATE TABLE IF NOT EXISTS `#__complaints` (
  `id` int(11) NOT NULL auto_increment,
  `message_id` varchar(20) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
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
ALTER TABLE `#__complaints` ADD COLUMN `beneficiary_id` varchar(250) NOT NULL default '' AFTER `location`;
ALTER TABLE `#__complaints` ADD COLUMN `building_id` varchar(250) NOT NULL default '' AFTER `beneficiary_id`;
ALTER TABLE `#__complaints` ADD COLUMN `contract_id` int(11) NOT NULL default 0 AFTER `processed_message`;
ALTER TABLE `#__complaints` ADD COLUMN `support_group_id` int(11) NOT NULL default 0 AFTER `contract_id`;
ALTER TABLE `#__complaints` ADD COLUMN `preferred_contact` ENUM('', 'Email', 'SMS', 'Telephone Call') AFTER `ip_address`;
ALTER TABLE `#__complaints` ADD COLUMN `date_closed` datetime default NULL AFTER `confirmed_closed`;
ALTER TABLE `#__complaints` ADD COLUMN `related_to_pb` int(4) NOT NULL default 0 AFTER `processed_message`;
ALTER TABLE `#__complaints` ADD COLUMN `issue_type` int(4) NOT NULL default 1 AFTER `related_to_pb`;
ALTER TABLE `#__complaints` ADD COLUMN `gender` ENUM('Male', 'Female', 'Not Specified') NOT NULL default 'Not Specified' AFTER `related_to_pb`;

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
ALTER TABLE `#__complaint_contracts` ADD COLUMN `email` varchar(100) NOT NULL default '' AFTER `contractors`;
ALTER TABLE `#__complaint_contracts` ADD COLUMN `phone` varchar(20) NOT NULL default '' AFTER `email`;
ALTER TABLE `#__complaint_contracts` ADD UNIQUE KEY `name` (`name`);
ALTER TABLE `#__complaint_contracts` ADD UNIQUE KEY `contract_id` (`contract_id`);

CREATE TABLE IF NOT EXISTS `#__complaint_sections` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `polygon` text,
  `polyline` text,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `#__complaint_sections` ADD UNIQUE KEY `name` (`name`);

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

ALTER TABLE `#__complaint_message_queue` MODIFY `msg_type` ENUM('Processed', 'Resolved', 'Notification', 'Acknowledgment') default 'Processed';

CREATE TABLE IF NOT EXISTS `#__complaint_areas` (
  `id` int(11) NOT NULL auto_increment,
  `area` varchar(40),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

REPLACE INTO `#__complaint_areas` VALUES (1, 'Environment'), (2, 'Land Acquisition and Resettlement'), (3, 'Compensation'), (4, 'Engineering'), (5, 'Traffic'), (6, 'Project Management'), (7, 'Safety'), (8, 'HIV/AIDS'), (9, 'Gender'), (10, 'Employment Law'), (11, 'Other'), (12, 'Beneficiary Classification'), (13, 'Noise'), (14, 'Solid Waste');

ALTER TABLE `#__complaint_areas` ADD COLUMN `description` text AFTER `area`;
ALTER TABLE `#__complaint_areas` ADD UNIQUE KEY `area` (`area`);

REPLACE INTO `#__complaint_support_groups` (id, name) VALUES (1, 'Environment'), (2, 'Land Acquisition and Resettlement'), (3, 'Compensation'), (4, 'Engineering'), (5, 'Traffic'), (6, 'Project Management'), (7, 'Safety'), (8, 'HIV/AIDS'), (9, 'Gender'), (10, 'Employment Law'), (11, 'Other'), (12, 'Beneficiary Classification'), (13, 'Noise'), (14, 'Solid Waste');

REPLACE INTO `#__users` (`id`, `name`, `username`, `email`, `password`, `block`, `sendEmail`, `registerDate`, `lastvisitDate`, `activation`, `params`, `lastResetTime`, `resetCount`, `otpKey`, `otep`, `requireReset`) VALUES
(100377, 'Complaint - Administrator', 'Complaint_Admin', 'complaint_admin@isafeguards.com', '$2y$10$bHT9Eh8OZYNeKRNObLz2SebNW3nxjUfK2pV6SFiseRv3XKjPnpjc2', 0, 0, '2014-11-14 05:05:22', '2014-11-21 01:07:50', '', '{"admin_style":"","admin_language":"","language":"","editor":"","helpsite":"","timezone":"","organization":"","area":"","telephone":"","role":"Level 1","receive_notifications":"1","receive_by_email":"1","receive_by_sms":"0"}', '0000-00-00 00:00:00', 0, '', '', 0),
(100378, 'Complaint - Safeguards', 'Complaint_Safeguards', 'complaint_safeguards@isafeguards.com', '$2y$10$BzvhXD94qqdhVmmZerngwOD7mHaG92YnGV0TQkmSC9vlZAzXLg/du', 0, 0, '2014-11-14 05:05:59', '0000-00-00 00:00:00', '', '{"admin_style":"","admin_language":"","language":"","editor":"","helpsite":"","timezone":"","organization":"","area":"","telephone":"","role":"Level 2","receive_notifications":"1","receive_by_email":"1","receive_by_sms":"0"}', '0000-00-00 00:00:00', 0, '', '', 0),
(100379, 'Complaint - Supervisor', 'Complaint_Supervisor', 'complaint_supervisor@isafeguards.com', '$2y$10$FRORxroQWr1XVzGUWkxP6eJITLVhJEpWuVuPSba/tV6H572p78.My', 0, 0, '2014-11-14 05:07:48', '0000-00-00 00:00:00', '', '{"admin_style":"","admin_language":"","language":"","editor":"","helpsite":"","timezone":"","organization":"","area":"","telephone":"","role":"Supervisor","receive_notifications":"1","receive_by_email":"1","receive_by_sms":"0"}', '0000-00-00 00:00:00', 0, '', '', 0),
(100380, 'Complaint - Contracts', 'Complaint_Contracts', 'complaint_contracts@isafeguards.com', '$2y$10$2OPVr6D9RNJP30HGofF3cuVCds8KBUiwEwvDMSeeHkH2iP1b/qrYy', 0, 0, '2014-11-14 05:33:28', '0000-00-00 00:00:00', '', '{"admin_style":"","admin_language":"","language":"","editor":"","helpsite":"","timezone":"","organization":"","area":"","telephone":"","role":"Level 2","receive_notifications":"1","receive_by_email":"1","receive_by_sms":"0"}', '0000-00-00 00:00:00', 0, '', '', 0),
(100381, 'Complaint - Accountant', 'Complaint_Accountant', 'complaint_accountant@isafeguards.com', '$2y$10$fa42KSkR8BuakbgOm8hWoOdgKgUQ07Srt7Rc1Uful7PkV23kXUmIm', 0, 0, '2014-11-14 05:34:23', '0000-00-00 00:00:00', '', '{"admin_style":"","admin_language":"","language":"","editor":"","helpsite":"","timezone":"","organization":"","area":"","telephone":"","role":"Level 2","receive_notifications":"1","receive_by_email":"1","receive_by_sms":"0"}', '0000-00-00 00:00:00', 0, '', '', 0);

INSERT INTO `#__user_usergroup_map` (`user_id`, `group_id`) VALUES (100377, 6), (100378, 6), (100379, 6), (100380, 6), (100381, 6);

REPLACE INTO `#__menu` (`id`, `menutype`, `title`, `alias`, `note`, `path`, `link`, `type`, `published`, `parent_id`, `level`, `component_id`, `checked_out`, `checked_out_time`, `browserNav`, `access`, `img`, `template_style_id`, `params`, `lft`, `rgt`, `home`, `language`, `client_id`) VALUES (100128, 'mainmenu', 'Make a Complaint', 'make-a-complaint', '', 'make-a-complaint', 'index.php?option=com_cls&view=complain', 'component', 1, 1, 1, 10006, 0, '0000-00-00 00:00:00', 0, 1, '', 0, '{"menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_text":1,"page_title":"","show_page_heading":0,"page_heading":"","pageclass_sfx":"","menu-meta_description":"","menu-meta_keywords":"","robots":"","secure":0}', 15, 16, 0, '*', 0);
REPLACE INTO `#__menu` (`id`, `menutype`, `title`, `alias`, `note`, `path`, `link`, `type`, `published`, `parent_id`, `level`, `component_id`, `checked_out`, `checked_out_time`, `browserNav`, `access`, `img`, `template_style_id`, `params`, `lft`, `rgt`, `home`, `language`, `client_id`) VALUES (100129, 'mainmenu', 'Complaint Statistics', 'complaint-statistics', '', 'complaint-statistics', 'index.php?option=com_cls&view=reports', 'component', 1, 1, 1, 10006, 0, '0000-00-00 00:00:00', 0, 1, '', 0, '{"show_summary":"1","show_chart":"1","show_map":"1","show_summary_table":"1","menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_text":1,"page_title":"","show_page_heading":0,"page_heading":"","pageclass_sfx":"","menu-meta_description":"","menu-meta_keywords":"","robots":"","secure":0}', 17, 18, 0, '*', 0);

REPLACE INTO `#__complaint_support_groups_users_map` (`id`, `group_id`, `user_id`) VALUES (9, 1, 100378),(2, 3, 100381),(4, 5, 100380),(5, 6, 100380),(6, 7, 100380),(7, 10, 100380),(8, 12, 100378),(10, 9, 100378),(11, 8, 100378),(12, 2, 100378),(13, 13, 100378),(14, 14, 100378);