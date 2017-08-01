ALTER TABLE `#__complaints` ADD COLUMN `gbv` int(4) NOT NULL default 0 AFTER `gender`;
ALTER TABLE `#__complaints` ADD COLUMN `gbv_type` ENUM('', 'rape', 'sexual_assault', 'physical_assault', 'forced_marriage', 'denial_of_resources', 'psychological_emotional_abuse') NOT NULL default '' AFTER `gbv`;
ALTER TABLE `#__complaints` ADD COLUMN `gbv_relation` ENUM('0', '1', 'unknown') NOT NULL default 'unknown' AFTER `gbv_type`;
ALTER TABLE `#__complaints` MODIFY COLUMN `name` varchar(1000) NOT NULL DEFAULT '';
ALTER TABLE `#__complaints` MODIFY COLUMN `email` varchar(1000) NOT NULL DEFAULT '';
ALTER TABLE `#__complaints` MODIFY COLUMN `phone` varchar(1000) NOT NULL DEFAULT '';
ALTER TABLE `#__complaints` MODIFY COLUMN `address` varchar(2500) NOT NULL DEFAULT '';
ALTER TABLE `#__complaints` MODIFY COLUMN `ip_address` varchar(150) NOT NULL DEFAULT '';