<?php

// Determina a página atual
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Mapeia páginas para seus títulos
$nome_da_pagina = [
    'cadastro_produto.php' => 'Produtos e Serviços',
    'cadastro_cliente.php' => 'Clientes',
    'cadastro_funcionario.php' => 'Cadastro de Funcionario',
    'cadastro_fornecedores.php' => 'Fornecedor',
    'consulta_vendas.php' => 'Consulta Vendas',
    'estoque.php' => 'Consultar estoque',
    'caixa.php' => 'Controle de caixa',
    'fiado.php' => 'Fiado',
    'financeiro.php' => 'Financeiro',
    'relatorios.php' => 'Relatórios',
    'relatorios_consolidados.php' => 'Relatórios consolidados',
    'configuracoes.php' => 'Configurações',
    'ajuda.php' => 'Ajuda'
];
?>
<style>
    /* Estilos do menu lateral */
    .sidebar {
        position: fixed;
        top: 0;
        left: -300px;
        width: 300px;
        height: 100vh;
        background: linear-gradient(145deg, #2193b0, #6dd5ed);
        box-shadow: 4px 0px 10px rgba(0, 0, 0, 0.2);
        border-radius: 0 10px 10px 0;
        transition: all 0.4s ease;
        overflow-y: auto;
    }

    .sidebar.show {
        left: 0;
    }

    /* Header do menu */
    .sidebar-header {
        background-color: rgba(0, 0, 0, 0.1);
        padding: 20px;
        color: white;
        font-weight: bold;
        font-size: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Lista de links */
    .sidebar-list a {
        color: #fff;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        text-decoration: none;
        font-size: 16px;
        transition: all 0.3s ease;
        border-radius: 8px;
        /* Links arredondados */
        margin: 10px 10px;
    }

    .sidebar-list a i {
        margin-right: 15px;
        font-size: 18px;
        transition: transform 0.3s ease, color 0.3s ease;
    }

    /* Hover nos links */
    .sidebar-list a:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }

    .sidebar-list a:hover i {
        color: #ffeb3b;
    }

    /* Ícones customizados */
    .sidebar-list i {
        transition: transform 0.3s ease;
    }

    .sidebar-list a:hover i {
        transform: rotate(20deg);
    }

    /* Estilo do conteúdo principal */
    .content {
        transition: margin-left 0.4s ease;
        padding: 20px;
    }

    .content.shift {
        margin-left: 300px;
    }

    /* Estilo do Navbar */
    .navbar {
        background: linear-gradient(90deg, #2193b0, #6dd5ed);
    }

    .navbar-toggler {
        border: none;
        background: none;
    }

    .navbar-brand {
        color: white !important;
        font-size: 1.5rem;
        font-weight: bold;
    }

    /*barra de rola*/
    .sidebar::-webkit-scrollbar {
        width: 8px;
        /* Largura da barra de rolagem */
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.5);
        /* Cor do "polegar" (a parte que você arrasta) */
        border-radius: 4px;
        /* Bordas arredondadas */
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.8);
        /* Cor ao passar o mouse */
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.2);
        /* Fundo da barra de rolagem */
        border-radius: 4px;
    }

    .navbar-toggler {
        display: block !important;
        /* Força a exibição do botão */
        border: none;
        background: none;
        cursor: pointer;
        /* Adiciona o ponteiro ao passar o mouse */
    }

    .navbar-toggler i {
        color: white;
        font-size: 1.8rem;
        /* Ajuste o tamanho do ícone */
        transition: transform 0.3s ease;
    }

    /* Efeito de hover no botão */
    .navbar-toggler:hover i {
        transform: scale(1.2);
        /* Aumenta levemente o ícone */
    }

    .sidebar {
        position: fixed;
        /* ou 'absolute', dependendo do seu layout */
        z-index: 1000;
        /* Um valor alto para garantir que fique acima de outros elementos */
        /* Adicione outras propriedades de estilo conforme necessário */
    }
</style>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" id="menuToggle"><i
                class="bi bi-list text-white fs-2"></i></button>
        <span class="navbar-brand ms-2">Meu Comércio de Bolso</span>
    </div>
