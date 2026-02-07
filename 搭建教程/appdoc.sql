-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2022-08-11 23:10:09
-- 服务器版本： 5.6.50-log
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `appdoc`
--

-- --------------------------------------------------------

--
-- 表的结构 `code`
--

CREATE TABLE IF NOT EXISTS `code` (
  `admin` varchar(20) NOT NULL,
  `id` int(11) NOT NULL,
  `title` varchar(60) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` varchar(20) NOT NULL,
  `view` int(10) NOT NULL DEFAULT '0',
  `check` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `course`
--

CREATE TABLE IF NOT EXISTS `course` (
  `id` int(11) NOT NULL,
  `admin` varchar(20) NOT NULL,
  `title` varchar(60) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` varchar(20) NOT NULL,
  `view` int(10) NOT NULL DEFAULT '0',
  `check` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `delay`
--

CREATE TABLE IF NOT EXISTS `delay` (
  `admin` varchar(15) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `delay`
--

INSERT INTO `delay` (`admin`, `time`) VALUES
('123456', 1660229082);

-- --------------------------------------------------------

--
-- 表的结构 `discuss`
--

CREATE TABLE IF NOT EXISTS `discuss` (
  `id` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `db` varchar(20) NOT NULL,
  `admin` varchar(20) NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `discuss_dly`
--

CREATE TABLE IF NOT EXISTS `discuss_dly` (
  `admin` varchar(15) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `interact`
--

CREATE TABLE IF NOT EXISTS `interact` (
  `id` int(11) NOT NULL,
  `title` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` varchar(20) NOT NULL,
  `admin` varchar(20) NOT NULL,
  `view` int(10) NOT NULL DEFAULT '0',
  `check` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `interact`
--

INSERT INTO `interact` (`id`, `title`, `content`, `time`, `admin`, `view`, `check`) VALUES
(1, '测试', '123', '1660229082', '123456', 2, 1);

-- --------------------------------------------------------

--
-- 表的结构 `moreCode`
--

CREATE TABLE IF NOT EXISTS `moreCode` (
  `id` int(11) NOT NULL,
  `admin` varchar(20) NOT NULL,
  `title` varchar(60) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` varchar(20) NOT NULL,
  `view` int(10) NOT NULL DEFAULT '0',
  `check` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `online`
--

CREATE TABLE IF NOT EXISTS `online` (
  `id` int(11) NOT NULL,
  `admin` varchar(20) NOT NULL,
  `user` varchar(20) NOT NULL,
  `time` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ranBox`
--

CREATE TABLE IF NOT EXISTS `ranBox` (
  `id` int(11) NOT NULL,
  `admin` varchar(20) NOT NULL,
  `user` varchar(20) NOT NULL,
  `sex` int(11) NOT NULL,
  `name` text NOT NULL,
  `introduce` text NOT NULL,
  `portrait` varchar(100) NOT NULL,
  `contact` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `vip_km`
--

CREATE TABLE IF NOT EXISTS `vip_km` (
  `km` varchar(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `code`
--
ALTER TABLE `code`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delay`
--
ALTER TABLE `delay`
  ADD UNIQUE KEY `admin` (`admin`);

--
-- Indexes for table `discuss`
--
ALTER TABLE `discuss`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_3` (`id`),
  ADD KEY `id` (`id`),
  ADD KEY `id_2` (`id`),
  ADD KEY `id_4` (`id`);

--
-- Indexes for table `discuss_dly`
--
ALTER TABLE `discuss_dly`
  ADD PRIMARY KEY (`admin`);

--
-- Indexes for table `interact`
--
ALTER TABLE `interact`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `moreCode`
--
ALTER TABLE `moreCode`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online`
--
ALTER TABLE `online`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ranBox`
--
ALTER TABLE `ranBox`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vip_km`
--
ALTER TABLE `vip_km`
  ADD PRIMARY KEY (`km`),
  ADD UNIQUE KEY `km` (`km`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `code`
--
ALTER TABLE `code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `discuss`
--
ALTER TABLE `discuss`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `interact`
--
ALTER TABLE `interact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `moreCode`
--
ALTER TABLE `moreCode`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `online`
--
ALTER TABLE `online`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ranBox`
--
ALTER TABLE `ranBox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
