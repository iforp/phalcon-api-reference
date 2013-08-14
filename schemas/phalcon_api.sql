-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 03 2013 г., 19:27
-- Версия сервера: 5.5.30-log
-- Версия PHP: 5.4.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `phalcon_api`
--

-- --------------------------------------------------------

--
-- Структура таблицы `arguments`
--

CREATE TABLE IF NOT EXISTS `arguments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `default_value` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `ordering` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_arguments_methods1_idx` (`method_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `classes`
--

CREATE TABLE IF NOT EXISTS `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` char(10) NOT NULL,
  `namespace` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `type` enum('class','final','abstract','interface') NOT NULL,
  `file` varchar(255) NOT NULL,
  `docs` text,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `fk_classes_versions_idx` (`version`),
  KEY `namespace` (`namespace`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `inheritance`
--

CREATE TABLE IF NOT EXISTS `inheritance` (
  `class_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  KEY `fk_inheritance_classes1_idx` (`class_id`),
  KEY `fk_inheritance_classes2_idx` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `methods`
--

CREATE TABLE IF NOT EXISTS `methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('constant','property','method') NOT NULL,
  `visibility` enum('public','protected','private') NOT NULL,
  `is_static` tinyint(1) NOT NULL,
  `returns` varchar(255) NOT NULL,
  `docs` text,
  `example` text,
  `line` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_methods_classes1_idx` (`class_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `versions`
--

CREATE TABLE IF NOT EXISTS `versions` (
  `version` char(10) NOT NULL,
  `changelog` text,
  `notes` text,
  `github_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `arguments`
--
ALTER TABLE `arguments`
  ADD CONSTRAINT `fk_arguments_methods1` FOREIGN KEY (`method_id`) REFERENCES `methods` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classes_versions` FOREIGN KEY (`version`) REFERENCES `versions` (`version`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `inheritance`
--
ALTER TABLE `inheritance`
  ADD CONSTRAINT `fk_inheritance_classes1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_inheritance_classes2` FOREIGN KEY (`parent_id`) REFERENCES `classes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `methods`
--
ALTER TABLE `methods`
  ADD CONSTRAINT `fk_methods_classes1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