</nav>
<div class="sidebar p-3" id="sidebar">
    <div class="card mb-3" style="width: 100%;">
        <div class="card-body text-center">
            <img src="<?php echo $foto ? $foto : 'https://via.placeholder.com/100'; ?>"
                class="card-img-top rounded-circle mb-2" alt="Foto do Usuário"
                style="width: 80px; height: 80px; object-fit: cover;">
            <h5 class="card-title"><?php echo htmlspecialchars($usuario); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($email); ?></p>
        </div>
    </div>

    <!-- Seção Cadastro -->
    <div class="sidebar-header">
        <h5 class="mb-0"><i class="bi bi-person"></i> Cadastro</h5>
    </div>
    <ul class="sidebar-list list-unstyled">
        <?php if ($pagina_atual != 'cadastro_produto.php'): ?>
            <li><a href="cadastro_produto.php"><i class="bi bi-box"></i> Produtos e Serviços</a></li>
        <?php endif; ?>

        <?php if ($pagina_atual != 'cadastro_cliente.php'): ?>
            <li><a href="cadastro_cliente.php"><i class="bi bi-person-check"></i> Clientes</a></li>
        <?php endif; ?>

        <?php if ($pagina_atual != 'cadastro_funcionario.php'): ?>
            <li><a href="cadastro_funcionario.php"><i class="bi bi-person-badge"></i> Cadastro de Funcionario</a></li>
        <?php endif; ?>

        <?php if ($pagina_atual != 'cadastro_fornecedores.php'): ?>
            <li><a href="cadastro_fornecedores.php"><i class="bi bi-truck"></i> Fornecedor</a></li>
        <?php endif; ?>
    </ul>

    <?php if ($dante === 'admin'): ?>
        <!-- Seção Gestão (adm) -->
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Gestão</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <?php if ($pagina_atual != 'consulta_vendas.php'): ?>
                <li><a href="consulta_vendas.php"><i class="bi bi-search"></i> Consulta Vendas</a></li>
            <?php endif; ?>

            <?php if ($pagina_atual != 'estoque.php'): ?>
                <li><a href="estoque.php"><i class="bi bi-box"></i> Consultar estoque</a></li>
            <?php endif; ?>

            <?php if ($pagina_atual != 'caixa.php'): ?>
                <li><a href="caixa.php"><i class="bi bi-cash-stack"></i> Controle de caixa</a></li>
            <?php endif; ?>

            <?php if ($pagina_atual != 'fiado.php'): ?>
                <li><a href="fiado.php"><i class="bi bi-credit-card"></i> Fiado</a></li>
            <?php endif; ?>

            <?php if ($pagina_atual != 'financeiro.php'): ?>
                <li><a href="financeiro.php"><i class="bi bi-wallet"></i> Financeiro</a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <?php if ($dante === 'admin'): ?>
        <!-- Seção Relatório -->
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Relatório</h5>
        </div>
        <ul class="sidebar-list list-unstyled">
            <?php if ($pagina_atual != 'relatorios.php'): ?>
                <li><a href="relatorios.php"><i class="bi bi-bar-chart"></i> Relatórios</a></li>
            <?php endif; ?>

            <?php if ($pagina_atual != 'relatorios_consolidados.php'): ?>
                <li><a href="relatorios_consolidados.php"><i class="bi bi-pie-chart"></i> Relatórios consolidados</a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <!-- Seção Configuração -->
    <div class="sidebar-header">
        <h5 class="mb-0"><i class="bi bi-gear"></i> Preferencias</h5>
    </div>
    <ul class="sidebar-list list-unstyled">
        <?php if ($pagina_atual != 'configuracoes.php'): ?>
            <li><a href="configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
        <?php endif; ?>

        <?php if ($pagina_atual != 'ajuda.php'): ?>
            <li><a href="ajuda.php"><i class="bi bi-question-circle"></i> Ajuda</a></li>
        <?php endif; ?>

        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
</div>

<script>
    // Script para controlar a exibição do menu lateral
    const toggleButton = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const content = document.querySelector('.content');

    // Abrir ou fechar o menu lateral
    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        content.classList.toggle('shift');
    });

    // Fechar o menu lateral quando clicar fora dele
    document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
            sidebar.classList.remove('show');
            content.classList.remove('shift');
        }
    });
</script>