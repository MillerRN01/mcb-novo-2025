<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Inicializa variáveis
$pesquisa = '';
$status = '';
$resultado = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém os valores do formulário
    $pesquisa = isset($_POST['pesquisa']) ? trim($_POST['pesquisa']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Se não houver critérios de pesquisa, busca todos os clientes sem duplicar
    if (empty($pesquisa) && empty($status)) {
        // Busca apenas clientes PJ
        $sql_pj = "SELECT c.*, e.logradouro, e.numero, e.cidade, e.complemento, e.bairro, e.estado, e.cep 
                  FROM clientes_pj c 
                  LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco";
        $resultado_pj = $conn->query($sql_pj);

        while ($row = $resultado_pj->fetch_assoc()) {
            $row['tipo_pessoa'] = 'pj';
            $resultado[] = $row;
        }

        // Busca apenas clientes PF
        $sql_pf = "SELECT c.*, e.logradouro, e.numero, e.cidade, e.complemento, e.bairro, e.estado, e.cep 
                  FROM clientes_pf c 
                  LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco";
        $resultado_pf = $conn->query($sql_pf);

        while ($row = $resultado_pf->fetch_assoc()) {
            $row['tipo_pessoa'] = 'pf';
            $resultado[] = $row;
        }
    } else {
        // Se houver critérios de pesquisa, busca com filtros
        $like_param = '%' . $pesquisa . '%';

        // Busca clientes PJ com filtros
        $sql_pj = "SELECT c.*, e.logradouro, e.numero, e.cidade, e.complemento, e.bairro, e.estado, e.cep 
                  FROM clientes_pj c 
                  LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco 
                  WHERE 1=1";

        if (!empty($pesquisa)) {
            $sql_pj .= " AND (c.razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.email LIKE ? OR c.telefone LIKE ?)";
        }
        if (!empty($status)) {
            $sql_pj .= " AND c.status = ?";
        }

        $stmt_pj = $conn->prepare($sql_pj);
        if ($stmt_pj) {
            if (!empty($pesquisa) && !empty($status)) {
                $stmt_pj->bind_param('sssss', $like_param, $like_param, $like_param, $like_param, $status);
            } elseif (!empty($pesquisa)) {
                $stmt_pj->bind_param('ssss', $like_param, $like_param, $like_param, $like_param);
            } elseif (!empty($status)) {
                $stmt_pj->bind_param('s', $status);
            }

            $stmt_pj->execute();
            $resultado_pj = $stmt_pj->get_result();

            while ($row = $resultado_pj->fetch_assoc()) {
                $row['tipo_pessoa'] = 'pj';
                $resultado[] = $row;
            }
            $stmt_pj->close();
        }

        // Busca clientes PF com filtros
        $sql_pf = "SELECT c.*, e.logradouro, e.numero, e.cidade, e.complemento, e.bairro, e.estado, e.cep 
                  FROM clientes_pf c 
                  LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco 
                  WHERE 1=1";

        if (!empty($pesquisa)) {
            $sql_pf .= " AND (c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ?)";
        }
        if (!empty($status)) {
            $sql_pf .= " AND c.status = ?";
        }

        $stmt_pf = $conn->prepare($sql_pf);
        if ($stmt_pf) {
            if (!empty($pesquisa) && !empty($status)) {
                $stmt_pf->bind_param('ssss', $like_param, $like_param, $like_param, $status);
            } elseif (!empty($pesquisa)) {
                $stmt_pf->bind_param('sss', $like_param, $like_param, $like_param);
            } elseif (!empty($status)) {
                $stmt_pf->bind_param('s', $status);
            }

            $stmt_pf->execute();
            $resultado_pf = $stmt_pf->get_result();

            while ($row = $resultado_pf->fetch_assoc()) {
                $row['tipo_pessoa'] = 'pf';
                $resultado[] = $row;
            }
            $stmt_pf->close();
        }
    }
} else {
    // Se não for POST, busca todos os clientes (para exibição inicial)
    $sql_pj = "SELECT c.*, e.logradouro, e.numero, e.cidade, e.complemento, e.bairro, e.estado, e.cep 
              FROM clientes_pj c 
              LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco";
    $resultado_pj = $conn->query($sql_pj);

    while ($row = $resultado_pj->fetch_assoc()) {
        $row['tipo_pessoa'] = 'pj';
        $resultado[] = $row;
    }

    $sql_pf = "SELECT c.*, e.logradouro, e.numero, e.cidade, e.complemento, e.bairro, e.estado, e.cep 
              FROM clientes_pf c 
              LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco";
    $resultado_pf = $conn->query($sql_pf);

    while ($row = $resultado_pf->fetch_assoc()) {
        $row['tipo_pessoa'] = 'pf';
        $resultado[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Vendas</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<style>
    #container_pesquisa {
        width: 850px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 15px;
        background-color: #ffffff;
        border-radius: 0.5rem;
    }

    body {
        background-color: #f8f9fa;
        font-family: Arial, sans-serif;
    }

    .card {
        transition: transform 0.2s;
        width: 320px;

    }

    .card:hover {
        transform: translateY(-5px);
    }

    .btn-custom-small {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .additional-info {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
</style>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Filtros e Pesquisa -->
    <div class="container mt-4" id="container_pesquisa">
        <h1 class="mb-4">Gerenciamento de Clientes</h1>
        <div class="filters-section mb-4">
            <form method="POST" action="" class="row g-3 align-items-center justify-content-center">
                <div class="col-auto d-flex align-items-center">
                    <div class="input-group me-2">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="pesquisa" class="form-control" placeholder="Buscar clientes..." value="<?php echo htmlspecialchars($pesquisa); ?>" style="width: 200px;">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
                <div class="col-auto d-flex align-items-center">
                    <div class="input-group me-2">
                        <select name="status" class="form-select" style="width: 200px;">
                            <option value="">Todos os Status</option>
                            <option value="Ativo" <?php echo ($status === 'Ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="Inativo" <?php echo ($status === 'Inativo') ? 'selected' : ''; ?>>Inativo
                            </option>
                        </select>
                        <div class="d-flex">
                            <button type="button" class="btn btn-success me-2 btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#novoClienteModal">
                                <i class="fas fa-user-plus me-1"></i> Novo Cliente
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm d-flex align-items-center" onclick="exportaClientes()">
                                <i class="fas fa-file-export me-1"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Lista de Clientes -->
        <div class="clients-grid" id="clientsGrid">
            <div class="row">
                <?php if (!empty($resultado)): ?>
                    <?php foreach ($resultado as $row): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm" style="font-size: 12px; padding: 10px;">
                                <div class="card-body" style="padding: 10px;">
                                    <p class="card-text">Tipo:
                                        <?= htmlspecialchars($row["tipo_pessoa"] ?? 'Sem tipo disponível') ?>
                                    </p>
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($row['tipo_pessoa'] === 'pj' ? ($row["razao_social"] ?? 'Sem razão social') : ($row["nome"] ?? 'Sem nome')) ?>
                                    </h5>

                                    <?php if ($row['tipo_pessoa'] === 'pj' && !empty($row["nome_fantasia"])): ?>
                                        <p class="card-text">Nome Fantasia: <?= htmlspecialchars($row["nome_fantasia"]) ?></p>
                                    <?php endif; ?>
                                    <p class="card-text">Email: <?= htmlspecialchars($row["email"] ?? 'Sem e-mail') ?></p>
                                    <p class="card-text">Telefone: <?= htmlspecialchars($row["telefone"] ?? 'Sem telefone') ?></p>
                                    <p class="card-text">
                                        Status:
                                        <span class="badge bg-<?= ($row["status"] == 'ativo') ? 'success' : 'danger' ?>">
                                            <?= ucfirst($row["status"] ?? 'Sem status') ?>
                                        </span>
                                    </p>
                                    <div class="button-container d-flex justify-content-between">
                                        <button class="btn btn-danger w-100 me-2" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal<?= $row['id_cliente'] ?>" style="height: 30px; font-size: 12px;">
                                            <i class="fas fa-trash-alt"></i> Excluir
                                        </button>
                                        <a href="editar_cadastro_cliente.php?id=<?= $row['id_cliente'] ?>&tipo=<?= $row['tipo_pessoa'] ?>" class="btn btn-info text-white w-100 me-2" style="height: 30px; font-size: 12px;">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button class="btn btn-primary btn-sm btn-custom-small expand-button w-100"style="height: 30px; font-size: 12px;"onclick="toggleAdditionalInfo(this, 'additionalInfo<?= $row['id_cliente'] ?>')">
                                            <i class="fas fa-eye"></i> Ver mais
                                        </button>
                                    </div>
                                    <div class="additional-info" style="display: none;">
                                        <?php if ($row['tipo_pessoa'] === 'pf'): ?>
                                            <p><strong>CPF:</strong> <?= htmlspecialchars($row["cpf"] ?? 'Sem CPF') ?></p>
                                            <p><strong>RG:</strong> <?= htmlspecialchars($row["rg"] ?? 'Sem RG') ?></p>
                                            <p><strong>Data Nasc.:</strong> <?= htmlspecialchars($row["data_nascimento"] ?? 'Sem data') ?></p>
                                        <?php else: ?>
                                            <p><strong>CNPJ:</strong> <?= htmlspecialchars($row["cnpj"] ?? 'Sem CNPJ') ?></p>
                                            <p><strong>IE:</strong> <?= htmlspecialchars($row["ie"] ?? 'Sem IE') ?></p>
                                        <?php endif; ?>

                                        <p><strong>WhatsApp:</strong> <?= htmlspecialchars($row["whatsapp"] ?? 'Sem WhatsApp') ?></p>

                                        <?php if (!empty($row["logradouro"])): ?>
                                            <p><strong>Endereço:</strong>
                                                <?= htmlspecialchars($row["logradouro"]) ?>,
                                                <?= htmlspecialchars($row["numero"]) ?>
                                                <?= !empty($row["complemento"]) ? ', ' . htmlspecialchars($row["complemento"]) : '' ?>,
                                                <?= htmlspecialchars($row["bairro"]) ?>,
                                                <?= htmlspecialchars($row["cidade"]) ?>,
                                                <?= htmlspecialchars($row["estado"]) ?>,
                                                CEP: <?= htmlspecialchars($row["cep"]) ?>
                                            </p>
                                        <?php else: ?>
                                            <p><strong>Endereço:</strong> Não cadastrado</p>
                                        <?php endif; ?>

                                        <?php if (!empty($row["observacoes"])): ?>
                                            <p><strong>Observações:</strong> <?= htmlspecialchars($row["observacoes"]) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Nenhum cliente encontrado.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para selecionar o tipo de cliente -->
    <div class="modal fade" id="novoClienteModal" tabindex="-1" aria-labelledby="novoClienteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="novoClienteModalLabel">Cadastrar Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Selecione o tipo de cliente que deseja cadastrar:</p>
                    <div class="d-flex justify-content-between">
                        <a href="cadastrar_cliente_pf.php" class="btn btn-success">
                            <i class="fas fa-user"></i> Pessoa Física
                        </a>
                        <a href="cadastrar_cliente_pj.php" class="btn btn-warning text-white">
                            <i class="fas fa-building"></i> Pessoa Jurídica
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmação -->
    <div class="modal fade" id="confirmDeleteModal<?= $row['id_cliente'] ?>" tabindex="-1" aria-labelledby="confirmDeleteModalLabel<?= $row['id_cliente'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel<?= $row['id_cliente'] ?>">Confirmar Deleção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Você tem certeza de que deseja excluir este cliente? Esta ação não pode ser desfeita.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="excluir_cliente_conn.php" method="POST" class="d-inline">
                        <input type="hidden" name="id_cliente" value="<?= $row['id_cliente'] ?>">
                        <input type="hidden" name="tipo_cliente" value="<?= $row['tipo_pessoa'] ?>">
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para alternar informações adicionais
        function toggleAdditionalInfo(button) {
            const cardBody = button.closest('.card-body');
            const additionalInfo = cardBody.querySelector('.additional-info');

            if (additionalInfo.style.display === 'none') {
                additionalInfo.style.display = 'block';
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Ver menos';
            } else {
                additionalInfo.style.display = 'none';
                button.innerHTML = '<i class="fas fa-eye"></i> Ver mais';
            }
        }
    </script>
</body>

</html>