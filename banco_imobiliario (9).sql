-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/06/2025 às 02:26
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `banco_imobiliario`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `conjunto`
--

CREATE TABLE `conjunto` (
  `id` int(11) NOT NULL,
  `nome_conjunto` varchar(255) NOT NULL,
  `cor_conjunto` varchar(50) NOT NULL,
  `nome_propriedade` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conjunto`
--

INSERT INTO `conjunto` (`id`, `nome_conjunto`, `cor_conjunto`, `nome_propriedade`) VALUES
(1, 'Amarelo', 'Amarelo', 'Av Niemeyer'),
(2, 'Rosa', 'Rosa', 'Av Higienopolis'),
(3, 'Amarelo', 'Amarelo', 'Av. Oscar Freire');

-- --------------------------------------------------------

--
-- Estrutura para tabela `game_status`
--

CREATE TABLE `game_status` (
  `id` int(11) NOT NULL,
  `reset_status` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `game_status`
--

INSERT INTO `game_status` (`id`, `reset_status`) VALUES
(1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `game_timer`
--

CREATE TABLE `game_timer` (
  `id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `remaining_time` int(11) DEFAULT 7200
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `game_timer`
--

INSERT INTO `game_timer` (`id`, `start_time`, `remaining_time`) VALUES
(1, '2024-05-26 04:35:48', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogando`
--

CREATE TABLE `jogando` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `objetivoId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `jogando`
--

INSERT INTO `jogando` (`id`, `userId`, `nickname`, `objetivoId`) VALUES
(809, 10, 'Monique', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lances`
--

CREATE TABLE `lances` (
  `id` int(11) NOT NULL,
  `leilao_id` int(11) NOT NULL,
  `jogador_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `rodada` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `leiloes`
--

CREATE TABLE `leiloes` (
  `id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `cor` varchar(50) NOT NULL,
  `propriedade` varchar(255) NOT NULL,
  `lance_minimo` decimal(10,2) NOT NULL,
  `jogador_id` int(11) NOT NULL,
  `lance_atual` decimal(10,2) DEFAULT 0.00,
  `jogador_lance_id` int(11) DEFAULT NULL,
  `rodada_atual` int(11) DEFAULT 1,
  `status` varchar(50) DEFAULT 'ativo',
  `jogador_vencedor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read` tinyint(1) DEFAULT 0,
  `type` varchar(50) NOT NULL DEFAULT 'notification'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `created_at`, `read`, `type`) VALUES
(44596, 17, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44597, 9, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44598, 21, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44599, 23, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44600, 11, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44601, 22, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44602, 19, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44604, 12, 'Windsor entrou na partida.', '2025-06-10 00:12:06', 0, 'join'),
(44606, 17, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44607, 9, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44608, 21, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44609, 23, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44610, 11, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44611, 22, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44612, 19, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44613, 12, 'Monique entrou na partida.', '2025-06-10 00:12:53', 0, 'join'),
(44617, 17, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44618, 9, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44619, 21, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44620, 23, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44621, 11, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44622, 22, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44623, 19, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44625, 12, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21', 0, 'transaction'),
(44626, 17, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44627, 9, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44628, 21, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44629, 23, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44630, 11, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44631, 22, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44632, 19, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44633, 10, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning'),
(44634, 12, 'Windsor abandonou a partida.', '2025-06-10 00:25:37', 0, 'warning');

-- --------------------------------------------------------

--
-- Estrutura para tabela `objetivos`
--

CREATE TABLE `objetivos` (
  `id` int(11) NOT NULL,
  `nome_objetivo` varchar(255) DEFAULT NULL,
  `descricao_objetivo` text DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `objetivo1` varchar(255) DEFAULT NULL,
  `objetivo2` varchar(255) DEFAULT NULL,
  `objetivo3` varchar(255) DEFAULT NULL,
  `objetivo4` varchar(255) DEFAULT NULL,
  `assigned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `objetivos`
--

INSERT INTO `objetivos` (`id`, `nome_objetivo`, `descricao_objetivo`, `status`, `objetivo1`, `objetivo2`, `objetivo3`, `objetivo4`, `assigned`) VALUES
(1, 'Magnata das Conexões', 'Adquirir ação da Viação Garcia e construir pelo menos 6 casas em propriedades próximas a ação.', 0, 'Ação da <b><font color=\"#003f7c\">Viação Garcia</font></b>', '6 Casas', '', NULL, 0),
(2, 'Mestre da Comunicação', 'Controlar as ações da Mastercard e Globo, construir pelo menos 6 casas em propriedades no conjunto roxo.', 0, 'Ações <b><font color=\'Red\'>Master</font><font color=\"orange\">card</font></b> e <b><font color=\"#E60012\">G</font><font color=\"#EA4C89\">l</font><font color=\"#FAA61A\">o</font><font color=\"#8BC34A\">b</font><font color=\"#03A9F4\">o</font></b>', '6 <font color=\"purple\"><b>Casas</b></font>', '', NULL, 1),
(3, 'Urbanista', 'Adquirir um conjunto completo de propriedades de qualquer cor e construir 1 hotel e pelo menos 4 casas nesse conjunto.', 0, '1 Conj.', '1 Hotel', '4 Casas', NULL, 1),
(4, 'Comerciante Estratégico', 'Adquirir todos os conjuntos de propriedades de cores laranja ou amarelo, construir 6 casas e investir em ações da Petrobras.', 0, 'Conj. <font color=\"orange\"><b>Lar.</b></font> ou <font color=\"gold\"><b>Ama.</b></font>', 'Ação <b><font color=\"green\">Petrobras</font></b>', '6 Casas', NULL, 0),
(5, 'Colecionador Versátil', 'Possuir um conjunto completo de propriedades de qualquer cor e construir 5 casas nessas propriedades. Adquirir ações de pelo menos três tipos diferentes.', 0, '1 Conj.', '5 Casas', '3 Ações', NULL, 0),
(6, 'Estrategista de Elite', 'Adquirir todas as propriedades do conjunto verde escuro ou azul, construir 6 casas nessas propriedades e adquirir ações da GOL e Mercado Livre.', 0, 'Conj. <font color=\"#006400\"><b>Verde Esc.</b></font> ou <font color=\"blue\"><b>Azul</b></font>', '6 Casas', 'Ação <font color=\"orage\">G</font><font color=\"gray\">O</font><font color=\"oragen\">L</font> e <font color=\"gold\">Mercado Livre</font>', NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `poupanca`
--

CREATE TABLE `poupanca` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `valor_aplicado` decimal(15,2) NOT NULL,
  `data_aplicacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_atualizacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `valor_atual` decimal(15,2) NOT NULL,
  `status` enum('ativa','sacada','penalizada') NOT NULL DEFAULT 'ativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `poupanca`
--

INSERT INTO `poupanca` (`id`, `user_id`, `valor_aplicado`, `data_aplicacao`, `ultima_atualizacao`, `valor_atual`, `status`) VALUES
(3, 13, 5500000.00, '2025-06-08 21:37:16', '2025-06-08 21:43:25', 75800688492.08, 'sacada'),
(4, 13, 5500000.00, '2025-06-08 21:44:19', '2025-06-09 01:05:25', 75793367992.08, 'penalizada'),
(5, 13, 5500000.00, '2025-06-10 00:12:15', '2025-06-10 00:12:15', 5500000.00, 'ativa'),
(6, 10, 10000000.00, '2025-06-10 00:13:08', '2025-06-10 00:13:08', 10000000.00, 'ativa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `message`, `created_at`) VALUES
(6214, 13, 'Aplicação na Poupança: R$5,500,000', '2025-06-10 00:12:15'),
(6215, 10, 'Aplicação na Poupança: R$10,000,000', '2025-06-10 00:13:08'),
(6216, 13, 'Windsor recebeu R$ 2.000.000 do banco.', '2025-06-10 00:13:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(15,2) DEFAULT 15000000.00,
  `abandonou` tinyint(1) DEFAULT 0,
  `notificado` tinyint(1) DEFAULT 0,
  `last_reward_time` timestamp NULL DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `nickname`, `password`, `balance`, `abandonou`, `notificado`, `last_reward_time`, `icone`) VALUES
(9, 'Carol', '$2y$10$vROwhNL/K1aESifSKIpwVuzrRL198sXICG4lJAbWCbwbcp0fY7Gfe', 15000000.00, 0, 0, NULL, NULL),
(10, 'Monique', '$2y$10$8tojQsmGcXazi4ptPAzxXeZdivcaylHFPITdwyp.aNP6TlREPfymS', 5000000.00, 0, 0, NULL, 'fa-dollar-sign'),
(11, 'Jhow', '$2y$10$GlCUEgb6MkHihHTI2/fPmO62s5.MNiIMLypYnu1b4rZqovpPowqZ2', 15000000.00, 0, 0, NULL, NULL),
(12, 'Roberto', '$2y$10$4TMbH0L5n4IABAjUQo7v5.VsEr9VLdKfjaczK6ltxfybCXKe1z7um', 15000000.00, 0, 0, NULL, NULL),
(13, 'Windsor', '$2y$10$XIYfCtsW134JoR8qxpaITeQl.jPduz//48Jvb0.kJVOVuzDZalF/i', 0.00, 1, 0, '2025-06-10 05:13:21', 'fa-piggy-bank'),
(17, 'Amanda', '$2y$10$r6ObXk884gsO9T6DossRVeA7haVm1pUXFtz2DRgjbF249P/rQaRVe', 15000000.00, 0, 0, NULL, NULL),
(19, 'Luka', '$2y$10$8h1aGuA8Ey/CtfstRlZ5auMWkK8pOMJttDjnGytVKWMIQFPbNNc9C', 15000000.00, 0, 0, NULL, NULL),
(21, 'Dudu', '$2y$10$3GF5C2W8P14SVTu91OhtBOHMLU4AjxJTLJUknz9g7JNPlZMb5foky', 15000000.00, 0, 0, NULL, NULL),
(22, 'Lokes', '$2y$10$mKOHKWFhK1R6X4Q6PlRDueklXtSlgsanSIR7JAlJmmzpLM8oDZHKi', 15000000.00, 0, 0, NULL, NULL),
(23, 'Gabriel', '$2y$10$MKyEB19mJK7dS7yNFZeGSe7Gl3a7snQvpJq0mpjqLiKAZOp8QSs1m', 15000000.00, 0, 0, NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `conjunto`
--
ALTER TABLE `conjunto`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `game_status`
--
ALTER TABLE `game_status`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `game_timer`
--
ALTER TABLE `game_timer`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `jogando`
--
ALTER TABLE `jogando`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `lances`
--
ALTER TABLE `lances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leilao_id` (`leilao_id`),
  ADD KEY `jogador_id` (`jogador_id`);

--
-- Índices de tabela `leiloes`
--
ALTER TABLE `leiloes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jogador_id` (`jogador_id`),
  ADD KEY `jogador_lance_id` (`jogador_lance_id`);

--
-- Índices de tabela `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `objetivos`
--
ALTER TABLE `objetivos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `poupanca`
--
ALTER TABLE `poupanca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_active` (`user_id`,`status`);

--
-- Índices de tabela `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nickname` (`nickname`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `conjunto`
--
ALTER TABLE `conjunto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `game_status`
--
ALTER TABLE `game_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `game_timer`
--
ALTER TABLE `game_timer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `jogando`
--
ALTER TABLE `jogando`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=810;

--
-- AUTO_INCREMENT de tabela `lances`
--
ALTER TABLE `lances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT de tabela `leiloes`
--
ALTER TABLE `leiloes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=384;

--
-- AUTO_INCREMENT de tabela `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44636;

--
-- AUTO_INCREMENT de tabela `poupanca`
--
ALTER TABLE `poupanca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6217;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `lances`
--
ALTER TABLE `lances`
  ADD CONSTRAINT `lances_ibfk_1` FOREIGN KEY (`leilao_id`) REFERENCES `leiloes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lances_ibfk_2` FOREIGN KEY (`jogador_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `leiloes`
--
ALTER TABLE `leiloes`
  ADD CONSTRAINT `leiloes_ibfk_1` FOREIGN KEY (`jogador_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leiloes_ibfk_2` FOREIGN KEY (`jogador_lance_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `poupanca`
--
ALTER TABLE `poupanca`
  ADD CONSTRAINT `poupanca_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `AtualizarJurosPoupanca` ON SCHEDULE EVERY 2 MINUTE STARTS '2025-06-08 18:07:25' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
  UPDATE poupanca
  SET valor_atual = valor_atual * 1.10,
      ultima_atualizacao = CURRENT_TIMESTAMP
  WHERE status = 'ativa'
    AND TIMESTAMPDIFF(MINUTE, ultima_atualizacao, CURRENT_TIMESTAMP) >= 2;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
