<?php
// Simular dados - na prática você buscaria do banco
$atividades = [
    ['tipo' => 'venda', 'descricao' => 'Pedido #' . rand(1000, 9999), 'valor' => rand(100, 1000), 'tempo' => rand(1, 60) . ' minutos atrás'],
    ['tipo' => 'cliente', 'descricao' => 'Novo cliente cadastrado', 'valor' => null, 'tempo' => rand(1, 24) . ' horas atrás'],
    ['tipo' => 'produto', 'descricao' => 'Produto atualizado', 'valor' => null, 'tempo' => rand(1, 3) . ' dias atrás']
];
?>

<div class="recent-activity">
    <?php foreach ($atividades as $ativ): ?>
    <div class="activity-item">
        <div class="fw-bold">
            <?= $ativ['tipo'] == 'venda' ? 'Nova venda' : ($ativ['tipo'] == 'cliente' ? 'Cliente cadastrado' : 'Produto atualizado') ?>
        </div>
        <div class="small text-muted"><?= $ativ['descricao'] ?></div>
        <?php if ($ativ['valor']): ?>
        <div class="small text-muted">Valor: R$ <?= number_format($ativ['valor'], 2, ',', '.') ?></div>
        <?php endif; ?>
        <div class="small text-muted"><?= $ativ['tempo'] ?></div>
    </div>
    <?php endforeach; ?>
</div>