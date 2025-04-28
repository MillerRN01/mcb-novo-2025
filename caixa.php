<?php
require_once 'conexao_pdo.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Ações do CRUD
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'status';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($acao == 'abrir') {
        try {
            $valor_abertura = str_replace(',', '.', $_POST['valor_abertura']);
            $observacoes = $_POST['observacoes'];
            $funcionario_id = $_SESSION['id_funcionario']; // ID do funcionário logado

            $stmt = $pdo->prepare("INSERT INTO caixa 
                                  (data_abertura, funcionario_id, valor_abertura, observacoes) 
                                  VALUES (NOW(), ?, ?, ?)");
            $stmt->execute([$funcionario_id, $valor_abertura, $observacoes]);

            $_SESSION['mensagem'] = "Caixa aberto com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: caixa.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['mensagem'] = "Erro ao abrir caixa: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
    elseif ($acao == 'fechar') {
        try {
            $caixa_id = $_POST['caixa_id'];
            $valor_fechamento = str_replace(',', '.', $_POST['valor_fechamento']);
            $observacoes = $_POST['observacoes'];

            $pdo->beginTransaction();

            // Atualiza o caixa
            $stmt = $pdo->prepare("UPDATE caixa 
                                  SET data_fechamento = NOW(), 
                                      valor_fechamento = ?,
                                      observacoes = ?,
                                      status = 'fechado'
                                  WHERE id_caixa = ?");
            $stmt->execute([$valor_fechamento, $observacoes, $caixa_id]);

            // Registra a movimentação de fechamento
            $stmt = $pdo->prepare("INSERT INTO movimentacoes_caixa
                                  (caixa_id, tipo, valor, forma_pagamento, descricao)
                                  VALUES (?, 'entrada', ?, 'dinheiro', 'Fechamento de caixa')");
            $stmt->execute([$caixa_id, $valor_fechamento]);

            $pdo->commit();

            $_SESSION['mensagem'] = "Caixa fechado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: caixa.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao fechar caixa: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
    elseif ($acao == 'movimentar') {
        try {
            $caixa_id = $_POST['caixa_id'];
            $tipo = $_POST['tipo'];
            $valor = str_replace(',', '.', $_POST['valor']);
            $forma_pagamento = $_POST['forma_pagamento'];
            $descricao = $_POST['descricao'];

            $stmt = $pdo->prepare("INSERT INTO movimentacoes_caixa
                                  (caixa_id, tipo, valor, forma_pagamento, descricao)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$caixa_id, $tipo, $valor, $forma_pagamento, $descricao]);

            $_SESSION['mensagem'] = "Movimentação registrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: caixa.php?acao=movimentacoes&caixa_id=" . $caixa_id);
            exit();

        } catch (Exception $e) {
            $_SESSION['mensagem'] = "Erro ao registrar movimentação: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}

// Verifica status do caixa
$caixa_aberto = null;
$stmt = $pdo->query("SELECT * FROM caixa WHERE status = 'aberto' LIMIT 1");
if ($stmt->rowCount() > 0) {
    $caixa_aberto = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Busca histórico de caixas
if ($acao == 'historico') {
    $stmt = $pdo->query("SELECT c.*, f.nome_completo as funcionario 
                        FROM caixa c
                        JOIN funcionario f ON c.funcionario_id = f.id_funcionario
                        ORDER BY c.data_abertura DESC");
    $caixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca movimentações de um caixa específico
if ($acao == 'movimentacoes' && isset($_GET['caixa_id'])) {
    $caixa_id = $_GET['caixa_id'];
    
    $stmt = $pdo->prepare("SELECT m.*, v.id_venda
                          FROM movimentacoes_caixa m
                          LEFT JOIN vendas v ON m.venda_id = v.id_venda
                          WHERE m.caixa_id = ?
                          ORDER BY m.data_movimentacao DESC");
    $stmt->execute([$caixa_id]);
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcula totais
    $entradas = 0;
    $saidas = 0;
    foreach ($movimentacoes as $mov) {
        if ($mov['tipo'] == 'entrada') {
            $entradas += $mov['valor'];
        } else {
            $saidas += $mov['valor'];
        }
    }
    $saldo = $entradas - $saidas;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Caixa</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .card-saldo {
            border-left: 4px solid #28a745;
        }
        .card-entradas {
            border-left: 4px solid #17a2b8;
        }
        .card-saidas {
            border-left: 4px solid #dc3545;
        }
        .badge-status {
            font-size: 0.9em;
        }
        .badge-aberto {
            background-color: #28a745;
        }
        .badge-fechado {
            background-color: #6c757d;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-cash-stack"></i> Gerenciamento de Caixa</h2>
        
        <?php include 'mensagem.php'; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'status' ? 'active' : '' ?>" href="caixa.php">Status</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'historico' ? 'active' : '' ?>" href="caixa.php?acao=historico">Histórico</a>
                    </li>
                    <?php if ($caixa_aberto): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'movimentacoes' ? 'active' : '' ?>" 
                           href="caixa.php?acao=movimentacoes&caixa_id=<?= $caixa_aberto['id_caixa'] ?>">
                           Movimentações
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="card-body">
                <?php if ($acao == 'status'): ?>
                    <!-- Status do Caixa -->
                    <?php if ($caixa_aberto): ?>
                        <div class="alert alert-success">
                            <h4><i class="bi bi-check-circle"></i> Caixa Aberto</h4>
                            <p>
                                <strong>Aberto em:</strong> <?= date('d/m/Y H:i', strtotime($caixa_aberto['data_abertura'])) ?><br>
                                <strong>Por:</strong> <?= $caixa_aberto['funcionario_id'] ?><br>
                                <strong>Valor de abertura:</strong> R$ <?= number_format($caixa_aberto['valor_abertura'], 2, ',', '.') ?>
                            </p>
                            
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card card-entradas h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">Entradas</h5>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT SUM(valor) as total 
                                                                  FROM movimentacoes_caixa 
                                                                  WHERE caixa_id = ? AND tipo = 'entrada'");
                                            $stmt->execute([$caixa_aberto['id_caixa']]);
                                            $entradas = $stmt->fetchColumn();
                                            ?>
                                            <p class="card-text display-6">R$ <?= number_format($entradas, 2, ',', '.') ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="card card-saidas h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">Saídas</h5>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT SUM(valor) as total 
                                                                  FROM movimentacoes_caixa 
                                                                  WHERE caixa_id = ? AND tipo = 'saida'");
                                            $stmt->execute([$caixa_aberto['id_caixa']]);
                                            $saidas = $stmt->fetchColumn();
                                            ?>
                                            <p class="card-text display-6">R$ <?= number_format($saidas, 2, ',', '.') ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="card card-saldo h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">Saldo Atual</h5>
                                            <?php $saldo = $caixa_aberto['valor_abertura'] + $entradas - $saidas; ?>
                                            <p class="card-text display-6">R$ <?= number_format($saldo, 2, ',', '.') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMovimentacao">
                                    <i class="bi bi-plus-circle"></i> Nova Movimentação
                                </button>
                                
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalFecharCaixa">
                                    <i class="bi bi-x-circle"></i> Fechar Caixa
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h4><i class="bi bi-exclamation-triangle"></i> Caixa Fechado</h4>
                            <p>Não há caixa aberto no momento. Para iniciar as operações, abra um novo caixa.</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAbrirCaixa">
                                <i class="bi bi-lock-open"></i> Abrir Caixa
                            </button>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($acao == 'historico'): ?>
                    <!-- Histórico de Caixas -->
                    <h4 class="mb-3">Histórico de Caixas</h4>
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-caixas">
                            <thead>
                                <tr>
                                    <th>Data Abertura</th>
                                    <th>Data Fechamento</th>
                                    <th>Responsável</th>
                                    <th>Valor Abertura</th>
                                    <th>Valor Fechamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($caixas as $caixa): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($caixa['data_abertura'])) ?></td>
                                    <td><?= $caixa['data_fechamento'] ? date('d/m/Y H:i', strtotime($caixa['data_fechamento'])) : '--' ?></td>
                                    <td><?= $caixa['funcionario'] ?></td>
                                    <td>R$ <?= number_format($caixa['valor_abertura'], 2, ',', '.') ?></td>
                                    <td><?= $caixa['valor_fechamento'] ? 'R$ ' . number_format($caixa['valor_fechamento'], 2, ',', '.') : '--' ?></td>
                                    <td>
                                        <span class="badge badge-status <?= $caixa['status'] == 'aberto' ? 'badge-aberto' : 'badge-fechado' ?>">
                                            <?= ucfirst($caixa['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="caixa.php?acao=movimentacoes&caixa_id=<?= $caixa['id_caixa'] ?>" 
                                           class="btn btn-sm btn-info" title="Detalhes">
                                            <i class="bi bi-list"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'movimentacoes' && isset($_GET['caixa_id'])): ?>
                    <!-- Movimentações do Caixa -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Movimentações do Caixa</h4>
                        <a href="caixa.php" class="btn btn-secondary">Voltar</a>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card card-entradas h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Entradas</h5>
                                    <p class="card-text display-6">R$ <?= number_format($entradas, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card card-saidas h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Saídas</h5>
                                    <p class="card-text display-6">R$ <?= number_format($saidas, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card card-saldo h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo Final</h5>
                                    <p class="card-text display-6">R$ <?= number_format($saldo, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMovimentacao">
                            <i class="bi bi-plus-circle"></i> Nova Movimentação
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-movimentacoes">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Forma Pagamento</th>
                                    <th>Descrição</th>
                                    <th>Venda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                    <td>
                                        <span class="badge <?= $mov['tipo'] == 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>R$ <?= number_format($mov['valor'], 2, ',', '.') ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $mov['forma_pagamento'])) ?></td>
                                    <td><?= $mov['descricao'] ?></td>
                                    <td>
                                        <?php if ($mov['id_venda']): ?>
                                            <a href="vendas.php?acao=visualizar&id=<?= $mov['id_venda'] ?>" class="btn btn-sm btn-info">
                                                #<?= $mov['id_venda'] ?>
                                            </a>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Abrir Caixa -->
    <div class="modal fade" id="modalAbrirCaixa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="caixa.php?acao=abrir">
                    <div class="modal-header">
                        <h5 class="modal-title">Abrir Caixa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="valor_abertura" class="form-label">Valor de Abertura</label>
                            <input type="text" class="form-control" id="valor_abertura" name="valor_abertura" 
                                   placeholder="0,00" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Abrir Caixa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Fechar Caixa -->
    <div class="modal fade" id="modalFecharCaixa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="caixa.php?acao=fechar">
                    <input type="hidden" name="caixa_id" value="<?= $caixa_aberto['id_caixa'] ?? '' ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Fechar Caixa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Valor de abertura:</strong> R$ <?= number_format($caixa_aberto['valor_abertura'] ?? 0, 2, ',', '.') ?></p>
                        <p><strong>Saldo atual:</strong> R$ <?= number_format($saldo ?? 0, 2, ',', '.') ?></p>
                        
                        <div class="mb-3">
                            <label for="valor_fechamento" class="form-label">Valor de Fechamento</label>
                            <input type="text" class="form-control" id="valor_fechamento" name="valor_fechamento" 
                                   value="<?= number_format($saldo ?? 0, 2, ',', '.') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Fechar Caixa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nova Movimentação -->
    <div class="modal fade" id="modalMovimentacao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="caixa.php?acao=movimentar">
                    <input type="hidden" name="caixa_id" value="<?= $caixa_aberto['id_caixa'] ?? '' ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Movimentação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor</label>
                            <input type="text" class="form-control" id="valor" name="valor" placeholder="0,00" required>
                        </div>
                        <div class="mb-3">
                            <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                            <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="pix">PIX</option>
                                <option value="transferencia">Transferência</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar</button>
                    </div>
                </form>
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
            $('#tabela-caixas, #tabela-movimentacoes').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
            
            // Máscara para valores monetários
            $('#valor_abertura, #valor_fechamento, #valor').mask('000.000.000.000.000,00', {reverse: true});
            
            // Atualiza o valor de fechamento quando o modal é aberto
            $('#modalFecharCaixa').on('show.bs.modal', function () {
                var saldo = <?= $saldo ?? 0 ?>;
                $('#valor_fechamento').val(saldo.toFixed(2).trigger('input');
            });
        });
    </script>
</body>
</html>