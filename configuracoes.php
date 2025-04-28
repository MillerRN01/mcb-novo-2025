<?php
require_once 'conexao_pdo.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Ativar exibição de erros (apenas para desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifique conexão com o banco
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Verificação de sessão e permissão
if (!isset($_SESSION['dante']) || $_SESSION['dante'] != 'admin') {
    header("Location: login.php");
    exit();
}

// ID do usuário logado - Versão melhorada
$usuario_id = $_SESSION['id_login'] ?? null;
if (!$usuario_id) {
    try {
        $stmt = $pdo->prepare("SELECT id_login FROM login WHERE usuario = ?");
        if ($stmt->execute([$_SESSION['usuario']])) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $usuario_id = $user['id_login'] ?? null;
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar ID do usuário: " . $e->getMessage());
    }
    
    if (!$usuario_id) {
        header("Location: login.php");
        exit();
    }
}

// Ações do CRUD
$acao = $_GET['acao'] ?? 'empresa';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($acao == 'salvar_empresa') {
        try {
            $pdo->beginTransaction();
            
            $nome_empresa = $_POST['nome_empresa'] ?? '';
            $cnpj = $_POST['cnpj'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';
            
            // Upload da logo - Versão melhorada
            $logo_url = $config_empresa['logo_url'] ?? null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $nome_arquivo = 'logo_' . time() . '.' . $ext;
                $diretorio = 'assets/uploads/logos/';
                
                if (!is_dir($diretorio)) {
                    mkdir($diretorio, 0755, true);
                }
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $diretorio . $nome_arquivo)) {
                    $logo_url = $diretorio . $nome_arquivo;
                }
            }
            
            // Verifica se já existe configuração - Versão melhorada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM config_empresa");
            $stmt->execute();
            $existe = $stmt->fetchColumn() > 0;
            
            if ($existe) {
                $stmt = $pdo->prepare("UPDATE config_empresa SET 
                    nome_empresa = ?, cnpj = ?, telefone = ?, email = ?, logo_url = ?
                    WHERE id_config = 1");
                $stmt->execute([$nome_empresa, $cnpj, $telefone, $email, $logo_url]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO config_empresa 
                    (nome_empresa, cnpj, telefone, email, logo_url)
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome_empresa, $cnpj, $telefone, $email, $logo_url]);
            }
            
            $pdo->commit();
            $_SESSION['mensagem'] = "Configurações salvas com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: configuracoes.php?acao=empresa");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao salvar: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
    // [Demais ações POST podem ser mantidas como estão no código original]
}

// Função auxiliar para executar consultas com tratamento de erros - Versão melhorada
function executarConsulta($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt === false) return false;
        
        if ($stmt->execute($params) === false) return false;
        
        return $stmt;
    } catch (PDOException $e) {
        error_log("PDOException: " . $e->getMessage());
        return false;
    }
}

// Carrega todas as configurações com tratamento robusto
$config_empresa = [];
$config_email = [];
$config_backup = [];
$preferencias = [];
$tipos_notificacao = [];
$config_notificacoes = [];

