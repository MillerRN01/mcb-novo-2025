<?php
// Simular dados - na prática você buscaria do banco
$clientesRecentes = [
    ['nome' => 'Ana Carolina Silva', 'data' => '2023-06-15 14:30', 'total' => 1250.00],
    ['nome' => 'Carlos Eduardo Oliveira', 'data' => '2023-06-14 18:45', 'total' => 899.00],
    ['nome' => 'Mariana Santos', 'data' => '2023-06-14 11:20', 'total' => 3299.90],
    ['nome' => 'João Pedro Costa', 'data' => '2023-06-13 16:10', 'total' => 199.90],
    ['nome' => 'Luiza Fernandes', 'data' => '2023-06-12 09:15', 'total' => 599.00]
];

function formatarData($data) {
    $date = new DateTime($data);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->d > 0) {
        return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    } else {
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    }
}
?>

<div class="clientes-recentes-widget">
    <h5 class="mb-3">Últimos Clientes Cadastrados</h5>
    
    <div class="list-group">
        <?php foreach ($clientesRecentes as $cliente): ?>
        <div class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?= htmlspecialchars($cliente['nome']) ?></h6>
                <small class="text-muted"><?= formatarData($cliente['data']) ?></small>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small>Primeira compra</small>
                <span class="badge bg-success rounded-pill">
                    R$ <?= number_format($cliente['total'], 2, ',', '.') ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-end mt-2">
        <a href="clientes.php" class="btn btn-sm btn-outline-primary">
            Ver todos <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<style>
.clientes-recentes-widget .list-group-item {
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 0.5rem !important;
}
.clientes-recentes-widget h6 {
    font-size: 0.9rem;
}
</style>