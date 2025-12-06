-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 24-Out-2019 às 06:37
-- Versão do servidor: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `bancoverdanna`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `clientes`
--

CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `data` date NOT NULL,
  `num_pessoas` int(11) NOT NULL,
  `horario` time NOT NULL,
  `telefone` int(11) NOT NULL,
  `telefone2` int(11) NOT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `forma_pagamento` varchar(50) NOT NULL,
  `preco_rodizio` varchar(20) NOT NULL,
  `num_mesa` int(3) NOT NULL,
  `observacoes` varchar(300) NOT NULL,
  `data_emissao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=101 ;

--
-- Extraindo dados da tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `data`, `num_pessoas`, `horario`, `telefone`, `telefone2`, `tipo_evento`, `forma_pagamento`, `preco_rodizio`, `num_mesa`, `observacoes`, `data_emissao`) VALUES
(61, 'Marciel Rossetto', '2018-09-11', 12, '12:30:00', 2147483647, 0, '', '', '0', 0, '', '2018-09-11 02:02:02'),
(62, 'KHAUAN DA SILVA ROSSETTO', '2018-09-13', 12, '20:00:00', 2147483647, 0, '', '', '0', 0, '', '2018-09-11 02:02:22'),
(64, 'joana', '2018-09-13', 23, '23:34:00', 234234, 2147483647, 'casamento', 'individual', '0', 0, '', '2018-09-12 01:34:58'),
(65, 'khauan', '0000-00-00', 25, '00:00:23', 45454564, 456456, 'aniversario', 'individual', '0', 0, '', '2018-09-12 01:58:43'),
(66, 'marciel', '2018-09-11', 23, '23:23:00', 23234234, 234234, 'casamento', 'outros', '0', 0, '', '2018-09-12 20:04:09'),
(67, 'joana', '2018-09-12', 12, '12:12:00', 121212, 0, 'confraternizacao', 'unica_individual', '0', 0, '', '2018-09-12 20:10:16'),
(68, 'marciel', '2018-09-17', 20, '20:30:00', 2147483647, 0, 'aniversario', 'individual', '0', 0, '', '2018-09-17 00:41:00'),
(69, 'karol', '2018-03-21', 21, '20:30:00', 2136366969, 996329658, 'aniversario', 'individual', '0', 0, '', '2018-09-21 00:51:27'),
(72, 'KHAUAN DA SILVA ROSSETTO', '2018-11-29', 21, '12:00:00', 2147483647, 0, '', '', '0', 0, '', '2018-11-29 02:18:45'),
(73, 'Marciel Rossetto', '2018-11-29', 45, '18:30:00', 2147483647, 0, '', '', '0', 0, '', '2018-11-29 02:19:03'),
(75, 'Marciel Rossetto', '2018-11-30', 23, '23:33:00', 2147483647, 0, '', '', '0', 0, '', '2018-11-29 23:21:46'),
(77, 'joao', '2018-11-29', 41, '17:02:00', 32232323, 234354343, '', '', '0', 0, '234234234', '2018-11-30 09:25:27'),
(78, 'FRANCILENE DE RAUJO SILVA', '2018-11-30', 100, '12:00:00', 0, 0, '', '', '0', 0, '', '2018-11-30 09:25:51'),
(79, 'jose', '2018-11-30', 255, '19:00:00', 5656, 5656, '', '', '0', 0, '5656', '2018-11-30 09:26:13'),
(80, 'Marciel Rossetto', '2018-12-04', 45, '12:00:00', 2147483647, 0, 'casamento', 'individual', '0', 0, '4545', '2018-12-04 02:59:42'),
(81, 'Marciel Rossetto', '2018-12-05', 2, '23:23:00', 2147483647, 2147483647, 'confraternizacao', 'unica', '0', 23, '23232', '2018-12-04 03:15:35'),
(82, 'KHAUAN DA SILVA ROSSETTO', '2018-12-05', 23, '23:23:00', 2147483647, 23232323, 'confraternizacao', 'unica', '0', 34, '5454545', '2018-12-04 03:18:55'),
(83, 'KHAUAN DA SILVA ROSSETTO', '2018-12-06', 32, '23:23:00', 2147483647, 2147483647, 'confraternizacao', 'unica_individual', 'individual', 878, '78787878', '2018-12-04 03:20:54'),
(84, 'KHAUAN DA SILVA ROSSETTO', '2018-12-06', 45, '23:32:00', 2147483647, 2147483647, 'casamento', 'unica', 'R$ 69,80', 788878, 'nadad', '2018-12-04 03:23:02'),
(85, 'KHAUAN DA SILVA ROSSETTOd', '2018-12-06', 78, '12:21:00', 2147483647, 2147483647, 'casamento', 'unica_individual', 'Valor do di', 34, '34533453', '2018-12-04 03:24:20'),
(86, 'qwqweqqw', '2018-12-06', 15, '12:12:00', 2147483647, 32323232, 'confraternizacao', 'unica', 'Valor nÃ£o ', 23, '', '2018-12-04 03:26:09'),
(87, 'gttrtrt', '2018-12-05', 123123, '12:31:00', 2147483647, 2147483647, 'formatura', 'outros', 'Valor nÃ£o estipulad', 23, '3423452345', '2018-12-04 03:28:09'),
(91, 'jose da silva', '2018-12-04', 25, '12:12:00', 2147483647, 2147483647, 'formatura', 'unica_individual', 'R$ 74,80', 58, '12', '2018-12-04 16:06:51'),
(92, 'mnarfef', '2019-04-18', 23, '12:11:00', 122312312, 34343434, 'aniversario', 'unica', '', 0, '12eqwe', '2019-04-18 23:49:59'),
(93, 'KHAUAN DA SILVA ROSSETTO', '2019-04-22', 23, '22:22:00', 2147483647, 2147483647, 'casamento', 'unica', '', 0, 'ertert', '2019-04-22 19:24:17'),
(95, 'MARCIEL ROSSETTO', '2019-08-19', 34, '12:12:00', 0, 0, 'aniversario', 'unica', '', 0, '', '2019-08-19 12:55:43'),
(97, 'marciel', '2019-09-23', 30, '08:08:00', 2147483647, 2147483647, 'formatura', 'unica_individual', '', 0, '', '2019-09-23 13:12:30'),
(98, 'pedro', '2019-09-23', 34, '00:00:00', 0, 0, '', '', '', 0, '', '2019-09-23 13:15:55'),
(99, 'marcos', '2019-09-23', 20, '12:20:00', 0, 0, '', '', '', 0, '', '2019-09-23 13:16:28'),
(100, 'joao', '2019-09-25', 34, '12:33:00', 343434, 343434, 'casamento', 'individual', '', 0, '343434', '2019-09-24 12:59:16');

-- --------------------------------------------------------

--
-- Estrutura da tabela `login`
--

CREATE TABLE IF NOT EXISTS `login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(40) NOT NULL,
  `data_emissao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=29 ;

--
-- Extraindo dados da tabela `login`
--

INSERT INTO `login` (`id`, `nome`, `email`, `senha`, `data_emissao`) VALUES
(1, 'marciel', 'marciel.rossetto3@gmail.com', '343b6968668732f499532b2f7ee94044', '2018-07-19 00:48:21'),
(17, 'admin', 'admin@admin', '21232f297a57a5a743894a0e4a801fc3', '2018-07-23 01:53:46'),
(18, 'marciel', 'rossettoti@gmail.com', '343b6968668732f499532b2f7ee94044', '2018-11-30 09:09:43'),
(25, 'Marciel Rossetto', 'marciel.rossetto3@gmail.com', '343b6968668732f499532b2f7ee94044', '2018-12-04 03:30:15'),
(26, 'jose', 'jose@gmail.com', '202cb962ac59075b964b07152d234b70', '2019-08-19 02:04:28'),
(27, 'hjghj', 'ghjghj@ertertert', 'ea7d201d1cdd240f3798b2dc51d6adcb', '2019-08-19 02:28:09'),
(28, '123', '123@gmail.com', '343b6968668732f499532b2f7ee94044', '2019-09-23 13:10:04');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
