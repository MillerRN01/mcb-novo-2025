<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Consultar categorias
$sql = "SELECT * FROM categorias_produto";
$resultado = $conn->query($sql);

if ($resultado === false) {
    $resultado = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Categorias</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    .container {
        margin-top: 20px;
        max-width: 600px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    body {
        background-color: #f8f9fa;
    }

    h1 {
        color: #343a40;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
</style>

<body>
    <div class="container mt-5 shadow-lg p-4 bg-white rounded">

        <div class="">
            <h1>Gerenciamento de Categorias</h1>
        </div>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <!-- Formulário para adicionar nova categoria -->
        <form method="POST" action="cadastro_categoria_conn.php" class="mb-4">
            <div class="input-group mb-3">
                <input type="text" name="categoria" class="form-control" placeholder="Nome da Categoria" required>
                <button class="btn btn-primary" type="submit" name="add_category">Adicionar</button>
            </div>
        </form>

        <!-- Tabela de Categorias -->
        <div class="table-responsive mt-3">
            <table class="table table-hover table-bordered rounded">
                <thead>
                    <tr>
                        <th style="width: 100px; text-align: center;">Categoria</th>
                        <th style="width: 100px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($resultado->num_rows > 0) {
                        while ($row = $resultado->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["nome"]) . "</td>";
                            echo "<td>
                        <button class='btn btn-warning btn-sm text-white' onclick='openEditModal(" . $row["id_categoria"] . ", \"" . htmlspecialchars($row["nome"]) . "\")' style='width: 60px; text-align: center;'>Editar</button>
                        <button class='btn btn-danger btn-sm' onclick='openDeleteModal(" . $row["id_categoria"] . ")' style='width: 60px; text-align: center;'>Excluir</button>
                      </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>Nenhuma categoria encontrada</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <a href="cadastro_produto.php" class="btn btn-danger btn-sm">Cancelar</a>
    </div>

    <!-- Modal para Editar Categoria -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="editar_categoria_conn.php" >
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label for="editar_categoria" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="editar_categoria" name="categoria" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Excluir Categoria -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Excluir Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir esta categoria?</p>
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="POST" action="excluir_categoria_conn.php" >
                        <input type="hidden" name="delete_id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(id, nome) {
            document.getElementById('edit_id').value = id;
            document.getElementById('editar_categoria').value = nome;
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }

        function openDeleteModal(id) {
            document.getElementById('delete_id').value = id;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>

</html>