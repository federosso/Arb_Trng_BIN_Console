-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: Apr 06, 2021 alle 02:07
-- Versione del server: 5.0.92-50-log
-- Versione PHP: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `arb_binance`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `arb_log_balance`
--

CREATE TABLE IF NOT EXISTS `arb_log_balance` (
  `id_lo` int(11) NOT NULL auto_increment,
  `data_a` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `info` blob,
  PRIMARY KEY  (`id_lo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=150 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `arb_log_best_candidate`
--

CREATE TABLE IF NOT EXISTS `arb_log_best_candidate` (
  `id_lo` int(11) NOT NULL auto_increment,
  `data_bc` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `ord_vol_1` double(18,8) NOT NULL,
  `ord_vol_2` double(18,8) NOT NULL,
  `ord_vol_3` double(18,8) NOT NULL,
  `diff_vol` double(18,8) NOT NULL,
  `status` int(1) NOT NULL,
  `pair_1` varchar(15) collate utf8_bin default NULL,
  `pair_2` varchar(15) collate utf8_bin NOT NULL,
  `pair_3` varchar(15) collate utf8_bin NOT NULL,
  `pair_price_1` double(18,8) NOT NULL,
  `pair_price_2` double(18,8) NOT NULL,
  `pair_price_3` double(18,8) NOT NULL,
  `str` text collate utf8_bin NOT NULL,
  `x_2_3` double(18,8) NOT NULL,
  `diff_prz` float(18,8) NOT NULL,
  `min_trans` text collate utf8_bin NOT NULL,
  `vol_check` text collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id_lo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=14578 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `arb_log_errors`
--

CREATE TABLE IF NOT EXISTS `arb_log_errors` (
  `id_lo` int(11) NOT NULL auto_increment,
  `data_a` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `info` text collate utf8_bin,
  PRIMARY KEY  (`id_lo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `arb_log_orders`
--

CREATE TABLE IF NOT EXISTS `arb_log_orders` (
  `id_lo` int(11) NOT NULL auto_increment,
  `data_a` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `triangle` text collate utf8_bin NOT NULL,
  `trng_status` tinyint(1) NOT NULL,
  `ord_id` int(11) default NULL,
  `pair` varchar(15) collate utf8_bin NOT NULL,
  `step` tinyint(1) NOT NULL,
  `side` varchar(4) collate utf8_bin NOT NULL,
  `order_type` varchar(6) collate utf8_bin NOT NULL,
  `price` double(18,8) NOT NULL,
  `amount` double(18,8) NOT NULL,
  `cost` double(18,8) NOT NULL,
  `filled` double(18,8) NOT NULL,
  `remaining` double(18,8) NOT NULL,
  `status` text collate utf8_bin NOT NULL,
  `status2` text collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id_lo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `arb_log_timer_ms4`
--

CREATE TABLE IF NOT EXISTS `arb_log_timer_ms4` (
  `id_lo` int(11) NOT NULL auto_increment,
  `data_a` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `requestor` text collate utf8_bin,
  `time_set` text collate utf8_bin NOT NULL,
  `time_left` text collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id_lo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=14588 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `asset_pairs_all_pairs`
--

CREATE TABLE IF NOT EXISTS `asset_pairs_all_pairs` (
  `id_ap` int(11) NOT NULL auto_increment,
  `base` varchar(7) collate utf8_bin NOT NULL,
  `asset_name` varchar(15) collate utf8_bin default NULL,
  `quote` varchar(7) collate utf8_bin NOT NULL,
  `base_1` varchar(7) collate utf8_bin NOT NULL,
  `asset_name_1` varchar(15) collate utf8_bin NOT NULL,
  `quote_1` varchar(7) collate utf8_bin NOT NULL,
  `base_2` varchar(7) collate utf8_bin NOT NULL,
  `asset_name_2` varchar(15) collate utf8_bin NOT NULL,
  `quote_2` varchar(7) collate utf8_bin NOT NULL,
  `status` int(1) NOT NULL,
  `ordermin` float(18,8) NOT NULL,
  `ordermin_1` float(18,8) NOT NULL,
  `ordermin_2` float(18,8) NOT NULL,
  `prc_base` varchar(1) collate utf8_bin NOT NULL,
  `prc_base_1` varchar(1) collate utf8_bin NOT NULL,
  `prc_base_2` varchar(1) collate utf8_bin NOT NULL,
  `prc_quote` varchar(1) collate utf8_bin NOT NULL,
  `prc_quote_1` varchar(1) collate utf8_bin NOT NULL,
  `prc_quote_2` varchar(1) collate utf8_bin NOT NULL,
  `prc_amount` varchar(1) collate utf8_bin NOT NULL,
  `prc_amount_1` varchar(1) collate utf8_bin NOT NULL,
  `prc_amount_2` varchar(1) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id_ap`),
  KEY `base` (`base`),
  KEY `quote` (`quote`),
  KEY `asset_name` (`asset_name`),
  KEY `base_1` (`base_1`),
  KEY `asset_name_1` (`asset_name_1`),
  KEY `quote_1` (`quote_1`),
  KEY `base_2` (`base_2`),
  KEY `asset_name_2` (`asset_name_2`),
  KEY `quote_2` (`quote_2`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=5789 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `asset_pairs_b_q`
--

CREATE TABLE IF NOT EXISTS `asset_pairs_b_q` (
  `id_ap` int(11) NOT NULL auto_increment,
  `asset_name` varchar(15) collate utf8_bin NOT NULL,
  `base` varchar(7) collate utf8_bin NOT NULL,
  `quote` varchar(7) collate utf8_bin NOT NULL,
  `ordermin` float(18,8) NOT NULL,
  `prc_base` varchar(1) collate utf8_bin NOT NULL,
  `prc_quote` varchar(1) collate utf8_bin NOT NULL,
  `prc_amount` varchar(1) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id_ap`),
  KEY `base` (`base`),
  KEY `quote` (`quote`),
  KEY `asset_name_k` (`asset_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1009 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
