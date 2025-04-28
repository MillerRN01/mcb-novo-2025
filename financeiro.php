<?php
require_once 'conexao_pdo.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Ações do CRUD
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'dashboard';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($acao == 'cadastrar_movimentacao') {
        try {
            $pdo->beginTransaction();
            
            $tipo = $_POST['tipo'];
            $categoria_id = $_POST['categoria_id'];
            $conta_id = $_POST['conta_id'];
            $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
            $data_operacao = $_POST['data_operacao'];
            $data_vencimento = $_POST['data_vencimento'] ?? null;
            $descricao = $_POST['descricao'];
            $forma_pagamento = $_POST['forma_pagamento'];
            $status = $_POST['status'];
            $observacoes = $_POST['observacoes'];
            $funcionario_id = $_SESSION['id_funcionario'];
            
            // Insere a movimentação
            $stmt = $pdo->prepare("INSERT INTO movimentacoes_financeiras 
                                  (tipo, categoria_id, conta_id, valor, data_operacao, data_vencimento, 
                                   descricao, forma_pagamento, status, funcionario_id, observacoes)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tipo, $categoria_id, $conta_id, $valor, $data_operacao, $data_vencimento,
                $descricao, $forma_pagamento, $status, $funcionario_id, $observacoes
            ]);
            
            // Atualiza saldo da conta
            if ($status == 'pago') {
                $sinal = ($tipo == 'receita') ? 1 : -1;
                $stmt = $pdo->prepare("UPDATE contas_bancarias 
                                      SET saldo_atual = saldo_atual + (? * ?)
                                      WHERE id_conta = ?");
                $stmt->execute([$valor, $sinal, $conta_id]);
            }
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Movimentação registrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: financeiro.php?acao=listar_movimentacoes");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao registrar movimentação: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
    elseif ($acao == 'registrar_transferencia') {
        try {
            $pdo->beginTransaction();
            
            $conta_origem_id = $_POST['conta_origem_id'];
            $conta_destino_id = $_POST['conta_destino_id'];
            $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
            $data_transferencia = $_POST['data_transferencia'];
            $taxa = str_replace(['.', ','], ['', '.'], $_POST['taxa'] ?? 0);
            $descricao = $_POST['descricao'];
            $observacoes = $_POST['observacoes'];
            $funcionario_id = $_SESSION['id_funcionario'];
            
            // Insere a transferência
            $stmt = $pdo->prepare("INSERT INTO transferencias 
                                  (conta_origem_id, conta_destino_id, valor, data_transferencia, 
                                   taxa, descricao, funcionario_id, observacoes)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $conta_origem_id, $conta_destino_id, $valor, $data_transferencia,
                $taxa, $descricao, $funcionario_id, $observacoes
            ]);
            
            // Atualiza saldo das contas
            $stmt = $pdo->prepare("UPDATE contas_bancarias 
                                  SET saldo_atual = saldo_atual - ? 
                                  WHERE id_conta = ?");
            $stmt->execute([$valor + $taxa, $conta_origem_id]);
            
            $stmt = $pdo->prepare("UPDATE contas_bancarias 
                                  SET saldo_atual = saldo_atual + ? 
                                  WHERE id_conta = ?");
            $stmt->execute([$valor, $conta_destino_id]);
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Transferência registrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: financeiro.php?acao=listar_transferencias");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao registrar transferência: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
    elseif ($acao == 'atualizar_status') {
        try {
            $pdo->beginTransaction();
            
            $movimentacao_id = $_POST['movimentacao_id'];
            $novo_status = $_POST['novo_status'];
            $data_pagamento = ($novo_status == 'pago') ? date('Y-m-d') : null;
            
            // Busca a movimentação atual
            $stmt = $pdo->prepare("SELECT tipo, conta_id, valor FROM movimentacoes_financeiras WHERE id_movimentacao = ?");
            $stmt->execute([$movimentacao_id]);
            $movimentacao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Atualiza o status
            $stmt = $pdo->prepare("UPDATE movimentacoes_financeiras 
                                  SET status = ?, data_pagamento = ?
                                  WHERE id_movimentacao = ?");
            $stmt->execute([$novo_status, $data_pagamento, $movimentacao_id]);
            
            // Atualiza saldo da conta se necessário
            if ($novo_status == 'pago') {
                $sinal = ($movimentacao['tipo'] == 'receita') ? 1 : -1;
                $stmt = $pdo->prepare("UPDATE contas_bancarias 
                                      SET saldo_atual = saldo_atual + (? * ?)
                                      WHERE id_conta = ?");
                $stmt->execute([$movimentacao['valor'], $sinal, $movimentacao['conta_id']]);
            } elseif ($movimentacao['data_pagamento'] !== null) {
                // Reverte o saldo se estava pago e foi alterado
                $sinal = ($movimentacao['tipo'] == 'receita') ? -1 : 1;
                $stmt = $pdo->prepare("UPDATE contas_bancarias 
                                      SET saldo_atual = saldo_atual + (? * ?)
                                      WHERE id_conta = ?");
                $stmt->execute([$movimentacao['valor'], $sinal, $movimentacao['conta_id']]);
            }
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Status atualizado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao atualizar status: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}

// Dados para o dashboard
if ($acao == 'dashboard') {
    // Saldo total
    $stmt = $pdo->query("SELECT SUM(saldo_atual) as saldo_total FROM contas_bancarias WHERE ativa = TRUE");
    $saldo_total = $stmt->fetch(PDO::FETCH_ASSOC)['saldo_total'];
    
    // Receitas do mês
    $stmt = $pdo->prepare("SELECT SUM(valor) as total 
                          FROM movimentacoes_financeiras 
                          WHERE tipo = 'receita' AND status = 'pago'
                          AND MONTH(data_pagamento) = MONTH(CURRENT_DATE())
                          AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $receitas_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Despesas do mês
    $stmt = $pdo->prepare("SELECT SUM(valor) as total 
                          FROM movimentacoes_financeiras 
                          WHERE tipo = 'despesa' AND status = 'pago'
                          AND MONTH(data_pagamento) = MONTH(CURRENT_DATE())
                          AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $despesas_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contas a pagar
    $stmt = $pdo->query("SELECT COUNT(*) as total 
                        FROM movimentacoes_financeiras 
                        WHERE tipo = 'despesa' AND status = 'pendente'
                        AND data_vencimento <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");
    $contas_pagar = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contas a receber
    $stmt = $pdo->query("SELECT COUNT(*) as total 
                        FROM movimentacoes_financeiras 
                        WHERE tipo = 'receita' AND status = 'pendente'
                        AND data_vencimento <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");
    $contas_receber = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Últimas movimentações
    $stmt = $pdo->query("SELECT m.*, c.nome as categoria_nome, cb.nome as conta_nome
                        FROM movimentacoes_financeiras m
                        LEFT JOIN categorias_financeiras c ON m.categoria_id = c.id_categoria
                        JOIN contas_bancarias cb ON m.conta_id = cb.id_conta
                        ORDER BY m.data_operacao DESC, m.criacao DESC
                        LIMIT 10");
    $ultimas_movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listagem de movimentações
if ($acao == 'listar_movimentacoes') {
    $filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $filtro_status = isset($_GET['status']) ? $_GET['status'] : null;
    $filtro_conta = isset($_GET['conta_id']) ? $_GET['conta_id'] : null;
    $filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
    $filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
    
    $sql = "SELECT m.*, c.nome as categoria_nome, cb.nome as conta_nome,
                   f.nome_completo as funcionario_nome
            FROM movimentacoes_financeiras m
            LEFT JOIN categorias_financeiras c ON m.categoria_id = c.id_categoria
            JOIN contas_bancarias cb ON m.conta_id = cb.id_conta
            JOIN funcionario f ON m.funcionario_id = f.id_funcionario
            WHERE m.data_operacao BETWEEN ? AND ?";
    
    $params = [$filtro_data_inicio, $filtro_data_fim];
    
    if ($filtro_tipo) {
        $sql .= " AND m.tipo = ?";
        $params[] = $filtro_tipo;
    }
    
    if ($filtro_status) {
        $sql .= " AND m.status = ?";
        $params[] = $filtro_status;
    }
    
    if ($filtro_conta) {
        $sql .= " AND m.conta_id = ?";
        $params[] = $filtro_conta;
    }
    
    $sql .= " ORDER BY m.data_operacao DESC, m.criacao DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listagem de transferências
if ($acao == 'listar_transferencias') {
    $filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
    $filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT t.*, 
                                  co.nome as conta_origem_nome,
                                  cd.nome as conta_destino_nome,
                                  f.nome_completo as funcionario_nome
                           FROM transferencias t
                           JOIN contas_bancarias co ON t.conta_origem_id = co.id_conta
                           JOIN contas_bancarias cd ON t.conta_destino_id = cd.id_conta
                           JOIN funcionario f ON t.funcionario_id = f.id_funcionario
                           WHERE t.data_transferencia BETWEEN ? AND ?
                           ORDER BY t.data_transferencia DESC, t.criacao DESC");
    $stmt->execute([$filtro_data_inicio, $filtro_data_fim]);
    $transferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca contas bancárias para selects
$stmt = $pdo->query("SELECT * FROM contas_bancarias WHERE ativa = TRUE ORDER BY nome");
$contas_bancarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca categorias financeiras para selects
$stmt = $pdo->query("SELECT * FROM categorias_financeiras ORDER BY tipo, nome");
$categorias_financeiras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Financeiro</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .card-saldo {
            border-left: 4px solid #28a745;
        }
        .card-receitas {
            border-left: 4px solid #17a2b8;
        }
        .card-despesas {
            border-left: 4px solid #dc3545;
        }
        .card-pagar {
            border-left: 4px solid #ffc107;
        }
        .card-receber {
            border-left: 4px solid #6610f2;
        }
        .badge-status {
            font-size: 0.9em;
        }
        .badge-pendente {
            background-color: #ffc107;
            color: #000;
        }
        .badge-pago {
            background-color: #28a745;
        }
        .badge-cancelado {
            background-color: #dc3545;
        }
        .receita {
            color: #28a745;
        }
        .despesa {
            color: #dc3545;
        }
        .transferencia {
            color: #17a2b8;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-graph-up"></i> Gestão Financeira</h2>
        
        <?php include 'mensagem.php'; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'dashboard' ? 'active' : '' ?>" href="financeiro.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'listar_movimentacoes' ? 'active' : '' ?>" href="financeiro.php?acao=listar_movimentacoes">Movimentações</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'listar_transferencias' ? 'active' : '' ?>" href="financeiro.php?acao=listar_transferencias">Transferências</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'cadastrar_movimentacao' ? 'active' : '' ?>" href="financeiro.php?acao=cadastrar_movimentacao">Nova Movimentação</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'registrar_transferencia' ? 'active' : '' ?>" href="financeiro.php?acao=registrar_transferencia">Nova Transferência</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <?php if ($acao == 'dashboard'): ?>
                    <!-- Dashboard Financeiro -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card card-saldo h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo Total</h5>
                                    <p class="card-text display-6">R$ <?= number_format($saldo_total, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-receitas h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Receitas (Mês)</h5>
                                    <p class="card-text display-6">R$ <?= number_format($receitas_mes, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-despesas h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Despesas (Mês)</h5>
                                    <p class="card-text display-6">R$ <?= number_format($despesas_mes, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Resultado (Mês)</h5>
                                    <p class="card-text display-6 <?= ($receitas_mes - $despesas_mes) >= 0 ? 'text-success' : 'text-danger' ?>">
                                        R$ <?= number_format($receitas_mes - $despesas_mes, 2, ',', '.') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="card card-receber h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Contas a Receber (7 dias)</h5>
                                    <p class="card-text display-6"><?= $contas_receber ?></p>
                                    <a href="financeiro.php?acao=listar_movimentacoes&tipo=receita&status=pendente" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card card-pagar h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Contas a Pagar (7 dias)</h5>
                                    <p class="card-text display-6"><?= $contas_pagar ?></p>
                                    <a href="financeiro.php?acao=listar_movimentacoes&tipo=despesa&status=pendente" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Últimas Movimentações</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Conta</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_movimentacoes as $mov): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($mov['data_operacao'])) ?></td>
                                    <td>
                                        <span class="badge <?= $mov['tipo'] == 'receita' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= $mov['descricao'] ?></td>
                                    <td class="<?= $mov['tipo'] == 'receita' ? 'receita' : 'despesa' ?>">
                                        R$ <?= number_format($mov['valor'], 2, ',', '.') ?>
                                    </td>
                                    <td><?= $mov['conta_nome'] ?></td>
                                    <td>
                                        <span class="badge badge-status <?= 'badge-' . $mov['status'] ?>">
                                            <?= ucfirst($mov['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'listar_movimentacoes'): ?>
                    <!-- Listagem de movimentações -->
                    <form method="get" class="mb-4">
                        <input type="hidden" name="acao" value="listar_movimentacoes">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos</option>
                                    <option value="receita" <?= $filtro_tipo == 'receita' ? 'selected' : '' ?>>Receita</option>
                                    <option value="despesa" <?= $filtro_tipo == 'despesa' ? 'selected' : '' ?>>Despesa</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendente" <?= $filtro_status == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="pago" <?= $filtro_status == 'pago' ? 'selected' : '' ?>>Pago</option>
                                    <option value="cancelado" <?= $filtro_status == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="conta_id" class="form-label">Conta</label>
                                <select class="form-select" id="conta_id" name="conta_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($contas_bancarias as $conta): ?>
                                    <option value="<?= $conta['id_conta'] ?>" <?= $filtro_conta == $conta['id_conta'] ? 'selected' : '' ?>>
                                        <?= $conta['nome'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $filtro_data_inicio ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $filtro_data_fim ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-movimentacoes">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Conta</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($mov['data_operacao'])) ?></td>
                                    <td>
                                        <span class="badge <?= $mov['tipo'] == 'receita' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= $mov['descricao'] ?></td>
                                    <td><?= $mov['categoria_nome'] ?? '--' ?></td>
                                    <td class="<?= $mov['tipo'] == 'receita' ? 'receita' : 'despesa' ?>">
                                        R$ <?= number_format($mov['valor'], 2, ',', '.') ?>
                                    </td>
                                    <td><?= $mov['conta_nome'] ?></td>
                                    <td><?= $mov['data_vencimento'] ? date('d/m/Y', strtotime($mov['data_vencimento'])) : '--' ?></td>
                                    <td>
                                        <span class="badge badge-status <?= 'badge-' . $mov['status'] ?>">
                                            <?= ucfirst($mov['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($mov['status'] != 'pago'): ?>
                                            <form method="post" action="financeiro.php?acao=atualizar_status" class="d-inline">
                                                <input type="hidden" name="movimentacao_id" value="<?= $mov['id_movimentacao'] ?>">
                                                <input type="hidden" name="novo_status" value="pago">
                                                <button type="submit" class="btn btn-sm btn-success" title="Marcar como pago">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($mov['status'] != 'cancelado'): ?>
                                            <form method="post" action="financeiro.php?acao=atualizar_status" class="d-inline">
                                                <input type="hidden" name="movimentacao_id" value="<?= $mov['id_movimentacao'] ?>">
                                                <input type="hidden" name="novo_status" value="cancelado">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Cancelar" onclick="return confirm('Tem certeza que deseja cancelar esta movimentação?')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'listar_transferencias'): ?>
                    <!-- Listagem de transferências -->
                    <form method="get" class="mb-4">
                        <input type="hidden" name="acao" value="listar_transferencias">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $filtro_data_inicio ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $filtro_data_fim ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-transferencias">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Conta Origem</th>
                                    <th>Conta Destino</th>
                                    <th>Valor</th>
                                    <th>Taxa</th>
                                    <th>Total</th>
                                    <th>Responsável</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transferencias as $transf): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($transf['data_transferencia'])) ?></td>
                                    <td><?= $transf['conta_origem_nome'] ?></td>
                                    <td><?= $transf['conta_destino_nome'] ?></td>
                                    <td class="transferencia">R$ <?= number_format($transf['valor'], 2, ',', '.') ?></td>
                                    <td class="despesa">R$ <?= number_format($transf['taxa'], 2, ',', '.') ?></td>
                                    <td class="despesa">R$ <?= number_format($transf['valor'] + $transf['taxa'], 2, ',', '.') ?></td>
                                    <td><?= $transf['funcionario_nome'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'cadastrar_movimentacao'): ?>
                    <!-- Formulário de nova movimentação -->
                    <form method="post" action="financeiro.php?acao=cadastrar_movimentacao">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="receita">Receita</option>
                                    <option value="despesa">Despesa</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="categoria_id" class="form-label">Categoria</label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <?php foreach ($categorias_financeiras as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>"><?= $cat['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="conta_id" class="form-label">Conta</label>
                                <select class="form-select" id="conta_id" name="conta_id" required>
                                    <?php foreach ($contas_bancarias as $conta): ?>
                                    <option value="<?= $conta['id_conta'] ?>"><?= $conta['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pendente">Pendente</option>
                                    <option value="pago">Pago</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="valor" class="form-label">Valor</label>
                                <input type="text" class="form-control" id="valor" name="valor" placeholder="0,00" required>
                            </div>
                            <div class="col-md-4">
                                <label for="data_operacao" class="form-label">Data da Operação</label>
                                <input type="date" class="form-control" id="data_operacao" name="data_operacao" required>
                            </div>
                            <div class="col-md-4">
                                <label for="data_vencimento" class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" id="data_vencimento" name="data_vencimento">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="descricao" class="form-label">Descrição</label>
                                <input type="text" class="form-control" id="descricao" name="descricao" required>
                            </div>
                            <div class="col-md-6">
                                <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                                <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="conta_bancaria">Conta Bancária</option>
                                    <option value="cartao_credito">Cartão de Crédito</option>
                                    <option value="cartao_debito">Cartão de Débito</option>
                                    <option value="pix">PIX</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="financeiro.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Movimentação</button>
                        </div>
                    </form>
                    
                <?php elseif ($acao == 'registrar_transferencia'): ?>
                    <!-- Formulário de nova transferência -->
                    <form method="post" action="financeiro.php?acao=registrar_transferencia">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="conta_origem_id" class="form-label">Conta de Origem</label>
                                <select class="form-select" id="conta_origem_id" name="conta_origem_id" required>
                                    <?php foreach ($contas_bancarias as $conta): ?>
                                    <option value="<?= $conta['id_conta'] ?>"><?= $conta['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="conta_destino_id" class="form-label">Conta de Destino</label>
                                <select class="form-select" id="conta_destino_id" name="conta_destino_id" required>
                                    <?php foreach ($contas_bancarias as $conta): ?>
                                    <option value="<?= $conta['id_conta'] ?>"><?= $conta['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="valor" class="form-label">Valor</label>
                                <input type="text" class="form-control" id="valor" name="valor" placeholder="0,00" required>
                            </div>
                            <div class="col-md-4">
                                <label for="taxa" class="form-label">Taxa</label>
                                <input type="text" class="form-control" id="taxa" name="taxa" placeholder="0,00" value="0,00">
                            </div>
                            <div class="col-md-4">
                                <label for="data_transferencia" class="form-label">Data da Transferência</label>
                                <input type="date" class="form-control" id="data_transferencia" name="data_transferencia" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="descricao" class="form-label">Descrição</label>
                                <input type="text" class="form-control" id="descricao" name="descricao" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="financeiro.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Transferência</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // DataTables
            $('#tabela-movimentacoes, #tabela-transferencias').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
            
            // Máscara para valores monetários
            $('#valor, #taxa').mask('000.000.000.000.000,00', {reverse: true});
            
            // Configura datas padrão
            $('#data_operacao, #data_transferencia').val(new Date().toISOString().split('T')[0]);
            
            // Filtra categorias por tipo
            $('#tipo').change(function() {
                const tipo = $(this).val();
                $('#categoria_id').empty();
                
                <?php foreach ($categorias_financeiras as $cat): ?>
                if ('<?= $cat['tipo'] ?>' == tipo || tipo == '') {
                    $('#categoria_id').append(
                        $('<option>', {
                            value: '<?= $cat['id_categoria'] ?>',
                            text: '<?= $cat['nome'] ?>'
                        })
                    );
                }
                <?php endforeach; ?>
            });
            
            // Atualiza vencimento quando altera data de operação
            $('#data_operacao').change(function() {
                if (!$('#data_vencimento').val()) {
                    $('#data_vencimento').val($(this).val());
                }
            });
        });
    </script>
</body>
</html>