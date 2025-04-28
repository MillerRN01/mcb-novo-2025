-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/04/2025 às 05:38
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
-- Banco de dados: `mcb`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `ajuda_contatos`
--

CREATE TABLE `ajuda_contatos` (
  `id_contato` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `valor` varchar(100) NOT NULL,
  `icone` varchar(50) NOT NULL,
  `horario_funcionamento` varchar(100) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ajuda_faq`
--

CREATE TABLE `ajuda_faq` (
  `id_faq` int(11) NOT NULL,
  `pergunta` varchar(255) NOT NULL,
  `resposta` text NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `ordem` int(11) DEFAULT 0,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ajuda_redes_sociais`
--

CREATE TABLE `ajuda_redes_sociais` (
  `id_rede` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icone` varchar(50) NOT NULL,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ajuda_tutoriais`
--

CREATE TABLE `ajuda_tutoriais` (
  `id_tutorial` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `url_video` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `duracao` varchar(20) DEFAULT NULL,
  `categoria` varchar(50) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `caixa`
--

CREATE TABLE `caixa` (
  `id_caixa` int(11) NOT NULL,
  `data_abertura` datetime NOT NULL,
  `data_fechamento` datetime DEFAULT NULL,
  `funcionario_id` int(11) NOT NULL DEFAULT 0,
  `valor_abertura` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_fechamento` decimal(10,2) DEFAULT NULL,
  `status` enum('aberto','fechado') DEFAULT 'aberto',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_financeiras`
--

CREATE TABLE `categorias_financeiras` (
  `id_categoria` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `descricao` text DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_produto`
--

CREATE TABLE `categorias_produto` (
  `id_categoria` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias_produto`
--

INSERT INTO `categorias_produto` (`id_categoria`, `nome`, `descricao`, `criacao`, `atualizacao`) VALUES
(1, 'Biscoito', NULL, '2025-04-27 13:50:25', '2025-04-27 13:50:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_pf`
--

CREATE TABLE `clientes_pf` (
  `id_cliente` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` char(15) NOT NULL,
  `rg` varchar(15) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` char(15) NOT NULL,
  `whatsapp` char(15) DEFAULT NULL,
  `endereco_id` int(11) NOT NULL,
  `limite_credito` decimal(10,2) DEFAULT 0.00,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_pj`
--

CREATE TABLE `clientes_pj` (
  `id_cliente` int(11) NOT NULL,
  `razao_social` varchar(255) NOT NULL,
  `nome_fantasia` varchar(255) DEFAULT NULL,
  `cnpj` char(18) NOT NULL,
  `ie` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` char(15) NOT NULL,
  `whatsapp` char(15) DEFAULT NULL,
  `endereco_id` int(11) NOT NULL,
  `limite_credito` decimal(10,2) DEFAULT 0.00,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_backup`
--

CREATE TABLE `config_backup` (
  `id_config` int(11) NOT NULL,
  `backup_auto` tinyint(1) DEFAULT 0,
  `frequencia` enum('diario','semanal','mensal') DEFAULT 'diario',
  `hora_backup` time DEFAULT '02:00:00',
  `destino_backup` varchar(255) DEFAULT NULL,
  `ultimo_backup` datetime DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_email`
--

CREATE TABLE `config_email` (
  `id_config` int(11) NOT NULL,
  `servidor_smtp` varchar(100) NOT NULL,
  `porta` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ssl` tinyint(1) DEFAULT 1,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_empresa`
--

CREATE TABLE `config_empresa` (
  `id_config` int(11) NOT NULL,
  `nome_empresa` varchar(100) NOT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `endereco_id` int(11) DEFAULT NULL,
  `logo` longblob DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `config_empresa`
--

INSERT INTO `config_empresa` (`id_config`, `nome_empresa`, `cnpj`, `telefone`, `email`, `endereco_id`, `logo`, `logo_url`, `criacao`, `atualizacao`) VALUES
(1, 'comercio sa', '154948987987098485', '849980980', 'qwdeasda@gmail.com', NULL, NULL, NULL, '2025-04-28 02:13:26', '2025-04-28 02:13:28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_notificacoes`
--

CREATE TABLE `config_notificacoes` (
  `id_config` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_notificacao_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `sistema` tinyint(1) DEFAULT 1,
  `email` tinyint(1) DEFAULT 0,
  `push` tinyint(1) DEFAULT 0,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `config_preferencias`
--

CREATE TABLE `config_preferencias` (
  `id_config` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tema` enum('claro','escuro','azul','verde') DEFAULT 'claro',
  `tamanho_fonte` enum('pequeno','medio','grande') DEFAULT 'medio',
  `menu_reduzido` tinyint(1) DEFAULT 0,
  `notificacoes_sistema` tinyint(1) DEFAULT 1,
  `notificacoes_email` tinyint(1) DEFAULT 0,
  `notificacoes_push` tinyint(1) DEFAULT 0,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_bancarias`
--

CREATE TABLE `contas_bancarias` (
  `id_conta` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `banco` varchar(100) NOT NULL,
  `agencia` varchar(20) NOT NULL,
  `conta` varchar(20) NOT NULL,
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_atual` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ativa` tinyint(1) DEFAULT 1,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `dashboard_preferencias`
--

CREATE TABLE `dashboard_preferencias` (
  `id_preferencia` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `widget_id` int(11) NOT NULL,
  `posicao` int(11) NOT NULL,
  `visivel` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `dashboard_widgets`
--

CREATE TABLE `dashboard_widgets` (
  `id_widget` int(11) NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `icone` varchar(30) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `componente` varchar(50) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ordem_padrao` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `dashboard_widgets`
--

INSERT INTO `dashboard_widgets` (`id_widget`, `titulo`, `descricao`, `icone`, `tipo`, `componente`, `ativo`, `ordem_padrao`) VALUES
(1, 'Resumo de Vendas', 'Mostra o total de vendas no período selecionado', 'currency-dollar', 'resumo', 'resumo_vendas', 1, 1),
(2, 'Gráfico de Vendas', 'Exibe um gráfico com o histórico de vendas', 'bar-chart', 'grafico', 'grafico_vendas', 1, 2),
(3, 'Atividades Recentes', 'Mostra as últimas atividades no sistema', 'activity', 'lista', 'atividades_recentes', 1, 3),
(4, 'Top Produtos', 'Lista os produtos mais vendidos', 'trophy', 'lista', 'top_produtos', 1, 4),
(5, 'Metas do Mês', 'Acompanhamento de metas de vendas', 'bullseye', 'resumo', 'metas_mes', 1, 5),
(6, 'Clientes Recentes', 'Mostra os últimos clientes cadastrados', 'people', 'lista', 'clientes_recentes', 1, 6);

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id_endereco` int(11) NOT NULL,
  `cep` char(9) NOT NULL,
  `logradouro` varchar(255) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `complemento` varchar(50) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

CREATE TABLE `estoque` (
  `id_estoque` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `tipo_movimentacao` enum('entrada','saida') NOT NULL,
  `quantidade_estoque` int(11) NOT NULL DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 0,
  `estoque_maximo` int(11) DEFAULT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL,
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fiado`
--

CREATE TABLE `fiado` (
  `id_fiado` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_cliente` enum('pf','pj') NOT NULL,
  `data_abertura` datetime NOT NULL DEFAULT current_timestamp(),
  `data_quitacao` datetime DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('aberto','quitado','parcial') NOT NULL DEFAULT 'aberto',
  `funcionario_id` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedor`
--

CREATE TABLE `fornecedor` (
  `id_fornecedor` int(11) NOT NULL,
  `cpf_cnpj` char(18) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `nome_fantasia` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` char(15) DEFAULT NULL,
  `whatsapp` char(15) DEFAULT NULL,
  `endereco_id` int(11) DEFAULT NULL,
  `optante_simples_nacional` tinyint(1) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario`
--

CREATE TABLE `funcionario` (
  `id_funcionario` int(11) NOT NULL,
  `nome_completo` varchar(100) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `cpf` char(15) NOT NULL,
  `telefone` char(15) DEFAULT NULL,
  `endereco_id` int(11) DEFAULT NULL,
  `login_id` int(11) DEFAULT NULL,
  `data_admissao` date NOT NULL,
  `data_demissao` date DEFAULT NULL,
  `status` enum('ativo','ferias','licenca') DEFAULT 'ativo',
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionario`
--

INSERT INTO `funcionario` (`id_funcionario`, `nome_completo`, `cargo`, `cpf`, `telefone`, `endereco_id`, `login_id`, `data_admissao`, `data_demissao`, `status`, `atualizacao`) VALUES
(1, 'Hector Miller', '', '', NULL, NULL, 1, '2025-04-27', NULL, 'ativo', '2025-04-27 13:48:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_fiado`
--

CREATE TABLE `historico_fiado` (
  `id_historico` int(11) NOT NULL,
  `fiado_id` int(11) NOT NULL,
  `tipo_operacao` enum('abertura','pagamento','ajuste','quitacao') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_operacao` datetime NOT NULL DEFAULT current_timestamp(),
  `funcionario_id` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `login`
--

CREATE TABLE `login` (
  `id_login` int(11) NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `email` varchar(80) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` blob DEFAULT NULL,
  `nivel_acesso` enum('admin','funcionario') NOT NULL DEFAULT 'funcionario',
  `dante` enum('admin','funcionario') NOT NULL DEFAULT 'funcionario',
  `ativo` tinyint(1) DEFAULT 1,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `login`
--

INSERT INTO `login` (`id_login`, `usuario`, `email`, `senha`, `foto`, `nivel_acesso`, `dante`, `ativo`, `criacao`, `atualizacao`) VALUES
(1, 'Miller', 'farmmiller2@gmail.com', '$2y$10$TlGSi5l.5fsqTgvUco/T5eE1DIpSNgtFPNXvLIEMi2T7g63gPW5lm', 0x6173736574732f75706c6f6164732f66756e63696f6e6172696f2f363830653335633535636639615f756e6e616d65642e6a7067, 'admin', 'admin', 1, '2025-04-27 13:48:53', '2025-04-27 22:11:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_caixa`
--

CREATE TABLE `movimentacoes_caixa` (
  `id_movimentacao` int(11) NOT NULL,
  `caixa_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','cartao_debito','cartao_credito','pix','transferencia','outro') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `venda_id` int(11) DEFAULT NULL,
  `data_movimentacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_financeiras`
--

CREATE TABLE `movimentacoes_financeiras` (
  `id_movimentacao` int(11) NOT NULL,
  `tipo` enum('receita','despesa','transferencia') NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_id` int(11) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_operacao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `forma_pagamento` enum('dinheiro','conta_bancaria','cartao_credito','cartao_debito','pix','transferencia','outro') NOT NULL,
  `status` enum('pendente','pago','cancelado') DEFAULT 'pendente',
  `venda_id` int(11) DEFAULT NULL,
  `fiado_id` int(11) DEFAULT NULL,
  `funcionario_id` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parcelas_fiado`
--

CREATE TABLE `parcelas_fiado` (
  `id_parcela` int(11) NOT NULL,
  `fiado_id` int(11) NOT NULL,
  `numero_parcela` int(11) NOT NULL,
  `valor_parcela` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `status` enum('pendente','pago','atrasado') NOT NULL DEFAULT 'pendente',
  `forma_pagamento` enum('dinheiro','cartao','pix','transferencia') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id_produto` int(11) NOT NULL,
  `produto` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `foto_produto` mediumblob DEFAULT NULL,
  `foto_comprovante` mediumblob DEFAULT NULL,
  `status` enum('ativo','esgotado','vencido') DEFAULT 'ativo',
  `criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `estoque_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_notificacao`
--

CREATE TABLE `tipos_notificacao` (
  `id_tipo` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo_padrao` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipos_notificacao`
--

INSERT INTO `tipos_notificacao` (`id_tipo`, `codigo`, `nome`, `descricao`, `ativo_padrao`) VALUES
(1, 'nova_venda', 'Nova Venda', 'Notificação quando uma nova venda é registrada', 1),
(2, 'pagamento_recebido', 'Pagamento Recebido', 'Notificação quando um pagamento é recebido', 1),
(3, 'estoque_baixo', 'Estoque Baixo', 'Notificação quando um produto está com estoque baixo', 1),
(4, 'backup_concluido', 'Backup Concluído', 'Notificação quando um backup é concluído', 1),
(5, 'atualizacao_sistema', 'Atualização do Sistema', 'Notificação sobre atualizações do sistema', 1),
(6, 'tarefa_pendente', 'Tarefa Pendente', 'Notificação sobre tarefas pendentes', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `transferencias`
--

CREATE TABLE `transferencias` (
  `id_transferencia` int(11) NOT NULL,
  `conta_origem_id` int(11) NOT NULL,
  `conta_destino_id` int(11) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_transferencia` date NOT NULL,
  `taxa` decimal(15,2) DEFAULT 0.00,
  `descricao` varchar(255) DEFAULT NULL,
  `funcionario_id` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id_venda` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_cliente` enum('pf','pj') NOT NULL,
  `funcionario_id` int(11) DEFAULT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `data_venda` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `ajuda_contatos`
--
ALTER TABLE `ajuda_contatos`
  ADD PRIMARY KEY (`id_contato`);

--
-- Índices de tabela `ajuda_faq`
--
ALTER TABLE `ajuda_faq`
  ADD PRIMARY KEY (`id_faq`);

--
-- Índices de tabela `ajuda_redes_sociais`
--
ALTER TABLE `ajuda_redes_sociais`
  ADD PRIMARY KEY (`id_rede`);

--
-- Índices de tabela `ajuda_tutoriais`
--
ALTER TABLE `ajuda_tutoriais`
  ADD PRIMARY KEY (`id_tutorial`);

--
-- Índices de tabela `caixa`
--
ALTER TABLE `caixa`
  ADD PRIMARY KEY (`id_caixa`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `categorias_financeiras`
--
ALTER TABLE `categorias_financeiras`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Índices de tabela `categorias_produto`
--
ALTER TABLE `categorias_produto`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `clientes_pf`
--
ALTER TABLE `clientes_pf`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `endereco_id` (`endereco_id`);

--
-- Índices de tabela `clientes_pj`
--
ALTER TABLE `clientes_pj`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `endereco_id` (`endereco_id`);

--
-- Índices de tabela `config_backup`
--
ALTER TABLE `config_backup`
  ADD PRIMARY KEY (`id_config`);

--
-- Índices de tabela `config_email`
--
ALTER TABLE `config_email`
  ADD PRIMARY KEY (`id_config`);

--
-- Índices de tabela `config_empresa`
--
ALTER TABLE `config_empresa`
  ADD PRIMARY KEY (`id_config`),
  ADD KEY `endereco_id` (`endereco_id`);

--
-- Índices de tabela `config_notificacoes`
--
ALTER TABLE `config_notificacoes`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`,`tipo_notificacao_id`),
  ADD KEY `tipo_notificacao_id` (`tipo_notificacao_id`);

--
-- Índices de tabela `config_preferencias`
--
ALTER TABLE `config_preferencias`
  ADD PRIMARY KEY (`id_config`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `contas_bancarias`
--
ALTER TABLE `contas_bancarias`
  ADD PRIMARY KEY (`id_conta`);

--
-- Índices de tabela `dashboard_preferencias`
--
ALTER TABLE `dashboard_preferencias`
  ADD PRIMARY KEY (`id_preferencia`),
  ADD KEY `widget_id` (`widget_id`);

--
-- Índices de tabela `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  ADD PRIMARY KEY (`id_widget`);

--
-- Índices de tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id_endereco`);

--
-- Índices de tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`id_estoque`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `fiado`
--
ALTER TABLE `fiado`
  ADD PRIMARY KEY (`id_fiado`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  ADD PRIMARY KEY (`id_fornecedor`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `endereco_id` (`endereco_id`);

--
-- Índices de tabela `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`id_funcionario`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `endereco_id` (`endereco_id`),
  ADD KEY `login_id` (`login_id`);

--
-- Índices de tabela `historico_fiado`
--
ALTER TABLE `historico_fiado`
  ADD PRIMARY KEY (`id_historico`),
  ADD KEY `fiado_id` (`fiado_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id_login`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `movimentacoes_caixa`
--
ALTER TABLE `movimentacoes_caixa`
  ADD PRIMARY KEY (`id_movimentacao`),
  ADD KEY `caixa_id` (`caixa_id`),
  ADD KEY `venda_id` (`venda_id`);

--
-- Índices de tabela `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  ADD PRIMARY KEY (`id_movimentacao`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `conta_id` (`conta_id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `fiado_id` (`fiado_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `parcelas_fiado`
--
ALTER TABLE `parcelas_fiado`
  ADD PRIMARY KEY (`id_parcela`),
  ADD KEY `fiado_id` (`fiado_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id_produto`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `fk_produto_estoque` (`estoque_id`);

--
-- Índices de tabela `tipos_notificacao`
--
ALTER TABLE `tipos_notificacao`
  ADD PRIMARY KEY (`id_tipo`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `transferencias`
--
ALTER TABLE `transferencias`
  ADD PRIMARY KEY (`id_transferencia`),
  ADD KEY `conta_origem_id` (`conta_origem_id`),
  ADD KEY `conta_destino_id` (`conta_destino_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id_venda`),
  ADD KEY `funcionario_id` (`funcionario_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `ajuda_contatos`
--
ALTER TABLE `ajuda_contatos`
  MODIFY `id_contato` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ajuda_faq`
--
ALTER TABLE `ajuda_faq`
  MODIFY `id_faq` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ajuda_redes_sociais`
--
ALTER TABLE `ajuda_redes_sociais`
  MODIFY `id_rede` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ajuda_tutoriais`
--
ALTER TABLE `ajuda_tutoriais`
  MODIFY `id_tutorial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caixa`
--
ALTER TABLE `caixa`
  MODIFY `id_caixa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias_financeiras`
--
ALTER TABLE `categorias_financeiras`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias_produto`
--
ALTER TABLE `categorias_produto`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `clientes_pf`
--
ALTER TABLE `clientes_pf`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes_pj`
--
ALTER TABLE `clientes_pj`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `config_backup`
--
ALTER TABLE `config_backup`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `config_email`
--
ALTER TABLE `config_email`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `config_empresa`
--
ALTER TABLE `config_empresa`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `config_notificacoes`
--
ALTER TABLE `config_notificacoes`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `config_preferencias`
--
ALTER TABLE `config_preferencias`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas_bancarias`
--
ALTER TABLE `contas_bancarias`
  MODIFY `id_conta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dashboard_preferencias`
--
ALTER TABLE `dashboard_preferencias`
  MODIFY `id_preferencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  MODIFY `id_widget` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id_endereco` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estoque`
--
ALTER TABLE `estoque`
  MODIFY `id_estoque` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fiado`
--
ALTER TABLE `fiado`
  MODIFY `id_fiado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  MODIFY `id_fornecedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `id_funcionario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `historico_fiado`
--
ALTER TABLE `historico_fiado`
  MODIFY `id_historico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `login`
--
ALTER TABLE `login`
  MODIFY `id_login` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `movimentacoes_caixa`
--
ALTER TABLE `movimentacoes_caixa`
  MODIFY `id_movimentacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  MODIFY `id_movimentacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parcelas_fiado`
--
ALTER TABLE `parcelas_fiado`
  MODIFY `id_parcela` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id_produto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_notificacao`
--
ALTER TABLE `tipos_notificacao`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id_transferencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id_venda` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `caixa`
--
ALTER TABLE `caixa`
  ADD CONSTRAINT `caixa_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`);

--
-- Restrições para tabelas `clientes_pf`
--
ALTER TABLE `clientes_pf`
  ADD CONSTRAINT `clientes_pf_ibfk_1` FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id_endereco`);

--
-- Restrições para tabelas `clientes_pj`
--
ALTER TABLE `clientes_pj`
  ADD CONSTRAINT `clientes_pj_ibfk_1` FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id_endereco`);

--
-- Restrições para tabelas `config_empresa`
--
ALTER TABLE `config_empresa`
  ADD CONSTRAINT `config_empresa_ibfk_1` FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id_endereco`);

--
-- Restrições para tabelas `config_notificacoes`
--
ALTER TABLE `config_notificacoes`
  ADD CONSTRAINT `config_notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `login` (`id_login`),
  ADD CONSTRAINT `config_notificacoes_ibfk_2` FOREIGN KEY (`tipo_notificacao_id`) REFERENCES `tipos_notificacao` (`id_tipo`);

--
-- Restrições para tabelas `config_preferencias`
--
ALTER TABLE `config_preferencias`
  ADD CONSTRAINT `config_preferencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `login` (`id_login`);

--
-- Restrições para tabelas `dashboard_preferencias`
--
ALTER TABLE `dashboard_preferencias`
  ADD CONSTRAINT `dashboard_preferencias_ibfk_1` FOREIGN KEY (`widget_id`) REFERENCES `dashboard_widgets` (`id_widget`);

--
-- Restrições para tabelas `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id_produto`) ON DELETE CASCADE;

--
-- Restrições para tabelas `fiado`
--
ALTER TABLE `fiado`
  ADD CONSTRAINT `fiado_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`);

--
-- Restrições para tabelas `fornecedor`
--
ALTER TABLE `fornecedor`
  ADD CONSTRAINT `fornecedor_ibfk_1` FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id_endereco`);

--
-- Restrições para tabelas `funcionario`
--
ALTER TABLE `funcionario`
  ADD CONSTRAINT `funcionario_ibfk_1` FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id_endereco`),
  ADD CONSTRAINT `funcionario_ibfk_2` FOREIGN KEY (`login_id`) REFERENCES `login` (`id_login`);

--
-- Restrições para tabelas `historico_fiado`
--
ALTER TABLE `historico_fiado`
  ADD CONSTRAINT `historico_fiado_ibfk_1` FOREIGN KEY (`fiado_id`) REFERENCES `fiado` (`id_fiado`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_fiado_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`);

--
-- Restrições para tabelas `movimentacoes_caixa`
--
ALTER TABLE `movimentacoes_caixa`
  ADD CONSTRAINT `movimentacoes_caixa_ibfk_1` FOREIGN KEY (`caixa_id`) REFERENCES `caixa` (`id_caixa`),
  ADD CONSTRAINT `movimentacoes_caixa_ibfk_2` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id_venda`);

--
-- Restrições para tabelas `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id_categoria`),
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_2` FOREIGN KEY (`conta_id`) REFERENCES `contas_bancarias` (`id_conta`),
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_3` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id_venda`),
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_4` FOREIGN KEY (`fiado_id`) REFERENCES `fiado` (`id_fiado`),
  ADD CONSTRAINT `movimentacoes_financeiras_ibfk_5` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`);

--
-- Restrições para tabelas `parcelas_fiado`
--
ALTER TABLE `parcelas_fiado`
  ADD CONSTRAINT `parcelas_fiado_ibfk_1` FOREIGN KEY (`fiado_id`) REFERENCES `fiado` (`id_fiado`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produto_estoque` FOREIGN KEY (`estoque_id`) REFERENCES `estoque` (`id_estoque`) ON DELETE CASCADE,
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_produto` (`id_categoria`),
  ADD CONSTRAINT `produtos_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id_fornecedor`);

--
-- Restrições para tabelas `transferencias`
--
ALTER TABLE `transferencias`
  ADD CONSTRAINT `transferencias_ibfk_1` FOREIGN KEY (`conta_origem_id`) REFERENCES `contas_bancarias` (`id_conta`),
  ADD CONSTRAINT `transferencias_ibfk_2` FOREIGN KEY (`conta_destino_id`) REFERENCES `contas_bancarias` (`id_conta`),
  ADD CONSTRAINT `transferencias_ibfk_3` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_funcionario`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendas_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id_produto`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