// Carrega configurações da empresa
$stmt = executarConsulta($pdo, "SELECT * FROM config_empresa LIMIT 1");
if ($stmt !== false) {
    $config_empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Carrega configurações de e-mail
$stmt = executarConsulta($pdo, "SELECT * FROM config_email LIMIT 1");
if ($stmt !== false) {
    $config_email = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Carrega configurações de backup
$stmt = executarConsulta($pdo, "SELECT * FROM config_backup LIMIT 1");
if ($stmt !== false) {
    $config_backup = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Carrega preferências do usuário
$stmt = executarConsulta($pdo, "SELECT * FROM config_preferencias WHERE usuario_id = ?", [$usuario_id]);
if ($stmt !== false) {
    $preferencias = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Carrega tipos de notificação
$stmt = executarConsulta($pdo, "SELECT * FROM tipos_notificacao");
if ($stmt !== false) {
    $tipos_notificacao = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Carrega configurações de notificação do usuário
$stmt = executarConsulta($pdo, "SELECT * FROM config_notificacoes WHERE usuario_id = ?", [$usuario_id]);
if ($stmt !== false) {
    $config_notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Organiza configurações de notificação
$notificacoes_config = [];
foreach ($config_notificacoes as $config) {
    $notificacoes_config[$config['tipo_notificacao_id']] = $config;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Configurações</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .config-section {
            display: none;
        }
        .config-section.active {
            display: block;
        }
        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            margin-bottom: 15px;
        }
        .tema-claro {
            background-color: #ffffff;
            color: #212529;
        }
        .tema-escuro {
            background-color: #343a40;
            color: #f8f9fa;
        }
        .tema-azul {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        .tema-verde {
            background-color: #e8f5e9;
            color: #1b5e20;
        }
        .fonte-pequena {
            font-size: 0.9rem;
        }
        .fonte-media {
            font-size: 1rem;
        }
        .fonte-grande {
            font-size: 1.1rem;
        }
    </style>
</head>

<body class="<?= $_SESSION['tema'] ?? 'tema-claro' ?> <?= 'fonte-' . ($_SESSION['tamanho_fonte'] ?? 'media') ?>">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-gear"></i> Configurações</h2>
        
        <?php include 'mensagem.php'; ?>
        
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action <?= $acao == 'empresa' ? 'active' : '' ?>" 
                       onclick="mostrarSecao('empresa')">
                        <i class="bi bi-building"></i> Empresa
                    </a>
                    <a href="#" class="list-group-item list-group-item-action <?= $acao == 'email' ? 'active' : '' ?>" 
                       onclick="mostrarSecao('email')">
                        <i class="bi bi-envelope"></i> Config. E-mail
                    </a>
                    <a href="#" class="list-group-item list-group-item-action <?= $acao == 'backup' ? 'active' : '' ?>" 
                       onclick="mostrarSecao('backup')">
                        <i class="bi bi-hdd"></i> Backup
                    </a>
                    <a href="#" class="list-group-item list-group-item-action <?= $acao == 'preferencias' ? 'active' : '' ?>" 
                       onclick="mostrarSecao('preferencias')">
                        <i class="bi bi-person-circle"></i> Preferências
                    </a>
                    <a href="#" class="list-group-item list-group-item-action <?= $acao == 'notificacoes' ? 'active' : '' ?>" 
                       onclick="mostrarSecao('notificacoes')">
                        <i class="bi bi-bell"></i> Notificações
                    </a>
                    <a href="#" class="list-group-item list-group-item-action <?= $acao == 'seguranca' ? 'active' : '' ?>" 
                       onclick="mostrarSecao('seguranca')">
                        <i class="bi bi-shield-lock"></i> Segurança
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Seção Empresa -->
                <div id="empresa" class="config-section <?= $acao == 'empresa' ? 'active' : '' ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-building"></i> Configurações da Empresa</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="configuracoes.php?acao=salvar_empresa" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nome_empresa" class="form-label">Nome da Empresa</label>
                                        <input type="text" class="form-control" id="nome_empresa" name="nome_empresa" 
                                               value="<?= htmlspecialchars($config_empresa['nome_empresa'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cnpj" class="form-label">CNPJ</label>
                                        <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                               value="<?= htmlspecialchars($config_empresa['cnpj'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="text" class="form-control" id="telefone" name="telefone" 
                                               value="<?= htmlspecialchars($config_empresa['telefone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($config_empresa['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="logo" class="form-label">Logo da Empresa</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                        <?php if (!empty($config_empresa['logo_url'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= htmlspecialchars($config_empresa['logo_url']) ?>" class="logo-preview" id="logo-preview">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Seção E-mail -->
                <div id="email" class="config-section <?= $acao == 'email' ? 'active' : '' ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-envelope"></i> Configurações de E-mail</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="configuracoes.php?acao=salvar_email">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="servidor_smtp" class="form-label">Servidor SMTP</label>
                                        <input type="text" class="form-control" id="servidor_smtp" name="servidor_smtp" 
                                               value="<?= htmlspecialchars($config_email['servidor_smtp'] ?? 'smtp.example.com') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="porta" class="form-label">Porta</label>
                                        <input type="number" class="form-control" id="porta" name="porta" 
                                               value="<?= htmlspecialchars($config_email['porta'] ?? '587') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($config_email['email'] ?? 'seu@email.com') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="senha" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="senha" name="senha" 
                                               value="<?= htmlspecialchars($config_email['senha'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="ssl" name="ssl" 
                                           <?= ($config_email['ssl'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ssl">Usar SSL/TLS</label>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Seção Backup -->
                <div id="backup" class="config-section <?= $acao == 'backup' ? 'active' : '' ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-hdd"></i> Configurações de Backup</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="configuracoes.php?acao=salvar_backup">
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="backup_auto" name="backup_auto" 
                                           <?= ($config_backup['backup_auto'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="backup_auto">Backup Automático</label>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="frequencia" class="form-label">Frequência</label>
                                        <select class="form-select" id="frequencia" name="frequencia" <?= ($config_backup['backup_auto'] ?? 0) ? '' : 'disabled' ?>>
                                            <option value="diario" <?= ($config_backup['frequencia'] ?? 'diario') == 'diario' ? 'selected' : '' ?>>Diário</option>
                                            <option value="semanal" <?= ($config_backup['frequencia'] ?? '') == 'semanal' ? 'selected' : '' ?>>Semanal</option>
                                            <option value="mensal" <?= ($config_backup['frequencia'] ?? '') == 'mensal' ? 'selected' : '' ?>>Mensal</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="hora_backup" class="form-label">Hora do Backup</label>
                                        <input type="time" class="form-control" id="hora_backup" name="hora_backup" 
                                               value="<?= htmlspecialchars($config_backup['hora_backup'] ?? '02:00:00') ?>" <?= ($config_backup['backup_auto'] ?? 0) ? '' : 'disabled' ?>>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="destino_backup" class="form-label">Diretório de Backup</label>
                                    <input type="text" class="form-control" id="destino_backup" name="destino_backup" 
                                           value="<?= htmlspecialchars($config_backup['destino_backup'] ?? '') ?>">
                                </div>
                                
                                <?php if (!empty($config_backup['ultimo_backup'])): ?>
                                <div class="alert alert-info">
                                    Último backup: <?= date('d/m/Y H:i', strtotime($config_backup['ultimo_backup'])) ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-success" onclick="executarBackup()">
                                        <i class="bi bi-download"></i> Executar Backup Agora
                                    </button>
                                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Seção Preferências -->
                <div id="preferencias" class="config-section <?= $acao == 'preferencias' ? 'active' : '' ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-person-circle"></i> Preferências Pessoais</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="configuracoes.php?acao=salvar_preferencias">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tema" class="form-label">Tema</label>
                                        <select class="form-select" id="tema" name="tema">
                                            <option value="claro" <?= ($preferencias['tema'] ?? 'claro') == 'claro' ? 'selected' : '' ?>>Claro</option>
                                            <option value="escuro" <?= ($preferencias['tema'] ?? '') == 'escuro' ? 'selected' : '' ?>>Escuro</option>
                                            <option value="azul" <?= ($preferencias['tema'] ?? '') == 'azul' ? 'selected' : '' ?>>Azul</option>
                                            <option value="verde" <?= ($preferencias['tema'] ?? '') == 'verde' ? 'selected' : '' ?>>Verde</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tamanho_fonte" class="form-label">Tamanho da Fonte</label>
                                        <select class="form-select" id="tamanho_fonte" name="tamanho_fonte">
                                            <option value="pequeno" <?= ($preferencias['tamanho_fonte'] ?? 'medio') == 'pequeno' ? 'selected' : '' ?>>Pequeno</option>
                                            <option value="medio" <?= ($preferencias['tamanho_fonte'] ?? '') == 'medio' ? 'selected' : '' ?>>Médio</option>
                                            <option value="grande" <?= ($preferencias['tamanho_fonte'] ?? '') == 'grande' ? 'selected' : '' ?>>Grande</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="menu_reduzido" name="menu_reduzido" 
                                           <?= ($preferencias['menu_reduzido'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="menu_reduzido">Menu Reduzido</label>
                                </div>
                                
                                <h5 class="mt-4 mb-3">Preferências de Notificação</h5>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notificacoes_sistema" name="notificacoes_sistema" 
                                                   <?= ($preferencias['notificacoes_sistema'] ?? 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notificacoes_sistema">Notificações no Sistema</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notificacoes_email" name="notificacoes_email" 
                                                   <?= ($preferencias['notificacoes_email'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notificacoes_email">Notificações por E-mail</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notificacoes_push" name="notificacoes_push" 
                                                   <?= ($preferencias['notificacoes_push'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notificacoes_push">Notificações Push</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Salvar Preferências</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Seção Notificações -->
                <div id="notificacoes" class="config-section <?= $acao == 'notificacoes' ? 'active' : '' ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-bell"></i> Configurações de Notificações</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="configuracoes.php?acao=salvar_notificacoes">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Tipo de Notificação</th>
                                            <th>Ativar</th>
                                            <th>Sistema</th>
                                            <th>E-mail</th>
                                            <th>Push</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tipos_notificacao as $tipo): 
                                            $config = $notificacoes_config[$tipo['id_tipo']] ?? [
                                                'ativo' => $tipo['ativo_padrao'],
                                                'sistema' => true,
                                                'email' => false,
                                                'push' => false
                                            ];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($tipo['nome']) ?></strong>
                                                <p class="small text-muted mb-0"><?= htmlspecialchars($tipo['descricao']) ?></p>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="notificacoes[<?= $tipo['id_tipo'] ?>][ativo]"
                                                           <?= $config['ativo'] ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="notificacoes[<?= $tipo['id_tipo'] ?>][sistema]"
                                                           <?= $config['sistema'] ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="notificacoes[<?= $tipo['id_tipo'] ?>][email]"
                                                           <?= $config['email'] ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="notificacoes[<?= $tipo['id_tipo'] ?>][push]"
                                                           <?= $config['push'] ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Seção Segurança -->
                <div id="seguranca" class="config-section <?= $acao == 'seguranca' ? 'active' : '' ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-shield-lock"></i> Segurança</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="configuracoes.php?acao=alterar_senha">
                                <div class="mb-3">
                                    <label for="senha_atual" class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Alterar Senha</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    // Mostra a seção selecionada ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        // Obtém a ação da URL - Versão melhorada
        const urlParams = new URLSearchParams(window.location.search);
        const acao = urlParams.get('acao') || 'empresa';
        
        mostrarSecao(acao);
        
        // Configura o evento de change para o backup automático
        const backupAuto = document.getElementById('backup_auto');
        if (backupAuto) {
            backupAuto.addEventListener('change', function() {
                document.getElementById('frequencia').disabled = !this.checked;
                document.getElementById('hora_backup').disabled = !this.checked;
            });
            
            // Configura estado inicial
            document.getElementById('frequencia').disabled = !backupAuto.checked;
            document.getElementById('hora_backup').disabled = !backupAuto.checked;
        }
    });

    // Mostra a seção selecionada - Versão melhorada
    function mostrarSecao(secao) {
        // Esconde todas as seções
        document.querySelectorAll('.config-section').forEach(function(el) {
            el.classList.remove('active');
        });
        
        // Mostra a seção selecionada
        const secaoElement = document.getElementById(secao);
        if (secaoElement) {
            secaoElement.classList.add('active');
        }
        
        // Atualiza o item ativo no menu
        document.querySelectorAll('.list-group-item').forEach(function(el) {
            el.classList.remove('active');
        });
        
        const itemMenu = document.querySelector(`.list-group-item[onclick*="${secao}"]`);
        if (itemMenu) {
            itemMenu.classList.add('active');
        }
        
        // Atualiza a URL sem recarregar a página - Versão melhorada
        history.pushState(null, null, `configuracoes.php?acao=${secao}`);
    }

    // Executa backup manual
    function executarBackup() {
        if (confirm('Deseja executar o backup agora?')) {
            window.location.href = 'configuracoes.php?acao=executar_backup';
        }
    }
    
    // Pré-visualização da logo - Versão melhorada
    document.getElementById('logo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let preview = document.getElementById('logo-preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.id = 'logo-preview';
                    preview.className = 'logo-preview mt-2';
                    document.querySelector('#logo').parentNode.appendChild(preview);
                }
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Aplica tema e tamanho de fonte imediatamente ao alterar
    document.getElementById('tema')?.addEventListener('change', function() {
        document.body.className = document.body.className.replace(/\btema-\w+/g, '') + ' tema-' + this.value;
    });
    
    document.getElementById('tamanho_fonte')?.addEventListener('change', function() {
        document.body.className = document.body.className.replace(/\bfonte-\w+/g, '') + ' fonte-' + this.value;
    });
</script>
</body>
</html>