<?php
// Simular dados - na prática você buscaria do banco
$topProdutos = [
    ['nome' => 'Smartphone Premium', 'vendas' => 42, 'valor' => 1250.00],
    ['nome' => 'Notebook Ultrafino', 'vendas' => 28, 'valor' => 3299.90],
    ['nome' => 'Fone Bluetooth', 'vendas' => 65, 'valor' => 199.90],
    ['nome' => 'Smartwatch', 'vendas' => 37, 'valor' => 599.00],
    ['nome' => 'Tablet 10"', 'vendas' => 23, 'valor' => 899.00]
];
?>

<div class="top-produtos-widget">
    <h5 class="mb-3">Top 5 Produtos Mais Vendidos</h5>
    
    <div class="list-group">
        <?php foreach ($topProdutos as $produto): ?>
        <div class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?= htmlspecialchars($produto['nome']) ?></h6>
                <small class="text-muted"><?= $produto['vendas'] ?> vendas</small>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small>R$ <?= number_format($produto['valor'], 2, ',', '.') ?></small>
                <span class="badge bg-primary rounded-pill">
                    R$ <?= number_format($produto['vendas'] * $produto['valor'], 2, ',', '.') ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.top-produtos-widget .list-group-item {
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 0.5rem !important;
}
.top-produtos-widget h6 {
    font-size: 0.9rem;
}
</style>