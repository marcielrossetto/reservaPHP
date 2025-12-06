-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 04/12/2024 às 18:31
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `rosset85_bancoverdanna`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `data` date NOT NULL,
  `num_pessoas` int(11) NOT NULL,
  `horario` time NOT NULL,
  `telefone` varchar(255) DEFAULT NULL,
  `telefone2` varchar(255) DEFAULT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `forma_pagamento` varchar(50) NOT NULL,
  `valor_rodizio` int(11) DEFAULT NULL,
  `num_mesa` varchar(255) DEFAULT NULL,
  `observacoes` varchar(300) NOT NULL,
  `data_emissao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT '1',
  `usuario_id` int(11) DEFAULT NULL,
  `motivo_cancelamento` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `data`, `num_pessoas`, `horario`, `telefone`, `telefone2`, `tipo_evento`, `forma_pagamento`, `valor_rodizio`, `num_mesa`, `observacoes`, `data_emissao`, `status`, `usuario_id`, `motivo_cancelamento`) VALUES
(62, 'KHAUAN DA SILVA ROSSETTO', '2018-09-13', 12, '20:00:00', '2147483647', '0', '', '', 0, '0', '', '2018-09-11 02:02:22', 1, NULL, NULL),
(64, 'joana', '2018-09-13', 23, '23:34:00', '234234', '2147483647', 'casamento', 'individual', 0, '0', '', '2018-09-12 01:34:58', 1, NULL, NULL),
(65, 'pedro de jesus', '2024-10-31', 28, '04:03:23', '21996565885', '2199632258452', '', '', 0, '', 'erterterter\r\nzxcv\r\nzxc\r\nvzxc\r\nvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcv\r\nzxcv\r\nzxcv\r\nzxcv', '2018-09-12 01:58:43', 1, NULL, NULL),
(66, 'marciel', '2018-09-11', 23, '23:23:00', '23234234', '234234', 'casamento', 'outros', 0, '0', '', '2018-09-12 20:04:09', 1, NULL, NULL),
(68, 'marciel', '2018-09-17', 20, '20:30:00', '2147483647', '0', 'aniversario', 'individual', 0, '0', '', '2018-09-17 00:41:00', 1, NULL, NULL),
(72, 'KHAUAN DA SILVA ROSSETTO', '2018-11-29', 21, '12:00:00', '2147483647', '0', '', '', 0, '0', '', '2018-11-29 02:18:45', 1, NULL, NULL),
(73, 'Marciel Rossetto', '2018-11-29', 45, '18:30:00', '2147483647', '0', '', '', 0, '0', '', '2018-11-29 02:19:03', 1, NULL, NULL),
(75, 'Marciel Rossetto', '2018-11-30', 23, '23:33:00', '2147483647', '0', '', '', 0, '0', '', '2018-11-29 23:21:46', 1, NULL, NULL),
(77, 'joao', '2018-11-29', 41, '17:02:00', '32232323', '234354343', '', '', 0, '0', '234234234', '2018-11-30 09:25:27', 1, NULL, NULL),
(78, 'FRANCILENE DE RAUJO SILVA', '2018-11-30', 100, '12:00:00', '0', '0', '', '', 0, '0', '', '2018-11-30 09:25:51', 1, NULL, NULL),
(79, 'jose sssasdas', '2018-11-30', 255, '21:02:00', '5656', '5656', 'nao definido', 'nao definido', 0, '0', 'puiuio', '2018-11-30 09:26:13', 1, NULL, NULL),
(80, 'Marciel Rossetto', '2018-12-04', 45, '12:00:00', '2147483647', '0', 'casamento', 'individual', 0, '0', '4545', '2018-12-04 02:59:42', 1, NULL, NULL),
(81, 'Marciel Rossetto', '2018-12-05', 2, '23:23:00', '2147483647', '2147483647', 'confraternizacao', 'unica', 0, '23', '23232', '2018-12-04 03:15:35', 1, NULL, NULL),
(82, 'KHAUAN DA SILVA ROSSETTO', '2018-12-05', 23, '23:23:00', '2147483647', '23232323', 'confraternizacao', 'unica', 0, '34', '5454545', '2018-12-04 03:18:55', 1, NULL, NULL),
(83, 'KHAUAN DA SILVA ROSSETTO', '2018-12-06', 32, '23:23:00', '2147483647', '2147483647', 'confraternizacao', 'unica_individual', 0, '878', '78787878', '2018-12-04 03:20:54', 1, NULL, NULL),
(84, 'KHAUAN DA SILVA ROSSETTO', '2018-12-06', 45, '23:32:00', '2147483647', '2147483647', 'casamento', 'unica', 0, '788878', 'nadad', '2018-12-04 03:23:02', 1, NULL, NULL),
(85, 'KHAUAN DA SILVA ROSSETTOd', '2018-12-06', 78, '12:21:00', '2147483647', '2147483647', 'casamento', 'unica_individual', 0, '34', '34533453', '2018-12-04 03:24:20', 1, NULL, NULL),
(86, 'qwqweqqw', '2018-12-06', 15, '12:12:00', '2147483647', '32323232', 'confraternizacao', 'unica', 0, '23', '', '2018-12-04 03:26:09', 1, NULL, NULL),
(87, 'gttrtrt', '2018-12-05', 123123, '12:31:00', '2147483647', '2147483647', 'formatura', 'outros', 0, '23', '3423452345', '2018-12-04 03:28:09', 1, NULL, NULL),
(91, 'jose da silva', '2018-12-04', 25, '12:12:00', '2147483647', '2147483647', 'formatura', 'unica_individual', 0, '58', '12', '2018-12-04 16:06:51', 1, NULL, NULL),
(92, 'mnarfef', '2019-04-18', 23, '12:11:00', '122312312', '34343434', 'aniversario', 'unica', 0, '0', '12eqwe', '2019-04-18 23:49:59', 1, NULL, NULL),
(93, 'KHAUAN DA SILVA ROSSETTO', '2019-04-22', 23, '22:22:00', '2147483647', '2147483647', 'casamento', 'unica', 0, '0', 'ertert', '2019-04-22 19:24:17', 1, NULL, NULL),
(95, 'MARCIEL ROSSETTO', '2019-08-19', 34, '12:12:00', '0', '0', 'aniversario', 'unica', 0, '0', '', '2019-08-19 12:55:43', 1, NULL, NULL),
(97, 'marciel', '2019-09-23', 30, '08:08:00', '2147483647', '2147483647', 'formatura', 'unica_individual', 0, '0', '', '2019-09-23 13:12:30', 1, NULL, NULL),
(98, 'pedro', '2019-09-23', 34, '00:00:00', '0', '0', '', '', 0, '0', '', '2019-09-23 13:15:55', 1, NULL, NULL),
(99, 'marcos', '2019-09-23', 20, '12:20:00', '0', '0', '', '', 0, '0', '', '2019-09-23 13:16:28', 1, NULL, NULL),
(100, 'joao', '2019-09-25', 34, '12:33:00', '343434', '343434', 'casamento', 'individual', 0, '0', '343434', '2019-09-24 12:59:16', 1, NULL, NULL),
(101, 'ross', '2024-02-19', 44, '12:36:00', '2147483647', '0', 'Bodas casamento', 'U (rod) I (beb)', 0, '0', 'fdfdfd', '2024-02-17 01:52:50', 1, NULL, NULL),
(102, 'ross', '2024-02-23', 89, '08:11:00', '44554455', '2147483647', 'Bodas casamento', 'outros', 0, '13', '56756756', '2024-02-17 01:54:58', 1, NULL, NULL),
(103, 'Marciel Lazzaretti Rossetto', '2024-02-21', 10, '20:49:00', '2147483647', '0', 'Aniversario', 'individual', 0, '16', 'nasda a declarar\r\n', '2024-02-17 19:48:26', 1, NULL, NULL),
(104, 'heloiza 22', '2024-02-22', 45, '12:00:00', '2147483647', '0', 'Aniversario', 'unica', 0, '0', 'teste 2', '2024-02-17 19:52:28', 1, NULL, NULL),
(105, 'altair da silveira', '2024-02-24', 25, '20:12:00', '2147483647', '0', 'nao definido', 'unica', 0, '0', '5uhsdhshdbfbsdfsdfsdf', '2024-02-17 20:02:39', 1, NULL, NULL),
(106, 'joel', '2024-02-23', 65, '21:35:00', '2147483647', '0', 'Formatura', 'unica', 0, '0', 'nadassss', '2024-02-17 20:13:16', 1, NULL, NULL),
(107, 'httt', '2024-02-23', 96, '23:35:00', '2147483647', '0', 'Aniversario', 'unica', 0, '12', '669988', '2024-02-17 20:14:32', 1, NULL, NULL),
(108, 'Marciel Lazzaretti Rossetto', '2024-03-02', 58, '08:05:00', '0', '2147483647', 'Conf. Familia', 'outros', 0, '0', '58585858', '2024-02-17 20:16:13', 1, NULL, NULL),
(109, 'gfgfgf', '2024-02-23', 58, '21:21:00', '2147483647', '2147483647', 'Conf. fim de ano', 'U (rod) I (beb)', 0, '0', '5858585', '2024-02-17 20:28:22', 1, NULL, NULL),
(110, '888555588', '2024-02-18', 85, '21:33:00', '2147483647', '133', 'Conf. fim de ano', 'individual', 0, '3', '', '2024-02-17 20:32:53', 1, NULL, NULL),
(111, 'Fernanda ', '2024-02-25', 48, '02:32:00', '889955555', '0', 'Conf. fim de ano', 'individual', 0, '14', 'dfgdfh', '2024-02-17 23:31:47', 1, NULL, NULL),
(112, 'Marciel Lazzaretti Rossetto', '2024-10-30', 2344, '04:03:00', 'dsfgsdfg', '0', 'Formatura', 'individual', 0, 'Salão 1', 'dfgsdfgsdfgsdfg', '2024-10-28 01:48:00', 1, NULL, NULL),
(113, 'marciel2', '2024-10-29', 235, '11:15:00', '899665544778', '07845546563', 'aniversario', 'unica', NULL, '0', 'rrytrtyrtyrtyrty', '2024-10-28 19:12:50', 1, NULL, NULL),
(115, 'marciel', '2024-10-30', 23, '12:23:00', '589854875', '21889966584785', 'Formatura', 'unica', 0, 'Centro do salão', 'qwesdfhdfghdfghdfgh', '2024-10-28 19:30:45', 1, NULL, NULL),
(116, 'franci ', '2024-10-29', 45, '11:11:00', '2147483647', '2147483647', 'aniversario', 'unica', NULL, '0', '12123qweqweqweq', '2024-10-28 19:34:05', 1, NULL, NULL),
(117, 'khauan', '2024-10-29', 234, '11:11:00', '21996169369', '21995214745', 'casamento', 'individual', NULL, '0', 'ghuxthjdstgh', '2024-10-28 19:39:07', 1, NULL, NULL),
(118, 'joao', '2024-10-29', 65, '22:22:00', '995114782', '21', 'aniversario', 'unica', NULL, '0', 'jwdjsjkgnajfdkghifjghadj', '2024-10-28 19:46:48', 1, NULL, NULL),
(119, 'jose', '2024-10-29', 4, '22:22:00', '21996169369', '21996169369', 'aniversario', 'unica', NULL, '0', 'khuajhahah', '2024-10-28 20:11:25', 1, NULL, NULL),
(120, 'marciel rossetto 23  ', '2024-10-29', 35, '17:03:00', '21996169369', '21995114782', 'Bodas casamento', 'individual', 0, '0', 'qwwwadfasda', '2024-10-28 22:03:06', 1, NULL, NULL),
(121, 'maria de nazaré', '2024-10-31', 100, '20:30:00', '219988774455', '219966554455', 'Bodas casamento', 'unica', 0, 'Próximo ao jardim', 'nao pediu nada', '2024-10-28 23:14:26', 1, NULL, NULL),
(122, 'maria jose', '2024-10-30', 23, '22:22:00', '2222222222', '2222222222', 'Conf. Familia', 'unica', 110, 'Próximo ao jardim', '2222222222', '2024-10-28 23:18:45', 1, NULL, NULL),
(123, 'maria jose', '2024-10-23', 23, '17:49:00', '2222222226', '2222222227', 'Bodas casamento', 'unica', 125, '13', 'zzxvxv', '2024-10-29 00:48:02', 1, NULL, NULL),
(124, 'francilene de araujo silva', '2024-10-30', 54, '12:12:00', '21995114782', '21995221132', 'Conf. fim de ano', 'individual', 0, 'Próximo ao jardim', 'aniver do dia nao liberei ft mr', '2024-10-29 11:00:43', 1, NULL, NULL),
(125, 'marciel', '2024-10-31', 1000, '11:11:00', '11111111', '111111', 'Conf. fim de ano', 'unica', 105, 'Centro do salão', '1111111', '2024-10-30 01:22:06', 1, NULL, NULL),
(126, 'qweqweqweqweqwe', '2024-10-31', 50000, '11:11:00', '111111', '11111', 'Conf. Familia', 'unica', 125, 'Centro do salão', '1231231231', '2024-10-30 01:24:35', 1, NULL, NULL),
(127, 'sdgdfsdf', '2024-10-31', 2344422, '12:32:00', '4542324234', '3423423423423', 'Conf. fim de ano', '', 0, '12', '234234234234234', '2024-10-30 01:33:03', 1, NULL, NULL),
(128, 'rtwertwert', '2024-10-09', 1000, '20:06:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 02:05:18', 1, NULL, NULL),
(129, 'qwerqwerqwerqwer', '2024-11-21', 10, '00:00:00', '', '', '', '', 0, '', '', '2024-10-30 02:09:32', 1, NULL, NULL),
(130, 'wererwerasdfafdas', '2024-10-21', 23, '12:52:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 07:52:59', 1, NULL, NULL),
(131, 'sdfsdfsdf', '2025-03-20', 100, '12:59:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 07:59:21', 1, NULL, NULL),
(132, 'ertyertyrty', '2024-12-31', 1222, '12:00:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 08:00:19', 1, NULL, NULL),
(133, 'werwerwerdfdfdg', '2025-12-31', 345, '12:01:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 08:01:07', 1, NULL, NULL),
(134, 'hshdhjdjjdgo', '2026-03-26', 234, '12:20:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 09:20:50', 1, NULL, NULL),
(135, 'ueueueuueuh', '2025-10-15', 243, '13:22:00', '73747377', '74747477', 'Conf. fim de ano', 'U (rod) I (beb)', 115, 'Próximo à janela', '', '2024-10-30 09:22:48', 1, NULL, NULL),
(136, 'jdhdhhhxh', '2024-10-07', 258, '13:26:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 09:26:31', 1, NULL, NULL),
(137, 'fernanda ', '2024-11-06', 8, '17:08:00', '21996169369', '2199876545', 'Confraternizacao', 'individual', 0, 'Centro do salão', 'nao servir bebida alc', '2024-10-30 16:09:20', 1, NULL, NULL),
(138, 'woriysodfisdofigsdpofgis´pdfogs´dpfogs´dpfog', '2025-11-28', 2334, '23:10:00', '233233556757978', '789707897897897', 'Conf. fim de ano', 'unica', 105, 'Centro do salão', '3ert56yt', '2024-10-30 22:11:15', 1, NULL, NULL),
(139, 'ewtertwertwert', '2024-11-01', 345, '12:22:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 22:45:29', 1, NULL, NULL),
(140, '12213', '2024-11-02', 123, '12:23:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 22:45:47', 1, NULL, NULL),
(141, '123123123123', '2024-11-03', 123, '12:32:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-30 22:46:09', 1, NULL, NULL),
(142, '123123123123', '2024-10-15', 1201, '12:22:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:02:22', 1, NULL, NULL),
(143, 'marciel', '2024-10-09', 124, '12:33:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:02:49', 1, NULL, NULL),
(144, 'sdfgsdfgsdfg', '2024-10-11', 34553, '22:22:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:03:12', 1, NULL, NULL),
(145, '234234234234', '2024-10-10', 234, '22:22:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:03:33', 1, NULL, NULL),
(146, '234234234', '2024-10-12', 234, '03:43:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:03:52', 1, NULL, NULL),
(147, '24dfgsdfgdf', '2024-11-04', 2324, '23:04:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:04:16', 1, NULL, NULL),
(148, 'werwew', '2024-11-07', 123, '11:11:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:04:40', 1, NULL, NULL),
(149, 'ertertert', '2024-11-10', 1233, '12:33:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:05:15', 1, NULL, NULL),
(150, 'dfgdfgdfgd', '2027-03-28', 2344, '12:11:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-10-31 01:05:54', 1, NULL, NULL),
(151, 'maiquel', '2024-11-02', 250, '12:30:00', '21996410645', '21996169369', 'Formatura', 'unica(rod)individual(beb)', 0, 'Próximo ao jardim', '23', '2024-10-31 23:56:03', 1, NULL, NULL),
(152, 'maiquel lazzaretti', '2024-11-22', 24, '12:00:00', '999999999', '', 'Aniversario', 'unica', 84, '49', 'aniver nao é do dia, liberar rodizio', '2024-11-01 00:40:04', 1, NULL, NULL),
(153, 'maiquel lazzaretti', '2024-11-01', 24, '12:34:00', '9999999991', '1213123123123', 'Aniversario', 'unica', 84, 'Próximo ao jardim', 'efdasfasasdasdasdkansdajdkajbsdkabsdkasbdkasakdbakbskasbkabdskajbdskasdasd\r\nasd\r\nas\r\nda\r\nsd\r\nas\r\n\r\n', '2024-11-01 00:49:33', 1, NULL, NULL),
(154, 'weqwerqwerqwerqw', '2024-11-01', 235, '12:12:00', '123123123', '123123', 'Aniversario', 'unica', 99, 'Centro do salão', '1231231fsdsdfsdfs~llsmdvsmmpsmpdmfpsmdpfmsdpfmsçldmfçsldmfçlsmdçlsmdfçsmdçfmsdçfmsçdmfsçdmfçsdmfçsldmfçlsmdfçsmdfçlsmdçfmsçdfmçsldmfçsdmfçsdmfçsdmfçsdmfçsmdfsdmfçsdmfçsdmfçsdfsdfs\r\n\r\nsd\r\nf\r\nsdf\r\nsd\r\nf\r\nsd', '2024-11-01 00:50:28', 1, NULL, NULL),
(155, 'weqwerqwerqwerqw', '2024-10-31', 23, '12:12:00', '', '123123', 'Não definido', 'Não definido', 0, '', 'asgsdfdsfsdfhsdfhsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfg', '2024-11-01 02:40:28', 1, NULL, NULL),
(156, 'kw ', '2024-11-01', 254, '12:58:00', '21589568548', '545845485', 'Formatura', 'individual', 89, 'Próximo à janela', 'sdfgsdfgsdfgsdfsdfghsdfgsdgsdgrsdfgsdfhsdfhsdghsdfhgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgdsfgsdfgdsfgdfgsdfgsdfgsdfgdsfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgdfgsdfgsdfgsdfgsdfgsdfg', '2024-11-01 10:30:18', 1, NULL, NULL),
(157, 'wee', '2024-11-01', 698, '18:35:00', '', '', 'Não definido', 'Não definido', 0, '', 'dfhsdfhsdhsdhsdf', '2024-11-01 10:31:09', 1, NULL, NULL),
(158, 'fernanda ', '2024-11-01', 234, '18:34:00', '', '', '', '', 0, '1', '', '2024-11-01 23:22:26', 1, NULL, NULL),
(159, 'qwerwerqwerwq', '2024-12-01', 235, '12:11:00', '', '', 'Não definido', 'Não definido', 0, '', '1wrtgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfgsdfg', '2024-11-01 23:33:37', 1, NULL, NULL),
(160, 'sadshsdhfsdfgsdfg', '2024-12-02', 456, '12:56:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-01 23:34:19', 1, NULL, NULL),
(161, 'hwryey', '2024-12-03', 1234, '12:44:00', '', '', '', '', 0, '', '', '2024-11-01 23:44:10', 1, NULL, NULL),
(162, 'hwryey', '2024-11-02', 12, '00:00:00', '', '', 'Não definido', 'Não definido', 99, 'Próximo à janela', '', '2024-11-01 23:57:45', 1, NULL, NULL),
(163, 'hwryey', '2024-11-01', 12255, '00:00:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-02 00:12:34', 1, NULL, NULL),
(164, 'werasasdasd', '2024-11-02', 211, '11:11:00', '', '', 'Não definido', 'Não definido', 105, '', '', '2024-11-02 01:08:12', 0, NULL, 'Nada que vc cdhhdh'),
(165, 'werasasdasd', '2024-11-01', 21, '17:33:00', '', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-02 02:32:12', 1, NULL, NULL),
(166, 'Maju', '2024-11-09', 214, '12:22:00', '21996169369', '', 'Casamento', 'unica(rod)individual(beb)', 0, 'Próximo ao jardim', '', '2024-11-02 19:05:27', 1, NULL, NULL),
(167, 'wiwiwiwiwiiw', '2027-01-01', 122, '12:12:00', '222222222', '2222222222', 'Bodas casamento', 'unica', 125, 'Próximo à janela', '9wwkwiiw9wowow0w9wowowo8s9wikw', '2024-11-02 19:09:08', 1, NULL, NULL),
(168, 'w2ww2www', '0000-00-00', 322, '12:22:00', '', '', 'Não definido', 'Não definido', 125, '', '', '2024-11-02 19:12:55', 1, NULL, 'olalalallallallalla'),
(169, 'Artur da silva', '2024-11-08', 120, '12:33:00', '21996169369', '', '', 'unica', 0, 'Próximo ao jardim', 'Hshsh', '2024-11-07 00:39:39', 1, 17, NULL),
(170, 'MARCIEL ROSSETTO', '2024-11-22', 32, '00:20:00', '2155578', '', 'casamento', 'individual', 125, 'Salão 2', '6wywtwg', '2024-11-07 00:51:39', 1, 33, NULL),
(171, 'Marciel ', '2024-11-14', 53, '20:05:00', '21996169369', '', 'Conf. fim de ano', 'individual', 125, 'Próximo ao jardim', 'Bdhddh', '2024-11-07 01:06:11', 1, 17, NULL),
(172, 'Jose ', '2024-11-05', 63, '18:08:00', '42616', '', 'Bodas casamento', 'U (rod) I (beb)', 125, 'Próximo à janela', 'Jdjdh', '2024-11-07 01:08:56', 1, 17, NULL),
(173, 'Pietro da silva ', '2024-11-08', 43, '18:30:00', '422262727', '', 'Conf. Familia', 'individual', 125, 'Próximo ao jardim', 'Jsjdhdh', '2024-11-07 01:23:36', 1, 37, 'Teste'),
(174, 'Marciel', '2024-11-10', 64, '20:03:00', '62637', '', 'Conf. fim de ano', 'U (rod) I (beb)', 125, 'Próximo à janela', 'Gshshdhh', '2024-11-07 02:03:49', 1, 37, NULL),
(175, 'João da silva ', '2024-11-08', 65, '21:08:00', '21996169369', '', 'Aniversario', '', 0, 'Próximo ao jardim', 'Hdhdhh', '2024-11-07 02:09:12', 1, 35, 'Nosso que nada'),
(176, 'Leandro', '2024-11-08', 6, '13:30:00', '21996169369', '', 'Conf. Familia', 'individual', 125, 'Próximo à janela', 'Hsjdjjdjjx', '2024-11-07 02:53:46', 1, 37, NULL),
(177, 'Franci', '2024-11-13', 50, '19:00:00', '21995114782', '', 'Conf. fim de ano', 'individual', 110, 'Próximo à janela', 'Tem 3 crianças 3, 5 e 7 anos\r\nTem 1 bariátricas \r\nTem 2 aniversariante ', '2024-11-07 09:46:26', 1, 17, NULL),
(178, 'Talison da silva ', '2024-11-08', 109, '19:30:00', '21995114782', '', 'Formatura', 'U (rod) I (beb)', 110, '5', '', '2024-11-07 10:13:03', 1, 17, NULL),
(179, 'Talisom', '2024-11-08', 42, '13:13:00', '21996169369', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-07 10:13:41', 1, 17, NULL),
(180, 'Franci silva ', '2024-11-09', 15, '19:00:00', '21995114782', '', 'Conf. fim de ano', 'unica', 110, '6', 'Teste', '2024-11-07 10:20:09', 1, 17, 'nao falou'),
(181, 'Franci Araújo ', '2024-11-09', 20, '19:00:00', '21995114782', '', 'Conf. fim de ano', 'individual', 0, '6', 'Testdghbh', '2024-11-07 10:23:40', 1, 17, 'sdfsds'),
(182, 'Maiquel rossetto', '2024-11-08', 20, '18:30:00', '21996410645', '', 'Aniversario', 'individual', 110, '49', 'Jdhhdgdggdgdhsjvsvhdjid', '2024-11-07 10:26:33', 1, 17, NULL),
(183, 'Marcos', '2025-04-24', 580, '02:30:00', '21996169369', '', 'Conf. fim de ano', 'unica', 125, 'Centro do salão', 'Gagsgsgsgsg', '2024-11-07 11:14:12', 1, 17, NULL),
(184, 'sfgsdfgsdfg', '2025-01-01', 25, '12:00:00', '21996169369', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-07 11:39:44', 1, 17, NULL),
(185, 'Antônio Joilson Ricardo ', '2024-11-17', 22, '19:00:00', '987888060', '', 'Aniversario', 'individual', 0, '18', '', '2024-11-07 12:22:53', 1, 17, NULL),
(186, 'Ronaldo', '2024-11-21', 52, '12:40:00', '21988760271', '', 'casamento', 'unica', 125, 'Próximo à janela', 'Nada a pedir ', '2024-11-07 12:42:21', 1, 17, NULL),
(187, 'Vitor ', '2024-11-07', 5, '12:06:00', '21996070643', '', 'Conf. Familia', 'unica', 110, '8', 'Ok', '2024-11-07 13:07:30', 1, 17, NULL),
(188, 'Marciel ', '2024-11-07', 543, '13:10:00', '21996169369', '', 'casamento', 'outros', 125, 'Centro do salão', 'Nada ', '2024-11-07 13:10:47', 1, 37, NULL),
(189, 'Ana ferreira', '2024-11-09', 15, '12:30:00', '21996070643', '', 'Conf. fim de ano', 'U (rod) I (beb)', 34, 'Próximo à janela', 'Ind', '2024-11-08 11:14:51', 1, 17, NULL),
(190, 'Gilmar Dedordi', '2024-12-06', 25, '20:30:00', '21993694901', '', 'Aniversario', 'individual', 111, 'Próximo à janela', 'Aniversário do dia ', '2024-11-08 13:27:33', 1, 37, NULL),
(191, 'Marciel ', '2025-01-01', 65, '18:22:00', '21996169369', '', 'Conf. Familia', 'individual', 156, 'Próximo ao jardim', 'Bsbsbs', '2024-11-08 17:23:16', 1, 37, NULL),
(192, 'Joana ', '2024-11-08', 56, '20:45:00', '21996169369', '', 'casamento', 'U (rod) I (beb)', 156, 'Próximo ao jardim', '', '2024-11-08 19:46:16', 1, 37, NULL),
(193, 'Gabriel', '2024-11-09', 54, '20:56:00', '21983912066', '', 'Conf. fim de ano', 'individual', 106, 'Próximo à janela', 'Nada ', '2024-11-08 22:57:03', 1, 37, NULL),
(194, 'marciel', '2024-11-19', 233, '12:22:00', '21996169369', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-09 01:22:12', 1, 17, NULL),
(195, 'pedro', '2024-11-04', 23, '12:33:00', '21996169369', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-09 01:23:03', 1, 17, 'teste'),
(196, 'maiquel lazzaretti', '2024-11-11', 54, '12:33:00', '999999999', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-09 01:23:23', 1, 17, NULL),
(197, 'Artur da silva', '2024-12-07', 122, '12:22:00', '21996169369', '', 'Não definido', 'Não definido', 0, '', 'I\\\'m not sure if anyone is reading this right now, but if so, I want you to know that I wish you a day full of love and joy. May every moment bring you closer to your goals and may you never lose faith in yourself. You are unique and capable of achieving anything you set your mind to!\r\n\r\nI\\\'m not su', '2024-11-09 02:51:38', 1, 17, NULL),
(198, 'Tiago', '2024-11-15', 12, '19:26:00', '21996169369', '', 'Conf. Familia', 'U (rod) I (beb)', 89, 'Centro do salão', 'Hdjdh', '2024-11-09 19:27:06', 1, 37, NULL),
(199, 'Gabriel ', '2024-11-30', 43, '19:29:00', '21996169369', '', 'Aniversario', 'U (rod) I (beb)', 111, 'Próximo ao jardim', '', '2024-11-09 19:29:54', 1, 37, NULL),
(200, 'Lucas', '2024-11-26', 54, '20:31:00', '21986846183', '', 'Aniversario', 'unica', 111, 'Próximo à janela', 'Aniver do dia liberado ', '2024-11-09 19:34:18', 1, 37, NULL),
(201, 'Adaltom ', '2024-11-25', 15, '19:30:00', '21969621603', '', 'Conf. fim de ano', 'individual', 106, 'Próximo à janela', 'Nada ', '2024-11-10 19:13:56', 1, 37, NULL),
(202, 'rtwewwerr', '2025-02-11', 34, '22:33:00', '21996169369', '', 'Conf. fim de ano', 'unica', 111, 'Centro do salão', '', '2024-11-11 23:26:27', 1, 17, NULL),
(203, 'rgertertert', '2025-03-11', 44, '22:33:00', '21996169369', '', 'Aniversario', 'unica', 111, 'Centro do salão', '', '2024-11-11 23:27:04', 1, 17, NULL),
(204, 'weqwerqwer', '2024-04-11', 36, '12:22:00', '1122223332221', '', 'Não definido', 'Não definido', 0, '', '', '2024-11-11 23:28:08', 1, 17, NULL),
(205, 'MARCIEL ROSSETTO', '2024-11-06', 42, '19:00:00', '2199619369', '', 'casamento', 'U (rod) I (beb)', 106, '1', '', '2024-11-13 19:21:03', 1, 17, NULL),
(206, 'Raphael ', '2024-11-20', 10, '20:00:00', '21996153576', '', 'casamento', 'individual', 106, 'Próximo ao jardim', 'Nada ', '2024-11-14 21:45:15', 1, 17, NULL),
(207, 'Juliano ', '2024-11-19', 4, '20:00:00', '21999767226', '', 'Conf. fim de ano', 'individual', 111, 'Próximo ao jardim', '', '2024-11-18 14:09:34', 1, 17, NULL),
(208, 'Franci', '2024-11-28', 647, '20:45:00', '21995114782', '', 'Conf. Familia', 'U (rod) I (beb)', 156, 'Próximo à janela', 'Hshdh', '2024-11-27 17:45:51', 1, 17, 'asasdasdasd'),
(209, 'Pedro ', '2024-11-28', 45, '20:52:00', '21995114782', '', 'casamento', 'outros', 89, 'Próximo ao jardim', 'Nada ', '2024-11-27 17:53:06', 1, 39, NULL),
(210, 'Khyan', '2024-11-28', 34, '21:55:00', '2116263663', '', 'casamento', 'individual', 0, 'Próximo ao jardim', '', '2024-11-27 17:55:29', 1, 40, NULL),
(211, 'marciel 2 ', '2024-11-28', 65, '12:00:00', '21996169369', '', 'Bodas casamento', 'U (rod) I (beb)', 132, 'Centro do salão', '<div class=\\\"modal-dialog\\\" role=\\\"document\\\">\r\n    <div class=\\\"modal-content\\\">\r\n        <div class=\\\"modal-header\\\">\r\n            <h5 class=\\\"modal-title\\\" id=\\\"exampleModalLongTitle\\\">Login</h5>\r\n        </div>\r\n        <div class=\\\"modal-body\\\">', '2024-11-27 23:09:58', 1, 17, NULL),
(212, 'Maria ', '2025-02-12', 45, '20:30:00', '21996169369', '', 'Aniversario', 'individual', 124, '2', '', '2024-11-27 23:23:34', 1, 17, NULL),
(213, 'Ronaldo', '2024-11-30', 36, '08:40:00', '21996169369', '', 'Formatura', 'U (rod) I (beb)', 132, 'Próximo ao jardim', '', '2024-11-28 00:38:54', 1, 17, NULL),
(214, 'Mario ', '2025-01-24', 32, '12:24:00', '219996169369', '', 'Formatura', '', 0, 'Centro do salão', 'As taxas dos títulos do Tesouro Direto operam em forte alta na tarde desta quarta-feira (27), após retomada das negociações, que haviam sido suspensas devido à alta volatilidade dos juros. A suspensão é um procedimento padrão do Tesouro Nacional em momentos de grande oscilação das taxas, visando pro', '2024-11-28 10:01:56', 1, 17, NULL),
(215, 'MARCIEL ROSSETTO', '2024-11-29', 21, '14:25:00', '21996169369', '', 'Conf. Familia', 'U (rod) I (beb)', 100, 'Centro do salão', 'As taxas dos títulos do Tesouro Direto operam em forte alta na tarde desta quarta-feira (27), após retomada das negociações, que haviam sido suspensas devido à alta volatilidade dos juros. A suspensão é um procedimento padrão do Tesouro Nacional em momentos de grande oscilação das taxas, visando pro', '2024-11-28 10:15:22', 1, 17, 'Nadav'),
(216, 'Fabio ', '2024-12-01', 18, '13:00:00', '21995334729', '', 'Aniversario', 'unica', 100, 'Próximo ao jardim', '', '2024-11-28 17:23:05', 1, 17, NULL),
(217, 'Marcos', '2024-11-30', 45, '20:48:00', '21996169369', '', 'Formatura', 'individual', 115, 'Próximo ao jardim', 'Teste ', '2024-11-29 11:48:23', 1, 17, NULL),
(218, 'Gilvan', '2024-12-30', 190, '19:54:00', '21980454300', '', 'Conf. fim de ano', 'individual', 0, '', 'Nada ', '2024-11-29 11:55:23', 1, 17, NULL),
(219, 'Mateus', '2025-01-21', 234, '21:41:00', '21996169369', '', 'Não definido', 'U (rod) I (beb)', 115, '4', 'Nndbbddb', '2024-11-29 12:42:03', 1, 17, NULL),
(220, 'Marciel', '2024-12-25', 234, '20:30:00', '21996169369', '', 'Aniversario', 'individual', 115, 'Próximo ao jardim', 'Ndndnj', '2024-11-29 14:30:56', 1, 38, 'Teste pra ver se funciona'),
(221, 'vgfdfgdfgdf', '2026-01-21', 456, '12:00:00', '21995214745', '', 'Aniversario', 'unica', 120, 'Próximo ao jardim', 'fsdfsdfsdf', '2024-12-01 01:49:44', 1, 17, NULL),
(222, 'Marcos', '2025-12-25', 123, '12:05:00', '21996169369', '', '', '', 0, 'Próximo ao jardim', 'Hfhfhhdjejrh', '2024-12-01 02:37:39', 1, 17, NULL),
(223, 'Mateus', '2024-12-02', 568, '12:30:00', '21996169369', '', '', '', 0, '', 'Pagou a metade dos rodizios', '2024-12-01 10:02:23', 1, 17, NULL),
(224, 'Jertndh', '2024-12-04', 478, '21:30:00', '2199161616', '', 'Não definido', 'Não definido', 0, '', '', '2024-12-01 10:03:05', 0, 17, 'Teste'),
(225, 'Ururu4', '2024-12-05', 78, '12:30:00', '2144', '', 'Formatura', 'U (rod) I (beb)', 110, 'Próximo à janela', '', '2024-12-01 10:04:28', 1, 17, NULL),
(226, 'Ana', '2024-12-02', 34, '21:07:00', '21995114782', '', 'Não definido', 'individual', 110, 'Centro do salão', 'Kkkkk\r\nFfffffd\r\n\r\n\r\nDddfffd\r\nDdd\r\nDddfff\r\nHggg\r\n', '2024-12-01 21:07:39', 1, 17, NULL),
(227, 'Khauan', '2024-12-02', 67, '20:08:00', '21995114782', '', 'Conf. fim de ano', 'unica', 110, '6', 'Jxjdjdjd', '2024-12-01 21:09:29', 1, 42, NULL),
(228, 'Hshhjshsh', '2024-12-02', 65, '12:14:00', '21999999888', '', 'Não definido', 'U (rod) I (beb)', 0, '', '', '2024-12-01 21:14:24', 1, 37, NULL),
(229, 'sfgsdfgsdfg', '2027-01-20', 1234, '12:00:00', '215566778899', '', 'Aniversario', 'unica(rod)individual(beb)', 0, '6', 'O mercado financeiro deve abrir o pregão com um clima mais tranquilo nesta segunda-feira (2), após o Ibovespa registrar o pior desempenho em oito anos e o dólar bater em R$ 6. Não que a tensão sobre o anúncio paralelo do pacote fiscal e da isenção do Imposto de Renda (IR) tenha saído do ar. O cenári', '2024-12-01 23:51:05', 1, 17, NULL),
(230, 'Marciel ', '2024-12-04', 125, '12:29:00', '21664994555', '', '', 'unica', 0, '10', 'Yeysgtstststdt', '2024-12-02 02:37:09', 1, 17, NULL),
(231, 'Renato salomao', '2024-12-08', 20, '12:00:00', '21971500874', '', 'Conf. fim de ano', 'unica', 120, 'Próximo à janela', '', '2024-12-02 13:11:46', 1, 17, NULL),
(232, 'Cristiano', '2024-12-23', 15, '19:20:00', '21982792683', '', 'Conf. fim de ano', 'individual', 120, 'Salão 3', 'Gsgshh', '2024-12-02 14:35:29', 1, 41, NULL),
(233, 'Silvio ', '2024-12-10', 30, '19:00:00', '21982203885', '', 'casamento', 'individual', 110, 'Próximo ao jardim', '', '2024-12-02 15:03:25', 1, 41, NULL),
(234, 'Adaltom', '2024-12-17', 25, '12:30:00', '21969621603', '', 'Conf. fim de ano', 'individual', 21, 'Centro do salão', 'Jdkdjdjdudu', '2024-12-02 23:20:35', 1, 17, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(40) NOT NULL,
  `data_emissao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `login`
