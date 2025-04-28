<?php
require_once 'conexao_pdo.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Ações do CRUD
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($acao == 'cadastrar') {
        try {
            $pdo->beginTransaction();
            
            // Dados do fiado
            $cliente_id = $_POST['cliente_id'];
            $tipo_cliente = $_POST['tipo_cliente'];
            $valor_total = str_replace(['.', ','], ['', '.'], $_POST['valor_total']);
            $quantidade_parcelas = $_POST['quantidade_parcelas'];
            $primeiro_vencimento = $_POST['primeiro_vencimento'];
            $observacoes = $_POST['observacoes'];
            $funcionario_id = $_SESSION['id_funcionario'];
            
            // Insere o fiado
            $stmt = $pdo->prepare("INSERT INTO fiado 
                                  (cliente_id, tipo_cliente, valor_total, funcionario_id, observacoes) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cliente_id, $tipo_cliente, $valor_total, $funcionario_id, $observacoes]);
            $fiado_id = $pdo->lastInsertId();
            
            // Calcula parcelas
            $valor_parcela = $valor_total / $quantidade_parcelas;
            $data_vencimento = new DateTime($primeiro_vencimento);
            
            for ($i = 1; $i <= $quantidade_parcelas; $i++) {
                $stmt = $pdo->prepare("INSERT INTO parcelas_fiado 
                                      (fiado_id, numero_parcela, valor_parcela, data_vencimento) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $fiado_id,
                    $i,
                    $valor_parcela,
                    $data_vencimento->format('Y-m-d')
                ]);
                
                // Adiciona 1 mês para a próxima parcela
                $data_vencimento->add(new DateInterval('P1M'));
            }
            
            // Registra na histórico
            $stmt = $pdo->prepare("INSERT INTO historico_fiado 
                                  (fiado_id, tipo_operacao, valor, funcionario_id, observacoes) 
                                  VALUES (?, 'abertura', ?, ?, ?)");
            $stmt->execute([$fiado_id, $valor_total, $funcionario_id, $observacoes]);
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Fiado cadastrado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: fiado.php?acao=detalhes&id=" . $fiado_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao cadastrar fiado: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
    elseif ($acao == 'pagar') {
        try {
            $pdo->beginTransaction();
            
            $parcela_id = $_POST['parcela_id'];
            $fiado_id = $_POST['fiado_id'];
            $valor_pago = str_replace(['.', ','], ['', '.'], $_POST['valor_pago']);
            $forma_pagamento = $_POST['forma_pagamento'];
            $observacoes = $_POST['observacoes'];
            $funcionario_id = $_SESSION['id_funcionario'];
            
            // Atualiza a parcela
            $stmt = $pdo->prepare("UPDATE parcelas_fiado 
                                  SET data_pagamento = NOW(), 
                                      valor_pago = ?,
                                      forma_pagamento = ?,
                                      status = 'pago'
                                  WHERE id_parcela = ?");
            $stmt->execute([$valor_pago, $forma_pagamento, $parcela_id]);
            
            // Atualiza o fiado (valor pago)
            $stmt = $pdo->prepare("UPDATE fiado 
                                  SET valor_pago = valor_pago + ? 
                                  WHERE id_fiado = ?");
            $stmt->execute([$valor_pago, $fiado_id]);
            
            // Atualiza status do fiado
            $stmt = $pdo->prepare("UPDATE fiado 
                                  SET status = CASE 
                                      WHEN valor_pago >= valor_total THEN 'quitado'
                                      WHEN valor_pago > 0 THEN 'parcial'
                                      ELSE 'aberto'
                                  END
                                  WHERE id_fiado = ?");
            $stmt->execute([$fiado_id]);
            
            // Registra no histórico
            $stmt = $pdo->prepare("INSERT INTO historico_fiado 
                                  (fiado_id, tipo_operacao, valor, funcionario_id, observacoes) 
                                  VALUES (?, 'pagamento', ?, ?, ?)");
            $stmt->execute([$fiado_id, $valor_pago, $funcionario_id, $observacoes]);
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Pagamento registrado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: fiado.php?acao=detalhes&id=" . $fiado_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao registrar pagamento: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}

// Listagem de fiados
if ($acao == 'listar') {
    $filtro_status = isset($_GET['status']) ? $_GET['status'] : 'aberto';
    
    $sql = "SELECT f.*, 
                   CASE 
                       WHEN f.tipo_cliente = 'pf' THEN (SELECT nome FROM clientes_pf WHERE id_cliente = f.cliente_id)
                       WHEN f.tipo_cliente = 'pj' THEN (SELECT razao_social FROM clientes_pj WHERE id_cliente = f.cliente_id)
                   END as cliente_nome,
                   func.nome_completo as funcionario_nome
            FROM fiado f
            JOIN funcionario func ON f.funcionario_id = func.id_funcionario
            WHERE f.status = ?
            ORDER BY f.data_abertura DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filtro_status]);
    $fiados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Detalhes de um fiado específico
if ($acao == 'detalhes' && isset($_GET['id'])) {
    $fiado_id = $_GET['id'];
    
    // Informações do fiado
    $stmt = $pdo->prepare("SELECT f.*, 
                                  CASE 
                                      WHEN f.tipo_cliente = 'pf' THEN (SELECT nome FROM clientes_pf WHERE id_cliente = f.cliente_id)
                                      WHEN f.tipo_cliente = 'pj' THEN (SELECT razao_social FROM clientes_pj WHERE id_cliente = f.cliente_id)
                                  END as cliente_nome,
                                  func.nome_completo as funcionario_nome
                           FROM fiado f
                           JOIN funcionario func ON f.funcionario_id = func.id_funcionario
                           WHERE f.id_fiado = ?");
    $stmt->execute([$fiado_id]);
    $fiado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Parcelas
    $stmt = $pdo->prepare("SELECT * FROM parcelas_fiado 
                          WHERE fiado_id = ?
                          ORDER BY numero_parcela");
    $stmt->execute([$fiado_id]);
    $parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Histórico
    $stmt = $pdo->prepare("SELECT h.*, f.nome_completo as funcionario_nome
                          FROM historico_fiado h
                          JOIN funcionario f ON h.funcionario_id = f.id_funcionario
                          WHERE h.fiado_id = ?
                          ORDER BY h.data_operacao DESC");
    $stmt->execute([$fiado_id]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca de clientes para o autocomplete
if ($acao == 'buscar_clientes') {
    $termo = isset($_GET['term']) ? $_GET['term'] : '';
    
    $clientes_pf = $pdo->prepare("SELECT id_cliente, nome, cpf FROM clientes_pf WHERE nome LIKE ? LIMIT 10");
    $clientes_pf->execute(["%$termo%"]);
    $result_pf = $clientes_pf->fetchAll(PDO::FETCH_ASSOC);
    
    $clientes_pj = $pdo->prepare("SELECT id_cliente, razao_social as nome, cnpj FROM clientes_pj WHERE razao_social LIKE ? LIMIT 10");
    $clientes_pj->execute(["%$termo%"]);
    $result_pj = $clientes_pj->fetchAll(PDO::FETCH_ASSOC);
    
    $clientes = array_merge($result_pf, $result_pj);
    
    echo json_encode($clientes);
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Controle de Fiado</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .badge-status {
            font-size: 0.9em;
        }
        .badge-aberto {
            background-color: #dc3545;
        }
        .badge-parcial {
            background-color: #ffc107;
            color: #000;
        }
        .badge-quitado {
            background-color: #28a745;
        }
        .badge-pendente {
            background-color: #6c757d;
        }
        .badge-pago {
            background-color: #28a745;
        }
        .badge-atrasado {
            background-color: #dc3545;
        }
        .card-saldo {
            border-left: 4px solid #17a2b8;
        }
        .parcela-paga {
            background-color: #e8f5e9;
        }
        .parcela-atrasada {
            background-color: #ffebee;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-receipt"></i> Controle de Fiado</h2>
        
        <?php include 'mensagem.php'; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'listar' ? 'active' : '' ?>" href="fiado.php">Fiados Ativos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'historico' ? 'active' : '' ?>" href="fiado.php?acao=historico">Histórico</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'cadastrar' ? 'active' : '' ?>" href="fiado.php?acao=cadastrar">Novo Fiado</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <?php if ($acao == 'listar'): ?>
                    <!-- Listagem de fiados -->
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <a href="fiado.php?status=aberto" class="btn btn-<?= $filtro_status == 'aberto' ? 'primary' : 'outline-primary' ?>">Abertos</a>
                            <a href="fiado.php?status=parcial" class="btn btn-<?= $filtro_status == 'parcial' ? 'primary' : 'outline-primary' ?>">Parciais</a>
                            <a href="fiado.php?status=quitado" class="btn btn-<?= $filtro_status == 'quitado' ? 'primary' : 'outline-primary' ?>">Quitados</a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-fiados">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Data Abertura</th>
                                    <th>Valor Total</th>
                                    <th>Valor Pago</th>
                                    <th>Saldo</th>
                                    <th>Status</th>
                                    <th>Responsável</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fiados as $f): ?>
                                <tr>
                                    <td><?= $f['cliente_nome'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($f['data_abertura'])) ?></td>
                                    <td>R$ <?= number_format($f['valor_total'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($f['valor_pago'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($f['valor_total'] - $f['valor_pago'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-status <?= 'badge-' . $f['status'] ?>">
                                            <?= ucfirst($f['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $f['funcionario_nome'] ?></td>
                                    <td>
                                        <a href="fiado.php?acao=detalhes&id=<?= $f['id_fiado'] ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'detalhes' && isset($fiado)): ?>
                    <!-- Detalhes do fiado -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4>Fiado de <?= $fiado['cliente_nome'] ?></h4>
                            <p class="mb-0">
                                <span class="badge badge-status <?= 'badge-' . $fiado['status'] ?>">
                                    <?= ucfirst($fiado['status']) ?>
                                </span>
                                <span class="ms-2">Aberto em: <?= date('d/m/Y H:i', strtotime($fiado['data_abertura'])) ?></span>
                            </p>
                        </div>
                        <a href="fiado.php" class="btn btn-secondary">Voltar</a>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Valor Total</h5>
                                    <p class="card-text display-6">R$ <?= number_format($fiado['valor_total'], 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Valor Pago</h5>
                                    <p class="card-text display-6">R$ <?= number_format($fiado['valor_pago'], 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-saldo">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo Devedor</h5>
                                    <p class="card-text display-6">R$ <?= number_format($fiado['valor_total'] - $fiado['valor_pago'], 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Parcelas</h5>
                    <div class="table-responsive mb-4">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Pagamento</th>
                                    <th>Valor Pago</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parcelas as $p): 
                                    $atrasada = $p['status'] == 'pendente' && strtotime($p['data_vencimento']) < time();
                                    $classe_linha = $p['status'] == 'pago' ? 'parcela-paga' : ($atrasada ? 'parcela-atrasada' : '');
                                ?>
                                <tr class="<?= $classe_linha ?>">
                                    <td><?= $p['numero_parcela'] ?></td>
                                    <td>R$ <?= number_format($p['valor_parcela'], 2, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['data_vencimento'])) ?></td>
                                    <td><?= $p['data_pagamento'] ? date('d/m/Y H:i', strtotime($p['data_pagamento'])) : '--' ?></td>
                                    <td><?= $p['valor_pago'] ? 'R$ ' . number_format($p['valor_pago'], 2, ',', '.') : '--' ?></td>
                                    <td>
                                        <?php 
                                        $status = $atrasada ? 'atrasado' : $p['status'];
                                        ?>
                                        <span class="badge <?= 'badge-' . $status ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] != 'pago'): ?>
                                        <button class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalPagarParcela"
                                                data-parcela-id="<?= $p['id_parcela'] ?>"
                                                data-fiado-id="<?= $fiado['id_fiado'] ?>"
                                                data-valor-parcela="<?= number_format($p['valor_parcela'], 2, ',', '.') ?>">
                                            <i class="bi bi-cash"></i> Pagar
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5 class="mb-3">Histórico</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Operação</th>
                                    <th>Valor</th>
                                    <th>Responsável</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($h['data_operacao'])) ?></td>
                                    <td><?= ucfirst($h['tipo_operacao']) ?></td>
                                    <td>R$ <?= number_format($h['valor'], 2, ',', '.') ?></td>
                                    <td><?= $h['funcionario_nome'] ?></td>
                                    <td><?= $h['observacoes'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'cadastrar'): ?>
                    <!-- Formulário de novo fiado -->
                    <form method="post" action="fiado.php?acao=cadastrar">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cliente" class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="cliente" name="cliente" placeholder="Digite o nome do cliente" required>
                                <input type="hidden" id="cliente_id" name="cliente_id">
                                <input type="hidden" id="tipo_cliente" name="tipo_cliente">
                                <div id="sugestoes-clientes" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="valor_total" class="form-label">Valor Total</label>
                                <input type="text" class="form-control" id="valor_total" name="valor_total" placeholder="0,00" required>
                            </div>
                            <div class="col-md-4">
                                <label for="quantidade_parcelas" class="form-label">Quantidade de Parcelas</label>
                                <input type="number" class="form-control" id="quantidade_parcelas" name="quantidade_parcelas" min="1" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="primeiro_vencimento" class="form-label">Primeiro Vencimento</label>
                                <input type="date" class="form-control" id="primeiro_vencimento" name="primeiro_vencimento" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="fiado.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Fiado</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Pagar Parcela -->
    <div class="modal fade" id="modalPagarParcela" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="fiado.php?acao=pagar">
                    <input type="hidden" id="parcela_id" name="parcela_id">
                    <input type="hidden" id="fiado_id" name="fiado_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Pagamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="valor_parcela_modal" class="form-label">Valor da Parcela</label>
                            <input type="text" class="form-control" id="valor_parcela_modal" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="valor_pago" class="form-label">Valor Pago</label>
                            <input type="text" class="form-control" id="valor_pago" name="valor_pago" required>
                        </div>
                        <div class="mb-3">
                            <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                            <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao">Cartão</option>
                                <option value="pix">PIX</option>
                                <option value="transferencia">Transferência</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_pagamento" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_pagamento" name="observacoes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Pagamento</button>
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
            $('#tabela-fiados').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
            
            // Máscara para valores monetários
            $('#valor_total, #valor_pago').mask('000.000.000.000.000,00', {reverse: true});
            
            // Configura a data do primeiro vencimento para amanhã
            $('#primeiro_vencimento').val(new Date().toISOString().split('T')[0]);
            
            // Autocomplete para clientes
            $('#cliente').on('input', function() {
                const termo = $(this).val();
                if (termo.length < 2) {
                    $('#sugestoes-clientes').hide().empty();
                    return;
                }
                
                $.get('fiado.php?acao=buscar_clientes&term=' + termo, function(data) {
                    const sugestoes = $('#sugestoes-clientes');
                    sugestoes.empty();
                    
                    if (data.length > 0) {
                        data.forEach(function(cliente) {
                            const tipo = cliente.cpf ? 'PF' : 'PJ';
                            const doc = cliente.cpf || cliente.cnpj;
                            
                            sugestoes.append(
                                `<div class="autocomplete-suggestion" 
                                    data-id="${cliente.id_cliente}" 
                                    data-tipo="${tipo == 'PF' ? 'pf' : 'pj'}"
                                    data-nome="${cliente.nome}">
                                    ${cliente.nome} (${tipo} - ${doc})
                                </div>`
                            );
                        });
                        sugestoes.show();
                    } else {
                        sugestoes.hide();
                    }
                });
            });
            
            // Seleção de cliente
            $(document).on('click', '.autocomplete-suggestion', function() {
                $('#cliente').val($(this).data('nome'));
                $('#cliente_id').val($(this).data('id'));
                $('#tipo_cliente').val($(this).data('tipo'));
                $('#sugestoes-clientes').hide().empty();
            });
            
            // Configura o modal de pagamento
            $('#modalPagarParcela').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                const parcelaId = button.data('parcela-id');
                const fiadoId = button.data('fiado-id');
                const valorParcela = button.data('valor-parcela');
                
                const modal = $(this);
                modal.find('#parcela_id').val(parcelaId);
                modal.find('#fiado_id').val(fiadoId);
                modal.find('#valor_parcela_modal').val(valorParcela);
                modal.find('#valor_pago').val(valorParcela).trigger('input');
            });
        });
    </script>
</body>
</html>