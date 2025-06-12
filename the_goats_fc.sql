-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12-Jun-2025 às 17:19
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `the goats fc`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `finance`
--

CREATE TABLE `finance` (
  `id` int(11) NOT NULL,
  `type` enum('Receita','Despesa') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `finance`
--

INSERT INTO `finance` (`id`, `type`, `amount`, `description`) VALUES
(2, 'Receita', 10000.00, 'Patrocínio da Câmara de Vila Verde.'),
(8, 'Receita', 20.00, 'Pagamento de atleta: André Peixoto Mota (Ref: Pagamento Mensal)'),
(11, 'Receita', 20.00, 'Pagamento de Quota - Sócio: Alberto Carlos Amorim Oliveira (2025-06)');

-- --------------------------------------------------------

--
-- Estrutura da tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `login`
--

CREATE TABLE `login` (
  `login_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `login`
--

INSERT INTO `login` (`login_id`, `user_id`, `role`, `login_time`) VALUES
(178, 1, 'admin', '2025-06-12 10:28:13');

-- --------------------------------------------------------

--
-- Estrutura da tabela `marketing`
--

CREATE TABLE `marketing` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `marketing`
--

INSERT INTO `marketing` (`id`, `title`, `type`, `date`, `description`, `created_at`) VALUES
(3, 'Samurai', 'Evento', '2025-06-19', '', '2025-06-12 01:05:36');

-- --------------------------------------------------------

--
-- Estrutura da tabela `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `opponent` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `result` enum('Vitória','Empate','Derrota') NOT NULL,
  `goals_for` int(11) NOT NULL,
  `goals_against` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender` varchar(50) DEFAULT NULL,
  `recipient` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `messages`
--

INSERT INTO `messages` (`id`, `sender`, `recipient`, `content`, `date`) VALUES
(6, 'Andre', 'Gustavo', 'Vou ser titular?', '2025-06-12 12:46:34');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pagamentos_atletas`
--

CREATE TABLE `pagamentos_atletas` (
  `id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pagamentos_atletas`
--

INSERT INTO `pagamentos_atletas` (`id`, `atleta_id`, `valor`, `data_pagamento`, `referencia`, `observacoes`) VALUES
(13, 36, 20.00, '2025-06-12', 'Pagamento Mensal', '');

-- --------------------------------------------------------

--
-- Estrutura da tabela `parcerias`
--

CREATE TABLE `parcerias` (
  `id` int(11) NOT NULL,
  `nome_entidade` varchar(100) NOT NULL,
  `tipo` enum('Protocolo','Patrocínio','Outra') NOT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `parcerias`
--

INSERT INTO `parcerias` (`id`, `nome_entidade`, `tipo`, `data_inicio`, `data_fim`, `descricao`) VALUES
(2, 'IPCA', 'Protocolo', '2025-06-16', '2025-06-20', 'Apresentação de Cursos.');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pending_funcionarios`
--

CREATE TABLE `pending_funcionarios` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pending_players`
--

CREATE TABLE `pending_players` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` int(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `history` text DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pending_socios`
--

CREATE TABLE `pending_socios` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `contacto` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pending_treinadores`
--

CREATE TABLE `pending_treinadores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `history` text DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` int(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `history` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `players`
--

INSERT INTO `players` (`id`, `user_id`, `name`, `phone`, `email`, `birthdate`, `history`) VALUES
(36, 12, 'André Peixoto Mota', 926456288, 'a.peixoto.m.1020@gmail.com', '2006-03-10', 'Joguei no Pico de Regalados e na Ribeira do Neiva.');

-- --------------------------------------------------------

--
-- Estrutura da tabela `player_stats`
--

CREATE TABLE `player_stats` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `goals` int(11) DEFAULT 0,
  `yellow_cards` int(11) DEFAULT 0,
  `red_cards` int(11) DEFAULT 0,
  `minutes_played` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `quotas_socios`
--

CREATE TABLE `quotas_socios` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `mes_ano` varchar(7) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `estado` enum('Pago','Em atraso') DEFAULT 'Pago'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `quotas_socios`
--

INSERT INTO `quotas_socios` (`id`, `socio_id`, `mes_ano`, `valor`, `data_pagamento`, `estado`) VALUES
(6, 1, '2025-06', 20.00, '2025-06-12', 'Pago');

-- --------------------------------------------------------

--
-- Estrutura da tabela `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `type` enum('Treino','Jogo') NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `description` text DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `opponent` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `schedule`
--

INSERT INTO `schedule` (`id`, `type`, `date`, `time`, `description`, `team_id`, `opponent`) VALUES
(21, 'Treino', '2025-06-11', '03:01:00', 'asdsda', 8, ''),
(22, 'Treino', '2025-06-11', '03:05:00', 'Treino fisico', 1, ''),
(23, 'Jogo', '2025-06-12', '03:08:00', '', 1, 'Sbording');

-- --------------------------------------------------------

--
-- Estrutura da tabela `socios`
--

CREATE TABLE `socios` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `contacto` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `data_registo` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `socios`
--

INSERT INTO `socios` (`id`, `user_id`, `nome`, `data_nascimento`, `contacto`, `email`, `morada`, `nif`, `data_registo`) VALUES
(1, 13, 'Alberto Carlos Amorim Oliveira', '2006-07-02', '933069920', 'a32891@alunos.ipca.pt', 'Prado (São Miguel)', '214556280', '2025-06-10');

-- --------------------------------------------------------

--
-- Estrutura da tabela `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `teams`
--

INSERT INTO `teams` (`id`, `name`) VALUES
(8, 'Fut.7 Sub-11 (Benjamim)\r\n'),
(7, 'Fut.7 Sub-13 (Infantis)\r\n'),
(10, 'Fut.7 Sub-7 (Petiz)'),
(9, 'Fut.7 Sub-9 (Traquina)\r\n'),
(6, 'Fut.9 Sub-13 (Infantis)\r\n'),
(5, 'Fut.9 Sub-15 (Iniciados)'),
(1, 'Seniores'),
(4, 'Sub-15 (Iniciados)\r\n'),
(3, 'Sub-17 (Juvenis)\r\n'),
(2, 'Sub-19 (Juniores)\r\n');

-- --------------------------------------------------------

--
-- Estrutura da tabela `team_coaches`
--

CREATE TABLE `team_coaches` (
  `team_id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `team_coaches`
--

INSERT INTO `team_coaches` (`team_id`, `coach_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `team_players`
--

CREATE TABLE `team_players` (
  `team_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `team_players`
--

INSERT INTO `team_players` (`team_id`, `player_id`) VALUES
(1, 36);

-- --------------------------------------------------------

--
-- Estrutura da tabela `treinadores`
--

CREATE TABLE `treinadores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `history` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `treinadores`
--

INSERT INTO `treinadores` (`id`, `user_id`, `name`, `phone`, `email`, `birthdate`, `history`) VALUES
(1, 15, 'Gustavo Sá Viana Martins', '967888354', 'a32890@alunos.ipca.pt', '2006-11-14', 'Sem carreira proficional');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','funcionario','treinador','jogador','socio','adepto') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$.FHOAwFuEXjuV1Hk8qMHGu3BT0S5X8rU.sCXc972T9lMvKfheJfhK', 'admin', 'approved'),
(12, 'Andre', '$2y$10$ZHDJIK3yn2IcMcSq.TckB..XELTqmQOIeUkqcZka.zBPLg0dfJB2y', 'jogador', 'approved'),
(13, 'Alberto', '$2y$10$VYTdxjKoVSzutXz8oFNZ..ykbGq//RJNA3HcwgwZTqbb2.Pv/llE2', 'socio', 'approved'),
(14, 'Vitor', '$2y$10$N3vxtzHgzQsRVO11FU/eqOMEdYu0B2CQXJAOH.ITNUPylRpVwT.Ji', 'funcionario', 'approved'),
(15, 'Gustavo', '$2y$10$sY8z4CwEbRDOe/W/f.gEEOR2QnX5gV2Xl2n/0.4V8zDlfICpfH86u', 'treinador', 'approved'),
(18, 'Miguel', '$2y$10$fPH8amYj6t8ZJ.WBRFF/RedeKe86Gv2qP3ZSuaeJ0ydx5dVnkxU66', 'adepto', 'approved');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `finance`
--
ALTER TABLE `finance`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`login_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `marketing`
--
ALTER TABLE `marketing`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_matches_team_id` (`team_id`);

--
-- Índices para tabela `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `pagamentos_atletas`
--
ALTER TABLE `pagamentos_atletas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atleta_id` (`atleta_id`);

--
-- Índices para tabela `parcerias`
--
ALTER TABLE `parcerias`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `pending_funcionarios`
--
ALTER TABLE `pending_funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `pending_players`
--
ALTER TABLE `pending_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `pending_socios`
--
ALTER TABLE `pending_socios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `pending_treinadores`
--
ALTER TABLE `pending_treinadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `player_stats`
--
ALTER TABLE `player_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `match_id` (`match_id`);

--
-- Índices para tabela `quotas_socios`
--
ALTER TABLE `quotas_socios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `socio_id` (`socio_id`);

--
-- Índices para tabela `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`);

--
-- Índices para tabela `socios`
--
ALTER TABLE `socios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices para tabela `team_coaches`
--
ALTER TABLE `team_coaches`
  ADD PRIMARY KEY (`team_id`,`coach_id`),
  ADD UNIQUE KEY `coach_id` (`coach_id`);

--
-- Índices para tabela `team_players`
--
ALTER TABLE `team_players`
  ADD PRIMARY KEY (`team_id`,`player_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Índices para tabela `treinadores`
--
ALTER TABLE `treinadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `finance`
--
ALTER TABLE `finance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `login`
--
ALTER TABLE `login`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT de tabela `marketing`
--
ALTER TABLE `marketing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `pagamentos_atletas`
--
ALTER TABLE `pagamentos_atletas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `parcerias`
--
ALTER TABLE `parcerias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pending_funcionarios`
--
ALTER TABLE `pending_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pending_players`
--
ALTER TABLE `pending_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pending_socios`
--
ALTER TABLE `pending_socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pending_treinadores`
--
ALTER TABLE `pending_treinadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `player_stats`
--
ALTER TABLE `player_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `quotas_socios`
--
ALTER TABLE `quotas_socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `socios`
--
ALTER TABLE `socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `treinadores`
--
ALTER TABLE `treinadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD CONSTRAINT `fk_user_funcionario` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_team_id` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Limitadores para a tabela `pagamentos_atletas`
--
ALTER TABLE `pagamentos_atletas`
  ADD CONSTRAINT `pagamentos_atletas_ibfk_1` FOREIGN KEY (`atleta_id`) REFERENCES `players` (`id`);

--
-- Limitadores para a tabela `pending_funcionarios`
--
ALTER TABLE `pending_funcionarios`
  ADD CONSTRAINT `pending_funcionarios_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pending_players`
--
ALTER TABLE `pending_players`
  ADD CONSTRAINT `pending_players_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pending_socios`
--
ALTER TABLE `pending_socios`
  ADD CONSTRAINT `pending_socios_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pending_treinadores`
--
ALTER TABLE `pending_treinadores`
  ADD CONSTRAINT `pending_treinadores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `fk_user_player` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `player_stats`
--
ALTER TABLE `player_stats`
  ADD CONSTRAINT `player_stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `player_stats_ibfk_2` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`);

--
-- Limitadores para a tabela `quotas_socios`
--
ALTER TABLE `quotas_socios`
  ADD CONSTRAINT `quotas_socios_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`);

--
-- Limitadores para a tabela `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Limitadores para a tabela `socios`
--
ALTER TABLE `socios`
  ADD CONSTRAINT `fk_user_socio` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `team_coaches`
--
ALTER TABLE `team_coaches`
  ADD CONSTRAINT `team_coaches_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_coaches_ibfk_2` FOREIGN KEY (`coach_id`) REFERENCES `treinadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `team_players`
--
ALTER TABLE `team_players`
  ADD CONSTRAINT `team_players_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `team_players_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`);

--
-- Limitadores para a tabela `treinadores`
--
ALTER TABLE `treinadores`
  ADD CONSTRAINT `fk_user_treinador` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