--

INSERT INTO `login` (`id`, `nome`, `email`, `senha`, `data_emissao`, `status`) VALUES
(1, 'marciel', 'marciel.rossetto3@gmail.com', '343b6968668732f499532b2f7ee94044', '2018-07-19 00:48:21', 1),
(17, 'admin', 'admin@admin', 'E38E37A99F7DE1F45D169EFCDB288DD1', '2018-07-23 01:53:46', 1),
(18, 'marciel', 'rossettoti@gmail.com', '343b6968668732f499532b2f7ee94044', '2018-11-30 09:09:43', 1),
(25, 'Marciel Rossetto', 'marciel.rossetto3@gmail.com', '343b6968668732f499532b2f7ee94044', '2018-12-04 03:30:15', 1),
(26, 'jose', 'jose@gmail.com', '202cb962ac59075b964b07152d234b70', '2019-08-19 02:04:28', 1),
(27, 'hjghj', 'ghjghj@ertertert', 'ea7d201d1cdd240f3798b2dc51d6adcb', '2019-08-19 02:28:09', 1),
(28, '123', '123@gmail.com', '343b6968668732f499532b2f7ee94044', '2019-09-23 13:10:04', 1),
(29, 'neiva', 'teste@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', '2024-02-17 00:56:00', 1),
(30, '', 'marciel@gmail.com', '343b6968668732f499532b2f7ee94044', '2024-02-17 00:56:14', 1),
(31, 'Marciel Lazzaretti Rossetto', 'erttt@gmail.com', 'b0baee9d279d34fa1dfd71aadb908c3f', '2024-02-17 02:44:22', 1),
(32, 'franci ', 'fraci@franci', '17166193b35a231d8031c52931e06a70', '2024-10-28 21:37:37', 1),
(33, 'pedro', 'pedro@admin', 'b59c67bf196a4758191e42f76670ceba', '2024-10-29 00:00:15', 0),
(34, 'pedro', 'admin@admin', 'e38e37a99f7de1f45d169efcdb288dd1', '2024-10-29 00:17:39', 1),
(35, 'marciel', 'marciel.rossetto3@gmail.com', 'e38e37a99f7de1f45d169efcdb288dd1', '2024-10-29 00:21:04', 0),
(36, 'rossettoti', 'rossettoti@gmail.com', '7ab32f7d361576175ba392e5997f08ac', '2024-10-29 00:21:56', 0),
(37, 'marciel', 'marciel@admin', 'e38e37a99f7de1f45d169efcdb288dd1', '2024-11-01 23:39:37', 0),
(38, 'Vitor', 'vitor@vitor', '81dc9bdb52d04dc20036dbd8313ed055', '2024-11-08 11:11:29', 0),
(39, 'Franci', 'franci@franci', '1f36c15d6a3d18d52e8d493bc8187cb9', '2024-11-27 17:51:30', 0),
(40, 'Khauan', 'khauan@khauan', 'a00e5eb0973d24649a4a920fc53d9564', '2024-11-27 17:54:24', 0),
(41, 'marciel', 'a@a', 'e17a5a399de92e1d01a56c50afb2a68e', '2024-12-01 01:57:04', 0),
(42, 'Franci', 'franci@gmail', '1f36c15d6a3d18d52e8d493bc8187cb9', '2024-12-01 21:06:21', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `preco_rodizio`
--

CREATE TABLE `preco_rodizio` (
  `id` int(11) NOT NULL,
  `almoco` decimal(10,2) NOT NULL,
  `jantar` decimal(10,2) NOT NULL,
  `domingo_almoco` decimal(10,2) NOT NULL,
  `outros` decimal(10,2) NOT NULL,
  `data_emissao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `preco_rodizio`
--

INSERT INTO `preco_rodizio` (`id`, `almoco`, `jantar`, `domingo_almoco`, `outros`, `data_emissao`) VALUES
(1, 185.00, 110.00, 198.00, 10.00, '2024-10-29 01:48:47'),
(2, 110.00, 115.00, 125.00, 10.00, '2024-10-29 01:53:01'),
(3, 130.00, 115.00, 135.00, 10.00, '2024-10-29 14:07:29'),
(4, 89.00, 95.00, 109.00, 18.00, '2024-10-29 14:10:07'),
(5, 145.00, 187.00, 200.00, 76.00, '2024-10-29 18:12:48'),
(6, 105.00, 115.00, 125.00, 10.00, '2024-10-29 21:30:39'),
(7, 84.00, 89.00, 99.00, 78.00, '2024-11-01 03:07:35'),
(8, 110.00, 110.00, 125.00, 105.00, '2024-11-02 04:07:41'),
(9, 110.80, 110.80, 125.80, 105.80, '2024-11-07 22:36:21'),
(10, 34.00, 23.00, 76.00, 56.00, '2024-11-08 02:37:26'),
(11, 110.80, 105.80, 155.80, 89.00, '2024-11-08 15:18:58'),
(12, 123.90, 99.90, 132.00, 89.00, '2024-11-27 20:48:30'),
(13, 115.00, 115.00, 130.00, 120.00, '2024-11-29 13:42:50'),
(14, 110.00, 110.00, 130.00, 120.00, '2024-12-01 04:53:26'),
(15, 21.00, 34.00, 98.00, 56.00, '2024-12-03 02:09:45');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `preco_rodizio`
--
ALTER TABLE `preco_rodizio`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

--
-- AUTO_INCREMENT de tabela `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `preco_rodizio`
--
ALTER TABLE `preco_rodizio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
