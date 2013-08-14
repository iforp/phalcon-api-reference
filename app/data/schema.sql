DROP TABLE IF EXISTS `{{prefix}}arguments`;
CREATE TABLE `{{prefix}}arguments` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`method_id` int(11) NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` varchar(255) DEFAULT NULL,
	`is_optional` tinyint(1) NOT NULL,
	`default_value` varchar(255) DEFAULT NULL,
	`description` varchar(255) DEFAULT NULL,
	`ordering` tinyint(4) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `name` (`name`),
	KEY `method_id` (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{{prefix}}classes`;
CREATE TABLE `{{prefix}}classes` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`version` char(10) NOT NULL,
	`namespace` varchar(255) NOT NULL DEFAULT '',
	`name` varchar(255) NOT NULL,
	`is_interface` tinyint(1) NOT NULL,
	`is_abstract` tinyint(1) NOT NULL,
	`is_final` tinyint(1) NOT NULL,
	`file` varchar(255) NOT NULL COLLATE 'utf8_bin',
	`docs` text,
	`example` text,
	`initializer_line` smallint(5) unsigned DEFAULT NULL,
	`extends` varchar(255) DEFAULT NULL,
	`implements` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `namespace` (`namespace`),
	KEY `name` (`name`),
	KEY `version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{{prefix}}constants`;
CREATE TABLE `{{prefix}}constants` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`class_id` int(11) NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` varchar(255) DEFAULT NULL,
	`value` varchar(255) NOT NULL,
	`docs` text,
	`line` smallint(5) unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `name` (`name`),
	KEY `class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{{prefix}}methods`;
CREATE TABLE `{{prefix}}methods` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`class_id` int(11) NOT NULL,
	`name` varchar(255) NOT NULL,
	`visibility` enum('public','protected','private') NOT NULL,
	`is_static` tinyint(1) NOT NULL,
	`is_abstract` tinyint(1) NOT NULL,
	`is_final` tinyint(1) NOT NULL,
	`returns` varchar(255) DEFAULT NULL,
	`returns_docs` varchar(255) DEFAULT NULL,
	`docs` text,
	`example` text,
	`line` smallint(5) unsigned DEFAULT NULL,
	`defined_by` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `name` (`name`),
	KEY `class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{{prefix}}properties`;
CREATE TABLE `{{prefix}}properties` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`class_id` int(11) NOT NULL,
	`name` varchar(255) NOT NULL,
	`type` varchar(255) DEFAULT NULL,
	`visibility` enum('public','protected','private') NOT NULL,
	`is_static` tinyint(1) NOT NULL,
	`access` enum('read','write','') NOT NULL DEFAULT '',
	`getter` varchar(255) DEFAULT NULL,
	`setter` varchar(255) DEFAULT NULL,
	`docs` text,
	`line` smallint(5) unsigned DEFAULT NULL,
	`defined_by` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `name` (`name`),
	KEY `class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{{prefix}}versions`;
CREATE TABLE `{{prefix}}versions` (
	`version` char(10) NOT NULL,
	`changelog` mediumtext,
	PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `{{prefix}}translations`;
CREATE TABLE IF NOT EXISTS `{{prefix}}translations` (
	`lang` char(2) NOT NULL,
	`version` char(10) DEFAULT NULL,
	`class_id` int(11) DEFAULT NULL,
	`constant_id` int(11) DEFAULT NULL,
	`property_id` int(11) DEFAULT NULL,
	`method_id` int(11) DEFAULT NULL,
	`argument_id` int(11) DEFAULT NULL,
	`text` text NOT NULL,
	KEY `version` (`version`),
	KEY `class_id` (`class_id`),
	KEY `constant_id` (`constant_id`),
	KEY `property_id` (`property_id`),
	KEY `method_id` (`method_id`),
	KEY `argument_id` (`argument_id`),
	KEY `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `{{prefix}}arguments`
  ADD CONSTRAINT `fk_arguments_method_id` FOREIGN KEY (`method_id`) REFERENCES `{{prefix}}methods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{{prefix}}classes`
  ADD CONSTRAINT `fk_classes_versions_version` FOREIGN KEY (`version`) REFERENCES `{{prefix}}versions` (`version`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{{prefix}}classes_inheritance`
  ADD CONSTRAINT `fk_inheritance_classes_class_id` FOREIGN KEY (`class_id`) REFERENCES `{{prefix}}classes` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_inheritance_classes_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `{{prefix}}classes` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{{prefix}}constants`
  ADD CONSTRAINT `fk_constants_class_id` FOREIGN KEY (`class_id`) REFERENCES `{{prefix}}classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{{prefix}}methods`
  ADD CONSTRAINT `fk_methods_class_id` FOREIGN KEY (`class_id`) REFERENCES `{{prefix}}classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{{prefix}}properties`
  ADD CONSTRAINT `fk_properties_class_id` FOREIGN KEY (`class_id`) REFERENCES `{{prefix}}classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{{prefix}}translations`
  ADD CONSTRAINT `fk_translations_version` FOREIGN KEY (`version`) REFERENCES `{{prefix}}versions` (`version`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_translations_class` FOREIGN KEY (`class_id`) REFERENCES `{{prefix}}classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_translations_constant` FOREIGN KEY (`constant_id`) REFERENCES `{{prefix}}constants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_translations_property` FOREIGN KEY (`property_id`) REFERENCES `{{prefix}}properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_translations_method` FOREIGN KEY (`method_id`) REFERENCES `{{prefix}}methods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_translations_argument` FOREIGN KEY (`argument_id`) REFERENCES `{{prefix}}arguments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;